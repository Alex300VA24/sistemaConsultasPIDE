<?php
/**
 * Sidebar Dinámico con diseño moderno expandible
 */

use App\Repositories\ModuloRepository;

if (!isset($_SESSION['usuarioID'])) {
    header('Location: /MDESistemaPIDE/public/login');
    exit;
}

$usuarioId = $_SESSION['usuarioID'];
$moduloRepo = new ModuloRepository();
$modulos = $moduloRepo->obtenerModulosPorUsuario($usuarioId);
$modulosJerarquicos = organizarModulosJerarquicos($modulos);

function obtenerNombrePagina($url) {
    $url = str_replace('/pide/', '', $url);
    $partes = explode('-', $url);
    $nombrePagina = '';
    foreach ($partes as $parte) {
        $nombrePagina .= ucfirst($parte);
    }
    return $nombrePagina;
}

$cargo = $_SESSION['cargo'] ?? '';
$area = $_SESSION['area'] ?? '';
?>

<style>
/* Sidebar base styles */
#sidebar {
    width: 70px;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

#sidebar.expanded {
    width: 260px;
}

/* Text labels transition */
.nav-text {
    opacity: 0;
    visibility: hidden;
    transform: translateX(-10px);
    transition: all 0.25s ease-in-out;
    white-space: nowrap;
    overflow: hidden;
}

#sidebar.expanded .nav-text {
    opacity: 1;
    visibility: visible;
    transform: translateX(0);
}

/* User section text */
.user-info {
    opacity: 0;
    visibility: hidden;
    transition: all 0.25s ease-in-out;
}

#sidebar.expanded .user-info {
    opacity: 1;
    visibility: visible;
}

/* Chevron rotation */
.chevron {
    transition: transform 0.3s ease;
}

.has-submenu.open .chevron {
    transform: rotate(180deg);
}

/* Submenu */
.submenu {
    display: none;
    flex-direction: column;
}

/* Active states */
.option.active,
.suboption.active {
    background: rgba(59, 130, 246, 0.3) !important;
    border-left: 4px solid #3b82f6;
}
</style>

<aside id="sidebar" class="fixed left-0 top-0 h-full glass-dark text-white z-50 flex flex-col shadow-2xl overflow-hidden">
    
    <!-- Logo Section -->
    <div class="h-20 flex items-center border-b border-blue-700/50 relative overflow-hidden">
        <div class="flex items-center gap-3 px-4 w-full min-w-[260px]">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-yellow-400 to-red-500 flex items-center justify-center shadow-lg flex-shrink-0">
                <!-- <i class="fas fa-landmark text-white text-lg"></i> -->
                <img src="<?= BASE_URL ?>assets/images/logo.png" alt="logo">
            </div>
            <div class="nav-text">
                <h1 class="font-bold text-sm leading-tight">MDE</h1>
                <p class="text-xs text-blue-200">Sistema PIDE</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 py-6 px-3 space-y-2 overflow-y-auto overflow-x-hidden">
        <?php foreach ($modulosJerarquicos as $moduloPadre): ?>
            <?php if (!in_array($moduloPadre['MOD_codigo'], $permisos)) continue; ?>
            
            <?php 
            $tieneHijosConPermiso = false;
            if (!empty($moduloPadre['hijos'])) {
                foreach ($moduloPadre['hijos'] as $hijo) {
                    if (in_array($hijo['MOD_codigo'], $permisos)) {
                        $tieneHijosConPermiso = true;
                        break;
                    }
                }
            }
            ?>
            
            <!-- Usar misma estructura base para todos -->
            <div class="relative min-w-[240px]">
                <?php if (!$tieneHijosConPermiso): ?>
                    <!-- Módulo sin hijos (sin chevron) -->
                    <a href="#" class="flex items-center gap-4 px-3 py-3 rounded-xl text-blue-100 hover:bg-white/10 transition-all group" 
                    onclick="showPage('<?= obtenerNombrePagina($moduloPadre['MOD_url']) ?>', this); return false;">
                        <div class="w-10 h-10 rounded-lg bg-blue-800/50 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600 transition-colors">
                            <i class="<?= htmlspecialchars($moduloPadre['MOD_icono']) ?> text-sm"></i>
                        </div>
                        <span class="nav-text font-medium text-sm"><?= htmlspecialchars($moduloPadre['MOD_nombre']) ?></span>
                    </a>
                <?php else: ?>
                    <!-- Módulo con hijos -->
                    <a href="#" class="has-submenu flex items-center gap-4 px-3 py-3 rounded-xl text-blue-100 hover:bg-white/10 transition-all group" 
                    onclick="toggleSubmenu(this); return false;">
                        <div class="w-10 h-10 rounded-lg bg-blue-800/50 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600 transition-colors">
                            <i class="<?= htmlspecialchars($moduloPadre['MOD_icono']) ?> text-sm"></i>
                        </div>
                        <span class="nav-text font-medium text-sm flex-1"><?= htmlspecialchars($moduloPadre['MOD_nombre']) ?></span>
                        <i class="fas fa-chevron-down text-xs nav-text opacity-60 chevron"></i>
                    </a>
                    
                    <div class="submenu mt-1 space-y-1 ml-4">
                        <?php foreach ($moduloPadre['hijos'] as $moduloHijo): ?>
                            <?php if (in_array($moduloHijo['MOD_codigo'], $permisos)): ?>
                                <a href="#" class="suboption flex items-center gap-3 px-3 py-2 rounded-lg text-blue-200 hover:bg-white/10 transition-all text-sm" 
                                onclick="showPage('<?= obtenerNombrePagina($moduloHijo['MOD_url']) ?>', this); return false;">
                                    <i class="<?= htmlspecialchars($moduloHijo['MOD_icono']) ?> text-xs w-5 text-center flex-shrink-0"></i>
                                    <span class="nav-text"><?= htmlspecialchars($moduloHijo['MOD_nombre']) ?></span>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- User Section -->
    <div class="p-4 border-t border-blue-700/50 min-w-[260px]">
        <div class="flex items-center gap-3 mb-3 px-2">
            <div class="relative flex-shrink-0">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                    <?= strtoupper(substr($_SESSION['nombreUsuario'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-400 rounded-full border-2 border-blue-900 pulse-dot"></div>
            </div>
            <div class="user-info flex-1 min-w-0">
                <p class="font-semibold text-sm truncate text-white"><?= htmlspecialchars($_SESSION['nombreUsuario'] ?? '') ?></p>
                <p class="text-xs text-blue-300 truncate"><?= htmlspecialchars($_SESSION['ROL_nombre'] ?? '') ?></p>
            </div>
        </div>
        
        <button id="btnLogout" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl bg-red-500/20 hover:bg-red-500/30 text-red-200 transition-all border border-red-500/30 group min-w-[240px]">
            <i class="fas fa-sign-out-alt text-sm w-5 text-center flex-shrink-0"></i>
            <span class="nav-text text-sm font-medium">Cerrar Sesión</span>
        </button>
    </div>
</aside>

<!-- Modal de Confirmación de Cierre de Sesión -->
<div id="logoutModal" class="fixed inset-0 bg-black/50 items-center justify-center z-[60] backdrop-blur-sm" style="display: none;">
    <div class="glass rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all border border-white/50">
        <div class="p-6">
            <div class="flex items-center justify-center w-16 h-16 mx-auto bg-red-100 rounded-full mb-4">
                <i class="fas fa-sign-out-alt text-red-600 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 text-center mb-2">¿Cerrar sesión?</h3>
            <p class="text-gray-600 text-center mb-6">Tu sesión actual se cerrará y volverás a la pantalla de inicio de sesión.</p>
            <div class="flex gap-3">
                <button id="cancelLogout" class="flex-1 px-4 py-2 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition duration-200">
                    Cancelar
                </button>
                <button id="confirmLogout" class="flex-1 px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white font-semibold rounded-xl hover:from-red-600 hover:to-red-700 transition duration-200 shadow-lg">
                    Cerrar sesión
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    let expandTimeout;

    if (sidebar && mainContent) {
        sidebar.addEventListener('mouseenter', () => {
            clearTimeout(expandTimeout);
            sidebar.classList.add('expanded');
            mainContent.style.marginLeft = '260px';
        });

        sidebar.addEventListener('mouseleave', () => {
            expandTimeout = setTimeout(() => {
                sidebar.classList.remove('expanded');
                mainContent.style.marginLeft = '70px';
            }, 100);
        });
    }

    window.toggleSubmenu = function(element) {
        const submenu = element.nextElementSibling;
        const isOpen = element.classList.contains('open');
        
        document.querySelectorAll('.submenu').forEach(s => {
            s.style.display = 'none';
        });
        document.querySelectorAll('.has-submenu').forEach(o => {
            o.classList.remove('open');
        });
        
        if (!isOpen) {
            submenu.style.display = 'flex';
            element.classList.add('open');
        }
    };
});
</script>
