<?php

/**
 * Bootstrap para PHPUnit
 * Configura el entorno de pruebas
 */

// Cargar autoloader de Composer si existe
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Cargar autoloader del proyecto
require_once __DIR__ . '/../autoload.php';

// Configurar zona horaria
date_default_timezone_set('America/Lima');

// Simular sesión para pruebas
if (session_status() === PHP_SESSION_NONE) {
    // En pruebas, usar almacenamiento en memoria
    ini_set('session.save_handler', 'files');
    session_start();
}

// Definir constantes de prueba
if (!defined('BASE_URL')) {
    define('BASE_URL', '/sistemaConsultasPIDE/public/');
}

// Configurar variables de entorno para pruebas
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'testing';

/**
 * Clase base para pruebas con utilidades comunes
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Limpia la sesión antes de cada prueba
     */
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    /**
     * Limpia después de cada prueba
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
    }
}
