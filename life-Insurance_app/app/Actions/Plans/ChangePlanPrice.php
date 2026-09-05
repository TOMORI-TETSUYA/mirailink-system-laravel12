<?php

namespace App\Actions\Plans;

use App\Models\InsurancePlan;
use App\Models\InsurancePlanPrice;
use App\Services\AuditLogService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * プラン金額の変更（価格履歴の追加）。
 * 同一プランの適用期間が重複しないよう、トランザクションと行ロックで検査します（仕様 14.4）。
 */
final class ChangePlanPrice
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function execute(InsurancePlan $plan, array $data, int $userId): InsurancePlanPrice
    {
        $from = CarbonImmutable::parse($data['effective_from'])->startOfDay();
        $to = isset($data['effective_to']) && $data['effective_to'] !== null && $data['effective_to'] !== ''
            ? CarbonImmutable::parse($data['effective_to'])->startOfDay()
            : null;

        if ($to !== null && $to->lessThan($from)) {
            throw ValidationException::withMessages([
                'effective_to' => '適用終了日は適用開始日以降の日付を指定してください。',
            ]);
        }

        return DB::transaction(function () use ($plan, $data, $userId, $from, $to): InsurancePlanPrice {
            $existing = $plan->prices()->lockForUpdate()->get();

            foreach ($existing as $price) {
                $existingFrom = CarbonImmutable::parse($price->effective_from->toDateString());
                $existingTo = $price->effective_to ? CarbonImmutable::parse($price->effective_to->toDateString()) : null;

                // 終了日なしの現行価格は、新しい価格の開始日前日で自動的に閉じます。
                if ($existingTo === null && $existingFrom->lessThan($from)) {
                    $price->forceFill(['effective_to' => $from->subDay()->toDateString()])->save();

                    continue;
                }

                $overlaps = $existingFrom->lessThanOrEqualTo($to ?? $existingFrom->addYears(1000))
                    && ($existingTo === null || $existingTo->greaterThanOrEqualTo($from));

                if ($overlaps) {
                    throw ValidationException::withMessages([
                        'effective_from' => '同じプランに適用期間が重複する金額が既に登録されています。',
                    ]);
                }
            }

            $newPrice = $plan->prices()->create([
                'amount_yen' => $data['amount_yen'],
                'effective_from' => $from->toDateString(),
                'effective_to' => $to?->toDateString(),
                'created_by' => $userId,
            ]);

            $plan->forceFill(['updated_by' => $userId])->save();

            $this->auditLogService->record(
                userId: $userId,
                action: 'insurance_plan_price.changed',
                targetType: InsurancePlan::class,
                targetId: $plan->id,
                changedFields: ['amount_yen', 'effective_from', 'effective_to'],
            );

            return $newPrice;
        });
    }
}
