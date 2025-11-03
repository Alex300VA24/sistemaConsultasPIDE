<div class="usuario-container">
    <div class="page-title">
        <h1><i class="fas fa-pen-to-square"></i> Actualizar Mi Contraseña</h1>
    </div>

    <div class="content-wrapper">
        <div class="form-section">
            <!-- Información del usuario actual -->
            <div id="infoUsuarioActual"></div>

            <!-- Alertas -->
            <div id="alertContainerPassword"></div>

            <!-- Datos de Usuario -->
            <div class="section-header">
                <i class="fas fa-lock"></i> Actualizar Contraseña
            </div>
            <div class="form-grid">

                <div class="form-group">
                    <label>Contraseña Actual <span style="color: red;">*</span></label>
                    <input type="password" id="usuPassActualPassword" maxlength="100" placeholder="Ingrese su contraseña actual" required>
                </div>

                <div class="form-group">
                    <label>Nueva Contraseña <span style="color: red;">*</span></label>
                    <input type="password" id="usu-passPassword" maxlength="100" placeholder="Ingrese su nueva contraseña" required>
                    <small style="color: #666; font-size: 12px;">Mínimo 6 caracteres</small>
                </div>

                <div class="form-group">
                    <label>Confirmar Nueva Contraseña <span style="color: red;">*</span></label>
                    <input type="password" id="usu-passConfirmPassword" maxlength="100" placeholder="Confirme su nueva contraseña" required>
                </div>

            </div>

            <!-- Botones de acción -->
            <div class="action-buttons">
                <button class="btn btn-secondary" onclick="limpiarCamposFormularioPassword()">
                    <i class="fas fa-broom"></i> Limpiar
                </button>
                <button id="btnActualizarPassword" class="btn btn-primary" onclick="actualizarPasswordUsuarioActual()">
                    <span class="loading" style="display: none;"></span>
                    <i class="fas fa-save"></i> Actualizar Contraseña
                </button>
            </div>
        </div>
    </div>
</div>