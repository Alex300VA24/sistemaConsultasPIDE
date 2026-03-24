<?php

namespace App\Services\Contracts;

/**
 * Interface para el servicio de consultas SUNAT.
 */
interface SunatServiceInterface
{
    /**
     * Consulta datos de contribuyente por RUC.
     *
     * @param string $ruc
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function consultarRUC(string $ruc): array;

    /**
     * Busca contribuyentes por razón social.
     *
     * @param string $razonSocial
     * @return array ['success' => bool, 'message' => string, 'data' => array, 'total' => int]
     */
    public function buscarPorRazonSocial(string $razonSocial): array;
}
