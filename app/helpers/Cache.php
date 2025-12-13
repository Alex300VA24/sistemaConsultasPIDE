<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Sistema de caché simple basado en archivos
 * Para datos que no cambian frecuentemente
 */
class Cache
{
    private const CACHE_DIR = __DIR__ . '/../../cache/data/';

    // TTL por defecto para diferentes tipos de datos (en segundos)
    private const DEFAULT_TTL = [
        'modulos' => 300,      // 5 minutos
        'roles' => 300,        // 5 minutos
        'permisos' => 180,     // 3 minutos
        'default' => 60,       // 1 minuto
    ];

    /**
     * Obtiene un valor del caché
     *
     * @param string $key Clave del caché
     * @return mixed|null Valor cacheado o null si no existe/expiró
     */
    public static function get(string $key)
    {
        self::ensureCacheDir();

        $file = self::getFilePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);

        if (!is_array($data) || !isset($data['expires']) || !isset($data['value'])) {
            return null;
        }

        // Verificar expiración
        if (time() > $data['expires']) {
            self::delete($key);
            return null;
        }

        return $data['value'];
    }

    /**
     * Guarda un valor en el caché
     *
     * @param string $key Clave del caché
     * @param mixed $value Valor a cachear
     * @param int|null $ttl Tiempo de vida en segundos (null = usar default)
     * @return bool True si se guardó correctamente
     */
    public static function set(string $key, $value, ?int $ttl = null): bool
    {
        self::ensureCacheDir();

        if ($ttl === null) {
            $ttl = self::getTtlForKey($key);
        }

        $data = [
            'expires' => time() + $ttl,
            'value' => $value,
            'created' => time()
        ];

        $file = self::getFilePath($key);
        return file_put_contents($file, json_encode($data), LOCK_EX) !== false;
    }

    /**
     * Elimina un valor del caché
     *
     * @param string $key Clave a eliminar
     * @return bool True si se eliminó
     */
    public static function delete(string $key): bool
    {
        $file = self::getFilePath($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    /**
     * Verifica si una clave existe en el caché (y no está expirada)
     *
     * @param string $key Clave a verificar
     * @return bool True si existe
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    /**
     * Obtiene un valor del caché o lo genera si no existe
     *
     * @param string $key Clave del caché
     * @param callable $callback Función para generar el valor
     * @param int|null $ttl Tiempo de vida en segundos
     * @return mixed Valor cacheado o generado
     */
    public static function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $value = self::get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }

    /**
     * Limpia todo el caché
     *
     * @return int Número de archivos eliminados
     */
    public static function flush(): int
    {
        if (!is_dir(self::CACHE_DIR)) {
            return 0;
        }

        $deleted = 0;
        $files = glob(self::CACHE_DIR . '*.json');

        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Limpia entradas expiradas del caché
     *
     * @return int Número de archivos eliminados
     */
    public static function cleanup(): int
    {
        if (!is_dir(self::CACHE_DIR)) {
            return 0;
        }

        $deleted = 0;
        $files = glob(self::CACHE_DIR . '*.json');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);

            if (!is_array($data) || !isset($data['expires']) || time() > $data['expires']) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Invalida caché por prefijo/tag
     *
     * @param string $prefix Prefijo de las claves a invalidar
     * @return int Número de entradas eliminadas
     */
    public static function invalidateByPrefix(string $prefix): int
    {
        if (!is_dir(self::CACHE_DIR)) {
            return 0;
        }

        $deleted = 0;
        $pattern = self::CACHE_DIR . md5($prefix) . '*.json';
        $files = glob($pattern);

        // También buscar archivos que contengan el prefijo hasheado
        $allFiles = glob(self::CACHE_DIR . '*.json');
        foreach ($allFiles as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);

            if (isset($data['key']) && strpos($data['key'], $prefix) === 0) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Obtiene el TTL apropiado para una clave
     *
     * @param string $key Clave del caché
     * @return int TTL en segundos
     */
    private static function getTtlForKey(string $key): int
    {
        foreach (self::DEFAULT_TTL as $prefix => $ttl) {
            if (strpos($key, $prefix) === 0) {
                return $ttl;
            }
        }
        return self::DEFAULT_TTL['default'];
    }

    /**
     * Obtiene la ruta del archivo de caché
     *
     * @param string $key Clave del caché
     * @return string Ruta del archivo
     */
    private static function getFilePath(string $key): string
    {
        return self::CACHE_DIR . md5($key) . '.json';
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
     * Obtiene estadísticas del caché
     *
     * @return array Estadísticas
     */
    public static function stats(): array
    {
        if (!is_dir(self::CACHE_DIR)) {
            return ['total' => 0, 'expired' => 0, 'valid' => 0, 'size_bytes' => 0];
        }

        $files = glob(self::CACHE_DIR . '*.json');
        $total = count($files);
        $expired = 0;
        $sizeBytes = 0;

        foreach ($files as $file) {
            $sizeBytes += filesize($file);
            $content = file_get_contents($file);
            $data = json_decode($content, true);

            if (!is_array($data) || !isset($data['expires']) || time() > $data['expires']) {
                $expired++;
            }
        }

        return [
            'total' => $total,
            'expired' => $expired,
            'valid' => $total - $expired,
            'size_bytes' => $sizeBytes,
            'size_human' => self::formatBytes($sizeBytes)
        ];
    }

    /**
     * Formatea bytes a formato legible
     *
     * @param int $bytes Bytes
     * @return string Formato legible
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
