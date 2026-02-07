<div class="rol-container usuario-container">
    <div class="page-title" id=tituloRoles>
        <h1><i class="fas fa-user-shield"></i> Gestión de Roles</h1>
    </div>

    <div id="alertContainerRoles"></div>

    <div class="content-wrapper">
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="cambiarTab('crear')">
                <i class="fas fa-plus"></i> Crear Rol
            </button>
            <button class="tab-btn" onclick="cambiarTab('listar')">
                <i class="fas fa-list"></i> Listar Roles
            </button>
        </div>


        <!-- Tab Crear -->
        <div id="tab-crear" class="tab-content active">
            <div class="form-section">
                <div id="alertContainer"></div>
                
                <div class="section-header">
                    <i class="fas fa-info-circle"></i> Información del Rol
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Código <span class="required">*</span></label>
                        <input type="text" id="rolCodigo" maxlength="50" placeholder="Ej: VENDEDOR">
                    </div>
                    
                    <div class="form-group">
                        <label>Nombre <span class="required">*</span></label>
                        <input type="text" id="rolNombre" maxlength="100" placeholder="Ej: Vendedor">
                    </div>
                    
                    <div class="form-group">
                        <label>Nivel de Acceso <span class="required">*</span></label>
                        <input type="number" id="rolNivel" min="1" max="10" value="1">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Descripción</label>
                        <textarea id="rolDescripcion" rows="3" placeholder="Descripción del rol"></textarea>
                    </div>
                </div>
                
                <div class="section-header">
                    <i class="fas fa-puzzle-piece"></i> Módulos Disponibles
                </div>
                
                <div id="modulosContainer" class="modulos-grid">
                    <!-- Se llenan dinámicamente -->
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="limpiarFormulario()">
                        <i class="fas fa-broom"></i> Limpiar
                    </button>
                    <button class="btn btn-primary" onclick="guardarRol()">
                        <i class="fas fa-save"></i> Crear Rol
                    </button>
                </div>
            </div>
        </div>
        <!-- Tab Listar -->
        <div id="tab-listar" class="tab-content">
            <div class="table-container">
                <table id="tablaRoles">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Nivel</th>
                            <th>Usuarios</th>
                            <th>Módulos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Se llena dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>