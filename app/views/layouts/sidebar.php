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

#sidebar.expanded .nav-text,
#sidebar.mobile-open .nav-text {
    opacity: 1;
    visibility: visible;
    transform: translateX(0);
}

/* User section text */
.user-info,
.user-info-collapsed {
    opacity: 1;
    visibility: visible;
    transition: all 0.25s ease-in-out;
}

#sidebar:not(.expanded):not(.mobile-open) .user-info-collapsed {
    display: none;
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

/* Ocultar submenús solo cuando sidebar está colapsado en desktop */
#sidebar:not(.expanded):not(.mobile-open) .submenu {
    display: none !important;
}

#sidebar:not(.expanded):not(.mobile-open) .has-submenu.open {
    background: transparent !important;
}

#sidebar:not(.expanded):not(.mobile-open) .has-submenu.open .chevron {
    opacity: 0;
}

/* Active states */
.option.active,
.suboption.active {
    background: rgba(59, 130, 246, 0.3) !important;
    border-left: 4px solid #3b82f6;
}

/* Mobile: sidebar completamente expandido al abrir */
@media (max-width: 768px) {
    #sidebar {
        width: 280px !important;
    }

    /* Los items del nav no deben desbordar */
    #sidebar nav .relative {
        min-width: 0 !important;
    }

    /* Ocultar botón cerrar en desktop */
    .sidebar-close-btn {
        display: none;
    }

    #sidebar.mobile-open .sidebar-close-btn {
        display: flex !important;
    }
}
</style>

<aside id="sidebar" class="fixed left-0 top-0 h-full glass-dark text-white z-50 flex flex-col shadow-2xl overflow-hidden">
    
    <!-- Botón cerrar sidebar (mobile) -->
    <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Cerrar menú">
        <i class="fas fa-times"></i>
    </button>
    
    <!-- Logo Section -->
    <div class="h-20 flex items-center border-b border-blue-700/50 relative overflow-hidden">
        <div class="flex items-center gap-3 px-4 w-full min-w-[260px]">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-yellow-400 to-red-500 flex items-center justify-center shadow-lg flex-shrink-0">
                <!-- <i class="fas fa-landmark text-white text-lg"></i> -->
                <img src="<?= BASE_URL ?>assets/images/muni2.png" alt="logo">
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
    <div class="p-4 border-t border-slate-700/50 w-[260px]">
        <div class="flex items-center gap-3 mb-4 px-2">
            <div class="relative flex-shrink-0">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center ring-2 ring-white/10">
                    <span class="font-bold text-sm"><?= strtoupper(substr($_SESSION['nombreUsuario'] ?? 'U', 0, 1)) ?></span>
                </div>
                <div class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-green-500 rounded-full border-2 border-slate-900"></div>
            </div>
            <div class="user-info-collapsed">
                <p class="font-semibold text-sm text-white"><?= htmlspecialchars($_SESSION['nombreUsuario'] ?? '') ?></p>
                <p class="text-xs text-slate-400 leading-tight"><?= htmlspecialchars($_SESSION['ROL_nombre'] ?? '') ?></p>
                <p class="text-[10px] text-slate-500"><?= htmlspecialchars($_SESSION['nombreCargo'] ?? '') ?></p>
            </div>
        </div>
        
        <button id="btnLogout" onclick="mostrarModalLogout()" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg bg-red-500/60 hover:bg-red-600 text-white transition-all duration-200 border border-red-500 min-w-[240px] group cursor-pointer">
            <i class="fas fa-sign-out-alt text-sm w-5 text-center flex-shrink-0 group-hover:translate-x-1 transition-transform"></i>
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

    // Logout modal functions
    const logoutModal = document.getElementById('logoutModal');
    const btnLogout = document.getElementById('btnLogout');
    const cancelLogout = document.getElementById('cancelLogout');
    const confirmLogout = document.getElementById('confirmLogout');

    window.mostrarModalLogout = function() {
        if (logoutModal) logoutModal.style.display = 'flex';
    };

    window.cerrarModalLogout = function() {
        if (logoutModal) logoutModal.style.display = 'none';
    };

    if (btnLogout) {
        btnLogout.addEventListener('click', mostrarModalLogout);
    }

    if (cancelLogout) {
        cancelLogout.addEventListener('click', cerrarModalLogout);
    }

    if (confirmLogout) {
        confirmLogout.addEventListener('click', function() {
            fetch('<?= BASE_URL ?>api/logout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                window.location.href = '<?= BASE_URL ?>login';
            })
            .catch(err => {
                window.location.href = '<?= BASE_URL ?>login';
            });
        });
    }

    if (logoutModal) {
        logoutModal.addEventListener('click', function(e) {
            if (e.target === logoutModal) {
                cerrarModalLogout();
            }
        });
    }

    if (sidebar && mainContent) {
        const isMobile = () => window.innerWidth <= 768;

        sidebar.addEventListener('mouseenter', () => {
            if (isMobile()) return;
            clearTimeout(expandTimeout);
            sidebar.classList.add('expanded');
            mainContent.style.marginLeft = '260px';
        });

        sidebar.addEventListener('mouseleave', () => {
            if (isMobile()) return;
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
