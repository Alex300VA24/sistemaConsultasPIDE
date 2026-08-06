<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Excepción para límite de solicitudes excedido (HTTP 429)
 */
class RateLimitException extends ApiException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 429);
    }
}
