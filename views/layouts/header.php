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
    <title><?= $titulo ?? 'Dashboard - Sistema de Consultas PIDE' ?></title>
    <link rel="stylesheet" href='<?= BASE_URL ?>assets/css/fontawesome/css/all.min.css'">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/consultaDNI.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/consultaRUC.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/consultaPartidas.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/usuario.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/inicio.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/modal-password.css">
</head>
<body>
