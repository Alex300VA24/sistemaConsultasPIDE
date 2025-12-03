
<div class="modulo-container usuario-container">
    <!-- Título de la Página -->
    <div class="page-title">
        <h1>
            <i class="fa-solid fa-puzzle-piece"></i>
            Gestión de Módulos
        </h1>
    </div>

    <!-- Contenedor de Alertas -->
    <div id="alertContainerModulo"></div>

    <!-- Content Wrapper con Tabs -->
    <div class="content-wrapper">
        <!-- Tabs Navigation -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="crearModulo">
                <i class="fa-solid fa-plus-circle"></i>
                Crear Módulo
            </button>
            <button class="tab-btn" data-tab="listarModulos">
                <i class="fa-solid fa-list"></i>
                Listado de Módulos
            </button>
        </div>

        <!-- Tab Content: Crear Módulo -->
        <div id="tabCrearModulo" class="tab-content active">
            <div class="form-section">
                <form id="formCrearModulo">
                    <!-- Header: Información Básica -->
                    <div class="section-header">
                        <i class="fa-solid fa-info-circle"></i>
                        Información del Módulo
                    </div>

                    <div class="form-grid">
                        <!-- Módulo Padre -->
                        <div class="form-group">
                            <label for="moduloPadre">
                                Módulo Padre
                                <small>(opcional)</small>
                            </label>
                            <select id="moduloPadre" name="modulo_padre_id">
                                <option value="">Sin Padre (Módulo Principal) </option>
                            </select>
                        </div>

                        <!-- Código del Módulo -->
                        <div class="form-group">
                            <label for="codigoModulo">
                                Código del Módulo
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="codigoModulo" 
                                name="codigo"
                                placeholder="Ej: CON, MAN, SIS"
                                maxlength="10"
                                required
                                style="text-transform: uppercase;">
                        </div>

                        <!-- Nombre del Módulo -->
                        <div class="form-group">
                            <label for="nombreModulo">
                                Nombre del Módulo
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="nombreModulo" 
                                name="nombre"
                                placeholder="Ej: CONSULTAS, MANTENIMIENTO"
                                maxlength="100"
                                required>
                        </div>

                        <!-- Descripción -->
                        <div class="form-group full-width">
                            <label for="descripcionModulo">
                                Descripción
                                <span class="required">*</span>
                            </label>
                            <textarea 
                                id="descripcionModulo" 
                                name="descripcion"
                                placeholder="Descripción detallada del módulo"
                                rows="3"
                                required></textarea>
                        </div>
                    </div>

                    <!-- Header: Configuración -->
                    <div class="section-header">
                        <i class="fa-solid fa-cog"></i>
                        Configuración de Visualización
                    </div>

                    <div class="form-grid">
                        <!-- URL del Módulo -->
                        <div class="form-group">
                            <label for="urlModulo">
                                URL del Módulo
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="urlModulo" 
                                name="url"
                                placeholder="/pide/consultas"
                                required>
                        </div>

                        <!-- Ícono -->
                        <div class="form-group">
                            <label for="iconoModulo">
                                Ícono (Font Awesome)
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="iconoModulo" 
                                name="icono"
                                placeholder="fa-solid fa-database"
                                required>
                        </div>

                        <!-- Orden -->
                        <div class="form-group">
                            <label for="ordenModulo">
                                Orden de Visualización
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="number" 
                                id="ordenModulo" 
                                name="orden"
                                placeholder="1, 2, 3..."
                                min="1"
                                required>
                        </div>

                        <!-- Nivel -->
                        <div class="form-group">
                            <label for="nivelModulo">
                                Nivel
                                <span class="required">*</span>
                            </label>
                            <select id="nivelModulo" name="nivel" required>
                                <option value="">Seleccione</option>
                                <option value="1">Nivel 1 - Principal</option>
                                <option value="2">Nivel 2 - Secundario</option>
                                <option value="3">Nivel 3 - Terciario</option>
                                <option value="4">Nivel 4 - Cuaternario</option>
                            </select>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="action-buttons">
                        <button type="button" class="btn btn-secondary" id="btnLimpiarModulo">
                            <i class="fas fa-eraser"></i>
                            <span>Limpiar</span>
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarModulo">
                            <i class="fas fa-save"></i>
                            <span>Guardar Módulo</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tab Content: Listado de Módulos -->
        <div id="tabListarModulos" class="tab-content">
            <div class="table-container">
                <table id="tablaModulos">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Padre</th>
                            <th>Nivel</th>
                            <th>Orden</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaModulosBody">
                        <!-- Se llenará dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
