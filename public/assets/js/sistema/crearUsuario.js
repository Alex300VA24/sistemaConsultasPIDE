// ============================================
// 👤 MÓDULO DE CREAR USUARIO
// ============================================

const ModuloCrearUsuario = {
    elementos: {},
    inicializado: false,

    // ============================================
    // 🚀 INICIALIZACIÓN
    // ============================================
    async init() {
        if (this.inicializado) {
            console.log('ℹ️ Módulo Crear Usuario ya está inicializado');
            return;
        }

        console.log('👤 Inicializando Módulo Crear Usuario...');
        
        this.cachearElementos();
        this.setupEventListeners();
        await this.cargarDatosIniciales();
        
        this.inicializado = true;
        console.log('✅ Módulo Crear Usuario inicializado correctamente');
    },

    // ============================================
    // 📦 CACHEAR ELEMENTOS DEL DOM
    // ============================================
    cachearElementos() {
        this.elementos = {
            btnCrear: document.getElementById('btnCrear'),
            alertContainer: document.getElementById('alertContainerCrearUsuario'),
            
            // Inputs persona
            perTipo: document.getElementById('perTipo'),
            perDocumentoTipo: document.getElementById('perDocumentoTipo'),
            perDocumentoNum: document.getElementById('perDocumentoNum'),
            perNombre: document.getElementById('perNombre'),
            perApellidoPat: document.getElementById('perApellidoPat'),
            perApellidoMat: document.getElementById('perApellidoMat'),
            perSexo: document.getElementById('perSexo'),
            perEmail: document.getElementById('perEmail'),
            perTipoPersonal: document.getElementById('perTipoPersonal'),
            
            // Inputs usuario
            usuLogin: document.getElementById('usuLogin'),
            usuPass: document.getElementById('usuPass'),
            usuPassConfirm: document.getElementById('usuPassConfirm'),
            usuPermiso: document.getElementById('usuPermiso'),
            usuEstado: document.getElementById('usuEstado'),
            cui: document.getElementById('cui'),
            togglePassword: document.getElementById('togglePasswordCrear'),
            togglePasswordConfirm: document.getElementById('togglePasswordConfirmCrear'),
            
            // Inputs RENIEC/SUNARP
            reniecDni: document.getElementById('reniecDni'),
            reniecRuc: document.getElementById('reniecRuc')
        };
    },

    // ============================================
    // 🎯 CONFIGURAR EVENT LISTENERS
    // ============================================
    setupEventListeners() {
        // El botón crear ya tiene onclick en HTML, pero podemos agregarlo aquí también
        if (this.elementos.btnCrear) {
            // El onclick global ya existe, no hace falta agregar otro
            console.log('✓ Botón crear configurado');
        }
        this.configurarTogglePassword('usuPass', 'togglePasswordCrear');
        this.configurarTogglePassword('usuPassConfirm', 'togglePasswordConfirmCrear');
    },
    configurarTogglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        if (input && icon) {
            icon.addEventListener('click', () => {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    },

    // ============================================
    // 📊 CARGAR DATOS INICIALES
    // ============================================
    async cargarDatosIniciales() {
        try {
            await Promise.all([
                this.cargarRoles(),
                this.cargarTiposDePersonal()
            ]);
            console.log('✓ Datos iniciales cargados');
        } catch (error) {
            console.error('❌ Error al cargar datos iniciales:', error);
        }
    },

    // ============================================
    // 🔧 CARGAR ROLES
    // ============================================
    async cargarRoles() {
        try {
            const response = await api.obtenerRoles();

            const select = this.elementos.usuPermiso;
            if (!select) return;

            // Limpiar antes de cargar
            select.innerHTML = '<option value="">Seleccionar...</option>';

            // Agregar roles
            response.data.forEach(rol => {
                const option = document.createElement('option');
                option.value = rol.ROL_id;
                option.textContent = rol.ROL_nombre;
                select.appendChild(option);
            });

            console.log('✓ Roles cargados:', response.data.length);
        } catch (error) {
            console.error('❌ Error cargando roles:', error);
            mostrarAlerta('No se pudieron cargar los roles.', 'error', 'alertContainerCrearUsuario');
        }
    },

    // ============================================
    // 🔧 CARGAR TIPOS DE PERSONAL
    // ============================================
    async cargarTiposDePersonal() {
        try {
            const response = await api.obtenerTipoPersonal();

            const select = this.elementos.perTipoPersonal;
            if (!select) return;

            // Agregar tipos de personal
            response.data.forEach(tipo => {
                const option = document.createElement('option');
                option.value = tipo.TPE_id;
                option.textContent = tipo.TPE_nombre;
                select.appendChild(option);
            });

            console.log('✓ Tipos de personal cargados:', response.data.length);
        } catch (error) {
            console.error('❌ Error cargando tipo de personal:', error);
            mostrarAlerta('No se pudieron cargar los tipos de personal.', 'error', 'alertContainerCrearUsuario');
        }
    },

    // ============================================
    // 💾 CREAR USUARIO
    // ============================================
    async crearUsuario() {
        this.limpiarAlertas();

        // Recolectar datos
        const data = this.recolectarDatos();

        // Validaciones
        if (!this.validarDatosPersonales(data)) return;
        if (!this.validarDatosUsuario(data)) return;

        // Mostrar loading
        this.mostrarLoading(true);

        try {
            const response = await api.crearUsuario(data);

            if (response.success) {
                mostrarAlerta(response.message || 'Usuario creado correctamente.', 'success', 'alertContainerCrearUsuario');
                this.limpiarFormulario();
                
                // Si existe función global para cargar lista
                if (typeof cargarListaUsuarios === 'function') {
                    cargarListaUsuarios();
                }
            } else {
                mostrarAlerta(response.message || 'Error al crear el usuario.', 'error', 'alertContainerCrearUsuario');
            }

        } catch (error) {
            console.error('Error en crearUsuario:', error);
            mostrarAlerta('Error de conexión con el servidor.', 'error', 'alertContainerCrearUsuario');
        } finally {
            this.mostrarLoading(false);
        }
    },

    // ============================================
    // 📝 RECOLECTAR DATOS DEL FORMULARIO
    // ============================================
    recolectarDatos() {
        return {
            perTipo: this.getValue('perTipo'),
            perDocumentoTipo: this.getValue('perDocumentoTipo'),
            perDocumentoNum: this.getValue('perDocumentoNum'),
            perNombre: this.getValue('perNombre'),
            perApellidoPat: this.getValue('perApellidoPat'),
            perApellidoMat: this.getValue('perApellidoMat'),
            perSexo: this.getValue('perSexo'),
            perEmail: this.getValue('perEmail'),
            perTipoPersonal: this.getValue('perTipoPersonal'),

            usuLogin: this.getValue('usuLogin'),
            usuPass: this.getValue('usuPass'),
            usuPassConfirm: this.getValue('usuPassConfirm'),
            usuPermiso: this.getValue('usuPermiso'),
            usuEstado: this.getValue('usuEstado'),
            cui: this.getValue('cui'),

            reniecDni: this.getValue('reniecDni'),
            reniecRuc: this.getValue('reniecRuc')
        };
    },

    // ============================================
    // ✅ VALIDACIONES
    // ============================================
    validarDatosPersonales(data) {
        if (!data.perTipo || !data.perDocumentoTipo || !data.perDocumentoNum || 
            !data.perNombre || !data.perApellidoPat) {
            mostrarAlerta('Completa todos los campos personales obligatorios.', 'info', 'alertContainerCrearUsuario');
            return false;
        }
        return true;
    },

    validarDatosUsuario(data) {
        if (!data.usuLogin || !data.usuPass || !data.usuPassConfirm) {
            mostrarAlerta('Completa los campos de usuario y contraseña.', 'info', 'alertContainerCrearUsuario');
            return false;
        }

        if (data.usuPass !== data.usuPassConfirm) {
            mostrarAlerta('Las contraseñas no coinciden.', 'info', 'alertContainerCrearUsuario');
            return false;
        }

        return true;
    },

    // ============================================
    // 🔧 UTILIDADES
    // ============================================
    getValue(id) {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
    },

    limpiarAlertas() {
        if (this.elementos.alertContainer) {
            this.elementos.alertContainer.innerHTML = '';
        }
    },

    mostrarLoading(mostrar) {
        const btnCrear = this.elementos.btnCrear;
        if (!btnCrear) return;

        const loading = btnCrear.querySelector('.loading');

        if (mostrar) {
            btnCrear.disabled = true;
            if (loading) loading.style.display = 'inline-block';
        } else {
            btnCrear.disabled = false;
            if (loading) loading.style.display = 'none';
        }
    },

    // ============================================
    // 🧹 LIMPIAR FORMULARIO
    // ============================================
    limpiarFormulario() {
        document.querySelectorAll('.usuario-container input, .usuario-container select').forEach(el => {
            el.value = '';
        });
        console.log('🧹 Formulario de crear usuario limpiado');
    }
};

// ============================================
// 🌐 FUNCIONES GLOBALES
// ============================================
window.crearUsuario = async function() {
    if (ModuloCrearUsuario.inicializado) {
        await ModuloCrearUsuario.crearUsuario();
    } else {
        console.warn('⚠️ Módulo Crear Usuario no está inicializado');
    }
};

window.limpiarFormularioCrearUsuario = function() {
    if (ModuloCrearUsuario.inicializado) {
        ModuloCrearUsuario.limpiarFormulario();
    }
};