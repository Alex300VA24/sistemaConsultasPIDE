<?php
namespace App\Controllers;

use App\Services\DashboardService;

class DashboardController extends BaseController {
    private $service;

    public function __construct() {
        $this->service = new DashboardService();
    }

    public function obtenerDatosInicio() {
        header('Content-Type: application/json');
        try {
            $data = $this->service->obtenerDatosInicio();
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            $this->handleError($e, 500);
        }
    }
}
