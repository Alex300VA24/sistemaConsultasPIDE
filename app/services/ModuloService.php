<?php

namespace App\Services;

use App\Repositories\ModuloRepository;

class ModuloService {
    private $moduloRepository;

    public function __construct() {
        $this->moduloRepository = new ModuloRepository();
    }

    /**
     * Crear un nuevo módulo
     */
    public function crearModulo($data) {
        // Validar y preparar datos
        $datosModulo = [
            'sistema_id' => $data['sistema_id'] ?? 2, // Por defecto sistema 2 (PIDE)
            'padre_id' => $data['modulo_padre_id'] ?? null,
            'codigo' => strtoupper(trim($data['codigo'])),
            'nombre' => trim($data['nombre']),
            'descripcion' => trim($data['descripcion']),
            'url' => trim($data['url']),
            'icono' => trim($data['icono']),
            'orden' => intval($data['orden']),
            'nivel' => intval($data['nivel']),
            'activo' => 1
        ];

        return $this->moduloRepository->crear($datosModulo);
    }

    /**
     * Actualizar un módulo existente
     */
    public function actualizarModulo($data) {
        $moduloId = $data['modulo_id'];

        $datosActualizar = [
            'padre_id' => $data['modulo_padre_id'] ?? null,
            'codigo' => strtoupper(trim($data['codigo'])),
            'nombre' => trim($data['nombre']),
            'descripcion' => trim($data['descripcion']),
            'url' => trim($data['url']),
            'icono' => trim($data['icono']),
            'orden' => intval($data['orden']),
            'nivel' => intval($data['nivel'])
        ];

        return $this->moduloRepository->actualizar($moduloId, $datosActualizar);
    }

    /**
     * Listar todos los módulos con información del padre
     */
    public function listarModulos() {
        return $this->moduloRepository->listarTodos();
    }

    /**
     * Obtener un módulo por su ID
     */
    public function obtenerModuloPorId($moduloId) {
        return $this->moduloRepository->obtenerPorId($moduloId);
    }

    /**
     * Eliminar un módulo
     */
    public function eliminarModulo($moduloId) {
        return $this->moduloRepository->eliminar($moduloId);
    }

    /**
     * Cambiar el estado activo/inactivo de un módulo
     */
    public function cambiarEstadoModulo($moduloId, $estado) {
        return $this->moduloRepository->cambiarEstado($moduloId, $estado);
    }

    /**
     * Verificar si existe un código de módulo
     */
    public function existeCodigoModulo($codigo, $moduloIdExcluir = null) {
        return $this->moduloRepository->existeCodigo($codigo, $moduloIdExcluir);
    }

    /**
     * Verificar si un módulo tiene hijos
     */
    public function tieneModulosHijos($moduloId) {
        return $this->moduloRepository->tieneHijos($moduloId);
    }

    /**
     * Obtener módulos por usuario (según sus permisos de rol)
     */
    public function obtenerModulosPorUsuario($usuarioId) {
        $modulos = $this->moduloRepository->obtenerModulosPorUsuario($usuarioId);
        
        // Organizar módulos en estructura jerárquica
        return $this->organizarModulosJerarquicos($modulos);
    }

    /**
     * Organizar módulos en estructura jerárquica (padres e hijos)
     */
    private function organizarModulosJerarquicos($modulos) {
        $modulosPorId = [];
        $modulosOrganizados = [];

        // Indexar módulos por ID
        foreach ($modulos as $modulo) {
            $modulosPorId[$modulo['MOD_id']] = $modulo;
            $modulosPorId[$modulo['MOD_id']]['hijos'] = [];
        }

        // Organizar en jerarquía
        foreach ($modulosPorId as $id => $modulo) {
            if ($modulo['MOD_padre_id'] === null) {
                // Es un módulo padre
                $modulosOrganizados[] = &$modulosPorId[$id];
            } else {
                // Es un módulo hijo
                if (isset($modulosPorId[$modulo['MOD_padre_id']])) {
                    $modulosPorId[$modulo['MOD_padre_id']]['hijos'][] = &$modulosPorId[$id];
                }
            }
        }

        // Ordenar por orden
        usort($modulosOrganizados, function($a, $b) {
            return $a['MOD_orden'] - $b['MOD_orden'];
        });

        // Ordenar hijos
        foreach ($modulosOrganizados as &$moduloPadre) {
            if (!empty($moduloPadre['hijos'])) {
                usort($moduloPadre['hijos'], function($a, $b) {
                    return $a['MOD_orden'] - $b['MOD_orden'];
                });
            }
        }

        return $modulosOrganizados;
    }
}