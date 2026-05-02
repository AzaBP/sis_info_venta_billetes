<?php
session_start();
require_once __DIR__ . '/php/auth_helpers.php';
$usuarioSesion = $_SESSION['usuario'] ?? null;
if ($usuarioSesion && ($usuarioSesion['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($usuarioSesion));
    exit;
}
$nombreSesion = $usuarioSesion['nombre'] ?? '';
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TrainWeb - Ayuda y contacto</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/session_menu.css">
    <link rel="stylesheet" href="css/ayuda.css">
    <link rel="stylesheet" href="css/perfil_pasajero.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!--HEADER -->
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
        <!--BUSCADOR AYUDA-->
        <section class="help-hero">
            <div class="hero-content">
                <h1 data-i18n="ayuda_titulo">¿En qué podemos ayudarte?</h1>
                <p data-i18n="ayuda_desc">Busca soluciones rápidas a tus dudas sobre viajes, billetes y servicios.</p>
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" data-i18n="help_search_placeholder" placeholder="Ej: Cambiar billete, equipaje, mascotas...">
                    <button data-i18n="buscar">Buscar</button>
                </div>
            </div>
        </section>

        <!-- CONTENIDO AYUDA Y CONTACTO -->
        <div class="profile-container help-container">
            <!-- TEMAS FRECUENTES -->
            <h2 class="section-title" data-i18n="temas_frecuentes">Temas frecuentes</h2>
            <div class="topics-grid">
                <a href="#" class="topic-card">
                    <i class="fa-solid fa-ticket"></i>
                    <span data-i18n="tema_compra_cambio">Compra y Cambio</span>
                </a>
                <a href="#" class="topic-card">
                    <i class="fa-solid fa-suitcase-rolling"></i>
                    <span data-i18n="tema_equipajes">Equipajes</span>
                </a>
                <a href="#" class="topic-card">
                    <i class="fa-solid fa-dog"></i>
                    <span data-i18n="tema_mascotas">Mascotas</span>
                </a>
                <a href="#" class="topic-card">
                    <i class="fa-solid fa-wheelchair"></i>
                    <span data-i18n="tema_asistencia_pmr">Asistencia PMR</span>
                </a>
                <a href="#" class="topic-card">
                    <i class="fa-solid fa-train-subway"></i>
                    <span data-i18n="tema_estado_trenes">Estado de trenes</span>
                </a>
                <a href="#" class="topic-card">
                    <i class="fa-solid fa-file-invoice"></i>
                    <span data-i18n="tema_facturas">Facturas</span>
                </a>
            </div>
            <!-- PREGUNTAS FRECUENTES -->
            <h2 class="section-title" style="margin-top: 40px;" data-i18n="preguntas_frecuentes">Preguntas frecuentes</h2>
            <!--ACORDEON DE FRECUENTES-->
            <div class="profile-panel accordion-wrapper">
                <!-- PREGUNTA 1-->
                <div class="accordion-item">
                    <div class="accordion-header">
                        <div class="header-title" data-i18n="faq_q1">¿Cómo puedo anular mi billete?</div>
                        <i class="fa-solid fa-chevron-down arrow-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <p style="padding-bottom: 20px; color: #555;" data-i18n="faq_a1">
                            Puedes anular tu billete hasta 15 minutos antes de la salida del tren desde la sección "Mis Viajes" en tu área privada. Dependiendo de tu tarifa, podrían aplicarse gastos de anulación.
                        </p>
                    </div>
                </div>
                <!-- PREGUNTA 2-->
                <div class="accordion-item">
                    <div class="accordion-header">
                        <div class="header-title" data-i18n="faq_q2">¿Con cuánta antelación debo llegar a la estación?</div>
                        <i class="fa-solid fa-chevron-down arrow-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <p style="padding-bottom: 20px; color: #555;" data-i18n="faq_a2">
                            Recomendamos llegar al menos <strong>30 minutos antes</strong> de la salida para pasar los controles de seguridad con tranquilidad. El cierre de puertas se realiza 2 minutos antes de la hora de salida.
                        </p>
                    </div>
                </div>
                <!-- PREGUNTA 3-->
                <div class="accordion-item">
                    <div class="accordion-header">
                        <div class="header-title" data-i18n="faq_q3">Indemnizaciones por retraso</div>
                        <i class="fa-solid fa-chevron-down arrow-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <p style="padding-bottom: 20px; color: #555;" data-i18n="faq_a3">
                            Si tu tren llega con retraso superior a 15 minutos (AVE) o 30 minutos (Larga Distancia), tienes derecho a devolución parcial o total. Solicítalo automáticamente pasadas 24 horas de la llegada.
                        </p>
                    </div>
                </div>
            </div>

            <!-- CANALES DE CONTACTO -->
            <div class="contact-channels">
                <!-- ATENCIÓN TELEFÓNICA -->
                <div class="contact-box info-box">
                    <i class="fa-solid fa-phone-volume"></i>
                    <h3 data-i18n="contacto_telefonico">Atención Telefónica</h3>
                    <p class="phone-number">912 320 320</p>
                    <p class="small-text" data-i18n="contacto_horario">Lunes a Domingo: 24h</p>
                    <hr>
                    <p class="phone-number">900 878 333</p>
                    <p class="small-text" data-i18n="contacto_discapacidad">Atención a personas con discapacidad</p>
                </div>
                <!-- REDES SOCIALES -->
                <div class="contact-box social-box">
                    <i class="fa-brands fa-x-twitter"></i>
                    <h3 data-i18n="soporte_x_titulo">Soporte en X (Twitter)</h3>
                    <p data-i18n="soporte_x_desc">Escríbenos para consultas rápidas e incidencias en tiempo real.</p>
                    <a href="julio_apruebanos.php" class="btn-social">@TrainWeb_Ayuda</a>
                </div>
                <!-- FORMULARIO DE CONTACTO -->
                <div class="contact-box form-box">
                    <i class="fa-regular fa-envelope"></i>
                    <h3 data-i18n="formulario_quejas">Formulario / Quejas</h3>
                    <p data-i18n="formulario_desc">Para reclamaciones formales o consultas extensas, utiliza nuestro formulario.</p>
                    <a href="#" class="btn-primary" data-i18n="abrir_formulario">Abrir formulario</a>
                </div>
            </div>

        </div>
    </main>

    <!--FOOTER -->
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

    <!-- ACCORDION SCRIPT -->
    <script src="scripts/i18n.js?v=<?php echo @filemtime(__DIR__ . '/scripts/i18n.js'); ?>"></script>
    <script>
        const accordions = document.querySelectorAll('.accordion-header');
        accordions.forEach(header => {
            header.addEventListener('click', () => {
                const item = header.parentElement;
                item.classList.toggle('active');
            });
        });
    </script>
    <script src="scripts/session_menu.js"></script>

</body>
</html>

