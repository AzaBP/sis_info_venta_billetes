<?php
session_start();
$usuarioSesion = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
$nombreSesion = isset($usuarioSesion['nombre']) ? $usuarioSesion['nombre'] : '';

require_once __DIR__ . '/php/auth_helpers.php';
if (isset($_SESSION['usuario']) && ($_SESSION['usuario']['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($_SESSION['usuario']));
    exit;
}
require_once __DIR__ . '/php/Conexion.php';

// 1. Recoger el tipo de abono de la URL (por defecto 'mensual' si alguien entra sin hacer clic)
$tipo_codigo = $_GET['tipo'] ?? 'mensual';
$abono = null;

try {
    $pdo = (new Conexion())->conectar();
    
    // 2. Intentar buscar el abono en la base de datos
    $stmt = $pdo->prepare("SELECT nombre, descripcion, precio FROM TIPO_ABONO WHERE tipo_codigo = :tipo");
    $stmt->execute([':tipo' => $tipo_codigo]);
    $abono = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si la tabla no existe, no hacemos nada y usamos el fallback de abajo
}

// 3. FALLBACK: Si no existe en la DB o hay error, usamos estos datos fijos
if (!$abono) {
    $abonos_default = [
        "mensual" => ["nombre" => "Abono Mensual", "descripcion" => "Viajes ilimitados durante 30 días.", "precio" => 49.90],
        "anual" => ["nombre" => "Abono Anual", "descripcion" => "Viaja todo el año sin preocupaciones.", "precio" => 450.00],
        "estudiante" => ["nombre" => "Abono Estudiante", "descripcion" => "Descuento exclusivo para menores de 26 años.", "precio" => 29.90]
    ];
    // Si el tipo no existe en nuestro array, forzamos el mensual
    $abono = $abonos_default[$tipo_codigo] ?? $abonos_default['mensual'];
}

// Formateamos el precio para que se vea bien (ej: 49,90 €)
$precio_formateado = number_format($abono['precio'], 2, ',', '.') . ' €';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprar <?= htmlspecialchars($abono['nombre']) ?> - TrainWeb</title>
    
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/compra.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Unos pequeños ajustes específicos para esta página */
        .abono-header { text-align: center; margin-bottom: 30px; }
        .abono-price { font-size: 2.5rem; color: #0a2a66; font-weight: bold; margin: 10px 0; }
        .payment-wrapper { max-width: 600px; margin: 40px auto; padding: 0 20px; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb</div>
        <nav class="nav">
            <a href="index.php">Inicio</a>
            <a href="#">Billetes</a>
            <div class="dropdown">
                <a href="#">Idiomas <i class="fa-solid fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="#">Español</a>
                    <a href="#">Inglés</a>
                    <a href="#">Francés</a>
                    <a href="#">Alemán</a>
                </div>
            </div>
            <a href="ofertas.php">Ofertas</a>
            <a href="ayuda.php">Ayuda</a>
        </nav>
        <div class="user-actions" id="userActions">
            <?php if ($usuarioSesion): ?>
                <div class="account-dropdown open-on-hover">
                    <button type="button" class="account-toggle">
                        <span class="account-avatar"><?php echo strtoupper(substr($nombreSesion, 0, 1)); ?></span>
                        <span class="account-name"><?php echo htmlspecialchars($nombreSesion, ENT_QUOTES, 'UTF-8'); ?></span>
                        <i class="fa-solid fa-caret-down"></i>
                    </button>
                    <div class="account-menu">
                        <a href="perfil_pasajero.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
                        <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="inicio_sesion.html" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="payment-wrapper">
        <div class="payment-container">
            <div class="abono-header">
                <h1><?= htmlspecialchars($abono['nombre']) ?></h1>
                <p><?= htmlspecialchars($abono['descripcion']) ?></p>
                <div class="abono-price"><?= $precio_formateado ?></div>
            </div>

            <form action="procesar_compra_abono.php" method="POST">
                <input type="hidden" name="tipo_abono" value="<?= htmlspecialchars($tipo_codigo) ?>">
                <input type="hidden" name="precio" value="<?= $abono['precio'] ?>">

                <div class="payment-header">
                    <h3><i class="fa-solid fa-credit-card"></i> Detalles de Pago</h3>
                    <div class="card-icons">
                        <i class="fa-brands fa-cc-visa brand-visa"></i>
                        <i class="fa-brands fa-cc-mastercard brand-mastercard"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Titular de la tarjeta</label>
                    <input type="text" name="titular" placeholder="Nombre completo" required>
                </div>

                <div class="form-group">
                    <label>Número de tarjeta</label>
                    <input type="text" name="tarjeta" placeholder="0000 0000 0000 0000" maxlength="16" required>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form-group expand">
                        <label>Caducidad</label>
                        <input type="text" name="caducidad" placeholder="MM/AA" maxlength="5" required>
                    </div>
                    <div class="form-group expand">
                        <label>CVV</label>
                        <input type="password" name="cvv" placeholder="123" maxlength="3" required>
                    </div>
                </div>

                <button type="submit" class="btn-pay-confirm" style="width: 100%; margin-top: 20px; padding: 15px; font-size: 1.1rem; background: #0a2a66; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    Pagar <?= $precio_formateado ?>
                </button>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-bottom">&copy; 2026 TrainWeb</div>
    </footer>

</body>
</html>
