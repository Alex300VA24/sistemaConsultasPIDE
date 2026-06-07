/**
 * Sidebar Mobile Controller
 * Maneja la lógica del sidebar responsive para dispositivos móviles.
 * - Toggle con botón hamburguesa
 * - Cerrar con overlay, botón X, o al seleccionar opción
 * - Bloqueo de scroll del body
 * - Detección de resize para limpiar estado mobile
 */
(function () {
    'use strict';

    const MOBILE_BREAKPOINT = 768;

    const SidebarMobile = {
        sidebar: null,
        overlay: null,
        menuBtn: null,
        menuIcon: null,
        closeBtn: null,
        isOpen: false,

        init() {
            this.sidebar = document.getElementById('sideBar');
            this.overlay = document.getElementById('sidebarOverlay');
            this.menuBtn = document.getElementById('mobileMenuBtn');
            this.menuIcon = document.getElementById('mobileMenuIcon');
            this.closeBtn = document.getElementById('sidebarCloseBtn');

            if (!this.sidebar || !this.overlay || !this.menuBtn) {
                return;
            }

            this.bindEvents();
        },

        bindEvents() {
            // Toggle con botón hamburguesa
            this.menuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggle();
            });

            // Cerrar con overlay
            this.overlay.addEventListener('click', () => {
                this.close();
            });

            // Cerrar con botón X
            if (this.closeBtn) {
                this.closeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.close();
                });
            }

            // Cerrar al seleccionar una opción del menú (solo en móvil)
            this.sidebar.querySelectorAll('.option:not(.has-submenu), .suboption').forEach(option => {
                option.addEventListener('click', () => {
                    if (this.isMobile()) {
                        // Pequeño delay para que la navegación se procese primero
                        setTimeout(() => this.close(), 150);
                    }
                });
            });

            // Detectar cambio de tamaño de pantalla
            window.addEventListener('resize', () => {
                if (!this.isMobile() && this.isOpen) {
                    this.close();
                }
            });

            // Cerrar con tecla Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        },

        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        },

        open() {
            this.isOpen = true;
            this.sidebar.classList.add('mobile-open');
            this.overlay.classList.add('active');
            document.body.classList.add('sidebar-mobile-open');

            // Cambiar ícono a X
            if (this.menuIcon) {
                this.menuIcon.classList.remove('fa-bars');
                this.menuIcon.classList.add('fa-times');
            }

            // Focus trap: mover foco al sidebar
            if (this.closeBtn) {
                this.closeBtn.focus();
            }
        },

        close() {
            this.isOpen = false;
            this.sidebar.classList.remove('mobile-open');
            this.overlay.classList.remove('active');
            document.body.classList.remove('sidebar-mobile-open');

            // Restaurar ícono hamburguesa
            if (this.menuIcon) {
                this.menuIcon.classList.remove('fa-times');
                this.menuIcon.classList.add('fa-bars');
            }

            // Devolver foco al botón hamburguesa
            if (this.menuBtn) {
                this.menuBtn.focus();
            }
        },

        isMobile() {
            return window.innerWidth <= MOBILE_BREAKPOINT;
        }
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => SidebarMobile.init());
    } else {
        SidebarMobile.init();
    }

    window.SidebarMobile = SidebarMobile;
})();
