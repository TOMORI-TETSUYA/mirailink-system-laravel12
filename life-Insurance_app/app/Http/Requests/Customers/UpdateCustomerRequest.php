<?php

namespace App\Http\Requests\Customers;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof Customer
            && ($this->user()?->can('update', $customer) ?? false);
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'name_kana' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            ...StoreCustomerRequest::addressRules(),
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9０-９+\-() ]+$/u'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'family' => ['nullable', 'string', 'max:500'],
            'assigned_user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'status' => ['required', Rule::in(array_keys(Customer::STATUSES))],
        ];

        $customer = $this->route('customer');

        if ($customer instanceof Customer && $this->user()?->can('viewHealth', $customer)) {
            $rules['health_information'] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return StoreCustomerRequest::attributeNames();
    }
}
