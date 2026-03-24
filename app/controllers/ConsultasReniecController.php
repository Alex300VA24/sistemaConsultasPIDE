<?php

namespace App\Controllers;

use App\Services\Contracts\ReniecServiceInterface;

/**
 * Controller para consultas RENIEC.
 * Solo valida request, delega al servicio y envía respuesta (SRP).
 */
class ConsultasReniecController extends ConsultasPideBaseController
{
    /** @var ReniecServiceInterface */
    private $reniecService;

    public function __construct(ReniecServiceInterface $reniecService)
    {
        $this->reniecService = $reniecService;
    }

    /**
     * Consultar DNI en RENIEC.
     */
    public function consultarDNI(): void
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['dniConsulta', 'dniUsuario', 'password'], 'Faltan datos: dni, dniUsuario o password');
        if ($input === null) return;

        $dni = trim($input['dniConsulta']);
        $dniUsuario = trim($input['dniUsuario']);
        $passwordPIDE = trim($input['password']);

        if (!$this->validateDni($dni)) return;

        $resultado = $this->reniecService->consultarDNI($dni, $dniUsuario, $passwordPIDE);

        $this->sendJsonResult($resultado);
    }

    /**
     * Actualizar contraseña RENIEC.
     */
    public function actualizarPasswordRENIEC(): void
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['credencialAnterior', 'credencialNueva', 'nuDni'], 'Faltan datos: credencialAnterior, credencialNueva o nuDni');
        if ($input === null) return;

        $credencialAnterior = $input['credencialAnterior'];
        $credencialNueva = trim($input['credencialNueva']);
        $nuDni = trim($input['nuDni']);

        if (!$this->validateDni($nuDni)) return;

        try {
            $resultado = $this->reniecService->actualizarPasswordRENIEC($credencialAnterior, $credencialNueva, $nuDni);

            http_response_code($resultado['success'] ? 200 : 400);
            echo json_encode($resultado);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al comunicarse con RENIEC: ' . $e->getMessage()
            ]);
        }
    }
}
