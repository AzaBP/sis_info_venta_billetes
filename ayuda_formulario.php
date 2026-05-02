<?php
session_start();
require_once __DIR__ . '/php/auth_helpers.php';
$usuarioSesion = $_SESSION['usuario'] ?? null;
if ($usuarioSesion && ($usuarioSesion['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($usuarioSesion));
    exit;
}
$nombreSesion = $usuarioSesion['nombre'] ?? '';

$enviado = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enviado = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TrainWeb - Formulario de Contacto</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/session_menu.css">
    <link rel="stylesheet" href="css/ayuda.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-container h1 {
            color: #0a2a66;
            margin-bottom: 10px;
            text-align: center;
        }
        .form-container p {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            border-color: #0a2a66;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: #0a2a66;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background-color: #061d4a;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #0a2a66;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .success-container {
            text-align: center;
            padding: 20px 0;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }
        .success-title {
            color: #0a2a66;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        .success-text {
            color: #555;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }
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

    <main class="help-main">
        <div class="form-container">
            <?php if ($enviado): ?>
                <div class="success-container">
                    <div class="success-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <h2 class="success-title" data-i18n="form_enviado_titulo">¡Formulario enviado con éxito!</h2>
                    <p class="success-text" data-i18n="form_enviado_desc">
                        Hemos recibido tu consulta correctamente. Nuestro equipo de atención al cliente revisará tu solicitud y te responderá brevemente en un plazo máximo de <strong>48 horas hábiles</strong>.
                    </p>
                    <a href="ayuda.php" class="btn-submit" style="display: inline-block; text-decoration: none;" data-i18n="volver_ayuda_btn">Volver a Ayuda</a>
                </div>
            <?php else: ?>
                <h1 data-i18n="formulario_contacto_h1">Formulario de Contacto</h1>
                <p data-i18n="formulario_contacto_p">Cuéntanos tu problema o duda y te responderemos lo antes posible.</p>
                
                <form action="ayuda_formulario.php" method="POST">
                    <div class="form-group">
                        <label for="nombre" data-i18n="form_nombre">Nombre completo</label>
                        <input type="text" id="nombre" name="nombre" required placeholder="Tu nombre..." data-i18n-placeholder="form_placeholder_nombre">
                    </div>
                    
                    <div class="form-group">
                        <label for="email" data-i18n="form_email">Correo electrónico</label>
                        <input type="email" id="email" name="email" required 
                               placeholder="tu@email.com" 
                               pattern="[^@\s]+@[^@\s]+\.[^@\s]+" 
                               title="Por favor, introduce un correo válido (ejemplo@dominio.com)"
                               data-i18n-placeholder="form_placeholder_email">
                    </div>
                    
                    <div class="form-group">
                        <label for="motivo" data-i18n="form_motivo">Motivo de la consulta</label>
                        <select id="motivo" name="motivo" required>
                            <option value="" disabled selected data-i18n="form_selecciona">Selecciona una opción</option>
                            <option value="reclamacion" data-i18n="motivo_reclamacion">Reclamación</option>
                            <option value="consulta" data-i18n="motivo_consulta">Consulta general</option>
                            <option value="incidencia" data-i18n="motivo_incidencia">Incidencia técnica</option>
                            <option value="sugerencia" data-i18n="motivo_sugerencia">Sugerencia</option>
                            <option value="otros" data-i18n="motivo_otros">Otros</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="mensaje" data-i18n="form_mensaje">Mensaje</label>
                        <textarea id="mensaje" name="mensaje" required 
                                  placeholder="Quiero poner un 10 a Azahara, Yousra y Chema por su gran trabajo." 
                                  data-i18n-placeholder="form_placeholder_mensaje"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit" data-i18n="enviar_formulario">Enviar formulario</button>
                </form>
                
                <a href="ayuda.php" class="back-link" data-i18n="volver_ayuda"><i class="fa-solid fa-arrow-left"></i> Volver a ayuda</a>
            <?php endif; ?>
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

    <script src="scripts/i18n.js"></script>
    <script src="scripts/session_menu.js"></script>
</body>
</html>
