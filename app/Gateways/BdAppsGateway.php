<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * Real BDApps SDP integration — a stub. Every method throws until the actual
 * API contract is obtained. Never fabricate the request/response shape
 * from guesswork; wire this in once real docs exist.
 */
final class BdAppsGateway implements SubscriptionGateway
{
    private const NOT_IMPLEMENTED = 'BdAppsGateway is a stub — the real BDApps SDP/OTP API contract has not been '
        . 'obtained yet. Wire this class once real API docs exist; never guess the shape.';

    public function requestOtp(string $mobileNumber): array
    {
        throw new \RuntimeException(self::NOT_IMPLEMENTED);
    }

    public function confirmSubscription(string $mobileNumber, string $otp): array
    {
        throw new \RuntimeException(self::NOT_IMPLEMENTED);
    }

    public function unsubscribe(string $externalRef): bool
    {
        throw new \RuntimeException(self::NOT_IMPLEMENTED);
    }

    public function checkStatus(string $externalRef): array
    {
        throw new \RuntimeException(self::NOT_IMPLEMENTED);
    }
}
