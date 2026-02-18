<style>
/* Card hover effects */
.service-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.service-card:hover {
    transform: translateY(-5px);
}

/* Animated checkmarks */
.check-item {
    transition: all 0.2s ease;
}
.check-item:hover {
    transform: translateX(5px);
}
</style>

<!-- Services Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- RENIEC Card -->
    <div class="service-card glass rounded-2xl overflow-hidden shadow-lg border-t-4 border-emerald-500 relative group">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
        
        <div class="p-6 relative">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-id-card text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">RENIEC</h3>
                        <span class="text-xs text-gray-500 font-medium">Registro Nacional</span>
                    </div>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                    <i class="fas fa-shield-alt text-emerald-600"></i>
                </div>
            </div>

            <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                Registro Nacional de Identificación y Estado Civil
            </p>

            <ul class="space-y-3 mb-6">
                <li class="check-item flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-emerald-600 text-xs"></i>
                    </div>
                    <span>Consulta por DNI</span>
                </li>
                <li class="check-item flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-emerald-600 text-xs"></i>
                    </div>
                    <span>Datos personales</span>
                </li>
                <li class="check-item flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-emerald-600 text-xs"></i>
                    </div>
                    <span>Estado del documento</span>
                </li>
                <li class="check-item flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-emerald-600 text-xs"></i>
                    </div>
                    <span>Foto y firma digital</span>
                </li>
            </ul>

            <button onclick="irConsultaReniec()" class="w-full py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 transform hover:scale-[1.02] transition-all flex items-center justify-center gap-2 group/btn">
                <i class="fas fa-search group-hover/btn:rotate-12 transition-transform"></i>
                <span>Consultar RENIEC</span>
            </button>
        </div>
    </div>

    <!-- SUNAT Card -->
    <div class="service-card glass rounded-2xl overflow-hidden shadow-lg border-t-4 border-red-500 relative group">
        <div class="absolute inset-0 bg-gradient-to-br from-red-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
        
        <div class="p-6 relative">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-file-invoice-dollar text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">SUNAT</h3>
                        <span class="text-xs text-gray-500 font-medium">Administración Tributaria</span>
                    </div>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <i class="fas fa-calculator text-red-600"></i>
                </div>
            </div>

            <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                Superintendencia Nacional de Aduanas y de Administración Tributaria
            </p>

            <ul class="space-y-3 mb-6">
                <li class="check-item flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-red-600 text-xs"></i>
                    </div>
                    <span>Consulta por RUC</span>
                </li>
                <li class="check-item flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-red-600 text-xs"></i>
                    </div>
                    <span>Razón social</span>
                </li>
                <li class="check-item flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-red-600 text-xs"></i>
                    </div>
                    <span>Estado del contribuyente</span>
                </li>
                <li class="check-item flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-red-600 text-xs"></i>
                    </div>
                    <span>Domicilio fiscal</span>
                </li>
            </ul>

            <button onclick="irConsultaSunat()" class="w-full py-3 rounded-xl bg-gradient-to-r from-red-500 to-red-600 text-white font-semibold shadow-lg shadow-red-500/30 hover:shadow-red-500/50 transform hover:scale-[1.02] transition-all flex items-center justify-center gap-2 group/btn">
                <i class="fas fa-search group-hover/btn:rotate-12 transition-transform"></i>
                <span>Consultar SUNAT</span>
            </button>
        </div>
    </div>

    <!-- SUNARP Card -->
    <div class="service-card glass rounded-2xl overflow-hidden shadow-lg border-t-4 border-violet-500 relative group">
        <div class="absolute inset-0 bg-gradient-to-br from-violet-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
        
        <div class="p-6 relative">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-400 to-violet-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-home text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">SUNARP</h3>
                        <span class="text-xs text-gray-500 font-medium">Registros Públicos</span>
                    </div>
                </div>
                <div class="w-10 h-10 rounded-full bg-violet-100 flex items-center justify-center">
                    <i class="fas fa-archive text-violet-600"></i>
                </div>
            </div>

            <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                Superintendencia Nacional de los Registros Públicos
            </p>

            <ul class="space-y-3 mb-6">
                <li class="check-item flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-violet-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-violet-600 text-xs"></i>
                    </div>
                    <span>Consulta registral</span>
                </li>
                <li class="check-item flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-violet-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-violet-600 text-xs"></i>
                    </div>
                    <span>Propiedades inmuebles</span>
                </li>
                <li class="check-item flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-violet-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-violet-600 text-xs"></i>
                    </div>
                    <span>Vehículos registrados</span>
                </li>
                <li class="check-item flex items-center gap-3 text-sm text-gray-700">
                    <div class="w-5 h-5 rounded-full bg-violet-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-violet-600 text-xs"></i>
                    </div>
                    <span>Personas jurídicas</span>
                </li>
            </ul>

            <button onclick="irConsultaSunarp()" class="w-full py-3 rounded-xl bg-gradient-to-r from-violet-500 to-violet-600 text-white font-semibold shadow-lg shadow-violet-500/30 hover:shadow-violet-500/50 transform hover:scale-[1.02] transition-all flex items-center justify-center gap-2 group/btn">
                <i class="fas fa-search group-hover/btn:rotate-12 transition-transform"></i>
                <span>Consultar SUNARP</span>
            </button>
        </div>
    </div>
</div>

<!-- Footer Info -->
<div class="glass rounded-2xl p-6 text-center border border-white/50">
    <div class="flex items-center justify-center gap-2 mb-2 text-gray-600">
        <i class="fas fa-info-circle text-blue-500"></i>
        <span class="font-medium">Sistema de Consultas PIDE v1.5</span>
        <span class="text-gray-400">|</span>
        <span class="text-sm">Plataforma de Interoperabilidad del Estado Peruano</span>
    </div>
    <p class="text-xs text-gray-500">Acceso autorizado únicamente para entidades del Estado</p>
</div>

<!-- Modal de cambio de password obligatorio -->
<div id="modalPasswordObligatorio" class="fixed inset-0 bg-black/60 items-center justify-center z-[100] backdrop-blur-sm" style="display: none;">
    <div class="glass rounded-3xl shadow-2xl max-w-lg w-full mx-4 transform transition-all border border-white/50 overflow-hidden">
        <!-- Header con gradiente -->
        <div class="relative bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-8 text-white">
            <!-- Efecto de ondas decorativas -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>
            
            <div class="relative text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 mx-auto bg-white/20 backdrop-blur-sm rounded-2xl mb-4 shadow-lg">
                    <i class="fas fa-exclamation-triangle text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold">Cambio de Contraseña Requerido</h2>
                <p class="text-orange-100 mt-2 text-sm opacity-90">
                    Por seguridad, debe actualizar su contraseña para continuar usando el sistema
                </p>
            </div>
        </div>

        <!-- Contenido -->
        <div class="p-6">
            <!-- Alerta informativa -->
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-l-4 border-orange-500 p-4 rounded-xl mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-info-circle text-orange-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-700 font-medium">Importante</p>
                        <p class="text-sm text-gray-600 mt-1">
                            Si no actualiza su contraseña, perderá acceso a los servicios de RENIEC, SUNAT, SUNARP y otros módulos del sistema.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Botones de acción -->
            <div class="flex gap-3">
                <button type="button" onclick="recordarMasTarde()" class="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-clock"></i>
                    Recordar más tarde
                </button>
                <button type="button" onclick="btnCambiarPass()" class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-blue-800 transition duration-200 shadow-lg flex items-center justify-center gap-2">
                    <i class="fas fa-key"></i>
                    Cambiar Contraseña
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Add smooth entrance animation for cards
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.service-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>
