<?php

namespace App\Controllers;

use App\Services\Contracts\SunatServiceInterface;

/**
 * Controller para consultas SUNAT.
 * Solo valida request, delega al servicio y envía respuesta (SRP).
 */
class ConsultasSunatController extends ConsultasPideBaseController
{
    /** @var SunatServiceInterface */
    private $sunatService;

    public function __construct(SunatServiceInterface $sunatService)
    {
        $this->sunatService = $sunatService;
    }

    /**
     * Consultar RUC en SUNAT.
     */
    public function consultarRUC(): void
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['ruc'], 'RUC no proporcionado');
        if ($input === null) return;

        $ruc = trim($input['ruc']);

        if (!$this->validateRuc($ruc)) return;

        $resultado = $this->sunatService->consultarRUC($ruc);

        $this->sendJsonResult($resultado);
    }

    /**
     * Buscar por razón social en SUNAT.
     */
    public function buscarRazonSocial(): void
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['razonSocial'], 'Razón social no proporcionada');
        if ($input === null) return;

        $razonSocial = trim($input['razonSocial']);

        if (empty($razonSocial)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Razón social no puede estar vacía'
            ]);
            return;
        }

        $resultado = $this->sunatService->buscarPorRazonSocial($razonSocial);

        $this->sendJsonResult($resultado);
    }
}
