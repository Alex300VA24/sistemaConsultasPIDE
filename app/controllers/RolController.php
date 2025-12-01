<?php
namespace App\Controllers;

use App\Services\RolService;
use App\Helpers\Debug;

class RolController {
    private $rolService;

    public function __construct() {
        $this->rolService = new RolService();
    }

    public function crearRol() {
        try {
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
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function actualizarRol() {
        try {
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
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function listarRoles() {
        try {
            $roles = $this->rolService->listarRoles();
            echo json_encode([
                'success' => true,
                'data' => $roles
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
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
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function listarModulos() {
        try {
            $modulos = $this->rolService->listarModulos();
            echo json_encode([
                'success' => true,
                'data' => $modulos
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function eliminarRol() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $rolId = $data['rol_id'] ?? 0;
            
            $resultado = $this->rolService->eliminarRol($rolId, $_SESSION['usuario_id'] ?? null);
            
            echo json_encode([
                'success' => true,
                'message' => $resultado['mensaje']
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}