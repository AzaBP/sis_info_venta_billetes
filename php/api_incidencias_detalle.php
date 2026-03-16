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

$esAdmin = trainwebEsAdministrador($usuario);
if (($usuario['tipo_empleado'] ?? '') !== 'mantenimiento' && !$esAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id_incidencia = (int)($_GET['id_incidencia'] ?? 0);
if ($id_incidencia <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'id_incidencia requerido']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('Conexion no disponible');
    }

    $idEmpleado = null;
    if (!$esAdmin) {
        $stmt = $pdo->prepare(
            "SELECT e.id_empleado
             FROM empleado e
             WHERE e.id_usuario = :id_usuario
             LIMIT 1"
        );
        $stmt->execute([':id_usuario' => (int)$usuario['id_usuario']]);
        $idEmpleado = (int)$stmt->fetchColumn();
    }

    $sql = "SELECT i.id_incidencia, i.id_viaje, i.id_mantenimiento, i.id_maquinista, i.tipo_incidencia, i.origen, i.descripcion,
                   i.fecha_reporte, i.estado, i.afecta_pasajero, i.resolucion, i.fecha_resolucion,
                   v.fecha, v.hora_salida, v.hora_llegada, v.estado AS estado_viaje, v.id_tren,
                   r.origen AS ruta_origen, r.destino AS ruta_destino,
                   t.modelo AS tren_modelo, t.capacidad AS tren_capacidad,
                   u.nombre AS maq_nombre, u.apellido AS maq_apellido, u.email AS maq_email, u.telefono AS maq_telefono
            FROM incidencia i
            LEFT JOIN viaje v ON v.id_viaje = i.id_viaje
            LEFT JOIN ruta r ON r.id_ruta = v.id_ruta
            LEFT JOIN tren t ON t.id_tren = v.id_tren
            LEFT JOIN empleado em ON em.id_empleado = i.id_maquinista
            LEFT JOIN usuario u ON u.id_usuario = em.id_usuario
            WHERE i.id_incidencia = :id_incidencia";

    if (!$esAdmin) {
        $sql .= " AND i.id_mantenimiento = :id_mantenimiento";
    }

    $stmt = $pdo->prepare($sql);
    $params = [':id_incidencia' => $id_incidencia];
    if (!$esAdmin) {
        $params[':id_mantenimiento'] = $idEmpleado;
    }
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Incidencia no encontrada']);
        exit;
    }

    echo json_encode($row);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar detalle']);
}