const Storage = {
    session: {
        get(key) {
            try {
                const value = sessionStorage.getItem(key);
                return value ? JSON.parse(value) : null;
            } catch (e) {
                console.error('Storage.session.get error:', e);
                return null;
            }
        },

        set(key, value) {
            try {
                sessionStorage.setItem(key, JSON.stringify(value));
                return true;
            } catch (e) {
                console.error('Storage.session.set error:', e);
                return false;
            }
        },

        remove(key) {
            try {
                sessionStorage.removeItem(key);
                return true;
            } catch (e) {
                console.error('Storage.session.remove error:', e);
                return false;
            }
        },

        clear() {
            try {
                sessionStorage.clear();
                return true;
            } catch (e) {
                console.error('Storage.session.clear error:', e);
                return false;
            }
        },

        has(key) {
            return sessionStorage.getItem(key) !== null;
        }
    },

    local: {
        get(key) {
            try {
                const value = localStorage.getItem(key);
                return value ? JSON.parse(value) : null;
            } catch (e) {
                console.error('Storage.local.get error:', e);
                return null;
            }
        },

        set(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
                return true;
            } catch (e) {
                console.error('Storage.local.set error:', e);
                return false;
            }
        },

        remove(key) {
            try {
                localStorage.removeItem(key);
                return true;
            } catch (e) {
                console.error('Storage.local.remove error:', e);
                return false;
            }
        },

        clear() {
            try {
                localStorage.clear();
                return true;
            } catch (e) {
                console.error('Storage.local.clear error:', e);
                return false;
            }
        },

        has(key) {
            return localStorage.getItem(key) !== null;
        }
    },

    getUsuario() {
        const data = this.session.get(Constants.UI.SESSION_KEYS.USUARIO);
        return data || null;
    },

    setUsuario(usuario) {
        return this.session.set(Constants.UI.SESSION_KEYS.USUARIO, usuario);
    },

    getPermisos() {
        const data = this.session.get(Constants.UI.SESSION_KEYS.PERMISOS);
        return data || [];
    },

    setPermisos(permisos) {
        return this.session.set(Constants.UI.SESSION_KEYS.PERMISOS, permisos);
    },

    getPaginaActiva() {
        return this.local.get(Constants.UI.STORAGE_KEYS.PAGINA_ACTIVA);
    },

    setPaginaActiva(pagina) {
        return this.local.set(Constants.UI.STORAGE_KEYS.PAGINA_ACTIVA, pagina);
    },

    getMenuActivo() {
        return this.local.get(Constants.UI.STORAGE_KEYS.MENU_ACTIVO);
    },

    setMenuActivo(menu) {
        return this.local.set(Constants.UI.STORAGE_KEYS.MENU_ACTIVO, menu);
    },

    requiereCambioPassword() {
        return this.session.get(Constants.UI.SESSION_KEYS.REQUIERE_CAMBIO_PASSWORD) === true;
    },

    diasRestantes() {
        return parseInt(this.session.get(Constants.UI.SESSION_KEYS.DIAS_RESTANTES) || '30');
    },

    clearAuth() {
        const usuario = this.getUsuario();
        const usuarioId = usuario?.USU_id;
        
        this.session.clear();
        
        if (usuarioId) {
            this.local.remove(`${Constants.UI.STORAGE_KEYS.CAMBIO_PASSWORD_POSPTO}${usuarioId}`);
        }
        
        this.local.remove(Constants.UI.STORAGE_KEYS.PAGINA_ACTIVA);
        this.local.remove(Constants.UI.STORAGE_KEYS.MENU_ACTIVO);
    },

    setLoginReciente() {
        return this.session.set(Constants.UI.SESSION_KEYS.LOGIN_RECIENTE, true);
    },

    isLoginReciente() {
        return this.session.get(Constants.UI.SESSION_KEYS.LOGIN_RECIENTE) === true;
    },

    clearLoginReciente() {
        return this.session.remove(Constants.UI.SESSION_KEYS.LOGIN_RECIENTE);
    }
};

window.Storage = Storage;
