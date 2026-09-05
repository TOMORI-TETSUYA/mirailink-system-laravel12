<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customers\StoreInteractionRequest;
use App\Models\Customer;
use App\Models\Interaction;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final class InteractionController extends Controller
{
    public function store(
        StoreInteractionRequest $request,
        Customer $customer,
        AuditLogService $auditLog
    ): RedirectResponse {
        $data = $request->validated();
        $userId = $request->user()->id;

        DB::transaction(function () use ($customer, $data, $userId, $auditLog): void {
            $interaction = $customer->interactions()->create([
                'user_id' => $userId,
                'channel' => $data['channel'],
                'summary' => $data['summary'],
                'next_action' => $data['next_action'] ?? null,
                'contacted_at' => $data['contacted_at'],
                'next_action_at' => $data['next_action_at'] ?? null,
            ]);

            $auditLog->record(
                userId: $userId,
                action: 'interaction.created',
                targetType: Interaction::class,
                targetId: $interaction->id,
                changedFields: array_keys($data),
            );
        });

        return redirect()
            ->route('customers.show', ['customer' => $customer, 'tab' => 'interactions'])
            ->with('status', '対応履歴を登録しました。');
    }
}
