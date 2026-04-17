<?php
session_start();
require_once __DIR__ . '/php/auth_helpers.php';

$usuarioSesion = $_SESSION['usuario'] ?? null;
if (!$usuarioSesion) {
    header('Location: inicio_sesion.html');
    exit;
}

if (($usuarioSesion['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($usuarioSesion));
    exit;
}

if (($usuarioSesion['tipo_usuario'] ?? '') !== 'pasajero') {
    header('Location: inicio_sesion.html');
    exit;
}

$assetVersion = (string)@filemtime(__FILE__);
$nombreSesion = $usuarioSesion['nombre'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis billetes - TrainWeb</title>
    <link rel="stylesheet" href="css/index.css?v=<?php echo urlencode($assetVersion); ?>">
    <link rel="stylesheet" href="css/session_menu.css?v=<?php echo urlencode($assetVersion); ?>">
    <style>
        body { background: #eef3fb; }
        .my-tickets-page { max-width: 1240px; margin: 0 auto; padding: 28px 20px 48px; }
        .page-hero {
            background: linear-gradient(135deg, #0c2344 0%, #1f4fa6 100%);
            color: #fff;
            border-radius: 20px;
            padding: 26px 28px;
            box-shadow: 0 14px 34px rgba(16, 39, 82, 0.15);
            margin-bottom: 22px;
        }
        .page-hero h1 { margin: 0 0 8px; font-size: 2rem; }
        .page-hero p { margin: 0; color: rgba(255,255,255,.82); }
        .tickets-loading { text-align: center; color: #5c6b85; padding: 30px; }
        .tickets-list { display: grid; gap: 14px; }
        .ticket-row {
            display: grid;
            grid-template-columns: 1.3fr 1fr auto;
            gap: 14px;
            align-items: center;
            background: #fff;
            border: 1px solid #d8e0ef;
            border-radius: 18px;
            box-shadow: 0 10px 26px rgba(16, 39, 82, 0.08);
            padding: 16px 18px;
            border-left: 6px solid #1f4fa6;
        }
        .ticket-row.expired { border-left-color: #8a93a8; opacity: 0.82; }
        .ticket-route { display: flex; flex-direction: column; gap: 7px; }
        .ticket-route h3 { margin: 0; color: #12213d; font-size: 1.08rem; }
        .ticket-route p, .ticket-meta p { margin: 0; color: #5c6b85; font-size: 0.92rem; }
        .ticket-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
        .badge { border-radius: 999px; padding: 5px 10px; font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; }
        .badge-ok { background: #daf5e8; color: #146f47; }
        .badge-soft { background: #e7eefb; color: #183d82; }
        .ticket-meta { min-width: 220px; }
        .ticket-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
        .ticket-qrcode {
            width: 86px;
            height: 86px;
            border-radius: 14px;
            border: 1px dashed #b7c6df;
            display: grid;
            place-items: center;
            color: #1f4fa6;
            background: #f8fbff;
            font-size: 0.72rem;
            text-align: center;
            padding: 8px;
        }
        .empty-box, .error-box {
            background: #fff;
            border-radius: 18px;
            border: 1px dashed #b7c6df;
            padding: 26px;
            text-align: center;
            color: #5c6b85;
        }
        .error-box { border-color: #e3b6b6; background: #fff5f5; color: #8e2e2e; }
        .btn-link {
            background: #0a2a66;
            color: #fff;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        @media (max-width: 920px) {
            .ticket-row { grid-template-columns: 1fr; }
            .ticket-actions { align-items: flex-start; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb</div>
        <nav class="nav">
            <a href="index.php" data-i18n="inicio">Inicio</a>
            <a href="mis_billetes.php" data-i18n="mis_billetes">Mis billetes</a>
            <a href="ofertas.php" data-i18n="ofertas">Ofertas</a>
            <a href="ayuda.php" data-i18n="ayuda">Ayuda</a>
        </nav>
        <div class="user-actions" id="userActions">
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
        </div>
    </header>

    <main class="my-tickets-page">
        <section class="page-hero">
            <h1>Mis billetes</h1>
            <p>Consulta tus reservas y descarga el PDF con QR de cada viaje.</p>
        </section>

        <div id="ticketsState" class="tickets-loading">Cargando tus billetes...</div>
        <div id="ticketsList" class="tickets-list" hidden></div>
    </main>

    <script src="scripts/i18n.js?v=<?php echo urlencode($assetVersion); ?>"></script>
    <script>
    window.misBilletesConfig = {
        apiUrl: 'php/api_billetes_pasajero.php',
        downloadUrl: 'php/descargar_billete.php'
    };
    </script>
    <script src="js/mis_billetes.js?v=<?php echo urlencode($assetVersion); ?>"></script>
</body>
</html>
