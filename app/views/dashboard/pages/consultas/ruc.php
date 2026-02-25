<div class="space-y-6">
    <!-- Header -->
    <div class="glass rounded-2xl p-6 shadow-lg border border-white/50">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-magnifying-glass text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Consulta RUC - SUNAT</h1>
                <p class="text-sm text-gray-600">Superintendencia Nacional de Aduanas y de Administración Tributaria</p>
            </div>
        </div>
    </div>

    <!-- Formulario de búsqueda -->
    <div class="glass rounded-2xl p-6 shadow-lg border border-white/50">
        <div id="alertContainerRUC" class="mb-4"></div>
        
        <form method="POST" action="" id="searchFormRUC">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="ruc" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-file-invoice text-red-600 mr-2"></i>Número de RUC
                    </label>
                    <input 
                        type="text" 
                        id="ruc" 
                        name="ruc" 
                        maxlength="11" 
                        pattern="[0-9]{11}" 
                        placeholder="Ingrese 11 dígitos"
                        value="<?php echo isset($_POST['ruc']) ? htmlspecialchars($_POST['ruc']) : ''; ?>"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent transition duration-200 outline-none bg-white/80"
                    >
                </div>
                <div class="flex gap-2">
                    <button type="submit" name="buscar" class="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold py-3 px-4 rounded-xl transition duration-200 flex items-center justify-center gap-2 shadow-lg shadow-red-500/30">
                        <i class="fas fa-magnifying-glass"></i>
                        <span>Buscar</span>
                    </button>
                    <button type="button" onclick="limpiarFormularioRUC()" class="px-4 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition duration-200">
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
    <div class="glass rounded-2xl p-6 shadow-lg border border-white/50">
        <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-building text-red-600 mr-2"></i>
            Información del Contribuyente
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- RUC -->
            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-4 border-2 border-red-200 lg:col-span-3">
                <span class="text-xs font-medium text-red-700 uppercase tracking-wide">RUC</span>
                <div class="mt-1 text-2xl font-bold text-red-800" data-campo="ruc">
                    <?php echo isset($contribuyente['ruc']) ? htmlspecialchars($contribuyente['ruc']) : '-'; ?>
                </div>
            </div>

            <!-- Razón Social -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border-2 border-blue-200 lg:col-span-3">
                <span class="text-xs font-medium text-blue-700 uppercase tracking-wide">Nombre y/o Razón Social</span>
                <div class="mt-1 text-xl font-bold text-blue-800" data-campo="razon_social">
                    <?php echo isset($contribuyente['razon_social']) ? htmlspecialchars($contribuyente['razon_social']) : '-'; ?>
                </div>
            </div>

            <!-- Resto de campos -->
            <?php 
            $campos = [
                'estado_contribuyente' => 'Estado del Contribuyente',
                'tipo_persona' => 'Tipo de Persona',
                'tipo_contribuyente' => 'Tipo de Contribuyente',
                'actividad_economica' => 'Actividad Económica',
                'fecha_alta' => 'Fecha de Alta',
                'fecha_baja' => 'Fecha de Baja',
                'fecha_actualizacion' => 'Fecha de Actualización',
                'codigo_ubigeo' => 'Código de Ubigeo',
                'departamento' => 'Departamento',
                'provincia' => 'Provincia',
                'distrito' => 'Distrito',
                'tipo_via' => 'Tipo de Vía',
                'nombre_via' => 'Nombre de Vía',
                'numero' => 'Número',
                'interior' => 'Interior',
                'tipo_zona' => 'Tipo de Zona',
                'nombre_zona' => 'Nombre de la Zona',
                'referencia' => 'Referencia',
                'condicion_domicilio' => 'Condición del Domicilio',
                'estado_activo' => 'Estado Activo',
                'estado_habido' => 'Estado Habido',
                'dependencia' => 'Dependencia',
                'codigo_secuencia' => 'Código Secuencia'
            ];
            
            foreach ($campos as $key => $label):
                $colSpan = in_array($key, ['actividad_economica', 'nombre_via', 'nombre_zona', 'referencia']) ? 'lg:col-span-2' : '';
            ?>
                <div class="bg-white/60 rounded-xl p-4 border border-gray-200 <?= $colSpan ?>">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide"><?= $label ?></span>
                    <div class="mt-1 text-base font-semibold text-gray-800" data-campo="<?= $key ?>">
                        <?php echo isset($contribuyente[$key]) ? htmlspecialchars($contribuyente[$key]) : '-'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
