<div class="space-y-6">
    <!-- Header -->
    <div class="glass rounded-2xl p-6 shadow-lg border border-white/50">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-magnifying-glass text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Consulta DNI - RENIEC</h1>
                <p class="text-sm text-gray-600">Registro Nacional de Identificación y Estado Civil</p>
            </div>
        </div>
    </div>

    <!-- Formulario de búsqueda -->
    <div class="glass rounded-2xl p-6 shadow-lg border border-white/50">
        <div id="alertContainerDNI" class="mb-4"></div>
        
        <form method="POST" action="" id="searchFormDNI">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="dniInput" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-id-card text-emerald-600 mr-2"></i>Número de DNI
                    </label>
                    <input 
                        type="text" 
                        id="dniInput" 
                        name="dni" 
                        maxlength="8" 
                        pattern="[0-9]{8}" 
                        placeholder="Ingrese 8 dígitos"
                        value="<?php echo isset($_POST['dni']) ? htmlspecialchars($_POST['dni']) : ''; ?>"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition duration-200 outline-none bg-white/80"
                    >
                </div>
                <div class="flex gap-2">
                    <button type="submit" name="buscar" id="btnBuscarDNI" class="flex-1 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/30">
                        <i class="fas fa-magnifying-glass"></i>
                        <span>Buscar</span>
                    </button>
                    <button type="button" onclick="limpiarFormularioDNI()" class="px-4 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition duration-200">
                        <i class="fas fa-eraser"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php if (isset($mensaje)): ?>
        <div class="glass rounded-xl p-4 border <?php 
            echo $tipo_mensaje === 'success' ? 'border-emerald-200 bg-emerald-50/80' : 
                ($tipo_mensaje === 'danger' ? 'border-red-200 bg-red-50/80' : 
                ($tipo_mensaje === 'warning' ? 'border-yellow-200 bg-yellow-50/80' : 
                'border-blue-200 bg-blue-50/80')); 
        ?>">
            <div class="flex items-center">
                <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle text-emerald-600' : ($tipo_mensaje === 'danger' ? 'exclamation-circle text-red-600' : ($tipo_mensaje === 'warning' ? 'exclamation-triangle text-yellow-600' : 'info-circle text-blue-600')); ?> mr-3 text-xl"></i>
                <span class="font-medium text-gray-800"><?php echo $mensaje; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Resultados -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Foto -->
        <div class="lg:col-span-1">
            <div class="glass rounded-2xl p-6 shadow-lg border border-white/50">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-camera text-emerald-600 mr-2"></i>
                    Fotografía
                </h3>
                <div id="photoContainer" class="aspect-[3/4] bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl overflow-hidden shadow-inner flex items-center justify-center">
                    <?php if (isset($persona['foto']) && !empty($persona['foto'])): ?>
                        <img src="<?php echo htmlspecialchars($persona['foto']); ?>" alt="Foto de persona" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="text-center text-gray-400">
                            <i class="fas fa-user text-6xl mb-3"></i>
                            <p class="text-sm">Sin fotografía</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Información personal -->
        <div class="lg:col-span-2">
            <div class="glass rounded-2xl p-6 shadow-lg border border-white/50">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user-circle text-emerald-600 mr-2"></i>
                    Información Personal
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white/60 rounded-xl p-4 border border-gray-200">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">DNI</span>
                        <div class="mt-1 text-lg font-semibold text-gray-800" id="result-dni">
                            <?php echo isset($persona['dni']) ? htmlspecialchars($persona['dni']) : '-'; ?>
                        </div>
                    </div>
                    
                    <div class="bg-white/60 rounded-xl p-4 border border-gray-200">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nombres</span>
                        <div class="mt-1 text-lg font-semibold text-gray-800" id="result-nombres">
                            <?php echo isset($persona['nombres']) ? htmlspecialchars($persona['nombres']) : '-'; ?>
                        </div>
                    </div>
                    
                    <div class="bg-white/60 rounded-xl p-4 border border-gray-200">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Apellido Paterno</span>
                        <div class="mt-1 text-lg font-semibold text-gray-800" id="result-paterno">
                            <?php echo isset($persona['apellido_paterno']) ? htmlspecialchars($persona['apellido_paterno']) : '-'; ?>
                        </div>
                    </div>
                    
                    <div class="bg-white/60 rounded-xl p-4 border border-gray-200">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Apellido Materno</span>
                        <div class="mt-1 text-lg font-semibold text-gray-800" id="result-materno">
                            <?php echo isset($persona['apellido_materno']) ? htmlspecialchars($persona['apellido_materno']) : '-'; ?>
                        </div>
                    </div>
                    
                    <div class="bg-white/60 rounded-xl p-4 border border-gray-200">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Estado Civil</span>
                        <div class="mt-1 text-lg font-semibold text-gray-800" id="result-estado-civil">
                            <?php echo isset($persona['estado_civil']) ? htmlspecialchars($persona['estado_civil']) : '-'; ?>
                        </div>
                    </div>
                    
                    <div class="bg-white/60 rounded-xl p-4 border border-gray-200">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Ubigeo</span>
                        <div class="mt-1 text-lg font-semibold text-gray-800" id="result-ubigeo">
                            <?php echo isset($persona['ubigeo']) ? htmlspecialchars($persona['ubigeo']) : '-'; ?>
                        </div>
                    </div>
                    
                    <div class="bg-white/60 rounded-xl p-4 border border-gray-200 md:col-span-2">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Dirección</span>
                        <div class="mt-1 text-lg font-semibold text-gray-800" id="result-direccion">
                            <?php echo isset($persona['direccion']) ? htmlspecialchars($persona['direccion']) : '-'; ?>
                        </div>
                    </div>
                    
                    <div class="bg-white/60 rounded-xl p-4 border border-gray-200 md:col-span-2">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Restricción</span>
                        <div class="mt-1 text-lg font-semibold text-gray-800" id="result-restriccion">
                            <?php echo isset($persona['restriccion']) ? htmlspecialchars($persona['restriccion']) : '-'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
