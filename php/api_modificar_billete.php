<?php
// Modificar un billete (cambiar viaje y/o asiento)
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

$data = json_decode(file_get_contents('php://input'), true);

$localizador = isset($data['localizador']) ? trim($data['localizador']) : '';
$id_pasajero = isset($data['id_pasajero']) ? (int)$data['id_pasajero'] : (isset($data['id_usuario']) ? (int)$data['id_usuario'] : 0);
$id_mongo = isset($data['id_mongo']) ? trim($data['id_mongo']) : '';
$id_viaje_nuevo = isset($data['id_viaje']) ? (int)$data['id_viaje'] : 0;
$numero_asiento_nuevo = isset($data['numero_asiento']) ? (int)$data['numero_asiento'] : 0;

if (!$localizador || !$id_pasajero || !$id_viaje_nuevo || !$numero_asiento_nuevo) {
    echo json_encode(['error' => 'Datos incompletos para la modificación']);
    exit;
}

// Buscar el billete en MongoDB
$collection = $db->selectCollection('billetes');

$filter = ['codigo_billete' => $localizador, 'id_pasajero' => $id_pasajero];
if ($id_mongo) {
    $filter['_id'] = new MongoDB\BSON\ObjectId($id_mongo);
}

$billete = $collection->findOne($filter);

if (!$billete) {
    echo json_encode(['error' => 'Billete no encontrado']);
    exit;
}

// Verificar que no esté cancelado
$estadoActual = isset($billete['estado']) ? $billete['estado'] : 'confirmado';
if ($estadoActual === 'cancelado') {
    echo json_encode(['error' => 'No se puede modificar un billete cancelado']);
    exit;
}

// Obtener información del asiento anterior para liberarlo
$id_viaje_anterior = (int)($billete['id_viaje'] ?? 0);
$numero_asiento_anterior = isset($billete['numero_asiento']) ? (int)$billete['numero_asiento'] : 0;

// Verificar que el nuevo asiento esté disponible
$stmt = $pdo->prepare('SELECT v.id_tren FROM VIAJE v WHERE v.id_viaje = :id_viaje');
$stmt->execute([':id_viaje' => $id_viaje_nuevo]);
$viajeNuevo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$viajeNuevo) {
    echo json_encode(['error' => 'Viaje no encontrado']);
    exit;
}

$stmt = $pdo->prepare('SELECT estado FROM ASiento WHERE numero_asiento = :numero_asiento AND id_tren = :id_tren');
$stmt->execute([':numero_asiento' => $numero_asiento_nuevo, ':id_tren' => $viajeNuevo['id_tren']]);
$asientoNuevo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asientoNuevo || $asientoNuevo['estado'] !== 'disponible') {
    echo json_encode(['error' => 'El nuevo asiento no está disponible']);
    exit;
}

// Obtener precio del nuevo viaje
$stmt = $pdo->prepare('SELECT precio FROM VIAJE WHERE id_viaje = :id_viaje');
$stmt->execute([':id_viaje' => $id_viaje_nuevo]);
$precioViaje = $stmt->fetch(PDO::FETCH_ASSOC);
$precio_nuevo = $precioViaje ? (float)$precioViaje['precio'] : 0;

// TRANSACCIÓN: Liberar asiento anterior y ocupar el nuevo
try {
    $pdo->beginTransaction();
    
    // 1. Liberar asiento anterior (si existe y es diferente)
    if ($id_viaje_anterior > 0 && $numero_asiento_anterior > 0 && 
        ($id_viaje_anterior !== $id_viaje_nuevo || $numero_asiento_anterior !== $numero_asiento_nuevo)) {
        
        $stmt = $pdo->prepare('SELECT id_tren FROM VIAJE WHERE id_viaje = :id_viaje');
        $stmt->execute([':id_viaje' => $id_viaje_anterior]);
        $viajeAnterior = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($viajeAnterior) {
            $stmt = $pdo->prepare('UPDATE ASiento SET estado = \'disponible\' WHERE numero_asiento = :numero_asiento AND id_tren = :id_tren');
            $stmt->execute([
                ':numero_asiento' => $numero_asiento_anterior,
                ':id_tren' => $viajeAnterior['id_tren']
            ]);
        }
    }
    
    // 2. Ocupar el nuevo asiento
    $stmt = $pdo->prepare('UPDATE ASiento SET estado = \'ocupado\' WHERE numero_asiento = :numero_asiento AND id_tren = :id_tren');
    $stmt->execute([
        ':numero_asiento' => $numero_asiento_nuevo,
        ':id_tren' => $viajeNuevo['id_tren']
    ]);
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Error al actualizar los asientos: ' . $e->getMessage()]);
    exit;
}

// Actualizar el billete en MongoDB con los nuevos datos
$updateData = [
    'id_viaje' => $id_viaje_nuevo,
    'numero_asiento' => $numero_asiento_nuevo,
    'precio_final' => $precio_nuevo,
    'fecha_modificacion' => new MongoDB\BSON\UTCDateTime()
];

$collection->updateOne($filter, ['$set' => $updateData]);

echo json_encode([
    'ok' => true,
    'mensaje' => 'Billete modificado correctamente',
    'localizador' => $localizador,
    'nuevo_viaje' => $id_viaje_nuevo,
    'nuevo_asiento' => $numero_asiento_nuevo,
    'precio_nuevo' => $precio_nuevo
]);