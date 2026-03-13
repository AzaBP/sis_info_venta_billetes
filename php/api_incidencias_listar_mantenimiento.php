<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/auth_helpers.php';

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'empleado') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if (($usuario['tipo_empleado'] ?? '') !== 'mantenimiento' && !trainwebEsAdministrador($usuario)) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('Conexion no disponible');
    }

    $sql = "SELECT id_incidencia, id_viaje, id_mantenimiento, id_maquinista, tipo_incidencia, origen, descripcion,
                   fecha_reporte, estado, afecta_pasajero, resolucion, fecha_resolucion
            FROM incidencia
            ORDER BY CASE estado WHEN 'reportado' THEN 1 WHEN 'en_proceso' THEN 2 ELSE 3 END, fecha_reporte DESC";

    $stmt = $pdo->query($sql);
    $incidencias = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    echo json_encode($incidencias);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar incidencias']);
}
