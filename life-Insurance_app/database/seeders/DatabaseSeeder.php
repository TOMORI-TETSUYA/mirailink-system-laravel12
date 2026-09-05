<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 平文パスワードや個人情報を Seeder へ固定しません。
 * 管理者は `php artisan app:create-admin` で対話式に作成します（仕様 30）。
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        //
    }
}
