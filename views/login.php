<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
    header('Location: ' . BASE_URL . 'dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Consultas PIDE</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="logo-container">
                <img class="logo-login" src="<?= BASE_URL ?>assets/images/logo.png" alt="Logo Municipalidad">
                <div class="divider"></div>
                <div class="institution-info">
                    <h3>Sistema de Consultas PIDE</h3>
                    <p>Plataforma de Interoperabilidad del Estado</p>
                </div>
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-header">
                <h2 class="login-title">Iniciar Sesión</h2>
                <p class="login-subtitle">Ingrese sus credenciales para acceder</p>
            </div>
            
            <form id="formLogin" method="post">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" autocomplete="off" placeholder="Ingrese su usuario" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper password-container">
                        <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required>
                        <i id="togglePassword" class="fas fa-eye-slash toggle-password"></i>
                    </div>
                </div>

                <button id="btnLogin" type="submit">INGRESAR AL SISTEMA</button>
            </form>
        </div>
    </div>

    <!-- Modal CUI -->
    <div id="modalValidarCUI" class="modalCUI modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Autenticación de Doble Factor</h5>
                </div>
                <form class="formValidarCUI" id="validarCUIForm" method="post">
                    <div class="modal-body">
                        <div class="containerGuiaCUI">
                            <p>
                                Por favor, ingrese el último dígito de su Código Único de Identificación (CUI) que se encuentra en su DNI.
                            </p>
                            <img src="<?= BASE_URL ?>assets/images/dniGuiCUI.svg" alt="Guía ubicación CUI en DNI">
                        </div>

                        <div class="containerCUI">
                            <label for="cui">Último dígito del CUI:</label>
                            <input type="text" id="cui" maxlength="1" autocomplete="off" pattern="[0-9]" required>
                        </div>
                        <div class="containerButtonsModals">
                            <input type="submit" id="btnConfirmarCUI" class="btn btn-submit" value="Confirmar">
                            <input type="button" id="btnCancelarCUI" class="btn btn-cancel" value="Cancelar">
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>assets/js/api.js"></script>
    <script src="<?= BASE_URL ?>assets/js/login.js"></script>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });

        // Prevent non-numeric input in CUI field
        document.getElementById('cui').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>

<?php
?>

