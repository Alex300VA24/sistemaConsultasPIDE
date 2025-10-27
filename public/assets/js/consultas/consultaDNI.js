document.addEventListener("DOMContentLoaded", async () => {
    const form = document.getElementById('searchFormDNI');
    const dniInput = document.getElementById('dniInput');
    const btnBuscar = document.getElementById('btnBuscarDNI');
    const alertContainer = document.getElementById('alertContainer');

    console.log("El valor del dni es: ", dniInput.value);

    // Validar solo números en el campo DNI
    dniInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    async function consultarDNI(dni) {
        try {
            // Deshabilitar botón y mostrar loading
            btnBuscar.disabled = true;
            btnBuscar.innerHTML = '<span class="loading"></span>';
            limpiarResultados();
            alertContainer.innerHTML = '';

            // Llamar a tu API
            const response = await api.consultarDNI(dni);

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

        const photoContainer = document.getElementById('photoContainer');

        // Limpiar contenido previo
        photoContainer.innerHTML = '';

        if (data.foto) {
            const fotoBase64 = data.foto.startsWith('data:image')
                ? data.foto
                : `data:image/jpeg;base64,${data.foto}`;

            const img = document.createElement('img');
            img.src = fotoBase64;
            img.alt = 'Foto del DNI';

            // ✅ Restablecer tamaño del contenedor
            photoContainer.style.width = '350px';
            photoContainer.style.height = '450px';

            photoContainer.innerHTML = ''; // limpiar
            photoContainer.appendChild(img);
        } else {
            // Mostrar placeholder si no hay foto
            photoContainer.innerHTML = `
                <div class="photo-placeholder"></div>
            `;
            // ✅ Solo se aplica cuando no hay foto
            photoContainer.style.width = '200px';
            photoContainer.style.height = '200px';
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
        photoContainer.innerHTML = `
            <div class="photo-placeholder"></div>
        `;
        photoContainer.style.width = '200px';
        photoContainer.style.height = '200px';
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
});


