<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

/**
 * The public /contact form has no dedicated table in 02-SCHEMA.sql (out of
 * scope for this build's locked schema) — messages are logged so the
 * mailbox exists, and forwarded to SUPPORT_EMAIL when configured. If contact
 * volume grows enough to need a real inbox + states, that is a schema
 * addendum (0NN_addendum_contact_messages.sql), not a silent addition here.
 */
final class ContactMessageRepo
{
    public function log(string $name, string $mobileOrEmail, string $message): void
    {
        \App\Core\Logger::channel('contact', json_encode([
            'name'    => $name,
            'contact' => $mobileOrEmail,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE));
    }
}
