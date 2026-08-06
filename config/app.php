<?php
// config/app.php

$envFile = getenv('PIDE_ENV_FILE') ?: __DIR__ . '/../.env';
$env = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => false,
    'APP_URL' => null,
];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if ($name === 'APP_ENV') $env['APP_ENV'] = $value;
        if ($name === 'APP_DEBUG') $env['APP_DEBUG'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        if ($name === 'APP_URL') $env['APP_URL'] = $value;
    }
}

// Lista exacta de orígenes permitidos (CORS). Nunca se usa '*' como fallback.
$allowedOrigins = [];

if (empty($env['APP_URL'])) {
    if ($env['APP_ENV'] === 'production') {
        throw new \RuntimeException(
            'APP_URL no está definida. Configure el origen permitido en el archivo .env antes de usar el sistema en producción.'
        );
    }
} else {
    $parsed = parse_url($env['APP_URL']);
    if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
        $allowedOrigins[] = $parsed['scheme'] . '://' . $parsed['host']
                          . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
    }
}

// Definir BASE_URL como constante global
define('BASE_URL', '/MDESistemaPIDE/public/');

return [
    'env' => $env['APP_ENV'],
    'debug' => $env['APP_DEBUG'],
    'url' => $env['APP_URL'],
    'base_url' => BASE_URL,
    
    'cors' => [
        'allowed_origins' => $allowedOrigins,
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-CSRF-Token'],
    ],
    
    'session' => [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'httponly' => true,
        'samesite' => 'Strict'
    ]
];