<div class="space-y-6">
    <!-- Header -->
    <div class="glass rounded-2xl p-6 shadow-lg border border-white/50">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-violet-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-file-contract text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Consulta de Partidas Registrales - SUNARP</h1>
                <p class="text-sm text-gray-600">Superintendencia Nacional de los Registros Públicos</p>
            </div>
        </div>
    </div>

    <!-- Alertas -->
    <div id="alertContainerPartidas"></div>

    <!-- Selector de Tipo de Persona -->
    <div class="glass rounded-2xl p-6 shadow-lg border border-white/50">
        <div class="flex flex-wrap gap-3">
            <label class="flex-1 min-w-[200px] cursor-pointer">
                <input type="radio" id="personaNatural" name="tipoPersona" value="natural" checked class="peer sr-only">
                <div class="px-6 py-3 rounded-xl border-2 border-gray-300 bg-white/60 peer-checked:border-violet-500 peer-checked:bg-violet-50 transition-all text-center font-semibold text-gray-700 peer-checked:text-violet-700">
                    <i class="fas fa-user mr-2"></i>PERSONA NATURAL
                </div>
            </label>
            <label class="flex-1 min-w-[200px] cursor-pointer">
                <input type="radio" id="personaJuridica" name="tipoPersona" value="juridica" class="peer sr-only">
                <div class="px-6 py-3 rounded-xl border-2 border-gray-300 bg-white/60 peer-checked:border-violet-500 peer-checked:bg-violet-50 transition-all text-center font-semibold text-gray-700 peer-checked:text-violet-700">
                    <i class="fas fa-building mr-2"></i>PERSONA JURÍDICA
                </div>
            </label>
            <label class="flex-1 min-w-[200px] cursor-pointer">
                <input type="radio" id="porPartidas" name="tipoPersona" value="partida" class="peer sr-only">
                <div class="px-6 py-3 rounded-xl border-2 border-gray-300 bg-white/60 peer-checked:border-violet-500 peer-checked:bg-violet-50 transition-all text-center font-semibold text-gray-700 peer-checked:text-violet-700">
                    <i class="fas fa-file-alt mr-2"></i>POR PARTIDA
                </div>
            </label>
        </div>
    </div>

    <!-- Formulario de Búsqueda -->
    <div class="glass rounded-2xl p-6 shadow-lg border border-white/50">
        <form id="searchFormPartidas">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="persona" id="labelPersona" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user text-violet-600 mr-2"></i>Persona:
                    </label>
                    <input
                        type="text"
                        id="persona"
                        name="persona"
                        readonly
                        placeholder="Haga clic en el botón de búsqueda"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent transition duration-200 outline-none bg-white/80"
                    >
                    <div id="contenedorOficina" style="display: none;" class="mt-3">
                        <select name="oficinasRegistrales" id="oficinaRegistralID" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent transition duration-200 outline-none bg-white/80">
                            <option value="">Seleccione Oficina Registral</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" id="btnBuscarPersona" class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 flex items-center justify-center gap-2 shadow-lg shadow-blue-500/30">
                        <i class="fas fa-magnifying-glass"></i>
                        <span>Buscar</span>
                    </button>
                    <button type="button" id="btnConsultar" disabled class="flex-1 bg-gradient-to-r from-violet-500 to-violet-600 hover:from-violet-600 hover:to-violet-700 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 flex items-center justify-center gap-2 shadow-lg shadow-violet-500/30 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-search"></i>
                        <span>Consultar</span>
                    </button>
                    <button type="button" id="btnLimpiar" class="px-4 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition duration-200">
                        <i class="fas fa-eraser"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Sección de Resultados -->
    <div id="resultsSection" style="display: none;">
        <!-- Contenedor Principal: Foto + Información -->
        <div class="glass rounded-2xl p-6 shadow-lg border border-white/50 mb-6" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); border: 1px solid rgba(255, 255, 255, 0.5); margin-bottom: 1.5rem;">
            <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
                <div style="display: grid; grid-template-columns: 300px 1fr; gap: 1.5rem;">
                    <!-- Foto a la izquierda -->
                    <div id="photoSection" style="display: none;">
                        <h3 style="font-size: 1.125rem; font-weight: 600; color: #1f2937; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-camera" style="color: #8b5cf6;"></i>
                            Fotografía
                        </h3>
                        <div id="fotoContainer" style="aspect-ratio: 3/4; background: linear-gradient(to bottom right, #f3f4f6, #e5e7eb); border-radius: 0.75rem; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; min-height: 400px; max-height: 500px;">
                            <div style="text-align: center; color: #9ca3af;">
                                <i class="fas fa-user" style="font-size: 4rem; margin-bottom: 0.75rem; display: block; opacity: 0.5;"></i>
                                <p style="font-size: 0.875rem; margin: 0;">Sin fotografía</p>
                            </div>
                        </div>
                    </div>

                    <!-- Información a la derecha -->
                    <div>
                        <h3 style="font-size: 1.125rem; font-weight: 600; color: #1f2937; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-info-circle" style="color: #8b5cf6;"></i>
                            Información del Registro
                        </h3>
                        
                        <div id="infoGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <!-- Nombres -->
                            <div id="containerNombres" style="background: rgba(255, 255, 255, 0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; grid-column: span 2; transition: all 0.2s ease;">
                                <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Nombres</span>
                                <div id="nombres" style="margin-top: 0.25rem; font-size: 1.125rem; font-weight: 600; color: #1f2937;">-</div>
                            </div>
                            
                            <!-- Apellido Paterno -->
                            <div id="containerApellidoPaterno" style="background: rgba(255, 255, 255, 0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; transition: all 0.2s ease;">
                                <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Apellido Paterno</span>
                                <div id="apellidoPaterno" style="margin-top: 0.25rem; font-size: 1.125rem; font-weight: 600; color: #1f2937;">-</div>
                            </div>
                            
                            <!-- Apellido Materno -->
                            <div id="containerApellidoMaterno" style="background: rgba(255, 255, 255, 0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; transition: all 0.2s ease;">
                                <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Apellido Materno</span>
                                <div id="apellidoMaterno" style="margin-top: 0.25rem; font-size: 1.125rem; font-weight: 600; color: #1f2937;">-</div>
                            </div>
                            
                            <!-- Tipo de Documento -->
                            <div style="background: rgba(255, 255, 255, 0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; transition: all 0.2s ease;">
                                <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Tipo de Documento</span>
                                <div id="tipoDoc" style="margin-top: 0.25rem; font-size: 1.125rem; font-weight: 600; color: #1f2937;">-</div>
                            </div>
                            
                            <!-- Nro. Documento -->
                            <div style="background: rgba(255, 255, 255, 0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; transition: all 0.2s ease;">
                                <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Nro. Documento</span>
                                <div id="nroDoc" style="margin-top: 0.25rem; font-size: 1.125rem; font-weight: 600; color: #1f2937;">-</div>
                            </div>
                            
                            <!-- Razón Social -->
                            <div id="containerRazonSocial" style="display: none; background: rgba(255, 255, 255, 0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; grid-column: span 2; transition: all 0.2s ease;">
                                <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Razón Social</span>
                                <div id="campoRazonSocial" style="margin-top: 0.25rem; font-size: 1.125rem; font-weight: 600; color: #1f2937;">-</div>
                            </div>
                            
                            <!-- Nro. Partida -->
                            <div id="containerNroPartida" style="background: rgba(255, 255, 255, 0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; transition: all 0.2s ease;">
                                <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Nro. Partida</span>
                                <div id="nroPartida" style="margin-top: 0.25rem; font-size: 1.125rem; font-weight: 600; color: #1f2937;">-</div>
                            </div>
                            
                            <!-- Nro. Placa -->
                            <div id="containerNroPlaca" style="background: rgba(255, 255, 255, 0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; transition: all 0.2s ease;">
                                <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Nro. Placa</span>
                                <div id="nroPlaca" style="margin-top: 0.25rem; font-size: 1.125rem; font-weight: 600; color: #1f2937;">-</div>
                            </div>
                            
                            <!-- Estado -->
                            <div id="containerEstado" style="background: rgba(255, 255, 255, 0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; transition: all 0.2s ease;">
                                <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Estado</span>
                                <div id="estado" style="margin-top: 0.25rem; font-size: 1.125rem; font-weight: 600; color: #1f2937;">-</div>
                            </div>
                            
                            <!-- Zona -->
                            <div id="containerZona" style="background: rgba(255, 255, 255, 0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; transition: all 0.2s ease;">
                                <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Zona</span>
                                <div id="zona" style="margin-top: 0.25rem; font-size: 1.125rem; font-weight: 600; color: #1f2937;">-</div>
                            </div>
                            
                            <!-- Libro -->
                            <div id="containerLibro" style="background: rgba(255, 255, 255, 0.6); border-radius: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb; transition: all 0.2s ease;">
                                <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Libro</span>
                                <div id="libro" style="margin-top: 0.25rem; font-size: 1.125rem; font-weight: 600; color: #1f2937;">-</div>
                            </div>
                        
                        <!-- Oficina -->
                        <div id="containerOficina" class="bg-white/60 rounded-xl p-4 border border-gray-200 md:col-span-2 hover:shadow-md transition duration-200">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Oficina</span>
                            <div id="oficina" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                        
                        <!-- Dirección -->
                        <div id="containerDireccion" class="bg-white/60 rounded-xl p-4 border border-gray-200 md:col-span-2 hover:shadow-md transition duration-200">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Dirección</span>
                            <div id="direccion" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asientos Registrales -->
        <div id="asientosSection" style="display: none;" class="glass rounded-2xl p-6 shadow-lg border border-white/50 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-file-invoice text-violet-600 mr-2"></i>
                Asientos Registrales
            </h3>
            <div id="asientosContainer" class="overflow-x-auto"></div>
        </div>

        <!-- Imágenes de Documentos - Viewer Profesional -->
        <div id="imagenesSection" style="display: none;" class="glass rounded-2xl p-6 shadow-lg border border-white/50 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-file-image text-violet-600 mr-2"></i>
                Visor de Documentos
            </h3>
            
            <!-- Controles superiores -->
            <div class="flex flex-col lg:flex-row gap-4 mb-6">
                <!-- Selector de página -->
                <div class="flex items-center gap-3 flex-1">
                    <label for="selectImagenes" class="text-sm font-semibold text-gray-700 whitespace-nowrap">
                        <i class="fas fa-file mr-2"></i>Página:
                    </label>
                    <select id="selectImagenes" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent outline-none bg-white font-medium"></select>
                </div>
                
                <!-- Controles de zoom -->
                <div class="flex items-center gap-2 bg-white/60 rounded-lg p-2 border border-gray-200">
                    <button type="button" id="btnZoomOut" class="p-2 hover:bg-gray-100 rounded-lg transition text-gray-600 hover:text-gray-800 font-semibold" title="Reducir (-)">
                        <i class="fas fa-minus text-sm"></i>
                    </button>
                    <button type="button" id="btnZoomReset" class="p-2 hover:bg-gray-100 rounded-lg transition text-gray-600 hover:text-gray-800 font-semibold" title="Restaurar (100%)">
                        <i class="fas fa-redo text-sm"></i>
                    </button>
                    <button type="button" id="btnZoomIn" class="p-2 hover:bg-gray-100 rounded-lg transition text-gray-600 hover:text-gray-800 font-semibold" title="Aumentar (+)">
                        <i class="fas fa-plus text-sm"></i>
                    </button>
                    <div class="w-px h-6 bg-gray-300 mx-1"></div>
                    <span id="zoomLabel" class="px-3 py-1 bg-violet-100 text-violet-700 rounded-lg text-sm font-bold min-w-[60px] text-center">100%</span>
                </div>
                
                <!-- Botones de acción -->
                <div class="flex gap-2">
                    <button type="button" id="btnVerImagen" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all font-semibold flex items-center gap-2 shadow-md whitespace-nowrap">
                        <i class="fas fa-expand"></i>
                        <span>Expandir</span>
                    </button>
                    <button type="button" id="btnDescargar" class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-lg hover:from-emerald-600 hover:to-emerald-700 transition-all font-semibold flex items-center gap-2 shadow-md whitespace-nowrap">
                        <i class="fas fa-download"></i>
                        <span>Descargar</span>
                    </button>
                </div>
            </div>
            
            <!-- Visor de imagen principal -->
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border-2 border-gray-300 overflow-auto shadow-lg" style="max-height: 600px; min-height: 450px;">
                <div class="w-full h-full flex items-center justify-center relative p-4" id="imageViewerContainer">
                    <img id="imagenViewer" src="" alt="Documento" style="display: none; transition: transform 0.2s ease;" class="shadow-xl" />
                    <div id="noImagen" class="flex flex-col items-center justify-center text-gray-400 py-16 px-8">
                        <i class="fas fa-image text-7xl mb-4 opacity-40"></i>
                        <span class="text-xl font-semibold">Seleccione una página para ver</span>
                        <p class="text-sm mt-2">Los documentos aparecerán aquí</p>
                    </div>
                </div>
            </div>
            
            <!-- Miniatura de páginas (si hay múltiples) -->
            <div id="thumbnailContainer" class="mt-4 flex gap-3 overflow-x-auto pb-3 px-1" style="display: none;">
                <!-- Las miniaturas se generarán dinámicamente -->
            </div>
        </div>

        <!-- Información Vehicular -->
        <div id="vehiculoSection" style="display: none;" class="glass rounded-2xl p-6 shadow-lg border border-white/50">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-car text-violet-600 mr-2"></i>
                Información Vehicular
            </h3>
            <div id="vehiculoContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
        </div>
    </div>
</div>

<!-- Modales se mantienen igual pero con estilos actualizados -->
<!-- Modal: Búsqueda de Personas Naturales -->
<div id="modalBusquedaNatural" class="fixed inset-0 bg-black/50 items-center justify-center z-50 backdrop-blur-sm" style="display: none;">
    <div class="glass rounded-2xl shadow-2xl w-[90vw] max-w-5xl max-h-[90vh] mx-4 border border-white/50 overflow-hidden">
        <div class="bg-gradient-to-r from-violet-600 to-violet-700 text-white p-6 rounded-t-2xl flex items-center justify-between">
            <h5 class="text-xl font-bold flex items-center gap-2">
                <i class="fas fa-user"></i>
                Búsqueda de Personas Naturales
            </h5>
            <button type="button" class="text-white hover:text-gray-200 text-2xl leading-none" data-modal="modalBusquedaNatural">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
            <form id="formBusquedaNatural" class="mb-4">
                <div class="mb-4">
                    <label for="dniNatural" class="block text-sm font-medium text-gray-700 mb-2">DNI:</label>
                    <input
                        type="text"
                        id="dniNatural"
                        maxlength="8"
                        pattern="[0-9]{8}"
                        placeholder="Ingrese 8 dígitos"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent transition duration-200 outline-none bg-white/80"
                    >
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-violet-500 to-violet-600 text-white font-semibold py-3 px-4 rounded-xl hover:from-violet-600 hover:to-violet-700 transition flex items-center justify-center gap-2">
                        <i class="fas fa-search"></i>
                        <span>Buscar</span>
                    </button>
                    <button type="button" onclick="limpiarModalNatural()" class="px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition">
                        <i class="fas fa-eraser mr-2"></i>
                        Limpiar
                    </button>
                </div>
            </form>
            <div id="resultadosNatural" style="display: none;"></div>
        </div>
    </div>
</div>

<!-- Modal: Búsqueda de Personas Jurídicas -->
<div id="modalBusquedaJuridica" class="fixed inset-0 bg-black/50 items-center justify-center z-50 backdrop-blur-sm" style="display: none;">
    <div class="glass rounded-2xl shadow-2xl w-[95vw] max-w-7xl max-h-[90vh] mx-4 border border-white/50 overflow-hidden">
        <div class="bg-gradient-to-r from-violet-600 to-violet-700 text-white p-6 rounded-t-2xl flex items-center justify-between">
            <h5 class="text-xl font-bold flex items-center gap-2">
                <i class="fas fa-building"></i>
                Búsqueda de Personas Jurídicas
            </h5>
            <button type="button" class="text-white hover:text-gray-200 text-2xl leading-none" data-modal="modalBusquedaJuridica">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
            <div class="flex gap-3 mb-4">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" id="porRuc" name="tipoBusquedaJuridica" value="ruc" checked class="peer sr-only">
                    <div class="px-4 py-2 rounded-xl border-2 border-gray-300 bg-white/60 peer-checked:border-violet-500 peer-checked:bg-violet-50 transition-all text-center font-semibold text-gray-700 peer-checked:text-violet-700">
                        POR RUC
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" id="porRazonSocial" name="tipoBusquedaJuridica" value="razonSocial" class="peer sr-only">
                    <div class="px-4 py-2 rounded-xl border-2 border-gray-300 bg-white/60 peer-checked:border-violet-500 peer-checked:bg-violet-50 transition-all text-center font-semibold text-gray-700 peer-checked:text-violet-700">
                        POR RAZÓN SOCIAL
                    </div>
                </label>
            </div>

            <form id="formBusquedaJuridica" class="mb-4">
                <div id="grupoRuc" class="mb-4">
                    <label for="rucJuridica" class="block text-sm font-medium text-gray-700 mb-2">RUC:</label>
                    <input
                        type="text"
                        id="rucJuridica"
                        maxlength="11"
                        pattern="[0-9]{11}"
                        placeholder="Ingrese 11 dígitos"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent transition duration-200 outline-none bg-white/80"
                    >
                </div>
                <div id="grupoRazonSocial" style="display: none;" class="mb-4">
                    <label for="razonSocial" class="block text-sm font-medium text-gray-700 mb-2">Razón Social:</label>
                    <input
                        type="text"
                        id="razonSocial"
                        placeholder="Ingrese la razón social"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent transition duration-200 outline-none bg-white/80"
                    >
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-violet-500 to-violet-600 text-white font-semibold py-3 px-4 rounded-xl hover:from-violet-600 hover:to-violet-700 transition flex items-center justify-center gap-2">
                        <i class="fas fa-search"></i>
                        <span>Buscar</span>
                    </button>
                    <button type="button" onclick="limpiarModalJuridica()" class="px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition">
                        <i class="fas fa-eraser mr-2"></i>
                        Limpiar
                    </button>
                </div>
            </form>
            <div id="resultadosJuridica" style="display: none;"></div>
        </div>
    </div>
</div>
