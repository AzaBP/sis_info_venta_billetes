document.addEventListener('DOMContentLoaded', () => {
    const tripCard = document.querySelector('.maq-trip');
    const statusEl = document.getElementById('maqStatus');
    const buttons = document.querySelectorAll('.btn-incident');

    function setStatus(text, isError) {
        statusEl.textContent = text;
        statusEl.classList.remove('ok', 'err');
        statusEl.classList.add(isError ? 'err' : 'ok');
    }

    function getViajeId() {
        if (!tripCard) return null;
        const id = tripCard.getAttribute('data-viaje');
        return id && id !== '' ? id : null;
    }

    async function reportar(tipo) {
        const viajeId = getViajeId();
        if (!viajeId) {
            setStatus('No hay viaje asignado.', true);
            return;
        }

        const body = new URLSearchParams();
        body.set('id_viaje', viajeId);
        body.set('tipo_incidencia', tipo);

        try {
            const resp = await fetch('php/api_incidencias_reportar_maquinista.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body,
                credentials: 'same-origin'
            });
            let data = null;
            let rawText = await resp.text();
            rawText = rawText.replace(/^\uFEFF/, '');
            try {
                data = JSON.parse(rawText);
            } catch (_) {
                setStatus(`Error en respuesta: ${rawText.slice(0, 120)}`, true);
                return;
            }
            if (!resp.ok || !data.ok) {
                setStatus(data.error || 'Error al enviar incidencia.', true);
                return;
            }
            setStatus(`Incidencia registrada (#${data.id_incidencia}).`, false);
        } catch (e) {
            setStatus(`Error de red al enviar incidencia. ${e && e.message ? e.message : ''}`.trim(), true);
        }
    }

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const tipo = btn.getAttribute('data-tipo');
            if (tipo) {
                reportar(tipo);
            }
        });
    });
});