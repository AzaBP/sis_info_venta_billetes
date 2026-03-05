<?php
session_start();
// Si el usuario no ha iniciado sesión, podrías redirigirlo:
// if (!isset($_SESSION['usuario'])) { header('Location: inicio_sesion.html'); exit; }

// Obtenemos el tipo de abono de la URL (por defecto 'mensual' si no viene nada)
$tipo_abono = isset($_GET['tipo']) ? htmlspecialchars($_GET['tipo']) : 'mensual';

// Definimos precios base simulados según el tipo
$precios = [
    'mensual' => '40.00',
    'trimestral' => '100.00',
    'anual' => '350.00',
    'estudiante' => '25.00',
    'viajes_limitados' => '20.00'
];

// Nombre formateado para mostrar al usuario
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
        <div class="logo">
            <i class="fa-solid fa-train"></i> TrainWeb 
        </div>
        <nav class="nav">
            <a href="index.php">Inicio</a>
            <a href="ofertas.html">Ofertas y Abonos</a>
        </nav>
    </header>

    <main class="booking-container">
        <section class="payment-container" style="margin-top: 40px;">
            <div class="payment-header">
                <h2>Comprar Abono <?php echo $nombre_mostrar; ?></h2>
                <div class="card-icons">
                    <i class="fa-brands fa-cc-visa brand-visa"></i>
                    <i class="fa-brands fa-cc-mastercard brand-mastercard"></i>
                </div>
            </div>

            <div class="summary-box" style="background: #f4f6f8; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                <p style="margin: 0; font-size: 1.2rem;">Total a pagar: <strong><?php echo $precio_final; ?> €</strong></p>
                <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: #666;">El abono se activará inmediatamente después del pago.</p>
            </div>

            <form id="formCompraAbono">
                <input type="hidden" id="tipoAbono" value="<?php echo $tipo_abono; ?>">
                <input type="hidden" id="precioAbono" value="<?php echo $precio_final; ?>">

                <div class="form-group">
                    <label>Titular de la tarjeta</label>
                    <input type="text" required placeholder="Nombre como aparece en la tarjeta">
                </div>
                <div class="form-group">
                    <label>Número de tarjeta</label>
                    <input type="text" required placeholder="XXXX XXXX XXXX XXXX" maxlength="19">
                </div>
                <div style="display: flex; gap: 15px;">
                    <div class="form-group expand">
                        <label>Caducidad</label>
                        <input type="text" required placeholder="MM/AA">
                    </div>
                    <div class="form-group expand">
                        <label>CVV</label>
                        <input type="password" required placeholder="123" maxlength="3">
                    </div>
                </div>

                <div id="mensajeCompra" style="margin-bottom: 15px; font-weight: bold;"></div>

                <button type="submit" class="btn-pay-confirm" style="width: 100%; padding: 15px; background: #0a2a66; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1rem;">
                    Confirmar Pago
                </button>
            </form>
        </section>
    </main>

    <script src="js/comprar_abono.js"></script>
</body>
</html>