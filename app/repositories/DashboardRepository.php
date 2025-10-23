<?php
namespace App\Repositories;

use App\Config\Database;

class DashboardRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerTotalPracticantes() {
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM Practicante");
        return $stmt->fetch()['total'];
    }

    public function obtenerPendientesAprobacion() {
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM Practicante WHERE EstadoID = 6");
        return $stmt->fetch()['total'];
    }

    public function obtenerPracticantesActivos() {
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM Practicante WHERE EstadoID = 7");
        return $stmt->fetch()['total'];
    }

    public function obtenerAsistenciasHoy() {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM Asistencia WHERE CAST(Fecha AS DATE) = CAST(GETDATE() AS DATE)");
        $stmt->execute();
        return $stmt->fetch()['total'];
    }

    /*public function obtenerActividadReciente($limite = 5) {
        // Aseguramos que sea un número entero válido
        $limite = (int) $limite;

        $sql = "SELECT TOP $limite 
                    p.Nombres, p.ApellidoPaterno, p.ApellidoMaterno, 
                    a.HoraEntrada, a.HoraSalida, a.Fecha
                FROM Asistencia a
                JOIN Practicante p ON a.PracticanteID = p.PracticanteID
                ORDER BY a.Fecha DESC, a.HoraEntrada DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }*/

}
