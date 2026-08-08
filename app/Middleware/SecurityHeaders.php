<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Applied globally in public/index.php. No 'unsafe-inline' anywhere — data
 * that JS needs from PHP travels in <script type="application/json"> and is
 * JSON.parse'd instead.
 */
final class SecurityHeaders implements Middleware
{
    private const CSP = "default-src 'self'; script-src 'self'; style-src 'self'; "
        . "img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; "
        . "base-uri 'self'; form-action 'self'; object-src 'none'; manifest-src 'self'";

    public function handle(Request $request, callable $next): Response
    {
        $response = $next();

        $response
            ->withHeader('Content-Security-Policy', self::CSP)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'geolocation=(), camera=(), microphone=(), payment=()')
            ->withHeader('Cross-Origin-Opener-Policy', 'same-origin');

        if (config('app.env') !== 'local') {
            $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
