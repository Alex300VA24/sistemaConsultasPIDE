<?php

namespace App\Services\Contracts;

/**
 * Interface para el servicio de consultas SUNARP.
 */
interface SunarpServiceInterface
{
    /**
     * Busca persona natural usando RENIEC.
     */
    public function buscarPersonaNatural(string $dni, string $dniUsuario, string $passwordPIDE): array;

    /**
     * Busca persona jurídica usando SUNAT.
     */
    public function buscarPersonaJuridica(array $input): array;

    /**
     * Consulta titularidad TSIRSARP para persona natural.
     */
    public function consultarTSIRSARPNatural(string $apellidoPaterno, string $apellidoMaterno, string $nombres): array;

    /**
     * Consulta titularidad TSIRSARP para persona jurídica.
     */
    public function consultarTSIRSARPJuridica(string $razonSocial): array;

    /**
     * Obtiene el catálogo de oficinas SUNARP.
     */
    public function consultarGOficina(): array;

    /**
     * Consulta asientos registrales LASIRSARP.
     */
    public function consultarLASIRSARP(string $zona, string $oficina, string $partida): array;

    /**
     * Carga detalle completo de una partida (asientos, imágenes, vehículos).
     */
    public function cargarDetallePartida(string $numeroPartida, string $codigoZona, string $codigoOficina, string $numeroPlaca = ''): array;
}
