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
            $_SESSION['usuario'] = $resultado['usuario'];

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


    //🔹 Enviar respuesta JSON
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
