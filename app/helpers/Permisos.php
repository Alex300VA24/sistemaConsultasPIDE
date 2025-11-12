<?php
namespace App\Helpers;

class Permisos {
    public static function obtenerPermisos($rolID) {
        $permisos = [
            '000' => ['inicio', 'consultaDNI', 'consultaRUC', 'consultaPartidas', 
                  'consultaCobranza', 'consultaPapeletas', 'consultaCertificaciones',
                  'crearUsuario', 'actualizarPassword', 'actualizarUsuario'], // ADMIN DEL SISTEMA
            '006' => ['inicio', 'consultaDNI', 'actualizarPassword'], // CONSULTA TIPO 1
            '007' => ['inicio', 'consultaRUC', 'actualizarPassword'], // CONSULTA TIPO 2
            '008' => ['inicio', 'consultaPartidas', 'actualizarPassword'], // CONSULTA TIPO 3
            '009' => ['inicio', 'consultaDNI', 'consultaRUC', 'actualizarPassword'], // CONSULTA TIPO 4
            '010' => ['inicio', 'consultaRUC', 'consultaPartidas', 'actualizarPassword'], // CONSULTA TIPO 5
            '011' => ['inicio', 'consultaDNI', 'consultaPartidas', 'actualizarPassword'], // CONSULTA TIPO 5
            '012' => ['inicio', 'consultaDNI', 'consultaRUC', 'consultaPartidas', 'actualizarPassword'], // CONSULTA TIPO 5
        ];

        return $permisos[$rolID] ?? ['inicio'];
    }
}
