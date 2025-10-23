<?php
namespace App\Repositories;

use App\Config\Database;
use App\Models\Persona;
use App\Models\Usuario;
use PDO;
use PDOException;

class UsuarioRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    

    public function login($nombreUsuario, $password) {
        try {
            $sql = "EXEC SP_S_USUARIO_LOGIN @USU_login = :nombreUsuario, @USU_pass = :password";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':nombreUsuario', $nombreUsuario, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return ['valido' => false, 'mensaje' => 'Sin respuesta del servidor'];
            }

            return [
                'valido' => (bool)$result['VALIDO'],
                'mensaje' => $result['MENSAJE']
            ];

        } catch (PDOException $e) {
            throw new \Exception("Error al iniciar sesión: " . $e->getMessage());
        }
    }

    /**
     * Validar CUI usando SP_S_USUARIO_VALIDAR_CUI
     */
    public function validarCUI($nombreUsuario, $password, $cui) {
        try {
            $sql = "EXEC SP_S_USUARIO_VALIDAR_CUI @USU_login = :nombreUsuario, @USU_pass = :password, @CUI = :cui";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':nombreUsuario', $nombreUsuario, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':cui', $cui, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si devuelve sólo un mensaje de error
            if (isset($result['VALIDO']) && $result['VALIDO'] == 0) {
                return [
                    'valido' => false,
                    'mensaje' => $result['MENSAJE']
                ];
            }

            // Si devuelve datos completos del usuario
            return [
                'valido' => true,
                'mensaje' => 'CUI validado correctamente',
                'usuario' => $result
            ];

        } catch (PDOException $e) {
            throw new \Exception("Error al validar CUI: " . $e->getMessage());
        }
    }

    
    /**
     * Mapea los datos del SP al modelo Usuario
     */
    private function mapToUsuario($data) {
        $usuario = new Usuario();
        $usuario->setUSU_ID($data['USU_id']);
        $usuario->setUSU_login($data['USU_login']);
        $usuario->setUSU_pass($data['USU_pass']);
        $usuario->setUSU_permiso($data['USU_permiso']);
        $usuario->setUSU_Estado($data['USU_estado']);
        $usuario->setCui($data['cui'] ?? null);

        $persona = new Persona();
        $persona->setPER_documento_num($data['PER_documento_num']);
        $persona->setPER_nombre($data['PER_nombre']);
        $persona->setPER_apellido_pat($data['PER_apellido_pat']);
        $persona->setPER_apellido_mat($data['PER_apellido_mat']);

        return $usuario;
    }
}
