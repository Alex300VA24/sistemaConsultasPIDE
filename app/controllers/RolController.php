<?php
namespace App\Controllers;

use App\Services\RolService;
use App\Helpers\Debug;
use App\Middleware\SecurityMiddleware;

class RolController extends BaseController {
    private $rolService;

    public function __construct() {
        $this->rolService = new RolService();
    }

    public function crearRol() {
        try {
            SecurityMiddleware::requirePermission('roles.crear');

            $data = json_decode(file_get_contents('php://input'), true);
            
            $datos = [
                'codigo' => $data['codigo'] ?? '',
                'nombre' => $data['nombre'] ?? '',
                'descripcion' => $data['descripcion'] ?? '',
                'nivel' => $data['nivel'] ?? 1,
                'sistema_id' => 2, // Sistema por defecto
                'modulos' => $data['modulos'] ?? [],
                'creado_por' => $_SESSION['usuario_id'] ?? null
            ];
            
            $resultado = $this->rolService->crearRol($datos);
            
            echo json_encode([
                'success' => true,
                'message' => $resultado['mensaje'],
                'rol_id' => $resultado['rol_id']
            ]);
        } catch (\Throwable $e) {
            $this->handleError($e, 400);
        }
    }

    public function actualizarRol() {
        try {
            SecurityMiddleware::requirePermission('roles.actualizar');

            $data = json_decode(file_get_contents('php://input'), true);
            
            $datos = [
                'rol_id' => $data['rol_id'] ?? 0,
                'codigo' => $data['codigo'] ?? '',
                'nombre' => $data['nombre'] ?? '',
                'descripcion' => $data['descripcion'] ?? '',
                'nivel' => $data['nivel'] ?? 1,
                'sistema_id' => 2,
                'modulos' => $data['modulos'] ?? [],
                'actualizado_por' => $_SESSION['usuario_id'] ?? null
            ];
            
            $resultado = $this->rolService->actualizarRol($datos);
            
            echo json_encode([
                'success' => true,
                'message' => $resultado['mensaje']
            ]);
        } catch (\Throwable $e) {
            $this->handleError($e, 400);
        }
    }

    public function listarRoles() {
        try {
            $roles = $this->rolService->listarRoles();
            echo json_encode([
                'success' => true,
                'data' => $roles
            ]);
        } catch (\Throwable $e) {
            $this->handleError($e, 500);
        }
    }

    public function obtenerRol() {
        try {
            $rolId = $_GET['id'] ?? 0;
            $rol = $this->rolService->obtenerRol($rolId);
            echo json_encode([
                'success' => true,
                'data' => $rol
            ]);
        } catch (\Throwable $e) {
            $this->handleError($e, 500);
        }
    }

    public function listarModulos() {
        try {
            $modulos = $this->rolService->listarModulos();
            echo json_encode([
                'success' => true,
                'data' => $modulos
            ]);
        } catch (\Throwable $e) {
            $this->handleError($e, 500);
        }
    }

    public function eliminarRol() {
        try {
            SecurityMiddleware::requirePermission('roles.eliminar');

            $data = json_decode(file_get_contents('php://input'), true);
            $rolId = $data['rol_id'] ?? 0;
            
            $resultado = $this->rolService->eliminarRol($rolId, $_SESSION['usuario_id'] ?? null);
            
            echo json_encode([
                'success' => true,
                'message' => $resultado['mensaje']
            ]);
        } catch (\Throwable $e) {
            $this->handleError($e, 400);
        }
    }
}