<?php
/**
 * Simulador de Sensores IoT para Trenes
 * Genera incidencias realistas detectadas por sensores del tren
 * Se ejecuta automáticamente cada 30 segundos desde el panel de mantenimiento
 */

header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';

// Validar token - usar default si no existe variable de entorno
$tokenEnv = getenv('TRAINWEB_IOT_TOKEN') ?: 'trainweb_iot_test_token_2026';
$tokenReq = $_SERVER['HTTP_X_IOT_TOKEN'] ?? ($_POST['token'] ?? $_GET['token'] ?? '');

if ($tokenReq !== $tokenEnv) {
    http_response_code(403);
    echo json_encode(['error' => 'Token IoT no válido']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('Conexión no disponible');
    }

    // Limpiar incidencias IoT no confirmadas de hace más de 24 horas
    $pdo->exec("DELETE FROM incidencia WHERE origen = 'iot' AND estado = 'reportado' AND fecha_reporte < (NOW() - INTERVAL 24 HOUR)");

    // Obtener viajes activos o recientes (más flexible para testing)
    $stmtViajes = $pdo->query(
        "SELECT id_viaje, id_maquinista FROM viaje
         WHERE estado IN ('en_transito', 'proximo', 'en_estacion', 'programado')
         OR fecha_salida >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         ORDER BY RAND()
         LIMIT 15"
    );
    $viajes = $stmtViajes->fetchAll(PDO::FETCH_ASSOC);

    if (empty($viajes)) {
        echo json_encode([
            'ok' => true,
            'incidencias_generadas' => 0,
            'mensaje' => 'No hay viajes disponibles',
        ]);
        exit;
    }

    // Definición de sensores con probabilidades AUMENTADAS para testing
    $sensores = [
        [
            'nombre' => 'Sensor de Temperatura Motor',
            'tipo' => 'temperatura_motor',
            'probabilidad' => 0.15,
            'descripciones' => [
                'Temperatura del motor: 95°C (umbral crítico 90°C)',
                'Motor sobrecalentado - Sistema de refrigeración fallo',
                'Temperatura anormal detectada en motor principal',
                'Sensor reporta 98°C en motor de tracción',
            ],
            'afecta_pasajero' => true,
        ],
        [
            'nombre' => 'Sensor de Presión de Frenos',
            'tipo' => 'presion_frenos',
            'probabilidad' => 0.18,
            'descripciones' => [
                'Presión de frenos baja: 6.3 bar (mínimo requerido: 6.5 bar)',
                'Sistema de frenos detecta pérdida de presión gradual',
                'Presión anormal en línea de frenos - Posible fuga',
                'Sensor de presión: 6.1 bar - Por debajo del límite seguro',
            ],
            'afecta_pasajero' => true,
        ],
        [
            'nombre' => 'Sensor de Vibración en Ejes',
            'tipo' => 'vibracion_ejes',
            'probabilidad' => 0.12,
            'descripciones' => [
                'Vibración detectada en eje delantero - Amplitud: 4.2mm',
                'Posible desalineación en bogie frontal',
                'Vibración anormal en eje trasero - Mayor que el valor normal',
                'Sistema de suspensión: Vibración excesiva detectada',
            ],
            'afecta_pasajero' => true,
        ],
        [
            'nombre' => 'Sensor de Puerta',
            'tipo' => 'fallo_puerta',
            'probabilidad' => 0.14,
            'descripciones' => [
                'Puerta del coche 3 ciclo defectuoso - No cierra correctamente',
                'Sensor magnético puerta 5 reporta contacto intermitente',
                'Mecanismo de cierre puerta 2 con fallo - Requiere mantenimiento',
                'Puerta 4 operación lenta - Mayor tiempo de cierre de lo normal',
            ],
            'afecta_pasajero' => true,
        ],
        [
            'nombre' => 'Sensor de Temperatura Rodamientos',
            'tipo' => 'temp_rodamientos',
            'probabilidad' => 0.13,
            'descripciones' => [
                'Temperatura rodamiento eje delantero: 72°C (máximo: 70°C)',
                'Rodamiento trasero en sobrecalentamiento - 75°C detectados',
                'Sensores detectan calentamiento anormal en cojinetes',
                'Temperatura crítica en rodamientos de tracción: 78°C',
            ],
            'afecta_pasajero' => true,
        ],
        [
            'nombre' => 'Sensor de Nivel de Aceite',
            'tipo' => 'nivel_aceite',
            'probabilidad' => 0.10,
            'descripciones' => [
                'Nivel de aceite motor bajo - Recarga recomendada',
                'Sensor hidráulico advierte nivel mínimo en depósito',
                'Sistema de lubricación: Por debajo del nivel mínimo seguro',
                'Pérdida de aceite detectada - Chequeo de fugas recomendado',
            ],
            'afecta_pasajero' => false,
        ],
        [
            'nombre' => 'Sensor Eléctrico',
            'tipo' => 'fallo_electrico',
            'probabilidad' => 0.11,
            'descripciones' => [
                'Voltaje auxiliar fuera de rango: 23.5V (rango: 24V ±2V)',
                'Sistema de tracción reporta pico de corriente anormal',
                'Circuito de emergencia: Voltaje bajo detectado',
                'Regulador de tensión: Salida inestable reportada',
            ],
            'afecta_pasajero' => false,
        ],
        [
            'nombre' => 'Sensor de Ocupación',
            'tipo' => 'sensor_ocupacion',
            'probabilidad' => 0.08,
            'descripciones' => [
                'Sensor ocupación coche 2 defectuoso - Lecturas inconsistentes',
                'Sistema de carga: Sensor fallo en sección central',
                'Indicador de ocupación compartimento 4 sin respuesta',
            ],
            'afecta_pasajero' => false,
        ],
        [
            'nombre' => 'Sensor de Desgaste de Ruedas',
            'tipo' => 'desgaste_ruedas',
            'probabilidad' => 0.12,
            'descripciones' => [
                'Desgaste rueda eje 2: 16mm (límite: 12.5mm) - Mantenimiento urgente',
                'Rueda trasera con desgaste irregular detectado',
                'Sistema de monitoreo: Rueda frontal derecha próxima a límite de desgaste',
            ],
            'afecta_pasajero' => true,
        ],
        [
            'nombre' => 'Sensor de Colisión',
            'tipo' => 'sensor_coalicion',
            'probabilidad' => 0.09,
            'descripciones' => [
                'Sensor de colisión trasero reporta obstáculo detectado',
                'Sistema anti-colisión alarma baja detectada en zona de acoplamiento',
                'Colisión frontal: Distancia crítica a objeto',
            ],
            'afecta_pasajero' => true,
        ],
    ];

    $incidenciasGeneradas = 0;
    $detallesGenerados = [];

    // Obtener mantenedor aleatorio
    $stmtMant = $pdo->query('SELECT id_empleado FROM mantenimiento ORDER BY RAND() LIMIT 1');
    $idMantenimiento = (int)$stmtMant->fetchColumn();

    if ($idMantenimiento <= 0) {
        echo json_encode(['error' => 'No hay mantenimiento disponible']);
        exit;
    }

    // Iterar sobre cada viaje y simular sensores
    foreach ($viajes as $viaje) {
        $idViaje = (int)$viaje['id_viaje'];
        $idMaquinista = (int)$viaje['id_maquinista'];

        foreach ($sensores as $sensor) {
            // Simular probabilidad de fallo
            $random = mt_rand(1, 1000) / 1000;
            if ($random <= $sensor['probabilidad']) {
                // Seleccionar descripción aleatoria
                $descripcion = $sensor['descripciones'][array_rand($sensor['descripciones'])];

                // Verificar que no exista incidencia similar muy reciente (últimos 5 minutos)
                $stmtCheck = $pdo->prepare(
                    "SELECT COUNT(*) FROM incidencia
                     WHERE id_viaje = :id_viaje
                     AND tipo_incidencia = :tipo
                     AND origen = 'iot'
                     AND estado IN ('reportado', 'en_proceso')
                     AND fecha_reporte > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
                );
                $stmtCheck->execute([
                    ':id_viaje' => $idViaje,
                    ':tipo' => $sensor['tipo'],
                ]);

                if ((int)$stmtCheck->fetchColumn() > 0) {
                    continue; // Evitar duplicados muy recientes
                }

                // Registrar la incidencia
                $stmtInsert = $pdo->prepare(
                    "INSERT INTO incidencia (id_viaje, id_mantenimiento, id_maquinista, tipo_incidencia, origen, descripcion, fecha_reporte, estado, afecta_pasajero)
                     VALUES (:id_viaje, :id_mantenimiento, :id_maquinista, :tipo_incidencia, :origen, :descripcion, :fecha_reporte, :estado, :afecta_pasajero)"
                );

                $afectaPasajero = $sensor['afecta_pasajero'] ? 1 : 0;

                $stmtInsert->execute([
                    ':id_viaje' => $idViaje,
                    ':id_mantenimiento' => $idMantenimiento,
                    ':id_maquinista' => $idMaquinista,
                    ':tipo_incidencia' => $sensor['tipo'],
                    ':origen' => 'iot',
                    ':descripcion' => $descripcion,
                    ':fecha_reporte' => date('Y-m-d H:i:s'),
                    ':estado' => 'reportado',
                    ':afecta_pasajero' => $afectaPasajero,
                ]);

                $incidenciasGeneradas++;
                $detallesGenerados[] = [
                    'viaje' => $idViaje,
                    'sensor' => $sensor['nombre'],
                    'tipo' => $sensor['tipo'],
                    'descripcion' => $descripcion,
                ];
            }
        }
    }

    echo json_encode([
        'ok' => true,
        'incidencias_generadas' => $incidenciasGeneradas,
        'detalles' => $detallesGenerados,
        'timestamp' => date('Y-m-d H:i:s'),
        'viajes_procesados' => count($viajes),
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al generar incidencias IoT']);
}
?>
