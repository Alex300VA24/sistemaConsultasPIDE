// consulta-partidas.js - Con consulta TSIRSARP separada

let personaSeleccionada = null;
let tipoPersonaActual = 'natural';
let registrosEncontrados = [];

// Credenciales del usuario actual
let credencialesUsuario = {
    dni: '',
    password: ''
};

// ========================================
// 📌 INICIALIZACIÓN
// ========================================

async function cargarCredencialesUsuario() {
    try {
        const usuario = await api.obtenerUsuarioActual();
        if (usuario.success && usuario.data) {
            credencialesUsuario.dni = usuario.data.PER_documento_num || '';
            
            if (usuario.data.USU_login) {
                const credenciales = await api.obtenerDniYPassword(usuario.data.USU_login);
                if (credenciales && credenciales.success) {
                    credencialesUsuario.password = credenciales.data.password || '';
                }
            }
        }
        console.log('✅ Credenciales cargadas:', { 
            dni: credencialesUsuario.dni, 
            passwordLength: credencialesUsuario.password.length 
        });
    } catch (error) {
        console.error('❌ Error al cargar credenciales:', error);
        mostrarAlertaPartidas('Error al cargar credenciales de usuario', 'danger');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    cargarCredencialesUsuario();
    initEventListeners();
});

function initEventListeners() {
    // Radio buttons de tipo de persona
    document.querySelectorAll('input[name="tipoPersona"]').forEach(radio => {
        radio.addEventListener('change', cambiarTipoPersona);
    });

    // Botón buscar persona
    document.getElementById('btnBuscarPersona').addEventListener('click', abrirModalBusqueda);

    // Botón consultar TSIRSARP
    document.getElementById('btnConsultar').addEventListener('click', consultarTSIRSARP);

    // Botón limpiar
    document.getElementById('btnLimpiar').addEventListener('click', limpiarFormularioPartidas);

    // Form búsqueda natural
    document.getElementById('formBusquedaNatural').addEventListener('submit', buscarPersonaNatural);

    // Form búsqueda jurídica
    document.getElementById('formBusquedaJuridica').addEventListener('submit', buscarPersonaJuridica);

    // Radio buttons de búsqueda jurídica
    document.querySelectorAll('input[name="tipoBusquedaJuridica"]').forEach(radio => {
        radio.addEventListener('change', cambiarTipoBusquedaJuridica);
    });

    // Cerrar modal al hacer clic fuera
    document.querySelectorAll('.modal-partidas').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal(this.id);
            }
        });
    });

    // Validación solo números
    document.getElementById('dniNatural').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    document.getElementById('rucJuridica').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
}

// ========================================
// 📌 CAMBIO DE TIPO DE PERSONA
// ========================================

function cambiarTipoPersona(e) {
    tipoPersonaActual = e.target.value;
    limpiarFormularioPartidas();
    
    const labelPersona = document.getElementById('labelPersona');
    if (tipoPersonaActual === 'natural') {
        labelPersona.textContent = 'Persona:';
    } else {
        labelPersona.textContent = 'Razón Social:';
    }
}

// ========================================
// 📌 MODALES
// ========================================

function abrirModalBusqueda() {
    if (tipoPersonaActual === 'natural') {
        abrirModal('modalBusquedaNatural');
    } else {
        abrirModal('modalBusquedaJuridica');
    }
}

function abrirModal(modalId) {
    document.getElementById(modalId).classList.add('show');
}

function cerrarModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// ========================================
// 📌 BÚSQUEDA PERSONA NATURAL
// ========================================

async function buscarPersonaNatural(e) {
    e.preventDefault();
    
    const dni = document.getElementById('dniNatural').value.trim();
    
    if (!dni || dni.length !== 8) {
        mostrarAlertaPartidas('Por favor ingrese un DNI válido de 8 dígitos', 'warning');
        return;
    }

    if (!credencialesUsuario.dni || !credencialesUsuario.password) {
        mostrarAlertaPartidas('No se han cargado las credenciales del usuario. Recargue la página.', 'danger');
        return;
    }

    mostrarLoadingPartidas('formBusquedaNatural');
    
    try {
        console.log('🔍 Buscando persona natural en RENIEC:', dni);
        
        const resultado = await api.buscarPersonaNaturalSunarp(
            dni,
            credencialesUsuario.dni,
            credencialesUsuario.password
        );
        
        console.log('📊 Resultado búsqueda natural:', resultado);
        
        if (resultado.success && resultado.data && resultado.data.length > 0) {
            registrosEncontrados = resultado.data;
            mostrarResultadosNatural(resultado.data);
            mostrarAlertaPartidas(`Se encontró información en RENIEC`, 'success');
        } else {
            mostrarAlertaPartidas(resultado.message || 'No se encontraron datos en RENIEC para este DNI', 'info');
            registrosEncontrados = [];
            mostrarResultadosNatural([]);
        }
    } catch (error) {
        console.error('❌ Error en búsqueda natural:', error);
        mostrarAlertaPartidas(error.message || 'Error al buscar persona natural', 'danger');
        registrosEncontrados = [];
        mostrarResultadosNatural([]);
    } finally {
        ocultarLoadingPartidas('formBusquedaNatural');
    }
}

// ========================================
// 📌 BÚSQUEDA PERSONA JURÍDICA
// ========================================

async function buscarPersonaJuridica(e) {
    e.preventDefault();
    
    const tipoBusqueda = document.querySelector('input[name="tipoBusquedaJuridica"]:checked').value;
    console.log('🔍 Tipo de búsqueda:', tipoBusqueda);
    let parametro;
    
    if (tipoBusqueda === 'ruc') {
        parametro = document.getElementById('rucJuridica').value.trim();
        if (!parametro || parametro.length !== 11) {
            mostrarAlertaPartidas('Por favor ingrese un RUC válido de 11 dígitos', 'warning');
            return;
        }
    } else {
        parametro = document.getElementById('razonSocial').value.trim();
        if (!parametro) {
            mostrarAlertaPartidas('Por favor ingrese una razón social', 'warning');
            return;
        }
    }

    if (!credencialesUsuario.dni || !credencialesUsuario.password) {
        mostrarAlertaPartidas('No se han cargado las credenciales del usuario. Recargue la página.', 'danger');
        return;
    }

    mostrarLoadingPartidas('formBusquedaJuridica');
    
    try {
        console.log('🔍 Buscando persona jurídica en SUNAT:', { parametro, tipoBusqueda });
        
        const resultado = await api.buscarPersonaJuridicaSunarp(
            parametro,
            tipoBusqueda,
            credencialesUsuario.dni,
            credencialesUsuario.password
        );
        
        console.log('📊 Resultado búsqueda jurídica:', resultado);
        
        if (resultado.success && resultado.data && resultado.data.length > 0) {
            registrosEncontrados = resultado.data;
            mostrarResultadosJuridica(resultado.data);
            mostrarAlertaPartidas(`Se encontraron ${resultado.data.length} resultado(s) en SUNAT`, 'success');
        } else {
            mostrarAlertaPartidas(resultado.message || 'No se encontraron registros en SUNAT', 'info');
            registrosEncontrados = [];
            mostrarResultadosJuridica([]);
        }
    } catch (error) {
        console.error('❌ Error en búsqueda jurídica:', error);
        mostrarAlertaPartidas(error.message || 'Error al buscar persona jurídica', 'danger');
        registrosEncontrados = [];
        mostrarResultadosJuridica([]);
    } finally {
        ocultarLoadingPartidas('formBusquedaJuridica');
    }
}

// ========================================
// MOSTRAR RESULTADOS PERSONA NATURAL (RENIEC)
// ========================================

function mostrarResultadosNatural(data) {
    const contenedor = document.getElementById('resultadosNatural');
    
    console.log('📋 Mostrando resultados naturales:', data);
    
    if (!data || data.length === 0) {
        contenedor.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No se encontraron datos en RENIEC</div>';
        contenedor.style.display = 'block';
        return;
    }

    let html = `
        <div style="margin-bottom: 15px; padding: 10px; background: #e8f5e9; border-radius: 5px;">
            <strong>✅ Datos obtenidos de RENIEC</strong>
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
        
        let fotoBase64 = '';

        if (persona.foto) {
            fotoBase64 = persona.foto.startsWith('data:image')
                ? persona.foto
                : `data:image/jpeg;base64,${persona.foto}`;
        }

        const fotoHtml = `
            <div class="photo-box">
                ${
                    persona.foto
                    ? `<img src="${fotoBase64}" alt="Foto RENIEC">`
                    : `<div class="photo-placeholder"></div>`
                }
            </div>
        `;

        
        html += `
            <tr>
                <td><strong>${persona.dni || '-'}</strong></td>
                <td>${nombresCompletos || 'N/A'}</td>
                <td style="text-align: center;">${fotoHtml}</td>
                <td>
                    <button class="btn-select" onclick="seleccionarRegistro(${index})">
                        Seleccionar
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    contenedor.innerHTML = html;
    contenedor.style.display = 'block';
}

// ========================================
// 📌 MOSTRAR RESULTADOS PERSONA JURÍDICA (SUNAT)
// ========================================

function mostrarResultadosJuridica(data) {
    const contenedor = document.getElementById('resultadosJuridica');
    
    console.log('📋 Mostrando resultados jurídicos:', data);
    
    if (!data || data.length === 0) {
        contenedor.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No se encontraron datos en SUNAT</div>';
        contenedor.style.display = 'block';
        return;
    }
    
    let html = `
        <div style="margin-bottom: 15px; padding: 10px; background: #e3f2fd; border-radius: 5px;">
            <strong>✅ ${data.length} resultado(s) obtenido(s) de SUNAT</strong>
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
        
        // Badge de estado
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
                    <button class="btn-select" onclick="seleccionarRegistro(${index})">
                        Seleccionar
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    contenedor.innerHTML = html;
    contenedor.style.display = 'block';
}

// ========================================
// 📌 SELECCIONAR REGISTRO (Solo llena el campo)
// ========================================

function seleccionarRegistro(index) {
    if (!registrosEncontrados || !registrosEncontrados[index]) {
        mostrarAlertaPartidas('Error al seleccionar el registro', 'danger');
        return;
    }

    personaSeleccionada = registrosEncontrados[index];
    
    console.log('✅ Registro seleccionado:', personaSeleccionada);
    
    const inputPersona = document.getElementById('persona');
    
    if (tipoPersonaActual === 'natural') {
        const nombresCompletos = personaSeleccionada.nombres_completos ||
            `${personaSeleccionada.nombres || ''} ${personaSeleccionada.apellido_paterno || ''} ${personaSeleccionada.apellido_materno || ''}`.trim();
        inputPersona.value = nombresCompletos;
        cerrarModal('modalBusquedaNatural');
        mostrarAlertaPartidas('Persona seleccionada. Haga clic en "Consultar" para buscar en SUNARP', 'info');
    } else {
        inputPersona.value = personaSeleccionada.razon_social || '';
        cerrarModal('modalBusquedaJuridica');
        mostrarAlertaPartidas('Razón social seleccionada. Haga clic en "Consultar" para buscar en SUNARP', 'info');
    }
    
    // Habilitar el botón consultar
    document.getElementById('btnConsultar').disabled = false;
    
    // Limpiar resultados anteriores
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('selectorPartidas').style.display = 'none';

}

// ========================================
// 📌 CONSULTAR TSIRSARP (Botón Consultar)
// ========================================

async function consultarTSIRSARP() {
    if (!personaSeleccionada) {
        mostrarAlertaPartidas('Por favor seleccione una persona o razón social primero', 'warning');
        return;
    }

    if (!credencialesUsuario.dni || !credencialesUsuario.password) {
        mostrarAlertaPartidas('No se han cargado las credenciales del usuario. Recargue la página.', 'danger');
        return;
    }

    const btnConsultar = document.getElementById('btnConsultar');
    const originalHTML = btnConsultar.innerHTML;
    btnConsultar.disabled = true;
    btnConsultar.innerHTML = '<span class="loading-spinner"></span> Consultando SUNARP...';

    try {
        console.log('🔍 Consultando TSIRSARP para:', personaSeleccionada);

        let resultado;

        if (tipoPersonaActual === 'natural') {
            // Consultar TSIRSARP para persona natural
            resultado = await api.consultarTSIRSARPNatural({
                usuario: credencialesUsuario.dni,
                clave: credencialesUsuario.password,
                apellidoPaterno: personaSeleccionada.apellido_paterno || '',
                apellidoMaterno: personaSeleccionada.apellido_materno || '',
                nombres: personaSeleccionada.nombres || ''
            });
        } else {
            // Consultar TSIRSARP para persona jurídica
            resultado = await api.consultarTSIRSARPJuridica({
                usuario: credencialesUsuario.dni,
                clave: credencialesUsuario.password,
                razonSocial: personaSeleccionada.razon_social || ''
            });
        }

        console.log('📊 Resultado TSIRSARP COMPLETO:', resultado);
        
        // Log detallado de cada partida
        if (resultado.success && resultado.data && resultado.data.length > 0) {
            resultado.data.forEach((partida, index) => {
                console.log(`📄 Partida ${index + 1}:`, {
                    numero_partida: partida.numero_partida,
                    tiene_asientos: !!partida.asientos,
                    cantidad_asientos: partida.asientos?.length || 0,
                    tiene_imagenes: !!partida.imagenes,
                    cantidad_imagenes: partida.imagenes?.length || 0,
                    tiene_vehiculo: !!partida.datos_vehiculo,
                    placa: partida.numero_placa
                });
            });
            
            mostrarResultadosTSIRSARP(resultado.data);
            mostrarAlertaPartidas(`Se encontraron ${resultado.data.length} registro(s) en SUNARP con información completa`, 'success');
        } else {
            mostrarAlertaPartidas('No se encontraron registros en SUNARP', 'info');
            document.getElementById('resultsSection').style.display = 'none';
        }
    } catch (error) {
        console.error('❌ Error en consulta TSIRSARP:', error);
        mostrarAlertaPartidas(error.message || 'Error al consultar SUNARP', 'danger');
    } finally {
        btnConsultar.disabled = false;
        btnConsultar.innerHTML = originalHTML;
    }
}

// ========================================
// 📌 MOSTRAR RESULTADOS TSIRSARP
// ========================================

let partidasEncontradas = []; // Guardar todas las partidas

function mostrarResultadosTSIRSARP(data) {
    console.log('📊 Mostrando resultados TSIRSARP:', data);
    
    // Guardar todas las partidas
    partidasEncontradas = data;
    
    // Mostrar sección de resultados
    const resultsSection = document.getElementById('resultsSection');
    resultsSection.style.display = 'block';

    // Si hay múltiples resultados, mostrar selector de partidas
    if (data.length > 1) {
        mostrarSelectorPartidas(data);
        mostrarAlertaPartidas(`Se encontraron ${data.length} partidas registradas. Seleccione una para ver los detalles.`, 'info');
    } else {
        // Ocultar selector si solo hay una partida
        const selectorPartidas = document.getElementById('selectorPartidas');
        if (selectorPartidas) {
            selectorPartidas.style.display = 'none';
        }
    }

    // Mostrar la primera partida por defecto
    mostrarDetallePartida(data[0]);

    // Scroll a resultados
    resultsSection.scrollIntoView({ behavior: 'smooth' });
}

// ========================================
// 📌 MOSTRAR SELECTOR DE PARTIDAS
// ========================================

function mostrarSelectorPartidas(partidas) {
    let selectorPartidas = document.getElementById('selectorPartidas');
    
    // Crear el contenedor si no existe
    if (!selectorPartidas) {
        selectorPartidas = document.createElement('div');
        selectorPartidas.id = 'selectorPartidas';
        selectorPartidas.className = 'selector-partidas-container';
        
        // Insertar antes de resultsSection
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
                <input type="radio" 
                       name="partidaSeleccionada" 
                       id="partida${index}" 
                       value="${index}" 
                       ${index === 0 ? 'checked' : ''}
                       onchange="cambiarPartida(${index})">
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
}

// ========================================
// 📌 CAMBIAR PARTIDA SELECCIONADA
// ========================================

function cambiarPartida(index) {
    console.log('📌 Cambiando a partida:', index);
    if (partidasEncontradas && partidasEncontradas[index]) {
        mostrarDetallePartida(partidasEncontradas[index]);
        mostrarAlertaPartidas('Mostrando detalles de la partida seleccionada', 'success');
    }
}

// ========================================
// 📌 MOSTRAR DETALLE DE UNA PARTIDA
// ========================================

function mostrarDetallePartida(registro) {
    console.log('📄 Mostrando detalle de partida COMPLETO:', registro);

    const photoSection = document.getElementById('photoSection');
    const resultsLayout = document.querySelector('.results-layout');

    if (tipoPersonaActual === 'natural') {
        // PERSONA NATURAL
        if (photoSection) photoSection.classList.remove('hidden');
        if (resultsLayout) resultsLayout.classList.remove('no-photo');

        // Usar los nombres de campos correctos del response
        mostrarCampo('libro', registro.libro || '-');
        mostrarCampo('nombres', registro.nombre || personaSeleccionada.nombres || '-', 'containerNombres');
        mostrarCampo('apellidoPaterno', registro.apPaterno || personaSeleccionada.apellido_paterno || '-', 'containerApellidoPaterno');
        mostrarCampo('apellidoMaterno', registro.apMaterno || personaSeleccionada.apellido_materno || '-', 'containerApellidoMaterno');
        
        ocultarCampo('campoRazonSocial', 'containerRazonSocial');
        
        mostrarCampo('tipoDoc', registro.tipo_documento || 'DNI');
        mostrarCampo('nroDoc', registro.numero_documento || personaSeleccionada.dni || '-');
        mostrarCampo('nroPartida', registro.numero_partida || '-');
        mostrarCampo('nroPlaca', registro.numero_placa || '-');
        mostrarCampo('estado', registro.estado || '-');
        mostrarCampo('zona', registro.zona || '-');
        mostrarCampo('oficina', registro.oficina || '-');
        mostrarCampo('direccion', registro.direccion || '-');

        const photoFrame = document.getElementById('photoSection');

        // limpiar contenido previo
        photoFrame.innerHTML = '';

        let fotoBase64 = '';

        if (personaSeleccionada && personaSeleccionada.foto) {

            fotoBase64 = personaSeleccionada.foto.startsWith('data:image')
                ? personaSeleccionada.foto
                : `data:image/jpeg;base64,${personaSeleccionada.foto}`;

            const img = document.createElement('img');
            img.src = fotoBase64;
            img.alt = "Foto de persona";

            // mismo tamaño que DNI
            photoFrame.style.width = "350px";
            photoFrame.style.height = "320px";

            photoFrame.appendChild(img);

        } else {

            // sin foto → placeholder
            photoFrame.innerHTML = `<div class="photo-placeholder"></div>`;

            // tamaño reducido (igual que en DNI)
            photoFrame.style.width = "200px";
            photoFrame.style.height = "200px";
        }

        
    } else {
        // PERSONA JURÍDICA
        if (photoSection) photoSection.classList.add('hidden');
        if (resultsLayout) resultsLayout.classList.add('no-photo');

        mostrarCampo('libro', registro.libro || '-');
        
        // Usar los nombres de campos correctos del response
        const tieneNombres = registro.nombre || registro.apPaterno || registro.apMaterno;
        
        if (tieneNombres) {
            mostrarCampo('nombres', registro.nombre || '-', 'containerNombres');
            mostrarCampo('apellidoPaterno', registro.apPaterno || '-', 'containerApellidoPaterno');
            mostrarCampo('apellidoMaterno', registro.apMaterno || '-', 'containerApellidoMaterno');
        } else {
            ocultarCampo('nombres', 'containerNombres');
            ocultarCampo('apellidoPaterno', 'containerApellidoPaterno');
            ocultarCampo('apellidoMaterno', 'containerApellidoMaterno');
        }
        
        const razonSocial = registro.razon_social || personaSeleccionada.razon_social || '-';
        mostrarCampo('campoRazonSocial', razonSocial, 'containerRazonSocial');
        
        mostrarCampo('tipoDoc', registro.tipo_documento || 'RUC');
        mostrarCampo('nroDoc', registro.numero_documento || personaSeleccionada.ruc || '-');
        mostrarCampo('nroPartida', registro.numero_partida || '-');
        mostrarCampo('nroPlaca', registro.numero_placa || '-');
        mostrarCampo('estado', registro.estado || '-');
        mostrarCampo('zona', registro.zona || '-');
        mostrarCampo('oficina', registro.oficina || '-');
        mostrarCampo('direccion', registro.direccion || '-');
    }


    // ========================================
    // MOSTRAR IMÁGENES (VASIRSARP)
    // Para NATURAL y JURÍDICA
    // ========================================
    console.log('🔍 Verificando imágenes:', registro.imagenes);
    if (registro.imagenes && Array.isArray(registro.imagenes) && registro.imagenes.length > 0) {
        mostrarImagenes(registro.imagenes);
    } else {
        document.getElementById('imagenesSection').style.display = 'none';
    }

    // ========================================
    // MOSTRAR DATOS VEHICULARES (VDRPVExtra)
    // Para NATURAL y JURÍDICA (si tiene placa)
    // ========================================
    console.log('🔍 Verificando datos vehículo:', registro.datos_vehiculo);
    if (registro.datos_vehiculo && Object.keys(registro.datos_vehiculo).length > 0) {
        mostrarDatosVehiculo(registro.datos_vehiculo);
    } else {
        document.getElementById('vehiculoSection').style.display = 'none';
    }
}

// ========================================
// 📌 FUNCIONES AUXILIARES PARA MOSTRAR/OCULTAR CAMPOS
// ========================================

function mostrarCampo(idCampo, valor, idContenedor = null) {
    const elemento = document.getElementById(idCampo);
    if (elemento) {
        elemento.textContent = valor;
    }
    
    // Si se proporciona un ID de contenedor específico, usarlo
    const contenedor = idContenedor ? document.getElementById(idContenedor) : null;
    
    if (contenedor) {
        contenedor.style.display = '';
    } else {
        // Si no hay contenedor específico, buscar el padre del elemento
        const contenedorPadre = elemento ? elemento.closest('.info-item') : null;
        if (contenedorPadre) {
            contenedorPadre.style.display = '';
        }
    }
}

function ocultarCampo(idCampo, idContenedor = null) {
    const elemento = document.getElementById(idCampo);
    
    // Si se proporciona un ID de contenedor específico, usarlo
    const contenedor = idContenedor ? document.getElementById(idContenedor) : null;
    
    if (contenedor) {
        contenedor.style.display = 'none';
    } else {
        // Si no hay contenedor específico, buscar el padre del elemento
        const contenedorPadre = elemento ? elemento.closest('.info-item') : null;
        if (contenedorPadre) {
            contenedorPadre.style.display = 'none';
        }
    }
}

// ========================================
// 📌 LIMPIAR FORMULARIOS
// ========================================

function limpiarFormularioPartidas() {
    console.log('🧹 Limpiando formulario completo...');
    
    // Limpiar variables globales
    personaSeleccionada = null;
    registrosEncontrados = [];
    partidasEncontradas = [];
    
    // Limpiar campo de persona
    document.getElementById('persona').value = '';
    
    // Ocultar sección de resultados
    document.getElementById('resultsSection').style.display = 'none';
    
    // Limpiar alertas
    document.getElementById('alertContainer').innerHTML = '';
    
    // Deshabilitar botón consultar
    document.getElementById('btnConsultar').disabled = true;
    
    // Ocultar y limpiar selector de partidas
    const selectorPartidas = document.getElementById('selectorPartidas');
    if (selectorPartidas) {
        selectorPartidas.innerHTML = '';
        selectorPartidas.style.display = 'none';
    }
    
    // Limpiar todos los campos de resultados
    limpiarCamposResultados();
    
    // Limpiar también los modales
    limpiarModalNatural();
    limpiarModalJuridica();
    
    console.log('✅ Formulario limpiado completamente');
}

function limpiarCamposResultados() {
    // Limpiar todos los campos de texto
    const camposTexto = [
        'libro', 'nombres', 'apellidoPaterno', 'apellidoMaterno', 'razonSocial',
        'tipoDoc', 'nroDoc', 'nroPartida', 'nroPlaca', 
        'estado', 'zona', 'oficina', 'direccion'
    ];
    
    camposTexto.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            elemento.textContent = '-';
        }
    });
    
    // Mostrar todos los contenedores (por si estaban ocultos)
    const contenedores = [
        'containerNombres', 
        'containerApellidoPaterno', 
        'containerApellidoMaterno'
    ];
    
    contenedores.forEach(idContenedor => {
        const contenedor = document.getElementById(idContenedor);
        if (contenedor) {
            contenedor.style.display = '';
        }
    });
    
    // Ocultar razón social por defecto
    const containerRazonSocial = document.getElementById('containerRazonSocial');
    if (containerRazonSocial) {
        containerRazonSocial.style.display = 'none';
    }
    
    // Mostrar photo-section por defecto
    const photoSection = document.getElementById('photoSection');
    if (photoSection) {
        photoSection.classList.remove('hidden');
    }
    
    // Resetear layout
    const resultsLayout = document.querySelector('.results-layout');
    if (resultsLayout) {
        resultsLayout.classList.remove('no-photo');
    }
    
    // Limpiar foto
    const imgFoto = document.getElementById('personaFoto');
    const noFoto = document.getElementById('noFoto');
    if (imgFoto) {
        imgFoto.src = '';
        imgFoto.style.display = 'none';
    }
    if (noFoto) {
        noFoto.style.display = 'flex';
    }

    // Limpiar secciones adicionales
    document.getElementById('asientosSection').style.display = 'none';
    document.getElementById('imagenesSection').style.display = 'none';
    document.getElementById('vehiculoSection').style.display = 'none';
    
    document.getElementById('asientosContainer').innerHTML = '';
    document.getElementById('vehiculoContainer').innerHTML = '';
    
    const selectImagenes = document.getElementById('selectImagenes');
    if (selectImagenes) {
        selectImagenes.innerHTML = '';
    }
    
    const imagenViewer = document.getElementById('imagenViewer');
    const noImagen = document.getElementById('noImagen');
    if (imagenViewer) {
        imagenViewer.src = '';
        imagenViewer.style.display = 'none';
    }
    if (noImagen) {
        noImagen.style.display = 'flex';
    }
}

function limpiarModalNatural() {
    document.getElementById('dniNatural').value = '';
    document.getElementById('resultadosNatural').innerHTML = '';
    document.getElementById('resultadosNatural').style.display = 'none';
}

function limpiarModalJuridica() {
    document.getElementById('rucJuridica').value = '';
    document.getElementById('razonSocial').value = '';
    document.getElementById('resultadosJuridica').innerHTML = '';
    document.getElementById('resultadosJuridica').style.display = 'none';
}

// ========================================
// 📌 CAMBIO TIPO DE BÚSQUEDA JURÍDICA
// ========================================

function cambiarTipoBusquedaJuridica(e) {
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
    
    limpiarModalJuridica();
}

// ========================================
// 📌 UTILIDADES UI
// ========================================

function mostrarAlertaPartidas(mensaje, tipo) {
    const iconos = {
        success: 'check-circle',
        danger: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };

    const alerta = `
        <div class="alert alert-${tipo}">
            <i class="fas fa-${iconos[tipo]}"></i>
            <span>${mensaje}</span>
        </div>
    `;

    document.getElementById('alertContainerPartidas').innerHTML = alerta;
    
    // Auto-ocultar solo si es success o info
    if (tipo === 'success' || tipo === 'info') {
        setTimeout(() => {
            document.getElementById('alertContainer').innerHTML = '';
        }, 5000);
    }
}

function mostrarLoadingPartidas(formId) {
    const form = document.getElementById(formId);
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="loading-spinner"></span> <span>Buscando...</span>';
}

function ocultarLoadingPartidas(formId) {
    const form = document.getElementById(formId);
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-search"></i> <span>Buscar</span>';
}

// ========================================
// 📌 MOSTRAR ASIENTOS REGISTRALES
// ========================================
function mostrarAsientos(asientos) {
    console.log('📋 Mostrando asientos:', asientos);
    
    const asientosSection = document.getElementById('asientosSection');
    const asientosContainer = document.getElementById('asientosContainer');
    
    let html = `
        <table class="asientos-table">
            <thead>
                <tr>
                    <th>ID Imagen</th>
                    <th>Número Página</th>
                    <th>Tipo</th>
                    <th>Página Ref</th>
                    <th>Página</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    asientos.forEach(asiento => {
        html += `
            <tr>
                <td>${asiento.idImgAsiento || '-'}</td>
                <td>${asiento.numPag || '-'}</td>
                <td>${asiento.tipo || '-'}</td>
                <td>${asiento.listPag?.nroPagRef || '-'}</td>
                <td>${asiento.listPag?.pagina || '-'}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    
    asientosContainer.innerHTML = html;
    asientosSection.style.display = 'block';
}

// ========================================
// 📌 MOSTRAR IMÁGENES
// ========================================
function mostrarImagenes(imagenes) {
    console.log('🖼️ Mostrando imágenes:', imagenes.length);
    
    const imagenesSection = document.getElementById('imagenesSection');
    const selectImagenes = document.getElementById('selectImagenes');
    const imagenViewer = document.getElementById('imagenViewer');
    const noImagen = document.getElementById('noImagen');
    
    // Limpiar selector
    selectImagenes.innerHTML = '';
    
    // Agregar opciones
    imagenes.forEach((img, index) => {
        const option = document.createElement('option');
        option.value = index;
        option.textContent = `Página ${img.pagina || (index + 1)}`;
        selectImagenes.appendChild(option);
    });
    
    // Función para cambiar imagen
    const cambiarImagen = () => {
        const index = parseInt(selectImagenes.value);
        const imagenData = imagenes[index];
        
        if (imagenData && imagenData.imagen_base64) {
            imagenViewer.src = `data:image/jpeg;base64,${imagenData.imagen_base64}`;
            imagenViewer.style.display = 'block';
            noImagen.style.display = 'none';
        } else {
            imagenViewer.src = '';
            imagenViewer.style.display = 'none';
            noImagen.style.display = 'flex';
        }
    };
    
    // Event listener
    selectImagenes.addEventListener('change', cambiarImagen);
    
    // Mostrar primera imagen
    cambiarImagen();
    
    imagenesSection.style.display = 'block';
}

// ========================================
// 📌 MOSTRAR DATOS VEHICULARES
// ========================================
function mostrarDatosVehiculo(datosVehiculo) {
    console.log('🚗 Mostrando datos vehiculares:', datosVehiculo);
    
    const vehiculoSection = document.getElementById('vehiculoSection');
    const vehiculoContainer = document.getElementById('vehiculoContainer');
    
    // Mapeo de campos a etiquetas legibles
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
        
        // Solo mostrar si hay valor
        if (valor !== undefined && valor !== null && valor !== '') {
            html += `
                <div class="vehiculo-item">
                    <div class="label">${label}</div>
                    <div class="value">${valor}</div>
                </div>
            `;
        }
    }
    
    // Si no hay campos, mostrar todos los campos disponibles
    if (html === '') {
        for (const [campo, valor] of Object.entries(datosVehiculo)) {
            if (valor !== undefined && valor !== null && valor !== '') {
                const labelFormateado = campo.replace(/([A-Z])/g, ' $1').trim();
                html += `
                    <div class="vehiculo-item">
                        <div class="label">${labelFormateado}</div>
                        <div class="value">${valor}</div>
                    </div>
                `;
            }
        }
    }
    
    vehiculoContainer.innerHTML = html;
    vehiculoSection.style.display = 'block';
}

