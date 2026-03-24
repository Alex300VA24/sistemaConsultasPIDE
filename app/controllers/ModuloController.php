<?php

namespace App\Controllers;

use App\Services\Contracts\ModuloServiceInterface;

/**
 * Controller para operaciones de módulos.
 * Ahora extiende BaseController y recibe ModuloServiceInterface por inyección (DIP).
 */
class ModuloController extends BaseController
{
    /** @var ModuloServiceInterface */
    private $moduloService;

    public function __construct(ModuloServiceInterface $moduloService)
    {
        $this->moduloService = $moduloService;
    }

    /**
     * Crear un nuevo módulo
     */
    public function crearModulo(): void
    {
        try {
            $data = $this->getJsonInput();

            // Validar datos requeridos
            $camposRequeridos = ['codigo', 'nombre', 'descripcion', 'url', 'icono', 'orden', 'nivel'];
            $this->validateRequired($data, $camposRequeridos);

            // Validar que el código sea único
            if ($this->moduloService->existeCodigoModulo($data['codigo'])) {
                $this->errorResponse('El código del módulo ya existe', 400);
                return;
            }

            $resultado = $this->moduloService->crearModulo($data);

            if ($resultado) {
                $this->successResponse(['modulo_id' => $resultado], 'Módulo creado exitosamente', 201);
            } else {
                $this->errorResponse('Error al crear el módulo', 500);
            }
        } catch (\Exception $e) {
            error_log("Error en crearModulo: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Actualizar un módulo existente
     */
    public function actualizarModulo(): void
    {
        try {
            $data = $this->getJsonInput();

            if (!isset($data['modulo_id']) || empty($data['modulo_id'])) {
                $this->errorResponse('El ID del módulo es requerido', 400);
                return;
            }

            $camposRequeridos = ['codigo', 'nombre', 'descripcion', 'url', 'icono', 'orden', 'nivel'];
            $this->validateRequired($data, $camposRequeridos);

            if ($this->moduloService->existeCodigoModulo($data['codigo'], $data['modulo_id'])) {
                $this->errorResponse('El código del módulo ya existe', 400);
                return;
            }

            $resultado = $this->moduloService->actualizarModulo($data);

            if ($resultado) {
                $this->successResponse(null, 'Módulo actualizado exitosamente');
            } else {
                $this->errorResponse('Error al actualizar el módulo', 500);
            }
        } catch (\Exception $e) {
            error_log("Error en actualizarModulo: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Listar todos los módulos
     */
    public function listarModulos(): void
    {
        $this->executeServiceAction(function () {
            $modulos = $this->moduloService->listarModulos();
            return [
                'data' => $modulos,
                'message' => 'Módulos obtenidos correctamente'
            ];
        });
    }

    /**
     * Obtener un módulo específico por ID
     */
    public function obtenerModulo(): void
    {
        $this->executeServiceAction(function () {
            $moduloId = $_GET['id'] ?? null;

            if (!$moduloId) {
                throw new \Exception('ID del módulo no proporcionado');
            }

            $modulo = $this->moduloService->obtenerModuloPorId($moduloId);

            if (!$modulo) {
                throw new \Exception('Módulo no encontrado');
            }

            return ['data' => $modulo, 'message' => 'Módulo obtenido correctamente'];
        });
    }

    /**
     * Eliminar un módulo
     */
    public function eliminarModulo(): void
    {
        try {
            $data = $this->getJsonInput();
            $moduloId = $data['moduloId'] ?? null;

            if (!$moduloId) {
                $this->errorResponse('ID del módulo no proporcionado', 400);
                return;
            }

            if ($this->moduloService->tieneModulosHijos($moduloId)) {
                $this->errorResponse('No se puede eliminar el módulo porque tiene módulos hijos asociados', 400);
                return;
            }

            $resultado = $this->moduloService->eliminarModulo($moduloId);

            if ($resultado) {
                $this->successResponse(null, 'Módulo eliminado exitosamente');
            } else {
                $this->errorResponse('Error al eliminar el módulo', 500);
            }
        } catch (\Exception $e) {
            error_log("Error en eliminarModulo: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Cambiar el estado activo/inactivo de un módulo
     */
    public function toggleEstadoModulo(): void
    {
        try {
            $data = $this->getJsonInput();
            $moduloId = $data['modulo_id'] ?? null;
            $estado = $data['estado'] ?? null;

            if (!$moduloId || !isset($estado)) {
                $this->errorResponse('Datos incompletos', 400);
                return;
            }

            $resultado = $this->moduloService->cambiarEstadoModulo($moduloId, $estado);

            if ($resultado) {
                $this->successResponse(null, 'Estado del módulo actualizado exitosamente');
            } else {
                $this->errorResponse('Error al cambiar el estado del módulo', 500);
            }
        } catch (\Exception $e) {
            error_log("Error en toggleEstadoModulo: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Obtener los módulos del usuario actual (según sus permisos)
     */
    public function obtenerModulosUsuario(): void
    {
        try {
            if (!isset($_SESSION['usuario_id'])) {
                $this->errorResponse('Usuario no autenticado', 401);
                return;
            }

            $modulos = $this->moduloService->obtenerModulosPorUsuario($_SESSION['usuario_id']);

            $this->successResponse($modulos);
        } catch (\Exception $e) {
            error_log("Error en obtenerModulosUsuario: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
}