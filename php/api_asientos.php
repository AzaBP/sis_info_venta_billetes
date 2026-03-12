<?php
header('Content-Type: application/json');
require_once 'Conexion.php';
require_once 'ConexionMongo.php';

$id_viaje = $_GET['id_viaje'] ?? null;

if (!$id_viaje) {
    echo json_encode(['error' => 'ID de viaje no proporcionado']);
    exit;
}

try {
    // 1. Obtener asientos de PostgreSQL
    $pg = new Conexion();
    $pdo = $pg->conectar();
    
    $stmt = $pdo->prepare("
        SELECT a.numero_asiento, a.clase 
        FROM ASIENTO a
        JOIN VIAJE v ON a.id_tren = v.id_tren
        WHERE v.id_viaje = ?
        ORDER BY a.numero_asiento ASC
    ");
    $stmt->execute([$id_viaje]);
    $asientosPostgres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener ocupación de MongoDB
    $mgo = new ConexionMongo();
    $dbMongo = $mgo->conectar();
    $coleccion = $dbMongo->selectCollection('billetes');
    
    // Buscamos los billetes de este viaje
    $billetesMongo = $coleccion->find(['id_viaje' => (int)$id_viaje]);
    
    $ocupados = [];
    foreach ($billetesMongo as $billete) {
        $ocupados[] = (int)$billete['numero_asiento'];
    }

    // 3. Cruzar datos
    $mapaFinal = array_map(function($asiento) use ($ocupados) {
        return [
            'numero' => (int)$asiento['numero_asiento'],
            'clase' => $asiento['clase'],
            'ocupado' => in_array((int)$asiento['numero_asiento'], $ocupados)
        ];
    }, $asientosPostgres);

    echo json_encode(['exito' => true, 'asientos' => $mapaFinal]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}