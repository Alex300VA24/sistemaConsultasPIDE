<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Excepción para errores de validación (HTTP 422)
 */
class ValidationException extends ApiException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 422);
    }
}
