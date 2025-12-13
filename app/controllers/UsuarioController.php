<?php

namespace App\Controllers;

use App\Services\UsuarioService;
use App\Helpers\InputValidator;
use App\Middleware\RateLimiter;

class UsuarioController
{
    private $usuarioService;

    public function __construct()
    {
        $this->usuarioService = new UsuarioService();
    }

    /**
     * LOGIN: Valida usuario y contraseña, obtiene datos básicos
     * Luego solicita validar CUI (segunda fase)
     */
    public function login()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception("Método no permitido");
            }

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            // Sanitizar entrada
            $nombreUsuario = InputValidator::sanitizeString($data['nombreUsuario'] ?? '');
            $password = $data['password'] ?? ''; // No sanitizar password

            if (empty($nombreUsuario) || empty($password)) {
                throw new \Exception("Usuario y contraseña son requeridos");
            }

            $resultado = $this->usuarioService->login($nombreUsuario, $password);

            if (!$resultado['success']) {
                // Registrar intento fallido para rate limiting
                RateLimiter::hit('login', $nombreUsuario);

                $this->jsonResponse([
                    'success' => false,
                    'message' => $resultado['mensaje']
                ], 401);
                return;
            }

            // Limpiar intentos fallidos después de éxito
            RateLimiter::clear('login', $nombreUsuario);

            // SEGURIDAD: En lugar de guardar la contraseña en sesión,
            // generamos un token temporal cifrado que expira en 5 minutos
            $authToken = bin2hex(random_bytes(32));
            $encryptedData = $this->encryptAuthData($nombreUsuario, $password);

            $_SESSION['nombreUsuario'] = $nombreUsuario;
            $_SESSION['auth_token'] = $authToken;
            $_SESSION['auth_data'] = $encryptedData;
            $_SESSION['auth_expiry'] = time() + 300; // 5 minutos para completar CUI
            $_SESSION['requireCUI'] = true;

            $this->jsonResponse([
                'success' => true,
                'message' => $resultado['mensaje'],
                'data' => [
                    'requireCUI' => true,
                    'usuarioLogin' => $nombreUsuario
                ]
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cifra los datos de autenticación temporales
     * 
     * @param string $username
     * @param string $password
     * @return string Datos cifrados en base64
     */
    private function encryptAuthData(string $username, string $password): string
    {
        $data = json_encode(['u' => $username, 'p' => $password, 't' => time()]);
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Descifra los datos de autenticación temporales
     * 
     * @param string $encryptedData
     * @return array|null Datos descifrados o null si falla
     */
    private function decryptAuthData(string $encryptedData): ?array
    {
        try {
            $data = base64_decode($encryptedData);
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            $key = $this->getEncryptionKey();
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);

            if ($decrypted === false) {
                return null;
            }

            return json_decode($decrypted, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtiene la clave de cifrado (desde variable de entorno o genera una)
     * 
     * @return string
     */
    private function getEncryptionKey(): string
    {
        // Idealmente esto vendría de una variable de entorno
        $key = $_ENV['APP_ENCRYPTION_KEY'] ?? null;

        if ($key === null) {
            // Generar clave única basada en la instalación
            $keyFile = __DIR__ . '/../../.encryption_key';
            if (file_exists($keyFile)) {
                $key = file_get_contents($keyFile);
            } else {
                $key = bin2hex(random_bytes(32));
                file_put_contents($keyFile, $key);
            }
        }

        return hash('sha256', $key, true);
    }


    /**
     * VALIDAR CUI: Comprueba el CUI ingresado por el usuario
     * Si es correcto, completa la autenticación y retorna datos del usuario
     */
    public function validarCUI()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception("Método no permitido");
            }

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            // Verificar que la sesión de pre-autenticación no haya expirado
            $authExpiry = $_SESSION['auth_expiry'] ?? 0;
            if (time() > $authExpiry) {
                // Limpiar sesión expirada
                unset($_SESSION['auth_data'], $_SESSION['auth_token'], $_SESSION['auth_expiry']);
                throw new \Exception("Sesión expirada. Por favor inicie sesión nuevamente.");
            }

            $nombreUsuario = $_SESSION['nombreUsuario'] ?? null;
            $encryptedData = $_SESSION['auth_data'] ?? null;
            $cui = InputValidator::sanitizeString($data['cui'] ?? '');

            // Validar CUI
            if (!InputValidator::validateCUI($cui)) {
                throw new \Exception("CUI debe ser un dígito numérico");
            }

            if (!$nombreUsuario || !$encryptedData) {
                throw new \Exception("Sesión incompleta para validar CUI");
            }

            // Descifrar datos de autenticación
            $authData = $this->decryptAuthData($encryptedData);
            if (!$authData || !isset($authData['p'])) {
                throw new \Exception("Error de autenticación. Por favor inicie sesión nuevamente.");
            }

            $password = $authData['p'];

            $resultado = $this->usuarioService->validarCUI($nombreUsuario, $password, $cui);

            // Limpiar datos sensibles de la sesión después de validación exitosa
            unset($_SESSION['auth_data'], $_SESSION['auth_token'], $_SESSION['auth_expiry']);

            // Guardar sesión completa
            $_SESSION['usuarioID'] = $resultado['usuario']['USU_id'] ?? null;
            $_SESSION['rolID'] = $resultado['usuario']['ROL_id'] ?? null;
            $_SESSION['authenticated'] = true;
            $_SESSION['requireCUI'] = false;
            $_SESSION['login_time'] = time();

            $permisos = \App\Helpers\Permisos::obtenerPermisos($_SESSION['usuarioID']);
            $_SESSION['permisos'] = $permisos;

            // Verificar si requiere cambio de password
            $requiereCambioPassword = $resultado['usuario']['USU_requiere_cambio_password'] ?? 0;
            $diasDesdeCambio = $resultado['usuario']['DIAS_DESDE_CAMBIO_PASSWORD'] ?? 0;

            $respuesta = [
                'success' => true,
                'message' => $resultado['mensaje'] ?? 'Sin mensaje',
                'data' => [
                    'usuario' => $resultado['usuario'] ?? [],
                    'permisos' => $permisos,
                    'requiere_cambio_password' => (bool)$requiereCambioPassword,
                    'dias_desde_cambio' => (int)$diasDesdeCambio,
                    'dias_restantes' => max(0, 30 - (int)$diasDesdeCambio)
                ]
            ];

            $this->jsonResponse($respuesta);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Nuevo método: Cambiar password
     */
    public function cambiarPassword()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception("Método no permitido");
            }

            // Verificar que el usuario esté autenticado
            if (!isset($_SESSION['usuarioID'])) {
                throw new \Exception("No hay sesión activa");
            }

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $passwordActual = $data['passwordActual'] ?? '';
            $passwordNueva = $data['passwordNueva'] ?? '';
            $usuarioId = $_SESSION['usuarioID'];

            if (empty($passwordActual) || empty($passwordNueva)) {
                throw new \Exception("Todos los campos son requeridos");
            }

            // Validar seguridad de la nueva password
            if (!$this->validarPasswordSegura($passwordNueva)) {
                throw new \Exception("La contraseña no cumple con los requisitos de seguridad");
            }

            $resultado = $this->usuarioService->cambiarPasswordObligatorio($usuarioId, $passwordActual, $passwordNueva);

            // Obtener datos actualizados del usuario
            $usuarioActualizado = $this->usuarioService->obtenerUsuarioPorId($usuarioId);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente',
                'data' => [
                    'actualizado' => $resultado['actualizado'],
                    'fecha_actualizacion' => $resultado['fecha_actualizacion'],
                    'usuario' => $usuarioActualizado
                ]
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Validar que la password cumpla con requisitos de seguridad
     */
    private function validarPasswordSegura($password)
    {
        // Mínimo 8 caracteres
        if (strlen($password) < 8) {
            return false;
        }

        // Al menos una mayúscula
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Al menos una minúscula
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Al menos un número
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Al menos un carácter especial
        if (!preg_match('/[@$!%*?&#]/', $password)) {
            return false;
        }

        return true;
    }


    /**
     * LOGOUT: Cierra sesión del usuario
     */
    public function logout()
    {
        session_destroy();

        $this->jsonResponse([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    /**
     * Obtener datos del usuario actual desde la sesión
     */
    public function obtenerUsuarioActual()
    {
        try {

            if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
                throw new \Exception("Usuario no autenticado");
            }

            if (!isset($_SESSION['usuarioID'])) {
                throw new \Exception("No se encontró el ID del usuario en la sesión");
            }

            $usuarioId = $_SESSION['usuarioID'];
            $usuario = $this->usuarioService->obtenerUsuarioPorId($usuarioId);

            if (!$usuario) {
                throw new \Exception("Usuario no encontrado");
            }

            // 🔹 Enviar respuesta compatible con el frontend
            $this->jsonResponse([
                'success' => true,
                'message' => 'Usuario obtenido correctamente',
                'data' => $usuario
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }


    // 🔹 Método para crear usuario
    public function crearUsuario()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $data = $input['data'] ?? $input; // si viene dentro de 'data', la saca

            $result = $this->usuarioService->crearUsuario($data);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'data' => $result
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 200);
        }
    }


    // Método para eliminar usuario
    public function eliminarUsuario()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['usuario_id'])) {
            return $this->jsonResponse(["error" => "Debe proporcionar el ID del usuario"], 400);
        }

        try {
            $this->usuarioService->eliminarUsuario($data['usuario_id']);
            $this->jsonResponse([
                "success" => true,
                "message" => "Usuario eliminado correctamente"
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                "success" => false,
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function obtenerDniYPassword()
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $nombreUsuario = $data['nombreUsuario'] ?? null;

            if (empty($nombreUsuario)) {
                throw new \Exception("El nombre de usuario es requerido");
            }

            $resultado = $this->usuarioService->obtenerDni($nombreUsuario);

            if (!$resultado) {
                echo json_encode([
                    "success" => false,
                    "message" => "No se encontró el usuario"
                ]);
                return;
            }

            // Obtener la contraseña desde la sesión
            $passwordSesion = $_SESSION['password'] ?? null;

            if (empty($passwordSesion)) {
                throw new \Exception("No se encontró la contraseña en la sesión");
            }

            // Armar la respuesta combinada
            $respuesta = [
                "success" => true,
                "data" => [
                    "DNI" => $resultado['DNI'],
                    "password" => $passwordSesion
                ]
            ];

            echo json_encode($respuesta);
        } catch (\Throwable $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    /**
     * Listar todos los usuarios
     */
    public function listarUsuarios()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        try {
            $usuarios = $this->usuarioService->listarUsuarios();

            echo json_encode([
                'success' => true,
                'message' => 'Usuarios obtenidos correctamente',
                'data' => $usuarios
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al listar usuarios: ' . $e->getMessage()
            ]);
        }
    }

    /* Obtener roles de usuario */
    public function obtenerRoles()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        try {
            $roles = $this->usuarioService->obtenerRoles();

            echo json_encode([
                'success' => true,
                'message' => 'Roles obteniedo correctamente',
                'data' => $roles
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener roles: ' . $e->getMessage()
            ]);
        }
    }

    public function obtenerTipoPersonal()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        try {
            $tipos = $this->usuarioService->obtenerTipoPersonal();

            echo json_encode([
                'success' => true,
                'message' => 'Tipo de personal obtenido correctamente',
                'data' => $tipos
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener los tipos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener usuario por ID para edición
     */
    public function obtenerUsuario()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        try {
            // Obtener ID desde query parameter
            $usuarioId = $_GET['id'] ?? null;

            if (empty($usuarioId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de usuario es requerido'
                ]);
                return;
            }

            $usuario = $this->usuarioService->obtenerUsuarioPorId($usuarioId);

            echo json_encode([
                'success' => true,
                'message' => 'Usuario obtenido correctamente',
                'data' => $usuario
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener usuario: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar usuario
     */
    public function actualizarUsuario()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['data'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos no proporcionados'
                ]);
                return;
            }

            $datos = $input['data'];
            error_log("Datos recibidos para actualizar usuario: " . print_r($datos, true));

            $response = $this->usuarioService->actualizarUsuario($datos);
            error_log("Respuesta de actualización de usuario: " . print_r($response, true));

            echo json_encode($response);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage()
            ]);
        }
    }

    public function actualizarPassword()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['data'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos no proporcionados'
                ]);
                return;
            }

            $datos = $input['data'];

            $response = $this->usuarioService->actualizarPassword($datos);

            echo json_encode($response);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage()
            ]);
        }
    }

    //Enviar respuesta JSON
    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
