<?php
require_once __DIR__ . '/php/DAO/EmailCodeDAO.php';
require_once __DIR__ . '/php/DAO/UsuarioDAO.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $codigo = $_POST['codigo'] ?? '';

    $dao = new EmailCodeDAO();
    $res = $dao->validarCodigo($email, $codigo, 'verification');
    if (!$res) {
        header('Location: verificar_email.php?email=' . urlencode($email) . '&error=invalid');
        exit;
    }

    // Marcar usado y actualizar usuario
    $dao->marcarUsado($res['id']);
    if (!empty($res['id_usuario'])) {
        $usuarioDAO = new UsuarioDAO();
        $usuarioDAO->setEmailVerified($res['id_usuario'], true);
    }

    header('Location: inicio_sesion.html?verificado=ok');
    exit;
}

header('Location: inicio_sesion.html');
exit;
