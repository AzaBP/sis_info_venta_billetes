<?php
/**
 * Helper Testing IoT Simulador
 * Archivo para testing manual del simulador de sensores
 *
 * Uso:
 * 1. Acceder a: http://localhost/ruta/testing_iot_helper.php
 * 2. Establecer token en la variable de entorno o parámetro
 * 3. Hacer clic en botones para generar incidencias
 */

// Validar token
$tokenEnv = getenv('TRAINWEB_IOT_TOKEN') ?: '';
$tokenParam = $_GET['token'] ?? $_POST['token'] ?? '';

// Si hace especificado token en parámetro y es correcto, continuar
$tokenValido = ($tokenEnv !== '' && $tokenParam === $tokenEnv);
$modoProduccion = !isset($_GET['debug']);

if (!$tokenValido && $modoProduccion) {
    die('🔒 Token no válido. Uso: ?token=TU_TOKEN_AQUI&debug=1');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚂 Testing IoT Simulador</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1d4ed8, #60a5fa);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 2.5rem;
        }

        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }

        .content {
            padding: 30px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section h2 {
            color: #0a2a66;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        button {
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn-generate {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }

        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(34, 197, 94, 0.3);
        }

        .btn-clear {
            background: #ef4444;
            color: white;
        }

        .btn-clear:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .btn-refresh {
            background: #0ea5e9;
            color: white;
        }

        .btn-refresh:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(14, 165, 233, 0.3);
        }

        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            color: #0c4a6e;
        }

        .status {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-family: 'Courier New', monospace;
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
            margin-top: 10px;
        }

        .status.success {
            background: #f0fdf4;
            border-color: #22c55e;
            color: #166534;
        }

        .status.error {
            background: #fef2f2;
            border-color: #ef4444;
            color: #991b1b;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.85rem;
            margin: 2px;
        }

        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef08a; color: #854d0e; }
        .badge-error { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #e0f2fe; color: #0c4a6e; }

        .footer {
            background: #f8fafc;
            padding: 15px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            color: #666;
            font-size: 0.9rem;
        }

        .log-entry {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        .timestamp {
            color: #999;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚂 Testing IoT Simulador</h1>
            <p>Generador de Incidencias de Sensores para Trenes</p>
        </div>

        <div class="content">
            <div class="section">
                <h2>📊 Estado del Simulador</h2>
                <div class="info-box">
                    ✅ El simulador está configurado y listo para generar incidencias automáticamente en el panel de mantenimiento.
                    <br><br>
                    <strong>Intervalo:</strong> 30 segundos<br>
                    <strong>Validación:</strong> Token <?php echo $tokenValido ? '<span class="badge badge-success">✓ Válido</span>' : '<span class="badge badge-error">✗ Inválido</span>'; ?>
                </div>
            </div>

            <div class="section">
                <h2>🎬 Controles de Testing</h2>
                <div class="controls">
                    <button class="btn-generate" onclick="generarIncidencias()">🔴 Generar Incidencias</button>
                    <button class="btn-refresh" onclick="refrescarEstado()">🔄 Refrescar Estado</button>
                    <button class="btn-clear" onclick="limpiarLogs()">🗑️ Limpiar Logs</button>
                </div>
            </div>

            <div class="section">
                <h2>📝 Logs de Ejecución</h2>
                <div id="logContainer" class="status">
                    <div class="log-entry">
                        <span class="timestamp">[<?php echo date('H:i:s'); ?>]</span> Sistema iniciado...
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>📋 Resumen de Sensores</h2>
                <p>Tipos de incidencias que el simulador puede generar:</p>
                <div>
                    <span class="badge badge-info">Temperatura Motor (2%)</span>
                    <span class="badge badge-info">Presión Frenos (3%)</span>
                    <span class="badge badge-info">Vibración Ejes (1.5%)</span>
                    <span class="badge badge-info">Fallo Puerta (2%)</span>
                    <span class="badge badge-info">Temp Rodamientos (1.8%)</span>
                    <span class="badge badge-info">Nivel Aceite (1%)</span>
                    <span class="badge badge-info">Fallo Eléctrico (1.2%)</span>
                    <span class="badge badge-info">Sensor Ocupación (0.5%)</span>
                    <span class="badge badge-info">Desgaste Ruedas (0.8%)</span>
                    <span class="badge badge-info">Sensor Colisión (0.6%)</span>
                </div>
            </div>
        </div>

        <div class="footer">
            📖 Ver documentación en: <code>IoT_DOCUMENTATION.md</code>
        </div>
    </div>

    <script>
        const logContainer = document.getElementById('logContainer');
        const token = '<?php echo htmlspecialchars($tokenParam, ENT_QUOTES, 'UTF-8'); ?>';

        function agregarLog(mensaje, tipo = 'info') {
            const timestamp = new Date().toLocaleTimeString('es-ES');
            const iconos = {
                info: 'ℹ️',
                success: '✅',
                error: '❌',
                warning: '⚠️'
            };

            const entry = document.createElement('div');
            entry.className = 'log-entry';
            entry.innerHTML = `
                <span class="timestamp">[${timestamp}]</span>
                ${iconos[tipo]} <span>${mensaje}</span>
            `;

            logContainer.appendChild(entry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        async function generarIncidencias() {
            agregarLog('Generando incidencias...', 'info');

            try {
                const formData = new FormData();
                if (token) formData.append('token', token);

                const response = await fetch('php/sensor_iot_simulador.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-IoT-Token': token || ''
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.ok) {
                    const count = data.incidencias_generadas || 0;
                    if (count > 0) {
                        agregarLog(`✨ ${count} incidencia(s) generada(s)`, 'success');
                        data.detalles.forEach(inc => {
                            agregarLog(`• Viaje ${inc.viaje}: ${inc.sensor}`, 'info');
                        });
                    } else {
                        agregarLog('No se generaron nuevas incidencias en esta ejecución', 'warning');
                    }
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            } catch (error) {
                agregarLog('Error: ' + error.message, 'error');
            }
        }

        async function refrescarEstado() {
            agregarLog('Refrescando estado...', 'info');

            try {
                const response = await fetch('php/api_incidencias_listar_mantenimiento.php');

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const incidencias = await response.json();
                if (Array.isArray(incidencias)) {
                    const iot = incidencias.filter(inc =>
                        inc.origen?.toLowerCase() === 'iot' && inc.estado !== 'resuelto'
                    );
                    agregarLog(`Incidencias IoT pendientes: ${iot.length}`, 'info');
                } else {
                    agregarLog('Formato inesperado en respuesta', 'warning');
                }
            } catch (error) {
                agregarLog('Error al refrescar: ' + error.message, 'error');
            }
        }

        function limpiarLogs() {
            logContainer.innerHTML = '';
            agregarLog('Logs limpiados', 'info');
        }

        // Mensaje inicial
        document.addEventListener('DOMContentLoaded', () => {
            agregarLog('🚂 Sistema de testing listo');
            agregarLog('Token: ' + (token ? '✓ Configurado' : '✗ No configurado'), token ? 'success' : 'warning');
        });
    </script>
</body>
</html>
