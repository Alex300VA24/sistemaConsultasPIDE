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

};

function btnCambiarPass() {
    showPage('sistemaActualizarPass');
}

function recordarMasTarde() {
    try {
        // Obtener ID del usuario actual
        const usuarioData = sessionStorage.getItem('usuario');
        if (usuarioData) {
            const usuario = JSON.parse(usuarioData);
            const usuarioId = usuario.USU_id;
            
            // Guardar con clave específica del usuario
            const keyPospuesto = `cambio_password_pospuesto_${usuarioId}`;
            localStorage.setItem(keyPospuesto, Date.now());
            
            console.log(`Password pospuesto para usuario ${usuarioId} hasta ${new Date(Date.now() + 24*60*60*1000)}`);
        }
    } catch (error) {
        console.error('Error al posponer cambio de password:', error);
    }
    
    ModuloCambioPasswordObligatorio.ocultarModal();
}

// Exponer globalmente
window.ModuloCambioPasswordObligatorio = ModuloCambioPasswordObligatorio;