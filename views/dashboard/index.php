<?php
session_start();


// Validar sesión
/*if (!isset($_SESSION['usuarioID'])) {
    header("Location: /login");
    exit;
}*/


?>

<?php $titulo = "Dashboard Principal"; ?>
<?php include __DIR__ . "/../layouts/header.php"; ?>

<div class="dashboard-container" id="dashboardContainer">
    <?php include __DIR__ . "/../layouts/sidebar.php"; ?>

    <div class="main-content">

        <div id="pageInicio" class="page-content active">
            <?php include __DIR__ . "/pages/inicio.php"; ?>
        </div>

        <div id="pageMantenimiento" class="page-content">
            <?php include __DIR__ . "/pages/mantenimiento.php"; ?>
        </div>

        <div id="pageSistemas" class="page-content">
            <?php include __DIR__ . "/pages/sistemas.php"; ?>
        </div>

        <!-- SubPages-->
        <div id="pageConsultaDNI" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaDNI.php"; ?>
        </div>

        <div id="pageConsultaRUC" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaRUC.php"; ?>
        </div>

        <div id="pageConsultaPartidas" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaPartidas.php"; ?>
        </div>

        <div id="pageConsultaCobranza" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaCobranza.php"; ?>
        </div>

        <div id="pageConsultaPapeletas" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaPapeletas.php"; ?>
        </div>

        <div id="pageConsultaCertificaciones" class="page-content">
            <?php include __DIR__ . "/pages/subpages/consultaCertificaciones.php"; ?>
        </div>

    </div>
</div>


<?php include __DIR__ . "/../layouts/footer.php"; ?>
