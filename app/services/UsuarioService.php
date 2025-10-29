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

    public function crearUsuario($data) {
        try {
            // ✅ Validaciones mínimas
            if (empty($data['usuLogin']) || empty($data['usuPass'])) {
                throw new \Exception("El login y la contraseña son obligatorios");
            }

            // ✅ Llamada al repositorio
            return $this->usuarioRepository->crearUsuario($data);

        } catch (\Throwable $e) {
            // 🧾 Crear carpeta de logs si no existe
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }

            // 🧠 Contenido del log
            $logFile = $logDir . '/error_crear_usuario.txt';
            $errorMsg = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . PHP_EOL;
            $errorMsg .= "Datos enviados:" . PHP_EOL . print_r($data, true) . PHP_EOL;
            $errorMsg .= str_repeat("-", 60) . PHP_EOL;

            // 🖊️ Escribir log
            file_put_contents($logFile, $errorMsg, FILE_APPEND);

            // Re-lanzar la excepción para que el controlador la maneje
            throw new \Exception("Error al crear usuario. Revisa logs/error_crear_usuario.txt para más detalles.");
        }
    }


    public function eliminarUsuario($usuarioId) {
        if (!$usuarioId) {
            throw new \Exception("Debe proporcionar un ID válido");
        }

        return $this->usuarioRepository->eliminarUsuario($usuarioId);
    }

    public function obtenerDniYPassword($nombreUsuario)
    {
        return $this->usuarioRepository->obtenerDniYPassword($nombreUsuario);
    }
    
}