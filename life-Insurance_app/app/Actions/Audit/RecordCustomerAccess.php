<?php

namespace App\Actions\Audit;

use App\Models\Customer;
use App\Services\AuditLogService;

/** 顧客詳細の閲覧を監査ログへ記録します（誰が・いつ・どの情報を閲覧したか）。 */
final class RecordCustomerAccess
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function execute(Customer $customer, int $userId, bool $healthViewed): void
    {
        $this->auditLogService->record(
            userId: $userId,
            action: $healthViewed ? 'customer.viewed_with_health' : 'customer.viewed',
            targetType: Customer::class,
            targetId: $customer->id,
        );
    }
}
