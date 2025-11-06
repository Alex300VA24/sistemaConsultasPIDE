// ============================================
// 🔧 FUNCIONES DE VALIDACIÓN
// ============================================

function validarRUC(ruc) {
    if (!ruc || ruc.trim() === '') {
        return { valido: false, mensaje: 'El RUC es obligatorio' };
    }

    ruc = ruc.trim();

    if (!/^\d{11}$/.test(ruc)) {
        return { valido: false, mensaje: 'El RUC debe tener 11 dígitos numéricos' };
    }

    return { valido: true, ruc };
}

// ============================================
// 🎨 FUNCIONES DE UI
// ============================================

/**
 * Mostrar loading en el botón de búsqueda
 */
function mostrarLoading(mostrar = true) {
    const btnBuscar = document.querySelector('#searchFormRUC button[type="submit"]');
    const icon = btnBuscar.querySelector('i');
    const span = btnBuscar.querySelector('span');

    if (mostrar) {
        btnBuscar.disabled = true;
        icon.className = 'fas fa-spinner fa-spin';
        span.textContent = 'Buscando...';
    } else {
        btnBuscar.disabled = false;
        icon.className = 'fas fa-magnifying-glass';
        span.textContent = 'Buscar';
    }
}

/**
 * Mostrar alerta en la interfaz
 */
function mostrarAlerta(mensaje, tipo = 'info') {
    const alertContainer = document.querySelector('.content-wrapper');
    const searchSection = document.querySelector('.search-section');
    
    // Remover alertas anteriores
    const alertaAnterior = document.querySelector('.alert');
    if (alertaAnterior) {
        alertaAnterior.remove();
    }

    const iconos = {
        success: 'check-circle',
        danger: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };

    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo}`;
    alerta.innerHTML = `
        <i class="fas fa-${iconos[tipo]}"></i>
        <span>${mensaje}</span>
    `;

    searchSection.insertAdjacentElement('afterend', alerta);

    // Auto-ocultar después de 5 segundos si es success o info
    if (tipo === 'success' || tipo === 'info') {
        setTimeout(() => {
            alerta.style.transition = 'opacity 0.3s ease';
            alerta.style.opacity = '0';
            setTimeout(() => alerta.remove(), 300);
        }, 5000);
    }
}

// ============================================
// 📝 FUNCIONES DE LLENADO DE DATOS
// ============================================

/**
 * Llenar los campos del formulario con los datos del contribuyente
 */
function llenarDatosRUC(datos) {
    // Limpiar todos los campos primero
    limpiarCamposRUC();

    if (!datos) return;

    // Mapeo de campos: clave = texto del label, valor = campo de datos
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

    // Llenar todos los campos usando el mapeo
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

    console.log('✅ Datos cargados correctamente:', datos);
}

/**
 * Limpiar todos los campos del formulario
 */
function limpiarCamposRUC() {
    const campos = document.querySelectorAll('.info-value');
    campos.forEach(campo => {
        campo.textContent = '-';
    });
}

// ============================================
// 🌐 FUNCIONES DE API
// ============================================

/**
 * Manejar el envío del formulario de búsqueda
 */
async function buscarRUC(event) {
    event.preventDefault();

    const rucInput = document.querySelector('#ruc');
    const ruc = rucInput.value.trim();

    // Validar RUC
    const validacion = validarRUC(ruc);
    if (!validacion.valido) {
        mostrarAlerta(validacion.mensaje, 'warning');
        rucInput.focus();
        return;
    }

    try {
        mostrarLoading(true);
        limpiarCamposRUC();

        // Realizar consulta a la API
        const resultado = await api.consultarRUC(validacion.ruc);

        if (resultado.success) {
            mostrarAlerta(resultado.message || 'Consulta realizada exitosamente', 'success');
            llenarDatosRUC(resultado.data);
        } else {
            mostrarAlerta(resultado.message || 'No se encontraron datos para el RUC consultado', 'danger');
        }

    } catch (error) {
        console.error('Error al consultar RUC:', error);
        mostrarAlerta(
            error.message || 'Error al conectar con el servicio de SUNAT. Por favor, intente nuevamente.',
            'danger'
        );
    } finally {
        mostrarLoading(false);
    }
}

// ============================================
// 🧹 FUNCIONES DE LIMPIEZA
// ============================================

/**
 * Limpiar el formulario completamente
 */
function limpiarFormularioRUC() {
    const form = document.querySelector('#searchFormRUC');
    if (form) {
        form.reset();
    }
    
    limpiarCamposRUC();

    // Remover alertas
    const alerta = document.querySelector('.alert');
    if (alerta) {
        alerta.remove();
    }

    // Enfocar el input
    const rucInput = document.querySelector('#ruc');
    if (rucInput) {
        rucInput.focus();
    }
}

// ============================================
// ✅ VALIDACIÓN EN TIEMPO REAL
// ============================================

/**
 * Validación en tiempo real del RUC
 */
function validarRUCTiempoReal() {
    const rucInput = document.querySelector('#ruc');
    
    if (!rucInput) return;

    rucInput.addEventListener('input', function(e) {
        // Solo permitir números
        this.value = this.value.replace(/[^0-9]/g, '');

        // Limitar a 11 dígitos
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
}

// ============================================
// 🚀 INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando sistema de consulta RUC...');
    
    const form = document.querySelector('#searchFormRUC');
    
    if (form) {
        form.addEventListener('submit', buscarRUC);
        validarRUCTiempoReal();
        
        console.log('✅ Sistema de Consulta RUC inicializado correctamente');
    } else {
        console.error('❌ No se encontró el formulario #searchFormRUC');
    }
});

// Exponer función global para limpiar
window.limpiarFormularioRUC = limpiarFormularioRUC;