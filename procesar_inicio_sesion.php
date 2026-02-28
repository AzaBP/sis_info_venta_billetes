<?php
session_start();

require_once __DIR__ . '/php/Conexion.php';

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

    $sql = "SELECT id_usuario, nombre, apellido, email, password, tipo_usuario
            FROM usuario
            WHERE email = :identificador
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':identificador' => $identificador]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !password_verify($password, $usuario['password'])) {
        header('Location: inicio_sesion.html?error=credenciales');
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id_usuario' => (int)$usuario['id_usuario'],
        'nombre' => $usuario['nombre'],
        'apellido' => $usuario['apellido'],
        'email' => $usuario['email'],
        'tipo_usuario' => $usuario['tipo_usuario']
    ];

    if ($remember) {
        setcookie('recordarme_email', $usuario['email'], time() + (60 * 60 * 24 * 30), '/', '', false, true);
    } else if (isset($_COOKIE['recordarme_email'])) {
        setcookie('recordarme_email', '', time() - 3600, '/', '', false, true);
    }

    if ($usuario['tipo_usuario'] === 'pasajero') {
        header('Location: perfil_pasajero.html');
    } else {
        header('Location: index.html');
    }
    exit;
} catch (Throwable $e) {
    header('Location: inicio_sesion.html?error=interno');
    exit;
}
?>
