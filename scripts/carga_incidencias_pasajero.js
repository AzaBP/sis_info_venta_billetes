document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('incidencias-viaje');
    if (!container) return;

    function tr(key, fallback, params = {}) {
        const i18n = window.trainwebI18n;
        let text = (i18n && typeof i18n.t === 'function') ? i18n.t(key) : null;
        if (!text) text = fallback;
        Object.keys(params).forEach((k) => {
            text = text.replace(`{${k}}`, params[k]);
        });
        return text;
    }

    function render(incidencias) {
        container.innerHTML = '';
        if (!incidencias || incidencias.length === 0) {
            container.innerHTML = `<div class="empty-state">${tr('perfil_no_incidencias', 'No hay incidencias asociadas a tus viajes.')}</div>`;
            return;
        }

        incidencias.forEach(inc => {
            const div = document.createElement('div');
            div.className = 'data-card';
            const ruta = `${inc.ruta_origen} - ${inc.ruta_destino}`;
            const fecha = inc.fecha ? inc.fecha : '';
            const hora = inc.hora_salida ? inc.hora_salida : '';
            const descripcion = inc.descripcion ? inc.descripcion : tr('perfil_sin_descripcion', 'Sin descripcion');
            div.innerHTML = `
                <div class="data-card-top">
                    <h4>${tr('perfil_viaje_num', 'Viaje #{id}', { id: inc.id_viaje })}</h4>
                    <span class="badge badge-danger">${tr('perfil_incidencia', 'Incidencia')}</span>
                </div>
                <p><strong>${tr('perfil_ruta', 'Ruta')}:</strong> ${ruta}</p>
                <p><strong>${tr('salida_label', 'Salida')}:</strong> ${fecha} ${hora}</p>
                <p><strong>${tr('perfil_detalle', 'Detalle')}:</strong> ${descripcion}</p>
            `;
            container.appendChild(div);
        });
    }

    fetch('php/api_incidencias_pasajero.php', { cache: 'no-store' })
        .then(async (r) => {
            const body = await r.text();
            try {
                const data = JSON.parse(body);
                return Array.isArray(data) ? data : [];
            } catch (e) {
                return [];
            }
        })
        .then(render)
        .catch(() => {
            container.innerHTML = `<div class="empty-state">${tr('perfil_no_incidencias', 'No hay incidencias asociadas a tus viajes.')}</div>`;
        });
});
