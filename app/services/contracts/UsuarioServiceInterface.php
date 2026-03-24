<?php

namespace App\Services\Contracts;

/**
 * Interface para el servicio de usuarios.
 * Sin type hints estrictos para compatibilidad con la implementación existente.
 */
interface UsuarioServiceInterface
{
    public function login($nombreUsuario, $password);
    public function validarCUI($nombreUsuario, $password, $cui);
    public function cambiarPasswordObligatorio($usuarioId, $passwordActual, $passwordNueva);
    public function crearUsuario($data);
    public function listarUsuarios();
    public function obtenerRoles();
    public function obtenerTipoPersonal();
    public function obtenerUsuarioPorId($usuarioId);
    public function eliminarUsuario($usuarioId);
    public function obtenerDni($nombreUsuario);
    public function actualizarUsuario($datos);
    public function actualizarPassword($datos);
}
