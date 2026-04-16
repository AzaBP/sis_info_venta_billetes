<?php
session_start();
require_once __DIR__ . '/php/auth_helpers.php';
require_once __DIR__ . '/php/Conexion.php';

// 1. Obtener el destino de la URL (ej: rutas_destino.php?destino=Madrid)
$destinoBuscado = $_GET['destino'] ?? 'Todos';

$usuarioSesion = $_SESSION['usuario'] ?? null;
$nombreSesion = $usuarioSesion['nombre'] ?? '';

try {
    $pdo = (new Conexion())->conectar();
    
    // 2. Consulta dinámica: Filtramos por el destino recibido (Mejorado con ILIKE)
    $sql = "SELECT v.*, r.origen, r.destino, t.modelo as tren 
            FROM VIAJE v
            JOIN RUTA r ON v.id_ruta = r.id_ruta
            JOIN TREN t ON v.id_tren = t.id_tren
            WHERE r.destino ILIKE :destino
            ORDER BY v.fecha ASC, v.hora_salida ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['destino' => $destinoBuscado]);
    $viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $viajes = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Viajes a <?php echo htmlspecialchars($destinoBuscado); ?> - TrainWeb</title>
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
        .trip-price { font-size: 1.5rem; color: #17632A; font-weight: bold; }
        .no-results { text-align: center; padding: 50px; font-size: 1.2rem; color: #666; }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb</div>
        <nav class="nav">
            <a href="index.php" data-i18n="inicio">Inicio</a>
            <a href="billetes_web.php" data-i18n="billetes">Billetes</a>
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
        <h1><span data-i18n="rutas_viajes_disponibles_a">Viajes disponibles a</span> <?php echo htmlspecialchars($destinoBuscado); ?></h1>
        <p data-i18n="rutas_mejores_precios">Encuentra los mejores precios para tu próximo destino.</p>
    </div>

    <main>
        <?php if (empty($viajes)): ?>
            <div class="no-results">
                <i class="fa-solid fa-circle-info fa-3x"></i>
                <p><span data-i18n="rutas_no_viajes_a">Lo sentimos, no hay viajes programados a</span> <?php echo htmlspecialchars($destinoBuscado); ?> <span data-i18n="rutas_en_este_momento">en este momento.</span></p>
                <a href="index.php" class="btn-login" style="display:inline-block; margin-top:20px;" data-i18n="volver_inicio">Volver al inicio</a>
            </div>
        <?php else: ?>
            <div class="trips-grid">
                <?php foreach ($viajes as $v): ?>
                    <div class="trip-card">
                        <div style="display:flex; justify-content: space-between; align-items: center;">
                            <span><i class="fa-solid fa-calendar"></i> <?php echo date('d/m/Y', strtotime($v['fecha'])); ?></span>
                            <span class="trip-price"><?php echo number_format($v['precio'], 2); ?> €</span>
                        </div>
                        <hr style="margin: 15px 0; opacity: 0.2;">
                        <p><strong data-i18n="origen">Origen</strong>: <?php echo htmlspecialchars($v['origen']); ?></p>
                        <p><strong data-i18n="salida_label">Salida</strong>: <?php echo substr($v['hora_salida'], 0, 5); ?> h</p>
                        <p><strong data-i18n="tren_label">Tren</strong>: <?php echo htmlspecialchars($v['tren']); ?></p>
                        <button onclick="window.location.href='compra.php?id=<?php echo $v['id_viaje']; ?>'" 
                                style="width:100%; padding:10px; margin-top:15px; background:#0a2a66; color:white; border:none; border-radius:5px; cursor:pointer;">
                            <span data-i18n="reservar_billete">Reservar billete</span>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="scripts/i18n.js?v=<?php echo @filemtime(__DIR__ . '/scripts/i18n.js'); ?>"></script>
    <script src="scripts/session_menu.js"></script>
</body>
</html>