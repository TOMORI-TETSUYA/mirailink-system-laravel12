<?php

namespace App\Http\Requests\Customers;

use App\Models\Customer;
use App\Models\CustomerIntention;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCustomerIntentionRequest extends FormRequest
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
            'initial_intention' => ['required', 'string', 'max:2000'],
            'final_intention' => ['nullable', 'string', 'max:2000'],
            'protection_purpose' => ['nullable', 'string', 'max:500'],
            'budget' => ['nullable', 'string', 'max:100'],
            'desired_period' => ['nullable', 'string', 'max:100'],
            'proposed_reason' => ['nullable', 'string', 'max:2000'],
            'differences' => ['nullable', 'string', 'max:2000'],
            'confirmed' => ['nullable', 'boolean'],
            'confirmation_method' => ['nullable', Rule::in(array_keys(CustomerIntention::CONFIRMATION_METHODS))],
        ];
    }

    public function attributes(): array
    {
        return [
            'initial_intention' => '当初意向',
            'final_intention' => '最終意向',
            'protection_purpose' => '保障目的',
            'budget' => '予算',
            'desired_period' => '希望期間',
            'proposed_reason' => '提案理由',
            'differences' => '当初意向との相違点',
            'confirmed' => '意向確認',
            'confirmation_method' => '確認方法',
        ];
    }
}
