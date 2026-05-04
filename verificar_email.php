<?php
// Formulario para introducir código de verificación
$email = $_GET['email'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar correo - TrainWeb</title>
    <link rel="stylesheet" href="css/verificar_email.css">
    <link rel="stylesheet" href="css/session_menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- HEADER -->
    <header class="header">
        <a href="index.php" class="logo"><i class="fa-solid fa-train"></i> TrainWeb</a>
        <nav class="nav">
            <a href="index.php" data-i18n="inicio">Inicio</a>
            <a href="inicio_sesion.html" data-i18n="iniciar_sesion">Iniciar Sesión</a>
            <a href="ayuda.php" data-i18n="ayuda">Ayuda</a>
        </nav>
    </header>

    <!-- MAIN -->
    <main class="main">
        <div class="verify-wrap">
            <div class="verify-card">
                <h2><i class="fa-solid fa-envelope-circle-check"></i> Verificar tu correo</h2>
                <p>Hemos enviado un código de verificación a:</p>
                <p class="email-highlight"><?php echo htmlspecialchars($email); ?></p>
                <p>Introduce el código que recibiste (válido 1 hora):</p>

                <?php if ($error === 'invalid'): ?>
                    <div class="error-message">
                        <i class="fa-solid fa-circle-exclamation"></i> El código es inválido o ha expirado. Intenta de nuevo.
                    </div>
                <?php endif; ?>

                <form action="procesar_verificacion_email.php" method="post" class="verify-form">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <label for="codigo"><i class="fa-solid fa-key"></i> Código de verificación</label>
                    <input 
                        type="text" 
                        id="codigo"
                        name="codigo" 
                        placeholder="XXXXXX" 
                        maxlength="6"
                        required 
                        autocomplete="off"
                        autocapitalize="characters"
                        pattern="[A-Za-z0-9]{6}">
                    
                    <div class="verify-form-actions">
                        <button type="submit" class="btn-verify"><i class="fa-solid fa-check"></i> Verificar</button>
                        <a href="inicio_sesion.html" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Volver</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
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

    <script src="scripts/session_menu.js"></script>
</body>
</html>
