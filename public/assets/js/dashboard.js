// ============================================
// DASHBOARD - SISTEMA DE NAVEGACIÓN MODULAR DINÁMICO
// ============================================

const Dashboard = {
    BASE_URL: '/MDESistemaPIDE/public/',
    modulosInicializados: new Set(),
    
    // Registro de módulos disponibles (se auto-registran)
    modulosDisponibles: {},
    
    init() {
        this.setupEventListeners();
        this.verificarCambioPasswordRequerido(); // AGREGAR ESTA LÍNEA
        this.restaurarPaginaActiva();
    },

    // ============================================
    // NUEVO MÉTODO: Verificar si requiere cambio de password
    // ============================================
    verificarCambioPasswordRequerido() {
        const usuarioData = sessionStorage.getItem('usuario');
        
        if (usuarioData) {
            try {
                const usuario = JSON.parse(usuarioData);
                const requiereCambio = parseInt(usuario.USU_requiere_cambio_password) || 0;
                const diasDesdeCambio = parseInt(usuario.DIAS_DESDE_CAMBIO_PASSWORD) || 0;
                const diasRestantes = parseInt(usuario.DIAS_RESTANTES) || 30;
                const usuarioId = usuario.USU_id;

                if (requiereCambio === 1 || diasRestantes <= 0) {
                    const keyPospuesto = `cambio_password_pospuesto_${usuarioId}`;
                    const pospuesto = localStorage.getItem(keyPospuesto);
                    const ahora = Date.now();
                    const unDia = 24 * 60 * 60 * 1000;
                    
                    if (!pospuesto || (ahora - parseInt(pospuesto)) > unDia) {
                        setTimeout(() => {
                            if (typeof ModuloCambioPasswordObligatorio !== 'undefined') {
                                ModuloCambioPasswordObligatorio.init(diasRestantes);
                                ModuloCambioPasswordObligatorio.mostrarModal();
                            } else {
                                console.warn('ModuloCambioPasswordObligatorio no está definido');
                            }
                        }, 1000);
                    }
                }
            } catch (error) {
                console.error('Error al verificar cambio de password:', error);
            }
        }
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
    // 📌 REGISTRO DINÁMICO DE MÓDULOS
    // ============================================
    registrarModulo(nombreModulo, objetoModulo) {
        this.modulosDisponibles[nombreModulo.toLowerCase()] = objetoModulo;
    },

    // ============================================
    // 📌 NAVEGACIÓN ENTRE PÁGINAS
    // ============================================
    showPage(pageId, element) {
        console.clear();
        
        // Ocultar todas las páginas
        document.querySelectorAll('.page-content').forEach(p => {
            p.classList.remove('active');
        });

        // Convertir pageId a formato de ID de página
        const convertir = s =>
            s.replace(/\/(.)/g, (_, c) => c.toUpperCase())
            .replace(/^\w/, c => c.toUpperCase());
        const targetId = `page${convertir(pageId)}`;
        const targetPage = document.getElementById(targetId);

        if (targetPage) {
            targetPage.classList.add('active');
            
            // Guardar página activa
            localStorage.setItem('paginaActiva', targetId);
            if (element) {
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
        // Remover active de TODAS las opciones
        document.querySelectorAll('.option, .suboption').forEach(o => {
            o.classList.remove('active');
        });
        
        // Activar el elemento clickeado
        if (element) {
            element.classList.add('active');
            
            // Si es una subopción, asegurar que el submenú padre esté abierto
            if (element.classList.contains('suboption')) {
                const submenu = element.closest('.submenu');
                if (submenu) {
                    submenu.style.display = 'flex';
                    const parentOption = submenu.previousElementSibling;
                    if (parentOption && parentOption.classList.contains('has-submenu')) {
                        parentOption.classList.add('open');
                    }
                }
            }
        }
    },

    // ============================================
    // INICIALIZACIÓN DINÁMICA DE MÓDULOS
    // ============================================
    inicializarModulo(pageId) {
        // Evitar inicializar el mismo módulo dos veces
        if (this.modulosInicializados.has(pageId)) {
            return;
        }

        // Normalizar el pageId
        const pageIdNormalizado = pageId
            .toLowerCase()
            .replace('sistema', '')      // elimina la palabra sistema
            .replace(/[\/\-]/g, '');

        // Método 1: Buscar en módulos registrados dinámicamente
        if (this.modulosDisponibles[pageIdNormalizado]) {
            const modulo = this.modulosDisponibles[pageIdNormalizado];
            if (typeof modulo.init === 'function') {
                modulo.init();
                this.modulosInicializados.add(pageId);
                return;
            }
        }

        // Método 2: Intentar inicializar por convención de nombres
        const nombresModulosPosibles = this.generarNombresModulos(pageId);
        for (const nombreModulo of nombresModulosPosibles) {
            if (typeof window['Dashboard']['modulosDisponibles'][nombreModulo] !== 'undefined') {
                const modulo = window[nombreModulo];
                if (typeof modulo.init === 'function') {
                    modulo.init();
                    this.modulosInicializados.add(pageId);
                    return;
                }
            }
        }

        // Método 3: Casos especiales (compatibilidad con código anterior)
        const inicializacionesEspeciales = {
            'inicio': () => this.cargarInicio(),
            'consultadni': () => this.inicializarSiExiste('ModuloDNI', pageId),
            'consultaruc': () => this.inicializarSiExiste('ModuloRUC', pageId),
            'consultapartidas': () => this.inicializarSiExiste('ModuloPartidas', pageId),
            'crearusuario': () => this.inicializarSiExiste('ModuloCrearUsuario', pageId),
            'actualizarusuario': () => this.inicializarSiExiste('ModuloActualizarUsuario', pageId),
            'actualizarpassword': () => this.inicializarSiExiste('ModuloActualizarPassword', pageId),
            'crearroles': () => this.inicializarSiExiste('ModuloRoles', pageId),
            'crearmodulo': () => this.inicializarSiExiste('ModuloCrearModulo', pageId),
        };

        const pageIdLower = pageIdNormalizado;
        if (inicializacionesEspeciales[pageIdLower]) {
            inicializacionesEspeciales[pageIdLower]();
            return;
        }

    },

    // ============================================
    // 🔧 GENERAR NOMBRES DE MÓDULOS POSIBLES
    // ============================================
    generarNombresModulos(pageId) {
        // Limpiar el pageId
        const limpio = pageId.replace(/[\/\-]/g, '');
        
        // Generar variaciones posibles del nombre
        const variaciones = [
            // Formato: ModuloNombre
            `Modulo${this.capitalize(limpio)}`,
            // Formato: Nombre (sin prefijo)
            this.capitalize(limpio),
            // Formato: ModuloNombreNombre (para casos con guiones)
            `Modulo${pageId.split(/[\/\-]/).map(p => this.capitalize(p)).join('')}`,
        ];

        return [...new Set(variaciones)]; // Eliminar duplicados
    },

    // ============================================
    // 🔧 INICIALIZAR SI EXISTE
    // ============================================
    inicializarSiExiste(nombreModulo, pageId) {
        if (typeof window[nombreModulo] !== 'undefined') {
            window[nombreModulo].init();
            this.modulosInicializados.add(pageId);
        } else {
            console.warn(`⚠️ Módulo no encontrado: ${nombreModulo}`);
        }
    },

    // ============================================
    // 🏠 PÁGINA DE INICIO
    // ============================================
    async cargarInicio() {
        try {
            const actividadDiv = document.getElementById('actividadReciente');
            if (actividadDiv) {
                actividadDiv.innerHTML = '<p>No hay actividad reciente.</p>';
            }
            this.modulosInicializados.add('inicio');
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
            // Obtener ID del usuario antes de cerrar sesión
            const usuarioData = sessionStorage.getItem('usuario');
            let usuarioId = null;
            
            if (usuarioData) {
                try {
                    const usuario = JSON.parse(usuarioData);
                    usuarioId = usuario.USU_id;
                } catch (e) {
                    console.error('Error al parsear usuario:', e);
                }
            }
            
            await api.logout();
            
            // Limpiar sessionStorage
            sessionStorage.removeItem('paginaActiva');
            sessionStorage.removeItem('menuActivo');
            sessionStorage.removeItem('usuario');
            sessionStorage.removeItem('permisos');
            sessionStorage.removeItem('requiere_cambio_password');
            sessionStorage.removeItem('dias_desde_cambio');
            sessionStorage.removeItem('dias_restantes');
            
            // Limpiar localStorage específico del usuario
            if (usuarioId) {
                localStorage.removeItem(`cambio_password_pospuesto_${usuarioId}`);
            }
            
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
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    },

    restaurarPaginaActiva() {
        // Verificar si es un login recién hecho
        const esLoginNuevo = sessionStorage.getItem('loginReciente');
        
        // Ocultar TODAS las páginas
        document.querySelectorAll('.page-content').forEach(p => {
            p.classList.remove('active');
        });
        
        // Si es login reciente, SIEMPRE ir a inicio
        if (esLoginNuevo === 'true') {
            sessionStorage.removeItem('loginReciente');
            localStorage.removeItem('paginaActiva');
            localStorage.removeItem('menuActivo');
            
            const paginaInicio = document.getElementById('pageInicio');
            if (paginaInicio) {
                paginaInicio.classList.add('active');
                this.restaurarMenuActivo('Inicio');
                this.inicializarModulo('Inicio');
            }
            return;
        }
        
        // Si no es login reciente, intentar restaurar última página
        const paginaGuardada = localStorage.getItem('paginaActiva');
        const menuGuardado = localStorage.getItem('menuActivo');
        
        if (paginaGuardada) {
            const pagina = document.getElementById(paginaGuardada);
            if (pagina) {
                pagina.classList.add('active');
                
                const pageId = paginaGuardada.replace('page', '');
                this.restaurarMenuActivo(menuGuardado || pageId);
                this.inicializarModulo(pageId);
            } else {
                // Si la página guardada no existe, ir a inicio
                console.warn('⚠️ Página guardada no encontrada, redirigiendo a Inicio');
                this.mostrarPaginaInicio();
            }
        } else {
            // Si no hay página guardada, mostrar inicio
            this.mostrarPaginaInicio();
        }
    },

    // Nuevo método auxiliar
    mostrarPaginaInicio() {
        const paginaInicio = document.getElementById('pageInicio');
        if (paginaInicio) {
            paginaInicio.classList.add('active');
            this.restaurarMenuActivo('Inicio');
            this.inicializarModulo('Inicio');
        } else {
            console.error('❌ No se encontró la página de inicio (pageInicio)');
        }
    },

    restaurarMenuActivo(pageId) {
        // Remover TODAS las clases active
        document.querySelectorAll('.option, .suboption').forEach(o => {
            o.classList.remove('active');
        });
        
        // Cerrar todos los submenús
        document.querySelectorAll('.submenu').forEach(s => {
            s.style.display = 'none';
        });
        
        // Remover clase open
        document.querySelectorAll('.has-submenu').forEach(o => {
            o.classList.remove('open');
        });
        
        // Buscar la opción con el pageId
        const opciones = document.querySelectorAll('.option, .suboption');
        let encontrado = false;
        
        opciones.forEach(opcion => {
            const onclick = opcion.getAttribute('onclick');
            const regex = new RegExp(`showPage\\s*\\(\\s*['"]${pageId}['"]\\s*,`);
            
            if (onclick && regex.test(onclick)) {
                opcion.classList.add('active');
                encontrado = true;
                
                if (opcion.classList.contains('suboption')) {
                    const submenu = opcion.closest('.submenu');
                    if (submenu) {
                        submenu.style.display = 'flex';
                        const parentOption = submenu.previousElementSibling;
                        if (parentOption && parentOption.classList.contains('has-submenu')) {
                            parentOption.classList.add('open');
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
        document.querySelectorAll('.page-content').forEach(p => {
            // console.log(`   → ${p.id}`);
        });
    }
};

// ============================================
// 🔧 FUNCIONES GLOBALES
// ============================================

function toggleSubmenu(element) {
    const submenu = element.nextElementSibling;
    const isOpen = element.classList.contains('open');
    
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

// Función global showPage
window.showPage = function(pageId, element) {
    Dashboard.showPage(pageId, element);
};

// Función para registrar módulos (los módulos se auto-registran)
window.registrarModulo = function(nombre, modulo) {
    Dashboard.registrarModulo(nombre, modulo);
};

// Función de alertas
window.mostrarAlerta = function(mensaje, tipo = 'info', contenedorId = 'alertContainer') {
    const alertContainer = document.getElementById(contenedorId);
    
    if (!alertContainer) {
        console.warn('No se encontró el contenedor de alertas:', contenedorId);
        return;
    }
    
    const tiposIconos = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle',
        danger: 'fa-times-circle',
        noData: 'fa-search-minus'
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
    
    if (tipo === 'error' || tipo === 'warning') {
        alertContainer.innerHTML = '';
    }
    
    alertContainer.appendChild(alerta);
    
    const timeout = tipo === 'error' ? 8000 : 5000;
    setTimeout(() => {
        if (alerta.parentElement) {
            alerta.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => alerta.remove(), 300);
        }
    }, timeout);
};


// Función para verificar acceso
function verificarAcceso(codigoModulo) {
    try {
        // Verificar si requiere cambio de password
        const requiereCambio = sessionStorage.getItem('requiere_cambio_password') === 'true' ? 1 : 0;
        const diasRestantes = parseInt(sessionStorage.getItem('dias_restantes') || '30');


        // Si el password expiró (0 días restantes o menos), bloquear módulos críticos
        if (requiereCambio && diasRestantes <= 0) {
            const modulosProtegidos = ['DNI', 'RUC', 'PAR']; // Módulos de RENIEC, SUNAT, SUNARP
            
            if (modulosProtegidos.includes(codigoModulo)) {
                alert(
                    'Tu contraseña ha expirado. Debes cambiarla para acceder a este módulo.',
                    'error'
                );
                
                // Mostrar modal de cambio de password
                if (typeof ModuloCambioPasswordObligatorio !== 'undefined') {
                    ModuloCambioPasswordObligatorio.mostrarModal();
                }
                
                return false;
            }
        }

        // Verificar permisos normales
        const permisosStr = sessionStorage.getItem('permisos');
        if (!permisosStr) return false;
        const permisos = JSON.parse(permisosStr);
        return permisos.includes(codigoModulo);
    } catch (error) {
        console.error('Error al verificar acceso:', error);
        return false;
    }
}

// Funciones de navegación con validación
window.irConsultaReniec = function() {
    if (!verificarAcceso('DNI')) {
        alert('No tienes permisos para acceder al módulo de RENIEC');
        return;
    }
    showPage('ConsultasDni');
};

window.irConsultaSunat = function() {
    if (!verificarAcceso('RUC')) {
        alert('No tienes permisos para acceder al módulo de SUNAT');
        return;
    }
    showPage('ConsultasRuc');
};

window.irConsultaSunarp = function() {
    if (!verificarAcceso('PAR')) {
        alert('No tienes permisos para acceder al módulo de SUNARP');
        return;
    }
    showPage('ConsultasPartidas');
};

// ============================================
// 🚀 INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    Dashboard.init();
});

// ============================================
// 📝 EXPONER Dashboard GLOBALMENTE
// ============================================
window.Dashboard = Dashboard;