<div class="user-container usuario-container">
    <div class="page-title" id="tituloCrearUsuario">
        <h1><i class="fas fa-user-plus"></i> Gestión de Usuarios</h1>
    </div>

    <!-- Alertas -->
    <div id="alertContainerCrearUsuario"></div>

    <div class="content-wrapper">

        <!-- Tabs Navigation -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="crearUsuario">
                <i class="fa-solid fa-plus-circle"></i>
                Crear Usuario
            </button>
            <button class="tab-btn" data-tab="listarUsuarios">
                <i class="fa-solid fa-list"></i>
                Listado de Usuarios
            </button>
        </div>

        <!-- Tab Content: Crear Usuario -->
        <div id="tabCrearUsuario" class="tab-content active">
            <div class="form-section">
                <form id="formCrearUsuario">

                    <!-- Header: Datos de Persona -->
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

                        <div class="form-group" style="position: relative;">
                            <label>Contraseña <span class="required">*</span></label>
                            <i id="togglePasswordCrear" class="fas fa-eye-slash toggle-password"style="position: absolute; right: 10px; top: 40px; cursor: pointer; color: #666;"></i>
                            <input type="password" id="usuPass" maxlength="100" placeholder="Contraseña segura">
                        </div>

                        <div class="form-group" style="position: relative;">
                            <label>Confirmar Contraseña <span class="required">*</span></label>
                            <i id="togglePasswordConfirmCrear" class="fas fa-eye-slash toggle-password" style="position: absolute; right: 10px; top: 40px; cursor: pointer; color: #666;"></i>
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
                        <button type="button" class="btn btn-secondary" id="btnLimpiarUsuario">
                            <i class="fas fa-broom"></i> Limpiar
                        </button>
                        <button id="btnGuardarUsuario" class="btn btn-primary" >
                            <span class="loading" style="display: none;"></span>
                            <i class="fas fa-save"></i> Guardar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Tab Content: Listado de Usuarios -->
        <div id="tabListarUsuarios" class="tab-content">
            <div class="table-container">
                <table id="tablaUsuarios">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaUsuariosBody">
                        <!-- Contenido generado dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>