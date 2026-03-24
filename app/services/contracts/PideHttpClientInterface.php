<?php

namespace App\Services\Contracts;

/**
 * Interface para el cliente HTTP de la plataforma PIDE.
 */
interface PideHttpClientInterface
{
    /**
     * Ejecuta una petición HTTP (cURL) genérica.
     *
     * @param string      $url       URL del servicio
     * @param array|null  $data      Datos a enviar (se codifican como JSON)
     * @param string      $method    Método HTTP (POST o GET)
     * @param string      $servicio  Nombre del servicio para mensajes de error
     * @param int         $timeout   Timeout en segundos
     * @return array ['success' => bool, 'httpCode' => int, 'response' => string|null, 'error' => string|null]
     */
    public function execute(string $url, ?array $data = null, string $method = 'POST', string $servicio = 'PIDE', int $timeout = 45): array;

    /**
     * Decodifica respuesta JSON de forma segura.
     *
     * @param string $response  Respuesta raw
     * @param string $servicio  Nombre del servicio para mensajes de error
     * @return array ['success' => bool, 'data' => array|null, 'message' => string]
     */
    public function decodeJsonResponse(string $response, string $servicio = 'PIDE'): array;
}
