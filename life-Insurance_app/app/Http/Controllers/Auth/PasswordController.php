<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.password');
    }

    public function update(UpdatePasswordRequest $request, AuditLogService $auditLog): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->forceFill([
            'password' => $request->validated('password'),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        $request->session()->regenerate();

        $auditLog->record(
            userId: $user->id,
            action: 'user.password_changed',
            targetType: User::class,
            targetId: $user->id,
            changedFields: ['password'],
        );

        return redirect()->route('dashboard')->with('status', 'パスワードを変更しました。');
    }
}
