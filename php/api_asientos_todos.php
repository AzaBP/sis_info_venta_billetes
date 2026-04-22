<?php
header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/ConexionMongo.php';

$id_viaje = isset($_GET['id_viaje']) ? (int)$_GET['id_viaje'] : 0;

if (!$id_viaje) {
    echo json_encode(['error' => 'ID de viaje requerido']);
    exit;
}

try {
    // 1. PostgreSQL: Obtenemos la capacidad real del tren asignado al viaje
    $pdo = (new Conexion())->conectar();
    $stmt = $pdo->prepare('
        SELECT v.id_viaje, t.capacidad, r.origen, r.destino, v.fecha
        FROM VIAJE v
        JOIN TREN t ON v.id_tren = t.id_tren
        JOIN RUTA r ON v.id_ruta = r.id_ruta
        WHERE v.id_viaje = :id_viaje
    ');
    $stmt->execute([':id_viaje' => $id_viaje]);
    $viaje = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$viaje) {
        echo json_encode(['error' => 'Viaje no encontrado']);
        exit;
    }

    $capacidad = (int)$viaje['capacidad'];

    // 2. MongoDB: Buscamos qué asientos ya están comprados en este viaje
    $mongo = new ConexionMongo();
    $db = $mongo->conectar();
    $collection = $db->selectCollection('billetes');
    
    // Solo traemos asientos que no hayan sido cancelados
    $cursor = $collection->find([
        'id_viaje' => $id_viaje,
        'estado' => ['$ne' => 'cancelado']
    ]);

    $asientos_ocupados = [];
    foreach ($cursor as $doc) {
        if (isset($doc['numero_asiento'])) {
            $asientos_ocupados[] = (int)$doc['numero_asiento'];
        }
    }

    // 3. Generamos exactamente el número de asientos del tren (del 1 hasta la $capacidad)
    $asientos = [];
    for ($i = 1; $i <= $capacidad; $i++) {
        $asientos[] = [
            'numero_asiento' => $i,
            'estado' => in_array($i, $asientos_ocupados) ? 'ocupado' : 'disponible'
        ];
    }

    echo json_encode([
        'ok' => true,
        'viaje' => $viaje,
        'asientos' => $asientos
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error al cargar asientos: ' . $e->getMessage()]);
}