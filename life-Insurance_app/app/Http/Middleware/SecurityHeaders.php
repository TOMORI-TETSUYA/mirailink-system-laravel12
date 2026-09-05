<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 検索エンジンへの登録を全面的に禁止します。
        // <meta name="robots"> は HTML にしか置けないため、CSV エクスポートのような
        // 非 HTML のレスポンスも含めて確実に伝わるようヘッダーでも指定します。
        // noarchive はキャッシュ、nosnippet は抜粋、noimageindex は画像の登録を防ぎます。
        $response->headers->set(
            'X-Robots-Tag',
            'noindex, nofollow, noarchive, nosnippet, noimageindex'
        );
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=()'
        );
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; ".
            "img-src 'self' data:; ".
            "style-src 'self'; ".
            "script-src 'self'; ".
            "font-src 'self'; ".
            "object-src 'none'; ".
            "base-uri 'self'; ".
            "frame-ancestors 'none'; ".
            "form-action 'self'"
        );

        return $response;
    }
}
