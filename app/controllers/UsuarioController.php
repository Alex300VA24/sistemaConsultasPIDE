<?php

namespace App\Controllers;

use App\Services\Contracts\UsuarioServiceInterface;

/**
 * Controller para operaciones de usuario.
 * Ahora extiende BaseController (DRY: elimina jsonResponse duplicado).
 * Recibe UsuarioServiceInterface por inyección (DIP).
 */
class UsuarioController extends BaseController
{
    /** @var UsuarioServiceInterface */
    private $usuarioService;

    public function __construct(UsuarioServiceInterface $usuarioService)
    {
        $this->usuarioService = $usuarioService;
    }

    /**
     * LOGIN: Valida usuario y contraseña, obtiene datos básicos
     */
    public function login(): void
    {
        try {
            $this->validateMethod('POST');
            $data = $this->getJsonInput();

            $nombreUsuario = $data['nombreUsuario'] ?? '';
            $password = $data['password'] ?? '';

            $resultado = $this->usuarioService->login($nombreUsuario, $password);

            $_SESSION['nombreUsuario'] = $nombreUsuario;
            $_SESSION['password'] = $password;
            $_SESSION['requireCUI'] = true;

            error_log("Resultado del login: " . print_r($resultado, true));

            if (!$resultado['success']) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $resultado['mensaje']
                ], 401);
                return;
            }

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
     * VALIDAR CUI: Comprueba el CUI ingresado por el usuario
     */
    public function validarCUI(): void
    {
        try {
            $this->validateMethod('POST');
            $data = $this->getJsonInput();

            $nombreUsuario = $_SESSION['nombreUsuario'] ?? null;
            $password = $_SESSION['password'] ?? null;
            $cui = $data['cui'] ?? '';

            if (!$nombreUsuario || !$password) {
                throw new \Exception("Sesión incompleta para validar CUI");
            }

            $resultado = $this->usuarioService->validarCUI($nombreUsuario, $password, $cui);

            // Guardar sesión completa
            $_SESSION['usuarioID'] = $resultado['usuario']['USU_id'] ?? null;
            $_SESSION['rolID'] = $resultado['usuario']['ROL_id'] ?? null;
            $_SESSION['authenticated'] = true;
            $_SESSION['requireCUI'] = false;

            $permisos = \App\Helpers\Permisos::obtenerPermisos($_SESSION['usuarioID']);
            $_SESSION['permisos'] = $permisos;

            // Verificar si requiere cambio de password
            $requiereCambioPassword = $resultado['usuario']['USU_requiere_cambio_password'] ?? 0;
            $diasDesdeCambio = $resultado['usuario']['DIAS_DESDE_CAMBIO_PASSWORD'] ?? 0;

            $this->jsonResponse([
                'success' => true,
                'message' => $resultado['mensaje'] ?? 'Sin mensaje',
                'data' => [
                    'usuario' => $resultado['usuario'] ?? [],
                    'permisos' => $permisos,
                    'requiere_cambio_password' => (bool)$requiereCambioPassword,
                    'dias_desde_cambio' => (int)$diasDesdeCambio,
                    'dias_restantes' => max(0, 30 - (int)$diasDesdeCambio)
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
     * Cambiar password
     */
    public function cambiarPassword(): void
    {
        try {
            $this->validateMethod('POST');

            if (!isset($_SESSION['usuarioID'])) {
                throw new \Exception("No hay sesión activa");
            }

            $data = $this->getJsonInput();

            $passwordActual = $data['passwordActual'] ?? '';
            $passwordNueva = $data['passwordNueva'] ?? '';
            $usuarioId = $_SESSION['usuarioID'];

            if (empty($passwordActual) || empty($passwordNueva)) {
                throw new \Exception("Todos los campos son requeridos");
            }

            if (!$this->validarPasswordSegura($passwordNueva)) {
                throw new \Exception("La contraseña no cumple con los requisitos de seguridad");
            }

            $resultado = $this->usuarioService->cambiarPasswordObligatorio($usuarioId, $passwordActual, $passwordNueva);
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
    private function validarPasswordSegura(string $password): bool
    {
        if (strlen($password) < 8) return false;
        if (!preg_match('/[A-Z]/', $password)) return false;
        if (!preg_match('/[a-z]/', $password)) return false;
        if (!preg_match('/[0-9]/', $password)) return false;
        if (!preg_match('/[@$!%*?&#]/', $password)) return false;
        return true;
    }

    /**
     * LOGOUT: Cierra sesión del usuario
     */
    public function logout(): void
    {
        $this->destroySession();

        $this->jsonResponse([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    /**
     * Obtener datos del usuario actual desde la sesión
     */
    public function obtenerUsuarioActual(): void
    {
        try {
            if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
                throw new \Exception("Usuario no autenticado");
            }

            if (!isset($_SESSION['usuarioID'])) {
                throw new \Exception("No se encontró el ID del usuario en la sesión");
            }

            $usuario = $this->usuarioService->obtenerUsuarioPorId($_SESSION['usuarioID']);

            if (!$usuario) {
                throw new \Exception("Usuario no encontrado");
            }

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

    /**
     * Crear usuario
     */
    public function crearUsuario(): void
    {
        try {
            $input = $this->getJsonInput();
            $data = $input['data'] ?? $input;

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

    /**
     * Eliminar usuario
     */
    public function eliminarUsuario(): void
    {
        try {
            $data = $this->getJsonInput();

            if (empty($data['usuario_id'])) {
                $this->errorResponse("Debe proporcionar el ID del usuario", 400);
                return;
            }

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

    /**
     * Obtener DNI y Password
     */
    public function obtenerDniYPassword(): void
    {
        header('Content-Type: application/json');

        try {
            $data = $this->getJsonInput();
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

            $passwordSesion = $_SESSION['password'] ?? null;

            if (empty($passwordSesion)) {
                throw new \Exception("No se encontró la contraseña en la sesión");
            }

            echo json_encode([
                "success" => true,
                "data" => [
                    "DNI" => $resultado['DNI'],
                    "password" => $passwordSesion
                ]
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
    public function listarUsuarios(): void
    {
        $this->executeServiceAction(function () {
            $usuarios = $this->usuarioService->listarUsuarios();
            return [
                'message' => 'Usuarios obtenidos correctamente',
                'data' => $usuarios
            ];
        });
    }

    /**
     * Obtener roles de usuario
     */
    public function obtenerRoles(): void
    {
        $this->executeServiceAction(function () {
            $roles = $this->usuarioService->obtenerRoles();
            return [
                'message' => 'Roles obtenidos correctamente',
                'data' => $roles
            ];
        });
    }

    /**
     * Obtener tipo de personal
     */
    public function obtenerTipoPersonal(): void
    {
        $this->executeServiceAction(function () {
            $tipos = $this->usuarioService->obtenerTipoPersonal();
            return [
                'message' => 'Tipo de personal obtenido correctamente',
                'data' => $tipos
            ];
        });
    }

    /**
     * Obtener usuario por ID para edición
     */
    public function obtenerUsuario(): void
    {
        $this->executeServiceAction(function () {
            $usuarioId = $_GET['id'] ?? null;

            if (empty($usuarioId)) {
                throw new \Exception('ID de usuario es requerido');
            }

            $usuario = $this->usuarioService->obtenerUsuarioPorId($usuarioId);
            return [
                'message' => 'Usuario obtenido correctamente',
                'data' => $usuario
            ];
        });
    }

    /**
     * Actualizar usuario
     */
    public function actualizarUsuario(): void
    {
        try {
            $this->validateMethod('PUT');

            $input = $this->getJsonInput();

            if (!isset($input['data'])) {
                $this->errorResponse('Datos no proporcionados', 400);
                return;
            }

            $datos = $input['data'];
            error_log("Datos recibidos para actualizar usuario: " . print_r($datos, true));

            $response = $this->usuarioService->actualizarUsuario($datos);
            error_log("Respuesta de actualización de usuario: " . print_r($response, true));

            echo json_encode($response);
        } catch (\Exception $e) {
            $this->errorResponse('Error al actualizar usuario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualizar password
     */
    public function actualizarPassword(): void
    {
        try {
            $this->validateMethod('PUT');

            $input = $this->getJsonInput();

            if (!isset($input['data'])) {
                $this->errorResponse('Datos no proporcionados', 400);
                return;
            }

            $datos = $input['data'];
            $response = $this->usuarioService->actualizarPassword($datos);

            echo json_encode($response);
        } catch (\Exception $e) {
            $this->errorResponse('Error al actualizar usuario: ' . $e->getMessage(), 500);
        }
    }
}
