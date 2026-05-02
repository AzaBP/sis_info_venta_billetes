<?php
session_start();
$usuario = $_SESSION['usuario'] ?? null;
if ($usuario && ($usuario['tipo_usuario'] ?? '') === 'empleado') {
    require_once __DIR__ . '/php/auth_helpers.php';
    header('Location: ' . trainwebRutaPorRol($usuario));
    exit;
}

$error = trim($_GET['error'] ?? '');
$attempt = trim($_GET['attempt'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TrainWeb - Acceso Empleados</title>
    <link rel="stylesheet" href="css/inicio_sesion.css">
    <link rel="stylesheet" href="css/session_menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .header {
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        body {
            background: radial-gradient(circle at 20% 20%, rgba(0,217,255,0.08), transparent 40%),
                        radial-gradient(circle at 80% 0%, rgba(13,110,253,0.08), transparent 45%),
                        #f6f8fb;
        }
        .logo {
            position: relative;
            transition: text-shadow 0.3s ease, filter 0.3s ease;
        }
        .logo:hover {
            text-shadow: 0 0 6px rgba(0, 217, 255, 0.9), 0 0 14px rgba(0, 217, 255, 0.6), 0 0 28px rgba(0, 217, 255, 0.35);
            filter: drop-shadow(0 0 6px rgba(0, 217, 255, 0.5));
        }
        .login-card {
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            border: 1px solid rgba(13, 110, 253, 0.12);
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }
        .login-card:hover {
            box-shadow: 0 0 16px rgba(0, 217, 255, 0.5), 0 0 32px rgba(13, 110, 253, 0.35);
            transform: translateY(-2px);
        }
        .btn-login.primary {
            position: relative;
            overflow: hidden;
            transition: box-shadow 0.3s ease, transform 0.2s ease;
        }
        .btn-login.primary:hover {
            box-shadow: 0 0 10px rgba(0, 217, 255, 0.8), 0 0 24px rgba(13, 110, 253, 0.6);
            transform: translateY(-1px);
        }
        .btn-login.primary::after {
            content: "";
            position: absolute;
            inset: -2px;
            border-radius: 10px;
            background: linear-gradient(120deg, rgba(0,217,255,0.4), rgba(13,110,253,0.25), transparent 60%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        .btn-login.primary:hover::after {
            opacity: 1;
        }
        .login-form input:focus {
            outline: none;
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.15), 0 0 10px rgba(0,217,255,0.25);
        }
        .login-info h1:hover {
            text-shadow: 0 0 8px rgba(0,217,255,0.35);
        }
        .led-strip {
            position: relative;
            margin: 16px 0 6px;
            padding: 12px 14px;
            border-radius: 10px;
            background: linear-gradient(90deg, rgba(10,42,102,0.08), rgba(0,217,255,0.12), rgba(10,42,102,0.08));
            border: 1px solid rgba(0,217,255,0.2);
        }
        .led-strip p {
            margin: 0;
            font-weight: 600;
            color: #0a2a66;
            text-shadow: 0 0 6px rgba(0,217,255,0.2);
        }
        .login-info ul {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <header class="header">
        <a href="index.php" class="logo"><i class="fa-solid fa-train"></i> TrainWeb</a>
        <nav class="nav"></nav>
        <div class="user-actions" id="userActions"></div>
    </header>

    <main class="main">
        <section class="login-wrap">
            <div class="login-card">
                <div class="login-info">
                    <h1>Acceso de empleados</h1>
                    <p>Inicia sesion con tus credenciales de empleado.</p>
                    <ul>
                        <li>Panel de vendedor, mantenimiento o maquinista</li>
                        <li>Acceso restringido por rol</li>
                        <li>Seguridad interna</li>
                    </ul>
                    <div class="led-strip">
                        <p>Acceso operativo seguro y directo.</p>
                    </div>
                    <?php if ($error !== '' && $error !== 'metodo'): ?>
                        <p style="color:#b00020; font-weight:600;">Credenciales no validas o acceso no permitido.</p>
                    <?php endif; ?>
                </div>

                <div class="login-form-container">
                    <form class="login-form" method="POST" action="procesar_empleado_login.php">
                        <label for="username">Correo</label>
                        <input type="text" id="username" name="username" placeholder="empleado@correo.com" required>

                        <label for="password">Contrasena</label>
                        <input type="password" id="password" name="password" placeholder="********" required>

                        <div class="form-actions">
                            <label class="remember"><input type="checkbox" name="remember"> Recuerdame</label>
                        </div>

                        <button type="submit" class="btn-login primary">Iniciar sesion</button>
                    </form>
                </div>
            </div>
        </section>
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
</body>
</html>



