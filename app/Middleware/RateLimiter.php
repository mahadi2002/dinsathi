<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\HttpException;

/** Generic IP/user-keyed limiter used as `rl:<bucket>` in the route table. */
final class RateLimiter implements Middleware
{
    public function __construct(private string $bucket)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $key = match ($this->bucket) {
            'contact' => 'ip:' . $request->ipHash(),
            default   => 'ip:' . $request->ipHash() . ':u' . (Session::userId() ?? 0),
        };

        $retry = RateLimit::hit($this->bucket, $key);

        if ($retry !== null) {
            throw new HttpException(429, 'অনেকবার চেষ্টা হয়েছে। ' . RateLimit::humanWait($retry) . ' পর আবার চেষ্টা করুন।', [
                'retry_after' => $retry,
            ]);
        }

        return $next();
    }
}
