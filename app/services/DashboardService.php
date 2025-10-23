<?php
namespace App\Services;

use App\Repositories\DashboardRepository;

class DashboardService {
    private $repo;

    public function __construct() {
        $this->repo = new DashboardRepository();
    }

    public function obtenerDatosInicio() {
        return [
            'totalPracticantes' => $this->repo->obtenerTotalPracticantes(),
            'pendientesAprobacion' => $this->repo->obtenerPendientesAprobacion(),
            'practicantesActivos' => $this->repo->obtenerPracticantesActivos(),
            'asistenciaHoy' => $this->repo->obtenerAsistenciasHoy(),
            //'actividadReciente' => $this->repo->obtenerActividadReciente()
        ];
    }
}
