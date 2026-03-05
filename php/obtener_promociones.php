<?php
// Configuramos la cabecera para que devuelva JSON
header('Content-Type: application/json');

// Incluimos tu archivo de conexión
require_once 'Conexion.php';

try {
    // IMPORTANTE: Asegúrate de que la variable coincida con la que usas en Conexion.php
    // Por ejemplo, si tu archivo crea una variable $conexion, usa esa en lugar de $pdo.
    // Si usas una clase, podría ser algo como: $pdo = (new Conexion())->conectar();
    
    // Consulta SQL para obtener promociones válidas (no caducadas y con usos disponibles)
    $sql = "SELECT codigo, descuento_porcentaje, fecha_fin 
            FROM PROMOCION 
            WHERE fecha_fin >= CURRENT_DATE 
            AND (usos_maximos IS NULL OR usos_actuales < usos_maximos)";
            
    // Ejecutamos la consulta
    $stmt = $pdo->query($sql);
    
    // Obtenemos todos los resultados como un array asociativo
    $promociones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Devolvemos los datos en formato JSON
    echo json_encode($promociones);

} catch (PDOException $e) {
    // Si hay un error, devolvemos un mensaje de error en JSON
    http_response_code(500);
    echo json_encode(["error" => "Error de base de datos: " . $e->getMessage()]);
}
?>