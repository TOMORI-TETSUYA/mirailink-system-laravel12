<?php

namespace App\Http\Requests\Plans;

use App\Models\InsurancePlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateInsurancePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plan = $this->route('plan');

        return $plan instanceof InsurancePlan
            && ($this->user()?->can('update', $plan) ?? false);
    }

    public function rules(): array
    {
        return [
            'plan_name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(InsurancePlan::CATEGORIES))],
            'plan_type' => ['nullable', 'string', 'max:100'],
            'insurer_name' => ['nullable', 'string', 'max:150'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'annual', 'single', 'other'])],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return StoreInsurancePlanRequest::attributeNames();
    }
}
