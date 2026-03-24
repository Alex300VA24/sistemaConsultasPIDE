<?php

namespace App\Controllers;

/**
 * Clase base para los controladores de consultas PIDE (RENIEC, SUNAT, SUNARP).
 * Centraliza validaciones comunes de request y envío de respuestas JSON.
 * La lógica de curl y carga de .env fue extraída a PideHttpClient y EnvLoader (SRP).
 */
abstract class ConsultasPideBaseController extends BaseController
{
    // ========================================
    // VALIDACIONES COMUNES DE CONSULTAS PIDE
    // ========================================

    /**
     * Valida que el request sea POST y limpia el buffer de salida.
     * Retorna true si es válido, false si no (y envía respuesta de error).
     */
    protected function validatePostRequest(): bool
    {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return false;
        }

        return true;
    }

    /**
     * Obtiene input JSON del body del request y valida campos requeridos.
     * Retorna null y envía error si faltan campos.
     *
     * @param array  $requiredFields Campos requeridos
     * @param string $errorMessage   Mensaje de error personalizado
     * @return array|null
     */
    protected function getPostInput(array $requiredFields = [], string $errorMessage = ''): ?array
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!empty($requiredFields)) {
            $missing = [];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field])) {
                    $missing[] = $field;
                }
            }

            if (!empty($missing)) {
                http_response_code(400);
                $msg = $errorMessage ?: 'Faltan datos: ' . implode(', ', $missing);
                echo json_encode([
                    'success' => false,
                    'message' => $msg
                ]);
                return null;
            }
        }

        return $input;
    }

    /**
     * Valida formato de DNI (8 dígitos).
     */
    protected function validateDni(string $dni): bool
    {
        if (!preg_match('/^\d{8}$/', $dni)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'DNI inválido. Debe tener 8 dígitos'
            ]);
            return false;
        }
        return true;
    }

    /**
     * Valida formato de RUC (11 dígitos).
     */
    protected function validateRuc(string $ruc): bool
    {
        if (!preg_match('/^\d{11}$/', $ruc)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'RUC inválido. Debe tener 11 dígitos'
            ]);
            return false;
        }
        return true;
    }

    /**
     * Envía resultado JSON con código HTTP apropiado según success.
     */
    protected function sendJsonResult(array $resultado): void
    {
        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}
