<?php

namespace App\Controllers;

class ConsultasSunarpController {
    
    private $urlSUNARP;
    private $rucUsuario;
    private $nombreUsuario;
    private $passUsuario;
    
    public function __construct() {
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
    public function buscarPersonaNatural() {
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
    public function buscarPersonaJuridica() {
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
                'message' => 'Faltan datos: dniUsuario o password'
            ]);
            return;
        }

        $dniUsuario = trim($input['dniUsuario']);
        $passwordPIDE = trim($input['password']);
        $tipoBusqueda = $input['tipoBusqueda'] ?? 'ruc';

        error_log("=== INICIO BÚSQUEDA PERSONA JURÍDICA (SUNAT) ===");
        error_log("Tipo de búsqueda: $tipoBusqueda");

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

            $razonSocial = trim($input['razonSocial']);
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
    public function consultarTSIRSARPNatural() {
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
    public function consultarTSIRSARPJuridica() {
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
    public function consultarGOficina() {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['usuario']) || !isset($input['clave'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Faltan credenciales: usuario o clave']);
            return;
        }

        $usuario = trim($input['usuario']);
        $clave = trim($input['clave']);

        error_log("=== CONSULTA GOficina ===");

        $resultado = $this->ejecutarGOficina($usuario, $clave);

        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    private function ejecutarGOficina($usuario, $clave) {
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

    private function ejecutarLASIRSARP($usuario, $clave, $zona, $oficina, $partida, $registro) {
        try {
            $url = $this->urlSUNARP . "/LASIRSARP?out=json";
            
            $data = [
                "PIDE" => [
                    "usuario" => (string)$usuario,
                    "clave" => (string)$clave,
                    "zona" => (string)$zona,
                    "oficina" => (string)$oficina,
                    "partida" => (string)$partida,
                    "registro" => (string)$registro
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
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return ['success' => false, 'message' => 'Error al decodificar JSON', 'data' => []];
                }

                $asientos = $jsonResponse['listarAsientosSIRSARPResponse']['asientos']['listAsientos'] ?? [];
                $transaccion = $jsonResponse['listarAsientosSIRSARPResponse']['asientos']['transaccion'] ?? '';
                
                return [
                    'success' => true,
                    'message' => 'Consulta exitosa',
                    'data' => $asientos,
                    'transaccion' => $transaccion,
                    'nroTotalPag' => $jsonResponse['listarAsientosSIRSARPResponse']['asientos']['nroTotalPag'] ?? ''
                ];
            }

            return ['success' => false, 'message' => "HTTP $httpCode", 'data' => []];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    private function ejecutarVASIRSARP($usuario, $clave, $transaccion, $idImg, $tipo, $nroTotalPag, $nroPagRef, $pagina) {
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

    private function ejecutarVDRPVExtra($usuario, $clave, $zona, $oficina, $placa) {
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
                
                $vehiculo = $jsonResponse['verDetalleRPVExtraResponse']['vehiculo'] ?? [];

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
    private function consultarTSIRSARP($usuario, $clave, $tipoParticipante, $apellidoPaterno, $apellidoMaterno, $nombres, $razonSocial) {
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
    // PROCESAR RESPUESTA TSIRSARP
    // ========================================
    private function procesarRespuestaTSIRSARP($jsonResponse, $tipoParticipante) {
        try {
            error_log("Procesando respuesta TSIRSARP. Tipo: $tipoParticipante");
            
            if (!isset($jsonResponse['buscarTitularidadSIRSARPResponse']) || 
                !isset($jsonResponse['buscarTitularidadSIRSARPResponse']['respuestaTitularidad'])) {
                
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
            } else {
                
            }

            foreach ($registros as $index => $registro) {
                
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
                    'registro' => $registro['registro'] ?? ''
                ];

                // ========================================
                // OBTENER CÓDIGOS DE ZONA Y OFICINA
                // ========================================
                $codigoZona = $item['zona'];
                $codigoOficina = $item['oficina'];
                
                if (!empty($catalogoOficinas) && !empty($item['oficina'])) {
                    $oficinaKey = strtoupper(trim($item['oficina']));
                    
                    if (isset($catalogoOficinas[$oficinaKey])) {
                        $codigoZona = $catalogoOficinas[$oficinaKey]['codZona'];
                        $codigoOficina = $catalogoOficinas[$oficinaKey]['codOficina'];
                    } else {
                    }
                }

                // ========================================
                // CONSULTA LASIRSARP (Asientos)
                // ========================================
                if (!empty($item['numero_partida']) && !empty($codigoZona) && !empty($codigoOficina)) {
                    
                    $registroCodigo = $tipoParticipante === 'N' ? '23000' : '22000';
                    
                    $resultLASIRSARP = $this->ejecutarLASIRSARP(
                        $usuario,
                        $clave,
                        $codigoZona,
                        $codigoOficina,
                        $item['numero_partida'],
                        $registroCodigo
                    );
                    
                    if ($resultLASIRSARP['success']) {
                        $item['asientos'] = $resultLASIRSARP['data'];
                        $item['transaccion'] = $resultLASIRSARP['transaccion'] ?? '';
                        
                        // ========================================
                        // CONSULTA VASIRSARP (Imágenes)
                        // ========================================
                        if (!empty($resultLASIRSARP['data']) && !empty($item['transaccion'])) {
                            $imagenes = [];
                            $asientos = array_reverse($resultLASIRSARP['data']);
                            
                            foreach ($asientos as $indexAsiento => $asiento) {
                                $resultVASIRSARP = $this->ejecutarVASIRSARP(
                                    $usuario,
                                    $clave,
                                    $item['transaccion'],
                                    $asiento['idImgAsiento'],
                                    $asiento['tipo'] ?? 'A',
                                    $resultLASIRSARP['nroTotalPag'] ?? '',
                                    $asiento['listPag']['nroPagRef'] ?? '',
                                    $asiento['listPag']['pagina'] ?? ''
                                );
                                
                                if ($resultVASIRSARP['success'] && !empty($resultVASIRSARP['img'])) {
                                    $imagenes[] = [
                                        'pagina' => $indexAsiento + 1,
                                        'imagen_base64' => $resultVASIRSARP['img']
                                    ];
                                }
                            }
                            
                            $item['imagenes'] = $imagenes;
                        }
                    } else {
                    }
                }

                // ========================================
                // CONSULTA VDRPVExtra (Vehículos)
                // ========================================
                if (!empty($item['numero_placa']) && 
                    trim($item['numero_placa']) !== '-' && 
                    !empty($codigoZona) && 
                    !empty($codigoOficina)) {
                    
                    $resultVDRPVExtra = $this->ejecutarVDRPVExtra(
                        $usuario,
                        $clave,
                        $codigoZona,
                        $codigoOficina,
                        $item['numero_placa']
                    );
                    
                    if ($resultVDRPVExtra['success']) {
                        $item['datos_vehiculo'] = $resultVDRPVExtra['data'];
                    }
                }

                $resultados[] = $item;
            }
            

            // Al final de procesarRespuestaTSIRSARP, antes del return success
            error_log("=== RESPONSE FINAL A ENVIAR ===");
            error_log("Total partidas: " . count($resultados));
            foreach ($resultados as $idx => $res) {
                error_log("Partida $idx:");
                error_log("  - Tiene asientos: " . (isset($res['asientos']) ? 'SÍ (' . count($res['asientos']) . ')' : 'NO'));
                error_log("  - Tiene imágenes: " . (isset($res['imagenes']) ? 'SÍ (' . count($res['imagenes']) . ')' : 'NO'));
                error_log("  - Tiene vehículo: " . (isset($res['datos_vehiculo']) ? 'SÍ' : 'NO'));
                error_log("  - Placa: " . ($res['numero_placa'] ?? 'N/A'));
            }

            if (empty($resultados)) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron registros válidos en SUNARP',
                    'data' => []
                ];
            }

            return [
                'success' => true,
                'message' => 'Consulta exitosa con datos adicionales',
                'data' => $resultados,
                'total' => count($resultados)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al procesar respuesta de SUNARP: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    

    // ========================================
    // MÉTODOS INTERNOS SUNAT
    // ========================================

    private function consultarRUCInterno($sunatController, $ruc) {
        $metodoReflexion = new \ReflectionMethod($sunatController, 'consultarServicioSUNATRest');
        $metodoReflexion->setAccessible(true);
        return $metodoReflexion->invoke($sunatController, $ruc);
    }

    private function buscarRazonSocialInterno($sunatController, $razonSocial) {
        $metodoReflexion = new \ReflectionMethod($sunatController, 'buscarPorRazonSocialSUNATRest');
        $metodoReflexion->setAccessible(true);
        return $metodoReflexion->invoke($sunatController, $razonSocial);
    }

    // ========================================
    // OBTENER DATOS DE RENIEC
    // ========================================
    private function obtenerDatosRENIEC($dni, $dniUsuario, $passwordPIDE) {
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

                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Error JSON RENIEC: " . json_last_error_msg());
                    return [
                        'success' => false,
                        'message' => 'Error al decodificar respuesta de RENIEC'
                    ];
                }

                if (isset($jsonResponse['consultarResponse']['return']['datosPersona'])) {
                    $datosPersona = $jsonResponse['consultarResponse']['return']['datosPersona'];

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
                'message' => 'No se encontraron datos en RENIEC para el DNI proporcionado'
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
?>