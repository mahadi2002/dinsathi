<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\AdminUserRepo;
use App\Repositories\AuditLogRepo;

final class AdminAuthController extends Controller
{
    public function form(Request $request): Response
    {
        if (Session::adminId() !== null) {
            return $this->redirect('/admin');
        }
        return $this->view('admin/login', ['title' => 'Admin Login']);
    }

    public function login(Request $request): Response
    {
        $v = Validator::make($request->body(), [
            'email'    => 'required|email',
            'password' => 'required',
        ], ['email' => 'Email', 'password' => 'Password']);

        if ($v->fails()) {
            $v->flash(['_token', 'password']);
            return $this->redirect('/admin/login');
        }

        $key = 'ip:' . $request->ipHash();
        $wait = RateLimit::tooMany('admin_login', $key);
        if ($wait !== null) {
            Session::notify('error', 'অনেকবার চেষ্টা হয়েছে। ' . RateLimit::humanWait($wait) . ' পর আবার চেষ্টা করুন।');
            return $this->redirect('/admin/login');
        }

        $admin = (new AdminUserRepo())->findByEmail($v->get('email'));
        RateLimit::hit('admin_login', $key);

        if ($admin === null || !password_verify($request->str('password'), (string) $admin['password_hash'])) {
            Session::notify('error', 'Email অথবা Password ভুল।');
            return $this->redirect('/admin/login');
        }

        Session::regenerate();
        Session::put('admin_id', $admin['id']);
        (new AuditLogRepo())->log((int) $admin['id'], 'admin.login');

        return $this->redirect('/admin');
    }

    public function logout(Request $request): Response
    {
        $adminId = Session::adminId();
        if ($adminId !== null) {
            (new AuditLogRepo())->log($adminId, 'admin.logout');
        }
        Session::forget('admin_id');
        return $this->redirect('/admin/login');
    }
}
