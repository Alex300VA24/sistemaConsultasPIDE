<?php
namespace App\Helpers;

class Permisos {
    public static function obtenerPermisos($cargoID, $modoEmergencia = false) {
        $permisos = [
            1 => ['inicio', 'practicantes', 'documentos', 
                  'asistencias', 'reportes', 'certificados',
                  'consultaDNI', 'consultaRUC', 'consultaPartidas', 
                  'consultaCobranza', 'consultaPapeletas', 'consultaCertificaciones',
                  'crearUsuario', 'actualizarPassword'], // Gerente RRHH
            2 => ['inicio', 'practicantes', 'asistencias'], // Encargado de Área
            3 => ['inicio', 'asistencias'], // Usuario de Área
        ];

        return $permisos[$cargoID] ?? ['inicio'];
    }
}
