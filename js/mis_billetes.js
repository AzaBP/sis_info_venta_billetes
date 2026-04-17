document.addEventListener('DOMContentLoaded', () => {
    const state = document.getElementById('ticketsState');
    const list = document.getElementById('ticketsList');
    const config = window.misBilletesConfig || {};

    function setState(message, isError = false) {
        if (!state) return;
        state.hidden = false;
        state.className = isError ? 'error-box' : 'tickets-loading';
        state.textContent = message;
    }

    function escapeHtml(text) {
        return String(text ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatMoney(value) {
        const number = Number(value || 0);
        return `${number.toFixed(2)} EUR`;
    }

    function renderTicket(ticket) {
        const fechaViaje = ticket.fecha_viaje || '';
        const esPasado = fechaViaje ? (new Date(fechaViaje) < new Date(new Date().toDateString())) : false;
        const ruta = `${ticket.origen || ''} → ${ticket.destino || ''}`.trim();
        const pasajero = `${ticket.pasajero_nombre || ''} ${ticket.pasajero_apellidos || ''}`.trim();
        const qrcodeText = ticket.codigo_billete ? ticket.codigo_billete : 'QR';
        const downloadUrl = `${config.downloadUrl || 'php/descargar_billete.php'}?id_mongo=${encodeURIComponent(ticket.id_mongo || '')}`;

        return `
            <article class="ticket-row${esPasado ? ' expired' : ''}">
                <div class="ticket-route">
                    <div class="ticket-badges">
                        <span class="badge badge-soft">${escapeHtml(ticket.tipo_tren || 'Tren')}</span>
                        <span class="badge ${esPasado ? 'badge-soft' : 'badge-ok'}">${esPasado ? 'Finalizado' : 'Activo'}</span>
                    </div>
                    <h3>${escapeHtml(ruta)}</h3>
                    <p><strong>Pasajero:</strong> ${escapeHtml(pasajero || 'N/D')}</p>
                    <p><strong>Código:</strong> ${escapeHtml(ticket.codigo_billete || '')}</p>
                </div>

                <div class="ticket-meta">
                    <p><strong>Fecha:</strong> ${escapeHtml(fechaViaje)}</p>
                    <p><strong>Horario:</strong> ${escapeHtml((ticket.hora_salida || '') + ' - ' + (ticket.hora_llegada || ''))}</p>
                    <p><strong>Asiento:</strong> ${escapeHtml(ticket.numero_asiento || '')}${ticket.vagon ? ` · Vagón ${escapeHtml(ticket.vagon)}` : ''}</p>
                    <p><strong>Precio:</strong> ${formatMoney(ticket.precio_pagado)}</p>
                </div>

                <div class="ticket-actions">
                    <div class="ticket-qrcode">
                        <i class="fa-solid fa-qrcode fa-2x"></i>
                        <span>${escapeHtml(qrcodeText)}</span>
                    </div>
                    <a class="btn-link" href="${downloadUrl}">
                        <i class="fa-solid fa-file-pdf"></i> Descargar PDF
                    </a>
                </div>
            </article>
        `;
    }

    async function loadTickets() {
        try {
            const response = await fetch(config.apiUrl || 'php/api_billetes_pasajero.php', { credentials: 'same-origin' });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || `HTTP ${response.status}`);
            }

            if (!Array.isArray(data) || data.length === 0) {
                setState('No tienes billetes todavía. Cuando hagas una reserva, aparecerán aquí.');
                return;
            }

            list.innerHTML = data.map(renderTicket).join('');
            list.hidden = false;
            state.hidden = true;
        } catch (error) {
            setState(error.message || 'No se pudieron cargar tus billetes.', true);
        }
    }

    loadTickets();
});
