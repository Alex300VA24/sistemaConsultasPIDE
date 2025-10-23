document.addEventListener('DOMContentLoaded', function() {
    const formLogin = document.getElementById('formLogin');
    const formValidarCUI = document.getElementById('validarCUIForm');
    const modalCUI = document.getElementById('modalValidarCUI');
    const btnCancelarCUI = document.getElementById('btnCancelarCUI');
    const btnLogin = document.getElementById('btnLogin');

    // Login
    formLogin.addEventListener('submit', async function(e) {
        e.preventDefault();
        const nombreUsuario = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        try {
            btnLogin.disabled = true;
            btnLogin.textContent = 'Ingresando...';
            
            const response = await api.login(nombreUsuario, password);
            
            if (response.success && response.data.requireCUI) {
                // Mostrar modal de CUI
                modalCUI.style.display = 'block';
                sessionStorage.setItem('usuarioID', response.data.usuarioID);
            }
        } catch (error) {
            alert('Error: ' + error.message);
            btnLogin.disabled = false;
            btnLogin.textContent = 'Ingresar';
        }
    });

    // Validar CUI
    formValidarCUI.addEventListener('submit', async function(e) {
        e.preventDefault();

        const cui = document.getElementById('cui').value;
        if (!cui || cui.length < 1) {
            alert('Por favor ingrese un CUI válido');
            return;
        }

        try {
            const btnConfirmar = document.getElementById('btnConfirmarCUI');
            btnConfirmar.disabled = true;
            btnConfirmar.value = 'Validando...';
            
            const response = await api.validarCUI(cui);
            if (response.success) {
                window.location.href = 'dashboard';
            }
        } catch (error) {
            alert('Error: ' + error.message);
            const btnConfirmar = document.getElementById('btnConfirmarCUI');
            btnConfirmar.disabled = false;
            btnConfirmar.value = 'Confirmar';
        }
    });

    // Cancelar CUI
    if (btnCancelarCUI) {
        btnCancelarCUI.addEventListener('click', function() {
            if (modalCUI) {
                modalCUI.style.display = 'none';
            } else {
                console.warn('⚠️ No se encontró el modal con id="modalValidarCUI"');
            }
            sessionStorage.clear();
            btnLogin.disabled = false;
            btnLogin.textContent = 'Ingresar';
        });
    }
});
