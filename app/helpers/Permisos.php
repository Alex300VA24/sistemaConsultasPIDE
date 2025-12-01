<?php
namespace App\Helpers;

use App\Config\Database;
use PDO;
use PDOException;

class Permisos {

    public static function obtenerPermisos($usuarioID) {

        try {
            $db = Database::getInstance()->getConnection();

            $stmt = $db->prepare("EXEC SP_USUARIO_OBTENER_PERMISOS :usuarioID");
            $stmt->bindValue(':usuarioID', $usuarioID, PDO::PARAM_INT);
            $stmt->execute();

            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Si no hay datos, mínimo devolver inicio
            if (!$resultados) {
                return ['inicio'];
            }

            $permisos = [];

            foreach ($resultados as $row) {

                // Validar que pertenezca al sistema PIDE (o el código que definas)
                if (trim($row['SIS_codigo']) !== 'PIDE') {
                    continue;
                }

                // Guardar permisos según MOD_codigo
                if (!empty($row['MOD_codigo'])) {
                    $permisos[] = trim($row['MOD_codigo']);
                }
            }

            // Si no hay módulos válidos, devolver inicio
            if (empty($permisos)) {
                return ['inicio'];
            }

            // Quitar duplicados
            return array_unique($permisos);

        } catch (PDOException $e) {
            // Si ocurre un error, siempre retornar algo por defecto
            return ['inicio'];
        }
    }
}

