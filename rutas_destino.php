<?php
session_start();
require_once __DIR__ . '/php/auth_helpers.php';
require_once __DIR__ . '/php/Conexion.php';
require_once __DIR__ . '/php/ConexionMongo.php';

// 1. Obtener el destino de la URL (ej: rutas_destino.php?destino=Madrid)
$destinoBuscado = trim((string)($_GET['destino'] ?? ''));
$tituloDestino = $destinoBuscado !== '' ? $destinoBuscado : 'Todos';

$usuarioSesion = $_SESSION['usuario'] ?? null;
$nombreSesion = $usuarioSesion['nombre'] ?? '';

try {
    $pdo = (new Conexion())->conectar();

    // 2. Consulta dinámica: destino parcial + viajes que aun no han empezado
    $where = [
        "v.estado <> 'cancelado'",
        "(v.fecha > CURRENT_DATE OR (v.fecha = CURRENT_DATE AND v.hora_salida > CURRENT_TIME))"
    ];
    $params = [];

    if ($destinoBuscado !== '') {
        $where[] = 'r.destino ILIKE :destino';
        $params[':destino'] = '%' . $destinoBuscado . '%';
    }

    $sql = "SELECT v.id_viaje, v.fecha, v.hora_salida, v.hora_llegada, v.precio,
                   r.origen, r.destino, t.modelo AS tren, t.capacidad AS capacidad_tren
            FROM VIAJE v
            JOIN RUTA r ON v.id_ruta = r.id_ruta
            JOIN TREN t ON v.id_tren = t.id_tren
            WHERE " . implode(' AND ', $where) . "
            ORDER BY v.fecha ASC, v.hora_salida ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Ocupacion por viaje usando billetes confirmados en MongoDB
    $ocupacionPorViaje = [];
    if (!empty($viajes)) {
        $idsViaje = array_map('intval', array_column($viajes, 'id_viaje'));
        $dbMongo = (new ConexionMongo())->conectar();

        if ($dbMongo && !empty($idsViaje)) {
            $coleccion = $dbMongo->selectCollection('billetes');
            $cursor = $coleccion->aggregate([
                [
                    '$match' => [
                        'id_viaje' => ['$in' => $idsViaje],
                        'estado' => 'confirmado'
                    ]
                ],
                [
                    '$group' => [
                        '_id' => '$id_viaje',
                        'ocupados' => ['$sum' => 1]
                    ]
                ]
            ]);

            foreach ($cursor as $doc) {
                $ocupacionPorViaje[(int)$doc['_id']] = (int)$doc['ocupados'];
            }
        }
    }

} catch (PDOException $e) {
    $viajes = [];
    $ocupacionPorViaje = [];
} catch (Throwable $e) {
    $viajes = [];
    $ocupacionPorViaje = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Viajes a <?php echo htmlspecialchars($tituloDestino); ?> - TrainWeb</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/session_menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .destination-hero {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('img/hero-train.jpg');
            background-size: cover; background-position: center;
            color: white; padding: 60px 20px; text-align: center; margin-bottom: 30px;
        }
        .trips-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px; padding: 20px; max-width: 1200px; margin: 0 auto;
        }
        .trip-card {
            background: white; border-radius: 10px; padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-top: 5px solid #0a2a66;
        }
        .trip-card.full { border-top-color: #b42318; opacity: 0.95; }
        .trip-price { font-size: 1.5rem; color: #17632A; font-weight: bold; }
        .trip-status { display: inline-block; margin-top: 6px; font-size: 0.9rem; font-weight: 600; }
        .trip-status.available { color: #17632A; }
        .trip-status.full { color: #b42318; }
        .trip-btn {
            width: 100%; padding: 10px; margin-top: 15px;
            background: #0a2a66; color: white; border: none;
            border-radius: 5px; cursor: pointer; text-align: center;
            text-decoration: none; display: inline-block;
        }
        .trip-btn.disabled { background: #9aa1b1; cursor: not-allowed; pointer-events: none; }
        .no-results { text-align: center; padding: 50px; font-size: 1.2rem; color: #666; }
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
        <div class="user-actions">
            <?php if ($usuarioSesion): ?>
                <div class="account-dropdown open-on-hover">
                    <button class="account-toggle">
                        <span class="account-avatar"><?php echo strtoupper(substr($nombreSesion, 0, 1)); ?></span>
                        <span class="account-name"><?php echo htmlspecialchars($nombreSesion); ?></span>
                    </button>
                </div>
            <?php else: ?>
                <a href="inicio_sesion.html" class="btn-login"><span data-i18n="iniciar_sesion">Iniciar sesión</span></a>
            <?php endif; ?>
        </div>
    </header>

    <div class="destination-hero">
        <h1><span data-i18n="rutas_viajes_disponibles_a">Viajes disponibles a</span> <?php echo htmlspecialchars($tituloDestino); ?></h1>
        <p data-i18n="rutas_mejores_precios">Encuentra los mejores precios para tu próximo destino.</p>
    </div>

    <main>
        <?php if (empty($viajes)): ?>
            <div class="no-results">
                <i class="fa-solid fa-circle-info fa-3x"></i>
                <p><span data-i18n="rutas_no_viajes_a">Lo sentimos, no hay viajes programados a</span> <?php echo htmlspecialchars($tituloDestino); ?> <span data-i18n="rutas_en_este_momento">en este momento.</span></p>
                <a href="index.php" class="btn-login" style="display:inline-block; margin-top:20px;" data-i18n="volver_inicio">Volver al inicio</a>
            </div>
        <?php else: ?>
            <div class="trips-grid">
                <?php foreach ($viajes as $v): ?>
                    <?php
                        $idViaje = (int)$v['id_viaje'];
                        $capacidad = (int)($v['capacidad_tren'] ?? 0);
                        $ocupados = (int)($ocupacionPorViaje[$idViaje] ?? 0);
                        $disponibles = max(0, $capacidad - $ocupados);
                        $agotado = ($disponibles <= 0);
                        $urlCompra = 'compra.php?trip=oneway&pasajeros=1&id_viaje=' . $idViaje
                            . '&origen=' . urlencode((string)$v['origen'])
                            . '&destino=' . urlencode((string)$v['destino'])
                            . '&fecha=' . urlencode((string)$v['fecha']);
                    ?>
                    <div class="trip-card<?php echo $agotado ? ' full' : ''; ?>">
                        <div style="display:flex; justify-content: space-between; align-items: center;">
                            <span><i class="fa-solid fa-calendar"></i> <?php echo date('d/m/Y', strtotime($v['fecha'])); ?></span>
                            <span class="trip-price"><?php echo number_format($v['precio'], 2); ?> €</span>
                        </div>
                        <hr style="margin: 15px 0; opacity: 0.2;">
                        <p><strong data-i18n="origen">Origen</strong>: <?php echo htmlspecialchars($v['origen']); ?></p>
                        <p><strong data-i18n="destino_label">Destino</strong>: <?php echo htmlspecialchars($v['destino']); ?></p>
                        <p><strong data-i18n="salida_label">Salida</strong>: <?php echo substr($v['hora_salida'], 0, 5); ?> h</p>
                        <p><strong data-i18n="tren_label">Tren</strong>: <?php echo htmlspecialchars($v['tren']); ?></p>
                        <p class="trip-status <?php echo $agotado ? 'full' : 'available'; ?>">
                            <?php if ($agotado): ?>
                                <span>Agotado</span>
                            <?php else: ?>
                                <span><?php echo $disponibles; ?> plaza(s) disponible(s)</span>
                            <?php endif; ?>
                        </p>
                        <?php if ($agotado): ?>
                            <span class="trip-btn disabled">Sin plazas</span>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($urlCompra, ENT_QUOTES, 'UTF-8'); ?>" class="trip-btn">
                                <span data-i18n="reservar_billete">Reservar billete</span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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

    <script src="scripts/i18n.js?v=<?php echo @filemtime(__DIR__ . '/scripts/i18n.js'); ?>"></script>
    <script src="scripts/session_menu.js"></script>
</body>
</html>