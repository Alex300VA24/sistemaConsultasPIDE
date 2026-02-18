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

<div class="flex h-screen overflow-hidden" id="dashboardContainer">
    <?php include __DIR__ . "/../layouts/sidebar.php"; ?>
    
    <main id="main-content" class="ml-[70px] transition-all duration-300 min-h-screen p-6 flex-1 overflow-y-auto">
        <!-- Header -->
        <header class="glass rounded-2xl p-6 mb-8 shadow-lg border border-white/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center shadow-lg">
                        <i class="fas fa-search-location text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Sistema de Consultas PIDE</h1>
                        <p class="text-gray-500 text-sm">Plataforma de Interoperabilidad del Estado Peruano</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="hidden md:flex items-center gap-2 px-4 py-2 rounded-full bg-blue-50 border border-blue-200">
                        <div class="w-2 h-2 rounded-full bg-green-500 pulse-dot"></div>
                        <span class="text-sm font-medium text-blue-800">Sistema Operativo</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        <i class="far fa-calendar-alt mr-1"></i>
                        <?= date('d/m/Y H:i') ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Contenido Principal -->
        <div>
            <!-- ============================================ -->
            <!-- PÁGINA DE INICIO (SIEMPRE VISIBLE) -->
            <!-- ============================================ -->


            <!-- ============================================ -->
            <!-- PÁGINAS DINÁMICAS (NUEVOS MÓDULOS) -->
            <!-- ============================================ -->
            <?php 
            generarPaginasDinamicas($modulosJerarquicos, $permisos); 
            ?>
        </div>
    </main>
</div>

<style>
/* Asegurar que el main-content se ajuste correctamente */
#main-content {
    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

<?php include __DIR__ . "/../layouts/footer.php"; ?>