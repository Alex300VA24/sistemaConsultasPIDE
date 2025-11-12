<script src="<?= BASE_URL ?>assets/js/api.js"></script>
<script src="<?= BASE_URL ?>assets/js/dashboard.js"></script>
<script src="<?= BASE_URL ?>assets/js/consultas/consultaDNI.js"></script>
<script src="<?= BASE_URL ?>assets/js/consultas/consultaRUC.js"></script>
<script src="<?= BASE_URL ?>assets/js/consultas/consultaPartidas.js"></script>
<script src="<?= BASE_URL ?>assets/js/sistema/crearUsuario.js"></script>
<script src="<?= BASE_URL ?>assets/js/sistema/actualizarUsuario.js"></script>
<script src="<?= BASE_URL ?>assets/js/sistema/actualizarPassword.js"></script>

<script>
// ✅ Guardar la página activa al cambiar
document.querySelectorAll('.page-content').forEach(page => {
    page.addEventListener('click', () => {
        if (page.classList.contains('active')) {
            localStorage.setItem('paginaActiva', page.id);
        }
    });
});

// ✅ Restaurar la página activa al recargar
window.addEventListener('DOMContentLoaded', () => {
    const ultimaPagina = localStorage.getItem('paginaActiva');
    if (ultimaPagina) {
        // Ocultar todas las páginas
        document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));

        // Mostrar la última activa
        const pagina = document.getElementById(ultimaPagina);
        if (pagina) pagina.classList.add('active');
    }
});
</script>


</body>
</html>
