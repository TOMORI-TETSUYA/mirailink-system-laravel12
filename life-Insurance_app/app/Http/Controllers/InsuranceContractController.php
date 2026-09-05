<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contracts\StoreInsuranceContractRequest;
use App\Models\Customer;
use App\Models\InsuranceContract;
use App\Models\InsurancePlan;
use App\Services\AuditLogService;
use App\Services\PlanPriceResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class InsuranceContractController extends Controller
{
    private const NO_PRICE_MESSAGE = "このプランには契約日時点で有効な金額が設定されていません。\n設定画面で金額を登録してください。";

    public function __construct(
        private readonly PlanPriceResolver $priceResolver,
        private readonly AuditLogService $auditLog,
    ) {
    }

    /** 契約登録画面。契約日（GET契約日）を基準にプランごとの価格を解決して選択肢へ載せます。 */
    public function create(Request $request, Customer $customer): View
    {
        $this->authorize('update', $customer);

        $contractDate = $this->contractDateFrom($request->query('contract_date'));
        $plans = $this->selectablePlans($request->query('plan_keyword'), $contractDate);

        return view('contracts.create', [
            'customer' => $customer,
            'plans' => $plans,
            'contractDate' => $contractDate,
            'planKeyword' => (string) $request->query('plan_keyword', ''),
            'canOverride' => $request->user()->can('overrideContractPrice', InsurancePlan::class),
        ]);
    }

    public function store(StoreInsuranceContractRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $contractDate = CarbonImmutable::parse($data['contract_date']);

        /** @var InsurancePlan $plan */
        $plan = InsurancePlan::query()->findOrFail($data['insurance_plan_id']);

        if (! $plan->isSelectable()) {
            throw ValidationException::withMessages([
                'insurance_plan_id' => 'このプランは現在選択できません。',
            ]);
        }

        // ブラウザから送られた価格IDは信用せず、サーバー側で契約日時点の価格を再判定します（仕様 20.3）。
        $price = $this->priceResolver->resolve($plan, $contractDate);

        if ($price === null) {
            throw ValidationException::withMessages([
                'insurance_plan_id' => self::NO_PRICE_MESSAGE,
            ]);
        }

        $override = $user->can('overrideContractPrice', InsurancePlan::class)
            && (bool) ($data['override_price'] ?? false);
        $amount = $override ? (int) $data['override_amount_yen'] : $price->amount_yen;

        $contract = DB::transaction(function () use ($customer, $plan, $price, $data, $user, $override, $amount, $contractDate): InsuranceContract {
            $contract = $customer->contracts()->create([
                'created_by' => $user->id,
                'insurance_plan_id' => $plan->id,
                'insurance_plan_price_id' => $price->id,
                'insurer_name_snapshot' => $plan->insurer_name ?? '',
                'plan_name_snapshot' => $plan->plan_name,
                'plan_type_snapshot' => $plan->plan_type,
                'premium_amount_snapshot' => (string) $amount,
                'billing_cycle_snapshot' => $plan->billing_cycle,
                'is_price_overridden' => $override,
                'price_override_reason' => $override ? $data['price_override_reason'] : null,
                'policy_number' => $data['policy_number'] ?? null,
                'coverage' => $data['coverage'] ?? null,
                'contract_date' => $contractDate->toDateString(),
                'maturity_date' => $data['maturity_date'] ?? null,
                'status' => $data['status'],
            ]);

            $this->auditLog->record(
                userId: $user->id,
                action: 'insurance_contract.created',
                targetType: InsuranceContract::class,
                targetId: $contract->id,
                changedFields: ['insurance_plan_id', 'insurance_plan_price_id', 'contract_date', 'status'],
            );

            if ($override) {
                // 上書き前後の金額は個人情報ではないため、監査ログの変更項目へ記録します（仕様 7.6）。
                $this->auditLog->record(
                    userId: $user->id,
                    action: 'insurance_contract.price_overridden',
                    targetType: InsuranceContract::class,
                    targetId: $contract->id,
                    changedFields: [
                        'before_amount_yen:'.$price->amount_yen,
                        'after_amount_yen:'.$amount,
                        'price_override_reason',
                    ],
                );
            }

            return $contract;
        });

        return redirect()
            ->route('customers.show', ['customer' => $customer, 'tab' => 'contracts'])
            ->with('status', "契約（{$contract->plan_name_snapshot}）を登録しました。");
    }

    public function edit(Request $request, Customer $customer, InsuranceContract $contract): View
    {
        $this->authorize('update', $customer);
        abort_unless($contract->customer_id === $customer->id, 404);

        return view('contracts.edit', [
            'customer' => $customer,
            'contract' => $contract,
        ]);
    }

    /** 契約状態・証券番号・保障内容のみ更新できます。スナップショット金額は変更しません。 */
    public function update(Request $request, Customer $customer, InsuranceContract $contract): RedirectResponse
    {
        $this->authorize('update', $customer);
        abort_unless($contract->customer_id === $customer->id, 404);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(InsuranceContract::STATUSES))],
            'policy_number' => ['nullable', 'string', 'max:50'],
            'coverage' => ['nullable', 'string', 'max:1000'],
            'maturity_date' => ['nullable', 'date', 'after:'.$contract->contract_date->toDateString()],
        ], [], [
            'status' => '契約状態',
            'policy_number' => '証券番号',
            'coverage' => '保障内容',
            'maturity_date' => '満期日',
        ]);

        DB::transaction(function () use ($contract, $data, $request): void {
            $contract->fill($data);
            $changed = array_keys($contract->getDirty());
            $contract->save();

            $this->auditLog->record(
                userId: $request->user()->id,
                action: 'insurance_contract.updated',
                targetType: InsuranceContract::class,
                targetId: $contract->id,
                changedFields: $changed,
            );
        });

        return redirect()
            ->route('customers.show', ['customer' => $customer, 'tab' => 'contracts'])
            ->with('status', '契約情報を更新しました。');
    }

    private function contractDateFrom(mixed $value): CarbonImmutable
    {
        if (is_string($value) && $value !== '' && strtotime($value) !== false) {
            return CarbonImmutable::parse($value)->startOfDay();
        }

        return CarbonImmutable::today();
    }

    private function selectablePlans(mixed $keyword, CarbonImmutable $contractDate)
    {
        return InsurancePlan::query()
            ->active()
            ->searchName(is_string($keyword) ? $keyword : null)
            ->ordered()
            ->get()
            ->each(function (InsurancePlan $plan) use ($contractDate): void {
                $plan->setAttribute('resolved_price', $this->priceResolver->resolve($plan, $contractDate));
            });
    }
}
