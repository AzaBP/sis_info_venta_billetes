<?php

require_once __DIR__ . '/php/DAO/UsuarioDAO.php';
require_once __DIR__ . '/php/DAO/PasajeroDAO.php';
require_once __DIR__ . '/php/VO/Usuario.php';
require_once __DIR__ . '/php/VO/Pasajero.php';
require_once __DIR__ . '/php/Conexion.php';
require_once __DIR__ . '/php/DAO/EmailCodeDAO.php';
require_once __DIR__ . '/php/Utils/Mailer.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $apellido = trim((string)($_POST['apellido'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $passwordPlano = (string)($_POST['password'] ?? '');
    $password = password_hash($passwordPlano, PASSWORD_DEFAULT);
    $telefono = trim((string)($_POST['telefono'] ?? ''));
    $tipoUsuario = "pasajero";

    $aceptaTerminos = isset($_POST['terminos']);
    $aceptaPrivacidad = isset($_POST['privacidad']);
    $newsletter = isset($_POST['newsletter']);

    if (!$aceptaTerminos || !$aceptaPrivacidad) {
        header("Location: registro.html?error=aceptar_politicas&step=4");
        exit;
    }

    $fechaNacimiento = trim((string)($_POST['nacimiento'] ?? ''));
    $genero = trim((string)($_POST['genero'] ?? ''));
    $tipoDocumento = trim((string)($_POST['tipo_documento'] ?? ''));
    $numeroDocumento = trim((string)($_POST['numero_documento'] ?? ''));
    $calle = trim((string)($_POST['calle'] ?? ''));
    $ciudad = trim((string)($_POST['ciudad'] ?? ''));
    $codigoPostal = trim((string)($_POST['codigo_postal'] ?? ''));
    $pais = trim((string)($_POST['pais'] ?? ''));

    if (
        $nombre === '' || $apellido === '' || $email === '' || $passwordPlano === '' || $telefono === '' ||
        $fechaNacimiento === '' || $genero === '' || $tipoDocumento === '' || $numeroDocumento === '' ||
        $calle === '' || $ciudad === '' || $codigoPostal === '' || $pais === ''
    ) {
        header("Location: registro.html?error=datos_incompletos&step=1");
        exit;
    }

    $usuarioDAO = new UsuarioDAO();

    // 🔎 Verificar si el email ya existe
    if ($usuarioDAO->existeEmail($email)) {
        header("Location: registro.html?error=usuario_existente&step=2");
        exit;
    }

    // 1️⃣ Insertar Usuario
    $usuario = new Usuario(
        null,
        $nombre,
        $apellido,
        $email,
        $password,
        $telefono,
        $tipoUsuario
    );

    $idUsuario = $usuarioDAO->insertar($usuario);

    if (!$idUsuario) {
        header("Location: registro.html?error=error_usuario&step=1");
        exit;
    }

    // 2️⃣ Insertar Pasajero
    $pasajero = new Pasajero(
        null,
        $idUsuario,
        $fechaNacimiento,
        $genero,
        $tipoDocumento,
        $numeroDocumento,
        $calle,
        $ciudad,
        $codigoPostal,
        $pais,
        $aceptaTerminos,
        $aceptaPrivacidad,
        $newsletter
    );

    $pasajeroDAO = new PasajeroDAO();
    $resultado = $pasajeroDAO->insertar($pasajero);

    if ($resultado) {
        // Generar código de verificación y enviar por email
        $codigo = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
        $emailCodeDAO = new EmailCodeDAO();
        $emailCodeDAO->crearCodigo($idUsuario, $email, $codigo, 'verification');

        $mailer = new Mailer();
        $subject = 'Verifica tu email';
        $body = "<p>Hola $nombre,</p><p>Tu código de verificación es <b>$codigo</b>. Válido 1 hora.</p>";
        $mailer->send($email, $nombre . ' ' . $apellido, $subject, $body);

        header("Location: verificar_email.php?email=" . urlencode($email));
        exit;
    } else {
        header("Location: registro.html?error=error_pasajero&step=1");
        exit;
    }
}
?>
