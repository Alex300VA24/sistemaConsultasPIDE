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
        this.partidaActualmenteMostrada = 0;
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

            const resultsSection = document.getElementById('resultsSection');
            resultsSection.parentNode.insertBefore(selectorPartidas, resultsSection);
        }

        const totalPartidas = this.partidasEncontradas.length;
        const totalPaginas = Math.ceil(totalPartidas / this.partidasPorPagina);
        const inicio = (this.paginaActual - 1) * this.partidasPorPagina;
        const fin = Math.min(inicio + this.partidasPorPagina, totalPartidas);
        const partidasPagina = this.partidasEncontradas.slice(inicio, fin);

        const indiceSeleccionado = Number.isInteger(this.partidaActualmenteMostrada)
            ? this.partidaActualmenteMostrada
            : 0;

        let html = `
            <div class="glass rounded-2xl p-6 shadow-lg border border-white/50 mb-6" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); border: 1px solid rgba(255, 255, 255, 0.5); margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 700; color: #1f2937; display: flex; align-items: center; gap: 0.5rem; margin: 0 0 0.5rem 0;">
                            <i class="fas fa-list" style="color: #8b5cf6;"></i>
                            Partidas Registradas
                        </h3>
                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Mostrando ${inicio + 1} - ${fin} de ${totalPartidas} partida(s)</p>
                    </div>
                    ${totalPartidas > 1 ? '<div style="padding: 0.5rem 1rem; background: #f3e8ff; color: #7c3aed; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem;">' + totalPartidas + ' resultados</div>' : ''}
                </div>
                
                ${totalPaginas > 1 ? this.generarControlesPaginacion(totalPaginas) : ''}
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin-top: 1rem;">
        `;

        partidasPagina.forEach((partida, indexEnPagina) => {
            const indexGlobal = inicio + indexEnPagina;
            const partidaNumero = partida.numero_partida || 'S/N';
            const estado = partida.estado || 'Sin estado';
            const oficina = partida.oficina || 'Sin oficina';
            const libro = partida.libro || '-';
            const estadoUpper = estado.toUpperCase();
            const esActiva = estadoUpper === 'ACTIVA' || estadoUpper === 'VIGENTE';
            const esSeleccionada = indexGlobal === indiceSeleccionado;

            const estadoBadge = esActiva 
                ? '<span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; background: #d1fae5; color: #065f46; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;"><i class="fas fa-check-circle"></i> ' + estado + '</span>'
                : '<span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; background: #fee2e2; color: #991b1b; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;"><i class="fas fa-times-circle"></i> ' + estado + '</span>';

            html += `
                <label style="cursor: pointer; display: block;">
                    <input type="radio" name="partidaSeleccionada" value="${indexGlobal}" 
                           ${esSeleccionada ? 'checked' : ''}
                           onchange="ModuloPartidas.cambiarPartida(${indexGlobal})"
                           style="position: absolute; opacity: 0; width: 0; height: 0;">
                    <div data-partida-card="${indexGlobal}" style="height: 100%; padding: 1rem; border-radius: 0.75rem; border: 2px solid ${esSeleccionada ? '#8b5cf6' : '#e5e7eb'}; background: ${esSeleccionada ? '#f5f3ff' : 'rgba(255, 255, 255, 0.8)'}; transition: all 0.2s ease;">
                        <div style="display: flex; align-items: start; justify-content: space-between; margin-bottom: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: #8b5cf6; font-weight: 700; font-size: 1.125rem;">
                                <i class="fas fa-file-contract"></i>
                                <span style="color: #1f2937;">${partidaNumero}</span>
                            </div>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.875rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: #6b7280;">
                                <i class="fas fa-book" style="color: #8b5cf6; width: 1rem;"></i>
                                <span style="font-weight: 500;">Libro:</span>
                                <span style="color: #1f2937;">${libro}</span>
                            </div>
                            
                            <div style="display: flex; align-items: start; gap: 0.5rem; color: #6b7280;">
                                <i class="fas fa-building" style="color: #8b5cf6; width: 1rem; margin-top: 0.125rem;"></i>
                                <div style="flex: 1;">
                                    <span style="font-weight: 500; display: block;">Oficina:</span>
                                    <span style="color: #374151; font-size: 0.75rem; line-height: 1.25;">${oficina}</span>
                                </div>
                            </div>
                            
                            <div style="padding-top: 0.5rem; border-top: 1px solid #e5e7eb;">
                                ${estadoBadge}
                            </div>
                        </div>
                    </div>
                </label>
            `;
        });

        html += `
                </div>
            </div>
        `;

        selectorPartidas.innerHTML = html;
        selectorPartidas.style.display = 'block';

        // Efecto hover en tarjetas no seleccionadas
        selectorPartidas.querySelectorAll('[data-partida-card]').forEach(card => {
            const index = Number(card.getAttribute('data-partida-card'));
            card.addEventListener('mouseenter', () => {
                if (index !== this.partidaActualmenteMostrada) {
                    card.style.border = '2px solid #c4b5fd';
                    card.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.1)';
                }
            });
            card.addEventListener('mouseleave', () => {
                if (index !== this.partidaActualmenteMostrada) {
                    card.style.border = '2px solid #e5e7eb';
                    card.style.boxShadow = 'none';
                }
            });
        });
        
        console.log(`✅ Selector de partidas mostrado con ${partidasPagina.length} partida(s)`);
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

        let html = '<div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; flex-wrap: wrap;">';

        // Botón Anterior
        const btnAnteriorDisabled = this.paginaActual === 1;
        html += `
            <button style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 500; transition: all 0.3s; ${btnAnteriorDisabled ? 'background: #e5e7eb; color: #9ca3af; cursor: not-allowed;' : 'background: white; color: #374151; border: 1px solid #d1d5db;'}" 
                    onclick="ModuloPartidas.cambiarPagina(${this.paginaActual - 1})"
                    ${btnAnteriorDisabled ? 'disabled' : ''}
                    onmouseover="if(!this.disabled) { this.style.background='#f5f3ff'; this.style.color='#7c3aed'; }"
                    onmouseout="if(!this.disabled) { this.style.background='white'; this.style.color='#374151'; }">
                <i class="fas fa-chevron-left" style="margin-right: 0.25rem;"></i> Anterior
            </button>
        `;

        // Primera página
        if (inicio > 1) {
            html += `<button style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; font-weight: 600; transition: all 0.3s; background: white; color: #374151; border: 1px solid #d1d5db;" 
                onclick="ModuloPartidas.cambiarPagina(1)"
                onmouseover="this.style.background='#f5f3ff'; this.style.color='#7c3aed';"
                onmouseout="this.style.background='white'; this.style.color='#374151';">1</button>`;
            if (inicio > 2) {
                html += '<span style="padding: 0 0.5rem; color: #9ca3af;">...</span>';
            }
        }

        // Páginas numeradas
        for (let i = inicio; i <= fin; i++) {
            const esActual = i === this.paginaActual;
            html += `
                <button style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; font-weight: 600; transition: all 0.3s; ${esActual ? 'background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; box-shadow: 0 4px 6px rgba(139, 92, 246, 0.4);' : 'background: white; color: #374151; border: 1px solid #d1d5db;'}"
                        onclick="ModuloPartidas.cambiarPagina(${i})"
                        ${!esActual ? 'onmouseover="this.style.background=\'#f5f3ff\'; this.style.color=\'#7c3aed\';" onmouseout="this.style.background=\'white\'; this.style.color=\'#374151\';"' : ''}>
                    ${i}
                </button>
            `;
        }

        // Última página
        if (fin < totalPaginas) {
            if (fin < totalPaginas - 1) {
                html += '<span style="padding: 0 0.5rem; color: #9ca3af;">...</span>';
            }
            html += `<button style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; font-weight: 600; transition: all 0.3s; background: white; color: #374151; border: 1px solid #d1d5db;" 
                onclick="ModuloPartidas.cambiarPagina(${totalPaginas})"
                onmouseover="this.style.background='#f5f3ff'; this.style.color='#7c3aed';"
                onmouseout="this.style.background='white'; this.style.color='#374151';">${totalPaginas}</button>`;
        }

        // Botón Siguiente
        const btnSiguienteDisabled = this.paginaActual === totalPaginas;
        html += `
            <button style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 500; transition: all 0.3s; ${btnSiguienteDisabled ? 'background: #e5e7eb; color: #9ca3af; cursor: not-allowed;' : 'background: white; color: #374151; border: 1px solid #d1d5db;'}"
                    onclick="ModuloPartidas.cambiarPagina(${this.paginaActual + 1})"
                    ${btnSiguienteDisabled ? 'disabled' : ''}
                    onmouseover="if(!this.disabled) { this.style.background='#f5f3ff'; this.style.color='#7c3aed'; }"
                    onmouseout="if(!this.disabled) { this.style.background='white'; this.style.color='#374151'; }">
                Siguiente <i class="fas fa-chevron-right" style="margin-left: 0.25rem;"></i>
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

        // Auto-seleccionar la primera partida de la nueva página
        const inicio = (this.paginaActual - 1) * this.partidasPorPagina;
        const primerIndice = inicio;
        const primeraPartida = this.partidasEncontradas[primerIndice];
        if (primeraPartida) {
            this.partidaActualmenteMostrada = primerIndice;
            this.mostrarSelectorPartidasPaginado();
            this.cargarYMostrarDetalle(primeraPartida);
        } else {
            this.mostrarSelectorPartidasPaginado();
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
            this.partidaActualmenteMostrada = index;
            this.actualizarEstilosTarjetaPartida(index);
            this.cargarYMostrarDetalle(this.partidasEncontradas[index]);
        }
    },

    actualizarEstilosTarjetaPartida(indexSeleccionado) {
        document.querySelectorAll('[data-partida-card]').forEach(card => {
            const indexCard = Number(card.getAttribute('data-partida-card'));
            if (indexCard === indexSeleccionado) {
                card.style.border = '2px solid #8b5cf6';
                card.style.background = '#f5f3ff';
                card.style.boxShadow = '0 10px 25px rgba(124, 58, 237, 0.15)';
            } else {
                card.style.border = '2px solid #e5e7eb';
                card.style.background = 'rgba(255, 255, 255, 0.8)';
                card.style.boxShadow = 'none';
            }
        });
    },

    // ============================================
    // CARGAR Y MOSTRAR DETALLE (NUEVO)
    // ============================================
    async cargarYMostrarDetalle(partida) {
        const numeroPartida = partida.numero_partida;
        const indexPartida = this.partidasEncontradas.findIndex(p => p.numero_partida === numeroPartida);
        if (indexPartida >= 0) {
            this.partidaActualmenteMostrada = indexPartida;
            this.actualizarEstilosTarjetaPartida(indexPartida);
        }

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
    // GENERAR ESTILOS ADICIONALES (NO TAILWIND)
    // ============================================
    generarEstilosPaginacion() {
        return `
            <style>
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
                    border-top: 4px solid #8b5cf6;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin-bottom: 20px;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `;
    },

    // ============================================
    // CONSULTAR LASIRSARP
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
        console.log('📋 Mostrando detalle de partida:', registro);

        const data = registro || {};
        const esNatural = this.tipoPersonaActual === 'natural';
        const esPartida = this.tipoPersonaActual === 'partida';
        const esJuridica = this.tipoPersonaActual === 'juridica';

        const resultsSection = document.getElementById('resultsSection');
        const infoGrid = document.getElementById('infoGrid');
        if (resultsSection) resultsSection.style.display = 'block';
        if (infoGrid) infoGrid.style.display = 'grid';
        this.ajustarLayoutInfoRegistro(esNatural);

        // Resetear visibilidad base de los contenedores de información
        const contenedores = [
            'containerNombres', 'containerApellidoPaterno',
            'containerApellidoMaterno', 'containerRazonSocial',
            'containerNroPartida', 'containerNroPlaca', 'containerEstado',
            'containerZona', 'containerOficina', 'containerDireccion', 'containerLibro'
        ];
        contenedores.forEach(id => {
            const elem = document.getElementById(id);
            if (elem) elem.style.display = '';
        });

        const photoSection = document.getElementById('photoSection');

        if (esNatural) {
            if (photoSection) {
                photoSection.style.display = 'block';
            }
            this.mostrarCampo('nombres', data.nombre || this.personaSeleccionada?.nombres || '-', 'containerNombres');
            this.mostrarCampo('apellidoPaterno', data.apPaterno || this.personaSeleccionada?.apellido_paterno || '-', 'containerApellidoPaterno');
            this.mostrarCampo('apellidoMaterno', data.apMaterno || this.personaSeleccionada?.apellido_materno || '-', 'containerApellidoMaterno');
            this.ocultarCampo('campoRazonSocial', 'containerRazonSocial');
            this.mostrarFotoPersona();
        } else if (esJuridica) {
            if (photoSection) {
                photoSection.style.display = 'none';
            }
            const tieneNombres = data.nombre || data.apPaterno || data.apMaterno;
            if (tieneNombres) {
                this.mostrarCampo('nombres', data.nombre || '-', 'containerNombres');
                this.mostrarCampo('apellidoPaterno', data.apPaterno || '-', 'containerApellidoPaterno');
                this.mostrarCampo('apellidoMaterno', data.apMaterno || '-', 'containerApellidoMaterno');
            } else {
                this.ocultarCampo('nombres', 'containerNombres');
                this.ocultarCampo('apellidoPaterno', 'containerApellidoPaterno');
                this.ocultarCampo('apellidoMaterno', 'containerApellidoMaterno');
            }
            const razonSocial = data.razon_social || this.personaSeleccionada?.razon_social || '-';
            this.mostrarCampo('campoRazonSocial', razonSocial, 'containerRazonSocial');
        } else {
            // Búsqueda por partida: solo usar información registral, sin bloque de persona/foto
            if (photoSection) {
                photoSection.style.display = 'none';
            }
            this.ocultarCampo('nombres', 'containerNombres');
            this.ocultarCampo('apellidoPaterno', 'containerApellidoPaterno');
            this.ocultarCampo('apellidoMaterno', 'containerApellidoMaterno');
            this.ocultarCampo('campoRazonSocial', 'containerRazonSocial');
            this.ocultarCampo('tipoDoc');
            this.ocultarCampo('nroDoc');
        }

        if (!esPartida) {
            this.mostrarCampo('tipoDoc', data.tipo_documento || (esNatural ? 'DNI' : 'RUC'));
            this.mostrarCampo('nroDoc', data.numero_documento || (esNatural ? this.personaSeleccionada?.dni : this.personaSeleccionada?.ruc) || '-');
        } else {
            const tipoDocWrap = document.getElementById('tipoDoc')?.closest('div');
            const nroDocWrap = document.getElementById('nroDoc')?.closest('div');
            if (tipoDocWrap) tipoDocWrap.style.display = 'none';
            if (nroDocWrap) nroDocWrap.style.display = 'none';
        }

        this.mostrarCampo('nroPartida', data.numero_partida || '-', 'containerNroPartida');
        this.mostrarCampo('nroPlaca', data.numero_placa || '-', 'containerNroPlaca');
        this.mostrarCampo('estado', data.estado || '-', 'containerEstado');
        this.mostrarCampo('zona', data.zona || '-', 'containerZona');
        this.mostrarCampo('libro', data.libro || '-', 'containerLibro');
        this.mostrarCampo('oficina', data.oficina || '-', 'containerOficina');
        this.mostrarCampo('direccion', data.direccion || '-', 'containerDireccion');

        const imagenesSection = document.getElementById('imagenesSection');
        const vehiculoSection = document.getElementById('vehiculoSection');

        const imagenes = Array.isArray(data.imagenes)
            ? data.imagenes
            : Object.values(data.imagenes || {});

        if (imagenes.length > 0) {
            this.mostrarImagenes(imagenes);
        } else {
            if (imagenesSection) imagenesSection.style.display = 'none';
        }

        const datosVehiculo = data.datos_vehiculo && typeof data.datos_vehiculo === 'object'
            ? data.datos_vehiculo
            : {};

        if (Object.keys(datosVehiculo).length > 0) {
            this.mostrarDatosVehiculo(datosVehiculo);
        } else {
            if (vehiculoSection) vehiculoSection.style.display = 'none';
        }
    },

    ajustarLayoutInfoRegistro(mostrarFoto) {
        const photoSection = document.getElementById('photoSection');
        const filaPrincipal = photoSection?.parentElement;
        const infoWrapper = document.getElementById('infoGrid')?.parentElement;

        if (filaPrincipal) {
            filaPrincipal.style.display = 'grid';
            filaPrincipal.style.gap = '1.5rem';
            filaPrincipal.style.gridTemplateColumns = mostrarFoto ? '300px minmax(0, 1fr)' : 'minmax(0, 1fr)';
        }

        if (infoWrapper) {
            infoWrapper.style.width = '100%';
            infoWrapper.style.maxWidth = '100%';
            infoWrapper.style.minWidth = '0';
        }

        const infoGrid = document.getElementById('infoGrid');
        if (infoGrid) {
            infoGrid.style.width = '100%';
            infoGrid.style.maxWidth = '100%';
            infoGrid.style.gridTemplateColumns = 'repeat(auto-fit, minmax(220px, 1fr))';
        }
    },

    mostrarFotoPersona() {
        const photoSection = document.getElementById('photoSection');
        if (!photoSection) {
            console.warn('⚠️ photoSection no encontrado');
            return;
        }

        // Buscar el contenedor de la foto
        let fotoContainer = document.getElementById('fotoContainer');
        if (!fotoContainer) {
            console.error('❌ fotoContainer no encontrado');
            return;
        }

        // Limpiar contenido anterior
        fotoContainer.innerHTML = '';

        if (this.personaSeleccionada && this.personaSeleccionada.foto) {
            const fotoBase64 = this.personaSeleccionada.foto.startsWith('data:image')
                ? this.personaSeleccionada.foto
                : `data:image/jpeg;base64,${this.personaSeleccionada.foto}`;

            const img = document.createElement('img');
            img.src = fotoBase64;
            img.alt = "Foto de persona";
            img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; display: block;';
            
            fotoContainer.appendChild(img);
            photoSection.style.display = 'block';
            
            console.log('✅ Foto de persona mostrada');
        } else {
            const placeholder = document.createElement('div');
            placeholder.style.cssText = 'text-align: center; color: #9ca3af; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;';
            placeholder.innerHTML = `
                <i class="fas fa-user" style="font-size: 4rem; margin-bottom: 0.75rem; display: block; opacity: 0.5;"></i>
                <p style="font-size: 0.875rem; margin: 0;">Sin fotografía</p>
            `;
            fotoContainer.appendChild(placeholder);
            photoSection.style.display = 'block';
            
            console.log('⚠️ No hay foto disponible, mostrando placeholder');
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
        const listaImagenes = (Array.isArray(imagenes) ? imagenes : Object.values(imagenes || {}))
            .filter(img => img && img.imagen_base64);

        console.log('🖼️ Iniciando mostrarImagenes con', listaImagenes.length, 'imagen(es)');

        const imagenesSection = document.getElementById('imagenesSection');
        const selectImagenes = document.getElementById('selectImagenes');
        const imagenViewer = document.getElementById('imagenViewer');
        const noImagen = document.getElementById('noImagen');
        const thumbnailContainer = document.getElementById('thumbnailContainer');

        if (!imagenesSection) {
            console.error('❌ imagenesSection no encontrado');
            return;
        }
        
        if (!selectImagenes) {
            console.error('❌ selectImagenes no encontrado');
            return;
        }
        
        if (!imagenViewer) {
            console.error('❌ imagenViewer no encontrado');
            return;
        }
        
        if (!noImagen) {
            console.error('❌ noImagen no encontrado');
            return;
        }
        
        if (!listaImagenes.length) {
            this.imagenActual = null;
            if (imagenesSection) imagenesSection.style.display = 'none';
            return;
        }

        const imageViewerContainer = document.getElementById('imageViewerContainer');
        if (imageViewerContainer) {
            imageViewerContainer.style.alignItems = 'flex-start';
            imageViewerContainer.style.justifyContent = 'flex-start';
            imageViewerContainer.style.minWidth = 'max-content';
            imageViewerContainer.style.minHeight = 'max-content';
        }

        // Resetear zoom al cambiar de partida
        this.zoomLevel = 1;
        imagenViewer.style.transform = 'none';
        imagenViewer.style.maxWidth = 'none';
        imagenViewer.style.maxHeight = 'none';
        imagenViewer.style.width = 'auto';
        imagenViewer.style.height = 'auto';

        // Limpiar selects y miniaturas anteriores
        selectImagenes.innerHTML = '';
        if (thumbnailContainer) {
            thumbnailContainer.innerHTML = '';
            thumbnailContainer.style.display = 'none';
        }

        console.log('🔄 Generando opciones del select y miniaturas...');

        // Llenar el select y generar miniaturas
        listaImagenes.forEach((img, index) => {
            // Opción en el select
            const option = document.createElement('option');
            option.value = index;
            option.textContent = `Página ${img.pagina || (index + 1)}`;
            selectImagenes.appendChild(option);
            
            console.log(`  ✓ Opción ${index + 1} agregada al select`);

            // Miniatura
            if (img.imagen_base64) {
                const thumbnailDiv = document.createElement('div');
                thumbnailDiv.style.cssText = `
                    flex-shrink: 0;
                    cursor: pointer;
                    border-radius: 0.5rem;
                    overflow: hidden;
                    border: 3px solid #e5e7eb;
                    width: 90px;
                    height: 110px;
                    background-color: #fff;
                    transition: all 0.2s ease;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                `;
                thumbnailDiv.setAttribute('data-index', index);
                
                const img_el = document.createElement('img');
                img_el.src = `data:image/jpeg;base64,${img.imagen_base64}`;
                img_el.alt = `Página ${index + 1}`;
                img_el.style.cssText = 'width: 100%; height: 100%; object-fit: cover; display: block;';
                
                thumbnailDiv.appendChild(img_el);
                
                // Evento click en miniatura
                thumbnailDiv.addEventListener('click', () => {
                    console.log(`🖱️ Click en miniatura ${index}`);
                    selectImagenes.value = index;
                    cambiarImagen();
                });
                
                // Efectos hover
                thumbnailDiv.addEventListener('mouseenter', function() {
                    if (this.style.borderColor !== 'rgb(139, 92, 246)') {
                        this.style.transform = 'scale(1.05)';
                        this.style.boxShadow = '0 8px 16px rgba(0,0,0,0.2)';
                    }
                });
                
                thumbnailDiv.addEventListener('mouseleave', function() {
                    if (this.style.borderColor !== 'rgb(139, 92, 246)') {
                        this.style.transform = 'scale(1)';
                        this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                    }
                });
                
                if (thumbnailContainer) {
                    thumbnailContainer.appendChild(thumbnailDiv);
                }
                console.log(`  ✓ Miniatura ${index + 1} agregada`);
            }
        });

        // Mostrar miniaturas si hay más de 1 página
        if (thumbnailContainer && listaImagenes.length > 1) {
            thumbnailContainer.style.display = 'flex';
            console.log('✅ Miniaturas visibles (múltiples páginas)');
        } else {
            console.log('ℹ️ Solo una página, miniaturas ocultas');
        }

        const cambiarImagen = () => {
            const index = parseInt(selectImagenes.value);
            const imagenData = listaImagenes[index];
            
            console.log(`🔄 Cambiando a imagen ${index + 1}`);

            // Resetear zoom al cambiar de imagen
            this.zoomLevel = 1;

            if (imagenData && imagenData.imagen_base64) {
                this.imagenActual = imagenData;
                imagenViewer.src = `data:image/jpeg;base64,${imagenData.imagen_base64}`;
                imagenViewer.style.display = 'block';
                imagenViewer.style.transform = 'none';
                imagenViewer.style.maxWidth = 'none';
                imagenViewer.style.maxHeight = 'none';
                noImagen.style.display = 'none';

                imagenViewer.onload = () => {
                    imagenViewer.dataset.naturalWidth = String(imagenViewer.naturalWidth || 0);
                    imagenViewer.dataset.naturalHeight = String(imagenViewer.naturalHeight || 0);
                    this.aplicarZoom(imagenViewer, document.getElementById('zoomLabel'));
                };

                // Actualizar el label de zoom
                const zoomLabel = document.getElementById('zoomLabel');
                if (zoomLabel) {
                    zoomLabel.textContent = '100%';
                }
                
                // Highlight selected thumbnail
                document.querySelectorAll('#thumbnailContainer > div').forEach((el, i) => {
                    if (i === index) {
                        el.style.borderColor = '#8b5cf6';
                        el.style.boxShadow = '0 4px 12px rgba(139, 92, 246, 0.4)';
                        el.style.transform = 'scale(1)';
                    } else {
                        el.style.borderColor = '#e5e7eb';
                        el.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                        el.style.transform = 'scale(1)';
                    }
                });
                
                console.log(`✅ Imagen ${index + 1} mostrada correctamente`);
            } else {
                this.imagenActual = null;
                imagenViewer.src = '';
                imagenViewer.style.display = 'none';
                noImagen.style.display = 'flex';
                console.warn('⚠️ No hay datos de imagen para mostrar');
            }
        };

        // Reemplazar listener previo de forma segura
        selectImagenes.onchange = () => {
            console.log('📝 Select cambiado');
            cambiarImagen();
        };

        console.log('✅ Event listener del select configurado');

        // Configurar controles de zoom
        this.configurarControlesZoom();

        // Mostrar primera imagen
        cambiarImagen();
        
        // Mostrar sección
        imagenesSection.style.display = 'block';
        
        console.log(`✅ Visor de imágenes configurado y visible con ${listaImagenes.length} página(s)`);
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

        const anchoNatural = parseFloat(imagenViewer.dataset.naturalWidth || imagenViewer.naturalWidth || 0);
        const altoNatural = parseFloat(imagenViewer.dataset.naturalHeight || imagenViewer.naturalHeight || 0);

        if (anchoNatural > 0 && altoNatural > 0) {
            imagenViewer.style.width = `${Math.round(anchoNatural * this.zoomLevel)}px`;
            imagenViewer.style.height = `${Math.round(altoNatural * this.zoomLevel)}px`;
            imagenViewer.style.maxWidth = 'none';
            imagenViewer.style.maxHeight = 'none';
            imagenViewer.style.transform = 'none';
            imagenViewer.style.transition = 'width 0.2s ease, height 0.2s ease';
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
        console.log('📋 datosVehiculo recibidos:', datosVehiculo);
        
        const vehiculoSection = document.getElementById('vehiculoSection');
        const vehiculoContainer = document.getElementById('vehiculoContainer');

        if (!datosVehiculo || Object.keys(datosVehiculo).length === 0) {
            console.log('⚠️ No hay datos de vehículo');
            if (vehiculoSection) vehiculoSection.style.display = 'none';
            return;
        }

        const camposVehiculo = {
            'placa': { label: 'Placa', icon: 'fa-id-card' },
            'marca': { label: 'Marca', icon: 'fa-copyright' },
            'modelo': { label: 'Modelo', icon: 'fa-car-side' },
            'anoFabricacion': { label: 'Año de Fabricación', icon: 'fa-calendar' },
            'color': { label: 'Color', icon: 'fa-palette' },
            'nro_motor': { label: 'Número de Motor', icon: 'fa-cog' },
            'carroceria': { label: 'Carrocería', icon: 'fa-truck' },
            'codCategoria': { label: 'Código de Categoría', icon: 'fa-tag' },
            'codTipoCarr': { label: 'Código de Tipo', icon: 'fa-barcode' },
            'estado': { label: 'Estado', icon: 'fa-info-circle' }
        };

        if (vehiculoContainer) {
            vehiculoContainer.style.display = 'grid';
            vehiculoContainer.style.gridTemplateColumns = 'repeat(auto-fit, minmax(220px, 1fr))';
            vehiculoContainer.style.gap = '1rem';
            vehiculoContainer.style.width = '100%';
            vehiculoContainer.style.alignItems = 'stretch';
        }

        let html = '';

        for (const [campo, config] of Object.entries(camposVehiculo)) {
            const valor = datosVehiculo[campo];
            if (valor !== undefined && valor !== null && valor !== '') {
                const esPlaca = campo === 'placa';
                const esEstado = campo === 'estado';
                
                html += `
                    <div class="bg-white/80 rounded-xl p-4 border border-gray-200 hover:shadow-lg transition-all duration-200" style="${esPlaca ? 'grid-column: 1 / -1;' : ''}">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center">
                                <i class="fas ${config.icon} text-violet-600 text-sm"></i>
                            </div>
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">${config.label}</span>
                        </div>
                        <div class="text-lg font-bold ${esPlaca ? 'text-violet-700 text-2xl' : esEstado ? 'text-emerald-700' : 'text-gray-800'} pl-10">
                            ${esPlaca ? valor.toUpperCase() : valor}
                        </div>
                    </div>
                `;
            }
        }

        if (vehiculoContainer) {
            vehiculoContainer.innerHTML = html;
        }
        if (vehiculoSection) {
            vehiculoSection.style.display = 'block';
        }
        console.log('✅ Sección de vehículo mostrada con estilo mejorado');
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

        // 3. Limpiar secciones de resultados sin reconstruir HTML
        const resultsSection = document.getElementById('resultsSection');
        if (resultsSection) {
            resultsSection.style.display = 'none';
        }

        const infoGrid = document.getElementById('infoGrid');
        if (infoGrid) {
            infoGrid.style.display = 'none';
        }

        const photoSection = document.getElementById('photoSection');
        if (photoSection) {
            photoSection.style.display = 'none';
        }

        const fotoContainer = document.getElementById('fotoContainer');
        if (fotoContainer) {
            fotoContainer.innerHTML = `
                <div style="text-align: center; color: #9ca3af;">
                    <i class="fas fa-user" style="font-size: 4rem; margin-bottom: 0.75rem; display: block; opacity: 0.5;"></i>
                    <p style="font-size: 0.875rem; margin: 0;">Sin fotografía</p>
                </div>
            `;
        }

        ['nombres', 'apellidoPaterno', 'apellidoMaterno', 'campoRazonSocial', 'tipoDoc', 'nroDoc',
            'nroPartida', 'nroPlaca', 'estado', 'zona', 'libro', 'oficina', 'direccion'].forEach(id => {
            const campo = document.getElementById(id);
            if (campo) campo.textContent = '-';
        });

        const contenedores = ['containerNombres', 'containerApellidoPaterno', 'containerApellidoMaterno',
            'containerRazonSocial', 'containerNroPartida', 'containerNroPlaca', 'containerEstado',
            'containerZona', 'containerLibro', 'containerOficina', 'containerDireccion'];
        contenedores.forEach(id => {
            const elem = document.getElementById(id);
            if (elem) elem.style.display = '';
        });

        const imagenesSection = document.getElementById('imagenesSection');
        if (imagenesSection) {
            imagenesSection.style.display = 'none';
        }

        const selectImagenes = document.getElementById('selectImagenes');
        if (selectImagenes) {
            selectImagenes.innerHTML = '';
            selectImagenes.onchange = null;
        }

        const imagenViewer = document.getElementById('imagenViewer');
        if (imagenViewer) {
            imagenViewer.src = '';
            imagenViewer.style.display = 'none';
            imagenViewer.style.transform = 'scale(1)';
        }

        const noImagen = document.getElementById('noImagen');
        if (noImagen) {
            noImagen.style.display = 'flex';
        }

        const thumbnailContainer = document.getElementById('thumbnailContainer');
        if (thumbnailContainer) {
            thumbnailContainer.innerHTML = '';
            thumbnailContainer.style.display = 'none';
        }

        const vehiculoSection = document.getElementById('vehiculoSection');
        if (vehiculoSection) {
            vehiculoSection.style.display = 'none';
        }

        const vehiculoContainer = document.getElementById('vehiculoContainer');
        if (vehiculoContainer) {
            vehiculoContainer.innerHTML = '';
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
