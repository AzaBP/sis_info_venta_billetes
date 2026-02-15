<?php

// Autoloader de Composer
require_once(__DIR__ . '/../vendor/autoload.php');

require_once(__DIR__ . '/VO/Billete.php');
require_once(__DIR__ . '/DAO/BilleteMongoDB.php');

echo "=== TEST MONGODB - INSERTAR BILLETE ===\n\n";

// Verificar que el driver está disponible
if (!extension_loaded('mongodb')) {
    echo "✗ Error: MongoDB driver no cargado\n";
    echo "extension_loaded('mongodb'): " . var_export(extension_loaded('mongodb'), true) . "\n";
    echo "class_exists('MongoDB\\Client'): " . var_export(class_exists('MongoDB\Client'), true) . "\n";
    exit;
}

echo "✓ MongoDB driver cargado\n\n";

try {
    // Crear billete
    $now = date('Y-m-d H:i:s');
    $rand = rand(1000, 9999);
    
    $billete = new Billete(
        null,                          // id_billete (MongoDB lo genera)
        1,                             // id_pasajero
        1,                             // id_ruta
        1,                             // id_tren
        1,                             // id_asiento
        '2026-03-15 14:30:00',        // fecha_viaje
        100.50,                        // precio_pagado
        'tarjeta',                     // metodo_pago
        'BILL_' . $now . '_' . $rand,  // codigo_billete
        'confirmado',                  // estado
        $now                           // fecha_compra
    );
    
    echo "Insertando billete:\n";
    echo "  - Código: " . $billete->getCodigoBillete() . "\n";
    echo "  - Pasajero: " . $billete->getIdPasajero() . "\n";
    echo "  - Precio: $" . $billete->getPrecioPagado() . "\n\n";
    
    // Insertar
    $billeteDAO = new BilleteMongoDB();
    $id_insertado = $billeteDAO->insertar($billete);
    
    if ($id_insertado) {
        echo "✓ Billete insertado correctamente\n";
        echo "  - ID MongoDB: " . $id_insertado . "\n\n";
        
        // Obtener el billete
        echo "Obteniendo billete...\n";
        $billete_obtenido = $billeteDAO->obtenerPorId($id_insertado);
        
        if ($billete_obtenido) {
            echo "✓ Billete obtenido:\n";
            echo "  - Código: " . $billete_obtenido->getCodigoBillete() . "\n";
            echo "  - Estado: " . $billete_obtenido->getEstado() . "\n";
            echo "  - Fecha viaje: " . $billete_obtenido->getFechaViaje() . "\n";
            echo "  - Precio pagado: $" . $billete_obtenido->getPrecioPagado() . "\n";
        } else {
            echo "✗ Error al obtener billete\n";
        }
    } else {
        echo "✗ Error al insertar billete\n";
    }
    
} catch (Exception $e) {
    echo "✗ Excepción: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

?>
