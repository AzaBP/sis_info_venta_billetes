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
    
    // Primero, sacamos el id_pasajero de este usuario
    $stmtPasajero = $pdo->prepare("SELECT id_pasajero FROM PASAJERO WHERE id_usuario = :id_usuario");
    $stmtPasajero->execute([':id_usuario' => $id_usuario]);
    $pasajero = $stmtPasajero->fetch(PDO::FETCH_ASSOC);

    if (!$pasajero) {
        echo json_encode([]); // No es un pasajero válido
        exit;
    }

    // Ahora buscamos sus abonos reales ordenados por fecha de fin (los activos primero)
    $sql = "SELECT tipo, fecha_inicio, fecha_fin, viajes_totales, viajes_restantes 
            FROM ABONO 
            WHERE id_pasajero = :id_pasajero 
            ORDER BY fecha_fin DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_pasajero' => $pasajero['id_pasajero']]);
    
    $abonos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Devolvemos los datos en JSON para que el JS genere las tarjetas
    echo json_encode($abonos);

} catch (PDOException $e) {
    // Si hay error (ej: la tabla no existe aún), devolvemos array vacío para no romper la web
    echo json_encode([]);
}
?>