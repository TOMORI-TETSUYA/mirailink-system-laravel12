<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customers\StoreCustomerIntentionRequest;
use App\Models\Customer;
use App\Models\CustomerIntention;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final class CustomerIntentionController extends Controller
{
    public function store(
        StoreCustomerIntentionRequest $request,
        Customer $customer,
        AuditLogService $auditLog
    ): RedirectResponse {
        $data = $request->validated();
        $userId = $request->user()->id;
        $confirmed = (bool) ($data['confirmed'] ?? false);

        DB::transaction(function () use ($customer, $data, $userId, $confirmed, $auditLog): void {
            $intention = $customer->intentions()->create([
                'created_by' => $userId,
                'initial_intention' => $data['initial_intention'],
                'final_intention' => $data['final_intention'] ?? null,
                'protection_purpose' => $data['protection_purpose'] ?? null,
                'budget' => $data['budget'] ?? null,
                'desired_period' => $data['desired_period'] ?? null,
                'proposed_reason' => $data['proposed_reason'] ?? null,
                'differences' => $data['differences'] ?? null,
                'confirmed_at' => $confirmed ? now() : null,
                'confirmation_method' => $confirmed ? ($data['confirmation_method'] ?? null) : null,
            ]);

            $auditLog->record(
                userId: $userId,
                action: 'customer_intention.created',
                targetType: CustomerIntention::class,
                targetId: $intention->id,
                changedFields: array_keys($data),
            );
        });

        return redirect()
            ->route('customers.show', ['customer' => $customer, 'tab' => 'intention'])
            ->with('status', '顧客意向を登録しました。');
    }
}
