<?php

namespace App\Controllers;

class ConsultasReniecController extends ConsultasPideBaseController
{

    private $rucUsuario;
    private $urlRENIEC;
    private $dniUsuario;
    private $passwordPIDE;

    public function __construct()
    {
        parent::__construct();
        $this->rucUsuario = $_ENV['PIDE_RUC_EMPRESA'];
        $this->urlRENIEC = $_ENV['PIDE_URL_RENIEC'];
    }

    // ========================================
    // CONSULTAR DNI (RENIEC)
    // ========================================
    public function consultarDNI()
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['dniConsulta', 'dniUsuario', 'password'], 'Faltan datos: dni, dniUsuario o password');
        if ($input === null) return;

        $dni = trim($input['dniConsulta']);
        $this->dniUsuario = trim($input['dniUsuario']);
        $this->passwordPIDE = trim($input['password']);

        if (!$this->validateDni($dni)) return;

        $resultado = $this->consultarServicioRENIEC($dni, $this->dniUsuario, $this->passwordPIDE);

        $this->sendJsonResult($resultado);
    }

    // ========================================
    // ACTUALIZAR CONTRASEÑA RENIEC
    // ========================================
    public function actualizarPasswordRENIEC()
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['credencialAnterior', 'credencialNueva', 'nuDni'], 'Faltan datos: credencialAnterior, credencialNueva o nuDni');
        if ($input === null) return;

        $credencialAnterior = $input['credencialAnterior'];
        $credencialNueva = trim($input['credencialNueva']);
        $nuDni = trim($input['nuDni']);

        if (!$this->validateDni($nuDni)) return;

        try {
            $resultado = $this->actualizarServicioRENIEC($credencialAnterior, $credencialNueva, $nuDni);

            http_response_code($resultado['success'] ? 200 : 400);
            echo json_encode($resultado);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al comunicarse con RENIEC: ' . $e->getMessage()
            ]);
        }
    }

    // ========================================
    // SERVICIO ACTUALIZAR CONTRASEÑA RENIEC
    // ========================================
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

            $curlResult = $this->executeCurl($urlActualizar, $data, 'POST', 'RENIEC');

            if (!$curlResult['success']) {
                return [
                    'success' => false,
                    'message' => $curlResult['error']
                ];
            }

            if ($curlResult['httpCode'] == 200) {
                $jsonResponse = json_decode($curlResult['response'], true);

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
                return $this->serviceErrorResult('RENIEC', $curlResult['httpCode']);
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar contraseña: ' . $e->getMessage()
            ];
        }
    }

    // ========================================
    // SERVICIO RENIEC (CURL) — protected para que Sunarp pueda usarlo
    // ========================================
    protected function consultarServicioRENIEC($dni, $dniUsuario, $passwordPIDE)
    {
        try {
            $data = [
                "PIDE" => [
                    "nuDniConsulta" => $dni,
                    "nuDniUsuario"  => $dniUsuario,
                    "nuRucUsuario"  => $this->rucUsuario,
                    "password"      => $passwordPIDE
                ]
            ];

            $curlResult = $this->executeCurl($this->urlRENIEC, $data, 'POST', 'RENIEC');

            if (!$curlResult['success']) {
                return [
                    'success' => false,
                    'message' => $curlResult['error'],
                    'data' => null
                ];
            }

            if ($curlResult['httpCode'] == 200) {
                $jsonResponse = json_decode($curlResult['response'], true);
                $estructura = $jsonResponse['consultarResponse']['return'];

                if (isset($estructura['datosPersona'])) {
                    $datosPersona = $estructura['datosPersona'];

                    return [
                        'success' => true,
                        'message' => $estructura['deResultado'],
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
                } else {
                    return [
                        'success' => false,
                        'message' => $estructura["deResultado"],
                        'data' => null
                    ];
                }
            } else {
                return $this->serviceErrorResult('RENIEC', $curlResult['httpCode']);
            }
        } catch (\Exception $e) {
            return $this->exceptionResult('consultar DNI', $e);
        }
    }

    /**
     * Método auxiliar usado por ConsultasSunarpController para obtener datos RENIEC
     * con formato específico para búsqueda de persona natural.
     */
    public function obtenerDatosRENIEC($dni, $dniUsuario, $passwordPIDE)
    {
        try {
            $data = [
                "PIDE" => [
                    "nuDniConsulta" => $dni,
                    "nuDniUsuario"  => $dniUsuario,
                    "nuRucUsuario"  => $this->rucUsuario,
                    "password"      => $passwordPIDE
                ]
            ];

            $curlResult = $this->executeCurl($this->urlRENIEC, $data, 'POST', 'RENIEC');

            if (!$curlResult['success']) {
                return [
                    'success' => false,
                    'message' => $curlResult['error']
                ];
            }

            error_log("RENIEC Response Code: " . $curlResult['httpCode']);
            error_log("RENIEC Response: " . substr($curlResult['response'], 0, 500));

            if ($curlResult['httpCode'] == 200) {
                $jsonResponse = json_decode($curlResult['response'], true);
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
                'message' => $estructura['deResultado'] ?? 'Error al consultar RENIEC'
            ];
        } catch (\Exception $e) {
            return $this->exceptionResult('consultar RENIEC', $e);
        }
    }
}
