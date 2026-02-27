<?php

namespace App\Controllers;

class ConsultasSunarpController extends ConsultasPideBaseController
{

    private $urlSUNARP;
    private $rucUsuario;
    private $nombreUsuario;
    private $passUsuario;

    public function __construct()
    {
        parent::__construct();
        $this->urlSUNARP = $_ENV['PIDE_URL_SUNARP'];
        $this->rucUsuario = $_ENV['PIDE_RUC_EMPRESA'];
        $this->nombreUsuario = $_ENV['PIDE_SUNARP_USUARIO'];
        $this->passUsuario = $_ENV['PIDE_SUNARP_PASS'];
    }

    // ========================================
    // BUSCAR PERSONA NATURAL (SOLO RENIEC)
    // ========================================
    public function buscarPersonaNatural()
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['dni', 'dniUsuario', 'password'], 'Faltan datos: dni, dniUsuario o password');
        if ($input === null) return;

        $dni = trim($input['dni']);
        $dniUsuario = trim($input['dniUsuario']);
        $passwordPIDE = trim($input['password']);

        if (!$this->validateDni($dni)) return;

        error_log("=== INICIO BÚSQUEDA PERSONA NATURAL (RENIEC) ===");
        error_log("DNI: $dni");

        // Usar ConsultasReniecController directamente (sin duplicar lógica)
        $reniecController = new ConsultasReniecController();
        $datosReniec = $reniecController->obtenerDatosRENIEC($dni, $dniUsuario, $passwordPIDE);

        if (!$datosReniec['success']) {
            error_log("Error RENIEC: " . $datosReniec['message']);
            http_response_code(404);
            echo json_encode($datosReniec, JSON_UNESCAPED_UNICODE);
            return;
        }

        error_log("Datos RENIEC obtenidos correctamente");
        http_response_code(200);
        echo json_encode($datosReniec, JSON_UNESCAPED_UNICODE);
    }

    // ========================================
    // BUSCAR PERSONA JURÍDICA (SOLO SUNAT)
    // ========================================
    public function buscarPersonaJuridica()
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['dniUsuario', 'password'], 'Faltan datos: dniUsuario o password');
        if ($input === null) return;

        $dniUsuario = trim($input['dniUsuario']);
        $passwordPIDE = trim($input['password']);
        $tipoBusqueda = $input['tipoBusqueda'] ?? 'ruc';

        error_log("=== INICIO BÚSQUEDA PERSONA JURÍDICA (SUNAT) ===");
        error_log("Tipo de búsqueda: $tipoBusqueda");

        // Usar ConsultasSunatController directamente (sin Reflection)
        $sunatController = new ConsultasSunatController();

        if ($tipoBusqueda === 'ruc') {
            if (!isset($input['ruc'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'RUC no proporcionado'
                ]);
                return;
            }

            $ruc = trim($input['ruc']);
            if (!$this->validateRuc($ruc)) return;

            error_log("Buscando por RUC: $ruc");

            // Llamar directamente al método protected (ahora accesible sin Reflection)
            $datosSunat = $sunatController->consultarServicioSUNATRest($ruc);

            if (!$datosSunat['success']) {
                error_log("Error SUNAT: " . $datosSunat['message']);
                http_response_code(404);
                echo json_encode($datosSunat, JSON_UNESCAPED_UNICODE);
                return;
            }

            $resultado = [
                'success' => true,
                'message' => 'Consulta exitosa',
                'data' => [$datosSunat['data']],
                'total' => 1
            ];

            http_response_code(200);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        } else if ($tipoBusqueda === 'razonSocial') {
            if (!isset($input['razonSocial']) || empty(trim($input['razonSocial']))) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Razón social no proporcionada'
                ]);
                return;
            }

            $razonSocial = trim($input['razonSocial']);

            if (strlen($razonSocial) > 255) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'La razón social excede el máximo permitido'
                ]);
                return;
            }

            // Filtrar caracteres peligrosos
            $razonSocial = preg_replace('/[^A-Za-z0-9\s\.\-]/', '', $razonSocial);

            error_log("Buscando por razón social: $razonSocial");

            // Llamar directamente al método protected (sin Reflection)
            $resultadosSunat = $sunatController->buscarPorRazonSocialSUNATRest($razonSocial);

            if (!$resultadosSunat['success']) {
                error_log("Error SUNAT: " . $resultadosSunat['message']);
                http_response_code(404);
                echo json_encode($resultadosSunat, JSON_UNESCAPED_UNICODE);
                return;
            }

            error_log("Encontrados " . count($resultadosSunat['data']) . " resultados en SUNAT");

            http_response_code(200);
            echo json_encode($resultadosSunat, JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Tipo de búsqueda inválido. Use "ruc" o "razonSocial"'
            ]);
        }
    }

    // ========================================
    // CONSULTAR TSIRSARP - PERSONA NATURAL
    // ========================================
    public function consultarTSIRSARPNatural()
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['usuario', 'clave'], 'Faltan credenciales: usuario o clave');
        if ($input === null) return;

        $usuario = $this->nombreUsuario;
        $clave = $this->passUsuario;
        $apellidoPaterno = trim($input['apellidoPaterno'] ?? '');
        $apellidoMaterno = trim($input['apellidoMaterno'] ?? '');
        $nombres = trim($input['nombres'] ?? '');

        error_log("=== CONSULTA TSIRSARP PERSONA NATURAL ===");
        error_log("Nombres: $nombres $apellidoPaterno $apellidoMaterno");

        $resultado = $this->consultarTSIRSARP(
            $usuario,
            $clave,
            'N',
            $apellidoPaterno,
            $apellidoMaterno,
            $nombres,
            ''
        );

        http_response_code(200);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    // ========================================
    // CONSULTAR TSIRSARP - PERSONA JURÍDICA
    // ========================================
    public function consultarTSIRSARPJuridica()
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['usuario', 'clave', 'razonSocial'], 'Faltan datos: usuario, clave o razonSocial');
        if ($input === null) return;

        $usuario = $this->nombreUsuario;
        $clave = $this->passUsuario;
        $razonSocial = trim($input['razonSocial']);

        error_log("=== CONSULTA TSIRSARP PERSONA JURÍDICA ===");
        error_log("Razón Social: $razonSocial");

        $resultado = $this->consultarTSIRSARP(
            $usuario,
            $clave,
            'J',
            '',
            '',
            '',
            $razonSocial
        );

        http_response_code(200);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    // ========================================
    // CONSULTAR GOficina - Obtener Catálogo de Oficinas
    // ========================================
    public function consultarGOficina()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $usuario = $_ENV['PIDE_SUNARP_USUARIO'];
        $clave = $_ENV['PIDE_SUNARP_PASS'];

        error_log("=== CONSULTA GOficina ===");

        $resultado = $this->ejecutarGOficina($usuario, $clave);

        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

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
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['zona', 'oficina', 'partida'], 'Faltan datos: zona, oficina o partida');
        if ($input === null) return;

        $usuario = $_ENV['PIDE_SUNARP_USUARIO'];
        $clave = $_ENV['PIDE_SUNARP_PASS'];
        $zona = $input['zona'];
        $oficina = $input['oficina'];
        $partida = $input['partida'];
        $registro = '21000';

        error_log("=== CONSULTA LASIRSARP POR PARTIDA ===");
        error_log("Zona: $zona, Oficina: $oficina, Partida: $partida");

        // 1. CONSULTA LASIRSARP (Asientos)
        $resultLASIRSARP = $this->ejecutarLASIRSARP($usuario, $clave, $zona, $oficina, $partida, $registro);

        if (!$resultLASIRSARP['success']) {
            http_response_code(404);
            echo json_encode($resultLASIRSARP, JSON_UNESCAPED_UNICODE);
            return;
        }

        error_log("Este es el resultLASIRSARP: " . print_r($resultLASIRSARP, true));

        // VALIDAR SI HAY ASIENTOS
        if (empty($resultLASIRSARP['data']) || !is_array($resultLASIRSARP['data'])) {
            error_log("No se encontraron asientos para la partida");
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'No se encontraron asientos registrales para la partida consultada'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $item = $resultLASIRSARP['data'];
        $item['asientos'] = $resultLASIRSARP['data'];
        $transaccion = $resultLASIRSARP['transaccion'] ?? '';

        // 2. CONSULTA VASIRSARP (Imágenes)
        $imagenes = $this->obtenerImagenesVASIRSARP($usuario, $clave, $transaccion, $resultLASIRSARP);

        $item['imagenes'] = $imagenes;

        // 3. PREPARAR RESPUESTA FINAL
        error_log("=== RESPONSE FINAL ===");
        error_log("Tiene asientos: SÍ (" . count($item['asientos']) . ")");
        error_log("Tiene imágenes: " . (count($imagenes) > 0 ? 'SÍ (' . count($imagenes) . ')' : 'NO'));

        $resultado = [
            'success' => true,
            'message' => 'Consulta exitosa con datos adicionales',
            'data' => $item
        ];

        http_response_code(200);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    // ========================================
    // CARGAR DETALLE DE PARTIDA INDIVIDUAL
    // ========================================
    public function cargarDetallePartida()
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(
            ['numero_partida', 'codigo_zona', 'codigo_oficina'],
            'Faltan datos: numero_partida, codigo_zona, codigo_oficina'
        );
        if ($input === null) return;

        $usuario = $_ENV['PIDE_SUNARP_USUARIO'];
        $clave = $_ENV['PIDE_SUNARP_PASS'];
        $numeroPartida = $input['numero_partida'];
        $codigoZona = $input['codigo_zona'];
        $codigoOficina = $input['codigo_oficina'];
        $numeroPlaca = $input['numero_placa'] ?? '';
        $registroCodigo = '21000';

        error_log("=== CARGANDO DETALLE DE PARTIDA ===");
        error_log("Partida: $numeroPartida, Zona: $codigoZona, Oficina: $codigoOficina");

        try {
            $detalle = [
                'asientos' => [],
                'imagenes' => [],
                'datos_vehiculo' => []
            ];

            // 1. CONSULTA LASIRSARP (Asientos y Fichas)
            if (!empty($numeroPartida) && !empty($codigoZona) && !empty($codigoOficina)) {
                error_log("Ejecutando LASIRSARP...");

                $resultLASIRSARP = $this->ejecutarLASIRSARP(
                    $usuario,
                    $clave,
                    $codigoZona,
                    $codigoOficina,
                    $numeroPartida,
                    $registroCodigo
                );

                if ($resultLASIRSARP['success']) {
                    $detalle['asientos'] = $resultLASIRSARP['data'];

                    $totalElementos = count($detalle['asientos']);
                    error_log("LASIRSARP exitoso - Total elementos (asientos + fichas): $totalElementos");

                    // 2. CONSULTA VASIRSARP (Imágenes)
                    $transaccion = $resultLASIRSARP['transaccion'] ?? '';
                    $detalle['imagenes'] = $this->obtenerImagenesDetalleVASIRSARP(
                        $usuario,
                        $clave,
                        $transaccion,
                        $resultLASIRSARP
                    );
                } else {
                    error_log("LASIRSARP falló: " . $resultLASIRSARP['message']);
                }
            }

            // 3. CONSULTA VDRPVExtra (Vehículos)
            if (
                !empty($numeroPlaca) &&
                trim($numeroPlaca) !== '-' &&
                !empty($codigoZona) &&
                !empty($codigoOficina)
            ) {
                error_log("=== EJECUTANDO VDRPVExtra ===");
                error_log("Placa: $numeroPlaca");

                $resultVDRPVExtra = $this->ejecutarVDRPVExtra(
                    $usuario,
                    $clave,
                    $codigoZona,
                    $codigoOficina,
                    $numeroPlaca
                );

                if ($resultVDRPVExtra['success'] && !empty($resultVDRPVExtra['data'])) {
                    $detalle['datos_vehiculo'] = $resultVDRPVExtra['data'];
                    error_log("✓ Datos del vehículo obtenidos");
                } else {
                    error_log("✗ No se obtuvieron datos del vehículo");
                }
            } else {
                error_log("No se ejecutó VDRPVExtra - Placa: " . ($numeroPlaca ?: 'N/A'));
            }

            // 4. LOG FINAL Y RESPUESTA
            error_log("=== RESPUESTA FINAL ===");
            error_log("Total asientos/fichas: " . count($detalle['asientos']));
            error_log("Total imágenes: " . count($detalle['imagenes']));
            error_log("Tiene datos vehículo: " . (!empty($detalle['datos_vehiculo']) ? 'SÍ' : 'NO'));

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Detalle cargado exitosamente',
                'data' => $detalle
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("Exception en cargarDetallePartida: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al cargar detalle: ' . $e->getMessage(),
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ========================================
    // MÉTODOS INTERNOS DE SERVICIOS SUNARP
    // ========================================

    private function ejecutarLASIRSARP($usuario, $clave, $zona, $oficina, $partida, $registro)
    {
        try {
            $url = $this->urlSUNARP . "/LASIRSARP?out=json";

            $data = [
                "PIDE" => [
                    "usuario" => $usuario,
                    "clave" => $clave,
                    "zona" => $zona,
                    "oficina" => $oficina,
                    "partida" => $partida,
                    "registro" => $registro
                ]
            ];

            error_log("LASIRSARP Request: " . json_encode($data, JSON_UNESCAPED_UNICODE));

            $curlResult = $this->executeCurl($url, $data, 'POST', 'SUNARP (LASIRSARP)', 60);

            if (!$curlResult['success']) {
                return ['success' => false, 'message' => $curlResult['error'], 'data' => []];
            }

            $jsonResponse = json_decode($curlResult['response'], true);
            error_log("Este es el jsonResponse: " . print_r($jsonResponse, true));

            if ($curlResult['httpCode'] == 200) {
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Error JSON LASIRSARP: " . json_last_error_msg());
                    return ['success' => false, 'message' => 'Error al decodificar respuesta', 'data' => []];
                }

                return $this->procesarRespuestaLASIRSARP($jsonResponse);
            } else {
                return ['success' => false, 'message' => "Error HTTP {$curlResult['httpCode']}", 'data' => []];
            }
        } catch (\Exception $e) {
            error_log("Exception en ejecutarLASIRSARP: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => []];
        }
    }

    private function procesarRespuestaLASIRSARP($jsonResponse)
    {
        try {
            if (
                !isset($jsonResponse['listarAsientosSIRSARPResponse']) ||
                !isset($jsonResponse['listarAsientosSIRSARPResponse']['asientos'])
            ) {
                return ['success' => false, 'message' => 'No se encontraron asientos en la respuesta', 'data' => []];
            }

            $asientos = $jsonResponse['listarAsientosSIRSARPResponse']['asientos'];

            $transaccion = $asientos['transaccion'] ?? '';
            $nroTotalPag = $asientos['nroTotalPag'] ?? '0';

            $todosLosElementos = [];

            // 1. PROCESAR ASIENTOS (listAsientos)
            if (isset($asientos['listAsientos']) && !empty($asientos['listAsientos'])) {
                $listAsientos = $asientos['listAsientos'];
                if (!isset($listAsientos[0])) {
                    $listAsientos = [$listAsientos];
                }

                foreach ($listAsientos as $asiento) {
                    $todosLosElementos[] = [
                        'idImgAsiento' => $asiento['idImgAsiento'] ?? '',
                        'numPag' => $asiento['numPag'] ?? '',
                        'tipo' => $asiento['tipo'] ?? 'ASIENTO',
                        'listPag' => $asiento['listPag'] ?? [],
                        'categoria' => 'asiento'
                    ];
                }

                error_log("Asientos procesados: " . count($listAsientos));
            }

            // 2. PROCESAR FICHAS (listFichas)
            if (isset($asientos['listFichas']) && !empty($asientos['listFichas'])) {
                $listFichas = $asientos['listFichas'];
                if (!isset($listFichas[0])) {
                    $listFichas = [$listFichas];
                }

                foreach ($listFichas as $ficha) {
                    $todosLosElementos[] = [
                        'idImgAsiento' => $ficha['idImgFicha'] ?? '',
                        'numPag' => $ficha['numPag'] ?? '',
                        'tipo' => $ficha['tipo'] ?? 'FICHA',
                        'listPag' => $ficha['listPag'] ?? [],
                        'categoria' => 'ficha'
                    ];
                }

                error_log("Fichas procesadas: " . count($listFichas));
            }

            // 3. VERIFICAR QUE HAY DATOS
            if (empty($todosLosElementos)) {
                error_log("No se encontraron asientos ni fichas");
                return ['success' => false, 'message' => 'No se encontraron asientos ni fichas registrales', 'data' => []];
            }

            error_log("Total elementos procesados: " . count($todosLosElementos));

            return [
                'success' => true,
                'message' => 'Consulta exitosa',
                'data' => $todosLosElementos,
                'transaccion' => $transaccion,
                'nroTotalPag' => $nroTotalPag
            ];
        } catch (\Exception $e) {
            error_log("Exception en procesarRespuestaLASIRSARP: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al procesar respuesta: ' . $e->getMessage(), 'data' => []];
        }
    }

    private function ejecutarVASIRSARP($usuario, $clave, $transaccion, $idImg, $tipo, $nroTotalPag, $nroPagRef, $pagina)
    {
        try {
            $url = $this->urlSUNARP . "/VASIRSARP?out=json";

            $data = [
                "PIDE" => [
                    "usuario" => (string)$usuario,
                    "clave" => (string)$clave,
                    "transaccion" => (string)$transaccion,
                    "idImg" => (string)$idImg,
                    "tipo" => (string)$tipo,
                    "nroTotalPag" => (string)$nroTotalPag,
                    "nroPagRef" => (string)$nroPagRef,
                    "pagina" => (string)$pagina
                ]
            ];

            $curlResult = $this->executeCurl($url, $data, 'POST', 'SUNARP (VASIRSARP)');

            if (!$curlResult['success']) {
                return ['success' => false, 'message' => $curlResult['error']];
            }

            if ($curlResult['httpCode'] == 200) {
                $jsonResponse = json_decode($curlResult['response'], true);
                $img = $jsonResponse['verAsientoSIRSARPResponse']['img'] ?? null;

                return [
                    'success' => true,
                    'message' => 'Imagen obtenida',
                    'img' => $img
                ];
            }

            return ['success' => false, 'message' => "HTTP {$curlResult['httpCode']}"];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function limpiarRespuesta($texto)
    {
        $mapa = [
            "ï¿½" => "ó",
            "En circulaciï¿½n" => "En circulación"
        ];
        return strtr($texto, $mapa);
    }

    private function ejecutarVDRPVExtra($usuario, $clave, $zona, $oficina, $placa)
    {
        try {
            $url = $this->urlSUNARP . "/VDRPVExtra?out=json";

            $data = [
                "PIDE" => [
                    "usuario" => (string)$usuario,
                    "clave" => (string)$clave,
                    "zona" => (string)$zona,
                    "oficina" => (string)$oficina,
                    "placa" => (string)$placa
                ]
            ];

            $curlResult = $this->executeCurl($url, $data, 'POST', 'SUNARP (VDRPVExtra)');

            if (!$curlResult['success']) {
                return ['success' => false, 'message' => $curlResult['error'], 'data' => []];
            }

            if ($curlResult['httpCode'] == 200) {
                $jsonResponse = json_decode($curlResult['response'], true);
                $vehiculo = $jsonResponse['verDetalleRPVExtraResponse']['vehiculo'] ?? [];
                $limpiado = $this->limpiarRespuesta($vehiculo['estado']);
                $vehiculo['estado'] = $limpiado;

                error_log("Resultado de vehiculo" . print_r($vehiculo, true));

                return [
                    'success' => true,
                    'message' => 'Consulta vehicular exitosa',
                    'data' => $vehiculo
                ];
            }

            return ['success' => false, 'message' => "HTTP {$curlResult['httpCode']}", 'data' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    // ========================================
    // MÉTODO INTERNO CONSULTAR TSIRSARP
    // ========================================
    private function consultarTSIRSARP($usuario, $clave, $tipoParticipante, $apellidoPaterno, $apellidoMaterno, $nombres, $razonSocial)
    {
        try {
            $url = $this->urlSUNARP . "/TSIRSARP?out=json";

            $data = [
                "PIDE" => [
                    "usuario" => $usuario,
                    "clave" => $clave,
                    "tipoParticipante" => $tipoParticipante,
                    "apellidoPaterno" => $apellidoPaterno,
                    "apellidoMaterno" => $apellidoMaterno,
                    "nombres" => $nombres,
                    "razonSocial" => $razonSocial
                ]
            ];

            error_log("TSIRSARP Request: " . json_encode($data, JSON_UNESCAPED_UNICODE));

            $curlResult = $this->executeCurl($url, $data, 'POST', 'SUNARP (TSIRSARP)');

            if (!$curlResult['success']) {
                return ['success' => false, 'message' => $curlResult['error'], 'data' => []];
            }

            $jsonResponse = json_decode($curlResult['response'], true);

            error_log("TSIRSARP Response Code: " . $curlResult['httpCode']);
            error_log("TSIRSARP Response: " . $curlResult['response']);

            if ($curlResult['httpCode'] == 200) {
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Error JSON TSIRSARP: " . json_last_error_msg());
                    return ['success' => false, 'message' => 'Error al decodificar respuesta de SUNARP', 'data' => []];
                }

                return $this->procesarRespuestaTSIRSARP($jsonResponse, $tipoParticipante);
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP {$curlResult['httpCode']} en el servicio SUNARP.",
                    'data' => []
                ];
            }
        } catch (\Exception $e) {
            error_log("Exception en consultarTSIRSARP: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al consultar SUNARP: ' . $e->getMessage(), 'data' => []];
        }
    }

    private function procesarRespuestaTSIRSARP($jsonResponse, $tipoParticipante)
    {
        try {
            error_log("Procesando respuesta TSIRSARP. Tipo: $tipoParticipante");

            if (
                !isset($jsonResponse['buscarTitularidadSIRSARPResponse']) ||
                !isset($jsonResponse['buscarTitularidadSIRSARPResponse']['respuestaTitularidad'])
            ) {
                return ['success' => false, 'message' => 'No se encontraron registros en SUNARP', 'data' => []];
            }

            $return = $jsonResponse['buscarTitularidadSIRSARPResponse']['respuestaTitularidad']['respuestaTitularidad'];

            $registros = isset($return[0]) ? $return : [$return];

            $resultados = [];

            $usuario = $this->nombreUsuario;
            $clave = $this->passUsuario;

            // OBTENER CATÁLOGO DE OFICINAS
            $catalogoOficinas = [];
            $resultGOficina = $this->ejecutarGOficina($usuario, $clave);

            if ($resultGOficina['success']) {
                $catalogoOficinas = $resultGOficina['data'];
            }

            error_log("Total de registros a procesar: " . count($registros));

            foreach ($registros as $index => $registro) {
                error_log("=== Procesando registro " . ($index + 1) . "/" . count($registros) . " ===");

                $item = [
                    'libro' => $registro['libro'] ?? '',
                    'apPaterno' => $registro['apPaterno'] ?? '',
                    'apMaterno' => $registro['apMaterno'] ?? '',
                    'nombre' => $registro['nombre'] ?? '',
                    'razon_social' => $registro['razonSocial'] ?? '',
                    'tipo_documento' => $registro['tipo_documento'] ?? '',
                    'numero_documento' => $registro['numero_documento'] ?? '',
                    'numero_partida' => $registro['numeroPartida'] ?? '',
                    'numero_placa' => $registro['numeroPlaca'] ?? '',
                    'oficina' => $registro['oficina'] ?? '',
                    'zona' => $registro['zona'] ?? '',
                    'estado' => $registro['estado'] ?? '',
                    'direccion' => $registro['direccion'] ?? '',
                    'registro' => $registro['registro'] ?? '',
                    'indice' => $index,
                    'asientos' => [],
                    'imagenes' => [],
                    'datos_vehiculo' => [],
                    'detalle_cargado' => false
                ];

                $codigoZona = $item['zona'];
                $codigoOficina = $item['oficina'];

                if (!empty($catalogoOficinas) && !empty($item['oficina'])) {
                    $oficinaKey = strtoupper(trim($item['oficina']));

                    if (isset($catalogoOficinas[$oficinaKey])) {
                        $codigoZona = $catalogoOficinas[$oficinaKey]['codZona'];
                        $codigoOficina = $catalogoOficinas[$oficinaKey]['codOficina'];
                        error_log("Oficina mapeada: $oficinaKey -> Zona: $codigoZona, Oficina: $codigoOficina");
                    }
                }

                $item['codigo_zona'] = $codigoZona;
                $item['codigo_oficina'] = $codigoOficina;

                $resultados[] = $item;
            }

            error_log("=== RESPONSE INICIAL (sin detalles) ===");
            error_log("Total partidas: " . count($resultados));

            if (empty($resultados)) {
                return ['success' => false, 'message' => 'No se encontraron registros válidos en SUNARP', 'data' => []];
            }

            return [
                'success' => true,
                'message' => 'Consulta exitosa. Use /cargar-detalle-partida para obtener detalles completos.',
                'data' => $resultados,
                'total' => count($resultados),
                'requiere_carga_bajo_demanda' => true
            ];
        } catch (\Exception $e) {
            error_log("Exception en procesarRespuestaTSIRSARP: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Error al procesar respuesta de SUNARP: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    // ========================================
    // HELPERS DE IMÁGENES VASIRSARP
    // ========================================

    /**
     * Obtiene imágenes VASIRSARP para la consulta LASIRSARP simple.
     */
    private function obtenerImagenesVASIRSARP($usuario, $clave, $transaccion, $resultLASIRSARP)
    {
        $imagenes = [];

        if (empty($transaccion)) {
            error_log("No se ejecutó VASIRSARP - Sin transacción");
            return $imagenes;
        }

        error_log("=== EJECUTANDO VASIRSARP ===");

        $asientos = array_reverse($resultLASIRSARP['data']);
        $nroTotalPag = $resultLASIRSARP['nroTotalPag'] ?? '1';

        foreach ($asientos as $indexAsiento => $asiento) {
            $idImgAsiento = $asiento['idImgAsiento'] ?? '';
            $tipo = $asiento['tipo'] ?? 'A';

            error_log("Procesando asiento " . ($indexAsiento + 1) . " - idImg: $idImgAsiento");

            $paginas = $this->normalizarPaginas($asiento['listPag'] ?? []);

            foreach ($paginas as $indexPagina => $pagina) {
                $nroPagRef = $pagina['nroPagRef'] ?? '';
                $paginaNum = $pagina['pagina'] ?? '';

                error_log("  - Obteniendo imagen para página " . ($indexPagina + 1) . " (nroPagRef: $nroPagRef, pagina: $paginaNum)");

                $resultVASIRSARP = $this->ejecutarVASIRSARP(
                    $usuario,
                    $clave,
                    $transaccion,
                    $idImgAsiento,
                    $tipo,
                    $nroTotalPag,
                    $nroPagRef,
                    $paginaNum
                );

                if ($resultVASIRSARP['success'] && !empty($resultVASIRSARP['img'])) {
                    $imagenes[] = [
                        'asiento' => $indexAsiento + 1,
                        'pagina' => $indexPagina + 1,
                        'nroPagRef' => $nroPagRef,
                        'imagen_base64' => $resultVASIRSARP['img'],
                    ];
                    error_log("    ✓ Imagen obtenida correctamente");
                } else {
                    error_log("    ✗ No se obtuvo imagen - " . ($resultVASIRSARP['message'] ?? 'Error desconocido'));
                }
            }
        }

        error_log("Total imágenes obtenidas: " . count($imagenes));
        return $imagenes;
    }

    /**
     * Obtiene imágenes VASIRSARP para la carga de detalle de partida.
     */
    private function obtenerImagenesDetalleVASIRSARP($usuario, $clave, $transaccion, $resultLASIRSARP)
    {
        $imagenes = [];

        if (empty($resultLASIRSARP['data']) || empty($transaccion)) {
            error_log("No se ejecutó VASIRSARP - Sin transacción o sin elementos");
            return $imagenes;
        }

        error_log("=== EJECUTANDO VASIRSARP ===");

        $elementosOriginales = $resultLASIRSARP['data'];
        if (!isset($elementosOriginales[0])) {
            $elementosOriginales = [$elementosOriginales];
            error_log("Normalizado: Un solo elemento detectado");
        }

        $elementos = array_reverse($elementosOriginales);
        $nroTotalPag = $resultLASIRSARP['nroTotalPag'] ?? '1';
        $contadorImagenGlobal = 0;

        error_log("Total elementos a procesar: " . count($elementos));

        foreach ($elementos as $indexElemento => $elemento) {
            if (!is_array($elemento)) {
                error_log("⚠️ Elemento " . ($indexElemento + 1) . " no es un array - SALTANDO");
                continue;
            }

            $idImg = (string)($elemento['idImgAsiento'] ?? '');
            $tipo = $elemento['tipo'] ?? 'ASIENTO';
            $categoria = $elemento['categoria'] ?? 'asiento';

            error_log("Procesando elemento " . ($indexElemento + 1) . "/" . count($elementos) .
                " - Tipo: $tipo, Categoría: $categoria, idImg: $idImg");

            $paginas = $this->normalizarPaginas($elemento['listPag'] ?? []);

            foreach ($paginas as $indexPagina => $pagina) {
                if (!is_array($pagina)) {
                    error_log("    - Página " . ($indexPagina + 1) . " no es un array - SALTANDO");
                    continue;
                }

                $nroPagRef = (string)($pagina['nroPagRef'] ?? '');
                $paginaNum = (string)($pagina['pagina'] ?? '');

                if (empty($nroPagRef) || empty($paginaNum)) {
                    error_log("    - Página " . ($indexPagina + 1) . " sin nroPagRef o pagina - SALTANDO");
                    continue;
                }

                error_log("  - Obteniendo imagen página " . ($indexPagina + 1) . "/" . count($paginas) .
                    " (nroPagRef: $nroPagRef, pagina: $paginaNum)");

                $resultVASIRSARP = $this->ejecutarVASIRSARP(
                    $usuario,
                    $clave,
                    $transaccion,
                    $idImg,
                    $tipo,
                    $nroTotalPag,
                    $nroPagRef,
                    $paginaNum
                );

                if ($resultVASIRSARP['success'] && !empty($resultVASIRSARP['img'])) {
                    $contadorImagenGlobal++;
                    $imagenes[] = [
                        'numero_secuencial' => $contadorImagenGlobal,
                        'elemento' => $indexElemento + 1,
                        'tipo' => $tipo,
                        'categoria' => $categoria,
                        'pagina_en_elemento' => $indexPagina + 1,
                        'total_paginas_elemento' => count($paginas),
                        'nroPagRef' => $nroPagRef,
                        'imagen_base64' => $resultVASIRSARP['img']
                    ];
                    error_log("    ✓ Imagen obtenida (#$contadorImagenGlobal)");
                } else {
                    error_log("    ✗ No se obtuvo imagen - " . ($resultVASIRSARP['message'] ?? 'Error desconocido'));
                }
            }
        }

        error_log("Total imágenes obtenidas: " . count($imagenes));
        return $imagenes;
    }

    /**
     * Normaliza listPag a un array de páginas, manejando el caso de una sola página.
     */
    private function normalizarPaginas($listPag)
    {
        if (!is_array($listPag)) {
            return [];
        }

        if (isset($listPag['nroPagRef']) && isset($listPag['pagina'])) {
            return [$listPag];
        }

        if (count($listPag) > 0 && isset($listPag[0])) {
            return $listPag;
        }

        return [];
    }
}
