<?php
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

function generarCodigoBillete(): string {
    return 'TW-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    session_start();
    header('Content-Type: application/json');

    require_once __DIR__ . '/ConexionMongo.php';
    require_once __DIR__ . '/Conexion.php';

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || !isset($input['id_viaje'])) {
        responderJson(400, ['error' => 'Faltan datos para la reserva.']);
    }

    if (!isset($_SESSION['usuario']['id_usuario'])) {
        responderJson(401, ['error' => 'Sesion no valida.']);
    }

    $asientos = [];
    if (isset($input['asientos']) && is_array($input['asientos']) && count($input['asientos']) > 0) {
        foreach ($input['asientos'] as $a) {
            $num = isset($a['numero_asiento']) ? (int)$a['numero_asiento'] : 0;
            if ($num <= 0) {
                responderJson(400, ['error' => 'Hay asientos no validos en la reserva.']);
            }
            $asientos[] = [
                'numero_asiento' => $num,
                'vagon' => isset($a['vagon']) ? (int)$a['vagon'] : null,
                'precio' => isset($a['precio']) ? (float)$a['precio'] : null,
            ];
        }
    } elseif (isset($input['numero_asiento'])) {
        // Compatibilidad con formato antiguo (1 pasajero)
        $asientos[] = [
            'numero_asiento' => (int)$input['numero_asiento'],
            'vagon' => null,
            'precio' => isset($input['precio']) ? (float)$input['precio'] : null,
        ];
    } else {
        responderJson(400, ['error' => 'Debes seleccionar al menos un asiento.']);
    }

    $pasajeros = [];
    if (isset($input['pasajeros']) && is_array($input['pasajeros']) && count($input['pasajeros']) > 0) {
        foreach ($input['pasajeros'] as $p) {
            $nombre = trim((string)($p['nombre'] ?? ''));
            $apellidos = trim((string)($p['apellidos'] ?? ''));
            $documento = trim((string)($p['documento'] ?? ''));
            $email = trim((string)($p['email'] ?? ''));

            if ($nombre === '' || $apellidos === '' || $documento === '' || $email === '') {
                responderJson(400, ['error' => 'Completa todos los datos de pasajeros.']);
            }

            $pasajeros[] = [
                'nombre' => $nombre,
                'apellidos' => $apellidos,
                'documento' => $documento,
                'email' => $email,
            ];
        }
    } else {
        // Compatibilidad con formato antiguo
        $pasajeros[] = [
            'nombre' => (string)($_SESSION['usuario']['nombre'] ?? 'Pasajero'),
            'apellidos' => (string)($_SESSION['usuario']['apellido'] ?? ''),
            'documento' => '',
            'email' => (string)($_SESSION['usuario']['email'] ?? ''),
        ];
    }

    if (count($asientos) !== count($pasajeros)) {
        responderJson(400, ['error' => 'El numero de asientos debe coincidir con el numero de pasajeros.']);
    }

    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('Conexion SQL no disponible');
    }

    $stmtPasajero = $pdo->prepare('SELECT id_pasajero FROM pasajero WHERE id_usuario = :id_usuario LIMIT 1');
    $stmtPasajero->execute([':id_usuario' => (int)$_SESSION['usuario']['id_usuario']]);
    $idPasajeroComprador = (int)$stmtPasajero->fetchColumn();

    if ($idPasajeroComprador <= 0) {
        responderJson(403, ['error' => 'Usuario sin perfil de pasajero.']);
    }

    $stmtViaje = $pdo->prepare(
        'SELECT v.id_viaje, v.fecha, v.hora_salida, v.hora_llegada, r.origen, r.destino, t.modelo AS tipo_tren
         FROM viaje v
         JOIN ruta r ON r.id_ruta = v.id_ruta
         JOIN tren t ON t.id_tren = v.id_tren
         WHERE v.id_viaje = :id_viaje LIMIT 1'
    );
    $stmtViaje->execute([':id_viaje' => (int)$input['id_viaje']]);
    $viaje = $stmtViaje->fetch(PDO::FETCH_ASSOC);

    if (!$viaje) {
        responderJson(404, ['error' => 'Viaje no encontrado.']);
    }

    $mgo = new ConexionMongo();
    $db = $mgo->conectar();
    if (!$db) {
        throw new RuntimeException('Conexion Mongo no disponible');
    }
    $coleccion = $db->selectCollection('billetes');

    foreach ($asientos as $asiento) {
        $duplicado = $coleccion->findOne([
            'id_viaje' => (int)$input['id_viaje'],
            'numero_asiento' => (int)$asiento['numero_asiento'],
            'estado' => 'confirmado',
        ]);

        if ($duplicado) {
            responderJson(409, [
                'error' => 'Uno de los asientos seleccionados ya ha sido reservado. Refresca y elige otro asiento.'
            ]);
        }
    }

    $fechaCompra = date('Y-m-d H:i:s');
    $precioTotal = isset($input['precio_total']) ? (float)$input['precio_total'] : 0.0;
    $precioPorBillete = count($asientos) > 0 ? $precioTotal / count($asientos) : 0.0;

    $documentos = [];
    foreach ($asientos as $i => $asiento) {
        $pasajero = $pasajeros[$i];
        $documentos[] = [
            'id_viaje' => (int)$input['id_viaje'],
            'numero_asiento' => (int)$asiento['numero_asiento'],
            'id_pasajero' => $idPasajeroComprador,
            'vagon' => $asiento['vagon'],
            'estado' => 'confirmado',
            'fecha_compra' => $fechaCompra,
            'fecha_viaje' => (string)($viaje['fecha'] ?? ''),
            'hora_salida' => (string)($viaje['hora_salida'] ?? ''),
            'hora_llegada' => (string)($viaje['hora_llegada'] ?? ''),
            'origen' => (string)($viaje['origen'] ?? ''),
            'destino' => (string)($viaje['destino'] ?? ''),
            'tipo_tren' => (string)($viaje['tipo_tren'] ?? ''),
            'precio_pagado' => $asiento['precio'] !== null ? (float)$asiento['precio'] : (float)$precioPorBillete,
            'codigo_billete' => generarCodigoBillete(),
            'pasajero_nombre' => $pasajero['nombre'],
            'pasajero_apellidos' => $pasajero['apellidos'],
            'pasajero_documento' => $pasajero['documento'],
            'pasajero_email' => $pasajero['email'],
        ];
    }

    $resultado = $coleccion->insertMany($documentos);
    if ($resultado->getInsertedCount() !== count($documentos)) {
        responderJson(500, ['error' => 'No se pudieron guardar todos los billetes.']);
    }

    $insertedIds = [];
    foreach ($resultado->getInsertedIds() as $id) {
        $insertedIds[] = (string)$id;
    }

    $billetesSesion = [];
    foreach ($documentos as $k => $doc) {
        $doc['id_mongo'] = $insertedIds[$k] ?? '';
        $billetesSesion[] = $doc;
    }

    $token = bin2hex(random_bytes(16));
    $_SESSION['ultima_reserva'] = [
        'token' => $token,
        'creado_en' => time(),
        'id_viaje' => (int)$input['id_viaje'],
        'precio_total' => $precioTotal,
        'billetes' => $billetesSesion,
    ];

    responderJson(200, [
        'exito' => true,
        'token' => $token,
        'total_billetes' => count($billetesSesion),
    ]);
} catch (Throwable $e) {
    responderJson(500, ['error' => 'Error del servidor: ' . $e->getMessage()]);
} finally {
    restore_error_handler();
}
