<?php
// php/api_reservar.php
session_start();
header('Content-Type: application/json');
require_once 'ConexionMongo.php';
require_once 'Conexion.php';

// 1. Leer los datos enviados por JavaScript (en formato JSON)
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id_viaje']) || !isset($input['numero_asiento'])) {
    echo json_encode(['error' => 'Faltan datos para la reserva.']);
    exit;
}

if (!isset($_SESSION['usuario']['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesion no valida.']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    $stmtPasajero = $pdo->prepare('SELECT id_pasajero FROM pasajero WHERE id_usuario = :id_usuario LIMIT 1');
    $stmtPasajero->execute([':id_usuario' => (int)$_SESSION['usuario']['id_usuario']]);
    $idPasajero = (int)$stmtPasajero->fetchColumn();

    if ($idPasajero <= 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Usuario sin perfil de pasajero.']);
        exit;
    }

    // 2. Conectar a MongoDB
    $mgo = new ConexionMongo();
    $db = $mgo->conectar();
    $coleccion = $db->selectCollection('billetes');

    // 3. Preparar el documento del Billete
    $nuevoBillete = [
        'id_viaje' => (int)$input['id_viaje'],
        'numero_asiento' => (int)$input['numero_asiento'],
        'id_pasajero' => $idPasajero,
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