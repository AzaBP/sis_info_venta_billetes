<?php

require_once __DIR__ . '/../../vendor/autoload.php';

class BilletePdf {
    public static function generarContenido(array $billete): string {
        $pasajero = trim((string)($billete['pasajero_nombre'] ?? '') . ' ' . (string)($billete['pasajero_apellidos'] ?? ''));
        $codigo = (string)($billete['codigo_billete'] ?? '');
        $origen = (string)($billete['origen'] ?? '');
        $destino = (string)($billete['destino'] ?? '');
        $fechaViaje = (string)($billete['fecha_viaje'] ?? '');
        $horaSalida = (string)($billete['hora_salida'] ?? '');
        $horaLlegada = (string)($billete['hora_llegada'] ?? '');
        $asiento = (string)($billete['numero_asiento'] ?? '');
        $vagon = (string)($billete['vagon'] ?? '');
        $precio = number_format((float)($billete['precio_pagado'] ?? 0), 2, ',', '.');
        $documentoIdentidad = (string)($billete['pasajero_documento'] ?? '');
        $tipoTren = (string)($billete['tipo_tren'] ?? 'Tren');

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
        $pdf->SetTextColor(24, 39, 62);
        $pdf->AddPage();

        $pdf->SetFillColor(10, 42, 102);
        $pdf->RoundedRect(12, 12, 186, 20, 3, '1111', 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetXY(16, 16);
        $pdf->Cell(0, 7, 'TrainWeb', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9.5);
        $pdf->SetXY(16, 23);
        $pdf->Cell(0, 5, 'Billete digital minimalista y listo para embarque', 0, 1, 'L');

        $pdf->SetTextColor(24, 39, 62);
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
        $pdf->RoundedRect(12, 38, 186, 239, 4, '1111', 'F');
        $pdf->write2DBarcode($qrPayload ?: $codigo, 'QRCODE,H', 150, 46, 40, 40, $style, 'N');

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetXY(16, 48);
        $pdf->Cell(0, 8, 'Billete · ' . $codigo, 0, 1, 'L');

        $infoRows = [
            ['Pasajero', $pasajero],
            ['Documento', $documentoIdentidad],
            ['Ruta', $origen . ' → ' . $destino],
            ['Fecha', $fechaViaje],
            ['Horario', $horaSalida . ' - ' . $horaLlegada],
            ['Tipo de tren', $tipoTren],
            ['Asiento', $asiento . ' / Vagon ' . $vagon],
            ['Precio', $precio . ' EUR'],
        ];

        $y = 62;
        $pdf->SetFont('helvetica', '', 10.5);
        foreach ($infoRows as [$label, $value]) {
            $pdf->SetXY(18, $y);
            $pdf->SetTextColor(92, 107, 133);
            $pdf->Cell(28, 7, $label . ':', 0, 0, 'L');
            $pdf->SetTextColor(18, 33, 61);
            $pdf->MultiCell(88, 7, $value, 0, 'L', false, 1, 48, $y, true, 0, false, true, 7, 'M');
            $y += 15;
        }

        $pdf->SetTextColor(92, 107, 133);
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetXY(150, 96);
        $pdf->MultiCell(40, 18, 'Escanea el QR para control rápido en embarque.', 0, 'C', false, 1, 150, 96, true);

        return $pdf->Output('billete_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $codigo) . '.pdf', 'S');
    }

    public static function generarNombreArchivo(array $billete): string {
        $codigo = (string)($billete['codigo_billete'] ?? 'billete');
        return 'billete_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $codigo) . '.pdf';
    }
}