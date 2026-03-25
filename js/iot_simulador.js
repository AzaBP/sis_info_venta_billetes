/**
 * IoT Sensor Simulator - Gestor de Incidencias Automáticas
 * Se ejecuta automáticamente cada 30 segundos en el panel de mantenimiento
 */

class IoTSimulador {
    constructor(tokenIoT = null) {
        this.tokenIoT = tokenIoT || 'trainweb_iot_test_token_2026';
        this.intervalo = 10000; // 10 segundos entre simulaciones (acelerar testing)
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

        const ahora = Date.now();
        if (ahora - this.ultimaSimulacion < this.intervalo) {
            return;
        }

        this.ultimaSimulacion = ahora;
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

            if (!response.ok) {
                console.warn(`⚠️ [IoT Simulador] HTTP ${response.status}`);
                return;
            }

            const data = await response.json();

            if (data.ok && data.incidencias_generadas > 0) {
                console.log(`✅ [IoT] Ejecución #${this.contador}: ${data.incidencias_generadas} incidencias generadas`, data.detalles);

                // Recargar el panel de incidencias
                this.refrescarIncidencias();

                // Mostrar notificación
                this.mostrarNotificacion(data);
            } else if (data.ok) {
                console.log(`⏳ [IoT] Ejecución #${this.contador}: Sin nuevas incidencias (probando...)`);
            }
        } catch (error) {
            console.error('❌ [IoT] Error:', error);
        }
    }

    refrescarIncidencias() {
        const contenedor = document.getElementById('incidenciasPendientesIot');
        if (!contenedor) return;

        // Recargar el contenido del panel vía AJAX
        fetch('php/api_incidencias_listar_mantenimiento.php')
            .then(r => r.json())
            .then(incidencias => {
                const iotIncidencias = incidencias.filter(inc =>
                    inc.origen?.toLowerCase() === 'iot' &&
                    inc.estado !== 'resuelto'
                );

                if (iotIncidencias.length === 0) {
                    contenedor.innerHTML = `
                        <div class="issue-item low-priority">
                            <p class="issue-desc">No hay incidencias automáticas.</p>
                        </div>
                    `;
                    return;
                }

                contenedor.innerHTML = iotIncidencias.map(inc => {
                    const estado = inc.estado ?? '';
                    const estadoClase = estado === 'reportado' ? 'high-priority' :
                                       (estado === 'en_proceso' ? 'medium-priority' : 'low-priority');
                    const estadoEtiqueta = estado === 'reportado' ? 'REPORTADO' :
                                          (estado === 'en_proceso' ? 'CONFIRMADO' : 'RESUELTO');

                    return `
                        <div class="issue-item ${estadoClase}" data-incidencia-id="${inc.id_incidencia}" data-estado="${estado}">
                            <div class="issue-header">
                                <span class="issue-id">#INC-${inc.id_incidencia}</span>
                                <span class="priority-tag">${estadoEtiqueta}</span>
                            </div>
                            <p class="issue-desc">${escapeHtml(inc.descripcion)}</p>
                            <div class="issue-meta">
                                <span><i class="fa-solid fa-train"></i> Viaje ${inc.id_viaje}</span>
                                <span>IoT</span>
                                <span>Estado: ${estadoEtiqueta}</span>
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
                }).join('');

                // Re-adjuntar event listeners
                if (window.attachIncidenciaListeners) {
                    window.attachIncidenciaListeners();
                }
            })
            .catch(err => console.error('[IoT] Error al refrescar:', err));
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

