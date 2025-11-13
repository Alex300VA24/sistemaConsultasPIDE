<div class="consulta-partidas-container">
        <div class="page-title">
            <h1><i class="fas fa-file-contract"></i> Consulta de Partidas Registrales - SUNARP</h1>
        </div>
        
        <div class="content-wrapper">
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

            <!-- Alertas -->
            <div id="alertContainer"></div>

            <!-- Sección de Resultados -->
            <div class="results-section" id="resultsSection" style="display: none;">
                <div class="results-layout">
                    <!-- Foto -->
                    <div class="photo-section">
                        <img id="personaFoto" src="" alt="Foto" style="display: none;">
                        <div class="no-photo" id="noFoto">Sin foto disponible</div>
                    </div>

                    <!-- Información -->
                    <div class="info-grid" id="infoGrid">
                        <!-- Fila 1: Registro, Libro -->
                        <div class="info-item">
                            <span class="info-label">Registro</span>
                            <div class="info-value white-bg" id="registro"></div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Libro</span>
                            <div class="info-value white-bg" id="libro"></div>
                        </div>
                        <div class="info-item"></div>

                        <!-- Fila 2: Apellidos -->
                        <div class="info-item">
                            <span class="info-label">Apellido Paterno</span>
                            <div class="info-value white-bg" id="apellidoPaterno"></div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Apellido Materno</span>
                            <div class="info-value white-bg" id="apellidoMaterno"></div>
                        </div>
                        <div class="info-item"></div>

                        <!-- Fila 3: Nombres -->
                        <div class="info-item full-width">
                            <span class="info-label">Nombres</span>
                            <div class="info-value white-bg" id="nombres"></div>
                        </div>

                        <!-- Fila 4: Documento -->
                        <div class="info-item">
                            <span class="info-label">Tipo de Documento</span>
                            <div class="info-value white-bg" id="tipoDoc"></div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nro. Documento</span>
                            <div class="info-value white-bg" id="nroDoc"></div>
                        </div>
                        <div class="info-item"></div>

                        <!-- Fila 5: Partida -->
                        <div class="info-item">
                            <span class="info-label">Nro. Partida</span>
                            <div class="info-value white-bg" id="nroPartida"></div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nro. Placa</span>
                            <div class="info-value white-bg" id="nroPlaca"></div>
                        </div>
                        <div class="info-item"></div>

                        <!-- Fila 6: Estado -->
                        <div class="info-item">
                            <span class="info-label">Estado</span>
                            <div class="info-value white-bg" id="estado"></div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Zona</span>
                            <div class="info-value white-bg" id="zona"></div>
                        </div>
                        <div class="info-item"></div>

                        <!-- Fila 7: Oficina y Dirección -->
                        <div class="info-item full-width">
                            <span class="info-label">Oficina</span>
                            <div class="info-value white-bg" id="oficina"></div>
                        </div>

                        <div class="info-item full-width">
                            <span class="info-label">Dirección</span>
                            <div class="info-value white-bg" id="direccion"></div>
                        </div>
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
                                <i class="fas fa-search" style="color: black;"></i>
                                <span style="color: black;">Buscar</span>
                            </button>
                            <button type="button" class="btn btn-clear" onclick="limpiarModalNatural()">
                                <i class="fas fa-eraser" style="color: black;"></i>
                                <span style="color: black;">Limpiar</span>
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
                                <i class="fas fa-search" style="color: black;"></i>
                                <span style="color: black;">Buscar</span>
                            </button>
                            <button type="button" class="btn btn-clear" onclick="limpiarModalJuridica()">
                                <i class="fas fa-eraser" style="color: black;"></i>
                                <span style="color: black;">Limpiar</span>
                            </button>
                        </div>
                    </form>

                    <!-- Resultados de búsqueda -->
                    <div id="resultadosJuridica" class="modal-results-table" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>