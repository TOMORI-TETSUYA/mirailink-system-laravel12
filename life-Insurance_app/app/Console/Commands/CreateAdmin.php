<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/** 対話式で初期管理者を作成します。平文パスワードをコードや Seeder へ固定しません（仕様 30）。 */
final class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'MiraiLink の管理者ユーザーを対話式で作成します';

    public function handle(AuditLogService $auditLog): int
    {
        $loginId = Str::lower(trim((string) $this->ask('ログインID（4〜64文字）')));
        $displayName = trim((string) $this->ask('表示名（100文字以内）'));
        $password = (string) $this->secret('パスワード（12〜128文字）');
        $confirmation = (string) $this->secret('パスワード（再入力）');

        $validator = Validator::make(
            [
                'login_id' => $loginId,
                'display_name' => $displayName,
                'password' => $password,
                'password_confirmation' => $confirmation,
            ],
            [
                'login_id' => ['required', 'string', 'min:4', 'max:64', 'regex:/^[a-z0-9._-]+$/', 'unique:users,login_id'],
                'display_name' => ['required', 'string', 'max:100'],
                'password' => ['required', 'string', 'max:128', 'confirmed', Password::defaults()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($loginId, $displayName, $password, $auditLog): User {
            $user = User::query()->create([
                'login_id' => $loginId,
                'display_name' => $displayName,
                'password' => $password,
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            $auditLog->record(
                userId: $user->id,
                action: 'user.created_by_console',
                targetType: User::class,
                targetId: $user->id,
                changedFields: ['login_id', 'display_name', 'role'],
            );

            return $user;
        });

        $this->info("管理者 {$user->login_id} を作成しました。初回ログイン時にパスワード変更が求められます。");

        return self::SUCCESS;
    }
}
