<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\SubscriptionService;

/** Keeps signed-in users out of the register/login funnel. */
final class GuestOnly implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $userId = Session::userId();

        if ($userId !== null) {
            return Response::redirect(SubscriptionService::hasAccess($userId) ? '/app' : '/subscribe');
        }

        return $next();
    }
}
