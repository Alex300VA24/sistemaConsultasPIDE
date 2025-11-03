// Variables globales
let usuarioIdActual = null;
let personaIdActual = null;

// Al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    cargarListaUsuarios();
});

/**
 * Cargar lista de usuarios en el combobox
 */
async function cargarListaUsuarios() {
    try {
        const response = await api.listarUsuarios();
        
        if (response.success && response.data) {
            const select = document.getElementById('selectorUsuario');
            
            // Limpiar opciones existentes (excepto la primera)
            select.innerHTML = '<option value="">-- Seleccione un usuario --</option>';
            
            // Agregar usuarios al select
            response.data.forEach(usuario => {
                const option = document.createElement('option');
                option.value = usuario.USU_id;
                option.textContent = `${usuario.nombre_completo} (${usuario.PER_documento_num})`;
                option.dataset.nombreCompleto = usuario.nombre_completo;
                option.dataset.documento = usuario.PER_documento_num;
                option.dataset.login = usuario.USU_login;
                select.appendChild(option);
            });
            
        } else {
            mostrarAlerta(response.message || 'Error al cargar usuarios', 'error');
        }
    } catch (error) {
        console.error('Error al cargar lista de usuarios:', error);
        mostrarAlerta('Error al cargar la lista de usuarios', 'error');
    }
}

/**
 * Cargar datos del usuario seleccionado
 */
async function cargarDatosUsuarioSeleccionado() {
    const select = document.getElementById('selectorUsuario');
    const usuarioId = select.value;
    
    if (!usuarioId) {
        // Ocultar formulario si no hay selección
        document.getElementById('formularioEdicion').style.display = 'none';
        limpiarCamposFormulario();
        return;
    }
    
    await cargarDatosUsuario(usuarioId);
}



/**
 * Cargar datos del usuario en el formulario
 */
async function cargarDatosUsuario(usuarioId) {
    try {
        mostrarCargando(true);
        
        const response = await api.obtenerUsuario(usuarioId);
        
        if (response.success && response.data) {
            const usuario = response.data;
            
            // Guardar IDs globales
            usuarioIdActual = usuario.USU_id;
            personaIdActual = usuario.PER_id;
            
            // Mostrar formulario
            document.getElementById('formularioEdicion').style.display = 'block';

            console.log(usuario)
            
            // === DATOS PERSONALES ===
            document.getElementById('perTipo-actualizar').value = String(usuario.PER_tipo ?? '');
            document.getElementById('perDocumentoTipo-actualizar').value = String(usuario.PER_documento_tipo ?? '');
            document.getElementById('per-documento-num').value = usuario.PER_documento_num || '';  // corregido ID
            document.getElementById('per-nombre').value = usuario.PER_nombre || '';
            document.getElementById('per-apellido-pat').value = usuario.PER_apellido_pat || '';
            document.getElementById('per-apellido-mat').value = usuario.PER_apellido_mat || '';
            document.getElementById('perSexo-actualizar').value = String(usuario.PER_sexo ?? '');
            document.getElementById('per-email').value = usuario.PER_email || '';
            
            // === DATOS DE USUARIO ===
            document.getElementById('usu-login').value = usuario.USU_login || '';
            document.getElementById('usuPermiso-actualizar').value = String(usuario.USU_permiso ?? '0');
            document.getElementById('usuEstado-actualizar').value = String(usuario.USU_estado ?? '1');
            document.getElementById('cui').value = usuario.cui || '';
            
            // Limpiar campos de contraseña (por seguridad)
            document.getElementById('usu-pass').value = '';
            document.getElementById('usu-passConfirm').value = '';
            
            // Desplazar al formulario
            document.getElementById('formularioEdicion').scrollIntoView({ behavior: 'smooth', block: 'start' });
            
        } else {
            mostrarAlerta(response.message || 'Error al cargar usuario', 'error');
        }
    } catch (error) {
        console.error('Error al cargar usuario:', error);
        mostrarAlerta('Error al cargar los datos del usuario', 'error');
    } finally {
        mostrarCargando(false);
    }
}


/**
 * Actualizar usuario
 */
async function actualizarUsuario() {
    try {
        console.log('Esta entrando');
        // Validar que tenemos los IDs necesarios
        console.log(usuarioIdActual, personaIdActual);
        if (!usuarioIdActual || !personaIdActual) {
            alert('Debe seleccionar un usuario primero');
            return;
        }
        console.log(validarFormulario());
        // Validar formulario
        if (!validarFormulario()) {
            alert('No se esta validando el formulario correctamente');
            return;
        }
        
        // Obtener datos del formulario
        const datos = obtenerDatosFormulario();
        console.log(datos);
        
        // Si hay contraseña nueva, validar con RENIEC primero
        if (datos.USU_pass && datos.USU_pass.trim() !== '') {
            const dniUsuario = document.getElementById('per-documento-num').value;
            
            // Crear un modal personalizado para solicitar la contraseña actual
            const passwordAnterior = await solicitarPasswordActual();
            
            if (!passwordAnterior) {
                alert('Debe ingresar la contraseña actual para cambiarla', 'warning');
                mostrarCargando(false);
                return;
            }
            
            alert('Actualizando contraseña en RENIEC...', 'info');
            
            // Actualizar en RENIEC primero
            const resultadoRENIEC = await api.actualizarPasswordRENIEC({
                credencialAnterior: passwordAnterior,
                credencialNueva: datos.USU_pass,
                nuDni: dniUsuario
            });
            
            if (!resultadoRENIEC.success) {
                alert('Error al actualizar contraseña en RENIEC: ' + resultadoRENIEC.message, 'error');
                mostrarCargando(false);
                return;
            }
            
            alert('Contraseña actualizada en RENIEC correctamente', 'success');
        }
        
        // Actualizar en base de datos
        alert('Actualizando datos en el sistema...', 'info');
        const response = await api.actualizarUsuario(datos);
        
        if (response.success) {
            alert('Usuario actualizado correctamente', 'success');
            
            // Limpiar campos de contraseña
            document.getElementById('usu-pass').value = '';
            document.getElementById('usu-passConfirm').value = '';
            
            // Recargar datos después de 1.5 segundos
            setTimeout(() => {
                cargarDatosUsuario(usuarioIdActual);
            }, 1500);
        } else {
            alert('❌ ' + (response.message || 'Error al actualizar usuario'), 'error');
        }
    } catch (error) {
        console.error('Error al actualizar usuario:', error);
        alert('❌ Error al actualizar el usuario: ' + error.message, 'error');
    } finally {
        mostrarCargando(false);
    }
}

/**
 * Solicitar contraseña actual mediante prompt
 */
function solicitarPasswordActual() {
    return new Promise((resolve) => {
        const password = prompt('🔐 Ingrese su contraseña actual de RENIEC para actualizarla:');
        resolve(password);
    });
}

/**
 * Obtener datos del formulario
 */
function obtenerDatosFormulario() {
    return {
        USU_id: usuarioIdActual,
        PER_id: personaIdActual,
        PER_tipo: parseInt(document.getElementById('perTipo-actualizar').value),
        PER_documento_tipo: parseInt(document.getElementById('perDocumentoTipo-actualizar').value),
        PER_documento_num: document.getElementById('per-documento-num').value.trim(),
        PER_nombre: document.getElementById('per-nombre').value.trim(),
        PER_apellido_pat: document.getElementById('per-apellido-pat').value.trim(),
        PER_apellido_mat: document.getElementById('per-apellido-mat').value.trim() || null,
        PER_sexo: parseInt(document.getElementById('perSexo-actualizar').value),
        PER_email: document.getElementById('per-email').value.trim() || null,
        USU_login: document.getElementById('usu-login').value.trim(),
        USU_pass: document.getElementById('usu-pass').value.trim() || null,
        USU_permiso: parseInt(document.getElementById('usuPermiso-actualizar').value),
        USU_estado: parseInt(document.getElementById('usuEstado-actualizar').value),
    };
}

/**
 * Validar formulario
 */
function validarFormulario() {
    // Validar campos requeridos
    const camposRequeridos = [
        { id: 'perTipo-actualizar', nombre: 'Tipo de Persona' },
        { id: 'perDocumentoTipo-actualizar', nombre: 'Tipo de Documento' },
        { id: 'per-documento-num', nombre: 'Número de Documento' },
        { id: 'per-nombre', nombre: 'Nombres' },
        { id: 'per-apellido-pat', nombre: 'Apellido Paterno' },
        { id: 'perSexo-actualizar', nombre: 'Sexo' },
        { id: 'usu-login', nombre: 'Login/Usuario' }
    ];
    
    for (const campo of camposRequeridos) {
        const elemento = document.getElementById(campo.id);
        if (!elemento.value || elemento.value.trim() === '') {
            mostrarAlerta(`El campo "${campo.nombre}" es requerido`, 'warning');
            elemento.focus();
            return false;
        }
    }
    
    // Validar contraseñas si se están actualizando
    const passwordActual = document.getElementById('usuPassActual').value;
    const password = document.getElementById('usu-pass').value;
    const passwordConfirm = document.getElementById('usu-passConfirm').value;
    
    if (password || passwordConfirm) {
        if (password !== passwordConfirm) {
            mostrarAlerta('Las contraseñas no coinciden', 'warning');
            document.getElementById('usu-passConfirm').focus();
            return false;
        }
        
        if (password.length < 6) {
            mostrarAlerta('La contraseña debe tener al menos 6 caracteres', 'warning');
            document.getElementById('usu-pass').focus();
            return false;
        }
    }
    
    // Validar email si se proporciona
    const email = document.getElementById('per-email').value;
    if (email && !validarEmail(email)) {
        mostrarAlerta('El formato del email no es válido', 'warning');
        document.getElementById('per-email').focus();
        return false;
    }
    
    // Validar DNI según tipo de documento
    const tipoDoc = document.getElementById('perDocumentoTipo').value;
    const numDoc = document.getElementById('per-documento-num').value.trim();
    
    if (tipoDoc === '1' && numDoc.length !== 8) {
        mostrarAlerta('El DNI debe tener 8 dígitos', 'warning');
        document.getElementById('per-documento-num').focus();
        return false;
    }
    
    if (tipoDoc === '2' && numDoc.length !== 11) {
        mostrarAlerta('El RUC debe tener 11 dígitos', 'warning');
        document.getElementById('per-documento-num').focus();
        return false;
    }
    
    return true;
}

/**
 * Validar formato de email
 */
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Limpiar formulario (recargar datos originales)
 */
function limpiarFormulario() {
    if (!usuarioIdActual) {
        limpiarCamposFormulario();
        return;
    }
    
    if (confirm('¿Está seguro de que desea recargar los datos originales del usuario?')) {
        cargarDatosUsuario(usuarioIdActual);
    }
}

/**
 * Limpiar todos los campos del formulario
 */
function limpiarCamposFormulario() {
    // Limpiar datos personales
    document.getElementById('perTipo-actualizar').value = '';
    document.getElementById('perDocumentoTipo-actualizar').value = '';
    document.getElementById('per-documento-num').value = '';
    document.getElementById('per-nombre').value = '';
    document.getElementById('per-apellido-pat').value = '';
    document.getElementById('per-apellido-mat').value = '';
    document.getElementById('perSexo-actualizar').value = '';
    document.getElementById('per-email').value = '';
    
    // Limpiar datos de usuario
    document.getElementById('usu-login').value = '';
    document.getElementById('usuPassActual').value = '';
    document.getElementById('usu-pass').value = '';
    document.getElementById('usu-passConfirm').value = '';
    document.getElementById('usuPermiso-actualizar').value = '';
    document.getElementById('usuEstado-actualizar').value = '1';
    
    // Resetear variables globales
    usuarioIdActual = null;
    personaIdActual = null;
}

/**
 * Mostrar alerta
 */
function mostrarAlerta(mensaje, tipo = 'info') {
    const alertContainer = document.getElementById('alertContainer');
    
    const tiposIconos = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo}`;
    alerta.innerHTML = `
        <i class="fas ${tiposIconos[tipo]}"></i>
        <span>${mensaje}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    alertContainer.innerHTML = '';
    alertContainer.appendChild(alerta);
    
    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        if (alerta.parentElement) {
            alerta.remove();
        }
    }, 5000);
}

/**
 * Mostrar/ocultar indicador de carga
 */
function mostrarCargando(mostrar) {
    const btnActualizar = document.getElementById('btnActualizar');
    if (!btnActualizar) return;
    
    const loading = btnActualizar.querySelector('.loading');
    const icon = btnActualizar.querySelector('i.fa-save');
    
    if (mostrar) {
        btnActualizar.disabled = true;
        if (loading) loading.style.display = 'inline-block';
        if (icon) icon.style.display = 'none';
    } else {
        btnActualizar.disabled = false;
        if (loading) loading.style.display = 'none';
        if (icon) icon.style.display = 'inline';
    }
}