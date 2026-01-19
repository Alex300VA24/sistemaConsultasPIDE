<?php

namespace App\Controllers;

use App\Services\ModuloService;

class ModuloController
{
    private $moduloService;

    public function __construct()
    {
        $this->moduloService = new ModuloService();
    }

    /**
     * Crear un nuevo módulo
     */
    public function crearModulo()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar datos requeridos
            $camposRequeridos = ['codigo', 'nombre', 'descripcion', 'url', 'icono', 'orden', 'nivel'];
            foreach ($camposRequeridos as $campo) {
                if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "El campo {$campo} es requerido"
                    ]);
                    return;
                }
            }

            // Validar que el código sea único
            if ($this->moduloService->existeCodigoModulo($data['codigo'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'El código del módulo ya existe'
                ]);
                return;
            }

            // Crear el módulo
            $resultado = $this->moduloService->crearModulo($data);

            if ($resultado) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Módulo creado exitosamente',
                    'data' => ['modulo_id' => $resultado]
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al crear el módulo'
                ]);
            }
        } catch (\Exception $e) {
            error_log("Error en crearModulo: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar un módulo existente
     */
    public function actualizarModulo()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar que exista el ID
            if (!isset($data['modulo_id']) || empty($data['modulo_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'El ID del módulo es requerido'
                ]);
                return;
            }

            // Validar datos requeridos
            $camposRequeridos = ['codigo', 'nombre', 'descripcion', 'url', 'icono', 'orden', 'nivel'];
            foreach ($camposRequeridos as $campo) {
                if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "El campo {$campo} es requerido"
                    ]);
                    return;
                }
            }

            // Validar que el código sea único (excepto para el módulo actual)
            if ($this->moduloService->existeCodigoModulo($data['codigo'], $data['modulo_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'El código del módulo ya existe'
                ]);
                return;
            }

            // Actualizar el módulo
            $resultado = $this->moduloService->actualizarModulo($data);

            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Módulo actualizado exitosamente'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al actualizar el módulo'
                ]);
            }
        } catch (\Exception $e) {
            error_log("Error en actualizarModulo: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Listar todos los módulos
     */
    public function listarModulos()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        try {
            $modulos = $this->moduloService->listarModulos();

            echo json_encode([
                'success' => true,
                'data' => $modulos,
                'total' => count($modulos)
            ]);
        } catch (\Exception $e) {
            error_log("Error en listarModulos: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al listar los módulos',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener un módulo específico por ID
     */
    public function obtenerModulo()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        try {
            $moduloId = $_GET['id'] ?? null;

            if (!$moduloId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID del módulo no proporcionado'
                ]);
                return;
            }

            $modulo = $this->moduloService->obtenerModuloPorId($moduloId);

            if ($modulo) {
                echo json_encode([
                    'success' => true,
                    'data' => $modulo
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Módulo no encontrado'
                ]);
            }
        } catch (\Exception $e) {
            error_log("Error en obtenerModulo: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener el módulo',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar un módulo
     */
    public function eliminarModulo()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $moduloId = $data['modulo_id'] ?? null;

            if (!$moduloId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID del módulo no proporcionado'
                ]);
                return;
            }

            // Verificar si el módulo tiene hijos
            if ($this->moduloService->tieneModulosHijos($moduloId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No se puede eliminar el módulo porque tiene módulos hijos asociados'
                ]);
                return;
            }

            $resultado = $this->moduloService->eliminarModulo($moduloId);

            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Módulo eliminado exitosamente'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al eliminar el módulo'
                ]);
            }
        } catch (\Exception $e) {
            error_log("Error en eliminarModulo: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar el módulo',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cambiar el estado activo/inactivo de un módulo
     */
    public function toggleEstadoModulo()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $moduloId = $data['modulo_id'] ?? null;
            $estado = $data['estado'] ?? null;

            if (!$moduloId || !isset($estado)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos incompletos'
                ]);
                return;
            }

            $resultado = $this->moduloService->cambiarEstadoModulo($moduloId, $estado);

            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Estado del módulo actualizado exitosamente'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al cambiar el estado del módulo'
                ]);
            }
        } catch (\Exception $e) {
            error_log("Error en toggleEstadoModulo: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener los módulos del usuario actual (según sus permisos)
     */
    public function obtenerModulosUsuario()
    {
        try {
            // Verificar sesión
            if (!isset($_SESSION['usuario_id'])) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ]);
                return;
            }

            $usuarioId = $_SESSION['usuario_id'];
            $modulos = $this->moduloService->obtenerModulosPorUsuario($usuarioId);

            echo json_encode([
                'success' => true,
                'data' => $modulos
            ]);
        } catch (\Exception $e) {
            error_log("Error en obtenerModulosUsuario: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener los módulos del usuario',
                'error' => $e->getMessage()
            ]);
        }
    }
}
