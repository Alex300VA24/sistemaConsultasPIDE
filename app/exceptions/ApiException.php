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
