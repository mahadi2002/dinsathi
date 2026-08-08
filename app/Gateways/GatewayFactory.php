<?php
declare(strict_types=1);

namespace App\Gateways;

final class GatewayFactory
{
    public static function subscription(): SubscriptionGateway
    {
        return config('gateway.driver') === 'bdapps' ? new BdAppsGateway() : new MockGateway();
    }

    public static function sms(): SmsGateway
    {
        return config('sms.driver') === 'provider' ? new SmsGatewayAdapter() : new MockSmsGateway();
    }

    public static function push(): PushGateway
    {
        return config('push.driver') === 'webpush' ? new WebPushGateway() : new MockPushGateway();
    }
}
