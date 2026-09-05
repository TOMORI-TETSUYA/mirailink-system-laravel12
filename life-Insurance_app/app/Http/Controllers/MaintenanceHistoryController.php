<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customers\StoreMaintenanceHistoryRequest;
use App\Models\Customer;
use App\Models\MaintenanceHistory;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final class MaintenanceHistoryController extends Controller
{
    public function store(
        StoreMaintenanceHistoryRequest $request,
        Customer $customer,
        AuditLogService $auditLog
    ): RedirectResponse {
        $data = $request->validated();
        $userId = $request->user()->id;

        DB::transaction(function () use ($customer, $data, $userId, $auditLog): void {
            $history = $customer->maintenanceHistories()->create([
                'user_id' => $userId,
                'type' => $data['type'],
                'description' => $data['description'],
                'status' => $data['status'],
                'requested_at' => $data['requested_at'],
                'completed_at' => $data['completed_at'] ?? null,
            ]);

            $auditLog->record(
                userId: $userId,
                action: 'maintenance_history.created',
                targetType: MaintenanceHistory::class,
                targetId: $history->id,
                changedFields: array_keys($data),
            );
        });

        return redirect()
            ->route('customers.show', ['customer' => $customer, 'tab' => 'maintenance'])
            ->with('status', '保全・給付履歴を登録しました。');
    }
}
