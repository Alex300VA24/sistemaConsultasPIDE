<?php
namespace App\Controllers;

use App\Services\UsuarioService;

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
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception("Método no permitido");
            }

            session_start();
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $nombreUsuario = $_SESSION['nombreUsuario'];
            $password = $_SESSION['password'] ?? null;
            $cui = $data['cui'] ?? '';

            if (!$nombreUsuario || !$password) {
                throw new \Exception("Sesión incompleta para validar CUI");
            }

            $resultado = $this->usuarioService->validarCUI($nombreUsuario, $password, $cui);

            if (!$resultado['valido']) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $resultado['mensaje']
                ], 401);
                return;
            }

            // Si todo va bien → guardar sesión completa
            
            $_SESSION['usuarioID'] = $resultado['USU_id'];
            $_SESSION['authenticated'] = true;
            $_SESSION['requireCUI'] = false;
            //$_SESSION['usuario'] = $nombreUsuario;

            $this->jsonResponse([
                'success' => true,
                'message' => $resultado['mensaje'],
                'data' => $resultado['usuario']
            ]);

        } catch (\Exception $e) {
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


    //🔹 Enviar respuesta JSON
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
