// ============================================
// MÓDULO DE CONSULTA PARTIDAS REGISTRALES
// ============================================

const ModuloPartidas = {
    // Estado del módulo
    inicializado: false,
    personaSeleccionada: null,
    tipoPersonaActual: 'natural',
    registrosEncontrados: [],
    partidasEncontradas: [],
    credencialesUsuario: {
        dni: '',
        password: ''
    },

    // ============================================
    // INICIALIZACIÓN
    // ============================================
    async init() {
        if (this.inicializado) {
            return;
        }
        
        await this.cargarCredencialesUsuario();
        this.setupEventListeners();
        
        this.inicializado = true;
        this.zoomLevel = 1;
        this.imagenActual = null;
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
        document.getElementById('btnConsultar')?.addEventListener('click', () => this.consultarTSIRSARP());
        document.getElementById('btnLimpiar')?.addEventListener('click', () => this.limpiarFormularioPartidas());

        // Forms de búsqueda
        document.getElementById('formBusquedaNatural')?.addEventListener('submit', (e) => this.buscarPersonaNatural(e));
        document.getElementById('formBusquedaJuridica')?.addEventListener('submit', (e) => this.buscarPersonaJuridica(e));

        // Radio buttons búsqueda jurídica
        document.querySelectorAll('input[name="tipoBusquedaJuridica"]').forEach(radio => {
            radio.addEventListener('change', (e) => this.cambiarTipoBusquedaJuridica(e));
        });

        // Cerrar modales al hacer clic fuera
        document.querySelectorAll('.modal-partidas').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.cerrarModal(modal.id);
                }
            });
        });

        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modalId = btn.getAttribute('data-modal');
                this.cerrarModal(modalId);
            });
        });


        // Validación solo números
        document.getElementById('dniNatural')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        document.getElementById('rucJuridica')?.addEventListener('input', function(e) {
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
        if (labelPersona) {
            labelPersona.textContent = this.tipoPersonaActual === 'natural' ? 'Persona:' : 'Razón Social:';
        }
    },

    // ============================================
    // 📌 MODALES
    // ============================================
    abrirModalBusqueda() {
        if (this.tipoPersonaActual === 'natural') {
            this.abrirModal('modalBusquedaNatural');
        } else {
            this.abrirModal('modalBusquedaJuridica');
        }
    },

    abrirModal(modalId) {
        document.getElementById(modalId)?.classList.add('show');
    },

    cerrarModal(modalId) {
        document.getElementById(modalId)?.classList.remove('show');
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
            contenedor.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No se encontraron datos en RENIEC</div>';
            contenedor.style.display = 'block';
            return;
        }

        let html = `
            <div style="margin-bottom: 15px; padding: 10px; background: #e8f5e9; border-radius: 5px;">
                <strong>Datos obtenidos de RENIEC</strong>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Nombres Completos</th>
                        <th>Foto</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.forEach((persona, index) => {
            const nombresCompletos = persona.nombres_completos || 
                `${persona.nombres || ''} ${persona.apellido_paterno || ''} ${persona.apellido_materno || ''}`.trim();
            
            let fotoHtml = '<div class="photo-placeholder"></div>';
            
            if (persona.foto) {
                const fotoBase64 = persona.foto.startsWith('data:image')
                    ? persona.foto
                    : `data:image/jpeg;base64,${persona.foto}`;
                fotoHtml = `<img src="${fotoBase64}" alt="Foto RENIEC" style="max-width: 80px; max-height: 100px;">`;
            }

            html += `
                <tr>
                    <td><strong>${persona.dni || '-'}</strong></td>
                    <td>${nombresCompletos || 'N/A'}</td>
                    <td style="text-align: center;"><div class="photo-box">${fotoHtml}</div></td>
                    <td>
                        <button class="btn-select" onclick="ModuloPartidas.seleccionarRegistro(${index})">
                            Seleccionar
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        contenedor.innerHTML = html;
        contenedor.style.display = 'block';
    },

    // ============================================
    // MOSTRAR RESULTADOS JURÍDICOS
    // ============================================
    mostrarResultadosJuridica(data) {
        const contenedor = document.getElementById('resultadosJuridica');
        
        if (!data || data.length === 0) {
            contenedor.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No se encontraron datos en SUNAT</div>';
            contenedor.style.display = 'block';
            return;
        }
        
        let html = `
            <div style="margin-bottom: 15px; padding: 10px; background: #e3f2fd; border-radius: 5px;">
                <strong>${data.length} resultado(s) obtenido(s) de SUNAT</strong>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>RUC</th>
                        <th>Razón Social</th>
                        <th>Estado</th>
                        <th>Condición</th>
                        <th>Departamento</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.forEach((item, index) => {
            const razonSocial = item.razon_social || '-';
            const ruc = item.ruc || '-';
            const estadoActivo = item.estado_activo || (item.es_activo ? 'SÍ' : 'NO');
            const estadoHabido = item.estado_habido || (item.es_habido ? 'SÍ' : 'NO');
            const departamento = item.departamento || '-';
            
            const badgeActivo = estadoActivo === 'SÍ' 
                ? '<span style="background: #4caf50; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">ACTIVO</span>'
                : '<span style="background: #f44336; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">NO ACTIVO</span>';
                
            const badgeHabido = estadoHabido === 'SÍ'
                ? '<span style="background: #2196f3; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">HABIDO</span>'
                : '<span style="background: #ff9800; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">NO HABIDO</span>';
            
            html += `
                <tr>
                    <td><strong>${ruc}</strong></td>
                    <td>${razonSocial}</td>
                    <td>${badgeActivo}</td>
                    <td>${badgeHabido}</td>
                    <td>${departamento}</td>
                    <td>
                        <button class="btn-select" onclick="ModuloPartidas.seleccionarRegistro(${index})">
                            Seleccionar
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
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
                document.getElementById('resultsSection').style.display = 'none';
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
    // 📊 MOSTRAR RESULTADOS TSIRSARP
    // ============================================
    mostrarResultadosTSIRSARP(data) {
        this.partidasEncontradas = data;
        
        const resultsSection = document.getElementById('resultsSection');
        resultsSection.style.display = 'block';

        if (data.length > 1) {
            this.mostrarSelectorPartidas(data);
        } else {
            const selectorPartidas = document.getElementById('selectorPartidas');
            if (selectorPartidas) {
                selectorPartidas.style.display = 'none';
            }
        }

        this.mostrarDetallePartida(data[0]);
        resultsSection.scrollIntoView({ behavior: 'smooth' });
    },

    // ============================================
    // 📋 SELECTOR DE PARTIDAS
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

    cambiarPartida(index) {
        if (this.partidasEncontradas && this.partidasEncontradas[index]) {
            this.mostrarDetallePartida(this.partidasEncontradas[index]);
            mostrarAlerta('Mostrando detalles de la partida seleccionada', 'success', "alertContainerPartidas");
        }
    },

    // ============================================
    // 📄 MOSTRAR DETALLE DE PARTIDA
    // ============================================
    mostrarDetallePartida(registro) {
        const photoSection = document.getElementById('photoSection');
        const resultsLayout = document.querySelector('.results-layout');
        if (this.tipoPersonaActual === 'natural') {
            if (photoSection) photoSection.classList.remove('hidden');
            if (resultsLayout) resultsLayout.classList.remove('no-photo');
            this.mostrarCampo('libro', registro.libro || '-');
            this.mostrarCampo('nombres', registro.nombre || this.personaSeleccionada.nombres || '-', 'containerNombres');
            this.mostrarCampo('apellidoPaterno', registro.apPaterno || this.personaSeleccionada.apellido_paterno || '-', 'containerApellidoPaterno');
            this.mostrarCampo('apellidoMaterno', registro.apMaterno || this.personaSeleccionada.apellido_materno || '-', 'containerApellidoMaterno');
            
            this.ocultarCampo('campoRazonSocial', 'containerRazonSocial');
            
            this.mostrarFotoPersona();
        } else {
            if (photoSection) photoSection.classList.add('hidden');
            if (resultsLayout) resultsLayout.classList.add('no-photo');
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
        this.mostrarCampo('tipoDoc', registro.tipo_documento || (this.tipoPersonaActual === 'natural' ? 'DNI' : 'RUC'));
        this.mostrarCampo('nroDoc', registro.numero_documento || (this.tipoPersonaActual === 'natural' ? this.personaSeleccionada.dni : this.personaSeleccionada.ruc) || '-');
        this.mostrarCampo('nroPartida', registro.numero_partida || '-');
        this.mostrarCampo('nroPlaca', registro.numero_placa || '-');
        this.mostrarCampo('estado', registro.estado || '-');
        this.mostrarCampo('zona', registro.zona || '-');
        this.mostrarCampo('oficina', registro.oficina || '-');
        this.mostrarCampo('direccion', registro.direccion || '-');
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
            photoFrame.style.width = "350px";
            photoFrame.style.height = "320px";
            photoFrame.appendChild(img);
        } else {
            photoFrame.innerHTML = '<div class="photo-placeholder"></div>';
            photoFrame.style.width = "200px";
            photoFrame.style.height = "200px";
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
            const contenedorPadre = elemento ? elemento.closest('.info-item') : null;
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
            const contenedorPadre = elemento ? elemento.closest('.info-item') : null;
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
        
        // Resetear zoom al cambiar de partida
        this.zoomLevel = 1;
        if (imagenViewer) {
            imagenViewer.style.width = '';
            imagenViewer.style.height = '';
            imagenViewer.style.maxWidth = '100%';
            imagenViewer.classList.remove('with-zoom');
        }
        
        selectImagenes.innerHTML = '';
        
        imagenes.forEach((img, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = `Página ${img.pagina || (index + 1)}`;
            selectImagenes.appendChild(option);
        });
        
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
            img.onload = function() {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                
                // Descargar desde canvas
                canvas.toBlob(function(blob) {
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
        img.onload = function() {
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
        const vehiculoSection = document.getElementById('vehiculoSection');
        const vehiculoContainer = document.getElementById('vehiculoContainer');
        
        const camposVehiculo = {
            'anoFabricacion': 'Año',
            'placa': 'Placa',
            'marca': 'Marca',
            'modelo': 'Modelo',
            'color': 'Color',
            'nro_motor': 'Número de Motor',
            'carroceria': 'Carroceria',
            'codCategoria': 'Codigo de Categoria',
            'codTipoCarr': 'Codigo de Tipo de Carro',
            'estado': 'Estado'
        };
        
        let html = '';
        
        for (const [campo, label] of Object.entries(camposVehiculo)) {
            const valor = datosVehiculo[campo];
            if (valor !== undefined && valor !== null && valor !== '') {
                html += `
                    <div class="vehiculo-item">
                        <div class="label">${label}</div>
                        <div class="value">${valor}</div>
                    </div>
                `;
            }
        }
        
        vehiculoContainer.innerHTML = html;
        vehiculoSection.style.display = 'block';
    },

    // ============================================
    // 🧹 LIMPIAR FORMULARIO
    // ============================================
    limpiarFormularioPartidas() {
        this.personaSeleccionada = null;
        this.registrosEncontrados = [];
        this.partidasEncontradas = [];
        
        document.getElementById('persona').value = '';
        document.getElementById('resultsSection').style.display = 'none';
        document.getElementById('alertContainerPartidas').innerHTML = '';
        document.getElementById('btnConsultar').disabled = true;
        
        const selectorPartidas = document.getElementById('selectorPartidas');
        if (selectorPartidas) {
            selectorPartidas.innerHTML = '';
            selectorPartidas.style.display = 'none';
        }
        
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
    limpiarModalNatural() {
        document.getElementById('dniNatural').value = '';
        document.getElementById('resultadosNatural').innerHTML = '';
        document.getElementById('resultadosNatural').style.display = 'none';
    },

    limpiarModalJuridica() {
        document.getElementById('rucJuridica').value = '';
        document.getElementById('razonSocial').value = '';
        document.getElementById('resultadosJuridica').innerHTML = '';
        document.getElementById('resultadosJuridica').style.display = 'none';
    }
};

// ============================================
// 🌐 FUNCIONES GLOBALES
// ============================================
window.limpiarFormularioPartidas = function() {
    if (ModuloPartidas.inicializado) {
        ModuloPartidas.limpiarFormularioPartidas();
    }
};

window.limpiarModalNatural = function() {
        document.getElementById('dniNatural').value = '';
        document.getElementById('resultadosNatural').innerHTML = '';
        document.getElementById('resultadosNatural').style.display = 'none';
};

window.limpiarModalJuridica = function() {
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