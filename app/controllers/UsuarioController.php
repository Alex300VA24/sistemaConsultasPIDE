<?php
namespace App\Controllers;

use App\Services\UsuarioService;
use App\Helpers\Debug;

class UsuarioController {
    private $usuarioService;
    
    public function __construct() {
        $this->usuarioService = new UsuarioService();
    }
    
    /**
     * 🔹 LOGIN: Valida usuario y contraseña, obtiene datos básicos
     * Luego solicita validar CUI (segunda fase)
     */
    public function login() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception("Método no permitido");
            }

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $nombreUsuario = $data['nombreUsuario'] ?? '';
            $password = $data['password'] ?? '';

            $resultado = $this->usuarioService->login($nombreUsuario, $password);

            $_SESSION['nombreUsuario'] = $nombreUsuario;
            $_SESSION['password'] = $password;
            $_SESSION['requireCUI'] = true;


            if (!$resultado['valido']) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $resultado['mensaje']
                ], 401);
                return;
            }

            // Login válido, pero aún no validó CUI
            session_start();
            $_SESSION['nombreUsuario'] = $nombreUsuario;
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
     * 🔹 VALIDAR CUI: Comprueba el CUI ingresado por el usuario
     * Si es correcto, completa la autenticación y retorna datos del usuario
     */
    public function validarCUI() {
        try {
            $debug = new Debug();
            $debug->log_debug("Inicio validarCUI()");
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $debug->log_debug("Método no permitido", $_SERVER['REQUEST_METHOD']);
                throw new \Exception("Método no permitido");
            }

            session_start();
            $debug->log_debug("Sesión iniciada", $_SESSION);

            $json = file_get_contents('php://input');
            $debug->log_debug("JSON recibido", $json);

            $data = json_decode($json, true);
            $debug->log_debug("JSON decodificado", $data);

            $nombreUsuario = $_SESSION['nombreUsuario'] ?? null;
            $password = $_SESSION['password'] ?? null;
            $cui = $data['cui'] ?? '';

            $debug->log_debug("Variables preparadas", [
                'nombreUsuario' => $nombreUsuario,
                'password' => $password,
                'cui' => $cui
            ]);

            if (!$nombreUsuario || !$password) {
                $debug->log_debug("Sesión incompleta", $_SESSION);
                throw new \Exception("Sesión incompleta para validar CUI");
            }

            $resultado = $this->usuarioService->validarCUI($nombreUsuario, $password, $cui);
            $debug->log_debug("Resultado del servicio", $resultado);

            // Guardar sesión completa
            $_SESSION['usuarioID'] = $resultado['usuario']['USU_id'] ?? null;
            $_SESSION['authenticated'] = true;
            $_SESSION['requireCUI'] = false;
            $debug->log_debug("Sesión actualizada", $_SESSION);

            $respuesta = [
                'success' => true,
                'message' => $resultado['mensaje'] ?? 'Sin mensaje',
                'data' => $resultado['usuario'] ?? []
            ];
            $debug->log_debug("Respuesta JSON", $respuesta);

            $this->jsonResponse($respuesta);

        } catch (\Exception $e) {
            $debug->log_debug("Error capturado", $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }



    /**
     * 🔹 LOGOUT: Cierra sesión del usuario
     */
    public function logout() {
        session_start();
        session_destroy();

        $this->jsonResponse([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    /**
     * Obtener datos del usuario actual desde la sesión
     */
    public function obtenerUsuarioActual() {
        try {
            session_start();

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
    public function crearUsuario() {
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
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // 🔹 Método para eliminar usuario
    public function eliminarUsuario() {
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

            $resultado = $this->usuarioService->obtenerDniYPassword($nombreUsuario);

            if (!$resultado) {
                echo json_encode([
                    "success" => false,
                    "message" => "No se encontró el usuario"
                ]);
                return;
            }

            echo json_encode([
                "success" => true,
                "data" => $resultado
            ]);

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
    public function actualizarUsuario(){
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

            $this->usuarioService->actualizarUsuario($datos);

            echo json_encode([
                'success' => true,
                'message' => 'Usuario actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage()
            ]);
        }
    }

    


    //🔹 Enviar respuesta JSON
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
