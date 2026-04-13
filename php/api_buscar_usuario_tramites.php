<?php
header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';


$busqueda = $_GET['dni'] ?? '';
if (!$busqueda) {
    echo json_encode(['error' => 'DNI o correo no proporcionado']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    // Buscar usuario por DNI o correo
    $stmt = $pdo->prepare('SELECT nombre, dni, email, telefono, tarjeta FROM usuarios WHERE dni = :busqueda OR email = :busqueda LIMIT 1');
    $stmt->execute([':busqueda' => $busqueda]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit;
    }
    // Buscar viajes (ajusta la consulta a tu modelo real)
    $stmt2 = $pdo->prepare('SELECT ruta, fecha, estado FROM viajes WHERE dni_usuario = :dni ORDER BY fecha DESC LIMIT 5');
    $stmt2->execute([':dni' => $usuario['dni']]);
    $viajes = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['usuario' => $usuario, 'viajes' => $viajes]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error en la base de datos']);
}
