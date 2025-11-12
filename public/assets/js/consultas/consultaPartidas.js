// consulta-partidas.js

let personaSeleccionada = null;
let tipoPersonaActual = 'natural';

// Al inicio del archivo
let credencialesUsuario = {
    dni: '',
    password: ''
};

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

    // Botón consultar
    document.getElementById('btnConsultar').addEventListener('click', consultarPartida);

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

// Actualizar función buscarPersonaNatural
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
        console.log('Credenciales:', { dni, credencialesUsuario });
        const resultado = await api.buscarPersonaNaturalSunarp(
            dni,
            credencialesUsuario.dni,
            credencialesUsuario.password
        );
        console.log(resultado);
        mostrarResultadosNatural(resultado.data || []);
        if (resultado.message) {
            mostrarAlertaPartidas(resultado.message, 'info');
        }
    } catch (error) {
        mostrarAlertaPartidas(error.message || 'Error al buscar persona natural', 'danger');
    } finally {
        ocultarLoadingPartidas('formBusquedaNatural');
    }
}

async function buscarPersonaJuridica(e) {
    e.preventDefault();
    
    const tipoBusqueda = document.querySelector('input[name="tipoBusquedaJuridica"]:checked').value;
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
        const resultado = await api.buscarPersonaJuridicaSunarp(
            parametro,
            tipoBusqueda,
            credencialesUsuario.dni,
            credencialesUsuario.password
        );
        mostrarResultadosJuridica(resultado.data || []);
    } catch (error) {
        mostrarAlertaPartidas(error.message || 'Error al buscar persona jurídica', 'danger');
    } finally {
        ocultarLoadingPartidas('formBusquedaJuridica');
    }
}

function mostrarResultadosNatural(datos) {
    const contenedor = document.getElementById('resultadosNatural');
    
    if (!datos || datos.length === 0) {
        contenedor.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No se encontraron resultados</div>';
        contenedor.style.display = 'block';
        return;
    }

    let html = `
        <table>
            <thead>
                <tr>
                    <th>DNI</th>
                    <th>Nombres</th>
                    <th>Apellido Paterno</th>
                    <th>Apellido Materno</th>
                    <th>Información Adicional</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
    `;

    datos.forEach(persona => {
        let infoAdicional = '';
        if (persona.profesion) {
            infoAdicional += `<div><strong>Profesión:</strong> ${persona.profesion}</div>`;
        }
        if (persona.tipoVerificador) {
            infoAdicional += `<div><strong>Tipo:</strong> ${persona.tipoVerificador}</div>`;
        }
        if (persona.zonaRegistral) {
            infoAdicional += `<div><strong>Zona:</strong> ${persona.zonaRegistral}</div>`;
        }
        if (persona.estado) {
            infoAdicional += `<div><strong>Estado:</strong> ${persona.estado === 'A' ? 'Activo' : 'Inactivo'}</div>`;
        }

        html += `
            <tr>
                <td>${persona.dni || '-'}</td>
                <td>${persona.nombres || '-'}</td>
                <td>${persona.apellidoPaterno || '-'}</td>
                <td>${persona.apellidoMaterno || '-'}</td>
                <td style="font-size: 0.85em;">${infoAdicional || '-'}</td>
                <td>
                    <button class="btn-select" onclick='seleccionarPersona(${JSON.stringify(persona)})'>
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

function mostrarResultadosJuridica(datos) {
    const contenedor = document.getElementById('resultadosJuridica');
    
    if (!datos || datos.length === 0) {
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
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
    `;

    datos.forEach(empresa => {
        let infoAdicional = '';
        if (empresa.direccion) {
            infoAdicional += `<div><strong>Dirección:</strong> ${empresa.direccion}</div>`;
        }
        if (empresa.departamento && empresa.provincia && empresa.distrito) {
            infoAdicional += `<div><strong>Ubicación:</strong> ${empresa.departamento} / ${empresa.provincia} / ${empresa.distrito}</div>`;
        }
        if (empresa.tipo_contribuyente) {
            infoAdicional += `<div><strong>Tipo:</strong> ${empresa.tipo_contribuyente}</div>`;
        }
        
        // Información de partidas SUNARP
        if (empresa.partidas_sunarp && empresa.partidas_sunarp.length > 0) {
            infoAdicional += `<div style="margin-top: 5px;"><strong>Partidas SUNARP:</strong> ${empresa.partidas_sunarp.length} encontrada(s)</div>`;
        }

        const estadoClass = empresa.es_activo ? 'badge-success' : 'badge-danger';
        const estadoTexto = empresa.es_activo ? 'Activo' : 'Inactivo';

        html += `
            <tr>
                <td>${empresa.ruc || '-'}</td>
                <td>${empresa.razonSocial || '-'}</td>
                <td><span class="badge ${estadoClass}">${estadoTexto}</span></td>
                <td style="font-size: 0.85em;">${infoAdicional || '-'}</td>
                <td>
                    <button class="btn-select" onclick='seleccionarPersona(${JSON.stringify(empresa)})'>
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

function seleccionarPersona(persona) {
    personaSeleccionada = persona;
    
    const inputPersona = document.getElementById('persona');
    if (tipoPersonaActual === 'natural') {
        inputPersona.value = `${persona.nombres} ${persona.apellidoPaterno} ${persona.apellidoMaterno}`;
        cerrarModal('modalBusquedaNatural');
    } else {
        inputPersona.value = persona.razonSocial;
        cerrarModal('modalBusquedaJuridica');
    }
    
    document.getElementById('btnConsultar').disabled = false;
    mostrarAlertaPartidas('Persona seleccionada correctamente', 'success');
}

async function consultarPartida() {
    if (!personaSeleccionada) {
        mostrarAlertaPartidas('Por favor seleccione una persona primero', 'warning');
        return;
    }

    if (!credencialesUsuario.dni || !credencialesUsuario.password) {
        mostrarAlertaPartidas('No se han cargado las credenciales del usuario. Recargue la página.', 'danger');
        return;
    }

    const btnConsultar = document.getElementById('btnConsultar');
    btnConsultar.disabled = true;
    btnConsultar.innerHTML = '<span class="loading-spinner"></span> <span>Consultando...</span>';

    try {
        console.log('Credenciales para consultar Partida registral: ', { personaSeleccionada, credencialesUsuario });
        const resultado = await api.consultarPartidaRegistral(
            personaSeleccionada,
            credencialesUsuario.dni,
            credencialesUsuario.password
        );
        mostrarResultados(resultado.data);
        mostrarAlertaPartidas('Consulta realizada exitosamente', 'success');
    } catch (error) {
        mostrarAlertaPartidas(error.message || 'Error al consultar partida registral', 'danger');
    } finally {
        btnConsultar.disabled = false;
        btnConsultar.innerHTML = '<i class="fas fa-search"></i> <span>Consultar</span>';
    }
}

function mostrarResultados(datos) {
    // Mostrar sección de resultados
    document.getElementById('resultsSection').style.display = 'block';

    // Llenar campos
    document.getElementById('registro').textContent = datos.registro || '';
    document.getElementById('libro').textContent = datos.libro || '';
    document.getElementById('apellidoPaterno').textContent = datos.apellidoPaterno || '';
    document.getElementById('apellidoMaterno').textContent = datos.apellidoMaterno || '';
    document.getElementById('nombres').textContent = datos.nombres || '';
    document.getElementById('tipoDoc').textContent = datos.tipoDocumento || '';
    document.getElementById('nroDoc').textContent = datos.nroDocumento || '';
    document.getElementById('nroPartida').textContent = datos.nroPartida || '';
    document.getElementById('nroPlaca').textContent = datos.nroPlaca || '';
    document.getElementById('estado').textContent = datos.estado || '';
    document.getElementById('zona').textContent = datos.zona || '';
    document.getElementById('oficina').textContent = datos.oficina || '';
    document.getElementById('direccion').textContent = datos.direccion || '';

    // Manejar foto
    const imgFoto = document.getElementById('personaFoto');
    const noFoto = document.getElementById('noFoto');
    
    if (datos.foto) {
        imgFoto.src = datos.foto;
        imgFoto.style.display = 'block';
        noFoto.style.display = 'none';
    } else {
        imgFoto.style.display = 'none';
        noFoto.style.display = 'block';
    }

    // Scroll a resultados
    document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth' });
}

function limpiarFormularioPartidas() {
    personaSeleccionada = null;
    document.getElementById('persona').value = '';
    document.getElementById('btnConsultar').disabled = true;
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

// Validación solo números
document.getElementById('dniNatural').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

document.getElementById('rucJuridica').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});