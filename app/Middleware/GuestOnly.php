<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/** Keeps signed-in users out of the register/login/password-reset funnel. */
final class GuestOnly implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (Session::userId() !== null) {
            return Response::redirect('/app');
        }

        return $next();
    }
}
