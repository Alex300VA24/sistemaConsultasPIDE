<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Middleware de protección CSRF
 * Genera y valida tokens para prevenir ataques Cross-Site Request Forgery
 */
class CsrfMiddleware
{
    private const TOKEN_NAME = 'csrf_token';
    private const HEADER_NAME = 'X-CSRF-TOKEN';

    /**
     * Genera un nuevo token CSRF y lo almacena en la sesión
     *
     * @return string Token generado
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::TOKEN_NAME] = $token;
        $_SESSION['csrf_token_time'] = time();

        return $token;
    }

    /**
     * Obtiene el token CSRF actual de la sesión
     *
     * @return string|null Token actual o null si no existe
     */
    public static function getToken(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION[self::TOKEN_NAME] ?? null;
    }

    /**
     * Valida el token CSRF enviado en la petición
     *
     * @return bool True si el token es válido
     */
    public static function validateToken(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Obtener token de la sesión
        $sessionToken = $_SESSION[self::TOKEN_NAME] ?? null;

        if ($sessionToken === null) {
            return false;
        }

        // Verificar expiración (tokens válidos por 1 hora)
        $tokenTime = $_SESSION['csrf_token_time'] ?? 0;
        if (time() - $tokenTime > 3600) {
            self::regenerateToken();
            return false;
        }

        // Obtener token de la petición (header o body)
        $requestToken = self::getRequestToken();

        if ($requestToken === null) {
            return false;
        }

        return hash_equals($sessionToken, $requestToken);
    }

    /**
     * Obtiene el token CSRF de la petición actual
     *
     * @return string|null Token de la petición o null
     */
    private static function getRequestToken(): ?string
    {
        // Primero buscar en headers
        $headers = getallheaders();
        if (isset($headers[self::HEADER_NAME])) {
            return $headers[self::HEADER_NAME];
        }

        // También buscar con el nombre en minúsculas
        foreach ($headers as $name => $value) {
            if (strtolower($name) === strtolower(self::HEADER_NAME)) {
                return $value;
            }
        }

        // Buscar en el body JSON
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $data = json_decode($input, true);
            if (isset($data['_token'])) {
                return $data['_token'];
            }
        }

        // Buscar en POST data
        if (isset($_POST['_token'])) {
            return $_POST['_token'];
        }

        return null;
    }

    /**
     * Regenera el token CSRF
     *
     * @return string Nuevo token
     */
    public static function regenerateToken(): string
    {
        return self::generateToken();
    }

    /**
     * Middleware: Verifica CSRF para métodos que modifican datos
     * Solo se aplica a POST, PUT, DELETE, PATCH
     *
     * @throws \App\Exceptions\ApiException Si el token es inválido
     * @return bool True si la validación pasa o no es necesaria
     */
    public static function handle(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Solo validar para métodos que modifican datos
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return true;
        }

        // Excepciones: endpoints de login no requieren CSRF inicial
        $path = $_SERVER['REQUEST_URI'] ?? '';
        $csrfExemptPaths = [
            '/api/login',
            '/api/validar-cui',
            '/api/csrf-token' // Endpoint para obtener token
        ];

        foreach ($csrfExemptPaths as $exemptPath) {
            if (strpos($path, $exemptPath) !== false) {
                return true;
            }
        }

        return self::validateToken();
    }

    /**
     * Genera un input hidden con el token CSRF para formularios HTML
     *
     * @return string HTML del input hidden
     */
    public static function getHiddenInput(): string
    {
        $token = self::getToken() ?? self::generateToken();
        return sprintf('<input type="hidden" name="_token" value="%s">', htmlspecialchars($token));
    }

    /**
     * Genera el meta tag para incluir en el head del HTML
     * Útil para aplicaciones que usan AJAX
     *
     * @return string HTML del meta tag
     */
    public static function getMetaTag(): string
    {
        $token = self::getToken() ?? self::generateToken();
        return sprintf('<meta name="csrf-token" content="%s">', htmlspecialchars($token));
    }
}
