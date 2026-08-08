<?php
declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Transport/protocol failure talking to a subscription or SMS gateway. */
class GatewayException extends RuntimeException
{
}
