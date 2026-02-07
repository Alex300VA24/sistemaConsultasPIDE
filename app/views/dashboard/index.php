<?php
    header("Content-type: text/html; charset=utf-8");
?>

<?php
use App\Helpers\Permisos;

// Obtener permisos según id del usuario
$usuarioID = $_SESSION['usuarioID'];
$permisos = Permisos::obtenerPermisos($usuarioID);


// Cargar módulos del usuario para generación dinámica
//require_once __DIR__ . '/../../app/Repositories/ModuloRepository.php';
use App\Repositories\ModuloRepository;

$moduloRepo = new ModuloRepository();
$modulosUsuario = $moduloRepo->obtenerModulosPorUsuario($usuarioID);

// 🔹 Organizar módulos jerárquicamente
function organizarModulosJerarquicos($modulos) {
    $modulosPorId = [];
    $modulosOrganizados = [];

    // Indexar módulos por ID
    foreach ($modulos as $modulo) {
        $modulosPorId[$modulo['MOD_id']] = $modulo;
        $modulosPorId[$modulo['MOD_id']]['hijos'] = [];
    }

    // Organizar en jerarquía
    foreach ($modulosPorId as $id => $modulo) {
        if ($modulo['MOD_padre_id'] === null) {
            $modulosOrganizados[] = &$modulosPorId[$id];
        } else {
            if (isset($modulosPorId[$modulo['MOD_padre_id']])) {
                $modulosPorId[$modulo['MOD_padre_id']]['hijos'][] = &$modulosPorId[$id];
            }
        }
    }

    // Ordenar por orden
    usort($modulosOrganizados, function($a, $b) {
        return $a['MOD_orden'] - $b['MOD_orden'];
    });

    // Ordenar hijos
    foreach ($modulosOrganizados as &$moduloPadre) {
        if (!empty($moduloPadre['hijos'])) {
            usort($moduloPadre['hijos'], function($a, $b) {
                return $a['MOD_orden'] - $b['MOD_orden'];
            });
        }
    }

    return $modulosOrganizados;
}

$modulosJerarquicos = organizarModulosJerarquicos($modulosUsuario);

// 🔹 Incluir helper para generación de páginas
require_once __DIR__ . '/../../helpers/generarPaginasDinamicas.php';
?>
<?php $titulo = "Dashboard Principal"; ?>
<?php include __DIR__ . "/../layouts/header.php"; ?>

<div class="dashboard-container" id="dashboardContainer">
    <?php include __DIR__ . "/../layouts/sidebar.php"; ?>
    
    <div class="main-content">
        <!-- ============================================ -->
        <!-- PÁGINA DE INICIO (SIEMPRE VISIBLE) -->
        <!-- ============================================ -->


        <!-- ============================================ -->
        <!-- PÁGINAS DINÁMICAS (NUEVOS MÓDULOS) -->
        <!-- ============================================ -->
        <?php 
        // Generar páginas dinámicamente para módulos nuevos
        // Esto generará automáticamente las páginas de los módulos
        // que no están en la lista estática de arriba
        generarPaginasDinamicas($modulosJerarquicos, $permisos); 
        ?>
    </div>
</div>

<?php include __DIR__ . "/../layouts/footer.php"; ?>