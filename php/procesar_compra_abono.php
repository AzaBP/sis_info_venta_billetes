<?php
session_start();
header('Content-Type: application/json');
require_once 'Conexion.php';

// Leemos el JSON que nos envía el JavaScript
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Simularemos un ID de pasajero si no hay sesión para pruebas
// En un entorno real, debes obtener esto de $_SESSION['usuario']['id_pasajero']
$id_pasajero = isset($_SESSION['usuario']['id_pasajero']) ? $_SESSION['usuario']['id_pasajero'] : 1; 

if (!$input || !isset($input['tipo'])) {
    echo json_encode(['exito' => false, 'error' => 'Datos inválidos.']);
    exit;
}

$tipo = $input['tipo'];
// Calculamos las fechas
$fecha_inicio = date('Y-m-d');
$fecha_fin = date('Y-m-d'); // Valor por defecto

// Determinamos la fecha de fin según el tipo
switch ($tipo) {
    case 'mensual':
        $fecha_fin = date('Y-m-d', strtotime('+1 month'));
        break;
    case 'trimestral':
        $fecha_fin = date('Y-m-d', strtotime('+3 months'));
        break;
    case 'anual':
        $fecha_fin = date('Y-m-d', strtotime('+1 year'));
        break;
    case 'estudiante':
    case 'viajes_limitados':
        $fecha_fin = date('Y-m-d', strtotime('+6 months')); // Por poner un ejemplo
        break;
}

try {
    $conexion = new Conexion();
    $pdo = $conexion->conectar();

    if (!$pdo) {
        throw new Exception("No se pudo conectar a la base de datos.");
    }

    // Si el abono es de viajes limitados, podrías querer calcular los viajes_totales y viajes_restantes aquí
    $sql = "INSERT INTO ABONO (id_pasajero, tipo, fecha_inicio, fecha_fin) 
            VALUES (:id_pasajero, :tipo, :fecha_inicio, :fecha_fin)";
            
    $stmt = $pdo->prepare($sql);
    $exito = $stmt->execute([
        ':id_pasajero' => $id_pasajero,
        ':tipo' => $tipo,
        ':fecha_inicio' => $fecha_inicio,
        ':fecha_fin' => $fecha_fin
    ]);

    if ($exito) {
        echo json_encode(['exito' => true, 'mensaje' => 'Abono creado correctamente']);
    } else {
        echo json_encode(['exito' => false, 'error' => 'No se pudo insertar en la base de datos.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'error' => 'Excepción del servidor: ' . $e->getMessage()]);
}
?>