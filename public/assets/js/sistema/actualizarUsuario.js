// ============================================
// ✏️ MÓDULO DE ACTUALIZAR USUARIO
// ============================================

const ModuloActualizarUsuario = {
    elementos: {},
    inicializado: false,
    
    // Estado del módulo
    usuarioIdActual: null,
    personaIdActual: null,

    // Estado del usuario actual
    usuarioElegido: {
        id: null,
        personaId: null,
        dni: null,
        login: null,
        nombreCompleto: null,
        modulos: [] // ← Agregar módulos del usuario
    },

    // ============================================
    // 🚀 INICIALIZACIÓN
    // ============================================
    async init() {
        if (this.inicializado) {
            return;
        }
        
        this.cachearElementos();
        this.setupEventListeners();
        await this.cargarDatosIniciales();
        
        this.inicializado = true;
    },

    // ============================================
    // 📦 CACHEAR ELEMENTOS DEL DOM
    // ============================================
    cachearElementos() {
        this.elementos = {
            selectorUsuario: document.getElementById('selectorUsuario'),
            formularioEdicion: document.getElementById('formularioEdicion'),
            btnActualizar: document.getElementById('btnActualizar'),
            alertContainer: document.getElementById('alertContainerActualizarUsuario'),
            
            // Toggle password
            togglePassword: document.getElementById('togglePassword'),
            togglePasswordConfirm: document.getElementById('togglePasswordConfirm'),
            
            // Inputs
            perTipoActualizar: document.getElementById('perTipo-actualizar'),
            perTipoPersonal: document.getElementById('per-tipo-personal'),
            perDocumentoTipoActualizar: document.getElementById('perDocumentoTipo-actualizar'),
            perDocumentoNum: document.getElementById('per-documento-num'),
            perNombre: document.getElementById('per-nombre'),
            perApellidoPat: document.getElementById('per-apellido-pat'),
            perApellidoMat: document.getElementById('per-apellido-mat'),
            perSexoActualizar: document.getElementById('perSexo-actualizar'),
            perEmail: document.getElementById('per-email'),
            
            usuLogin: document.getElementById('usu-login'),
            usuPassActual: document.getElementById('usuPassActual'),
            usuPass: document.getElementById('usu-pass'),
            usuPassConfirm: document.getElementById('usu-passConfirm'),
            usuPermisoActualizar: document.getElementById('usuPermiso-actualizar'),
            usuEstadoActualizar: document.getElementById('usuEstado-actualizar'),
            cui: document.getElementById('cui')
        };
    },

    // ============================================
    // 🎯 CONFIGURAR EVENT LISTENERS
    // ============================================
    setupEventListeners() {
        // Selector de usuario
        if (this.elementos.selectorUsuario) {
            this.elementos.selectorUsuario.addEventListener('change', () => {
                this.cargarDatosUsuarioSeleccionado();
            });
        }

        // Toggle password visibility
        this.configurarTogglePassword('usu-pass', 'togglePassword');
        this.configurarTogglePassword('usu-passConfirm', 'togglePasswordConfirm');
    },

    // ============================================
    // 👁️ CONFIGURAR TOGGLE PASSWORD
    // ============================================
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
                this.cargarListaUsuarios(),
                this.cargarRoles(),
                this.cargarTiposDePersonal()
            ]);
        } catch (error) {
            console.error('❌ Error al cargar datos iniciales:', error);
        }
    },

    // ============================================
    // 📋 CARGAR LISTA DE USUARIOS
    // ============================================
    async cargarListaUsuarios() {
        try {
            const response = await api.listarUsuarios();
            
            if (response.success && response.data) {
                const select = this.elementos.selectorUsuario;
                if (!select) return;
                
                select.innerHTML = '<option value="">-- Seleccione un usuario --</option>';
                
                response.data.forEach(usuario => {
                    const option = document.createElement('option');
                    option.value = usuario.USU_id;
                    option.textContent = `${usuario.nombre_completo} (${usuario.PER_documento_numero})`;
                    option.dataset.nombreCompleto = usuario.nombre_completo;
                    option.dataset.documento = usuario.PER_documento_numero;
                    option.dataset.login = usuario.USU_username;
                    select.appendChild(option);
                });

            } else {
                mostrarAlerta(response.message || 'Error al cargar usuarios', 'error', 'alertContainerActualizarUsuario');
            }
        } catch (error) {
            console.error('❌ Error al cargar lista de usuarios:', error);
            mostrarAlerta('Error al cargar la lista de usuarios', 'error', 'alertContainerActualizarUsuario');
        }
    },

    // ============================================
    // 🔧 CARGAR ROLES
    // ============================================
    async cargarRoles() {
        try {
            const response = await api.listarRoles();

            const select = this.elementos.usuPermisoActualizar;
            if (!select) return;

            select.innerHTML = '<option value="">Seleccionar...</option>';

            response.data.forEach(rol => {
                const option = document.createElement('option');
                option.value = rol.ROL_id;
                option.textContent = rol.ROL_nombre;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('❌ Error cargando roles:', error);
            mostrarAlerta('No se pudieron cargar los roles.', 'danger', 'alertContainerActualizarUsuario');
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

            response.data.forEach(tipo => {
                const option = document.createElement('option');
                option.value = tipo.TPE_id;
                option.textContent = tipo.TPE_nombre;
                select.appendChild(option);
            });

        } catch (error) {
            console.error('❌ Error cargando tipo de personal:', error);
            mostrarAlerta('No se pudieron cargar los tipos de personal.', 'danger', 'alertContainerActualizarUsuario');
        }
    },

    // ============================================
    // 📥 CARGAR DATOS DEL USUARIO SELECCIONADO
    // ============================================
    async cargarDatosUsuarioSeleccionado() {
        const usuarioId = this.elementos.selectorUsuario.value;
        
        if (!usuarioId) {
            this.elementos.formularioEdicion.style.display = 'none';
            this.limpiarCamposFormulario();
            return;
        }
        
        await this.cargarDatosUsuario(usuarioId);
    },

    // ============================================
    // 📄 CARGAR DATOS DEL USUARIO
    // ============================================
    async cargarDatosUsuario(usuarioId) {
        try {
            this.mostrarCargando(true);
            
            const response = await api.obtenerUsuario(usuarioId);
            
            if (response.success && response.data) {
                const usuario = response.data;
                
                // Guardar IDs
                this.usuarioIdActual = usuario.USU_id;
                this.personaIdActual = usuario.PER_id;
                
                // Mostrar formulario
                this.elementos.formularioEdicion.style.display = 'block';

                // Llenar campos
                this.elementos.perTipoActualizar.value = String(usuario.PER_tipo_persona ?? '');
                this.elementos.perTipoPersonal.value = String(usuario.PER_tipo_personal_id ?? '0');
                this.elementos.perDocumentoTipoActualizar.value = String(usuario.PER_documento_tipo_id ?? '');
                this.elementos.perDocumentoNum.value = usuario.PER_documento_numero || '';
                this.elementos.perNombre.value = usuario.PER_nombres || '';
                this.elementos.perApellidoPat.value = usuario.PER_apellido_paterno || '';
                this.elementos.perApellidoMat.value = usuario.PER_apellido_materno || '';
                this.elementos.perSexoActualizar.value = String(usuario.PER_sexo ?? '');
                this.elementos.perEmail.value = usuario.USU_email || '';
                
                this.elementos.usuLogin.value = usuario.USU_username || '';
                this.elementos.usuPermisoActualizar.value = String(usuario.rol_id ?? '0');
                this.elementos.usuEstadoActualizar.value = String(usuario.PER_estado_id ?? '1');
                this.elementos.cui.value = usuario.USU_cui || '';
                
                // Limpiar campos de contraseña
                this.elementos.usuPassActual.value = '';
                this.elementos.usuPass.value = '';
                this.elementos.usuPassConfirm.value = '';

                const listaModulos = usuario.modulos_acceso
                ? usuario.modulos_acceso.split(',').map(m => m.trim())
                : [];
                this.usuarioElegido = {modulos: listaModulos};
                
            } else {
                mostrarAlerta(response.message || 'Error al cargar usuario', 'error', 'alertContainerActualizarUsuario');
            }
        } catch (error) {
            console.error('❌ Error al cargar usuario:', error);
            mostrarAlerta('Error al cargar los datos del usuario', 'error', 'alertContainerActualizarUsuario');
        } finally {
            this.mostrarCargando(false);
        }
    },

    tieneAccesoRENIEC() {
        const modulosRENIEC = ['DNI', 'PAR'];

        // this.usuarioElegio.modulos es un array de strings
        const tieneAcceso = this.usuarioElegido.modulos.some(modulo =>
            modulosRENIEC.includes(modulo) ||
            modulosRENIEC.some(cod => modulo.includes(cod))
        );

        return tieneAcceso;
    },

    // ============================================
    // ACTUALIZAR USUARIO
    // ============================================
    async actualizarUsuario() {
        try {
            const alerta = document.getElementById('alertContainerActualizarUsuario');
            const titulo = document.getElementById("tituloActualizarUsuario");
            if (alerta) {
                titulo.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            if (!this.usuarioIdActual || !this.personaIdActual) {
                mostrarAlerta('Debe seleccionar un usuario primero', 'warning', 'alertContainerActualizarUsuario');
                return;
            }

            // Validar formulario
            if (!this.validarFormulario()) {
                return;
            }
            
            this.mostrarCargando(true);
            
            // Obtener datos
            const datos = this.obtenerDatosFormulario();
            
            // Verificar si tiene acceso a RENIEC
            const tieneAccesoRENIEC = this.tieneAccesoRENIEC();
            // Si hay contraseña nueva, actualizar en RENIEC primero
            if (datos.USU_pass && datos.USU_pass.trim() !== '' && tieneAccesoRENIEC) {
                const dniUsuario = this.elementos.perDocumentoNum.value;
                const passwordAnterior = this.elementos.usuPassActual.value;
                
                if (!passwordAnterior) {
                    mostrarAlerta('Debe ingresar la contraseña actual para cambiarla', 'warning', 'alertContainerActualizarUsuario');
                    this.mostrarCargando(false);
                    return;
                }
                
                mostrarAlerta('Actualizando contraseña en RENIEC...', 'info', 'alertContainerActualizarUsuario');
                
                const resultadoRENIEC = await api.actualizarPasswordRENIEC({
                    credencialAnterior: passwordAnterior,
                    credencialNueva: datos.USU_pass,
                    nuDni: dniUsuario
                });
                
                if (!resultadoRENIEC.success) {
                    mostrarAlerta('Error al actualizar contraseña en RENIEC: ' + resultadoRENIEC.message, 'error', 'alertContainerActualizarUsuario');
                    this.mostrarCargando(false);
                    return;
                }
                
                mostrarAlerta('✓ Contraseña actualizada en RENIEC', 'success', 'alertContainerActualizarUsuario');
            }
            
            // Actualizar en base de datos
            mostrarAlerta('Actualizando datos en el sistema...', 'info', 'alertContainerActualizarUsuario');
            const response = await api.actualizarUsuario(datos);
            
            if (response.success) {
                mostrarAlerta('✓ Usuario actualizado correctamente', 'success', 'alertContainerActualizarUsuario');
                
                // Limpiar campos de contraseña
                this.elementos.usuPassActual.value = '';
                this.elementos.usuPass.value = '';
                this.elementos.usuPassConfirm.value = '';
                
                // Recargar datos
                setTimeout(() => {
                    this.cargarDatosUsuario(this.usuarioIdActual);
                }, 1500);
                
            } else {
                mostrarAlerta('❌ ' + (response.message || 'Error al actualizar usuario'), 'error', 'alertContainerActualizarUsuario');
            }
        } catch (error) {
            console.error('❌ Error al actualizar usuario:', error);
            mostrarAlerta('❌ Error al actualizar el usuario: ' + error.message, 'error', 'alertContainerActualizarUsuario');
        } finally {
            this.mostrarCargando(false);
        }
    },

    // ============================================
    // 📝 OBTENER DATOS DEL FORMULARIO
    // ============================================
    obtenerDatosFormulario() {
        return {
            USU_id: this.usuarioIdActual,
            PER_id: this.personaIdActual,
            PER_tipo: parseInt(this.elementos.perTipoActualizar.value),
            PER_tipoPersonal: parseInt(this.elementos.perTipoPersonal.value),
            PER_documento_tipo: parseInt(this.elementos.perDocumentoTipoActualizar.value),
            PER_documento_num: this.elementos.perDocumentoNum.value.trim(),
            PER_nombre: this.elementos.perNombre.value.trim(),
            PER_apellido_pat: this.elementos.perApellidoPat.value.trim(),
            PER_apellido_mat: this.elementos.perApellidoMat.value.trim() || null,
            PER_sexo: this.elementos.perSexoActualizar.value,
            PER_email: this.elementos.perEmail.value.trim() || null,
            USU_login: this.elementos.usuLogin.value.trim(),
            USU_passActual: this.elementos.usuPassActual.value.trim() || null,
            USU_pass: this.elementos.usuPass.value.trim() || null,
            USU_permiso: parseInt(this.elementos.usuPermisoActualizar.value),
            USU_estado: parseInt(this.elementos.usuEstadoActualizar.value)
        };
    },

    // ============================================
    // ✅ VALIDAR FORMULARIO
    // ============================================
    validarFormulario() {
        const camposRequeridos = [
            { elem: this.elementos.perTipoActualizar, nombre: 'Tipo de Persona' },
            { elem: this.elementos.perDocumentoTipoActualizar, nombre: 'Tipo de Documento' },
            { elem: this.elementos.perDocumentoNum, nombre: 'Número de Documento' },
            { elem: this.elementos.perNombre, nombre: 'Nombres' },
            { elem: this.elementos.perApellidoPat, nombre: 'Apellido Paterno' },
            { elem: this.elementos.perSexoActualizar, nombre: 'Sexo' },
            { elem: this.elementos.usuLogin, nombre: 'Login/Usuario' }
        ];
        
        for (const campo of camposRequeridos) {
            if (!campo.elem.value || campo.elem.value.trim() === '') {
                mostrarAlerta(`El campo "${campo.nombre}" es requerido`, 'warning', 'alertContainerActualizarUsuario');
                campo.elem.focus();
                return false;
            }
        }
        
        // Validar contraseñas
        const password = this.elementos.usuPass.value;
        const passwordConfirm = this.elementos.usuPassConfirm.value;
        
        if (password || passwordConfirm) {
            if (password !== passwordConfirm) {
                mostrarAlerta('Las contraseñas no coinciden', 'warning', 'alertContainerActualizarUsuario');
                this.elementos.usuPassConfirm.focus();
                return false;
            }
            
            if (password.length < 6) {
                mostrarAlerta('La contraseña debe tener al menos 6 caracteres', 'warning', 'alertContainerActualizarUsuario');
                this.elementos.usuPass.focus();
                return false;
            }
        }
        
        // Validar email
        const email = this.elementos.perEmail.value;
        if (email && !this.validarEmail(email)) {
            mostrarAlerta('El formato del email no es válido', 'warning', 'alertContainerActualizarUsuario');
            this.elementos.perEmail.focus();
            return false;
        }
        
        return true;
    },

    validarEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },

    // ============================================
    // 🔧 UTILIDADES
    // ============================================
    mostrarCargando(mostrar) {
        const btnActualizar = this.elementos.btnActualizar;
        if (!btnActualizar) return;
        
        const loading = btnActualizar.querySelector('.loading');
        const icon = btnActualizar.querySelector('i.fa-save');
        
        if (mostrar) {
            btnActualizar.disabled = true;
            if (loading) loading.style.display = 'inline-block';
            if (icon) icon.style.display = 'none';
        } else {
            btnActualizar.disabled = false;
            if (loading) loading.style.display = 'none';
            if (icon) icon.style.display = 'inline';
        }
    },

    // ============================================
    // 🧹 LIMPIAR FORMULARIO
    // ============================================
    limpiarFormulario() {
        if (!this.usuarioIdActual) {
            this.limpiarCamposFormulario();
            return;
        }
        
        if (confirm('¿Está seguro de que desea recargar los datos originales del usuario?')) {
            this.cargarDatosUsuario(this.usuarioIdActual);
        }
    },

    limpiarCamposFormulario() {
        Object.values(this.elementos).forEach(elem => {
            if (elem && (elem.tagName === 'INPUT' || elem.tagName === 'SELECT')) {
                elem.value = '';
            }
        });
        
        this.usuarioIdActual = null;
        this.personaIdActual = null;
        
    }
};

// ============================================
// 🌐 FUNCIONES GLOBALES
// ============================================
window.cargarListaUsuarios = async function() {
    if (ModuloActualizarUsuario.inicializado) {
        await ModuloActualizarUsuario.cargarListaUsuarios();
    }
};

window.cargarDatosUsuarioSeleccionado = async function() {
    if (ModuloActualizarUsuario.inicializado) {
        await ModuloActualizarUsuario.cargarDatosUsuarioSeleccionado();
    }
};

window.actualizarUsuario = async function() {
    if (ModuloActualizarUsuario.inicializado) {
        await ModuloActualizarUsuario.actualizarUsuario();
    } else {
        console.warn('⚠️ Módulo Actualizar Usuario no está inicializado');
    }
};

window.limpiarFormulario = function() {
    if (ModuloActualizarUsuario.inicializado) {
        ModuloActualizarUsuario.limpiarFormulario();
    }
};
// ============================================
// 🔧 AUTO-REGISTRO DEL MÓDULO
// ============================================
if (typeof window.registrarModulo === 'function') {
    window.registrarModulo('actualizarusuario', ModuloActualizarUsuario);
}

// Auto-inicializar cuando se cargue el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // No auto-inicializar, esperar a que Dashboard lo llame
    });
}