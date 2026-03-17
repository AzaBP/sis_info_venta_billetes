<?php
session_start();

$usuario = $_SESSION['usuario'] ?? null;
$redir = ($usuario && ($usuario['tipo_usuario'] ?? '') === 'empleado')
    ? 'employee_login.php'
    : 'index.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();
setcookie('recordarme_email', '', time() - 3600, '/', '', false, true);

header('Location: ' . $redir);
exit;
?>
