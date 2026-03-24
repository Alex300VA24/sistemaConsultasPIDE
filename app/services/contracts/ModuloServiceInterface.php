<?php

namespace App\Services\Contracts;

/**
 * Interface para el servicio de módulos.
 * Sin type hints estrictos para compatibilidad con la implementación existente.
 */
interface ModuloServiceInterface
{
    public function crearModulo($data);
    public function actualizarModulo($data);
    public function listarModulos();
    public function obtenerModuloPorId($moduloId);
    public function eliminarModulo($moduloId);
    public function cambiarEstadoModulo($moduloId, $estado);
    public function existeCodigoModulo($codigo, $moduloIdExcluir = null);
    public function tieneModulosHijos($moduloId);
    public function obtenerModulosPorUsuario($usuarioId);
}
