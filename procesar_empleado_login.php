<?php
session_start();

require_once __DIR__ . '/php/Conexion.php';
require_once __DIR__ . '/php/auth_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: employee_login.php?error=metodo&attempt=1');
    exit;
}

$identificador = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if ($identificador === '' || $password === '') {
    header('Location: employee_login.php?error=campos_vacios&attempt=1');
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        header('Location: employee_login.php?error=conexion&attempt=1');
        exit;
    }

    $sql = "SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.password, u.tipo_usuario, e.tipo_empleado
            FROM usuario u
            LEFT JOIN empleado e ON e.id_usuario = u.id_usuario
            WHERE LOWER(u.email) = LOWER(:identificador)
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':identificador' => $identificador]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $passwordOk = false;
    if ($usuario && password_verify($password, $usuario['password'])) {
        $passwordOk = true;
        if (password_needs_rehash($usuario['password'], PASSWORD_DEFAULT)) {
            $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
            $stmtUpdate = $pdo->prepare('UPDATE usuario SET password = :password WHERE id_usuario = :id_usuario');
            $stmtUpdate->execute([
                ':password' => $nuevoHash,
                ':id_usuario' => (int)$usuario['id_usuario']
            ]);
        }
    } elseif ($usuario && hash_equals((string)$usuario['password'], (string)$password)) {
        // Soporta contrasenas en texto plano en BD y las migra a hash.
        $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
        $stmtUpdate = $pdo->prepare('UPDATE usuario SET password = :password WHERE id_usuario = :id_usuario');
        $stmtUpdate->execute([
            ':password' => $nuevoHash,
            ':id_usuario' => (int)$usuario['id_usuario']
        ]);
        $passwordOk = true;
    }

    if (!$usuario || !$passwordOk) {
        header('Location: employee_login.php?error=credenciales&attempt=1');
        exit;
    }

    if (($usuario['tipo_usuario'] ?? '') !== 'empleado') {
        header('Location: employee_login.php?error=solo_empleados&attempt=1');
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
    header('Location: employee_login.php?error=interno&attempt=1');
    exit;
}
?>
