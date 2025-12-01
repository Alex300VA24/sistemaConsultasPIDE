
<div class="dashboard-container">
    <div class="sideBar">
        <div class="sidebar-header">
            <img class="sidebar-logo" src="<?= BASE_URL ?>assets/images/logo.png" alt="Logo">
            <div class="sidebar-title">MDE</div>
            <div class="sidebar-subtitle">Sistema de Consultas PIDE</div>
        </div>


        <!-- Inicio del Sistema -->
        <div class="option" onclick="showPage('Inicio', this)">
            <div class="containerIconOption"><i class="fas fa-home"></i></div>
            <p>Inicio</p>
        </div>
        <!-- Consultas del Sistema -->
            <!-- Bloque de Consultas -->
        <?php if (in_array('DNI', $permisos) 
            || in_array('RUC', $permisos) 
            || in_array('PAR', $permisos)
            || in_array('COB', $permisos)
            || in_array('PAP', $permisos)
            || in_array('CER', $permisos)): ?>

        <div class="option has-submenu" onclick="toggleSubmenu(this)">
            <div class="containerIconOption"><i class="fa-solid fa-database"></i></div>
            <p>Consultas</p>
            <i class="fa-solid fa-chevron-down submenu-icon"></i>
        </div>

        <div class="submenu">
            <?php if (in_array('DNI', $permisos)): ?>
                <div class="suboption" onclick="showPage('ConsultaDNI', this)">
                    <i class="fa-solid fa-id-card"></i>
                    <p>Consulta DNI</p>
                </div>
            <?php endif; ?>

            <?php if (in_array('RUC', $permisos)): ?>
                <div class="suboption" onclick="showPage('ConsultaRUC', this)">
                    <i class="fa-solid fa-building-columns"></i>
                    <p>Consulta RUC</p>
                </div>
            <?php endif; ?>

            <?php if (in_array('consultaCobranza', $permisos)): ?>
                <div class="suboption" onclick="showPage('ConsultaCobranza', this)">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <p>Consulta Cobranza Coactiva</p>
                </div>
            <?php endif; ?>

            <?php if (in_array('consultaPapeletas', $permisos)): ?>
                <div class="suboption" onclick="showPage('ConsultaPapeletas', this)">
                    <i class="fa-solid fa-car-burst"></i>
                    <p>Consulta Papeletas, Lic. Conduc. y Sanc.</p>
                </div>
            <?php endif; ?>

            <?php if (in_array('PAR', $permisos)): ?>
                <div class="suboption" onclick="showPage('ConsultaPartidas', this)">
                    <i class="fa-solid fa-file-signature"></i>
                    <p>Consulta de Partidas Registrales</p>
                </div>
            <?php endif; ?>

            <?php if (in_array('consultaCertificaciones', $permisos)): ?>
                <div class="suboption" onclick="showPage('ConsultaCertificaciones', this)">
                    <i class="fa-solid fa-leaf"></i>
                    <p>Consulta Certificaciones Ambientales</p>
                </div>
            <?php endif; ?>
        </div>

        <?php endif; ?>


        <!-- Mantenimiento del Sistema -->
        <?php if ($accesoTotal): ?>
        <div class="option" onclick="showPage('Sistema', this)">
            <div class="containerIconOption"><i class="fa-solid fa-tools"></i></div>
            <p>Mantenimiento</p>
        </div>
        <?php endif ?>



        <!-- Apartado de Sistemas -->
        <?php if (in_array('RUSU', $permisos) 
            || in_array('AUSU', $permisos) 
            || in_array('APAS', $permisos)
            || in_array('CROL', $permisos)): ?>
            <div class="option has-submenu" onclick="toggleSubmenu(this)">
                <div class="containerIconOption"><i class="fa-solid fa-gear"></i></div>
                <p>Sistema</p>
                <i class="fa-solid fa-chevron-down submenu-icon"></i>
            </div>

            <div class="submenu">
                <?php if (in_array('RUSU', $permisos)): ?>
                <div class="suboption" onclick="showPage('CrearUsuario', this)">
                    <i class="fa-solid fa-user-plus"></i>
                    <p>Crear Usuario</p>
                </div>
                <?php endif; ?>

                <?php if (in_array('AUSU', $permisos)): ?>
                <div class="suboption" onclick="showPage('ActualizarUsuario', this)">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <p>Actualizar Usuario</p>
                </div>
                <?php endif; ?>

                <?php if (in_array('APAS', $permisos)): ?>
                <div class="suboption" onclick="showPage('ActualizarPassword', this)">
                    <i class="fas fa-lock"></i>
                    <p>Actualizar Contraseña</p>
                </div>
                <?php endif; ?>

                <?php if (in_array('CROL', $permisos)): ?>
                <div class="suboption" onclick="showPage('CrearRoles', this)">
                    <i class="fas fa-user-shield"></i>
                    <p>Crear Roles</p>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>



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
</div>