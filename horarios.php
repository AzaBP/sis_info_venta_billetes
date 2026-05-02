<?php
session_start();
require_once __DIR__ . '/php/auth_helpers.php';
require_once __DIR__ . '/php/Conexion.php';

// Obtener los datos de sesión para el Header
$usuarioSesion = $_SESSION['usuario'] ?? null;
$nombreSesion = $usuarioSesion['nombre'] ?? '';

$viajes = [];

try {
    $pdo = (new Conexion())->conectar();
    if ($pdo) {
        // Consultar los próximos 6 viajes disponibles
        $sql = "SELECT v.id_viaje, v.fecha, v.hora_salida, v.hora_llegada, v.precio,
                       r.origen, r.destino
                FROM VIAJE v
                JOIN RUTA r ON v.id_ruta = r.id_ruta
                WHERE v.estado <> 'cancelado' 
                  AND (v.fecha > CURRENT_DATE OR (v.fecha = CURRENT_DATE AND v.hora_salida > CURRENT_TIME))
                ORDER BY v.fecha ASC, v.hora_salida ASC
                LIMIT 6";
        
        $stmt = $pdo->query($sql);
        $viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Si falla la BD, el array se queda vacío
}

/**
 * Calcula la diferencia entre dos horas en formato HH:MM:SS
 */
function calcularDuracion($salida, $llegada) {
    $start = strtotime($salida);
    $end = strtotime($llegada);
    if ($end < $start) $end += 86400; // Caso de llegada al día siguiente
    $diff = $end - $start;
    $h = floor($diff / 3600);
    $m = floor(($diff % 3600) / 60);
    return "{$h}h {$m}m";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Horarios y Rutas</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/session_menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Outfit', sans-serif;
        }

        .schedules-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
            background: linear-gradient(135deg, #0a2a66 0%, #1e3799 100%);
            color: white;
            padding: 60px 20px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            font-size: 2.8rem;
            margin-bottom: 15px;
        }

        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .schedules-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        .schedules-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .schedules-table th {
            text-align: left;
            padding: 15px;
            color: #64748b;
            font-weight: 600;
            border-bottom: 2px solid #f1f5f9;
        }

        .schedules-table td {
            padding: 20px 15px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .schedules-table tr:last-child td {
            border-bottom: none;
        }

        .route-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .route-icon {
            width: 40px;
            height: 40px;
            background: #eff6ff;
            color: #3b82f6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .time-box {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .duration-tag {
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #64748b;
        }

        .price-text {
            font-weight: 700;
            color: #0a2a66;
            font-size: 1.2rem;
        }

        .btn-view {
            padding: 8px 16px;
            background: #0a2a66;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-view:hover {
            background: #1e3799;
        }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>

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

    <main class="schedules-container">
        <div class="page-header">
            <h1 data-i18n="horarios_titulo">Horarios y Rutas</h1>
            <p data-i18n="horarios_desc">Consulta todas las frecuencias de nuestros trenes entre las principales ciudades.</p>
        </div>

        <div class="schedules-card">
            <table class="schedules-table">
                <thead>
                    <tr>
                        <th data-i18n="tabla_origen">Origen</th>
                        <th data-i18n="tabla_destino">Destino</th>
                        <th data-i18n="tabla_salida">Salida</th>
                        <th data-i18n="tabla_llegada">Llegada</th>
                        <th data-i18n="tabla_duracion">Duración</th>
                        <th data-i18n="tabla_precio">Precio desde</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($viajes as $v): ?>
                    <?php
                        $urlReserva = 'compra.php?trip=oneway&pasajeros=1&id_viaje=' . $v['id_viaje']
                            . '&origen=' . urlencode($v['origen'])
                            . '&destino=' . urlencode($v['destino'])
                            . '&fecha=' . urlencode($v['fecha']);
                    ?>
                    <tr>
                        <td>
                            <div class="route-info">
                                <div class="route-icon"><i class="fa-solid fa-location-dot"></i></div>
                                <span><?= htmlspecialchars($v['origen']) ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="route-info">
                                <div class="route-icon" style="background:#f0fdf4; color:#22c55e;"><i class="fa-solid fa-location-arrow"></i></div>
                                <span><?= htmlspecialchars($v['destino']) ?></span>
                            </div>
                        </td>
                        <td><div class="time-box"><?= substr($v['hora_salida'], 0, 5) ?></div></td>
                        <td><div class="time-box"><?= substr($v['hora_llegada'], 0, 5) ?></div></td>
                        <td><span class="duration-tag"><?= calcularDuracion($v['hora_salida'], $v['hora_llegada']) ?></span></td>
                        <td><span class="price-text"><?= number_format($v['precio'], 2) ?> €</span></td>
                        <td><a href="<?= $urlReserva ?>" class="btn-view" data-i18n="reservar_billete">Reservar billete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

    <script src="scripts/i18n.js?v=<?php echo @filemtime(__DIR__ . '/scripts/i18n.js'); ?>"></script>
    <script src="scripts/session_menu.js"></script>
</body>
</html>
