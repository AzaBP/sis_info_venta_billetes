<?php
session_start();

require_once __DIR__ . '/php/Conexion.php';
require_once __DIR__ . '/php/auth_helpers.php';

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'empleado') {
    header('Location: inicio_sesion.html?error=no_autorizado');
    exit;
}

if (($usuario['tipo_empleado'] ?? '') !== 'vendedor' && !trainwebEsAdministrador($usuario)) {
    header('Location: index.php?error=acceso_denegado');
    exit;
}

$idEmpleado = null;
$region = 'Sin asignar';
$comision = '0.00';

try {
    $pdo = (new Conexion())->conectar();
    if ($pdo) {
        $stmt = $pdo->prepare(
            "SELECT e.id_empleado, v.region, v.comision_porcentaje
             FROM empleado e
             LEFT JOIN vendedor v ON v.id_empleado = e.id_empleado
             WHERE e.id_usuario = :id_usuario
             LIMIT 1"
        );
        $stmt->execute([':id_usuario' => (int)$usuario['id_usuario']]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            $idEmpleado = $fila['id_empleado'] ?? null;
            $region = $fila['region'] ?? $region;
            $comision = $fila['comision_porcentaje'] ?? $comision;
        }
    }
} catch (Throwable $e) {
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Panel de Vendedor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/vendedor.css">
</head>
<body>
<header class="header">
    <div class="logo">
        <i class="fa-solid fa-train"></i> TrainWeb
        <span style="font-size: .8rem; opacity: .8; font-weight: normal; margin-left: 10px;">| Portal Vendedor</span>
    </div>
    <nav class="nav">
        <div class="user-display" style="color: white; margin-right: 20px; font-weight: 500;">
            <i class="fa-solid fa-id-badge"></i>
            <?php echo htmlspecialchars(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            <?php if ($idEmpleado): ?>
                | ID #<?php echo (int)$idEmpleado; ?>
            <?php endif; ?>
        </div>
        <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesion</a>
    </nav>
</header>

<main class="dashboard-container">
    <div class="dashboard-header">
        <h1>Gestion comercial y venta asistida</h1>
        <p>Sesion activa: <?php echo htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?> | Region: <?php echo htmlspecialchars((string)$region, ENT_QUOTES, 'UTF-8'); ?> | Comision: <?php echo htmlspecialchars((string)$comision, ENT_QUOTES, 'UTF-8'); ?>%</p>
    </div>

    <div class="dashboard-grid">
        <section class="card search-section-panel">
            <h2><i class="fa-solid fa-magnifying-glass"></i> Identificacion cliente</h2>
            <div class="search-box">
                <input type="text" id="dniInput" placeholder="DNI / NIE del pasajero" maxlength="9">
                <button id="btnBuscar" class="btn-primary">Buscar</button>
            </div>
            <div id="clientInfo" class="client-details hidden">
                <div class="status-badge active">Cliente verificado</div>
                <h3><span id="clientName">---</span></h3>
                <p><strong>Email:</strong> <span id="clientEmail">---</span></p>
                <p><strong>Telefono:</strong> <span id="clientPhone">---</span></p>
                <hr>
                <p class="payment-method"><i class="fa-brands fa-cc-visa"></i> VISA terminada en <span id="clientCard">****</span></p>
            </div>
            <div id="clientError" class="error-msg hidden">
                <i class="fa-solid fa-circle-exclamation"></i> Cliente no encontrado.
            </div>
        </section>

        <section class="card operations-section disabled" id="operationsPanel">
            <h2><i class="fa-solid fa-ticket"></i> Consola de operaciones</h2>
            <div class="actions-grid">
                <button class="action-btn" onclick="openModal('venta')"><i class="fa-solid fa-cart-plus"></i><span>Nueva venta</span></button>
                <button class="action-btn" onclick="openModal('cambio')"><i class="fa-solid fa-repeat"></i><span>Cambio billete</span></button>
                <button class="action-btn" onclick="alert('Funcionalidad en construccion')"><i class="fa-solid fa-ban"></i><span>Cancelar reserva</span></button>
                <button class="action-btn" onclick="alert('Factura enviada')"><i class="fa-solid fa-file-invoice"></i><span>Reenviar factura</span></button>
            </div>
            <div class="mini-list-container">
                <h4>Ultimos viajes del cliente</h4>
                <ul class="mini-list" id="recentTrips"></ul>
            </div>
        </section>
    </div>
</main>

<script src="js/vendedor.js"></script>
</body>
</html>

