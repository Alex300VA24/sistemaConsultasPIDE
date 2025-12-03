<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class ModuloRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crear un nuevo módulo
     */
    public function crear($datos) {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // 1. Insertar el módulo
            $sql = "INSERT INTO modulo (
                        MOD_sistema_id, 
                        MOD_padre_id, 
                        MOD_codigo, 
                        MOD_nombre, 
                        MOD_descripcion, 
                        MOD_url, 
                        MOD_icono, 
                        MOD_orden, 
                        MOD_nivel, 
                        MOD_activo
                    ) VALUES (
                        :sistema_id, 
                        :padre_id, 
                        :codigo, 
                        :nombre, 
                        :descripcion, 
                        :url, 
                        :icono, 
                        :orden, 
                        :nivel, 
                        :activo
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':sistema_id' => $datos['sistema_id'],
                ':padre_id' => $datos['padre_id'],
                ':codigo' => $datos['codigo'],
                ':nombre' => $datos['nombre'],
                ':descripcion' => $datos['descripcion'],
                ':url' => $datos['url'],
                ':icono' => $datos['icono'],
                ':orden' => $datos['orden'],
                ':nivel' => $datos['nivel'],
                ':activo' => $datos['activo']
            ]);
            
            $moduloId = $this->db->lastInsertId();
            
            // 2. Asignar automáticamente al rol ADMIN
            $this->asignarModuloARolAdmin($moduloId, $datos['sistema_id']);
            
            // Confirmar transacción
            $this->db->commit();
            
            return $moduloId;
            
        } catch (\PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            error_log("Error al crear módulo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Asignar un módulo al rol ADMIN automáticamente
     */
    private function asignarModuloARolAdmin($moduloId, $sistemaId) {
        try {
            // Buscar el ROL_id del ADMIN
            $sqlRol = "SELECT ROL_id FROM ROL WHERE ROL_codigo = 'ADMIN' AND ROL_activo = 1";
            $stmtRol = $this->db->query($sqlRol);
            $rolAdmin = $stmtRol->fetch(PDO::FETCH_ASSOC);
            
            if (!$rolAdmin) {
                error_log("ADVERTENCIA: No se encontró el rol ADMIN activo");
                return false;
            }
            
            $rolId = $rolAdmin['ROL_id'];
            
            // Verificar si ya existe la asignación (por si acaso)
            $sqlCheck = "SELECT COUNT(*) as existe 
                        FROM rol_modulo 
                        WHERE ROM_rol_id = :rol_id 
                        AND ROM_modulo_id = :modulo_id 
                        AND ROM_sistema_id = :sistema_id";
            
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([
                ':rol_id' => $rolId,
                ':modulo_id' => $moduloId,
                ':sistema_id' => $sistemaId
            ]);
            
            $existe = $stmtCheck->fetch(PDO::FETCH_ASSOC)['existe'];
            
            if ($existe > 0) {
                error_log("El módulo ya está asignado al rol ADMIN");
                return true;
            }
            
            // Insertar en rol_modulo
            $sqlInsert = "INSERT INTO rol_modulo (
                            ROM_rol_id,
                            ROM_sistema_id,
                            ROM_modulo_id,
                            ROM_fecha_asignacion
                        ) VALUES (
                            :rol_id,
                            :sistema_id,
                            :modulo_id,
                            GETDATE()
                        )";
            
            $stmtInsert = $this->db->prepare($sqlInsert);
            $resultado = $stmtInsert->execute([
                ':rol_id' => $rolId,
                ':sistema_id' => $sistemaId,
                ':modulo_id' => $moduloId
            ]);
            
            if ($resultado) {
                error_log("✅ Módulo {$moduloId} asignado automáticamente al rol ADMIN");
            }
            
            return $resultado;
            
        } catch (\PDOException $e) {
            error_log("Error al asignar módulo al rol ADMIN: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualizar un módulo
     */
    public function actualizar($moduloId, $datos) {
        try {
            $sql = "UPDATE modulo SET
                        MOD_padre_id = :padre_id,
                        MOD_codigo = :codigo,
                        MOD_nombre = :nombre,
                        MOD_descripcion = :descripcion,
                        MOD_url = :url,
                        MOD_icono = :icono,
                        MOD_orden = :orden,
                        MOD_nivel = :nivel
                    WHERE MOD_id = :modulo_id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':modulo_id' => $moduloId,
                ':padre_id' => $datos['padre_id'],
                ':codigo' => $datos['codigo'],
                ':nombre' => $datos['nombre'],
                ':descripcion' => $datos['descripcion'],
                ':url' => $datos['url'],
                ':icono' => $datos['icono'],
                ':orden' => $datos['orden'],
                ':nivel' => $datos['nivel']
            ]);
        } catch (\PDOException $e) {
            error_log("Error al actualizar módulo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Listar todos los módulos con información del padre
     */
    public function listarTodos() {
        try {
            $sql = "SELECT 
                        m.*,
                        p.MOD_nombre as padre_nombre
                    FROM modulo m
                    LEFT JOIN modulo p ON m.MOD_padre_id = p.MOD_id
                    ORDER BY m.MOD_orden ASC, m.MOD_nivel ASC";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error al listar módulos: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener un módulo por ID
     */
    public function obtenerPorId($moduloId) {
        try {
            $sql = "SELECT * FROM modulo WHERE MOD_id = :modulo_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':modulo_id' => $moduloId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error al obtener módulo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Eliminar un módulo
     */
    public function eliminar($moduloId) {
        try {
            // Primero eliminar las relaciones con roles
            $sqlRoles = "DELETE FROM rol_modulo WHERE ROM_modulo_id = :modulo_id";
            $stmtRoles = $this->db->prepare($sqlRoles);
            $stmtRoles->execute([':modulo_id' => $moduloId]);

            // Luego eliminar el módulo
            $sql = "DELETE FROM modulo WHERE MOD_id = :modulo_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':modulo_id' => $moduloId]);
        } catch (\PDOException $e) {
            error_log("Error al eliminar módulo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cambiar el estado de un módulo
     */
    public function cambiarEstado($moduloId, $estado) {
        try {
            $sql = "UPDATE modulo SET MOD_activo = :estado WHERE MOD_id = :modulo_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':modulo_id' => $moduloId,
                ':estado' => $estado
            ]);
        } catch (\PDOException $e) {
            error_log("Error al cambiar estado del módulo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verificar si existe un código de módulo
     */
    public function existeCodigo($codigo, $moduloIdExcluir = null) {
        try {
            $sql = "SELECT COUNT(*) FROM modulo WHERE MOD_codigo = :codigo";
            
            if ($moduloIdExcluir) {
                $sql .= " AND MOD_id != :modulo_id";
            }

            $stmt = $this->db->prepare($sql);
            $params = [':codigo' => $codigo];
            
            if ($moduloIdExcluir) {
                $params[':modulo_id'] = $moduloIdExcluir;
            }

            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Error al verificar código de módulo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verificar si un módulo tiene hijos
     */
    public function tieneHijos($moduloId) {
        try {
            $sql = "SELECT COUNT(*) FROM modulo WHERE MOD_padre_id = :modulo_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':modulo_id' => $moduloId]);
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Error al verificar hijos del módulo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Método alternativo para obtener módulos
     * Este método obtiene TODOS los módulos activos si no se puede determinar por permisos
     */
    public function obtenerModulosPorUsuario($usuarioId) {
        try {
            
            // Retornar todos los módulos activos
            // En producción, deberías ajustar esto según tu lógica de permisos
            $sql = "SELECT DISTINCT
                        m.MOD_id,
                        m.MOD_padre_id,
                        m.MOD_codigo,
                        m.MOD_nombre,
                        m.MOD_descripcion,
                        m.MOD_url,
                        m.MOD_icono,
                        m.MOD_orden,
                        m.MOD_nivel
                    FROM modulo m
                    WHERE m.MOD_activo = 1
                    ORDER BY m.MOD_orden ASC";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error en método alternativo: " . $e->getMessage());
            return [];
        }
    }
}