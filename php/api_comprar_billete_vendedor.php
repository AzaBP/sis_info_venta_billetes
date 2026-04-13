<?php
// Compra billete para el cliente gestionado por el vendedor
header('Content-Type: application/json');
session_start();
require_once 'Conexion.php';
$pdo = (new Conexion())->conectar();
$id_pasajero = $_SESSION['cliente_gestionado'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);
$id_viaje = $data['id_viaje'] ?? 0;
$numero_asiento = $data['numero_asiento'] ?? 0;
if (!$id_pasajero || !$id_viaje || !$numero_asiento) {
    echo json_encode(['error'=>'Faltan datos para la compra']);
    exit;
}
// Comprobar asiento disponible
$stmt = $pdo->prepare('SELECT v.id_tren FROM VIAJE v WHERE v.id_viaje = :id_viaje');
$stmt->execute([':id_viaje'=>$id_viaje]);
$viaje = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$viaje) {
    echo json_encode(['error'=>'Viaje no encontrado']);
    exit;
}
$stmt = $pdo->prepare('SELECT estado FROM ASIENTO WHERE numero_asiento = :numero_asiento AND id_tren = :id_tren');
$stmt->execute([':numero_asiento'=>$numero_asiento, ':id_tren'=>$viaje['id_tren']]);
$asiento = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$asiento || $asiento['estado'] !== 'disponible') {
    echo json_encode(['error'=>'Asiento no disponible']);
    exit;
}
// Marcar asiento ocupado
$stmt = $pdo->prepare('UPDATE ASIENTO SET estado = \'ocupado\' WHERE numero_asiento = :numero_asiento AND id_tren = :id_tren');
$stmt->execute([':numero_asiento'=>$numero_asiento, ':id_tren'=>$viaje['id_tren']]);
// Insertar billete (debes tener tabla BILLETE)
$stmt = $pdo->prepare('INSERT INTO BILLETE (id_pasajero, id_viaje, numero_asiento, fecha_compra) VALUES (:id_pasajero, :id_viaje, :numero_asiento, NOW())');
$stmt->execute([
    ':id_pasajero'=>$id_pasajero,
    ':id_viaje'=>$id_viaje,
    ':numero_asiento'=>$numero_asiento
]);
echo json_encode(['ok'=>true]);
