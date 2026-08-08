<?php
declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Thrown to short-circuit a request into an error view. */
class HttpException extends RuntimeException
{
    public function __construct(
        public readonly int $status = 500,
        string $message = '',
        public readonly array $context = [],
    ) {
        parent::__construct($message !== '' ? $message : ('HTTP ' . $status), $status);
    }
}
