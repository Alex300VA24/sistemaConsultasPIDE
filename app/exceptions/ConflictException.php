<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Excepción para conflictos de estado (HTTP 409)
 */
class ConflictException extends ApiException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 409);
    }
}
