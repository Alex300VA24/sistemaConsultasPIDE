<?php
/**
 * API Router - Sistema de Consultas PIDE
 * Archivo: public/api/index.php
 */

// Iniciar sesión
session_start();

// Headers para API REST
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir archivos necesarios
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/controllers/ConsultasController.php';

// Obtener la ruta solicitada
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/sistemaConsultasPIDE/public/api';
$route = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH));

// Instanciar controlador
$consultasController = new ConsultasController();

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtener datos del body (para POST, PUT)
$input = json_decode(file_get_contents('php://input'), true);

// ========================================
// 🔄 ROUTER - MANEJO DE RUTAS
// ========================================

try {
    switch ($route) {
        
        // ========================================
        // 📌 AUTENTICACIÓN
        // ========================================
        
        case '/login':
            if ($method === 'POST') {
                echo json_encode([
                    'success' => true,
                    'message' => 'Login exitoso',
                    'data' => [
                        'usuario' => $input['nombreUsuario']
                    ]
                ]);
            }
            break;

        case '/logout':
            if ($method === 'POST') {
                session_destroy();
                echo json_encode([
                    'success' => true,
                    'message' => 'Sesión cerrada'
                ]);
            }
            break;

        // ========================================
        // 📌 CONSULTAS RENIEC
        // ========================================
        
        case '/consultar-dni':
            if ($method === 'POST') {
                if (!isset($input['dni'])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'DNI no proporcionado'
                    ]);
                    break;
                }

                $dni = trim($input['dni']);

                // Validar formato
                if (!preg_match('/^\d{8}$/', $dni)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'DNI inválido. Debe tener 8 dígitos'
                    ]);
                    break;
                }

                // Llamar al controlador
                $resultado = $consultasController->consultarDNI($dni);
                
                http_response_code($resultado['success'] ? 200 : 404);
                echo json_encode($resultado);
            }
            break;

        // ========================================
        // 📌 CONSULTAS SUNAT
        // ========================================
        
        case '/consultar-ruc':
            if ($method === 'POST') {
                if (!isset($input['ruc'])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'RUC no proporcionado'
                    ]);
                    break;
                }

                $ruc = trim($input['ruc']);

                // Validar formato
                if (!preg_match('/^\d{11}$/', $ruc)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'RUC inválido. Debe tener 11 dígitos'
                    ]);
                    break;
                }

                // Llamar al controlador
                $resultado = $consultasController->consultarRUC($ruc);
                
                http_response_code($resultado['success'] ? 200 : 404);
                echo json_encode($resultado);
            }
            break;

        // ========================================
        // 📌 OTRAS CONSULTAS
        // ========================================
        
        case '/consultar-partidas':
            if ($method === 'POST') {
                $resultado = $consultasController->consultarPartidas($input);
                echo json_encode($resultado);
            }
            break;

        case '/consultar-cobranza':
            if ($method === 'POST') {
                $resultado = $consultasController->consultarCobranza($input);
                echo json_encode($resultado);
            }
            break;

        case '/consultar-papeletas':
            if ($method === 'POST') {
                $resultado = $consultasController->consultarPapeletas($input);
                echo json_encode($resultado);
            }
            break;

        case '/consultar-certificaciones':
            if ($method === 'POST') {
                $resultado = $consultasController->consultarCertificaciones($input);
                echo json_encode($resultado);
            }
            break;

        // ========================================
        // 📌 INICIO / DASHBOARD
        // ========================================
        
        case '/inicio':
            if ($method === 'GET') {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total_consultas' => 150,
                        'consultas_hoy' => 25,
                        'sistemas_activos' => 6
                    ]
                ]);
            }
            break;

        // ========================================
        // ❌ RUTA NO ENCONTRADA
        // ========================================
        
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Ruta no encontrada: ' . $route
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>