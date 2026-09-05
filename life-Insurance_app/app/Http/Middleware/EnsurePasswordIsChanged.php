<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** 初期パスワードのまま（must_change_password=true）の場合、パスワード変更画面へ誘導します（仕様 6.3）。 */
final class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->must_change_password && ! $request->routeIs('password.*', 'logout')) {
            return redirect()->route('password.edit');
        }

        return $next($request);
    }
}
