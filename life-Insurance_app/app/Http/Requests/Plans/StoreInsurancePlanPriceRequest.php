<?php

namespace App\Http\Requests\Plans;

use App\Models\InsurancePlan;
use Illuminate\Foundation\Http\FormRequest;

final class StoreInsurancePlanPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plan = $this->route('plan');

        return $plan instanceof InsurancePlan
            && ($this->user()?->can('changePrice', $plan) ?? false);
    }

    public function rules(): array
    {
        return [
            'amount_yen' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }

    public function attributes(): array
    {
        return StoreInsurancePlanRequest::attributeNames();
    }
}
