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
            $sql = "EXEC SP_USUARIO_LOGIN @USU_username = :nombreUsuario, @USU_pass = :password";
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
            $sql = "EXEC SP_USUARIO_VALIDAR_CUI @USU_username = :nombreUsuario, @USU_pass = :password, @USU_cui = :cui";
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

    // UsuarioRepository.php
    public function obtenerPasswordUser($nombreUsuario) {
        $sql = "SELECT u.USU_password_hash, r.ROL_nombre 
                FROM usuario u
                LEFT JOIN usuario_rol ur ON u.USU_id = ur.USR_usuario_id
                LEFT JOIN rol r ON ur.USR_rol_id = r.ROL_id
                WHERE u.USU_username = :login 
                AND u.USU_estado_id = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['login' => $nombreUsuario]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPasswordCUIUser($nombreUsuario) {
        $sql = "SELECT u.USU_password_hash, u.USU_cui, r.ROL_nombre 
                FROM usuario u
                LEFT JOIN usuario_rol ur ON u.USU_id = ur.USR_usuario_id
                LEFT JOIN rol r ON ur.USR_rol_id = r.ROL_id
                WHERE u.USU_username = :login 
                AND u.USU_estado_id = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['login' => $nombreUsuario]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPasswordPorDNI($dni) {
        $sql = 'SELECT USU_password_hash 
                FROM USUARIO u
                INNER JOIN PERSONA p ON u.USU_persona_id = p.PER_id 
                WHERE p.PER_documento_numero = :dni AND u.USU_estado_id = 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['dni' => $dni]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPasswordPorId($id) {
        $sql = "SELECT u.USU_password_hash, r.ROL_nombre 
                FROM usuario u
                LEFT JOIN usuario_rol ur ON u.USU_id = ur.USR_usuario_id
                LEFT JOIN rol r ON ur.USR_rol_id = r.ROL_id
                WHERE u.USU_id = :usuarioID
                AND u.USU_estado_id = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuarioID' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function crearUsuario(array $data)
    {
        try {
            // ⚠️ NO usar beginTransaction() porque el SP maneja las transacciones
            // $this->db->beginTransaction();

            $sql = "
                DECLARE @resultado INT;
                DECLARE @mensaje VARCHAR(500);
                DECLARE @usuario_id INT;
                DECLARE @persona_id INT;

                EXEC SP_CREAR_USUARIO
                    @p_tipo_persona = :PER_tipo,
                    @p_tipo_personal_id = :PER_tipoPersonal,
                    @p_documento_tipo_id = :PER_documento_tipo,
                    @p_documento_numero = :PER_documento_num,
                    @p_nombres = :PER_nombre,
                    @p_apellido_paterno = :PER_apellido_pat,
                    @p_apellido_materno = :PER_apellido_mat,
                    @p_sexo = :PER_sexo,
                    @p_email = :PER_email,
                    @p_username = :USU_login,
                    @p_password = :USU_pass,
                    @p_permiso = :USU_permiso,
                    @p_estado_id = :USU_estado,
                    @p_cui = :cui,
                    @p_resultado = @resultado OUTPUT,
                    @p_mensaje = @mensaje OUTPUT,
                    @p_usuario_id = @usuario_id OUTPUT,
                    @p_persona_id = @persona_id OUTPUT;

                SELECT @persona_id AS NuevoUsuarioID, 
                    @resultado AS Resultado,
                    @mensaje AS Mensaje;
            ";

            $stmt = $this->db->prepare($sql);

            // PERSONA
            $stmt->bindValue(':PER_tipo', $data['perTipo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':PER_tipoPersonal', $data['perTipoPersonal'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':PER_documento_tipo', $data['perDocumentoTipo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':PER_documento_num', $data['perDocumentoNum'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':PER_nombre', $data['perNombre'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':PER_apellido_pat', $data['perApellidoPat'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':PER_apellido_mat', $data['perApellidoMat'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':PER_sexo', $data['perSexo'] ?? null, PDO::PARAM_STR); // CHAR(1)
            $stmt->bindValue(':PER_email', $data['perEmail'] ?? null, PDO::PARAM_STR);

            // USUARIO
            $stmt->bindValue(':USU_login', $data['usuLogin'], PDO::PARAM_STR);
            $stmt->bindValue(':USU_pass', $data['usuPass'], PDO::PARAM_STR);
            $stmt->bindValue(':USU_permiso', $data['usuPermiso'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':USU_estado', $data['usuEstado'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':cui', $data['cui'] ?? null, PDO::PARAM_STR); // CHAR(1)

            $stmt->execute();

            // Obtener resultados del SELECT final
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new \Exception("No se recibió respuesta del stored procedure.");
            }

            // Puedes validar errores del SP
            if ($row["Resultado"] <= 0) {
                throw new \Exception("SP Error: " . $row["Mensaje"]);
            }

            return $row["NuevoUsuarioID"];


        } catch (PDOException $e) {
            throw new \Exception("Error al crear usuario: " . $e->getMessage());
        }
    }


    /**
     * Eliminar un usuario (llama al procedimiento sp_EliminarUsuario)
     */
    public function eliminarUsuario(int $usuarioId) {
        try {
            $sql = "
                DECLARE @resultado INT;
                DECLARE @mensaje VARCHAR(500);

                EXEC SP_ELIMINAR_USUARIO 
                    @p_usuario_id = :UsuarioID,
                    @p_eliminado_por = NULL,
                    @p_resultado = @resultado OUTPUT,
                    @p_mensaje = @mensaje OUTPUT;

                SELECT 
                    @resultado AS Resultado,
                    @mensaje AS Mensaje;
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':UsuarioID', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();

            // obtener el resultado del select
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result; // Ej: ["Resultado" => 1, "Mensaje" => "Usuario eliminado exitosamente"]
        }
        catch (PDOException $e) {
            throw new \Exception("Error al eliminar usuario: " . $e->getMessage());
        }
    }


    public function obtenerDni($nombreUsuario)
    {
        $sql = "EXEC SP_USUARIO_OBTENER_DNI @USU_username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $nombreUsuario);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Listar todos los usuarios
     */
    public function listarUsuarios()
    {
        try {
            $stmt = $this->db->prepare("EXEC SP_LISTAR_USUARIOS");
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
            $stmt = $this->db->prepare("EXEC SP_OBTENER_USUARIO @p_usuario_id = :usuarioId");
            $stmt->bindParam(':usuarioId', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $resultado ?: null;
        } catch (PDOException $e) {
            error_log("Error en obtenerUsuarioPorId: " . $e->getMessage());
            throw $e;
        }
    }

    public function obtenerRoles(){
        try{
            $sql = "SELECT ROL_id, ROL_nombre FROM ROL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            error_log("Error obteniendo los roles:" . $e->getMessage());
            throw $e;
        }
    }

    public function obtenerTipoPersonal(){
        try{
            $sql = "SELECT TPE_id, TPE_nombre FROM TIPO_PERSONAL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            error_log("Error obteniendo los tipos de personal:" . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Actualizar usuario
     */
    public function actualizarUsuario($datos){
        error_log(print_r($datos, true));
        try {
            $sql = "
            
            DECLARE @resultado INT;
            DECLARE @mensaje VARCHAR(500);

            EXEC SP_ACTUALIZAR_USUARIO 
                @p_usuario_id = :usuarioId,
                @p_tipo_persona = :perTipo,
                @p_tipo_personal_id = :perTipoPersonal,
                @p_documento_tipo_id = :perDocumentoTipo,
                @p_documento_numero = :perDocumentoNum,
                @p_nombres = :perNombre,
                @p_apellido_paterno = :perApellidoPat,
                @p_apellido_materno = :perApellidoMat,
                @p_sexo = :perSexo,
                @p_email = :perEmail,
                @p_username = :usuLogin,
                @p_password_actual = :usuPassActual,
                @p_password_nueva = :usuPass,
                @p_permiso = :usuPermiso,
                @p_estado_id = :usuEstado,
                @p_resultado = @resultado OUTPUT,
                @p_mensaje = @mensaje OUTPUT;
            SELECT @resultado AS resultado, @mensaje AS mensaje;
                ";

            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':usuarioId', $datos['USU_id'], PDO::PARAM_INT);
            $stmt->bindParam(':perTipo', $datos['perTipo'], PDO::PARAM_INT);
            $stmt->bindParam(':perTipoPersonal', $datos['perTipoPersonal'], PDO::PARAM_INT);
            $stmt->bindParam(':perDocumentoTipo', $datos['perDocumentoTipo'], PDO::PARAM_INT);
            $stmt->bindParam(':perDocumentoNum', $datos['perDocumentoNum'], PDO::PARAM_STR);
            $stmt->bindParam(':perNombre', $datos['perNombre'], PDO::PARAM_STR);
            $stmt->bindParam(':perApellidoPat', $datos['perApellidoPat'], PDO::PARAM_STR);
            $stmt->bindParam(':perApellidoMat', $datos['perApellidoMat'], PDO::PARAM_STR);
            $stmt->bindParam(':perSexo', $datos['perSexo'], PDO::PARAM_STR);
            $stmt->bindParam(':perEmail', $datos['perEmail'], PDO::PARAM_STR);
            $stmt->bindParam(':usuLogin', $datos['usuUsername'], PDO::PARAM_STR);
            $stmt->bindParam(':usuPassActual', $datos['usuPassActual'], PDO::PARAM_STR);

            // contraseña nueva puede ser null
            $usuPass = !empty($datos['usuPass']) ? $datos['usuPass'] : null;
            $stmt->bindParam(':usuPass', $usuPass, PDO::PARAM_STR);

            $stmt->bindParam(':usuPermiso', $datos['usuPermiso'], PDO::PARAM_INT);
            $stmt->bindParam(':usuEstado', $datos['usuEstado'], PDO::PARAM_INT);


            $stmt->execute();

            // leer resultados de salida
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res['resultado'] != 1) {
                // error del procedimiento
                return [
                    "success" => false,
                    "message" => $res['mensaje']
                ];
            }

            return [
                "success" => true,
                "message" => $res['mensaje']
            ];

        } catch (PDOException $e) {
            error_log("Error en actualizarUsuario: " . $e->getMessage());
            throw $e;
        }
    }

    public function actualizarPassword($datos)
    {
        try {
            $sql = "
            DECLARE @resultado INT;
            DECLARE @mensaje VARCHAR(500);
            EXEC SP_ACTUALIZAR_PASSWORD
                @p_usuario_id = :usuarioId,
                @p_password_actual = :usuPassActual,
                @p_password_nueva = :usuPass,
                @p_resultado = @resultado OUTPUT,
                @p_mensaje = @mensaje OUTPUT;
            SELECT @resultado AS resultado, @mensaje AS mensaje;
                ";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':usuarioId', $datos['USU_id'], PDO::PARAM_INT);
            $stmt->bindParam(':usuPassActual', $datos['USU_passActual'], PDO::PARAM_STR);
            
            
            // La contraseña puede ser NULL si no se actualiza
            $usuPass = !empty($datos['USU_pass']) ? $datos['USU_pass'] : null;
            $stmt->bindParam(':usuPass', $usuPass, PDO::PARAM_STR);
            

            $stmt->execute();

            // leer resultados de salida
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res['resultado'] != 1) {
                // error del procedimiento
                return [
                    "success" => false,
                    "message" => $res['mensaje']
                ];
            }

            return [
                "success" => true,
                "message" => $res['mensaje']
            ];
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
