<?php
session_start();

if (!empty($_SESSION['admin_simple_auth']) && $_SESSION['admin_simple_auth'] === true) {
    header('Location: panel_administrador.php');
    exit;
}

$error = $_GET['error'] ?? '';
$mostrarErrorCredenciales = ($error === 'credenciales');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Acceso Administrador</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; }
        .wrap { max-width: 420px; margin: 80px auto; background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,.12); }
        h1 { margin: 0 0 12px; font-size: 1.5rem; color: #1f2d3d; }
        p { margin: 0 0 16px; color: #4a5568; }
        label { display: block; margin: 8px 0 6px; font-weight: 600; color: #1f2d3d; }
        input { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; }
        button { margin-top: 14px; width: 100%; border: 0; background: #0d6efd; color: #fff; padding: 10px; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .error { border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; padding: 10px; border-radius: 8px; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Panel de Administrador</h1>
        <p>Inicia sesión para acceder al panel.</p>
        <?php if ($mostrarErrorCredenciales): ?>
            <div class="error">Credenciales inválidas.</div>
        <?php endif; ?>
        <form method="POST" action="procesar_admin_login.php">
            <label for="username">Usuario</label>
            <input id="username" name="username" required>

            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" required>

            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>
