<?php

namespace App\Controllers;

use App\Services\Contracts\SunarpServiceInterface;

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
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['usuario', 'clave'], 'Faltan credenciales: usuario o clave');
        if ($input === null) return;

        $apellidoPaterno = trim($input['apellidoPaterno'] ?? '');
        $apellidoMaterno = trim($input['apellidoMaterno'] ?? '');
        $nombres = trim($input['nombres'] ?? '');

        $resultado = $this->sunarpService->consultarTSIRSARPNatural($apellidoPaterno, $apellidoMaterno, $nombres);

        http_response_code(200);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Consultar TSIRSARP - Persona Jurídica.
     */
    public function consultarTSIRSARPJuridica(): void
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['usuario', 'clave', 'razonSocial'], 'Faltan datos: usuario, clave o razonSocial');
        if ($input === null) return;

        $razonSocial = trim($input['razonSocial']);

        $resultado = $this->sunarpService->consultarTSIRSARPJuridica($razonSocial);

        http_response_code(200);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Consultar GOficina - Catálogo de oficinas.
     */
    public function consultarGOficina(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $resultado = $this->sunarpService->consultarGOficina();

        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

<<<<<<< HEAD
    /**
     * Consultar LASIRSARP - Asientos registrales.
     */
    public function consultarLASIRSARP(): void
=======
    private function ejecutarGOficina($usuario, $clave)
    {
        try {
            $url = $this->urlSUNARP . "/GOficina?out=json";

            $data = [
                "PIDE" => [
                    "usuario" => (string)$usuario,
                    "clave" => (string)$clave
                ]
            ];

            error_log("GOficina Request: " . json_encode($data, JSON_UNESCAPED_UNICODE));


            $curlResult = $this->executeCurl($url, $data, 'POST', 'SUNARP (GOficina)');

            if (!$curlResult['success']) {
                return ['success' => false, 'message' => $curlResult['error'], 'data' => []];
            }

            error_log("GOficina Response Code: " . $curlResult['httpCode']);

            if ($curlResult['httpCode'] == 200) {
                $jsonResult = $this->decodeJsonResponse($curlResult['response'], 'SUNARP (GOficina)');

                if (!$jsonResult['success']) {
                    return ['success' => false, 'message' => $jsonResult['message'], 'data' => []];
                }

                $jsonResponse = $jsonResult['data'];
                $oficinas = $jsonResponse['oficina']['oficina'] ?? [];

                if (empty($oficinas)) {
                    return ['success' => false, 'message' => 'No se encontraron oficinas', 'data' => []];
                }

                $catalogo = [];
                foreach ($oficinas as $oficina) {
                    $key = strtoupper(trim($oficina['descripcion']));
                    $catalogo[$key] = [
                        'codZona' => $oficina['codZona'],
                        'codOficina' => $oficina['codOficina'],
                        'descripcion' => $oficina['descripcion']
                    ];
                }

                error_log("GOficina: " . count($catalogo) . " oficinas cargadas");

                return [
                    'success' => true,
                    'message' => 'Catálogo de oficinas obtenido',
                    'data' => $catalogo,
                    'total' => count($catalogo)
                ];
            }

            return ['success' => false, 'message' => "HTTP {$curlResult['httpCode']}", 'data' => []];
        } catch (\Exception $e) {
            error_log("Exception en ejecutarGOficina: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    // ========================================
    // CONSULTAR LASIRSARP
    // ========================================
    public function consultarLASIRSARP()
>>>>>>> 45ffdb2 (cambios)
    {
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
