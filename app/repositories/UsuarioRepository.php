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

    public function crearUsuario(array $data) {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();

            $sql = "
                DECLARE @NuevoUsuarioID INT;
                EXEC sp_CrearUsuario
                    @PER_tipo = :PER_tipo,
                    @PER_documento_tipo = :PER_documento_tipo,
                    @PER_documento_num = :PER_documento_num,
                    @PER_nombre = :PER_nombre,
                    @PER_apellido_pat = :PER_apellido_pat,
                    @PER_apellido_mat = :PER_apellido_mat,
                    @PER_sexo = :PER_sexo,
                    @PER_email = :PER_email,
                    @USU_login = :USU_login,
                    @USU_pass = :USU_pass,
                    @USU_permiso = :USU_permiso,
                    @USU_estado = :USU_estado,
                    @cui = :cui,
                    @NuevoUsuarioID = @NuevoUsuarioID OUTPUT;
                SELECT @NuevoUsuarioID AS NuevoUsuarioID;
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->bindValue(':PER_tipo', $data['perTipo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':PER_documento_tipo', $data['perDocumentoTipo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':PER_documento_num', $data['perDocumentoNum'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':PER_nombre', $data['perNombre'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':PER_apellido_pat', $data['perApellidoPat'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':PER_apellido_mat', $data['perApellidoMat'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':PER_sexo', $data['perSexo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':PER_email', $data['perEmail'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':USU_login', $data['usuLogin'], PDO::PARAM_STR);
            $stmt->bindValue(':USU_pass', $data['usuPass'], PDO::PARAM_STR);
            $stmt->bindValue(':USU_permiso', $data['usuPermiso'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':USU_estado', $data['usuEstado'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':cui', $data['cui'] ?? null, PDO::PARAM_INT);

            $stmt->execute();

            // Obtener el ID de salida
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nuevoUsuarioID = $row['NuevoUsuarioID'] ?? null;

            $this->db->commit();

            return $nuevoUsuarioID;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new \Exception("Error al crear usuario: " . $e->getMessage());
        }
    }

    /**
     * Eliminar un usuario (llama al procedimiento sp_EliminarUsuario)
     */
    public function eliminarUsuario(int $usuarioId) {
        try {
            $sql = "EXEC sp_EliminarUsuario @UsuarioID = :UsuarioID;";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':UsuarioID', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            throw new \Exception("Error al eliminar usuario: " . $e->getMessage());
        }
    }

    public function obtenerDniYPassword($nombreUsuario)
    {
        $sql = "EXEC sp_ObtenerDniYPassword @usuLogin = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $nombreUsuario);
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
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
