<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\HttpException;

final class RequireAdmin implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $allowlist = (array) config('app.admin_ip_allowlist', []);
        if ($allowlist !== [] && !in_array($request->ip(), $allowlist, true)) {
            throw new HttpException(403);
        }

        $adminId = Session::adminId();
        if ($adminId === null) {
            return Response::redirect('/admin/login');
        }

        $admin = Db::first('SELECT id, email, role FROM admin_users WHERE id = ?', [$adminId]);
        if ($admin === null) {
            Session::forget('admin_id');
            return Response::redirect('/admin/login');
        }

        \App\Core\View::share('admin', $admin);

        return $next();
    }
}
