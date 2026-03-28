const Dashboard = {
    BASE_URL: Constants.ROUTES.BASE,
    modulosInicializados: new Set(),
    modulosDisponibles: {},

    init() {
        this.setupEventListeners();
        this.verificarCambioPasswordRequerido();
        this.restaurarPaginaActiva();
    },

    setupEventListeners() {
        DOM.on(document.getElementById('btnLogout'), 'click', () => this.mostrarModalLogout());
        DOM.on(document.getElementById('cancelLogout'), 'click', () => this.ocultarModalLogout());
        DOM.on(document.getElementById('confirmLogout'), 'click', async () => await this.handleLogout());
    },

    verificarCambioPasswordRequerido() {
        const usuario = Storage.getUsuario();
        
        if (usuario) {
            try {
                const requiereCambio = parseInt(usuario.USU_requiere_cambio_password) || 0;
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
                            }
                        }, 1000);
                    }
                }
            } catch (error) {
                console.error('Error al verificar cambio de password:', error);
            }
        }
    },

    registrarModulo(nombreModulo, objetoModulo) {
        this.modulosDisponibles[nombreModulo.toLowerCase()] = objetoModulo;
    },

    showPage(pageId, element) {
        document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));

        const convertir = s => s.replace(/\/(.)/g, (_, c) => c.toUpperCase()).replace(/^\w/, c => c.toUpperCase());
        const targetId = `page${convertir(pageId)}`;
        const targetPage = document.getElementById(targetId);

        if (targetPage) {
            targetPage.classList.add('active');
            Storage.setPaginaActiva(targetId);
            if (element) {
                Storage.setMenuActivo(pageId);
            }
            this.inicializarModulo(pageId);
        } else {
            console.warn(`No se encontró la página: ${targetId}`);
            this.listarPaginasDisponibles();
        }

        this.activarOpcionMenu(element);
    },

    activarOpcionMenu(element) {
        document.querySelectorAll('.option, .suboption').forEach(o => o.classList.remove('active'));
        
        if (element) {
            element.classList.add('active');
            
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

    inicializarModulo(pageId) {
        if (this.modulosInicializados.has(pageId)) return;

        const pageIdNormalizado = pageId.toLowerCase().replace('sistema', '').replace(/[\/\-]/g, '');

        if (this.modulosDisponibles[pageIdNormalizado]) {
            const modulo = this.modulosDisponibles[pageIdNormalizado];
            if (typeof modulo.init === 'function') {
                modulo.init();
                this.modulosInicializados.add(pageId);
                return;
            }
        }

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

        const fn = inicializacionesEspeciales[pageIdNormalizado];
        if (fn) fn();
    },

    inicializarSiExiste(nombreModulo, pageId) {
        if (typeof window[nombreModulo] !== 'undefined') {
            window[nombreModulo].init();
            this.modulosInicializados.add(pageId);
        }
    },

    cargarInicio() {
        try {
            const actividadDiv = document.getElementById('actividadReciente');
            if (actividadDiv) {
                actividadDiv.innerHTML = '<p>No hay actividad reciente.</p>';
            }
            this.modulosInicializados.add('inicio');
        } catch (error) {
            console.error('Error al cargar el inicio:', error);
        }
    },

    mostrarModalLogout() {
        const modal = document.getElementById('logoutModal');
        if (modal) modal.style.display = 'flex';
    },

    ocultarModalLogout() {
        const modal = document.getElementById('logoutModal');
        if (modal) modal.style.display = 'none';
    },

    async handleLogout() {
        this.ocultarModalLogout();
        try {
            const usuario = Storage.getUsuario();
            const usuarioId = usuario?.USU_id;
            
            await authService.logout();
            Storage.clearAuth();
            
            if (usuarioId) {
                localStorage.removeItem(`${Constants.UI.STORAGE_KEYS.CAMBIO_PASSWORD_POSPTO}${usuarioId}`);
            }
            
            window.location.href = Constants.ROUTES.LOGIN;
        } catch (error) {
            console.error('Error al cerrar sesión:', error);
            Alerts.error('Error', 'Ocurrió un error al cerrar sesión. Inténtelo nuevamente.');
        }
    },

    restaurarPaginaActiva() {
        const esLoginNuevo = Storage.isLoginReciente();
        
        document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
        
        if (esLoginNuevo) {
            Storage.clearLoginReciente();
            Storage.setPaginaActiva(null);
            Storage.setMenuActivo(null);
            
            const paginaInicio = document.getElementById('pageInicio');
            if (paginaInicio) {
                paginaInicio.classList.add('active');
                this.restaurarMenuActivo('Inicio');
                this.inicializarModulo('Inicio');
            }
            return;
        }
        
        const paginaGuardada = Storage.getPaginaActiva();
        const menuGuardado = Storage.getMenuActivo();
        
        if (paginaGuardada) {
            const pagina = document.getElementById(paginaGuardada);
            if (pagina) {
                pagina.classList.add('active');
                const pageId = paginaGuardada.replace('page', '');
                this.restaurarMenuActivo(menuGuardado || pageId);
                this.inicializarModulo(pageId);
            } else {
                this.mostrarPaginaInicio();
            }
        } else {
            this.mostrarPaginaInicio();
        }
    },

    mostrarPaginaInicio() {
        const paginaInicio = document.getElementById('pageInicio');
        if (paginaInicio) {
            paginaInicio.classList.add('active');
            this.restaurarMenuActivo('Inicio');
            this.inicializarModulo('Inicio');
        }
    },

    restaurarMenuActivo(pageId) {
        document.querySelectorAll('.option, .suboption').forEach(o => o.classList.remove('active'));
        document.querySelectorAll('.submenu').forEach(s => s.style.display = 'none');
        document.querySelectorAll('.has-submenu').forEach(o => o.classList.remove('open'));
        
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
    },

    listarPaginasDisponibles() {
        document.querySelectorAll('.page-content').forEach(p => {});
    }
};

window.toggleSubmenu = function(element) {
    const submenu = element.nextElementSibling;
    const isOpen = element.classList.contains('open');
    
    document.querySelectorAll('.submenu').forEach(s => s.style.display = 'none');
    document.querySelectorAll('.has-submenu').forEach(o => o.classList.remove('open'));
    
    if (!isOpen) {
        submenu.style.display = 'flex';
        element.classList.add('open');
    }
};

window.showPage = function(pageId, element) {
    Dashboard.showPage(pageId, element);
};

window.registrarModulo = function(nombre, modulo) {
    Dashboard.registrarModulo(nombre, modulo);
};

window.verificarAcceso = function(codigoModulo) {
    try {
        const requiereCambio = Storage.requiereCambioPassword();
        const diasRestantes = Storage.diasRestantes();

        if (requiereCambio && diasRestantes <= 0) {
            const modulosProtegidos = ['DNI', 'RUC', 'PAR'];
            
            if (modulosProtegidos.includes(codigoModulo)) {
                Alerts.warning('Contraseña Expirada', 'Tu contraseña ha expirado. Debes cambiarla para acceder a este módulo.');
                
                if (typeof ModuloCambioPasswordObligatorio !== 'undefined') {
                    ModuloCambioPasswordObligatorio.mostrarModal();
                }
                
                return false;
            }
        }

        const permisos = Storage.getPermisos();
        return permisos.includes(codigoModulo);
    } catch (error) {
        console.error('Error al verificar acceso:', error);
        return false;
    }
};

window.irConsultaReniec = function() {
    if (!verificarAcceso('DNI')) {
        Alerts.error('Acceso Denegado', 'No tienes permisos para acceder al módulo de <strong>RENIEC</strong>. Contacta al administrador del sistema.');
        return;
    }
    showPage('ConsultasDni');
};

window.irConsultaSunat = function() {
    if (!verificarAcceso('RUC')) {
        Alerts.error('Acceso Denegado', 'No tienes permisos para acceder al módulo de <strong>SUNAT</strong>. Contacta al administrador del sistema.');
        return;
    }
    showPage('ConsultasRuc');
};

window.irConsultaSunarp = function() {
    if (!verificarAcceso('PAR')) {
        Alerts.error('Acceso Denegado', 'No tienes permisos para acceder al módulo de <strong>SUNARP</strong>. Contacta al administrador del sistema.');
        return;
    }
    showPage('ConsultasPartidas');
};

window.volverInicio = function() {
    if (typeof showPage === 'function') {
        showPage('inicio');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    Dashboard.init();
});

window.Dashboard = Dashboard;
