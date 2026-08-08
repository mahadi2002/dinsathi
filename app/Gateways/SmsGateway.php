<?php
declare(strict_types=1);

namespace App\Gateways;

interface SmsGateway
{
    /** @return array{success: bool, response: array} */
    public function send(string $mobileNumber, string $message): array;
}
