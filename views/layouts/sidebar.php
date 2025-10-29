
<div class="dashboard-container">
    <div class="sideBar">
        <div class="sidebar-header">
            <img class="sidebar-logo" src="<?= BASE_URL ?>assets/images/logo.png" alt="Logo">
            <div class="sidebar-title">MDE</div>
            <div class="sidebar-subtitle">Sistema de Consultas PIDE</div>
        </div>

        <!-- Inicio del Sistema -->
        <div class="option active" onclick="showPage('inicio', this)">
            <div class="containerIconOption"><i class="fas fa-home"></i></div>
            <p>Inicio</p>
        </div>
        <!-- Consultas del Sistema -->
            <div class="option has-submenu" onclick="toggleSubmenu(this)">
                <div class="containerIconOption"><i class="fa-solid fa-database"></i></div>
                <p>Consultas</p>
                <i class="fa-solid fa-chevron-down submenu-icon"></i>
            </div>

            <div class="submenu">
                <div class="suboption" onclick="showPage('consultaDNI', this)">
                    <i class="fa-solid fa-id-card"></i>
                    <p>Consulta DNI</p>
                </div>
                <div class="suboption" onclick="showPage('consultaRUC', this)">
                    <i class="fa-solid fa-building-columns"></i>
                    <p>Consulta RUC</p>
                </div>
                <div class="suboption" onclick="showPage('consultaCobranza', this)">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <p>Consulta Cobranza Coactiva</p>
                </div>
                <div class="suboption" onclick="showPage('consultaPapeletas', this)">
                    <i class="fa-solid fa-car-burst"></i>
                    <p>Consulta Papeletas, Lic. Conduc. y Sanc.</p>
                </div>
                <div class="suboption" onclick="showPage('consultaPartidas', this)">
                    <i class="fa-solid fa-file-signature"></i>
                    <p>Consulta de Partidas Registrales</p>
                </div>
                <div class="suboption" onclick="showPage('consultaCertificaciones', this)">
                    <i class="fa-solid fa-leaf"></i>
                    <p>Consulta Certificaciones Ambientales</p>
                </div>
            </div>

        <!-- Mantenimiento del Sistema -->
        <div class="option" onclick="showPage('mantenimiento', this)">
            <div class="containerIconOption"><i class="fa-solid fa-tools"></i></div>
            <p>Mantenimiento</p>
        </div>



        <!-- Apartado de Sistemas -->
        <div class="option has-submenu" onclick="toggleSubmenu(this)">
            <div class="containerIconOption"><i class="fa-solid fa-gear"></i></div>
            <p>Sistema</p>
            <i class="fa-solid fa-chevron-down submenu-icon"></i>
        </div>

        <div class="submenu">
            <div class="suboption" onclick="showPage('crearUsuario', this)">
                <i class="fa-solid fa-user-plus"></i>
                <p>Crear Usuario</p>
            </div>
            <div class="suboption" onclick="showPage('actualizarUsuario', this)">
                <i class="fa-solid fa-pen-to-square"></i>
                <p>Actualizar Usuario</p>
            </div>
        </div>


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
            <div id="currentUserRole">
                <?= htmlspecialchars($cargo) ?><?= $area ? " - " . htmlspecialchars($area) : '' ?>
            </div>

            <button class="logout-btn" id="btnLogout">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </button>
        </div>
        
    </div>
</div>