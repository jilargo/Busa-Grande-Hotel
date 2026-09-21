<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

/**
 * Base exception for requests that should return a specific HTTP status.
 * The exception handler renders a friendly page for these.
 */
class HttpException extends \RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}