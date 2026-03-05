<?php
session_start();
require_once __DIR__ . '/php/Conexion.php';

// 1. COMPROBAR SESIÓN
if (!isset($_SESSION['usuario'])) {
    header("Location: inicio_sesion.html?error=necesitas_sesion");
    exit;
}

$id_usuario = $_SESSION['usuario']['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// 2. RECIBIR DATOS DEL FORMULARIO
$tipo_abono = $_POST['tipo_abono'] ?? 'mensual';
$precio = (float)($_POST['precio'] ?? 0);
$titular = $_POST['titular'] ?? '';

// 3. CALCULAR FECHAS Y VIAJES
$fecha_inicio = date('Y-m-d');
$viajes_totales = null;
$viajes_restantes = null;

// Ajustamos las fechas según las restricciones de tu tabla ABONO
if ($tipo_abono === 'anual') {
    $fecha_fin = date('Y-m-d', strtotime('+1 year'));
} elseif ($tipo_abono === 'trimestral') {
    $fecha_fin = date('Y-m-d', strtotime('+3 months'));
} elseif ($tipo_abono === 'viajes_limitados') {
    $fecha_fin = date('Y-m-d', strtotime('+6 months'));
    $viajes_totales = 10; // Ejemplo: asignamos 10 viajes a este bono
    $viajes_restantes = 10;
} else {
    // 'mensual' o 'estudiante' duran 30 días
    $fecha_fin = date('Y-m-d', strtotime('+30 days'));
}

// 4. GUARDAR EN TU TABLA "ABONO"
$pago_exitoso = false;
$mensaje_error = "";

try {
    $pdo = (new Conexion())->conectar();
    
    // Obtenemos el id_pasajero exacto asociado al usuario actual [cite: 1, 2]
    $stmtPasajero = $pdo->prepare("SELECT id_pasajero FROM PASAJERO WHERE id_usuario = :id_usuario");
    $stmtPasajero->execute([':id_usuario' => $id_usuario]);
    $pasajero = $stmtPasajero->fetch(PDO::FETCH_ASSOC);

    if ($pasajero) {
        $id_pasajero = $pasajero['id_pasajero'];

        // INSERTAMOS DIRECTAMENTE EN TU TABLA ABONO ORIGINAL
        $sql = "INSERT INTO ABONO (id_pasajero, tipo, fecha_inicio, fecha_fin, viajes_totales, viajes_restantes) 
                VALUES (:id_pasajero, :tipo, :fecha_inicio, :fecha_fin, :viajes_totales, :viajes_restantes)";
        
        $stmtAbono = $pdo->prepare($sql);
        $stmtAbono->execute([
            ':id_pasajero' => $id_pasajero,
            ':tipo' => $tipo_abono,
            ':fecha_inicio' => $fecha_inicio,
            ':fecha_fin' => $fecha_fin,
            ':viajes_totales' => $viajes_totales,
            ':viajes_restantes' => $viajes_restantes
        ]);
        
        $pago_exitoso = true;
    } else {
        $mensaje_error = "No se encontró el perfil de pasajero asociado a tu cuenta.";
    }

} catch (PDOException $e) {
    $pago_exitoso = false;
    $mensaje_error = "Error DB: " . $e->getMessage(); 
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesando Pago - TrainWeb</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .success-container { max-width: 600px; margin: 60px auto; background: white; padding: 40px; border-radius: 10px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-top: 5px solid #28a745; }
        .success-icon { font-size: 4rem; color: #28a745; margin-bottom: 20px; }
        .details-box { background: #f4f6f8; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left; }
        .details-box p { margin: 10px 0; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .btn-profile { display: inline-block; padding: 12px 25px; background: #0a2a66; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 15px; }
        .error-container { border-top-color: #dc3545; }
        .error-icon { color: #dc3545; }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb</div>
        <nav class="nav"><a href="index.php">Inicio</a></nav>
    </header>

    <main style="min-height: 70vh; padding: 20px;">
        <?php if ($pago_exitoso): ?>
            <div class="success-container">
                <i class="fa-solid fa-circle-check success-icon"></i>
                <h2>¡Compra Completada, <?= htmlspecialchars($titular) ?>!</h2>
                <p>Tu pago se ha procesado correctamente.</p>
                
                <div class="details-box">
                    <p><strong>Tipo de Abono:</strong> <span style="text-transform: capitalize;"><?= str_replace('_', ' ', htmlspecialchars($tipo_abono)) ?></span></p>
                    <p><strong>Válido desde:</strong> <?= date('d/m/Y', strtotime($fecha_inicio)) ?></p>
                    <p><strong>Válido hasta:</strong> <?= date('d/m/Y', strtotime($fecha_fin)) ?></p>
                    <?php if ($viajes_totales): ?>
                        <p><strong>Viajes disponibles:</strong> <?= $viajes_totales ?></p>
                    <?php endif; ?>
                </div>

                <p>El abono se ha vinculado correctamente a tu cuenta. Puedes usarlo en tu próxima compra de billetes.</p>
                <a href="perfil_pasajero.php" class="btn-profile"><i class="fa-solid fa-user"></i> Ir a mi perfil</a>
            </div>
        <?php else: ?>
            <div class="success-container error-container">
                <i class="fa-solid fa-circle-xmark success-icon error-icon"></i>
                <h2>Oops, algo salió mal</h2>
                <p>No pudimos procesar tu abono en este momento.</p>
                <p style="color: red; font-size: 0.9em;"><?= htmlspecialchars($mensaje_error) ?></p>
                <a href="ofertas.php" class="btn-profile" style="background: #dc3545;">Volver a intentar</a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="footer-bottom">&copy; 2026 TrainWeb</div>
    </footer>

</body>
</html>