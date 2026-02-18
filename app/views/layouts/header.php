<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name("APPPIDESESSID");
    session_start();
}
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: ' . BASE_URL . 'login');
    exit;
}

$nombreUsuario = $_SESSION['nombreUsuario'] ?? 'Usuario';
$nombreCargo   = $_SESSION['nombreCargo'] ?? 'Sin cargo';
$nombreArea    = $_SESSION['nombreArea'] ?? 'Sin área';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Dashboard - Sistema de Consultas PIDE' ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href='<?= BASE_URL ?>assets/css/fontawesome/css/all.min.css'">
    
    <!-- CSS Personalizados -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/consultaDNI.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/consultaRUC.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/consultaPartidas.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/usuario.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/inicio.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modal-password.css">
    
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Pulse animation */
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse-dot {
            animation: pulse-dot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
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
        
        /* Main content transition for sidebar */
        #main-content {
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Responsive sidebar */
        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            #sidebar.expanded {
                transform: translateX(0);
            }
            
            #main-content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-gradient-modern min-h-screen overflow-hidden">
