<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

/**
 * Thrown by the Validator when one or more rules fail.
 * Carries the field => message map so forms can be re-populated with errors.
 */
final class ValidationException extends HttpException
{
    public function __construct(private readonly array $errors, ?string $summary = null)
    {
        $summary ??= 'Please fix the errors below and try again.';
        parent::__construct(422, $summary);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}