<?php
if (session_status() === PHP_SESSION_NONE) session_start();

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
    <title><?= $titulo ?? 'Dashboard - Gestión de Practicantes' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/consultaDNI.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/consultaRUC.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
</head>
<body>
