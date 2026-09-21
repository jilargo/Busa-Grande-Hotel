<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

/** Thrown when a state-changing request lacks a valid CSRF token. */
final class CsrfException extends HttpException
{
    public function __construct()
    {
        parent::__construct(419, 'Your session has expired. Please refresh the page and try again.');
    }
}