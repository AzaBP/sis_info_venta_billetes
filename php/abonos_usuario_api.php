<?php
session_start();
require_once __DIR__ . '/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

// Verifica que el usuario esté autenticado
$idUsuario = $_SESSION['usuario']['id_usuario'] ?? null;
if (!$idUsuario) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    // Busca el id_pasajero correspondiente al usuario
    $stmt = $pdo->prepare("SELECT id_pasajero FROM PASAJERO WHERE id_usuario = :id_usuario");
    $stmt->execute([':id_usuario' => $idUsuario]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode([]);
        exit;
    }
    $idPasajero = $row['id_pasajero'];

    // Obtiene los abonos del pasajero
    $sql = "SELECT id_abono, tipo, fecha_inicio, fecha_fin, viajes_totales, viajes_restantes,
                   CASE WHEN fecha_fin >= CURRENT_DATE THEN 'activo' ELSE 'vencido' END AS estado
            FROM ABONO
            WHERE id_pasajero = :id_pasajero
            ORDER BY fecha_fin DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_pasajero' => $idPasajero]);
    $abonos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($abonos);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
}