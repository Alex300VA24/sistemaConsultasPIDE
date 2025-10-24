<?php

namespace App\Controllers;

class ConsultasController {
    
    // ========================================
    // 🔐 CONFIGURACIÓN PIDE/RENIEC
    // ========================================
    private $dniUsuario;
    private $rucUsuario;
    private $passwordPIDE;
    private $urlRENIEC;
    private $urlSUNAT;
    
    public function __construct() {
        // Cargar desde configuración o variables de entorno
        $this->dniUsuario = $_ENV['PIDE_DNI_USUARIO'] ?? "TU_DNI_USUARIO";
        $this->rucUsuario = $_ENV['PIDE_RUC_USUARIO'] ?? "TU_RUC";
        $this->passwordPIDE = $_ENV['PIDE_PASSWORD'] ?? "TU_PASSWORD";
        $this->urlRENIEC = $_ENV['PIDE_URL_RENIEC'] ?? "https://URL_SERVICIO_RENIEC";
        $this->urlSUNAT = $_ENV['PIDE_URL_SUNAT'] ?? "https://URL_SERVICIO_SUNAT";
    }

    // ========================================
    // 📌 CONSULTAR DNI (RENIEC)
    // ========================================
    public function consultarDNI() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        // Obtener datos del request
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['dni'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'DNI no proporcionado'
            ]);
            return;
        }

        $dni = trim($input['dni']);

        // Validar formato
        if (!preg_match('/^\d{8}$/', $dni)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'DNI inválido. Debe tener 8 dígitos'
            ]);
            return;
        }

        // Realizar consulta
        $resultado = $this->consultarServicioRENIEC($dni);
        
        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado);
    }

    // ========================================
    // 🔍 SERVICIO RENIEC (CURL)
    // ========================================
    private function consultarServicioRENIEC($dni) {
        try {
            // Construir petición JSON
            $data = [
                "PIDE" => [
                    "nuDniConsulta" => $dni,
                    "nuDniUsuario"  => $this->dniUsuario,
                    "nuRucUsuario"  => $this->rucUsuario,
                    "password"      => $this->passwordPIDE
                ]
            ];

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            // Inicializar CURL
            $ch = curl_init($this->urlRENIEC);

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

            // Ejecutar petición
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                
                return [
                    'success' => false,
                    'message' => "Error de conexión con RENIEC: $error",
                    'data' => null
                ];
            }

            curl_close($ch);

            // Procesar respuesta
            if ($httpCode == 200) {
                $jsonResponse = json_decode($response, true);

                if (isset($jsonResponse['consultarResponse']['return']['datosPersona'])) {
                    $datosPersona = $jsonResponse['consultarResponse']['return']['datosPersona'];

                    $resultado = [
                        'success' => true,
                        'message' => 'Consulta exitosa',
                        'data' => [
                            'dni' => $dni,
                            'nombres' => $datosPersona['prenombres'] ?? '',
                            'apellido_paterno' => $datosPersona['apPrimer'] ?? '',
                            'apellido_materno' => $datosPersona['apSegundo'] ?? '',
                            'estado_civil' => $datosPersona['estadoCivil'] ?? '',
                            'direccion' => $datosPersona['direccion'] ?? '',
                            'restriccion' => $datosPersona['restriccion'] ?? '',
                            'ubigeo' => $datosPersona['ubigeo'] ?? '',
                            'foto' => $datosPersona['foto'] ?? null
                        ]
                    ];

                    // Registrar en BD (opcional)
                    $this->registrarConsulta('DNI', $dni, $resultado['data']);

                    return $resultado;
                } else {
                    return [
                        'success' => false,
                        'message' => 'No se encontraron datos para el DNI proporcionado',
                        'data' => null
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP $httpCode en el servicio RENIEC",
                    'data' => null
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al consultar DNI: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    // ========================================
    // 📌 CONSULTAR RUC (SUNAT)
    // ========================================
    public function consultarRUC() {
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

        if (!isset($input['ruc'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'RUC no proporcionado'
            ]);
            return;
        }

        $ruc = trim($input['ruc']);

        // Validar formato
        if (!preg_match('/^\d{11}$/', $ruc)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'RUC inválido. Debe tener 11 dígitos'
            ]);
            return;
        }

        // Realizar consulta
        $resultado = $this->consultarServicioSUNAT($ruc);
        
        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado);
    }

    // ========================================
    // 🔍 SERVICIO SUNAT (CURL)
    // ========================================
    private function consultarServicioSUNAT($ruc) {
        try {
            // Construir petición (adaptar según tu servicio SUNAT)
            $data = [
                "PIDE" => [
                    "nuRucConsulta" => $ruc,
                    "nuDniUsuario"  => $this->dniUsuario,
                    "nuRucUsuario"  => $this->rucUsuario,
                    "password"      => $this->passwordPIDE
                ]
            ];

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            $ch = curl_init($this->urlSUNAT);

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
                    'message' => "Error de conexión con SUNAT: $error",
                    'data' => null
                ];
            }

            curl_close($ch);

            if ($httpCode == 200) {
                $jsonResponse = json_decode($response, true);

                // Adaptar según estructura de respuesta de SUNAT
                if (isset($jsonResponse['consultarResponse']['return']['datosContribuyente'])) {
                    $datos = $jsonResponse['consultarResponse']['return']['datosContribuyente'];

                    $resultado = [
                        'success' => true,
                        'message' => 'Consulta exitosa',
                        'data' => [
                            'ruc' => $ruc,
                            'razon_social' => $datos['razonSocial'] ?? '',
                            'estado_contribuyente' => $datos['estadoContribuyente'] ?? '',
                            'condicion_domicilio' => $datos['condicionDomicilio'] ?? '',
                            'tipo_contribuyente' => $datos['tipoContribuyente'] ?? '',
                            'tipo_persona' => $datos['tipoPersona'] ?? '',
                            'actividad_economica' => $datos['actividadEconomica'] ?? '',
                            'direccion' => $datos['direccion'] ?? '',
                            'departamento' => $datos['departamento'] ?? '',
                            'provincia' => $datos['provincia'] ?? '',
                            'distrito' => $datos['distrito'] ?? '',
                            'codigo_ubigeo' => $datos['ubigeo'] ?? '',
                            'fecha_actualizacion' => $datos['fechaActualizacion'] ?? '',
                            'fecha_alta' => $datos['fechaAlta'] ?? '',
                            'fecha_baja' => $datos['fechaBaja'] ?? ''
                        ]
                    ];

                    $this->registrarConsulta('RUC', $ruc, $resultado['data']);

                    return $resultado;
                }
            }

            return [
                'success' => false,
                'message' => 'No se encontró el RUC',
                'data' => null
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al consultar RUC: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    // ========================================
    // 📌 OTRAS CONSULTAS (PLACEHOLDER)
    // ========================================
    
    public function consultarPartidas() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Servicio no implementado aún'
        ]);
    }

    public function consultarCobranza() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Servicio no implementado aún'
        ]);
    }

    public function consultarPapeletas() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Servicio no implementado aún'
        ]);
    }

    public function consultarCertificaciones() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Servicio no implementado aún'
        ]);
    }

    // ========================================
    // 💾 REGISTRAR CONSULTA EN BD
    // ========================================
    private function registrarConsulta($tipo, $documento, $respuesta) {
        try {
            // Si tienes un modelo o conexión a BD, úsala aquí
            // Ejemplo con PDO:
            // $db = Database::getInstance()->getConnection();
            // $stmt = $db->prepare("INSERT INTO consultas_log ...");
            // $stmt->execute([...]);

            error_log("Consulta registrada: $tipo - $documento");
            
        } catch (\Exception $e) {
            error_log("Error al registrar consulta: " . $e->getMessage());
        }
    }
}
?>