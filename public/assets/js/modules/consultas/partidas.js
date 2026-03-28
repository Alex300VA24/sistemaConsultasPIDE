const ModuloPartidas = {
    ALERT_CONTAINER: 'alertContainerPartidas',
    initialized: false,
    state: {
        selectedPerson: null,
        currentPersonType: 'natural',
        foundRecords: [],
        foundPartidas: [],
        currentPage: 1,
        itemsPerPage: 8,
        currentPartida: null,
        userCredentials: { dni: '', password: '' },
        detailCache: {},
        loadingDetail: false,
        zoomLevel: 1,
        currentImage: null
    },
    handlers: {
        tSIRSARP: null,
        lASIRSARP: null
    },

    async init() {
        if (this.initialized) return;

        await this.loadUserCredentials();
        await this.loadOffices();
        this.bindHandlers();
        this.setupEventListeners();
        this.initialized = true;
    },

    bindHandlers() {
        this.handlers.tSIRSARP = this.consultTSIRSARP.bind(this);
        this.handlers.lASIRSARP = this.consultLASIRSARP.bind(this);
    },

    async loadUserCredentials() {
        try {
            const usuario = await usuarioService.obtenerActual();
            if (usuario.success && usuario.data) {
                this.state.userCredentials.dni = usuario.data.PER_documento_numero || '';

                if (usuario.data.USU_username) {
                    const credenciales = await usuarioService.obtenerDniYPassword(usuario.data.USU_username);
                    if (credenciales?.success) {
                        this.state.userCredentials.password = credenciales.data.password || '';
                    }
                }
            }
        } catch (error) {
            console.error('Error loading credentials:', error);
            Alerts.inline('Error al cargar credenciales de usuario', 'danger', this.ALERT_CONTAINER);
        }
    },

    async loadOffices() {
        try {
            const oficinas = await consultaService.obtenerOficinasRegistrales();

            if (oficinas.success && oficinas.data) {
                const select = DOM.$('#oficinaRegistralID');
                if (!select) return;

                select.innerHTML = '<option value="">Seleccione Oficina Registral</option>';

                const officesArray = Object.values(oficinas.data)
                    .sort((a, b) => a.descripcion.localeCompare(b.descripcion));

                officesArray.forEach(oficina => {
                    const option = DOM.create('option', {
                        textContent: oficina.descripcion,
                        value: `${oficina.codZona}-${oficina.codOficina}`
                    });
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading offices:', error);
            Alerts.inline('Error al cargar oficinas registrales', 'danger', this.ALERT_CONTAINER);
        }
    },

    setupEventListeners() {
        DOM.$$('input[name="tipoPersona"]').forEach(radio => {
            radio.addEventListener('change', (e) => this.changePersonType(e));
        });

        DOM.$('#btnBuscarPersona')?.addEventListener('click', () => this.openSearchModal());
        DOM.$('#btnConsultar')?.addEventListener('click', this.handlers.tSIRSARP);
        DOM.$('#btnLimpiar')?.addEventListener('click', () => this.clearForm());

        DOM.$('#formBusquedaNatural')?.addEventListener('submit', (e) => this.searchNaturalPerson(e));
        DOM.$('#formBusquedaJuridica')?.addEventListener('submit', (e) => this.searchJuridicPerson(e));

        DOM.$$('input[name="tipoBusquedaJuridica"]').forEach(radio => {
            radio.addEventListener('change', (e) => {});
        });

        const modalNatural = DOM.$('#modalBusquedaNatural');
        const modalJuridica = DOM.$('#modalBusquedaJuridica');

        modalNatural?.addEventListener('click', (e) => {
            if (e.target === modalNatural) this.closeModal('modalBusquedaNatural');
        });

        modalJuridica?.addEventListener('click', (e) => {
            if (e.target === modalJuridica) this.closeModal('modalBusquedaJuridica');
        });

        DOM.$$('[data-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.closeModal(btn.dataset.modal);
            });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal('modalBusquedaNatural');
                this.closeModal('modalBusquedaJuridica');
            }
        });

        DOM.$('#dniNatural')?.addEventListener('input', function() {
            this.value = Validator.sanitizeNumber(this.value);
        });

        DOM.$('#rucJuridica')?.addEventListener('input', function() {
            this.value = Validator.sanitizeNumber(this.value);
        });
    },

    changePersonType(e) {
        this.state.currentPersonType = e.target.value;
        this.clearForm();

        const ui = {
            labelPersona: DOM.$('#labelPersona'),
            inputPersona: DOM.$('#persona'),
            containerOffice: DOM.$('#contenedorOficina'),
            btnBuscar: DOM.$('#btnBuscarPersona'),
            btnConsultar: DOM.$('#btnConsultar')
        };

        if (!ui.labelPersona || !ui.inputPersona || !ui.btnConsultar) return;

        const switchType = this.state.currentPersonType;
        const btnConsultar = ui.btnConsultar;

        switch (switchType) {
            case 'natural':
                ui.labelPersona.textContent = 'Persona:';
                ui.inputPersona.disabled = true;
                ui.inputPersona.value = '';
                DOM.hide(ui.containerOffice);
                DOM.show(ui.btnBuscar);
                btnConsultar.disabled = false;
                btnConsultar.removeEventListener('click', this.handlers.lASIRSARP);
                btnConsultar.addEventListener('click', this.handlers.tSIRSARP);
                break;
            case 'juridica':
                ui.labelPersona.textContent = 'Razón Social:';
                ui.inputPersona.disabled = true;
                ui.inputPersona.value = '';
                DOM.hide(ui.containerOffice);
                DOM.show(ui.btnBuscar);
                btnConsultar.disabled = false;
                btnConsultar.removeEventListener('click', this.handlers.lASIRSARP);
                btnConsultar.addEventListener('click', this.handlers.tSIRSARP);
                break;
            case 'partida':
                ui.labelPersona.textContent = 'Número de partida:';
                ui.inputPersona.placeholder = 'Escribe el número de partida';
                ui.inputPersona.disabled = false;
                ui.inputPersona.readOnly = false;
                DOM.hide(ui.btnBuscar);
                DOM.show(ui.containerOffice);
                btnConsultar.disabled = false;
                btnConsultar.removeEventListener('click', this.handlers.tSIRSARP);
                btnConsultar.addEventListener('click', this.handlers.lASIRSARP);
                break;
        }
    },

    openSearchModal() {
        const type = this.state.currentPersonType;
        if (type === 'natural') {
            this.openModal('modalBusquedaNatural');
        } else if (type === 'juridica') {
            this.openModal('modalBusquedaJuridica');
        }
    },

    openModal(modalId) {
        const modal = DOM.$(`#${modalId}`);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    },

    closeModal(modalId) {
        const modal = DOM.$(`#${modalId}`);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    },

    async searchNaturalPerson(e) {
        e.preventDefault();

        const dni = DOM.val(DOM.$('#dniNatural'));

        if (!Validator.validateDNI(dni, this.ALERT_CONTAINER)) return;
        if (!this.validateCredentials()) return;

        this.showLoading('formBusquedaNatural');

        try {
            const result = await consultaService.buscarPersonaNatural(
                dni,
                this.state.userCredentials.dni,
                this.state.userCredentials.password
            );

            if (result.success && result.data?.length > 0) {
                this.state.foundRecords = result.data;
                this.renderNaturalResults(result.data);
                Alerts.inline('Se encontró información en RENIEC', 'success', this.ALERT_CONTAINER);
            } else {
                Alerts.inline(result.message || 'No se encontraron datos en RENIEC', 'info', this.ALERT_CONTAINER);
                this.state.foundRecords = [];
                this.renderNaturalResults([]);
            }
        } catch (error) {
            console.error('Error in natural search:', error);
            this.closeModal('modalBusquedaNatural');
            Alerts.inline(error.message || 'Error al buscar persona natural', 'danger', this.ALERT_CONTAINER);
            this.state.foundRecords = [];
            this.renderNaturalResults([]);
        } finally {
            this.hideLoading('formBusquedaNatural');
        }
    },

    async searchJuridicPerson(e) {
        e.preventDefault();

        const searchType = DOM.$('input[name="tipoBusquedaJuridica"]:checked')?.value;
        let param;

        if (searchType === 'ruc') {
            param = DOM.val(DOM.$('#rucJuridica'));
            if (!Validator.validateRUC(param, this.ALERT_CONTAINER)) return;
        } else {
            param = DOM.val(DOM.$('#razonSocial'));
            if (!param.trim()) {
                Alerts.inline('Por favor ingrese una razón social', 'warning', this.ALERT_CONTAINER);
                return;
            }
        }

        if (!this.validateCredentials()) return;

        this.showLoading('formBusquedaJuridica');

        try {
            const result = await consultaService.buscarPersonaJuridica({
                parametro: param,
                tipoBusqueda: searchType,
                dniUsuario: this.state.userCredentials.dni,
                password: this.state.userCredentials.password
            });

            if (result.success && result.data?.length > 0) {
                this.state.foundRecords = result.data;
                this.renderJuridicResults(result.data);
                Alerts.inline(`Se encontraron ${result.data.length} resultado(s) en SUNAT`, 'success', this.ALERT_CONTAINER);
            } else {
                Alerts.inline(result.message || 'No se encontraron registros en SUNAT', 'info', this.ALERT_CONTAINER);
                this.state.foundRecords = [];
                this.renderJuridicResults([]);
            }
        } catch (error) {
            console.error('Error in juridic search:', error);
            this.closeModal('modalBusquedaJuridica');
            Alerts.inline(error.message || 'Error al buscar persona jurídica', 'danger', this.ALERT_CONTAINER);
            this.state.foundRecords = [];
            this.renderJuridicResults([]);
        } finally {
            this.hideLoading('formBusquedaJuridica');
        }
    },

    validateCredentials() {
        if (!this.state.userCredentials.dni || !this.state.userCredentials.password) {
            Alerts.inline('No se han cargado las credenciales. Recargue la página.', 'danger', this.ALERT_CONTAINER);
            return false;
        }
        return true;
    },

    renderNaturalResults(data) {
        const container = DOM.$('#resultadosNatural');
        if (!container) return;

        if (!data?.length) {
            container.innerHTML = `
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center gap-3">
                    <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                    <span class="text-blue-800 font-medium">No se encontraron datos en RENIEC</span>
                </div>`;
            DOM.show(container);
            return;
        }

        let html = `
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                <strong class="text-emerald-800">Datos obtenidos de RENIEC</strong>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gradient-to-r from-violet-600 to-violet-700 text-white">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">DNI</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Nombres Completos</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Foto</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">`;

        data.forEach((person, index) => {
            const fullName = person.nombres_completos || 
                `${person.nombres || ''} ${person.apellido_paterno || ''} ${person.apellido_materno || ''}`.trim();
            
            const photoHtml = person.foto 
                ? `<img src="${person.foto.startsWith('data:image') ? person.foto : `data:image/jpeg;base64,${person.foto}`}" alt="Foto" class="w-20 h-24 object-cover rounded">`
                : `<div class="w-20 h-24 bg-gray-100 rounded flex items-center justify-center">
                    <i class="fas fa-user text-gray-400 text-2xl"></i>
                   </div>`;

            html += `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3"><strong>${person.dni || '-'}</strong></td>
                    <td class="px-4 py-3">${fullName || 'N/A'}</td>
                    <td class="px-4 py-3 text-center">${photoHtml}</td>
                    <td class="px-4 py-3">
                        <button onclick="ModuloPartidas.selectRecord(${index})" 
                            class="px-4 py-2 bg-emerald-500 text-white rounded-lg text-sm hover:bg-emerald-600">
                            Seleccionar
                        </button>
                    </td>
                </tr>`;
        });

        html += '</tbody></table></div>';
        DOM.html(container, html);
        DOM.show(container);
    },

    renderJuridicResults(data) {
        const container = DOM.$('#resultadosJuridica');
        if (!container) return;

        if (!data?.length) {
            container.innerHTML = `
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center gap-3">
                    <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                    <span class="text-blue-800 font-medium">No se encontraron datos en SUNAT</span>
                </div>`;
            DOM.show(container);
            return;
        }

        const manyResults = data.length > 5;
        const tableStyle = manyResults ? 'style="max-height: 400px; overflow-y: auto;"' : '';

        let html = `
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <strong class="text-blue-800">${data.length} resultado(s) de SUNAT</strong>
            </div>
            <div ${tableStyle}>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gradient-to-r from-violet-600 to-violet-700 text-white">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">RUC</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Razón Social</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Condición</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Depto</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">`;

        data.forEach((item, index) => {
            const active = item.estado_activo || (item.es_activo ? 'SÍ' : 'NO');
            const habited = item.estado_habido || (item.es_habido ? 'SÍ' : 'NO');
            
            const badgeActive = active === 'SÍ'
                ? '<span class="px-2 py-1 bg-green-500 text-white text-xs rounded">ACTIVO</span>'
                : '<span class="px-2 py-1 bg-red-500 text-white text-xs rounded">NO ACTIVO</span>';
            
            const badgeHabited = habited === 'SÍ'
                ? '<span class="px-2 py-1 bg-blue-500 text-white text-xs rounded">HABIDO</span>'
                : '<span class="px-2 py-1 bg-orange-500 text-white text-xs rounded">NO HABIDO</span>';

            html += `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3"><strong>${item.ruc || '-'}</strong></td>
                    <td class="px-4 py-3">${item.razon_social || '-'}</td>
                    <td class="px-4 py-3">${badgeActive}</td>
                    <td class="px-4 py-3">${badgeHabited}</td>
                    <td class="px-4 py-3">${item.departamento || '-'}</td>
                    <td class="px-4 py-3">
                        <button onclick="ModuloPartidas.selectRecord(${index})" 
                            class="px-4 py-2 bg-emerald-500 text-white rounded-lg text-sm hover:bg-emerald-600">
                            Seleccionar
                        </button>
                    </td>
                </tr>`;
        });

        html += '</tbody></table></div>';
        DOM.html(container, html);
        DOM.show(container);
    },

    selectRecord(index) {
        if (!this.state.foundRecords?.[index]) {
            Alerts.inline('Error al seleccionar el registro', 'danger', this.ALERT_CONTAINER);
            return;
        }

        this.state.selectedPerson = this.state.foundRecords[index];
        const inputPerson = DOM.$('#persona');

        if (this.state.currentPersonType === 'natural') {
            const fullName = this.state.selectedPerson.nombres_completos ||
                `${this.state.selectedPerson.nombres || ''} ${this.state.selectedPerson.apellido_paterno || ''} ${this.state.selectedPerson.apellido_materno || ''}`.trim();
            DOM.val(inputPerson, fullName);
            this.closeModal('modalBusquedaNatural');
        } else {
            DOM.val(inputPerson, this.state.selectedPerson.razon_social || '');
            this.closeModal('modalBusquedaJuridica');
        }

        Alerts.inline('Persona seleccionada. Haga clic en "Consultar" para buscar en SUNARP', 'info', this.ALERT_CONTAINER);

        DOM.$('#btnConsultar').disabled = false;
        DOM.hide(DOM.$('#resultsSection'));
        DOM.hide(DOM.$('#selectorPartidas'));
    },

    async consultTSIRSARP() {
        if (!this.state.selectedPerson) {
            Alerts.inline('Seleccione una persona primero', 'warning', this.ALERT_CONTAINER);
            return;
        }

        if (!this.validateCredentials()) return;

        this.clearPreviousResults();

        const btn = DOM.$('#btnConsultar');
        const loader = Loading.button(btn, { text: '<span class="loading-spinner"></span> Consultando SUNARP...' });

        try {
            let result;
            const creds = this.state.userCredentials;
            const person = this.state.selectedPerson;

            if (this.state.currentPersonType === 'natural') {
                result = await consultaService.consultarPartidaNatural({
                    usuario: creds.dni,
                    clave: creds.password,
                    apellidoPaterno: person.apellido_paterno || '',
                    apellidoMaterno: person.apellido_materno || '',
                    nombres: person.nombres || ''
                });
            } else {
                result = await consultaService.consultarPartidaJuridica({
                    usuario: creds.dni,
                    clave: creds.password,
                    razonSocial: person.razon_social || ''
                });
            }

            if (result.success && result.data?.length > 0) {
                this.displayTSIRSARPResults(result.data);
                Alerts.inline(`Se encontraron ${result.data.length} registro(s) en SUNARP`, 'success', this.ALERT_CONTAINER);
            } else {
                Alerts.inline('No se encontraron registros en SUNARP', 'info', this.ALERT_CONTAINER);
                this.showNoResultsMessage();
            }
        } catch (error) {
            console.error('Error in TSIRSARP:', error);
            Alerts.inline(error.message || 'Error al consultar SUNARP', 'danger', this.ALERT_CONTAINER);
        } finally {
            loader.restore();
        }
    },

    displayTSIRSARPResults(data) {
        this.state.foundPartidas = data;
        this.state.currentPage = 1;
        this.state.currentPartida = 0;
        this.state.detailCache = {};

        const info = DOM.$('#infoGrid');
        const resultsSection = DOM.$('#resultsSection');
        
        if (info) info.style.display = 'grid';
        if (resultsSection) {
            resultsSection.style.display = 'block';
            resultsSection.scrollIntoView({ behavior: 'smooth' });
        }

        if (data.length > 1) {
            this.renderPaginatedPartidaSelector();
        } else {
            DOM.hide(DOM.$('#selectorPartidas'));
        }

        this.loadAndShowDetail(data[0]);
    },

    showNoResultsMessage() {
        const resultsSection = DOM.$('#resultsSection');
        if (resultsSection) {
            resultsSection.style.display = 'block';
            resultsSection.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 6px; padding: 14px 18px;">
                    <div style="width: 32px; height: 32px; background: #f5c6cb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">!</div>
                    <div><strong>Aviso:</strong> No se encontraron registros en SUNARP.</div>
                </div>`;
        }
    },

    renderPaginatedPartidaSelector() {
        let selector = DOM.$('#selectorPartidas');
        
        if (!selector) {
            selector = DOM.create('div', { id: 'selectorPartidas' });
            DOM.$('#resultsSection')?.parentNode?.insertBefore(selector, DOM.$('#resultsSection'));
        }

        const total = this.state.foundPartidas.length;
        const totalPages = Math.ceil(total / this.state.itemsPerPage);
        const start = (this.state.currentPage - 1) * this.state.itemsPerPage;
        const end = Math.min(start + this.state.itemsPerPage, total);
        const pageItems = this.state.foundPartidas.slice(start, end);
        const selectedIndex = this.state.currentPartida ?? 0;

        let html = `
            <div class="glass rounded-2xl p-6 shadow-lg mb-6">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 700; color: #1f2937; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-list" style="color: #8b5cf6;"></i>
                            Partidas Registradas
                        </h3>
                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Mostrando ${start + 1} - ${end} de ${total}</p>
                    </div>
                </div>
                
                ${totalPages > 1 ? this.renderPaginationControls(totalPages) : ''}
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin-top: 1rem;">`;

        pageItems.forEach((partida, idx) => {
            const globalIdx = start + idx;
            const numero = partida.numero_partida || 'S/N';
            const estado = partida.estado || 'Sin estado';
            const oficina = partida.oficina || 'Sin oficina';
            const libro = partida.libro || '-';
            const esActiva = estado.toUpperCase() === 'ACTIVA' || estado.toUpperCase() === 'VIGENTE';
            const isSelected = globalIdx === selectedIndex;

            const badge = esActiva
                ? `<span style="padding: 0.25rem 0.5rem; background: #d1fae5; color: #065f46; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;">${estado}</span>`
                : `<span style="padding: 0.25rem 0.5rem; background: #fee2e2; color: #991b1b; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;">${estado}</span>`;

            html += `
                <label style="cursor: pointer; display: block;">
                    <input type="radio" name="partidaSeleccionada" value="${globalIdx}" 
                           ${isSelected ? 'checked' : ''}
                           onchange="ModuloPartidas.changePartida(${globalIdx})"
                           style="position: absolute; opacity: 0; width: 0; height: 0;">
                    <div data-partida-card="${globalIdx}" style="padding: 1rem; border-radius: 0.75rem; border: 2px solid ${isSelected ? '#8b5cf6' : '#e5e7eb'}; background: ${isSelected ? '#f5f3ff' : 'rgba(255, 255, 255, 0.8)'}; transition: all 0.2s;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: #8b5cf6; font-weight: 700;">
                            <i class="fas fa-file-contract"></i>
                            <span>${numero}</span>
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                            <div><strong>Libro:</strong> ${libro}</div>
                            <div><strong>Oficina:</strong> ${oficina}</div>
                        </div>
                        <div style="margin-top: 0.5rem;">${badge}</div>
                    </div>
                </label>`;
        });

        html += '</div></div>';
        DOM.html(selector, html);
        DOM.show(selector);
    },

    renderPaginationControls(totalPages) {
        const maxButtons = 5;
        let start = Math.max(1, this.state.currentPage - Math.floor(maxButtons / 2));
        let end = Math.min(totalPages, start + maxButtons - 1);

        if (end - start < maxButtons - 1) {
            start = Math.max(1, end - maxButtons + 1);
        }

        let html = '<div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 1rem;">';

        const prevDisabled = this.state.currentPage === 1;
        html += `<button ${prevDisabled ? 'disabled' : ''} onclick="ModuloPartidas.changePage(${this.state.currentPage - 1})"
                    style="padding: 0.5rem 1rem; border-radius: 0.5rem; ${prevDisabled ? 'background: #e5e7eb; color: #9ca3af;' : 'background: white; border: 1px solid #d1d5db;'}">Anterior</button>`;

        for (let i = start; i <= end; i++) {
            const isActive = i === this.state.currentPage;
            html += `<button onclick="ModuloPartidas.changePage(${i})"
                        style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; font-weight: 600; ${isActive ? 'background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white;' : 'background: white; border: 1px solid #d1d5db;'}">${i}</button>`;
        }

        const nextDisabled = this.state.currentPage === totalPages;
        html += `<button ${nextDisabled ? 'disabled' : ''} onclick="ModuloPartidas.changePage(${this.state.currentPage + 1})"
                    style="padding: 0.5rem 1rem; border-radius: 0.5rem; ${nextDisabled ? 'background: #e5e7eb; color: #9ca3af;' : 'background: white; border: 1px solid #d1d5db;'}">Siguiente</button>`;

        html += '</div>';
        return html;
    },

    changePage(newPage) {
        const totalPages = Math.ceil(this.state.foundPartidas.length / this.state.itemsPerPage);
        if (newPage < 1 || newPage > totalPages) return;

        this.state.currentPage = newPage;
        const firstIndex = (this.state.currentPage - 1) * this.state.itemsPerPage;
        
        if (this.state.foundPartidas[firstIndex]) {
            this.state.currentPartida = firstIndex;
            this.renderPaginatedPartidaSelector();
            this.loadAndShowDetail(this.state.foundPartidas[firstIndex]);
        }

        DOM.$('#selectorPartidas')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    },

    changePartida(index) {
        if (this.state.foundPartidas?.[index]) {
            this.state.currentPartida = index;
            this.updateCardStyles(index);
            this.loadAndShowDetail(this.state.foundPartidas[index]);
        }
    },

    updateCardStyles(selectedIndex) {
        DOM.$$('[data-partida-card]').forEach(card => {
            const idx = Number(card.getAttribute('data-partida-card'));
            if (idx === selectedIndex) {
                card.style.border = '2px solid #8b5cf6';
                card.style.background = '#f5f3ff';
            } else {
                card.style.border = '2px solid #e5e7eb';
                card.style.background = 'rgba(255, 255, 255, 0.8)';
            }
        });
    },

    async loadAndShowDetail(partida) {
        const numeroPartida = partida.numero_partida;
        const idx = this.state.foundPartidas.findIndex(p => p.numero_partida === numeroPartida);
        
        if (idx >= 0) {
            this.state.currentPartida = idx;
            this.updateCardStyles(idx);
        }

        if (this.state.detailCache[numeroPartida]) {
            this.showPartidaDetail({ ...partida, ...this.state.detailCache[numeroPartida] });
            return;
        }

        if (partida.detalle_cargado === false || partida.requiere_carga_bajo_demanda) {
            await this.loadDetailOnDemand(partida);
        } else {
            this.showPartidaDetail(partida);
        }
    },

    async loadDetailOnDemand(partida) {
        if (this.state.loadingDetail) return;

        this.state.loadingDetail = true;
        this.showDetailLoading();

        try {
            const result = await consultaService.cargarDetallePartida({
                numero_partida: partida.numero_partida,
                codigo_zona: partida.codigo_zona,
                codigo_oficina: partida.codigo_oficina,
                numero_placa: partida.numero_placa || ''
            });

            if (result.success && (
                (result.data.asientos?.length > 0) ||
                (result.data.imagenes?.length > 0) ||
                (result.data.datos_vehiculo && Object.keys(result.data.datos_vehiculo).length > 0)
            )) {
                this.state.detailCache[partida.numero_partida] = result.data;
                this.showPartidaDetail({ ...partida, ...result.data, detalle_cargado: true });
                Alerts.inline('Detalles cargados exitosamente', 'success', this.ALERT_CONTAINER);
            } else {
                Alerts.inline('No se encontraron detalles', 'warning', this.ALERT_CONTAINER);
            }
        } catch (error) {
            console.error('Error loading detail:', error);
            Alerts.inline('Error al cargar detalles', 'danger', this.ALERT_CONTAINER);
            this.showPartidaDetail({ ...partida, asientos: [], imagenes: [], datos_vehiculo: [] });
        } finally {
            this.state.loadingDetail = false;
            this.hideDetailLoading();
        }
    },

    showDetailLoading() {
        let overlay = DOM.$('#loadingOverlayPartida');
        if (!overlay) {
            overlay = DOM.create('div', {
                id: 'loadingOverlayPartida',
                style: 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 999999; display: flex; align-items: center; justify-content: center;'
            });
            overlay.innerHTML = `
                <div style="background: white; padding: 40px; border-radius: 10px; text-align: center;">
                    <div style="border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                    <p>Cargando detalles de la partida...</p>
                </div>
                <style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>`;
            document.body.appendChild(overlay);
        }
        DOM.show(overlay);
    },

    hideDetailLoading() {
        const overlay = DOM.$('#loadingOverlayPartida');
        if (overlay) DOM.remove(overlay);
    },

    showPartidaDetail(data) {
        const resultsSection = DOM.$('#resultsSection');
        const infoGrid = DOM.$('#infoGrid');
        
        if (resultsSection) resultsSection.style.display = 'block';
        if (infoGrid) infoGrid.style.display = 'grid';
        
        this.adjustLayout(this.state.currentPersonType === 'natural');

        this.showFields(data);
        this.showPersonPhoto();
        this.showImages(data.imagenes);
        this.showVehicleData(data.datos_vehiculo);
    },

    adjustLayout(showPhoto) {
        const photoSection = DOM.$('#photoSection');
        const mainRow = photoSection?.parentElement;

        if (mainRow) {
            mainRow.style.display = 'grid';
            mainRow.style.gap = '1.5rem';
            mainRow.style.gridTemplateColumns = showPhoto ? '300px minmax(0, 1fr)' : 'minmax(0, 1fr)';
        }
    },

    showFields(data) {
        const personType = this.state.currentPersonType;
        const selected = this.state.selectedPerson;
        const isNatural = personType === 'natural';
        const isJuridic = personType === 'juridica';
        const isPartida = personType === 'partida';

        const fieldMap = {
            nombres: data.nombre || selected?.nombres || '-',
            apellidoPaterno: data.apPaterno || selected?.apellido_paterno || '-',
            apellidoMaterno: data.apMaterno || selected?.apellido_materno || '-',
            campoRazonSocial: data.razon_social || selected?.razon_social || '-',
            tipoDoc: data.tipo_documento || (isNatural ? 'DNI' : isJuridic ? 'RUC' : '-'),
            nroDoc: data.numero_documento || (isNatural ? selected?.dni : isJuridic ? selected?.ruc : '-'),
            nroPartida: data.numero_partida || '-',
            nroPlaca: data.numero_placa || '-',
            estado: data.estado || '-',
            zona: data.zona || '-',
            libro: data.libro || '-',
            oficina: data.oficina || '-',
            direccion: data.direccion || '-'
        };

        Object.entries(fieldMap).forEach(([id, value]) => {
            const el = DOM.$(`#${id}`);
            if (el) DOM.text(el, value);
        });

        const containerIds = [
            'containerNombres', 'containerApellidoPaterno', 'containerApellidoMaterno',
            'containerRazonSocial', 'containerNroPartida', 'containerNroPlaca',
            'containerEstado', 'containerZona', 'containerLibro', 'containerOficina', 'containerDireccion'
        ];

        containerIds.forEach(id => {
            const el = DOM.$(`#${id}`);
            if (el) el.style.display = '';
        });

        if (isPartida) {
            DOM.$('#tipoDoc')?.closest('div') && (DOM.$('#tipoDoc').closest('div').style.display = 'none');
            DOM.$('#nroDoc')?.closest('div') && (DOM.$('#nroDoc').closest('div').style.display = 'none');
        }

        if (isNatural) {
            DOM.hide(DOM.$('#containerRazonSocial'));
            DOM.show(DOM.$('#photoSection'));
        } else if (isJuridic) {
            DOM.hide(DOM.$('#containerNombres'));
            DOM.hide(DOM.$('#containerApellidoPaterno'));
            DOM.hide(DOM.$('#containerApellidoMaterno'));
            DOM.hide(DOM.$('#photoSection'));
        } else {
            DOM.hide(DOM.$('#containerNombres'));
            DOM.hide(DOM.$('#containerApellidoPaterno'));
            DOM.hide(DOM.$('#containerApellidoMaterno'));
            DOM.hide(DOM.$('#containerRazonSocial'));
            DOM.hide(DOM.$('#tipoDoc')?.closest('div'));
            DOM.hide(DOM.$('#nroDoc')?.closest('div'));
            DOM.hide(DOM.$('#photoSection'));
        }
    },

    showPersonPhoto() {
        const container = DOM.$('#fotoContainer');
        const section = DOM.$('#photoSection');
        if (!container || !section) return;

        DOM.empty(container);

        const person = this.state.selectedPerson;
        if (person?.foto) {
            const photoBase64 = person.foto.startsWith('data:image') ? person.foto : `data:image/jpeg;base64,${person.foto}`;
            container.innerHTML = `<img src="${photoBase64}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">`;
        } else {
            container.innerHTML = `
                <div style="text-align: center; color: #9ca3af; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <i class="fas fa-user" style="font-size: 4rem; margin-bottom: 0.75rem; opacity: 0.5;"></i>
                    <p style="font-size: 0.875rem;">Sin fotografía</p>
                </div>`;
        }
        DOM.show(section);
    },

    showImages(imagenes) {
        const section = DOM.$('#imagenesSection');
        if (!section) return;

        const images = Array.isArray(imagenes) ? imagenes : Object.values(imagenes || {});

        if (!images?.length) {
            DOM.hide(section);
            return;
        }

        this.state.currentImage = null;
        this.state.zoomLevel = 1;

        const select = DOM.$('#selectImagenes');
        const viewer = DOM.$('#imagenViewer');
        const thumbnails = DOM.$('#thumbnailContainer');
        
        if (!select || !viewer) return;

        select.innerHTML = '';
        DOM.empty(thumbnails);

        images.forEach((img, index) => {
            if (!img?.imagen_base64) return;

            const option = DOM.create('option', { value: index, textContent: `Página ${img.pagina || (index + 1)}` });
            select.appendChild(option);

            const thumb = DOM.create('div', {
                'data-index': index,
                style: 'flex-shrink: 0; cursor: pointer; border-radius: 0.5rem; overflow: hidden; border: 3px solid #e5e7eb; width: 90px; height: 110px; background: #fff; transition: all 0.2s;'
            });
            thumb.innerHTML = `<img src="data:image/jpeg;base64,${img.imagen_base64}" style="width: 100%; height: 100%; object-fit: cover;">`;
            
            thumb.addEventListener('click', () => {
                select.value = index;
                this.changeImage(images, index);
            });

            thumbnails?.appendChild(thumb);
        });

        select.onchange = () => this.changeImage(images, parseInt(select.value));
        
        this.setupZoomControls();
        this.changeImage(images, 0);
        
        DOM.show(section);
    },

    changeImage(images, index) {
        const viewer = DOM.$('#imagenViewer');
        const select = DOM.$('#selectImagenes');
        const thumbnails = DOM.$('#thumbnailContainer');
        
        if (!viewer || !images[index]) return;

        this.state.currentImage = images[index];
        this.state.zoomLevel = 1;

        viewer.src = `data:image/jpeg;base64,${images[index].imagen_base64}`;
        viewer.style.display = 'block';
        viewer.style.transform = 'none';
        
        DOM.hide(DOM.$('#noImagen'));

        DOM.$$('#thumbnailContainer > div').forEach((el, i) => {
            el.style.borderColor = i === index ? '#8b5cf6' : '#e5e7eb';
        });

        const zoomLabel = DOM.$('#zoomLabel');
        if (zoomLabel) zoomLabel.textContent = '100%';
    },

    setupZoomControls() {
        const controls = {
            btnZoomIn: '#btnZoomIn',
            btnZoomOut: '#btnZoomOut',
            btnZoomReset: '#btnZoomReset',
            btnVer: '#btnVerImagen',
            btnDownload: '#btnDescargar'
        };

        Object.entries(controls).forEach(([name, selector]) => {
            const btn = DOM.$(selector);
            const newBtn = btn?.cloneNode(true);
            if (btn && newBtn) btn.parentNode.replaceChild(newBtn, btn);
        });

        DOM.$('#btnZoomIn')?.addEventListener('click', () => {
            if (this.state.zoomLevel < 3) {
                this.state.zoomLevel += 0.25;
                this.applyZoom();
            }
        });

        DOM.$('#btnZoomOut')?.addEventListener('click', () => {
            if (this.state.zoomLevel > 0.5) {
                this.state.zoomLevel -= 0.25;
                this.applyZoom();
            }
        });

        DOM.$('#btnZoomReset')?.addEventListener('click', () => {
            this.state.zoomLevel = 1;
            this.applyZoom();
        });

        DOM.$('#btnVerImagen')?.addEventListener('click', () => this.viewImage());
        DOM.$('#btnDescargar')?.addEventListener('click', () => this.downloadImage());
    },

    applyZoom() {
        const viewer = DOM.$('#imagenViewer');
        const zoomLabel = DOM.$('#zoomLabel');
        
        if (!viewer) return;

        const naturalW = parseFloat(viewer.dataset.naturalWidth || viewer.naturalWidth || 0);
        const naturalH = parseFloat(viewer.dataset.naturalHeight || viewer.naturalHeight || 0);

        if (naturalW > 0 && naturalH > 0) {
            viewer.style.width = `${Math.round(naturalW * this.state.zoomLevel)}px`;
            viewer.style.height = `${Math.round(naturalH * this.state.zoomLevel)}px`;
            viewer.style.maxWidth = 'none';
            viewer.style.maxHeight = 'none';
        }

        if (zoomLabel) zoomLabel.textContent = `${Math.round(this.state.zoomLevel * 100)}%`;
    },

    viewImage() {
        if (!this.state.currentImage?.imagen_base64) {
            Alerts.inline('No hay imagen disponible', 'warning', this.ALERT_CONTAINER);
            return;
        }

        const win = window.open('', '_blank');
        win.document.write(`
            <!DOCTYPE html><html><head><title>Imagen de Partida</title>
            <style>body { margin: 0; display: flex; justify-content: center; align-items: center; background: #333; } img { max-width: 100%; height: auto; }</style>
            </head><body><img src="data:image/jpeg;base64,${this.state.currentImage.imagen_base64}"></body></html>`);
        win.document.close();
    },

    downloadImage() {
        if (!this.state.currentImage?.imagen_base64) {
            Alerts.inline('No hay imagen para descargar', 'warning', this.ALERT_CONTAINER);
            return;
        }

        const base64 = this.state.currentImage.imagen_base64;
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            canvas.getContext('2d').drawImage(img, 0, 0);
            canvas.toBlob(blob => {
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `partida_${Date.now()}.jpg`;
                link.click();
                URL.revokeObjectURL(url);
            }, 'image/jpeg', 0.95);
        };
        img.src = `data:image/jpeg;base64,${base64}`;
    },

    showVehicleData(datos) {
        const section = DOM.$('#vehiculoSection');
        const container = DOM.$('#vehiculoContainer');
        
        if (!datos || Object.keys(datos).length === 0) {
            DOM.hide(section);
            return;
        }

        const fields = {
            placa: { label: 'Placa', icon: 'fa-id-card' },
            marca: { label: 'Marca', icon: 'fa-copyright' },
            modelo: { label: 'Modelo', icon: 'fa-car-side' },
            anoFabricacion: { label: 'Año', icon: 'fa-calendar' },
            color: { label: 'Color', icon: 'fa-palette' },
            nro_motor: { label: 'N° Motor', icon: 'fa-cog' },
            carroceria: { label: 'Carrocería', icon: 'fa-truck' },
            codCategoria: { label: 'Categoría', icon: 'fa-tag' },
            estado: { label: 'Estado', icon: 'fa-info-circle' }
        };

        let html = '';
        for (const [field, config] of Object.entries(fields)) {
            const val = datos[field];
            if (val !== undefined && val !== null && val !== '') {
                const isPlaca = field === 'placa';
                const isEstado = field === 'estado';
                html += `
                    <div style="background: white; border-radius: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <i class="fas ${config.icon} text-violet-600"></i>
                            <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">${config.label}</span>
                        </div>
                        <div style="font-size: 1.125rem; font-weight: 700; ${isPlaca ? 'color: #7c3aed; text-transform: uppercase;' : isEstado ? 'color: #059669;' : 'color: #1f2937;'}">${val}</div>
                    </div>`;
            }
        }

        DOM.html(container, html);
        DOM.show(section);
    },

    clearPreviousResults() {
        this.state.foundPartidas = [];
        this.state.detailCache = {};
        this.state.currentPage = 1;
        this.state.currentPartida = null;
        this.state.currentImage = null;
        this.state.zoomLevel = 1;

        const selector = DOM.$('#selectorPartidas');
        if (selector) DOM.remove(selector);

        const results = DOM.$('#resultsSection');
        if (results) DOM.hide(results);

        const fields = ['nombres', 'apellidoPaterno', 'apellidoMaterno', 'campoRazonSocial', 'tipoDoc', 'nroDoc',
            'nroPartida', 'nroPlaca', 'estado', 'zona', 'libro', 'oficina', 'direccion'];
        fields.forEach(id => {
            const el = DOM.$(`#${id}`);
            if (el) DOM.text(el, '-');
        });

        const photoContainer = DOM.$('#fotoContainer');
        if (photoContainer) {
            photoContainer.innerHTML = `<div style="text-align: center; color: #9ca3af;"><i class="fas fa-user" style="font-size: 4rem; opacity: 0.5;"></i><p>Sin fotografía</p></div>`;
        }

        DOM.hide(DOM.$('#imagenesSection'));
        DOM.hide(DOM.$('#vehiculoSection'));
    },

    clearForm() {
        this.state.selectedPerson = null;
        this.state.foundRecords = [];
        this.clearPreviousResults();

        DOM.val(DOM.$('#persona'), '');
        DOM.val(DOM.$('#oficinaRegistralID'), '');

        DOM.empty(DOM.$(`#${this.ALERT_CONTAINER}`));

        DOM.$('#dniNatural') && (DOM.$('#dniNatural').value = '');
        DOM.$('#rucJuridica') && (DOM.$('#rucJuridica').value = '');
        DOM.$('#razonSocial') && (DOM.$('#razonSocial').value = '');
        
        DOM.hide(DOM.$('#resultadosNatural'));
        DOM.hide(DOM.$('#resultadosJuridica'));
    },

    showLoading(formId) {
        const btn = formId === 'formBusquedaNatural' ? DOM.$('#btnBuscarPersona') : DOM.$('#btnBuscarPersona');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span>';
        }
    },

    hideLoading(formId) {
        const btn = DOM.$('#btnBuscarPersona');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search"></i>';
        }
    }
};

window.ModuloPartidas = ModuloPartidas;

if (typeof window.registrarModulo === 'function') {
    window.registrarModulo('consultaspartidas', ModuloPartidas);
}
