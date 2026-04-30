<?php
require_once __DIR__ . '/php/DAO/EmailCodeDAO.php';
require_once __DIR__ . '/php/Utils/Mailer.php';
require_once __DIR__ . '/php/Conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));

    if ($email === '') {
        header('Location: olvidaste_contrasena.php?error=no_encontrado');
        exit;
    }

    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        header('Location: olvidaste_contrasena.php?error=conexion');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id_usuario, nombre, apellido, email FROM usuario WHERE LOWER(email) = LOWER(:email) LIMIT 1');
    $stmt->execute([':email'=>$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        header('Location: olvidaste_contrasena.php?error=no_encontrado');
        exit;
    }

    $mailer = new Mailer();
    if ($mailer->getConfigIssue() !== null) {
        header('Location: olvidaste_contrasena.php?error=smtp_config');
        exit;
    }

    $codigo = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
    $emailCodeDAO = new EmailCodeDAO();
    $codigoId = $emailCodeDAO->crearCodigo((int)$row['id_usuario'], (string)$row['email'], $codigo, 'password_reset');

    $subject = 'Código para restablecer contraseña';
    $body = "<p>Hola {$row['nombre']},</p><p>Tu código para restablecer la contraseña es <b>$codigo</b>. Válido 1 hora.</p>";
    $sent = $mailer->send((string)$row['email'], trim(((string)$row['nombre']) . ' ' . ((string)$row['apellido'])), $subject, $body);

    if (!$sent) {
        if ($codigoId) {
            $emailCodeDAO->marcarUsado((int)$codigoId);
        }
        header('Location: olvidaste_contrasena.php?error=envio');
        exit;
    }

    header('Location: reset_password.php?email=' . urlencode((string)$row['email']));
    exit;
}
header('Location: inicio_sesion.html');
exit;
