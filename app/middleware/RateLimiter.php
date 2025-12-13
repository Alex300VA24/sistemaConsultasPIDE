<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\RateLimitException;

/**
 * Rate Limiter - Limita la cantidad de solicitudes por IP
 * Utiliza archivos para almacenar los conteos (simple, sin Redis)
 */
class RateLimiter
{
    private const CACHE_DIR = __DIR__ . '/../../cache/rate_limit/';

    // Configuración por defecto
    private const DEFAULT_LIMITS = [
        'login' => ['attempts' => 5, 'window' => 60],      // 5 intentos por minuto
        'api' => ['attempts' => 60, 'window' => 60],       // 60 peticiones por minuto
        'password_reset' => ['attempts' => 3, 'window' => 300], // 3 intentos cada 5 minutos
    ];

    /**
     * Verifica si la IP actual ha excedido el límite para una acción
     *
     * @param string $action Tipo de acción (login, api, password_reset)
     * @param string|null $identifier Identificador adicional (ej: username)
     * @return bool True si puede continuar
     * @throws RateLimitException Si excede el límite
     */
    public static function check(string $action = 'api', ?string $identifier = null): bool
    {
        self::ensureCacheDir();

        $ip = self::getClientIp();
        $key = self::generateKey($action, $ip, $identifier);
        $limits = self::DEFAULT_LIMITS[$action] ?? self::DEFAULT_LIMITS['api'];

        $data = self::getData($key);

        // Limpiar intentos antiguos
        $data = self::cleanOldAttempts($data, $limits['window']);

        // Verificar límite
        if (count($data['attempts']) >= $limits['attempts']) {
            $retryAfter = $limits['window'] - (time() - min($data['attempts']));
            throw new RateLimitException(
                "Demasiadas solicitudes. Por favor, espere {$retryAfter} segundos."
            );
        }

        // Registrar intento
        $data['attempts'][] = time();
        self::saveData($key, $data);

        return true;
    }

    /**
     * Registra un intento fallido (ej: login fallido)
     *
     * @param string $action Tipo de acción
     * @param string|null $identifier Identificador adicional
     * @return void
     */
    public static function hit(string $action, ?string $identifier = null): void
    {
        self::ensureCacheDir();

        $ip = self::getClientIp();
        $key = self::generateKey($action, $ip, $identifier);

        $data = self::getData($key);
        $data['attempts'][] = time();
        self::saveData($key, $data);
    }

    /**
     * Limpia los intentos para una IP/acción específica
     * Útil después de un login exitoso
     *
     * @param string $action Tipo de acción
     * @param string|null $identifier Identificador adicional
     * @return void
     */
    public static function clear(string $action, ?string $identifier = null): void
    {
        $ip = self::getClientIp();
        $key = self::generateKey($action, $ip, $identifier);
        $file = self::CACHE_DIR . $key . '.json';

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Obtiene los intentos restantes para una acción
     *
     * @param string $action Tipo de acción
     * @param string|null $identifier Identificador adicional
     * @return int Intentos restantes
     */
    public static function getRemainingAttempts(string $action = 'api', ?string $identifier = null): int
    {
        $ip = self::getClientIp();
        $key = self::generateKey($action, $ip, $identifier);
        $limits = self::DEFAULT_LIMITS[$action] ?? self::DEFAULT_LIMITS['api'];

        $data = self::getData($key);
        $data = self::cleanOldAttempts($data, $limits['window']);

        return max(0, $limits['attempts'] - count($data['attempts']));
    }

    /**
     * Obtiene la IP del cliente
     *
     * @return string IP del cliente
     */
    private static function getClientIp(): string
    {
        // Verificar headers de proxy en orden de confiabilidad
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy genérico
            'HTTP_X_REAL_IP',            // Nginx
            'REMOTE_ADDR'                // Directo
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // X-Forwarded-For puede contener múltiples IPs
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1'; // Fallback
    }

    /**
     * Genera una clave única para el rate limit
     *
     * @param string $action Tipo de acción
     * @param string $ip IP del cliente
     * @param string|null $identifier Identificador adicional
     * @return string Clave hash
     */
    private static function generateKey(string $action, string $ip, ?string $identifier): string
    {
        $key = $action . '_' . $ip;
        if ($identifier !== null) {
            $key .= '_' . $identifier;
        }
        return md5($key);
    }

    /**
     * Obtiene los datos del rate limit para una clave
     *
     * @param string $key Clave del rate limit
     * @return array Datos del rate limit
     */
    private static function getData(string $key): array
    {
        $file = self::CACHE_DIR . $key . '.json';

        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if (is_array($data) && isset($data['attempts'])) {
                return $data;
            }
        }

        return ['attempts' => []];
    }

    /**
     * Guarda los datos del rate limit
     *
     * @param string $key Clave del rate limit
     * @param array $data Datos a guardar
     * @return void
     */
    private static function saveData(string $key, array $data): void
    {
        $file = self::CACHE_DIR . $key . '.json';
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Limpia intentos antiguos fuera de la ventana de tiempo
     *
     * @param array $data Datos del rate limit
     * @param int $window Ventana de tiempo en segundos
     * @return array Datos limpios
     */
    private static function cleanOldAttempts(array $data, int $window): array
    {
        $cutoff = time() - $window;
        $data['attempts'] = array_values(array_filter(
            $data['attempts'],
            fn($timestamp) => $timestamp > $cutoff
        ));
        return $data;
    }

    /**
     * Asegura que el directorio de caché exista
     *
     * @return void
     */
    private static function ensureCacheDir(): void
    {
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
    }

    /**
     * Limpia archivos de rate limit antiguos (mantenimiento)
     * Debería ejecutarse periódicamente (ej: cada hora)
     *
     * @param int $maxAge Edad máxima en segundos (default: 1 hora)
     * @return int Número de archivos eliminados
     */
    public static function cleanup(int $maxAge = 3600): int
    {
        if (!is_dir(self::CACHE_DIR)) {
            return 0;
        }

        $deleted = 0;
        $files = glob(self::CACHE_DIR . '*.json');

        foreach ($files as $file) {
            if (filemtime($file) < time() - $maxAge) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
