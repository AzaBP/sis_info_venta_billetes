<?php

// Test de inserción de datos de ejemplo en todas las entidades PostgreSQL
// y de un documento en MongoDB (billete) usando un viaje y pasajero creados.

require_once __DIR__ . '/../vendor/autoload.php';
require_once(__DIR__ . '/Conexion.php');
require_once(__DIR__ . '/ConexionMongo.php');
require_once(__DIR__ . '/VO/Billete.php');
require_once(__DIR__ . '/DAO/BilleteMongoDB.php');

echo "=== TEST DE POBLADO COMPLETO ===\n\n";

$conexion = new Conexion();
$pdo = $conexion->conectar();
if (!$pdo) {
    echo "Error: no se pudo conectar a PostgreSQL.\n";
    exit(1);
}

try {
    $now = date('Ymd_His');

    // 1) USUARIO (pasajero)
    $sql = "INSERT INTO usuario (nombre, apellido, email, password, telefono, tipo_usuario) 
            VALUES (:nombre, :apellido, :email, :password, :telefono, :tipo_usuario) RETURNING id_usuario";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => 'Ana',
        ':apellido' => 'García',
        ':email' => 'ana.' . $now . '@example.com',
        ':password' => password_hash('pass1234', PASSWORD_BCRYPT),
        ':telefono' => '600' . rand(100000,999999),
        ':tipo_usuario' => 'pasajero'
    ]);
    $id_usuario_pasajero = $stmt->fetchColumn();
    echo "Usuario pasajero creado: id_usuario={$id_usuario_pasajero}\n";

    // 2) PASAJERO (usar id_pasajero = id_usuario para consistencia)
    $sql = "INSERT INTO pasajero (id_pasajero, id_usuario, fecha_nacimiento, genero, tipo_documento, calle, ciudad, codigo_postal, pais, metodo_pago, acepta_terminos, acepta_privacidad, newsletter)
            VALUES (:id_pasajero, :id_usuario, :fecha_nacimiento, :genero, :tipo_documento, :calle, :ciudad, :codigo_postal, :pais, :metodo_pago, :acepta_terminos, :acepta_privacidad, :newsletter)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id_pasajero', $id_usuario_pasajero, PDO::PARAM_INT);
    $stmt->bindValue(':id_usuario', $id_usuario_pasajero, PDO::PARAM_INT);
    $stmt->bindValue(':fecha_nacimiento', '1990-05-12');
    $stmt->bindValue(':genero', 'femenino');
    $stmt->bindValue(':tipo_documento', 'dni');
    $stmt->bindValue(':calle', 'Calle Falsa 123');
    $stmt->bindValue(':ciudad', 'Ciudad');
    $stmt->bindValue(':codigo_postal', '28000');
    $stmt->bindValue(':pais', 'España');
    $stmt->bindValue(':metodo_pago', 'tarjeta_credito');
    $stmt->bindValue(':acepta_terminos', true, PDO::PARAM_BOOL);
    $stmt->bindValue(':acepta_privacidad', true, PDO::PARAM_BOOL);
    $stmt->bindValue(':newsletter', false, PDO::PARAM_BOOL);
    $stmt->execute();
    echo "Pasajero creado: id_pasajero={$id_usuario_pasajero}\n";

    // 3) USUARIOS Y EMPLEADOS (vendedor, maquinista, mantenimiento)
    $empleados = [];
    $tipos = ['vendedor', 'maquinista', 'mantenimiento'];
    foreach ($tipos as $tipo) {
        $stmt = $pdo->prepare($sql = "INSERT INTO usuario (nombre, apellido, email, password, telefono, tipo_usuario) VALUES (:nombre, :apellido, :email, :password, :telefono, :tipo_usuario) RETURNING id_usuario");
        $stmt->execute([
            ':nombre' => ucfirst($tipo) . 'Nombre',
            ':apellido' => ucfirst($tipo) . 'Apellido',
            ':email' => $tipo . '.' . $now . '@example.com',
            ':password' => password_hash('empleado123', PASSWORD_BCRYPT),
            ':telefono' => '699' . rand(100000,999999),
            ':tipo_usuario' => 'empleado'
        ]);
        $id_usuario_emp = $stmt->fetchColumn();

        // insertar en empleado
        $stmt2 = $pdo->prepare("INSERT INTO empleado (id_usuario, tipo_empleado) VALUES (:id_usuario, :tipo_empleado) RETURNING id_empleado");
        $stmt2->execute([':id_usuario' => $id_usuario_emp, ':tipo_empleado' => $tipo]);
        $id_empleado = $stmt2->fetchColumn();

        // subtabla
        if ($tipo === 'vendedor') {
            $stmt3 = $pdo->prepare("INSERT INTO vendedor (id_empleado, comision_porcentaje, region) VALUES (:id_empleado, :comision, :region)");
            $stmt3->execute([':id_empleado' => $id_empleado, ':comision' => 5.5, ':region' => 'Norte']);
        } elseif ($tipo === 'maquinista') {
            $stmt3 = $pdo->prepare("INSERT INTO maquinista (id_empleado, licencia, experiencia_años, horario_preferido) VALUES (:id_empleado, :licencia, :exp, :horario)");
            $stmt3->execute([':id_empleado' => $id_empleado, ':licencia' => 'LIC-' . rand(1000,9999), ':exp' => 10, ':horario' => 'diurno']);
        } else {
            $stmt3 = $pdo->prepare("INSERT INTO mantenimiento (id_empleado, especialidad, turno, certificaciones) VALUES (:id_empleado, :esp, :turno, :cert)");
            $stmt3->execute([':id_empleado' => $id_empleado, ':esp' => 'Electrónica', ':turno' => 'mañana', ':cert' => 'ISO9001']);
        }

        $empleados[$tipo] = ['id_usuario' => $id_usuario_emp, 'id_empleado' => $id_empleado];
        echo "Empleado tipo={$tipo} creado: id_empleado={$id_empleado}\n";
    }

    // 4) RUTA (asociada al vendedor)
    $stmt = $pdo->prepare("INSERT INTO ruta (origen, destino, duracion, id_vendedor) VALUES (:origen, :destino, :duracion, :id_vendedor) RETURNING id_ruta");
    $stmt->execute([':origen' => 'Madrid', ':destino' => 'Barcelona', ':duracion' => '02:30:00', ':id_vendedor' => $empleados['vendedor']['id_empleado']]);
    $id_ruta = $stmt->fetchColumn();
    echo "Ruta creada: id_ruta={$id_ruta}\n";

    // 5) TREN
    $stmt = $pdo->prepare("INSERT INTO tren (modelo, capacidad) VALUES (:modelo, :capacidad) RETURNING id_tren");
    $stmt->execute([':modelo' => 'Intercity X', ':capacidad' => 180]);
    $id_tren = $stmt->fetchColumn();
    echo "Tren creado: id_tren={$id_tren}\n";

    // 6) ASIENTO
    $numero_asiento = rand(1, 999);
    $stmt = $pdo->prepare("INSERT INTO asiento (numero_asiento, id_tren, clase, estado) VALUES (:num, :id_tren, :clase, :estado)");
    $stmt->execute([':num' => $numero_asiento, ':id_tren' => $id_tren, ':clase' => 'segunda', ':estado' => 'disponible']);
    echo "Asiento creado: numero_asiento={$numero_asiento}\n";

    // 7) ABONO (opcional)
    $stmt = $pdo->prepare("INSERT INTO abono (id_pasajero, tipo, fecha_inicio, fecha_fin, viajes_totales, viajes_restantes) VALUES (:id_pasajero, :tipo, :fi, :ff, :vt, :vr) RETURNING id_abono");
    $stmt->execute([':id_pasajero' => $id_usuario_pasajero, ':tipo' => 'mensual', ':fi' => date('Y-m-d'), ':ff' => date('Y-m-d', strtotime('+1 month')), ':vt' => 30, ':vr' => 30]);
    $id_abono = $stmt->fetchColumn();
    echo "Abono creado: id_abono={$id_abono}\n";

    // 8) VIAJE (usar fecha + hora)
    $fecha = date('Y-m-d', strtotime('+3 days'));
    $hora_salida = '09:30:00';
    $hora_llegada = '12:00:00';
    $precio = 49.90;

    $stmt = $pdo->prepare("INSERT INTO viaje (id_vendedor, id_ruta, id_tren, id_maquinista, fecha, hora_salida, hora_llegada, precio, estado) 
        VALUES (:id_vendedor, :id_ruta, :id_tren, :id_maquinista, :fecha, :hs, :hl, :precio, :estado) RETURNING id_viaje");
    $stmt->execute([
        ':id_vendedor' => $empleados['vendedor']['id_empleado'],
        ':id_ruta' => $id_ruta,
        ':id_tren' => $id_tren,
        ':id_maquinista' => $empleados['maquinista']['id_empleado'],
        ':fecha' => $fecha,
        ':hs' => $hora_salida,
        ':hl' => $hora_llegada,
        ':precio' => $precio,
        ':estado' => 'programado'
    ]);
    $id_viaje = $stmt->fetchColumn();
    echo "Viaje creado: id_viaje={$id_viaje} fecha={$fecha} hora_salida={$hora_salida}\n";

    // 9) INCIDENCIA (relacionada con viaje, mantenimiento y maquinista)
    $stmt = $pdo->prepare("INSERT INTO incidencia (id_viaje, id_mantenimiento, id_maquinista, descripcion, estado) VALUES (:id_viaje, :id_mantenimiento, :id_maquinista, :descripcion, :estado) RETURNING id_incidencia");
    $stmt->execute([':id_viaje' => $id_viaje, ':id_mantenimiento' => $empleados['mantenimiento']['id_empleado'], ':id_maquinista' => $empleados['maquinista']['id_empleado'], ':descripcion' => 'Fallo en sistema de frenos', ':estado' => 'reportado']);
    $id_incidencia = $stmt->fetchColumn();
    echo "Incidencia creada: id_incidencia={$id_incidencia}\n";

    // 10) INSERTAR DOCUMENTO BILLETE EN MONGODB usando viaje y pasajero creados
    if (class_exists('MongoDB\\Client')) {

        $billeteDAO = new BilleteMongoDB();
        $codigo = 'BIL-' . $now . '-' . rand(100,999);

        // Precio que realmente paga (puede coincidir con el viaje)
        $precio_pagado = $precio;
        $precio_pagado = $fecha;
        $precio_pagado = $precio;


        $billete = new Billete(
            null,                       // _id Mongo
            $id_usuario_pasajero,       // id_pasajero
            $id_viaje,                  // id_viaje (CLAVE IMPORTANTE)
            $numero_asiento,            // asiento
            $precio_pagado,  
            $fecha,
            $hora_salida,
            $hora_llegada,        // precio_pagado
            'tarjeta_credito',          // metodo_pago
            $codigo,                    // codigo
            'confirmado',               // estado
            date('Y-m-d')               // fecha_compra
        );

        $idMongo = $billeteDAO->insertar($billete);

        echo "Billete insertado en MongoDB: id={$idMongo} codigo={$codigo}\n";

    } else {
        echo "MongoDB driver no disponible, omitiendo inserción en MongoDB.\n";
    }
} catch (Exception $e) {
    echo "Error durante el poblado: " . $e->getMessage() . "\n";
}


?>
