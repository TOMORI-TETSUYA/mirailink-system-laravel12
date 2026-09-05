<?php

namespace App\Http\Requests\Plans;

use App\Models\InsurancePlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInsurancePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InsurancePlan::class)
            ?? false;
    }

    public function rules(): array
    {
        return [
            'plan_name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(InsurancePlan::CATEGORIES))],
            'plan_type' => ['nullable', 'string', 'max:100'],
            'insurer_name' => ['nullable', 'string', 'max:150'],
            'billing_cycle' => [
                'required',
                Rule::in(['monthly', 'annual', 'single', 'other']),
            ],
            'amount_yen' => [
                'required',
                'integer',
                'min:0',
                'max:999999999999',
            ],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => [
                'required',
                Rule::in(['draft', 'active', 'inactive']),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return self::attributeNames();
    }

    public static function attributeNames(): array
    {
        return [
            'plan_name' => 'プラン名',
            'category' => '保険分類',
            'plan_type' => 'プラン種類',
            'insurer_name' => '保険会社名',
            'billing_cycle' => '支払単位',
            'amount_yen' => '金額',
            'effective_from' => '適用開始日',
            'effective_to' => '適用終了日',
            'display_order' => '表示順',
            'status' => '状態',
            'notes' => '備考',
        ];
    }
}
