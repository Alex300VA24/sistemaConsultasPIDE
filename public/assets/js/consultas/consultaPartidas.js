// consulta-partidas.js - Actualizado para TSIRSARP

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

// Función para cargar credenciales del usuario actual
async function cargarCredencialesUsuario() {
    try {
        const usuario = await api.obtenerUsuarioActual();
        if (usuario.success && usuario.data) {
            credencialesUsuario.dni = usuario.data.PER_documento_num || '';
            
            // Obtener también la password si está disponible
            if (usuario.data.USU_login) {
                const credenciales = await api.obtenerDniYPassword(usuario.data.USU_login);
                if (credenciales && credenciales.success) {
                    credencialesUsuario.password = credenciales.data.password || '';
                }
            }
        }
        console.log('Credenciales cargadas:', { 
            dni: credencialesUsuario.dni, 
            passwordLength: credencialesUsuario.password.length 
        });
    } catch (error) {
        console.error('Error al cargar credenciales:', error);
        mostrarAlertaPartidas('Error al cargar credenciales de usuario', 'danger');
    }
}

// Llamar al cargar la página
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

    // Botón limpiar
    document.getElementById('btnLimpiar').addEventListener('click', limpiarFormularioPartidas);

    // Form búsqueda natural
    document.getElementById('formBusquedaNatural').addEventListener('submit', buscarPersonaNatural);

    // Form búsqueda jurídica
    document.getElementById('formBusquedaJuridica').addEventListener('submit', buscarPersonaJuridica);

    // Radio buttons de búsqueda jurídica
    document.querySelectorAll('input[name="tipoBusquedaJuridica"]').forEach(radio => {
        radio.addEventListener('change', cambiarTipoBusquedaJuridica);
        // Form búsqueda jurídica
        document.getElementById('formBusquedaJuridica').addEventListener('submit', buscarPersonaJuridica);
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
        console.log('Buscando persona natural:', dni);
        
        const resultado = await api.buscarPersonaNaturalSunarp(
            dni,
            credencialesUsuario.dni,
            credencialesUsuario.password
        );
        
        console.log('Resultado búsqueda natural:', resultado);
        
        if (resultado.success && resultado.data) {
            registrosEncontrados = resultado.data;
            mostrarResultadosNatural(resultado.data);
            
            if (resultado.data.length === 0) {
                mostrarAlertaPartidas('No se encontraron registros en SUNARP para este DNI', 'info');
            }
        } else {
            mostrarAlertaPartidas(resultado.message || 'No se encontraron resultados', 'warning');
            registrosEncontrados = [];
            mostrarResultadosNatural([]);
        }
    } catch (error) {
        console.error('Error en búsqueda natural:', error);
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
    console.log('Tipo de busqueda', tipoBusqueda);
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
        console.log('Buscando persona jurídica:', { parametro, tipoBusqueda, credencialesUsuario });
        
        const resultado = await api.buscarPersonaJuridicaSunarp(
            parametro,
            tipoBusqueda,
            credencialesUsuario.dni,
            credencialesUsuario.password
        );
        
        console.log('Resultado búsqueda jurídica:', resultado);
        
        if (resultado.success && resultado.data) {
            registrosEncontrados = resultado.data;
            mostrarResultadosJuridica(resultado.data);
            
            if (resultado.data.length === 0) {
                mostrarAlertaPartidas('No se encontraron registros en SUNARP', 'info');
            }
        } else {
            mostrarAlertaPartidas(resultado.message || 'No se encontraron resultados', 'warning');
            registrosEncontrados = [];
            mostrarResultadosJuridica([]);
        }
    } catch (error) {
        console.error('Error en búsqueda jurídica:', error);
        mostrarAlertaPartidas(error.message || 'Error al buscar persona jurídica', 'danger');
        registrosEncontrados = [];
        mostrarResultadosJuridica([]);
    } finally {
        ocultarLoadingPartidas('formBusquedaJuridica');
    }
}

// ========================================
// 📌 MOSTRAR RESULTADOS PERSONA NATURAL
// ========================================

function mostrarResultadosNatural(data) {
    const contenedor = document.getElementById('resultadosNatural');
    
    console.log(data, Object.keys(data).length);
    if (!data || Object.keys(data).length === 0) {
        contenedor.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No se encontraron registros en SUNARP</div>';
        contenedor.style.display = 'block';
        return;
    }

    let html = `
        <table>
            <thead>
                <tr>
                    <th>DNI</th>
                    <th>Nombres Completos</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
    `;

    const nombresCompletos = `${data.nombres || ''} ${data.apellido_paterno || ''} ${data.apellido_materno || ''}`.trim();
        
    html += `
        <tr>
            <td>${data.dni || '-'}</td>
            <td><strong>${nombresCompletos || 'N/A'}</strong></td>
            <td>
                <button class="btn-select" onclick='seleccionarPersona(${JSON.stringify(data)})'>
                    Seleccionar
                </button>
            </td>
        </tr>
    `;

    html += '</tbody></table>';
    contenedor.innerHTML = html;
    contenedor.style.display = 'block';
}

// ========================================
// 📌 MOSTRAR RESULTADOS PERSONA JURÍDICA
// ========================================

function mostrarResultadosJuridica(datos) {
    const contenedor = document.getElementById('resultadosJuridica');
    
    if (!datos || Object.keys(datos).length === 0) {
        contenedor.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No se encontraron resultados</div>';
        contenedor.style.display = 'block';
        return;
    }

    let html = `
        <table>
            <thead>
                <tr>
                    <th>RUC</th>
                    <th>Razón Social</th>
                    <th>Estado</th>
                    <th>Informacion</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
    `;


    let infoAdicional = '';
    if (datos.direccion_completa) {
        infoAdicional += `<div><strong>Dirección:</strong> ${datos.direccion_completa}</div>`;
    }
    if (datos.departamento && datos.provincia && datos.distrito) {
        infoAdicional += `<div><strong>Ubicación:</strong> ${datos.departamento} / ${datos.provincia} / ${datos.distrito}</div>`;
    }
    if (datos.condicion_domicilio) {
        infoAdicional += `<div><strong>Condicion:</strong> ${datos.condicion_domicilio}</div>`;
    }
    

    const estadoClass = datos.estado_contribuyente ? 'badge-success' : 'badge-danger';
    const estadoTexto = datos.estado_contribuyente ? 'Activo' : 'Inactivo';

    html += `
        <tr>
            <td>${datos.ruc || '-'}</td>
            <td>${datos.razon_social || '-'}</td>
            <td><span class="badge ${estadoClass}">${estadoTexto}</span></td>
            <td style="font-size: 0.85em;">${infoAdicional || '-'}</td>
            <td>
                <button class="btn-select" onclick='seleccionarPersona(${JSON.stringify(datos)})'>
                    Seleccionar
                </button>
            </td>
        </tr>
    `;


    html += '</tbody></table>';
    contenedor.innerHTML = html;
    contenedor.style.display = 'block';
}

// ========================================
// 📌 SELECCIONAR REGISTRO
// ========================================

function seleccionarRegistro(index) {
    if (!registrosEncontrados || !registrosEncontrados[index]) {
        mostrarAlertaPartidas('Error al seleccionar el registro', 'danger');
        return;
    }

    personaSeleccionada = registrosEncontrados[index];
    
    const inputPersona = document.getElementById('persona');
    
    if (tipoPersonaActual === 'natural') {
        const nombresCompletos = `${personaSeleccionada.nombres || ''} ${personaSeleccionada.apellidoPaterno || ''} ${personaSeleccionada.apellidoMaterno || ''}`.trim();
        inputPersona.value = nombresCompletos;
        cerrarModal('modalBusquedaNatural');
    } else {
        inputPersona.value = personaSeleccionada.razonSocial || '';
        cerrarModal('modalBusquedaJuridica');
    }
    
    // Mostrar directamente los resultados
    mostrarResultados(personaSeleccionada);
    mostrarAlertaPartidas('Registro seleccionado correctamente', 'success');
}

function seleccionarPersona(persona) {
    personaSeleccionada = persona;
    
    const inputPersona = document.getElementById('persona');
    if (tipoPersonaActual === 'natural') {
        inputPersona.value = `${persona.nombres} ${persona.apellido_paterno} ${persona.apellido_materno}`;
        cerrarModal('modalBusquedaNatural');
    } else {
        inputPersona.value = persona.razon_social;
        cerrarModal('modalBusquedaJuridica');
    }

    mostrarAlertaPartidas('Persona seleccionada correctamente', 'success');
}

// ========================================
// 📌 MOSTRAR RESULTADOS EN LA VISTA PRINCIPAL
// ========================================

function mostrarResultados(datos) {
    // Mostrar sección de resultados
    document.getElementById('resultsSection').style.display = 'block';

    // Llenar campos según tipo de persona
    if (tipoPersonaActual === 'natural') {
        document.getElementById('registro').textContent = datos.registro || '-';
        document.getElementById('libro').textContent = datos.libro || '-';
        document.getElementById('apellidoPaterno').textContent = datos.apellidoPaterno || '-';
        document.getElementById('apellidoMaterno').textContent = datos.apellidoMaterno || '-';
        document.getElementById('nombres').textContent = datos.nombres || '-';
        document.getElementById('tipoDoc').textContent = 'DNI';
        document.getElementById('nroDoc').textContent = datos.dni || '-';
        document.getElementById('nroPartida').textContent = datos.partida || '-';
        document.getElementById('nroPlaca').textContent = datos.placa || '-';
        document.getElementById('estado').textContent = datos.estado || '-';
        document.getElementById('zona').textContent = datos.zona || '-';
        document.getElementById('oficina').textContent = datos.oficina || '-';
        document.getElementById('direccion').textContent = datos.descripcion || '-';

        // Manejar foto
        const imgFoto = document.getElementById('personaFoto');
        const noFoto = document.getElementById('noFoto');
        
        if (datos.foto) {
            imgFoto.src = `data:image/jpeg;base64,${datos.foto}`;
            imgFoto.style.display = 'block';
            noFoto.style.display = 'none';
        } else {
            imgFoto.style.display = 'none';
            noFoto.style.display = 'block';
        }
    } else {
        // Persona Jurídica
        document.getElementById('registro').textContent = datos.registro || '-';
        document.getElementById('libro').textContent = datos.libro || '-';
        document.getElementById('apellidoPaterno').textContent = '-';
        document.getElementById('apellidoMaterno').textContent = '-';
        document.getElementById('nombres').textContent = datos.razonSocial || '-';
        document.getElementById('tipoDoc').textContent = '-';
        document.getElementById('nroDoc').textContent = '-';
        document.getElementById('nroPartida').textContent = datos.partida || '-';
        document.getElementById('nroPlaca').textContent = '-';
        document.getElementById('estado').textContent = datos.estado || '-';
        document.getElementById('zona').textContent = datos.zona || '-';
        document.getElementById('oficina').textContent = datos.oficina || '-';
        document.getElementById('direccion').textContent = datos.descripcion || '-';

        // No hay foto para personas jurídicas
        const imgFoto = document.getElementById('personaFoto');
        const noFoto = document.getElementById('noFoto');
        imgFoto.style.display = 'none';
        noFoto.style.display = 'block';
    }

    // Scroll a resultados
    document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth' });
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
    
    setTimeout(() => {
        document.getElementById('alertContainer').innerHTML = '';
    }, 5000);
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