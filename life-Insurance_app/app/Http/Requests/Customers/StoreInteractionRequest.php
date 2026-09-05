<?php

namespace App\Http\Requests\Customers;

use App\Models\Customer;
use App\Models\Interaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInteractionRequest extends FormRequest
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
            'channel' => ['required', Rule::in(array_keys(Interaction::CHANNELS))],
            'contacted_at' => ['required', 'date'],
            'summary' => ['required', 'string', 'max:2000'],
            'next_action' => ['nullable', 'string', 'max:500'],
            'next_action_at' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'channel' => '対応手段',
            'contacted_at' => '対応日時',
            'summary' => '対応内容',
            'next_action' => '次回対応',
            'next_action_at' => '次回対応日時',
        ];
    }
}
