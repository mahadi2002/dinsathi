<?php
declare(strict_types=1);

namespace App\Gateways;

use App\Core\Logger;

/**
 * Writes straight to the log, no external call — lets the whole reminder
 * pipeline be exercised end-to-end before a real SMS provider is chosen.
 * SmsLogRepo persists the row; this class is only the "did it send"
 * transport step.
 */
final class MockSmsGateway implements SmsGateway
{
    public function send(string $mobileNumber, string $message): array
    {
        Logger::channel('sms', "MockSmsGateway → ...{$this->last4($mobileNumber)}: {$message}");
        return ['success' => true, 'response' => ['mock' => true]];
    }

    private function last4(string $mobile): string
    {
        return substr($mobile, -4);
    }
}
