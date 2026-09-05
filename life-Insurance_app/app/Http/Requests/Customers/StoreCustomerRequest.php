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
            ...self::addressRules(),
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

    /**
     * 住所（分割後）の検証規則。登録・更新で共通です。
     *
     * 郵便番号は半角数字 7 桁で、ハイフンの有無どちらも受け付けます。
     * 都道府県は入力揺れを防ぐため定義済みの 47 件のみ許可します。
     */
    public static function addressRules(): array
    {
        return [
            'postal_code' => ['nullable', 'string', 'max:8', 'regex:/^[0-9]{3}-?[0-9]{4}$/'],
            'prefecture' => ['nullable', 'string', Rule::in(Customer::PREFECTURES)],
            'city' => ['nullable', 'string', 'max:100'],
            'address_line1' => ['nullable', 'string', 'max:200'],
            'address_line2' => ['nullable', 'string', 'max:200'],
            'building' => ['nullable', 'string', 'max:100'],
        ];
    }

    public static function attributeNames(): array
    {
        return [
            'name' => '氏名',
            'name_kana' => '氏名カナ',
            'birth_date' => '生年月日',
            'postal_code' => '郵便番号',
            'prefecture' => '都道府県',
            'city' => '市区町村',
            'address_line1' => '住所1',
            'address_line2' => '住所2',
            'building' => '建物名',
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
