<?php
header('Content-Type: application/json');
require_once 'ConexionMongo.php';

$id_viaje = $_GET['id_viaje'] ?? null;
if (!$id_viaje) {
    echo json_encode(['exito' => false, 'error' => 'ID de viaje no proporcionado']);
    exit;
}

try {
    $mgo = new ConexionMongo();
    $dbMongo = $mgo->conectar();
    $coleccion = $dbMongo->selectCollection('billetes');
    $billetesMongo = $coleccion->find(['id_viaje' => (int)$id_viaje]);

    $ocupados = [];
    foreach ($billetesMongo as $billete) {
        $ocupados[] = [
            'numero_asiento' => (int)$billete['numero_asiento']
        ];
    }

    echo json_encode(['exito' => true, 'ocupados' => $ocupados]);
} catch (Exception $e) {
    echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
}
?>