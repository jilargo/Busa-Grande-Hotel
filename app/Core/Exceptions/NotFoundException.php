<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'The page you are looking for could not be found.')
    {
        parent::__construct(404, $message);
    }
}