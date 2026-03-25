<?php
/**
 * Script de prueba para generar incidencias IoT
 *
 * Uso:
 * 1. Acceder a: http://localhost/ruta/php/sensor_iot_simulador.php?debug=1&token=TU_TOKEN
 * 2. O con curl:
 *    curl -X POST http://localhost/ruta/php/sensor_iot_simulador.php \
 *    -H "X-IoT-Token: TU_TOKEN"
 */

header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';

// Validar token
$tokenEnv = getenv('TRAINWEB_IOT_TOKEN') ?: 'trainweb_iot_test_token_2026';
$tokenReq = $_SERVER['HTTP_X_IOT_TOKEN'] ?? ($_POST['token'] ?? $_GET['token'] ?? '');

$isDebug = isset($_GET['debug']);

if ($tokenReq !== $tokenEnv) {
    if (!$isDebug) {
        http_response_code(403);
        echo json_encode(['error' => 'Token IoT no válido']);
        exit;
    }
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('Conexión no disponible');
    }

    // Obtener viajes activos
    $stmtViajes = $pdo->query(
        "SELECT id_viaje, id_maquinista FROM viaje
         WHERE estado IN ('en_transito', 'proximo')
         LIMIT 5"
    );
    $viajes = $stmtViajes->fetchAll(PDO::FETCH_ASSOC);

    if (empty($viajes)) {
        echo json_encode(['ok' => true, 'mensaje' => 'No hay viajes activos para pruebas']);
        exit;
    }

    // Obtener mantenedor
    $stmtMant = $pdo->query('SELECT id_empleado FROM mantenimiento ORDER BY id_empleado ASC LIMIT 1');
    $idMantenimiento = (int)$stmtMant->fetchColumn();

    if ($idMantenimiento <= 0) {
        echo json_encode(['error' => 'No hay mantenimiento disponible']);
        exit;
    }

    // Incidencias de prueba
    $incidenciasTest = [
        [
            'tipo' => 'temperatura_motor',
            'descripcion' => '[TEST] Temperatura del motor: 95°C (umbral crítico 90°C)',
            'afecta_pasajero' => 1,
        ],
        [
            'tipo' => 'presion_frenos',
            'descripcion' => '[TEST] Presión de frenos baja: 6.3 bar (mínimo requerido: 6.5 bar)',
            'afecta_pasajero' => 1,
        ],
        [
            'tipo' => 'vibracion_ejes',
            'descripcion' => '[TEST] Vibración detectada en eje delantero - Amplitud: 4.2mm',
            'afecta_pasajero' => 1,
        ],
        [
            'tipo' => 'fallo_puerta',
            'descripcion' => '[TEST] Puerta del coche 3 ciclo defectuoso - No cierra correctamente',
            'afecta_pasajero' => 1,
        ],
        [
            'tipo' => 'temp_rodamientos',
            'descripcion' => '[TEST] Temperatura rodamiento eje delantero: 72°C (máximo: 70°C)',
            'afecta_pasajero' => 1,
        ],
        [
            'tipo' => 'nivel_aceite',
            'descripcion' => '[TEST] Nivel de aceite motor bajo - Recarga recomendada',
            'afecta_pasajero' => 0,
        ],
        [
            'tipo' => 'fallo_electrico',
            'descripcion' => '[TEST] Voltaje auxiliar fuera de rango: 23.5V (rango: 24V ±2V)',
            'afecta_pasajero' => 0,
        ],
        [
            'tipo' => 'sensor_ocupacion',
            'descripcion' => '[TEST] Sensor ocupación coche 2 defectuoso - Lecturas inconsistentes',
            'afecta_pasajero' => 0,
        ],
        [
            'tipo' => 'desgaste_ruedas',
            'descripcion' => '[TEST] Desgaste rueda eje 2: 16mm (límite: 12.5mm) - Mantenimiento urgente',
            'afecta_pasajero' => 1,
        ],
    ];

    $incidenciasGeneradas = [];
    $contador = 0;

    foreach ($viajes as $viaje) {
        $idViaje = (int)$viaje['id_viaje'];
        $idMaquinista = (int)$viaje['id_maquinista'];

        // Generar 2-3 incidencias por viaje
        $numInc = mt_rand(2, 3);
        for ($i = 0; $i < $numInc && $contador < count($incidenciasTest); $i++) {
            $inc = $incidenciasTest[$contador];

            // Verificar que no exista
            $stmtCheck = $pdo->prepare(
                "SELECT COUNT(*) FROM incidencia
                 WHERE id_viaje = :id_viaje
                 AND tipo_incidencia = :tipo
                 AND origen = 'iot'
                 AND estado IN ('reportado', 'en_proceso')"
            );
            $stmtCheck->execute([
                ':id_viaje' => $idViaje,
                ':tipo' => $inc['tipo'],
            ]);

            if ((int)$stmtCheck->fetchColumn() > 0) {
                $contador++;
                continue;
            }

            $stmtInsert = $pdo->prepare(
                "INSERT INTO incidencia (id_viaje, id_mantenimiento, id_maquinista, tipo_incidencia, origen, descripcion, fecha_reporte, estado, afecta_pasajero)
                 VALUES (:id_viaje, :id_mantenimiento, :id_maquinista, :tipo_incidencia, :origen, :descripcion, :fecha_reporte, :estado, :afecta_pasajero)"
            );

            $stmtInsert->execute([
                ':id_viaje' => $idViaje,
                ':id_mantenimiento' => $idMantenimiento,
                ':id_maquinista' => $idMaquinista,
                ':tipo_incidencia' => $inc['tipo'],
                ':origen' => 'iot',
                ':descripcion' => $inc['descripcion'],
                ':fecha_reporte' => date('Y-m-d H:i:s'),
                ':estado' => 'reportado',
                ':afecta_pasajero' => $inc['afecta_pasajero'],
            ]);

            $incidenciasGeneradas[] = [
                'viaje' => $idViaje,
                'tipo' => $inc['tipo'],
                'descripcion' => $inc['descripcion'],
            ];

            $contador++;
        }
    }

    echo json_encode([
        'ok' => true,
        'incidencias_generadas' => count($incidenciasGeneradas),
        'detalles' => $incidenciasGeneradas,
        'timestamp' => date('Y-m-d H:i:s'),
        'mensaje' => count($incidenciasGeneradas) . ' incidencias de prueba generadas correctamente',
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>
