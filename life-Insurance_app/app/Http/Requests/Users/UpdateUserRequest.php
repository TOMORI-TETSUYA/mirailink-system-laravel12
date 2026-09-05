<?php

namespace App\Http\Requests\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            && ($this->user()?->can('update', $target) ?? false);
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:100'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'max:128', 'confirmed', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return StoreUserRequest::attributeNames();
    }
}
