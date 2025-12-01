<?php
session_start();

use App\Helpers\Permisos;


// 🔹 Obtener permisos según id del usuario
$usuarioID = $_SESSION['usuarioID'];
$permisos = Permisos::obtenerPermisos($usuarioID);

?>


<?php $titulo = "Dashboard Principal"; ?>
<?php include __DIR__ . "/../layouts/header.php"; ?>

<div class="dashboard-container" id="dashboardContainer">
    <?php include __DIR__ . "/../layouts/sidebar.php"; ?>

    <div class="main-content">

        <?php if (in_array('INI', $permisos)): ?>
        <div id="pageInicio" class="page-content active">
            <?php include __DIR__ . "/pages/inicio.php"; ?>
        </div>
        <?php endif; ?>

        
        <?php if (in_array('MAN', $permisos)): ?>
        <div id="pageMantenimiento" class="page-content">
            <?php include __DIR__ . "/pages/mantenimiento.php"; ?>
        </div>
        <?php endif; ?>

        <!-- SubPages-->
         <?php if (in_array('DNI', $permisos)): ?>
        <div id="pageConsultaDNI" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaDNI.php"; ?>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('RUC', $permisos)): ?>
        <div id="pageConsultaRUC" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaRUC.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('PAR', $permisos)): ?>
        <div id="pageConsultaPartidas" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaPartidas.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('COB', $permisos)): ?>
        <div id="pageConsultaCobranza" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaCobranza.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('PAP', $permisos)): ?>
        <div id="pageConsultaPapeletas" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaPapeletas.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('CER', $permisos)): ?>
        <div id="pageConsultaCertificaciones" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaCertificaciones.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('RUSU', $permisos)): ?>
        <div id="pageCrearUsuario" class="page-content">
            <?php include __DIR__ . "/pages/sistema/crearUsuario.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('AUSU', $permisos)): ?>
        <div id="pageActualizarUsuario" class="page-content">
            <?php include __DIR__ . "/pages/sistema/actualizarUsuario.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('APAS', $permisos)): ?>
        <div id="pageActualizarPassword" class="page-content">
            <?php include __DIR__ . "/pages/sistema/actualizarPassword.php"; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('CROL', $permisos)): ?>
        <div id="pageCrearRoles" class="page-content">
            <?php include __DIR__ . "/pages/sistema/crearRoles.php"; ?>
        </div>
        <?php endif; ?>

    </div>
</div>


<?php include __DIR__ . "/../layouts/footer.php"; ?>
