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

                <div class="form-group password-container">
                    <label>Contraseña Actual <span class="required">*</span></label>
                    <input type="password" id="usuPassActualPassword" maxlength="100" placeholder="Ingrese su contraseña actual" required>
                    <i id="togglePasswordActual" class="fas fa-eye-slash toggle-password"></i>
                    <small class="password-hint"></small>
                </div>

                <div class="form-group password-container">
                    <label>Nueva Contraseña <span class="required">*</span></label>
                    <input type="password" id="usu-passPassword" maxlength="100" placeholder="Ingrese su nueva contraseña" required>
                    <i id="togglePassword2" class="fas fa-eye-slash toggle-password"></i>
                    <small class="password-hint">Mínimo 6 caracteres</small>
                </div>

                <div class="form-group password-container">
                    <label>Confirmar Nueva Contraseña <span class="required">*</span></label>
                    <input type="password" id="usu-passConfirmPassword" maxlength="100" placeholder="Confirme su nueva contraseña" required>
                    <i id="togglePasswordConfirm2" class="fas fa-eye-slash toggle-password"></i>
                    <small class="password-hint"></small>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="action-buttons">
                <button class="btn btn-secondary" onclick="limpiarFormularioPassword()">
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