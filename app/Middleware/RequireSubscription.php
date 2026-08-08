<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\SubscriptionService;

/**
 * Gates the base "planner" plan — there is no free tier, so this sits on
 * every /app/* route. The one rule this gate exists to enforce: every
 * gated request re-reads the database. No session flag, no cookie, no
 * cached claim ever decides access on its own — a lapsed subscription
 * loses access on this very request, not at next login.
 */
final class RequireSubscription implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $userId = Session::userId();

        if ($userId === null || !SubscriptionService::hasAccess($userId)) {
            Session::notify('info', 'দিনসাথী ব্যবহার করতে Subscribe করুন।');
            return Response::redirect('/subscribe');
        }

        return $next();
    }
}
