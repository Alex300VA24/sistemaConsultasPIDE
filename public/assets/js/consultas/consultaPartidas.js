// consulta-partidas.js

let personaSeleccionada = null;
let tipoPersonaActual = 'natural';

// Al inicio del archivo, obtener credenciales del usuario
let credencialesSunarp = {
    usuario: '',
    clave: ''
};

// Función para cargar credenciales
async function cargarCredencialesSunarp() {
    try {
        const usuario = await api.obtenerUsuarioActual();
        if (usuario && usuario.data) {
            credencialesSunarp.usuario = usuario.data.dni || '';
            // La clave debe ser obtenida de alguna forma segura
            // Podrías tener un campo específico o pedirla al usuario
        }
    } catch (error) {
        console.error('Error al cargar credenciales:', error);
    }
}

// Llamar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    cargarCredencialesSunarp();
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
    document.getElementById('btnLimpiar').addEventListener('click', limpiarFormulario);

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
}

function cambiarTipoPersona(e) {
    tipoPersonaActual = e.target.value;
    limpiarFormulario();
    
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

async function buscarPersonaNatural(e) {
    e.preventDefault();
    
    const dni = document.getElementById('dniNatural').value.trim();
    
    if (!dni || dni.length !== 8) {
        mostrarAlerta('Por favor ingrese un DNI válido de 8 dígitos', 'warning');
        return;
    }

    if (!credencialesSunarp.usuario || !credencialesSunarp.clave) {
        mostrarAlerta('No se han configurado las credenciales de SUNARP', 'danger');
        return;
    }

    mostrarLoading('formBusquedaNatural');
    
    try {
        const resultado = await api.buscarPersonaNaturalSunarp(
            dni,
            credencialesSunarp.usuario,
            credencialesSunarp.clave
        );
        mostrarResultadosNatural(resultado.data || []);
    } catch (error) {
        mostrarAlerta(error.message || 'Error al buscar persona natural', 'danger');
    } finally {
        ocultarLoading('formBusquedaNatural');
    }
}

async function buscarPersonaJuridica(e) {
    e.preventDefault();
    
    const tipoBusqueda = document.querySelector('input[name="tipoBusquedaJuridica"]:checked').value;
    let parametro;
    
    if (tipoBusqueda === 'ruc') {
        parametro = document.getElementById('rucJuridica').value.trim();
        if (!parametro || parametro.length !== 11) {
            mostrarAlerta('Por favor ingrese un RUC válido de 11 dígitos', 'warning');
            return;
        }
    } else {
        parametro = document.getElementById('razonSocial').value.trim();
        if (!parametro) {
            mostrarAlerta('Por favor ingrese una razón social', 'warning');
            return;
        }
    }

    if (!credencialesSunarp.usuario || !credencialesSunarp.clave) {
        mostrarAlerta('No se han configurado las credenciales de SUNARP', 'danger');
        return;
    }

    mostrarLoading('formBusquedaJuridica');
    
    try {
        const resultado = await api.buscarPersonaJuridicaSunarp(
            parametro,
            tipoBusqueda,
            credencialesSunarp.usuario,
            credencialesSunarp.clave
        );
        mostrarResultadosJuridica(resultado.data || []);
    } catch (error) {
        mostrarAlerta(error.message || 'Error al buscar persona jurídica', 'danger');
    } finally {
        ocultarLoading('formBusquedaJuridica');
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
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
    `;

    datos.forEach(persona => {
        html += `
            <tr>
                <td>${persona.dni || '-'}</td>
                <td>${persona.nombres || '-'}</td>
                <td>${persona.apellidoPaterno || '-'}</td>
                <td>${persona.apellidoMaterno || '-'}</td>
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
        html += `
            <tr>
                <td>${empresa.ruc || '-'}</td>
                <td>${empresa.razonSocial || '-'}</td>
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
    mostrarAlerta('Persona seleccionada correctamente', 'success');
}

async function consultarPartida() {
    if (!personaSeleccionada) {
        mostrarAlerta('Por favor seleccione una persona primero', 'warning');
        return;
    }

    if (!credencialesSunarp.usuario || !credencialesSunarp.clave) {
        mostrarAlerta('No se han configurado las credenciales de SUNARP', 'danger');
        return;
    }

    const btnConsultar = document.getElementById('btnConsultar');
    btnConsultar.disabled = true;
    btnConsultar.innerHTML = '<span class="loading-spinner"></span> <span>Consultando...</span>';

    try {
        const resultado = await api.consultarPartidaRegistral(
            personaSeleccionada,
            tipoPersonaActual,
            credencialesSunarp.usuario,
            credencialesSunarp.clave
        );
        mostrarResultados(resultado.data);
        mostrarAlerta('Consulta realizada exitosamente', 'success');
    } catch (error) {
        mostrarAlerta(error.message || 'Error al consultar partida registral', 'danger');
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

function limpiarFormulario() {
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

function mostrarAlerta(mensaje, tipo) {
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

function mostrarLoading(formId) {
    const form = document.getElementById(formId);
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="loading-spinner"></span> <span>Buscando...</span>';
}

function ocultarLoading(formId) {
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