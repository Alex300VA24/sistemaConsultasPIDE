<?php
namespace App\Helpers;


class Debug {
    public static function log_debug($mensaje, $data = null) {
        $ruta = __DIR__ . '/debug.txt'; // archivo en misma carpeta del controlador
        $fecha = date('Y-m-d H:i:s');
        $contenido = "[$fecha] $mensaje";
        if ($data !== null) {
            $contenido .= ' -> ' . print_r($data, true);
        }
        $contenido .= "\n";
        file_put_contents($ruta, $contenido, FILE_APPEND);
    }
}

