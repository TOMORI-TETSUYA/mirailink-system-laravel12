<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Plans\CreateInsurancePlan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plans\StoreInsurancePlanRequest;
use App\Http\Requests\Plans\UpdateInsurancePlanRequest;
use App\Http\Requests\Plans\UpdateInsurancePlanStatusRequest;
use App\Models\InsurancePlan;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class InsurancePlanController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', InsurancePlan::class);

        $category = (string) $request->query('category', '');

        $plans = InsurancePlan::query()
            ->searchName($request->query('q'))
            ->ofCategory($category !== '' ? $category : null)
            ->when($request->boolean('active_only'), fn ($q) => $q->active())
            ->with(['currentPrice', 'prices'])
            ->ordered()
            ->get();

        return view('settings.plans.index', [
            'plans' => $plans,
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'category' => array_key_exists($category, InsurancePlan::CATEGORIES) ? $category : '',
                'active_only' => $request->boolean('active_only'),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', InsurancePlan::class);

        return view('settings.plans.create');
    }

    public function store(StoreInsurancePlanRequest $request, CreateInsurancePlan $action): RedirectResponse
    {
        $plan = $action->execute($request->validated(), $request->user()->id);

        return redirect()
            ->route('settings.plans.index')
            ->with('status', "プラン「{$plan->plan_name}」を登録しました。");
    }

    public function edit(InsurancePlan $plan): View
    {
        $this->authorize('update', $plan);

        $plan->load(['prices.creator', 'currentPrice']);

        return view('settings.plans.edit', ['plan' => $plan]);
    }

    public function update(UpdateInsurancePlanRequest $request, InsurancePlan $plan): RedirectResponse
    {
        $data = $request->validated();

        if ($data['status'] === InsurancePlan::STATUS_ACTIVE && ! $plan->hasCurrentOrFuturePrice()) {
            throw ValidationException::withMessages([
                'status' => '有効化するには、適用中または将来適用される金額が1件以上必要です。',
            ]);
        }

        DB::transaction(function () use ($plan, $data, $request): void {
            $plan->fill([
                'plan_name' => $data['plan_name'],
                'category' => $data['category'],
                'plan_type' => $data['plan_type'] ?? null,
                'insurer_name' => $data['insurer_name'] ?? null,
                'billing_cycle' => $data['billing_cycle'],
                'display_order' => $data['display_order'] ?? 0,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'updated_by' => $request->user()->id,
            ]);
            $changed = array_keys($plan->getDirty());
            $plan->save();

            $this->auditLog->record(
                userId: $request->user()->id,
                action: 'insurance_plan.updated',
                targetType: InsurancePlan::class,
                targetId: $plan->id,
                changedFields: $changed,
            );
        });

        return redirect()
            ->route('settings.plans.index')
            ->with('status', "プラン「{$plan->plan_name}」を更新しました。");
    }

    /** 有効・無効切替（仕様 7.5）。有効化には適用中または将来の価格が必要です。 */
    public function updateStatus(UpdateInsurancePlanStatusRequest $request, InsurancePlan $plan): RedirectResponse
    {
        $status = $request->validated('status');

        if ($status === InsurancePlan::STATUS_ACTIVE && ! $plan->hasCurrentOrFuturePrice()) {
            return back()->withErrors([
                'status' => "「{$plan->plan_name}」を有効化するには、適用中または将来適用される金額が1件以上必要です。",
            ]);
        }

        DB::transaction(function () use ($plan, $status, $request): void {
            $plan->forceFill([
                'status' => $status,
                'updated_by' => $request->user()->id,
            ])->save();

            $this->auditLog->record(
                userId: $request->user()->id,
                action: 'insurance_plan.status_changed:'.$status,
                targetType: InsurancePlan::class,
                targetId: $plan->id,
                changedFields: ['status'],
            );
        });

        return redirect()
            ->route('settings.plans.index')
            ->with('status', "プラン「{$plan->plan_name}」を{$plan->status_label}にしました。");
    }

    /** 論理削除。既存契約はスナップショットを保持するため影響を受けません。 */
    public function destroy(Request $request, InsurancePlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        DB::transaction(function () use ($plan, $request): void {
            $plan->forceFill([
                'status' => InsurancePlan::STATUS_DELETED,
                'updated_by' => $request->user()->id,
            ])->save();
            $plan->delete();

            $this->auditLog->record(
                userId: $request->user()->id,
                action: 'insurance_plan.soft_deleted',
                targetType: InsurancePlan::class,
                targetId: $plan->id,
                changedFields: ['status', 'deleted_at'],
            );
        });

        return redirect()
            ->route('settings.plans.index')
            ->with('status', "プラン「{$plan->plan_name}」を削除しました。");
    }
}
