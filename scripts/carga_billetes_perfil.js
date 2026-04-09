document.addEventListener('DOMContentLoaded', () => {
    const contenedorProximos = document.getElementById('viajes-proximos-list');
    const contenedorFinalizados = document.getElementById('viajes-finalizados-list');
    if (!contenedorProximos || !contenedorFinalizados) {
        return;
    }

    function parseFechaHora(fechaViaje, horaSalida) {
        if (!fechaViaje) {
            return null;
        }

        const hora = (horaSalida && String(horaSalida).trim() !== '') ? String(horaSalida).slice(0, 8) : '00:00:00';
        const textoIso = `${fechaViaje}T${hora}`;
        const d = new Date(textoIso);
        return Number.isNaN(d.getTime()) ? null : d;
    }

    function renderCard(b) {
        const estado = (b.estado || 'confirmado').toLowerCase();
        const claseEstado = estado === 'confirmado' || estado === 'activo' ? 'badge-ok' : 'badge-danger';
        const codigoBase = b.id_mongo ? String(b.id_mongo) : '000000';
        const codigo = b.codigo_billete ? b.codigo_billete : `TKT-${codigoBase.slice(-6).toUpperCase()}`;
        const ruta = b.origen && b.destino ? `${b.origen} - ${b.destino}` : `Viaje #${b.id_viaje}`;
        const horario = b.hora_salida || b.hora_llegada ? `${b.hora_salida || '--:--'} / ${b.hora_llegada || '--:--'}` : 'Horario no disponible';
        const precio = Number.isFinite(Number(b.precio_pagado)) ? `${Number(b.precio_pagado).toFixed(2)} EUR` : 'N/D';
        const asiento = b.numero_asiento !== null && b.numero_asiento !== undefined ? b.numero_asiento : 'N/D';

        return `
            <article class="data-card" data-id-viaje="${b.id_viaje}">
                <div class="data-card-top">
                    <h4>${codigo}</h4>
                    <span class="badge ${claseEstado}">${estado}</span>
                </div>
                <p><strong>Ruta:</strong> ${ruta}</p>
                <p><strong>Fecha viaje:</strong> ${b.fecha_viaje || 'N/D'}</p>
                <p><strong>Horario:</strong> ${horario}</p>
                <p><strong>Asiento:</strong> ${asiento}</p>
                <p><strong>Precio:</strong> ${precio}</p>
                <p><strong>Compra:</strong> ${b.fecha_compra || 'N/D'}</p>
            </article>
        `;
    }

    fetch('php/api_billetes_pasajero.php', { cache: 'no-store' })
        .then(async (res) => {
            const body = await res.text();
            let billetes;

            try {
                billetes = JSON.parse(body);
            } catch (e) {
                throw new Error('Respuesta invalida al consultar billetes');
            }

            if (!Array.isArray(billetes)) {
                return [];
            }

            return billetes;
        })
        .then((billetes) => {
            if (!Array.isArray(billetes) || billetes.length === 0) {
                contenedorProximos.innerHTML = '<div class="empty-state">No tienes viajes proximos.</div>';
                contenedorFinalizados.innerHTML = '<div class="empty-state">No tienes viajes previos en tu cuenta.</div>';
                return;
            }

            const ahora = new Date();
            const proximos = [];
            const finalizados = [];

            billetes.forEach((b) => {
                const fechaViaje = parseFechaHora(b.fecha_viaje, b.hora_salida);
                if (fechaViaje && fechaViaje.getTime() >= ahora.getTime()) {
                    proximos.push(b);
                } else {
                    finalizados.push(b);
                }
            });

            contenedorProximos.innerHTML = proximos.length > 0
                ? proximos.map(renderCard).join('')
                : '<div class="empty-state">No tienes viajes proximos.</div>';

            contenedorFinalizados.innerHTML = finalizados.length > 0
                ? finalizados.map(renderCard).join('')
                : '<div class="empty-state">No tienes viajes previos en tu cuenta.</div>';
        })
        .catch(() => {
            contenedorProximos.innerHTML = '<div class="error-state">Error tecnico al consultar tus viajes proximos.</div>';
            contenedorFinalizados.innerHTML = '<div class="error-state">Error tecnico al consultar tus viajes finalizados.</div>';
        });
});
