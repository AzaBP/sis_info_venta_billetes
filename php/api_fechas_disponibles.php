<?php
header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';

$origen = trim((string)($_GET['origen'] ?? ''));
$destino = trim((string)($_GET['destino'] ?? ''));
$fecha_desde = (string)($_GET['fecha_desde'] ?? date('Y-m-d'));
$fecha_hasta = date('Y-m-d', strtotime($fecha_desde . ' +30 days'));

if ($origen === '' || $destino === '') {
    echo json_encode(['error' => 'Faltan origen o destino', 'fechas' => []]);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();

    $sql = "SELECT DISTINCT v.fecha
            FROM VIAJE v
            JOIN RUTA r ON v.id_ruta = r.id_ruta
            WHERE r.origen ILIKE :origen
              AND r.destino ILIKE :destino
              AND v.fecha >= :fecha_desde
              AND v.fecha <= :fecha_hasta
              AND v.estado <> 'cancelado'
              AND (v.fecha > CURRENT_DATE OR (v.fecha = CURRENT_DATE AND v.hora_salida > CURRENT_TIME))
            ORDER BY v.fecha ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':origen' => '%' . $origen . '%',
        ':destino' => '%' . $destino . '%',
        ':fecha_desde' => $fecha_desde,
        ':fecha_hasta' => $fecha_hasta
    ]);

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $fechas_disponibles = array_map(fn($row) => (string)$row['fecha'], $resultados);

    echo json_encode([
        'exito' => true,
        'fechas' => $fechas_disponibles,
        'fecha_desde' => $fecha_desde,
        'fecha_hasta' => $fecha_hasta
    ]);
} catch (Exception $e) {
    echo json_encode([
        'exito' => false,
        'error' => $e->getMessage(),
        'fechas' => []
    ]);
}
?>
