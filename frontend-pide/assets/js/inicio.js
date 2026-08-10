/*document.addEventListener("DOMContentLoaded", () => {
    if (document.getElementById('actividadReciente')) {
        cargarInicio();
    }
});


async function cargarInicio() {
    try {
        const data = await api.obtenerEstadisticasInicio();

        document.getElementById('totalPracticantes').textContent = data.totalPracticantes || 0;
        document.getElementById('pendientesAprobacion').textContent = data.pendientesAprobacion || 0;
        document.getElementById('practicantesActivos').textContent = data.practicantesActivos || 0;
        document.getElementById('asistenciaHoy').textContent = data.asistenciaHoy || 0;

        const actividadDiv = document.getElementById('actividadReciente');
        actividadDiv.innerHTML = '';

        if (data.actividadReciente && data.actividadReciente.length > 0) {
            data.actividadReciente.forEach(act => {
                const div = document.createElement('div');
                div.classList.add('actividad-item');
                div.innerHTML = `
                    <strong>${act.practicante}</strong> - ${act.accion}
                    <span class="fecha">${act.fecha}</span>
                `;
                actividadDiv.appendChild(div);
            });
        } else {
            actividadDiv.innerHTML = '<p>No hay actividad reciente.</p>';
        }
    } catch (error) {
        console.error('Error al cargar el inicio:', error);
    }
}*/
