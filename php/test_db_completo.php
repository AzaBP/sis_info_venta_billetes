<?php
require_once(__DIR__ . '/Conexion.php');

header("Content-Type: text/plain");
echo "=== INICIANDO TEST DE BASE DE DATOS (POSTGRESQL) ===\n\n";

$conexion = new Conexion();
$pdo = $conexion->conectar();

if (!$pdo) {
    echo " ERROR: No se pudo establecer la conexión física con el servidor.\n";
    exit;
}

echo " CONEXIÓN EXITOSA: El usuario y la contraseña son correctos.\n\n";

try {
    // Iniciamos una transacción para no ensuciar la base de datos permanentemente
    $pdo->beginTransaction();
    echo "Probando inserción en cascada según tu esquema...\n";

    // 1. Insertar en USUARIO
    $email_test = "test_" . time() . "@tren.com";
    $sqlUser = "INSERT INTO USUARIO (nombre, apellido, email, password, telefono, tipo_usuario) 
                VALUES ('Test', 'User', :email, '1234', '555-000', 'empleado') RETURNING id_usuario";
    $stmt = $pdo->prepare($sqlUser);
    $stmt->execute([':email' => $email_test]);
    $id_usuario = $stmt->fetchColumn();
    echo "  - Tabla USUARIO: OK (ID: $id_usuario)\n";

    // 2. Insertar en EMPLEADO (Depende de USUARIO)
    $sqlEmp = "INSERT INTO EMPLEADO (id_usuario, tipo_empleado) 
               VALUES (:id_u, 'vendedor') RETURNING id_empleado";
    $stmt = $pdo->prepare($sqlEmp);
    $stmt->execute([':id_u' => $id_usuario]);
    $id_empleado = $stmt->fetchColumn();
    echo "  - Tabla EMPLEADO: OK (ID: $id_empleado)\n";

    // 3. Insertar en VENDEDOR (Subtabla de EMPLEADO)
    $sqlVend = "INSERT INTO VENDEDOR (id_empleado, comision_porcentaje, region) 
                VALUES (:id_e, 5.0, 'Norte')";
    $stmt = $pdo->prepare($sqlVend);
    $stmt->execute([':id_e' => $id_empleado]);
    echo "  - Tabla VENDEDOR: OK\n";

    // 4. Insertar en RUTA (Depende de VENDEDOR)
    $sqlRuta = "INSERT INTO RUTA (origen, destino, duracion, id_vendedor) 
                VALUES ('Zaragoza', 'Madrid', '01:15:00', :id_v)";
    $stmt = $pdo->prepare($sqlRuta);
    $stmt->execute([':id_v' => $id_empleado]);
    echo "  - Tabla RUTA: OK\n";

    // Si llegamos aquí, todo funciona. Revertimos para dejar la BD limpia.
    $pdo->rollBack();
    echo "\n TEST FINALIZADO: Todas las tablas y relaciones de integridad funcionan correctamente.\n";

} catch (Exception $e) {
    // Si hay un error, lo mostramos y cancelamos los cambios
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "\n ERROR DE ESTRUCTURA:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "\nConsejo: Revisa que hayas ejecutado el script SQL de creación de tablas en la base de datos 'trenesDB'.\n";
}

echo "\n=== FIN DEL TEST ===\n";