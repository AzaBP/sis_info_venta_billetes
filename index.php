<?php
session_start();
require_once __DIR__ . '/php/auth_helpers.php';
require_once __DIR__ . '/php/Conexion.php'; 

$usuarioSesion = $_SESSION['usuario'] ?? null;
if ($usuarioSesion && ($usuarioSesion['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($usuarioSesion));
    exit;
}
$nombreSesion = $usuarioSesion['nombre'] ?? '';

// --- NUEVO CÓDIGO: OBTENER ABONOS Y PROMOCIONES ---
$abonos_index = [];
$promociones_index = [];

try {
    $pdo = (new Conexion())->conectar();
    if ($pdo) {
        // 1. Obtener Abonos
        $stmtAbonos = $pdo->query("SELECT * FROM TIPO_ABONO ORDER BY precio ASC");
        $abonos_index = $stmtAbonos->fetchAll(PDO::FETCH_ASSOC);

        // 2. Obtener Promociones activas
        $stmtPromos = $pdo->query("SELECT codigo, descuento_porcentaje, fecha_fin FROM PROMOCION WHERE fecha_fin >= CURRENT_DATE");
        $promociones_index = $stmtPromos->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Si hay error, los arrays se quedan vacíos y el carrusel no se rompe
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TrainWeb - Página de Trenes</title>
    <link rel="stylesheet" href="css/index.css?v=<?php echo @filemtime(__DIR__ . '/css/index.css'); ?>">
    <link rel="stylesheet" href="css/session_menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- HEADER -->
    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb</div>
        <nav class="nav">
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
                        <a href="perfil_pasajero.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
                        <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="inicio_sesion.html" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> <span data-i18n="iniciar_sesion">Iniciar sesión</span></a>
            <?php endif; ?>
        </div>
    </header>

    <!-- MAIN -->
    <main class="main">

        <!-- BUSCADOR -->
        <section class="search-section">
            <div class="search-bg"></div>
            <h1 data-i18n="busca_tren">Busca tu tren</h1>
            <form action="compra.php" method="GET" class="search-form">
                <!-- Tipo de viaje -->
                <div class="trip-type">
                    <label>
                        <input type="radio" name="trip" value="oneway" checked>
                        <span data-i18n="solo_ida">Solo ida</span>
                    </label>
                    <label>
                        <input type="radio" name="trip" value="roundtrip">
                        <span data-i18n="ida_vuelta">Ida y vuelta</span>
                    </label>
                </div>

                <!-- Origen -->
                <div class="input-group">
                    <input type="text" id="origen" name="origen" placeholder="Origen" autocomplete="off">
                    <div class="suggestions" id="suggestions-origen"></div>
                </div>

                <!-- Destino -->
                <div class="input-group">
                    <input type="text" id="destino" name="destino" placeholder="Destino" autocomplete="off">
                    <div class="suggestions" id="suggestions-destino"></div>
                </div>

                <!-- Fechas dinámicas -->
                <div class="date-type" id="date-container">
                    <input type="date" id="fecha-ida" name="fecha" required>
                </div>

                <select name="pasajeros">
                    <option value="1" data-i18n="pasajero_1">1 pasajero</option>
                    <option value="2" data-i18n="pasajero_2">2 pasajeros</option>
                    <option value="3" data-i18n="pasajero_3">3 pasajeros</option>
                    <option value="4" data-i18n="pasajero_4">4 pasajeros</option>
                </select>
                <button type="submit" data-i18n="buscar_billetes">Buscar billetes</button>
            </form>
        </section>

        <!-- DESTINOS POPULARES -->
        <section class="popular-section">
            <h2 data-i18n="destinos_populares">Destinos Populares</h2>
            <div class="carousel">
                <button class="prev"><i class="fa-solid fa-chevron-left"></i></button>
                <div class="popular-track">
                    <div class="popular-card">
                        <img src="imagenes/madrid.webp" alt="Madrid">
                        <div class="popular-content">
                            <h3>Madrid</h3>
                            <p data-i18n="desc_madrid">Capital vibrante con conexiones a todo el país.</p>
                            <a href="rutas_destino.php?destino=Madrid" class="btn-popular" data-i18n="ver_rutas">Ver rutas</a>
                        </div>
                    </div>

                    <div class="popular-card">
                        <img src="imagenes/barcelona.jpeg" alt="Barcelona">
                        <div class="popular-content">
                            <h3>Barcelona</h3>
                            <p data-i18n="desc_barcelona">Rutas rápidas y vistas espectaculares al Mediterráneo.</p>
                            <a href="rutas_destino.php?destino=Barcelona" class="btn-popular" data-i18n="ver_rutas">Ver rutas</a>
                        </div>
                    </div>

                    <div class="popular-card">
                        <img src="imagenes/sevilla.webp" alt="Sevilla">
                        <div class="popular-content">
                            <h3>Sevilla</h3>
                            <p data-i18n="desc_sevilla">Cultura, historia y gastronomía en cada estación.</p>
                            <a href="rutas_destino.php?destino=Sevilla" class="btn-popular" data-i18n="ver_rutas">Ver rutas</a>
                        </div>
                    </div>

                    <div class="popular-card">
                        <img src="imagenes/valencia.jpg" alt="Valencia">
                        <div class="popular-content">
                            <h3>Valencia</h3>
                            <p data-i18n="desc_valencia">Costa mediterránea y ciudades modernas conectadas por tren.</p>
                            <a href="rutas_destino.php?destino=Valencia" class="btn-popular" data-i18n="ver_rutas">Ver rutas</a>
                        </div>
                    </div>
                </div>
                <button class="next"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </section>

        <!-- OFERTAS / PROMOCIONES -->
        <section class="offers-section">
            <h2 data-i18n="abonos_promociones">Abonos y Promociones</h2>
            <div class="carousel">
                <button class="prev"><i class="fa-solid fa-chevron-left"></i></button>
                <div class="offers-track">

                    <?php foreach ($abonos_index as $abono): ?>
                        <div class="offer-card" style="background: #fff; border-top: 5px solid #0a2a66; padding: 20px; min-width: 250px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                            <i class="<?= htmlspecialchars($abono['icono'] ?? 'fa-solid fa-ticket') ?>" style="font-size: 2rem; color: #0a2a66; margin-bottom: 10px;"></i>
                            <h3 style="margin: 5px 0;"><?= htmlspecialchars($abono['nombre']) ?></h3>
                            <p style="font-size: 0.9rem; color: #555; height: 40px; overflow: hidden;"><?= htmlspecialchars($abono['descripcion']) ?></p>
                            <div style="font-size: 1.5rem; font-weight: bold; color: #17632A; margin: 10px 0;">
                                <?= number_format($abono['precio'], 2, ',', '.') ?> €
                            </div>
                            <a href="comprar_abono.php?tipo=<?= urlencode($abono['tipo_codigo']) ?>" class="btn-popular" data-i18n="comprar" style="display: block; width: 100%; box-sizing: border-box;">Comprar</a>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($promociones_index) && empty($abonos_index)): ?>
                        <p style="padding: 20px;" data-i18n="sin_ofertas">Actualmente no hay ofertas disponibles. ¡Vuelve pronto!</p>
                    <?php endif; ?>

                </div>
                <button class="next"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </section>

        <section class="offers-section">
            <div class="carousel">
                <button class="prev"><i class="fa-solid fa-chevron-left"></i></button>
                <div class="offers-track">
                    
                    <?php foreach ($promociones_index as $promo): ?>
                        <div class="offer-card" style="background: #fdf5e6; border-left: 5px solid #f39c12; padding: 20px; min-width: 250px; border-radius: 8px; text-align: center;">
                            <i class="fa-solid fa-tag" style="font-size: 2rem; color: #f39c12; margin-bottom: 10px;"></i>
                            <h3 class="promo-discount" data-discount="<?= floatval($promo['descuento_porcentaje']) ?>" style="margin: 5px 0;">-<?= floatval($promo['descuento_porcentaje']) ?>% Dto.</h3>
                            <p style="font-size: 0.9rem;"><span data-i18n="usa_codigo">Usa el código</span>:<br><strong style="font-size: 1.2rem; background: #fff; padding: 5px; border-radius: 4px; border: 1px dashed #f39c12; display: inline-block; margin-top: 5px;"><?= htmlspecialchars($promo['codigo']) ?></strong></p>
                            <p style="font-size: 0.8rem; color: #666; margin-bottom: 0;"><span data-i18n="valido_hasta">Válido hasta</span>: <?= date('d/m/Y', strtotime($promo['fecha_fin'])) ?></p>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($promociones_index) && empty($abonos_index)): ?>
                        <p style="padding: 20px;" data-i18n="sin_ofertas">Actualmente no hay ofertas disponibles. ¡Vuelve pronto!</p>
                    <?php endif; ?>

                </div>
                <button class="next"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>TrainWeb</h3>
                <p data-i18n="footer_descripcion">Plataforma digital para la búsqueda y compra de billetes de tren en todo el territorio nacional.</p>
            </div>
            <div class="footer-column">
                <h4 data-i18n="footer-services">Servicios</h4>
                <a href="#"><i class="fa-solid fa-ticket"></i> <span data-i18n="footer-billetes">Billetes</span></a>
                <a href="#"><i class="fa-solid fa-clock"></i> <span data-i18n="footer-horarios">Horarios</span></a>
                <a href="ofertas.html"><i class="fa-solid fa-tags"></i> <span data-i18n="footer-ofertas">Ofertas</span></a>
                <a href="#"><i class="fa-solid fa-headset"></i> <span data-i18n="footer-atencion">Atención al cliente</span></a>
            </div>
            <div class="footer-column">
                <h4 data-i18n="footer-legal">Información legal</h4>
                <a href="#"><i class="fa-solid fa-scale-balanced"></i> <span data-i18n="footer-aviso">Aviso legal</span></a>
                <a href="#"><i class="fa-solid fa-user-shield"></i> <span data-i18n="footer-privacidad">Privacidad</span></a>
                <a href="#"><i class="fa-solid fa-cookie-bite"></i> <span data-i18n="footer-cookies">Cookies</span></a>
                <a href="#"><i class="fa-solid fa-file-contract"></i> <span data-i18n="footer-terminos">Términos y condiciones</span></a>
            </div>
            <div class="footer-column">
                <h4 data-i18n="footer-social">Redes sociales</h4>
                <a href="#"><i class="fa-brands fa-facebook-f"></i> Facebook</a>
                <a href="#"><i class="fa-brands fa-x-twitter"></i> Twitter</a>
                <a href="#"><i class="fa-brands fa-instagram"></i> Instagram</a>
                <a href="#"><i class="fa-brands fa-linkedin-in"></i> LinkedIn</a>
            </div>
        </div>
        <div class="footer-bottom" data-i18n="footer_copyright">© 2026 TrainWeb · Todos los derechos reservados</div>
    </footer>
    <script src="scripts/i18n_index.js?v=<?php echo @filemtime(__DIR__ . '/scripts/i18n_index.js'); ?>"></script>
    <script>
        const tracks = document.querySelectorAll('.offers-track, .popular-track');
        const nextBtns = document.querySelectorAll('.next');
        const prevBtns = document.querySelectorAll('.prev');
        tracks.forEach((track, i) => {
            const next = nextBtns[i];
            const prev = prevBtns[i];
            const cardWidth = track.querySelector('img').offsetWidth + 20;
            const cardsPerScroll = 3;
            next.addEventListener('click', () => track.scrollBy({left: cardWidth * cardsPerScroll, behavior:'smooth'}));
            prev.addEventListener('click', () => track.scrollBy({left: -cardWidth * cardsPerScroll, behavior:'smooth'}));
        });
    </script>
    <script src="scripts/session_menu.js"></script>
    <script src="scripts/index.js"></script>


</body>
</html>






