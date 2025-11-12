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
            
            // 🔹 Obtenemos el texto de la respuesta (puede estar vacío)
            const text = await response.text();

            // 🔹 Si hay contenido, parseamos JSON
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
    

    async listarUsuarios() {
        return this.get('/listar-usuarios');
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

    // 📌 --- CONSULTAS SUNARP ---
    /**
     * Buscar persona natural en SUNARP por DNI
     * Primero consulta RENIEC y luego prepara datos para SUNARP
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
     * Si es por RUC, primero consulta SUNAT para obtener la razón social
     */
    async buscarPersonaJuridicaSunarp(parametro, tipoBusqueda, dniUsuario, password) {
        const data = {
            dniUsuario,
            password,
            tipoBusqueda
        };

        if (tipoBusqueda === 'ruc') {
            data.ruc = parametro;
        } else {
            data.razonSocial = parametro;
        }

        return this.post('/buscar-persona-juridica-sunarp', data);
    }

    /**
     * Consultar partida registral en SUNARP
     */
    async consultarPartidaRegistral(persona, dniUsuario, password) {
        return this.post('/consultar-partida-registral', {
            partida: persona.partida,
            zona: persona.zona,
            oficina: persona.oficina,
            dniUsuario,
            password
        });
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

    async obtenerDniYPassword(nombreUsuario) {
        return this.post('/obtener-dni-pass', {
            nombreUsuario
        });
    }


}

const api = new API();