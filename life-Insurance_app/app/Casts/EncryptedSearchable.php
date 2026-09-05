<?php

namespace App\Casts;

use App\Services\SearchHashService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * 暗号化して保存しつつ、完全一致検索用の HMAC を `<column>_hmac` 列へ同時に書き込むキャストです。
 * 使い方: 'phone' => EncryptedSearchable::class.':phone'
 */
final class EncryptedSearchable implements CastsAttributes
{
    public function __construct(
        private readonly string $type = 'generic',
    ) {
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::decryptString($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        $hasher = app(SearchHashService::class);

        if ($value === null || $value === '') {
            return [
                $key => null,
                $key.'_hmac' => null,
            ];
        }

        $normalized = match ($this->type) {
            'phone' => $hasher->normalizePhone((string) $value),
            'email' => $hasher->normalizeEmail((string) $value),
            default => trim((string) $value),
        };

        return [
            $key => Crypt::encryptString((string) $value),
            $key.'_hmac' => $hasher->hash($normalized),
        ];
    }
}
