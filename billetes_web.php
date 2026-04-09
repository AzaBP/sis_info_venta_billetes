<?php
session_start();
require_once __DIR__ . '/php/auth_helpers.php';
require_once __DIR__ . '/php/Conexion.php';

// Obtener datos de sesión
$usuarioSesion = $_SESSION['usuario'] ?? null;
$nombreSesion = $usuarioSesion['nombre'] ?? '';

// Si es un empleado, lo mandamos a su panel
if ($usuarioSesion && ($usuarioSesion['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($usuarioSesion));
    exit;
}

$pdo = (new Conexion())->conectar();

// 1. OBTENER ORÍGENES Y DESTINOS PARA EL BUSCADOR
try {
    $origenes = $pdo->query("SELECT DISTINCT origen FROM RUTA ORDER BY origen ASC")->fetchAll(PDO::FETCH_COLUMN);
    $destinos = $pdo->query("SELECT DISTINCT destino FROM RUTA ORDER BY destino ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $origenes = [];
    $destinos = [];
}

// 2. OBTENER "MIS BILLETES" (Solo si está logueado como pasajero)
$mis_billetes = [];
if ($usuarioSesion && ($usuarioSesion['tipo_usuario'] ?? '') === 'pasajero') {
    try {
        // Buscamos el ID del pasajero usando su ID de usuario
        $stmtPasajero = $pdo->prepare("SELECT id_pasajero FROM PASAJERO WHERE id_usuario = ?");
        $stmtPasajero->execute([$usuarioSesion['id_usuario']]);
        $id_pasajero = $stmtPasajero->fetchColumn();

        if ($id_pasajero) {
            // Consulta de billetes (Ajusta los nombres de las columnas si tu tabla se llama distinto)
            $sqlBilletes = "SELECT b.id_billete, b.precio_compra, b.asiento,
                                   v.fecha, v.hora_salida, v.hora_llegada, 
                                   r.origen, r.destino, t.modelo as tren
                            FROM BILLETE b
                            JOIN VIAJE v ON b.id_viaje = v.id_viaje
                            JOIN RUTA r ON v.id_ruta = r.id_ruta
                            JOIN TREN t ON v.id_tren = t.id_tren
                            WHERE b.id_pasajero = ?
                            ORDER BY v.fecha DESC, v.hora_salida DESC";
            $stmt = $pdo->prepare($sqlBilletes);
            $stmt->execute([$id_pasajero]);
            $mis_billetes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // Si hay error (ej. la tabla BILLETE aún no existe), no rompemos la web
        $mis_billetes = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Reserva y Gestiona tus Billetes</title>
    
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/session_menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* ESTILOS ESPECÍFICOS PARA ESTA PÁGINA */
        .search-hero {
            background: linear-gradient(rgba(10, 42, 102, 0.8), rgba(10, 42, 102, 0.8)), url('img/hero-train.jpg') center/cover;
            padding: 60px 20px;
            color: white;
            text-align: center;
        }

        .search-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            max-width: 900px;
            margin: 30px auto 0 auto;
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .search-group {
            flex: 1;
            min-width: 200px;
            text-align: left;
        }

        .search-group label {
            display: block;
            color: #555;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .search-group select, .search-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: inherit;
            font-size: 1rem;
        }

        .btn-search {
            background: #f39c12;
            color: white;
            border: none;
            padding: 14px 25px;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
            flex: 0 1 auto;
        }
        .btn-search:hover { background: #d68910; }

        .section-tickets {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .section-title {
            color: #0a2a66;
            border-bottom: 3px solid #f39c12;
            display: inline-block;
            padding-bottom: 5px;
            margin-bottom: 30px;
        }

        /* Diseño de los Billetes */
        .tickets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .ticket-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            border: 1px solid #e1e5eb;
        }

        .ticket-header {
            background: #0a2a66;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ticket-body {
            padding: 20px;
            display: flex;
            justify-content: space-between;
        }

        .ticket-info h4 { margin: 0 0 5px 0; color: #333; font-size: 1.2rem; }
        .ticket-info p { margin: 5px 0; color: #666; font-size: 0.9rem; }
        
        .ticket-qr {
            width: 80px;
            height: 80px;
            background: #f4f7fb;
            border: 2px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            color: #aaa;
        }

        .ticket-footer {
            background: #f8f9fa;
            padding: 15px;
            text-align: center;
            border-top: 1px dashed #ccc;
            font-weight: bold;
            color: #17632A;
        }

        .login-banner {
            background: #eef2f7;
            padding: 40px;
            text-align: center;
            border-radius: 10px;
            border: 1px dashed #b6c6d3;
        }

        /* Diseño Información */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .info-card {
            background: white; padding: 20px; border-radius: 8px;
            text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .info-card i { color: #0900bc; margin-bottom: 15px; }
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

    <main class="section-tickets">
        <h2 class="section-title"><i class="fa-solid fa-ticket"></i> Mis Próximos Viajes</h2>
        
        <?php if (!$usuarioSesion): ?>
            <div class="login-banner">
                <i class="fa-solid fa-user-lock fa-3x" style="color: #b6c6d3; margin-bottom: 15px;"></i>
                <h3>Inicia sesión para gestionar tus billetes</h3>
                <p style="color: #666; margin-bottom: 20px;">Si ya has comprado un billete o tienes un abono, entra a tu cuenta para descargarlo o modificarlo.</p>
                <a href="inicio_sesion.html" class="btn-login" style="background: #0a2a66;">Iniciar Sesión</a>
            </div>
        <?php else: ?>
            <?php if (empty($mis_billetes)): ?>
                <p style="text-align: center; color: #666; padding: 40px; background: #f8f9fa; border-radius: 8px;">No tienes ningún viaje programado actualmente. ¡Usa el buscador para planear tu próxima escapada!</p>
            <?php else: ?>
                <div class="tickets-grid">
                    <?php foreach ($mis_billetes as $b): 
                        // Determinar si el viaje ya pasó
                        $ya_viajo = (strtotime($b['fecha']) < strtotime('today'));
                        $color_header = $ya_viajo ? '#6c757d' : '#0a2a66';
                    ?>
                        <div class="ticket-card" style="opacity: <?= $ya_viajo ? '0.7' : '1' ?>;">
                            <div class="ticket-header" style="background: <?= $color_header ?>;">
                                <span><i class="fa-solid fa-train"></i> <?= htmlspecialchars($b['tren']) ?></span>
                                <strong><?= date('d/m/Y', strtotime($b['fecha'])) ?></strong>
                            </div>
                            <div class="ticket-body">
                                <div class="ticket-info">
                                    <h4><?= htmlspecialchars($b['origen']) ?> <i class="fa-solid fa-arrow-right" style="font-size:0.8em; color:#ccc;"></i> <?= htmlspecialchars($b['destino']) ?></h4>
                                    <p><i class="fa-regular fa-clock"></i> Salida: <strong><?= substr($b['hora_salida'], 0, 5) ?>h</strong></p>
                                    <p><i class="fa-solid fa-couch"></i> Asiento: <strong><?= htmlspecialchars($b['asiento'] ?? 'Por asignar') ?></strong></p>
                                </div>
                                <div class="ticket-qr" title="Muestra este QR al revisor">
                                    <i class="fa-solid fa-qrcode fa-3x"></i>
                                </div>
                            </div>
                            <div class="ticket-footer">
                                <?php if ($ya_viajo): ?>
                                    <span style="color: #6c757d;">Viaje Finalizado</span>
                                <?php else: ?>
                                    Localizador: #TRN-<?= str_pad($b['id_billete'], 5, '0', STR_PAD_LEFT) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <h2 class="section-title" style="margin-top: 60px;"><i class="fa-solid fa-circle-info"></i> Información Útil</h2>
        <div class="info-grid">
            <div class="info-card">
                <i class="fa-solid fa-suitcase fa-2x"></i>
                <h3>Equipaje Incluido</h3>
                <p style="color: #666; font-size: 0.9rem;">Todos nuestros billetes incluyen 1 equipaje de mano y 1 maleta de cabina (máx. 10kg). Puedes añadir equipaje extra durante el proceso de compra.</p>
            </div>
            <div class="info-card">
                <i class="fa-solid fa-bolt fa-2x"></i>
                <h3>Trenes AVLO y ALVIA</h3>
                <p style="color: #666; font-size: 0.9rem;">Disfruta de la Alta Velocidad Low Cost con nuestros trenes AVLO, o llega a cualquier rincón del país con la comodidad de nuestra flota ALVIA.</p>
            </div>
            <div class="info-card">
                <i class="fa-solid fa-rotate-left fa-2x"></i>
                <h3>Cambios y Anulaciones</h3>
                <p style="color: #666; font-size: 0.9rem;">Modifica tu billete hasta 2 horas antes de la salida abonando la diferencia. Cancelaciones gratuitas solo con la tarifa Flex.</p>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-bottom">&copy; 2026 TrainWeb</div>
    </footer>

    <script src="scripts/session_menu.js"></script>
</body>
</html>