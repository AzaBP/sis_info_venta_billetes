document.addEventListener('DOMContentLoaded', () => {
    const state = document.getElementById('ticketsState');
    const list = document.getElementById('ticketsList');
    const config = window.misBilletesConfig || {};
    let expandedTicketId = null;

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

    function generateQRCode(ticket) {
        const qrPayload = JSON.stringify({
            codigo: ticket.codigo_billete || '',
            pasajero: `${ticket.pasajero_nombre || ''} ${ticket.pasajero_apellidos || ''}`.trim(),
            origen: ticket.origen || '',
            destino: ticket.destino || '',
            fecha: ticket.fecha_viaje || '',
            salida: ticket.hora_salida || '',
            llegada: ticket.hora_llegada || '',
            asiento: ticket.numero_asiento || '',
            vagon: ticket.vagon || '',
        });

        return `
            <div class="qr-container">
                <div id="qr_${ticket.id_mongo || ticket.codigo_billete}" style="display:inline-block;"></div>
                <p class="qr-info">Código QR - Escanea en el mostrador</p>
            </div>
        `;
    }

    function renderTicketCompact(ticket) {
        const fechaViaje = ticket.fecha_viaje || '';
        const esPasado = fechaViaje ? (new Date(fechaViaje) < new Date(new Date().toDateString())) : false;
        const ruta = `${ticket.origen || ''} → ${ticket.destino || ''}`.trim();
        const pasajero = `${ticket.pasajero_nombre || ''} ${ticket.pasajero_apellidos || ''}`.trim();

        return `
            <article class="ticket-row" data-ticket-id="${escapeHtml(ticket.id_mongo || '')}">
                <div class="ticket-route">
                    <div class="ticket-badges">
                        <span class="badge badge-soft">${escapeHtml(ticket.tipo_tren || 'Tren')}</span>
                        <span class="badge ${esPasado ? 'badge-soft' : 'badge-ok'}">${esPasado ? 'Finalizado' : 'Activo'}</span>
                    </div>
                    <h3>${escapeHtml(ruta)}</h3>
                    <p><strong>Pasajero:</strong> ${escapeHtml(pasajero || 'N/D')}</p>
                </div>

                <div class="ticket-meta">
                    <p><strong>Fecha:</strong> ${escapeHtml(fechaViaje)}</p>
                    <p><strong>Horario:</strong> ${escapeHtml((ticket.hora_salida || '') + ' - ' + (ticket.hora_llegada || ''))}</p>
                    <p><strong>Asiento:</strong> ${escapeHtml(ticket.numero_asiento || '')}${ticket.vagon ? ` · Vagón ${escapeHtml(ticket.vagon)}` : ''}</p>
                </div>

                <div class="ticket-actions">
                    <button class="btn-link btn-expand">
                        <i class="fa-solid fa-chevron-down"></i> Ver detalles
                    </button>
                </div>

                <div class="ticket-details-expanded">
                    <p><strong>Código billete:</strong> ${escapeHtml(ticket.codigo_billete || '')}</p>
                    <p><strong>Precio:</strong> ${formatMoney(ticket.precio_pagado)}</p>
                    <p><strong>Documento:</strong> ${escapeHtml(ticket.pasajero_documento || 'N/D')}</p>
                    ${generateQRCode(ticket)}
                    <div style="display:flex; gap:10px; margin-top:12px;">
                        <a class="btn-link" href="${config.downloadUrl || 'php/descargar_billete.php'}?id_mongo=${encodeURIComponent(ticket.id_mongo || '')}">
                            <i class="fa-solid fa-file-pdf"></i> Descargar PDF
                        </a>
                    </div>
                </div>
            </article>
        `;
    }

    function attachTicketListeners() {
        document.querySelectorAll('.ticket-row').forEach((row) => {
            const ticketId = row.getAttribute('data-ticket-id');
            const btn = row.querySelector('.btn-expand');

            row.addEventListener('click', (e) => {
                if (e.target.closest('a')) return;
                toggleExpand(row, ticketId);
            });

            if (btn) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    toggleExpand(row, ticketId);
                });
            }
        });
    }

    function toggleExpand(row, ticketId) {
        const isExpanded = row.classList.contains('expanded');

        document.querySelectorAll('.ticket-row.expanded').forEach((r) => {
            r.classList.remove('expanded');
            const btn = r.querySelector('.btn-expand');
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-chevron-down"></i> Ver detalles';
            }
        });

        if (!isExpanded) {
            row.classList.add('expanded');
            const btn = row.querySelector('.btn-expand');
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-chevron-up"></i> Ocultar detalles';
            }

            setTimeout(() => {
                const qrEl = document.getElementById(`qr_${ticketId}`);
                if (qrEl && qrEl.innerHTML === '') {
                    const qrData = row.querySelector('.ticket-details-expanded')?.textContent || ticketId;
                    try {
                        new QRCode(qrEl, {
                            text: qrData.substring(0, 2000),
                            width: 200,
                            height: 200,
                            colorDark: '#0a2a66',
                            colorLight: '#ffffff',
                            correctLevel: QRCode.CorrectLevel.H
                        });
                    } catch (e) {
                        console.error('Error generating QR code:', e);
                    }
                }
            }, 100);
        }
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

            list.innerHTML = data.map(renderTicketCompact).join('');
            list.hidden = false;
            state.hidden = true;
            attachTicketListeners();
        } catch (error) {
            setState(error.message || 'No se pudieron cargar tus billetes.', true);
        }
    }

    loadTickets();
});
