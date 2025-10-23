<?php
namespace App\Controllers;

use App\Services\DashboardService;

class DashboardController {
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
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener los datos del inicio',
                'error' => $e->getMessage()
            ]);
        }
    }
}
