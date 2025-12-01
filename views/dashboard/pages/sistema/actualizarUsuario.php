<div class="usuario-container">
    <div class="page-title" id="tituloActualizarUsuario">
        <h1><i class="fas fa-pen-to-square"></i> Actualizar Usuario</h1>
    </div>

    <div class="content-wrapper">
        <div class="form-section">
            <!-- Alertas -->
            <div id="alertContainerActualizarUsuario"></div>

            <!-- Selector de Usuario -->
            <div class="section-header">
                <i class="fas fa-users"></i> Seleccionar Usuario
            </div>
            <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Usuario a Actualizar <span class="required">*</span></label>
                    <select id="selectorUsuario" onchange="cargarDatosUsuarioSeleccionado()">
                        <option value="">-- Seleccione un usuario --</option>
                    </select>
                </div>
            </div>

            <!-- Formulario de edición (inicialmente oculto) -->
            <div id="formularioEdicion" style="display: none;">
                <!-- Datos de Persona -->
                <div class="section-header">
                    <i class="fas fa-user"></i> Datos Personales
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Tipo de Persona <span class="required">*</span></label>
                        <select id="perTipo-actualizar">
                            <option value="">Seleccionar...</option>
                            <option value="1">Natural</option>
                            <option value="2">Jurídica</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tipo de Documento <span class="required">*</span></label>
                        <select id="perDocumentoTipo-actualizar">
                            <option value="">Seleccionar...</option>
                            <option value="1">DNI</option>
                            <option value="2">RUC</option>
                            <option value="3">Carnet de Extranjería</option>
                            <option value="4">Pasaporte</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Número de Documento <span class="required">*</span></label>
                        <input type="text" id="per-documento-num" maxlength="8" placeholder="Ej: 12345678">
                    </div>

                    <div class="form-group">
                        <label>Nombres <span class="required">*</span></label>
                        <input type="text" id="per-nombre" maxlength="40" placeholder="Nombres completos">
                    </div>

                    <div class="form-group">
                        <label>Apellido Paterno <span class="required">*</span></label>
                        <input type="text" id="per-apellido-pat" maxlength="20" placeholder="Apellido paterno">
                    </div>

                    <div class="form-group">
                        <label>Apellido Materno</label>
                        <input type="text" id="per-apellido-mat" maxlength="20" placeholder="Apellido materno">
                    </div>

                    <div class="form-group">
                        <label>Sexo <span class="required">*</span></label>
                        <select id="perSexo-actualizar">
                            <option value="">Seleccionar...</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="per-email" maxlength="50" placeholder="correo@ejemplo.com">
                    </div>

                    <div class="form-group">
                        <label>Tipo de Personal <span class="required">*</span></label>
                        <select id="per-tipo-personal">
                            <option value="0">Seleccionar...</option>
                        </select>
                    </div>
                </div>

                <!-- Datos de Usuario -->
                <div class="section-header">
                    <i class="fas fa-lock"></i> Datos de Usuario
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Login/Usuario <span class="required">*</span></label>
                        <input type="text" id="usu-login" maxlength="15" placeholder="Nombre de usuario">
                    </div>

                    <div class="form-group">
                        <label>Contraseña Actual </label>
                        <input type="password" id="usuPassActual" maxlength="100" placeholder="Contraseña segura">
                    </div>

                    <div class="form-group" style="position: relative;">
                        <label>Nueva Contraseña</label>
                        <input type="password" id="usu-pass" maxlength="100" placeholder="Dejar vacío para no cambiar">
                        <i id="togglePassword" class="fas fa-eye-slash toggle-password" style="position: absolute; right: 10px; top: 38px; cursor: pointer; color: #666;"></i>
                        <small style="color: #666; font-size: 12px;">Dejar vacío si no desea cambiar la contraseña</small>
                    </div>

                    <div class="form-group" style="position: relative;">
                        <label>Confirmar Nueva Contraseña</label>
                        <input type="password" id="usu-passConfirm" maxlength="100" placeholder="Confirmar contraseña">
                        <i id="togglePasswordConfirm" class="fas fa-eye-slash toggle-password" style="position: absolute; right: 10px; top: 38px; cursor: pointer; color: #666;"></i>
                    </div>


                    <div class="form-group">
                        <label>Nivel de Permiso <span class="required">*</span></label>
                        <select id="usuPermiso-actualizar">
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Estado <span class="required">*</span></label>
                        <select id="usuEstado-actualizar">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="limpiarFormularioUpdateUser()">
                        <i class="fas fa-broom"></i> Limpiar
                    </button>
                    <button id="btnActualizar" class="btn btn-primary" onclick="actualizarUsuario()">
                        <span class="loading" style="display: none;"></span>
                        <i class="fas fa-save"></i> Actualizar Usuario
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>