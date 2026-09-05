<?php

namespace App\Services;

use RuntimeException;

/** 電話番号・メールアドレスの完全一致検索用 HMAC を生成します（仕様 6.9）。鍵は APP_KEY と分離します。 */
final class SearchHashService
{
    public function hash(string $normalized): string
    {
        $key = (string) config('mirailink.search_hmac_key');

        if ($key === '' || str_starts_with($key, 'CHANGE_TO')) {
            throw new RuntimeException('CUSTOMER_SEARCH_HMAC_KEY が設定されていません。');
        }

        return hash_hmac('sha256', $normalized, $key);
    }

    public function normalizePhone(string $phone): string
    {
        $converted = mb_convert_kana($phone, 'n');

        return preg_replace('/[^0-9]/', '', $converted) ?? '';
    }

    public function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
