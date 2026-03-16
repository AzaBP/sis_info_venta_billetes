<?php
session_start();

if (isset($_SESSION['usuario']) && ($_SESSION['usuario']['tipo_usuario'] ?? '') === 'empleado') {
    require_once __DIR__ . '/php/auth_helpers.php';
    header('Location: ' . trainwebRutaPorRol($_SESSION['usuario']));
    exit;
}

$id_usuario = $_SESSION['usuario']['id_usuario'];

require_once __DIR__ . '/php/Conexion.php';
require_once __DIR__ . '/php/auth_helpers.php';

// 2. RECIBIR DATOS DEL FORMULARIO
$tipo_abono = $_POST['tipo_abono'] ?? 'mensual';
$precio = (float)($_POST['precio'] ?? 0);
$titular = $_POST['titular'] ?? '';

// 3. CALCULAR FECHAS Y VIAJES
$fecha_inicio = date('Y-m-d');
$viajes_totales = null;
$viajes_restantes = null;

if ($tipo_abono === 'anual') {
    $fecha_fin = date('Y-m-d', strtotime('+1 year'));
} elseif ($tipo_abono === 'trimestral') {
    $fecha_fin = date('Y-m-d', strtotime('+3 months'));
} elseif ($tipo_abono === 'viajes_limitados') {
    $fecha_fin = date('Y-m-d', strtotime('+1 month'));
    $viajes_totales = 10;
    $viajes_restantes = 10;
} else { // mensual o estudiante
    $fecha_fin = date('Y-m-d', strtotime('+1 month'));
}

$exito = false;
$mensaje_error = '';

try {
    $conexion = new Conexion();
    $pdo = $conexion->conectar();
    
    // === EL PASO CLAVE: BUSCAR O CREAR AL PASAJERO ===
    $stmtPasajero = $pdo->prepare("SELECT id_pasajero FROM pasajero WHERE id_usuario = ?");
    $stmtPasajero->execute([$id_usuario]);
    $id_pasajero = $stmtPasajero->fetchColumn();

    // Si el usuario no está en la tabla PASAJERO, lo insertamos
    if (!$id_pasajero) {
        // CORRECCIÓN PARA POSTGRESQL: Usamos RETURNING id_pasajero
        $stmtNuevoPasajero = $pdo->prepare("INSERT INTO pasajero (id_usuario) VALUES (?) RETURNING id_pasajero");
        $stmtNuevoPasajero->execute([$id_usuario]);
        $id_pasajero = $stmtNuevoPasajero->fetchColumn(); 
    }

    // === INSERTAR EL ABONO ===
    $sql = "INSERT INTO abono (id_pasajero, tipo, fecha_inicio, fecha_fin, viajes_totales, viajes_restantes) 
            VALUES (:id_pasajero, :tipo, :fecha_inicio, :fecha_fin, :viajes_totales, :viajes_restantes)";
            
    $stmt = $pdo->prepare($sql);
    $exito = $stmt->execute([
        ':id_pasajero' => $id_pasajero,
        ':tipo' => $tipo_abono,
        ':fecha_inicio' => $fecha_inicio,
        ':fecha_fin' => $fecha_fin,
        ':viajes_totales' => $viajes_totales,
        ':viajes_restantes' => $viajes_restantes
    ]);

} catch (PDOException $e) {
    $mensaje_error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Procesando Abono...</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .success-container { max-width: 600px; margin: 50px auto; text-align: center; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .success-icon { font-size: 4rem; color: #28a745; margin-bottom: 20px; }
        .btn-profile { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #0a2a66; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .error-box { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; text-align: left; font-size: 0.9em; border: 1px solid #f5c6cb; }
    </style>
</head>
<body style="background: #f4f6f8;">
    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb</div>
        <nav class="nav"><a href="index.php">Inicio</a><a href="ofertas.php">Ofertas</a></nav>
    </header>

    <main>
        <?php if ($exito): ?>
            <div class="success-container">
                <i class="fa-solid fa-circle-check success-icon"></i>
                <h2>¡Compra realizada con éxito!</h2>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: left;">
                    <p><strong>Tipo de Abono:</strong> <span style="text-transform: capitalize;"><?= str_replace('_', ' ', htmlspecialchars($tipo_abono)) ?></span></p>
                    <p><strong>Válido hasta:</strong> <?= date('d/m/Y', strtotime($fecha_fin)) ?></p>
                </div>
                <p>El abono se ha vinculado correctamente a tu cuenta.</p>
                <a href="perfil_pasajero.php" class="btn-profile"><i class="fa-solid fa-user"></i> Ir a mi perfil</a>
            </div>
        <?php else: ?>
            <div class="success-container">
                <i class="fa-solid fa-circle-xmark success-icon" style="color: #dc3545;"></i>
                <h2>Oops, algo salió mal</h2>
                <p>No pudimos procesar tu abono en este momento.</p>
                
                <?php if ($mensaje_error): ?>
                    <div class="error-box">
                        <strong>Detalle del error técnico:</strong><br>
                        <?= htmlspecialchars($mensaje_error) ?>
                    </div>
                <?php endif; ?>

                <a href="ofertas.php" class="btn-profile" style="background: #dc3545;">Volver a intentar</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>