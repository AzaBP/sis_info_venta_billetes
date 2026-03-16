<?php
session_start();
header('Content-Type: application/json');
require_once 'Conexion.php';

// Verificamos que el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    echo json_encode([]);
    exit;
}

$id_usuario = $_SESSION['usuario']['id_usuario'];

try {
    $pdo = (new Conexion())->conectar();
    
    // 1. Sacamos el id_pasajero de este usuario
    $stmtPasajero = $pdo->prepare("SELECT id_pasajero FROM PASAJERO WHERE id_usuario = :id_usuario");
    $stmtPasajero->execute([':id_usuario' => $id_usuario]);
    $pasajero = $stmtPasajero->fetch(PDO::FETCH_ASSOC);

    if (!$pasajero) {
        echo json_encode([]); // No es un pasajero válido
        exit;
    }

    // 2. AHORA SÍ: Pedimos los abonos (OJO: Sin la columna 'estado' que daba error)
    $sql = "SELECT id_abono, tipo, fecha_inicio, fecha_fin, viajes_totales, viajes_restantes 
            FROM ABONO 
            WHERE id_pasajero = :id_pasajero 
            ORDER BY fecha_fin DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_pasajero' => $pasajero['id_pasajero']]);
    
    $abonos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Devolvemos los abonos en formato JSON
    echo json_encode($abonos);

} catch (PDOException $e) {
    // Si hay un error SQL, lo devolvemos para poder verlo en la consola
    http_response_code(500);
    echo json_encode(["error_sql" => $e->getMessage()]);
}
?>