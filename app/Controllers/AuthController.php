<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Crypto;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\PasswordResetRepo;
use App\Repositories\TaskListRepo;
use App\Repositories\UserRepo;
use App\Services\MailerService;
use PDOException;

final class AuthController extends Controller
{
    // ── Register ─────────────────────────────────────────────────────────

    public function registerForm(Request $request): Response
    {
        return $this->view('auth/register', ['title' => 'Register']);
    }

    public function register(Request $request): Response
    {
        $key  = 'ip:' . $request->ipHash();
        $wait = RateLimit::tooMany('register', $key);
        if ($wait !== null) {
            Session::notify('error', 'অনেকবার চেষ্টা হয়েছে। ' . RateLimit::humanWait($wait) . ' পর আবার চেষ্টা করুন।');
            return $this->redirect('/register');
        }

        $v = Validator::make($request->body(), [
            'email'    => 'required|email|max:191',
            'password' => 'required|min:8|max:255',
        ], ['email' => 'Email', 'password' => 'Password']);

        if ($v->fails()) {
            $v->flash(['_token', 'password', 'password_confirmation']);
            return $this->redirect('/register');
        }

        $email    = mb_strtolower($v->get('email'), 'UTF-8');
        $password = $request->str('password');
        $confirm  = $request->str('password_confirmation');

        if ($password !== $confirm) {
            Session::flash('_errors', ['password_confirmation' => ['Password দুটো মিলছে না।']]);
            Session::flash('_old', ['email' => $email]);
            return $this->redirect('/register');
        }

        RateLimit::hit('register', $key);

        $userRepo = new UserRepo();

        // Generic failure on a duplicate email — never confirm whether an
        // account already exists for a given address.
        if ($userRepo->findByEmail($email) !== null) {
            Session::notify('error', 'এই তথ্য দিয়ে এখন Account তৈরি করা যাচ্ছে না।');
            Session::flash('_old', ['email' => $email]);
            return $this->redirect('/register');
        }

        try {
            $userId = $userRepo->create($email, password_hash($password, PASSWORD_DEFAULT));
        } catch (PDOException $e) {
            // Two requests can both pass the findByEmail() check above before
            // either INSERT commits — uq_users_email is the real guard, this
            // just turns its violation into the same friendly message.
            if ($e->getCode() === '23000') {
                Session::notify('error', 'এই তথ্য দিয়ে এখন Account তৈরি করা যাচ্ছে না।');
                Session::flash('_old', ['email' => $email]);
                return $this->redirect('/register');
            }
            throw $e;
        }

        (new TaskListRepo())->create($userId, 'আমার Task', '#2E3A87', true);

        $this->signIn($userId);

        return $this->redirect('/app');
    }

    // ── Login ────────────────────────────────────────────────────────────

    public function loginForm(Request $request): Response
    {
        return $this->view('auth/login', ['title' => 'Login']);
    }

    public function login(Request $request): Response
    {
        $key  = 'ip:' . $request->ipHash();
        $wait = RateLimit::tooMany('login', $key);
        if ($wait !== null) {
            Session::notify('error', 'অনেকবার চেষ্টা হয়েছে। ' . RateLimit::humanWait($wait) . ' পর আবার চেষ্টা করুন।');
            return $this->redirect('/login');
        }

        $v = Validator::make($request->body(), [
            'email'    => 'required|email',
            'password' => 'required',
        ], ['email' => 'Email', 'password' => 'Password']);

        if ($v->fails()) {
            $v->flash();
            return $this->redirect('/login');
        }

        RateLimit::hit('login', $key);

        $email = mb_strtolower($v->get('email'), 'UTF-8');
        $user  = (new UserRepo())->findByEmail($email);

        // Constant-ish work whether or not the account exists, so timing does
        // not reveal which emails are registered.
        $hash  = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $valid = password_verify($request->str('password'), (string) $hash);

        if ($user === null || !$valid) {
            Session::notify('error', 'Email অথবা Password ভুল।');
            Session::flash('_old', ['email' => $email]);
            return $this->redirect('/login');
        }

        if ($user['status'] !== 'active') {
            Session::notify('error', 'এই Account বর্তমানে সক্রিয় নয়।');
            return $this->redirect('/login');
        }

        $this->signIn((int) $user['id']);

        return $this->redirect('/app');
    }

    public function logout(Request $request): Response
    {
        Session::destroy_all();
        return $this->redirect('/');
    }

    // ── Forgot / reset password ─────────────────────────────────────────

    public function forgotPasswordForm(Request $request): Response
    {
        return $this->view('auth/forgot-password', ['title' => 'Password ভুলে গেছেন?']);
    }

    public function forgotPassword(Request $request): Response
    {
        $key  = 'ip:' . $request->ipHash();
        $wait = RateLimit::tooMany('password_reset', $key);
        if ($wait !== null) {
            Session::notify('error', 'অনেকবার চেষ্টা হয়েছে। ' . RateLimit::humanWait($wait) . ' পর আবার চেষ্টা করুন।');
            return $this->redirect('/forgot-password');
        }

        $v = Validator::make($request->body(), ['email' => 'required|email'], ['email' => 'Email']);
        if ($v->fails()) {
            $v->flash();
            return $this->redirect('/forgot-password');
        }

        RateLimit::hit('password_reset', $key);

        $email = mb_strtolower($v->get('email'), 'UTF-8');
        $user  = (new UserRepo())->findByEmail($email);

        // Always show the same message whether or not the email exists —
        // never confirm account existence through this form.
        if ($user !== null) {
            $token     = Crypto::randomToken(32);
            $ttl       = (int) config('app.password_reset.ttl', 3600);
            (new PasswordResetRepo())->create((int) $user['id'], Crypto::blindIndex('reset:' . $token), $ttl);

            $resetUrl = rtrim((string) config('app.url'), '/') . '/reset-password/' . $token;
            (new MailerService())->sendPasswordReset($email, $resetUrl);
        }

        Session::notify('success', 'যদি এই Email দিয়ে Account থাকে, একটি Reset Link পাঠানো হয়েছে।');
        return $this->redirect('/login');
    }

    public function resetPasswordForm(Request $request, string $token): Response
    {
        $reset = (new PasswordResetRepo())->findValidByHash(Crypto::blindIndex('reset:' . $token));
        if ($reset === null) {
            Session::notify('error', 'এই Link-এর মেয়াদ শেষ অথবা এটি সঠিক নয়। আবার চেষ্টা করুন।');
            return $this->redirect('/forgot-password');
        }

        return $this->view('auth/reset-password', ['title' => 'নতুন Password সেট করুন', 'token' => $token]);
    }

    public function resetPassword(Request $request, string $token): Response
    {
        $reset = (new PasswordResetRepo())->findValidByHash(Crypto::blindIndex('reset:' . $token));
        if ($reset === null) {
            Session::notify('error', 'এই Link-এর মেয়াদ শেষ অথবা এটি সঠিক নয়। আবার চেষ্টা করুন।');
            return $this->redirect('/forgot-password');
        }

        $v = Validator::make($request->body(), ['password' => 'required|min:8|max:255'], ['password' => 'Password']);
        if ($v->fails()) {
            $v->flash(['_token', 'password', 'password_confirmation']);
            return $this->redirect('/reset-password/' . $token);
        }

        $password = $request->str('password');
        $confirm  = $request->str('password_confirmation');
        if ($password !== $confirm) {
            Session::flash('_errors', ['password_confirmation' => ['Password দুটো মিলছে না।']]);
            return $this->redirect('/reset-password/' . $token);
        }

        $userRepo = new UserRepo();
        $userRepo->updatePassword((int) $reset['user_id'], password_hash($password, PASSWORD_DEFAULT));
        (new PasswordResetRepo())->consume((int) $reset['id']);

        // Password change invalidates every existing session for this account.
        Session::revokeAllForUser((int) $reset['user_id']);

        Session::notify('success', 'Password পরিবর্তন হয়েছে। এখন Login করুন।');
        return $this->redirect('/login');
    }

    // ── Shared ───────────────────────────────────────────────────────────

    private function signIn(int $userId): void
    {
        Session::regenerate();
        Session::put('user_id', $userId);
    }
}
