<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Excepción para recursos no encontrados (HTTP 404)
 */
class NotFoundException extends ApiException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 404);
    }
}
