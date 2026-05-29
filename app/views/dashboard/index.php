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
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 99999; background: rgba(15,23,42,0.85); backdrop-filter: blur(4px);">
        <div id="loadingOverlayInner" style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;">
            <div style="display: flex; flex-direction: column; align-items: center; gap: 24px;">
                <div style="position: relative; width: 100px; height: 100px;">
                    <div style="position: absolute; inset: 0; background: rgba(255,255,255,0.1); border-radius: 24px; backdrop-filter: blur(10px);"></div>
                    <img src="<?= BASE_URL ?>assets/images/muni2.png" alt="PIDE" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 60px; height: 60px; object-fit: contain;">
                    <div style="position: absolute; top: -10px; left: -10px; right: -10px; bottom: -10px; width: 120px; height: 120px; border: 3px solid rgba(255,255,255,0.15); border-top-color: #5EEAD4; border-radius: 50%; animation: loadSpin 1.2s linear infinite;"></div>
                    <div style="position: absolute; top: -5px; left: -5px; right: -5px; bottom: -5px; width: 110px; height: 110px; border: 2px solid transparent; border-bottom-color: rgba(255,255,255,0.3); border-radius: 50%; animation: loadSpin 1.8s linear infinite reverse;"></div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 28px; font-weight: 800; color: white; letter-spacing: 0.05em;">Sistema PIDE</div>
                    <div style="font-size: 14px; color: rgba(255,255,255,0.6); margin-top: 4px;">Cargando...</div>
                </div>
                <div style="width: 180px; height: 4px; background: rgba(255,255,255,0.15); border-radius: 999px; overflow: hidden;">
                    <div style="height: 100%; background: linear-gradient(90deg, #4A90D9, #1E5799); border-radius: 999px; animation: loadProgress 1.5s ease-in-out infinite;"></div>
                </div>
            </div>
        </div>
        <style>
            @keyframes loadSpin { to { transform: rotate(360deg); } }
            @keyframes loadProgress {
                0% { width: 0%; margin-left: 0; }
                50% { width: 70%; margin-left: 15%; }
                100% { width: 0%; margin-left: 100%; }
            }
        </style>
    </div>
    
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