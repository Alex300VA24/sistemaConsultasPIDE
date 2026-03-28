const ModuloDNI = {
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
            form: document.getElementById('searchFormDNI'),
            dniInput: document.getElementById('dniInput'),
            btnBuscar: document.getElementById('btnBuscarDNI'),
            alertContainer: document.getElementById('alertContainerDNI'),
            photoContainer: document.getElementById('photoContainer'),
            results: {
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

    setupEventListeners() {
        if (this.elements.dniInput) {
            this.elements.dniInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
        }

        if (this.elements.form) {
            this.elements.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });
        }
    },

    async handleSubmit() {
        const dni = DOM.val(this.elements.dniInput).trim();
        
        if (!Validator.validateDNI(dni, 'alertContainerDNI')) {
            return;
        }

        await this.consultarDNI(dni);
    },

    async consultarDNI(dni) {
        try {
            this.setLoading(true);
            this.clearResults();
            DOM.empty(this.elements.alertContainer);

            const usuario = localStorage.getItem('usuario');
            const credencialesResponse = await usuarioService.obtenerDniYPassword(usuario);

            if (!credencialesResponse.success || !credencialesResponse.data) {
                Alerts.inline('No se pudieron obtener las credenciales del usuario', 'danger', 'alertContainerDNI');
                return;
            }

            const dniUsuario = credencialesResponse.data.DNI;
            const password = credencialesResponse.data.password;

            const payload = {
                dniConsulta: dni,
                dniUsuario: dniUsuario,
                password: password
            };

            const response = await consultaService.consultarDNI(payload);

            if (response.success && response.data) {
                this.displayResults(response.data);
                Alerts.inline('Consulta realizada exitosamente', 'success', 'alertContainerDNI');
            } else {
                Alerts.inline(response.message || 'No se encontraron datos', 'warning', 'alertContainerDNI');
            }
        } catch (error) {
            console.error('Error al consultar DNI:', error);
            Alerts.inline('Error al realizar la consulta: ' + error.message, 'danger', 'alertContainerDNI');
        } finally {
            this.setLoading(false);
        }
    },

    displayResults(data) {
        DOM.text(this.elements.results.dni, data.dni || '');
        DOM.text(this.elements.results.nombres, data.nombres || data.prenombres || '');
        DOM.text(this.elements.results.paterno, data.apellido_paterno || data.apPrimer || '');
        DOM.text(this.elements.results.materno, data.apellido_materno || data.apSegundo || '');
        DOM.text(this.elements.results.estadoCivil, data.estado_civil || data.estadoCivil || '');
        DOM.text(this.elements.results.direccion, data.direccion || '');
        DOM.text(this.elements.results.restriccion, data.restriccion || '');
        DOM.text(this.elements.results.ubigeo, data.ubigeo || '');

        this.showPhoto(data.foto);
    },

    showPhoto(foto) {
        DOM.empty(this.elements.photoContainer);

        if (foto) {
            const fotoBase64 = foto.startsWith('data:image') ? foto : `data:image/jpeg;base64,${foto}`;
            const img = DOM.create('img', {
                src: fotoBase64,
                alt: 'Foto del DNI',
                className: 'w-full h-full object-cover'
            });
            this.elements.photoContainer.appendChild(img);
        } else {
            this.elements.photoContainer.innerHTML = `
                <div class="text-center text-gray-400">
                    <i class="fas fa-user text-6xl mb-3"></i>
                    <p class="text-sm">Sin fotografía</p>
                </div>
            `;
        }
    },

    clearResults() {
        Object.values(this.elements.results).forEach(el => {
            if (el) DOM.text(el, '');
        });

        this.elements.photoContainer.innerHTML = `
            <div class="text-center text-gray-400">
                <i class="fas fa-user text-6xl mb-3"></i>
                <p class="text-sm">Sin fotografía</p>
            </div>
        `;
    },

    setLoading(show) {
        if (!this.elements.btnBuscar) return;

        if (show) {
            this.elements.btnBuscar.disabled = true;
            this.elements.btnBuscar.innerHTML = '<span class="loading"></span>';
        } else {
            this.elements.btnBuscar.disabled = false;
            this.elements.btnBuscar.innerHTML = '🔍';
        }
    },

    clearForm() {
        if (this.elements.form) this.elements.form.reset();
        this.clearResults();
        DOM.empty(this.elements.alertContainer);
    }
};

window.limpiarFormularioDNI = function() {
    if (ModuloDNI.initialized) {
        ModuloDNI.clearForm();
    }
};

if (typeof window.registrarModulo === 'function') {
    window.registrarModulo('consultasdni', ModuloDNI);
}

window.ModuloDNI = ModuloDNI;
