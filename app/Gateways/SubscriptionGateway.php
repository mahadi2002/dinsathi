<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * BDApps SDP subscription contract. Every method returns a plain
 * associative array — kept simple on purpose, there is no framework-level
 * value-object convention elsewhere in this codebase.
 */
interface SubscriptionGateway
{
    /** @return array{sent: bool} */
    public function requestOtp(string $mobileNumber): array;

    /** @return array{success: bool, external_ref: ?string, status: string} status: active|suspended|expired */
    public function confirmSubscription(string $mobileNumber, string $otp): array;

    public function unsubscribe(string $externalRef): bool;

    /** @return array{status: string} */
    public function checkStatus(string $externalRef): array;
}
