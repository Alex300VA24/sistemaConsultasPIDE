// consultaRUC.js - Sistema PIDE - Consulta RUC SUNAT

class ConsultaRUCAPI {
    constructor() {
        this.baseURL = '/api';
    }

    /**
     * Realiza una petición POST al servidor
     */
    async post(endpoint, data) {
        try {
            const response = await fetch(`${this.baseURL}${endpoint}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'Error en la petición');
            }

            return result;
        } catch (error) {
            console.error('Error en petición:', error);
            throw error;
        }
    }

    /**
     * Consultar RUC en SUNAT
     */
    async consultarRUC(ruc) {
        return this.post('/consultar-ruc', { ruc });
    }
}

// Instancia global de la API
const rucAPI = new ConsultaRUCAPI();

// ============================================
// 🔍 FUNCIONES DE CONSULTA RUC
// ============================================

/**
 * Validar formato de RUC
 */
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

/**
 * Llenar los campos del formulario con los datos del contribuyente
 */
function llenarDatosRUC(datos) {
    // Limpiar todos los campos primero
    limpiarCamposRUC();

    if (!datos) return;

    // Función auxiliar para llenar un campo
    const llenarCampo = (selector, valor) => {
        const elemento = document.querySelector(selector);
        if (elemento) {
            elemento.textContent = valor || '-';
        }
    };

    // Llenar todos los campos
    llenarCampo('.info-item:has(.info-label:contains("Código de Ubigeo")) .info-value', datos.codigo_ubigeo);
    llenarCampo('.info-item:has(.info-label:contains("Departamento")) .info-value', datos.departamento);
    llenarCampo('.info-item:has(.info-label:contains("Provincia")) .info-value', datos.provincia);
    llenarCampo('.info-item:has(.info-label:contains("Distrito")) .info-value', datos.distrito);
    llenarCampo('.info-item:has(.info-label:contains("Actividad Económica")) .info-value', datos.actividad_economica);
    llenarCampo('.info-item:has(.info-label:contains("Estado del Contribuyente")) .info-value', datos.estado_contribuyente);
    llenarCampo('.info-item:has(.info-label:contains("Fecha de Actualización")) .info-value', datos.fecha_actualizacion);
    llenarCampo('.info-item:has(.info-label:contains("Fecha de Alta")) .info-value', datos.fecha_alta);
    llenarCampo('.info-item:has(.info-label:contains("Fecha de Baja")) .info-value', datos.fecha_baja);
    llenarCampo('.info-item:has(.info-label:contains("Tipo de Persona")) .info-value', datos.tipo_persona);
    llenarCampo('.info-item:has(.info-label:contains("Tipo de Contribuyente")) .info-value', datos.tipo_contribuyente);
    llenarCampo('.info-item:has(.info-label:contains("RUC")) .info-value', datos.ruc);
    llenarCampo('.info-item:has(.info-label:contains("Nombre y/o Razón Social")) .info-value', datos.razon_social);
    llenarCampo('.info-item:has(.info-label:contains("Tipo de Zona")) .info-value', datos.tipo_zona);
    llenarCampo('.info-item:has(.info-label:contains("Tipo de Vía")) .info-value', datos.tipo_via);
    llenarCampo('.info-item:has(.info-label:contains("Nombre de Vía")) .info-value', datos.nombre_via);
    llenarCampo('.info-item:has(.info-label:contains("Número")) .info-value', datos.numero);
    llenarCampo('.info-item:has(.info-label:contains("Interior")) .info-value', datos.interior);
    llenarCampo('.info-item:has(.info-label:contains("Nombre de la Zona")) .info-value', datos.nombre_zona);
    llenarCampo('.info-item:has(.info-label:contains("Referencia")) .info-value', datos.referencia);
    llenarCampo('.info-item:has(.info-label:contains("Condición del Domicilio")) .info-value', datos.condicion_domicilio);
    llenarCampo('.info-item:has(.info-label:contains("Dependencia")) .info-value', datos.dependencia);
    llenarCampo('.info-item:has(.info-label:contains("Código Secuencia")) .info-value', datos.codigo_secuencia);
    llenarCampo('.info-item:has(.info-label:contains("Estado Activo")) .info-value', datos.estado_activo);
    llenarCampo('.info-item:has(.info-label:contains("Estado Habido")) .info-value', datos.estado_habido);
}

/**
 * Limpiar todos los campos del formulario
 */
function limpiarCamposRUC() {
    const campos = document.querySelectorAll('.info-value');
    campos.forEach(campo => {
        campo.textContent = '';
    });
}

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

        const resultado = await rucAPI.consultarRUC(validacion.ruc);

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
    const form = document.querySelector('#searchFormRUC');
    
    if (form) {
        form.addEventListener('submit', buscarRUC);
        validarRUCTiempoReal();
        
        console.log('✅ Sistema de Consulta RUC inicializado correctamente');
    }
});

// Exponer funciones globalmente si es necesario
window.buscarRUC = buscarRUC;
window.limpiarFormularioRUC = limpiarFormularioRUC;