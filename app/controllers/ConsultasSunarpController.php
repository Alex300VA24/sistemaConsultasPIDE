<?php

namespace App\Controllers;

use App\Services\Contracts\SunarpServiceInterface;
use App\Middleware\SecurityMiddleware;

/**
 * Controller para consultas SUNARP.
 * Solo valida request, delega al servicio y envía respuesta (SRP).
 * Ya no instancia ConsultasReniecController ni ConsultasSunatController (DIP).
 */
class ConsultasSunarpController extends ConsultasPideBaseController
{
    /** @var SunarpServiceInterface */
    private $sunarpService;

    public function __construct(SunarpServiceInterface $sunarpService)
    {
        $this->sunarpService = $sunarpService;
    }

    /**
     * Buscar persona natural (RENIEC).
     */
    public function buscarPersonaNatural(): void
    {
        SecurityMiddleware::requirePermission('sunarp.persona_natural');

        if (!$this->enforceRateLimit('consulta', $_SESSION['usuarioID'] ?? '')) return;

        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['dni', 'dniUsuario', 'password'], 'Faltan datos: dni, dniUsuario o password');
        if ($input === null) return;

        $dni = trim($input['dni']);
        $dniUsuario = trim($input['dniUsuario']);
        $passwordPIDE = trim($input['password']);

        if (!$this->validateDni($dni)) return;

        $resultado = $this->sunarpService->buscarPersonaNatural($dni, $dniUsuario, $passwordPIDE);

        $this->sendJsonResult($resultado);
    }

    /**
     * Buscar persona jurídica (SUNAT).
     */
    public function buscarPersonaJuridica(): void
    {
        SecurityMiddleware::requirePermission('sunarp.persona_juridica');

        if (!$this->enforceRateLimit('consulta', $_SESSION['usuarioID'] ?? '')) return;

        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['dniUsuario', 'password'], 'Faltan datos: dniUsuario o password');
        if ($input === null) return;

        $resultado = $this->sunarpService->buscarPersonaJuridica($input);

        $this->sendJsonResult($resultado);
    }

    /**
     * Consultar TSIRSARP - Persona Natural.
     */
    public function consultarTSIRSARPNatural(): void
    {
        SecurityMiddleware::requirePermission('sunarp.partidas_natural');

        if (!$this->enforceRateLimit('consulta', $_SESSION['usuarioID'] ?? '')) return;

        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['usuario', 'clave'], 'Faltan credenciales: usuario o clave');
        if ($input === null) return;

        $apellidoPaterno = trim($input['apellidoPaterno'] ?? '');
        $apellidoMaterno = trim($input['apellidoMaterno'] ?? '');
        $nombres = trim($input['nombres'] ?? '');

        $resultado = $this->sunarpService->consultarTSIRSARPNatural($apellidoPaterno, $apellidoMaterno, $nombres);

        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Consultar TSIRSARP - Persona Jurídica.
     */
    public function consultarTSIRSARPJuridica(): void
    {
        SecurityMiddleware::requirePermission('sunarp.partidas_juridica');

        if (!$this->enforceRateLimit('consulta', $_SESSION['usuarioID'] ?? '')) return;

        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['usuario', 'clave', 'razonSocial'], 'Faltan datos: usuario, clave o razonSocial');
        if ($input === null) return;

        $razonSocial = trim($input['razonSocial']);

        $resultado = $this->sunarpService->consultarTSIRSARPJuridica($razonSocial);

        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Consultar GOficina - Catálogo de oficinas.
     */
    public function consultarGOficina(): void
    {
        SecurityMiddleware::requirePermission('sunarp.goficinas');

        if (!$this->enforceRateLimit('consulta', $_SESSION['usuarioID'] ?? '')) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $resultado = $this->sunarpService->consultarGOficina();

        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Consultar LASIRSARP - Asientos registrales.
     */
    public function consultarLASIRSARP(): void
    {
        SecurityMiddleware::requirePermission('sunarp.partidas_lasirsarp');

        if (!$this->enforceRateLimit('consulta', $_SESSION['usuarioID'] ?? '')) return;

        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['zona', 'oficina', 'partida'], 'Faltan datos: zona, oficina o partida');
        if ($input === null) return;

        $zona = $input['zona'];
        $oficina = $input['oficina'];
        $partida = $input['partida'];

        $resultado = $this->sunarpService->consultarLASIRSARP($zona, $oficina, $partida);

        if (!$resultado['success']) {
            http_response_code(404);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(200);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Cargar detalle de partida individual.
     */
    public function cargarDetallePartida(): void
    {
        SecurityMiddleware::requirePermission('sunarp.detalle_partida');

        if (!$this->enforceRateLimit('consulta', $_SESSION['usuarioID'] ?? '')) return;

        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(
            ['numero_partida', 'codigo_zona', 'codigo_oficina'],
            'Faltan datos: numero_partida, codigo_zona, codigo_oficina'
        );
        if ($input === null) return;

        $numeroPartida = $input['numero_partida'];
        $codigoZona = $input['codigo_zona'];
        $codigoOficina = $input['codigo_oficina'];
        $numeroPlaca = $input['numero_placa'] ?? '';

        $resultado = $this->sunarpService->cargarDetallePartida(
            $numeroPartida, $codigoZona, $codigoOficina, $numeroPlaca
        );

        http_response_code($resultado['success'] ? 200 : 500);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}
