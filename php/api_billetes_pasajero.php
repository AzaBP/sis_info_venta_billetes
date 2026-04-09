<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/ConexionMongo.php';

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'pasajero') {
    http_response_code(401);
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(['error' => 'Sesion no valida para pasajero']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('Conexion SQL no disponible');
    }

    $stmtPasajero = $pdo->prepare('SELECT id_pasajero FROM pasajero WHERE id_usuario = :id_usuario LIMIT 1');
    $stmtPasajero->execute([':id_usuario' => (int)$usuario['id_usuario']]);
    $idPasajero = (int)$stmtPasajero->fetchColumn();

    if ($idPasajero <= 0) {
        if (ob_get_length()) {
            ob_clean();
        }
        echo json_encode([]);
        exit;
    }

    $mongo = new ConexionMongo();
    $db = $mongo->conectar();
    if (!$db) {
        throw new RuntimeException('Conexion Mongo no disponible');
    }

    $collection = $db->selectCollection('billetes');
    $cursor = $collection->find(['id_pasajero' => $idPasajero], ['sort' => ['fecha_compra' => -1]]);

    $billetes = [];
    $idsViaje = [];

    foreach ($cursor as $doc) {
        $idViaje = (int)($doc['id_viaje'] ?? 0);
        if ($idViaje > 0) {
            $idsViaje[$idViaje] = true;
        }

        $billetes[] = [
            'id_mongo' => isset($doc['_id']) ? (string)$doc['_id'] : '',
            'id_viaje' => $idViaje,
            'codigo_billete' => (string)($doc['codigo_billete'] ?? ''),
            'estado' => (string)($doc['estado'] ?? 'confirmado'),
            'fecha_compra' => (string)($doc['fecha_compra'] ?? ''),
            'precio_pagado' => isset($doc['precio_pagado']) ? (float)$doc['precio_pagado'] : null,
            'numero_asiento' => isset($doc['numero_asiento']) ? (int)$doc['numero_asiento'] : (isset($doc['id_asiento']) ? (int)$doc['id_asiento'] : null)
        ];
    }

    if (count($billetes) === 0) {
        if (ob_get_length()) {
            ob_clean();
        }
        echo json_encode([]);
        exit;
    }

    $viajesPorId = [];
    if (count($idsViaje) > 0) {
        $listaIds = array_keys($idsViaje);
        $placeholders = implode(',', array_fill(0, count($listaIds), '?'));

        $sql = "SELECT v.id_viaje, v.fecha, v.hora_salida, v.hora_llegada,
                       r.origen, r.destino
                FROM viaje v
                JOIN ruta r ON r.id_ruta = v.id_ruta
                WHERE v.id_viaje IN ($placeholders)";

        $stmtViajes = $pdo->prepare($sql);
        $stmtViajes->execute($listaIds);
        $rows = $stmtViajes->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $viajesPorId[(int)$row['id_viaje']] = $row;
        }
    }

    $salida = [];
    foreach ($billetes as $b) {
        $detalleViaje = $viajesPorId[$b['id_viaje']] ?? null;
        $salida[] = [
            'id_mongo' => $b['id_mongo'],
            'id_viaje' => $b['id_viaje'],
            'codigo_billete' => $b['codigo_billete'],
            'estado' => $b['estado'],
            'fecha_compra' => $b['fecha_compra'],
            'precio_pagado' => $b['precio_pagado'],
            'numero_asiento' => $b['numero_asiento'],
            'origen' => $detalleViaje['origen'] ?? '',
            'destino' => $detalleViaje['destino'] ?? '',
            'fecha_viaje' => $detalleViaje['fecha'] ?? '',
            'hora_salida' => $detalleViaje['hora_salida'] ?? '',
            'hora_llegada' => $detalleViaje['hora_llegada'] ?? ''
        ];
    }

    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($salida);
} catch (Throwable $e) {
    http_response_code(500);
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    restore_error_handler();
}
