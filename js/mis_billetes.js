document.addEventListener('DOMContentLoaded', () => {
    const state = document.getElementById('ticketsState');
    const list = document.getElementById('ticketsList');
    const modal = document.getElementById('ticketModal');
    const modalBody = document.getElementById('ticketModalBody');
    const modalCloseBtn = document.getElementById('ticketModalClose');
    const config = window.misBilletesConfig || {};
    const ticketsById = new Map();

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

    function buildTicketKey(ticket) {
        const raw = String(ticket.id_mongo || ticket.codigo_billete || ticket.id_viaje || Date.now());
        return raw.replace(/[^a-zA-Z0-9_-]/g, '_');
    }

    function buildQrPayloadLikePdf(ticket) {
        const pasajero = `${ticket.pasajero_nombre || ''} ${ticket.pasajero_apellidos || ''}`.trim();
        const precio = Number(ticket.precio_pagado || 0).toFixed(2);
        return [
            'TrainWeb Billete',
            `Codigo: ${ticket.codigo_billete || ''}`,
            `Pasajero: ${pasajero}`,
            `Documento: ${ticket.pasajero_documento || ''}`,
            `Ruta: ${ticket.origen || ''} -> ${ticket.destino || ''}`,
            `Fecha viaje: ${ticket.fecha_viaje || ''}`,
            `Hora: ${ticket.hora_salida || ''} - ${ticket.hora_llegada || ''}`,
            `Asiento: ${ticket.numero_asiento || ''}`,
            `Vagon: ${ticket.vagon || ''}`,
            `Precio: ${precio} EUR`
        ].join('\n');
    }

    function generateQRCode(ticket) {
        const ticketKey = buildTicketKey(ticket);
        const qrId = `qr_${ticketKey}`;

        return `
            <div class="qr-container">
                <div id="${qrId}" class="qr-target"></div>
                <p class="qr-info">Código QR - Escanea en el mostrador</p>
            </div>
        `;
    }

    function renderTicketCompact(ticket) {
        const fechaViaje = ticket.fecha_viaje || '';
        const esPasado = fechaViaje ? (new Date(fechaViaje) < new Date(new Date().toDateString())) : false;
        const ruta = `${ticket.origen || ''} → ${ticket.destino || ''}`.trim();
        const ticketKey = buildTicketKey(ticket);
        ticketsById.set(ticketKey, ticket);

        return `
            <article class="ticket-row" data-ticket-id="${escapeHtml(ticketKey)}">
                <div class="ticket-route">
                    <div class="ticket-badges">
                        <span class="badge badge-soft">${escapeHtml(ticket.tipo_tren || 'Tren')}</span>
                        <span class="badge ${esPasado ? 'badge-soft' : 'badge-ok'}">${esPasado ? 'Finalizado' : 'Activo'}</span>
                    </div>
                    <h3>${escapeHtml(ruta)}</h3>
                </div>

                <div class="ticket-meta">
                    <p><strong>Fecha:</strong> ${escapeHtml(fechaViaje)}</p>
                    <p><strong>Horario:</strong> ${escapeHtml((ticket.hora_salida || '') + ' - ' + (ticket.hora_llegada || ''))}</p>
                </div>

                <div class="ticket-actions">
                    <button class="btn-link btn-open-modal" type="button">
                        <i class="fa-solid fa-up-right-and-down-left-from-center"></i> Ver detalles
                    </button>
                    ${!esPasado ? `
                    <button class="btn-link btn-cancel-ticket" type="button" data-codigo="${escapeHtml(ticket.codigo_billete || '')}" style="background-color: #8e2e2e; margin-top: 6px;">
                        <i class="fa-solid fa-trash"></i> Cancelar
                    </button>
                    ` : ''}
                </div>
            </article>
        `;
    }

    function renderTicketModal(ticket) {
        const pasajero = `${ticket.pasajero_nombre || ''} ${ticket.pasajero_apellidos || ''}`.trim();
        const ruta = `${ticket.origen || ''} → ${ticket.destino || ''}`.trim();

        return `
            <div class="ticket-modal-header">
                <h3>${escapeHtml(ruta)}</h3>
            </div>
            <div class="ticket-details-grid">
                <div class="ticket-details-info">
                    <p><strong>Código billete:</strong> ${escapeHtml(ticket.codigo_billete || '')}</p>
                    <p><strong>Pasajero:</strong> ${escapeHtml(pasajero || 'N/D')}</p>
                    <p><strong>Documento:</strong> ${escapeHtml(ticket.pasajero_documento || 'N/D')}</p>
                    <p><strong>Fecha:</strong> ${escapeHtml(ticket.fecha_viaje || '')}</p>
                    <p><strong>Horario:</strong> ${escapeHtml((ticket.hora_salida || '') + ' - ' + (ticket.hora_llegada || ''))}</p>
                    <p><strong>Asiento:</strong> ${escapeHtml(ticket.numero_asiento || '')}${ticket.vagon ? ` · Vagón ${escapeHtml(ticket.vagon)}` : ''}</p>
                    <p><strong>Precio:</strong> ${formatMoney(ticket.precio_pagado)}</p>
                </div>
                ${generateQRCode(ticket)}
            </div>
            <div class="ticket-detail-actions">
                <a class="btn-link" href="${config.downloadUrl || 'php/descargar_billete.php'}?id_mongo=${encodeURIComponent(ticket.id_mongo || '')}">
                    <i class="fa-solid fa-file-pdf"></i> Descargar PDF
                </a>
            </div>
        `;
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        if (modalBody) modalBody.innerHTML = '';
    }

    function openModal(ticketId) {
        if (!modal || !modalBody) return;
        const ticket = ticketsById.get(ticketId);
        if (!ticket) return;

        modalBody.innerHTML = renderTicketModal(ticket);
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');

        setTimeout(() => {
            const qrEl = modalBody.querySelector('.qr-target');
            if (!qrEl || qrEl.childElementCount > 0) return;
            const qrData = buildQrPayloadLikePdf(ticket);
            try {
                if (typeof QRCode !== 'function') {
                    qrEl.innerHTML = '<span style="color:#8e2e2e;font-size:0.85rem;">No se pudo cargar QR</span>';
                    return;
                }
                new QRCode(qrEl, {
                    text: qrData,
                    width: 170,
                    height: 170,
                    colorDark: '#0a2a66',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
            } catch (e) {
                console.error('Error generating QR code:', e);
            }
        }, 30);
    }

    function attachTicketListeners() {
        document.querySelectorAll('.ticket-row').forEach((row) => {
            const ticketId = row.getAttribute('data-ticket-id');
            const btn = row.querySelector('.btn-open-modal');
            const btnCancel = row.querySelector('.btn-cancel-ticket');

            row.addEventListener('click', (e) => {
                if (e.target.closest('a') || e.target.closest('.btn-cancel-ticket')) return;
                openModal(ticketId);
            });

            if (btn) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openModal(ticketId);
                });
            }

            // Listener actualizado para el botón de cancelar
            if (btnCancel) {
                btnCancel.addEventListener('click', (e) => {
                    e.stopPropagation(); 
                    const codigoBillete = btnCancel.getAttribute('data-codigo');
                    abrirModalCancelacion(codigoBillete);
                });
            }
        });
    }

    // --- NUEVAS FUNCIONES PARA EL FLUJO DE CANCELACIÓN ---

    function abrirModalCancelacion(codigoBillete) {
        const modal = document.getElementById('ticketModal');
        const modalBody = document.getElementById('ticketModalBody');
        const modalTitle = modal.querySelector('.ticket-modal-top h3');
        
        if (modalTitle) modalTitle.textContent = 'Confirmar Cancelación';

        // Inyectamos el diseño de confirmación dentro del modal
        modalBody.innerHTML = `
            <div style="text-align: center; padding: 20px;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 3rem; color: #8e2e2e; margin-bottom: 15px;"></i>
                <p style="font-size: 1.1rem; margin-bottom: 20px;">
                    ¿Estás seguro de que deseas cancelar el billete <strong>${escapeHtml(codigoBillete)}</strong>?<br>
                    Esta acción liberará tu asiento y no se puede deshacer.
                </p>
                <div style="display: flex; justify-content: center; gap: 15px;">
                    <button id="btnConfirmarSi" style="background-color: #8e2e2e; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: opacity 0.2s;">Sí, cancelar billete</button>
                    <button id="btnConfirmarNo" style="background-color: #e0e0e0; color: #333; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: background 0.2s;">No, mantener billete</button>
                </div>
            </div>
        `;

        modal.classList.remove('hidden');

        // Botón SÍ: Muestra un loader y llama al servidor
        document.getElementById('btnConfirmarSi').addEventListener('click', async () => {
            modalBody.innerHTML = '<p style="text-align:center; font-size: 1.2rem; padding: 30px;">Procesando cancelación <i class="fa-solid fa-spinner fa-spin"></i></p>';
            await procesarCancelacion(codigoBillete, modalBody);
        });

        // Botón NO: Cierra el modal
        document.getElementById('btnConfirmarNo').addEventListener('click', () => {
            modal.classList.add('hidden');
            if (modalTitle) modalTitle.textContent = 'Detalles del billete';
        });
    }

    async function procesarCancelacion(codigoBillete, modalBody) {
        try {
            // AHORA SÍ: Enviamos JSON como espera procesar_cancelacion.php
            const response = await fetch('php/procesar_cancelacion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ codigo: codigoBillete })
            });

            const data = await response.json();

            // AHORA SÍ: Leemos 'success' y 'message'
            if (data.success) { 
                modalBody.innerHTML = `
                    <div style="text-align: center; padding: 20px;">
                        <i class="fa-solid fa-circle-check" style="font-size: 3rem; color: #2e8e36; margin-bottom: 15px;"></i>
                        <p style="font-size: 1.1rem;">Tu billete ha sido cancelado y el asiento ha sido liberado.</p>
                        <button onclick="document.getElementById('ticketModal').classList.add('hidden')" style="margin-top: 20px; padding: 8px 20px; cursor: pointer; border-radius: 5px; border: 1px solid #ccc; background: white;">Cerrar</button>
                    </div>
                `;
                loadTickets(); // Recarga la lista para que desaparezca visualmente
            } else {
                modalBody.innerHTML = `
                    <div style="text-align: center; padding: 20px;">
                        <i class="fa-solid fa-circle-xmark" style="font-size: 3rem; color: #8e2e2e; margin-bottom: 15px;"></i>
                        <p style="font-size: 1.1rem;">No se pudo cancelar: <br><strong>${escapeHtml(data.message || 'Error desconocido')}</strong></p>
                        <button onclick="document.getElementById('ticketModal').classList.add('hidden')" style="margin-top: 20px; padding: 8px 20px; cursor: pointer; border-radius: 5px; border: 1px solid #ccc; background: white;">Volver</button>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error de red:', error);
            modalBody.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 3rem; color: #8e2e2e; margin-bottom: 15px;"></i>
                    <p style="font-size: 1.1rem;">Error de conexión al intentar cancelar el billete.</p>
                    <button onclick="document.getElementById('ticketModal').classList.add('hidden')" style="margin-top: 20px; padding: 8px 20px; cursor: pointer; border-radius: 5px; border: 1px solid #ccc; background: white;">Cerrar</button>
                </div>
            `;
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

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });

    loadTickets();
});
