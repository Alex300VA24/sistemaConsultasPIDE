// ============================================
// 🆔 MÓDULO DE CONSULTA DNI
// ============================================

const ModuloDNI = {
    elementos: {},
    inicializado: false,

    // ============================================
    // 🚀 INICIALIZACIÓN
    // ============================================
    init() {
        if (this.inicializado) {
            console.log('ℹ️ Módulo DNI ya está inicializado');
            return;
        }

        console.log('🆔 Inicializando Módulo DNI...');
        
        this.cachearElementos();
        this.setupEventListeners();
        
        this.inicializado = true;
        console.log('✅ Módulo DNI inicializado correctamente');
    },

    // ============================================
    // 📦 CACHEAR ELEMENTOS DEL DOM
    // ============================================
    cachearElementos() {
        this.elementos = {
            form: document.getElementById('searchFormDNI'),
            dniInput: document.getElementById('dniInput'),
            btnBuscar: document.getElementById('btnBuscarDNI'),
            alertContainer: document.getElementById('alertContainerDNI'),
            photoContainer: document.getElementById('photoContainer'),
            resultados: {
                dni: document.getElementById('result-dni'),
                nombres: document.getElementById('result-nombres'),
                paterno: document.getElementById('result-paterno'),
                materno: document.getElementById('result-materno'),
                estadoCivil: document.getElementById('result-estado-civil'),
                direccion: document.getElementById('result-direccion'),
                restriccion: document.getElementById('result-restriccion'),
                ubigeo: document.getElementById('result-ubigeo')
            }
        };
    },

    // ============================================
    // 🎯 CONFIGURAR EVENT LISTENERS
    // ============================================
    setupEventListeners() {
        // Validar solo números en el campo DNI
        this.elementos.dniInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });

        // Manejar envío del formulario
        this.elementos.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSubmit();
        });
    },

    // ============================================
    // 📝 MANEJAR ENVÍO DEL FORMULARIO
    // ============================================
    async handleSubmit() {
        const dni = this.elementos.dniInput.value.trim();
        
        if (dni.length !== 8) {
            mostrarAlerta('El DNI debe tener 8 dígitos', 'warning', 'alertContainerDNI');
            return;
        }

        await this.consultarDNI(dni);
    },

    // ============================================
    // 🔍 CONSULTAR DNI
    // ============================================
    async consultarDNI(dni) {
        try {
            // Deshabilitar botón y mostrar loading
            this.mostrarLoading(true);
            this.limpiarResultados();
            this.elementos.alertContainer.innerHTML = '';

            // Obtener credenciales del usuario actual
            const usuario = localStorage.getItem('usuario');
            console.log('👤 Usuario actual:', usuario);
            
            const credencialesResponse = await api.obtenerDniYPassword(usuario);
            console.log('🔑 Credenciales obtenidas:', credencialesResponse.data);

            if (!credencialesResponse.success || !credencialesResponse.data) {
                mostrarAlerta('No se pudieron obtener las credenciales del usuario', 'danger', 'alertContainerDNI');
                return;
            }

            const dniUsuario = credencialesResponse.data.DNI;
            const password = credencialesResponse.data.password;

            // Armar payload para la API
            const payload = {
                dniConsulta: dni,
                dniUsuario: dniUsuario,
                password: password
            };

            console.log('📤 Enviando consulta:', payload);

            // Realizar consulta a la API
            const response = await api.consultarDNI(payload);
            console.log('📥 Response del DNI:', response);

            // Manejar respuesta
            if (response.success && response.data) {
                this.mostrarResultados(response.data);
                mostrarAlerta('Consulta realizada exitosamente', 'success', 'alertContainerDNI');
            } else {
                mostrarAlerta(response.message || 'No se encontraron datos', 'warning', 'alertContainerDNI');
            }

        } catch (error) {
            console.error('❌ Error al consultar DNI:', error);
            mostrarAlerta('Error al realizar la consulta: ' + error.message, 'danger', 'alertContainerDNI');
        } finally {
            this.mostrarLoading(false);
        }
    },

    // ============================================
    // 📊 MOSTRAR RESULTADOS
    // ============================================
    mostrarResultados(data) {
        // Mapear los datos según la estructura de la API
        this.elementos.resultados.dni.textContent = data.dni || '';
        this.elementos.resultados.nombres.textContent = data.nombres || data.prenombres || '';
        this.elementos.resultados.paterno.textContent = data.apellido_paterno || data.apPrimer || '';
        this.elementos.resultados.materno.textContent = data.apellido_materno || data.apSegundo || '';
        this.elementos.resultados.estadoCivil.textContent = data.estado_civil || data.estadoCivil || '';
        this.elementos.resultados.direccion.textContent = data.direccion || '';
        this.elementos.resultados.restriccion.textContent = data.restriccion || '';
        this.elementos.resultados.ubigeo.textContent = data.ubigeo || '';

        // Manejar foto
        this.mostrarFoto(data.foto);
    },

    // ============================================
    // 🖼️ MOSTRAR FOTO
    // ============================================
    mostrarFoto(foto) {
        const photoContainer = this.elementos.photoContainer;
        photoContainer.innerHTML = '';

        if (foto) {
            const fotoBase64 = foto.startsWith('data:image')
                ? foto
                : `data:image/jpeg;base64,${foto}`;

            const img = document.createElement('img');
            img.src = fotoBase64;
            img.alt = 'Foto del DNI';

            // Restablecer tamaño del contenedor
            photoContainer.style.width = '350px';
            photoContainer.style.height = '450px';
            photoContainer.appendChild(img);
        } else {
            // Mostrar placeholder si no hay foto
            photoContainer.innerHTML = '<div class="photo-placeholder"></div>';
            photoContainer.style.width = '200px';
            photoContainer.style.height = '200px';
        }
    },

    // ============================================
    // 🧹 LIMPIAR RESULTADOS
    // ============================================
    limpiarResultados() {
        // Limpiar campos de texto
        Object.values(this.elementos.resultados).forEach(elemento => {
            elemento.textContent = '';
        });

        // Limpiar foto
        const photoContainer = this.elementos.photoContainer;
        photoContainer.innerHTML = '<div class="photo-placeholder"></div>';
        photoContainer.style.width = '200px';
        photoContainer.style.height = '200px';
    },

    // ============================================
    // ⏳ MOSTRAR/OCULTAR LOADING
    // ============================================
    mostrarLoading(mostrar) {
        const btnBuscar = this.elementos.btnBuscar;
        
        if (mostrar) {
            btnBuscar.disabled = true;
            btnBuscar.innerHTML = '<span class="loading"></span>';
        } else {
            btnBuscar.disabled = false;
            btnBuscar.innerHTML = '🔍';
        }
    },

    // ============================================
    // 🧹 LIMPIAR FORMULARIO COMPLETO
    // ============================================
    limpiarFormulario() {
        this.elementos.form.reset();
        this.limpiarResultados();
        this.elementos.alertContainer.innerHTML = '';
        console.log('🧹 Formulario DNI limpiado');
    }
};

// ============================================
// 🌐 FUNCIONES GLOBALES PARA HTML
// ============================================
window.limpiarFormularioDNI = function() {
    if (ModuloDNI.inicializado) {
        ModuloDNI.limpiarFormulario();
    }
};

window.volverInicio = function() {
    if (typeof showPage === 'function') {
        showPage('inicio');
    }
};
// ============================================
// 🔧 AUTO-REGISTRO DEL MÓDULO
// ============================================
if (typeof window.registrarModulo === 'function') {
    window.registrarModulo('consultasdni', ModuloDNI);
    console.log('✅ consultasdni registrado en Dashboard');
}

// Auto-inicializar cuando se cargue el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // No auto-inicializar, esperar a que Dashboard lo llame
    });
}