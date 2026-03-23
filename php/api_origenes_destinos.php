<?php
// Archivo: php/api_origenes_destinos.php
require_once 'Conexion.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $conexion = new Conexion();
    $pdo = $conexion->conectar();

    // Sacamos orígenes y destinos sin repetir
    $stmtOrigen = $pdo->query("SELECT DISTINCT origen FROM RUTA ORDER BY origen ASC");
    $origenes = $stmtOrigen->fetchAll(PDO::FETCH_COLUMN);

    $stmtDestino = $pdo->query("SELECT DISTINCT destino FROM RUTA ORDER BY destino ASC");
    $destinos = $stmtDestino->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'exito' => true,
        'origenes' => $origenes,
        'destinos' => $destinos
    ]);

} catch (PDOException $e) {
    echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
}
?>