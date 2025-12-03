<?php
/**
 * Helper para generar páginas dinámicamente basándose en módulos
 * Incluir este archivo en el dashboard/index.php
 */

// Función para convertir URL a ruta de archivo
function obtenerRutaArchivo($url) {
    // Eliminar /pide/ del inicio
    $url = str_replace('/pide/', '', $url);
    
    // Convertir formato URL a formato de archivo
    // Ejemplos:
    // /pide/consultas/dni -> subpages/consultaDNI.php
    // /pide/sistema/crear-usuario -> sistema/crearUsuario.php
    
    $partes = explode('/', $url);
    
    if (count($partes) === 1) {
        // Módulo de nivel 1 (ej: mantenimiento)
        return "pages/" . $url . ".php";
    } else {
        // Módulo de nivel 2 o más (ej: consultas/dni)
        $carpeta = $partes[0];
        $archivo = $partes[1];
        
        // Convertir guiones a camelCase
        $archivoFormateado = '';
        $palabras = explode('-', $archivo);
        foreach ($palabras as $i => $palabra) {
            if ($i === 0) {
                $archivoFormateado .= $palabra;
            } else {
                $archivoFormateado .= ucfirst($palabra);
            }
        }
        
        // Determinar la carpeta correcta
        if ($carpeta === 'consultas') {
            return "pages/consultas/" . $archivoFormateado . ".php";
        } else if ($carpeta === 'sistema') {
            return "pages/sistema/" . $archivoFormateado . ".php";
        } else {
            return "pages/{$carpeta}/{$archivoFormateado}.php";
        }
    }
}

// Función para obtener el ID de la página
function obtenerIdPagina($url) {
    // Eliminar /pide/ del inicio
    $url = str_replace('/pide/', '', $url);
    
    // Convertir guiones y barras a camelCase
    $url = str_replace('/', '-', $url);
    $partes = explode('-', $url);
    $idPagina = 'page';
    
    foreach ($partes as $parte) {
        $idPagina .= ucfirst($parte);
    }
    
    return $idPagina;
}

// Generar páginas dinámicamente
function generarPaginasDinamicas($modulos, $permisos) {
    $paginasGeneradas = [];
    
    // Recorrer todos los módulos
    foreach ($modulos as $modulo) {
        // Verificar permisos
        if (!in_array($modulo['MOD_codigo'], $permisos)) {
            continue;
        }
        
        $idPagina = obtenerIdPagina($modulo['MOD_url']);
        $rutaArchivo = obtenerRutaArchivo($modulo['MOD_url']);
        $rutaCompleta = __DIR__ . "/../../views/dashboard/" . $rutaArchivo;
        error_log("Esta es la ruta completa: " . $rutaCompleta);
        
        // Evitar duplicados
        if (in_array($idPagina, $paginasGeneradas)) {
            continue;
        }
        
        echo "\n        <!-- Módulo: {$modulo['MOD_nombre']} ({$modulo['MOD_codigo']}) -->\n";
        echo "        <div id=\"{$idPagina}\" class=\"page-content\">\n";
        
        if (file_exists($rutaCompleta)) {
            include $rutaCompleta;
        } else {
            // Página placeholder si no existe el archivo
            echo "            <div class=\"usuario-container\">\n";
            echo "                <div class=\"page-title\">\n";
            echo "                    <h1>\n";
            echo "                        <i class=\"{$modulo['MOD_icono']}\"></i>\n";
            echo "                        {$modulo['MOD_nombre']}\n";
            echo "                    </h1>\n";
            echo "                </div>\n";
            echo "                <div class=\"content-wrapper\">\n";
            echo "                    <div class=\"form-section\">\n";
            echo "                        <p><strong>Módulo en construcción</strong></p>\n";
            echo "                        <p>{$modulo['MOD_descripcion']}</p>\n";
            echo "                        <p><em>Archivo esperado: {$rutaArchivo}</em></p>\n";
            echo "                    </div>\n";
            echo "                </div>\n";
            echo "            </div>\n";
        }
        
        echo "        </div>\n";
        
        $paginasGeneradas[] = $idPagina;
        
        // Procesar hijos recursivamente
        if (!empty($modulo['hijos'])) {
            generarPaginasDinamicas($modulo['hijos'], $permisos);
        }
    }
}
?>