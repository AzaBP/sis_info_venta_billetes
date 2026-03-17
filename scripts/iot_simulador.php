<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Solo CLI\n";
    exit(1);
}

require_once __DIR__ . '/../php/Conexion.php';

$options = getopt('', ['once', 'interval::', 'max::']);
$once = array_key_exists('once', $options);
$interval = isset($options['interval']) ? max(5, (int)$options['interval']) : 20;
$max = isset($options['max']) ? max(1, (int)$options['max']) : 0;

$eventos = [
    ['tipo' => 'obstaculo_en_via', 'desc' => 'Radar detecta obstaculo en la via', 'afecta' => true],
    ['tipo' => 'paso_nivel_ocupado', 'desc' => 'Radar detecta paso a nivel ocupado', 'afecta' => true],
    ['tipo' => 'intrusion_en_via', 'desc' => 'Sensor detecta intrusion en zona de via', 'afecta' => true],
    ['tipo' => 'cojinete_sobrecalentado', 'desc' => 'Detector termico: cojinete sobrecalentado', 'afecta' => true],
    ['tipo' => 'rueda_plana_o_impacto', 'desc' => 'Monitor de ruedas detecta plano/impacto', 'afecta' => true],
    ['tipo' => 'equipo_arrastrando', 'desc' => 'Detector detecta equipo arrastrando', 'afecta' => true],
    ['tipo' => 'ocupacion_tramo_anomala', 'desc' => 'Contador de ejes indica ocupacion anomala', 'afecta' => true],
    ['tipo' => 'tren_detenido_prolongado', 'desc' => 'Sistema detecta tren detenido demasiado tiempo', 'afecta' => true],
];

$pdo = (new Conexion())->conectar();
if (!$pdo) {
    fwrite(STDERR, "Conexion no disponible\n");
    exit(1);
}

function limpiarIot(PDO $pdo): void
{
    $pdo->exec("DELETE FROM incidencia WHERE origen = 'iot' AND estado = 'reportado' AND fecha_reporte < (NOW() - INTERVAL '24 hours')");
}

function seleccionarViaje(PDO $pdo): ?array
{
    $sql = "SELECT id_viaje, id_maquinista
            FROM viaje
            WHERE fecha >= CURRENT_DATE
            ORDER BY fecha ASC, hora_salida ASC
            LIMIT 1";
    $stmt = $pdo->query($sql);
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    return $row ?: null;
}

function seleccionarMantenimiento(PDO $pdo): int
{
    $stmt = $pdo->query("SELECT id_empleado FROM mantenimiento ORDER BY id_empleado ASC LIMIT 1");
    return $stmt ? (int)$stmt->fetchColumn() : 0;
}

function insertarIncidencia(PDO $pdo, array $viaje, array $evento, int $id_mantenimiento): ?int
{
    $stmt = $pdo->prepare(
        "INSERT INTO incidencia (id_viaje, id_mantenimiento, id_maquinista, tipo_incidencia, origen, descripcion, fecha_reporte, estado, afecta_pasajero)
         VALUES (:id_viaje, :id_mantenimiento, :id_maquinista, :tipo_incidencia, :origen, :descripcion, :fecha_reporte, :estado, :afecta_pasajero)
         RETURNING id_incidencia"
    );

    $stmt->execute([
        ':id_viaje' => (int)$viaje['id_viaje'],
        ':id_mantenimiento' => $id_mantenimiento,
        ':id_maquinista' => (int)$viaje['id_maquinista'],
        ':tipo_incidencia' => $evento['tipo'],
        ':origen' => 'iot',
        ':descripcion' => $evento['desc'],
        ':fecha_reporte' => date('Y-m-d H:i:s'),
        ':estado' => 'reportado',
        ':afecta_pasajero' => $evento['afecta'] ? 1 : 0
    ]);

    $id = (int)$stmt->fetchColumn();
    return $id > 0 ? $id : null;
}

$contador = 0;
do {
    limpiarIot($pdo);
    $viaje = seleccionarViaje($pdo);

    if (!$viaje) {
        echo "[IOT] No hay viajes futuros disponibles.\n";
    } else {
        $id_mantenimiento = seleccionarMantenimiento($pdo);
        if ($id_mantenimiento <= 0) {
            echo "[IOT] No hay mantenimiento disponible.\n";
        } else {
            $evento = $eventos[array_rand($eventos)];
            $id = insertarIncidencia($pdo, $viaje, $evento, $id_mantenimiento);
            if ($id) {
                echo "[IOT] Incidencia creada #{$id} ({$evento['tipo']}) para viaje {$viaje['id_viaje']}.\n";
            } else {
                echo "[IOT] No se pudo crear incidencia.\n";
            }
        }
    }

    $contador++;
    if ($once) {
        break;
    }
    if ($max > 0 && $contador >= $max) {
        break;
    }
    sleep($interval);
} while (true);
