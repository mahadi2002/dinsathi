<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * Provider-agnostic HTTP adapter — base URL + API key come from .env
 * (SMS_PROVIDER_API_BASE / SMS_PROVIDER_API_KEY), so swapping a real
 * Bangladeshi SMS provider in later is a config change, not a code change.
 * The provider itself is unresolved — cost and reliability tradeoffs need
 * a human decision — so this refuses to run until both env vars are set;
 * it does not guess a request shape.
 */
final class SmsGatewayAdapter implements SmsGateway
{
    public function send(string $mobileNumber, string $message): array
    {
        $base = (string) config('sms.base_url');
        $key  = (string) config('sms.api_key');

        if ($base === '' || $key === '') {
            throw new \RuntimeException(
                'SmsGatewayAdapter has no provider configured (SMS_PROVIDER_API_BASE / SMS_PROVIDER_API_KEY). '
                . 'Choose a provider first — never guess the request shape.'
            );
        }

        // Intentionally unimplemented beyond the config guard above: the
        // actual request/response shape depends on which provider is chosen.
        throw new \RuntimeException('SmsGatewayAdapter: provider request shape not yet implemented.');
    }
}
