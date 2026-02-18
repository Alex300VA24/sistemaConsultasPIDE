<?php
header("Content-type: text/html; charset=utf-8");
?>

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
<html lang="es-PE">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Consultas PIDE</title>

    <!-- Tailwind CSS (local) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/tailwind.css">

    <!-- Fuente Inter (local) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/fonts.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/login.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/fontawesome/css/all.min.css">

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        /* Glassmorphism utilities */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .glass-dark {
            background: rgba(30, 58, 138, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        /* Gradient backgrounds */
        .bg-gradient-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Pulse animation */
        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .pulse-dot {
            animation: pulse-dot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Float animation */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .float {
            animation: float 6s ease-in-out infinite;
        }

        /* Modal centering fix */
        #modalValidarCUI,
        #loadingOverlay {
            display: none;
        }

        #modalValidarCUI.show,
        #loadingOverlay.show {
            display: flex !important;
        }
    </style>
</head>

<body class=" bg-gradient-modern min-h-screen flex items-center justify-center p-4 overflow-hidden relative">

    <!-- Decorative elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-72 h-72 bg-white/10 rounded-full blur-3xl float"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl float" style="animation-delay: 2s;"></div>
    </div>

    <div class="w-full max-w-6xl relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">

            <!-- Left Panel - Info -->
            <div class="hidden lg:block">
                <div class="text-white space-y-8">
                    <!-- Logo and Title -->
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-2xl">
                            <img class="w-16 h-16 object-contain" src="<?= BASE_URL ?>assets/images/logo.png" alt="Logo">
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold">Sistema PIDE</h1>
                            <p class="text-blue-200 text-lg">Municipalidad Distrital de La Esperanza</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Login Form -->
            <div class="glass rounded-3xl shadow-2xl overflow-hidden border border-white/50">
                <div class="p-8 lg:p-10">
                    <!-- Mobile Logo -->
                    <div class="lg:hidden flex items-center justify-center mb-6">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center shadow-lg">
                            <img class="w-12 h-12 object-contain" src="<?= BASE_URL ?>assets/images/logo.png" alt="Logo">
                        </div>
                    </div>

                    <div class="mb-8 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-purple-600 shadow-lg mb-4">
                            <i class="fas fa-user-lock text-white text-2xl"></i>
                        </div>
                        <h2 class="text-3xl font-bold text-gray-800 mb-2">Bienvenido</h2>
                        <p class="text-gray-600">Ingrese sus credenciales para acceder al sistema</p>
                    </div>

                    <form id="formLogin" method="post" class="space-y-5">
                        <div>
                            <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user text-blue-600 mr-2"></i>Usuario
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    autocomplete="off"
                                    placeholder="Ingrese su usuario"
                                    required
                                    class="w-full px-4 py-3 pl-11 bg-white/80 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 outline-none shadow-sm">
                                <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-lock text-blue-600 mr-2"></i>Contraseña
                            </label>
                            <div class="relative">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Ingrese su contraseña"
                                    required
                                    class="w-full px-4 py-3 pl-11 pr-12 bg-white/80 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 outline-none shadow-sm">
                                <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <button
                                    type="button"
                                    id="togglePassword"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 transition">
                                    <i class="fas fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>

                        <button
                            id="btnLogin"
                            type="submit"
                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold py-3.5 px-6 rounded-xl hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform transition duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/30">
                            <i class="fas fa-sign-in-alt mr-2"></i>INGRESAR AL SISTEMA
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                            <div class="w-2 h-2 rounded-full bg-green-500 pulse-dot"></div>
                            <span>Sistema operativo</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-white/80 text-sm">
            <p>© 2026 Sistema de Consultas PIDE - Municipalidad Distrital de La Esperanza</p>
            <p class="text-xs mt-1 text-white/60">Sistema de uso exclusivo interno</p>
        </div>
    </div>

    <!-- Modal CUI -->
    <div id="modalValidarCUI" class="fixed inset-0 bg-black/50 items-center justify-center z-50 backdrop-blur-sm" style="display: none;">
        <div class="glass rounded-3xl shadow-2xl max-w-md w-full mx-4 transform transition-all border border-white/50">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 rounded-t-3xl">
                <div class="flex items-center justify-center w-16 h-16 mx-auto bg-white/20 backdrop-blur-sm rounded-full mb-4">
                    <i class="fas fa-shield-alt text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-center">Autenticación de Doble Factor</h2>
                <p class="text-center text-blue-100 mt-2 text-sm">
                    Verificación de seguridad adicional
                </p>
            </div>

            <form class="formValidarCUI" id="validarCUIForm" method="post">
                <div class="p-6 space-y-6">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-xl">
                        <p class="text-sm text-gray-700 mb-4">
                            Por favor, ingrese el último dígito de su Código Único de Identificación (CUI) que se encuentra en su DNI.
                        </p>
                        <img src="<?= BASE_URL ?>assets/images/dniGuiCUI.svg" alt="Guía ubicación CUI en DNI" class="w-full rounded-lg shadow-md">
                    </div>

                    <div>
                        <label for="cui" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-key text-blue-600 mr-2"></i>Último dígito del CUI:
                        </label>
                        <div class="flex justify-center">
                            <input
                                type="text"
                                id="cui"
                                maxlength="1"
                                autocomplete="off"
                                pattern="[0-9]"
                                required
                                class="w-16 h-16 text-center text-3xl font-bold border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 outline-none bg-white/80">
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            id="btnCancelarCUI"
                            class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition duration-200">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                        <button
                            type="submit"
                            id="btnConfirmarCUI"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-purple-700 transition duration-200 shadow-lg">
                            <i class="fas fa-check mr-2"></i>Confirmar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black/50 items-center justify-center z-[100] backdrop-blur-sm" style="display: none;">
        <div class="glass rounded-3xl shadow-2xl p-8 mx-4 border border-white/50 text-center">
            <div class="flex flex-col items-center space-y-4">
                <div class="relative">
                    <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <i class="fas fa-lock text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-800 mb-1">Iniciando sesión...</h3>
                    <p class="text-sm text-gray-600">Por favor espere</p>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="<?= BASE_URL ?>assets/js/api.js"></script>
    <script src="<?= BASE_URL ?>assets/js/login.js"></script>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            icon.classList.toggle('fa-eye-slash');
            icon.classList.toggle('fa-eye');
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