class API {
    constructor(baseURL = '/sistemaConsultasPIDE/public/api') {
        this.baseURL = baseURL;
    }
    
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        
        const config = {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        };
        
        try {
            const response = await fetch(url, config);
            
            // Obtenemos el texto de la respuesta (puede estar vacío)
            const text = await response.text();

            // Si hay contenido, parseamos JSON
            const data = text ? JSON.parse(text) : {};

            if (!response.ok) {
                throw new Error(data.message || 'Error en la petición');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    
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
    
    // Métodos específicos
    async login(nombreUsuario, password) {
        return this.post('/login', { nombreUsuario, password });
    }
    
    async validarCUI(cui) {
        return this.post('/validar-cui', { cui });
    }
    
    async logout() {
        return this.post('/logout');
    }

    // 📌 --- INICIO / DASHBOARD ---
    async obtenerDatosInicio() {
        return this.get('/inicio');
    }

    async obtenerUsuario(usuarioId) {
        return this.get(`/obtener-usuario?id=${usuarioId}`);
    }

    async obtenerUsuarioActual() {
        return this.get(`/usuario/actual`);
    }
    async obtenerRoles(){
        return this.get('/usuario/rol');
    }

    async cambiarPassword(passwordActual, passwordNueva) {
        return this.post('/usuario/cambiar-password', {
            passwordActual: passwordActual,
            passwordNueva: passwordNueva
        });
    }

    async obtenerTipoPersonal(){
        return this.get('/usuario/tipo-personal');
    }

    async listarUsuarios() {
        return this.get('/listar-usuarios');
    }

    // Métodos de Roles
    async crearRol(data) {
        return this.post('/rol/crear', data);
    }

    async actualizarRol(data) {
        return this.put('/rol/actualizar', data);
    }

    async listarRoles() {
        return this.get('/rol/listar');
    }

    async obtenerRol(rolId) {
        return this.get(`/rol/obtener?id=${rolId}`);
    }

    async listarModulos() {
        return this.get('/rol/modulos');
    }

    async eliminarRol(rolId) {
        return this.post('/rol/eliminar', { rol_id: rolId });
    }

    // 📌 --- CONSULTAS RENIEC ---
    /**
     * Consultar DNI en RENIEC
     * @param {string} dni - DNI de 8 dígitos
     * @returns {Promise} - Datos de la persona
     */
    async consultarDNI(data) {
        return this.post('/consultar-dni', data);
    }

    async actualizarPasswordRENIEC(data) {
        return this.post('/actualizar-password-reniec', data);
    }

    // 📌 --- CONSULTAS SUNAT ---
    /**
     * Consultar RUC en SUNAT
     * @param {string} ruc - RUC de 11 dígitos
     * @returns {Promise} - Datos del contribuyente
     */
    async consultarRUC(ruc) {
        return this.post('/consultar-ruc', { ruc });
    }

    /**
     * Buscar por razón social en SUNAT
     * @param {string} razonSocial - Razón social a buscar
     * @returns {Promise} - Lista de contribuyentes encontrados
     */
    async buscarRazonSocialSUNAT(razonSocial) {
        return this.post('/buscar-razon-social', { razonSocial });
    }

    // ========================================
    // 📌 CONSULTAS SUNARP (TSIRSARP)
    // ========================================
    
    /**
     * Buscar persona natural en SUNARP
     * Flujo: RENIEC (obtener datos) → SUNARP TSIRSARP (buscar registros)
     * 
     * @param {string} dni - DNI de 8 dígitos
     * @param {string} dniUsuario - DNI del usuario que consulta
     * @param {string} password - Contraseña PIDE
     * @returns {Promise} - Lista de registros encontrados en SUNARP
     * 
     * Respuesta esperada:
     * {
     *   success: true,
     *   message: "Consulta exitosa",
     *   data: [
     *     {
     *       tipo: "PERSONA_NATURAL",
     *       dni: "12345678",
     *       nombres: "JUAN",
     *       apellidoPaterno: "PEREZ",
     *       apellidoMaterno: "GOMEZ",
     *       foto: "base64...",
     *       registro: "...",
     *       libro: "...",
     *       partida: "...",
     *       asiento: "...",
     *       placa: "...",
     *       zona: "...",
     *       oficina: "...",
     *       estado: "...",
     *       descripcion: "..."
     *     }
     *   ],
     *   total: 1
     * }
     */
    async buscarPersonaNaturalSunarp(dni, dniUsuario, password) {
        return this.post('/buscar-persona-natural-sunarp', { 
            dni, 
            dniUsuario, 
            password 
        });
    }

    /**
     * Buscar persona jurídica en SUNARP
     * Flujo: 
     *   - Si es por RUC: SUNAT (obtener razón social) → SUNARP TSIRSARP (buscar registros)
     *   - Si es por razón social: SUNARP TSIRSARP (buscar registros directamente)
     * 
     * @param {string} parametro - RUC (11 dígitos) o razón social
     * @param {string} tipoBusqueda - 'ruc' o 'razonSocial'
     * @param {string} dniUsuario - DNI del usuario que consulta
     * @param {string} password - Contraseña PIDE
     * @returns {Promise} - Lista de registros encontrados en SUNARP
     * 
     * Respuesta esperada:
     * {
     *   success: true,
     *   message: "Consulta exitosa",
     *   data: [
     *     {
     *       tipo: "PERSONA_JURIDICA",
     *       razonSocial: "EMPRESA S.A.C.",
     *       registro: "...",
     *       libro: "...",
     *       partida: "...",
     *       asiento: "...",
     *       zona: "...",
     *       oficina: "...",
     *       estado: "...",
     *       descripcion: "..."
     *     }
     *   ],
     *   total: 1
     * }
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

        return this.post('/buscar-persona-juridica-sunarp', data);
    }

    // Métodos TSIRSARP
    async consultarTSIRSARPNatural(datos) {
        return this.post('/sunarp/tsirsarp-natural', datos);
    }

    async consultarTSIRSARPJuridica(datos) {
        return this.post('/sunarp/tsirsarp-juridica', datos);
    }

    async crearUsuario(data) {
        return this.post('/crear-usuario', { data })
    }

    async actualizarUsuario(data) {
        return this.put('/actualizar-usuario', { data })
    }

    async actualizarPassword(data) {
        return this.put('/actualizar-password', { data })
    }

    async eliminarUsuario(usuario_id) {
        return this.post('/eliminar-usuario', { usuario_id });
    }

    // Métodos de Módulos
    async crearModulo(data) {
        return this.post('/modulo/crear', data);
    }

    async actualizarModulo(data) {
        return this.put('/modulo/actualizar', data);
    }

    async listarModulos() {
        return this.get('/modulo/listar');
    }

    async obtenerModulo(moduloId) {
        return this.get(`/modulo/obtener?id=${moduloId}`);
    }

    async eliminarModulo(moduloId) {
        return this.post('/modulo/eliminar', { modulo_id: moduloId });
    }

    async toggleEstadoModulo(moduloId, estado) {
        return this.post('/modulo/toggle-estado', { modulo_id: moduloId, estado });
    }

    async obtenerModulosUsuario() {
        return this.get('/modulo/usuario');
    }

    async obtenerDniYPassword(nombreUsuario) {
        return this.post('/obtener-dni-pass', {
            nombreUsuario
        });
    }


}

const api = new API();