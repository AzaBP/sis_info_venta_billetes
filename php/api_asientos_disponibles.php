<?php
// Devuelve asientos disponibles para un viaje
header('Content-Type: application/json');
require_once 'Conexion.php';
$pdo = (new Conexion())->conectar();
$id_viaje = isset($_GET['id_viaje']) ? (int)$_GET['id_viaje'] : 0;
if (!$id_viaje) {
    echo json_encode(['error'=>'Viaje no válido']);
    exit;
}
$stmt = $pdo->prepare('SELECT v.id_tren FROM VIAJE v WHERE v.id_viaje = :id_viaje');
$stmt->execute([':id_viaje'=>$id_viaje]);
$viaje = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$viaje) {
    echo json_encode(['error'=>'Viaje no encontrado']);
    exit;
}
$stmt = $pdo->prepare('SELECT numero_asiento FROM ASIENTO WHERE id_tren = :id_tren AND estado = \'disponible\' ORDER BY numero_asiento');
$stmt->execute([':id_tren'=>$viaje['id_tren']]);
$asientos = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode(['asientos'=>$asientos]);
