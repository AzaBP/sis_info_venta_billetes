<?php
header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';

$tokenEnv = getenv('TRAINWEB_IOT_TOKEN') ?: '';
$tokenReq = $_SERVER['HTTP_X_IOT_TOKEN'] ?? ($_POST['token'] ?? '');

if ($tokenEnv === '' || $tokenReq === '' || !hash_equals($tokenEnv, $tokenReq)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token IOT no valido']);
    exit;
}

$id_viaje = (int)($_POST['id_viaje'] ?? 0);
$tipo = strtolower(trim($_POST['tipo_incidencia'] ?? ''));
$descripcion = trim($_POST['descripcion'] ?? '');
$afecta_pasajero = isset($_POST['afecta_pasajero']) ? (bool)$_POST['afecta_pasajero'] : false;

if ($id_viaje <= 0 || $tipo === '') {
    http_response_code(400);
    echo json_encode(['error' => 'id_viaje y tipo_incidencia son requeridos']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('Conexion no disponible');
    }

    $stmt = $pdo->prepare('SELECT id_maquinista FROM viaje WHERE id_viaje = :id_viaje');
    $stmt->execute([':id_viaje' => $id_viaje]);
    $id_maquinista = (int)$stmt->fetchColumn();
    if ($id_maquinista <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Viaje no valido']);
        exit;
    }

    $stmt = $pdo->query('SELECT id_empleado FROM mantenimiento ORDER BY id_empleado ASC LIMIT 1');
    $id_mantenimiento = (int)$stmt->fetchColumn();
    if ($id_mantenimiento <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay mantenimiento disponible']);
        exit;
    }

    if ($descripcion === '') {
        $descripcion = 'Incidencia IOT detectada';
    }

    $stmt = $pdo->prepare(
        "INSERT INTO incidencia (id_viaje, id_mantenimiento, id_maquinista, tipo_incidencia, origen, descripcion, fecha_reporte, estado, afecta_pasajero)
         VALUES (:id_viaje, :id_mantenimiento, :id_maquinista, :tipo_incidencia, :origen, :descripcion, :fecha_reporte, :estado, :afecta_pasajero)
         RETURNING id_incidencia"
    );

    $stmt->execute([
        ':id_viaje' => $id_viaje,
        ':id_mantenimiento' => $id_mantenimiento,
        ':id_maquinista' => $id_maquinista,
        ':tipo_incidencia' => $tipo,
        ':origen' => 'iot',
        ':descripcion' => $descripcion,
        ':fecha_reporte' => date('Y-m-d H:i:s'),
        ':estado' => 'reportado',
        ':afecta_pasajero' => $afecta_pasajero
    ]);

    $id_incidencia = (int)$stmt->fetchColumn();
    echo json_encode(['ok' => true, 'id_incidencia' => $id_incidencia]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al registrar incidencia IOT']);
}