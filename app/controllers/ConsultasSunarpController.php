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
    // 📌 BUSCAR PERSONA NATURAL POR DNI
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
                'message' => 'Faltan datos como: dni, dniUsuario o password'
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

        // Primero obtener datos de RENIEC
        $datosReniec = $this->obtenerDatosRENIEC($dni, $dniUsuario, $passwordPIDE);
        
        if (!$datosReniec['success']) {
            http_response_code(404);
            echo json_encode($datosReniec);
            return;
        }

        // Retornar datos de RENIEC (SUNARP requiere partida manual)
        $resultado = [
            'success' => true,
            'message' => 'Datos obtenidos de RENIEC. Para consultar en SUNARP necesita el número de partida',
            'data' => [[
                'dni' => $datosReniec['data']['dni'],
                'nombres' => $datosReniec['data']['nombres'],
                'apellidoPaterno' => $datosReniec['data']['apellido_paterno'],
                'apellidoMaterno' => $datosReniec['data']['apellido_materno'],
                'foto' => $datosReniec['data']['foto'],
                'partida' => '', // El usuario debe ingresarlo
                'zona' => '',
                'oficina' => ''
            ]]
        ];
        
        http_response_code(200);
        echo json_encode($resultado);
    }

    // ========================================
    // 📌 BUSCAR PERSONA JURÍDICA
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
        $tipoBusqueda = $input['tipoBusqueda'] ?? 'ruc';

        // Validar credenciales PIDE para SUNARP
        if (!isset($input['dniUsuario']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan credenciales: dniUsuario o password'
            ]);
            return;
        }

        $dniUsuario = trim($input['dniUsuario']);
        $passwordPIDE = trim($input['password']);

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

            // Consultar en SUNAT para obtener datos básicos
            $resultado = $this->obtenerDatosSUNAT($ruc);
            
            // Si tiene razón social, buscar en SUNARP
            if ($resultado['success'] && !empty($resultado['data']['razon_social'])) {
                $razonSocial = $resultado['data']['razon_social'];
                $datosSunarp = $this->buscarEnSunarpPorRazonSocial($razonSocial, $dniUsuario, $passwordPIDE);
                
                // Combinar datos de SUNAT y SUNARP
                if ($datosSunarp['success']) {
                    $resultado['data']['partidas_sunarp'] = $datosSunarp['data'];
                    $resultado['message'] = 'Datos obtenidos de SUNAT y SUNARP';
                }
            }
            
            // Formatear respuesta como array de resultados
            if ($resultado['success']) {
                $resultado['data'] = [[
                    'ruc' => $resultado['data']['ruc'],
                    'razonSocial' => $resultado['data']['razon_social'],
                    'direccion' => $resultado['data']['direccion_completa'] ?? '',
                    'departamento' => $resultado['data']['departamento'] ?? '',
                    'provincia' => $resultado['data']['provincia'] ?? '',
                    'distrito' => $resultado['data']['distrito'] ?? '',
                    'estado' => $resultado['data']['estado_contribuyente'] ?? '',
                    'condicion' => $resultado['data']['condicion_domicilio'] ?? '',
                    'tipo_contribuyente' => $resultado['data']['tipo_contribuyente'] ?? '',
                    'actividad_economica' => $resultado['data']['actividad_economica'] ?? '',
                    'es_activo' => $resultado['data']['es_activo'] ?? false,
                    'es_habido' => $resultado['data']['es_habido'] ?? false,
                    // Datos de SUNARP
                    'partidas_sunarp' => $resultado['data']['partidas_sunarp'] ?? [],
                    // Campos adicionales de SUNAT
                    'datosCompletos' => $resultado['data']
                ]];
            }

        } else if ($tipoBusqueda === 'razonSocial') {
            if (!isset($input['razonSocial'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Razón social no proporcionada'
                ]);
                return;
            }

            $razonSocial = trim($input['razonSocial']);
            if (empty($razonSocial)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Razón social no puede estar vacía'
                ]);
                return;
            }

            // Buscar directamente en SUNARP usando BPJRSocial
            $resultado = $this->buscarEnSunarpPorRazonSocial($razonSocial, $dniUsuario, $passwordPIDE);

        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Tipo de búsqueda inválido. Use "ruc" o "razonSocial"'
            ]);
            return;
        }
        
        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado);
    }

    // ========================================
    // 📌 CONSULTAR PARTIDA REGISTRAL
    // ========================================
    public function consultarPartidaRegistral() {
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

        if (!isset($input['partida']) || !isset($input['dniUsuario']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos: partida, dniUsuario o password'
            ]);
            return;
        }

        $partida = trim($input['partida']);
        $dniUsuario = trim($input['dniUsuario']);
        $passwordPIDE = trim($input['password']);
        $zona = $input['zona'] ?? '';
        $oficina = $input['oficina'] ?? '';

        $resultado = $this->consultarTitularidadBienes($partida, $zona, $oficina, $dniUsuario, $passwordPIDE);
        
        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado);
    }

    // ========================================
    // 🔍 BUSCAR EN SUNARP POR RAZÓN SOCIAL
    // ========================================
    private function buscarEnSunarpPorRazonSocial($razonSocial, $dniUsuario, $passwordPIDE) {
        try {
            $url = $this->urlSUNARP . "/BPJRSocial?out=json";

            $data = [
                "PIDE" => [
                    "usuario" => $dniUsuario,
                    "clave" => $passwordPIDE,
                    "razonSocial" => $razonSocial
                ]
            ];

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

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

            if ($httpCode == 200) {
                $jsonResponse = json_decode($response, true);
                
                if (isset($jsonResponse['BPJRSocialResponse']['return'])) {
                    $datos = $jsonResponse['BPJRSocialResponse']['return'];
                    
                    // Si es un array de resultados
                    if (is_array($datos)) {
                        $resultados = [];
                        
                        // Normalizar: si no es array de arrays, convertirlo
                        $items = isset($datos[0]) ? $datos : [$datos];
                        
                        foreach ($items as $item) {
                            $resultados[] = [
                                'zona' => $item['zona'] ?? '',
                                'oficina' => $item['oficina'] ?? '',
                                'partida' => $item['partida'] ?? '',
                                'ficha' => $item['ficha'] ?? '',
                                'tomo' => $item['tomo'] ?? '',
                                'folio' => $item['folio'] ?? '',
                                'tipo' => $item['tipo'] ?? '',
                                'razonSocial' => $razonSocial
                            ];
                        }

                        return [
                            'success' => true,
                            'message' => 'Se encontraron ' . count($resultados) . ' registro(s) en SUNARP',
                            'data' => $resultados
                        ];
                    }
                }
                
                return [
                    'success' => false,
                    'message' => 'No se encontraron registros en SUNARP para la razón social proporcionada',
                    'data' => []
                ];
            }

            return [
                'success' => false,
                'message' => "Error HTTP $httpCode en el servicio SUNARP",
                'data' => []
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al consultar SUNARP: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    // ========================================
    // 🔍 OBTENER DATOS DE RENIEC
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
    private function obtenerDatosSUNAT($ruc) {
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
                        
                        // Ubicación
                        'codigo_ubigeo' => (string)($datos->ddp_ubigeo ?? ''),
                        'departamento' => (string)($datos->desc_dep ?? ''),
                        'provincia' => (string)($datos->desc_prov ?? ''),
                        'distrito' => (string)($datos->desc_dist ?? ''),
                        
                        // Dirección
                        'direccion_completa' => $this->construirDireccionSunat($datos),
                        'tipo_via' => (string)($datos->desc_tipvia ?? ''),
                        'nombre_via' => (string)($datos->ddp_nomvia ?? ''),
                        'numero' => (string)($datos->ddp_numer1 ?? ''),
                        
                        // Estado
                        'estado_contribuyente' => (string)($datos->desc_estado ?? ''),
                        'condicion_domicilio' => (string)($datos->desc_flag22 ?? ''),
                        
                        // Tipo
                        'tipo_contribuyente' => (string)($datos->desc_tpoemp ?? ''),
                        'tipo_persona' => (string)($datos->desc_identi ?? ''),
                        
                        // Actividad
                        'actividad_economica' => (string)($datos->desc_ciiu ?? ''),
                        'codigo_ciiu' => (string)($datos->ddp_ciiu ?? ''),
                        
                        // Estados
                        'es_activo' => $this->convertirBooleano($datos->esActivo ?? 'false'),
                        'es_habido' => $this->convertirBooleano($datos->esHabido ?? 'false')
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

    // ========================================
    // 🔍 CONSULTAR TITULARIDAD DE BIENES
    // ========================================
    private function consultarTitularidadBienes($partida, $zona, $oficina, $dniUsuario, $passwordPIDE) {
        try {
            $url = $this->urlSUNARP . "/TSIRSARP?out=json";

            $data = [
                "PIDE" => [
                    "usuario" => $dniUsuario,
                    "clave" => $passwordPIDE,
                    "partida" => $partida,
                    "zona" => $zona,
                    "oficina" => $oficina
                ]
            ];

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

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
                    'data' => null
                ];
            }

            curl_close($ch);

            if ($httpCode == 200) {
                $jsonResponse = json_decode($response, true);
                
                if (isset($jsonResponse['titularidadResponse']['return'])) {
                    $datos = $jsonResponse['titularidadResponse']['return'];

                    return [
                        'success' => true,
                        'message' => 'Consulta exitosa',
                        'data' => [
                            'registro' => $datos['registro'] ?? '',
                            'libro' => $datos['libro'] ?? '',
                            'apellidoPaterno' => $datos['apellidoPaterno'] ?? '',
                            'apellidoMaterno' => $datos['apellidoMaterno'] ?? '',
                            'nombres' => $datos['nombres'] ?? '',
                            'tipoDocumento' => $datos['tipoDocumento'] ?? '',
                            'nroDocumento' => $datos['nroDocumento'] ?? '',
                            'nroPartida' => $partida,
                            'nroPlaca' => $datos['nroPlaca'] ?? '',
                            'estado' => $datos['estado'] ?? '',
                            'zona' => $zona,
                            'oficina' => $oficina,
                            'direccion' => $datos['direccion'] ?? '',
                            'foto' => null
                        ]
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'No se encontró información para la partida proporcionada',
                        'data' => null
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP $httpCode en el servicio SUNARP",
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al consultar partida registral: ' . $e->getMessage(),
                'data' => null
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
    // 🔄 CONVERTIR STRING A BOOLEANO
    // ========================================
    private function convertirBooleano($valor) {
        $valorStr = strtolower((string)$valor);
        return in_array($valorStr, ['true', '1', 'yes', 'si', 'sí']);
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