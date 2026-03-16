<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'empleado' || ($usuario['tipo_empleado'] ?? '') !== 'maquinista') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$tipo = strtolower(trim($_POST['tipo_incidencia'] ?? ''));
$id_viaje = (int)($_POST['id_viaje'] ?? 0);

$tipos = [
    'frenos' => ['desc' => 'Fallo en sistema de frenos', 'afecta' => true],
    'puertas' => ['desc' => 'Puertas no cierran correctamente', 'afecta' => true],
    'climatizacion' => ['desc' => 'Fallo de climatizacion', 'afecta' => false],
    'senalizacion' => ['desc' => 'Incidencia en senalizacion', 'afecta' => true]
];

if ($tipo === '' || !isset($tipos[$tipo])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de incidencia no valido']);
    exit;
}

if ($id_viaje <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'id_viaje requerido']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('Conexion no disponible');
    }

    // Obtener id_maquinista
    $stmt = $pdo->prepare("SELECT e.id_empleado FROM empleado e WHERE e.id_usuario = :id_usuario AND e.tipo_empleado = 'maquinista' LIMIT 1");
    $stmt->execute([':id_usuario' => (int)$usuario['id_usuario']]);
    $id_maquinista = (int)$stmt->fetchColumn();
    if ($id_maquinista <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Maquinista no valido']);
        exit;
    }

    // Validar que el viaje pertenece al maquinista
    $stmt = $pdo->prepare("SELECT id_viaje FROM viaje WHERE id_viaje = :id_viaje AND id_maquinista = :id_maquinista LIMIT 1");
    $stmt->execute([':id_viaje' => $id_viaje, ':id_maquinista' => $id_maquinista]);
    if (!$stmt->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['error' => 'Viaje no pertenece al maquinista']);
        exit;
    }

    // Asignar mantenimiento (primer tecnico disponible)
    $stmt = $pdo->query("SELECT id_empleado FROM mantenimiento ORDER BY id_empleado ASC LIMIT 1");
    $id_mantenimiento = (int)$stmt->fetchColumn();
    if ($id_mantenimiento <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay mantenimiento disponible']);
        exit;
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
        ':origen' => 'maquinista',
        ':descripcion' => $tipos[$tipo]['desc'],
        ':fecha_reporte' => date('Y-m-d H:i:s'),
        ':estado' => 'en_proceso',
        ':afecta_pasajero' => $tipos[$tipo]['afecta']
    ]);

    $id_incidencia = (int)$stmt->fetchColumn();
    echo json_encode(['ok' => true, 'id_incidencia' => $id_incidencia]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al registrar incidencia']);
}