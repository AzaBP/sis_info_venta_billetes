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
    // Buscar usuario por correo
    $stmt = $pdo->prepare('SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.telefono, p.id_pasajero, p.numero_documento, p.metodo_pago FROM USUARIO u LEFT JOIN PASAJERO p ON u.id_usuario = p.id_usuario WHERE u.email = :busqueda OR p.numero_documento = :busqueda LIMIT 1');
    $stmt->execute([':busqueda' => $busqueda]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit;
    }
    // Buscar viajes asociados a este pasajero (por id_pasajero)
    // NOTA: No hay tabla de viajes de pasajero, pero sí de abonos y abonos tienen id_pasajero
    // Si tienes una tabla de billetes o reservas, aquí deberías consultarla
    // Por ahora, devolvemos abonos como "viajes"
    $viajes = [];
    if ($usuario['id_pasajero']) {
        $stmt2 = $pdo->prepare('SELECT tipo, fecha_inicio, fecha_fin, viajes_totales, viajes_restantes FROM ABONO WHERE id_pasajero = :id_pasajero ORDER BY fecha_inicio DESC LIMIT 5');
        $stmt2->execute([':id_pasajero' => $usuario['id_pasajero']]);
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $viajes[] = [
                'ruta' => $row['tipo'],
                'fecha' => $row['fecha_inicio'] . ' - ' . $row['fecha_fin'],
                'estado' => 'Abono: ' . $row['viajes_restantes'] . '/' . $row['viajes_totales']
            ];
        }
    }
    echo json_encode([
        'usuario' => [
            'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
            'dni' => $usuario['numero_documento'] ?? '',
            'email' => $usuario['email'],
            'telefono' => $usuario['telefono'],
            'tarjeta' => $usuario['metodo_pago'] ?? '****'
        ],
        'viajes' => $viajes
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error en la base de datos']);
}
