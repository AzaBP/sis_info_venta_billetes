<?php
session_start();
require_once __DIR__ . '/php/auth_helpers.php';

// Obtener los datos de sesión para el Header
$usuarioSesion = $_SESSION['usuario'] ?? null;
$nombreSesion = $usuarioSesion['nombre'] ?? '';

// Redirigir si es empleado
if ($usuarioSesion && ($usuarioSesion['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($usuarioSesion));
    exit;
}

require_once __DIR__ . '/php/Conexion.php';

try {
    $pdo = (new Conexion())->conectar();
    
    // 1. OBTENER PROMOCIONES (Añadimos usos_maximos y usos_actuales)
    $stmtP = $pdo->query("SELECT codigo, descuento_porcentaje, fecha_fin, usos_maximos, usos_actuales 
                          FROM PROMOCION 
                          WHERE fecha_fin >= CURRENT_DATE 
                          ORDER BY descuento_porcentaje DESC");
    $promociones = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    // 2. OBTENER ABONOS (Con todos sus campos visuales y de precio)
    $stmtA = $pdo->query("SELECT tipo_codigo, nombre, descripcion, precio, icono, color 
                          FROM TIPO_ABONO 
                          ORDER BY precio ASC");
    $abonos = $stmtA->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si hay error de base de datos, las listas quedan vacías
    $promociones = [];
    $abonos = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Ofertas y Abonos</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/session_menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* Ajustes específicos para las tarjetas de ofertas en esta página */
        body { margin: 0; padding: 0; background-color: #f4f7fb; }
        
        .offers-main { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .page-header { text-align: center; margin-bottom: 50px; }
        .page-header h1 { color: #0a2a66; font-size: 2.5rem; margin-bottom: 10px; }
        .page-header p { color: #666; font-size: 1.1rem; }

        .section-title { color: #0a2a66; border-bottom: 3px solid #1252f3; display: inline-block; padding-bottom: 5px; margin-bottom: 30px; }
        
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; margin-bottom: 60px; }
        
        /* TARJETAS DE PROMOCIONES */
        .promo-card { background: white; border-radius: 12px; padding: 25px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-top: 5px solid #3156fc; position: relative; overflow: hidden; }
        .promo-card .descuento { font-size: 3rem; font-weight: bold; color: #3156fc; margin: 10px 0; }
        .promo-card .codigo { display: inline-block; background: #f8f9fa; border: 2px dashed #ccc; padding: 10px 20px; font-size: 1.2rem; font-weight: bold; letter-spacing: 2px; color: #333; margin-bottom: 15px; border-radius: 5px; }
        .promo-card .info-extra { font-size: 0.85rem; color: #666; margin-bottom: 20px; display: flex; flex-direction: column; gap: 5px; }
        
        /* TARJETAS DE ABONOS */
        .abono-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column; transition: transform 0.3s; }
        .abono-card:hover { transform: translateY(-5px); }
        .abono-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .abono-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; flex-shrink: 0; }
        .abono-title { margin: 0; font-size: 1.3rem; color: #0a2a66; }
        
        .abono-body { flex-grow: 1; }
        .abono-desc { color: #555; font-size: 0.95rem; line-height: 1.5; margin-bottom: 20px; }
        
        .abono-price-box { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; border: 1px solid #e1e5eb; }
        .abono-price { font-size: 2rem; font-weight: bold; color: #28a745; }
        
        /* BOTONES COMUNES */
        .btn-action { display: block; width: 100%; padding: 12px; border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: background 0.3s; text-align: center; text-decoration: none; box-sizing: border-box; }
        .btn-copy { background: #3156fc; color: white; }
        .btn-copy:hover { background: #c82333; }
        .btn-buy { background: #1602fc; color: white; }
        .btn-buy:hover { background: #1010d6c5; }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb</div>
        <nav class="nav">
            <a href="index.php">Inicio</a>
            <a href="billetes.php">Billetes</a>
            <div class="dropdown">
                <a href="#">Idiomas <i class="fa-solid fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="#">Español</a>
                    <a href="#">Inglés</a>
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

    <main class="offers-main">
        <div class="page-header">
            <h1>Descubre Nuestras Ofertas</h1>
            <p>Ahorra en tus viajes con nuestros abonos y promociones exclusivas.</p>
        </div>

        <section>
            <h2 class="section-title"><i class="fa-solid fa-address-card"></i> Catálogo de Abonos</h2>
            <div id="abonos-container" class="grid-container">
                <?php if (empty($abonos)): ?>
                    <p style="color: #666;">No hay abonos a la venta en este momento.</p>
                <?php else: ?>
                    <?php foreach ($abonos as $a): ?>
                        <div class="abono-card" style="border-top: 5px solid <?= htmlspecialchars($a['color']) ?>;">
                            
                            <div class="abono-header">
                                <div class="abono-icon" style="background-color: <?= htmlspecialchars($a['color']) ?>;">
                                    <i class="fa-solid <?= htmlspecialchars($a['icono']) ?>"></i>
                                </div>
                                <h3 class="abono-title"><?= htmlspecialchars($a['nombre']) ?></h3>
                            </div>
                            
                            <div class="abono-body">
                                <p class="abono-desc">
                                    <?= nl2br(htmlspecialchars($a['descripcion'])) ?>
                                </p>
                            </div>

                            <div class="abono-price-box">
                                <span style="display:block; font-size: 0.85rem; color:#666; margin-bottom: 5px;">Precio final</span>
                                <div class="abono-price"><?= number_format($a['precio'], 2, ',', '.') ?> €</div>
                            </div>
                            
                            <a href="comprar_abono.php?tipo=<?= $a['tipo_codigo'] ?>" class="btn-action btn-buy">
                                <i class="fa-solid fa-cart-shopping"></i> Comprar Abono
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <h2 class="section-title"><i class="fa-solid fa-tags"></i> Códigos Promocionales</h2>
            <div id="promociones-container" class="grid-container">
                <?php if (empty($promociones)): ?>
                    <p style="color: #666;">No hay promociones disponibles actualmente.</p>
                <?php else: ?>
                    <?php foreach ($promociones as $p): ?>
                        <div class="promo-card">
                            <h3 style="margin-top:0; color:#333;">Cupón Descuento</h3>
                            
                            <div class="descuento">-<?= (float)$p['descuento_porcentaje'] ?>%</div>
                            <div class="codigo" id="codigo-<?= $p['codigo'] ?>"><?= htmlspecialchars($p['codigo']) ?></div>
                            
                            <div class="info-extra">
                                <span><i class="fa-regular fa-calendar-xmark"></i> Válido hasta: <strong><?= date('d/m/Y', strtotime($p['fecha_fin'])) ?></strong></span>
                                <?php if (!empty($p['usos_maximos'])): ?>
                                    <span><i class="fa-solid fa-users"></i> Usos restantes: <strong><?= $p['usos_maximos'] - $p['usos_actuales'] ?></strong> de <?= $p['usos_maximos'] ?></span>
                                <?php else: ?>
                                    <span><i class="fa-solid fa-infinity"></i> Usos ilimitados</span>
                                <?php endif; ?>
                            </div>
                            
                            <button class="btn-action btn-copy" onclick="copyToClipboard('<?= $p['codigo'] ?>')">
                                <i class="fa-regular fa-copy"></i> Copiar Código
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-bottom">&copy; 2026 TrainWeb</div>
    </footer>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert("¡Código " + text + " copiado al portapapeles! Úsalo en el proceso de compra.");
            }).catch(err => {
                console.error('Error al copiar: ', err);
            });
        }
    </script>
    <script src="scripts/session_menu.js"></script>
</body>
</html>