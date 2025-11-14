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
    // BUSCAR PERSONA NATURAL (RENIEC)
    // ========================================
    public function buscarPersonaNatural() {
        header('Content-Type: application/json');

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

        // 1. Obtener datos de RENIEC
        $datosReniec = $this->obtenerDatosRENIEC($dni, $dniUsuario, $passwordPIDE);
        
        if (!$datosReniec['success']) {
            http_response_code(404);
            echo json_encode($datosReniec);
            return;
        }
        
        http_response_code($datosReniec['success'] ? 200 : 404);
        echo json_encode($datosReniec);
    }

    // ========================================
    // BUSCAR PERSONA JURÍDICA (SUNAT)
    // ========================================
    public function buscarPersonaJuridica() {
        header('Content-Type: application/json');

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

        $razonSocial = trim($input['razonSocial']) ?? '';

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

            // Consultar en SUNAT para obtener razón social
            $datosSunat = $this->obtenerDatosPorRUC($ruc);
            
            if (!$datosSunat['success']) {
                http_response_code(404);
                echo json_encode($datosSunat);
                return;
            }

            $razonSocial = $datosSunat['data']['razon_social'];

        } else if ($tipoBusqueda === 'razonSocial') {
            if (!isset($input['razonSocial'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Razón social no proporcionada'
                ]);
                return;
            }

            // Consultar en SUNAT para obtener razón social
            $datosSunat = $this->obtenerDatosPorRazonSocial($razonSocial);
            if (!$datosSunat['success']) {
                http_response_code(404);
                echo json_encode($datosSunat);
                return;
            }

            $razonSocial = $datosSunat['data']['razon_social'];
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Tipo de búsqueda inválido. Use "ruc" o "razonSocial"'
            ]);
            return;
        }
        
        http_response_code($datosSunat['success'] ? 200 : 404);
        echo json_encode($datosSunat);
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

            $ch = curl_init($urlRENIEC);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => "POST",
                CURLOPT_HTTPHEADER     => [
                    "Content-Type: application/json",
                    "Accept: application/json"
                ],
                CURLOPT_POSTFIELDS     => $jsonData,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT        => 45
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return [
                    'success' => false,
                    'message' => "Error de conexión con RENIEC: $error"
                ];
            }

            curl_close($ch);

            if ($httpCode == 200) {
                $jsonResponse = json_decode($response, true);

                if (isset($jsonResponse['consultarResponse']['return']['datosPersona'])) {
                    $datosPersona = $jsonResponse['consultarResponse']['return']['datosPersona'];

                    return [
                        'success' => true,
                        'data' => [
                            'dni' => $dni,
                            'nombres' => $datosPersona['prenombres'] ?? '',
                            'apellido_paterno' => $datosPersona['apPrimer'] ?? '',
                            'apellido_materno' => $datosPersona['apSegundo'] ?? '',
                            'foto' => $datosPersona['foto'] ?? null
                        ]
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'No se encontraron datos en RENIEC para el DNI proporcionado'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al consultar RENIEC: ' . $e->getMessage()
            ];
        }
    }

    // ========================================
    // 🔍 OBTENER DATOS DE SUNAT
    // ========================================
    private function obtenerDatosPorRUC($ruc) {
        try {
            $urlSUNAT = $_ENV['PIDE_URL_SUNAT'] ?? "https://ws3.pide.gob.pe/services/SunatConsultaRuc?wsdl";

            $soapEnvelope = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                  xmlns:ws="http://ws.registro.servicio.sunat.gob.pe">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:getDatosPrincipales>
         <numRuc>$ruc</numRuc>
      </ws:getDatosPrincipales>
   </soapenv:Body>
</soapenv:Envelope>
XML;

            $headers = [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "getDatosPrincipales"',
                'Content-Length: ' . strlen($soapEnvelope)
            ];

            $ch = curl_init($urlSUNAT);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $soapEnvelope,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 45
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200) {
                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($response);
                
                if ($xml === false) {
                    return [
                        'success' => false,
                        'message' => 'Error al parsear respuesta de SUNAT'
                    ];
                }

                $namespaces = $xml->getNamespaces(true);
                foreach ($namespaces as $prefix => $ns) {
                    $xml->registerXPathNamespace($prefix ?: 'default', $ns);
                }

                $multiRef = $xml->xpath('//multiRef');
                
                if (empty($multiRef)) {
                    return [
                        'success' => false,
                        'message' => 'No se encontró información en SUNAT para el RUC consultado'
                    ];
                }
                
                $datos = $multiRef[0];

                return [
                    'success' => true,
                    'message' => 'Consulta exitosa',
                    'data' => [
                        'ruc' => (string)($datos->ddp_numruc ?? $ruc),
                        'razon_social' => (string)($datos->ddp_nombre ?? ''),
                        'direccion_completa' => $this->construirDireccionSunat($datos),
                        'estado_contribuyente' => (string)($datos->desc_estado ?? ''),
                        'condicion_domicilio' => (string)($datos->desc_flag22 ?? ''),
                        'departamento' => (string)($datos->desc_dep ?? ''),
                        'provincia' => (string)($datos->desc_prov ?? ''),
                        'distrito' => (string)($datos->desc_dist ?? '')
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => "Error HTTP $httpCode al consultar SUNAT"
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al consultar SUNAT: ' . $e->getMessage()
            ];
        }
    }

    // RAZON SOCIAL
    private function obtenerDatosPorRazonSocial($razonSocial) {
        // Validar que no esté vacío
        if (empty($razonSocial)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Razon social no puede estar vacia'
            ]);
            exit;
        }
        
        // Realizar consulta por razón social
        $sunatController = new ConsultasSunatController;
        $resultado = $sunatController->buscarPorRazonSocial($razonSocial);    
        
        // FIN LOG
        
        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado);
        exit;
    }

    // ========================================
    // 🔍 CONSULTAR TSIRSARP - PERSONA NATURAL
    // ========================================
    private function consultarTSIRSARPPersonaNatural($datosPersona, $dniUsuario, $passwordPIDE) {
        try {
            $url = $this->urlSUNARP . "/TSIRSARP?out=json";

            $data = [
                "PIDE" => [
                    "usuario" => $dniUsuario,
                    "clave" => $passwordPIDE,
                    "tipoParticipante" => "N",
                    "apellidoPaterno" => $datosPersona['apellido_paterno'],
                    "apellidoMaterno" => $datosPersona['apellido_materno'],
                    "nombres" => $datosPersona['nombres'],
                    "razonSocial" => ""
                ]
            ];

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            error_log("SUNARP Request: " . $jsonData);

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json; charset=UTF-8",
                    "Accept: application/json"
                ],
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 45
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return [
                    'success' => false,
                    'message' => "Error de conexión con SUNARP: $error",
                    'data' => []
                ];
            }

            curl_close($ch);

            error_log("SUNARP Response Code: $httpCode");
            error_log("SUNARP Response: " . substr($response, 0, 500));

            if ($httpCode == 200) {
                $jsonResponse = json_decode($response, true);
                
                return $this->procesarRespuestaTSIRSARP($jsonResponse, 'N', $datosPersona);
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP $httpCode en el servicio SUNARP",
                    'data' => []
                ];
            }

        } catch (\Exception $e) {
            error_log("Exception en consultarTSIRSARPPersonaNatural: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al consultar SUNARP: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    // ========================================
    // 🔍 CONSULTAR TSIRSARP - PERSONA JURÍDICA
    // ========================================
    private function consultarTSIRSARPPersonaJuridica($razonSocial, $dniUsuario, $passwordPIDE) {
        try {
            $url = $this->urlSUNARP . "/TSIRSARP?out=json";

            $data = [
                "PIDE" => [
                    "usuario" => $dniUsuario,
                    "clave" => $passwordPIDE,
                    "tipoParticipante" => "J",
                    "apellidoPaterno" => "",
                    "apellidoMaterno" => "",
                    "nombres" => "",
                    "razonSocial" => $razonSocial
                ]
            ];

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            error_log("SUNARP Request: " . $jsonData);

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json; charset=UTF-8",
                    "Accept: application/json"
                ],
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 45
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return [
                    'success' => false,
                    'message' => "Error de conexión con SUNARP: $error",
                    'data' => []
                ];
            }

            curl_close($ch);

            error_log("SUNARP Response Code: $httpCode");
            error_log("SUNARP Response: " . substr($response, 0, 500));

            if ($httpCode == 200) {
                $jsonResponse = json_decode($response, true);
                
                return $this->procesarRespuestaTSIRSARP($jsonResponse, 'J', ['razon_social' => $razonSocial]);
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP $httpCode en el servicio SUNARP",
                    'data' => []
                ];
            }

        } catch (\Exception $e) {
            error_log("Exception en consultarTSIRSARPPersonaJuridica: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al consultar SUNARP: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    // ========================================
    // 📄 PROCESAR RESPUESTA TSIRSARP
    // ========================================
    private function procesarRespuestaTSIRSARP($jsonResponse, $tipo, $datosOriginales) {
        try {
            // Verificar si hay resultados
            if (!isset($jsonResponse['TSIRSARPResponse']) || 
                !isset($jsonResponse['TSIRSARPResponse']['return'])) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron registros en SUNARP',
                    'data' => []
                ];
            }

            $return = $jsonResponse['TSIRSARPResponse']['return'];
            
            // Puede venir como array o como objeto único
            $registros = [];
            if (isset($return[0])) {
                // Es un array
                $registros = $return;
            } else {
                // Es un objeto único
                $registros = [$return];
            }

            $resultados = [];

            foreach ($registros as $registro) {
                $item = [];

                if ($tipo === 'N') {
                    // Persona Natural
                    $item = [
                        'tipo' => 'PERSONA_NATURAL',
                        'dni' => $datosOriginales['dni'] ?? '',
                        'nombres' => $datosOriginales['nombres'] ?? '',
                        'apellidoPaterno' => $datosOriginales['apellido_paterno'] ?? '',
                        'apellidoMaterno' => $datosOriginales['apellido_materno'] ?? '',
                        'foto' => $datosOriginales['foto'] ?? null,
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
                } else {
                    // Persona Jurídica
                    $item = [
                        'tipo' => 'PERSONA_JURIDICA',
                        'razonSocial' => $datosOriginales['razon_social'] ?? '',
                        'registro' => $registro['registro'] ?? '',
                        'libro' => $registro['libro'] ?? '',
                        'partida' => $registro['partida'] ?? '',
                        'asiento' => $registro['asiento'] ?? '',
                        'zona' => $registro['zona'] ?? '',
                        'oficina' => $registro['oficina'] ?? '',
                        'estado' => $registro['estado'] ?? '',
                        'descripcion' => $registro['descripcion'] ?? ''
                    ];
                }

                $resultados[] = $item;
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
    // 🏠 CONSTRUIR DIRECCIÓN DE SUNAT
    // ========================================
    private function construirDireccionSunat($datos) {
        $partes = [];
        
        if (!empty((string)$datos->desc_tipvia) && (string)$datos->desc_tipvia !== '-') {
            $partes[] = (string)$datos->desc_tipvia;
        }
        
        if (!empty((string)$datos->ddp_nomvia) && (string)$datos->ddp_nomvia !== '-') {
            $partes[] = (string)$datos->ddp_nomvia;
        }
        
        if (!empty((string)$datos->ddp_numer1) && (string)$datos->ddp_numer1 !== '-') {
            $partes[] = 'NRO. ' . (string)$datos->ddp_numer1;
        }
        
        if (!empty((string)$datos->ddp_inter1) && (string)$datos->ddp_inter1 !== '-') {
            $partes[] = 'INT. ' . (string)$datos->ddp_inter1;
        }
        
        if (!empty((string)$datos->ddp_nomzon) && (string)$datos->ddp_nomzon !== '-') {
            $partes[] = (string)$datos->ddp_nomzon;
        }
        
        return implode(' ', $partes);
    }

    // ========================================
    // 💾 REGISTRAR CONSULTA EN LOG
    // ========================================
    private function registrarConsulta($tipo, $documento) {
        try {
            error_log(sprintf(
                "[%s] Consulta SUNARP %s: %s",
                date('Y-m-d H:i:s'),
                $tipo,
                $documento
            ));
        } catch (\Exception $e) {
            error_log("Error al registrar consulta: " . $e->getMessage());
        }
    }
}
?>