<?php
// php/api_reservar.php
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

function responderJson(int $status, array $payload): void {
    if (ob_get_length()) {
        ob_clean();
    }
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json');
    }
    echo json_encode($payload);
    exit;
}

set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    session_start();
    header('Content-Type: application/json');

    require_once __DIR__ . '/ConexionMongo.php';
    require_once __DIR__ . '/Conexion.php';

    // 1. Leer los datos enviados por JavaScript (en formato JSON)
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input) || !isset($input['id_viaje']) || !isset($input['numero_asiento'])) {
        responderJson(400, ['error' => 'Faltan datos para la reserva.']);
    }

    if (!isset($_SESSION['usuario']['id_usuario'])) {
        responderJson(401, ['error' => 'Sesion no valida.']);
    }

    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('Conexion SQL no disponible');
    }

    $stmtPasajero = $pdo->prepare('SELECT id_pasajero FROM pasajero WHERE id_usuario = :id_usuario LIMIT 1');
    $stmtPasajero->execute([':id_usuario' => (int)$_SESSION['usuario']['id_usuario']]);
    $idPasajero = (int)$stmtPasajero->fetchColumn();

    if ($idPasajero <= 0) {
        responderJson(403, ['error' => 'Usuario sin perfil de pasajero.']);
    }

    // 2. Conectar a MongoDB
    $mgo = new ConexionMongo();
    $db = $mgo->conectar();
    if (!$db) {
        throw new RuntimeException('Conexion Mongo no disponible');
    }
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
        // Devolvemos el ID unico de MongoDB como confirmacion
        responderJson(200, [
            'exito' => true, 
            'id_mongo' => (string)$resultado->getInsertedId()
        ]);
    }

    responderJson(500, ['error' => 'No se pudo registrar el billete en la base de datos.']);

} catch (Throwable $e) {
    responderJson(500, ['error' => 'Error del servidor: ' . $e->getMessage()]);
} finally {
    restore_error_handler();
}
?>