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

    $qrPayload = implode("\n", [
        'TrainWeb Billete',
        'Codigo: ' . $codigo,
        'Pasajero: ' . $pasajero,
        'Documento: ' . $documentoIdentidad,
        'Ruta: ' . $origen . ' -> ' . $destino,
        'Fecha viaje: ' . $fechaViaje,
        'Hora: ' . $horaSalida . ' - ' . $horaLlegada,
        'Asiento: ' . $asiento,
        'Vagon: ' . $vagon,
        'Precio: ' . $precio . ' EUR',
    ]);

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('TrainWeb');
    $pdf->SetAuthor('TrainWeb');
    $pdf->SetTitle('Billete ' . $codigo);
    $pdf->SetMargins(12, 12, 12);
    $pdf->SetAutoPageBreak(true, 12);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();

    $pdf->SetFillColor(10, 42, 102);
    $pdf->Rect(12, 12, 186, 22, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetXY(16, 17);
    $pdf->Cell(0, 8, 'TrainWeb - Billete', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(16, 24);
    $pdf->Cell(0, 5, 'Reserva confirmada y lista para embarque', 0, 1, 'L');

    $pdf->SetTextColor(10, 42, 102);
    $pdf->SetDrawColor(216, 224, 239);

    $style = [
        'border' => 1,
        'vpadding' => 'auto',
        'hpadding' => 'auto',
        'fgcolor' => [10, 42, 102],
        'bgcolor' => false,
        'module_width' => 1,
        'module_height' => 1,
    ];

    $pdf->SetFillColor(248, 251, 255);
    $pdf->RoundedRect(12, 40, 118, 92, 4, '1111', 'F');
    $pdf->RoundedRect(134, 40, 64, 92, 4, '1111', 'F');
    $pdf->write2DBarcode($qrPayload ?: $codigo, 'QRCODE,H', 145, 48, 42, 42, $style, 'N');

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetXY(16, 46);
    $pdf->Cell(0, 6, 'Datos del billete', 0, 1, 'L');

    $infoRows = [
        ['Codigo', $codigo],
        ['Pasajero', $pasajero],
        ['Documento', $documentoIdentidad],
        ['Tipo de tren', $tipoTren],
        ['Ruta', $origen . ' -> ' . $destino],
        ['Fecha viaje', $fechaViaje],
        ['Hora', $horaSalida . ' - ' . $horaLlegada],
        ['Asiento', $asiento],
        ['Vagon', $vagon],
        ['Precio', $precio . ' EUR'],
    ];

    $y = 54;
    $pdf->SetFont('helvetica', '', 10.5);
    foreach ($infoRows as [$label, $value]) {
        $pdf->SetXY(16, $y);
        $pdf->SetTextColor(92, 107, 133);
        $pdf->Cell(28, 6, $label . ':', 0, 0, 'L');
        $pdf->SetTextColor(18, 33, 61);
        $pdf->MultiCell(90, 6, $value, 0, 'L', false, 1, 44, $y, true, 0, false, true, 6, 'M');
        $y += 8;
    }

    $pdf->SetTextColor(92, 107, 133);
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->SetXY(138, 90);
    $pdf->MultiCell(54, 14, 'Escanea el QR para mostrar estos datos en cualquier lector compatible.', 0, 'C', false, 1, 138, 90, true);

    $pdf->Output('billete_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $codigo) . '.pdf', 'D');
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'No se pudo generar el PDF del billete: ' . $e->getMessage();
    exit;
}
