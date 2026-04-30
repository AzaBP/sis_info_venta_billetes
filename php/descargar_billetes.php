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
$pdf->SetDefaultMonospacedFont('helvetica');
$pdf->SetTextColor(24, 39, 62);

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

    $pdf->SetFillColor(10, 42, 102);
    $pdf->RoundedRect(12, 12, 186, 18, 3, '1111', 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetXY(16, 16);
    $pdf->Cell(0, 7, 'TrainWeb', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9.5);
    $pdf->SetXY(16, 23);
    $pdf->Cell(0, 5, 'Billete digital minimalista y listo para embarque', 0, 1, 'L');

    $pdf->SetTextColor(24, 39, 62);
    $pdf->SetFillColor(248, 251, 255);
    $pdf->RoundedRect(12, 36, 186, 239, 4, '1111', 'F');

    $pdf->write2DBarcode($qrPayload ?: $codigo, 'QRCODE,H', 150, 46, 40, 40, $style, 'N');

    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetXY(16, 48);
    $pdf->Cell(0, 8, 'Billete #' . e((string)($idx + 1)) . '  ·  ' . e($codigo), 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 10.5);
    $rows = [
        ['Pasajero', $pasajero],
        ['Documento', $documento],
        ['Ruta', $origen . ' → ' . $destino],
        ['Fecha', $fechaViaje],
        ['Horario', $horaSalida . ' - ' . $horaLlegada],
        ['Asiento', $asiento . ' / Vagon ' . $vagon],
        ['Precio', $precio . ' EUR'],
    ];

    $y = 60;
    foreach ($rows as [$label, $value]) {
        $pdf->SetXY(18, $y);
        $pdf->SetTextColor(92, 107, 133);
        $pdf->Cell(28, 7, $label, 0, 0, 'L');
        $pdf->SetTextColor(24, 39, 62);
        $pdf->MultiCell(90, 7, $value, 0, 'L', false, 1, 50, $y, true);
        $y += 15;
    }

    $pdf->SetXY(150, 96);
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->MultiCell(40, 18, 'QR para control rápido en embarque.', 0, 'C', false, 1, 150, 96, true);
}

$pdf->Output('billetes_trainweb.pdf', 'D');
exit;
