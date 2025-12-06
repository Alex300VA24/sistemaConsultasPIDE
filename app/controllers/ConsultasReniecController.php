<?php

namespace App\Controllers;

class ConsultasReniecController {
    
    // CONFIGURACIÓN PIDE/RENIEC

    private $dniUsuario;
    private $rucUsuario;
    private $passwordPIDE;
    private $urlRENIEC;
    private $urlSUNAT;
    
    public function __construct() {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }
        // Cargar desde configuración o variables de entorno
        $this->rucUsuario = $_ENV['PIDE_RUC_EMPRESA'];
        $this->urlRENIEC = $_ENV['PIDE_URL_RENIEC'];
    }

    // CONSULTAR DNI (RENIEC)
    public function consultarDNI()
    {
        header('Content-Type: application/json');

        // Solo se permite método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        // Obtener datos del cuerpo del request
        $input = json_decode(file_get_contents('php://input'), true);

        // Validar campos requeridos
        if (!isset($input['dniConsulta']) || !isset($input['dniUsuario']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos: dni, dniUsuario o password'
            ]);
            return;
        }

        $dni = trim($input['dniConsulta']);
        $this->dniUsuario = trim($input['dniUsuario']);
        $this->passwordPIDE = trim($input['password']);

        // Validar formato del DNI a consultar
        if (!preg_match('/^\d{8}$/', $dni)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'DNI inválido. Debe tener 8 dígitos'
            ]);
            return;
        }

        // Realizar la consulta con las credenciales del usuario
        $resultado = $this->consultarServicioRENIEC($dni, $this->dniUsuario, $this->passwordPIDE);

        // Enviar respuesta según resultado
        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado);
    }

    // ACTUALIZAR CONTRASEÑA RENIEC
    public function actualizarPasswordRENIEC()
    {
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

        // Validar campos requeridos
        if (!isset($input['credencialAnterior']) || 
            !isset($input['credencialNueva']) || 
            !isset($input['nuDni'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos: credencialAnterior, credencialNueva o nuDni'
            ]);
            return;
        }

        $credencialAnterior = trim($input['credencialAnterior']);
        $credencialNueva = trim($input['credencialNueva']);
        $nuDni = trim($input['nuDni']);

        // Validar formato del DNI
        if (!preg_match('/^\d{8}$/', $nuDni)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'DNI inválido. Debe tener 8 dígitos'
            ]);
            return;
        }

        // Realizar actualización
        $resultado = $this->actualizarServicioRENIEC($credencialAnterior, $credencialNueva, $nuDni);

        http_response_code($resultado['success'] ? 200 : 400);
        echo json_encode($resultado);
    }

    // SERVICIO ACTUALIZAR CONTRASEÑA RENIEC
    private function actualizarServicioRENIEC($credencialAnterior, $credencialNueva, $nuDni)
    {
        try {
            $urlActualizar = "https://ws2.pide.gob.pe/Rest/RENIEC/Actualizar?out=json";

            $data = [
                "PIDE" => [
                    "credencialAnterior" => $credencialAnterior,
                    "credencialNueva"    => $credencialNueva,
                    "nuDni"              => $nuDni,
                    "nuRuc"              => $this->rucUsuario
                ]
            ];

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            $ch = curl_init($urlActualizar);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => "POST",
                CURLOPT_HTTPHEADER     => [
                    "Content-Type: application/json; charset=UTF-8",
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

                // Validar estructura esperada
                if (isset($jsonResponse['actualizarcredencialResponse']['return'])) {
                    $result = $jsonResponse['actualizarcredencialResponse']['return'];
                    
                    $codigoResultado = $result['coResultado'] ?? '';
                    $descripcionResultado = $result['deResultado'] ?? '';

                    if ($codigoResultado === '0000') {
                        return [
                            'success' => true,
                            'message' => $descripcionResultado
                        ];
                    } else {
                        return [
                            'success' => false,
                            'message' => $descripcionResultado ?: 'Error al actualizar contraseña'
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'message' => 'Respuesta inesperada del servicio RENIEC'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP $httpCode en el servicio RENIEC"
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar contraseña: ' . $e->getMessage()
            ];
        }
    }


    // ========================================
    // SERVICIO RENIEC (CURL)
    // ========================================
    private function consultarServicioRENIEC($dni, $dniUsuario, $passwordPIDE)
    {
        try {
            // Construir la estructura del request JSON según la API de RENIEC
            $data = [
                "PIDE" => [
                    "nuDniConsulta" => $dni,
                    "nuDniUsuario"  => $dniUsuario,
                    "nuRucUsuario"  => $this->rucUsuario,   // este sí puede mantenerse fijo en la clase
                    "password"      => $passwordPIDE
                ]
            ];

            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

            // Inicializar cURL
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

                // Validar estructura esperada
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

                    // (Opcional) Registrar en base de datos
                    // $this->registrarConsulta('DNI', $dni, $resultado['data']);

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

}
?>