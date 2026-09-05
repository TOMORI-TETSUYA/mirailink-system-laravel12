<?php

namespace App\Http\Requests\Contracts;

use App\Models\Customer;
use App\Models\InsuranceContract;
use App\Models\InsurancePlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInsuranceContractRequest extends FormRequest
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
            'insurance_plan_id' => ['required', 'integer', Rule::exists('insurance_plans', 'id')->whereNull('deleted_at')],
            'contract_date' => ['required', 'date'],
            'maturity_date' => ['nullable', 'date', 'after:contract_date'],
            'policy_number' => ['nullable', 'string', 'max:50'],
            'coverage' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys(InsuranceContract::STATUSES))],
        ];

        // 管理者のみ契約時金額を上書きでき、上書き時は理由が必須（仕様 7.6）。
        if ($this->user()?->can('overrideContractPrice', InsurancePlan::class)) {
            $rules['override_price'] = ['nullable', 'boolean'];
            $rules['override_amount_yen'] = ['required_if:override_price,1', 'nullable', 'integer', 'min:0', 'max:999999999999'];
            $rules['price_override_reason'] = ['required_if:override_price,1', 'nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'insurance_plan_id' => 'プラン',
            'contract_date' => '契約日',
            'maturity_date' => '満期日',
            'policy_number' => '証券番号',
            'coverage' => '保障内容',
            'status' => '契約状態',
            'override_price' => '金額の上書き',
            'override_amount_yen' => '上書き後金額',
            'price_override_reason' => '上書き理由',
        ];
    }
}
