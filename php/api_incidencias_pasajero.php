<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/ConexionMongo.php';
require_once __DIR__ . '/DAO/BilleteMongoDB.php';

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'pasajero') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('Conexion no disponible');
    }

    $stmt = $pdo->prepare('SELECT id_pasajero FROM pasajero WHERE id_usuario = :id_usuario LIMIT 1');
    $stmt->execute([':id_usuario' => (int)$usuario['id_usuario']]);
    $id_pasajero = (int)$stmt->fetchColumn();
    if ($id_pasajero <= 0) {
        echo json_encode([]);
        exit;
    }

    $billeteDAO = new BilleteMongoDB();
    $billetes = $billeteDAO->obtenerPorPasajero($id_pasajero);

    $ids = [];
    foreach ($billetes as $b) {
        $idv = (int)$b->getIdViaje();
        if ($idv > 0) {
            $ids[$idv] = true;
        }
    }

    if (count($ids) === 0) {
        echo json_encode([]);
        exit;
    }

    $idList = array_keys($ids);
    $placeholders = implode(',', array_fill(0, count($idList), '?'));

    $sql = "SELECT i.id_incidencia, i.id_viaje, i.tipo_incidencia, i.descripcion, i.estado, i.fecha_reporte, i.origen, i.resolucion,
                   r.origen AS ruta_origen, r.destino AS ruta_destino, v.fecha, v.hora_salida, v.hora_llegada
            FROM incidencia i
            JOIN viaje v ON v.id_viaje = i.id_viaje
            JOIN ruta r ON r.id_ruta = v.id_ruta
            WHERE i.afecta_pasajero = true AND i.id_viaje IN ($placeholders)
            ORDER BY v.fecha ASC, v.hora_salida ASC, i.fecha_reporte DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($idList);
    $incidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($incidencias);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([]);
}
