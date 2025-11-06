<?php

namespace App\Controllers;

class ConsultasSunatController {
    

    private $dniUsuario;
    private $rucUsuario;
    private $passwordPIDE;
    private $urlSUNAT;
    
    public function __construct() {
        // Cargar desde configuración o variables de entorno
        $this->dniUsuario = $_ENV['PIDE_DNI_USUARIO'] ?? "42761038"; // variable1
        $this->rucUsuario = $_ENV['PIDE_RUC_USUARIO'] ?? "20164091547";
        $this->passwordPIDE = $_ENV['PIDE_PASSWORD'] ?? "Muni2025@"; //variable
        $this->urlSUNAT = $_ENV['PIDE_URL_SUNAT'] ?? "https://ws2.pide.gob.pe/Rest/RENIEC/Consultar?out=json";
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