<?php

namespace App\Controllers;

class ConsultasSunatController {
    
    private $urlSUNATRest;
    
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
        // URL base del REST API de SUNAT (PIDE)
        $this->urlSUNATRest = $_ENV['PIDE_URL_SUNAT'];
    }

    // ========================================
    // 📌 CONSULTAR RUC (SUNAT) - REST/JSON
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

        // Realizar consulta REST
        $resultado = $this->consultarServicioSUNATRest($ruc);
        
        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado);
    }

    // ========================================
    // 📌 BUSCAR POR RAZÓN SOCIAL (SUNAT) - REST/JSON
    // ========================================
    public function buscarRazonSocial() {
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

        if (!isset($input['razonSocial'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Razón social no proporcionada'
            ]);
            return;
        }

        $razonSocial = trim($input['razonSocial']);

        // Validar que no esté vacío
        if (empty($razonSocial)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Razón social no puede estar vacía'
            ]);
            return;
        }

        // Realizar consulta REST por razón social
        $resultado = $this->buscarPorRazonSocialSUNATRest($razonSocial);
        
        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado);
    }

    // ========================================
    // 🔍 SERVICIO SUNAT REST - CONSULTA POR RUC
    // ========================================
    private function consultarServicioSUNATRest($ruc) {
        try {
            // Construir URL con parámetros
            $url = $this->urlSUNATRest . '/DatosPrincipales?numruc=' . urlencode($ruc) . '&out=json';

            // Inicializar CURL
            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false, // En producción, cambiar a true
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json'
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Log para debug
            error_log("SUNAT REST Response HTTP Code: $httpCode");
            if ($curlError) {
                error_log("SUNAT REST CURL Error: $curlError");
            }

            // Manejar errores de conexión
            if ($curlError) {
                return [
                    'success' => false,
                    'message' => "Error de conexión con SUNAT: $curlError",
                    'data' => null
                ];
            }

            // Procesar respuesta según código HTTP
            if ($httpCode == 200) {
                return $this->procesarRespuestaJSON($response, $ruc);
            } elseif ($httpCode == 404) {
                return [
                    'success' => false,
                    'message' => 'No se encontró información para el RUC consultado',
                    'data' => null
                ];
            } elseif ($httpCode == 500) {
                return [
                    'success' => false,
                    'message' => 'Error interno del servidor SUNAT',
                    'data' => null
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP $httpCode al consultar SUNAT",
                    'data' => null
                ];
            }

        } catch (\Exception $e) {
            error_log("Exception en consultarServicioSUNATRest: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al consultar RUC: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    // ========================================
    // 🔍 SERVICIO SUNAT REST - BÚSQUEDA POR RAZÓN SOCIAL
    // ========================================
    private function buscarPorRazonSocialSUNATRest($razonSocial) {
        try {
            // Construir URL con parámetros
            $url = $this->urlSUNATRest . '/RazonSocial?RSocial=' . urlencode($razonSocial) . '&out=json';

            error_log("URL búsqueda razón social: $url");

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json'
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            error_log("SUNAT REST Búsqueda Response HTTP Code: $httpCode");
            if ($curlError) {
                error_log("SUNAT REST Búsqueda CURL Error: $curlError");
            }

            if ($curlError) {
                return [
                    'success' => false,
                    'message' => "Error de conexión con SUNAT: $curlError",
                    'data' => []
                ];
            }

            if ($httpCode == 200) {
                return $this->procesarRespuestaBusquedaJSON($response);
            } elseif ($httpCode == 404) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron resultados para la razón social consultada',
                    'data' => []
                ];
            } elseif ($httpCode == 500) {
                return [
                    'success' => false,
                    'message' => 'Error interno del servidor SUNAT',
                    'data' => []
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP $httpCode al buscar en SUNAT",
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
    // 📄 PROCESAR RESPUESTA JSON (CONSULTA POR RUC)
    // ========================================
    private function procesarRespuestaJSON($jsonResponse, $ruc) {
        try {
            $respuesta = json_decode($jsonResponse, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'message' => 'Error al decodificar respuesta JSON de SUNAT: ' . json_last_error_msg(),
                    'data' => null
                ];
            }

            // Verificar estructura de la respuesta
            if (!isset($respuesta['list']['multiRef'])) {
                error_log("Respuesta SUNAT inesperada: " . print_r($respuesta, true));
                return [
                    'success' => false,
                    'message' => 'Formato de respuesta inválido de SUNAT',
                    'data' => null
                ];
            }

            // Extraer datos del multiRef
            $datos = $respuesta['list']['multiRef'];
            
            // Función auxiliar para extraer valor
            $extraerValor = function($campo) use ($datos) {
                if (!isset($datos[$campo])) {
                    return '';
                }
                
                $valor = $datos[$campo];
                
                // Si tiene @nil=true, retornar vacío
                if (is_array($valor) && isset($valor['@nil']) && $valor['@nil'] === true) {
                    return '';
                }
                
                // Si tiene $, retornar ese valor
                if (is_array($valor) && isset($valor['$'])) {
                    return trim($valor['$']);
                }
                
                // Si es string directo
                if (is_string($valor)) {
                    return trim($valor);
                }
                
                return '';
            };

            // Verificar si hay datos válidos
            $rucObtenido = $extraerValor('ddp_numruc');
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
                'data' => [
                    // Datos principales
                    'ruc' => $rucObtenido,
                    'razon_social' => $extraerValor('ddp_nombre'),
                    
                    // Ubicación
                    'codigo_ubigeo' => $extraerValor('ddp_ubigeo'),
                    'departamento' => $extraerValor('desc_dep'),
                    'provincia' => $extraerValor('desc_prov'),
                    'distrito' => $extraerValor('desc_dist'),
                    'cod_dep' => $extraerValor('cod_dep'),
                    'cod_prov' => $extraerValor('cod_prov'),
                    'cod_dist' => $extraerValor('cod_dist'),
                    
                    // Dirección completa
                    'tipo_via' => $extraerValor('desc_tipvia'),
                    'codigo_tipo_via' => $extraerValor('ddp_tipvia'),
                    'nombre_via' => $extraerValor('ddp_nomvia'),
                    'numero' => $extraerValor('ddp_numer1'),
                    'interior' => $extraerValor('ddp_inter1'),
                    'tipo_zona' => $extraerValor('desc_tipzon'),
                    'codigo_tipo_zona' => $extraerValor('ddp_tipzon'),
                    'nombre_zona' => $extraerValor('ddp_nomzon'),
                    'referencia' => $extraerValor('ddp_refer1'),
                    
                    // Estado y condición
                    'estado_contribuyente' => $extraerValor('desc_estado'),
                    'codigo_estado' => $extraerValor('ddp_estado'),
                    'condicion_domicilio' => $extraerValor('desc_flag22'),
                    'codigo_condicion' => $extraerValor('ddp_flag22'),
                    
                    // Tipo de contribuyente
                    'tipo_contribuyente' => $extraerValor('desc_tpoemp'),
                    'codigo_tipo_contribuyente' => $extraerValor('ddp_tpoemp'),
                    'tipo_persona' => $extraerValor('desc_identi'),
                    'codigo_tipo_persona' => $extraerValor('ddp_identi'),
                    
                    // Actividad económica
                    'actividad_economica' => $extraerValor('desc_ciiu'),
                    'codigo_ciiu' => $extraerValor('ddp_ciiu'),
                    
                    // Dependencia
                    'dependencia' => $extraerValor('desc_numreg'),
                    'codigo_dependencia' => $extraerValor('ddp_numreg'),
                    
                    // Fechas
                    'fecha_actualizacion' => $extraerValor('ddp_fecact'),
                    'fecha_alta' => $extraerValor('ddp_fecalt'),
                    'fecha_baja' => $extraerValor('ddp_fecbaj'),
                    
                    // Otros datos
                    'codigo_secuencia' => $extraerValor('ddp_secuen'),
                    'libreta_tributaria' => $extraerValor('ddp_lllttt'),
                    'tamaño' => $extraerValor('desc_tamano'),
                    
                    // Estados booleanos
                    'es_activo' => $this->convertirBooleano($extraerValor('esActivo')),
                    'es_habido' => $this->convertirBooleano($extraerValor('esHabido')),
                    'estado_activo' => $this->convertirBooleano($extraerValor('esActivo')) ? 'SÍ' : 'NO',
                    'estado_habido' => $this->convertirBooleano($extraerValor('esHabido')) ? 'SÍ' : 'NO'
                ]
            ];

            // Construir dirección completa
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
    // 📄 PROCESAR RESPUESTA JSON (BÚSQUEDA POR RAZÓN SOCIAL) - CORREGIDO
    // ========================================
    private function procesarRespuestaBusquedaJSON($jsonResponse) {
        try {
            error_log("Procesando respuesta de búsqueda por razón social");
            error_log("Response raw: " . substr($jsonResponse, 0, 1000));

            $respuesta = json_decode($jsonResponse, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error JSON decode: " . json_last_error_msg());
                return [
                    'success' => false,
                    'message' => 'Error al decodificar respuesta JSON de SUNAT: ' . json_last_error_msg(),
                    'data' => []
                ];
            }

            // Verificar estructura de la respuesta
            if (!isset($respuesta['list']['multiRef'])) {
                error_log("No se encontró multiRef en la respuesta");
                error_log("Estructura completa: " . print_r($respuesta, true));
                return [
                    'success' => false,
                    'message' => 'No se encontraron resultados para la razón social consultada',
                    'data' => []
                ];
            }

            $multiRef = $respuesta['list']['multiRef'];
            
            // Verificar si multiRef es un array de resultados
            if (!is_array($multiRef)) {
                error_log("multiRef no es un array");
                return [
                    'success' => false,
                    'message' => 'Formato de respuesta inválido',
                    'data' => []
                ];
            }

            // Si multiRef no tiene índices numéricos, es un solo resultado
            $esArrayMultiple = isset($multiRef[0]);
            if (!$esArrayMultiple) {
                // Es un solo resultado, convertirlo en array
                $multiRef = [$multiRef];
            }

            error_log("Total de resultados encontrados: " . count($multiRef));

            $resultados = [];

            // Función auxiliar para extraer valor
            $extraerValor = function($campo, $datos) {
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
            };

            foreach ($multiRef as $index => $datos) {
                $ruc = $extraerValor('ddp_numruc', $datos);
                $nombre = $extraerValor('ddp_nombre', $datos);
                
                error_log("Procesando resultado $index: RUC=$ruc, Nombre=$nombre");
                
                // Solo procesar si tiene RUC
                if (!empty($ruc)) {
                    $resultado = [
                        // Datos principales
                        'ruc' => $ruc,
                        'razon_social' => $nombre,
                        'secuencia' => (int)$extraerValor('ddp_secuen', $datos),
                        
                        // Ubicación
                        'codigo_ubigeo' => $extraerValor('ddp_ubigeo', $datos),
                        'departamento' => $extraerValor('desc_dep', $datos),
                        'provincia' => $extraerValor('desc_prov', $datos),
                        'distrito' => $extraerValor('desc_dist', $datos),
                        'cod_dep' => $extraerValor('cod_dep', $datos),
                        'cod_prov' => $extraerValor('cod_prov', $datos),
                        'cod_dist' => $extraerValor('cod_dist', $datos),
                        
                        // Dirección
                        'tipo_via' => $extraerValor('desc_tipvia', $datos),
                        'codigo_tipo_via' => $extraerValor('ddp_tipvia', $datos),
                        'nombre_via' => $extraerValor('ddp_nomvia', $datos),
                        'numero' => $extraerValor('ddp_numer1', $datos),
                        'interior' => $extraerValor('ddp_inter1', $datos),
                        'tipo_zona' => $extraerValor('desc_tipzon', $datos),
                        'codigo_tipo_zona' => $extraerValor('ddp_tipzon', $datos),
                        'nombre_zona' => $extraerValor('ddp_nomzon', $datos),
                        'referencia' => $extraerValor('ddp_refer1', $datos),
                        
                        // Estado
                        'estado_contribuyente' => $extraerValor('desc_estado', $datos),
                        'codigo_estado' => $extraerValor('ddp_estado', $datos),
                        'condicion_domicilio' => $extraerValor('desc_flag22', $datos),
                        'codigo_condicion' => $extraerValor('ddp_flag22', $datos),
                        
                        // Tipo
                        'tipo_contribuyente' => $extraerValor('desc_tpoemp', $datos),
                        'codigo_tipo_contribuyente' => $extraerValor('ddp_tpoemp', $datos),
                        'tipo_persona' => $extraerValor('desc_identi', $datos),
                        'codigo_tipo_persona' => $extraerValor('ddp_identi', $datos),
                        
                        // Actividad
                        'actividad_economica' => $extraerValor('desc_ciiu', $datos),
                        'codigo_ciiu' => $extraerValor('ddp_ciiu', $datos),
                        
                        // Dependencia
                        'dependencia' => $extraerValor('desc_numreg', $datos),
                        'codigo_dependencia' => $extraerValor('ddp_numreg', $datos),
                        
                        // Fechas
                        'fecha_actualizacion' => $extraerValor('ddp_fecact', $datos),
                        'fecha_alta' => $extraerValor('ddp_fecalt', $datos),
                        'fecha_baja' => $extraerValor('ddp_fecbaj', $datos),
                        
                        // Estados booleanos
                        'es_activo' => $this->convertirBooleano($extraerValor('esActivo', $datos)),
                        'es_habido' => $this->convertirBooleano($extraerValor('esHabido', $datos)),
                        'estado_activo' => $this->convertirBooleano($extraerValor('esActivo', $datos)) ? 'SÍ' : 'NO',
                        'estado_habido' => $this->convertirBooleano($extraerValor('esHabido', $datos)) ? 'SÍ' : 'NO'
                    ];
                    
                    // Construir dirección completa
                    $resultado['direccion_completa'] = $this->construirDireccionDesdeArray($resultado);
                    
                    $resultados[] = $resultado;
                    error_log("Resultado procesado exitosamente");
                } else {
                    error_log("Registro sin RUC, se omite");
                }
            }

            if (empty($resultados)) {
                error_log("No se encontraron resultados válidos");
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
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Error al procesar respuesta: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    // ========================================
    // 🏠 CONSTRUIR DIRECCIÓN COMPLETA DESDE ARRAY
    // ========================================
    private function construirDireccionDesdeArray($data) {
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
    // 🔄 CONVERTIR A BOOLEANO
    // ========================================
    private function convertirBooleano($valor) {
        // Si está vacío, retornar false
        if (empty($valor)) {
            return false;
        }
        
        // Si ya es booleano, retornarlo directamente
        if (is_bool($valor)) {
            return $valor;
        }
        
        // Si es numérico
        if (is_numeric($valor)) {
            return (int)$valor === 1;
        }
        
        // Si es string, convertir
        $valorStr = strtolower((string)$valor);
        return in_array($valorStr, ['true', '1', 'yes', 'si', 'sí']);
    }

    // ========================================
    // 💾 REGISTRAR CONSULTA EN LOG
    // ========================================
    private function registrarConsulta($tipo, $documento, $respuesta) {
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
?>