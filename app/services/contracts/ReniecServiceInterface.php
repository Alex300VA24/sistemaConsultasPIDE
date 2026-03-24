<?php

namespace App\Services\Contracts;

/**
 * Interface para el servicio de consultas RENIEC.
 */
interface ReniecServiceInterface
{
    /**
     * Consulta datos de una persona por DNI en RENIEC.
     *
     * @param string $dni          DNI a consultar
     * @param string $dniUsuario   DNI del usuario que realiza la consulta
     * @param string $passwordPIDE Contraseña PIDE
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function consultarDNI(string $dni, string $dniUsuario, string $passwordPIDE): array;

    /**
     * Obtiene datos RENIEC con formato para búsqueda de persona natural (usado por SUNARP).
     *
     * @param string $dni
     * @param string $dniUsuario
     * @param string $passwordPIDE
     * @return array
     */
    public function obtenerDatosRENIEC(string $dni, string $dniUsuario, string $passwordPIDE): array;

    /**
     * Actualiza la contraseña en el servicio RENIEC.
     *
     * @param string $credencialAnterior
     * @param string $credencialNueva
     * @param string $nuDni
     * @return array ['success' => bool, 'message' => string]
     */
    public function actualizarPasswordRENIEC(string $credencialAnterior, string $credencialNueva, string $nuDni): array;
}
