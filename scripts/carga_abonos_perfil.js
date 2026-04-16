document.addEventListener('DOMContentLoaded', function() {
    cargarAbonos();
});

function trPerfil(key, fallback, params = {}) {
    const i18n = window.trainwebI18n;
    let text = (i18n && typeof i18n.t === 'function') ? i18n.t(key) : null;
    if (!text) text = fallback;
    Object.keys(params).forEach((k) => {
        text = text.replace(`{${k}}`, params[k]);
    });
    return text;
}

function cargarAbonos() {
    fetch('php/abonos_usuario_api.php')
        .then(res => res.json())
        .then(abonos => {
            const contenedor = document.getElementById('abonos-list');
            contenedor.className = 'cards-grid';

            if (!abonos || abonos.length === 0) {
                contenedor.innerHTML = `
                    <div class="empty-state">
                        ${trPerfil('perfil_sin_abonos', 'Aun no tienes abonos activos. Revisa nuestras ofertas para conseguir uno.')}
                    </div>
                `;
                return;
            }

            contenedor.innerHTML = abonos.map(a => {
                const info = obtenerDisenoAbono(a.tipo);
                const fechaFin = new Date(a.fecha_fin);
                const hoy = new Date();
                const esActivo = fechaFin >= hoy;

                const textoEstado = esActivo ? trPerfil('perfil_activo', 'Activo') : trPerfil('perfil_caducado', 'Caducado');
                const claseEstado = esActivo ? 'badge-ok' : 'badge-danger';

                let textoViajes = `<p><strong>${trPerfil('perfil_viajes', 'Viajes')}:</strong> ${trPerfil('ilimitado', 'Ilimitado')}</p>`;
                if (a.viajes_totales !== null) {
                    textoViajes = `<p><strong>${trPerfil('perfil_viajes_restantes', 'Viajes restantes')}:</strong> ${a.viajes_restantes} ${trPerfil('de', 'de')} ${a.viajes_totales}</p>`;
                }

                return `
                    <article class="data-card">
                        <div class="data-card-top">
                            <h4><i class="fa-solid ${info.icon}"></i> ${info.nombre}</h4>
                            <span class="badge ${claseEstado}">${textoEstado}</span>
                        </div>
                        <p><strong>${trPerfil('perfil_valido_desde', 'Valido desde')}:</strong> ${formatearFecha(a.fecha_inicio)}</p>
                        <p><strong>${trPerfil('valido_hasta', 'Valido hasta')}:</strong> ${formatearFecha(a.fecha_fin)}</p>
                        ${textoViajes}
                    </article>
                `;
            }).join('');
        })
        .catch(err => {
            console.error("Error cargando abonos:", err);
            document.getElementById('abonos-list').innerHTML = `
                <div class="error-state">
                    ${trPerfil('perfil_error_abonos', 'Error al cargar tus abonos. Intentalo mas tarde.')}
                </div>
            `;
        });
}

// Función auxiliar para darle la estética correcta según el tipo
function obtenerDisenoAbono(tipo) {
    const disenios = {
        'mensual': { icon: 'fa-calendar-alt', color: '#0a2a66', nombre: 'Abono Mensual' },
        'trimestral': { icon: 'fa-calendar-days', color: '#17a2b8', nombre: 'Abono Trimestral' },
        'anual': { icon: 'fa-infinity', color: '#2058b3', nombre: 'Abono Anual' },
        'viajes_limitados': { icon: 'fa-ticket', color: '#3156fc', nombre: 'Bono Viajes' },
        'estudiante': { icon: 'fa-user-graduate', color: '#17632A', nombre: 'Abono Estudiante' }
    };
    
    // Si el tipo no coincide, le damos un diseño genérico
    return disenios[tipo] || { icon: 'fa-id-card', color: '#6c757d', nombre: 'Abono ' + tipo.replace('_', ' ') };
}

// Función auxiliar para poner la fecha en formato DD/MM/YYYY
function formatearFecha(fechaSql) {
    if (!fechaSql) return trPerfil('perfil_no_disponible', 'N/A');
    const partes = fechaSql.split('-'); // Asume YYYY-MM-DD
    if (partes.length === 3) {
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }
    return fechaSql;
}