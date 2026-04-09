document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('incidencias-viaje');
    if (!container) return;

    function render(incidencias) {
        container.innerHTML = '';
        if (!incidencias || incidencias.length === 0) {
            container.innerHTML = '<div class="empty-state">No hay incidencias que afecten a tus viajes.</div>';
            return;
        }

        incidencias.forEach(inc => {
            const div = document.createElement('div');
            div.className = 'data-card';
            const ruta = `${inc.ruta_origen} - ${inc.ruta_destino}`;
            const fecha = inc.fecha ? inc.fecha : '';
            const hora = inc.hora_salida ? inc.hora_salida : '';
            div.innerHTML = `
                <div class="data-card-top">
                    <h4>Viaje #${inc.id_viaje}</h4>
                    <span class="badge badge-danger">Incidencia</span>
                </div>
                <p><strong>Ruta:</strong> ${ruta}</p>
                <p><strong>Salida:</strong> ${fecha} ${hora}</p>
                <p><strong>Detalle:</strong> ${inc.descripcion}</p>
            `;
            container.appendChild(div);
        });
    }

    fetch('php/api_incidencias_pasajero.php')
        .then(r => r.json())
        .then(render)
        .catch(() => {
            container.innerHTML = '<div class="error-state">No se pudieron cargar las incidencias.</div>';
        });
});
