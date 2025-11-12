<?php

namespace App\Controllers;

class ConsultasSunarpController {
    
    private $urlSUNARP;
    
    public function __construct() {
        $this->urlSUNARP = $_ENV['PIDE_URL_SUNARP'] ?? "https://ws2.pide.gob.pe/Rest/SUNARP";
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

        if (!isset($input['dni']) || !isset($input['usuario']) || !isset($input['clave'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos: dni, usuario o clave'
            ]);
            return;
        }

        $dni = trim($input['dni']);
        $usuario = trim($input['usuario']);
        $clave = trim($input['clave']);

        if (!preg_match('/^\d{8}$/', $dni)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'DNI inválido. Debe tener 8 dígitos'
            ]);
            return;
        }

        $resultado = $this->consultarPersonaNaturalSUNARP($dni, $usuario, $clave);
        
        http_response_code($resultado['success'] ? 200 : 404);
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

        if (!isset($input['usuario']) || !isset($input['clave'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos: usuario o clave'
            ]);
            return;
        }

        $usuario = trim($input['usuario']);
        $clave = trim($input['clave']);
        $tipoBusqueda = $input['tipoBusqueda'] ?? 'ruc';

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

            $resultado = $this->consultarPersonaJuridicaPorRUC($ruc, $usuario, $clave);
        } else {
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

            $resultado = $this->consultarPersonaJuridicaPorRazonSocial($razonSocial, $usuario, $clave);
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

        if (!isset($input['partida']) || !isset($input['usuario']) || !isset($input['clave'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos: partida, usuario o clave'
            ]);
            return;
        }

        $partida = trim($input['partida']);
        $usuario = trim($input['usuario']);
        $clave = trim($input['clave']);
        $zona = $input['zona'] ?? '';
        $oficina = $input['oficina'] ?? '';

        $resultado = $this->consultarTitularidadBienes($partida, $zona, $oficina, $usuario, $clave);
        
        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado);
    }

    // ========================================
    // 🔍 SERVICIO: BUSCAR PERSONA NATURAL
    // ========================================
    private function consultarPersonaNaturalSUNARP($dni, $usuario, $clave) {
        try {
            // NOTA: SUNARP no tiene un servicio directo para buscar por DNI
            // Normalmente se busca por número de partida o se usa otro servicio
            // Este es un ejemplo adaptado
            
            $url = $this->urlSUNARP . "/TSIRSARP?out=json";

            $data = [
                "PIDE" => [
                    "usuario" => $usuario,
                    "clave" => $clave,
                    "numeroDocumento" => $dni,
                    "tipoDocumento" => "01" // DNI
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
                
                // Adaptar según la respuesta real de SUNARP
                if (isset($jsonResponse['resultado'])) {
                    $resultados = is_array($jsonResponse['resultado']) ? 
                                $jsonResponse['resultado'] : 
                                [$jsonResponse['resultado']];

                    $personas = [];
                    foreach ($resultados as $item) {
                        $personas[] = [
                            'dni' => $item['numeroDocumento'] ?? $dni,
                            'nombres' => $item['nombres'] ?? '',
                            'apellidoPaterno' => $item['apellidoPaterno'] ?? '',
                            'apellidoMaterno' => $item['apellidoMaterno'] ?? '',
                            'partida' => $item['partida'] ?? '',
                            'zona' => $item['zona'] ?? '',
                            'oficina' => $item['oficina'] ?? ''
                        ];
                    }

                    return [
                        'success' => true,
                        'message' => 'Búsqueda exitosa',
                        'data' => $personas
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'No se encontraron resultados',
                        'data' => []
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP $httpCode en el servicio SUNARP",
                    'data' => []
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar persona natural: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    // ========================================
    // 🔍 SERVICIO: BUSCAR PJ POR RUC
    // ========================================
    private function consultarPersonaJuridicaPorRUC($ruc, $usuario, $clave) {
        try {
            // Primero consultar SUNAT para obtener razón social
            $url = $this->urlSUNARP . "/BPJRSocial?out=json";

            // En este caso, necesitamos primero obtener la razón social del RUC
            // Esto requeriría una consulta previa a SUNAT o tener el dato
            // Por ahora, retornamos un mensaje indicando que se debe buscar por razón social
            
            return [
                'success' => false,
                'message' => 'Para buscar por RUC, primero consulte en SUNAT y luego busque por Razón Social',
                'data' => []
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar persona jurídica: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    // ========================================
    // 🔍 SERVICIO: BUSCAR PJ POR RAZÓN SOCIAL
    // ========================================
    private function consultarPersonaJuridicaPorRazonSocial($razonSocial, $usuario, $clave) {
        try {
            $url = $this->urlSUNARP . "/BPJRSocial?out=json";

            $data = [
                "PIDE" => [
                    "usuario" => $usuario,
                    "clave" => $clave,
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
                
                // Parsear respuesta según estructura de SUNARP
                if (isset($jsonResponse['personaJuridica']['resultado'])) {
                    $resultados = is_array($jsonResponse['personaJuridica']['resultado']) ? 
                                $jsonResponse['personaJuridica']['resultado'] : 
                                [$jsonResponse['personaJuridica']['resultado']];

                    $empresas = [];
                    foreach ($resultados as $item) {
                        $empresas[] = [
                            'razonSocial' => $item['denominacion'] ?? $item['denominación'] ?? '',
                            'partida' => $item['partida'] ?? '',
                            'zona' => $item['zona'] ?? '',
                            'oficina' => $item['oficina'] ?? '',
                            'tipo' => $item['tipo'] ?? '',
                            'ficha' => $item['ficha'] ?? '',
                            'tomo' => $item['tomo'] ?? '',
                            'folio' => $item['folio'] ?? ''
                        ];
                    }

                    return [
                        'success' => true,
                        'message' => 'Búsqueda exitosa',
                        'data' => $empresas
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'No se encontraron resultados para la razón social proporcionada',
                        'data' => []
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP $httpCode en el servicio SUNARP",
                    'data' => []
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar persona jurídica: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    // ========================================
    // 🔍 SERVICIO: CONSULTAR TITULARIDAD
    // ========================================
    private function consultarTitularidadBienes($partida, $zona, $oficina, $usuario, $clave) {
        try {
            $url = $this->urlSUNARP . "/TSIRSARP?out=json";

            $data = [
                "PIDE" => [
                    "usuario" => $usuario,
                    "clave" => $clave,
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
                
                // Adaptar según la estructura real de respuesta
                if (isset($jsonResponse['titularidadResponse'])) {
                    $datos = $jsonResponse['titularidadResponse'];

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
                            'foto' => $datos['foto'] ?? null
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
    // 💾 REGISTRAR CONSULTA EN LOG
    // ========================================
    private function registrarConsulta($tipo, $documento, $respuesta) {
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