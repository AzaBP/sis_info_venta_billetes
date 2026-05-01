<?php
// ARCHIVO: php/procesar_cancelacion.php
header('Content-Type: application/json; charset=utf-8');

// Ajusta las rutas según la ubicación de tus archivos (asumiendo que estamos en /php/)
require_once __DIR__ . '/DAO/BilleteMongoDB.php';
require_once __DIR__ . '/DAO/AsientoDAO.php';

// Leer los datos que vienen por JSON desde el JS
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
$codigo_billete = $input['codigo'] ?? '';

if (empty($codigo_billete)) {
    echo json_encode(['success' => false, 'message' => 'El código proporcionado no es válido.']);
    exit;
}

try {
    // 1. Instanciar el DAO de MongoDB y buscar el billete
    $billeteDAO = new BilleteMongoDB();
    $billete = $billeteDAO->obtenerPorId($codigo_billete);

    if (!$billete) {
        echo json_encode(['success' => false, 'message' => 'No se ha encontrado ningún billete con ese código.']);
        exit;
    }

    // Comprobar si ya está cancelado para no hacer doble trabajo
    if ($billete->getEstado() === 'cancelado') {
        echo json_encode(['success' => false, 'message' => 'Este billete ya se encuentra cancelado.']);
        exit;
    }

    // 2. Modificar el estado del billete y actualizar en MongoDB
    $billete->setEstado('cancelado');
    // Si en tu clase VO/Billete.php tienes un método setFechaCancelacion, puedes descomentar la siguiente línea:
    // $billete->setFechaCancelacion(date('Y-m-d H:i:s'));
    
    $actualizadoMongo = $billeteDAO->actualizar($billete);

    if (!$actualizadoMongo) {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado del billete en MongoDB.']);
        exit;
    }

    // 3. Liberar el asiento correspondiente en PostgreSQL
    // (Asumo que tienes métodos getNumeroAsiento() en VO/Billete y setEstado() en VO/Asiento)
    $numero_asiento = $billete->getNumeroAsiento(); 
    
    $asientoDAO = new AsientoDAO();
    $asiento = $asientoDAO->obtenerPorId($numero_asiento);

    if ($asiento) {
        $asiento->setEstado('disponible');
        $asientoDAO->actualizar($asiento);
    }

    // 4. Todo ha ido bien
    echo json_encode(['success' => true, 'message' => 'Tu billete ha sido cancelado exitosamente y la plaza ha sido liberada.']);

} catch (Exception $e) {
    // Para no mostrar detalles técnicos al usuario, devolvemos un mensaje genérico.
    echo json_encode(['success' => false, 'message' => 'Error de servidor. Revisa el ID y vuelve a intentarlo.']);
}