<?php

namespace App\Exceptions;

use RuntimeException;

class NfeEmissionException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 422,
        public readonly string $nfeStatus = 'erro',
        public readonly ?int $cstat = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
