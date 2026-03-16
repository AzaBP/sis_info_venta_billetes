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

$id_incidencia = (int)($_POST['id_incidencia'] ?? 0);
$accion = strtolower(trim($_POST['accion'] ?? ''));
$resolucion = trim($_POST['resolucion'] ?? '');

if ($id_incidencia <= 0 || ($accion !== 'confirmar' && $accion !== 'resolver')) {
    http_response_code(400);
    echo json_encode(['error' => 'Parametros no validos']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('Conexion no disponible');
    }

    if ($accion === 'confirmar') {
        $stmt = $pdo->prepare("UPDATE incidencia SET estado = 'en_proceso' WHERE id_incidencia = :id");
        $stmt->execute([':id' => $id_incidencia]);
    } else {
        $stmt = $pdo->prepare("UPDATE incidencia SET estado = 'resuelto', resolucion = :resolucion, fecha_resolucion = :fecha_resolucion WHERE id_incidencia = :id");
        $stmt->execute([
            ':id' => $id_incidencia,
            ':resolucion' => $resolucion !== '' ? $resolucion : 'Resuelto por mantenimiento',
            ':fecha_resolucion' => date('Y-m-d H:i:s')
        ]);
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar incidencia']);
}