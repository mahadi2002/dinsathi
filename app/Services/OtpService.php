<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Db;
use App\Core\RateLimit;
use App\Exceptions\OtpException;
use App\Gateways\GatewayFactory;

/**
 * OTP generation/verification for register + login (phone-only auth,
 * no passwords). Delivery goes through the SMS gateway — the same
 * channel reminders use — independent of the BDApps SDP subscribe flow,
 * which owns its own OTP round-trip via SubscriptionGateway.
 */
final class OtpService
{
    public function request(string $mobile, string $purpose): void
    {
        $wait = RateLimit::tooMany('otp_request', 'mobile:' . Crypto::blindIndex($mobile));
        if ($wait !== null) {
            throw new OtpException('rate_limited', 'অনেকবার চেষ্টা হয়েছে। ' . RateLimit::humanWait($wait) . ' পর আবার চেষ্টা করুন।');
        }
        RateLimit::hit('otp_request', 'mobile:' . Crypto::blindIndex($mobile));

        $length = (int) config('app.otp.length', 6);
        $ttl    = (int) config('app.otp.ttl', 300);
        $code   = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        Db::exec('DELETE FROM otp_verifications WHERE mobile_number = ? AND purpose = ? AND consumed_at IS NULL', [$mobile, $purpose]);
        Db::insert(
            'INSERT INTO otp_verifications (mobile_number, purpose, otp_hash, expires_at) VALUES (?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? SECOND))',
            [$mobile, $purpose, $this->hash($mobile, $purpose, $code), $ttl]
        );

        $this->deliver($mobile, $code);
    }

    public function resend(string $mobile, string $purpose): void
    {
        $wait = RateLimit::tooMany('otp_resend', 'mobile:' . Crypto::blindIndex($mobile));
        if ($wait !== null) {
            throw new OtpException('rate_limited', RateLimit::humanWait($wait) . ' পর আবার Resend করা যাবে।');
        }
        RateLimit::hit('otp_resend', 'mobile:' . Crypto::blindIndex($mobile));
        $this->request($mobile, $purpose);
    }

    /** @throws OtpException on wrong/expired/exhausted code */
    public function verify(string $mobile, string $purpose, string $code): void
    {
        // Local-only shortcut so testing doesn't require reading storage/logs/sms-*.log
        // every time — matches the same "always 123456" convention the rest of the
        // series uses. Gated on APP_ENV=local specifically, never on APP_DEBUG alone,
        // so a misconfigured staging/production box can't inherit it by accident.
        if (config('app.env') === 'local' && $code === '123456') {
            return;
        }

        $wait = RateLimit::tooMany('otp_verify', 'mobile:' . Crypto::blindIndex($mobile));
        if ($wait !== null) {
            throw new OtpException('rate_limited', 'অনেকবার চেষ্টা হয়েছে। ' . RateLimit::humanWait($wait) . ' পর আবার চেষ্টা করুন।');
        }

        $row = Db::first(
            'SELECT * FROM otp_verifications WHERE mobile_number = ? AND purpose = ? AND consumed_at IS NULL
             ORDER BY id DESC LIMIT 1',
            [$mobile, $purpose]
        );

        RateLimit::hit('otp_verify', 'mobile:' . Crypto::blindIndex($mobile));

        if ($row === null) {
            throw new OtpException('not_found', 'OTP পাওয়া যায়নি। আবার OTP নিন।');
        }

        $maxAttempts = (int) config('app.otp.max_attempts', 3);
        if ((int) $row['attempt_count'] >= $maxAttempts) {
            throw new OtpException('too_many_attempts', 'অনেকবার ভুল হয়েছে। নতুন OTP নিন।');
        }

        if (strtotime((string) $row['expires_at']) < time()) {
            throw new OtpException('expired', 'OTP-এর মেয়াদ শেষ। নতুন OTP নিন।');
        }

        if (!hash_equals((string) $row['otp_hash'], $this->hash($mobile, $purpose, $code))) {
            Db::exec('UPDATE otp_verifications SET attempt_count = attempt_count + 1 WHERE id = ?', [$row['id']]);
            $left = $maxAttempts - ((int) $row['attempt_count'] + 1);
            throw new OtpException('wrong_code', 'OTP মিলছে না। আবার চেষ্টা করুন।', max(0, $left));
        }

        Db::exec('UPDATE otp_verifications SET consumed_at = UTC_TIMESTAMP() WHERE id = ?', [$row['id']]);
    }

    private function hash(string $mobile, string $purpose, string $code): string
    {
        return Crypto::blindIndex('otp:' . $purpose . ':' . $mobile . ':' . $code);
    }

    private function deliver(string $mobile, string $code): void
    {
        $message = "দিনসাথী OTP: {$code} — কাউকে শেয়ার করবেন না। ৫ মিনিটের মধ্যে ব্যবহার করুন।";
        GatewayFactory::sms()->send($mobile, $message);
    }
}
