<?php

namespace App\Controllers;

class ConsultasSunatController extends ConsultasPideBaseController
{

    private $urlSUNATRest;

    public function __construct()
    {
        parent::__construct();
        $this->urlSUNATRest = $_ENV['PIDE_URL_SUNAT'];
    }

    // ========================================
    // CONSULTAR RUC (SUNAT) - REST/JSON
    // ========================================
    public function consultarRUC()
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['ruc'], 'RUC no proporcionado');
        if ($input === null) return;

        $ruc = trim($input['ruc']);

        if (!$this->validateRuc($ruc)) return;

        $resultado = $this->consultarServicioSUNATRest($ruc);

        $this->sendJsonResult($resultado);
    }

    // ========================================
    // BUSCAR POR RAZÓN SOCIAL (SUNAT)
    // ========================================
    public function buscarRazonSocial()
    {
        if (!$this->validatePostRequest()) return;

        $input = $this->getPostInput(['razonSocial'], 'Razón social no proporcionada');
        if ($input === null) return;

        $razonSocial = trim($input['razonSocial']);

        if (empty($razonSocial)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Razón social no puede estar vacía'
            ]);
            return;
        }

        $resultado = $this->buscarPorRazonSocialSUNATRest($razonSocial);

        $this->sendJsonResult($resultado);
    }

    // ========================================
    // SERVICIO SUNAT REST - CONSULTA POR RUC (protected para Sunarp)
    // ========================================
    public function consultarServicioSUNATRest($ruc)
    {
        try {
            $url = $this->urlSUNATRest . '/DatosPrincipales?numruc=' . urlencode($ruc) . '&out=json';

            $curlResult = $this->executeCurl($url, null, 'GET', 'SUNAT');

            error_log("SUNAT REST Response HTTP Code: " . $curlResult['httpCode']);

            if (!$curlResult['success']) {
                return [
                    'success' => false,
                    'message' => $curlResult['error'],
                    'data' => null
                ];
            }

            if ($curlResult['httpCode'] == 200) {
                return $this->procesarRespuestaJSON($curlResult['response'], $ruc);
            } elseif ($curlResult['httpCode'] == 404) {
                return [
                    'success' => false,
                    'message' => 'No se encontró información para el RUC consultado',
                    'data' => null
                ];
            } elseif ($curlResult['httpCode'] == 500) {
                return [
                    'success' => false,
                    'message' => 'Error interno del servidor SUNAT',
                    'data' => null
                ];
            } else {
                return $this->serviceErrorResult('SUNAT', $curlResult['httpCode']);
            }
        } catch (\Exception $e) {
            return $this->exceptionResult('consultar RUC', $e);
        }
    }

    // ========================================
    // SERVICIO SUNAT REST - BÚSQUEDA POR RAZÓN SOCIAL (protected para Sunarp)
    // ========================================
    public function buscarPorRazonSocialSUNATRest($razonSocial)
    {
        try {
            $razonSocialParam = rawurlencode($razonSocial);
            $url = $this->urlSUNATRest . '/RazonSocial?RSocial=' . $razonSocialParam . '&out=json';

            error_log("URL búsqueda razón social: $url");

            $curlResult = $this->executeCurl($url, null, 'GET', 'SUNAT');

            error_log("SUNAT REST Búsqueda Response HTTP Code: " . $curlResult['httpCode']);

            if (!$curlResult['success']) {
                return [
                    'success' => false,
                    'message' => $curlResult['error'],
                    'data' => []
                ];
            }

            if ($curlResult['httpCode'] == 200) {
                return $this->procesarRespuestaBusquedaJSON($curlResult['response']);
            } elseif ($curlResult['httpCode'] == 404) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron resultados para la razón social consultada',
                    'data' => []
                ];
            } elseif ($curlResult['httpCode'] == 500) {
                return [
                    'success' => false,
                    'message' => 'Error interno del servidor SUNAT',
                    'data' => []
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP {$curlResult['httpCode']} al buscar en SUNAT",
                    'data' => []
                ];
            }
        } catch (\Exception $e) {
            error_log("Exception en buscarPorRazonSocialSUNATRest: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al buscar razón social: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    // ========================================
    // PROCESAR RESPUESTA JSON (CONSULTA POR RUC)
    // ========================================
    protected function procesarRespuestaJSON($jsonResponse, $ruc)
    {
        try {
            $respuesta = json_decode($jsonResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'message' => 'Error al decodificar respuesta JSON de SUNAT: ' . json_last_error_msg(),
                    'data' => null
                ];
            }

            if (!isset($respuesta['list']['multiRef'])) {
                error_log("Respuesta SUNAT inesperada: " . print_r($respuesta, true));
                return [
                    'success' => false,
                    'message' => 'Formato de respuesta inválido de SUNAT',
                    'data' => null
                ];
            }

            $datos = $respuesta['list']['multiRef'];

            $rucObtenido = $this->extraerValorSunat('ddp_numruc', $datos);
            if (empty($rucObtenido)) {
                return [
                    'success' => false,
                    'message' => 'No se encontró información para el RUC consultado',
                    'data' => null
                ];
            }

            $resultado = [
                'success' => true,
                'message' => 'Consulta exitosa',
                'data' => $this->mapearDatosSunat($datos, $rucObtenido)
            ];

            $resultado['data']['direccion_completa'] = $this->construirDireccionDesdeArray($resultado['data']);

            $this->registrarConsulta('RUC', $ruc, $resultado['data']);

            return $resultado;
        } catch (\Exception $e) {
            error_log("Exception en procesarRespuestaJSON: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Error al procesar respuesta: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    // ========================================
    // PROCESAR RESPUESTA JSON (BÚSQUEDA POR RAZÓN SOCIAL)
    // ========================================
    protected function procesarRespuestaBusquedaJSON($jsonResponse)
    {
        try {
            error_log("Procesando respuesta de búsqueda por razón social");

            $respuesta = json_decode($jsonResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error JSON decode: " . json_last_error_msg());
                return [
                    'success' => false,
                    'message' => 'Error al decodificar respuesta JSON de SUNAT: ' . json_last_error_msg(),
                    'data' => []
                ];
            }

            if (!isset($respuesta['list']['multiRef'])) {
                error_log("No se encontró multiRef en la respuesta");
                return [
                    'success' => false,
                    'message' => 'No se encontraron resultados para la razón social consultada',
                    'data' => []
                ];
            }

            $multiRef = $respuesta['list']['multiRef'];

            if (!is_array($multiRef)) {
                return [
                    'success' => false,
                    'message' => 'Formato de respuesta inválido',
                    'data' => []
                ];
            }

            // Normalizar a array múltiple
            if (!isset($multiRef[0])) {
                $multiRef = [$multiRef];
            }

            error_log("Total de resultados encontrados: " . count($multiRef));

            $resultados = [];

            foreach ($multiRef as $index => $datos) {
                $ruc = $this->extraerValorSunat('ddp_numruc', $datos);
                $nombre = $this->extraerValorSunat('ddp_nombre', $datos);

                error_log("Procesando resultado $index: RUC=$ruc, Nombre=$nombre");

                if (!empty($ruc)) {
                    $resultado = $this->mapearDatosSunat($datos, $ruc);
                    $resultado['secuencia'] = (int)$this->extraerValorSunat('ddp_secuen', $datos);
                    $resultado['direccion_completa'] = $this->construirDireccionDesdeArray($resultado);

                    $resultados[] = $resultado;
                    error_log("Resultado procesado exitosamente");
                } else {
                    error_log("Registro sin RUC, se omite");
                }
            }

            if (empty($resultados)) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron resultados válidos para la razón social consultada',
                    'data' => []
                ];
            }

            error_log("Total de resultados procesados: " . count($resultados));

            return [
                'success' => true,
                'message' => 'Búsqueda exitosa',
                'data' => $resultados,
                'total' => count($resultados)
            ];
        } catch (\Exception $e) {
            error_log("Exception en procesarRespuestaBusquedaJSON: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar respuesta: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    // ========================================
    // HELPERS SUNAT
    // ========================================

    /**
     * Extrae un valor de la estructura de datos SUNAT, manejando @nil y $.
     */
    protected function extraerValorSunat($campo, $datos)
    {
        if (!isset($datos[$campo])) {
            return '';
        }

        $valor = $datos[$campo];

        if (is_array($valor) && isset($valor['@nil']) && $valor['@nil'] === true) {
            return '';
        }

        if (is_array($valor) && isset($valor['$'])) {
            return trim($valor['$']);
        }

        if (is_string($valor)) {
            return trim($valor);
        }

        return '';
    }

    /**
     * Mapea datos crudos de SUNAT a estructura normalizada.
     */
    protected function mapearDatosSunat($datos, $ruc)
    {
        return [
            // Datos principales
            'ruc' => $ruc,
            'razon_social' => $this->extraerValorSunat('ddp_nombre', $datos),

            // Ubicación
            'codigo_ubigeo' => $this->extraerValorSunat('ddp_ubigeo', $datos),
            'departamento' => $this->extraerValorSunat('desc_dep', $datos),
            'provincia' => $this->extraerValorSunat('desc_prov', $datos),
            'distrito' => $this->extraerValorSunat('desc_dist', $datos),
            'cod_dep' => $this->extraerValorSunat('cod_dep', $datos),
            'cod_prov' => $this->extraerValorSunat('cod_prov', $datos),
            'cod_dist' => $this->extraerValorSunat('cod_dist', $datos),

            // Dirección completa
            'tipo_via' => $this->extraerValorSunat('desc_tipvia', $datos),
            'codigo_tipo_via' => $this->extraerValorSunat('ddp_tipvia', $datos),
            'nombre_via' => $this->extraerValorSunat('ddp_nomvia', $datos),
            'numero' => $this->extraerValorSunat('ddp_numer1', $datos),
            'interior' => $this->extraerValorSunat('ddp_inter1', $datos),
            'tipo_zona' => $this->extraerValorSunat('desc_tipzon', $datos),
            'codigo_tipo_zona' => $this->extraerValorSunat('ddp_tipzon', $datos),
            'nombre_zona' => $this->extraerValorSunat('ddp_nomzon', $datos),
            'referencia' => $this->extraerValorSunat('ddp_refer1', $datos),

            // Estado y condición
            'estado_contribuyente' => $this->extraerValorSunat('desc_estado', $datos),
            'codigo_estado' => $this->extraerValorSunat('ddp_estado', $datos),
            'condicion_domicilio' => $this->extraerValorSunat('desc_flag22', $datos),
            'codigo_condicion' => $this->extraerValorSunat('ddp_flag22', $datos),

            // Tipo de contribuyente
            'tipo_contribuyente' => $this->extraerValorSunat('desc_tpoemp', $datos),
            'codigo_tipo_contribuyente' => $this->extraerValorSunat('ddp_tpoemp', $datos),
            'tipo_persona' => $this->extraerValorSunat('desc_identi', $datos),
            'codigo_tipo_persona' => $this->extraerValorSunat('ddp_identi', $datos),

            // Actividad económica
            'actividad_economica' => $this->extraerValorSunat('desc_ciiu', $datos),
            'codigo_ciiu' => $this->extraerValorSunat('ddp_ciiu', $datos),

            // Dependencia
            'dependencia' => $this->extraerValorSunat('desc_numreg', $datos),
            'codigo_dependencia' => $this->extraerValorSunat('ddp_numreg', $datos),

            // Fechas
            'fecha_actualizacion' => $this->extraerValorSunat('ddp_fecact', $datos),
            'fecha_alta' => $this->extraerValorSunat('ddp_fecalt', $datos),
            'fecha_baja' => $this->extraerValorSunat('ddp_fecbaj', $datos),

            // Otros datos
            'codigo_secuencia' => $this->extraerValorSunat('ddp_secuen', $datos),
            'libreta_tributaria' => $this->extraerValorSunat('ddp_lllttt', $datos),
            'tamaño' => $this->extraerValorSunat('desc_tamano', $datos),

            // Estados booleanos
            'es_activo' => $this->convertirBooleano($this->extraerValorSunat('esActivo', $datos)),
            'es_habido' => $this->convertirBooleano($this->extraerValorSunat('esHabido', $datos)),
            'estado_activo' => $this->convertirBooleano($this->extraerValorSunat('esActivo', $datos)) ? 'SÍ' : 'NO',
            'estado_habido' => $this->convertirBooleano($this->extraerValorSunat('esHabido', $datos)) ? 'SÍ' : 'NO'
        ];
    }

    // ========================================
    // CONSTRUIR DIRECCIÓN COMPLETA DESDE ARRAY
    // ========================================
    protected function construirDireccionDesdeArray($data)
    {
        $partes = [];

        if (!empty($data['tipo_via']) && $data['tipo_via'] !== '-') {
            $partes[] = $data['tipo_via'];
        }

        if (!empty($data['nombre_via']) && $data['nombre_via'] !== '-') {
            $partes[] = $data['nombre_via'];
        }

        if (!empty($data['numero']) && $data['numero'] !== '-') {
            $partes[] = 'NRO. ' . $data['numero'];
        }

        if (!empty($data['interior']) && $data['interior'] !== '-') {
            $partes[] = 'INT. ' . $data['interior'];
        }

        if (!empty($data['nombre_zona']) && $data['nombre_zona'] !== '-') {
            $partes[] = $data['nombre_zona'];
        }

        if (!empty($data['referencia']) && $data['referencia'] !== '-') {
            $partes[] = '(' . $data['referencia'] . ')';
        }

        return implode(' ', $partes);
    }

    // ========================================
    // CONVERTIR A BOOLEANO
    // ========================================
    protected function convertirBooleano($valor)
    {
        if (empty($valor)) {
            return false;
        }

        if (is_bool($valor)) {
            return $valor;
        }

        if (is_numeric($valor)) {
            return (int)$valor === 1;
        }

        $valorStr = strtolower((string)$valor);
        return in_array($valorStr, ['true', '1', 'yes', 'si', 'sí']);
    }

    // ========================================
    // REGISTRAR CONSULTA EN LOG
    // ========================================
    protected function registrarConsulta($tipo, $documento, $respuesta)
    {
        try {
            error_log(sprintf(
                "[%s] Consulta %s: %s - %s",
                date('Y-m-d H:i:s'),
                $tipo,
                $documento,
                $respuesta['razon_social'] ?? 'N/A'
            ));
        } catch (\Exception $e) {
            error_log("Error al registrar consulta: " . $e->getMessage());
        }
    }
}
