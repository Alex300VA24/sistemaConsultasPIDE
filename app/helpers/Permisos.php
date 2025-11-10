<?php
namespace App\Helpers;

class Permisos {
    public static function obtenerPermisos($rolID) {
        $permisos = [
            '000' => ['inicio', 'consultaDNI', 'consultaRUC', 'consultaPartidas', 
                  'consultaCobranza', 'consultaPapeletas', 'consultaCertificaciones',
                  'crearUsuario', 'actualizarPassword', 'actualizarPassword'], // ADMIN DEL SISTEMA
            '001' => ['inicio', 'consultaDNI', 'actualizarPassword'], // CONSULTA TIPO 1
            '002' => ['inicio', 'consultaRUC', 'actualizarPassword'], // CONSULTA TIPO 2
            '003' => ['inicio', 'consultaPartidas', 'actualizarPassword'], // CONSULTA TIPO 3
            '004' => ['inicio', 'consultaDNI', 'consultaRUC', 'actualizarPassword'], // CONSULTA TIPO 4
            '005' => ['inicio', 'consultaDNI', 'consultaPartidas', 'actualizarPassword'], // CONSULTA TIPO 5
        ];

        return $permisos[$rolID] ?? ['inicio'];
    }
}
