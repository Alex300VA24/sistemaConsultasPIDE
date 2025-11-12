<?php
require_once __DIR__ . '/../autoload.php';

// Para no mostrar los errores
error_reporting();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Iniciar la sesion
session_start();

// Constante BASE_URL
define('BASE_URL', '/sistemaConsultasPIDE/public/');

// Configurar headers para CORS si es necesario
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
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

    // RUTAS DE USUARIO/AUTH
    case preg_match('#^/api/login$#', $path):
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

    case preg_match('#^/api/consultar-partida-registral$#', $path):
        $controller = new \App\Controllers\ConsultasSunarpController();
        $controller->consultarPartidaRegistral();
        break;

    // Vista de Partidas Registrales
    case $path === '/consulta-partidas':
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            header('Location: /sistemaConsultasPIDE/public/login');
            exit;
        }
        require __DIR__ . '/../views/consulta-partidas.php';
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
?>