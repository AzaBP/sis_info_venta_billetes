<?php
header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';

// Token
$tokenEnv = getenv('TRAINWEB_IOT_TOKEN') ?: 'trainweb_iot_test_token_2026';
$tokenReq = $_SERVER['HTTP_X_IOT_TOKEN'] ?? ($_POST['token'] ?? '');

if ($tokenReq !== $tokenEnv) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token no válido']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) throw new Exception('No conexión BD');

    // SETUP AUTOMÁTICO - Crear datos si no existen
    setupAutomatico($pdo);

    // Limpiar incidencias antiguas
    $pdo->exec("DELETE FROM incidencia WHERE origen = 'iot' AND estado = 'reportado' AND fecha_reporte < (NOW() - INTERVAL 24 HOUR)");

    // Obtener viajes
    $stmt = $pdo->query("SELECT id_viaje, id_maquinista FROM viaje ORDER BY RAND() LIMIT 5");
    $viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($viajes)) {
        echo json_encode(['ok' => false, 'error' => 'Sin viajes']);
        exit;
    }

    // Obtener mantenedor
    $maintId = (int)$pdo->query("SELECT id_empleado FROM mantenimiento LIMIT 1")->fetchColumn();
    if (!$maintId) {
        echo json_encode(['ok' => false, 'error' => 'Sin mantenimiento']);
        exit;
    }

    // Mensajes de prueba
    $mensajes = [
        'Temperatura motor crítica: 98°C',
        'Presión frenos baja: 6.2 bar',
        'Vibración detectada en eje',
        'Puerta coche 2 defectuosa',
        'Sensor temperatura anómalo',
        'Sistema eléctrico fallo',
        'Rodamiento sobrecalentado',
        'Nivel aceite bajo',
    ];

    $generadas = 0;

    foreach ($viajes as $viaje) {
        $vid = (int)$viaje['id_viaje'];
        $mid = (int)$viaje['id_maquinista'];

        // Generar 1-2 incidencias por viaje CON ALTA PROBABILIDAD
        $numInc = mt_rand(1, 2);

        for ($i = 0; $i < $numInc; $i++) {
            $tipo = 'sensor_' . mt_rand(1, 999);
            $desc = $mensajes[array_rand($mensajes)];

            // Verificar no duplicado reciente
            $check = $pdo->prepare("SELECT COUNT(*) FROM incidencia WHERE id_viaje = ? AND tipo_incidencia = ? AND fecha_reporte > DATE_SUB(NOW(), INTERVAL 3 MINUTE)");
            $check->execute([$vid, $tipo]);

            if ($check->fetchColumn() > 0) continue;

            // Insertar
            $ins = $pdo->prepare("INSERT INTO incidencia (id_viaje, id_mantenimiento, id_maquinista, tipo_incidencia, origen, descripcion, fecha_reporte, estado, afecta_pasajero) VALUES (?, ?, ?, ?, 'iot', ?, NOW(), 'reportado', 1)");
            $ins->execute([$vid, $maintId, $mid, $tipo, $desc]);
            $generadas++;
        }
    }

    echo json_encode([
        'ok' => true,
        'inc_gen' => $generadas,
        'viajes' => count($viajes),
        'timestamp' => date('H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

function setupAutomatico($pdo) {
    // Mantenedor
    $c = (int)$pdo->query("SELECT COUNT(*) FROM mantenimiento")->fetchColumn();
    if ($c < 1) {
        $pdo->exec("INSERT INTO usuario (email, password, tipo_usuario) VALUES ('iot_maint@test.com', 'test123', 'empleado')");
        $uid = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO empleado (id_usuario, tipo_empleado, nombre, apellido) VALUES ($uid, 'mantenimiento', 'Sistema', 'IoT')");
        $eid = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO mantenimiento (id_empleado, especialidad, turno) VALUES ($eid, 'General', 'manana')");
    }

    // Maquinista
    $c = (int)$pdo->query("SELECT COUNT(*) FROM maquinista")->fetchColumn();
    if ($c < 1) {
        $pdo->exec("INSERT INTO usuario (email, password, tipo_usuario) VALUES ('iot_maq@test.com', 'test123', 'empleado')");
        $uid = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO empleado (id_usuario, tipo_empleado, nombre, apellido) VALUES ($uid, 'maquinista', 'Máquina', 'Test')");
        $eid = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO maquinista (id_empleado, numero_licencia, fecha_expedicion) VALUES ($eid, 'LIC-IOT-001', NOW())");
    }

    // Tren
    $c = (int)$pdo->query("SELECT COUNT(*) FROM tren")->fetchColumn();
    if ($c < 1) {
        $pdo->exec("INSERT INTO tren (modelo, asientos, velocidad_maxima, año_fabricacion) VALUES ('Tren-IoT', 300, 200, 2024)");
    }

    // Ruta
    $c = (int)$pdo->query("SELECT COUNT(*) FROM ruta")->fetchColumn();
    if ($c < 1) {
        $pdo->exec("INSERT INTO ruta (origen, destino, distancia, duracion_estimada) VALUES ('Origen', 'Destino', 100, 60)");
    }

    // Viajes (mínimo 3)
    $c = (int)$pdo->query("SELECT COUNT(*) FROM viaje")->fetchColumn();
    if ($c < 3) {
        $maq = (int)$pdo->query("SELECT id_maquinista FROM maquinista LIMIT 1")->fetchColumn();
        $tren = (int)$pdo->query("SELECT id_tren FROM tren LIMIT 1")->fetchColumn();
        $ruta = (int)$pdo->query("SELECT id_ruta FROM ruta LIMIT 1")->fetchColumn();

        if ($maq && $tren && $ruta) {
            for ($i = 0; $i < 5; $i++) {
                $est = ['proximo', 'en_transito', 'programado', 'en_estacion'][$i % 4];
                $fecha = date('Y-m-d', strtotime("+$i day"));
                $pdo->exec("INSERT INTO viaje (id_maquinista, id_tren, id_ruta, estado, fecha_salida, hora_salida, hora_llegada, numero_asientos_disponibles) VALUES ($maq, $tren, $ruta, '$est', '$fecha 08:00:00', '08:00:00', '11:00:00', 150)");
            }
        }
    }
}
?>
