<?php
session_start();

require_once __DIR__ . '/php/Conexion.php';
require_once __DIR__ . '/php/auth_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inicio_sesion.html?error=metodo');
    exit;
}

$identificador = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if ($identificador === '' || $password === '') {
    header('Location: inicio_sesion.html?error=campos_vacios');
    exit;
}

try {
    $conexion = new Conexion();
    $pdo = $conexion->conectar();

    if (!$pdo) {
        header('Location: inicio_sesion.html?error=conexion');
        exit;
    }

    $sql = "SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.password, u.tipo_usuario, u.email_verificado, e.tipo_empleado
            FROM usuario u
            LEFT JOIN empleado e ON e.id_usuario = u.id_usuario
            WHERE u.email = :identificador
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':identificador' => $identificador]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !password_verify($password, $usuario['password'])) {
        header('Location: inicio_sesion.html?error=credenciales');
        exit;
    }

    // Bloquear inicio de sesión si el correo no está verificado
    if (isset($usuario['email_verificado']) && !$usuario['email_verificado']) {
        header('Location: inicio_sesion.html?error=email_no_verificado');
        exit;
    }

    if (($usuario['tipo_usuario'] ?? '') !== 'pasajero') {
        header('Location: employee_login.php?error=solo_empleados');
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id_usuario' => (int)$usuario['id_usuario'],
        'nombre' => $usuario['nombre'],
        'apellido' => $usuario['apellido'],
        'email' => $usuario['email'],
        'tipo_usuario' => $usuario['tipo_usuario'],
        'tipo_empleado' => $usuario['tipo_empleado'] ?? null
    ];

    if ($remember) {
        setcookie('recordarme_email', $usuario['email'], time() + (60 * 60 * 24 * 30), '/', '', false, true);
    } else if (isset($_COOKIE['recordarme_email'])) {
        setcookie('recordarme_email', '', time() - 3600, '/', '', false, true);
    }

    header('Location: ' . trainwebRutaPorRol($_SESSION['usuario']));
    exit;
} catch (Throwable $e) {
    header('Location: inicio_sesion.html?error=interno');
    exit;
}
?>
