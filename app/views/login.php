<?php
header("Content-type: text/html; charset=utf-8");

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
    header('Location: ' . BASE_URL . 'dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es-PE">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema PIDE</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/tailwind.css">
    <link rel="icon" href="<?= BASE_URL ?>assets/images/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/login.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/fonts.css">
</head>

<body>

    <div class="login-container">
        <!-- Left Panel - Sidebar Style (Blue) -->
        <div class="login-left">
            <div class="header-section" style="text-align:center; display:flex; flex-direction:column; align-items:center;">
                <img class="muni-logo" src="<?= BASE_URL ?>assets/images/muni2.png" alt="Logo" style="display:block; margin:0 auto 15px;">
                <h2>Sistema PIDE</h2>
                <p style="text-align:center;">Municipalidad Distrital de La Esperanza</p>
            </div>

            <div class="divider-bar"></div>

            <div class="entity-cards">
                <div class="entity-card reniec">
                    <div class="entity-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="entity-info">
                        <h4>RENIEC</h4>
                        <p>Registro Nacional de Identificación y Estado Civil</p>
                    </div>
                </div>

                <div class="entity-card sunat">
                    <div class="entity-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="entity-info">
                        <h4>SUNAT</h4>
                        <p>Superintendencia Nacional de Aduanas</p>
                    </div>
                </div>

                <div class="entity-card sunarp">
                    <div class="entity-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="entity-info">
                        <h4>SUNARP</h4>
                        <p>Superintendencia de Registros Públicos</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="login-right">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-user-lock"></i>
                </div>
                <h2 class="login-title">Bienvenido</h2>
                <p class="login-subtitle">Ingrese sus credenciales para acceder</p>
            </div>

            <form id="formLogin" method="post" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>Usuario
                    </label>
                    <div class="input-wrapper has-icon">
                        <input type="text" id="username" name="username" autocomplete="off" placeholder="Ingrese su usuario" required>
                        <i class="fas fa-user icon-left"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>Contraseña
                    </label>
                    <div class="input-wrapper password-container has-icon">
                        <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required>
                        <i class="fas fa-lock icon-left"></i>
                        <i class="fas fa-eye-slash toggle-password" id="togglePassword"></i>
                    </div>
                </div>

                <button type="submit" id="btnLogin" class="btn">
                    <i class="fas fa-sign-in-alt mr-2"></i>Ingresar al Sistema
                </button>
            </form>

            <div class="system-status">
                <div class="status-dot"></div>
                <span>Sistema operativo</span>
            </div>

            <div class="footer-brand">
                <p>Sistema de Consultas PIDE</p>
                <p>Municipalidad Distrital de La Esperanza</p>
            </div>
        </div>
    </div>

    <!-- Modal CUI -->
    <div id="modalValidarCUI" class="modalCUI">
        <div class="flex items-center justify-center min-h-screen p-4 py-8">
            <div class="bg-white rounded-2xl w-full max-w-md border border-slate-200 shadow-xl modal-content-cui">
                <h5 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-shield-alt text-blue-500"></i>
                    Autenticación de Doble Factor — CUI
                </h5>

                <form id="validarCUIForm" method="post">
                    <div class="mb-4 sm:mb-6">
                        <p class="text-slate-500 text-sm mb-3 sm:mb-4">
                            Busca tu Código Único de Identificación (CUI) en tu DNI e ingrésalo a continuación.
                        </p>
                        <div class="bg-slate-50 rounded-xl p-3 sm:p-4 mb-3 sm:mb-4 border border-slate-200">
                            <img src="<?= BASE_URL ?>assets/images/dniGuiCUI.svg" alt="Guía CUI DNI" class="w-full max-w-[200px] sm:max-w-xs mx-auto">
                        </div>
                        <label for="cui" class="login-label block text-sm font-medium mb-2">Código único de Identificación (CUI):</label>
                        <input type="text" id="cui" maxlength="1" autocomplete="off" required
                               class="cui-input w-14 h-14 sm:w-16 sm:h-16 mx-auto block text-center text-2xl sm:text-3xl font-bold rounded-xl transition-all tracking-widest"
                               inputmode="numeric" pattern="[0-9]">
                    </div>
                    <div class="flex gap-3">
                        <button type="button" id="btnCancelarCUI"
                                class="flex-1 py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg transition-colors text-sm">
                            Cancelar
                        </button>
                        <button type="submit" id="btnConfirmarCUI"
                                class="login-btn flex-1 py-2.5 px-4 text-white font-medium rounded-lg text-sm">
                            Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= BASE_URL ?>assets/js/core/constants.js"></script>
    <script src="<?= BASE_URL ?>assets/js/core/events.js"></script>
    <script src="<?= BASE_URL ?>assets/js/utils/storage.js"></script>
    <script src="<?= BASE_URL ?>assets/js/utils/dom.js"></script>
    <script src="<?= BASE_URL ?>assets/js/utils/validator.js"></script>
    <script src="<?= BASE_URL ?>assets/js/utils/alerts.js"></script>
    <script src="<?= BASE_URL ?>assets/js/utils/loading.js"></script>
    <script src="<?= BASE_URL ?>assets/js/core/api.js"></script>
    <script src="<?= BASE_URL ?>assets/js/modules/auth/login.js"></script>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });

        document.getElementById('cui').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>

</html>