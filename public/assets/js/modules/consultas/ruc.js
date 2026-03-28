const ModuloRUC = {
    elements: {},
    initialized: false,

    init() {
        if (this.initialized) return;
        this.cacheElements();
        this.setupEventListeners();
        this.initialized = true;
    },

    cacheElements() {
        this.elements = {
            form: document.querySelector('#searchFormRUC'),
            rucInput: document.querySelector('#ruc'),
            btnBuscar: document.querySelector('#searchFormRUC button[type="submit"]'),
            alertContainer: document.getElementById('alertContainerRUC')
        };
    },

    setupEventListeners() {
        if (!this.elements.form) return;

        this.elements.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.buscarRUC();
        });

        this.setupRealTimeValidation();
    },

    setupRealTimeValidation() {
        const rucInput = this.elements.rucInput;
        if (!rucInput) return;

        rucInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
        });

        rucInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numeros = pastedText.replace(/[^0-9]/g, '').slice(0, 11);
            this.value = numeros;
        });
    },

    async buscarRUC() {
        const ruc = DOM.val(this.elements.rucInput).trim();

        const validation = this.validateRUCInput(ruc);
        if (!validation.valid) {
            Alerts.inline(validation.message, 'warning', 'alertContainerRUC');
            this.elements.rucInput.focus();
            return;
        }

        try {
            this.setLoading(true);
            this.clearFields();

            const resultado = await consultaService.consultarRUC(validation.ruc);

            if (resultado.success) {
                Alerts.inline('Consulta realizada exitosamente', 'success', 'alertContainerRUC');
                this.fillData(resultado.data);
            } else {
                Alerts.inline(resultado.message || 'No se encontraron datos para el RUC consultado', 'danger', 'alertContainerRUC');
            }
        } catch (error) {
            console.error('Error al consultar RUC:', error);
            Alerts.inline(error.message || 'Error al conectar con el servicio de SUNAT', 'danger', 'alertContainerRUC');
        } finally {
            this.setLoading(false);
        }
    },

    validateRUCInput(ruc) {
        if (!ruc || ruc.trim() === '') {
            return { valid: false, message: 'El RUC es obligatorio' };
        }

        ruc = ruc.trim();

        if (!/^\d{11}$/.test(ruc)) {
            return { valid: false, message: 'El RUC debe tener 11 dígitos numéricos' };
        }

        return { valid: true, ruc };
    },

    fillData(data) {
        if (!data) return;

        const fields = {
            'ruc': data.ruc,
            'razon_social': data.razon_social,
            'codigo_ubigeo': data.codigo_ubigeo,
            'departamento': data.departamento,
            'provincia': data.provincia,
            'distrito': data.distrito,
            'actividad_economica': data.actividad_economica,
            'estado_contribuyente': data.estado_contribuyente,
            'fecha_actualizacion': data.fecha_actualizacion,
            'fecha_alta': data.fecha_alta,
            'fecha_baja': data.fecha_baja,
            'tipo_persona': data.tipo_persona,
            'tipo_contribuyente': data.tipo_contribuyente,
            'tipo_zona': data.tipo_zona,
            'tipo_via': data.tipo_via,
            'nombre_via': data.nombre_via,
            'numero': data.numero,
            'interior': data.interior,
            'nombre_zona': data.nombre_zona,
            'referencia': data.referencia,
            'condicion_domicilio': data.condicion_domicilio,
            'dependencia': data.dependencia,
            'codigo_secuencia': data.codigo_secuencia,
            'estado_activo': data.estado_activo,
            'estado_habido': data.estado_habido,
            'direccion_completa': data.direccion_completa
        };

        Object.entries(fields).forEach(([field, value]) => {
            const el = document.querySelector(`[data-campo="${field}"]`);
            if (el) DOM.text(el, value || '-');
        });

        window.contribuyenteData = data;
    },

    clearFields() {
        document.querySelectorAll('[data-campo]').forEach(el => {
            DOM.text(el, '-');
        });
        window.contribuyenteData = null;
    },

    setLoading(show) {
        const btn = this.elements.btnBuscar;
        if (!btn) return;

        const icon = btn.querySelector('i');
        const span = btn.querySelector('span');

        if (show) {
            btn.disabled = true;
            if (icon) icon.className = 'fas fa-spinner fa-spin';
            if (span) span.textContent = 'Buscando...';
        } else {
            btn.disabled = false;
            if (icon) icon.className = 'fas fa-magnifying-glass';
            if (span) span.textContent = 'Buscar';
        }
    },

    clearForm() {
        if (this.elements.form) this.elements.form.reset();
        this.clearFields();

        const alerta = document.querySelector('#pageConsultaRUC .alert');
        if (alerta) alerta.remove();

        if (this.elements.rucInput) this.elements.rucInput.focus();
    }
};

window.limpiarFormularioRUC = function() {
    if (ModuloRUC.initialized) {
        ModuloRUC.clearForm();
    }
};

if (typeof window.registrarModulo === 'function') {
    window.registrarModulo('consultasruc', ModuloRUC);
}

window.ModuloRUC = ModuloRUC;
