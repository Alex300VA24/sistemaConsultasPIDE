<?php

namespace App\Controllers;

class ConsultasSunarpController {
    
    private $urlSUNARP;
    private $rucUsuario;
    
    public function __construct() {
        $this->urlSUNARP = $_ENV['PIDE_URL_SUNARP'] ?? "https://ws2.pide.gob.pe/Rest/SUNARP";
        $this->rucUsuario = $_ENV['PIDE_RUC_EMPRESA'] ?? "20164091547";
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

        error_log("✅ Datos RENIEC obtenidos correctamente");
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

            error_log("✅ Datos obtenidos de SUNAT");

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

            error_log("✅ Encontrados " . count($resultadosSunat['data']) . " resultados en SUNAT");

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

        $usuario = "20164091547-18066272";
        $clave = "z#rxstzNYUb4NZQ";
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

        http_response_code($resultado['success'] ? 200 : 404);
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

        $usuario = "20164091547-18066272";
        $clave = "z#rxstzNYUb4NZQ";
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

        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    // ========================================
    // MÉTODO INTERNO CONSULTAR TSIRSARP
    // ========================================
    private function consultarTSIRSARP($usuario, $clave, $tipoParticipante, $apellidoPaterno, $apellidoMaterno, $nombres, $razonSocial) {
        try {
            $url = $this->urlSUNARP . "/TSIRSARP?out=json";

            $data = [
                "PIDE" => [
                    "usuario"         => trim((string)$usuario),
                    "clave"           => trim((string)$clave),
                    "tipoParticipante"=> trim((string)$tipoParticipante),
                    "apellidoPaterno" => trim((string)$apellidoPaterno),
                    "apellidoMaterno" => trim((string)$apellidoMaterno),
                    "nombres"         => trim((string)$nombres),
                    "razonSocial"     => trim((string)$razonSocial)
                ]
            ];

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            // ============= ARCHIVO DE DEPURACIÓN =============
            $debugFile = __DIR__ . '/../../logs/tsirsarp_debug_' . date('Y-m-d') . '.txt';
            $debugDir = dirname($debugFile);
            if (!file_exists($debugDir)) {
                mkdir($debugDir, 0755, true);
            }
            
            $debugInfo = "\n" . str_repeat("=", 80) . "\n";
            $debugInfo .= "CONSULTA TSIRSARP - " . date('Y-m-d H:i:s') . "\n";
            $debugInfo .= str_repeat("=", 80) . "\n";
            $debugInfo .= "URL: $url\n";
            $debugInfo .= "Tipo Participante: $tipoParticipante\n";
            $debugInfo .= "Usuario: $usuario\n";
            $debugInfo .= "Clave: " . str_repeat("*", strlen($clave)) . "\n";
            
            if ($tipoParticipante === 'N') {
                $debugInfo .= "--- Persona Natural ---\n";
                $debugInfo .= "Apellido Paterno: $apellidoPaterno\n";
                $debugInfo .= "Apellido Materno: $apellidoMaterno\n";
                $debugInfo .= "Nombres: $nombres\n";
            } else {
                $debugInfo .= "--- Persona Jurídica ---\n";
                $debugInfo .= "Razón Social: $razonSocial\n";
            }
            
            $debugInfo .= "\n--- REQUEST JSON ---\n";
            $debugInfo .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            // ================================================

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

            // ============= DEPURACIÓN CURL INFO =============
            $debugInfo .= "\n--- CURL INFO ---\n";
            $debugInfo .= "HTTP Code: $httpCode\n";
            $debugInfo .= "Total Time: " . $curlInfo['total_time'] . "s\n";
            $debugInfo .= "Connect Time: " . $curlInfo['connect_time'] . "s\n";
            $debugInfo .= "Size Download: " . $curlInfo['size_download'] . " bytes\n";
            // ================================================

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                
                // ============= DEPURACIÓN ERROR =============
                $debugInfo .= "\n--- CURL ERROR ---\n";
                $debugInfo .= "Error: $error\n";
                file_put_contents($debugFile, $debugInfo, FILE_APPEND);
                // ============================================
                
                error_log("CURL Error TSIRSARP: $error");
                return [
                    'success' => false,
                    'message' => "Error de conexión con SUNARP: $error",
                    'data' => []
                ];
            }

            curl_close($ch);

            // ============= DEPURACIÓN RESPONSE =============
            $debugInfo .= "\n--- RESPONSE ---\n";
            $debugInfo .= "HTTP Code: $httpCode\n";
            $debugInfo .= "Response Length: " . strlen($response) . " caracteres\n";
            $debugInfo .= "\n--- RESPONSE BODY (primeros 2000 caracteres) ---\n";
            $debugInfo .= substr($response, 0, 2000) . "\n";
            
            if (strlen($response) > 2000) {
                $debugInfo .= "\n... (respuesta truncada, total: " . strlen($response) . " caracteres)\n";
            }
            
            // Intentar decodificar JSON y mostrar estructura
            $jsonResponse = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $debugInfo .= "\n--- JSON DECODIFICADO ---\n";
                $debugInfo .= json_encode($jsonResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                $debugInfo .= "\n--- ERROR JSON ---\n";
                $debugInfo .= "Error al decodificar JSON: " . json_last_error_msg() . "\n";
            }
            
            file_put_contents($debugFile, $debugInfo, FILE_APPEND);
            // ================================================

            error_log("TSIRSARP Response Code: $httpCode");
            error_log("TSIRSARP Response: " . $response);
            error_log("TSIRSARP Debug file: $debugFile");

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
                    'message' => "Error HTTP $httpCode en el servicio SUNARP. Ver log: $debugFile",
                    'data' => []
                ];
            }

        } catch (\Exception $e) {
            // ============= DEPURACIÓN EXCEPTION =============
            if (isset($debugFile)) {
                $debugInfo = "\n--- EXCEPTION ---\n";
                $debugInfo .= "Message: " . $e->getMessage() . "\n";
                $debugInfo .= "File: " . $e->getFile() . "\n";
                $debugInfo .= "Line: " . $e->getLine() . "\n";
                $debugInfo .= "Stack Trace:\n" . $e->getTraceAsString() . "\n";
                file_put_contents($debugFile, $debugInfo, FILE_APPEND);
            }
            // ================================================
            
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
                error_log("No se encontró TSIRSARPResponse en la respuesta");
                return [
                    'success' => false,
                    'message' => 'No se encontraron registros en SUNARP',
                    'data' => []
                ];
            }

            $return = $jsonResponse['buscarTitularidadSIRSARPResponse']['respuestaTitularidad'];
            
            $registros = [];
            if (isset($return[0])) {
                $registros = $return;
            } else {
                $registros = [$return];
            }

            $resultados = [];

            foreach ($registros as $registro) {
                $item = [
                    'registro' => $registro['registro'] ?? '',
                    'libro' => $registro['libro'] ?? '',
                    'partida' => $registro['partida'] ?? '',
                    'asiento' => $registro['asiento'] ?? '',
                    'placa' => $registro['placa'] ?? '',
                    'zona' => $registro['zona'] ?? '',
                    'oficina' => $registro['oficina'] ?? '',
                    'estado' => $registro['estado'] ?? '',
                    'descripcion' => $registro['descripcion'] ?? ''
                ];

                $resultados[] = $item;
                error_log("Registro TSIRSARP procesado: " . json_encode($item));
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
                'message' => 'Consulta exitosa',
                'data' => $resultados,
                'total' => count($resultados)
            ];

        } catch (\Exception $e) {
            error_log("Exception en procesarRespuestaTSIRSARP: " . $e->getMessage());
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