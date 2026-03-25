/**
 * IoT Sensor Simulator - Gestor de Incidencias Automáticas
 * Se ejecuta automáticamente cada 30 segundos en el panel de mantenimiento
 */

class IoTSimulador {
    constructor(tokenIoT = null) {
        this.tokenIoT = tokenIoT || 'trainweb_iot_test_token_2026';
        this.intervalo = 5000; // 5 segundos PARA TESTING
        this.running = false;
        this.ultimaSimulacion = 0;
        this.contador = 0;
        this.iniciar();
    }

    iniciar() {
        if (this.running) return;
        this.running = true;
        console.log('🚂 [IoT] Simulador iniciado - Generando incidencias automáticamente...');

        // Ejecutar primera simulación inmediatamente
        this.simular();

        // Después ejecutar cada X segundos
        setInterval(() => this.simular(), this.intervalo);
    }

    detener() {
        this.running = false;
        console.log('🛑 [IoT] Simulador detenido');
    }

    async simular() {
        if (!this.running) return;

        this.contador++;

        try {
            const url = 'php/sensor_iot_simulador.php';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-IoT-Token': this.tokenIoT
                },
                body: `token=${encodeURIComponent(this.tokenIoT)}`,
            });

            const data = await response.json();
            console.log(`🚂 [IoT #${this.contador}] respuesta:`, data);

            if (data.ok) {
                const gen = data.incidencias_generadas || data.gen || 0;
                console.log(`🚂 [IoT #${this.contador}] Generadas: ${gen} incidencias, viajes procesados: ${data.viajes || 0}`);

                // SIEMPRE refrescar el panel
                this.refrescarIncidencias();

                if (gen > 0) {
                    this.mostrarNotificacion(data);
                }
            } else {
                console.warn(`⚠️ [IoT] Error: ${data.error || 'desconocido'}`);
            }
        } catch (error) {
            console.error('❌ [IoT] Error fetch:', error);
        }
    }

    refrescarIncidencias() {
        const contenedor = document.getElementById('incidenciasPendientesIot');
        if (!contenedor) {
            console.warn('[IoT] No contenedor encontrado');
            return;
        }

        // Forzar recarga completa de la página de incidencias
        fetch('php/api_incidencias_listar_mantenimiento.php', { credentials: 'same-origin' })
            .then(r => {
                console.log('[IoT] API response status:', r.status);
                return r.json();
            })
            .then(data => {
                console.log('[IoT] API data (todas incidencias):', data);
                if (!Array.isArray(data)) {
                    console.warn('[IoT] Data no es array:', typeof data);
                    return;
                }

                console.log(`[IoT] Total de incidencias: ${data.length}`);

                // Filtrar SOLO incidencias IoT no resueltas
                const iotIncidencias = data.filter(inc => {
                    const esIot = String(inc.origen || '').toLowerCase() === 'iot';
                    const noResuelto = inc.estado !== 'resuelto';
                    if (data.length < 20) { // Solo log detallado si pocos registros
                        console.log(`[IoT] Inc #${inc.id_incidencia}: origen="${inc.origen}" (esIot=${esIot}), estado="${inc.estado}" (noResuelto=${noResuelto})`);
                    }
                    return esIot && noResuelto;
                });

                console.log(`[IoT] Filtradas de IoT no resueltas: ${iotIncidencias.length}`);

                if (iotIncidencias.length === 0) {
                    contenedor.innerHTML = '<div class="issue-item low-priority"><p class="issue-desc">No hay incidencias automáticas.</p></div>';
                    return;
                }

                // Renderizar incidencias
                let html = '';
                iotIncidencias.forEach(inc => {
                    const estado = inc.estado || '';
                    const clase = estado === 'reportado' ? 'high-priority' : (estado === 'en_proceso' ? 'medium-priority' : 'low-priority');
                    const etiqueta = estado === 'reportado' ? 'REPORTADO' : (estado === 'en_proceso' ? 'CONFIRMADO' : 'RESUELTO');

                    html += `
                        <div class="issue-item iot ${clase}" data-incidencia-id="${inc.id_incidencia}" data-estado="${estado}">
                            <div class="issue-header">
                                <span class="issue-id">#INC-${inc.id_incidencia}</span>
                                <span class="priority-tag">${etiqueta}</span>
                            </div>
                            <p class="issue-desc">${escapeHtml(inc.descripcion)}</p>
                            <div class="issue-meta">
                                <span><i class="fa-solid fa-train"></i> Viaje ${inc.id_viaje}</span>
                                <span>IoT</span>
                                <span>Estado: ${etiqueta}</span>
                            </div>
                            <div class="issue-actions">
                                <button class="btn-detail" data-incidencia-id="${inc.id_incidencia}">Ver detalles</button>
                                ${estado === 'reportado' ?
                                    `<button class="btn-resolve btn-confirm" data-action="confirmar" data-incidencia-id="${inc.id_incidencia}">Confirmar</button>` :
                                    (estado === 'en_proceso' ?
                                        `<button class="btn-resolve btn-final" data-action="resolver" data-incidencia-id="${inc.id_incidencia}">Resuelto</button>` :
                                        '')
                                }
                            </div>
                        </div>
                    `;
                });

                contenedor.innerHTML = html;
                console.log(`✅ Panel IoT actualizado con ${iotIncidencias.length} incidencias`);
            })
            .catch(err => console.error('[IoT] Error refresh:', err));
    }

    mostrarNotificacion(data) {
        if (!('Notification' in window)) return;

        if (Notification.permission === 'granted') {
            const incidencias = data.detalles || [];
            const titulo = `🚂 ${data.incidencias_generadas} Incidencia(s) IoT`;
            const msg = incidencias.slice(0, 2)
                .map(inc => `${inc.sensor}: ${inc.descripcion.substring(0, 35)}...`)
                .join('\n');

            new Notification(titulo, {
                body: msg,
                icon: 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="75" font-size="75">🚂</text></svg>',
                tag: 'iot-incidencia',
                requireInteraction: false,
            });
        }
    }

    cambiarIntervalo(ms) {
        this.intervalo = ms;
        console.log(`⏱️ [IoT] Intervalo cambiado a ${ms}ms`);
    }
}

// Utilidad para escapar HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Inicializar simulador cuando el documento esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Solicitar permiso para notificaciones
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Inicializar simulador (puede recibir token desde variable global)
    const tokenIoT = window.TRAINWEB_IOT_TOKEN || 'trainweb_iot_test_token_2026';
    window.iotSimulador = new IoTSimulador(tokenIoT);

    console.log('✅ [IoT] Sistema listo');
});

