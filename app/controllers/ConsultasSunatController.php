<?php

namespace App\Controllers;

class ConsultasSunatController {
    
    private $urlSUNAT;
    private $urlPIDE;
    
    public function __construct() {
        // URL del WSDL de SUNAT (sin espacios al final)
        $this->urlSUNAT = $_ENV['PIDE_URL_SUNAT'] ?? 
            "https://ws3.pide.gob.pe/services/SunatConsultaRuc?wsdl";
        $this->urlPIDE = "https://ws3.pide.gob.pe/Rest/Sunat/RazonSocial";
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
    // 📌 BUSCAR POR RAZÓN SOCIAL (SUNAT)
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

        // Realizar consulta por razón social
        $resultado = $this->buscarPorRazonSocial($razonSocial);
        
        http_response_code($resultado['success'] ? 200 : 404);
        echo json_encode($resultado);
    }

    // ========================================
    // 🔍 SERVICIO SUNAT - CONSULTA POR RUC (CURL + SOAP)
    // ========================================
    private function consultarServicioSUNAT($ruc) {
        try {
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

            // Inicializar CURL
            $ch = curl_init($this->urlSUNAT);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $soapEnvelope,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false, // En producción, cambiar a true
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 45
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Log para debug (opcional)
            error_log("SUNAT Response HTTP Code: $httpCode");
            if ($curlError) {
                error_log("SUNAT CURL Error: $curlError");
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
                return $this->parsearRespuestaSOAP($response, $ruc);
            } elseif ($httpCode == 500) {
                // Intentar extraer mensaje de error del SOAP Fault
                $errorMsg = $this->extraerErrorSOAP($response);
                return [
                    'success' => false,
                    'message' => $errorMsg ?: 'Error interno del servidor SUNAT',
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
            error_log("Exception en consultarServicioSUNAT: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al consultar RUC: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Buscar contribuyentes por razón social en PIDE
     * @param string $razonSocial Nombre o razón social a buscar
     * @return array Resultado de la búsqueda
     */
    public function buscarPorRazonSocial($razonSocial) {
        try {
            // Validar entrada
            if (empty(trim($razonSocial))) {
                return [
                    'success' => false,
                    'message' => 'Debe ingresar una razón social para buscar',
                    'data' => []
                ];
            }
            
            // Construir URL con parámetros (aunque pida JSON, devuelve XML)
            $url = $this->urlPIDE . '?RSocial=' . urlencode($razonSocial);
            
            error_log("Consultando PIDE: $url");
            
            // Configurar cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            error_log("PIDE Response HTTP Code: $httpCode");
            
            if ($curlError) {
                error_log("PIDE CURL Error: $curlError");
                return [
                    'success' => false,
                    'message' => "Error de conexión con PIDE: $curlError",
                    'data' => []
                ];
            }
            
            if ($httpCode == 200) {
                // La respuesta es XML/SOAP, no JSON
                return $this->parsearRespuestaXML($response, $razonSocial);
            } elseif ($httpCode == 404) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron resultados para la razón social consultada',
                    'data' => []
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Error HTTP $httpCode al consultar PIDE",
                    'data' => []
                ];
            }
            
        } catch (\Exception $e) {
            error_log("Exception en buscarPorRazonSocial: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al buscar razon social: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Parsear respuesta XML/SOAP de PIDE
     * @param string $xmlResponse Respuesta XML del servicio
     * @param string $razonSocialBuscada Razón social que se buscó
     * @return array Datos parseados
     */
    private function parsearRespuestaXML($xmlResponse, $razonSocialBuscada) {
        try {
            // Verificar si la respuesta está vacía
            if (empty($xmlResponse)) {
                return [
                    'success' => false,
                    'message' => 'Respuesta vacía del servidor PIDE',
                    'data' => []
                ];
            }
            
            // Cargar XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlResponse);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                error_log("Error al parsear XML: " . print_r($errors, true));
                return [
                    'success' => false,
                    'message' => 'Error al procesar respuesta XML de PIDE',
                    'data' => []
                ];
            }
            
            // Registrar namespaces
            $namespaces = $xml->getNamespaces(true);
            foreach ($namespaces as $prefix => $ns) {
                $xml->registerXPathNamespace($prefix ?: 'default', $ns);
            }
            
            // Buscar elementos multiRef (contienen los datos)
            $multiRefs = $xml->xpath('//multiRef');
            
            if (empty($multiRefs)) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron resultados para: ' . $razonSocialBuscada,
                    'data' => []
                ];
            }
            
            $resultados = [];
            
            foreach ($multiRefs as $item) {
                // Validar que tenga RUC
                $ruc = (string)$item->ddp_numruc;
                if (empty($ruc)) {
                    continue;
                }
                
                $resultados[] = [
                    // Datos principales
                    'ruc' => $ruc,
                    'razon_social' => (string)$item->ddp_nombre,
                    'secuencia' => (int)$item->ddp_secuen,
                    
                    // Ubicación
                    'codigo_ubigeo' => (string)$item->ddp_ubigeo,
                    'codigo_departamento' => (string)$item->cod_dep,
                    'codigo_provincia' => (string)$item->cod_prov,
                    'codigo_distrito' => (string)$item->cod_dist,
                    'departamento' => (string)$item->desc_dep,
                    'provincia' => (string)$item->desc_prov,
                    'distrito' => (string)$item->desc_dist,
                    
                    // Dirección
                    'direccion_completa' => $this->construirDireccionXML($item),
                    'tipo_via' => (string)$item->desc_tipvia,
                    'codigo_tipo_via' => (string)$item->ddp_tipvia,
                    'nombre_via' => (string)$item->ddp_nomvia,
                    'numero' => (string)$item->ddp_numer1,
                    'interior' => (string)$item->ddp_inter1,
                    'nombre_zona' => (string)$item->ddp_nomzon,
                    'tipo_zona' => (string)$item->desc_tipzon,
                    'codigo_tipo_zona' => (string)$item->ddp_tipzon,
                    'referencia' => (string)$item->ddp_refer1,
                    
                    // Estado
                    'estado_contribuyente' => (string)$item->desc_estado,
                    'codigo_estado' => (string)$item->ddp_estado,
                    'condicion_domicilio' => (string)$item->desc_flag22,
                    'codigo_condicion' => (string)$item->ddp_flag22,
                    
                    // Fechas
                    'fecha_alta' => (string)$item->ddp_fecalt,
                    'fecha_baja' => (string)$item->ddp_fecbaj,
                    'fecha_actividad' => (string)$item->ddp_fecact,
                    'fecha_reactivacion' => (string)$item->ddp_reacti,
                    
                    // Tipo
                    'tipo_contribuyente' => (string)$item->desc_tpoemp,
                    'codigo_tipo_contribuyente' => (string)$item->ddp_tpoemp,
                    'tipo_persona' => (string)$item->desc_identi,
                    'codigo_tipo_persona' => (string)$item->ddp_identi,
                    
                    // Actividad económica
                    'actividad_economica' => (string)$item->desc_ciiu,
                    'codigo_ciiu' => (string)$item->ddp_ciiu,
                    
                    // Tamaño
                    'tamano_empresa' => (string)$item->desc_tamano,
                    'codigo_tamano' => (string)$item->ddp_tamano,
                    
                    // Otros
                    'numero_registro' => (string)$item->ddp_numreg,
                    'desc_numero_registro' => (string)$item->desc_numreg,
                    'usuario' => (string)$item->ddp_userna,
                    'clasificacion' => (string)$item->ddp_mclase,
                    'doble_tributacion' => (string)$item->ddp_doble,
                    'latitud_longitud' => (string)$item->ddp_lllttt,
                    
                    // Estados booleanos
                    'es_activo' => $this->convertirBooleanoXML((string)$item->esActivo),
                    'es_habido' => $this->convertirBooleanoXML((string)$item->esHabido),
                    'estado_activo' => $this->convertirBooleanoXML((string)$item->esActivo) ? 'SÍ' : 'NO',
                    'estado_habido' => $this->convertirBooleanoXML((string)$item->esHabido) ? 'SÍ' : 'NO'
                ];
            }
            
            if (empty($resultados)) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron resultados válidos para: ' . $razonSocialBuscada,
                    'data' => []
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Búsqueda exitosa',
                'data' => $resultados,
                'total' => count($resultados)
            ];
            
        } catch (\Exception $e) {
            error_log("Exception en parsearRespuestaXML: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar respuesta: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    // ========================================
    // 📄 PARSEAR RESPUESTA SOAP (CONSULTA POR RUC)
    // ========================================
    private function parsearRespuestaSOAP($xmlResponse, $ruc) {
        try {
            libxml_use_internal_errors(true);
            
            $xml = simplexml_load_string($xmlResponse);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                return [
                    'success' => false,
                    'message' => 'Error al parsear respuesta XML de SUNAT',
                    'data' => null
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
                    'message' => 'No se encontró información para el RUC consultado',
                    'data' => null
                ];
            }
            
            $datos = $multiRef[0];

            $resultado = [
                'success' => true,
                'message' => 'Consulta exitosa',
                'data' => [
                    // Datos principales
                    'ruc' => (string)($datos->ddp_numruc ?? $ruc),
                    'razon_social' => (string)($datos->ddp_nombre ?? ''),
                    
                    // Ubicación
                    'codigo_ubigeo' => (string)($datos->ddp_ubigeo ?? ''),
                    'departamento' => (string)($datos->desc_dep ?? ''),
                    'provincia' => (string)($datos->desc_prov ?? ''),
                    'distrito' => (string)($datos->desc_dist ?? ''),
                    'cod_dep' => (string)($datos->cod_dep ?? ''),
                    'cod_prov' => (string)($datos->cod_prov ?? ''),
                    'cod_dist' => (string)($datos->cod_dist ?? ''),
                    
                    // Dirección completa
                    'tipo_via' => (string)($datos->desc_tipvia ?? ''),
                    'codigo_tipo_via' => (string)($datos->ddp_tipvia ?? ''),
                    'nombre_via' => (string)($datos->ddp_nomvia ?? ''),
                    'numero' => (string)($datos->ddp_numer1 ?? ''),
                    'interior' => (string)($datos->ddp_inter1 ?? ''),
                    'tipo_zona' => (string)($datos->desc_tipzon ?? ''),
                    'codigo_tipo_zona' => (string)($datos->ddp_tipzon ?? ''),
                    'nombre_zona' => (string)($datos->ddp_nomzon ?? ''),
                    'referencia' => (string)($datos->ddp_refer1 ?? ''),
                    'direccion_completa' => $this->construirDireccion($datos),
                    
                    // Estado y condición
                    'estado_contribuyente' => (string)($datos->desc_estado ?? ''),
                    'codigo_estado' => (string)($datos->ddp_estado ?? ''),
                    'condicion_domicilio' => (string)($datos->desc_flag22 ?? ''),
                    'codigo_condicion' => (string)($datos->ddp_flag22 ?? ''),
                    
                    // Tipo de contribuyente
                    'tipo_contribuyente' => (string)($datos->desc_tpoemp ?? ''),
                    'codigo_tipo_contribuyente' => (string)($datos->ddp_tpoemp ?? ''),
                    'tipo_persona' => (string)($datos->desc_identi ?? ''),
                    'codigo_tipo_persona' => (string)($datos->ddp_identi ?? ''),
                    
                    // Actividad económica
                    'actividad_economica' => (string)($datos->desc_ciiu ?? ''),
                    'codigo_ciiu' => (string)($datos->ddp_ciiu ?? ''),
                    
                    // Dependencia
                    'dependencia' => (string)($datos->desc_numreg ?? ''),
                    'codigo_dependencia' => (string)($datos->ddp_numreg ?? ''),
                    
                    // Fechas
                    'fecha_actualizacion' => (string)($datos->ddp_fecact ?? ''),
                    'fecha_alta' => (string)($datos->ddp_fecalt ?? ''),
                    'fecha_baja' => (string)($datos->ddp_fecbaj ?? ''),
                    
                    // Otros datos
                    'codigo_secuencia' => (string)($datos->ddp_secuen ?? ''),
                    'libreta_tributaria' => (string)($datos->ddp_lllttt ?? ''),
                    
                    // Estados booleanos
                    'es_activo' => $this->convertirBooleano($datos->esActivo ?? 'false'),
                    'es_habido' => $this->convertirBooleano($datos->esHabido ?? 'false'),
                    'estado_activo' => $this->convertirBooleano($datos->esActivo ?? 'false') ? 'SÍ' : 'NO',
                    'estado_habido' => $this->convertirBooleano($datos->esHabido ?? 'false') ? 'SÍ' : 'NO'
                ]
            ];

            $this->registrarConsulta('RUC', $ruc, $resultado['data']);

            return $resultado;

        } catch (\Exception $e) {
            error_log("Exception en parsearRespuestaSOAP: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar respuesta: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Construir dirección completa desde XML
     * @param SimpleXMLElement $item Elemento XML con los datos
     * @return string Dirección formateada
     */
    private function construirDireccionXML($item) {
        $partes = [];
        
        // Tipo y nombre de vía
        $via = trim((string)$item->desc_tipvia);
        $nombreVia = trim((string)$item->ddp_nomvia);
        if (!empty($via) && !empty($nombreVia)) {
            $partes[] = $via . ' ' . $nombreVia;
        } elseif (!empty($nombreVia)) {
            $partes[] = $nombreVia;
        }
        
        // Número
        $numero = trim((string)$item->ddp_numer1);
        if (!empty($numero)) {
            $partes[] = 'Nro. ' . $numero;
        }
        
        // Interior
        $interior = trim((string)$item->ddp_inter1);
        if (!empty($interior)) {
            $partes[] = 'Int. ' . $interior;
        }
        
        // Zona
        $tipoZona = trim((string)$item->desc_tipzon);
        $nombreZona = trim((string)$item->ddp_nomzon);
        if (!empty($tipoZona) && !empty($nombreZona)) {
            $partes[] = $tipoZona . ' ' . $nombreZona;
        } elseif (!empty($nombreZona)) {
            $partes[] = $nombreZona;
        }
        
        // Distrito, Provincia, Departamento
        $distrito = trim((string)$item->desc_dist);
        $provincia = trim((string)$item->desc_prov);
        $departamento = trim((string)$item->desc_dep);
        
        $ubicacion = array_filter([$distrito, $provincia, $departamento]);
        if (!empty($ubicacion)) {
            $partes[] = implode(' - ', $ubicacion);
        }
        
        return !empty($partes) ? implode(', ', $partes) : 'Sin dirección registrada';
    }
    /**
     * Convertir valor XML a booleano
     * @param string $valor Valor del XML
     * @return bool Valor booleano
     */
    private function convertirBooleanoXML($valor) {
        $valor = strtolower(trim($valor));
        return in_array($valor, ['true', '1', 'yes', 'si', 'sí']);
    }

    /**
     * Obtener valor de array con validación
     * @param array $data Array de datos
     * @param string $key Clave a buscar
     * @param mixed $default Valor por defecto
     * @return mixed Valor encontrado o valor por defecto
     */
    private function obtenerValor($data, $key, $default = '') {
        if (!isset($data[$key])) {
            return $default;
        }
        
        $valor = $data[$key];
        
        // Si es null o string vacío, retornar default
        if ($valor === null || $valor === '') {
            return $default;
        }
        
        return $valor;
    }
    

    // ========================================
    // 🏠 CONSTRUIR DIRECCIÓN COMPLETA
    // ========================================
    private function construirDireccion($datos) {
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
        
        if (!empty((string)$datos->ddp_refer1) && (string)$datos->ddp_refer1 !== '-') {
            $partes[] = '(' . (string)$datos->ddp_refer1 . ')';
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
    // ⚠️ EXTRAER ERROR DE SOAP FAULT
    // ========================================
    private function extraerErrorSOAP($xmlResponse) {
        try {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlResponse);
            
            if ($xml === false) {
                return null;
            }

            $namespaces = $xml->getNamespaces(true);
            foreach ($namespaces as $prefix => $ns) {
                $xml->registerXPathNamespace($prefix ?: 'default', $ns);
            }

            $fault = $xml->xpath('//faultstring');
            
            if (!empty($fault)) {
                return (string)$fault[0];
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    // ========================================
    // 💾 REGISTRAR CONSULTA EN BD
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