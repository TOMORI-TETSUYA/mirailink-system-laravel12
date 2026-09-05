<?php

namespace App\Http\Requests\Plans;

use App\Models\InsurancePlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateInsurancePlanStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
        ];
    }
}
