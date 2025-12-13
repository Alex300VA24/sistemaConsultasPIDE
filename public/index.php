<?php
require_once __DIR__ . '/../autoload.php';

// Importar middlewares de seguridad
use App\Middleware\CsrfMiddleware;
use App\Middleware\RateLimiter;
use App\Exceptions\RateLimitException;

// Para no mostrar los errores en producción
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Descomentar para desarrollo:
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);

date_default_timezone_set('America/Lima');

// Configurar sesión segura
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '0'); // Cambiar a '1' en producción con HTTPS
ini_set('session.use_strict_mode', '1');

// Iniciar la sesión
session_start();

// Constante BASE_URL
define('BASE_URL', '/sistemaConsultasPIDE/public/');

// ========================================
// HEADERS DE SEGURIDAD
// ========================================
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// ========================================
// CORS RESTRICTIVO
// ========================================
$allowed_origins = [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:80',
    // Agregar dominios de producción aquí:
    // 'https://tu-dominio.gob.pe',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
} elseif (empty($origin)) {
    // Permitir peticiones del mismo origen (sin header Origin)
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ========================================
// GENERAR TOKEN CSRF SI NO EXISTE
// ========================================
if (!isset($_SESSION['csrf_token'])) {
    CsrfMiddleware::generateToken();
}

// Router simple
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($script_name, '', $request_uri);
$path = parse_url($path, PHP_URL_PATH);

if ($path[0] !== '/') {
    $path = '/' . $path;
}

error_log("PATH RECIBIDO: " . $path);

// Rutas de la API
switch (true) {

    // ========================================
    // RUTA PARA OBTENER TOKEN CSRF
    // ========================================
    case preg_match('#^/api/csrf-token$#', $path):
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'token' => CsrfMiddleware::getToken() ?? CsrfMiddleware::generateToken()
        ]);
        break;

    // RUTAS DE USUARIO/AUTH
    case preg_match('#^/api/login$#', $path):
        // Rate limiting para login (5 intentos por minuto)
        try {
            RateLimiter::check('login');
        } catch (RateLimitException $e) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
        $controller = new \App\Controllers\UsuarioController();
        $controller->login();
        break;

    case preg_match('#^/api/validar-cui$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->validarCUI();
        break;

    case preg_match('#^/api/logout$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->logout();
        break;

    case preg_match('#^/api/crear-usuario$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->crearUsuario();
        break;

    case preg_match('#^/api/eliminar-usuario$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->eliminarUsuario();
        break;

    case preg_match('#^/api/obtener-dni-pass$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->obtenerDniYPassword();
        break;

    case preg_match('#^/api/obtener-usuario$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->obtenerUsuario();
        break;

    case preg_match('#^/api/listar-usuarios$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->listarUsuarios();
        break;

    // RUTAS DE ROLES
    case preg_match('#^/api/rol/crear$#', $path):
        $controller = new \App\Controllers\RolController();
        $controller->crearRol();
        break;

    case preg_match('#^/api/rol/actualizar$#', $path):
        $controller = new \App\Controllers\RolController();
        $controller->actualizarRol();
        break;

    case preg_match('#^/api/rol/listar$#', $path):
        $controller = new \App\Controllers\RolController();
        $controller->listarRoles();
        break;

    case preg_match('#^/api/rol/obtener$#', $path):
        $controller = new \App\Controllers\RolController();
        $controller->obtenerRol();
        break;

    case preg_match('#^/api/rol/modulos$#', $path):
        $controller = new \App\Controllers\RolController();
        $controller->listarModulos();
        break;

    case preg_match('#^/api/rol/eliminar$#', $path):
        $controller = new \App\Controllers\RolController();
        $controller->eliminarRol();
        break;

    case preg_match('#^/api/actualizar-usuario$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->actualizarUsuario();
        break;

    case preg_match('#^/api/actualizar-password$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->actualizarPassword();
        break;

    // 📌 RUTA PARA ACTUALIZAR PASSWORD EN RENIEC
    case preg_match('#^/api/actualizar-password-reniec$#', $path):
        $controller = new \App\Controllers\ConsultasReniecController();
        $controller->actualizarPasswordRENIEC();
        break;

    // En tu archivo de rutas (routes.php o similar)
    case preg_match('#^/api/usuario/actual$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->obtenerUsuarioActual();
        break;

    case preg_match('#^/api/usuario/rol$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->obtenerRoles();
        break;

    case preg_match('#^/api/usuario/tipo-personal$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->obtenerTipoPersonal();
        break;

    // RUTA DE INICIO/DASHBOARD
    case preg_match('#^/api/inicio$#', $path):
        $controller = new \App\Controllers\DashboardController();
        $controller->obtenerDatosInicio();
        break;

    // RUTAS DE CONSULTAS RENIEC
    case preg_match('#^/api/consultar-dni$#', $path):
        $controller = new \App\Controllers\ConsultasReniecController();
        $controller->consultarDNI();
        break;

    // RUTAS DE CONSULTAS SUNAT
    case preg_match('#^/api/consultar-ruc$#', $path):
        $controller = new \App\Controllers\ConsultasSunatController();
        $controller->consultarRUC();
        break;

    // RUTAS DE CONSULTAS SUNARP
    case preg_match('#^/api/buscar-persona-natural-sunarp$#', $path):
        $controller = new \App\Controllers\ConsultasSunarpController();
        $controller->buscarPersonaNatural();
        break;

    case preg_match('#^/api/buscar-persona-juridica-sunarp$#', $path):
        $controller = new \App\Controllers\ConsultasSunarpController();
        $controller->buscarPersonaJuridica();
        break;

    case preg_match('#^/api/sunarp/tsirsarp-natural$#', $path):
        $controller = new \App\Controllers\ConsultasSunarpController();
        $controller->consultarTSIRSARPNatural();
        break;

    case preg_match('#^/api/sunarp/tsirsarp-juridica$#', $path):
        $controller = new \App\Controllers\ConsultasSunarpController();
        $controller->consultarTSIRSARPJuridica();
        break;

    // Vista de Partidas Registrales
    case $path === '/consulta-partidas':
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            header('Location: /sistemaConsultasPIDE/public/login');
            exit;
        }
        require __DIR__ . '/../views/consulta-partidas.php';
        break;

    // RUTAS DE MÓDULOS
    case preg_match('#^/api/modulo/crear$#', $path):
        $controller = new \App\Controllers\ModuloController();
        $controller->crearModulo();
        break;

    case preg_match('#^/api/modulo/actualizar$#', $path):
        $controller = new \App\Controllers\ModuloController();
        $controller->actualizarModulo();
        break;

    case preg_match('#^/api/modulo/listar$#', $path):
        $controller = new \App\Controllers\ModuloController();
        $controller->listarModulos();
        break;

    case preg_match('#^/api/modulo/obtener$#', $path):
        $controller = new \App\Controllers\ModuloController();
        $controller->obtenerModulo();
        break;

    case preg_match('#^/api/modulo/eliminar$#', $path):
        $controller = new \App\Controllers\ModuloController();
        $controller->eliminarModulo();
        break;

    case preg_match('#^/api/modulo/toggle-estado$#', $path):
        $controller = new \App\Controllers\ModuloController();
        $controller->toggleEstadoModulo();
        break;

    case preg_match('#^/api/modulo/usuario$#', $path):
        $controller = new \App\Controllers\ModuloController();
        $controller->obtenerModulosUsuario();
        break;

    // Ruta para cambiar password obligatorio
    case preg_match('#^/api/usuario/cambiar-password$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->cambiarPassword();
        break;

    // VISTAS

    // Vista de Login
    case $path === '/' || $path === '/login':
        require __DIR__ . '/../views/login.php';
        break;

    // Vista de Dashboard
    case $path === '/dashboard':
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            header('Location: /sistemaConsultasPIDE/public/login');
            exit;
        }
        require __DIR__ . '/../views/dashboard/index.php';
        break;

    // RUTA NO ENCONTRADA
    default:
        http_response_code(404);
        echo json_encode([
            'error' => 'Ruta no encontrada',
            'path' => $path
        ]);
        break;
}
