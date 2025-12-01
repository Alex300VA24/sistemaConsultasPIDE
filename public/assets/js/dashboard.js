// ============================================
// 🚀 DASHBOARD - SISTEMA DE NAVEGACIÓN MODULAR
// ============================================

const Dashboard = {
    BASE_URL: '/sistemaConsultasPIDE/public/',
    modulosInicializados: new Set(),
    
    init() {
        console.log('🚀 Inicializando Dashboard...');
        this.setupEventListeners();
        this.restaurarPaginaActiva();
    },

    setupEventListeners() {
        // Logout
        document.getElementById('btnLogout')?.addEventListener('click', () => {
            this.mostrarModalLogout();
        });

        document.getElementById('cancelLogout')?.addEventListener('click', () => {
            this.ocultarModalLogout();
        });

        document.getElementById('confirmLogout')?.addEventListener('click', async () => {
            await this.handleLogout();
        });
    },

    // ============================================
    // 📌 NAVEGACIÓN ENTRE PÁGINAS
    // ============================================
    showPage(pageId, element) {
        console.clear();
        console.log(`🟦 Navegando a: ${pageId}`);
        
        // Ocultar todas las páginas
        document.querySelectorAll('.page-content').forEach(p => {
            p.classList.remove('active');
        });

        // Construir ID de la página (sin capitalizar, usar tal cual)
        const targetId = `page${pageId}`;
        const targetPage = document.getElementById(targetId);

        if (targetPage) {
            targetPage.classList.add('active');
            console.log(`✅ Página activada: ${targetId}`);
            
            // Guardar página activa Y el elemento del menú
            localStorage.setItem('paginaActiva', targetId);
            if (element) {
                // Guardar el pageId para restaurar el menú activo
                localStorage.setItem('menuActivo', pageId);
            }
            
            // Inicializar módulo específico
            this.inicializarModulo(pageId);
        } else {
            console.warn(`⚠️ No se encontró la página: ${targetId}`);
            this.listarPaginasDisponibles();
        }

        // Activar opción del menú
        this.activarOpcionMenu(element);
    },

    // ============================================
    // 🎨 ACTIVAR OPCIÓN DEL MENÚ
    // ============================================
    activarOpcionMenu(element) {
        console.log('🎨 Activando opción del menú');
        
        // Remover active de TODAS las opciones
        document.querySelectorAll('.option, .suboption').forEach(o => {
            o.classList.remove('active');
        });
        
        // NO cerrar submenús aquí si el elemento es subopción
        // porque queremos mantener abierto el submenú de la opción activa
        
        // Activar el elemento clickeado
        if (element) {
            element.classList.add('active');
            
            // Si es una subopción, asegurar que el submenú padre esté abierto
            if (element.classList.contains('suboption')) {
                const submenu = element.closest('.submenu');
                if (submenu) {
                    // Mantener este submenú abierto
                    submenu.style.display = 'flex';
                    
                    // Marcar el padre como open
                    const parentOption = submenu.previousElementSibling;
                    if (parentOption && parentOption.classList.contains('has-submenu')) {
                        parentOption.classList.add('open');
                    }
                }
            }
        }
    },

    // ============================================
    // 🔧 INICIALIZACIÓN DE MÓDULOS
    // ============================================
    inicializarModulo(pageId) {
        // Evitar inicializar el mismo módulo dos veces
        if (this.modulosInicializados.has(pageId)) {
            console.log(`ℹ️ Módulo ${pageId} ya está inicializado`);
            return;
        }

        console.log(`🔧 Inicializando módulo: ${pageId}`);

        // Normalizar el pageId para el switch (convertir a minúsculas para comparar)
        const pageIdLower = pageId.toLowerCase();

        switch(pageIdLower) {
            case 'inicio':
                this.cargarInicio();
                break;
            
            // Módulos de consulta
            case 'consultadni':
                if (typeof ModuloDNI !== 'undefined') {
                    ModuloDNI.init();
                    this.modulosInicializados.add(pageId);
                }
                break;
            
            case 'consultaruc':
                if (typeof ModuloRUC !== 'undefined') {
                    ModuloRUC.init();
                    this.modulosInicializados.add(pageId);
                }
                break;
            
            case 'consultapartidas':
                if (typeof ModuloPartidas !== 'undefined') {
                    ModuloPartidas.init();
                    this.modulosInicializados.add(pageId);
                }
                break;

            // Módulos de gestión de usuarios
            case 'crearusuario':
                if (typeof ModuloCrearUsuario !== 'undefined') {
                    ModuloCrearUsuario.init();
                    this.modulosInicializados.add(pageId);
                }
                break;

            case 'actualizarusuario':
                if (typeof ModuloActualizarUsuario !== 'undefined') {
                    ModuloActualizarUsuario.init();
                    this.modulosInicializados.add(pageId);
                }
                break;

            case 'actualizarpassword':
                if (typeof ModuloActualizarPassword !== 'undefined') {
                    ModuloActualizarPassword.init();
                    this.modulosInicializados.add(pageId);
                }
                break;

            // Módulos de administración
            case 'crearroles':
                if (typeof ModuloRoles !== 'undefined') {
                    ModuloRoles.init();
                    this.modulosInicializados.add(pageId);
                }
                break;

            default:
                console.log(`ℹ️ No hay módulo específico para: ${pageId}`);
        }
    },

    // ============================================
    // 🏠 PÁGINA DE INICIO
    // ============================================
    async cargarInicio() {
        try {
            console.log('🏠 Cargando página de inicio...');
            const actividadDiv = document.getElementById('actividadReciente');
            if (actividadDiv) {
                actividadDiv.innerHTML = '<p>No hay actividad reciente.</p>';
            }
        } catch (error) {
            console.error('❌ Error al cargar el inicio:', error);
        }
    },

    

    // ============================================
    // 🔓 LOGOUT
    // ============================================
    mostrarModalLogout() {
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    },

    ocultarModalLogout() {
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.style.display = 'none';
        }
    },

    async handleLogout() {
        this.ocultarModalLogout();
        try {
            await api.logout();
            localStorage.removeItem('paginaActiva');
            window.location.href = this.BASE_URL + 'login';
        } catch (error) {
            console.error('❌ Error al cerrar sesión:', error);
            alert('Error al cerrar sesión');
        }
    },

    // ============================================
    // 🔧 UTILIDADES
    // ============================================
    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    },

    restaurarPaginaActiva() {
        const paginaGuardada = localStorage.getItem('paginaActiva');
        const menuGuardado = localStorage.getItem('menuActivo');
        
        console.log('🔄 Restaurando página:', paginaGuardada);
        console.log('🔄 Restaurando menú:', menuGuardado);
        
        // IMPORTANTE: Primero ocultar TODAS las páginas
        document.querySelectorAll('.page-content').forEach(p => {
            p.classList.remove('active');
        });
        
        if (paginaGuardada) {
            // Mostrar la página guardada
            const pagina = document.getElementById(paginaGuardada);
            if (pagina) {
                pagina.classList.add('active');
                console.log('✅ Página restaurada:', paginaGuardada);
                
                // Extraer el pageId del paginaGuardada (remover "page")
                const pageId = paginaGuardada.replace('page', '');
                
                // Restaurar el menú activo
                this.restaurarMenuActivo(menuGuardado || pageId);
                
                // Inicializar el módulo
                this.inicializarModulo(pageId);
            }
        } else {
            // Si no hay página guardada, mostrar inicio por defecto
            console.log('ℹ️ No hay página guardada, mostrando Inicio');
            const paginaInicio = document.getElementById('pageInicio');
            if (paginaInicio) {
                paginaInicio.classList.add('active');
                this.restaurarMenuActivo('Inicio');
            }
        }
    },

    // ============================================
    // 🎨 RESTAURAR MENÚ ACTIVO
    // ============================================
    restaurarMenuActivo(pageId) {
        console.log('🎨 Restaurando menú para pageId:', pageId);
        
        // IMPORTANTE: Primero remover TODAS las clases active
        document.querySelectorAll('.option, .suboption').forEach(o => {
            o.classList.remove('active');
        });
        
        // Cerrar todos los submenús
        document.querySelectorAll('.submenu').forEach(s => {
            s.style.display = 'none';
        });
        
        // Remover clase open de todos los padres
        document.querySelectorAll('.has-submenu').forEach(o => {
            o.classList.remove('open');
        });
        
        // Buscar la opción o subopción con onclick que contenga este pageId EXACTO
        const opciones = document.querySelectorAll('.option, .suboption');
        let encontrado = false;
        
        opciones.forEach(opcion => {
            const onclick = opcion.getAttribute('onclick');
            
            // Verificar que el onclick contiene showPage con el pageId EXACTO
            // Usar regex para match exacto: showPage('pageId', ...)
            const regex = new RegExp(`showPage\\s*\\(\\s*['"]${pageId}['"]\\s*,`);
            
            if (onclick && regex.test(onclick)) {
                opcion.classList.add('active');
                encontrado = true;
                
                console.log('✅ Opción encontrada y activada:', opcion.textContent.trim());
                
                // Si es subopción, abrir el submenú padre
                if (opcion.classList.contains('suboption')) {
                    const submenu = opcion.closest('.submenu');
                    if (submenu) {
                        submenu.style.display = 'flex';
                        const parentOption = submenu.previousElementSibling;
                        if (parentOption && parentOption.classList.contains('has-submenu')) {
                            parentOption.classList.add('open');
                            console.log('✅ Submenú padre abierto');
                        }
                    }
                }
            }
        });
        
        if (!encontrado) {
            console.warn('⚠️ No se encontró opción de menú para:', pageId);
        }
    },

    listarPaginasDisponibles() {
        console.log('📋 Páginas disponibles:');
        document.querySelectorAll('.page-content').forEach(p => {
            console.log(`   → ${p.id}`);
        });
    }
};

// ============================================
// 🔧 FUNCIONES GLOBALES PARA SUBMENU
// ============================================
function toggleSubmenu(element) {
    const submenu = element.nextElementSibling;
    const isOpen = submenu.style.display === 'flex';
    
    // Cerrar todos los submenús
    document.querySelectorAll('.submenu').forEach(s => {
        s.style.display = 'none';
    });
    document.querySelectorAll('.has-submenu').forEach(o => {
        o.classList.remove('open');
    });
    
    // Abrir el seleccionado si estaba cerrado
    if (!isOpen) {
        submenu.style.display = 'flex';
        element.classList.add('open');
    }
}

// ============================================
// 📌 EXPONER FUNCIÓN GLOBAL showPage
// ============================================
window.showPage = function(pageId, element) {
    Dashboard.showPage(pageId, element);
};




window.mostrarAlerta = function(mensaje, tipo = 'info', contenedorId = 'alertContainer') {

    console.log('🔔 Intentando mostrar alerta:', {mensaje, tipo, contenedorId});
    
    const contenedorPrueba = document.getElementById(contenedorId);
    console.log('📦 Contenedor encontrado:', contenedorPrueba);
    
    if (!contenedorPrueba) {
        console.error(`❌ Contenedor ${contenedorId} no encontrado`);
        return;
    }
    const alertContainer = document.getElementById(contenedorId);
    
    if (!alertContainer) {
        console.warn('No se encontró el contenedor de alertas');
        return;
    }
    
    const tiposIconos = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const tiposColores = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo}`;
    alerta.style.cssText = `
        padding: 15px 20px;
        margin-bottom: 15px;
        border-radius: 8px;
        background-color: ${tiposColores[tipo]}15;
        border-left: 4px solid ${tiposColores[tipo]};
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.3s ease-out;
    `;
    
    alerta.innerHTML = `
        <i class="fas ${tiposIconos[tipo]}" style="color: ${tiposColores[tipo]}; font-size: 20px;"></i>
        <span style="flex: 1; color: #333;">${mensaje}</span>
        <button class="alert-close" onclick="this.parentElement.remove()" style="
            background: none;
            border: none;
            color: ${tiposColores[tipo]};
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        " onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Limpiar alertas anteriores si es de tipo error o warning
    if (tipo === 'error' || tipo === 'warning') {
        alertContainer.innerHTML = '';
    }
    
    alertContainer.appendChild(alerta);
    
    // Auto-cerrar después de 5 segundos (excepto errores que se cierran en 8 segundos)
    const timeout = tipo === 'error' ? 8000 : 5000;
    setTimeout(() => {
        if (alerta.parentElement) {
            alerta.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => alerta.remove(), 300);
        }
    }, timeout);
};

// Función para verificar si el usuario tiene acceso a un módulo
function verificarAcceso(codigoModulo) {
    try {
        const permisosStr = sessionStorage.getItem('permisos');
        console.log("Estos son los permiso: ", permisosStr);
        if (!permisosStr) {
            return false;
        }
        const permisos = JSON.parse(permisosStr);
        return permisos.includes(codigoModulo);
    } catch (error) {
        console.error('Error al verificar acceso:', error);
        return false;
    }
}

// Funciones para las consultas con validación de permisos
window.irConsultaReniec = function() {
    if (!verificarAcceso('DNI')) {
        alert('No tienes permisos para acceder al módulo de RENIEC');
        return;
    }
    // Si tiene acceso, redirigir o mostrar el módulo
    showPage('ConsultaDNI');
};

window.irConsultaSunat = function() {
    if (!verificarAcceso('RUC')) {
        alert('No tienes permisos para acceder al módulo de SUNAT');
        return;
    }
    showPage('ConsultaRUC');
};

window.irConsultaSunarp = function() {
    if (!verificarAcceso('PAR')) {
        alert('No tienes permisos para acceder al módulo de SUNARP');
        return;
    }
    showPage('ConsultaPartidas');
};


// ============================================
// 🚀 INICIALIZACIÓN AL CARGAR EL DOM
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    Dashboard.init();
});