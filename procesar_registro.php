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

    // Log non-sensitive POST fields to help debug insertion errors (avoid logging raw password)
    error_log('[REGISTRO] POST data: nombre=' . $nombre . ' email=' . $email . ' telefono=' . $telefono . ' nacimiento=' . $fechaNacimiento);

    // Validate fechaNacimiento is a valid date (expecting YYYY-MM-DD from <input type="date">)
    $dt = DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
    $dt_errors = DateTime::getLastErrors();
    $warningCount = 0;
    $errorCount = 0;
    if (is_array($dt_errors)) {
        $warningCount = isset($dt_errors['warning_count']) ? (int)$dt_errors['warning_count'] : 0;
        $errorCount = isset($dt_errors['error_count']) ? (int)$dt_errors['error_count'] : 0;
    }

    if (!$dt || $warningCount > 0 || $errorCount > 0) {
        error_log('[REGISTRO] Fecha de nacimiento inválida: ' . $fechaNacimiento);
        header("Location: registro.html?error=fecha_invalida&step=1");
        exit;
    }

    // Normalize date to Y-m-d (safe for Postgres DATE)
    $fechaNacimiento = $dt->format('Y-m-d');

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
        // Avoid ambiguous characters like O/0 and I/1 so the code is easier to read.
        $caracteresCodigo = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $codigo = '';
        for ($i = 0; $i < 6; $i++) {
            $codigo .= $caracteresCodigo[random_int(0, strlen($caracteresCodigo) - 1)];
        }
        $emailCodeDAO = new EmailCodeDAO();
        $codigoId = $emailCodeDAO->crearCodigo($idUsuario, $email, $codigo, 'verification');
        if (!$codigoId) {
            error_log('[REGISTRO] No se pudo guardar el código de verificación para email=' . $email . ' id_usuario=' . $idUsuario);
            header("Location: registro.html?error=error_codigo_verificacion&step=4");
            exit;
        }

        error_log('[REGISTRO] Código de verificación guardado id=' . $codigoId . ' email=' . $email . ' codigo=' . $codigo);

        $nombreHtml = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $body = "<p>Hola $nombreHtml,</p><p>Tu código de verificación es <b>$codigo</b>. Válido 1 hora.</p>";

        $mailer = new Mailer();
        $subject = 'Verifica tu email';
        $mailer->send($email, $nombre . ' ' . $apellido, $subject, $body);

        header("Location: verificar_email.php?email=" . urlencode($email));
        exit;
    } else {
        header("Location: registro.html?error=error_pasajero&step=1");
        exit;
    }
}
?>
