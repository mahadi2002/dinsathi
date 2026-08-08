<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\OtpException;
use App\Repositories\TaskListRepo;
use App\Repositories\UserRepo;
use App\Services\OtpService;

final class AuthController extends Controller
{
    // ── Register ─────────────────────────────────────────────────────────

    public function registerForm(Request $request): Response
    {
        return $this->view('auth/register', ['title' => 'Register']);
    }

    public function requestRegisterOtp(Request $request): Response
    {
        $v = Validator::make($request->body(), ['mobile_number' => 'required|msisdn'], ['mobile_number' => 'মোবাইল নম্বর']);

        if ($v->fails()) {
            $v->flash();
            return $this->redirect('/register');
        }

        $mobile   = $v->get('mobile_number');
        $operator = $this->operatorFor($mobile);

        if ($operator === null) {
            Session::flash('_errors', ['mobile_number' => [(string) config('operators.display_note')]]);
            Session::flash('_old', $request->body());
            return $this->redirect('/register');
        }

        if ((new UserRepo())->findByMobile($mobile) !== null) {
            Session::notify('info', 'এই নম্বর দিয়ে আগেই Account আছে। লগইন করুন।');
            return $this->redirect('/login');
        }

        try {
            (new OtpService())->request($mobile, 'register');
        } catch (OtpException $e) {
            Session::notify('error', $e->getMessage());
            return $this->redirect('/register');
        }

        Session::put('pending_mobile', $mobile);
        Session::put('pending_operator', $operator);
        Session::put('pending_purpose', 'register');

        return $this->redirect('/register/verify');
    }

    public function registerVerifyForm(Request $request): Response
    {
        if (Session::get('pending_mobile') === null || Session::get('pending_purpose') !== 'register') {
            return $this->redirect('/register');
        }
        return $this->view('auth/verify', [
            'title'   => 'OTP যাচাই করুন',
            'mobile'  => Session::get('pending_mobile'),
            'action'  => '/register/verify',
        ]);
    }

    public function verifyRegister(Request $request): Response
    {
        $mobile   = (string) Session::get('pending_mobile', '');
        $operator = (string) Session::get('pending_operator', '');

        if ($mobile === '' || Session::get('pending_purpose') !== 'register') {
            return $this->redirect('/register');
        }

        try {
            (new OtpService())->verify($mobile, 'register', $request->str('otp'));
        } catch (OtpException $e) {
            Session::notify('error', $e->getMessage());
            return $this->redirect('/register/verify');
        }

        $userRepo = new UserRepo();
        $existing = $userRepo->findByMobile($mobile);
        $userId   = $existing['id'] ?? $userRepo->create($mobile, $operator);

        if ($existing === null) {
            (new TaskListRepo())->create((int) $userId, 'আমার Task', '#2E3A87', true);
        }

        $this->signIn((int) $userId);

        // A brand new account is never subscribed yet — go straight to the
        // subscribe funnel instead of bouncing through /app first.
        return $this->redirect('/subscribe');
    }

    // ── Login ────────────────────────────────────────────────────────────

    public function loginForm(Request $request): Response
    {
        return $this->view('auth/login', ['title' => 'Login']);
    }

    public function requestLoginOtp(Request $request): Response
    {
        $v = Validator::make($request->body(), ['mobile_number' => 'required|msisdn'], ['mobile_number' => 'মোবাইল নম্বর']);
        if ($v->fails()) {
            $v->flash();
            return $this->redirect('/login');
        }

        $mobile = $v->get('mobile_number');
        $user   = (new UserRepo())->findByMobile($mobile);

        if ($user === null) {
            Session::notify('info', 'এই নম্বরে কোনো Account নেই। আগে Register করুন।');
            return $this->redirect('/register');
        }

        try {
            (new OtpService())->request($mobile, 'login');
        } catch (OtpException $e) {
            Session::notify('error', $e->getMessage());
            return $this->redirect('/login');
        }

        Session::put('pending_mobile', $mobile);
        Session::put('pending_purpose', 'login');

        return $this->redirect('/login/verify');
    }

    public function loginVerifyForm(Request $request): Response
    {
        if (Session::get('pending_mobile') === null || Session::get('pending_purpose') !== 'login') {
            return $this->redirect('/login');
        }
        return $this->view('auth/verify', [
            'title'  => 'OTP যাচাই করুন',
            'mobile' => Session::get('pending_mobile'),
            'action' => '/login/verify',
        ]);
    }

    public function verifyLogin(Request $request): Response
    {
        $mobile = (string) Session::get('pending_mobile', '');
        if ($mobile === '' || Session::get('pending_purpose') !== 'login') {
            return $this->redirect('/login');
        }

        try {
            (new OtpService())->verify($mobile, 'login', $request->str('otp'));
        } catch (OtpException $e) {
            Session::notify('error', $e->getMessage());
            return $this->redirect('/login/verify');
        }

        $user = (new UserRepo())->findByMobile($mobile);
        if ($user === null || $user['status'] !== 'active') {
            Session::notify('error', 'এই Account বর্তমানে সক্রিয় নয়।');
            return $this->redirect('/login');
        }

        $this->signIn((int) $user['id']);

        return $this->redirect('/app');
    }

    // ── Shared ───────────────────────────────────────────────────────────

    public function resendOtp(Request $request): Response
    {
        $mobile  = (string) Session::get('pending_mobile', '');
        $purpose = (string) Session::get('pending_purpose', '');

        if ($mobile === '' || $purpose === '') {
            return $this->redirect('/login');
        }

        try {
            (new OtpService())->resend($mobile, $purpose);
            Session::notify('success', 'নতুন OTP পাঠানো হয়েছে।');
        } catch (OtpException $e) {
            Session::notify('error', $e->getMessage());
        }

        return $this->redirect($purpose === 'register' ? '/register/verify' : '/login/verify');
    }

    public function logout(Request $request): Response
    {
        Session::destroy_all();
        return $this->redirect('/');
    }

    private function signIn(int $userId): void
    {
        Session::regenerate();
        Session::put('user_id', $userId);
        Session::forget('pending_mobile');
        Session::forget('pending_operator');
        Session::forget('pending_purpose');
    }

    private function operatorFor(string $mobile): ?string
    {
        $prefix = substr($mobile, 0, 3);
        $map    = (array) config('operators.map', []);
        $op     = $map[$prefix] ?? null;
        $allowed = (array) config('operators.allowed', []);
        return ($op !== null && in_array($op, $allowed, true)) ? $op : null;
    }
}
