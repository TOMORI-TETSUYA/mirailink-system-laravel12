<?php

namespace App\Actions\Plans;

use App\Models\InsurancePlan;
use App\Services\AuditLogService;
use App\Services\PlanCodeService;
use Illuminate\Support\Facades\DB;

final class CreateInsurancePlan
{
    public function __construct(
        private readonly PlanCodeService $planCodeService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function execute(array $data, int $userId): InsurancePlan
    {
        return DB::transaction(function () use ($data, $userId): InsurancePlan {
            $plan = InsurancePlan::query()->create([
                'plan_code' => $this->planCodeService->generate(),
                'plan_name' => $data['plan_name'],
                'category' => $data['category'],
                'plan_type' => $data['plan_type'] ?? null,
                'insurer_name' => $data['insurer_name'] ?? null,
                'billing_cycle' => $data['billing_cycle'],
                'status' => $data['status'],
                'display_order' => $data['display_order'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $price = $plan->prices()->create([
                'amount_yen' => $data['amount_yen'],
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'] ?? null,
                'created_by' => $userId,
            ]);

            $this->auditLogService->record(
                userId: $userId,
                action: 'insurance_plan.created',
                targetType: InsurancePlan::class,
                targetId: $plan->id,
                changedFields: [
                    'plan_name',
                    'category',
                    'plan_type',
                    'billing_cycle',
                    'status',
                    'amount_yen',
                    'effective_from',
                ],
            );

            return $plan->setRelation('currentPrice', $price);
        });
    }
}
