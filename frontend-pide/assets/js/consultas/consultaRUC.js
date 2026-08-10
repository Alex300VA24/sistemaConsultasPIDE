// ============================================
// 🏢 MÓDULO DE CONSULTA RUC
// ============================================

const ModuloRUC = {
    elementos: {},
    inicializado: false,

    // ============================================
    // 🚀 INICIALIZACIÓN
    // ============================================
    init() {
        if (this.inicializado) {
            return;
        }
        
        this.cachearElementos();
        this.setupEventListeners();
        
        this.inicializado = true;
    },

    // ============================================
    // 📦 CACHEAR ELEMENTOS DEL DOM
    // ============================================
    cachearElementos() {
        this.elementos = {
            form: document.querySelector('#searchFormRUC'),
            rucInput: document.querySelector('#ruc'),
            btnBuscar: document.querySelector('#searchFormRUC button[type="submit"]'),
            alertContainer: document.getElementById('alertContainerRUC')
        };

        if (!this.elementos.form) {
            console.error('❌ No se encontró el formulario #searchFormRUC');
        }
    },

    // ============================================
    // 🎯 CONFIGURAR EVENT LISTENERS
    // ============================================
    setupEventListeners() {
        if (!this.elementos.form) return;

        // Submit del formulario
        this.elementos.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.buscarRUC();
        });

        // Validación en tiempo real
        this.validarRUCTiempoReal();
    },

    // ============================================
    // 🔍 BUSCAR RUC
    // ============================================
    async buscarRUC() {
        const ruc = this.elementos.rucInput.value.trim();

        // Validar RUC
        const validacion = this.validarRUC(ruc);
        if (!validacion.valido) {
            mostrarAlerta(validacion.mensaje, 'warning', 'alertContainerRUC');
            this.elementos.rucInput.focus();
            return;
        }

        try {
            this.mostrarLoading(true);
            this.limpiarCamposRUC();

            // Realizar consulta a la API
            const resultado = await api.consultarRUC(validacion.ruc);

            if (resultado.success) {
                mostrarAlerta('Consulta realizada exitosamente', 'success', 'alertContainerRUC');
                this.llenarDatosRUC(resultado.data);
            } else {
                mostrarAlerta(
                    resultado.message || 'No se encontraron datos para el RUC consultado',
                    'danger',
                    'alertContainerRUC'
                );
            }

        } catch (error) {
            console.error('❌ Error al consultar RUC:', error);
            mostrarAlerta(
                error.message || 'Error al conectar con el servicio de SUNAT. Por favor, intente nuevamente.',
                'danger',
                'alertContainerRUC'
            );
        } finally {
            this.mostrarLoading(false);
        }
    },

    // ============================================
    // ✅ VALIDAR RUC
    // ============================================
    validarRUC(ruc) {
        if (!ruc || ruc.trim() === '') {
            return { valido: false, mensaje: 'El RUC es obligatorio' };
        }

        ruc = ruc.trim();

        if (!/^\d{11}$/.test(ruc)) {
            return { valido: false, mensaje: 'El RUC debe tener 11 dígitos numéricos' };
        }

        return { valido: true, ruc };
    },

    // ============================================
    // ✅ VALIDACIÓN EN TIEMPO REAL
    // ============================================
    validarRUCTiempoReal() {
        const rucInput = this.elementos.rucInput;
        if (!rucInput) return;

        // Solo permitir números
        rucInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
        });

        // Manejar paste
        rucInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numeros = pastedText.replace(/[^0-9]/g, '').slice(0, 11);
            this.value = numeros;
        });
    },

    // ============================================
    // 📊 LLENAR DATOS RUC
    // ============================================
    llenarDatosRUC(datos) {
        if (!datos) return;

        const camposAMostrar = {
            'ruc': datos.ruc,
            'razon_social': datos.razon_social,
            'codigo_ubigeo': datos.codigo_ubigeo,
            'departamento': datos.departamento,
            'provincia': datos.provincia,
            'distrito': datos.distrito,
            'actividad_economica': datos.actividad_economica,
            'estado_contribuyente': datos.estado_contribuyente,
            'fecha_actualizacion': datos.fecha_actualizacion,
            'fecha_alta': datos.fecha_alta,
            'fecha_baja': datos.fecha_baja,
            'tipo_persona': datos.tipo_persona,
            'tipo_contribuyente': datos.tipo_contribuyente,
            'tipo_zona': datos.tipo_zona,
            'tipo_via': datos.tipo_via,
            'nombre_via': datos.nombre_via,
            'numero': datos.numero,
            'interior': datos.interior,
            'nombre_zona': datos.nombre_zona,
            'referencia': datos.referencia,
            'condicion_domicilio': datos.condicion_domicilio,
            'dependencia': datos.dependencia,
            'codigo_secuencia': datos.codigo_secuencia,
            'estado_activo': datos.estado_activo,
            'estado_habido': datos.estado_habido,
            'direccion_completa': datos.direccion_completa
        };

        Object.entries(camposAMostrar).forEach(([campo, valor]) => {
            const elemento = document.querySelector(`[data-campo="${campo}"]`);
            if (elemento) {
                elemento.textContent = valor || '-';
            }
        });

        window.contribuyenteData = datos;
    },

    // ============================================
    // LIMPIAR CAMPOS RUC
    // ============================================
    limpiarCamposRUC() {
        const campos = document.querySelectorAll('[data-campo]');
        campos.forEach(campo => {
            campo.textContent = '-';
        });
        window.contribuyenteData = null;
    },

    // ============================================
    // MOSTRAR/OCULTAR LOADING
    // ============================================
    mostrarLoading(mostrar) {
        const btnBuscar = this.elementos.btnBuscar;
        if (!btnBuscar) return;

        const icon = btnBuscar.querySelector('i');
        const span = btnBuscar.querySelector('span');

        if (mostrar) {
            btnBuscar.disabled = true;
            if (icon) icon.className = 'fas fa-spinner fa-spin';
            if (span) span.textContent = 'Buscando...';
        } else {
            btnBuscar.disabled = false;
            if (icon) icon.className = 'fas fa-magnifying-glass';
            if (span) span.textContent = 'Buscar';
        }
    },

    // ============================================
    // 🧹 LIMPIAR FORMULARIO COMPLETO
    // ============================================
    limpiarFormulario() {
        if (this.elementos.form) {
            this.elementos.form.reset();
        }
        
        this.limpiarCamposRUC();

        // Remover alertas
        const alerta = document.querySelector('#pageConsultaRUC .alert');
        if (alerta) {
            alerta.remove();
        }

        // Enfocar el input
        if (this.elementos.rucInput) {
            this.elementos.rucInput.focus();
        }
    }
};

// ============================================
// 🌐 FUNCIONES GLOBALES PARA HTML
// ============================================
window.limpiarFormularioRUC = function() {
    if (ModuloRUC.inicializado) {
        ModuloRUC.limpiarFormulario();
    }
};

if (typeof window.registrarModulo === 'function') {
    window.registrarModulo('consultasruc', ModuloRUC);
}

// Auto-inicializar cuando se cargue el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // No auto-inicializar, esperar a que Dashboard lo llame
    });
}