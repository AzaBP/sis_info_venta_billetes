<?php

// 1. Configuración de entorno
set_time_limit(0); 
ini_set('memory_limit', '512M');

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../php');
header('Content-Type: text/html; charset=utf-8');

// Ajuste de rutas para estar en /baseDatos/ (Usamos conexiones directas para no depender de los DAO)
require_once __DIR__ . '/../php/Conexion.php';
require_once __DIR__ . '/../php/ConexionMongo.php';

echo "<h1>🚀 Panel de Carga Masiva - CAESARAV</h1>";
echo "<div style='font-family: monospace; background: #222; color: #0f0; padding: 20px; border-radius: 10px; max-height: 500px; overflow-y: scroll;'>";

try {
    // 1. Conexiones directas a las bases de datos (Saltamos el DAO para evitar el error estático)
    $pdo = (new Conexion())->conectar();

    // Extraer maquinistas válidos para no violar la clave foránea
    $stmtMaq = $pdo->query("SELECT id_empleado FROM MAQUINISTA");
    $maquinistasBD = $stmtMaq->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($maquinistasBD)) {
        throw new Exception("No hay ningún maquinista en la base de datos. Inicia sesión como administrador y crea al menos un maquinista antes de lanzar la carga masiva.");
    }

    // 2. Datos Maestros (Extraídos de tus consultas SQL)
    $rutas = [
//        ['id_ruta' => 1, 'origen' => 'Madrid Puerta de Atocha', 'destino' => 'Barcelona Sants', 'duracion' => '02:30:00', 'id_vendedor' => 1],
//        ['id_ruta' => 2, 'origen' => 'Zaragoza Delicias', 'destino' => 'Madrid Puerta de Atocha', 'duracion' => '01:15:00', 'id_vendedor' => 1],
//        ['id_ruta' => 3, 'origen' => 'Zaragoza Delicias', 'destino' => 'Barcelona Sants', 'duracion' => '01:30:00', 'id_vendedor' => 1],
//        ['id_ruta' => 4, 'origen' => 'Valencia Joaquín Sorolla', 'destino' => 'Madrid Puerta de Atocha', 'duracion' => '01:50:00', 'id_vendedor' => 1],
//        ['id_ruta' => 5, 'origen' => 'Sevilla Santa Justa', 'destino' => 'Madrid Puerta de Atocha', 'duracion' => '02:30:00', 'id_vendedor' => 1],
//        ['id_ruta' => 36, 'origen' => 'Barcelona Sants', 'destino' => 'Madrid Puerta de Atocha', 'duracion' => '02:30:00', 'id_vendedor' => 1],

        ['id_ruta' => 7, 'origen' => 'Madrid Puerta de Atocha', 'destino' => 'Valencia Joaquín Sorolla', 'duracion' => '01:50:00', 'id_vendedor' => 1],
        ['id_ruta' => 8, 'origen' => 'Barcelona Sants', 'destino' => 'Sevilla Santa Justa', 'duracion' => '03:00:00', 'id_vendedor' => 8],
        ['id_ruta' => 9, 'origen' => 'Valencia Joaquín Sorolla', 'destino' => 'Alicante Término', 'duracion' => '01:00:00', 'id_vendedor' => 1],
        ['id_ruta' => 10, 'origen' => 'Madrid Puerta de Atocha', 'destino' => 'Bilbao Abando', 'duracion' => '02:30:00', 'id_vendedor' => 1],
        ['id_ruta' => 11, 'origen' => 'Barcelona Sants', 'destino' => 'Valencia Joaquín Sorolla', 'duracion' => '01:30:00', 'id_vendedor' => 8],
        ['id_ruta' => 12, 'origen' => 'Sevilla Santa Justa', 'destino' => 'Córdoba Central', 'duracion' => '01:00:00', 'id_vendedor' => 1],
        ['id_ruta' => 13, 'origen' => 'Madrid Puerta de Atocha', 'destino' => 'Toledo Estación', 'duracion' => '00:45:00', 'id_vendedor' => 1],
        
//        ['id_ruta' => 14, 'origen' => 'Bilbao Abando', 'destino' => 'San Sebastián Donostia', 'duracion' => '01:30:00', 'id_vendedor' => 8],
//        ['id_ruta' => 15, 'origen' => 'Barcelona Sants', 'destino' => 'Girona Estación', 'duracion' => '01:00:00', 'id_vendedor' => 1],
//        ['id_ruta' => 16, 'origen' => 'Madrid Puerta de Atocha', 'destino' => 'Segovia-Guadarrama', 'duracion' => '01:00:00', 'id_vendedor' => 1],
//        ['id_ruta' => 17, 'origen' => 'Valencia Joaquín Sorolla', 'destino' => 'Tarragona Central', 'duracion' => '01:30:00', 'id_vendedor' => 8],
//        ['id_ruta' => 18, 'origen' => 'Sevilla Santa Justa', 'destino' => 'Cádiz Término', 'duracion' => '02:00:00', 'id_vendedor' => 1],
//        ['id_ruta' => 19, 'origen' => 'Valencia Joaquín Sorolla', 'destino' => 'Cuenca Fernando Zóbel', 'duracion' => '01:30:00', 'id_vendedor' => 1],
//        ['id_ruta' => 20, 'origen' => 'Madrid Puerta de Atocha', 'destino' => 'Guadalajara Central', 'duracion' => '00:40:00', 'id_vendedor' => 8]
    ];

    $trenes = [
        ['id_tren' => 1, 'modelo' => 'AVE', 'capacidad' => 300],
        ['id_tren' => 2, 'modelo' => 'AVLO', 'capacidad' => 400],
        ['id_tren' => 4, 'modelo' => 'AVE S-103', 'capacidad' => 250],
        ['id_tren' => 12, 'modelo' => 'Avant-Siemens', 'capacidad' => 550]
    ];

    // Datos de pasajeros reales para que Mongo tenga coherencia
    $pasajerosData = [
        ['id_pasajero' => 1, 'id_usuario' => 3, 'nombre' => 'Julio', 'apellido' => 'Apruebame', 'doc' => '12345678A', 'email' => 'julio@apruebame.porfa'],
        ['id_pasajero' => 2, 'id_usuario' => 4, 'nombre' => 'Maria', 'apellido' => 'Garcia', 'doc' => '87654321B', 'email' => 'maria@test.com'],
        ['id_pasajero' => 9, 'id_usuario' => 14, 'nombre' => 'Pasajero', 'apellido' => 'Nueve', 'doc' => '12345678z', 'email' => 'test9@test.com']
    ];

    // 3. Rango de fechas: 05 de Mayo al 07 de Noviembre 2026
    $fecha_inicio = new DateTime('2026-05-05');
    $fecha_fin = new DateTime('2026-11-07');
    $intervalo = new DateInterval('P1D');
    $periodo = new DatePeriod($fecha_inicio, $intervalo, $fecha_fin->modify('+1 day'));

    $totalViajes = 0;
    $totalBilletes = 0;

    echo "> Iniciando bucle temporal...<br>";

    foreach ($periodo as $fecha) {
        $fecha_str = $fecha->format('Y-m-d');
        
        foreach ($rutas as $ruta) {
            $num_viajes_hoy = rand(0, 4); // 0 a 4 viajes por ruta al día
            
            for ($i = 0; $i < $num_viajes_hoy; $i++) {
                $tren = $trenes[array_rand($trenes)];
                
                // Generar horas coherentes
                $h_salida = rand(6, 21);
                $m_salida = array_rand([0, 15, 30, 45]);
                $hora_salida_str = sprintf("%02d:%02d:00", $h_salida, $m_salida);
                
                // Calcular llegada basado en duracion de la ruta
                $dt_salida = new DateTime($fecha_str . ' ' . $hora_salida_str);
                $duracion_arr = explode(':', $ruta['duracion']);
                $dt_llegada = clone $dt_salida;
                $dt_llegada->modify("+{$duracion_arr[0]} hours +{$duracion_arr[1]} minutes");
                $hora_llegada_str = $dt_llegada->format('H:i:s');

                $precio_base = rand(45, 95) + 0.90;

                // A) Insertar en PostgreSQL (VIAJE) mediante SQL directo
                $sqlViaje = "INSERT INTO VIAJE (id_vendedor, id_ruta, id_tren, id_maquinista, fecha, hora_salida, hora_llegada, precio, estado)
                             VALUES (:id_vendedor, :id_ruta, :id_tren, :id_maquinista, :fecha, :hora_salida, :hora_llegada, :precio, :estado)";
                $stmt = $pdo->prepare($sqlViaje);
                $stmt->execute([
                    ':id_vendedor' => $ruta['id_vendedor'],
                    ':id_ruta' => $ruta['id_ruta'],
                    ':id_tren' => $tren['id_tren'],
                    ':id_maquinista' => $maquinistasBD[array_rand($maquinistasBD)],
                    ':fecha' => $fecha_str,
                    ':hora_salida' => $hora_salida_str,
                    ':hora_llegada' => $hora_llegada_str,
                    ':precio' => $precio_base,
                    ':estado' => 'programado'
                ]);
                $id_viaje_sql = $pdo->lastInsertId();

                if ($id_viaje_sql) {
                    $totalViajes++;
                    
                    // B) Calcular ocupación (5% al 30%)
                    $ocupacion_porc = rand(5, 30) / 100;
                    $num_asientos = ceil($tren['capacidad'] * $ocupacion_porc);
                    
                    // Mezclar asientos para que no sean siempre los primeros
                    $asientos_libres = range(1, $tren['capacidad']);
                    shuffle($asientos_libres);

                    for ($j = 0; $j < $num_asientos; $j++) {
                        $p = $pasajerosData[array_rand($pasajerosData)];
                        $asiento = array_pop($asientos_libres);
                        $vagon = rand(1, 4);
                        
                        // Generar localizador según tu api_reservar.php
                        $localizador = 'TW-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
                        $fecha_compra = date('Y-m-d H:i:s');

                        // C) Insertar en MongoDB
                        // Creamos el array con la estructura completa que espera tu API
                        $documentoMongo = [
                            'id_viaje' => (int)$id_viaje_sql,
                            'numero_asiento' => (int)$asiento,
                            'id_pasajero' => (int)$p['id_pasajero'],
                            'estado' => 'confirmado',
                            'fecha_compra' => $fecha_compra,
                            'vagon' => (int)$vagon,
                            'fecha_viaje' => $fecha_str,
                            'hora_salida' => $hora_salida_str,
                            'hora_llegada' => $hora_llegada_str,
                            'origen' => $ruta['origen'],
                            'destino' => $ruta['destino'],
                            'tipo_tren' => $tren['modelo'],
                            'precio_pagado' => (float)$precio_base,
                            'codigo_billete' => $localizador,
                            'pasajero_nombre' => $p['nombre'],
                            'pasajero_apellidos' => $p['apellido'],
                            'pasajero_documento' => $p['doc'],
                            'pasajero_email' => $p['email'],
                            'fecha_modificacion' => $fecha_compra,
                            'precio_final' => (float)$precio_base,
                            'tramo' => 'ida'
                        ];

                        // Usamos la colección directamente para asegurar que insertamos TODOS los campos extra
                        $dbMongo = (new ConexionMongo())->conectar();
                        $coleccion = $dbMongo->selectCollection('billetes');
                        $resMongo = $coleccion->insertOne($documentoMongo);

                        if ($resMongo->getInsertedCount() > 0) {
                            $totalBilletes++;
                        }
                    }
                    echo "> Creado Viaje #$id_viaje_sql ($fecha_str $hora_salida_str) con $num_asientos ocupantes.<br>";
                } else {
                    echo "<span style='color:red;'>! Error al insertar viaje en Postgres ($fecha_str)</span><br>";
                }
            }
        }
    }

} catch (Exception $e) {
    echo "<span style='color:red;'>❌ ERROR CRÍTICO: " . $e->getMessage() . "</span><br>";
}

echo "</div>";
echo "<div style='margin-top:20px; padding:20px; background:#e1f5fe; border-left: 5px solid #01579b;'>";
echo "<h3>Resumen de la operación:</h3>";
echo "<ul>";
echo "<li>Viajes creados en SQL: <strong>$totalViajes</strong></li>";
echo "<li>Billetes creados en MongoDB: <strong>$totalBilletes</strong></li>";
echo "</ul>";
echo "<p>Los datos ya están disponibles en las interfaces de <strong>Mis Billetes</strong> y <strong>Gestión de Viajes</strong>.</p>";
echo "</div>";
?>