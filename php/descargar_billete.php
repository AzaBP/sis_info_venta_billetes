<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/ConexionMongo.php';
require_once __DIR__ . '/Conexion.php';

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'pasajero') {
    http_response_code(401);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Sesion no valida para descargar billetes.';
    exit;
}

$idMongo = isset($_GET['id_mongo']) ? trim((string)$_GET['id_mongo']) : '';
if ($idMongo === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Falta el identificador del billete.';
    exit;
}

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
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
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'No existe un perfil de pasajero asociado a esta cuenta.';
        exit;
    }

    $mongo = new ConexionMongo();
    $db = $mongo->conectar();
    if (!$db) {
        throw new RuntimeException('Conexion Mongo no disponible');
    }

    $collection = $db->selectCollection('billetes');
    $documento = $collection->findOne([
        '_id' => new MongoDB\BSON\ObjectId($idMongo),
        'id_pasajero' => $idPasajero,
    ]);

    if (!$documento) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Billete no encontrado.';
        exit;
    }

    $pasajero = trim((string)($documento['pasajero_nombre'] ?? '') . ' ' . (string)($documento['pasajero_apellidos'] ?? ''));
    $codigo = (string)($documento['codigo_billete'] ?? '');
    $origen = (string)($documento['origen'] ?? '');
    $destino = (string)($documento['destino'] ?? '');
    $fechaViaje = (string)($documento['fecha_viaje'] ?? '');
    $horaSalida = (string)($documento['hora_salida'] ?? '');
    $horaLlegada = (string)($documento['hora_llegada'] ?? '');
    $asiento = (string)($documento['numero_asiento'] ?? '');
    $vagon = (string)($documento['vagon'] ?? '');
    $precio = number_format((float)($documento['precio_pagado'] ?? 0), 2, ',', '.');
    $documentoIdentidad = (string)($documento['pasajero_documento'] ?? '');
    $tipoTren = (string)($documento['tipo_tren'] ?? 'Tren');

    $qrPayload = json_encode([
        'codigo' => $codigo,
        'pasajero' => $pasajero,
        'origen' => $origen,
        'destino' => $destino,
        'fecha' => $fechaViaje,
        'salida' => $horaSalida,
        'llegada' => $horaLlegada,
        'asiento' => $asiento,
        'vagon' => $vagon,
    ], JSON_UNESCAPED_UNICODE);

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('TrainWeb');
    $pdf->SetAuthor('TrainWeb');
    $pdf->SetTitle('Billete ' . $codigo);
    $pdf->SetMargins(12, 12, 12);
    $pdf->SetAutoPageBreak(true, 12);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetTextColor(10, 42, 102);
    $pdf->Cell(0, 10, 'TrainWeb - Billete', 0, 1, 'L');
    $pdf->Ln(2);

    $style = [
        'border' => 1,
        'vpadding' => 'auto',
        'hpadding' => 'auto',
        'fgcolor' => [10, 42, 102],
        'bgcolor' => false,
        'module_width' => 1,
        'module_height' => 1,
    ];

    $pdf->write2DBarcode($qrPayload ?: $codigo, 'QRCODE,H', 155, 16, 38, 38, $style, 'N');

    $html = '
        <table cellpadding="4" cellspacing="0" border="0">
            <tr><td><b>Codigo:</b></td><td>' . e($codigo) . '</td></tr>
            <tr><td><b>Pasajero:</b></td><td>' . e($pasajero) . '</td></tr>
            <tr><td><b>Documento:</b></td><td>' . e($documentoIdentidad) . '</td></tr>
            <tr><td><b>Tipo de tren:</b></td><td>' . e($tipoTren) . '</td></tr>
            <tr><td><b>Ruta:</b></td><td>' . e($origen . ' -> ' . $destino) . '</td></tr>
            <tr><td><b>Fecha viaje:</b></td><td>' . e($fechaViaje) . '</td></tr>
            <tr><td><b>Hora:</b></td><td>' . e($horaSalida . ' - ' . $horaLlegada) . '</td></tr>
            <tr><td><b>Asiento:</b></td><td>' . e($asiento) . '</td></tr>
            <tr><td><b>Vagon:</b></td><td>' . e($vagon) . '</td></tr>
            <tr><td><b>Precio:</b></td><td>' . e($precio . ' EUR') . '</td></tr>
        </table>';

    $pdf->SetFont('helvetica', '', 11);
    $pdf->Ln(6);
    $pdf->writeHTML($html, true, false, true, false, '');

    $pdf->Output('billete_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $codigo) . '.pdf', 'D');
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'No se pudo generar el PDF del billete: ' . $e->getMessage();
    exit;
}
