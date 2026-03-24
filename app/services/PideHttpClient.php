<?php

namespace App\Services;

use App\Services\Contracts\PideHttpClientInterface;

/**
 * Cliente HTTP para la plataforma PIDE.
 * Encapsula toda la lógica de cURL (SRP).
 */
class PideHttpClient implements PideHttpClientInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(string $url, ?array $data = null, string $method = 'POST', string $servicio = 'PIDE', int $timeout = 45): array
    {
        $ch = curl_init($url);

        $headers = [
            "Content-Type: application/json; charset=UTF-8",
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
     * {@inheritdoc}
     */
    public function decodeJsonResponse(string $response, string $servicio = 'PIDE'): array
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
}
