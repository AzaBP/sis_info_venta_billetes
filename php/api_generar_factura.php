<?php
// Generar factura PDF de un billete y opcionalmente enviar por correo
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/ConexionMongo.php';
require_once __DIR__ . '/Conexion.php';

$usuario = $_SESSION['usuario'] ?? null;

// Verificar que sea empleado (vendedor o admin) O que venga una solicitud válida con id_pasajero
// Permitir acceso si hay un empleado en sesión O si se proporciona un id_pasajero válido
$acceso_valido = false;
if ($usuario && ($usuario['tipo_usuario'] ?? '') === 'empleado') {
    $acceso_valido = true;
} elseif (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['id_pasajero']) && $data['id_pasajero'] > 0) {
        $acceso_valido = true;
    }
}

if (!$acceso_valido) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Leer los datos del cuerpo de la petición
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

$localizador = isset($data['localizador']) ? trim($data['localizador']) : '';
$id_pasajero = isset($data['id_pasajero']) ? (int)$data['id_pasajero'] : (isset($data['id_usuario']) ? (int)$data['id_usuario'] : 0);
$enviar_correo = isset($data['enviar_correo']) ? (bool)$data['enviar_correo'] : false;

if (!$localizador || !$id_pasajero) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    $mongo = new ConexionMongo();
    $db = $mongo->conectar();
    
    if (!$db) {
        throw new RuntimeException('Conexion Mongo no disponible');
    }
    
    $collection = $db->selectCollection('billetes');
    $billete = $collection->findOne([
        'codigo_billete' => $localizador,
        'id_pasajero' => $id_pasajero
    ]);
    
    if (!$billete) {
        echo json_encode(['error' => 'Billete no encontrado']);
        exit;
    }
    
    // Obtener información del viaje
    $id_viaje = (int)($billete['id_viaje'] ?? 0);
    $viajeInfo = null;
    if ($id_viaje > 0) {
        $stmt = $pdo->prepare('
            SELECT v.id_viaje, v.fecha, v.hora_salida, v.hora_llegada, v.precio as precio_base,
                   t.modelo as tipo_tren,
                   r.origen, r.destino
            FROM VIAJE v
            JOIN TREN t ON v.id_tren = t.id_tren
            JOIN RUTA r ON v.id_ruta = r.id_ruta
            WHERE v.id_viaje = :id_viaje
        ');
        $stmt->execute([':id_viaje' => $id_viaje]);
        $viajeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Datos del billete
    $codigo = (string)($billete['codigo_billete'] ?? '');
    $pasajero = trim((string)($billete['pasajero_nombre'] ?? '') . ' ' . (string)($billete['pasajero_apellidos'] ?? ''));
    $documento = (string)($billete['pasajero_documento'] ?? '');
    $email = (string)($billete['pasajero_email'] ?? '');
    $asiento = isset($billete['numero_asiento']) ? (int)$billete['numero_asiento'] : '';
    $precio = (float)($billete['precio_final'] ?? $billete['precio_pagado'] ?? 0);
    $descuento = (float)($billete['descuento'] ?? 0);
    // Manejar fecha_compra que puede ser objeto MongoDB Date o string
$fecha_compra = '';
if (isset($billete['fecha_compra'])) {
    if (is_object($billete['fecha_compra']) && method_exists($billete['fecha_compra'], 'toDateTime')) {
        $fecha_compra = $billete['fecha_compra']->toDateTime()->format('d/m/Y H:i');
    } else {
        $fecha_compra = (string)$billete['fecha_compra'];
    }
}
    
    // Datos de facturación
    $factura = isset($billete['factura']) ? $billete['factura'] : [];
    $facturaNombre = $factura['nombre'] ?? $pasajero;
    $facturaNif = $factura['nif'] ?? $documento;
    $facturaDireccion = $factura['direccion'] ?? '';
    $facturaEmail = $factura['email'] ?? $email;
    
    // Generar PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('TrainWeb');
    $pdf->SetAuthor('TrainWeb');
    $pdf->SetTitle('Factura ' . $codigo);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    
    // Encabezado
    $pdf->SetFillColor(10, 42, 102);
    $pdf->Rect(15, 15, 180, 25, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetXY(20, 20);
    $pdf->Cell(0, 8, 'FACTURA', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetXY(20, 28);
    $pdf->Cell(0, 5, 'TrainWeb - Servicios Ferroviarios', 0, 1, 'L');
    
    // Datos de la factura
    $pdf->SetTextColor(10, 42, 102);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetXY(15, 50);
    $pdf->Cell(0, 6, 'Datos de Facturación', 0, 1, 'L');
    
    $pdf->SetDrawColor(216, 224, 239);
    $pdf->SetFillColor(248, 251, 255);
    $pdf->RoundedRect(15, 57, 180, 40, 3, '1111', 'F');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(60, 60, 60);
    $y = 62;
    $pdf->SetXY(20, $y);
    $pdf->Cell(40, 5, 'Nombre/Razón Social:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, $facturaNombre, 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(20, $y + 7);
    $pdf->Cell(40, 5, 'NIF/CIF:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, $facturaNif, 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(20, $y + 14);
    $pdf->Cell(40, 5, 'Dirección:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, $facturaDireccion, 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(20, $y + 21);
    $pdf->Cell(40, 5, 'Email:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, $facturaEmail, 0, 1, 'L');
    
    // Datos del billete
    $pdf->SetTextColor(10, 42, 102);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetXY(15, 105);
    $pdf->Cell(0, 6, 'Datos del Billete', 0, 1, 'L');
    
    $pdf->SetFillColor(248, 251, 255);
    $pdf->RoundedRect(15, 112, 180, 55, 3, '1111', 'F');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(60, 60, 60);
    $y = 117;
    
    $pdf->SetXY(20, $y);
    $pdf->Cell(40, 5, 'Localizador:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, $codigo, 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(20, $y + 7);
    $pdf->Cell(40, 5, 'Fecha de compra:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, $fecha_compra, 0, 1, 'L');
    
    if ($viajeInfo) {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $y + 14);
        $pdf->Cell(40, 5, 'Ruta:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, $viajeInfo['origen'] . ' → ' . $viajeInfo['destino'], 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $y + 21);
        $pdf->Cell(40, 5, 'Fecha y hora:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, $viajeInfo['fecha'] . ' - ' . $viajeInfo['hora_salida'] . ' a ' . $viajeInfo['hora_llegada'], 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(20, $y + 28);
        $pdf->Cell(40, 5, 'Tren:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, $viajeInfo['tipo_tren'], 0, 1, 'L');
    }
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(20, $y + 35);
    $pdf->Cell(40, 5, 'Asiento:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, $asiento, 0, 1, 'L');
    
    // Totales
    $pdf->SetFillColor(240, 240, 240);
    $pdf->RoundedRect(15, 175, 180, 35, 3, '1111', 'F');
    
    $pdf->SetTextColor(10, 42, 102);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(20, 180);
    $pdf->Cell(80, 5, 'Precio base:', 0, 0, 'L');
    $pdf->Cell(0, 5, number_format($precio / (1 - $descuento/100), 2, ',', '.') . ' EUR', 0, 1, 'R');
    
    if ($descuento > 0) {
        $pdf->SetXY(20, 187);
        $pdf->Cell(80, 5, 'Descuento (' . $descuento . '%):', 0, 0, 'L');
        $pdf->Cell(0, 5, '-' . number_format($precio * $descuento / (100 - $descuento), 2, ',', '.') . ' EUR', 0, 1, 'R');
    }
    
    $pdf->SetDrawColor(10, 42, 102);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(20, 195, 195, 195);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetXY(20, 198);
    $pdf->Cell(80, 6, 'TOTAL:', 0, 0, 'L');
    $pdf->Cell(0, 6, number_format($precio, 2, ',', '.') . ' EUR', 0, 1, 'R');
    
    // QR con datos de la factura
    $qrPayload = implode("\n", [
        'TrainWeb Factura',
        'Codigo: ' . $codigo,
        'Cliente: ' . $facturaNombre,
        'NIF: ' . $facturaNif,
        'Importe: ' . number_format($precio, 2, ',', '.') . ' EUR',
        'Fecha: ' . $fecha_compra,
    ]);
    
    $pdf->SetFillColor(248, 251, 255);
    $pdf->RoundedRect(15, 220, 180, 45, 3, '1111', 'F');
    
    $style = [
        'border' => 0,
        'vpadding' => 'auto',
        'hpadding' => 'auto',
        'fgcolor' => [10, 42, 102],
        'bgcolor' => false,
        'module_width' => 1,
        'module_height' => 1,
    ];
    
    $pdf->write2DBarcode($qrPayload ?: $codigo, 'QRCODE,H', 155, 225, 35, 35, $style, 'N');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(20, 230);
    $pdf->Cell(0, 5, 'Código QR de verificación', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY(20, 238);
    $pdf->Cell(0, 4, 'Escanee para verificar la autenticidad de esta factura', 0, 1, 'L');
    
    // Generar el PDF como string
    $pdfContent = $pdf->Output('factura_' . $codigo . '.pdf', 'S');
    
    // Si se solicita enviar por correo
    if ($enviar_correo && $facturaEmail) {
        // Usar PHPMailer para enviar el correo
        // Aquí asumimos que hay una configuración de correo
        // Por simplicidad, guardamos el PDF en un archivo temporal y simulamos el envío
        // En un entorno real, usarías PHPMailer o similar
        
        $archivoTemporal = sys_get_temp_dir() . '/factura_' . $codigo . '.pdf';
        file_put_contents($archivoTemporal, $pdfContent);
        
        // Aquí iría el código de envío de correo con PHPMailer
        // Por ahora, simplemente confirmamos que se generó
        $mensajeCorreo = 'Factura generada y lista para enviar a: ' . $facturaEmail;
    }
    
    // Devolver el PDF codificado en base64
    echo json_encode([
        'ok' => true,
        'pdf_base64' => base64_encode($pdfContent),
        'nombre_archivo' => 'factura_' . $codigo . '.pdf',
        'mensaje' => $enviar_correo ? 'Factura generada. Envío de correo pendiente de configurar.' : 'Factura generada correctamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al generar la factura: ' . $e->getMessage()]);
}