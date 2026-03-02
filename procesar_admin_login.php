<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === 'admin' && $password === 'sysadmin2026') {
    session_regenerate_id(true);
    $_SESSION['admin_simple_auth'] = true;
    header('Location: registro_empleado.php');
    exit;
}

header('Location: admin_login.php?error=credenciales');
exit;

