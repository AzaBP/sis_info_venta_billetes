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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #f4f7fb 0%, #e8ecf1 100%); min-height: 100vh; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        
        .offers-main { max-width: 1300px; margin: 0 auto; padding: 40px 20px; }
        
        /* HEADER DE PÁGINA */
        .page-header { 
            text-align: center; margin-bottom: 60px; 
            background: linear-gradient(135deg, #0a2a66 0%, #1252f3 100%); 
            padding: 60px 20px; 
            border-radius: 16px; 
            color: white;
            box-shadow: 0 10px 30px rgba(10, 42, 102, 0.15);
        }
        .page-header h1 { font-size: 3rem; margin-bottom: 15px; font-weight: 700; }
        .page-header p { font-size: 1.2rem; opacity: 0.95; }

        /* SECTION TITLES */
        .section-title { 
            color: #0a2a66; 
            font-size: 1.8rem;
            margin-bottom: 40px; 
            padding-bottom: 15px;
            border-bottom: 4px solid #1252f3;
            display: inline-block;
        }
        
        /* GRID CONTAINERS */
        .grid-container { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
            gap: 30px; 
            margin-bottom: 80px; 
        }
        
        /* TARJETAS DE ABONOS */
        .abono-card { 
            background: white; 
            border-radius: 16px; 
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08); 
            display: flex; 
            flex-direction: column; 
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .abono-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 16px 40px rgba(0,0,0,0.12);
            border-color: #1252f3;
        }
        
        .abono-image { 
            width: 100%; 
            height: 180px; 
            object-fit: cover; 
            display: block;
        }
        
        .abono-content { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
        
        .abono-header { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            margin-bottom: 20px;
        }
        .abono-icon { 
            width: 50px; 
            height: 50px; 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            font-size: 1.5rem; 
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .abono-title { 
            margin: 0; 
            font-size: 1.4rem; 
            color: #0a2a66; 
            font-weight: 600;
        }
        
        .abono-body { flex-grow: 1; }
        .abono-desc { 
            color: #666; 
            font-size: 0.95rem; 
            line-height: 1.6; 
            margin-bottom: 20px;
        }
        
        .abono-price-box { 
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f3f7 100%); 
            padding: 18px; 
            border-radius: 12px; 
            text-align: center; 
            margin-bottom: 25px; 
            border: 2px solid #e1e5eb;
        }
        .abono-price { 
            font-size: 2.2rem; 
            font-weight: 700; 
            color: #02002f;
        }
        .abono-price-label {
            display: block; 
            font-size: 0.8rem; 
            color: #999; 
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* TARJETAS DE PROMOCIONES */
        .promo-card { 
            background: white; 
            border-radius: 16px; 
            padding: 30px; 
            text-align: center; 
            box-shadow: 0 8px 24px rgba(0,0,0,0.08); 
            position: relative; 
            overflow: hidden;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .promo-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #3156fc 0%, #1252f3 100%);
        }
        .promo-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.12);
            border-color: #3156fc;
        }
        
        .promo-title {
            color: #0a2a66;
            font-size: 1.1rem;
            margin-bottom: 15px;
            margin-top: 0;
            font-weight: 600;
        }
        
        .promo-descuento-box {
            background: linear-gradient(135deg, #3156fc 0%, #1252f3 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .promo-descuento { 
            font-size: 3.5rem; 
            font-weight: 800; 
            display: block;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .promo-descuento-label {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .promo-codigo { 
            display: inline-block; 
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f3f7 100%);
            border: 2px dashed #3156fc; 
            padding: 12px 24px; 
            font-size: 1.3rem; 
            font-weight: 700;
            letter-spacing: 2px; 
            color: #0a2a66; 
            margin-bottom: 20px; 
            border-radius: 8px;
            font-family: 'Courier New', monospace;
        }
        
        .promo-info-extra { 
            font-size: 0.9rem; 
            color: #666; 
            margin-bottom: 25px; 
            display: flex; 
            flex-direction: column; 
            gap: 10px;
        }
        
        .promo-info-badge {
            display: inline-block;
            background: #e8f0fe;
            color: #0a2a66;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            width: fit-content;
            margin: 0 auto;
        }
        
        /* BOTONES COMUNES */
        .btn-action { 
            display: block; 
            width: 100%; 
            padding: 14px; 
            border: none; 
            border-radius: 10px; 
            font-size: 1rem; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            text-align: center; 
            text-decoration: none; 
            box-sizing: border-box;
        }
        
        .btn-buy { 
            background: linear-gradient(135deg, #1602fc 0%, #3156fc 100%);
            color: white;
        }
        .btn-buy:hover { 
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(22, 2, 252, 0.3);
        }
        
        .btn-copy { 
            background: linear-gradient(135deg, #3156fc 0%, #1252f3 100%);
            color: white;
        }
        .btn-copy:hover { 
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(49, 86, 252, 0.3);
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .page-header h1 { font-size: 2rem; }
            .page-header p { font-size: 1rem; }
            .grid-container { grid-template-columns: 1fr; gap: 20px; }
            .section-title { font-size: 1.5rem; }
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

    <main class="offers-main">
        <div class="page-header">
            <h1 data-i18n="ofertas_h1">Descubre Nuestras Ofertas</h1>
            <p data-i18n="ofertas_desc">Ahorra en tus viajes con nuestros abonos y promociones exclusivas.</p>
        </div>

        <section>
            <h2 class="section-title"><i class="fa-solid fa-address-card"></i> <span data-i18n="catalogo_abonos">Catálogo de Abonos</span></h2>
            <div id="abonos-container" class="grid-container">
                <?php if (empty($abonos)): ?>
                    <p style="color: #666;" data-i18n="no_abonos_venta">No hay abonos a la venta en este momento.</p>
                <?php else: ?>
                    <?php foreach ($abonos as $a): ?>
                        <?php
                        // Array de imágenes random de trenes y compras
                        $imagenes = [
                            'https://images.pexels.com/photos/33064779/pexels-photo-33064779.jpeg',
                            'https://images.pexels.com/photos/18377906/pexels-photo-18377906.jpeg', 
                            'https://images.pexels.com/photos/27617418/pexels-photo-27617418.jpeg', 
                            'https://images.pexels.com/photos/4881129/pexels-photo-4881129.jpeg', 
                            'https://images.pexels.com/photos/18505677/pexels-photo-18505677.jpeg',
                            'https://images.pexels.com/photos/31016263/pexels-photo-31016263.jpeg' ,
                            'https://images.pexels.com/photos/29248079/pexels-photo-29248079.jpeg',
                            'https://images.pexels.com/photos/34172748/pexels-photo-34172748.jpeg',
                            'https://images.pexels.com/photos/10652773/pexels-photo-10652773.jpeg',
                            'https://images.pexels.com/photos/31500815/pexels-photo-31500815.jpeg',
                            'https://images.pexels.com/photos/31500816/pexels-photo-31500816.jpeg',
                            'https://images.pexels.com/photos/837359/pexels-photo-837359.jpeg'
                        ];
                        $imagen = $imagenes[array_rand($imagenes)];
                        ?>
                        <div class="abono-card" style="border-top: 5px solid <?= htmlspecialchars($a['color']) ?>;">
                            <img src="<?= $imagen ?>" alt="<?= htmlspecialchars($a['nombre']) ?>" class="abono-image">
                            
                            <div class="abono-content">
                                <div class="abono-header">
                                    <div class="abono-icon" style="background-color: <?= htmlspecialchars($a['color']) ?>;">
                                        <i class="fa-solid <?= htmlspecialchars($a['icono']) ?>"></i>
                                    </div>
                                    <h3 class="abono-title js-i18n-abono-name" data-i18n-abono-code="<?= htmlspecialchars($a['tipo_codigo']) ?>"><?= htmlspecialchars($a['nombre']) ?></h3>
                                </div>
                                
                                <div class="abono-body">
                                    <p class="abono-desc js-i18n-abono-desc" data-i18n-abono-code="<?= htmlspecialchars($a['tipo_codigo']) ?>">
                                        <?= nl2br(htmlspecialchars($a['descripcion'])) ?>
                                    </p>
                                </div>

                                <div class="abono-price-box">
                                    <span class="abono-price-label" data-i18n="precio_final">Precio final</span>
                                    <div class="abono-price"><?= number_format($a['precio'], 2, ',', '.') ?> €</div>
                                </div>
                                
                                <a href="comprar_abono.php?tipo=<?= $a['tipo_codigo'] ?>" class="btn-action btn-buy">
                                    <i class="fa-solid fa-cart-shopping"></i> <span data-i18n="comprar_abono">Comprar Abono</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <h2 class="section-title"><i class="fa-solid fa-tags"></i> <span data-i18n="codigos_promocionales">Códigos Promocionales</span></h2>
            <div id="promociones-container" class="grid-container">
                <?php if (empty($promociones)): ?>
                    <p style="color: #666;" data-i18n="no_promociones_disponibles">No hay promociones disponibles actualmente.</p>
                <?php else: ?>
                    <?php foreach ($promociones as $p): ?>
                        <div class="promo-card">
                            <h3 class="promo-title" data-i18n="cupon_descuento">Cupón Descuento</h3>
                            
                            <div class="promo-descuento-box">
                                <span class="promo-descuento">-<?= (float)$p['descuento_porcentaje'] ?>%</span>
                                <div class="promo-descuento-label">Descuento</div>
                            </div>
                            
                            <div class="promo-codigo" id="codigo-<?= $p['codigo'] ?>"><?= htmlspecialchars($p['codigo']) ?></div>
                            
                            <div class="promo-info-extra">
                                <span><i class="fa-regular fa-calendar"></i> <span data-i18n="valido_hasta">Válido hasta</span>: <strong><?= date('d/m/Y', strtotime($p['fecha_fin'])) ?></strong></span>
                                <?php if (!empty($p['usos_maximos'])): ?>
                                    <div class="promo-info-badge">
                                        <i class="fa-solid fa-users"></i> <?= $p['usos_maximos'] - $p['usos_actuales'] ?> <span data-i18n="usos_restantes">usos restantes</span>
                                    </div>
                                <?php else: ?>
                                    <div class="promo-info-badge">
                                        <i class="fa-solid fa-infinity"></i> <span data-i18n="usos_ilimitados">Usos ilimitados</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button class="btn-action btn-copy" onclick="copyToClipboard('<?= $p['codigo'] ?>')">
                                <i class="fa-regular fa-copy"></i> <span data-i18n="copiar_codigo">Copiar Código</span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                <a href="mis_billetes.php"><i class="fa-solid fa-ticket"></i> <span data-i18n="footer-billetes">Billetes</span></a>
                <a href="horarios.php"><i class="fa-solid fa-clock"></i> <span data-i18n="footer-horarios">Horarios</span></a>
                <a href="ofertas.php"><i class="fa-solid fa-tags"></i> <span data-i18n="footer-ofertas">Ofertas</span></a>
                <a href="ayuda.php"><i class="fa-solid fa-headset"></i> <span data-i18n="footer-atencion">Atención al cliente</span></a>
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
                <a href="julio_apruebanos.php"><i class="fa-brands fa-facebook-f"></i> Facebook</a>
                <a href="julio_apruebanos.php"><i class="fa-brands fa-x-twitter"></i> Twitter</a>
                <a href="julio_apruebanos.php"><i class="fa-brands fa-instagram"></i> Instagram</a>
                <a href="julio_apruebanos.php"><i class="fa-brands fa-linkedin-in"></i> LinkedIn</a>
            </div>
        </div>
        <div class="footer-bottom" data-i18n="footer_copyright">© 2026 TrainWeb · Todos los derechos reservados</div>
    </footer>

    <script src="scripts/i18n.js?v=<?php echo @filemtime(__DIR__ . '/scripts/i18n.js'); ?>"></script>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const i18n = window.trainwebI18n;
                const template = i18n && i18n.t ? i18n.t('codigo_copiado_msg') : null;
                if (template) {
                    alert(template.replace('{code}', text));
                } else {
                    alert("¡Código " + text + " copiado al portapapeles! Úsalo en el proceso de compra.");
                }
            }).catch(err => {
                console.error('Error al copiar: ', err);
            });
        }
    </script>
    <script src="scripts/session_menu.js"></script>
</body>
</html>