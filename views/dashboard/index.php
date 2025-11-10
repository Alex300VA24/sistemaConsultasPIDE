<?php
session_start();

use App\Helpers\Permisos;


// 🔹 Obtener permisos según cargo
$rolID = $_SESSION['rolID'];
$permisos = Permisos::obtenerPermisos($rolID);

?>


<?php $titulo = "Dashboard Principal"; ?>
<?php include __DIR__ . "/../layouts/header.php"; ?>

<div class="dashboard-container" id="dashboardContainer">
    <?php include __DIR__ . "/../layouts/sidebar.php"; ?>

    <div class="main-content">

        <?php if (in_array('inicio', $permisos)): ?>
        <div id="pageInicio" class="page-content active">
            <?php include __DIR__ . "/pages/inicio.php"; ?>
        </div>
        <?php endif; ?>

        
        <?php if (in_array('mantenimiento', $permisos)): ?>
        <div id="pageMantenimiento" class="page-content">
            <?php include __DIR__ . "/pages/mantenimiento.php"; ?>
        </div>
        <?php endif; ?>

        <!-- SubPages-->
         <?php if (in_array('consultaDNI', $permisos)): ?>
        <div id="pageConsultaDNI" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaDNI.php"; ?>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('consultaRUC', $permisos)): ?>
        <div id="pageConsultaRUC" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaRUC.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('consultaPartidas', $permisos)): ?>
        <div id="pageConsultaPartidas" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaPartidas.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('consultaCobranza', $permisos)): ?>
        <div id="pageConsultaCobranza" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaCobranza.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('consultaPapeletas', $permisos)): ?>
        <div id="pageConsultaPapeletas" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaPapeletas.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('consultaCertificaciones', $permisos)): ?>
        <div id="pageConsultaCertificaciones" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaCertificaciones.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('crearUsuario', $permisos)): ?>
        <div id="pageCrearUsuario" class="page-content">
            <?php include __DIR__ . "/pages/sistema/crearUsuario.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('actualizarUsuario', $permisos)): ?>
        <div id="pageActualizarUsuario" class="page-content">
            <?php include __DIR__ . "/pages/sistema/actualizarUsuario.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('actualizarPassword', $permisos)): ?>
        <div id="pageActualizarPassword" class="page-content">
            <?php include __DIR__ . "/pages/sistema/actualizarPassword.php"; ?>
        </div>
        <?php endif; ?>

    </div>
</div>


<?php include __DIR__ . "/../layouts/footer.php"; ?>
