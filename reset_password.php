<?php
$email = $_GET['email'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña - TrainWeb</title>
    <link rel="stylesheet" href="css/recuperar_contrasena.css">
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
        <div class="recovery-wrap">
            <div class="recovery-card">
                <h2><i class="fa-solid fa-lock"></i> Restablecer contraseña</h2>
                <p>Introduce el código de verificación que recibiste por correo y tu nueva contraseña.</p>
                <p style="color: #666; font-size: 0.9rem;"><i class="fa-solid fa-info-circle"></i> Correo: <strong><?php echo htmlspecialchars($email); ?></strong></p>

                <?php if ($error === 'invalid'): ?>
                    <div class="error-message">
                        <i class="fa-solid fa-circle-exclamation"></i> Código inválido o expirado. Intenta de nuevo.
                    </div>
                <?php endif; ?>

                <form action="procesar_reset_password.php" method="post" class="recovery-form">
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
                        pattern="[A-Z0-9]{6}">
                    
                    <label for="password"><i class="fa-solid fa-lock"></i> Nueva contraseña</label>
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        placeholder="Mínimo 8 caracteres"
                        minlength="8"
                        required 
                        autocomplete="new-password">
                    
                    <div class="recovery-form-actions">
                        <button type="submit" class="btn-reset"><i class="fa-solid fa-save"></i> Cambiar contraseña</button>
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
                <a href="#"><i class="fa-solid fa-scale-balanced"></i> <span data-i18n="footer_aviso">Aviso legal</span></a>
                <a href="#"><i class="fa-solid fa-user-shield"></i> <span data-i18n="footer_privacidad">Privacidad</span></a>
                <a href="#"><i class="fa-solid fa-cookie-bite"></i> <span data-i18n="footer_cookies">Cookies</span></a>
                <a href="#"><i class="fa-solid fa-file-contract"></i> <span data-i18n="footer_terminos">Términos y condiciones</span></a>
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
