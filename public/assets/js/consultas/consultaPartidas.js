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
        
        const fotoHtml = persona.foto 
            ? `<img src="data:image/jpeg;base64,${persona.foto}" style="width: 50px; height: 50px; border-radius: 5px;" alt="Foto">`
            : '<span style="color: #999;">Sin foto</span>';
        
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
            console.log({credencialesUsuario, personaSeleccionada});
            resultado = await api.consultarTSIRSARPJuridica({
                usuario: credencialesUsuario.dni,
                clave: credencialesUsuario.password,
                razonSocial: personaSeleccionada.razon_social || ''
            });
        }

        console.log('📊 Resultado TSIRSARP:', resultado);

        if (resultado.success && resultado.data && resultado.data.length > 0) {
            mostrarResultadosTSIRSARP(resultado.data);
            mostrarAlertaPartidas(`Se encontraron ${resultado.data.length} registro(s) en SUNARP`, 'success');
        } else {
            mostrarAlertaPartidas(resultado.message || 'No se encontraron registros en SUNARP', 'info');
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

function mostrarResultadosTSIRSARP(data) {
    console.log('📊 Mostrando resultados TSIRSARP:', data);
    
    // Mostrar sección de resultados
    const resultsSection = document.getElementById('resultsSection');
    resultsSection.style.display = 'block';

    // Si hay múltiples resultados, mostrar el primero
    const registro = data[0];

    if (tipoPersonaActual === 'natural') {
        // PERSONA NATURAL
        document.getElementById('registro').textContent = registro.registro || '-';
        document.getElementById('libro').textContent = registro.libro || '-';
        document.getElementById('apellidoPaterno').textContent = personaSeleccionada.apellido_paterno || '-';
        document.getElementById('apellidoMaterno').textContent = personaSeleccionada.apellido_materno || '-';
        document.getElementById('nombres').textContent = personaSeleccionada.nombres || '-';
        document.getElementById('tipoDoc').textContent = 'DNI';
        document.getElementById('nroDoc').textContent = personaSeleccionada.dni || '-';
        document.getElementById('nroPartida').textContent = registro.partida || '-';
        document.getElementById('nroPlaca').textContent = registro.placa || '-';
        document.getElementById('estado').textContent = registro.estado || '-';
        document.getElementById('zona').textContent = registro.zona || '-';
        document.getElementById('oficina').textContent = registro.oficina || '-';
        document.getElementById('direccion').textContent = registro.descripcion || '-';

        // Manejar foto
        const imgFoto = document.getElementById('personaFoto');
        const noFoto = document.getElementById('noFoto');
        
        if (personaSeleccionada.foto) {
            imgFoto.src = `data:image/jpeg;base64,${personaSeleccionada.foto}`;
            imgFoto.style.display = 'block';
            noFoto.style.display = 'none';
        } else {
            imgFoto.style.display = 'none';
            noFoto.style.display = 'block';
        }
    } else {
        // PERSONA JURÍDICA
        document.getElementById('registro').textContent = registro.registro || '-';
        document.getElementById('libro').textContent = registro.libro || '-';
        document.getElementById('apellidoPaterno').textContent = '-';
        document.getElementById('apellidoMaterno').textContent = '-';
        document.getElementById('nombres').textContent = personaSeleccionada.razon_social || '-';
        document.getElementById('tipoDoc').textContent = 'RUC';
        document.getElementById('nroDoc').textContent = personaSeleccionada.ruc || '-';
        document.getElementById('nroPartida').textContent = registro.partida || '-';
        document.getElementById('nroPlaca').textContent = '-';
        document.getElementById('estado').textContent = registro.estado || '-';
        document.getElementById('zona').textContent = registro.zona || '-';
        document.getElementById('oficina').textContent = registro.oficina || '-';
        document.getElementById('direccion').textContent = registro.descripcion || '-';

        // No hay foto para personas jurídicas
        const imgFoto = document.getElementById('personaFoto');
        const noFoto = document.getElementById('noFoto');
        imgFoto.style.display = 'none';
        noFoto.style.display = 'block';
    }

    // Si hay múltiples resultados, mostrar notificación
    if (data.length > 1) {
        mostrarAlertaPartidas(`Se encontraron ${data.length} registros. Mostrando el primero.`, 'info');
    }

    // Scroll a resultados
    resultsSection.scrollIntoView({ behavior: 'smooth' });
}

// ========================================
// 📌 LIMPIAR FORMULARIOS
// ========================================

function limpiarFormularioPartidas() {
    personaSeleccionada = null;
    registrosEncontrados = [];
    document.getElementById('persona').value = '';
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('alertContainer').innerHTML = '';
    document.getElementById('btnConsultar').disabled = true;
    
    // Limpiar también los modales
    limpiarModalNatural();
    limpiarModalJuridica();
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

    document.getElementById('alertContainer').innerHTML = alerta;
    
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