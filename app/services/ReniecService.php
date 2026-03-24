<?php

namespace App\Services;

use App\Config\EnvLoader;
use App\Services\Contracts\PideHttpClientInterface;
use App\Services\Contracts\ReniecServiceInterface;

/**
 * Servicio de consultas RENIEC.
 * Contiene toda la lógica de negocio extraída de ConsultasReniecController (SRP).
 */
class ReniecService implements ReniecServiceInterface
{
    /** @var PideHttpClientInterface */
    private $httpClient;

    /** @var string */
    private $rucUsuario;

    /** @var string */
    private $urlRENIEC;

    public function __construct(PideHttpClientInterface $httpClient, EnvLoader $envLoader)
    {
        $this->httpClient = $httpClient;
        $this->rucUsuario = $envLoader->get('PIDE_RUC_EMPRESA');
        $this->urlRENIEC = $envLoader->get('PIDE_URL_RENIEC');
    }

    /**
     * {@inheritdoc}
     */
    public function consultarDNI(string $dni, string $dniUsuario, string $passwordPIDE): array
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

            $curlResult = $this->httpClient->execute($this->urlRENIEC, $data, 'POST', 'RENIEC');

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
     * {@inheritdoc}
     */
    public function obtenerDatosRENIEC(string $dni, string $dniUsuario, string $passwordPIDE): array
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

            $curlResult = $this->httpClient->execute($this->urlRENIEC, $data, 'POST', 'RENIEC');

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

    /**
     * {@inheritdoc}
     */
    public function actualizarPasswordRENIEC(string $credencialAnterior, string $credencialNueva, string $nuDni): array
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

            $curlResult = $this->httpClient->execute($urlActualizar, $data, 'POST', 'RENIEC');

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
    // HELPERS PRIVADOS
    // ========================================

    private function serviceErrorResult(string $servicio, int $httpCode): array
    {
        return [
            'success' => false,
            'message' => "Error HTTP $httpCode en el servicio $servicio",
            'data' => null
        ];
    }

    private function exceptionResult(string $accion, \Exception $exception): array
    {
        error_log("Exception en $accion: " . $exception->getMessage());
        return [
            'success' => false,
            'message' => "Error al $accion: " . $exception->getMessage(),
            'data' => null
        ];
    }
}
