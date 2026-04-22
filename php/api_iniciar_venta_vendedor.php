<?php
// Endpoint para iniciar una venta como cliente gestionado por el vendedor
header('Content-Type: application/json');
session_start();

$id_pasajero = isset($_GET['id_pasajero']) ? (int)$_GET['id_pasajero'] : 0;

if (!$id_pasajero) {
    echo json_encode(['error' => 'ID de pasajero requerido']);
    exit;
}

// Guardar en sesión el cliente gestionado
$_SESSION['cliente_gestionado'] = $id_pasajero;

echo json_encode(['success' => true, 'redirect' => 'compra.php']);