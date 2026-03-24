<?php

namespace App\Config;

/**
 * Carga las variables del archivo .env una sola vez (SRP).
 * Se registra como singleton en el contenedor IoC.
 */
class EnvLoader
{
    /** @var bool */
    private $loaded = false;

    /**
     * Carga las variables del archivo .env.
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $envFile = __DIR__ . '/../../.env';

        if (!file_exists($envFile)) {
            return;
        }

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

        $this->loaded = true;
    }

    /**
     * Obtiene una variable de entorno, con valor por defecto.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function get(string $key, string $default = ''): string
    {
        $this->load();
        return $_ENV[$key] ?? $default;
    }
}
