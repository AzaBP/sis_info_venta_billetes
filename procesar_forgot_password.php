<?php
require_once __DIR__ . '/php/DAO/UsuarioDAO.php';
require_once __DIR__ . '/php/DAO/EmailCodeDAO.php';
require_once __DIR__ . '/php/Utils/Mailer.php';
require_once __DIR__ . '/php/Conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $usuarioDAO = new UsuarioDAO();
    // Necesitamos obtener usuario por email. Añadimos lógica directa a DB
    $pdo = (new Conexion())->conectar();
    $stmt = $pdo->prepare('SELECT id_usuario, nombre, apellido FROM usuario WHERE email = :email LIMIT 1');
    $stmt->execute([':email'=>$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        header('Location: olvidaste_contrasena.php?error=no_encontrado');
        exit;
    }

    $codigo = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
    $emailCodeDAO = new EmailCodeDAO();
    $emailCodeDAO->crearCodigo($row['id_usuario'], $email, $codigo, 'password_reset');

    $mailer = new Mailer();
    $subject = 'Código para restablecer contraseña';
    $body = "<p>Hola {$row['nombre']},</p><p>Tu código para restablecer la contraseña es <b>$codigo</b>. Válido 1 hora.</p>";
    $mailer->send($email, $row['nombre'].' '.$row['apellido'], $subject, $body);

    header('Location: reset_password.php?email=' . urlencode($email));
    exit;
}
header('Location: inicio_sesion.html');
exit;
