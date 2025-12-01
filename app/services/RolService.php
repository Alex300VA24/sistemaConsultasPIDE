<?php
namespace App\Services;

use App\Repositories\RolRepository;

class RolService {
    private $rolRepository;

    public function __construct() {
        $this->rolRepository = new RolRepository();
    }

    public function crearRol($datos) {
        // Convertir array de módulos a string separado por comas
        if (isset($datos['modulos']) && is_array($datos['modulos'])) {
            $datos['modulos_ids'] = implode(',', $datos['modulos']);
        }
        
        $resultado = $this->rolRepository->crearRol($datos);
        
        if ($resultado['resultado'] != 1) {
            throw new \Exception($resultado['mensaje']);
        }
        
        return $resultado;
    }

    public function actualizarRol($datos) {
        // Convertir array de módulos a string
        if (isset($datos['modulos']) && is_array($datos['modulos'])) {
            $datos['modulos_ids'] = implode(',', $datos['modulos']);
        }
        
        $resultado = $this->rolRepository->actualizarRol($datos);
        
        if ($resultado['resultado'] != 1) {
            throw new \Exception($resultado['mensaje']);
        }
        
        return $resultado;
    }

    public function listarRoles($incluirInactivos = false) {
        return $this->rolRepository->listarRoles($incluirInactivos);
    }

    public function obtenerRol($rolId) {
        $rol = $this->rolRepository->obtenerRol($rolId);
        
        // Convertir string de IDs a array
        if (isset($rol['MODULOS_IDS']) && $rol['MODULOS_IDS']) {
            $rol['modulos'] = explode(',', $rol['MODULOS_IDS']);
        } else {
            $rol['modulos'] = [];
        }
        
        return $rol;
    }

    public function listarModulos($sistemaId = 2) {
        return $this->rolRepository->listarModulos($sistemaId);
    }

    public function eliminarRol($rolId, $eliminadoPor) {
        $resultado = $this->rolRepository->eliminarRol($rolId, $eliminadoPor);
        
        if ($resultado['resultado'] != 1) {
            throw new \Exception($resultado['mensaje']);
        }
        
        return $resultado;
    }
}