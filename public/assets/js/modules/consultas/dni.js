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

        DOM.$('#btnExportPDF')?.addEventListener('click', () => this.exportPDF());
        DOM.$('#btnPrint')?.addEventListener('click', () => this.printResult());
    },

    async exportPDF() {
        const dniEl = DOM.$('#result-dni');
        const dni = dniEl?.textContent?.trim();
        if (!dni || dni === '-') {
            Alerts.inline('Realice una consulta primero', 'warning', 'alertContainerDNI');
            return;
        }

        const btn = DOM.$('#btnExportPDF');
        const loader = Loading.button(btn, { text: '<span class="loading"></span> Generando...' });

        try {
            const photo = DOM.$('#photoContainer img');
            const fotoSrc = photo ? photo.src : '';

            const payload = {
                dni: dni,
                nombres: DOM.text(DOM.$('#result-nombres')),
                apellido_paterno: DOM.text(DOM.$('#result-paterno')),
                apellido_materno: DOM.text(DOM.$('#result-materno')),
                estado_civil: DOM.text(DOM.$('#result-estado-civil')),
                direccion: DOM.text(DOM.$('#result-direccion')),
                restriccion: DOM.text(DOM.$('#result-restriccion')),
                ubigeo: DOM.text(DOM.$('#result-ubigeo')),
                foto: fotoSrc
            };

            const response = await fetch('/api/consultas/dni/pdf', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error('Error del servidor: ' + response.status);
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `consulta-dni-${dni}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
        } catch (err) {
            console.error('PDF error:', err);
            Alerts.inline('Error al generar el PDF', 'danger', 'alertContainerDNI');
        } finally {
            loader.restore();
        }
    },

    printResult() {
        const dniEl = DOM.$('#result-dni');
        const dni = dniEl?.textContent?.trim();
        if (!dni || dni === '-') {
            Alerts.inline('Realice una consulta primero', 'warning', 'alertContainerDNI');
            return;
        }

        const content = DOM.$('#dniResultsContent');
        const photo = DOM.$('#photoContainer');
        if (!content) return;

        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            Alerts.inline('Permita ventanas emergentes para imprimir', 'warning', 'alertContainerDNI');
            return;
        }

        let html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Consulta DNI</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 30px; color: #1f2937; }
            .header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 3px solid #2563eb; }
            .header h1 { margin: 0; font-size: 22px; }
            .header p { margin: 5px 0 0; font-size: 13px; color: #6b7280; }
            .photo-row { text-align: center; margin-bottom: 20px; }
            .photo-row img { max-width: 200px; border-radius: 8px; }
            .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
            .info-item { padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb; }
            .info-item.full { grid-column: 1 / -1; }
            .info-item label { font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; display: block; margin-bottom: 4px; }
            .info-item span { font-size: 16px; font-weight: 600; color: #1f2937; }
            .footer { text-align: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; }
            .no-photo { padding: 40px; text-align: center; color: #9ca3af; }
            @media print { body { padding: 0; } }
        </style></head><body>`;

        html += `<div class="header"><h1>Consulta DNI - RENIEC</h1><p>Fecha: ${new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</p></div>`;

        if (photo) {
            const img = photo.querySelector('img');
            if (img) {
                html += `<div class="photo-row"><img src="${img.src}" alt="Foto"></div>`;
            } else {
                html += `<div class="photo-row no-photo"><p>Sin fotografía</p></div>`;
            }
        }

        html += `<div class="info-grid">`;
        content.querySelectorAll('[id^="result-"]').forEach(el => {
            const label = el.closest('.bg-white\\/60')?.querySelector('.text-xs')?.textContent || el.id.replace('result-', '').replace(/-/g, ' ').toUpperCase();
            const value = el.textContent || '-';
            const fullSpan = el.closest('.md\\:col-span-2') ? ' full' : '';
            html += `<div class="info-item${fullSpan}"><label>${label}</label><span>${value}</span></div>`;
        });
        html += `</div>`;

        html += `<div class="footer">Sistema de Consultas PIDE - Documento generado electrónicamente</div>`;
        html += `</body></html>`;

        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => printWindow.print(), 500);
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
