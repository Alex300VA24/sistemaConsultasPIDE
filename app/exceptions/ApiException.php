<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Excepción base para errores de la API
 */
class ApiException extends Exception
{
    protected int $statusCode;
    protected array $errors;

    /**
     * Constructor
     *
     * @param string $message Mensaje de error
     * @param int $statusCode Código HTTP
     * @param array $errors Errores adicionales
     */
    public function __construct(string $message, int $statusCode = 400, array $errors = [])
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    /**
     * Obtiene el código HTTP
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Obtiene los errores adicionales
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Convierte la excepción a un array para respuesta JSON
     *
     * @return array
     */
    public function toArray(): array
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage()
        ];

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        return $response;
    }
}

/**
 * Excepción para errores de validación
 */
class ValidationException extends ApiException
{
    public function __construct(string $message = 'Error de validación', array $errors = [])
    {
        parent::__construct($message, 400, $errors);
    }
}

/**
 * Excepción para errores de autenticación
 */
class AuthenticationException extends ApiException
{
    public function __construct(string $message = 'No autenticado')
    {
        parent::__construct($message, 401);
    }
}

/**
 * Excepción para errores de autorización
 */
class AuthorizationException extends ApiException
{
    public function __construct(string $message = 'No autorizado')
    {
        parent::__construct($message, 403);
    }
}

/**
 * Excepción para recursos no encontrados
 */
class NotFoundException extends ApiException
{
    public function __construct(string $message = 'Recurso no encontrado')
    {
        parent::__construct($message, 404);
    }
}

/**
 * Excepción para límite de tasa excedido
 */
class RateLimitException extends ApiException
{
    public function __construct(string $message = 'Demasiadas solicitudes. Por favor, espere un momento.')
    {
        parent::__construct($message, 429);
    }
}
