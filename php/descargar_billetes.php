<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$reserva = $_SESSION['ultima_reserva'] ?? null;

if (!is_array($reserva) || ($reserva['token'] ?? '') !== $token) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Reserva no encontrada para descargar billetes.';
    exit;
}

$billetes = $reserva['billetes'] ?? [];
if (!is_array($billetes) || count($billetes) === 0) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'No hay billetes para descargar.';
    exit;
}

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('TrainWeb');
$pdf->SetAuthor('TrainWeb');
$pdf->SetTitle('Billetes de reserva');
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 12);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

foreach ($billetes as $idx => $b) {
    $pdf->AddPage();

    $pasajero = trim((string)($b['pasajero_nombre'] ?? '') . ' ' . (string)($b['pasajero_apellidos'] ?? ''));
    $codigo = (string)($b['codigo_billete'] ?? '');
    $origen = (string)($b['origen'] ?? '');
    $destino = (string)($b['destino'] ?? '');
    $fechaViaje = (string)($b['fecha_viaje'] ?? '');
    $horaSalida = (string)($b['hora_salida'] ?? '');
    $horaLlegada = (string)($b['hora_llegada'] ?? '');
    $asiento = (string)($b['numero_asiento'] ?? '');
    $vagon = (string)($b['vagon'] ?? '');
    $precio = number_format((float)($b['precio_pagado'] ?? 0), 2, ',', '.');
    $documento = (string)($b['pasajero_documento'] ?? '');

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

    $style = [
        'border' => 1,
        'vpadding' => 'auto',
        'hpadding' => 'auto',
        'fgcolor' => [10, 42, 102],
        'bgcolor' => false,
        'module_width' => 1,
        'module_height' => 1,
    ];

    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 8, 'TrainWeb - Billete', 0, 1, 'L');
    $pdf->Ln(2);

    $pdf->write2DBarcode($qrPayload ?: $codigo, 'QRCODE,H', 155, 14, 40, 40, $style, 'N');

    $pdf->SetFont('helvetica', '', 11);
    $html = '
        <table cellpadding="4" cellspacing="0" border="0">
            <tr><td><b>Billete:</b></td><td>' . e((string)($idx + 1)) . '</td></tr>
            <tr><td><b>Codigo:</b></td><td>' . e($codigo) . '</td></tr>
            <tr><td><b>Pasajero:</b></td><td>' . e($pasajero) . '</td></tr>
            <tr><td><b>Documento:</b></td><td>' . e($documento) . '</td></tr>
            <tr><td><b>Ruta:</b></td><td>' . e($origen . ' -> ' . $destino) . '</td></tr>
            <tr><td><b>Fecha viaje:</b></td><td>' . e($fechaViaje) . '</td></tr>
            <tr><td><b>Hora:</b></td><td>' . e($horaSalida . ' - ' . $horaLlegada) . '</td></tr>
            <tr><td><b>Asiento:</b></td><td>' . e($asiento) . '</td></tr>
            <tr><td><b>Vagon:</b></td><td>' . e($vagon) . '</td></tr>
            <tr><td><b>Precio:</b></td><td>' . e($precio . ' EUR') . '</td></tr>
        </table>';

    $pdf->writeHTML($html, true, false, true, false, '');
}

$pdf->Output('billetes_trainweb.pdf', 'D');
exit;
