class ApiClient {
    constructor() {
        this.baseURL = Constants.API.BASE_URL;
        this.csrfToken = null;
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const method = (options.method || 'GET').toUpperCase();

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
                console.error('Error parsing JSON:', e);
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
            console.error('API Error:', error);
            throw error;
        }
    }

    async ensureCSRF() {
        if (this.csrfToken) return;

        try {
            const response = await fetch(`${this.baseURL}${Constants.API.ENDPOINTS.CSRF_TOKEN}`, { method: 'GET' });
            const data = await response.json();

            if (data && data.token) {
                this.csrfToken = data.token;
            }
        } catch (e) {
            console.error('Error obtaining CSRF token:', e);
        }
    }

    get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    }

    post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
}

class AuthService extends ApiClient {
    async login(nombreUsuario, password) {
        const response = await this.post(Constants.API.ENDPOINTS.LOGIN, { nombreUsuario, password });

        if (response.success) {
            await this.ensureCSRF();
        }

        return response;
    }

    async validarCUI(cui) {
        return this.post(Constants.API.ENDPOINTS.VALIDAR_CUI, { cui });
    }

    async logout() {
        return this.post(Constants.API.ENDPOINTS.LOGOUT);
    }

    async cambiarPassword(passwordActual, passwordNueva) {
        return this.post(Constants.API.ENDPOINTS.USUARIOS_CAMBIAR_PASS, {
            passwordActual,
            passwordNueva
        });
    }
}

class UsuarioService extends ApiClient {
    async obtenerDatosInicio() {
        return this.get(Constants.API.ENDPOINTS.INICIO);
    }

    async obtener(usuarioId) {
        return this.get(`${Constants.API.ENDPOINTS.USUARIOS_OBTENER}?id=${usuarioId}`);
    }

    async obtenerActual() {
        return this.get(Constants.API.ENDPOINTS.USUARIOS_ACTUAL);
    }

    async listar() {
        return this.get(Constants.API.ENDPOINTS.USUARIOS_LISTAR);
    }

    async obtenerRoles() {
        return this.get(Constants.API.ENDPOINTS.USUARIOS_ROL);
    }

    async obtenerTipoPersonal() {
        return this.get(Constants.API.ENDPOINTS.USUARIOS_TIPO_PERSONAL);
    }

    async crear(data) {
        return this.post(Constants.API.ENDPOINTS.USUARIOS_REGISTRAR, { data });
    }

    async actualizar(data) {
        return this.put(Constants.API.ENDPOINTS.USUARIOS_ACTUALIZAR, { data });
    }

    async eliminar(usuarioId) {
        return this.post(Constants.API.ENDPOINTS.USUARIOS_ELIMINAR, { usuario_id: usuarioId });
    }

    async obtenerDniYPassword(nombreUsuario) {
        return this.post(Constants.API.ENDPOINTS.USUARIOS_OBTENER_DNI_PASS, { nombreUsuario });
    }
}

class RolService extends ApiClient {
    async listar() {
        return this.get(Constants.API.ENDPOINTS.ROLES_LISTAR);
    }

    async obtener(rolId) {
        return this.get(`${Constants.API.ENDPOINTS.ROLES_OBTENER}?id=${rolId}`);
    }

    async crear(data) {
        return this.post(Constants.API.ENDPOINTS.ROLES_CREAR, data);
    }

    async actualizar(data) {
        return this.put(Constants.API.ENDPOINTS.ROLES_ACTUALIZAR, data);
    }

    async eliminar(rolId) {
        return this.post(Constants.API.ENDPOINTS.ROLES_ELIMINAR, { rol_id: rolId });
    }

    async listarModulos() {
        return this.get(Constants.API.ENDPOINTS.ROLES_MODULOS);
    }
}

class ModuloService extends ApiClient {
    async listar() {
        return this.get(Constants.API.ENDPOINTS.MODULOS_LISTAR);
    }

    async obtener(moduloId) {
        return this.get(`${Constants.API.ENDPOINTS.MODULOS_OBTENER}?id=${moduloId}`);
    }

    async crear(data) {
        return this.post(Constants.API.ENDPOINTS.MODULOS_REGISTRAR, data);
    }

    async actualizar(data) {
        return this.put(Constants.API.ENDPOINTS.MODULOS_ACTUALIZAR, data);
    }

    async eliminar(moduloId) {
        return this.post(Constants.API.ENDPOINTS.MODULOS_ELIMINAR, { moduloId });
    }

    async toggleEstado(moduloId, estado) {
        return this.post(Constants.API.ENDPOINTS.MODULOS_TOGGLE_ESTADO, { modulo_id: moduloId, estado });
    }

    async obtenerPorUsuario() {
        return this.get(Constants.API.ENDPOINTS.MODULOS_POR_USUARIO);
    }
}

class ConsultaService extends ApiClient {
    async consultarDNI(data) {
        return this.post(Constants.API.ENDPOINTS.CONSULTAS_DNI, data);
    }

    async consultarRUC(ruc) {
        return this.post(Constants.API.ENDPOINTS.CONSULTAS_RUC, { ruc });
    }

    async buscarRazonSocial(razonSocial) {
        return this.post(Constants.API.ENDPOINTS.CONSULTAS_BUSCAR_RAZON_SOCIAL, { razonSocial });
    }

    async buscarPersonaNatural(dni, dniUsuario, password) {
        return this.post(Constants.API.ENDPOINTS.CONSULTAS_BUSCAR_NATURAL, { dni, dniUsuario, password });
    }

    async buscarPersonaJuridica(data) {
        return this.post(Constants.API.ENDPOINTS.CONSULTAS_BUSCAR_JURIDICA, data);
    }

    async obtenerOficinasRegistrales() {
        return this.get(Constants.API.ENDPOINTS.CONSULTAS_OFICINAS);
    }

    async consultarPartidaLASIR(data) {
        return this.post(Constants.API.ENDPOINTS.CONSULTAS_PARTIDAS_LASIRSARP, data);
    }

    async consultarPartidaNatural(data) {
        return this.post(Constants.API.ENDPOINTS.CONSULTAS_PARTIDAS_NATURAL, data);
    }

    async consultarPartidaJuridica(data) {
        return this.post(Constants.API.ENDPOINTS.CONSULTAS_PARTIDAS_JURIDICA, data);
    }

    async cargarDetallePartida(params) {
        const data = {
            numero_partida: params.numero_partida,
            codigo_zona: params.codigo_zona,
            codigo_oficina: params.codigo_oficina,
            numero_placa: params.numero_placa || ''
        };
        return this.post(Constants.API.ENDPOINTS.CONSULTAS_DETALLE_PARTIDA, data);
    }

    async actualizarPasswordRENIEC(data) {
        return this.post(Constants.API.ENDPOINTS.ACTUALIZAR_PASS_RENIEC, data);
    }
}

const api = new ApiClient();
const authService = new AuthService();
const usuarioService = new UsuarioService();
const rolService = new RolService();
const moduloService = new ModuloService();
const consultaService = new ConsultaService();

window.API = ApiClient;
window.api = api;
window.authService = authService;
window.usuarioService = usuarioService;
window.rolService = rolService;
window.moduloService = moduloService;
window.consultaService = consultaService;
