<?php
namespace App\Middleware;

use App\Core\Request;

class CsrfMiddleware {
    
    // Rutas públicas que NO requieren CSRF
    private $excludedRoutes = [
        '/api/login',
        '/api/csrf-token'
    ];
    
    public function handle(Request $request) {
        // Siempre inicializar token
        $this->initToken();
        
        // Verificar si debe saltarse CSRF
        if ($this->shouldSkip($request)) {
            return true;
        }
        
        // Solo validar en métodos que modifican datos
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return $this->verifyToken($request);
        }
        
        return true;
    }

    private function initToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Genera un nuevo token CSRF y lo guarda en sesión.
     */
    public static function generateToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    /**
     * Rota el token CSRF (se usa tras login/logout).
     */
    public static function regenerateToken() {
        return self::generateToken();
    }

    /**
     * Obtiene el token CSRF almacenado en sesión (o null).
     */
    public static function getToken() {
        return $_SESSION['csrf_token'] ?? null;
    }

    /**
     * Input hidden para formularios.
     */
    public static function getHiddenInput() {
        $token = self::getToken() ?? self::generateToken();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Meta tag para usar el token desde JavaScript.
     */
    public static function getMetaTag() {
        $token = self::getToken() ?? self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    private function shouldSkip(Request $request) {
        // 1. Verificar atributo skip_csrf de la ruta
        if ($request->shouldSkipCsrf()) {
            return true;
        }
        
        // 2. Verificar lista de rutas excluidas (comparación EXACTA)
        $path = $request->getPath();
        if (in_array($path, $this->excludedRoutes, true)) {
            return true;
        }
        
        return false;
    }

    private function verifyToken(Request $request) {
        $tokenHeader = $request->header('X-CSRF-TOKEN', '');
        
        if (empty($tokenHeader)) {
            $this->sendError('Token CSRF no proporcionado');
            return false;
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $this->sendError('Sesión inválida');
            return false;
        }
        
        if (!hash_equals($_SESSION['csrf_token'], $tokenHeader)) {
            $this->sendError('Token CSRF inválido', [
                'path' => $request->getPath(),
                'method' => $request->getMethod()
            ]);
            return false;
        }
        
        return true;
    }
    
    private function sendError($message, $debug = []) {
        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => $message,
            'debug' => $debug
        ]);
    }
}