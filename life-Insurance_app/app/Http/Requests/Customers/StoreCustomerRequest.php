<?php

namespace App\Http\Requests\Customers;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Customer::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'name_kana' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:300'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9０-９+\-() ]+$/u'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'family' => ['nullable', 'string', 'max:500'],
            'health_information' => ['nullable', 'string', 'max:2000'],
            'assigned_user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'status' => ['required', Rule::in(array_keys(Customer::STATUSES))],
            'consented' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return self::attributeNames();
    }

    public static function attributeNames(): array
    {
        return [
            'name' => '氏名',
            'name_kana' => '氏名カナ',
            'birth_date' => '生年月日',
            'address' => '住所',
            'phone' => '電話番号',
            'email' => 'メールアドレス',
            'occupation' => '勤務先・職業',
            'family' => '家族構成',
            'health_information' => '健康・病歴情報',
            'assigned_user_id' => '担当者',
            'status' => '顧客状態',
            'consented' => '利用目的への同意',
        ];
    }
}
