<?php
declare(strict_types=1);

namespace App\Gateways;

use App\Core\Logger;

/**
 * Always succeeds. Mirrors the convention shared across this workspace's
 * sibling apps so test numbers behave predictably:
 *   - a number ending 00 simulates low balance  → 'suspended'
 *   - a number ending 99 simulates a hard failure → 'expired'
 *   - anything else                              → 'active'
 * Not a bug to "fix" in testing — pick a different test number instead.
 */
final class MockGateway implements SubscriptionGateway
{
    public function requestOtp(string $mobileNumber): array
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Logger::channel('otp', "MockGateway subscribe-OTP for ...{$this->last4($mobileNumber)}: {$otp}");
        return ['sent' => true];
    }

    public function confirmSubscription(string $mobileNumber, string $otp): array
    {
        $ref = 'MOCK-' . bin2hex(random_bytes(6));

        if (str_ends_with($mobileNumber, '00')) {
            return ['success' => true, 'external_ref' => $ref, 'status' => 'suspended'];
        }
        if (str_ends_with($mobileNumber, '99')) {
            return ['success' => true, 'external_ref' => $ref, 'status' => 'expired'];
        }

        return ['success' => true, 'external_ref' => $ref, 'status' => 'active'];
    }

    public function unsubscribe(string $externalRef): bool
    {
        return true;
    }

    /** Simulates the ~2s async delay real BDApps status polling would have. */
    public function checkStatus(string $externalRef): array
    {
        return ['status' => 'active'];
    }

    private function last4(string $mobile): string
    {
        return substr($mobile, -4);
    }
}
