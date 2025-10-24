(function() {
    const form = document.getElementById('searchFormDNI');
    const dniInput = document.getElementById('dniInput');
    const btnBuscar = document.getElementById('btnBuscarDNI');
    const alertContainer = document.getElementById('alertContainer');

    // Validar solo números en el campo DNI
    dniInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Manejar el envío del formulario
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const dni = dniInput.value.trim();
        
        if (dni.length !== 8) {
            mostrarAlerta('El DNI debe tener 8 dígitos', 'warning');
            return;
        }

        await consultarDNI(dni);
    });

    async function consultarDNI(dni) {
        try {
            // Deshabilitar botón y mostrar loading
            btnBuscar.disabled = true;
            btnBuscar.innerHTML = '<span class="loading"></span>';
            limpiarResultados();
            alertContainer.innerHTML = '';

            // Llamar a tu API
            const response = await api.post('/consultar-dni', { dni: dni });

            if (response.success && response.data) {
                mostrarResultados(response.data);
                mostrarAlerta('Consulta realizada exitosamente', 'success');
            } else {
                mostrarAlerta(response.message || 'No se encontraron datos', 'warning');
            }

        } catch (error) {
            console.error('Error al consultar DNI:', error);
            mostrarAlerta('Error al realizar la consulta: ' + error.message, 'danger');
        } finally {
            // Rehabilitar botón
            btnBuscar.disabled = false;
            btnBuscar.innerHTML = '🔍';
        }
    }

    function mostrarResultados(data) {
        // Mapear los datos según la estructura de tu API
        document.getElementById('result-dni').textContent = data.dni || '';
        document.getElementById('result-nombres').textContent = data.nombres || data.prenombres || '';
        document.getElementById('result-paterno').textContent = data.apellido_paterno || data.apPrimer || '';
        document.getElementById('result-materno').textContent = data.apellido_materno || data.apSegundo || '';
        document.getElementById('result-estado-civil').textContent = data.estado_civil || data.estadoCivil || '';
        document.getElementById('result-direccion').textContent = data.direccion || '';
        document.getElementById('result-restriccion').textContent = data.restriccion || '';
        document.getElementById('result-ubigeo').textContent = data.ubigeo || '';

        // Mostrar foto si existe
        const photoContainer = document.getElementById('photoContainer');
        if (data.foto) {
            photoContainer.innerHTML = `<img src="${data.foto}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">`;
        }
    }

    function limpiarResultados() {
        document.getElementById('result-dni').textContent = '';
        document.getElementById('result-nombres').textContent = '';
        document.getElementById('result-paterno').textContent = '';
        document.getElementById('result-materno').textContent = '';
        document.getElementById('result-estado-civil').textContent = '';
        document.getElementById('result-direccion').textContent = '';
        document.getElementById('result-restriccion').textContent = '';
        document.getElementById('result-ubigeo').textContent = '';
        
        const photoContainer = document.getElementById('photoContainer');
        photoContainer.className = 'photo-placeholder';
        photoContainer.innerHTML = '';
    }

    function mostrarAlerta(mensaje, tipo) {
        alertContainer.innerHTML = `
            <div class="alert alert-${tipo}">
                ${mensaje}
            </div>
        `;

        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            alertContainer.innerHTML = '';
        }, 5000);
    }

    // Función global para limpiar
    window.limpiarFormularioDNI = function() {
        form.reset();
        limpiarResultados();
        alertContainer.innerHTML = '';
    };

    // Función global para volver al inicio
    window.volverInicio = function() {
        if (typeof showPage === 'function') {
            showPage('pageInicio');
        }
    };
})();