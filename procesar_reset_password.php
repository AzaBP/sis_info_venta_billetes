<?php
require_once __DIR__ . '/php/DAO/EmailCodeDAO.php';
require_once __DIR__ . '/php/Conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    $password = $_POST['password'] ?? '';

    $dao = new EmailCodeDAO();
    $res = $dao->validarCodigo($email, $codigo, 'password_reset');
    if (!$res) {
        header('Location: reset_password.php?email=' . urlencode($email) . '&error=invalid');
        exit;
    }

    // Actualizar contraseña del usuario
    $pdo = (new Conexion())->conectar();
    $stmt = $pdo->prepare('UPDATE usuario SET password = :password WHERE id_usuario = :id');
    $stmt->execute([':password' => password_hash($password, PASSWORD_DEFAULT), ':id' => $res['id_usuario']]);

    $dao->marcarUsado($res['id']);

    header('Location: inicio_sesion.html?reset=ok');
    exit;
}

header('Location: inicio_sesion.html');
exit;
