<div class="consulta-partidas-container">
    <div class="page-title">
        <h1><i class="fas fa-file-contract"></i> Consulta de Partidas Registrales - SUNARP</h1>
    </div>
    
    <div class="content-wrapper">
        <!-- Alertas -->
        <div id="alertContainerPartidas"></div>
        <!-- Selector de Tipo de Persona -->
        <div class="tipo-persona-selector">
            <div class="radio-option">
                <input type="radio" id="personaNatural" name="tipoPersona" value="natural" checked>
                <label for="personaNatural">PERSONA NATURAL</label>
            </div>
            <div class="radio-option">
                <input type="radio" id="personaJuridica" name="tipoPersona" value="juridica">
                <label for="personaJuridica">PERSONA JURÍDICA</label>
            </div>
        </div>

        <!-- Sección de Búsqueda -->
        <div class="search-section">
            <form class="search-form" id="searchFormPartidas">
                <div>
                    <label for="persona" id="labelPersona">Persona:</label>
                    <input 
                        type="text" 
                        id="persona" 
                        name="persona" 
                        readonly
                        placeholder="Haga clic en el botón de búsqueda"
                    >
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-modal" id="btnBuscarPersona" title="Buscar Persona">
                        <i class="fas fa-magnifying-glass"></i>
                        <span>Buscar</span>
                    </button>
                    <button type="button" class="btn btn-search" id="btnConsultar" title="Consultar" disabled>
                        <i class="fas fa-search"></i>
                        <span>Consultar</span>
                    </button>
                    <button type="button" class="btn btn-clear" id="btnLimpiar" title="Limpiar">
                        <i class="fas fa-eraser"></i>
                        <span>Limpiar</span>
                    </button>
                </div>
            </form>
        </div>


        <!-- Sección de Resultados -->
        <div class="results-section" id="resultsSection" style="display: none;">
            <div class="results-layout">
                <!-- Foto (solo para personas naturales) -->
                <div class="photo-section">
                    <div class="photo-frame" id="photoSection">
                        <img id="personaFoto" src="" alt="Foto" style="display: none;">
                        <div class="no-photo" id="noFoto">
                            <i class="fas fa-user"></i>
                            <span>Sin foto</span>
                        </div>
                    </div>
                </div>

                <!-- Información -->
                <div class="info-grid" id="infoGrid">
                    <!-- Fila 1: Libro -->
                    <div class="info-item">
                        <span class="info-label">Libro</span>
                        <div class="info-value white-bg" id="libro">-</div>
                    </div>
                    <div class="info-item"></div>
                    <div class="info-item"></div>

                    <!-- Fila 2: Nombres (siempre visible inicialmente) -->
                    <div class="info-item full-width" id="containerNombres">
                        <span class="info-label">Nombres</span>
                        <div class="info-value white-bg" id="nombres">-</div>
                    </div>

                    <!-- Fila 3: Apellidos (siempre visible inicialmente) -->
                    <div class="info-item" id="containerApellidoPaterno">
                        <span class="info-label">Apellido Paterno</span>
                        <div class="info-value white-bg" id="apellidoPaterno">-</div>
                    </div>
                    <div class="info-item" id="containerApellidoMaterno">
                        <span class="info-label">Apellido Materno</span>
                        <div class="info-value white-bg" id="apellidoMaterno">-</div>
                    </div>
                    <div class="info-item"></div>

                    <!-- Fila 4: Razón Social (oculta por defecto, solo para jurídicas) -->
                    <div class="info-item full-width" id="containerRazonSocial" style="display: none;">
                        <span class="info-label">Razón Social</span>
                        <div class="info-value white-bg" id="campoRazonSocial">-</div>
                    </div>

                    <!-- Fila 5: Documento -->
                    <div class="info-item">
                        <span class="info-label">Tipo de Documento</span>
                        <div class="info-value white-bg" id="tipoDoc">-</div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nro. Documento</span>
                        <div class="info-value white-bg" id="nroDoc">-</div>
                    </div>
                    <div class="info-item"></div>

                    <!-- Fila 6: Partida y Placa -->
                    <div class="info-item">
                        <span class="info-label">Nro. Partida</span>
                        <div class="info-value white-bg" id="nroPartida">-</div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nro. Placa</span>
                        <div class="info-value white-bg" id="nroPlaca">-</div>
                    </div>
                    <div class="info-item"></div>

                    <!-- Fila 7: Estado y Zona -->
                    <div class="info-item">
                        <span class="info-label">Estado</span>
                        <div class="info-value white-bg" id="estado">-</div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Zona</span>
                        <div class="info-value white-bg" id="zona">-</div>
                    </div>
                    <div class="info-item"></div>

                    <!-- Fila 8: Oficina -->
                    <div class="info-item full-width">
                        <span class="info-label">Oficina</span>
                        <div class="info-value white-bg" id="oficina">-</div>
                    </div>

                    <!-- Fila 9: Dirección -->
                    <div class="info-item full-width">
                        <span class="info-label">Dirección</span>
                        <div class="info-value white-bg" id="direccion">-</div>
                    </div>
                </div>

                <!-- Sección de Asientos Registrales -->
                <div class="asientos-section" id="asientosSection" style="display: none;">
                    <div class="section-header">
                        <h3><i class="fas fa-file-invoice"></i> Asientos Registrales</h3>
                    </div>
                    <div id="asientosContainer" class="asientos-container"></div>
                </div>

                <!-- Sección de Imágenes de Documentos -->
                <div class="imagenes-section" id="imagenesSection" style="display: none;">
                    <div class="section-header">
                        <h3><i class="fas fa-images"></i> Imágenes de Documentos</h3>
                    </div>
                    <div class="imagenes-viewer">
                        <div class="imagenes-selector">
                            <label for="selectImagenes"><strong>Seleccionar página:</strong></label>
                            <select id="selectImagenes" class="imagen-select"></select>
                        </div>
                        <div class="imagen-container">
                            <img id="imagenViewer" src="" alt="Documento" style="display: none;">
                            <div class="no-imagen" id="noImagen">
                                <i class="fas fa-image"></i>
                                <span>Seleccione una página</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección de Datos Vehiculares -->
                <div class="vehiculo-section" id="vehiculoSection" style="display: none;">
                    <div class="section-header">
                        <h3><i class="fas fa-car"></i> Información Vehicular</h3>
                    </div>
                    <div id="vehiculoContainer" class="vehiculo-container"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Búsqueda de Personas Naturales -->
<div id="modalBusquedaNatural" class="modal-partidas">
    <div class="modal-dialog-partidas">
        <div class="modal-content-partidas">
            <div class="modal-header-partidas">
                <h5 class="modal-title-partidas">
                    <i class="fas fa-user"></i>
                    Búsqueda de Personas Naturales
                </h5>
                <button class="modal-close" onclick="cerrarModal('modalBusquedaNatural')">&times;</button>
            </div>
            <div class="modal-body-partidas">
                <form id="formBusquedaNatural" class="modal-search-form">
                    <div class="modal-form-group">
                        <label for="dniNatural">DNI:</label>
                        <input 
                            type="text" 
                            id="dniNatural" 
                            maxlength="8" 
                            pattern="[0-9]{8}"
                            placeholder="Ingrese 8 dígitos"
                        >
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-search">
                            <i class="fas fa-search"></i>
                            <span>Buscar</span>
                        </button>
                        <button type="button" class="btn btn-clear" onclick="limpiarModalNatural()">
                            <i class="fas fa-eraser"></i>
                            <span>Limpiar</span>
                        </button>
                    </div>
                </form>

                <!-- Resultados de búsqueda -->
                <div id="resultadosNatural" class="modal-results-table" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Búsqueda de Personas Jurídicas -->
<div id="modalBusquedaJuridica" class="modal-partidas">
    <div class="modal-dialog-partidas">
        <div class="modal-content-partidas">
            <div class="modal-header-partidas">
                <h5 class="modal-title-partidas">
                    <i class="fas fa-building"></i>
                    Búsqueda de Personas Jurídicas
                </h5>
                <button class="modal-close" onclick="cerrarModal('modalBusquedaJuridica')">&times;</button>
            </div>
            <div class="modal-body-partidas">
                <!-- Radio buttons para tipo de búsqueda -->
                <div class="modal-radio-group">
                    <div class="modal-radio-option">
                        <input type="radio" id="porRuc" name="tipoBusquedaJuridica" value="ruc" checked>
                        <label for="porRuc">POR RUC</label>
                    </div>
                    <div class="modal-radio-option">
                        <input type="radio" id="porRazonSocial" name="tipoBusquedaJuridica" value="razonSocial">
                        <label for="porRazonSocial">POR RAZÓN SOCIAL</label>
                    </div>
                </div>

                <form id="formBusquedaJuridica" class="modal-search-form">
                    <div class="modal-form-group" id="grupoRuc">
                        <label for="rucJuridica">RUC:</label>
                        <input 
                            type="text" 
                            id="rucJuridica" 
                            maxlength="11" 
                            pattern="[0-9]{11}"
                            placeholder="Ingrese 11 dígitos"
                        >
                    </div>
                    <div class="modal-form-group" id="grupoRazonSocial" style="display: none;">
                        <label for="razonSocial">Razón Social:</label>
                        <input 
                            type="text" 
                            id="razonSocial" 
                            placeholder="Ingrese la razón social"
                        >
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-search">
                            <i class="fas fa-search"></i>
                            <span>Buscar</span>
                        </button>
                        <button type="button" class="btn btn-clear" onclick="limpiarModalJuridica()">
                            <i class="fas fa-eraser"></i>
                            <span>Limpiar</span>
                        </button>
                    </div>
                </form>

                <!-- Resultados de búsqueda -->
                <div id="resultadosJuridica" class="modal-results-table" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para la sección de foto */
.photo-section {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 10px;
}

.photo-container {
    width: 160px;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
    overflow: hidden;
}

.photo-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-photo {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 0.9em;
    text-align: center;
    padding: 20px;
}

.no-photo i {
    font-size: 3em;
    margin-bottom: 10px;
    opacity: 0.5;
}

/* Ocultar photo-section cuando no es necesaria */
.photo-section.hidden {
    display: none;
}

/* Ajustar grid cuando no hay foto */
.results-layout.no-photo {
    grid-template-columns: 1fr;
}

.results-layout.no-photo .info-grid {
    max-width: 100%;
}

/* Contenedores de campos con ID para fácil manipulación */
#containerNombres,
#containerApellidoPaterno,
#containerApellidoMaterno,
#containerRazonSocial {
    transition: all 0.3s ease;
}

/* Asegurar que los campos full-width ocupen todo el espacio */
.info-item.full-width {
    grid-column: 1 / -1;
}

/* Ajustes para alineación cuando se ocultan campos */
.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    width: 100%;
}

/* Secciones adicionales */
.asientos-section,
.imagenes-section,
.vehiculo-section {
    margin-top: 30px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #3498db;
}

.section-header h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.2em;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-header i {
    color: #3498db;
}

/* Asientos */
.asientos-container {
    overflow-x: auto;
}

.asientos-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.asientos-table th,
.asientos-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.asientos-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
}

.asientos-table tr:hover {
    background-color: #f8f9fa;
}

/* Imágenes */
.imagenes-viewer {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.imagenes-selector {
    display: flex;
    align-items: center;
    gap: 10px;
}

.imagen-select {
    flex: 1;
    max-width: 300px;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    font-size: 0.95em;
}

.imagen-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 600px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
    overflow: hidden;
}

.imagen-container img {
    max-width: 100%;
    max-height: 800px;
    object-fit: contain;
}

.no-imagen {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 1em;
    padding: 40px;
}

.no-imagen i {
    font-size: 4em;
    margin-bottom: 15px;
    opacity: 0.5;
}

/* Vehículo */
.vehiculo-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.vehiculo-item {
    padding: 12px;
    background: #f8f9fa;
    border-radius: 5px;
    border-left: 3px solid #3498db;
}

.vehiculo-item .label {
    font-size: 0.85em;
    color: #6c757d;
    margin-bottom: 5px;
    font-weight: 600;
    text-transform: uppercase;
}

.vehiculo-item .value {
    font-size: 1em;
    color: #2c3e50;
    font-weight: 500;
}

/* Ocultar completamente la sección de imágenes del grid */
.imagenes-section[style*="display: none"] {
    display: none !important;
    grid-column: unset !important;
    grid-row: unset !important;
}

/* Si no hay imágenes, vehículo ocupa todo el ancho disponible */
.results-layout:not(:has(.imagenes-section:not([style*="display: none"]))) .vehiculo-section {
    grid-column: 1 / -1;
}
/* Cuando NO existan asientos, la sección de imágenes se expande */
.results-layout:not(:has(.asientos-section:not([style*="display: none"]))) 
    .imagenes-section {
    grid-column: 1 / -1 !important;
}



/* Responsive */
@media (max-width: 768px) {
    .asientos-table {
        font-size: 0.85em;
    }
    
    .imagen-container {
        min-height: 400px;
    }
    
    .vehiculo-container {
        grid-template-columns: 1fr;
    }
}

/* Ajustes responsivos */
@media (max-width: 768px) {
    .photo-container {
        width: 120px;
        height: 150px;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .info-item.full-width {
        grid-column: 1;
    }
}
</style>