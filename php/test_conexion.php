<?php

// Test de conexión y operaciones CRUD

require_once(__DIR__ . '/Conexion.php');
require_once(__DIR__ . '/ConexionMongo.php');
require_once(__DIR__ . '/VO/Usuario.php');
require_once(__DIR__ . '/VO/Pasajero.php');
require_once(__DIR__ . '/VO/Tren.php');
require_once(__DIR__ . '/VO/Asiento.php');
require_once(__DIR__ . '/VO/Billete.php');
require_once(__DIR__ . '/DAO/UsuarioDAO.php');
require_once(__DIR__ . '/DAO/PasajeroDAO.php');
require_once(__DIR__ . '/DAO/TrenDAO.php');
require_once(__DIR__ . '/DAO/AsientoDAO.php');
require_once(__DIR__ . '/DAO/BilleteMongoDB.php');

echo "=== TEST DE CONEXIÓN Y CRUD ===\n\n";

// TEST 1: POSTGRESQL - USUARIO
echo "TEST 1: Insertando Usuario en PostgreSQL...\n";
$usuarioDAO = new UsuarioDAO();
$usuario = new Usuario(
    null,
    'Juan Pérez',
    'juan@example.com',
    password_hash('password123', PASSWORD_BCRYPT),
    '612345678',
    'tarjeta_credito'
);

if ($usuarioDAO->insertar($usuario)) {
    echo "✓ Usuario insertado correctamente\n\n";
} else {
    echo "✗ Error al insertar usuario\n\n";
}

// TEST 2: POSTGRESQL - TREN
echo "TEST 2: Insertando Tren en PostgreSQL...\n";
$trenDAO = new TrenDAO();
$tren = new Tren(
    null,
    'AVE Serie 100',
    250
);

if ($trenDAO->insertar($tren)) {
    echo "✓ Tren insertado correctamente\n\n";
} else {
    echo "✗ Error al insertar tren\n\n";
}

// TEST 3: POSTGRESQL - ASIENTO
echo "TEST 3: Insertando Asiento en PostgreSQL...\n";
$asientoDAO = new AsientoDAO();
$asiento = new Asiento(
    1,
    1,
    'primera',
    'disponible'
);

if ($asientoDAO->insertar($asiento)) {
    echo "✓ Asiento insertado correctamente\n\n";
} else {
    echo "✗ Error al insertar asiento\n\n";
}

// TEST 4: POSTGRESQL - OBTENER DATOS
echo "TEST 4: Obteniendo datos de PostgreSQL...\n";
$usuarioObtenido = $usuarioDAO->obtenerPorId(1);
if ($usuarioObtenido) {
    echo "✓ Usuario obtenido: " . $usuarioObtenido->getNombre() . "\n";
    echo "  - Email: " . $usuarioObtenido->getEmail() . "\n";
    echo "  - Teléfono: " . $usuarioObtenido->getTelefono() . "\n\n";
} else {
    echo "✗ No se encontró el usuario\n\n";
}

// TEST 5: POSTGRESQL - LISTAR TODOS
echo "TEST 5: Listando todos los usuarios...\n";
$usuarios = $usuarioDAO->obtenerTodos();
echo "✓ Total de usuarios: " . count($usuarios) . "\n";
foreach ($usuarios as $user) {
    echo "  - " . $user->getNombre() . " (" . $user->getEmail() . ")\n";
}
echo "\n";

// TEST 6: MONGODB - BILLETE
echo "TEST 6: Insertando Billete en MongoDB...\n";
try {
    $billeteDAO = new BilleteMongoDB();
    $billete = new Billete(
        null,
        1,
        1,
        1,
        1,
        '2026-02-15',
        89.50,
        'tarjeta_credito',
        'BLL-20260212-001',
        'confirmado',
        '2026-02-12'
    );

    $idBillete = $billeteDAO->insertar($billete);
    if ($idBillete) {
        echo "✓ Billete insertado correctamente en MongoDB\n";
        echo "  - ID: " . $idBillete . "\n";
        echo "  - Código: " . $billete->getCodigoBillete() . "\n";
        echo "  - Precio: €" . $billete->getPrecioPagado() . "\n\n";
    } else {
        echo "✗ Error al insertar billete\n\n";
    }
} catch (Exception $e) {
    echo "✗ Error con MongoDB: " . $e->getMessage() . "\n\n";
}

// TEST 7: MONGODB - OBTENER BILLETE
echo "TEST 7: Obteniendo billete de MongoDB...\n";
try {
    $billeteObtenido = $billeteDAO->obtenerPorPasajero(1);
    if (count($billeteObtenido) > 0) {
        echo "✓ Billete encontrado:\n";
        foreach ($billeteObtenido as $bill) {
            echo "  - Código: " . $bill->getCodigoBillete() . "\n";
            echo "  - Estado: " . $bill->getEstado() . "\n";
            echo "  - Fecha viaje: " . $bill->getFechaViaje() . "\n";
        }
        echo "\n";
    } else {
        echo "✗ No se encontraron billetes\n\n";
    }
} catch (Exception $e) {
    echo "✗ Error al obtener billete: " . $e->getMessage() . "\n\n";
}

?>
