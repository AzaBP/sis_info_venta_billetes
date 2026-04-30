<?php
// Compra billete para el cliente gestionado por el vendedor
header('Content-Type: application/json');
session_start();
require_once 'Conexion.php';
require_once __DIR__ . '/Utils/Mailer.php';
require_once __DIR__ . '/Utils/BilletePdf.php';
$pdo = (new Conexion())->conectar();

$data = json_decode(file_get_contents('php://input'), true);

// NUEVO: Intentamos leer el ID que manda Javascript. Si no está, por seguridad buscamos en la sesión.
$id_pasajero = $data['id_usuario'] ?? $_SESSION['cliente_gestionado'] ?? null;

// Recoger descuento
$id_viaje = $data['id_viaje'] ?? 0;
$numero_asiento = $data['numero_asiento'] ?? 0;

// Datos de facturación
$descuento = $data['descuento'] ?? 0;
$facturaNombre = $data['facturaNombre'] ?? '';
$facturaNif = $data['facturaNif'] ?? '';
$facturaDireccion = $data['facturaDireccion'] ?? '';
$facturaEmail = $data['facturaEmail'] ?? '';
if (!$id_pasajero || !$id_viaje || !$numero_asiento) {
    echo json_encode(['error'=>'Faltan datos para la compra']);
    exit;
}

// Comprobar asiento disponible
$stmt = $pdo->prepare('SELECT v.id_tren FROM VIAJE v WHERE v.id_viaje = :id_viaje');
$stmt->execute([':id_viaje'=>$id_viaje]);
$viaje = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$viaje) {
    echo json_encode(['error'=>'Viaje no encontrado']);
    exit;
}
$stmt = $pdo->prepare('SELECT estado FROM ASIENTO WHERE numero_asiento = :numero_asiento AND id_tren = :id_tren');
$stmt->execute([':numero_asiento'=>$numero_asiento, ':id_tren'=>$viaje['id_tren']]);

$asiento = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$asiento || $asiento['estado'] !== 'disponible') {
    echo json_encode(['error'=>'Asiento no disponible']);
    exit;
}
// Marcar asiento ocupado
$stmt = $pdo->prepare('UPDATE ASIENTO SET estado = \'ocupado\' WHERE numero_asiento = :numero_asiento AND id_tren = :id_tren');
$stmt->execute([':numero_asiento'=>$numero_asiento, ':id_tren'=>$viaje['id_tren']]);

// Comprobar que el viaje existe y obtener sus datos completos para el PDF
$stmt = $pdo->prepare('
    SELECT v.id_tren, v.precio, v.fecha, v.hora_salida, v.hora_llegada, r.origen, r.destino, t.modelo AS tipo_tren
    FROM VIAJE v
    JOIN RUTA r ON v.id_ruta = r.id_ruta
    JOIN TREN t ON t.id_tren = v.id_tren
    WHERE v.id_viaje = :id_viaje
');
$stmt->execute([':id_viaje' => $id_viaje]);
$fila = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fila) {
    echo json_encode(['error' => 'Viaje no encontrado']);
    exit;
}

// Guardamos el precio de forma segura
$precio_final = $fila['precio'];

// Generar código de billete (localizador)
function generarCodigoBillete(): string {
    return 'TW-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

// Insertar billete en mongo
try {
    // Si tienes un archivo de conexión a Mongo (ej: ConexionMongo.php), requiérelo aquí.
    // Si usas la librería estándar de Composer, asegúrate de tener el autoload:
    require_once __DIR__ . '/../vendor/autoload.php'; 
    
    // Conexión a MongoDB (ajusta la URI 'mongodb://localhost:27017' si usas Docker u otra ruta)
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017"); 
    
    // Seleccionamos base de datos y colección
    $coleccionBilletes = $mongoClient->gestion_ferroviaria->billetes;
    
    // Generar el código del billete
    $codigoBillete = generarCodigoBillete();
    
    // Preparamos el documento a insertar
    $documentoBillete = [
        'codigo_billete' => $codigoBillete,
        'id_pasajero' => (int)$id_pasajero,
        'id_viaje' => (int)$id_viaje,
        'numero_asiento' => (int)$numero_asiento,
        'fecha_compra' => new MongoDB\BSON\UTCDateTime(), // Guarda la fecha actual en formato Mongo
        'descuento' => (float)$descuento,
        'precio_final' => (float)$precio_final,
        'estado' => 'confirmado',
        'factura' => [
            'nombre' => $facturaNombre,
            'nif' => $facturaNif,
            'direccion' => $facturaDireccion,
            'email' => $facturaEmail
        ]
    ];
    
    // Insertamos el documento
    $resultado = $coleccionBilletes->insertOne($documentoBillete);
    
    // Enviar el billete PDF al correo de facturación si existe
    $mailEnviado = false;
    $correoDestino = trim((string)$facturaEmail);

    if ($correoDestino !== '') {
        try {
            $mailer = new Mailer();
            $pdfPayload = [
                'codigo_billete' => $codigoBillete,
                'pasajero_nombre' => trim((string)$facturaNombre) !== '' ? trim((string)$facturaNombre) : 'Cliente',
                'pasajero_apellidos' => '',
                'pasajero_documento' => (string)$facturaNif,
                'pasajero_email' => $correoDestino,
                'origen' => (string)($fila['origen'] ?? ''),
                'destino' => (string)($fila['destino'] ?? ''),
                'fecha_viaje' => (string)($fila['fecha'] ?? ''),
                'hora_salida' => (string)($fila['hora_salida'] ?? ''),
                'hora_llegada' => (string)($fila['hora_llegada'] ?? ''),
                'numero_asiento' => (int)$numero_asiento,
                'vagon' => null,
                'precio_pagado' => (float)$precio_final,
                'tipo_tren' => (string)($fila['tipo_tren'] ?? 'Tren'),
            ];

            $attachment = [[
                'filename' => BilletePdf::generarNombreArchivo($pdfPayload),
                'mime' => 'application/pdf',
                'content' => BilletePdf::generarContenido($pdfPayload),
            ]];

            $subject = 'Tu billete PDF de TrainWeb';
            $body = '<p>Hola ' . htmlspecialchars((string)$facturaNombre, ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Adjuntamos el PDF de tu billete para esta compra.</p>'
                . '<p>Guárdalo para presentarlo en embarque.</p>';

            $mailEnviado = $mailer->send($correoDestino, (string)$facturaNombre, $subject, $body, '', $attachment);

            if (!$mailEnviado) {
                error_log('[MAIL ERROR] No se pudo enviar el PDF de la compra por vendedor a ' . $correoDestino . ' (billete ' . $codigoBillete . ')');
            }
        } catch (Throwable $mailError) {
            error_log('[MAIL ERROR] Fallo al generar/enviar el PDF de la compra por vendedor: ' . $mailError->getMessage());
        }
    }

    // Devolvemos el éxito al Javascript (incluyendo el ID generado por Mongo si quieres)
    echo json_encode([
        'ok' => true, 
        'precio_final' => $precio_final,
        'id_billete_mongo' => (string)$resultado->getInsertedId(),
        'codigo_billete' => $codigoBillete,
        'mail_enviado' => $mailEnviado
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error al guardar billete en MongoDB: ' . $e->getMessage()]);
}
