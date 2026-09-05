<?php

namespace App\Http\Requests\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'login_id' => mb_strtolower(trim((string) $this->input('login_id'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'login_id' => ['required', 'string', 'min:4', 'max:64', 'regex:/^[a-z0-9._-]+$/', 'unique:users,login_id'],
            'display_name' => ['required', 'string', 'max:100'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['required', 'string', 'max:128', 'confirmed', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return self::attributeNames();
    }

    public static function attributeNames(): array
    {
        return [
            'login_id' => 'ログインID',
            'display_name' => '表示名',
            'role' => '権限',
            'password' => '初期パスワード',
            'is_active' => '有効',
        ];
    }
}
