<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Gateways\GatewayFactory;
use App\Repositories\UserRepo;
use App\Services\SubscriptionService;

/**
 * Two independent subscribe/unsubscribe flows through one controller —
 * 'planner' (the app itself, mandatory, no free tier) and 'sms_reminders'
 * (the SMS delivery add-on, optional on top of planner). Same OTP-confirm
 * shape for both; only the plan key, price, and landing page after success
 * differ.
 */
final class SubscribeController extends Controller
{
    public function show(Request $request): Response
    {
        $userId = (int) $this->currentUserId();
        $user   = (new UserRepo())->find($userId);
        $subs   = SubscriptionService::currentBoth($userId);

        if (($subs['planner']['status'] ?? null) === 'active' && !$request->bool('manage')) {
            return $this->redirect('/app');
        }

        return $this->view('subscribe/show', [
            'title'   => 'Subscribe',
            'mobile'  => $user['mobile_number'] ?? '',
            'planner' => $subs['planner'],
            'sms'     => $subs['sms_reminders'],
            'otpSent' => Session::get('subscribe_otp_sent_planner', false),
        ]);
    }

    public function requestOtp(Request $request): Response
    {
        $this->doRequestOtp($request, SubscriptionService::PLAN_PLANNER);
        return $this->redirect('/subscribe');
    }

    public function confirm(Request $request): Response
    {
        return $this->doConfirm($request, SubscriptionService::PLAN_PLANNER, '/app', '/subscribe');
    }

    public function status(Request $request): Response
    {
        return $this->planStatus((int) $this->currentUserId(), SubscriptionService::PLAN_PLANNER);
    }

    /** Reachable regardless of subscription state — someone stuck without access still needs an exit. */
    public function unsubscribe(Request $request): Response
    {
        SubscriptionService::unsubscribe((int) $this->currentUserId(), SubscriptionService::PLAN_PLANNER);
        Session::notify('info', 'Planner Subscription বন্ধ করা হয়েছে। যেকোনো সময় আবার Subscribe করতে পারবেন।');
        return $this->redirect('/subscribe');
    }

    // ── SMS Reminder add-on — same shape, separate plan ─────────────────

    public function showSms(Request $request): Response
    {
        $userId = (int) $this->currentUserId();
        $user   = (new UserRepo())->find($userId);
        $sms    = SubscriptionService::current($userId, SubscriptionService::PLAN_SMS);

        return $this->view('subscribe/sms', [
            'title'   => 'SMS Reminder',
            'mobile'  => $user['mobile_number'] ?? '',
            'sms'     => $sms,
            'otpSent' => Session::get('subscribe_otp_sent_sms', false),
        ]);
    }

    public function requestOtpSms(Request $request): Response
    {
        $this->doRequestOtp($request, SubscriptionService::PLAN_SMS);
        return $this->redirect('/subscribe/sms');
    }

    public function confirmSms(Request $request): Response
    {
        return $this->doConfirm($request, SubscriptionService::PLAN_SMS, '/app/settings', '/subscribe/sms');
    }

    public function statusSms(Request $request): Response
    {
        return $this->planStatus((int) $this->currentUserId(), SubscriptionService::PLAN_SMS);
    }

    public function unsubscribeSms(Request $request): Response
    {
        SubscriptionService::unsubscribe((int) $this->currentUserId(), SubscriptionService::PLAN_SMS);
        Session::notify('info', 'SMS Reminder Add-on বন্ধ করা হয়েছে। Push Notification চালু থাকবে।');
        return $this->redirect('/app/settings');
    }

    // ── Shared plumbing ──────────────────────────────────────────────────

    private function doRequestOtp(Request $request, string $plan): void
    {
        $userId = (int) $this->currentUserId();
        $user   = (new UserRepo())->find($userId);
        if ($user === null) {
            return;
        }

        GatewayFactory::subscription()->requestOtp((string) $user['mobile_number']);
        Session::put('subscribe_otp_sent_' . ($plan === SubscriptionService::PLAN_PLANNER ? 'planner' : 'sms'), true);
        Session::notify('success', 'OTP পাঠানো হয়েছে।');
    }

    private function doConfirm(Request $request, string $plan, string $successRedirect, string $failureRedirect): Response
    {
        $userId = (int) $this->currentUserId();
        $user   = (new UserRepo())->find($userId);
        if ($user === null) {
            return $this->redirect($failureRedirect);
        }

        $result = GatewayFactory::subscription()->confirmSubscription((string) $user['mobile_number'], $request->str('otp'));

        if (!$result['success']) {
            Session::notify('error', 'Subscription confirm করা যায়নি। আবার চেষ্টা করুন।');
            return $this->redirect($failureRedirect);
        }

        $amount = (float) config('gateway.plans.' . $plan . '.amount', 2.78);

        SubscriptionService::activate($userId, $plan, 'mock', $amount, $result['external_ref'], $result['status']);

        Session::forget('subscribe_otp_sent_' . ($plan === SubscriptionService::PLAN_PLANNER ? 'planner' : 'sms'));

        if ($result['status'] === 'active') {
            Session::notify('success', $plan === SubscriptionService::PLAN_PLANNER
                ? 'Subscribe সফল হয়েছে! এখন দিনসাথী ব্যবহার করতে পারবেন।'
                : 'SMS Reminder চালু হয়েছে! এখন থেকে Push-এর পাশাপাশি SMS-ও পাবেন।');
            return $this->redirect($successRedirect);
        }

        Session::notify('info', 'Subscription তৈরি হয়েছে কিন্তু এখনো সক্রিয় নয়। Settings পেজে বিস্তারিত দেখুন।');
        return $this->redirect('/app/settings');
    }

    private function planStatus(int $userId, string $plan): Response
    {
        $sub = SubscriptionService::current($userId, $plan);
        if ($sub === null) {
            return $this->json(['status' => 'none']);
        }

        $live = GatewayFactory::subscription()->checkStatus((string) $sub['external_ref']);
        return $this->json(['status' => $live['status']]);
    }
}
