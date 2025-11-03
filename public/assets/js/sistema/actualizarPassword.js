// ============================================
// ACTUALIZAR CONTRASEÑA DEL USUARIO ACTUAL
// ============================================

// Variables globales del usuario actual (desde sesión)
let usuarioActual = {
    id: null,
    personaId: null,
    dni: null,
    login: null,
    nombreCompleto: null
};

/**
 * Al cargar la página, obtener datos del usuario actual
 */
document.addEventListener('DOMContentLoaded', async function() {
    await cargarDatosUsuarioActual();
});

/**
 * Cargar datos del usuario actual desde la sesión
 */
async function cargarDatosUsuarioActual() {
    try {
        // Obtener el ID del usuario desde la sesión PHP
        const response = await api.obtenerUsuarioActual();
        
        if (response.success && response.data) {
            const usuario = response.data;
            
            // Guardar datos globalmente
            usuarioActual = {
                id: usuario.USU_id,
                personaId: usuario.PER_id,
                dni: usuario.PER_documento_num,
                login: usuario.USU_login,
                nombreCompleto: `${usuario.PER_nombre} ${usuario.PER_apellido_pat} ${usuario.PER_apellido_mat || ''}`.trim()
            };
            
            // Mostrar información del usuario en la interfaz
            console.log(usuarioActual);
            mostrarInfoUsuario();
            
            console.log('Usuario actual cargado:', usuarioActual);
        } else {
            mostrarAlerta('Error al cargar datos del usuario actual', 'error');
        }
    } catch (error) {
        console.error('Error al cargar usuario actual:', error);
        mostrarAlerta('Error al cargar los datos del usuario', 'error');
    }
}

/**
 * Mostrar información del usuario en la interfaz
 */
function mostrarInfoUsuario() {
    const infoElement = document.getElementById('infoUsuarioActual');
    if (infoElement) {
        infoElement.innerHTML = `
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #007bff;">
                <strong><i class="fas fa-user"></i> Usuario:</strong> ${usuarioActual.nombreCompleto}<br>
                <strong><i class="fas fa-id-card"></i> DNI:</strong> ${usuarioActual.dni}<br>
                <strong><i class="fas fa-sign-in-alt"></i> Login:</strong> ${usuarioActual.login}
            </div>
        `;
    }
}

/**
 * Actualizar contraseña del usuario actual
 */
async function actualizarPasswordUsuarioActual() {
    try {
        mostrarCargando(true);
        
        // Validar que tengamos los datos del usuario
        if (!usuarioActual.id || !usuarioActual.dni) {
            mostrarAlerta('No se encontraron los datos del usuario. Por favor, recargue la página.', 'error');
            mostrarCargando(false);
            return;
        }
        
        // Validar formulario
        if (!validarFormularioPassword()) {
            mostrarCargando(false);
            return;
        }
        
        // Obtener datos del formulario
        const datos = obtenerDatosFormularioPassword();
        
        console.log('Iniciando actualización de contraseña...');
        console.log('DNI:', usuarioActual.dni);
        
        // PASO 1: Actualizar en RENIEC
        mostrarAlerta('Actualizando contraseña en RENIEC...', 'info');
        
        const resultadoRENIEC = await api.actualizarPasswordRENIEC({
            credencialAnterior: datos.passwordActual,
            credencialNueva: datos.passwordNueva,
            nuDni: usuarioActual.dni
        });
        
        if (!resultadoRENIEC.success) {
            mostrarAlerta('Error al actualizar contraseña en RENIEC: ' + resultadoRENIEC.message, 'error');
            mostrarCargando(false);
            return;
        }
        
        console.log('Contraseña actualizada en RENIEC correctamente');
        mostrarAlerta('✓ Contraseña actualizada en RENIEC correctamente', 'success');
        
        // PASO 2: Actualizar en base de datos local
        mostrarAlerta('Actualizando contraseña en el sistema local...', 'info');
        
        const response = await api.actualizarUsuario({
            USU_id: usuarioActual.id,
            PER_id: usuarioActual.personaId,
            USU_pass: datos.passwordNueva
        });
        
        if (response.success) {
            mostrarAlerta('✓ Contraseña actualizada correctamente en ambos sistemas', 'success');
            
            // Limpiar campos después de 2 segundos
            setTimeout(() => {
                limpiarCamposFormularioPassword();
                mostrarAlerta('Contraseña actualizada exitosamente. Por seguridad, será redirigido al login.', 'info');
                
                // Redirigir al login después de 3 segundos
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 3000);
            }, 2000);
        } else {
            mostrarAlerta('Error al actualizar contraseña en el sistema: ' + (response.message || 'Error desconocido'), 'error');
        }
        
    } catch (error) {
        console.error('Error al actualizar contraseña:', error);
        mostrarAlerta('Error al actualizar la contraseña: ' + error.message, 'error');
    } finally {
        mostrarCargando(false);
    }
}

/**
 * Obtener datos del formulario de contraseña
 */
function obtenerDatosFormularioPassword() {
    return {
        passwordActual: document.getElementById('usuPassActualPassword').value.trim(),
        passwordNueva: document.getElementById('usu-passPassword').value.trim(),
        passwordConfirm: document.getElementById('usu-passConfirmPassword').value.trim()
    };
}

/**
 * Validar formulario de contraseña
 */
function validarFormularioPassword() {
    const datos = obtenerDatosFormularioPassword();
    
    // Validar que se haya ingresado la contraseña actual
    if (!datos.passwordActual) {
        mostrarAlerta('Debe ingresar la contraseña actual', 'warning');
        document.getElementById('usuPassActualPassword').focus();
        return false;
    }
    
    // Validar que se haya ingresado la nueva contraseña
    if (!datos.passwordNueva) {
        mostrarAlerta('Debe ingresar la nueva contraseña', 'warning');
        document.getElementById('usu-passPassword').focus();
        return false;
    }
    
    // Validar longitud mínima de contraseña
    if (datos.passwordNueva.length < 6) {
        mostrarAlerta('La nueva contraseña debe tener al menos 6 caracteres', 'warning');
        document.getElementById('usu-passPassword').focus();
        return false;
    }
    
    // Validar que las contraseñas coincidan
    if (datos.passwordNueva !== datos.passwordConfirm) {
        mostrarAlerta('Las contraseñas nuevas no coinciden', 'warning');
        document.getElementById('usu-passConfirmPassword').focus();
        return false;
    }
    
    // Validar que la nueva contraseña sea diferente a la actual
    if (datos.passwordActual === datos.passwordNueva) {
        mostrarAlerta('La nueva contraseña debe ser diferente a la actual', 'warning');
        document.getElementById('usu-passPassword').focus();
        return false;
    }
    
    return true;
}

/**
 * Limpiar todos los campos del formulario
 */
function limpiarCamposFormularioPassword() {
    document.getElementById('usuPassActualPassword').value = '';
    document.getElementById('usu-passPassword').value = '';
    document.getElementById('usu-passConfirmPassword').value = '';
    
    // Limpiar alertas
    const alertContainer = document.getElementById('alertContainerPassword');
    if (alertContainer) {
        alertContainer.innerHTML = '';
    }
}

/**
 * Mostrar alerta
 */
function mostrarAlerta(mensaje, tipo = 'info') {
    const alertContainer = document.getElementById('alertContainerPassword');
    
    if (!alertContainer) {
        console.warn('No se encontró el contenedor de alertas');
        return;
    }
    
    const tiposIconos = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const tiposColores = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo}`;
    alerta.style.cssText = `
        padding: 15px 20px;
        margin-bottom: 15px;
        border-radius: 8px;
        background-color: ${tiposColores[tipo]}15;
        border-left: 4px solid ${tiposColores[tipo]};
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.3s ease-out;
    `;
    
    alerta.innerHTML = `
        <i class="fas ${tiposIconos[tipo]}" style="color: ${tiposColores[tipo]}; font-size: 20px;"></i>
        <span style="flex: 1; color: #333;">${mensaje}</span>
        <button class="alert-close" onclick="this.parentElement.remove()" style="
            background: none;
            border: none;
            color: ${tiposColores[tipo]};
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        " onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Limpiar alertas anteriores si es de tipo error o warning
    if (tipo === 'error' || tipo === 'warning') {
        alertContainer.innerHTML = '';
    }
    
    alertContainer.appendChild(alerta);
    
    // Auto-cerrar después de 5 segundos (excepto errores que se cierran en 8 segundos)
    const timeout = tipo === 'error' ? 8000 : 5000;
    setTimeout(() => {
        if (alerta.parentElement) {
            alerta.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => alerta.remove(), 300);
        }
    }, timeout);
}

/**
 * Mostrar/ocultar indicador de carga
 */
function mostrarCargando(mostrar) {
    const btnActualizar = document.getElementById('btnActualizarPassword');
    if (!btnActualizar) return;
    
    const loading = btnActualizar.querySelector('.loading');
    const icon = btnActualizar.querySelector('i.fa-save');
    
    if (mostrar) {
        btnActualizar.disabled = true;
        btnActualizar.style.opacity = '0.7';
        btnActualizar.style.cursor = 'not-allowed';
        
        if (loading) {
            loading.style.display = 'inline-block';
            loading.style.cssText = `
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid #ffffff;
                border-top-color: transparent;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
                margin-right: 8px;
            `;
        }
        if (icon) icon.style.display = 'none';
    } else {
        btnActualizar.disabled = false;
        btnActualizar.style.opacity = '1';
        btnActualizar.style.cursor = 'pointer';
        
        if (loading) loading.style.display = 'none';
        if (icon) icon.style.display = 'inline';
    }
}

// Agregar estilos de animación
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(-100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(-100%);
            opacity: 0;
        }
    }
    
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
`;
document.head.appendChild(style);