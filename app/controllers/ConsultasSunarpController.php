<?php

namespace App\Controllers;

class ConsultasSunarpController
{

    private $urlSUNARP;
    private $urlGOFICINA;
    private $rucUsuario;
    private $nombreUsuario;
    private $passUsuario;

    public function __construct()
    {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $content = file_get_contents($envFile);

            // Parsear manualmente manejando comillas
            $lines = preg_split('/\r\n|\n|\r/', $content);

            foreach ($lines as $line) {
                $line = trim($line);

                // Ignorar líneas vacías o comentarios
                if (empty($line) || $line[0] === '#') {
                    continue;
                }

                // Usar regex para dividir correctamente
                if (preg_match('/^([^=]+)=(.*)$/', $line, $matches)) {
                    $name = trim($matches[1]);
                    $value = trim($matches[2]);

                    // Manejar comillas
                    if (preg_match('/^["\'](.*)["\']$/', $value, $quoteMatches)) {
                        $value = $quoteMatches[1];
                    }

                    $_ENV[$name] = $value;
                }
            }
        }
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
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['dni']) || !isset($input['dniUsuario']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos: dni, dniUsuario o password'
            ]);
            return;
        }

        $dni = trim($input['dni']);
        $dniUsuario = trim($input['dniUsuario']);
        $passwordPIDE = trim($input['password']);

        if (!preg_match('/^\d{8}$/', $dni)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'DNI inválido. Debe tener 8 dígitos'
            ]);
            return;
        }
        error_log("=== INICIO BÚSQUEDA PERSONA NATURAL (RENIEC) ===");
        error_log("DNI: $dni");


        $datosReniec = $this->obtenerDatosRENIEC($dni, $dniUsuario, $passwordPIDE);

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
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['dniUsuario']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos: dni, Usuario o password'
            ]);
            return;
        }

        $dniUsuario = trim($input['dniUsuario']);
        $passwordPIDE = trim($input['password']);
        $tipoBusqueda = $input['tipoBusqueda'] ?? 'ruc';

        error_log("=== INICIO BÚSQUEDA PERSONA JURÍDICA (SUNAT) ===");
        error_log("Tipo de búsqueda: $tipoBusqueda");

        $razonSocial = '';

        // 1. Obtener razón social según tipo de búsqueda
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
            if (!preg_match('/^\d{11}$/', $ruc)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'RUC inválido. Debe tener 11 dígitos'
                ]);
                return;
            }
            error_log("Buscando por RUC: $ruc");

            $sunatController = new ConsultasSunatController();
            $datosSunat = $this->consultarRUCInterno($sunatController, $ruc);

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

            // Obtener y limpiar
            $razonSocial = isset($input['razonSocial']) ? trim($input['razonSocial']) : '';

            // Validar que no esté vacío
            if ($razonSocial === '') {
                throw new \Exception("La razón social no puede estar vacía");
            }

            // Opcional: limitar longitud
            if (strlen($razonSocial) > 255) {
                throw new \Exception("La razón social excede el máximo permitido");
            }

            // Filtrar caracteres peligrosos (ejemplo: solo letras, números, espacios y algunos símbolos)
            $razonSocial = preg_replace('/[^A-Za-z0-9\s\.\-]/', '', $razonSocial);

            error_log("Buscando por razón social: $razonSocial");

            $sunatController = new ConsultasSunatController();
            $resultadosSunat = $this->buscarRazonSocialInterno($sunatController, $razonSocial);

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
            return;
        }
    }

    // ========================================
    // CONSULTAR TSIRSARP - PERSONA NATURAL
    // ========================================
    public function consultarTSIRSARPNatural()
    {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['usuario']) || !isset($input['clave'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan credenciales: usuario o clave'
            ]);
            return;
        }

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
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['usuario']) || !isset($input['clave']) || !isset($input['razonSocial'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos: usuario, clave o razonSocial'
            ]);
            return;
        }

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

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            error_log("GOficina Request: " . $jsonData);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json; charset=UTF-8",
                    "Content-Length: " . strlen($jsonData)
                ],
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 45
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                error_log("CURL Error GOficina: $error");
                return ['success' => false, 'message' => "Error: $error", 'data' => []];
            }

            curl_close($ch);

            error_log("GOficina Response Code: $httpCode");

            if ($httpCode == 200) {
                $jsonResponse = json_decode($response, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return ['success' => false, 'message' => 'Error al decodificar JSON', 'data' => []];
                }

                // Extraer oficinas del response
                $oficinas = $jsonResponse['oficina']['oficina'] ?? [];

                if (empty($oficinas)) {
                    return ['success' => false, 'message' => 'No se encontraron oficinas', 'data' => []];
                }

                // Crear catálogo indexado por nombre de oficina
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

            return ['success' => false, 'message' => "HTTP $httpCode", 'data' => []];
        } catch (\Exception $e) {
            error_log("Exception en ejecutarGOficina: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    public function consultarLASIRSARP()
    {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['zona']) || !isset($input['oficina']) || !isset($input['partida'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos: zona, oficina o partida'
            ]);
            return;
        }

        $usuario = $_ENV['PIDE_SUNARP_USUARIO'];
        $clave = $_ENV['PIDE_SUNARP_PASS'];
        $zona = $input['zona'];
        $oficina = $input['oficina'];
        $partida = $input['partida'];
        $registro = '21000'; // Registro de propiedad vehicular

        error_log("=== CONSULTA LASIRSARP POR PARTIDA ===");
        error_log("Zona: $zona, Oficina: $oficina, Partida: $partida");

        // ========================================
        // 1. CONSULTA LASIRSARP (Asientos)
        // ========================================
        $resultLASIRSARP = $this->ejecutarLASIRSARP(
            $usuario,
            $clave,
            $zona,
            $oficina,
            $partida,
            $registro
        );

        if (!$resultLASIRSARP['success']) {
            http_response_code(404);
            echo json_encode($resultLASIRSARP, JSON_UNESCAPED_UNICODE);
            return;
        }

        error_log("Este es el resultLASIRSARP: " . print_r($resultLASIRSARP, true));

        // ========================================
        // VALIDAR SI HAY ASIENTOS
        // ========================================
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

        // ========================================
        // 2. CONSULTA VASIRSARP (Imágenes)
        // ========================================
        $imagenes = [];

        if (!empty($transaccion)) {
            error_log("=== EJECUTANDO VASIRSARP ===");

            // Invertir orden de asientos para mostrar los más recientes primero
            $asientos = array_reverse($resultLASIRSARP['data']);

            foreach ($asientos as $indexAsiento => $asiento) {
                $idImgAsiento = $asiento['idImgAsiento'] ?? '';
                $tipo = $asiento['tipo'] ?? 'A';
                $nroTotalPag = $resultLASIRSARP['nroTotalPag'] ?? '1';

                error_log("Procesando asiento " . ($indexAsiento + 1) . " - idImg: $idImgAsiento");

                // Verificar si listPag es un array de páginas o una sola página
                $listPag = $asiento['listPag'] ?? [];

                // Si listPag tiene las claves 'nroPagRef' y 'pagina', es una sola página
                // Si no, es un array de páginas
                $paginas = [];
                if (isset($listPag['nroPagRef']) && isset($listPag['pagina'])) {
                    // Caso 1: Una sola página
                    $paginas[] = $listPag;
                    error_log("  - Asiento con 1 página");
                } elseif (is_array($listPag) && count($listPag) > 0) {
                    // Caso 2: Múltiples páginas
                    $paginas = $listPag;
                    error_log("  - Asiento con " . count($paginas) . " páginas");
                } else {
                    error_log("  - Asiento sin páginas válidas");
                }

                // Obtener imagen para cada página
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
        } else {
            error_log("No se ejecutó VASIRSARP - Sin transacción");
        }

        $item['imagenes'] = $imagenes;

        // ========================================
        // 3. PREPARAR RESPUESTA FINAL
        // ========================================
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

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
            error_log("LASIRSARP Request: " . $jsonData);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json; charset=UTF-8",
                    "Content-Length: " . strlen($jsonData)
                ],
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 60
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                error_log("CURL Error LASIRSARP: $error");
                return [
                    'success' => false,
                    'message' => "Error de conexión: $error",
                    'data' => []
                ];
            }

            curl_close($ch);

            $jsonResponse = json_decode($response, true);
            error_log("Este es el jsonResponse: " . print_r($jsonResponse, true));

            if ($httpCode == 200) {
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Error JSON LASIRSARP: " . json_last_error_msg());
                    return [
                        'success' => false,
                        'message' => 'Error al decodificar respuesta',
                        'data' => []
                    ];
                }

                return $this->procesarRespuestaLASIRSARP($jsonResponse);
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP $httpCode",
                    'data' => []
                ];
            }
        } catch (\Exception $e) {
            error_log("Exception en ejecutarLASIRSARP: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    // ========================================
    // PROCESAR RESPUESTA LASIRSARP - MEJORADO
    // ========================================
    private function procesarRespuestaLASIRSARP($jsonResponse)
    {
        try {
            if (
                !isset($jsonResponse['listarAsientosSIRSARPResponse']) ||
                !isset($jsonResponse['listarAsientosSIRSARPResponse']['asientos'])
            ) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron asientos en la respuesta',
                    'data' => []
                ];
            }

            $asientos = $jsonResponse['listarAsientosSIRSARPResponse']['asientos'];

            // Extraer información básica
            $transaccion = $asientos['transaccion'] ?? '';
            $nroTotalPag = $asientos['nroTotalPag'] ?? '0';

            $todosLosElementos = [];

            // ========================================
            // 1. PROCESAR ASIENTOS (listAsientos)
            // ========================================
            if (isset($asientos['listAsientos']) && !empty($asientos['listAsientos'])) {
                $listAsientos = $asientos['listAsientos'];

                // Normalizar a array si es un solo elemento
                if (!isset($listAsientos[0])) {
                    $listAsientos = [$listAsientos];
                }

                foreach ($listAsientos as $asiento) {
                    $todosLosElementos[] = [
                        'idImgAsiento' => $asiento['idImgAsiento'] ?? '',
                        'numPag' => $asiento['numPag'] ?? '',
                        'tipo' => $asiento['tipo'] ?? 'ASIENTO',
                        'listPag' => $asiento['listPag'] ?? [],
                        'categoria' => 'asiento' // Para identificar tipo
                    ];
                }

                error_log("Asientos procesados: " . count($listAsientos));
            }

            // ========================================
            // 2. PROCESAR FICHAS (listFichas)
            // ========================================
            if (isset($asientos['listFichas']) && !empty($asientos['listFichas'])) {
                $listFichas = $asientos['listFichas'];

                // Normalizar a array si es un solo elemento
                if (!isset($listFichas[0])) {
                    $listFichas = [$listFichas];
                }

                foreach ($listFichas as $ficha) {
                    $todosLosElementos[] = [
                        'idImgAsiento' => $ficha['idImgFicha'] ?? '', // Nota: usa idImgFicha
                        'numPag' => $ficha['numPag'] ?? '',
                        'tipo' => $ficha['tipo'] ?? 'FICHA',
                        'listPag' => $ficha['listPag'] ?? [],
                        'categoria' => 'ficha' // Para identificar tipo
                    ];
                }

                error_log("Fichas procesadas: " . count($listFichas));
            }

            // ========================================
            // 3. VERIFICAR QUE HAY DATOS
            // ========================================
            if (empty($todosLosElementos)) {
                error_log("No se encontraron asientos ni fichas");
                return [
                    'success' => false,
                    'message' => 'No se encontraron asientos ni fichas registrales',
                    'data' => []
                ];
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
            return [
                'success' => false,
                'message' => 'Error al procesar respuesta: ' . $e->getMessage(),
                'data' => []
            ];
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

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json; charset=UTF-8",
                    "Content-Length: " . strlen($jsonData)
                ],
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 45
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return ['success' => false, 'message' => "Error: $error"];
            }

            curl_close($ch);

            if ($httpCode == 200) {
                $jsonResponse = json_decode($response, true);

                $img = $jsonResponse['verAsientoSIRSARPResponse']['img'] ?? null;

                return [
                    'success' => true,
                    'message' => 'Imagen obtenida',
                    'img' => $img
                ];
            }

            return ['success' => false, 'message' => "HTTP $httpCode"];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
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

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json; charset=UTF-8",
                    "Content-Length: " . strlen($jsonData)
                ],
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 45
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return ['success' => false, 'message' => "Error: $error", 'data' => []];
            }

            curl_close($ch);

            if ($httpCode == 200) {
                $jsonResponse = json_decode($response, true);

                $vehiculo = $jsonResponse['verRPVExtraResponse']['vehiculo'] ?? [];

                error_log("Resultado de vehiculo" . print_r($vehiculo, true));

                return [
                    'success' => true,
                    'message' => 'Consulta vehicular exitosa',
                    'data' => $vehiculo
                ];
            }

            return ['success' => false, 'message' => "HTTP $httpCode", 'data' => []];
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

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            error_log("TSIRSARP Request: " . $jsonData);

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json; charset=UTF-8",
                    "Content-Length: " . strlen($jsonData)
                ],
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 45
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlInfo = curl_getinfo($ch);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);

                error_log("CURL Error TSIRSARP: $error");
                return [
                    'success' => false,
                    'message' => "Error de conexión con SUNARP: $error",
                    'data' => []
                ];
            }

            curl_close($ch);

            // Intentar decodificar JSON y mostrar estructura
            $jsonResponse = json_decode($response, true);

            error_log("TSIRSARP Response Code: $httpCode");
            error_log("TSIRSARP Response: " . $response);

            if ($httpCode == 200) {
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Error JSON TSIRSARP: " . json_last_error_msg());
                    return [
                        'success' => false,
                        'message' => 'Error al decodificar respuesta de SUNARP',
                        'data' => []
                    ];
                }

                return $this->procesarRespuestaTSIRSARP($jsonResponse, $tipoParticipante);
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP $httpCode en el servicio SUNARP.",
                    'data' => []
                ];
            }
        } catch (\Exception $e) {

            error_log("Exception en consultarTSIRSARP: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al consultar SUNARP: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    // ========================================
    // PROCESAR RESPUESTA TSIRSARP - CORREGIDO
    // ========================================
    private function procesarRespuestaTSIRSARP($jsonResponse, $tipoParticipante)
    {
        try {
            error_log("Procesando respuesta TSIRSARP. Tipo: $tipoParticipante");

            if (
                !isset($jsonResponse['buscarTitularidadSIRSARPResponse']) ||
                !isset($jsonResponse['buscarTitularidadSIRSARPResponse']['respuestaTitularidad'])
            ) {

                return [
                    'success' => false,
                    'message' => 'No se encontraron registros en SUNARP',
                    'data' => []
                ];
            }

            $return = $jsonResponse['buscarTitularidadSIRSARPResponse']['respuestaTitularidad']['respuestaTitularidad'];

            $registros = [];
            if (isset($return[0])) {
                $registros = $return;
            } else {
                $registros = [$return];
            }

            $resultados = [];

            // Obtener credenciales para consultas adicionales
            $usuario = $this->nombreUsuario;
            $clave = $this->passUsuario;

            // ========================================
            // OBTENER CATÁLOGO DE OFICINAS PRIMERO
            // ========================================
            $catalogoOficinas = [];
            $resultGOficina = $this->ejecutarGOficina($usuario, $clave);

            if ($resultGOficina['success']) {
                $catalogoOficinas = $resultGOficina['data'];
            }

            // ========================================
            // PROCESAMIENTO SIMPLIFICADO: SOLO INFO BÁSICA
            // ========================================
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
                    // Agregar campos para identificación
                    'indice' => $index,
                    // Inicializar campos vacíos que se cargarán bajo demanda
                    'asientos' => [],
                    'imagenes' => [],
                    'datos_vehiculo' => [],
                    'detalle_cargado' => false
                ];

                // Obtener códigos de zona y oficina
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

                // Guardar códigos para uso posterior
                $item['codigo_zona'] = $codigoZona;
                $item['codigo_oficina'] = $codigoOficina;

                $resultados[] = $item;
            }

            // ========================================
            // LOG DE RESPUESTA FINAL
            // ========================================
            error_log("=== RESPONSE INICIAL (sin detalles) ===");
            error_log("Total partidas: " . count($resultados));

            if (empty($resultados)) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron registros válidos en SUNARP',
                    'data' => []
                ];
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
    // NUEVO MÉTODO: CARGAR DETALLE DE PARTIDA INDIVIDUAL
    // ========================================
    public function cargarDetallePartida()
    {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (
            !isset($input['numero_partida']) ||
            !isset($input['codigo_zona']) ||
            !isset($input['codigo_oficina'])
        ) {

            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos: numero_partida, codigo_zona, codigo_oficina'
            ]);
            return;
        }

        $usuario = $_ENV['PIDE_SUNARP_USUARIO'];
        $clave = $_ENV['PIDE_SUNARP_PASS'];
        $numeroPartida = $input['numero_partida'];
        $codigoZona = $input['codigo_zona'];
        $codigoOficina = $input['codigo_oficina'];
        $numeroPlaca = $input['numero_placa'] ?? '';
        $registroCodigo = '21000'; // Vehículos

        error_log("=== CARGANDO DETALLE DE PARTIDA ===");
        error_log("Partida: $numeroPartida, Zona: $codigoZona, Oficina: $codigoOficina");

        try {
            $detalle = [
                'asientos' => [],
                'imagenes' => [],
                'datos_vehiculo' => []
            ];

            // ========================================
            // 1. CONSULTA LASIRSARP (Asientos y Fichas)
            // ========================================
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
                    $transaccion = $resultLASIRSARP['transaccion'] ?? '';
                    $nroTotalPag = $resultLASIRSARP['nroTotalPag'] ?? '1';

                    $totalElementos = count($detalle['asientos']);
                    error_log("LASIRSARP exitoso - Total elementos (asientos + fichas): $totalElementos");

                    // ========================================
                    // 2. CONSULTA VASIRSARP (Imágenes)
                    // ========================================
                    if (!empty($resultLASIRSARP['data']) && !empty($transaccion)) {
                        error_log("=== EJECUTANDO VASIRSARP ===");

                        $imagenes = [];

                        // Normalizar elementos
                        $elementosOriginales = $resultLASIRSARP['data'];
                        if (!isset($elementosOriginales[0])) {
                            $elementosOriginales = [$elementosOriginales];
                            error_log("Normalizado: Un solo elemento detectado");
                        }

                        // Invertir para mostrar más recientes primero
                        $elementos = array_reverse($elementosOriginales);
                        $contadorImagenGlobal = 0;

                        error_log("Total elementos a procesar: " . count($elementos));

                        foreach ($elementos as $indexElemento => $elemento) {
                            if (!is_array($elemento)) {
                                error_log("⚠️ Elemento " . ($indexElemento + 1) . " no es un array - SALTANDO");
                                continue;
                            }

                            // Obtener ID de imagen (puede ser idImgAsiento o idImgFicha)
                            $idImg = (string)($elemento['idImgAsiento'] ?? '');
                            $tipo = $elemento['tipo'] ?? 'ASIENTO';
                            $categoria = $elemento['categoria'] ?? 'asiento';
                            $listPag = $elemento['listPag'] ?? [];

                            error_log("Procesando elemento " . ($indexElemento + 1) . "/" . count($elementos) .
                                " - Tipo: $tipo, Categoría: $categoria, idImg: $idImg");

                            if (!is_array($listPag)) {
                                error_log("  - listPag no es un array - SALTANDO");
                                continue;
                            }

                            // Normalizar páginas
                            $paginas = [];
                            if (isset($listPag['nroPagRef']) && isset($listPag['pagina'])) {
                                // Caso 1: Una sola página (objeto simple)
                                $paginas[] = $listPag;
                                error_log("  - Elemento con 1 página");
                            } elseif (count($listPag) > 0 && isset($listPag[0])) {
                                // Caso 2: Múltiples páginas (array numérico)
                                $paginas = $listPag;
                                error_log("  - Elemento con " . count($paginas) . " páginas");
                            } else {
                                error_log("  - Elemento sin páginas válidas");
                                error_log("  - Estructura de listPag: " . print_r($listPag, true));
                            }

                            // Obtener imagen para cada página
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

                        $detalle['imagenes'] = $imagenes;
                        error_log("Total imágenes obtenidas: " . count($imagenes));
                    } else {
                        error_log("No se ejecutó VASIRSARP - Sin transacción o sin elementos");
                    }
                } else {
                    error_log("LASIRSARP falló: " . $resultLASIRSARP['message']);
                }
            }

            // ========================================
            // 3. CONSULTA VDRPVExtra (Vehículos)
            // ========================================
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

            // ========================================
            // 4. LOG FINAL Y RESPUESTA
            // ========================================
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
    // MÉTODOS INTERNOS SUNAT
    // ========================================

    private function consultarRUCInterno($sunatController, $ruc)
    {
        $metodoReflexion = new \ReflectionMethod($sunatController, 'consultarServicioSUNATRest');
        $metodoReflexion->setAccessible(true);
        return $metodoReflexion->invoke($sunatController, $ruc);
    }

    private function buscarRazonSocialInterno($sunatController, $razonSocial)
    {
        $metodoReflexion = new \ReflectionMethod($sunatController, 'buscarPorRazonSocialSUNATRest');
        $metodoReflexion->setAccessible(true);
        return $metodoReflexion->invoke($sunatController, $razonSocial);
    }

    // ========================================
    // OBTENER DATOS DE RENIEC

    // ========================================
    private function obtenerDatosRENIEC($dni, $dniUsuario, $passwordPIDE)
    {
        try {
            $urlRENIEC = $_ENV['PIDE_URL_RENIEC'] ?? "https://ws2.pide.gob.pe/Rest/RENIEC/Consultar?out=json";

            $data = [
                "PIDE" => [
                    "nuDniConsulta" => $dni,
                    "nuDniUsuario"  => $dniUsuario,
                    "nuRucUsuario"  => $this->rucUsuario,
                    "password"      => $passwordPIDE
                ]
            ];

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            error_log("Request RENIEC: $jsonData");

            $ch = curl_init($urlRENIEC);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => "POST",
                CURLOPT_HTTPHEADER     => [
                    "Content-Type: application/json",
                    "Accept: application/json"
                ],
                CURLOPT_POSTFIELDS     => $jsonData,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT        => 45
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                error_log("CURL Error RENIEC: $error");
                return [
                    'success' => false,
                    'message' => "Error de conexión con RENIEC: $error"
                ];
            }

            curl_close($ch);

            error_log("RENIEC Response Code: $httpCode");
            error_log("RENIEC Response: " . substr($response, 0, 500));

            if ($httpCode == 200) {
                $jsonResponse = json_decode($response, true);
                $estructura = $jsonResponse['consultarResponse']['return'];

                if (isset($estructura['datosPersona'])) {
                    $datosPersona = $estructura['datosPersona'];

                    return [
                        'success' => true,
                        'message' => 'Datos obtenidos exitosamente de RENIEC',
                        'data' => [[
                            'tipo' => 'PERSONA_NATURAL',
                            'dni' => $dni,
                            'nombres' => $datosPersona['prenombres'] ?? '',
                            'apellido_paterno' => $datosPersona['apPrimer'] ?? '',
                            'apellido_materno' => $datosPersona['apSegundo'] ?? '',
                            'foto' => $datosPersona['foto'] ?? null,
                            'nombres_completos' => trim(
                                ($datosPersona['prenombres'] ?? '') . ' ' .
                                    ($datosPersona['apPrimer'] ?? '') . ' ' .
                                    ($datosPersona['apSegundo'] ?? '')
                            )
                        ]],
                        'total' => 1
                    ];
                }
            }

            return [
                'success' => false,
                'message' => $estructura['deResultado']
            ];
        } catch (\Exception $e) {
            error_log("Exception en obtenerDatosRENIEC: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al consultar RENIEC: ' . $e->getMessage()
            ];
        }
    }
}
