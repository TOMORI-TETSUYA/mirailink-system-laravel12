<?php

namespace App\Http\Requests\Customers;

use App\Models\Customer;
use App\Models\MaintenanceHistory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMaintenanceHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof Customer
            && ($this->user()?->can('update', $customer) ?? false);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(MaintenanceHistory::TYPES))],
            'status' => ['required', Rule::in(array_keys(MaintenanceHistory::STATUSES))],
            'requested_at' => ['required', 'date'],
            'completed_at' => ['nullable', 'date', 'after_or_equal:requested_at'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => '種別',
            'status' => '状態',
            'requested_at' => '受付日',
            'completed_at' => '完了日',
            'description' => '内容',
        ];
    }
}
