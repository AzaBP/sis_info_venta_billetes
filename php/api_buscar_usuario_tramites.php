<?php
header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';

$busqueda = $_GET['dni'] ?? $_GET['email'] ?? '';
if (!$busqueda) {
    echo json_encode(['error' => 'DNI o correo no proporcionado']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    
    // Buscar usuario por correo (búsqueda parcial con ILIKE) o por DNI
    // Si contiene @, buscar como email; si no, buscar como DNI o parte del email
    $esEmail = strpos($busqueda, '@') !== false;
    
    if ($esEmail) {
        // Búsqueda exacta o parcial de email
        $stmt = $pdo->prepare('
            SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.telefono, 
                   p.id_pasajero, p.numero_documento, p.metodo_pago 
            FROM USUARIO u 
            LEFT JOIN PASAJERO p ON u.id_usuario = p.id_usuario 
            WHERE u.email ILIKE :busqueda
            LIMIT 1
        ');
        $busquedaParam = '%' . $busqueda . '%';
    } else {
        // Buscar por DNI o por nombre/apellido
        $stmt = $pdo->prepare('
            SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.telefono, 
                   p.id_pasajero, p.numero_documento, p.metodo_pago 
            FROM USUARIO u 
            LEFT JOIN PASAJERO p ON u.id_usuario = p.id_usuario 
            WHERE p.numero_documento = :busqueda 
               OR u.nombre ILIKE :busqueda 
               OR u.apellido ILIKE :busqueda
            LIMIT 1
        ');
        $busquedaParam = '%' . $busqueda . '%';
    }
    $stmt->execute([':busqueda' => $busquedaParam]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit;
    }
    
    // Guardar id_usuario para referencia
    $idUsuario = $usuario['id_usuario'];
    
    // Buscar viajes/abonos asociados a este pasajero
    $viajes = [];
    if ($usuario['id_pasajero']) {
        // Primero buscar abonos
        $stmt2 = $pdo->prepare('
            SELECT tipo, fecha_inicio, fecha_fin, viajes_totales, viajes_restantes 
            FROM ABONO 
            WHERE id_pasajero = :id_pasajero 
            ORDER BY fecha_inicio DESC 
            LIMIT 5
        ');
        $stmt2->execute([':id_pasajero' => $usuario['id_pasajero']]);
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $viajes[] = [
                'ruta' => $row['tipo'],
                'fecha' => $row['fecha_inicio'] . ' - ' . $row['fecha_fin'],
                'estado' => 'Abono: ' . $row['viajes_restantes'] . '/' . $row['viajes_totales']
            ];
        }
        
        // También buscar billetes en MongoDB
        require_once __DIR__ . '/ConexionMongo.php';
        $mongo = new ConexionMongo();
        $db = $mongo->conectar();
        if ($db) {
            $collection = $db->selectCollection('billetes');
            $billetes = $collection->find([
                'id_pasajero' => (int)$usuario['id_pasajero'],
                'estado' => 'confirmado'
            ], ['sort' => ['fecha_compra' => -1], 'limit' => 5]);
            
            foreach ($billetes as $billete) {
                // Obtener info del viaje desde PostgreSQL
                $idViaje = (int)($billete['id_viaje'] ?? 0);
                if ($idViaje > 0) {
                    $stmtViaje = $pdo->prepare('
                        SELECT r.origen, r.destino, v.fecha 
                        FROM VIAJE v 
                        JOIN RUTA r ON v.id_ruta = r.id_ruta 
                        WHERE v.id_viaje = :id_viaje
                    ');
                    $stmtViaje->execute([':id_viaje' => $idViaje]);
                    $viajeInfo = $stmtViaje->fetch(PDO::FETCH_ASSOC);
                    if ($viajeInfo) {
                        $viajes[] = [
                            'ruta' => $viajeInfo['origen'] . ' → ' . $viajeInfo['destino'],
                            'fecha' => $viajeInfo['fecha'],
                            'estado' => 'Billete: Asiento ' . ($billete['numero_asiento'] ?? '-')
                        ];
                    }
                }
            }
        }
    }
    
    echo json_encode([
        'usuario' => [
            'id_usuario' => $idUsuario,
            'id_pasajero' => $usuario['id_pasajero'] ?? null,
            'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
            'dni' => $usuario['numero_documento'] ?? '',
            'email' => $usuario['email'],
            'telefono' => $usuario['telefono'],
            'tarjeta' => $usuario['metodo_pago'] ?? '****'
        ],
        'viajes' => $viajes
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
