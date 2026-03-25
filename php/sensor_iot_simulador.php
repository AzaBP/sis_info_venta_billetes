<?php
header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';

$token = $_POST['token'] ?? '';
if ($token !== 'trainweb_iot_test_token_2026') {
    echo json_encode(['ok' => false]);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();

    // SETUP: Crear datos básicos si no existen
    $c = (int)$pdo->query("SELECT COUNT(*) FROM mantenimiento")->fetchColumn();
    if ($c === 0) {
        // Insert user for maintenance
        $stmt = $pdo->prepare("INSERT INTO usuario (email, password, tipo_usuario) VALUES (?, ?, ?)");
        $stmt->execute(['mant_iot_test@test', 'test', 'empleado']);
        $uid = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO empleado (id_usuario, tipo_empleado, nombre, apellido) VALUES (?, ?, ?, ?)");
        $stmt->execute([$uid, 'mantenimiento', 'Maint', 'IoT']);
        $eid = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO mantenimiento (id_empleado, especialidad, turno) VALUES (?, ?, ?)");
        $stmt->execute([$eid, 'General', 'manana']);
    }

    $c = (int)$pdo->query("SELECT COUNT(*) FROM maquinista")->fetchColumn();
    if ($c === 0) {
        $stmt = $pdo->prepare("INSERT INTO usuario (email, password, tipo_usuario) VALUES (?, ?, ?)");
        $stmt->execute(['maq_iot_test@test', 'test', 'empleado']);
        $uid = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO empleado (id_usuario, tipo_empleado, nombre, apellido) VALUES (?, ?, ?, ?)");
        $stmt->execute([$uid, 'maquinista', 'Maq', 'IoT']);
        $eid = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO maquinista (id_empleado, numero_licencia, fecha_expedicion) VALUES (?, ?, NOW())");
        $stmt->execute([$eid, 'LIC_IOT_001']);
    }

    $c = (int)$pdo->query("SELECT COUNT(*) FROM tren")->fetchColumn();
    if ($c === 0) {
        $stmt = $pdo->prepare("INSERT INTO tren (modelo, asientos, velocidad_maxima, año_fabricacion) VALUES (?, ?, ?, ?)");
        $stmt->execute(['T_IoT_001', 300, 200, 2024]);
    }

    $c = (int)$pdo->query("SELECT COUNT(*) FROM ruta")->fetchColumn();
    if ($c === 0) {
        $stmt = $pdo->prepare("INSERT INTO ruta (origen, destino, distancia, duracion_estimada) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Station A', 'Station B', 100, 60]);
    }

    $c = (int)$pdo->query("SELECT COUNT(*) FROM viaje")->fetchColumn();
    if ($c === 0) {
        $maq = $pdo->query("SELECT id_maquinista FROM maquinista LIMIT 1")->fetchColumn();
        $tren = $pdo->query("SELECT id_tren FROM tren LIMIT 1")->fetchColumn();
        $ruta = $pdo->query("SELECT id_ruta FROM ruta LIMIT 1")->fetchColumn();
        if ($maq && $tren && $ruta) {
            $stmt = $pdo->prepare("INSERT INTO viaje (id_maquinista, id_tren, id_ruta, estado, fecha_salida, hora_salida, hora_llegada, numero_asientos_disponibles) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
            for ($i = 0; $i < 5; $i++) {
                $stmt->execute([$maq, $tren, $ruta, 'proximo', '08:00', '11:00', 150]);
            }
        }
    }

    // Limpiar incidencias viejas - ELIMINAR TODAS las iOT no resueltas después de 24h
    $pdo->exec("DELETE FROM incidencia WHERE origen = 'iot' AND estado != 'resuelto' AND fecha_reporte < (NOW() - INTERVAL '24 hours')");

    // Obtener viajes
    $viajes = $pdo->query("SELECT id_viaje, id_maquinista FROM viaje LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $maint = $pdo->query("SELECT id_empleado FROM mantenimiento LIMIT 1")->fetchColumn();

    if (empty($viajes) || !$maint) {
        echo json_encode(['ok' => true, 'gen' => 0]);
        exit;
    }

    $mensajes = [
        'Motor temperatura crítica 98°C',
        'Sensor frenos: presión baja 6.2bar',
        'Vibración detectada en eje delantero',
        'Puerta coche 2 no cierra correctamente',
        'Rodamiento sobrecalentado 75°C',
        'Sistema eléctrico fallo: voltaje 23.5V',
        'Sensor ocupación defectuoso',
        'Desgaste de ruedas: 16mm',
    ];

    $gen = 0;
    foreach ($viajes as $v) {
        // GENERAR SIEMPRE 2 INCIDENCIAS POR VIAJE
        for ($i = 0; $i < 2; $i++) {
            $tipo = 'sensor_' . uniqid();
            $desc = $mensajes[mt_rand(0, count($mensajes) - 1)];

            // Verificar no duplicada RECIENTE
            $check = $pdo->prepare("SELECT COUNT(*) FROM incidencia WHERE id_viaje = ? AND tipo_incidencia = ? AND fecha_reporte > (NOW() - INTERVAL '2 minutes')");
            $check->execute([$v['id_viaje'], $tipo]);
            if ($check->fetchColumn() > 0) continue;

            // INSERTAR
            $ins = $pdo->prepare("INSERT INTO incidencia (id_viaje, id_mantenimiento, id_maquinista, tipo_incidencia, origen, descripcion, fecha_reporte, estado, afecta_pasajero) VALUES (?, ?, ?, ?, 'iot', ?, NOW(), 'reportado', true)");
            if ($ins->execute([$v['id_viaje'], $maint, $v['id_maquinista'], $tipo, $desc])) {
                $gen++;
            }
        }
    }

    echo json_encode([
        'ok' => true,
        'incidencias_generadas' => $gen,
        'gen' => $gen,
        'viajes' => count($viajes)
    ]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>
