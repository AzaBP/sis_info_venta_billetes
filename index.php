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
<script>
    // Pasamos las promociones de PHP a JavaScript
    const promocionesDisponibles = <?php echo json_encode($promociones_index); ?>;
</script>

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
                        <?php
                        // Asignar imagen basada en el tipo de abono
                        $imagen = '';
                        switch (strtolower($abono['tipo_codigo'] ?? '')) {
                            case 'mensual':
                                $imagen = 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&h=200&q=80'; // Imagen de tarjeta mensual
                                break;
                            case 'anual':
                                $imagen = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&h=200&q=80'; // Imagen de abono anual
                                break;
                            case 'joven':
                                $imagen = 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&h=200&q=80'; // Imagen para jóvenes
                                break;
                            default:
                                $imagen = 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&h=200&q=80'; // Imagen genérica de tren
                        }
                        // Descripción extendida
                        $descripcion_extendida = htmlspecialchars($abono['descripcion']) . ' Ideal para viajes frecuentes con descuentos exclusivos y flexibilidad total.';
                        ?>
                        <div class="offer-card" style="background: #fff; border: 1px solid #ddd; border-radius: 12px; overflow: hidden; min-width: 280px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center;">
                            <img src="<?= $imagen ?>" alt="<?= htmlspecialchars($abono['nombre']) ?>" style="width: 100%; height: 150px; object-fit: cover;">
                            <div style="padding: 20px;">
                                <h3 style="margin: 0 0 10px 0; color: #0a2a66; font-size: 1.2rem;"><?= htmlspecialchars($abono['nombre']) ?></h3>
                                <p style="font-size: 0.9rem; color: #555; margin: 0 0 15px 0; line-height: 1.4;"><?= $descripcion_extendida ?></p>
                                <div style="font-size: 1.5rem; font-weight: bold; color: #17632A; margin-bottom: 15px;">
                                    <?= number_format($abono['precio'], 2, ',', '.') ?> €
                                </div>
                                <div style="display: flex; gap: 10px; justify-content: center;">
                                    <a href="ofertas.php" class="btn-info" style="background: #6c757d; color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem;">Más información</a>
                                    <a href="comprar_abono.php?tipo=<?= urlencode($abono['tipo_codigo']) ?>" class="btn-comprar" style="background: #0a2a66; color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem;">Comprar</a>
                                </div>
                            </div>
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
<div id="promo-popup-container"></div>

<script>
function mostrarPromoAleatoria() {
    if (!promocionesDisponibles || promocionesDisponibles.length === 0) return;

    const container = document.getElementById('promo-popup-container');
    
    // Elegimos una promo al azar
    const indice = Math.floor(Math.random() * promocionesDisponibles.length);
    const promo = promocionesDisponibles[indice];

    // Creamos el elemento
    const popup = document.createElement('div');
    popup.className = 'promo-popup';
    popup.innerHTML = `
        <span class="promo-close" onclick="this.parentElement.remove()">×</span>
        <div class="promo-header">¡Oferta Especial! 🚅</div>
        <div class="promo-body">
            Usa el código <strong>${promo.codigo}</strong> y obtén un 
            <strong>${promo.descuento_porcentaje}%</strong> de descuento.
        </div>
    `;

    container.appendChild(popup);

    // Se quita automáticamente a los 6 segundos
    setTimeout(() => {
        popup.classList.add('fade-out');
        setTimeout(() => popup.remove(), 500);
    }, 6000);
}

// Configuración de los "momentos random"
function programarSiguientePromo() {
    // Aparecerá entre cada 10 y 20 segundos para no agobiar
    const tiempoAleatorio = Math.floor(Math.random() * (20000 - 10000 + 1)) + 10000;
    
    setTimeout(() => {
        mostrarPromoAleatoria();
        programarSiguientePromo(); // Se vuelve a llamar a sí misma
    }, tiempoAleatorio);
}

// Empezar el ciclo a los 5 segundos de entrar en la web
if (promocionesDisponibles.length > 0) {
    setTimeout(programarSiguientePromo, 5000);
}
</script>

</body>
</html>






