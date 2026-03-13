<?php
// 1. Iniciamos la sesiÃ³n
session_start();
require_once __DIR__ . '/auth_helpers.php';
if (isset($_SESSION['usuario']) && ($_SESSION['usuario']['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($_SESSION['usuario']));
    exit;
}


// 2. Definimos las variables de sesiÃ³n SIEMPRE, para que nunca den error
$usuarioSesion = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
$nombreSesion = isset($usuarioSesion['nombre']) ? $usuarioSesion['nombre'] : '';

// 3. Si el usuario no estÃ¡ logueado, lo mandamos a iniciar sesiÃ³n y cortamos la ejecuciÃ³n
if (!$usuarioSesion) {
    header('Location: inicio_sesion.html');
    exit;
}

// 4. ConexiÃ³n a BD y lÃ³gica de abonos
require_once __DIR__ . '/php/Conexion.php';

$tipo_abono = isset($_GET['tipo']) ? htmlspecialchars($_GET['tipo']) : 'mensual';

$precios = [
    'mensual' => '40.00',
    'trimestral' => '100.00',
    'anual' => '350.00',
    'estudiante' => '25.00',
    'viajes_limitados' => '20.00'
];

$nombre_mostrar = ucfirst(str_replace('_', ' ', $tipo_abono));
$precio_final = isset($precios[$tipo_abono]) ? $precios[$tipo_abono] : '0.00';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprar Abono - TrainWeb</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/compra.css"> 
</head>
<body>
    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb</div>
        <nav class="nav">
            <a href="index.php">Inicio</a>
            <a href="compra.html">Billetes</a>
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
                        <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesiÃ³n</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="inicio_sesion.html" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> Iniciar sesiÃ³n</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="booking-container">
        <section class="payment-container" style="margin-top: 40px; max-width: 600px; margin-left: auto; margin-right: auto;">
            <div class="payment-header">
                <h2>Comprar Abono <?php echo $nombre_mostrar; ?></h2>
                <div class="card-icons">
                    <i class="fa-brands fa-cc-visa brand-visa"></i>
                    <i class="fa-brands fa-cc-mastercard brand-mastercard"></i>
                </div>
            </div>

            <div class="summary-box" style="background: #f4f6f8; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                <p style="margin: 0; font-size: 1.2rem;">Total a pagar: <strong><?php echo $precio_final; ?> â‚¬</strong></p>
                <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: #666;">El abono se activarÃ¡ inmediatamente despuÃ©s del pago.</p>
            </div>

            <form action="procesar_compra_abono.php" method="POST">
                
                <input type="hidden" name="tipo_abono" value="<?php echo $tipo_abono; ?>">
                <input type="hidden" name="precio" value="<?php echo $precio_final; ?>">

                <div class="form-group">
                    <label>Titular de la tarjeta</label>
                    <input type="text" name="titular" required placeholder="Nombre como aparece en la tarjeta">
                </div>
                
                <div class="form-group">
                    <label>NÃºmero de tarjeta</label>
                    <input type="text" name="tarjeta" required placeholder="XXXX XXXX XXXX XXXX" maxlength="19">
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <div class="form-group expand">
                        <label>Caducidad</label>
                        <input type="text" name="caducidad" required placeholder="MM/AA" maxlength="5">
                    </div>
                    <div class="form-group expand">
                        <label>CVV</label>
                        <input type="password" name="cvv" required placeholder="123" maxlength="3">
                    </div>
                </div>

                <button type="submit" class="btn-pay-confirm" style="width: 100%; padding: 15px; margin-top: 10px; background: #0a2a66; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1rem; font-weight: bold;">
                    Pagar <?php echo $precio_final; ?> â‚¬
                </button>
            </form>
        </section>
    </main>
</body>
</html>

