const LoginModule = {
    elements: {},

    init() {
        this.cachearElementos();
        this.setupEventListeners();
    },

    cachearElementos() {
        this.elements = {
            formLogin: document.getElementById('formLogin'),
            formValidarCUI: document.getElementById('validarCUIForm'),
            modalCUI: document.getElementById('modalValidarCUI'),
            btnCancelarCUI: document.getElementById('btnCancelarCUI'),
            btnLogin: document.getElementById('btnLogin')
        };
    },

    setupEventListeners() {
        if (this.elements.formLogin) {
            this.elements.formLogin.addEventListener('submit', (e) => this.handleLogin(e));
        }

        if (this.elements.formValidarCUI) {
            this.elements.formValidarCUI.addEventListener('submit', (e) => this.handleValidarCUI(e));
        }

        if (this.elements.btnCancelarCUI) {
            this.elements.btnCancelarCUI.addEventListener('click', () => this.cancelarCUI());
        }
    },

    async handleLogin(e) {
        e.preventDefault();
        
        const nombreUsuario = DOM.val(document.getElementById('username'));
        const password = DOM.val(document.getElementById('password'));

        if (!nombreUsuario || !password) {
            Alerts.warning('Campos requeridos', 'Por favor ingrese usuario y contraseña');
            return;
        }

        const btnLoader = Loading.button(this.elements.btnLogin, { text: '<i class="fas fa-spinner fa-spin mr-2"></i>Ingresando...' });
        
        try {
            const response = await authService.login(nombreUsuario, password);
            
            if (response.success && response.data.requireCUI) {
                this.elements.modalCUI.style.display = 'flex';
                Storage.session.set('usuarioID', response.data.usuarioID);
                localStorage.setItem('usuario', nombreUsuario);
                btnLoader.restore();
            }
        } catch (error) {
            Alerts.error('Error de Autenticación', error.message);
            btnLoader.restore();
        }
    },

    async handleValidarCUI(e) {
        e.preventDefault();

        const cui = DOM.val(document.getElementById('cui'));
        
        if (!cui || cui.length < 1) {
            Alerts.warning('CUI Requerido', 'Por favor ingrese un CUI válido para continuar con la verificación.');
            return;
        }

        const btnConfirmar = document.getElementById('btnConfirmarCUI');
        const btnLoader = Loading.button(btnConfirmar, { text: '<i class="fas fa-spinner fa-spin mr-2"></i>Validando...' });
        
        try {
            const response = await authService.validarCUI(cui);
            
            if (response.success) {
                if (response.data.permisos) {
                    Storage.setPermisos(response.data.permisos);
                }
                
                Storage.setUsuario(response.data.usuario);
                Storage.setPermisos(response.data.permisos);
                Storage.session.set('requiere_cambio_password', response.data.requiere_cambio_password === true);
                Storage.session.set('dias_desde_cambio', response.data.dias_desde_cambio);
                Storage.session.set('dias_restantes', response.data.dias_restantes);
                Storage.setLoginReciente();
                
                this.elements.modalCUI.style.display = 'none';
                Loading.show();
                
                setTimeout(() => {
                    window.location.href = 'dashboard';
                }, Constants.UI.MODAL_TIMEOUT);
            }
        } catch (error) {
            Alerts.error('Error de Verificación', error.message);
        } finally {
            btnLoader.restore();
        }
    },

    cancelarCUI() {
        if (this.elements.modalCUI) {
            this.elements.modalCUI.style.display = 'none';
        }
        sessionStorage.clear();
        if (this.elements.btnLogin) {
            this.elements.btnLogin.disabled = false;
            this.elements.btnLogin.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>INGRESAR AL SISTEMA';
        }
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => LoginModule.init());
} else {
    LoginModule.init();
}

window.LoginModule = LoginModule;
