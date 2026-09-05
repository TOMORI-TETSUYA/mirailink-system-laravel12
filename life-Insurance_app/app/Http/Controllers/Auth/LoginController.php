<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class LoginController extends Controller
{
    private const ERROR_MESSAGE = 'ログインIDまたはパスワードを確認してください。';

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(
        LoginRequest $request,
        AuditLogService $auditLog
    ): RedirectResponse {
        $credentials = $request->validated();
        $limits = config('mirailink.login_rate_limit');

        $normalizedLoginId = Str::lower(trim($credentials['login_id']));
        $loginKey = hash('sha256', $normalizedLoginId.'|'.$request->ip());
        $ipKey = hash('sha256', 'ip|'.$request->ip());

        if (
            RateLimiter::tooManyAttempts($loginKey, $limits['per_login_id_attempts']) ||
            RateLimiter::tooManyAttempts($ipKey, $limits['per_ip_attempts'])
        ) {
            $auditLog->recordLoginFailure($request, 'rate_limited');

            return back()
                ->withErrors([
                    'login_id' => self::ERROR_MESSAGE,
                ])
                ->onlyInput('login_id');
        }

        $authenticated = Auth::attempt([
            'login_id' => $normalizedLoginId,
            'password' => $credentials['password'],
            'is_active' => true,
        ]);

        if (! $authenticated) {
            RateLimiter::hit($loginKey, $limits['per_login_id_decay_seconds']);
            RateLimiter::hit($ipKey, $limits['per_ip_decay_seconds']);
            $auditLog->recordLoginFailure($request, 'invalid_credentials');

            return back()
                ->withErrors([
                    'login_id' => self::ERROR_MESSAGE,
                ])
                ->onlyInput('login_id');
        }

        $request->session()->regenerate();
        RateLimiter::clear($loginKey);

        /** @var User $user */
        $user = $request->user();

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $auditLog->recordLoginSuccess($request, $user);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(
        Request $request,
        AuditLogService $auditLog
    ): RedirectResponse {
        $user = $request->user();

        $auditLog->recordLogout($request, $user);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
