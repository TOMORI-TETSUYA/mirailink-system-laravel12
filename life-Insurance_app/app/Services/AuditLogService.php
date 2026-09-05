<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * 監査ログ記録サービス（仕様 6.10）。
 * パスワード、セッションID、CSRFトークン、顧客情報の平文は記録しません。
 */
final class AuditLogService
{
    private const REQUEST_ID_ATTRIBUTE = 'mirailink.request_id';

    public function record(
        ?int $userId,
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        array $changedFields = [],
        bool $succeeded = true,
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        return AuditLog::query()->create([
            'user_id' => $userId,
            'request_id' => $this->requestId($request),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'changed_fields' => array_values($changedFields),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'succeeded' => $succeeded,
            'created_at' => now(),
        ]);
    }

    public function recordLoginFailure(Request $request, string $reason): AuditLog
    {
        return $this->record(
            userId: null,
            action: 'auth.login_failed',
            changedFields: [$reason],
            succeeded: false,
            request: $request,
        );
    }

    public function recordLoginSuccess(Request $request, User $user): AuditLog
    {
        return $this->record(
            userId: $user->id,
            action: 'auth.login',
            targetType: User::class,
            targetId: $user->id,
            request: $request,
        );
    }

    public function recordLogout(Request $request, ?User $user): AuditLog
    {
        return $this->record(
            userId: $user?->id,
            action: 'auth.logout',
            targetType: User::class,
            targetId: $user?->id,
            request: $request,
        );
    }

    private function requestId(Request $request): string
    {
        $existing = $request->attributes->get(self::REQUEST_ID_ATTRIBUTE);

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $id = (string) Str::uuid();
        $request->attributes->set(self::REQUEST_ID_ATTRIBUTE, $id);

        return $id;
    }
}
