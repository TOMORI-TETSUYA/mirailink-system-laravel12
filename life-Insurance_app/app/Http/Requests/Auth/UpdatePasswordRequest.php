<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'max:128', 'confirmed', 'different:current_password', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => '現在のパスワード',
            'password' => '新しいパスワード',
        ];
    }
}
