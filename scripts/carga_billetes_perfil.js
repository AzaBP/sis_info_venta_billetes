document.addEventListener('DOMContentLoaded', () => {
    const contenedor = document.getElementById('billetes-list');
    if (!contenedor) {
        return;
    }

    fetch('php/api_billetes_pasajero.php')
        .then((res) => res.json())
        .then((billetes) => {
            if (!Array.isArray(billetes) || billetes.length === 0) {
                contenedor.innerHTML = '<div class="empty-state">No hay billetes registrados para este pasajero.</div>';
                return;
            }

            contenedor.innerHTML = billetes.map((b) => {
                const estado = (b.estado || 'confirmado').toLowerCase();
                const claseEstado = estado === 'confirmado' || estado === 'activo' ? 'badge-ok' : 'badge-danger';
                const codigo = b.codigo_billete ? b.codigo_billete : `TKT-${b.id_mongo.slice(-6).toUpperCase()}`;
                const ruta = b.origen && b.destino ? `${b.origen} - ${b.destino}` : `Viaje #${b.id_viaje}`;
                const horario = b.hora_salida || b.hora_llegada ? `${b.hora_salida || '--:--'} / ${b.hora_llegada || '--:--'}` : 'Horario no disponible';
                const precio = Number.isFinite(Number(b.precio_pagado)) ? `${Number(b.precio_pagado).toFixed(2)} EUR` : 'N/D';
                const asiento = b.numero_asiento !== null && b.numero_asiento !== undefined ? b.numero_asiento : 'N/D';

                return `
                    <article class="data-card">
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
            }).join('');
        })
        .catch(() => {
            contenedor.innerHTML = '<div class="error-state">No se pudieron cargar los billetes del pasajero.</div>';
        });
});
