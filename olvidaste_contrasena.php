<?php
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - TrainWeb</title>
    <link rel="stylesheet" href="css/recuperar_contrasena.css">
    <link rel="stylesheet" href="css/session_menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- HEADER -->
    <header class="header">
        <a href="index.php" class="logo"><i class="fa-solid fa-train"></i> TrainWeb</a>
        <nav class="nav">
            <a href="index.php">Inicio</a>
            <a href="inicio_sesion.html">Iniciar Sesión</a>
            <a href="ayuda.php">Ayuda</a>
        </nav>
    </header>

    <!-- MAIN -->
    <main class="main">
        <div class="recovery-wrap">
            <div class="recovery-card">
                <h2><i class="fa-solid fa-key"></i> Recuperar contraseña</h2>
                <p>Introduce tu correo electrónico registrado para recibir un código de recuperación.</p>

                <?php if ($error === 'no_encontrado'): ?>
                    <div class="error-message">
                        <i class="fa-solid fa-circle-exclamation"></i> No encontramos un usuario con este correo.
                    </div>
                <?php endif; ?>

                <?php if ($error === 'envio'): ?>
                    <div class="error-message">
                        <i class="fa-solid fa-circle-exclamation"></i> No se pudo enviar el correo. Revisa la configuración SMTP e inténtalo de nuevo.
                    </div>
                <?php endif; ?>

                <?php if ($error === 'smtp_config'): ?>
                    <div class="error-message">
                        <i class="fa-solid fa-triangle-exclamation"></i> Falta configurar SMTP con valores reales en `.env` o en Docker Compose.
                    </div>
                <?php endif; ?>

                <?php if ($error === 'conexion'): ?>
                    <div class="error-message">
                        <i class="fa-solid fa-database"></i> Error de conexión con la base de datos. Inténtalo más tarde.
                    </div>
                <?php endif; ?>

                <form action="procesar_forgot_password.php" method="post" class="recovery-form">
                    <label for="email"><i class="fa-solid fa-envelope"></i> Correo electrónico</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        placeholder="tu@correo.com"
                        required 
                        autocomplete="email">
                    
                    <div class="recovery-form-actions">
                        <button type="submit" class="btn-send"><i class="fa-solid fa-paper-plane"></i> Enviar código</button>
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
                <p>Plataforma digital para la búsqueda y compra de billetes de tren.</p>
            </div>
            <div class="footer-column">
                <h4 data-i18n="footer_services">Servicios</h4>
                <a href="mis_billetes.php"><i class="fa-solid fa-ticket"></i> <span data-i18n="footer_billetes">Billetes</span></a>
                <a href="horarios.php"><i class="fa-solid fa-clock"></i> <span data-i18n="footer_horarios">Horarios</span></a>
                <a href="ofertas.php"><i class="fa-solid fa-tags"></i> <span data-i18n="footer_ofertas">Ofertas</span></a>
            </div>
            <div class="footer-column">
                <h4>Información legal</h4>
                <a href="#"><i class="fa-solid fa-shield"></i> Privacidad</a>
                <a href="#"><i class="fa-solid fa-file-contract"></i> Términos</a>
            </div>
            <div class="footer-column">
                <h4>Soporte</h4>
                <a href="ayuda.php"><i class="fa-solid fa-question"></i> Ayuda</a>
                <a href="mailto:soporte@trainweb.es"><i class="fa-solid fa-envelope"></i> Contacto</a>
            </div>
        </div>
        <div class="footer-bottom">© 2026 TrainWeb · Todos los derechos reservados</div>
    </footer>

    <script src="scripts/session_menu.js"></script>
</body>
</html>
