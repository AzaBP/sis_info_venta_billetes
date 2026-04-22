<?php
// Cancelar un billete (liberar asiento y marcar como cancelado)
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
$id_usuario = isset($data['id_usuario']) ? (int)$data['id_usuario'] : 0;
$id_mongo = isset($data['id_mongo']) ? trim($data['id_mongo']) : '';

if (!$localizador || !$id_usuario) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

// Buscar el billete en MongoDB
$collection = $db->selectCollection('billetes');

$filter = ['codigo_billete' => $localizador, 'id_pasajero' => $id_usuario];
if ($id_mongo) {
    $filter['_id'] = new MongoDB\BSON\ObjectId($id_mongo);
}

$billete = $collection->findOne($filter);

if (!$billete) {
    echo json_encode(['error' => 'Billete no encontrado']);
    exit;
}

// Verificar que no esté ya cancelado
$estadoActual = isset($billete['estado']) ? $billete['estado'] : 'confirmado';
if ($estadoActual === 'cancelado') {
    echo json_encode(['error' => 'El billete ya está cancelado']);
    exit;
}

// Obtener información del asiento para liberarlo
$id_viaje = (int)($billete['id_viaje'] ?? 0);
$numero_asiento = isset($billete['numero_asiento']) ? (int)$billete['numero_asiento'] : 0;

if ($id_viaje > 0 && $numero_asiento > 0) {
    // Obtener el tren del viaje
    $stmt = $pdo->prepare('SELECT id_tren FROM VIAJE WHERE id_viaje = :id_viaje');
    $stmt->execute([':id_viaje' => $id_viaje]);
    $viaje = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($viaje) {
        // Liberar el asiento en PostgreSQL
        $stmt = $pdo->prepare('UPDATE ASiento SET estado = \'disponible\' WHERE numero_asiento = :numero_asiento AND id_tren = :id_tren');
        $stmt->execute([
            ':numero_asiento' => $numero_asiento,
            ':id_tren' => $viaje['id_tren']
        ]);
    }
}

// Actualizar el estado del billete a cancelado en MongoDB
$collection->updateOne(
    $filter,
    ['$set' => ['estado' => 'cancelado', 'fecha_cancelacion' => new MongoDB\BSON\UTCDateTime()]]
);

echo json_encode([
    'ok' => true,
    'mensaje' => 'Billete cancelado correctamente',
    'localizador' => $localizador
]);