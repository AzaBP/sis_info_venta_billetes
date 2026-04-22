<?php
// Obtener todos los billetes de un cliente para gestión por el vendedor
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/ConexionMongo.php';

$pdo = (new Conexion())->conectar();
$mongo = new ConexionMongo();
$db = $mongo->conectar();

if (!$db) {
    echo json_encode(['error' => 'Error de conexión a MongoDB']);
    exit;
}

// Obtener parámetros - aceptar id_pasajero o id_usuario
$id_pasajero = isset($_GET['id_pasajero']) ? (int)$_GET['id_pasajero'] : (isset($_GET['id_usuario']) ? (int)$_GET['id_usuario'] : 0);

if (!$id_pasajero) {
    echo json_encode(['error' => 'ID de pasajero requerido']);
    exit;
}

// Buscar todos los billete del cliente en MongoDB
$collection = $db->selectCollection('billetes');
$cursor = $collection->find(
    ['id_pasajero' => $id_pasajero],
    ['sort' => ['fecha_compra' => -1]]
);

$billetes = [];
$idsViaje = [];

foreach ($cursor as $doc) {
    $idViaje = (int)($doc['id_viaje'] ?? 0);
    if ($idViaje > 0) {
        $idsViaje[$idViaje] = true;
    }
    
    $billetes[] = [
        'id_mongo' => isset($doc['_id']) ? (string)$doc['_id'] : '',
        'codigo_billete' => (string)($doc['codigo_billete'] ?? ''),
        'id_viaje' => $idViaje,
        'numero_asiento' => isset($doc['numero_asiento']) ? (int)$doc['numero_asiento'] : null,
        'estado' => (string)($doc['estado'] ?? 'confirmado'),
        'fecha_compra' => isset($doc['fecha_compra']) ? $doc['fecha_compra']->toDateTime()->format('Y-m-d H:i:s') : '',
        'precio_pagado' => isset($doc['precio_final']) ? (float)$doc['precio_final'] : (isset($doc['precio_pagado']) ? (float)$doc['precio_pagado'] : null),
        'descuento' => isset($doc['descuento']) ? (float)$doc['descuento'] : 0,
        'pasajero_nombre' => (string)($doc['pasajero_nombre'] ?? ''),
        'pasajero_apellidos' => (string)($doc['pasajero_apellidos'] ?? ''),
        'pasajero_documento' => (string)($doc['pasajero_documento'] ?? ''),
        'pasajero_email' => (string)($doc['pasajero_email'] ?? '')
    ];
}

// Obtener información de los viajes
$viajesInfo = [];
if (!empty($idsViaje)) {
    $ids = array_keys($idsViaje);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT v.id_viaje, v.fecha, v.hora_salida, v.hora_llegada, v.precio as precio_base, v.estado as estado_viaje,
               t.modelo as tipo_tren, t.id_tren as codigo_tren,
               r.origen, r.destino
        FROM VIAJE v
        JOIN TREN t ON v.id_tren = t.id_tren
        JOIN RUTA r ON v.id_ruta = r.id_ruta
        WHERE v.id_viaje IN ($placeholders)
    ");
    $stmt->execute($ids);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $viajesInfo[$row['id_viaje']] = $row;
    }
}

// Enriquecer los billetes con información del viaje
foreach ($billetes as &$b) {
    if (isset($viajesInfo[$b['id_viaje']])) {
        $b['viaje'] = $viajesInfo[$b['id_viaje']];
    }
}

echo json_encode([
    'ok' => true,
    'billetes' => $billetes
]);