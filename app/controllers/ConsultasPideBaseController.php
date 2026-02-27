<?php

namespace App\Controllers;

/**
 * Clase base para los controladores de consultas PIDE (RENIEC, SUNAT, SUNARP).
 * Centraliza la carga de .env, ejecución de curl, validaciones comunes y envío de respuestas JSON.
 */
abstract class ConsultasPideBaseController extends BaseController
{

    public function __construct()
    {
        $this->loadEnv();
    }

    // ========================================
    // CARGA DE VARIABLES DE ENTORNO
    // ========================================

    /**
     * Carga las variables del archivo .env una sola vez.
     */
    protected function loadEnv()
    {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $content = file_get_contents($envFile);
            $lines = preg_split('/\r\n|\n|\r/', $content);

            foreach ($lines as $line) {
                $line = trim($line);

                if (empty($line) || $line[0] === '#') {
                    continue;
                }

                if (preg_match('/^([^=]+)=(.*)$/', $line, $matches)) {
                    $name = trim($matches[1]);
                    $value = trim($matches[2]);

                    // Manejar comillas
                    if (preg_match('/^["\'](.*)["\']\$/', $value, $quoteMatches)) {
                        $value = $quoteMatches[1];
                    }

                    $_ENV[$name] = $value;
                }
            }
        }
    }

    // ========================================
    // EJECUCIÓN CURL GENÉRICA
    // ========================================

    /**
     * Ejecuta una petición cURL genérica y retorna la respuesta.
     *
     * @param string $url        URL del servicio
     * @param array  $data       Datos a enviar (se codifican como JSON)
     * @param string $method     Método HTTP (POST o GET)
     * @param string $servicio   Nombre del servicio para mensajes de error
     * @param int    $timeout    Timeout en segundos
     * @return array ['success' => bool, 'httpCode' => int, 'response' => string|null, 'error' => string|null]
     */
    protected function executeCurl($url, $data = null, $method = 'POST', $servicio = 'PIDE', $timeout = 45)
    {
        $ch = curl_init($url);

        $headers = [
            "Content-Type: application/json; charset=UTF-8",
            "Accept: application/json"
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HTTPHEADER     => $headers
        ];

        if ($method === 'POST' && $data !== null) {
            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $jsonData;
            $headers[] = "Content-Length: " . strlen($jsonData);
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            error_log("CURL Error $servicio: $error");
            return [
                'success' => false,
                'httpCode' => 0,
                'response' => null,
                'error' => "Error de conexión con $servicio: $error"
            ];
        }

        curl_close($ch);

        return [
            'success' => true,
            'httpCode' => $httpCode,
            'response' => $response,
            'error' => null
        ];
    }

    /**
     * Decodifica respuesta JSON de curl de forma segura.
     *
     * @param string $response   Respuesta raw del curl
     * @param string $servicio   Nombre del servicio para mensajes de error
     * @return array ['success' => bool, 'data' => array|null, 'message' => string]
     */
    protected function decodeJsonResponse($response, $servicio = 'PIDE')
    {
        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Error JSON decode $servicio: " . json_last_error_msg());
            return [
                'success' => false,
                'data' => null,
                'message' => "Error al decodificar respuesta JSON de $servicio: " . json_last_error_msg()
            ];
        }

        return [
            'success' => true,
            'data' => $decoded,
            'message' => 'OK'
        ];
    }

    // ========================================
    // VALIDACIONES COMUNES DE CONSULTAS PIDE
    // ========================================

    /**
     * Valida que el request sea POST y limpia el buffer de salida.
     * Retorna true si es válido, false si no (y envía respuesta de error).
     */
    protected function validatePostRequest()
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
     * @param array $requiredFields   Campos requeridos
     * @param string $errorMessage    Mensaje de error personalizado
     * @return array|null
     */
    protected function getPostInput(array $requiredFields = [], $errorMessage = '')
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
     * Retorna true si es válido, false si no (y envía respuesta de error).
     */
    protected function validateDni($dni)
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
     * Retorna true si es válido, false si no (y envía respuesta de error).
     */
    protected function validateRuc($ruc)
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
    protected function sendJsonResult($resultado)
    {
        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Retorna un error estándar para cuando un servicio falla.
     */
    protected function serviceErrorResult($servicio, $httpCode)
    {
        return [
            'success' => false,
            'message' => "Error HTTP $httpCode en el servicio $servicio",
            'data' => null
        ];
    }

    /**
     * Retorna un error estándar para excepciones.
     */
    protected function exceptionResult($accion, $exception)
    {
        error_log("Exception en $accion: " . $exception->getMessage());
        return [
            'success' => false,
            'message' => "Error al $accion: " . $exception->getMessage(),
            'data' => null
        ];
    }
}
