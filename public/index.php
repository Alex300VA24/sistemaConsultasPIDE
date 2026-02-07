<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Router;
use App\Core\Request;
use App\Middleware\SecurityMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\AuthMiddleware;

date_default_timezone_set('America/Lima');

$config = require __DIR__ . '/../config/app.php';

//error_reporting(E_ALL);
//ini_set('display_startup_errors', $config['debug'] ? '1' : '0');
//ini_set('display_errors', $config['debug'] ? '1' : '0');
//ini_set('log_errors', '1');

SecurityMiddleware::initialize($config);

$request = new Request();
$router = new Router();

// Middleware global
$router->addMiddleware(new SecurityMiddleware());
$router->addMiddleware(new CsrfMiddleware());

// ============================================
// RUTAS PÚBLICAS
// ============================================
$router->get('/api/csrf-token', function () {
    header('Content-Type: application/json');
    echo json_encode(['token' => $_SESSION['csrf_token'] ?? '']);
});

$router->post('/api/login', 'UsuarioController@login', ['skip_csrf' => true]);
$router->post('/api/validar-cui', 'UsuarioController@validarCUI');
$router->post('/api/logout', 'UsuarioController@logout');

// ============================================
// RUTAS PROTEGIDAS
// ============================================
$router->group(['middleware' => AuthMiddleware::class], function ($router) {

    $router->get('/api/inicio', 'DashboardController@obtenerDatosInicio');

    // USUARIOS
    $router->group(['prefix' => '/api/usuarios'], function ($router) {
        $router->get('', 'UsuarioController@listarUsuarios');
        $router->post('/registrar', 'UsuarioController@crearUsuario');
        $router->post('/obtener-dni-pass', 'UsuarioController@obtenerDNIYPassword');
        $router->get('/obtener', 'UsuarioController@obtenerUsuario');
        $router->get('/actual', 'UsuarioController@obtenerUsuarioActual');
        $router->get('/tipo-personal', 'UsuarioController@obtenerTipoPersonal');
        $router->post('/eliminar', 'UsuarioController@eliminarUsuario');
        $router->put('/actualizar', 'UsuarioController@actualizarUsuario');
        $router->put('/actualizar-password', 'UsuarioController@actualizarPassword');
        $router->post('/cambiar-pass', 'UsuarioController@cambiarPassword');
        $router->get('/rol', 'UsuarioController@obtenerRoles');
    });

    // ROLES
    $router->group(['prefix' => '/api/roles'], function ($router) {
        $router->post('/crear', 'RolController@crearRol');
        $router->get('', 'RolController@listarRoles');
        $router->get('/modulos', 'RolController@listarModulos');
        $router->put('/actualizar', 'RolController@actualizarRol');
        $router->post('/eliminar', 'RolController@eliminarRol');
        $router->get('/obtener', 'RolController@obtenerRol');
    });

    $router->post('/api/actualizar-pass-reniec', 'ConsultasReniecController@actualizarPasswordRENIEC');

    // CONSULTAS
    $router->group(['prefix' => '/api/consultas'], function ($router) {
        $router->post('/dni', 'ConsultasReniecController@consultarDNI');
        $router->post('/ruc', 'ConsultasSunatController@consultarRUC');
        $router->post('/buscar/natural', 'ConsultasSunarpController@buscarPersonaNatural');
        $router->post('/buscar/juridica', 'ConsultasSunarpController@buscarPersonaJuridica');
        $router->post('/partidas/natural', 'ConsultasSunarpController@consultarTSIRSARPNatural');
        $router->post('/partidas/juridica', 'ConsultasSunarpController@consultarTSIRSARPJuridica');
        $router->post('/partidas/lasirsarp', 'ConsultasSunarpController@consultarLASIRSARP');
        $router->get('/goficinas', 'ConsultasSunarpController@consultarGOficina');
        $router->post('/sunarp/cargar-detalle-partida', 'ConsultasSunarpController@cargarDetallePartida');
    });


    // MODULOS
    $router->group(['prefix' => '/api/modulos'], function ($router) {
        $router->get('/obtener', 'ModuloController@obtenerModulo');
        $router->put('/actualizar', 'ModuloController@actualizarModulo');
        $router->get('', 'ModuloController@listarModulos');
        $router->post('/registrar', 'ModuloController@crearModulo');
        $router->post('/eliminar', 'ModuloController@eliminarModulo');
        $router->post('/obtener-por-usuario', 'ModuloController@obtenerModulosUsuario');
        $router->post('/toggle-estado', 'ModuloController@toggleEstadoModulo');
    });
});

// ============================================
// VISTAS
// ============================================
$router->get('/', function () {
    require __DIR__ . '/../app/views/login.php';
});

$router->get('/login', function () {
    require __DIR__ . '/../app/views/login.php';
});

$router->get('/dashboard', function () {
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        require __DIR__ . '/../app/views/login.php';
        exit;
    }
    require __DIR__ . '/../app/views/dashboard/index.php';
});

$router->dispatch($request);
