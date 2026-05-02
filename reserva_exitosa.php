<?php
session_start();

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$reserva = $_SESSION['ultima_reserva'] ?? null;
$valida = is_array($reserva) && ($reserva['token'] ?? '') === $token;
$billetes = $valida ? ($reserva['billetes'] ?? []) : [];
$precioTotal = $valida ? (float)($reserva['precio_total'] ?? 0) : 0;

// Detectar si es compra como invitado (sin usuario logueado)
$esInvitado = !isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id_usuario']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva completada - TrainWeb</title>
    <link rel="stylesheet" href="css/index.css">
    <style>
        .success-wrap { max-width: 980px; margin: 32px auto; padding: 0 16px; }
        .success-card { background: #fff; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.08); padding: 24px; }
        .ok-title { color: #17632A; margin: 0 0 8px; }
        .ok-sub { color: #334155; margin: 0 0 20px; }
        .summary-total { font-weight: 700; font-size: 1.1rem; margin-bottom: 14px; }
        .ticket-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px; }
        .ticket-mini { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; background: #f8fafc; }
        .ticket-mini h4 { margin: 0 0 8px; color: #0a2a66; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 18px; }
        .btn-primary, .btn-secondary {
            display: inline-block; text-decoration: none; border-radius: 8px; padding: 10px 16px; font-weight: 600;
        }
        .btn-primary { background: #0a2a66; color: #fff; }
        .btn-secondary { background: #fff; color: #0a2a66; border: 1px solid #0a2a66; }
    </style>
</head>
<body>
<div class="success-wrap">
    <div class="success-card">
        <?php if (!$valida): ?>
            <h1 class="ok-title">No se encontro la reserva</h1>
            <p class="ok-sub">La informacion de reserva no esta disponible en esta sesion.</p>
            <div class="actions">
                <a class="btn-primary" href="index.php">Volver al inicio</a>
            </div>
        <?php else: ?>
            <h1 class="ok-title">Reserva realizada con exito</h1>
            <p class="ok-sub">Se han generado <?php echo count($billetes); ?> billete(s). Puedes descargarlos en PDF con codigo QR.</p>
            <p class="summary-total">Total pagado: <?php echo number_format($precioTotal, 2, ',', '.'); ?> EUR</p>

            <div class="ticket-grid">
                <?php foreach ($billetes as $i => $b): ?>
                    <div class="ticket-mini">
                        <h4>Billete <?php echo $i + 1; ?></h4>
                        <p><strong>Pasajero:</strong> <?php echo htmlspecialchars(($b['pasajero_nombre'] ?? '') . ' ' . ($b['pasajero_apellidos'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Ruta:</strong> <?php echo htmlspecialchars(($b['origen'] ?? '') . ' -> ' . ($b['destino'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Fecha viaje:</strong> <?php echo htmlspecialchars((string)($b['fecha_viaje'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Asiento:</strong> <?php echo htmlspecialchars((string)($b['numero_asiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Codigo:</strong> <?php echo htmlspecialchars((string)($b['codigo_billete'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="actions">
                <a class="btn-primary" href="php/descargar_billetes.php?token=<?php echo urlencode($token); ?>">Descargar PDF de billetes</a>
                <?php if (!$esInvitado): ?>
                    <a class="btn-secondary" href="mis_billetes.php">Ver mis billetes</a>
                    <a class="btn-secondary" href="perfil_pasajero.php">Ir a mi perfil</a>
                <?php endif; ?>
                <a class="btn-secondary" href="index.php">Volver al inicio</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
