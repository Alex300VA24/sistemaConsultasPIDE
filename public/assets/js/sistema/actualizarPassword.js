// ============================================
// MÓDULO DE ACTUALIZAR CONTRASEÑA
// ============================================

const ModuloActualizarPassword = {
    elementos: {},
    inicializado: false,
    BASE_URL: '/MDESistemaPIDE/public/',
    
    // Estado del usuario actual
    usuarioActual: {
        id: null,
        personaId: null,
        dni: null,
        login: null,
        nombreCompleto: null,
        modulos: [] // ← Agregar módulos del usuario
    },

    // ============================================
    // INICIALIZACIÓN
    // ============================================
    async init() {
        if (this.inicializado) {
            return;
        }
        
        this.cachearElementos();
        this.setupEventListeners();
        await this.cargarDatosUsuarioActual();
        
        this.inicializado = true;
    },

    // ============================================
    // CACHEAR ELEMENTOS DEL DOM
    // ============================================
    cachearElementos() {
        this.elementos = {
            infoUsuarioActual: document.getElementById('infoUsuarioActual'),
            btnActualizar: document.getElementById('btnActualizarPassword'),
            alertContainer: document.getElementById('alertContainerPassword'),
            
            // Inputs
            usuPassActual: document.getElementById('usuPassActualPassword'),
            usuPass: document.getElementById('usu-passPassword'),
            usuPassConfirm: document.getElementById('usu-passConfirmPassword'),
            
            // Toggle icons
            togglePassword: document.getElementById('togglePassword2'),
            togglePasswordConfirm: document.getElementById('togglePasswordConfirm2')
        };
    },

    // ============================================
    // CONFIGURAR EVENT LISTENERS
    // ============================================
    setupEventListeners() {
        // Toggle password visibility
        this.configurarTogglePassword('usu-passPassword', 'togglePassword2');
        this.configurarTogglePassword('usu-passConfirmPassword', 'togglePasswordConfirm2');
    },

    // ============================================
    // CONFIGURAR TOGGLE PASSWORD
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
    // CARGAR DATOS DEL USUARIO ACTUAL
    // ============================================
    async cargarDatosUsuarioActual() {
        try {
            const response = await api.obtenerUsuarioActual();

            if (response.success && response.data) {
                const usuario = response.data;
                const cadenasModulos = usuario.modulos_acceso; 
                const listaModulos = usuario.modulos_acceso
                ? usuario.modulos_acceso.split(',').map(m => m.trim())
                : [];

                
                // Guardar datos del usuario incluyendo módulos
                this.usuarioActual = {
                    id: usuario.USU_id,
                    dni: usuario.PER_documento_numero,
                    login: usuario.USU_username,
                    nombreCompleto: `${usuario.PER_nombres} ${usuario.PER_apellido_paterno} ${usuario.PER_apellido_materno || ''}`.trim(),
                    modulos: listaModulos || [] // ← Módulos del usuario
                };

                this.mostrarInfoUsuario();
                
            } else {
                mostrarAlerta('Error al cargar datos del usuario actual', 'error', 'alertContainerPassword');
            }
        } catch (error) {
            console.error('❌ Error al cargar usuario actual:', error);
            mostrarAlerta('Error al cargar los datos del usuario', 'error', 'alertContainerPassword');
        }
    },

    // ============================================
    // MOSTRAR INFORMACIÓN DEL USUARIO
    // ============================================
    mostrarInfoUsuario() {
        const infoElement = this.elementos.infoUsuarioActual;
        if (infoElement) {
            infoElement.innerHTML = `
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #007bff;">
                    <strong><i class="fas fa-user"></i> Usuario:</strong> ${this.usuarioActual.nombreCompleto}<br>
                    <strong><i class="fas fa-id-card"></i> DNI:</strong> ${this.usuarioActual.dni}<br>
                    <strong><i class="fas fa-sign-in-alt"></i> Login:</strong> ${this.usuarioActual.login}
                </div>
            `;
        }
    },

    // ============================================
    // VERIFICAR SI TIENE ACCESO A RENIEC
    // ============================================
    tieneAccesoRENIEC() {
        const modulosRENIEC = ['DNI', 'PAR'];

        // this.usuarioActual.modulos es un array de strings
        const tieneAcceso = this.usuarioActual.modulos.some(modulo =>
            modulosRENIEC.includes(modulo) ||
            modulosRENIEC.some(cod => modulo.includes(cod))
        );

        return tieneAcceso;
    },


    // ============================================
    // 🔐 ACTUALIZAR CONTRASEÑA
    // ============================================
    async actualizarPassword() {
        try {
            this.mostrarCargando(true);
            
            // Validar datos del usuario
            if (!this.usuarioActual.id || !this.usuarioActual.dni) {
                mostrarAlerta('No se encontraron los datos del usuario. Por favor, recargue la página.', 'error', 'alertContainerPassword');
                this.mostrarCargando(false);
                return;
            }
            
            // Validar formulario
            if (!this.validarFormulario()) {
                this.mostrarCargando(false);
                return;
            }
            
            // Obtener datos
            const datos = this.obtenerDatosFormulario();
            
            // Verificar si tiene acceso a RENIEC
            const tieneAccesoRENIEC = this.tieneAccesoRENIEC();
            
            if (tieneAccesoRENIEC) {
                // PASO 1: Actualizar en RENIEC
                mostrarAlerta('🔄 Actualizando contraseña en RENIEC...', 'info', 'alertContainerPassword');
                
                const resultadoRENIEC = await api.actualizarPasswordRENIEC({
                    credencialAnterior: datos.passwordActual,
                    credencialNueva: datos.passwordNueva,
                    nuDni: this.usuarioActual.dni
                });
                
                if (!resultadoRENIEC.success) {
                    mostrarAlerta('❌ Error al actualizar contraseña en RENIEC: ' + resultadoRENIEC.message, 'error', 'alertContainerPassword');
                    this.mostrarCargando(false);
                    return;
                }
                
                mostrarAlerta('✓ Contraseña actualizada en RENIEC correctamente', 'success', 'alertContainerPassword');
            } else {
                mostrarAlerta('ℹ️ Actualizando solo en el sistema local (sin acceso a RENIEC)', 'info', 'alertContainerPassword');
            }
            
            // PASO 2: Actualizar en base de datos local (SIEMPRE)
            mostrarAlerta('🔄 Actualizando contraseña en el sistema local...', 'info', 'alertContainerPassword');
            
            const response = await api.actualizarPassword({
                USU_id: this.usuarioActual.id,
                USU_passActual: datos.passwordActual,
                USU_pass: datos.passwordNueva
            });
            
            if (response.success) {
                const mensaje = tieneAccesoRENIEC 
                    ? 'Contraseña actualizada correctamente en ambos sistemas'
                    : 'Contraseña actualizada correctamente en el sistema';
                    
                mostrarAlerta(mensaje, 'success', 'alertContainerPassword');
                
                // Limpiar formulario y redirigir
                setTimeout(() => {
                    this.limpiarFormulario();
                    mostrarAlerta('Contraseña actualizada exitosamente. Por seguridad, será redirigido al login.', 'info', 'alertContainerPassword');
                    
                    // Redirigir al login
                    setTimeout(async () => {
                        try {
                            await api.logout();
                            window.location.href = this.BASE_URL + 'login';
                        } catch (error) {
                            console.error('❌ Error al cerrar sesión:', error);
                            alert('Error al cerrar sesión');
                        }
                    }, 2000);
                }, 2000);
            } else {
                mostrarAlerta('Error al actualizar contraseña en el sistema: ' + (response.message || 'Error desconocido'), 'error', 'alertContainerPassword');
            }
            
        } catch (error) {
            console.error('Error al actualizar contraseña:', error);
            mostrarAlerta('Error al actualizar la contraseña: ' + error.message, 'error', 'alertContainerPassword');
        } finally {
            this.mostrarCargando(false);
        }
    },

    // ============================================
    // 📝 OBTENER DATOS DEL FORMULARIO
    // ============================================
    obtenerDatosFormulario() {
        return {
            passwordActual: this.elementos.usuPassActual.value.trim(),
            passwordNueva: this.elementos.usuPass.value.trim(),
            passwordConfirm: this.elementos.usuPassConfirm.value.trim()
        };
    },

    // ============================================
    // ✅ VALIDAR FORMULARIO
    // ============================================
    validarFormulario() {
        const datos = this.obtenerDatosFormulario();
        
        // Validar contraseña actual
        if (!datos.passwordActual) {
            mostrarAlerta('Debe ingresar la contraseña actual', 'warning', 'alertContainerPassword');
            this.elementos.usuPassActual.focus();
            return false;
        }
        
        // Validar nueva contraseña
        if (!datos.passwordNueva) {
            mostrarAlerta('Debe ingresar la nueva contraseña', 'warning', 'alertContainerPassword');
            this.elementos.usuPass.focus();
            return false;
        }
        
        // Validar longitud mínima
        if (datos.passwordNueva.length < 6) {
            mostrarAlerta('La nueva contraseña debe tener al menos 6 caracteres', 'warning', 'alertContainerPassword');
            this.elementos.usuPass.focus();
            return false;
        }
        
        // Validar coincidencia
        if (datos.passwordNueva !== datos.passwordConfirm) {
            mostrarAlerta('Las contraseñas nuevas no coinciden', 'warning', 'alertContainerPassword');
            this.elementos.usuPassConfirm.focus();
            return false;
        }
        
        // Validar que sea diferente
        if (datos.passwordActual === datos.passwordNueva) {
            mostrarAlerta('La nueva contraseña debe ser diferente a la actual', 'warning', 'alertContainerPassword');
            this.elementos.usuPass.focus();
            return false;
        }
        
        return true;
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
            btnActualizar.style.opacity = '0.7';
            btnActualizar.style.cursor = 'not-allowed';
            
            if (loading) {
                loading.style.display = 'inline-block';
                loading.style.cssText = `
                    display: inline-block;
                    width: 16px;
                    height: 16px;
                    border: 2px solid #ffffff;
                    border-top-color: transparent;
                    border-radius: 50%;
                    animation: spin 0.8s linear infinite;
                    margin-right: 8px;
                `;
            }
            if (icon) icon.style.display = 'none';
        } else {
            btnActualizar.disabled = false;
            btnActualizar.style.opacity = '1';
            btnActualizar.style.cursor = 'pointer';
            
            if (loading) loading.style.display = 'none';
            if (icon) icon.style.display = 'inline';
        }
    },

    // ============================================
    // 🧹 LIMPIAR FORMULARIO
    // ============================================
    limpiarFormulario() {
        this.elementos.usuPassActual.value = '';
        this.elementos.usuPass.value = '';
        this.elementos.usuPassConfirm.value = '';
        
        if (this.elementos.alertContainer) {
            this.elementos.alertContainer.innerHTML = '';
        }
    }
};

// ============================================
// 🌐 FUNCIONES GLOBALES
// ============================================
window.actualizarPasswordUsuarioActual = async function() {
    if (ModuloActualizarPassword.inicializado) {
        await ModuloActualizarPassword.actualizarPassword();
    } else {
        console.warn('Módulo Actualizar Password no está inicializado');
    }
};

window.limpiarFormularioPassword = function() {
    if (ModuloActualizarPassword.inicializado) {
        ModuloActualizarPassword.limpiarFormulario();
    }
};

// ============================================
// 🎨 ESTILOS DE ANIMACIÓN
// ============================================
if (!document.getElementById('password-module-styles')) {
    const style = document.createElement('style');
    style.id = 'password-module-styles';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(-100%);
                opacity: 0;
            }
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    `;
    document.head.appendChild(style);
}

// ============================================
// 🔧 AUTO-REGISTRO DEL MÓDULO
// ============================================
if (typeof window.registrarModulo === 'function') {
    window.registrarModulo('actualizarpass', ModuloActualizarPassword);
}

// Auto-inicializar cuando se cargue el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // No auto-inicializar, esperar a que Dashboard lo llame
    });
}