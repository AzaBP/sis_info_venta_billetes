<?php
// Buscar billete por localizador (codigo_billete) para un cliente específico
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
$localizador = isset($_GET['localizador']) ? trim($_GET['localizador']) : '';
$id_pasajero = isset($_GET['id_pasajero']) ? (int)$_GET['id_pasajero'] : (isset($_GET['id_usuario']) ? (int)$_GET['id_usuario'] : 0);

if (!$localizador) {
    echo json_encode(['error' => 'Localizador requerido']);
    exit;
}

if (!$id_pasajero) {
    echo json_encode(['error' => 'ID de pasajero requerido']);
    exit;
}

// Buscar el billete en MongoDB
$collection = $db->selectCollection('billetes');
$billete = $collection->findOne([
    'codigo_billete' => $localizador,
    'id_pasajero' => $id_pasajero
]);

if (!$billete) {
    echo json_encode(['error' => 'Billete no encontrado']);
    exit;
}

// Obtener información del viaje desde PostgreSQL
$id_viaje = (int)($billete['id_viaje'] ?? 0);
$viajeInfo = null;

if ($id_viaje > 0) {
    $stmt = $pdo->prepare('
        SELECT v.id_viaje, v.fecha, v.hora_salida, v.hora_llegada, v.precio as precio_base, v.estado as estado_viaje,
               t.modelo as tipo_tren, t.id_tren as codigo_tren,
               r.origen, r.destino
        FROM VIAJE v
        JOIN TREN t ON v.id_tren = t.id_tren
        JOIN RUTA r ON v.id_ruta = r.id_ruta
        WHERE v.id_viaje = :id_viaje
    ');
    $stmt->execute([':id_viaje' => $id_viaje]);
    $viajeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}


$fechaCompra = '';
if (isset($billete['fecha_compra'])) {
    if (is_object($billete['fecha_compra']) && method_exists($billete['fecha_compra'], 'toDateTime')) {
        $fechaCompra = $billete['fecha_compra']->toDateTime()->format('Y-m-d H:i:s');
    } else {
        $fechaCompra = (string)$billete['fecha_compra'];
    }
}

// Devolver información del billete
echo json_encode([
    'ok' => true,
    'billete' => [
        'id_mongo' => isset($billete['_id']) ? (string)$billete['_id'] : '',
        'codigo_billete' => (string)($billete['codigo_billete'] ?? ''),
        'id_viaje' => $id_viaje,
        'numero_asiento' => isset($billete['numero_asiento']) ? (int)$billete['numero_asiento'] : null,
        'estado' => (string)($billete['estado'] ?? 'confirmado'),
        'fecha_compra' => $fechaCompra,
        'precio_pagado' => isset($billete['precio_final']) ? (float)$billete['precio_final'] : (isset($billete['precio_pagado']) ? (float)$billete['precio_pagado'] : null),
        'descuento' => isset($billete['descuento']) ? (float)$billete['descuento'] : 0,
        'pasajero_nombre' => (string)($billete['pasajero_nombre'] ?? ''),
        'pasajero_apellidos' => (string)($billete['pasajero_apellidos'] ?? ''),
        'pasajero_documento' => (string)($billete['pasajero_documento'] ?? ''),
        'pasajero_email' => (string)($billete['pasajero_email'] ?? ''),
        'factura' => isset($billete['factura']) ? $billete['factura'] : null
    ],
    'viaje' => $viajeInfo
]);