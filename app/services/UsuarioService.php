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
        
        // Primero obtenemos el usuario por nombre de usuario
        $validacion = $this->usuarioRepository->obtenerPasswordUser($nombreUsuario);
        
        if ($validacion === null) {
            throw new \Exception("Credenciales incorrectas");
        }
        
        error_log(print_r($validacion, true) ."". print_r($password, true));
        // Verificar la contraseña hasheada
        if (!password_verify($password, $validacion['USU_password_hash'])) {
            throw new \Exception("Credenciales incorrectas");
        }
        $hasheadoPass = $validacion['USU_password_hash']; 
        $usuario = $this->usuarioRepository->login($nombreUsuario, $hasheadoPass);
        
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

        // Primero validamos usuario y contraseña
        $validacion = $this->usuarioRepository->obtenerPasswordCUIUser($nombreUsuario);
        
        if ($validacion === null) {
            throw new \Exception("Credenciales incorrectas");
        }
        
        // Verificar la contraseña hasheada
        if (!password_verify($password, $validacion['USU_password_hash'])) {
            throw new \Exception("Credenciales incorrectas");
        }

        // Ahora validamos el CUI
        if ($validacion['USU_cui'] != $cui) {
            throw new \Exception("CUI incorrecto");
        }

        $hasheadoPass = $validacion["USU_password_hash"];
        $usuario = $this->usuarioRepository->validarCUI($nombreUsuario, $hasheadoPass, $cui);
        
        if (is_array($usuario) && isset($usuario['valido']) && $usuario['valido'] == false) {
            throw new \Exception($usuario['mensaje'] ?? 'CUI incorrecto');
        }

        // Establecer datos de sesión
        $usuarioData = $usuario['usuario'];
        $_SESSION['ROL_nombre'] = $usuarioData['ROL_nombre'] ?? null;
        $_SESSION['usuario_id'] = $usuarioData['USU_id'] ?? null;
        $_SESSION['usuario'] = $usuarioData;

        return $usuario;
    }
    /**
     * Nuevo método: Cambiar password del usuario (obligatorio)
     */
    public function cambiarPasswordObligatorio($usuarioId, $passwordActual, $passwordNueva) {
        // Obtener el hash actual de la base de datos
        $usuario = $this->usuarioRepository->obtenerUsuarioPorId($usuarioId);
        
        if (!$usuario) {
            throw new \Exception("Usuario no encontrado");
        }

        // Verificar que la password actual sea correcta
        if (!password_verify($passwordActual, $usuario['USU_password_hash'])) {
            throw new \Exception("La contraseña actual es incorrecta");
        }

        // Verificar que la nueva password sea diferente a la actual
        if (password_verify($passwordNueva, $usuario['USU_password_hash'])) {
            throw new \Exception("La nueva contraseña debe ser diferente a la actual");
        }

        // Hashear la nueva password
        $nuevoHash = password_hash($passwordNueva, PASSWORD_BCRYPT);

        // Actualizar en la base de datos usando el nuevo SP
        $resultado = $this->usuarioRepository->actualizarPasswordObligatorio($usuarioId, $nuevoHash);

        if (!$resultado) {
            throw new \Exception("Error al actualizar la contraseña");
        }

        return [
            'actualizado' => true,
            'fecha_actualizacion' => date('Y-m-d H:i:s')
        ];
    }


    public function crearUsuario($data) {
        try {
            // Validaciones mínimas
            if (empty($data['usuLogin']) || empty($data['usuPass'])) {
                throw new \Exception("El usuario y la contraseña son obligatorios");
            }

            // Hashear la contraseña antes de guardar
            $data['usuPass'] = password_hash($data['usuPass'], PASSWORD_DEFAULT);

            // Llamada al repositorio
            return $this->usuarioRepository->crearUsuario($data);

        } catch (\Throwable $e) {
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
    public function obtenerPasswordPorDNI($dni)
    {
        return $this->usuarioRepository->obtenerPasswordPorDNI($dni);
    }

    /**
     * Actualizar usuario
     */
    public function actualizarUsuario($datos)
    {
        $validacion = $this->usuarioRepository->obtenerPasswordUser($datos['usuUsername']);
        
        if ($validacion === null) {
            throw new \Exception("No se encontró el usuario");
        }
        $datos['usuPassActual'] = $validacion['USU_password_hash'];
        // Validaciones
        $this->validarDatosUsuario($datos, true);

        // Si hay contraseña nueva, hashearla
        if (!empty($datos['usuPass'])) {
            $datos['usuPass'] = password_hash($datos['usuPass'], PASSWORD_DEFAULT);
        } else {
            $datos['usuPass'] = null; // No actualizar contraseña
        }

        // Actualizar en BD
        $response = $this->usuarioRepository->actualizarUsuario($datos);

        return $response;
    }

    public function actualizarPassword($datos){

        // Validaciones
        $this->validarPassword($datos, true);

        $validacion = $this->usuarioRepository->obtenerPasswordPorId($datos['USU_id']);
        
        if ($validacion === null) {
            throw new \Exception("No se encontró el usuario");
        }
        $datos['USU_passActual'] = $validacion['USU_password_hash'];

        // Si hay contraseña nueva, hashearla
        if (!empty($datos['USU_pass'])) {
            $datos['USU_pass'] = password_hash($datos['USU_pass'], PASSWORD_DEFAULT);
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
        if (empty($datos['perTipo'])) {
            $errores[] = 'Tipo de persona es requerido';
        }

        // Validar documento
        if (empty($datos['perDocumentoTipo'])) {
            $errores[] = 'Tipo de documento es requerido';
        }
        if (empty($datos['perDocumentoNum'])) {
            $errores[] = 'Número de documento es requerido';
        }

        // Validar nombres
        if (empty($datos['perNombre'])) {
            $errores[] = 'Nombres son requeridos';
        }
        
        if (empty($datos['perApellidoPat'])) {
            $errores[] = 'Apellido paterno es requerido';
        }

        // Validar sexo
        if (empty($datos['perSexo'])) {
            $errores[] = 'Sexo es requerido';
        }

        // Validar login
        if (empty($datos['usuUsername'])) {
            $errores[] = 'Usuario es requerido';
        }

        // Validar email si se proporciona
        if (!empty($datos['perEmail']) && !filter_var($datos['perEmail'], FILTER_VALIDATE_EMAIL)) {
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