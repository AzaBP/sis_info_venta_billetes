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
            <a href="index.php">Inicio</a>
            <a href="inicio_sesion.html">Iniciar Sesión</a>
            <a href="ayuda.php">Ayuda</a>
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
                        pattern="[A-Z0-9]{6}">
                    
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
                <p>Plataforma digital para la búsqueda y compra de billetes de tren.</p>
            </div>
            <div class="footer-column">
                <h4>Servicios</h4>
                <a href="#"><i class="fa-solid fa-ticket"></i> Billetes</a>
                <a href="#"><i class="fa-solid fa-tags"></i> Ofertas</a>
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
