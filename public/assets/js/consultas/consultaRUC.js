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
            console.log('ℹ️ Módulo RUC ya está inicializado');
            return;
        }

        console.log('🏢 Inicializando Módulo RUC...');
        
        this.cachearElementos();
        this.setupEventListeners();
        
        this.inicializado = true;
        console.log('✅ Módulo RUC inicializado correctamente');
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
            console.log('📊 Resultado RUC:', resultado);

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
        this.limpiarCamposRUC();

        if (!datos) return;

        // Mapeo de campos
        const mapaCampos = {
            'Código de Ubigeo': datos.codigo_ubigeo,
            'Departamento': datos.departamento,
            'Provincia': datos.provincia,
            'Distrito': datos.distrito,
            'Actividad Económica': datos.actividad_economica,
            'Estado del Contribuyente': datos.estado_contribuyente,
            'Fecha de Actualización': datos.fecha_actualizacion,
            'Fecha de Alta': datos.fecha_alta,
            'Fecha de Baja': datos.fecha_baja || '-',
            'Tipo de Persona': datos.tipo_persona,
            'Tipo de Contribuyente': datos.tipo_contribuyente,
            'RUC': datos.ruc,
            'Nombre y/o Razón Social': datos.razon_social,
            'Tipo de Zona': datos.tipo_zona || '-',
            'Tipo de Vía': datos.tipo_via,
            'Nombre de Vía': datos.nombre_via,
            'Número': datos.numero,
            'Interior': datos.interior,
            'Nombre de la Zona': datos.nombre_zona || '-',
            'Referencia': datos.referencia || '-',
            'Condición del Domicilio': datos.condicion_domicilio,
            'Dependencia': datos.dependencia,
            'Código Secuencia': datos.codigo_secuencia || '',
            'Estado Activo': datos.estado_activo,
            'Estado Habido': datos.estado_habido,
            'Dirección Completa': datos.direccion_completa
        };

        // Llenar todos los campos
        Object.entries(mapaCampos).forEach(([labelText, valor]) => {
            const infoItems = document.querySelectorAll('.info-item');
            
            infoItems.forEach(item => {
                const label = item.querySelector('.info-label');
                if (label && label.textContent.trim() === labelText) {
                    const valueElement = item.querySelector('.info-value');
                    if (valueElement) {
                        valueElement.textContent = valor || '-';
                    }
                }
            });
        });

        console.log('✅ Datos RUC cargados correctamente');
    },

    // ============================================
    // 🧹 LIMPIAR CAMPOS RUC
    // ============================================
    limpiarCamposRUC() {
        const campos = document.querySelectorAll('#pageConsultaRUC .info-value');
        campos.forEach(campo => {
            campo.textContent = '-';
        });
    },

    // ============================================
    // ⏳ MOSTRAR/OCULTAR LOADING
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

        console.log('🧹 Formulario RUC limpiado');
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