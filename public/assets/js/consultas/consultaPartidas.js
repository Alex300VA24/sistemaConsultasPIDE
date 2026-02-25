// ============================================
// MÓDULO DE CONSULTA PARTIDAS REGISTRALES - OPTIMIZADO
// ============================================

const ModuloPartidas = {
    // Estado del módulo
    inicializado: false,
    personaSeleccionada: null,
    tipoPersonaActual: 'natural',
    registrosEncontrados: [],

    // PAGINACIÓN
    partidasEncontradas: [],
    paginaActual: 1,
    partidasPorPagina: 8,
    partidaActualmenteMostrada: null,

    credencialesUsuario: {
        dni: '',
        password: ''
    },

    // Cache de detalles cargados
    cacheDetalles: {},

    // Estado de carga
    cargandoDetalle: false,

    zoomLevel: 1,
    imagenActual: null,

    // ============================================
    // INICIALIZACIÓN
    // ============================================
    async init() {
        if (this.inicializado) {
            return;
        }

        await this.cargarCredencialesUsuario();
        await this.cargarOficinasRegistrales();
        this.handlerTSIRSARP = this.consultarTSIRSARP.bind(this);
        this.handlerLASIRSARP = this.consultarLASIRSARP.bind(this);
        this.setupEventListeners();

        this.inicializado = true;
    },

    // ============================================
    // CARGAR CREDENCIALES
    // ============================================
    async cargarCredencialesUsuario() {
        try {
            const usuario = await api.obtenerUsuarioActual();
            if (usuario.success && usuario.data) {
                this.credencialesUsuario.dni = usuario.data.PER_documento_numero || '';

                if (usuario.data.USU_username) {
                    const credenciales = await api.obtenerDniYPassword(usuario.data.USU_username);
                    if (credenciales && credenciales.success) {
                        this.credencialesUsuario.password = credenciales.data.password || '';
                    }
                }
            }
        } catch (error) {
            console.error('❌ Error al cargar credenciales:', error);
            mostrarAlerta('Error al cargar credenciales de usuario', 'danger', "alertContainerPartidas");
        }
    },

    async cargarOficinasRegistrales() {
        try {
            const oficinas = await api.obtenerOficinasRegistrales();

            if (oficinas.success && oficinas.data) {
                const seleccionOficina = document.getElementById('oficinaRegistralID');

                // Limpia opciones previas
                seleccionOficina.innerHTML = '<option value="">Seleccione Oficina Registral</option>';

                // Convierte el objeto a array, ordena y luego itera
                const oficinasArray = Object.values(oficinas.data);

                // Ordenar alfabéticamente por descripción
                oficinasArray.sort((a, b) => {
                    // Convertir a mayúsculas para comparación insensible a mayúsculas/minúsculas
                    const descA = a.descripcion.toUpperCase();
                    const descB = b.descripcion.toUpperCase();

                    if (descA < descB) return -1;
                    if (descA > descB) return 1;
                    return 0;
                });

                // Versión más concisa (funciona igual):
                // oficinasArray.sort((a, b) => a.descripcion.localeCompare(b.descripcion));

                // Itera sobre las oficinas ordenadas
                oficinasArray.forEach(oficina => {
                    const option = document.createElement('option');
                    option.textContent = oficina.descripcion; // lo que se ve
                    option.value = `${oficina.codZona}-${oficina.codOficina}`; // lo que se envía
                    seleccionOficina.appendChild(option);
                });
            }
        } catch (error) {
            console.error('❌ Error al cargar oficinas registrales:', error);
            mostrarAlerta('Error al cargar oficinas registrales', 'danger', "alertContainerPartidas");
        }
    },

    // ============================================
    // CONFIGURAR EVENT LISTENERS
    // ============================================
    setupEventListeners() {
        // Radio buttons tipo de persona
        document.querySelectorAll('input[name="tipoPersona"]').forEach(radio => {
            radio.addEventListener('change', (e) => this.cambiarTipoPersona(e));
        });

        // Botones principales
        document.getElementById('btnBuscarPersona')?.addEventListener('click', () => this.abrirModalBusqueda());
        document.getElementById('btnConsultar')?.addEventListener('click', this.handlerTSIRSARP);

        document.getElementById('btnLimpiar')?.addEventListener('click', () => this.limpiarFormularioPartidas());

        // Forms de búsqueda
        document.getElementById('formBusquedaNatural')?.addEventListener('submit', (e) => this.buscarPersonaNatural(e));
        document.getElementById('formBusquedaJuridica')?.addEventListener('submit', (e) => this.buscarPersonaJuridica(e));

        // Radio buttons búsqueda jurídica
        document.querySelectorAll('input[name="tipoBusquedaJuridica"]').forEach(radio => {
            radio.addEventListener('change', (e) => this.cambiarTipoBusquedaJuridica(e));
        });

        // Cerrar modales al hacer clic fuera
        document.getElementById('modalBusquedaNatural')?.addEventListener('click', (e) => {
            if (e.target.id === 'modalBusquedaNatural') {
                this.cerrarModal('modalBusquedaNatural');
            }
        });

        document.getElementById('modalBusquedaJuridica')?.addEventListener('click', (e) => {
            if (e.target.id === 'modalBusquedaJuridica') {
                this.cerrarModal('modalBusquedaJuridica');
            }
        });

        // Botones de cierre de modales
        document.querySelectorAll('[data-modal]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modalId = btn.getAttribute('data-modal');
                this.cerrarModal(modalId);
            });
        });

        // Cerrar con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.cerrarModal('modalBusquedaNatural');
                this.cerrarModal('modalBusquedaJuridica');
            }
        });

        // Validación solo números
        document.getElementById('dniNatural')?.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        document.getElementById('rucJuridica')?.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    },

    // ============================================
    // CAMBIO DE TIPO DE PERSONA
    // ============================================
    cambiarTipoPersona(e) {
        this.tipoPersonaActual = e.target.value;
        this.limpiarFormularioPartidas();

        const labelPersona = document.getElementById('labelPersona');
        const inputPersona = document.getElementById('persona');
        const contenedorOficina = document.getElementById('contenedorOficina');
        const btnBuscar = document.getElementById("btnBuscarPersona");
        const btnConsultar = document.getElementById('btnConsultar');

        if (labelPersona && inputPersona && contenedorOficina && btnConsultar) {
            switch (this.tipoPersonaActual) {
                case 'natural':
                    labelPersona.textContent = 'Persona:';
                    inputPersona.disabled = true;
                    inputPersona.value = '';
                    contenedorOficina.style.display = 'none';
                    btnBuscar.style.display = 'flex';
                    btnConsultar.disabled = false;

                    btnConsultar.removeEventListener('click', this.handlerLASIRSARP);
                    btnConsultar.addEventListener('click', this.handlerTSIRSARP);
                    break;

                case 'juridica':
                    labelPersona.textContent = 'Razón Social:';
                    inputPersona.disabled = true;
                    inputPersona.value = '';
                    contenedorOficina.style.display = 'none';
                    btnBuscar.style.display = 'flex';
                    btnConsultar.disabled = false;

                    btnConsultar.removeEventListener('click', this.handlerLASIRSARP);
                    btnConsultar.addEventListener('click', this.handlerTSIRSARP);
                    break;

                case 'partida':
                    labelPersona.textContent = 'Número de partida:';
                    inputPersona.placeholder = 'Escribe el número de partida';
                    inputPersona.disabled = false;
                    inputPersona.readOnly = false;
                    btnBuscar.style.display = 'none';
                    contenedorOficina.style.display = 'flex';
                    btnConsultar.disabled = false;

                    btnConsultar.removeEventListener('click', this.handlerTSIRSARP);
                    btnConsultar.addEventListener('click', this.handlerLASIRSARP);
                    break;

                default:
                    labelPersona.textContent = 'Persona:';
                    inputPersona.disabled = true;
                    inputPersona.value = '';
                    contenedorOficina.style.display = 'none';

                    btnConsultar.removeEventListener('click', this.handlerLASIRSARP);
                    btnConsultar.addEventListener('click', this.handlerTSIRSARP);
            }
        }
    },
    // ============================================
    // 📌 MODALES
    // ============================================
    abrirModalBusqueda() {
        if (this.tipoPersonaActual === 'natural') {
            this.abrirModal('modalBusquedaNatural');
        } else if (this.tipoPersonaActual === 'juridica') {
            this.abrirModal('modalBusquedaJuridica');
        }
    },

    abrirModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    },

    cerrarModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    },

    // ============================================
    // BÚSQUEDA PERSONA NATURAL
    // ============================================
    async buscarPersonaNatural(e) {
        e.preventDefault();

        const dni = document.getElementById('dniNatural').value.trim();

        if (!dni || dni.length !== 8) {
            mostrarAlerta('Por favor ingrese un DNI válido de 8 dígitos', 'warning', "alertContainerPartidas");
            return;
        }

        if (!this.credencialesUsuario.dni || !this.credencialesUsuario.password) {
            mostrarAlerta('No se han cargado las credenciales del usuario. Recargue la página.', 'danger', "alertContainerPartidas");
            return;
        }
        this.mostrarLoadingPartidas('formBusquedaNatural');

        try {
            const resultado = await api.buscarPersonaNaturalSunarp(
                dni,
                this.credencialesUsuario.dni,
                this.credencialesUsuario.password
            );

            if (resultado.success && resultado.data && resultado.data.length > 0) {
                this.registrosEncontrados = resultado.data;
                this.mostrarResultadosNatural(resultado.data);
                mostrarAlerta('Se encontró información en RENIEC', 'success', "alertContainerPartidas");
            } else {
                mostrarAlerta(resultado.message || 'No se encontraron datos en RENIEC para este DNI', 'info', "alertContainerPartidas");
                this.registrosEncontrados = [];
                this.mostrarResultadosNatural([]);
            }
        } catch (error) {
            console.error('❌ Error en búsqueda natural:', error);
            this.cerrarModal('modalBusquedaNatural');
            mostrarAlerta(error.message || 'Error al buscar persona natural', 'danger', "alertContainerPartidas");
            this.registrosEncontrados = [];
            this.mostrarResultadosNatural([]);
        } finally {
            this.ocultarLoadingPartidas('formBusquedaNatural');
        }
    },

    // ============================================
    // BÚSQUEDA PERSONA JURÍDICA
    // ============================================
    async buscarPersonaJuridica(e) {
        e.preventDefault();

        const tipoBusqueda = document.querySelector('input[name="tipoBusquedaJuridica"]:checked').value;
        let parametro;

        if (tipoBusqueda === 'ruc') {
            parametro = document.getElementById('rucJuridica').value.trim();
            if (!parametro || parametro.length !== 11) {
                mostrarAlerta('Por favor ingrese un RUC válido de 11 dígitos', 'warning', "alertContainerPartidas");
                return;
            }
        } else {
            parametro = document.getElementById('razonSocial').value.trim();
            if (!parametro) {
                mostrarAlerta('Por favor ingrese una razón social', 'warning', "alertContainerPartidas");
                return;
            }
        }

        if (!this.credencialesUsuario.dni || !this.credencialesUsuario.password) {
            mostrarAlerta('No se han cargado las credenciales del usuario. Recargue la página.', 'danger', "alertContainerPartidas");
            return;
        }

        this.mostrarLoadingPartidas('formBusquedaJuridica');

        try {
            const resultado = await api.buscarPersonaJuridicaSunarp(
                parametro,
                tipoBusqueda,
                this.credencialesUsuario.dni,
                this.credencialesUsuario.password
            );

            if (resultado.success && resultado.data && resultado.data.length > 0) {
                this.registrosEncontrados = resultado.data;
                this.mostrarResultadosJuridica(resultado.data);
                mostrarAlerta(`Se encontraron ${resultado.data.length} resultado(s) en SUNAT`, 'success', "alertContainerPartidas");
            } else {
                mostrarAlerta(resultado.message || 'No se encontraron registros en SUNAT', 'info', "alertContainerPartidas");
                this.registrosEncontrados = [];
                this.mostrarResultadosJuridica([]);
            }
        } catch (error) {
            console.error('❌ Error en búsqueda jurídica:', error);
            this.cerrarModal('modalBusquedaJuridica');
            mostrarAlerta(error.message || 'Error al buscar persona jurídica', 'danger', "alertContainerPartidas");
            this.registrosEncontrados = [];
            this.mostrarResultadosJuridica([]);
        } finally {
            this.ocultarLoadingPartidas('formBusquedaJuridica');
        }
    },

    // ============================================
    // MOSTRAR RESULTADOS NATURALES
    // ============================================
    mostrarResultadosNatural(data) {
        const contenedor = document.getElementById('resultadosNatural');

        if (!data || data.length === 0) {
            contenedor.innerHTML = `
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center gap-3">
                    <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                    <span class="text-blue-800 font-medium">No se encontraron datos en RENIEC</span>
                </div>
            `;
            contenedor.style.display = 'block';
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
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">DNI</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Nombres Completos</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Foto</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
        `;

        data.forEach((persona, index) => {
            const nombresCompletos = persona.nombres_completos ||
                `${persona.nombres || ''} ${persona.apellido_paterno || ''} ${persona.apellido_materno || ''}`.trim();

            let fotoHtml = `<div class="w-20 h-24 bg-gray-100 rounded flex items-center justify-center">
                <i class="fas fa-user text-gray-400 text-2xl"></i>
            </div>`;

            if (persona.foto) {
                const fotoBase64 = persona.foto.startsWith('data:image')
                    ? persona.foto
                    : `data:image/jpeg;base64,${persona.foto}`;
                fotoHtml = `<img src="${fotoBase64}" alt="Foto RENIEC" class="w-20 h-24 object-cover rounded">`;
            }
            html += `
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3"><strong class="text-gray-800">${persona.dni || '-'}</strong></td>
                    <td class="px-4 py-3 text-gray-700">${nombresCompletos || 'N/A'}</td>
                    <td class="px-4 py-3 text-center">${fotoHtml}</td>
                    <td class="px-4 py-3">
                        <button onclick="ModuloPartidas.seleccionarRegistro(${index})" 
                            class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-lg font-medium text-sm hover:from-emerald-600 hover:to-emerald-700 transition shadow-md">
                            Seleccionar
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';
        contenedor.innerHTML = html;
        contenedor.style.display = 'block';
    },

    // ============================================
    // MOSTRAR RESULTADOS JURÍDICOS
    // ============================================
    mostrarResultadosJuridica(data) {
        const contenedor = document.getElementById('resultadosJuridica');

        if (!data || data.length === 0) {
            contenedor.innerHTML = `
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center gap-3">
                    <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                    <span class="text-blue-800 font-medium">No se encontraron datos en SUNAT</span>
                </div>
            `;
            contenedor.style.display = 'block';
            return;
        }

        const tieneMuchosResultados = data.length > 5;

        let html = `
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <strong class="text-blue-800">${data.length} resultado(s) obtenido(s) de SUNAT</strong>
            </div>
        `;

        // Contenedor con altura máxima y scroll vertical cuando hay muchos registros
        const tableContainerStyle = tieneMuchosResultados
            ? 'style="max-height: 400px; overflow-y: auto; overflow-x: auto;"'
            : 'style="overflow-x: auto;"';

        html += `
            <div ${tableContainerStyle}>
                <table class="w-full border-collapse">
                    <thead style="position: sticky; top: 0; z-index: 10;">
                        <tr class="bg-gradient-to-r from-violet-600 to-violet-700 text-white">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">RUC</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Razón Social</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Condición</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Departamento</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
        `;

        data.forEach((item, index) => {
            const razonSocial = item.razon_social || '-';
            const ruc = item.ruc || '-';
            const estadoActivo = item.estado_activo || (item.es_activo ? 'SÍ' : 'NO');
            const estadoHabido = item.estado_habido || (item.es_habido ? 'SÍ' : 'NO');
            const departamento = item.departamento || '-';

            const badgeActivo = estadoActivo === 'SÍ'
                ? '<span class="px-2 py-1 bg-green-500 text-white text-xs rounded font-medium">ACTIVO</span>'
                : '<span class="px-2 py-1 bg-red-500 text-white text-xs rounded font-medium">NO ACTIVO</span>';

            const badgeHabido = estadoHabido === 'SÍ'
                ? '<span class="px-2 py-1 bg-blue-500 text-white text-xs rounded font-medium">HABIDO</span>'
                : '<span class="px-2 py-1 bg-orange-500 text-white text-xs rounded font-medium">NO HABIDO</span>';

            html += `
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3"><strong class="text-gray-800">${ruc}</strong></td>
                    <td class="px-4 py-3 text-gray-700">${razonSocial}</td>
                    <td class="px-4 py-3">${badgeActivo}</td>
                    <td class="px-4 py-3">${badgeHabido}</td>
                    <td class="px-4 py-3 text-gray-600">${departamento}</td>
                    <td class="px-4 py-3">
                        <button onclick="ModuloPartidas.seleccionarRegistro(${index})" 
                            class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-lg font-medium text-sm hover:from-emerald-600 hover:to-emerald-700 transition shadow-md whitespace-nowrap">
                            Seleccionar
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';

        contenedor.innerHTML = html;
        contenedor.style.display = 'block';
    },

    // ============================================
    // SELECCIONAR REGISTRO
    // ============================================
    seleccionarRegistro(index) {
        if (!this.registrosEncontrados || !this.registrosEncontrados[index]) {
            mostrarAlerta('Error al seleccionar el registro', 'danger', "alertContainerPartidas");
            return;
        }

        this.personaSeleccionada = this.registrosEncontrados[index];

        const inputPersona = document.getElementById('persona');

        if (this.tipoPersonaActual === 'natural') {
            const nombresCompletos = this.personaSeleccionada.nombres_completos ||
                `${this.personaSeleccionada.nombres || ''} ${this.personaSeleccionada.apellido_paterno || ''} ${this.personaSeleccionada.apellido_materno || ''}`.trim();
            inputPersona.value = nombresCompletos;
            this.cerrarModal('modalBusquedaNatural');
        } else {
            inputPersona.value = this.personaSeleccionada.razon_social || '';
            this.cerrarModal('modalBusquedaJuridica');
        }

        mostrarAlerta('Persona seleccionada. Haga clic en "Consultar" para buscar en SUNARP', 'info', "alertContainerPartidas");

        document.getElementById('btnConsultar').disabled = false;

        const resultsSection = document.getElementById('resultsSection');
        if (resultsSection) resultsSection.style.display = 'none';

        const selectorPartidas = document.getElementById('selectorPartidas');
        if (selectorPartidas) selectorPartidas.style.display = 'none';
    },


    // ============================================
    // CONSULTAR TSIRSARP
    // ============================================
    async consultarTSIRSARP() {
        if (!this.personaSeleccionada) {
            mostrarAlerta('Por favor seleccione una persona o razón social primero', 'warning', "alertContainerPartidas");
            return;
        }

        if (!this.credencialesUsuario.dni || !this.credencialesUsuario.password) {
            mostrarAlerta('No se han cargado las credenciales del usuario. Recargue la página.', 'danger', "alertContainerPartidas");
            return;
        }

        // ========================================
        // LIMPIAR RESULTADOS ANTERIORES PRIMERO
        // ========================================
        this.limpiarResultadosAnteriores();

        const btnConsultar = document.getElementById('btnConsultar');
        const originalHTML = btnConsultar.innerHTML;
        btnConsultar.disabled = true;
        btnConsultar.innerHTML = '<span class="loading-spinner"></span> Consultando SUNARP...';

        try {
            let resultado;

            if (this.tipoPersonaActual === 'natural') {
                resultado = await api.consultarTSIRSARPNatural({
                    usuario: this.credencialesUsuario.dni,
                    clave: this.credencialesUsuario.password,
                    apellidoPaterno: this.personaSeleccionada.apellido_paterno || '',
                    apellidoMaterno: this.personaSeleccionada.apellido_materno || '',
                    nombres: this.personaSeleccionada.nombres || ''
                });
            } else {
                resultado = await api.consultarTSIRSARPJuridica({
                    usuario: this.credencialesUsuario.dni,
                    clave: this.credencialesUsuario.password,
                    razonSocial: this.personaSeleccionada.razon_social || ''
                });
            }

            if (resultado.success && resultado.data && resultado.data.length > 0) {
                this.mostrarResultadosTSIRSARP(resultado.data);
                mostrarAlerta(`Se encontraron ${resultado.data.length} registro(s) en SUNARP`, 'success', "alertContainerPartidas");
            } else {
                mostrarAlerta('No se encontraron registros en SUNARP', 'info', "alertContainerPartidas");
                // aqui debemos hacer algo
                const resultsSection = document.getElementById('resultsSection');
                resultsSection.style.display = 'block';

                // Limpiamos el contenido previo y agregamos el aviso

                resultsSection.innerHTML = `
                    <div style="
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        background: linear-gradient(135deg, #f8d7da, #f1b0b7);
                        color: #721c24;
                        border: 1px solid #f5c6cb;
                        border-radius: 6px;
                        padding: 14px 18px;
                        font-family: 'Segoe UI', Arial, sans-serif;
                        font-size: 15px;
                        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                    ">
                        <div style="
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            background-color: #f5c6cb;
                            color: #721c24;
                            font-size: 18px;
                            font-weight: bold;
                            border-radius: 50%;
                            width: 32px;
                            height: 32px;
                        ">
                            !
                        </div>
                        <div>
                            <strong>Aviso:</strong> No se encontraron registros en SUNARP.
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('❌ Error en consulta TSIRSARP:', error);
            mostrarAlerta(error.message || 'Error al consultar SUNARP', 'danger', "alertContainerPartidas");
        } finally {
            btnConsultar.disabled = false;
            btnConsultar.innerHTML = originalHTML;
        }
    },

    // ============================================
    // MOSTRAR RESULTADOS TSIRSARP CON PAGINACIÓN
    // ============================================
    mostrarResultadosTSIRSARP(data) {
        this.partidasEncontradas = data;
        this.paginaActual = 1;
        this.cacheDetalles = {}; // Limpiar cache

        const info = document.getElementById('infoGrid');
        info.style.display = 'grid';
        const resultsSection = document.getElementById('resultsSection');
        resultsSection.style.display = 'block';

        if (data.length > 1) {
            this.mostrarSelectorPartidasPaginado();
        } else {
            const selectorPartidas = document.getElementById('selectorPartidas');
            if (selectorPartidas) {
                selectorPartidas.style.display = 'none';
            }
        }

        // Cargar detalle de la primera partida
        this.cargarYMostrarDetalle(data[0]);
        resultsSection.scrollIntoView({ behavior: 'smooth' });
    },

    // ============================================
    // SELECTOR DE PARTIDAS CON PAGINACIÓN
    // ============================================
    mostrarSelectorPartidasPaginado() {
        let selectorPartidas = document.getElementById('selectorPartidas');

        if (!selectorPartidas) {
            selectorPartidas = document.createElement('div');
            selectorPartidas.id = 'selectorPartidas';
            selectorPartidas.className = 'selector-partidas-container';

            const resultsSection = document.getElementById('resultsSection');
            resultsSection.parentNode.insertBefore(selectorPartidas, resultsSection);
        }

        const totalPartidas = this.partidasEncontradas.length;
        const totalPaginas = Math.ceil(totalPartidas / this.partidasPorPagina);
        const inicio = (this.paginaActual - 1) * this.partidasPorPagina;
        const fin = Math.min(inicio + this.partidasPorPagina, totalPartidas);
        const partidasPagina = this.partidasEncontradas.slice(inicio, fin);

        let html = `
            <div class="selector-partidas-header">
                <h3><i class="fas fa-list"></i> Partidas Registradas (${totalPartidas})</h3>
                <p>Mostrando ${inicio + 1} - ${fin} de ${totalPartidas}</p>
            </div>
            
            ${totalPaginas > 1 ? this.generarControlesPaginacion(totalPaginas) : ''}
            
            <div class="selector-partidas-grid">
        `;

        partidasPagina.forEach((partida, indexEnPagina) => {
            const indexGlobal = inicio + indexEnPagina;
            const partidaNumero = partida.numero_partida || 'S/N';
            const estado = partida.estado || 'Sin estado';
            const oficina = partida.oficina || 'Sin oficina';
            const estadoClass = estado.toUpperCase() === 'ACTIVA' ? 'activa' : 'inactiva';
            const esPrimeraPagina = this.paginaActual === 1 && indexEnPagina === 0;

            html += `
                <div class="partida-card">
                    <input type="radio" name="partidaSeleccionada" id="partida${indexGlobal}" 
                           value="${indexGlobal}" ${esPrimeraPagina ? 'checked' : ''}
                           onchange="ModuloPartidas.cambiarPartida(${indexGlobal})">
                    <label for="partida${indexGlobal}">
                        <div class="partida-info">
                            <div class="partida-numero">
                                <i class="fas fa-file-alt"></i>
                                Partida N° <strong>${partidaNumero}</strong>
                            </div>
                            <div class="partida-detalles">
                                <span class="partida-estado estado-${estadoClass}">
                                    <i class="fas fa-circle"></i> ${estado}
                                </span>
                                <span class="partida-oficina">
                                    <i class="fas fa-building"></i> ${oficina}
                                </span>
                            </div>
                        </div>
                    </label>
                </div>
            `;
        });

        html += `</div>`;

        html += `
            </div>
            <style>
                .selector-partidas-container {
                    margin: 20px 0;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 8px;
                    border: 1px solid #dee2e6;
                }
                .selector-partidas-header h3 {
                    margin: 0 0 8px 0;
                    color: #2c3e50;
                    font-size: 1.2em;
                }
                .selector-partidas-header p {
                    margin: 0;
                    color: #6c757d;
                    font-size: 0.9em;
                }
                .selector-partidas-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                    gap: 15px;
                    margin-top: 15px;
                }
                .partida-card {
                    position: relative;
                }
                .partida-card input[type="radio"] {
                    position: absolute;
                    opacity: 0;
                    width: 0;
                    height: 0;
                }
                .partida-card label {
                    display: block;
                    padding: 15px;
                    background: white;
                    border: 2px solid #dee2e6;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                .partida-card label:hover {
                    border-color: #3498db;
                    box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
                }
                .partida-card input[type="radio"]:checked + label {
                    border-color: #3498db;
                    background: #e3f2fd;
                    box-shadow: 0 2px 12px rgba(52, 152, 219, 0.3);
                }
                .partida-numero {
                    font-size: 1.1em;
                    color: #2c3e50;
                    margin-bottom: 10px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .partida-numero i {
                    color: #3498db;
                }
                .partida-detalles {
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                    font-size: 0.85em;
                }
                .partida-estado, .partida-oficina {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    color: #6c757d;
                }
                .partida-estado i {
                    font-size: 0.6em;
                }
                .partida-estado.estado-activa {
                    color: #27ae60;
                }
                .partida-estado.estado-activa i {
                    color: #27ae60;
                }
                .partida-estado.estado-inactiva {
                    color: #e74c3c;
                }
                .partida-estado.estado-inactiva i {
                    color: #e74c3c;
                }
            </style>
        `;

        // Agregar estilos CSS
        html += this.generarEstilosPaginacion();

        selectorPartidas.innerHTML = html;
        selectorPartidas.style.display = 'block';
    },

    // ============================================
    // GENERAR CONTROLES DE PAGINACIÓN
    // ============================================
    generarControlesPaginacion(totalPaginas) {
        const maxBotones = 5;
        let inicio = Math.max(1, this.paginaActual - Math.floor(maxBotones / 2));
        let fin = Math.min(totalPaginas, inicio + maxBotones - 1);

        if (fin - inicio < maxBotones - 1) {
            inicio = Math.max(1, fin - maxBotones + 1);
        }

        let html = '<div class="paginacion-controles">';

        // Botón Anterior
        html += `
            <button class="btn-paginacion ${this.paginaActual === 1 ? 'disabled' : ''}" 
                    onclick="ModuloPartidas.cambiarPagina(${this.paginaActual - 1})"
                    ${this.paginaActual === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i> Anterior
            </button>
        `;

        // Primera página
        if (inicio > 1) {
            html += `<button class="btn-paginacion-num" onclick="ModuloPartidas.cambiarPagina(1)">1</button>`;
            if (inicio > 2) {
                html += '<span class="paginacion-ellipsis">...</span>';
            }
        }

        // Páginas numeradas
        for (let i = inicio; i <= fin; i++) {
            html += `
                <button class="btn-paginacion-num ${i === this.paginaActual ? 'active' : ''}"
                        onclick="ModuloPartidas.cambiarPagina(${i})">
                    ${i}
                </button>
            `;
        }

        // Última página
        if (fin < totalPaginas) {
            if (fin < totalPaginas - 1) {
                html += '<span class="paginacion-ellipsis">...</span>';
            }
            html += `<button class="btn-paginacion-num" onclick="ModuloPartidas.cambiarPagina(${totalPaginas})">${totalPaginas}</button>`;
        }

        // Botón Siguiente
        html += `
            <button class="btn-paginacion ${this.paginaActual === totalPaginas ? 'disabled' : ''}"
                    onclick="ModuloPartidas.cambiarPagina(${this.paginaActual + 1})"
                    ${this.paginaActual === totalPaginas ? 'disabled' : ''}>
                Siguiente <i class="fas fa-chevron-right"></i>
            </button>
        `;

        html += '</div>';
        return html;
    },

    // ============================================
    // CAMBIAR PÁGINA
    // ============================================
    cambiarPagina(nuevaPagina) {
        const totalPaginas = Math.ceil(this.partidasEncontradas.length / this.partidasPorPagina);

        if (nuevaPagina < 1 || nuevaPagina > totalPaginas) {
            return;
        }

        this.paginaActual = nuevaPagina;
        this.mostrarSelectorPartidasPaginado();

        // Auto-seleccionar la primera partida de la nueva página
        const inicio = (this.paginaActual - 1) * this.partidasPorPagina;
        const primeraPartida = this.partidasEncontradas[inicio];
        if (primeraPartida) {
            this.cargarYMostrarDetalle(primeraPartida);
        }

        // Scroll suave al selector
        document.getElementById('selectorPartidas')?.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    },

    // ============================================
    // CAMBIAR PARTIDA (ACTUALIZADO)
    // ============================================
    cambiarPartida(index) {
        if (this.partidasEncontradas && this.partidasEncontradas[index]) {
            this.cargarYMostrarDetalle(this.partidasEncontradas[index]);
        }
    },

    // ============================================
    // CARGAR Y MOSTRAR DETALLE (NUEVO)
    // ============================================
    async cargarYMostrarDetalle(partida) {
        const numeroPartida = partida.numero_partida;

        // Verificar si ya está en cache
        if (this.cacheDetalles[numeroPartida]) {
            this.mostrarDetallePartida({
                ...partida,
                ...this.cacheDetalles[numeroPartida]
            });
            return;
        }

        // Verificar si requiere carga bajo demanda
        if (partida.detalle_cargado === false || partida.requiere_carga_bajo_demanda) {
            await this.cargarDetallePartidaBajoDemanda(partida);
        } else {
            this.mostrarDetallePartida(partida);
        }
    },

    // ============================================
    // CARGAR DETALLE BAJO DEMANDA (NUEVO)
    // ============================================
    // ============================================
    // ACTUALIZAR: cargarDetallePartidaBajoDemanda
    // ============================================
    async cargarDetallePartidaBajoDemanda(partida) {
        if (this.cargandoDetalle) {
            return;
        }

        this.cargandoDetalle = true;

        // Si el loading NO está visible, mostrarlo
        if (!document.getElementById('loadingDetallePartida')) {
            this.mostrarLoadingDetallePartida();
        }


        try {
            const resultado = await api.cargarDetallePartida({
                numero_partida: partida.numero_partida,
                codigo_zona: partida.codigo_zona,
                codigo_oficina: partida.codigo_oficina,
                numero_placa: partida.numero_placa || ''
            });

            if (
                resultado.success &&
                (
                    (resultado.data.asientos && resultado.data.asientos.length > 0) ||
                    (resultado.data.imagenes && resultado.data.imagenes.length > 0) ||
                    (resultado.data.datos_vehiculo && Object.keys(resultado.data.datos_vehiculo).length > 0)
                )
            ) {
                // Guardar en cache
                this.cacheDetalles[partida.numero_partida] = resultado.data;

                // Combinar datos básicos con detalle
                const partidaCompleta = {
                    ...partida,
                    ...resultado.data,
                    detalle_cargado: true
                };

                this.mostrarDetallePartida(partidaCompleta);
                mostrarAlerta('Detalles cargados exitosamente', 'success', "alertContainerPartidas");
            } else {
                mostrarAlerta('No se encontraron detalles de esta partida', 'warning', "alertContainerPartidas");
            }
        } catch (error) {
            console.error('Error al cargar detalle:', error);
            mostrarAlerta('Error al cargar detalles de la partida' + error, 'danger', "alertContainerPartidas");

            // Mostrar datos básicos al menos
            this.mostrarDetallePartida({
                ...partida,
                asientos: [],
                imagenes: [],
                datos_vehiculo: []
            });
        } finally {
            this.cargandoDetalle = false;
            // IMPORTANTE: Remover el loading al terminar
            this.ocultarLoadingDetallePartida();
        }
    },

    // ============================================
    // GENERAR ESTILOS DE PAGINACIÓN
    // ============================================
    generarEstilosPaginacion() {
        return `
            <style>
                .paginacion-controles {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    gap: 8px;
                    margin: 20px 0;
                    flex-wrap: wrap;
                }
                
                .btn-paginacion {
                    padding: 8px 16px;
                    background: #3498db;
                    color: white;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 500;
                    transition: all 0.2s;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }
                
                .btn-paginacion:hover:not(.disabled) {
                    background: #2980b9;
                    transform: translateY(-1px);
                }
                
                .btn-paginacion.disabled {
                    background: #95a5a6;
                    cursor: not-allowed;
                    opacity: 0.6;
                }
                
                .btn-paginacion-num {
                    min-width: 40px;
                    height: 40px;
                    padding: 8px;
                    background: white;
                    color: #2c3e50;
                    border: 2px solid #dee2e6;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.2s;
                }
                
                .btn-paginacion-num:hover {
                    border-color: #3498db;
                    background: #e3f2fd;
                }
                
                .btn-paginacion-num.active {
                    background: #3498db;
                    color: white;
                    border-color: #3498db;
                }
                
                .paginacion-ellipsis {
                    padding: 0 8px;
                    color: #6c757d;
                    font-weight: bold;
                }
                
                .loading-detalle-partida {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.7);
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    color: white;
                }
                
                .loading-spinner-big {
                    width: 60px;
                    height: 60px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #3498db;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin-bottom: 20px;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                @media (max-width: 768px) {
                    .paginacion-controles {
                        gap: 4px;
                    }
                    
                    .btn-paginacion {
                        padding: 6px 12px;
                        font-size: 14px;
                    }
                    
                    .btn-paginacion-num {
                        min-width: 35px;
                        height: 35px;
                        font-size: 14px;
                    }
                }
                /* ============================================
                    LOADING OVERLAY - AGREGAR AL CSS
                    ============================================ */

                    .loading-detalle-partida {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(0, 0, 0, 0.75);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 99999; /* MUY ALTO para estar sobre todo */
                        animation: fadeIn 0.2s ease-in;
                    }

                    .loading-content {
                        background: white;
                        padding: 40px 60px;
                        border-radius: 12px;
                        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                        text-align: center;
                        max-width: 400px;
                    }

                    .loading-spinner-big {
                        width: 60px;
                        height: 60px;
                        border: 5px solid #f3f3f3;
                        border-top: 5px solid #3498db;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                        margin: 0 auto 20px;
                    }

                    .loading-text {
                        font-size: 18px;
                        font-weight: 600;
                        color: #2c3e50;
                        margin: 0 0 10px 0;
                    }

                    .loading-subtext {
                        font-size: 14px;
                        color: #7f8c8d;
                        margin: 0;
                    }

                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }

                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }

                    /* Versión alternativa con fondo oscuro (si prefieres) */
                    .loading-detalle-partida.dark-mode .loading-content {
                        background: #2c3e50;
                        color: white;
                    }

                    .loading-detalle-partida.dark-mode .loading-text {
                        color: white;
                    }

                    .loading-detalle-partida.dark-mode .loading-subtext {
                        color: #bdc3c7;
                    }

                    /* Responsive */
                    @media (max-width: 768px) {
                        .loading-content {
                            padding: 30px 40px;
                            max-width: 300px;
                        }
                        
                        .loading-spinner-big {
                            width: 50px;
                            height: 50px;
                            border-width: 4px;
                        }
                        
                        .loading-text {
                            font-size: 16px;
                        }
                        
                        .loading-subtext {
                            font-size: 13px;
                        }
                    }
            </style>
        `;
    },

    // ============================================
    // CONSULTAR LASIRSARP
    // ============================================
    // ============================================
    // VERSIÓN SIMPLE Y DIRECTA - 100% GARANTIZADA
    // ============================================

    async consultarLASIRSARP() {
        if (!this.credencialesUsuario.dni || !this.credencialesUsuario.password) {
            mostrarAlerta('No se han cargado las credenciales del usuario. Recargue la página.', 'danger', "alertContainerPartidas");
            return;
        }

        const btnConsultar = document.getElementById('btnConsultar');
        const originalHTML = btnConsultar.innerHTML;
        btnConsultar.disabled = true;
        btnConsultar.innerHTML = '<span class="loading-spinner"></span> Consultando SUNARP...';

        try {
            // Validaciones de partida y oficina
            const partidaInput = document.getElementById('persona').value.trim();
            const oficinaInput = document.getElementById('oficinaRegistralID').value.trim();

            if (!partidaInput) {
                mostrarAlerta('Por favor digite un numero de partida primero', 'warning', "alertContainerPartidas");
                return;
            }

            if (!oficinaInput) {
                mostrarAlerta('Por favor seleccione una oficina', 'warning', "alertContainerPartidas");
                return;
            }

            const oficinaRegex = /^\d{2}-\d{2}$/;
            if (!oficinaRegex.test(oficinaInput)) {
                mostrarAlerta('La oficina debe tener formato NN-NN (ejemplo: 01-01).', 'danger', "alertContainerPartidas");
                return;
            }

            const [zona, oficina] = oficinaInput.split("-");

            const partida = {
                numero_partida: partidaInput,
                codigo_zona: zona,
                codigo_oficina: oficina,
                numero_placa: ''
            };

            this.partidaEncontrada = partida;
            // ========================================
            // LIMPIAR RESULTADOS ANTERIORES PRIMERO
            // ========================================
            this.limpiarResultadosAnteriores();

            // ========================================
            // MOSTRAR LOADING OVERLAY - VERSIÓN SIMPLE
            // ========================================

            // 1. Crear overlay con ID único y estilos inline
            let overlay = document.createElement('div');
            overlay.id = 'loadingOverlayPartida';
            overlay.innerHTML = `
            <div style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.8);
                z-index: 999999;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <div style="
                    background: white;
                    padding: 40px;
                    border-radius: 10px;
                    text-align: center;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                ">
                    <div style="
                        border: 4px solid #f3f3f3;
                        border-top: 4px solid #3498db;
                        border-radius: 50%;
                        width: 50px;
                        height: 50px;
                        animation: spin 1s linear infinite;
                        margin: 0 auto 20px auto;
                    "></div>
                    <p style="
                        margin: 0;
                        font-size: 16px;
                        font-weight: 600;
                        color: #333;
                    ">Cargando detalles de la partida...</p>
                    <p style="
                        margin: 10px 0 0 0;
                        font-size: 14px;
                        color: #666;
                    ">Por favor espere</p>
                </div>
            </div>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `;

            // 2. Agregar al body AL FINAL
            document.body.appendChild(overlay);

            // 3. Forzar repaint
            overlay.offsetHeight;

            // 4. Pequeño delay
            await new Promise(resolve => setTimeout(resolve, 50));

            // 5. Ahora mostrar resultsSection
            const resultsSection = document.getElementById('resultsSection');
            const info = document.getElementById('infoGrid');
            info.style.display = 'grid';
            resultsSection.style.display = 'block';

            // 6. Cargar detalle
            await this.cargarDetallePartidaBajoDemandaSimple(partida);

            // 7. Remover overlay
            if (overlay && overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }

            // 8. Scroll
            resultsSection.scrollIntoView({ behavior: 'smooth' });

        } catch (error) {
            console.error('❌ Error en consulta de Partida Registral:', error);
            mostrarAlerta(error.message || 'Error al consultar Partida Registral', 'danger', "alertContainerPartidas");

            // Asegurar que se remueve el overlay incluso si hay error
            const overlay = document.getElementById('loadingOverlayPartida');
            if (overlay && overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        } finally {
            btnConsultar.disabled = false;
            btnConsultar.innerHTML = originalHTML;
        }
    },

    // ============================================
    // VERSIÓN SIMPLE DE CARGAR DETALLE
    // ============================================
    async cargarDetallePartidaBajoDemandaSimple(partida) {
        try {
            const resultado = await api.cargarDetallePartida({
                numero_partida: partida.numero_partida,
                codigo_zona: partida.codigo_zona,
                codigo_oficina: partida.codigo_oficina,
                numero_placa: partida.numero_placa || ''
            });

            if (
                resultado.success &&
                (
                    (resultado.data.asientos && resultado.data.asientos.length > 0) ||
                    (resultado.data.imagenes && resultado.data.imagenes.length > 0) ||
                    (resultado.data.datos_vehiculo && resultado.data.datos_vehiculo.length > 0)
                )
            ) {
                // Guardar en cache
                this.cacheDetalles[partida.numero_partida] = resultado.data;

                // Combinar datos básicos con detalle
                const partidaCompleta = {
                    ...partida,
                    ...resultado.data,
                    detalle_cargado: true
                };

                this.mostrarDetallePartida(partidaCompleta);
                mostrarAlerta('Detalles cargados exitosamente', 'success', "alertContainerPartidas");
            } else {
                const resultsSection = document.getElementById('resultsSection');
                const info = document.getElementById('infoGrid');

                resultsSection.innerHTML = `
                    <div style="
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        background: linear-gradient(135deg, #f8d7da, #f1b0b7);
                        color: #721c24;
                        border: 1px solid #f5c6cb;
                        border-radius: 6px;
                        padding: 14px 18px;
                        font-family: 'Segoe UI', Arial, sans-serif;
                        font-size: 15px;
                        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                    ">
                        <div style="
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            background-color: #f5c6cb;
                            color: #721c24;
                            font-size: 18px;
                            font-weight: bold;
                            border-radius: 50%;
                            width: 32px;
                            height: 32px;
                        ">
                            !
                        </div>
                        <div>
                            <strong>Aviso:</strong> No se encontraron registros en SUNARP.
                        </div>
                    </div>
                `;

                mostrarAlerta('No se encontraron detalles de esta partida', 'warning', "alertContainerPartidas");
            }
        } catch (error) {
            console.error('Error al cargar detalle:', error);
            mostrarAlerta('Error al cargar detalles de la partida', 'danger', "alertContainerPartidas");

            // Mostrar datos básicos
            this.mostrarDetallePartida({
                ...partida,
                asientos: [],
                imagenes: [],
                datos_vehiculo: []
            });
        }
    },

    // ============================================
    // MÉTODO AUXILIAR: MOSTRAR LOADING
    // ============================================
    mostrarLoadingDetallePartida() {
        // Remover loading anterior si existe
        this.ocultarLoadingDetallePartida();

        // Crear nuevo loading overlay
        const loadingIndicator = document.createElement('div');
        loadingIndicator.id = 'loadingDetallePartida';
        loadingIndicator.className = 'loading-detalle-partida';
        loadingIndicator.innerHTML = `
        <div class="loading-content">
            <div class="loading-spinner-big"></div>
            <p class="loading-text">Cargando detalles de la partida...</p>
            <p class="loading-subtext">Obteniendo asientos registrales e imágenes</p>
        </div>
    `;

        // Agregar al body (no a resultsSection)
        document.body.appendChild(loadingIndicator);

    },

    // ============================================
    // MÉTODO AUXILIAR: OCULTAR LOADING
    // ============================================
    ocultarLoadingDetallePartida() {
        const loadingIndicator = document.getElementById('loadingDetallePartida');
        if (loadingIndicator) {
            loadingIndicator.remove();
        }
    },

    // ============================================
    // MOSTRAR RESULTADOS LASIRSARP
    // ============================================
    mostrarResultadosLASIRSARP(data) {
        this.partidaEncontrada = data;
        const resultsSection = document.getElementById('resultsSection');
        const info = document.getElementById('infoGrid');

        // MOSTRAR el contenedor principal
        info.style.display = 'grid'; // o 'block' según tu CSS
        resultsSection.style.display = 'block';


        this.mostrarDetallePartida(data);
        resultsSection.scrollIntoView({ behavior: 'smooth' });
    },


    // ============================================
    // SELECTOR DE PARTIDAS
    // ============================================
    mostrarSelectorPartidas(partidas) {
        let selectorPartidas = document.getElementById('selectorPartidas');

        if (!selectorPartidas) {
            selectorPartidas = document.createElement('div');
            selectorPartidas.id = 'selectorPartidas';
            selectorPartidas.className = 'selector-partidas-container';

            const resultsSection = document.getElementById('resultsSection');
            resultsSection.parentNode.insertBefore(selectorPartidas, resultsSection);
        }

        let html = `
            <div class="selector-partidas-header">
                <h3><i class="fas fa-list"></i> Partidas Registradas (${partidas.length})</h3>
                <p>Seleccione una partida para ver los detalles completos</p>
            </div>
            <div class="selector-partidas-grid">
        `;

        partidas.forEach((partida, index) => {
            const partidaNumero = partida.numero_partida || 'S/N';
            const estado = partida.estado || 'Sin estado';
            const oficina = partida.oficina || 'Sin oficina';
            const estadoClass = estado.toUpperCase() === 'ACTIVA' ? 'activa' : 'inactiva';

            html += `
                <div class="partida-card">
                    <input type="radio" name="partidaSeleccionada" id="partida${index}" 
                           value="${index}" ${index === 0 ? 'checked' : ''}
                           onchange="ModuloPartidas.cambiarPartida(${index})">
                    <label for="partida${index}">
                        <div class="partida-info">
                            <div class="partida-numero">
                                <i class="fas fa-file-alt"></i>
                                Partida N° <strong>${partidaNumero}</strong>
                            </div>
                            <div class="partida-detalles">
                                <span class="partida-estado estado-${estadoClass}">
                                    <i class="fas fa-circle"></i> ${estado}
                                </span>
                                <span class="partida-oficina">
                                    <i class="fas fa-building"></i> ${oficina}
                                </span>
                            </div>
                        </div>
                    </label>
                </div>
            `;
        });

        html += `
            </div>
            <style>
                .selector-partidas-container {
                    margin: 20px 0;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 8px;
                    border: 1px solid #dee2e6;
                }
                .selector-partidas-header h3 {
                    margin: 0 0 8px 0;
                    color: #2c3e50;
                    font-size: 1.2em;
                }
                .selector-partidas-header p {
                    margin: 0;
                    color: #6c757d;
                    font-size: 0.9em;
                }
                .selector-partidas-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                    gap: 15px;
                    margin-top: 15px;
                }
                .partida-card {
                    position: relative;
                }
                .partida-card input[type="radio"] {
                    position: absolute;
                    opacity: 0;
                    width: 0;
                    height: 0;
                }
                .partida-card label {
                    display: block;
                    padding: 15px;
                    background: white;
                    border: 2px solid #dee2e6;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                .partida-card label:hover {
                    border-color: #3498db;
                    box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
                }
                .partida-card input[type="radio"]:checked + label {
                    border-color: #3498db;
                    background: #e3f2fd;
                    box-shadow: 0 2px 12px rgba(52, 152, 219, 0.3);
                }
                .partida-numero {
                    font-size: 1.1em;
                    color: #2c3e50;
                    margin-bottom: 10px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .partida-numero i {
                    color: #3498db;
                }
                .partida-detalles {
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                    font-size: 0.85em;
                }
                .partida-estado, .partida-oficina {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    color: #6c757d;
                }
                .partida-estado i {
                    font-size: 0.6em;
                }
                .partida-estado.estado-activa {
                    color: #27ae60;
                }
                .partida-estado.estado-activa i {
                    color: #27ae60;
                }
                .partida-estado.estado-inactiva {
                    color: #e74c3c;
                }
                .partida-estado.estado-inactiva i {
                    color: #e74c3c;
                }
            </style>
        `;
        selectorPartidas.innerHTML = html;
        selectorPartidas.style.display = 'block';
    },

    /*cambiarPartida(index) {
        if (this.partidasEncontradas && this.partidasEncontradas[index]) {
            this.mostrarDetallePartida(this.partidasEncontradas[index]);
            mostrarAlerta('Mostrando detalles de la partida seleccionada', 'success', "alertContainerPartidas");
        }
    },*/

    // ============================================
    // MOSTRAR DETALLE DE PARTIDA
    // ============================================
    mostrarDetallePartida(registro) {
        // Resetear visibilidad de todos los contenedores
        const contenedores = ['containerNombres', 'containerApellidoPaterno',
            'containerApellidoMaterno', 'containerRazonSocial', 'containerLibro',
            'containerNroPartida', 'containerNroPlaca', 'containerEstado',
            'containerZona', 'containerOficina', 'containerDireccion'];
        contenedores.forEach(id => {
            const elem = document.getElementById(id);
            if (elem) elem.style.display = '';
        });

        const photoSection = document.getElementById('photoSection');
        const resultsLayout = document.querySelector('.results-layout');
        if (this.tipoPersonaActual === 'natural') {
            if (photoSection) photoSection.style.display = '';
            if (resultsLayout) resultsLayout.style.opacity = '1';
            this.mostrarCampo('libro', registro.libro || '-');
            this.mostrarCampo('nombres', registro.nombre || this.personaSeleccionada.nombres || '-', 'containerNombres');
            this.mostrarCampo('apellidoPaterno', registro.apPaterno || this.personaSeleccionada.apellido_paterno || '-', 'containerApellidoPaterno');
            this.mostrarCampo('apellidoMaterno', registro.apMaterno || this.personaSeleccionada.apellido_materno || '-', 'containerApellidoMaterno');

            this.ocultarCampo('campoRazonSocial', 'containerRazonSocial');

            this.mostrarFotoPersona();
        } else if (this.tipoPersonaActual === 'partida') {
            if (photoSection) photoSection.style.display = 'none';
            if (resultsLayout) resultsLayout.style.opacity = '0.8';

            // OCULTAR los campos específicos que no se usan en LASIRSARP
            this.ocultarCampo('nombres', 'containerNombres');
            this.ocultarCampo('apellidoPaterno', 'containerApellidoPaterno');
            this.ocultarCampo('apellidoMaterno', 'containerApellidoMaterno');
            this.ocultarCampo('tipoDoc');
            this.ocultarCampo('nroDoc');
            this.ocultarCampo('libro', 'containerLibro');
            this.ocultarCampo('nroPartida', 'containerNroPartida');
            this.ocultarCampo('nroPlaca', 'containerNroPlaca');
            this.ocultarCampo('estado', 'containerEstado');
            this.ocultarCampo('zona', 'containerZona');
            this.ocultarCampo('oficina', 'containerOficina');
            this.ocultarCampo('direccion', 'containerDireccion');

            this.ocultarCampo('campoRazonSocial', 'containerRazonSocial');
        } else {
            if (photoSection) photoSection.style.display = 'none';
            if (resultsLayout) resultsLayout.style.opacity = '0.8';
            this.mostrarCampo('libro', registro.libro || '-');

            const tieneNombres = registro.nombre || registro.apPaterno || registro.apMaterno;
            if (tieneNombres) {
                this.mostrarCampo('nombres', registro.nombre || '-', 'containerNombres');
                this.mostrarCampo('apellidoPaterno', registro.apPaterno || '-', 'containerApellidoPaterno');
                this.mostrarCampo('apellidoMaterno', registro.apMaterno || '-', 'containerApellidoMaterno');
            } else {
                this.ocultarCampo('nombres', 'containerNombres');
                this.ocultarCampo('apellidoPaterno', 'containerApellidoPaterno');
                this.ocultarCampo('apellidoMaterno', 'containerApellidoMaterno');
            }

            const razonSocial = registro.razon_social || this.personaSeleccionada.razon_social || '-';
            this.mostrarCampo('campoRazonSocial', razonSocial, 'containerRazonSocial');
        }
        // Campos comunes

        // Solo mostrar estos campos si NO es tipo partida (LASIRSARP)
        if (this.tipoPersonaActual !== 'partida') {
            this.mostrarCampo('tipoDoc', registro.tipo_documento || (this.tipoPersonaActual === 'natural' ? 'DNI' : 'RUC'));
            this.mostrarCampo('nroDoc', registro.numero_documento || (this.tipoPersonaActual === 'natural' ? this.personaSeleccionada.dni : this.tipoPersonaActual === 'partida' ? '-' : this.personaSeleccionada.ruc) || '-');
            this.mostrarCampo('nroPartida', registro.numero_partida || '-', 'containerNroPartida');
            this.mostrarCampo('nroPlaca', registro.numero_placa || '-', 'containerNroPlaca');
            this.mostrarCampo('estado', registro.estado || '-', 'containerEstado');
            this.mostrarCampo('zona', registro.zona || '-', 'containerZona');
            this.mostrarCampo('oficina', registro.oficina || '-', 'containerOficina');
            this.mostrarCampo('direccion', registro.direccion || '-', 'containerDireccion');
        }

        // Secciones adicionales
        if (registro.imagenes && registro.imagenes.length > 0) {
            this.mostrarImagenes(registro.imagenes);
        } else {
            document.getElementById('imagenesSection').style.display = 'none';
        }
        if (registro.datos_vehiculo && Object.keys(registro.datos_vehiculo).length > 0) {
            this.mostrarDatosVehiculo(registro.datos_vehiculo);
        } else {
            document.getElementById('vehiculoSection').style.display = 'none';
        }
    },

    mostrarFotoPersona() {
        const photoFrame = document.getElementById('photoSection');
        if (!photoFrame) return;

        photoFrame.innerHTML = '';

        if (this.personaSeleccionada && this.personaSeleccionada.foto) {
            const fotoBase64 = this.personaSeleccionada.foto.startsWith('data:image')
                ? this.personaSeleccionada.foto
                : `data:image/jpeg;base64,${this.personaSeleccionada.foto}`;

            const img = document.createElement('img');
            img.src = fotoBase64;
            img.alt = "Foto de persona";
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '0.75rem';
            photoFrame.appendChild(img);
            
            photoFrame.style.display = '';
        } else {
            const placeholder = document.createElement('div');
            placeholder.style.width = '100%';
            placeholder.style.height = '100%';
            placeholder.style.display = 'flex';
            placeholder.style.alignItems = 'center';
            placeholder.style.justifyContent = 'center';
            placeholder.style.color = '#9ca3af';
            placeholder.innerHTML = '<div style="text-align: center;"><i class="fas fa-user" style="font-size: 4rem; margin-bottom: 0.75rem; display: block;"></i><p style="font-size: 0.875rem;">Sin fotografía</p></div>';
            photoFrame.appendChild(placeholder);
            photoFrame.style.display = '';
        }
    },

    mostrarCampo(idCampo, valor, idContenedor = null) {
        const elemento = document.getElementById(idCampo);
        if (elemento) {
            elemento.textContent = valor;
        }

        const contenedor = idContenedor ? document.getElementById(idContenedor) : null;
        if (contenedor) {
            contenedor.style.display = '';
        } else {
            // Si no hay contenedor específico, buscar el parent
            const contenedorPadre = elemento ? elemento.closest('div') : null;
            if (contenedorPadre) {
                contenedorPadre.style.display = '';
            }
        }
    },

    ocultarCampo(idCampo, idContenedor = null) {
        const contenedor = idContenedor ? document.getElementById(idContenedor) : null;
        if (contenedor) {
            contenedor.style.display = 'none';
        } else {
            const elemento = document.getElementById(idCampo);
            const contenedorPadre = elemento ? elemento.closest('div') : null;
            if (contenedorPadre) {
                contenedorPadre.style.display = 'none';
            }
        }
    },

    mostrarImagenes(imagenes) {
        const imagenesSection = document.getElementById('imagenesSection');
        const selectImagenes = document.getElementById('selectImagenes');
        const imagenViewer = document.getElementById('imagenViewer');
        const noImagen = document.getElementById('noImagen');
        const thumbnailContainer = document.getElementById('thumbnailContainer');

        // Resetear zoom al cambiar de partida
        this.zoomLevel = 1;
        if (imagenViewer) {
            imagenViewer.style.transform = 'scale(1)';
        }

        // Limpiar selects y miniaturas anteriores
        selectImagenes.innerHTML = '';
        thumbnailContainer.innerHTML = '';

        // Llenar el select y generar miniaturas
        imagenes.forEach((img, index) => {
            // Opción en el select
            const option = document.createElement('option');
            option.value = index;
            option.textContent = `Página ${img.pagina || (index + 1)}`;
            selectImagenes.appendChild(option);

            // Miniatura
            if (img.imagen_base64) {
                const thumbnailDiv = document.createElement('div');
                thumbnailDiv.className = 'flex-shrink-0 cursor-pointer rounded-lg overflow-hidden border-2 transition-all duration-200 hover:shadow-lg';
                thumbnailDiv.style.borderColor = '#e5e7eb';
                thumbnailDiv.style.width = '80px';
                thumbnailDiv.style.height = '100px';
                
                const img_el = document.createElement('img');
                img_el.src = `data:image/jpeg;base64,${img.imagen_base64}`;
                img_el.alt = `Página ${index + 1}`;
                img_el.style.width = '100%';
                img_el.style.height = '100%';
                img_el.style.objectFit = 'cover';
                
                thumbnailDiv.appendChild(img_el);
                thumbnailDiv.addEventListener('click', () => {
                    selectImagenes.value = index;
                    cambiarImagen();
                    
                    // Highlight the selected thumbnail
                    document.querySelectorAll('#thumbnailContainer > div').forEach(el => {
                        el.style.borderColor = '#e5e7eb';
                    });
                    thumbnailDiv.style.borderColor = '#8b5cf6';
                });
                
                thumbnailContainer.appendChild(thumbnailDiv);
            }
        });

        // Mostrar miniaturas si hay más de 1 página
        if (imagenes.length > 1) {
            thumbnailContainer.style.display = 'flex';
        }

        const cambiarImagen = () => {
            const index = parseInt(selectImagenes.value);
            const imagenData = imagenes[index];

            // Resetear zoom al cambiar de imagen
            this.zoomLevel = 1;

            if (imagenData && imagenData.imagen_base64) {
                this.imagenActual = imagenData;
                imagenViewer.src = `data:image/jpeg;base64,${imagenData.imagen_base64}`;
                imagenViewer.style.display = 'block';
                imagenViewer.style.transform = 'scale(1)';
                noImagen.style.display = 'none';

                // Actualizar el label de zoom
                const zoomLabel = document.getElementById('zoomLabel');
                if (zoomLabel) {
                    zoomLabel.textContent = '100%';
                }
                
                // Highlight selected thumbnail
                document.querySelectorAll('#thumbnailContainer > div').forEach((el, i) => {
                    el.style.borderColor = i === index ? '#8b5cf6' : '#e5e7eb';
                });
            } else {
                this.imagenActual = null;
                imagenViewer.src = '';
                imagenViewer.style.display = 'none';
                noImagen.style.display = 'flex';
            }
        };

        selectImagenes.addEventListener('change', cambiarImagen);

        // Configurar controles de zoom
        this.configurarControlesZoom();

        cambiarImagen();
        imagenesSection.style.display = 'block';
    },
    configurarControlesZoom() {
        const btnZoomIn = document.getElementById('btnZoomIn');
        const btnZoomOut = document.getElementById('btnZoomOut');
        const btnZoomReset = document.getElementById('btnZoomReset');
        const btnDescargar = document.getElementById('btnDescargar');
        const imagenViewer = document.getElementById('imagenViewer');
        const zoomLabel = document.getElementById('zoomLabel');

        // Remover listeners anteriores
        const newBtnZoomIn = btnZoomIn?.cloneNode(true);
        const newBtnZoomOut = btnZoomOut?.cloneNode(true);
        const newBtnZoomReset = btnZoomReset?.cloneNode(true);
        const newBtnDescargar = btnDescargar?.cloneNode(true);

        if (btnZoomIn && newBtnZoomIn) {
            btnZoomIn.parentNode.replaceChild(newBtnZoomIn, btnZoomIn);
        }
        if (btnZoomOut && newBtnZoomOut) {
            btnZoomOut.parentNode.replaceChild(newBtnZoomOut, btnZoomOut);
        }
        if (btnZoomReset && newBtnZoomReset) {
            btnZoomReset.parentNode.replaceChild(newBtnZoomReset, btnZoomReset);
        }
        if (btnDescargar && newBtnDescargar) {
            btnDescargar.parentNode.replaceChild(newBtnDescargar, btnDescargar);
        }

        // Zoom In
        newBtnZoomIn?.addEventListener('click', () => {
            if (this.zoomLevel < 3) {
                this.zoomLevel += 0.25;
                this.aplicarZoom(imagenViewer, zoomLabel);
            }
        });

        // Zoom Out
        newBtnZoomOut?.addEventListener('click', () => {
            if (this.zoomLevel > 0.5) {
                this.zoomLevel -= 0.25;
                this.aplicarZoom(imagenViewer, zoomLabel);
            }
        });

        // Reset Zoom
        newBtnZoomReset?.addEventListener('click', () => {
            this.zoomLevel = 1;
            this.aplicarZoom(imagenViewer, zoomLabel);
        });

        // Ver imagen
        const btnVerImagen = document.getElementById('btnVerImagen');
        const newBtnVerImagen = btnVerImagen?.cloneNode(true);
        if (btnVerImagen && newBtnVerImagen) {
            btnVerImagen.parentNode.replaceChild(newBtnVerImagen, btnVerImagen);
        }

        newBtnVerImagen?.addEventListener('click', () => {
            this.verImagen();
        });

        // Descargar
        newBtnDescargar?.addEventListener('click', () => {
            this.descargarImagen();
        });
    },
    aplicarZoom(imagenViewer, zoomLabel) {
        if (!imagenViewer) return;

        // Obtener dimensiones originales de la imagen
        const anchoOriginal = imagenViewer.naturalWidth;
        const altoOriginal = imagenViewer.naturalHeight;

        if (this.zoomLevel === 1) {
            // Sin zoom: imagen responsiva
            imagenViewer.style.width = '';
            imagenViewer.style.height = '';
            imagenViewer.style.maxWidth = '100%';
            imagenViewer.classList.remove('with-zoom');
        } else {
            // Con zoom: tamaño fijo escalado
            imagenViewer.style.width = `${anchoOriginal * this.zoomLevel}px`;
            imagenViewer.style.height = `${altoOriginal * this.zoomLevel}px`;
            imagenViewer.style.maxWidth = 'none';
            imagenViewer.classList.add('with-zoom');
        }

        // Actualizar label
        if (zoomLabel) {
            zoomLabel.textContent = `${Math.round(this.zoomLevel * 100)}%`;
        }
    },
    descargarImagen() {
        if (!this.imagenActual || !this.imagenActual.imagen_base64) {
            mostrarAlerta('No hay imagen disponible para descargar', 'warning', "alertContainerPartidas");
            return;
        }

        try {
            const selectImagenes = document.getElementById('selectImagenes');
            const paginaActual = selectImagenes.options[selectImagenes.selectedIndex].textContent;
            const base64Data = this.imagenActual.imagen_base64;

            // Crear canvas temporal
            const img = new Image();
            img.onload = function () {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);

                // Descargar desde canvas
                canvas.toBlob(function (blob) {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `partida_${paginaActual.replace(/\s+/g, '_')}.jpg`;
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();

                    setTimeout(() => {
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(link);
                    }, 100);
                }, 'image/jpeg', 0.95);
            };
            img.src = `data:image/jpeg;base64,${base64Data}`;

            mostrarAlerta('Descargando imagen...', 'success', "alertContainerPartidas");

        } catch (error) {
            console.error('Error al descargar:', error);
            mostrarAlerta('Error al descargar la imagen', 'danger', "alertContainerPartidas");
        }
    },
    verImagen() {
        if (!this.imagenActual || !this.imagenActual.imagen_base64) {
            mostrarAlerta('No hay imagen disponible', 'warning', "alertContainerPartidas");
            return;
        }

        try {
            const base64Data = this.imagenActual.imagen_base64;

            // Crear una nueva ventana con el HTML de la imagen
            const nuevaVentana = window.open('', '_blank');
            nuevaVentana.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Imagen de Partida</title>
                    <style>
                        body { margin: 0; display: flex; justify-content: center; align-items: center; background: #333; }
                        img { max-width: 100%; height: auto; }
                    </style>
                </head>
                <body>
                    <img src="data:image/jpeg;base64,${base64Data}" alt="Imagen de partida">
                </body>
                </html>
            `);
            nuevaVentana.document.close();

            mostrarAlerta('Imagen abierta en nueva pestaña', 'success', "alertContainerPartidas");
        } catch (error) {
            console.error('Error al abrir imagen:', error);
            mostrarAlerta('Error al abrir la imagen', 'danger', "alertContainerPartidas");
        }
    },

    // Método adicional para descargar como PDF (requiere jsPDF)
    descargarImagenPDF() {
        if (!this.imagenActual || !this.imagenActual.imagen_base64) {
            alert('No hay imagen disponible para descargar');
            return;
        }

        // Verifica si jsPDF está disponible
        if (typeof window.jspdf === 'undefined') {
            console.error('jsPDF no está cargado. Descargando como imagen JPG.');
            this.descargarImagen();
            return;
        }

        const { jsPDF } = window.jspdf;
        const selectImagenes = document.getElementById('selectImagenes');
        const paginaActual = selectImagenes.options[selectImagenes.selectedIndex].textContent;
        const base64Data = this.imagenActual.imagen_base64;

        // Crear una imagen temporal para obtener dimensiones
        const img = new Image();
        img.onload = function () {
            const pdf = new jsPDF({
                orientation: img.width > img.height ? 'landscape' : 'portrait',
                unit: 'px',
                format: [img.width, img.height]
            });

            pdf.addImage(`data:image/jpeg;base64,${base64Data}`, 'JPEG', 0, 0, img.width, img.height);
            pdf.save(`partida_${paginaActual.replace(/\s+/g, '_')}.pdf`);
        };
        img.src = `data:image/jpeg;base64,${base64Data}`;
    },

    mostrarDatosVehiculo(datosVehiculo) {
        console.log(' datosVehiculo recibidos:', datosVehiculo);
        
        const vehiculoSection = document.getElementById('vehiculoSection');
        const vehiculoContainer = document.getElementById('vehiculoContainer');

        if (!datosVehiculo || Object.keys(datosVehiculo).length === 0) {
            console.log('No hay datos de vehículo');
            vehiculoSection.style.display = 'none';
            return;
        }

        const camposVehiculo = {
            'anoFabricacion': 'Año de Fabricación',
            'placa': 'Placa',
            'marca': 'Marca',
            'modelo': 'Modelo',
            'color': 'Color',
            'nro_motor': 'Número de Motor',
            'carroceria': 'Carroceria',
            'codCategoria': 'Código de Categoría',
            'codTipoCarr': 'Código de Tipo de Carro',
            'estado': 'Estado'
        };

        let html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';

        for (const [campo, label] of Object.entries(camposVehiculo)) {
            const valor = datosVehiculo[campo];
            if (valor !== undefined && valor !== null && valor !== '') {
                html += `
                    <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">${label}</span>
                        <div class="mt-1 text-lg font-semibold text-gray-800">${valor}</div>
                    </div>
                `;
            }
        }

        html += '</div>';

        vehiculoContainer.innerHTML = html;
        vehiculoSection.style.display = 'block';
        console.log('Sección de vehículo mostrada');
    },

    // ============================================
    // VERSIÓN ALTERNATIVA: RECONSTRUIR HTML
    // ============================================

    limpiarResultadosAnteriores() {

        // 1. Limpiar variables de estado
        this.partidasEncontradas = [];
        this.cacheDetalles = {};
        this.paginaActual = 1;
        this.partidaActualmenteMostrada = null;
        this.imagenActual = null;
        this.zoomLevel = 1;

        // 2. Limpiar y ocultar selector de partidas
        const selectorPartidas = document.getElementById('selectorPartidas');
        if (selectorPartidas) {
            selectorPartidas.remove(); // Remover completamente
        }

        // 3. Reconstruir resultsSection completamente
        const resultsSection = document.getElementById('resultsSection');
        if (resultsSection) {
            resultsSection.style.display = 'none';

            // Reconstruir HTML desde cero
            resultsSection.innerHTML = `
            <div class="results-layout">
                <!-- Foto (solo para personas naturales) -->
                <div class="photo-section">
                    <div class="photo-frame" id="photoSection">
                        <div class="no-photo">
                            <i class="fas fa-user"></i>
                            <span>Sin foto</span>
                        </div>
                    </div>
                </div>

                <!-- Información -->
                <div class="info-grid" id="infoGrid" style="display: none;">
                    <!-- Fila 1: Libro -->
                    <div class="info-item">
                        <span class="info-label">Libro</span>
                        <div class="info-value white-bg" id="libro">-</div>
                    </div>
                    <div class="info-item"></div>
                    <div class="info-item"></div>

                    <!-- Fila 2: Nombres -->
                    <div class="info-item full-width" id="containerNombres">
                        <span class="info-label">Nombres</span>
                        <div class="info-value white-bg" id="nombres">-</div>
                    </div>

                    <!-- Fila 3: Apellidos -->
                    <div class="info-item" id="containerApellidoPaterno">
                        <span class="info-label">Apellido Paterno</span>
                        <div class="info-value white-bg" id="apellidoPaterno">-</div>
                    </div>
                    <div class="info-item" id="containerApellidoMaterno">
                        <span class="info-label">Apellido Materno</span>
                        <div class="info-value white-bg" id="apellidoMaterno">-</div>
                    </div>
                    <div class="info-item"></div>

                    <!-- Fila 4: Razón Social -->
                    <div class="info-item full-width" id="containerRazonSocial" style="display: none;">
                        <span class="info-label">Razón Social</span>
                        <div class="info-value white-bg" id="campoRazonSocial">-</div>
                    </div>

                    <!-- Fila 5: Documento -->
                    <div class="info-item">
                        <span class="info-label">Tipo de Documento</span>
                        <div class="info-value white-bg" id="tipoDoc">-</div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nro. Documento</span>
                        <div class="info-value white-bg" id="nroDoc">-</div>
                    </div>
                    <div class="info-item"></div>

                    <!-- Fila 6: Partida y Placa -->
                    <div class="info-item">
                        <span class="info-label">Nro. Partida</span>
                        <div class="info-value white-bg" id="nroPartida">-</div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nro. Placa</span>
                        <div class="info-value white-bg" id="nroPlaca">-</div>
                    </div>
                    <div class="info-item"></div>

                    <!-- Fila 7: Estado y Zona -->
                    <div class="info-item">
                        <span class="info-label">Estado</span>
                        <div class="info-value white-bg" id="estado">-</div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Zona</span>
                        <div class="info-value white-bg" id="zona">-</div>
                    </div>
                    <div class="info-item"></div>

                    <!-- Fila 8: Oficina -->
                    <div class="info-item full-width">
                        <span class="info-label">Oficina</span>
                        <div class="info-value white-bg" id="oficina">-</div>
                    </div>

                    <!-- Fila 9: Dirección -->
                    <div class="info-item full-width">
                        <span class="info-label">Dirección</span>
                        <div class="info-value white-bg" id="direccion">-</div>
                    </div>
                </div>

                <!-- Sección de Imágenes de Documentos -->
                <div class="imagenes-section" id="imagenesSection" style="display: none;">
                    <div class="section-header">
                        <h3><i class="fas fa-images"></i> Imágenes de Documentos</h3>
                    </div>
                    
                    <div class="imagenes-viewer">
                        <div class="imagenes-selector">
                            <label for="selectImagenes"><strong>Seleccionar página:</strong></label>
                            <select id="selectImagenes" class="imagen-select"></select>
                        </div>
                        
                        <div class="image-controls">
                            <button type="button" id="btnZoomOut" class="btn-zoom" title="Alejar">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" id="btnZoomReset" class="btn-zoom" title="Restablecer zoom">
                                <i class="fas fa-undo"></i>
                            </button>
                            <button type="button" id="btnZoomIn" class="btn-zoom" title="Acercar">
                                <i class="fas fa-plus"></i>
                            </button>
                            <span id="zoomLabel" class="zoom-label">100%</span>
                            <button type="button" id="btnVerImagen" class="btn-view" title="Ver imagen en nueva pestaña">
                                <i class="fas fa-external-link-alt"></i>
                                <span>Ver</span>
                            </button>
                            <button type="button" id="btnDescargar" class="btn-download" title="Descargar imagen">
                                <i class="fas fa-download"></i>
                                <span>Descargar</span>
                            </button>
                        </div>
                        
                        <div class="imagen-container">
                            <div class="imagen-wrapper">
                                <img id="imagenViewer" src="" alt="Documento" style="display: none;">
                                <div class="no-imagen" id="noImagen">
                                    <i class="fas fa-image"></i>
                                    <span>Seleccione una página</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección de Datos Vehiculares -->
                <div class="vehiculo-section" id="vehiculoSection" style="display: none;">
                    <div class="section-header">
                        <h3><i class="fas fa-car"></i> Información Vehicular</h3>
                    </div>
                    <div id="vehiculoContainer" class="vehiculo-container"></div>
                </div>
            </div>
        `;
        }

    },

    // ============================================
    // VERSIÓN SIMPLIFICADA (SI LA ANTERIOR ES DEMASIADO)
    // ============================================
    limpiarResultadosAnteriorresSimple() {

        // Variables
        this.partidasEncontradas = [];
        this.cacheDetalles = {};
        this.paginaActual = 1;
        this.imagenActual = null;
        this.zoomLevel = 1;

        // Ocultar todo
        const resultsSection = document.getElementById('resultsSection');
        if (resultsSection) {
            resultsSection.style.display = 'none';
        }

        const selectorPartidas = document.getElementById('selectorPartidas');
        if (selectorPartidas) {
            selectorPartidas.style.display = 'none';
            selectorPartidas.innerHTML = '';
        }

        const infoGrid = document.getElementById('infoGrid');
        if (infoGrid) {
            infoGrid.style.display = 'none';
        }

        const imagenesSection = document.getElementById('imagenesSection');
        if (imagenesSection) {
            imagenesSection.style.display = 'none';
        }

        const vehiculoSection = document.getElementById('vehiculoSection');
        if (vehiculoSection) {
            vehiculoSection.style.display = 'none';
        }
    },

    // ============================================
    // 🧹 LIMPIAR FORMULARIO
    // ============================================
    // ============================================
    // ACTUALIZAR: limpiarFormularioPartidas
    // ============================================
    limpiarFormularioPartidas() {

        // 1. Limpiar estado
        this.personaSeleccionada = null;
        this.registrosEncontrados = [];

        // 2. Limpiar resultados anteriores
        this.limpiarResultadosAnteriores();

        // 3. Limpiar inputs
        document.getElementById('persona').value = '';
        document.getElementById('oficinaRegistralID').value = '';

        // 4. Limpiar alertas
        document.getElementById('alertContainerPartidas').innerHTML = '';

        // 5. Limpiar modales
        this.limpiarModalNatural();
        this.limpiarModalJuridica();
    },

    limpiarModalNatural() {
        document.getElementById('dniNatural').value = '';
        const resultados = document.getElementById('resultadosNatural');
        resultados.innerHTML = '';
        resultados.style.display = 'none';
    },

    limpiarModalJuridica() {
        document.getElementById('rucJuridica').value = '';
        document.getElementById('razonSocial').value = '';
        const resultados = document.getElementById('resultadosJuridica');
        resultados.innerHTML = '';
        resultados.style.display = 'none';
    },

    cambiarTipoBusquedaJuridica(e) {
        const tipoBusqueda = e.target.value;
        const grupoRuc = document.getElementById('grupoRuc');
        const grupoRazon = document.getElementById('grupoRazonSocial');

        if (tipoBusqueda === 'ruc') {
            grupoRuc.style.display = 'flex';
            grupoRazon.style.display = 'none';
            document.getElementById('razonSocial').value = '';
        } else {
            grupoRuc.style.display = 'none';
            grupoRazon.style.display = 'flex';
            document.getElementById('rucJuridica').value = '';
        }

        this.limpiarModalJuridica();
    },

    mostrarLoadingPartidas(formId) {
        const form = document.getElementById(formId);
        const submitBtn = form?.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading-spinner"></span> <span>Buscando...</span>';
        }
    },

    ocultarLoadingPartidas(formId) {
        const form = document.getElementById(formId);
        const submitBtn = form?.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-search"></i> <span>Buscar</span>';
        }
    },


};

// ============================================
// 🌐 FUNCIONES GLOBALES
// ============================================
// Exponer funciones globales
window.ModuloPartidas = ModuloPartidas;

window.limpiarFormularioPartidas = function () {
    if (ModuloPartidas.inicializado) {
        ModuloPartidas.limpiarFormularioPartidas();
    }
};

window.limpiarModalNatural = function () {
    document.getElementById('dniNatural').value = '';
    document.getElementById('resultadosNatural').innerHTML = '';
    document.getElementById('resultadosNatural').style.display = 'none';
};

window.limpiarModalJuridica = function () {
    document.getElementById('rucJuridica').value = '';
    document.getElementById('razonSocial').value = '';
    document.getElementById('resultadosJuridica').innerHTML = '';
    document.getElementById('resultadosJuridica').style.display = 'none';
};

if (typeof window.registrarModulo === 'function') {
    window.registrarModulo('consultaspartidas', ModuloPartidas);
}

// Auto-inicializar cuando se cargue el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // No auto-inicializar, esperar a que Dashboard lo llame
    });
}