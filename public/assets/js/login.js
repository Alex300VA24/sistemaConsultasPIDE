document.addEventListener('DOMContentLoaded', function() {
    const formLogin = document.getElementById('formLogin');
    const formValidarCUI = document.getElementById('validarCUIForm');
    const modalCUI = document.getElementById('modalValidarCUI');
    const btnCancelarCUI = document.getElementById('btnCancelarCUI');
    const btnLogin = document.getElementById('btnLogin');
    const loadingOverlay = document.getElementById('loadingOverlay');

    // Función para mostrar loading
    function showLoading() {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
    }

    // Función para ocultar loading
    function hideLoading() {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }

    // Login
    formLogin.addEventListener('submit', async function(e) {
        e.preventDefault();
        const nombreUsuario = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        try {
            btnLogin.disabled = true;
            const originalText = btnLogin.innerHTML;
            btnLogin.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Ingresando...';
            
            const response = await api.login(nombreUsuario, password);
            
            if (response.success && response.data.requireCUI) {
                // Mostrar modal de CUI
                modalCUI.style.display = 'flex';
                sessionStorage.setItem('usuarioID', response.data.usuarioID);
                localStorage.setItem('usuario', nombreUsuario);
                btnLogin.disabled = false;
                btnLogin.innerHTML = originalText;
            }
        } catch (error) {
            alert('Error: ' + error.message);
            btnLogin.disabled = false;
            btnLogin.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>INGRESAR AL SISTEMA';
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
            const originalText = btnConfirmar.innerHTML;
            btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Validando...';
            
            const response = await api.validarCUI(cui);
            if (response.success) {
                if (response.data.permisos) {
                    sessionStorage.setItem('permisos', JSON.stringify(response.data.permisos));
                }
                
                sessionStorage.setItem('usuario', JSON.stringify(response.data.usuario));
                sessionStorage.setItem('permisos', JSON.stringify(response.data.permisos));

                if (response.data.requiere_cambio_password) {
                    sessionStorage.setItem('requiere_cambio_password', 'true');
                } else {
                    sessionStorage.setItem('requiere_cambio_password', 'false');
                }

                sessionStorage.setItem('dias_desde_cambio', response.data.dias_desde_cambio);
                sessionStorage.setItem('dias_restantes', response.data.dias_restantes);

                // AGREGAR ESTA LÍNEA
                sessionStorage.setItem('loginReciente', 'true');
                
                // Ocultar modal y mostrar loading
                modalCUI.style.display = 'none';
                showLoading();
                
                setTimeout(() => {
                    window.location.href = 'dashboard';
                }, 1000);
            }
        } catch (error) {
            alert('Error: ' + error.message);
            const btnConfirmar = document.getElementById('btnConfirmarCUI');
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="fas fa-check mr-2"></i>Confirmar';
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
            btnLogin.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>INGRESAR AL SISTEMA';
        });
    }

});
