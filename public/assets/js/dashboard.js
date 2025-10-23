document.addEventListener('DOMContentLoaded', async () => {
    let BASE_URL = '/sistemaConsultasPIDE/public/';

    // 🔹 Al cargar el dashboard, también se puede mostrar el inicio
    await cargarInicio();

    // Navegación
    window.showPage = function (pageId, element) {
        document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
        console.log(pageId);

        const targetPage = document.getElementById('page' + capitalize(pageId));
        if (targetPage) {
            targetPage.classList.add('active');
        } else {
            console.warn(`No se encontró la página: page${capitalize(pageId)}`);
        }

        document.querySelectorAll('.option, .suboption').forEach(o => o.classList.remove('active'));
        element.classList.add('active');

        if (pageId === 'inicio') {
            cargarInicio();
        }
    };


    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // Logout
    document.getElementById('btnLogout').addEventListener('click', () => {
        const modal = document.getElementById('logoutModal');
        modal.style.display = 'flex';
    });

    document.getElementById('cancelLogout').addEventListener('click', () => {
        const modal = document.getElementById('logoutModal');
        modal.style.display = 'none';
    });

    document.getElementById('confirmLogout').addEventListener('click', async () => {
        const modal = document.getElementById('logoutModal');
        modal.style.display = 'none';
        try {
            await api.logout();
            window.location.href = BASE_URL + 'login';
        } catch (error) {
            alert('Error al cerrar sesión');
        }
    });

    
});

async function cargarInicio() {
    try {

        // === Actividad reciente (opcional si más adelante la agregas) ===
        const actividadDiv = document.getElementById('actividadReciente');
        actividadDiv.innerHTML = '<p>No hay actividad reciente.</p>';

    } catch (error) {
        console.error('Error al cargar el inicio:', error);
    }
}

function toggleSubmenu(element) {
    const submenu = element.nextElementSibling;
    const isOpen = submenu.style.display === 'flex';
    
    // Cerrar todos los submenús antes de abrir otro (opcional)
    document.querySelectorAll('.submenu').forEach(s => s.style.display = 'none');
    document.querySelectorAll('.has-submenu').forEach(o => o.classList.remove('open'));

    if (!isOpen) {
        submenu.style.display = 'flex';
        element.classList.add('open');
    }
}