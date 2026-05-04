<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/php/Conexion.php';
require_once __DIR__ . '/php/auth_helpers.php';

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'empleado') {
    header('Location: employee_login.php?error=no_autorizado');
    exit;
}

if (($usuario['tipo_empleado'] ?? '') !== 'maquinista' && !trainwebEsAdministrador($usuario)) {
    header('Location: ' . trainwebRutaPorRol($usuario));
    exit;
}

$idEmpleado = null;
$viajes = [];
$viajeActivo = null;

try {
    $pdo = (new Conexion())->conectar();
    if ($pdo) {
        $stmt = $pdo->prepare(
            "SELECT e.id_empleado
             FROM empleado e
             WHERE e.id_usuario = :id_usuario
             LIMIT 1"
        );
        $stmt->execute([':id_usuario' => (int)$usuario['id_usuario']]);
        $idEmpleado = (int)$stmt->fetchColumn();

        if ($idEmpleado > 0) {
            $hoy = (new DateTimeImmutable('today', new DateTimeZone('Europe/Madrid')))->format('Y-m-d');
            $stmt = $pdo->prepare(
                "SELECT v.id_viaje, v.fecha, v.hora_salida, v.hora_llegada, v.id_tren, r.origen, r.destino
                 FROM viaje v
                 LEFT JOIN ruta r ON r.id_ruta = v.id_ruta
                 WHERE v.id_maquinista = :id_maquinista AND v.fecha::date >= :hoy
                 ORDER BY v.fecha ASC, v.hora_salida ASC"
            );
            $stmt->execute([':id_maquinista' => $idEmpleado, ':hoy' => $hoy]);
            $viajes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (count($viajes) > 0) {
                $viajeActivo = $viajes[0];
            }
        }
    }
} catch (Throwable $e) {
    error_log('Error cargando viajes de maquinista: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Panel Maquinista</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/maquinista.css">
</head>
<body class="maquinista-page">
<header class="header">
    <a href="index.php" class="logo">
        <i class="fa-solid fa-train"></i> TrainWeb
        <span class="sub-brand">| Maquinista</span>
    </a>
    <nav class="nav">
        <div class="user-display">
            <i class="fa-solid fa-user-gear"></i>
            <?php echo htmlspecialchars(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            <?php if ($idEmpleado): ?>
                | ID #<?php echo (int)$idEmpleado; ?>
            <?php endif; ?>
        </div>
        <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
    </nav>
</header>

<main class="maq-container">
    <section class="maq-hero">
        <h1><i class="fa-solid fa-triangle-exclamation"></i> Reporte rapido de incidencias</h1>
        <p>Viaje asignado automaticamente. Pulsa el tipo de incidencia y se envia a mantenimiento.</p>
    </section>

    <section class="maq-panel">
        <?php if ($viajeActivo): ?>
            <div class="maq-trip" data-viaje="<?php echo (int)$viajeActivo['id_viaje']; ?>">
                <div class="trip-badge">VIAJE ACTIVO</div>
                <div class="trip-main">
                    <div class="trip-id">#<?php echo (int)$viajeActivo['id_viaje']; ?></div>
                    <div class="trip-meta">
                        <?php echo htmlspecialchars((string)($viajeActivo['origen'] ?? 'Origen') . ' - ' . (string)($viajeActivo['destino'] ?? 'Destino'), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
                <div class="trip-time">
                    <?php echo htmlspecialchars($viajeActivo['fecha'] . ' ' . $viajeActivo['hora_salida'], ENT_QUOTES, 'UTF-8'); ?>
                    <span class="trip-sub">Salida programada</span>
                </div>
            </div>
        <?php else: ?>
            <div class="maq-trip maq-trip-empty">
                <div class="trip-badge">SIN VIAJE</div>
                <div class="trip-main">
                    <div class="trip-id">No hay viaje asignado</div>
                    <div class="trip-meta">Contacta con operaciones</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="maq-buttons">
            <button class="btn-incident red" data-tipo="frenos"><i class="fa-solid fa-train-tram"></i> Frenos</button>
            <button class="btn-incident yellow" data-tipo="puertas"><i class="fa-solid fa-door-closed"></i> Puertas</button>
            <button class="btn-incident lightblue" data-tipo="climatizacion"><i class="fa-solid fa-wind"></i> Climatizacion</button>
            <button class="btn-incident green" data-tipo="senalizacion"><i class="fa-solid fa-signal"></i> Señalización</button>
        </div>

        <div id="maqStatus" class="maq-status"></div>
    </section>
</main>

<script src="js/maquinista.js"></script>
</body>
</html>
