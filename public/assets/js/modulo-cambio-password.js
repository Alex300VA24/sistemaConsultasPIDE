// ============================================
// MÓDULO DE CAMBIO DE PASSWORD OBLIGATORIO
// ============================================

const ModuloCambioPasswordObligatorio = {
    diasRestantes: 0,
    usuarioId: null,

    init(diasRestantes = 0) {
        this.diasRestantes = diasRestantes;
        this.usuarioId = sessionStorage.getItem('usuario_id') || null;
        this.setupEventListeners();
    },

    setupEventListeners() {
        const form = document.getElementById('formCambioPassword');
        if (form) {
            form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
    },

    mostrarModal() {
        const modal = document.getElementById('modalPasswordObligatorio');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    ocultarModal() {
        const modal = document.getElementById('modalPasswordObligatorio');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    async handleSubmit(e) {
        e.preventDefault();

        const passwordActual = document.getElementById('passwordActual').value;
        const passwordNueva = document.getElementById('passwordNueva').value;
        const passwordConfirmar = document.getElementById('passwordConfirmar').value;

        // Validaciones
        if (passwordNueva !== passwordConfirmar) {
            this.mostrarError('Las contraseñas no coinciden');
            return;
        }

        if (!this.validarPasswordSegura(passwordNueva)) {
            this.mostrarError('La contraseña no cumple con los requisitos de seguridad');
            return;
        }

        const btnSubmit = document.getElementById('btnCambiarPass');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="loading-spinner"></span> Cambiando...';

        try {
            const response = await fetch('/sistemaConsultasPIDE/public/api/usuario/cambiar-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    passwordActual: passwordActual,
                    passwordNueva: passwordNueva
                })
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarExito('Contraseña cambiada correctamente');
                setTimeout(() => {
                    this.ocultarModal();
                    location.reload();
                }, 1500);
            } else {
                this.mostrarError(data.message || 'Error al cambiar la contraseña');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarError('Error de conexión. Intenta nuevamente.');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = 'Cambiar Contraseña';
        }
    },

    validarPasswordSegura(password) {
        const requisitos = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[@$!%*?&#]/.test(password)
        };

        return Object.values(requisitos).every(req => req === true);
    },

    mostrarError(mensaje) {
        const errorDiv = document.getElementById('errorMessage');
        const successDiv = document.getElementById('successMessage');
        
        successDiv.classList.remove('active');
        errorDiv.textContent = mensaje;
        errorDiv.classList.add('active');

        setTimeout(() => {
            errorDiv.classList.remove('active');
        }, 5000);
    },

    mostrarExito(mensaje) {
        const errorDiv = document.getElementById('errorMessage');
        const successDiv = document.getElementById('successMessage');
        
        errorDiv.classList.remove('active');
        successDiv.textContent = mensaje;
        successDiv.classList.add('active');
    }
};

// ============================================
// FUNCIONES AUXILIARES
// ============================================

function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function verificarFortalezaPassword(password) {
    const strengthDiv = document.getElementById('passwordStrength');
    const strengthBar = strengthDiv.querySelector('.strength-bar-fill');
    const strengthText = strengthDiv.querySelector('.strength-text');

    if (password.length === 0) {
        strengthDiv.classList.remove('active');
        return;
    }

    strengthDiv.classList.add('active');

    const requisitos = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[@$!%*?&#]/.test(password)
    };

    // Actualizar requisitos visuales
    document.getElementById('req-length').classList.toggle('valid', requisitos.length);
    document.getElementById('req-uppercase').classList.toggle('valid', requisitos.uppercase);
    document.getElementById('req-lowercase').classList.toggle('valid', requisitos.lowercase);
    document.getElementById('req-number').classList.toggle('valid', requisitos.number);
    document.getElementById('req-special').classList.toggle('valid', requisitos.special);

    const cumplidos = Object.values(requisitos).filter(r => r).length;

    strengthDiv.className = 'password-strength active';
    
    if (cumplidos <= 2) {
        strengthDiv.classList.add('strength-weak');
        strengthText.textContent = 'Débil';
    } else if (cumplidos <= 4) {
        strengthDiv.classList.add('strength-medium');
        strengthText.textContent = 'Media';
    } else {
        strengthDiv.classList.add('strength-strong');
        strengthText.textContent = 'Fuerte';
    }
}

function recordarMasTarde() {
    // Guardar que el usuario pospuso el cambio
    localStorage.setItem('cambio_password_pospuesto', Date.now());
    ModuloCambioPasswordObligatorio.ocultarModal();
}

// Exponer globalmente
window.ModuloCambioPasswordObligatorio = ModuloCambioPasswordObligatorio;