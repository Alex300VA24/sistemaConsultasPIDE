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
    <div id="resultsSection" style="display: none;" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Foto -->
            <div class="lg:col-span-1" id="photoSection">
                <div class="glass rounded-2xl p-6 shadow-lg border border-white/50">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-camera text-violet-600 mr-2"></i>
                        Fotografía
                    </h3>
                    <div class="aspect-[3/4] bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl overflow-hidden shadow-inner flex items-center justify-center">
                        <img id="personaFoto" src="" alt="Foto" style="display: none;" class="w-full h-full object-cover">
                        <div id="noFoto" class="text-center text-gray-400">
                            <i class="fas fa-user text-6xl mb-3"></i>
                            <p class="text-sm">Sin fotografía</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información -->
            <div class="lg:col-span-2">
                <div class="glass rounded-2xl p-6 shadow-lg border border-white/50">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-violet-600 mr-2"></i>
                        Información del Registro
                    </h3>
                    
                    <div id="infoGrid" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Los campos se generarán dinámicamente -->
                        <div class="bg-white/60 rounded-xl p-4 border border-gray-200">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Libro</span>
                            <div id="libro" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                        
                        <div id="containerNombres" class="bg-white/60 rounded-xl p-4 border border-gray-200 md:col-span-3">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nombres</span>
                            <div id="nombres" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                        
                        <div id="containerApellidoPaterno" class="bg-white/60 rounded-xl p-4 border border-gray-200">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Apellido Paterno</span>
                            <div id="apellidoPaterno" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                        
                        <div id="containerApellidoMaterno" class="bg-white/60 rounded-xl p-4 border border-gray-200">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Apellido Materno</span>
                            <div id="apellidoMaterno" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                        
                        <div class="bg-white/60 rounded-xl p-4 border border-gray-200"></div>
                        
                        <div id="containerRazonSocial" style="display: none;" class="bg-white/60 rounded-xl p-4 border border-gray-200 md:col-span-3">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Razón Social</span>
                            <div id="campoRazonSocial" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                        
                        <div class="bg-white/60 rounded-xl p-4 border border-gray-200">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tipo de Documento</span>
                            <div id="tipoDoc" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                        
                        <div class="bg-white/60 rounded-xl p-4 border border-gray-200">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nro. Documento</span>
                            <div id="nroDoc" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                        
                        <div class="bg-white/60 rounded-xl p-4 border border-gray-200"></div>
                        
                        <div class="bg-white/60 rounded-xl p-4 border border-gray-200">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nro. Partida</span>
                            <div id="nroPartida" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                        
                        <div class="bg-white/60 rounded-xl p-4 border border-gray-200">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nro. Placa</span>
                            <div id="nroPlaca" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                        
                        <div class="bg-white/60 rounded-xl p-4 border border-gray-200"></div>
                        
                        <div class="bg-white/60 rounded-xl p-4 border border-gray-200">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Estado</span>
                            <div id="estado" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                        
                        <div class="bg-white/60 rounded-xl p-4 border border-gray-200">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Zona</span>
                            <div id="zona" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                        
                        <div class="bg-white/60 rounded-xl p-4 border border-gray-200"></div>
                        
                        <div class="bg-white/60 rounded-xl p-4 border border-gray-200 md:col-span-3">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Oficina</span>
                            <div id="oficina" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                        
                        <div class="bg-white/60 rounded-xl p-4 border border-gray-200 md:col-span-3">
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Dirección</span>
                            <div id="direccion" class="mt-1 text-lg font-semibold text-gray-800">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asientos Registrales -->
        <div id="asientosSection" style="display: none;" class="glass rounded-2xl p-6 shadow-lg border border-white/50">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-file-invoice text-violet-600 mr-2"></i>
                Asientos Registrales
            </h3>
            <div id="asientosContainer" class="overflow-x-auto"></div>
        </div>

        <!-- Imágenes de Documentos -->
        <div id="imagenesSection" style="display: none;" class="glass rounded-2xl p-6 shadow-lg border border-white/50">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-images text-violet-600 mr-2"></i>
                Imágenes de Documentos
            </h3>
            
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <label for="selectImagenes" class="text-sm font-medium text-gray-700">Seleccionar página:</label>
                    <select id="selectImagenes" class="flex-1 max-w-xs px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent transition duration-200 outline-none bg-white/80"></select>
                </div>

                <div class="flex flex-wrap items-center gap-2 p-4 bg-white/60 rounded-xl border border-gray-200">
                    <button type="button" id="btnZoomOut" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" id="btnZoomReset" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button type="button" id="btnZoomIn" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-plus"></i>
                    </button>
                    <span id="zoomLabel" class="px-3 font-semibold text-gray-700">100%</span>
                    <button type="button" id="btnVerImagen" class="ml-auto px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition flex items-center gap-2">
                        <i class="fas fa-external-link-alt"></i>
                        <span>Ver</span>
                    </button>
                    <button type="button" id="btnDescargar" class="px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition flex items-center gap-2">
                        <i class="fas fa-download"></i>
                        <span>Descargar</span>
                    </button>
                </div>

                <div class="border-2 border-gray-300 rounded-xl overflow-auto bg-gray-50" style="max-height: 600px; min-height: 400px;">
                    <div class="inline-block min-w-full text-center">
                        <img id="imagenViewer" src="" alt="Documento" style="display: none;" class="inline-block max-w-full h-auto shadow-lg rounded">
                        <div id="noImagen" class="flex flex-col items-center justify-center py-20 text-gray-400">
                            <i class="fas fa-image text-6xl mb-3"></i>
                            <span>Seleccione una página</span>
                        </div>
                    </div>
                </div>
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
    <div class="glass rounded-2xl shadow-2xl max-w-2xl w-full mx-4 border border-white/50">
        <div class="bg-gradient-to-r from-violet-600 to-violet-700 text-white p-6 rounded-t-2xl flex items-center justify-between">
            <h5 class="text-xl font-bold flex items-center gap-2">
                <i class="fas fa-user"></i>
                Búsqueda de Personas Naturales
            </h5>
            <button type="button" class="text-white hover:text-gray-200 text-2xl leading-none" data-modal="modalBusquedaNatural">&times;</button>
        </div>
        <div class="p-6">
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
    <div class="glass rounded-2xl shadow-2xl max-w-2xl w-full mx-4 border border-white/50">
        <div class="bg-gradient-to-r from-violet-600 to-violet-700 text-white p-6 rounded-t-2xl flex items-center justify-between">
            <h5 class="text-xl font-bold flex items-center gap-2">
                <i class="fas fa-building"></i>
                Búsqueda de Personas Jurídicas
            </h5>
            <button type="button" class="text-white hover:text-gray-200 text-2xl leading-none" data-modal="modalBusquedaJuridica">&times;</button>
        </div>
        <div class="p-6">
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
