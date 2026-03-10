<?php
// php/api_reservar.php
header('Content-Type: application/json');
require_once 'ConexionMongo.php';

// 1. Leer los datos enviados por JavaScript (en formato JSON)
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id_viaje']) || !isset($input['numero_asiento'])) {
    echo json_encode(['error' => 'Faltan datos para la reserva.']);
    exit;
}

try {
    // 2. Conectar a MongoDB
    $mgo = new ConexionMongo();
    $db = $mgo->conectar();
    $coleccion = $db->selectCollection('billetes');

    // 3. Preparar el documento del Billete
    // OJO: Ponemos id_pasajero a 1 temporalmente. Más adelante lo cogeremos de la sesión (ej: $_SESSION['id_usuario'])
    $nuevoBillete = [
        'id_viaje' => (int)$input['id_viaje'],
        'numero_asiento' => (int)$input['numero_asiento'],
        'id_pasajero' => 1, 
        'estado' => 'confirmado',
        'fecha_compra' => date('Y-m-d H:i:s')
    ];

    // 4. Insertar en MongoDB
    $resultado = $coleccion->insertOne($nuevoBillete);

    if ($resultado->getInsertedCount() > 0) {
        // Devolvemos el ID único de MongoDB como confirmación
        echo json_encode([
            'exito' => true, 
            'id_mongo' => (string)$resultado->getInsertedId()
        ]);
    } else {
        echo json_encode(['error' => 'No se pudo registrar el billete en la base de datos.']);
    }

} catch (Exception $e) {
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>