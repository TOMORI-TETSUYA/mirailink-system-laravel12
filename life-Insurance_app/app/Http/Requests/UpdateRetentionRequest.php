<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateRetentionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_retention_years' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return ['customer_retention_years' => '保存期間（年）'];
    }
}
