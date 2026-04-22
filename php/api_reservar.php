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

    $idViajeIda = (int)$input['id_viaje'];
    $idViajeVuelta = isset($input['id_viaje_vuelta']) ? (int)$input['id_viaje_vuelta'] : 0;
    $esIdaVuelta = $idViajeVuelta > 0;

    if (!isset($_SESSION['usuario']['id_usuario'])) {
        responderJson(401, ['error' => 'Sesion no valida.']);
    }

    $asientosIda = [];
    $asientosVuelta = [];
    if (isset($input['asientos']) && is_array($input['asientos']) && count($input['asientos']) > 0) {
        foreach ($input['asientos'] as $a) {
            $num = isset($a['numero_asiento']) ? (int)$a['numero_asiento'] : 0;
            if ($num <= 0) {
                responderJson(400, ['error' => 'Hay asientos no validos en la reserva.']);
            }
            $tramo = strtolower(trim((string)($a['tramo'] ?? ($esIdaVuelta ? '' : 'ida'))));
            if ($esIdaVuelta && $tramo !== 'ida' && $tramo !== 'vuelta') {
                responderJson(400, ['error' => 'Debes seleccionar asientos separados para ida y vuelta.']);
            }

            $asiento = [
                'numero_asiento' => $num,
                'vagon' => isset($a['vagon']) ? (int)$a['vagon'] : null,
                'precio' => isset($a['precio']) ? (float)$a['precio'] : null,
            ];

            if ($esIdaVuelta) {
                if ($tramo === 'ida') {
                    $asientosIda[] = $asiento;
                } else {
                    $asientosVuelta[] = $asiento;
                }
            } else {
                $asientosIda[] = $asiento;
            }
        }
    } elseif (isset($input['numero_asiento'])) {
        // Compatibilidad con formato antiguo (1 pasajero)
        $asientosIda[] = [
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

    if (count($asientosIda) !== count($pasajeros)) {
        responderJson(400, ['error' => 'El numero de asientos de ida debe coincidir con el numero de pasajeros.']);
    }
    if ($esIdaVuelta && count($asientosVuelta) !== count($pasajeros)) {
        responderJson(400, ['error' => 'El numero de asientos de vuelta debe coincidir con el numero de pasajeros.']);
    }

    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('Conexion SQL no disponible');
    }

    // 1. Priorizar id_pasajero_compra (vendedor comprando para cliente)
    // 2. Si no existe, buscar por id_usuario del usuario en sesión (cliente normal)
    $idPasajeroComprador = 0;
    
    if (isset($_SESSION['id_pasajero_compra']) && (int)$_SESSION['id_pasajero_compra'] > 0) {
        $idPasajeroComprador = (int)$_SESSION['id_pasajero_compra'];
    } else {
        $stmtPasajero = $pdo->prepare('SELECT id_pasajero FROM pasajero WHERE id_usuario = :id_usuario LIMIT 1');
        $stmtPasajero->execute([':id_usuario' => (int)$_SESSION['usuario']['id_usuario']]);
        $idPasajeroComprador = (int)$stmtPasajero->fetchColumn();
    }

    if ($idPasajeroComprador <= 0) {
        responderJson(403, ['error' => 'Usuario sin perfil de pasajero. Por favor, contacta con administración.']);
    }

    $stmtViaje = $pdo->prepare(
        'SELECT v.id_viaje, v.fecha, v.hora_salida, v.hora_llegada, r.origen, r.destino, t.modelo AS tipo_tren
         FROM viaje v
         JOIN ruta r ON r.id_ruta = v.id_ruta
         JOIN tren t ON t.id_tren = v.id_tren
         WHERE v.id_viaje = :id_viaje LIMIT 1'
    );
    $stmtViaje->execute([':id_viaje' => $idViajeIda]);
    $viajeIda = $stmtViaje->fetch(PDO::FETCH_ASSOC);

    if (!$viajeIda) {
        responderJson(404, ['error' => 'Viaje no encontrado.']);
    }

    $viajeVuelta = null;
    if ($esIdaVuelta) {
        $stmtViaje->execute([':id_viaje' => $idViajeVuelta]);
        $viajeVuelta = $stmtViaje->fetch(PDO::FETCH_ASSOC);

        if (!$viajeVuelta) {
            responderJson(404, ['error' => 'Viaje de vuelta no encontrado.']);
        }
    }

    $mgo = new ConexionMongo();
    $db = $mgo->conectar();
    if (!$db) {
        throw new RuntimeException('Conexion Mongo no disponible');
    }
    $coleccion = $db->selectCollection('billetes');

    foreach ($asientosIda as $i => $asiento) {
        $duplicado = $coleccion->findOne([
            'id_viaje' => $idViajeIda,
            'numero_asiento' => (int)$asiento['numero_asiento'],
            'estado' => 'confirmado',
        ]);

        if ($duplicado) {
            responderJson(409, [
                'error' => 'Uno de los asientos seleccionados ya ha sido reservado. Refresca y elige otro asiento.'
            ]);
        }

        if ($esIdaVuelta) {
            $asientoVuelta = $asientosVuelta[$i] ?? null;
            if ($asientoVuelta === null) {
                responderJson(400, ['error' => 'No se pudo mapear el asiento de vuelta para un pasajero.']);
            }
            $duplicadoVuelta = $coleccion->findOne([
                'id_viaje' => $idViajeVuelta,
                'numero_asiento' => (int)$asientoVuelta['numero_asiento'],
                'estado' => 'confirmado',
            ]);

            if ($duplicadoVuelta) {
                responderJson(409, [
                    'error' => 'Uno de los asientos seleccionados ya esta reservado en la vuelta. Refresca y elige otro asiento.'
                ]);
            }
        }
    }

    $fechaCompra = date('Y-m-d H:i:s');
    $precioTotal = isset($input['precio_total']) ? (float)$input['precio_total'] : 0.0;
    $totalBilletes = count($asientosIda) + ($esIdaVuelta ? count($asientosVuelta) : 0);
    $precioPorBillete = $totalBilletes > 0 ? $precioTotal / $totalBilletes : 0.0;

    $documentos = [];
    foreach ($asientosIda as $i => $asientoIda) {
        $pasajero = $pasajeros[$i];
        $precioIda = $asientoIda['precio'] !== null
            ? (float)$asientoIda['precio']
            : (float)$precioPorBillete;

        $documentos[] = [
            'id_viaje' => $idViajeIda,
            'numero_asiento' => (int)$asientoIda['numero_asiento'],
            'id_pasajero' => $idPasajeroComprador,
            'vagon' => $asientoIda['vagon'],
            'estado' => 'confirmado',
            'tramo' => 'ida',
            'fecha_compra' => $fechaCompra,
            'fecha_viaje' => (string)($viajeIda['fecha'] ?? ''),
            'hora_salida' => (string)($viajeIda['hora_salida'] ?? ''),
            'hora_llegada' => (string)($viajeIda['hora_llegada'] ?? ''),
            'origen' => (string)($viajeIda['origen'] ?? ''),
            'destino' => (string)($viajeIda['destino'] ?? ''),
            'tipo_tren' => (string)($viajeIda['tipo_tren'] ?? ''),
            'precio_pagado' => $precioIda,
            'codigo_billete' => generarCodigoBillete(),
            'pasajero_nombre' => $pasajero['nombre'],
            'pasajero_apellidos' => $pasajero['apellidos'],
            'pasajero_documento' => $pasajero['documento'],
            'pasajero_email' => $pasajero['email'],
        ];

        if ($esIdaVuelta && $viajeVuelta) {
            $asientoVuelta = $asientosVuelta[$i];
            $precioVuelta = $asientoVuelta['precio'] !== null
                ? (float)$asientoVuelta['precio']
                : (float)$precioPorBillete;

            $documentos[] = [
                'id_viaje' => $idViajeVuelta,
                'numero_asiento' => (int)$asientoVuelta['numero_asiento'],
                'id_pasajero' => $idPasajeroComprador,
                'vagon' => $asientoVuelta['vagon'],
                'estado' => 'confirmado',
                'tramo' => 'vuelta',
                'fecha_compra' => $fechaCompra,
                'fecha_viaje' => (string)($viajeVuelta['fecha'] ?? ''),
                'hora_salida' => (string)($viajeVuelta['hora_salida'] ?? ''),
                'hora_llegada' => (string)($viajeVuelta['hora_llegada'] ?? ''),
                'origen' => (string)($viajeVuelta['origen'] ?? ''),
                'destino' => (string)($viajeVuelta['destino'] ?? ''),
                'tipo_tren' => (string)($viajeVuelta['tipo_tren'] ?? ''),
                'precio_pagado' => $precioVuelta,
                'codigo_billete' => generarCodigoBillete(),
                'pasajero_nombre' => $pasajero['nombre'],
                'pasajero_apellidos' => $pasajero['apellidos'],
                'pasajero_documento' => $pasajero['documento'],
                'pasajero_email' => $pasajero['email'],
            ];
        }
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
        'id_viaje' => $idViajeIda,
        'id_viaje_vuelta' => $idViajeVuelta > 0 ? $idViajeVuelta : null,
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
