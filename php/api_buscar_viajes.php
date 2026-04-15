<?php
// Devuelve viajes disponibles según origen, destino y fecha
header('Content-Type: application/json');
require_once 'Conexion.php';
$pdo = (new Conexion())->conectar();
$origen = $_GET['origen'] ?? '';
$destino = $_GET['destino'] ?? '';
$fecha = $_GET['fecha'] ?? '';
if (!$origen || !$destino || !$fecha) {
    echo json_encode(['error'=>'Faltan datos']);
    exit;
}
$stmt = $pdo->prepare('SELECT v.id_viaje, v.fecha, v.hora_salida, v.hora_llegada, v.precio as precio_base, t.modelo as tipo_tren, r.origen, r.destino FROM VIAJE v JOIN TREN t ON v.id_tren = t.id_tren JOIN RUTA r ON v.id_ruta = r.id_ruta WHERE r.origen ILIKE :origen AND r.destino ILIKE :destino AND v.fecha = :fecha AND v.estado != \'cancelado\' ORDER BY v.hora_salida ASC');
$stmt->execute([':origen'=>$origen, ':destino'=>$destino, ':fecha'=>$fecha]);
$viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['viajes'=>$viajes]);
