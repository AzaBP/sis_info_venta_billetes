document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('incidencias-viaje');
    if (!container) return;

    function render(incidencias) {
        container.innerHTML = '';
        if (!incidencias || incidencias.length === 0) {
            container.innerHTML = '<p>No hay incidencias que afecten a tus viajes.</p>';
            return;
        }

        incidencias.forEach(inc => {
            const div = document.createElement('div');
            div.className = 'trip-item active-trip';
            const ruta = `${inc.ruta_origen} - ${inc.ruta_destino}`;
            const fecha = inc.fecha ? inc.fecha : '';
            const hora = inc.hora_salida ? inc.hora_salida : '';
            div.innerHTML = `
                <div class="trip-header">
                    <span class="trip-status status-warning">Incidencia</span>
                    <span class="trip-id">Viaje #${inc.id_viaje}</span>
                </div>
                <div class="trip-body">
                    <div class="trip-route">
                        <div class="route-time">
                            <span class="time">${hora}</span>
                            <span class="city">${ruta}</span>
                        </div>
                    </div>
                    <div class="trip-date">${fecha}</div>
                    <div class="trip-date">${inc.descripcion}</div>
                </div>
            `;
            container.appendChild(div);
        });
    }

    fetch('php/api_incidencias_pasajero.php')
        .then(r => r.json())
        .then(render)
        .catch(() => {
            container.innerHTML = '<p>No se pudieron cargar las incidencias.</p>';
        });
});
