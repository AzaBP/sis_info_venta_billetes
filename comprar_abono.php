<?php
session_start();
$usuarioSesion = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
$nombreSesion = isset($usuarioSesion['nombre']) ? $usuarioSesion['nombre'] : '';

require_once __DIR__ . '/php/auth_helpers.php';
if (isset($_SESSION['usuario']) && ($_SESSION['usuario']['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($_SESSION['usuario']));
    exit;
}

// 1. Recoger el tipo de abono de la URL (por defecto 'mensual' si alguien entra sin hacer clic)
$tipo_codigo = $_GET['tipo'] ?? 'mensual';

if (!$usuarioSesion) {
    header('Location: inicio_sesion.html?redirect=' . urlencode('comprar_abono.php?tipo=' . $tipo_codigo));
    exit;
}

require_once __DIR__ . '/php/Conexion.php';
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
        <a href="index.php" class="logo"><i class="fa-solid fa-train"></i> TrainWeb</a>
        <nav class="nav">
            <a href="index.php" data-i18n="inicio">Inicio</a>
            <a href="mis_billetes.php" data-i18n="mis_billetes">Mis billetes</a>
            <div class="dropdown">
                <a href="#"><i class="fa-solid fa-earth-europe"></i> <span data-i18n="idiomas">Idiomas</span> <i class="fa-solid fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="#" data-lang="es" data-i18n="es">Español</a>
                    <a href="#" data-lang="en" data-i18n="en">Inglés</a>
                    <a href="#" data-lang="fr" data-i18n="fr">Francés</a>
                    <a href="#" data-lang="de" data-i18n="de">Alemán</a>
                </div>
            </div>
            <a href="ofertas.php" data-i18n="ofertas">Ofertas</a>
            <a href="ayuda.php" data-i18n="ayuda">Ayuda</a>
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
                        <a href="perfil_pasajero.php"><i class="fa-solid fa-user"></i> <span data-i18n="mi_perfil">Mi perfil</span></a>
                        <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> <span data-i18n="cerrar_sesion">Cerrar sesión</span></a>
                    </div>
                </div>
            <?php else: ?>
                <a href="inicio_sesion.html" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> <span data-i18n="iniciar_sesion">Iniciar sesión</span></a>
            <?php endif; ?>
        </div>
    </header>

    <main class="payment-wrapper">
        <div class="payment-container">
            <div class="abono-header">
                <h1 data-i18n="abono_<?= strtolower($tipo_codigo) ?>_nombre"><?= htmlspecialchars($abono['nombre']) ?></h1>
                <p data-i18n="abono_<?= strtolower($tipo_codigo) ?>_desc"><?= htmlspecialchars($abono['descripcion']) ?></p>
                <div class="abono-price"><?= $precio_formateado ?></div>
            </div>

            <form class="payment-form" action="procesar_compra_abono.php" method="POST" autocomplete="off">
                <input type="hidden" name="tipo_abono" value="<?= htmlspecialchars($tipo_codigo) ?>">
                <input type="hidden" name="precio" value="<?= $abono['precio'] ?>">

                <div class="payment-header">
                    <h3><i class="fa-solid fa-credit-card"></i> <span data-i18n="detalles_pago">Detalles de Pago</span></h3>
                    <div class="card-icons">
                        <i class="fa-brands fa-cc-visa brand-visa"></i>
                        <i class="fa-brands fa-cc-mastercard brand-mastercard"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label data-i18n="titular_tarjeta">Titular de la tarjeta</label>
                    <input type="text" id="cardHolder" name="titular" placeholder="Nombre completo" data-i18n-placeholder="nombre_completo" required>
                    <span class="input-error" id="errCardHolder" style="display:none;"></span>
                </div>

                <div class="form-group">
                    <label data-i18n="numero_tarjeta">Número de tarjeta</label>
                    <input type="text" id="cardNumber" name="tarjeta" placeholder="0000 0000 0000 0000" data-i18n-placeholder="card_number_placeholder" maxlength="19" required>
                    <span class="input-error" id="errCardNumber" style="display:none;"></span>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form-group expand">
                        <label data-i18n="caducidad">Caducidad</label>
                        <input type="text" id="cardExpiry" name="caducidad" placeholder="MM/AA" data-i18n-placeholder="mm_aa" maxlength="5" required>
                        <span class="input-error" id="errCardExpiry" style="display:none;"></span>
                    </div>
                    <div class="form-group expand">
                        <label data-i18n="cvv">CVV</label>
                        <input type="password" id="cardCVV" name="cvv" placeholder="123" data-i18n-placeholder="cvv_placeholder" maxlength="3" required>
                        <span class="input-error" id="errCardCVV" style="display:none;"></span>
                    </div>
                </div>

                <button type="submit" class="btn-pay-confirm" style="width: 100%; margin-top: 20px; padding: 15px; font-size: 1.1rem; background: #0a2a66; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <span data-i18n="pagar">Pagar</span> <?= $precio_formateado ?>
                </button>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>TrainWeb</h3>
                <p data-i18n="footer_descripcion">Plataforma digital para la búsqueda y compra de billetes de tren en todo el territorio nacional.</p>
            </div>
            <div class="footer-column">
                <h4 data-i18n="footer_services">Servicios</h4>
                <a href="mis_billetes.php"><i class="fa-solid fa-ticket"></i> <span data-i18n="footer_billetes">Billetes</span></a>
                <a href="horarios.php"><i class="fa-solid fa-clock"></i> <span data-i18n="footer_horarios">Horarios</span></a>
                <a href="ofertas.php"><i class="fa-solid fa-tags"></i> <span data-i18n="footer_ofertas">Ofertas</span></a>
                <a href="ayuda.php"><i class="fa-solid fa-headset"></i> <span data-i18n="footer_atencion">Atención al cliente</span></a>
            </div>
            <div class="footer-column">
                <h4 data-i18n="footer_legal">Información legal</h4>
                <a href="aviso_legal.php"><i class="fa-solid fa-scale-balanced"></i> <span data-i18n="footer_aviso">Aviso legal</span></a>
                <a href="politica_privacidad.php"><i class="fa-solid fa-user-shield"></i> <span data-i18n="footer_privacidad">Privacidad</span></a>
                <a href="politica_cookies.php"><i class="fa-solid fa-cookie-bite"></i> <span data-i18n="footer_cookies">Cookies</span></a>
                <a href="terminos_y_condiciones.php"><i class="fa-solid fa-file-contract"></i> <span data-i18n="footer_terminos">Términos y condiciones</span></a>
            </div>
            <div class="footer-column">
                <h4 data-i18n="footer_social">Redes sociales</h4>
                <a href="julio_apruebanos.php"><i class="fa-brands fa-facebook-f"></i> Facebook</a>
                <a href="julio_apruebanos.php"><i class="fa-brands fa-x-twitter"></i> Twitter</a>
                <a href="julio_apruebanos.php"><i class="fa-brands fa-instagram"></i> Instagram</a>
                <a href="julio_apruebanos.php"><i class="fa-brands fa-linkedin-in"></i> LinkedIn</a>
            </div>
        </div>
        <div class="footer-bottom" data-i18n="footer_copyright">© 2026 TrainWeb · Todos los derechos reservados</div>
    </footer>

    <script src="scripts/i18n.js?v=<?php echo @filemtime(__DIR__ . '/scripts/i18n.js'); ?>"></script>
    <script src="scripts/session_menu.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const cardNumber = document.getElementById('cardNumber');
        const cardExpiry = document.getElementById('cardExpiry');
        const cardCVV = document.getElementById('cardCVV');
        const cardHolder = document.getElementById('cardHolder');
        const errCardNumber = document.getElementById('errCardNumber');
        const errCardExpiry = document.getElementById('errCardExpiry');
        const errCardCVV = document.getElementById('errCardCVV');
        const errCardHolder = document.getElementById('errCardHolder');
        const paymentForm = document.querySelector('.payment-form');

        function limpiarError(input, errorElement) {
            if (input) input.classList.remove('input-invalid');
            if (errorElement) {
                errorElement.style.display = 'none';
                errorElement.textContent = '';
            }
        }

        function mostrarError(input, errorElement, mensaje) {
            if (input) input.classList.add('input-invalid');
            if (errorElement) {
                errorElement.textContent = mensaje;
                errorElement.style.display = 'block';
            }
        }

        function validateCardNumber() {
            const value = cardNumber.value.replace(/\D/g, '');
            if (value === '') {
                mostrarError(cardNumber, errCardNumber, 'Introduce el número de tarjeta.');
                return false;
            }
            if (!/^\d{16}$/.test(value)) {
                mostrarError(cardNumber, errCardNumber, 'Introduce 16 dígitos válidos.');
                return false;
            }
            limpiarError(cardNumber, errCardNumber);
            return true;
        }

        function validateCardExpiry() {
            const value = cardExpiry.value;
            if (value === '') {
                mostrarError(cardExpiry, errCardExpiry, 'Introduce la fecha de caducidad.');
                return false;
            }
            if (!/^\d{2}\/\d{2}$/.test(value)) {
                mostrarError(cardExpiry, errCardExpiry, 'Formato MM/AA.');
                return false;
            }
            const [mes, anio] = value.split('/').map(Number);
            if (mes < 1 || mes > 12) {
                mostrarError(cardExpiry, errCardExpiry, 'Mes inválido.');
                return false;
            }
            const hoy = new Date();
            const expYear = 2000 + anio;
            const expDate = new Date(expYear, mes - 1, 1);
            if (expDate < new Date(hoy.getFullYear(), hoy.getMonth(), 1)) {
                mostrarError(cardExpiry, errCardExpiry, 'Tarjeta caducada.');
                return false;
            }
            limpiarError(cardExpiry, errCardExpiry);
            return true;
        }

        function validateCardCVV() {
            const value = cardCVV.value;
            if (value === '') {
                mostrarError(cardCVV, errCardCVV, 'Introduce el CVV.');
                return false;
            }
            if (!/^\d{3}$/.test(value)) {
                mostrarError(cardCVV, errCardCVV, 'CVV de 3 dígitos.');
                return false;
            }
            limpiarError(cardCVV, errCardCVV);
            return true;
        }

        function validateCardHolder() {
            const value = cardHolder.value.trim();
            if (value === '') {
                mostrarError(cardHolder, errCardHolder, 'Introduce el nombre y apellidos del titular.');
                return false;
            }
            if (value.length < 3) {
                mostrarError(cardHolder, errCardHolder, 'Introduce el nombre y apellidos del titular.');
                return false;
            }
            limpiarError(cardHolder, errCardHolder);
            return true;
        }

        cardNumber.addEventListener('input', function() {
            let value = cardNumber.value.replace(/\D/g, '');
            if (value.length > 16) value = value.slice(0, 16);
            cardNumber.value = value.replace(/(.{4})/g, '$1 ').trim();
        });

        cardExpiry.addEventListener('input', function() {
            let value = cardExpiry.value.replace(/[^\d]/g, '');
            if (value.length > 4) value = value.slice(0, 4);
            if (value.length > 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            cardExpiry.value = value;
        });

        cardCVV.addEventListener('input', function() {
            let value = cardCVV.value.replace(/\D/g, '');
            if (value.length > 3) value = value.slice(0, 3);
            cardCVV.value = value;
        });

        cardNumber.addEventListener('blur', validateCardNumber);
        cardExpiry.addEventListener('blur', validateCardExpiry);
        cardCVV.addEventListener('blur', validateCardCVV);
        cardHolder.addEventListener('blur', validateCardHolder);

        paymentForm.addEventListener('submit', function(e) {
            let valid = true;
            if (!validateCardHolder()) valid = false;
            if (!validateCardNumber()) valid = false;
            if (!validateCardExpiry()) valid = false;
            if (!validateCardCVV()) valid = false;
            if (!valid) {
                e.preventDefault();
            }
        });
    });
    </script>

</body>
</html>
