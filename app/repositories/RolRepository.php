<?php
namespace App\Repositories;

use App\Config\Database;
use App\Models\Persona;
use App\Models\Usuario;
use PDO;
use PDOException;

class RolRepository {
    private $db;
    private $resultado;
    private $mensaje;
    private $rolId;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function crearRol($datos)
    {
        try {

            // SQL completo usando variables locales para manejar OUTPUT
            $sql = "
            DECLARE @resultado INT, @mensaje VARCHAR(500), @rol_id INT;

            EXEC SP_ROL_CREAR 
                @ROL_codigo = :codigo,
                @ROL_nombre = :nombre,
                @ROL_descripcion = :descripcion,
                @ROL_nivel = :nivel,
                @SISTEMA_id = :sistema_id,
                @MODULOS_ids = :modulos_ids,
                @p_resultado = @resultado OUTPUT,
                @p_mensaje = @mensaje OUTPUT,
                @p_rol_id = @rol_id OUTPUT;

            SELECT @resultado AS resultado, @mensaje AS mensaje, @rol_id AS rol_id;
            ";

            $stmt = $this->db->prepare($sql);

            // Bind normales
            $stmt->bindParam(':codigo', $datos['codigo'], PDO::PARAM_STR);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            $stmt->bindParam(':nivel', $datos['nivel'], PDO::PARAM_INT);
            $stmt->bindParam(':sistema_id', $datos['sistema_id'], PDO::PARAM_INT);
            $stmt->bindParam(':modulos_ids', $datos['modulos_ids'], PDO::PARAM_STR);

            $stmt->execute();

            // Recuperar los resultados del SELECT final
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'resultado' => $result['resultado'],
                'mensaje'   => $result['mensaje'],
                'rol_id'    => $result['rol_id']
            ];
        } catch (PDOException $e) {
            error_log("Error SP_ROL_CREAR: " . $e->getMessage());
            throw new \Exception("Error al crear el rol: " . $e->getMessage());
        }
    }


    public function actualizarRol($datos) {
        try {
            $sql = "
            DECLARE @resultado INT, @mensaje VARCHAR(500);
            EXEC SP_ROL_ACTUALIZAR 
                    @ROL_id = :rol_id,
                    @ROL_codigo = :codigo,
                    @ROL_nombre = :nombre,
                    @ROL_descripcion = :descripcion,
                    @ROL_nivel = :nivel,
                    @SISTEMA_id = :sistema_id,
                    @MODULOS_ids = :modulos_ids,
                    @p_resultado = @resultado OUTPUT,
                    @p_mensaje = @mensaje OUTPUT;
            SELECT @resultado AS resultado, @mensaje AS mensaje;
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':rol_id', $datos['rol_id'], PDO::PARAM_INT);
            $stmt->bindParam(':codigo', $datos['codigo'], PDO::PARAM_STR);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            $stmt->bindParam(':nivel', $datos['nivel'], PDO::PARAM_INT);
            $stmt->bindParam(':sistema_id', $datos['sistema_id'], PDO::PARAM_INT);
            $stmt->bindParam(':modulos_ids', $datos['modulos_ids'], PDO::PARAM_STR);
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'resultado' => $result['resultado'],
                'mensaje'   => $result['mensaje']
            ];
        } catch (PDOException $e) {
            throw new \Exception("Error al actualizar rol: " . $e->getMessage());
        }
    }

    public function listarRoles($incluirInactivos = false) {
        try {
            $sql = "EXEC SP_ROL_LISTAR @INCLUIR_inactivos = :incluir";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':incluir', $incluirInactivos, PDO::PARAM_BOOL);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception("Error al listar roles: " . $e->getMessage());
        }
    }

    public function obtenerRol($rolId) {
        try {
            $sql = "EXEC SP_ROL_OBTENER @ROL_id = :rol_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':rol_id', $rolId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception("Error al obtener rol: " . $e->getMessage());
        }
    }

    public function listarModulos($sistemaId = null) {
        try {
            $sql = "EXEC SP_MODULO_LISTAR @SISTEMA_id = :sistema_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':sistema_id', $sistemaId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception("Error al listar módulos: " . $e->getMessage());
        }
    }

    public function eliminarRol($rolId, $eliminadoPor) {
        try {
            $sql = "
            DECLARE @resultado INT, @mensaje VARCHAR(500);
            EXEC SP_ROL_ELIMINAR 
                    @ROL_id = :rol_id,
                    @ELIMINADO_por = :eliminado_por,
                    @p_resultado = @resultado OUTPUT,
                    @p_mensaje = @mensaje OUTPUT;
            SELECT @resultado AS resultado, @mensaje AS mensaje;
                    ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':rol_id', $rolId, PDO::PARAM_INT);
            $stmt->bindParam(':eliminado_por', $eliminadoPor, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'resultado' => $result['resultado'],
                'mensaje'   => $result['mensaje']
            ];
        } catch (PDOException $e) {
            throw new \Exception("Error al eliminar rol: " . $e->getMessage());
        }
    }
}