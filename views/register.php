<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
    header('Location: /dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sistema de Consultas PIDE</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="register-container">
        <div class="register-left">
            <img class="logo-register" src="<?= BASE_URL ?>assets/images/logo.png" alt="logo MDE">
        </div>
        <div class="register-right">
            <h2 class="register-title">SISTEMA DE CONSULTAS PIDE</h2>
            <p>Ingresa tus datos para crear tu usuario</p>
            <form id="formRegister" method="post">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" autocomplete="off" placeholder="ejemplo: alopezv" required>

                <label for="password">Nueva Contraseña:</label>
                <div class="password-container-register">
                    <input type="password" id="newpassword" name="newpassword" placeholder="******" required>
                    <i id="newtogglePassword" class="fas fa-eye-slash"></i>
                </div>

                <label for="password">Repite tu nueva Contraseña:</label>
                <div class="password-container-register">
                    <input type="password" id="newpassword2" name="newpassword2" placeholder="******" required>
                    <i id="newtogglePassword2" class="fas fa-eye-slash"></i>
                </div>

                <div class="containerCUI">
                    <label for="cui" class="form-label">Código único de Identificación (CUI):</label>
                    <input type="text" id="cui" maxlength="1" autocomplete="off" required>
                </div>

                <button id="btnRegister" type="submit">Registrar</button>
                <!-- 🔹 Enlace a register -->
                <p class="login-text">
                    Regresar a
                    <a id="linkLogin" href="#" class="login-link">Iniciar Sesion</a>
                </p>
            </form>
        </div>
    </div>

    <script src="<?= BASE_URL ?>assets/js/api.js"></script>
    <script src="<?= BASE_URL ?>assets/js/register.js"></script>

    <script>
        // Toggle password
        document.getElementById('newtogglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('newpassword');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });

        // Toggle password2
        document.getElementById('newtogglePassword2').addEventListener('click', function () {
            const passwordInput = document.getElementById('newpassword2');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });
    </script>
</body>
</html>