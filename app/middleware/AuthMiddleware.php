<?php
// app/Middleware/AuthMiddleware.php
namespace App\Middleware;

use App\Core\Request;

class AuthMiddleware {
    public function handle(Request $request) {
        SecurityMiddleware::checkAuthentication();
        return true;
    }
}