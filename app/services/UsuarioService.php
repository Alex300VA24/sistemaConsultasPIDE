<?php
namespace App\Services;

use App\Repositories\UsuarioRepository;

class UsuarioService {
    private $usuarioRepository;
    
    public function __construct() {
        $this->usuarioRepository = new UsuarioRepository();
    }
    
    public function login($nombreUsuario, $password) {
        if (empty($nombreUsuario) || empty($password)) {
            throw new \Exception("Usuario y contraseña son requeridos");
        }
        
        $usuario = $this->usuarioRepository->login($nombreUsuario, $password);
        
        if ($usuario === null) {
            throw new \Exception("Credenciales incorrectas");
        }
        
        return $usuario;
    }
    
    public function validarCUI($nombreUsuario, $password, $cui) {
        if (empty($cui)) {
            throw new \Exception("Es requerido el CUI");
        }
        
        if (strlen($cui) !== 1) {
            throw new \Exception("El CUI debe ser de 1 dígito");
        }
        
        $usuario = $this->usuarioRepository->validarCUI($nombreUsuario, $password, $cui);
        
        if ($usuario === null) {
            throw new \Exception("CUI incorrecto");
        }
        
        return $usuario;
    }
    
}