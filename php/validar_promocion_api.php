<?php
session_start();
require_once __DIR__ . '/Conexion.php'; // Ajusta la ruta si es necesario

header('Content-Type: application/json; charset=utf-8');

$codigo = trim($_GET['codigo'] ?? '');

if ($codigo === '') {
    echo json_encode(['valido' => false, 'mensaje' => 'Código vacío.']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    
    // Buscar la promoción por código y verificar que la fecha actual esté dentro de la validez
    $sql = "SELECT descuento_porcentaje, usos_maximos, usos_actuales 
            FROM PROMOCION 
            WHERE codigo = :codigo 
              AND CURRENT_DATE BETWEEN fecha_inicio AND fecha_fin";
              
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':codigo' => $codigo]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($promo) {
        // Comprobar si el código tiene un límite de usos y si ya se ha superado
        if ($promo['usos_maximos'] !== null && $promo['usos_actuales'] >= $promo['usos_maximos']) {
            echo json_encode(['valido' => false, 'mensaje' => 'Este código ha agotado sus usos.']);
            exit;
        }
        
        echo json_encode([
            'valido' => true, 
            'descuento_porcentaje' => (float)$promo['descuento_porcentaje']
        ]);
    } else {
        echo json_encode(['valido' => false, 'mensaje' => 'Código inválido o caducado.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['valido' => false, 'mensaje' => 'Error en el servidor.']);
}
?>