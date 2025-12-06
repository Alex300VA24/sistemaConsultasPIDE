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
    
    // Service: validar la estructura devuelta por el repo
    public function validarCUI($nombreUsuario, $password, $cui) {

        if (empty($cui)) {
            throw new \Exception("Es requerido el CUI");
        }

        if (strlen($cui) !== 1) {
            throw new \Exception("El CUI debe ser de 1 dígito");
        }

        $usuario = $this->usuarioRepository->validarCUI($nombreUsuario, $password, $cui);

        // Usuario inválido
        if ($usuario === null) {
            throw new \Exception("CUI incorrecto");
        }
        if (is_array($usuario) && isset($usuario['valido']) && $usuario['valido'] == false) {
            throw new \Exception($usuario['mensaje'] ?? 'CUI incorrecto');
        }

        // ===============================
        //   AQUI AGREGAS LA SESIÓN
        // ===============================
        $usuarioData = $usuario['usuario'];   // datos que vienen del SP

        $_SESSION['ROL_nombre'] = $usuarioData['ROL_nombre']; 
        $_SESSION['usuario_id'] = $usuarioData['USU_id'];
        $_SESSION['usuario'] = $usuarioData; // opcional

        return $usuario;
    }





    public function crearUsuario($data) {
        try {
            // Validaciones mínimas
            if (empty($data['usuLogin']) || empty($data['usuPass'])) {
                throw new \Exception("El login y la contraseña son obligatorios");
            }

            // Llamada al repositorio
            return $this->usuarioRepository->crearUsuario($data);

        } catch (\Throwable $e) {
            // Crear carpeta de logs si no existe
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }

            // Contenido del log
            $logFile = $logDir . '/error_crear_usuario.txt';
            $errorMsg = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . PHP_EOL;
            $errorMsg .= "Datos enviados:" . PHP_EOL . print_r($data, true) . PHP_EOL;
            $errorMsg .= str_repeat("-", 60) . PHP_EOL;

            // Escribir log
            file_put_contents($logFile, $errorMsg, FILE_APPEND);

            // Re-lanzar la excepción para que el controlador la maneje
            throw new \Exception($e->getMessage());

        }
    }

    /**
     * Listar todos los usuarios
     */
    public function listarUsuarios()
    {
        return $this->usuarioRepository->listarUsuarios();
    }

    public function obtenerRoles(){
        return $this->usuarioRepository->obtenerRoles();
    }

    public function obtenerTipoPersonal(){
        return $this->usuarioRepository->obtenerTipoPersonal();
    }

    /**
     * Obtener datos del usuario para edición
     */
    public function obtenerUsuarioPorId($usuarioId)
    {
        if (empty($usuarioId) || !is_numeric($usuarioId)) {
            throw new \Exception('ID de usuario inválido');
        }

        $usuario = $this->usuarioRepository->obtenerUsuarioPorId($usuarioId);

        if (!$usuario) {
            throw new \Exception('Usuario no encontrado');
        }

        return $usuario;
    }


    public function eliminarUsuario($usuarioId) {
        if (!$usuarioId) {
            throw new \Exception("Debe proporcionar un ID válido");
        }

        return $this->usuarioRepository->eliminarUsuario($usuarioId);
    }

    public function obtenerDni($nombreUsuario)
    {
        return $this->usuarioRepository->obtenerDni($nombreUsuario);
    }

    /**
     * Actualizar usuario
     */
    public function actualizarUsuario($datos)
    {
        // Validaciones
        $this->validarDatosUsuario($datos, true);

        // Si hay contraseña nueva, hashearla
        if (!empty($datos['USU_pass'])) {
            $datos['USU_pass'] = $datos['USU_pass'];
        } else {
            $datos['USU_pass'] = null; // No actualizar contraseña
        }

        // Actualizar en BD
        $response = $this->usuarioRepository->actualizarUsuario($datos);

        return $response;
    }

    public function actualizarPassword($datos)
    {
        // Validaciones
        $this->validarPassword($datos, true);

        // Si hay contraseña nueva, hashearla
        if (!empty($datos['USU_pass'])) {
            $datos['USU_pass'] = $datos['USU_pass'];
        } else {
            $datos['USU_pass'] = null; // No actualizar contraseña
        }

        // Actualizar en BD
        $response = $this->usuarioRepository->actualizarPassword($datos);

        return $response;
    }

    /**
     * Validar datos del usuario
     */
    private function validarDatosUsuario($datos, $esActualizacion = false)
    {
        $errores = [];

        // Validar ID en actualizaciones
        if ($esActualizacion) {
            if (empty($datos['USU_id'])) {
                $errores[] = 'ID de usuario es requerido';
            }
            if (empty($datos['PER_id'])) {
                $errores[] = 'ID de persona es requerido';
            }
        }

        // Validar tipo de persona
        if (empty($datos['PER_tipo'])) {
            $errores[] = 'Tipo de persona es requerido';
        }

        // Validar documento
        if (empty($datos['PER_documento_tipo'])) {
            $errores[] = 'Tipo de documento es requerido';
        }
        if (empty($datos['PER_documento_num'])) {
            $errores[] = 'Número de documento es requerido';
        }

        // Validar nombres
        if (empty($datos['PER_nombre'])) {
            $errores[] = 'Nombres son requeridos';
        }
        
        if (empty($datos['PER_apellido_pat'])) {
            $errores[] = 'Apellido paterno es requerido';
        }

        // Validar sexo
        if (empty($datos['PER_sexo'])) {
            $errores[] = 'Sexo es requerido';
        }

        // Validar login
        if (empty($datos['USU_login'])) {
            $errores[] = 'Login/Usuario es requerido';
        }

        // Validar email si se proporciona
        if (!empty($datos['PER_email']) && !filter_var($datos['PER_email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Email inválido';
        }

        if (!empty($errores)) {
            throw new \Exception(implode(', ', $errores));
        }
    }

    private function validarPassword($datos, $esActualizacion = false)
    {
        $errores = [];

        // Validar ID en actualizaciones
        if ($esActualizacion) {
            if (empty($datos['USU_id'])) {
                $errores[] = 'ID de usuario es requerido';
            }
        }


        if (!empty($errores)) {
            throw new \Exception(implode(', ', $errores));
        }
    }
    
}