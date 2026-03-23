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
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/session_menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- HEADER -->
    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb</div>
        <nav class="nav">
            <a href="billetes_web.php">Billetes</a>
            <div class="dropdown">
                <a href="#">Idiomas <i class="fa-solid fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="#">Español</a>
                    <a href="#">Inglés</a>
                    <a href="#">Francés</a>
                    <a href="#">Alemán</a>
                </div>
            </div>
            <a href="ofertas.php">Ofertas</a>
            <a href="ayuda.php">Ayuda</a>
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
                <a href="inicio_sesion.html" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- MAIN -->
    <main class="main">

        <!-- BUSCADOR -->
        <section class="search-section">
            <div class="search-bg"></div>
            <h1>Busca tu tren</h1>
            <form action="compra.php" method="GET" class="search-form">
                <!-- Tipo de viaje -->
                <div class="trip-type">
                    <label>
                        <input type="radio" name="trip" value="oneway" checked>
                        Solo ida
                    </label>
                    <label>
                        <input type="radio" name="trip" value="roundtrip">
                        Ida y vuelta
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
                    <option value="1">1 pasajero</option>
                    <option value="2">2 pasajeros</option>
                    <option value="3">3 pasajeros</option>
                    <option value="4">4 pasajeros</option>
                </select>
                <button type="submit">Buscar billetes</button>
            </form>
        </section>

        <!-- DESTINOS POPULARES -->
        <section class="popular-section">
            <h2>Destinos Populares</h2>
            <div class="carousel">
                <button class="prev"><i class="fa-solid fa-chevron-left"></i></button>
                <div class="popular-track">
                    <div class="popular-card">
                        <img src="imagenes/madrid.webp" alt="Madrid">
                        <div class="popular-content">
                            <h3>Madrid</h3>
                            <p>Capital vibrante con conexiones a todo el país.</p>
                            <a href="rutas_destino.php?destino=Madrid" class="btn-popular">Ver rutas</a>
                        </div>
                    </div>

                    <div class="popular-card">
                        <img src="imagenes/barcelona.jpeg" alt="Barcelona">
                        <div class="popular-content">
                            <h3>Barcelona</h3>
                            <p>Rutas rápidas y vistas espectaculares al Mediterráneo.</p>
                            <a href="rutas_destino.php?destino=Barcelona" class="btn-popular">Ver rutas</a>
                        </div>
                    </div>

                    <div class="popular-card">
                        <img src="imagenes/sevilla.webp" alt="Sevilla">
                        <div class="popular-content">
                            <h3>Sevilla</h3>
                            <p>Cultura, historia y gastronomía en cada estación.</p>
                            <a href="rutas_destino.php?destino=Sevilla" class="btn-popular">Ver rutas</a>
                        </div>
                    </div>

                    <div class="popular-card">
                        <img src="imagenes/valencia.jpg" alt="Valencia">
                        <div class="popular-content">
                            <h3>Valencia</h3>
                            <p>Costa mediterránea y ciudades modernas conectadas por tren.</p>
                            <a href="rutas_destino.php?destino=Valencia" class="btn-popular">Ver rutas</a>
                        </div>
                    </div>
                </div>
                <button class="next"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </section>

        <!-- ABONOS Y PROMOCIONES -->
        <section class="offers-container">
            <h2 class="section-title">Abonos y Promociones</h2>
            
            <div class="carousel-wrapper">
                <button class="carousel-btn prev"><i class="fa-solid fa-chevron-left"></i></button>
                
                <div class="offers-track">
                    <?php foreach ($abonos_index as $abono): ?>
                        <div class="offer-card abono-card">
                            <div class="card-icon">
                                <i class="<?= htmlspecialchars($abono['icono'] ?? 'fa-solid fa-ticket') ?>"></i>
                            </div>
                            <h3 class="card-title"><?= htmlspecialchars($abono['nombre']) ?></h3>
                            <p class="card-desc"><?= htmlspecialchars($abono['descripcion']) ?></p>
                            <div class="card-price">
                                <?= number_format($abono['precio'], 2, ',', '.') ?><span>€</span>
                            </div>
                            <a href="comprar_abono.php?tipo=<?= urlencode($abono['tipo_codigo']) ?>" class="btn-buy">Comprar ahora</a>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($promociones_index as $promo): ?>
                        <div class="offer-card promo-card">
                            <div class="card-icon">
                                <i class="fa-solid fa-tag"></i>
                            </div>
                            <div class="promo-badge">-<?= floatval($promo['descuento_porcentaje']) ?>%</div>
                            <p class="promo-code-label">Usa el código:</p>
                            <div class="promo-code"><?= htmlspecialchars($promo['codigo']) ?></div>
                            <p class="promo-expiry">Válido hasta: <span><?= date('d/m/Y', strtotime($promo['fecha_fin'])) ?></span></p>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($promociones_index) && empty($abonos_index)): ?>
                        <div class="empty-msg">
                            <i class="fa-solid fa-circle-info"></i>
                            <p>Actualmente no hay ofertas disponibles. ¡Vuelve pronto!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <button class="carousel-btn next"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>TrainWeb</h3>
                <p>Plataforma digital para la búsqueda y compra de billetes de tren en todo el territorio nacional.</p>
            </div>
            <div class="footer-column">
                <h4>Servicios</h4>
                <a href="#"><i class="fa-solid fa-ticket"></i> Billetes</a>
                <a href="#"><i class="fa-solid fa-clock"></i> Horarios</a>
                <a href="ofertas.html"><i class="fa-solid fa-tags"></i> Ofertas</a>
                <a href="#"><i class="fa-solid fa-headset"></i> Atención al cliente</a>
            </div>
            <div class="footer-column">
                <h4>Información legal</h4>
                <a href="#"><i class="fa-solid fa-scale-balanced"></i> Aviso legal</a>
                <a href="#"><i class="fa-solid fa-user-shield"></i> Privacidad</a>
                <a href="#"><i class="fa-solid fa-cookie-bite"></i> Cookies</a>
                <a href="#"><i class="fa-solid fa-file-contract"></i> Términos y condiciones</a>
            </div>
            <div class="footer-column">
                <h4>Redes sociales</h4>
                <a href="#"><i class="fa-brands fa-facebook-f"></i> Facebook</a>
                <a href="#"><i class="fa-brands fa-x-twitter"></i> Twitter</a>
                <a href="#"><i class="fa-brands fa-instagram"></i> Instagram</a>
                <a href="#"><i class="fa-brands fa-linkedin-in"></i> LinkedIn</a>
            </div>
        </div>
        <div class="footer-bottom">© 2026 TrainWeb · Todos los derechos reservados</div>
    </footer>
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






