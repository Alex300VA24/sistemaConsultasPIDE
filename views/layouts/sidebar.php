<?php
/**
 * Sidebar Dinámico
 * Genera la barra de navegación basándose en los módulos asignados al usuario
 * Usa el array $permisos ya cargado desde el archivo principal
 */

use App\Repositories\ModuloRepository;

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuarioID'])) {
    header('Location: /sistemaConsultasPIDE/public/login');
    exit;
}

$usuarioId = $_SESSION['usuarioID'];
$moduloRepo = new ModuloRepository();

// Obtener módulos del usuario
$modulos = $moduloRepo->obtenerModulosPorUsuario($usuarioId);

$modulosJerarquicos = organizarModulosJerarquicos($modulos);

// Función para extraer el nombre de la página del URL
function obtenerNombrePagina($url) {
    // Eliminar el prefijo /pide/
    $url = str_replace('/pide/', '', $url);
    
    // Convertir guiones a espacios y capitalizar
    $partes = explode('-', $url);
    $nombrePagina = '';
    
    foreach ($partes as $parte) {
        $nombrePagina .= ucfirst($parte);
    }
    
    return $nombrePagina;
}

// Variables de sesión para el usuario
$cargo = $_SESSION['cargo'] ?? '';
$area = $_SESSION['area'] ?? '';
?>

<div class="sideBar">
    <div class="sidebar-header">
        <img class="sidebar-logo" src="<?= BASE_URL ?>assets/images/logo.png" alt="Logo">
        <div class="sidebar-title">MDE</div>
        <div class="sidebar-subtitle">Sistema de Consultas PIDE</div>
    </div>


    <!-- Módulos Dinámicos -->
    <?php foreach ($modulosJerarquicos as $moduloPadre): ?>
        <?php 
        // Verificar si el usuario tiene permiso para este módulo
        if (!in_array($moduloPadre['MOD_codigo'], $permisos)) {
            continue;
        }
        ?>
        
        <?php if (empty($moduloPadre['hijos'])): ?>
            <!-- Módulo sin hijos (opción simple) -->
            <div class="option" onclick="showPage('<?= obtenerNombrePagina($moduloPadre['MOD_url']) ?>', this)">
                <div class="containerIconOption">
                    <i class="<?= htmlspecialchars($moduloPadre['MOD_icono']) ?>"></i>
                </div>
                <p><?= htmlspecialchars($moduloPadre['MOD_nombre']) ?></p>
            </div>
        <?php else: ?>
            <!-- Módulo con hijos (opción con submenú) -->
            <?php 
            // Verificar si al menos un hijo tiene permiso
            $tieneHijosConPermiso = false;
            foreach ($moduloPadre['hijos'] as $hijo) {
                if (in_array($hijo['MOD_codigo'], $permisos)) {
                    $tieneHijosConPermiso = true;
                    break;
                }
            }
            ?>
            
            <?php if ($tieneHijosConPermiso): ?>
                <div class="option has-submenu" onclick="toggleSubmenu(this)">
                    <div class="containerIconOption">
                        <i class="<?= htmlspecialchars($moduloPadre['MOD_icono']) ?>"></i>
                    </div>
                    <p><?= htmlspecialchars($moduloPadre['MOD_nombre']) ?></p>
                    <i class="fa-solid fa-chevron-down submenu-icon"></i>
                </div>

                <div class="submenu">
                    <?php foreach ($moduloPadre['hijos'] as $moduloHijo): ?>
                        <?php if (in_array($moduloHijo['MOD_codigo'], $permisos)): ?>
                            <div class="suboption" onclick="showPage('<?= obtenerNombrePagina($moduloHijo['MOD_url']) ?>', this)">
                                <i class="<?= htmlspecialchars($moduloHijo['MOD_icono']) ?>"></i>
                                <p><?= htmlspecialchars($moduloHijo['MOD_nombre']) ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- === Modal de Confirmación de Cierre de Sesión === -->
    <div id="logoutModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h3>¿Estás seguro que quieres cerrar sesión?</h3>
            <p>Tu sesión actual se cerrará y volverás a la pantalla de inicio de sesión.</p>
            <div class="modal-buttons">
                <button id="cancelLogout" class="btn-cancel">Cancelar</button>
                <button id="confirmLogout" class="btn-logout">Cerrar sesión</button>
            </div>
        </div>
    </div>

    <!-- === Información del usuario === -->
    <div class="user-info">
        <div><strong id="currentUserName"><?= htmlspecialchars($_SESSION['nombreUsuario'] ?? '') ?></strong></div>
        <div><?= htmlspecialchars($_SESSION['ROL_nombre'] ?? '') ?></div>
        <div id="currentUserRole">
            <?= htmlspecialchars($cargo) ?><?= $area ? " - " . htmlspecialchars($area) : '' ?>
        </div>

        <button class="logout-btn" id="btnLogout">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </button>
    </div>
</div>