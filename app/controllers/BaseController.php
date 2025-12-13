<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Clase base para todos los controladores
 * Proporciona métodos comunes de utilidad
 */
abstract class BaseController
{
    /**
     * Envía una respuesta JSON al cliente
     *
     * @param array $data Datos a enviar
     * @param int $statusCode Código HTTP de respuesta
     * @return void
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Envía una respuesta de éxito
     *
     * @param mixed $data Datos a incluir en la respuesta
     * @param string $message Mensaje de éxito
     * @param int $statusCode Código HTTP
     * @return void
     */
    protected function successResponse($data = null, string $message = 'Operación exitosa', int $statusCode = 200): void
    {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $this->jsonResponse($response, $statusCode);
    }

    /**
     * Envía una respuesta de error
     *
     * @param string $message Mensaje de error
     * @param int $statusCode Código HTTP
     * @param array $errors Errores adicionales
     * @return void
     */
    protected function errorResponse(string $message, int $statusCode = 400, array $errors = []): void
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        $this->jsonResponse($response, $statusCode);
    }

    /**
     * Valida que el método HTTP sea el esperado
     *
     * @param string|array $allowedMethods Método(s) permitido(s)
     * @return bool
     */
    protected function validateMethod($allowedMethods): bool
    {
        $methods = is_array($allowedMethods) ? $allowedMethods : [$allowedMethods];
        return in_array($_SERVER['REQUEST_METHOD'], $methods, true);
    }

    /**
     * Requiere un método HTTP específico o responde con error 405
     *
     * @param string|array $allowedMethods Método(s) permitido(s)
     * @return void
     */
    protected function requireMethod($allowedMethods): void
    {
        if (!$this->validateMethod($allowedMethods)) {
            $this->errorResponse('Método no permitido', 405);
        }
    }

    /**
     * Obtiene los datos JSON del body de la petición
     *
     * @return array
     */
    protected function getJsonInput(): array
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Verifica que el usuario esté autenticado
     *
     * @return void
     */
    protected function requireAuth(): void
    {
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            $this->errorResponse('No autenticado', 401);
        }
    }

    /**
     * Obtiene el ID del usuario actual de la sesión
     *
     * @return int|null
     */
    protected function getCurrentUserId(): ?int
    {
        return $_SESSION['usuarioID'] ?? null;
    }

    /**
     * Valida que los campos requeridos estén presentes
     *
     * @param array $data Datos a validar
     * @param array $requiredFields Campos requeridos
     * @return array Lista de campos faltantes
     */
    protected function validateRequiredFields(array $data, array $requiredFields): array
    {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    /**
     * Requiere campos obligatorios o responde con error
     *
     * @param array $data Datos a validar
     * @param array $requiredFields Campos requeridos
     * @return void
     */
    protected function requireFields(array $data, array $requiredFields): void
    {
        $missing = $this->validateRequiredFields($data, $requiredFields);
        if (!empty($missing)) {
            $this->errorResponse(
                'Campos requeridos faltantes: ' . implode(', ', $missing),
                400,
                ['missing_fields' => $missing]
            );
        }
    }
}
