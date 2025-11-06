<?php

namespace App\Controllers;

class ConsultasSunatController {
    
    private $urlSUNAT;
    
    public function __construct() {
        // URL del WSDL de SUNAT (sin espacios al final)
        $this->urlSUNAT = $_ENV['PIDE_URL_SUNAT'] ?? 
            "https://ws3.pide.gob.pe/services/SunatConsultaRuc?wsdl";
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
    // 🔍 SERVICIO SUNAT (CURL + SOAP)
    // ========================================
    private function consultarServicioSUNAT($ruc) {
        try {
            // ⚠️ IMPORTANTE: Usar el namespace que funciona en tu prueba
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

    // ========================================
    // 📄 PARSEAR RESPUESTA SOAP
    // ========================================
    private function parsearRespuestaSOAP($xmlResponse, $ruc) {
        try {
            // Suprimir errores de XML mal formado
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

            // Registrar namespaces
            $namespaces = $xml->getNamespaces(true);
            foreach ($namespaces as $prefix => $ns) {
                $xml->registerXPathNamespace($prefix ?: 'default', $ns);
            }

            // Buscar el elemento multiRef que contiene los datos
            $multiRef = $xml->xpath('//multiRef');
            
            if (empty($multiRef)) {
                return [
                    'success' => false,
                    'message' => 'No se encontró información para el RUC consultado',
                    'data' => null
                ];
            }
            
            $datos = $multiRef[0];

            // Extraer todos los datos del XML según la estructura de SUNAT
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

            // Registrar consulta en log
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

            // Buscar faultstring
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
            // Log simple en archivo
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