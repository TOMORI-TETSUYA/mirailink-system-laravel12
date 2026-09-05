<?php

/*
|--------------------------------------------------------------------------
| MiraiLink 固有設定
|--------------------------------------------------------------------------
| 保存期間・鍵などをソースコードへ固定しないための設定です。
| 保存期間は settings テーブルの値が優先され、未設定時のみ env を参照します。
*/

return [
    // 顧客情報の保存期間（年）。根拠確認前は未設定（null）のままにできます。
    'customer_retention_years' => env('CUSTOMER_RETENTION_YEARS') !== null && env('CUSTOMER_RETENTION_YEARS') !== ''
        ? (int) env('CUSTOMER_RETENTION_YEARS')
        : null,

    // 電話番号・メールの完全一致検索用HMAC鍵。APP_KEY と分離します。
    'search_hmac_key' => env('CUSTOMER_SEARCH_HMAC_KEY'),

    // ログイン制限（仕様 6.2）
    'login_rate_limit' => [
        'per_login_id_attempts' => 5,
        'per_login_id_decay_seconds' => 60,
        'per_ip_attempts' => 20,
        'per_ip_decay_seconds' => 3600,
    ],
];
