class API {
    constructor(baseURL = '/MDESistemaPIDE/public/api') {
        this.baseURL = baseURL;
        this.csrfToken = null;
    }

    // ==================== CORE HTTP ====================

    /**
     * Petición HTTP genérica
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const method = (options.method || 'GET').toUpperCase();

        // Obtener CSRF token para métodos que modifican datos
        if (method !== 'GET' && method !== 'OPTIONS') {
            await this.ensureCSRF();
        }

        const config = {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...(this.csrfToken && method !== 'GET' ? { 'X-CSRF-Token': this.csrfToken } : {}),
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, config);
            const text = await response.text();

            let data = {};
            try {
                data = text ? JSON.parse(text) : {};
            } catch (e) {
                console.error('❌ Error parsing JSON:', e);
                console.error('Response text:', text.substring(0, 500));
                throw new Error(`Respuesta inválida del servidor: ${text.substring(0, 100)}...`);
            }

            if (!response.ok) {
                const error = new Error(data.message || `HTTP ${response.status}`);
                error.status = response.status;
                error.data = data;
                throw error;
            }

            return data;
        } catch (error) {
            console.error('❌ API Error:', error);
            throw error;
        }
    }

    /**
     * Obtener token CSRF (solo una vez por sesión)
     */
    async ensureCSRF() {
        if (this.csrfToken) return;

        try {
            const response = await fetch(`${this.baseURL}/csrf-token`, { method: 'GET' });
            const data = await response.json();

            if (data && data.token) {
                this.csrfToken = data.token;
            } else {
                console.warn('⚠️ No se pudo obtener CSRF token');
            }
        } catch (e) {
            console.error('❌ Error obteniendo CSRF token:', e);
        }
    }

    // ==================== MÉTODOS HTTP ====================

    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    }

    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }

    // ==================== AUTENTICACIÓN ====================

    async login(nombreUsuario, password) {
        const response = await this.post('/login', { nombreUsuario, password });

        if (response.success) {
            await this.ensureCSRF();
        }

        return response;
    }

    async validarCUI(cui) {
        return this.post('/validar-cui', { cui });
    }

    async logout() {
        return this.post('/logout');
    }

    // ==================== DASHBOARD ====================

    async obtenerDatosInicio() {
        return this.get('/inicio');
    }

    async obtenerUsuario(usuarioId) {
        return this.get(`/usuarios/obtener?id=${usuarioId}`);
    }

    async obtenerUsuarioActual() {
        return this.get(`/usuarios/actual`);
    }
    async obtenerRoles() {
        return this.get('/usuarios/rol');
    }

    async cambiarPassword(passwordActual, passwordNueva) {
        return this.post('/usuarios/cambiar-pass', {
            passwordActual: passwordActual,
            passwordNueva: passwordNueva
        });
    }

    async listarUsuarios() {
        return this.get('/usuarios');
    }

    async obtenerTipoPersonal() {
        return this.get('/usuarios/tipo-personal');
    }

    // Métodos de Roles
    async crearRol(data) {
        return this.post('/roles/crear', data);
    }

    async actualizarRol(data) {
        return this.put('/roles/actualizar', data);
    }

    async listarRoles() {
        return this.get('/roles');
    }

    async obtenerRol(rolId) {
        return this.get(`/roles/obtener?id=${rolId}`);
    }

    async listarRolesModulos() {
        return this.get('/roles/modulos');
    }

    async eliminarRol(rolId) {
        return this.post('/roles/eliminar', { rol_id: rolId });
    }

    // --- CONSULTAS RENIEC ---
    /**
     * Consultar DNI en RENIEC
     */
    async consultarDNI(data) {
        return this.post('/consultas/dni', data);
    }

    async actualizarPasswordRENIEC(data) {
        return this.post('/actualizar-pass-reniec', data);
    }

    // --- CONSULTAS SUNAT ---
    /**
     * Consultar RUC en SUNAT
     */
    async consultarRUC(ruc) {
        return this.post('/consultas/ruc', { ruc });
    }

    /**
     * Buscar por razón social en SUNAT
     */
    async buscarRazonSocialSUNAT(razonSocial) {
        return this.post('/buscar-razon-social', { razonSocial });
    }


    /**
     * Buscar persona natural en SUNARP
     */
    async buscarPersonaNaturalSunarp(dni, dniUsuario, password) {
        return this.post('/consultas/buscar/natural', {
            dni,
            dniUsuario,
            password
        });
    }

    /**
     * Buscar persona jurídica en SUNARP
     */
    async buscarPersonaJuridicaSunarp(parametro, tipoBusqueda, dniUsuario, password) {
        const data = {
            tipoBusqueda,
            dniUsuario,
            password
        };

        if (tipoBusqueda === 'ruc') {
            data.ruc = parametro;
        } else {
            data.razonSocial = parametro;
        }

        return this.post('/consultas/buscar/juridica', data);
    }

    /**
     * Obtener oficinas registrales
     */

    async obtenerOficinasRegistrales() {
        return this.get('/consultas/goficinas');
    }

    // Metodo LASIRSARP
    async consultarLASIRSARP(datos) {
        return this.post('/consultas/partidas/lasirsarp', datos);
    }

    // Métodos TSIRSARP
    async consultarTSIRSARPNatural(datos) {
        return this.post('/consultas/partidas/natural', datos);
    }

    async consultarTSIRSARPJuridica(datos) {
        return this.post('/consultas/partidas/juridica', datos);
    }

    async crearUsuario(data) {
        return this.post('/usuarios/registrar', { data })
    }

    async actualizarUsuario(data) {
        return this.put('/usuarios/actualizar', { data })
    }

    async actualizarPassword(data) {
        return this.put('/usuarios/actualizar-password', { data })
    }

    async eliminarUsuario(usuario_id) {
        return this.post('/usuarios/eliminar', { usuario_id });
    }

    // Métodos de Módulos
    async crearModulo(data) {
        return this.post('/modulos/registrar', data);
    }

    async actualizarModulo(data) {
        return this.put('/modulos/actualizar', data);
    }

    async listarModulos() {
        return this.get('/modulos');
    }

    async obtenerModulo(moduloId) {
        return this.get(`/modulos/obtener?id=${moduloId}`);
    }

    async eliminarModulo(moduloId) {
        return this.post('/modulos/eliminar', { moduloId });
    }

    async toggleEstadoModulo(moduloId, estado) {
        return this.post('/modulos/toggle-estado', { modulo_id: moduloId, estado });
    }

    async obtenerModulosUsuario() {
        return this.get('/modulos/obtener-port-usuario');
    }

    async obtenerDniYPassword(nombreUsuario) {
        return this.post('/usuarios/obtener-dni-pass', {
            nombreUsuario
        });
    }

    // ============================================
    // AGREGAR AL ARCHIVO api.js
    // ============================================

    // Nuevo método para cargar detalle de partida bajo demanda
    async cargarDetallePartida(params) {
        const data = {
            numero_partida: params.numero_partida,
            codigo_zona: params.codigo_zona,
            codigo_oficina: params.codigo_oficina,
            numero_placa: params.numero_placa || ''
        };

        return this.post('/consultas/sunarp/cargar-detalle-partida', data);

    }

}

// ==================== INSTANCIA GLOBAL ====================
const api = new API();
