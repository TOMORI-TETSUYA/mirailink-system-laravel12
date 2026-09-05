<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login_id' => ['required', 'string', 'min:4', 'max:64'],
            'password' => ['required', 'string', 'min:12', 'max:128'],
        ];
    }

    /** 入力形式エラーでも ID の存在有無を推測させない共通メッセージにします（仕様 7.1）。 */
    public function messages(): array
    {
        $common = 'ログインIDまたはパスワードを確認してください。';

        return [
            'login_id.required' => $common,
            'login_id.min' => $common,
            'login_id.max' => $common,
            'password.required' => $common,
            'password.min' => $common,
            'password.max' => $common,
        ];
    }
}
