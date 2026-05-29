<?php

namespace App\Controllers;

use App\Services\Contracts\ReniecServiceInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

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
     * Generar PDF con los datos del DNI consultado.
     */
    public function generarPDF(): void
    {
        if (ob_get_level()) {
            ob_clean();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['dni'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Faltan datos del DNI']);
            return;
        }

        $dni            = trim($input['dni']);
        $nombres        = trim($input['nombres'] ?? '');
        $apellido_paterno = trim($input['apellido_paterno'] ?? '');
        $apellido_materno = trim($input['apellido_materno'] ?? '');
        $estado_civil   = trim($input['estado_civil'] ?? '');
        $direccion      = trim($input['direccion'] ?? '');
        $restriccion    = trim($input['restriccion'] ?? '');
        $ubigeo         = trim($input['ubigeo'] ?? '');
        $foto           = $input['foto'] ?? '';

        if (!$this->validateDni($dni)) return;

        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');

            $dompdf = new Dompdf($options);

            ob_start();
            require __DIR__ . '/../../views/dashboard/pages/consultas/dni_pdf.php';
            $html = ob_get_clean();

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="consulta-dni-' . $dni . '.pdf"');
            header('Cache-Control: private, max-age=0, must-revalidate');

            echo $dompdf->output();
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar PDF: ' . $e->getMessage()
            ]);
        }
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
