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
    // Repositorio: modificar la salida cuando VALID0 == 0
    public function validarCUI($nombreUsuario, $password, $cui) {
        try {
            $sql = "EXEC SP_S_USUARIO_VALIDAR_CUI @USU_login = :nombreUsuario, @USU_pass = :password, @CUI = :cui";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':nombreUsuario', $nombreUsuario, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':cui', $cui, PDO::PARAM_STR);
            $stmt->execute();

            // Puede devolver múltiples resultsets (SELECT + EXEC)
            $results = [];
            do {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) {
                    $results[] = $rows;
                }
            } while ($stmt->nextRowset());

            if (empty($results)) {
                return ['valido' => false, 'mensaje' => 'Sin datos devueltos'];
            }

            // El primer SELECT puede ser mensaje, el segundo los datos
            $first = $results[0][0];

            if (isset($first['VALIDO']) && $first['VALIDO'] == 0) {
                return [
                    'valido' => false,
                    'mensaje' => $first['MENSAJE']
                ];
            }

            // Si no hay error, asumimos que el último resultset es el usuario
            $usuarioData = end($results)[0];

            return [
                'valido' => true,
                'mensaje' => 'CUI validado correctamente',
                'usuario' => $usuarioData
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

    public function obtenerDni($nombreUsuario)
    {
        $sql = "EXEC sp_ObtenerDni @usuLogin = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $nombreUsuario);
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Listar todos los usuarios
     */
    public function listarUsuarios()
    {
        try {
            $stmt = $this->db->prepare("EXEC sp_ListarUsuarios");
            $stmt->execute();
            
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $resultados;
        } catch (PDOException $e) {
            error_log("Error en listarUsuarios: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener usuario por ID
     */
    public function obtenerUsuarioPorId($usuarioId)
    {
        try {
            $stmt = $this->db->prepare("EXEC sp_ObtenerUsuarioPorId @USU_id = :usuarioId");
            $stmt->bindParam(':usuarioId', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $resultado ?: null;
        } catch (PDOException $e) {
            error_log("Error en obtenerUsuarioPorId: " . $e->getMessage());
            throw $e;
        }
    }

    

    /**
     * Actualizar usuario
     */
    public function actualizarUsuario($datos)
    {
        try {
            $sql = "EXEC sp_ActualizarUsuario 
                @USU_id = :usuarioId,
                @PER_id = :personaId,
                @PER_tipo = :perTipo,
                @PER_documento_tipo = :perDocumentoTipo,
                @PER_documento_num = :perDocumentoNum,
                @PER_nombre = :perNombre,
                @PER_apellido_pat = :perApellidoPat,
                @PER_apellido_mat = :perApellidoMat,
                @PER_sexo = :perSexo,
                @PER_email = :perEmail,
                @USU_login = :usuLogin,
                @USU_pass = :usuPass,
                @USU_permiso = :usuPermiso,
                @USU_estado = :usuEstado,
                @cui = :cui";

            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':usuarioId', $datos['USU_id'], PDO::PARAM_INT);
            $stmt->bindParam(':personaId', $datos['PER_id'], PDO::PARAM_INT);
            $stmt->bindParam(':perTipo', $datos['PER_tipo'], PDO::PARAM_INT);
            $stmt->bindParam(':perDocumentoTipo', $datos['PER_documento_tipo'], PDO::PARAM_INT);
            $stmt->bindParam(':perDocumentoNum', $datos['PER_documento_num'], PDO::PARAM_STR);
            $stmt->bindParam(':perNombre', $datos['PER_nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':perApellidoPat', $datos['PER_apellido_pat'], PDO::PARAM_STR);
            $stmt->bindParam(':perApellidoMat', $datos['PER_apellido_mat'], PDO::PARAM_STR);
            $stmt->bindParam(':perSexo', $datos['PER_sexo'], PDO::PARAM_INT);
            $stmt->bindParam(':perEmail', $datos['PER_email'], PDO::PARAM_STR);
            $stmt->bindParam(':usuLogin', $datos['USU_login'], PDO::PARAM_STR);
            
            // La contraseña puede ser NULL si no se actualiza
            $usuPass = !empty($datos['USU_pass']) ? $datos['USU_pass'] : null;
            $stmt->bindParam(':usuPass', $usuPass, PDO::PARAM_STR);
            
            $stmt->bindParam(':usuPermiso', $datos['USU_permiso'], PDO::PARAM_INT);
            $stmt->bindParam(':usuEstado', $datos['USU_estado'], PDO::PARAM_INT);
            
            $cui = $datos['cui'] ?? null;
            $stmt->bindParam(':cui', $cui, PDO::PARAM_INT);

            $stmt->execute();
            
            return true;
        } catch (PDOException $e) {
            error_log("Error en actualizarUsuario: " . $e->getMessage());
            throw $e;
        }
    }

    public function actualizarPassword($datos)
    {
        try {
            $sql = "EXEC sp_ActualizarUsuario 
                @USU_id = :usuarioId,
                @PER_id = :personaId,
                @USU_pass = :usuPass";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':usuarioId', $datos['USU_id'], PDO::PARAM_INT);
            $stmt->bindParam(':personaId', $datos['PER_id'], PDO::PARAM_INT);
            
            
            // La contraseña puede ser NULL si no se actualiza
            $usuPass = !empty($datos['USU_pass']) ? $datos['USU_pass'] : null;
            $stmt->bindParam(':usuPass', $usuPass, PDO::PARAM_STR);
            

            $stmt->execute();
            
            return true;
        } catch (PDOException $e) {
            error_log("Error en actualizar password: " . $e->getMessage());
            throw $e;
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
