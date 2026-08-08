<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\HttpException;

final class CsrfGuard implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (in_array($request->method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next();
        }

        $token = $request->str('_token') ?: (string) $request->header('x-csrf-token', '');

        if (!Csrf::check($token)) {
            Csrf::rotate();
            throw new HttpException(419, 'Session-এর মেয়াদ শেষ হয়ে গেছে। আবার চেষ্টা করুন।');
        }

        return $next();
    }
}
