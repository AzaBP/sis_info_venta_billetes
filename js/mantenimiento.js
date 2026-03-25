document.addEventListener('DOMContentLoaded', () => {
    const pendingContainer = document.getElementById('incidenciasPendientes');
    const pendingIotContainer = document.getElementById('incidenciasPendientesIot');
    const historyContainer = document.getElementById('incidenciasHistorico');
    const refreshBtn = document.getElementById('refreshNow');
    const scrollTopBtn = document.getElementById('scrollTop');
    const filterOptions = document.querySelectorAll('.filter-option');
    const filterToggle = document.getElementById('filterToggle');
    const filterMenu = document.getElementById('filterMenu');
    const profileForm = document.getElementById('profileForm');
    const profileStatus = document.getElementById('profileStatus');
    const profileToggle = document.getElementById('profileToggle');
    const profilePanel = document.querySelector('.profile-panel');
    const detailModal = document.getElementById('detailModal');
    const detailModalBody = document.getElementById('detailModalBody');
    const detailClose = document.getElementById('detailClose');
    const profileNavBtn = document.getElementById('profileNavBtn');

    let allData = [];
    let pendingManual = [];
    let pendingIot = [];
    let historyData = [];
    let currentFilter = 'all';
    let currentId = null;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function safe(value, fallback = '-') {
        if (value === null || value === undefined) return fallback;
        const str = String(value).trim();
        return str === '' ? fallback : str;
    }

    function safeHtml(value, fallback = '-') {
        return escapeHtml(safe(value, fallback));
    }

    function openModal(html) {
        if (!detailModal || !detailModalBody) return;
        detailModalBody.innerHTML = html;
        detailModal.hidden = false;
        detailModal.classList.add('active');
        document.body.classList.add('modal-open');
    }

    function closeModal() {
        if (!detailModal || !detailModalBody) return;
        detailModal.hidden = true;
        detailModal.classList.remove('active');
        detailModalBody.innerHTML = '';
        document.body.classList.remove('modal-open');
    }

    function estadoClase(estado) {
        if (estado === 'reportado') return 'high-priority';
        if (estado === 'en_proceso') return 'medium-priority';
        return 'low-priority';
    }

    function estadoEtiqueta(estado) {
        if (estado === 'reportado') return 'REPORTADO';
        if (estado === 'en_proceso') return 'CONFIRMADO';
        return 'RESUELTO';
    }

    function estadoPill(estado) {
        if (estado === 'reportado') return 'status-reportado';
        if (estado === 'en_proceso') return 'status-en_proceso';
        return 'status-resuelto';
    }

    function getMaintId() {
        const main = document.querySelector('.maint-container');
        if (!main) return 0;
        const raw = main.getAttribute('data-maint-id') || '0';
        return parseInt(raw, 10) || 0;
    }

    function renderEmpty(container, message) {
        if (!container) return;
        container.innerHTML = '';
        const empty = document.createElement('div');
        empty.className = 'issue-item low-priority';
        empty.innerHTML = `<p class="issue-desc">${safeHtml(message)}</p>`;
        container.appendChild(empty);
    }

    function buildCard(inc, isHistory) {
        const card = document.createElement('div');
        card.className = `issue-item ${estadoClase(inc.estado)}`;
        card.dataset.incidenciaId = inc.id_incidencia;
        card.dataset.estado = inc.estado || '';

        const origen = (inc.origen || '').toUpperCase();
        card.innerHTML = `
            <div class="issue-header">
                <span class="issue-id">#INC-${safeHtml(inc.id_incidencia)}</span>
                <span class="priority-tag">${estadoEtiqueta(inc.estado)}</span>
            </div>
            <p class="issue-desc">${safeHtml(inc.descripcion)}</p>
            <div class="issue-meta">
                <span><i class="fa-solid fa-train"></i> Viaje ${safeHtml(inc.id_viaje)}</span>
                <span>${safeHtml(origen || 'ORIGEN')}</span>
                <span class="status-pill ${estadoPill(inc.estado)}">${estadoEtiqueta(inc.estado)}</span>
            </div>
        `;

        const actions = document.createElement('div');
        actions.className = 'issue-actions';

        const detailBtn = document.createElement('button');
        detailBtn.className = 'btn-detail';
        detailBtn.dataset.incidenciaId = inc.id_incidencia;
        detailBtn.textContent = 'Ver detalles';
        actions.appendChild(detailBtn);

        if (!isHistory) {
            if (inc.estado === 'reportado') {
                const btn = document.createElement('button');
                btn.className = 'btn-resolve btn-confirm';
                btn.dataset.action = 'confirmar';
                btn.dataset.incidenciaId = inc.id_incidencia;
                btn.textContent = 'Confirmar';
                actions.appendChild(btn);
            } else if (inc.estado === 'en_proceso') {
                const btn = document.createElement('button');
                btn.className = 'btn-resolve btn-final';
                btn.dataset.action = 'resolver';
                btn.dataset.incidenciaId = inc.id_incidencia;
                btn.textContent = 'Resuelto';
                actions.appendChild(btn);
            }
        }

        card.appendChild(actions);
        return card;
    }

    function renderPendingList(container, list, emptyMessage) {
        if (!container) return;
        container.innerHTML = '';
        if (!list || list.length === 0) {
            renderEmpty(container, emptyMessage);
            return;
        }
        list.forEach(inc => container.appendChild(buildCard(inc, false)));
    }

    function renderHistory(list) {
        if (!historyContainer) return;
        historyContainer.innerHTML = '';
        if (!list || list.length === 0) {
            renderEmpty(historyContainer, 'No hay incidencias resueltas.');
            return;
        }
        list.forEach(inc => historyContainer.appendChild(buildCard(inc, true)));
    }

    function aplicarFiltro() {
        if (currentFilter === 'all') {
            renderPendingList(pendingContainer, pendingManual, 'No hay incidencias pendientes.');
            renderPendingList(pendingIotContainer, pendingIot, 'No hay incidencias automaticas.');
            return;
        }
        if (currentFilter === 'resuelto') {
            renderEmpty(pendingContainer, 'El historico se muestra abajo.');
            renderEmpty(pendingIotContainer, 'El historico se muestra abajo.');
            return;
        }
        const filtradasManual = pendingManual.filter(i => i.estado === currentFilter);
        const filtradasIot = pendingIot.filter(i => i.estado === currentFilter);
        renderPendingList(pendingContainer, filtradasManual, 'No hay incidencias pendientes.');
        renderPendingList(pendingIotContainer, filtradasIot, 'No hay incidencias automaticas.');
    }

    function selectCard(id) {
        document.querySelectorAll('.issue-item.selected').forEach(el => el.classList.remove('selected'));
        if (!id) return;
        const card = document.querySelector(`.issue-item[data-incidencia-id="${id}"]`);
        if (card) {
            card.classList.add('selected');
        }
    }

    async function cargar() {
        try {
            const resp = await fetch('php/api_incidencias_listar_mantenimiento.php', { credentials: 'same-origin' });
            const raw = await resp.text();
            let data = null;
            try {
                data = JSON.parse(raw.replace(/^\uFEFF/, ''));
            } catch (_) {
                renderEmpty(pendingContainer, `Respuesta invalida: ${raw.slice(0, 120)}`);
                renderEmpty(pendingIotContainer, 'No se pudieron cargar incidencias automaticas.');
                return;
            }
            if (!resp.ok) {
                renderEmpty(pendingContainer, data.error || 'No autorizado');
                renderEmpty(pendingIotContainer, data.error || 'No autorizado');
                return;
            }
            const maintId = getMaintId();
            const list = Array.isArray(data) ? data : [];
            allData = Array.isArray(list) ? list : [];
            const esIot = (i) => String(i.origen || '').toLowerCase() === 'iot';

            const manualAsignadas = allData.filter(i => !esIot(i));
            const iotTodas = allData.filter(i => esIot(i));

            pendingManual = manualAsignadas.filter(i => i.estado !== 'resuelto');
            pendingIot = iotTodas.filter(i => i.estado !== 'resuelto');
            historyData = allData.filter(i => i.estado === 'resuelto');
            renderHistory(historyData);
            aplicarFiltro();
            if (currentId) {
                selectCard(currentId);
            }
        } catch (e) {
            renderEmpty(pendingContainer, 'Error al cargar incidencias.');
            renderEmpty(pendingIotContainer, 'Error al cargar incidencias automaticas.');
        }
    }

    async function actualizarIncidencia(id, accion, resolucion) {
        if (!id || !accion) return;
        const body = new URLSearchParams();
        body.set('id_incidencia', id);
        body.set('accion', accion);
        if (resolucion) {
            body.set('resolucion', resolucion);
        }
        try {
            const resp = await fetch('php/api_incidencias_actualizar_mantenimiento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body,
                credentials: 'same-origin'
            });
            const raw = await resp.text();
            let data = null;
            try {
                data = JSON.parse(raw.replace(/^\uFEFF/, ''));
            } catch (_) {
                return;
            }
            if (!resp.ok || !data.ok) {
                return;
            }
            await cargar();
        } catch (e) {
        }
    }

    async function cargarDetalle(id) {
        if (!id) return;
        currentId = id;
        selectCard(id);
        openModal('<p>Cargando detalle...</p>');

        try {
            const resp = await fetch(`php/api_incidencias_detalle.php?id_incidencia=${encodeURIComponent(id)}`, { credentials: 'same-origin' });
            const raw = await resp.text();
            let data = null;
            try {
                data = JSON.parse(raw.replace(/^\uFEFF/, ''));
            } catch (_) {
                openModal('<p>Respuesta invalida al cargar detalle.</p>');
                return;
            }
            if (!resp.ok) {
                openModal(`<p>${safeHtml(data.error || 'No se pudo cargar el detalle.')}</p>`);
                return;
            }

            const estadoLabel = estadoEtiqueta(data.estado);
            const estadoClass = estadoPill(data.estado);
            const afecta = data.afecta_pasajero ? 'Afecta pasajeros' : 'No afecta pasajeros';
            const maqNombre = safe(data.maq_nombre, '');
            const maqApellido = safe(data.maq_apellido, '');
            const maquinista = `${maqNombre} ${maqApellido}`.trim() || '-';
            const ruta = `${safe(data.ruta_origen)} - ${safe(data.ruta_destino)}`;
            const tren = `${safe(data.tren_modelo)} #${safe(data.id_tren)}`;

            const html = `
                <div class="detail-hero">
                    <div>
                        <div class="detail-id">#INC-${safeHtml(data.id_incidencia)}</div>
                        <div class="detail-sub">${safeHtml(data.fecha_reporte)}</div>
                    </div>
                    <span class="status-pill ${estadoClass}">${estadoLabel}</span>
                </div>
                <div class="detail-grid">
                    <div class="detail-card">
                        <h3>Incidencia</h3>
                        <div class="detail-row"><div class="detail-label">Tipo</div><div>${safeHtml(data.tipo_incidencia)}</div></div>
                        <div class="detail-row"><div class="detail-label">Origen</div><div>${safeHtml((data.origen || '').toUpperCase())}</div></div>
                        <div class="detail-row"><div class="detail-label">Estado</div><div>${estadoLabel}</div></div>
                        <div class="detail-row"><div class="detail-label">Afecta</div><div>${safeHtml(afecta)}</div></div>
                    </div>
                    <div class="detail-card">
                        <h3>Viaje</h3>
                        <div class="detail-row"><div class="detail-label">Ruta</div><div>${safeHtml(ruta)}</div></div>
                        <div class="detail-row"><div class="detail-label">Fecha</div><div>${safeHtml(data.fecha)}</div></div>
                        <div class="detail-row"><div class="detail-label">Hora</div><div>${safeHtml(data.hora_salida)} - ${safeHtml(data.hora_llegada)}</div></div>
                        <div class="detail-row"><div class="detail-label">Tren</div><div>${safeHtml(tren)}</div></div>
                    </div>
                    <div class="detail-card detail-full">
                        <h3>Maquinista</h3>
                        <div class="detail-row"><div class="detail-label">Nombre</div><div>${safeHtml(maquinista)}</div></div>
                        <div class="detail-row"><div class="detail-label">Email</div><div>${safeHtml(data.maq_email)}</div></div>
                        <div class="detail-row"><div class="detail-label">Telefono</div><div>${safeHtml(data.maq_telefono)}</div></div>
                    </div>
                    <div class="detail-card detail-full">
                        <h3>Descripcion</h3>
                        <div class="detail-text">${safeHtml(data.descripcion)}</div>
                        <div class="detail-row"><div class="detail-label">Resolucion</div><div>${safeHtml(data.resolucion || 'Pendiente')}</div></div>
                        <div class="detail-row"><div class="detail-label">Fecha res.</div><div>${safeHtml(data.fecha_resolucion || '-')}</div></div>
                    </div>
                </div>
            `;
            openModal(html);
        } catch (e) {
            openModal('<p>No se pudo cargar el detalle.</p>');
        }
    }

    function handleListClick(e) {
        const actionBtn = e.target.closest('.btn-resolve');
        if (actionBtn) {
            e.stopPropagation();
            const id = actionBtn.dataset.incidenciaId;
            const action = actionBtn.dataset.action;
            if (action === 'resolver') {
                const res = prompt('Describe la resolucion (opcional):') || '';
                actualizarIncidencia(id, action, res);
            } else {
                actualizarIncidencia(id, action);
            }
            return;
        }

        const detailBtn = e.target.closest('.btn-detail');
        if (detailBtn) {
            e.stopPropagation();
            const id = detailBtn.dataset.incidenciaId;
            cargarDetalle(id);
            return;
        }

        // Solo abrir detalle desde el boton "Ver detalles"
    }

    function setProfileStatus(message, isError) {
        if (!profileStatus) return;
        profileStatus.textContent = message || '';
        profileStatus.classList.remove('ok', 'err');
        if (message) {
            profileStatus.classList.add(isError ? 'err' : 'ok');
        }
    }

    if (pendingContainer) pendingContainer.addEventListener('click', handleListClick);
    if (pendingIotContainer) pendingIotContainer.addEventListener('click', handleListClick);
    if (historyContainer) historyContainer.addEventListener('click', handleListClick);

    if (refreshBtn) refreshBtn.addEventListener('click', () => cargar());
    if (scrollTopBtn) scrollTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

    if (detailClose) {
        detailClose.addEventListener('click', closeModal);
    }
    if (detailModal) {
        detailModal.addEventListener('click', (e) => {
            if (e.target === detailModal) {
                closeModal();
            }
        });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    filterOptions.forEach(btn => {
        btn.addEventListener('click', () => {
            filterOptions.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.dataset.filter || 'all';
            aplicarFiltro();
            if (filterMenu) filterMenu.classList.add('hidden');
        });
    });

    if (filterToggle && filterMenu) {
        filterToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            filterMenu.classList.toggle('hidden');
        });

        // Cerrar el menú si se hace clic fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.filter-dropdown')) {
                filterMenu.classList.add('hidden');
            }
        });
    }

    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            setProfileStatus('Guardando...', false);
            const formData = new FormData(profileForm);
            try {
                const resp = await fetch('php/api_mantenimiento_actualizar_perfil.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const raw = await resp.text();
                let data = null;
                try {
                    data = JSON.parse(raw.replace(/^\uFEFF/, ''));
                } catch (_) {
                    setProfileStatus('Respuesta invalida del servidor.', true);
                    return;
                }
                if (!resp.ok || !data.ok) {
                    setProfileStatus(data.error || 'No se pudo guardar.', true);
                    return;
                }
                setProfileStatus('Perfil actualizado.', false);
            } catch (e) {
                setProfileStatus('Error al guardar.', true);
            }
        });
    }

    if (profileToggle && profilePanel) {
        profileToggle.addEventListener('click', () => {
            profilePanel.classList.toggle('collapsed');
        });
    }

    if (profileNavBtn && profilePanel) {
        profileNavBtn.addEventListener('click', () => {
            profilePanel.classList.remove('collapsed');
            profilePanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    cargar();
    setInterval(cargar, 15000);
});
