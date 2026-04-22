<?php
// Devuelve destinos disponibles para un origen específico
header('Content-Type: application/json; charset=utf-8');
require_once 'Conexion.php';

try {
    $conexion = new Conexion();
    $pdo = $conexion->conectar();

    $origen = $_GET['origen'] ?? '';

    if (!$origen) {
        echo json_encode(['exito' => false, 'error' => 'Origen requerido']);
        exit;
    }

    // Obtener destinos que tienen rutas desde el origen especificado
    $stmt = $pdo->prepare("SELECT DISTINCT r.destino FROM RUTA r WHERE r.origen ILIKE :origen ORDER BY r.destino ASC");
    $stmt->execute([':origen' => '%' . $origen . '%']);
    $destinos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'exito' => true,
        'destinos' => $destinos
    ]);

} catch (PDOException $e) {
    echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
}