<div class="usuario-container">
    <div class="page-title">
        <h1><i class="fas fa-user-plus"></i> Crear Nuevo Usuario</h1>
    </div>

    <div class="content-wrapper">
        <div class="form-section">
            <!-- Alertas -->
            <div id="alertContainer"></div>

            <!-- Datos de Persona -->
            <div class="section-header">
                <i class="fas fa-user"></i> Datos Personales
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Tipo de Persona <span class="required">*</span></label>
                    <select id="perTipo">
                        <option value="">Seleccionar...</option>
                        <option value="1">Natural</option>
                        <option value="2">Jurídica</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tipo de Documento <span class="required">*</span></label>
                    <select id="perDocumentoTipo">
                        <option value="">Seleccionar...</option>
                        <option value="1">DNI</option>
                        <option value="2">RUC</option>
                        <option value="3">Carnet de Extranjería</option>
                        <option value="4">Pasaporte</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Número de Documento <span class="required">*</span></label>
                    <input type="text" id="perDocumentoNum" maxlength="12" placeholder="Ej: 12345678">
                </div>

                <div class="form-group">
                    <label>Nombres <span class="required">*</span></label>
                    <input type="text" id="perNombre" maxlength="40" placeholder="Nombres completos">
                </div>

                <div class="form-group">
                    <label>Apellido Paterno <span class="required">*</span></label>
                    <input type="text" id="perApellidoPat" maxlength="20" placeholder="Apellido paterno">
                </div>

                <div class="form-group">
                    <label>Apellido Materno</label>
                    <input type="text" id="perApellidoMat" maxlength="20" placeholder="Apellido materno">
                </div>

                <div class="form-group">
                    <label>Sexo <span class="required">*</span></label>
                    <select id="perSexo">
                        <option value="">Seleccionar...</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="perEmail" maxlength="50" placeholder="correo@ejemplo.com">
                </div>

                <div class="form-group">
                    <label>Tipo de Personal <span class="required">*</span></label>
                    <select id="perTipoPersonal">
                        <option value="">Seleccionar...</option>
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
                    <input type="text" id="usuLogin" maxlength="15" placeholder="Nombre de usuario">
                </div>

                <div class="form-group">
                    <label>Contraseña <span class="required">*</span></label>
                    <input type="password" id="usuPass" maxlength="100" placeholder="Contraseña segura">
                </div>

                <div class="form-group">
                    <label>Confirmar Contraseña <span class="required">*</span></label>
                    <input type="password" id="usuPassConfirm" maxlength="100" placeholder="Repita la contraseña">
                </div>

                <div class="form-group">
                    <label>Rol de Usuario <span class="required">*</span></label>
                    <select id="usuPermiso">
                    </select>
                </div>

                <div class="form-group">
                    <label>Estado <span class="required">*</span></label>
                    <select id="usuEstado">
                        <option value="1" selected>Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>CUI</label>
                    <input type="number" id="cui" min="0" max="255" placeholder="Código único (opcional)">
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="action-buttons">
                <button class="btn btn-secondary" onclick="limpiarFormulario()">
                    <i class="fas fa-broom"></i> Limpiar
                </button>
                <button id="btnCrear" class="btn btn-primary" onclick="crearUsuario()">
                    <span class="loading" style="display: none;"></span>
                    <i class="fas fa-save"></i> Crear Usuario
                </button>
            </div>
        </div>
    </div>
</div>