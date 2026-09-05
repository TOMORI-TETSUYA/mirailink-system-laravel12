<?php

namespace App\Http\Controllers;

use App\Actions\Audit\RecordCustomerAccess;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\CustomerCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CustomerController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->visibleTo($request->user())
            ->searchCode($request->query('code'))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->with(['assignedUser', 'latestInteraction', 'latestIntention'])
            ->withCount('contracts')
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'filters' => [
                'code' => (string) $request->query('code', ''),
                'status' => (string) $request->query('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create', [
            'users' => $this->assignableUsers(),
        ]);
    }

    public function store(StoreCustomerRequest $request, CustomerCodeService $codeService): RedirectResponse
    {
        $data = $request->validated();
        $userId = $request->user()->id;

        $customer = DB::transaction(function () use ($data, $userId, $codeService): Customer {
            $customer = Customer::query()->create([
                'customer_code' => $codeService->generate(),
                'name' => $data['name'],
                'name_kana' => $data['name_kana'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'prefecture' => $data['prefecture'] ?? null,
                'city' => $data['city'] ?? null,
                'address_line1' => $data['address_line1'] ?? null,
                'address_line2' => $data['address_line2'] ?? null,
                'building' => $data['building'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'family' => $data['family'] ?? null,
                'health_information' => $data['health_information'] ?? null,
                'assigned_user_id' => $data['assigned_user_id'],
                'status' => $data['status'],
                'consented_at' => now(),
            ]);

            $this->auditLog->record(
                userId: $userId,
                action: 'customer.created',
                targetType: Customer::class,
                targetId: $customer->id,
                changedFields: array_keys($data),
            );

            return $customer;
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "顧客 {$customer->customer_code} を登録しました。");
    }

    public function show(Request $request, Customer $customer, RecordCustomerAccess $recordAccess): View
    {
        $this->authorize('view', $customer);

        $canViewHealth = $request->user()->can('viewHealth', $customer);

        $customer->load([
            'assignedUser',
            'intentions.creator',
            'contracts.creator',
            'interactions.user',
            'maintenanceHistories.user',
        ]);

        $auditLogs = $customer->auditLogs()->with('user')->limit(50)->get();

        $recordAccess->execute($customer, $request->user()->id, $canViewHealth);

        return view('customers.show', [
            'customer' => $customer,
            'canViewHealth' => $canViewHealth,
            'auditLogs' => $auditLogs,
            'tab' => (string) $request->query('tab', 'basic'),
        ]);
    }

    public function edit(Request $request, Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', [
            'customer' => $customer,
            'users' => $this->assignableUsers(),
            'canViewHealth' => $request->user()->can('viewHealth', $customer),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();
        $userId = $request->user()->id;

        DB::transaction(function () use ($customer, $data, $userId): void {
            $customer->fill($data);
            $changed = array_keys($customer->getDirty());
            $customer->save();

            $this->auditLog->record(
                userId: $userId,
                action: 'customer.updated',
                targetType: Customer::class,
                targetId: $customer->id,
                changedFields: array_values(array_diff($changed, ['phone_hmac', 'email_hmac'])),
            );
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', '顧客情報を更新しました。');
    }

    /** 論理削除（仕様 6.11）。物理削除は行いません。 */
    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        DB::transaction(function () use ($customer, $request): void {
            $customer->delete();

            $this->auditLog->record(
                userId: $request->user()->id,
                action: 'customer.soft_deleted',
                targetType: Customer::class,
                targetId: $customer->id,
                changedFields: ['deleted_at'],
            );
        });

        return redirect()
            ->route('customers.index')
            ->with('status', "顧客 {$customer->customer_code} を削除しました。");
    }

    /** CSV出力（管理者のみ）。住所・電話・病歴などの機微情報は含めません。 */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Customer::class);

        $this->auditLog->record(
            userId: $request->user()->id,
            action: 'customer.exported_csv',
            targetType: Customer::class,
        );

        $filename = 'customers_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['顧客コード', '氏名', '担当者', '顧客状態', '登録日']);

            Customer::query()
                ->with('assignedUser')
                ->orderBy('customer_code')
                ->chunk(200, function ($customers) use ($handle): void {
                    foreach ($customers as $customer) {
                        fputcsv($handle, [
                            $customer->customer_code,
                            $customer->name,
                            $customer->assignedUser?->display_name,
                            $customer->status_label,
                            $customer->created_at?->format('Y-m-d'),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function assignableUsers()
    {
        return User::query()
            ->where('is_active', true)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
            ->orderBy('display_name')
            ->get(['id', 'display_name']);
    }
}
