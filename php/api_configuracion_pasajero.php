<?php
session_start();
header('Content-Type: application/json');
ob_start();

require_once __DIR__ . '/Conexion.php';

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'pasajero') {
    http_response_code(403);
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(['ok' => false, 'mensaje' => 'Sesion no valida']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$accion = strtolower(trim((string)($input['accion'] ?? '')));

if ($accion === '') {
    http_response_code(400);
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(['ok' => false, 'mensaje' => 'Accion no especificada']);
    exit;
}

function responder(bool $ok, string $mensaje, int $status = 200, array $extra = []): void
{
    http_response_code($status);
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(array_merge(['ok' => $ok, 'mensaje' => $mensaje], $extra));
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        responder(false, 'No se pudo conectar con la base de datos', 500);
    }

    $stmtPasajero = $pdo->prepare('SELECT id_pasajero FROM pasajero WHERE id_usuario = :id_usuario LIMIT 1');
    $stmtPasajero->execute([':id_usuario' => (int)$usuario['id_usuario']]);
    $idPasajero = (int)$stmtPasajero->fetchColumn();

    if ($idPasajero <= 0) {
        responder(false, 'No existe perfil de pasajero', 403);
    }

    if ($accion === 'actualizar_datos') {
        $nombre = trim((string)($input['nombre'] ?? ''));
        $apellido = trim((string)($input['apellido'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $telefono = trim((string)($input['telefono'] ?? ''));
        $fechaNacimiento = trim((string)($input['fecha_nacimiento'] ?? ''));
        $calle = trim((string)($input['calle'] ?? ''));
        $ciudad = trim((string)($input['ciudad'] ?? ''));
        $codigoPostal = trim((string)($input['codigo_postal'] ?? ''));
        $pais = trim((string)($input['pais'] ?? ''));

        if ($nombre === '' || $apellido === '' || $email === '') {
            responder(false, 'Nombre, apellido y email son obligatorios', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            responder(false, 'El email no es valido', 400);
        }

        $stmtEmail = $pdo->prepare('SELECT id_usuario FROM usuario WHERE email = :email AND id_usuario <> :id_usuario LIMIT 1');
        $stmtEmail->execute([
            ':email' => $email,
            ':id_usuario' => (int)$usuario['id_usuario']
        ]);
        if ($stmtEmail->fetchColumn()) {
            responder(false, 'El email ya esta en uso por otra cuenta', 409);
        }

        $fechaNacimientoDB = $fechaNacimiento !== '' ? $fechaNacimiento : null;

        $pdo->beginTransaction();

        $stmtUsuario = $pdo->prepare('UPDATE usuario
                                      SET nombre = :nombre, apellido = :apellido, email = :email, telefono = :telefono
                                      WHERE id_usuario = :id_usuario');
        $stmtUsuario->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':email' => $email,
            ':telefono' => $telefono !== '' ? $telefono : null,
            ':id_usuario' => (int)$usuario['id_usuario']
        ]);

        $stmtPasajeroUpdate = $pdo->prepare('UPDATE pasajero
                                             SET fecha_nacimiento = COALESCE(:fecha_nacimiento, fecha_nacimiento),
                                                 calle = :calle,
                                                 ciudad = :ciudad,
                                                 codigo_postal = :codigo_postal,
                                                 pais = :pais
                                             WHERE id_pasajero = :id_pasajero');
        $stmtPasajeroUpdate->execute([
            ':fecha_nacimiento' => $fechaNacimientoDB,
            ':calle' => $calle !== '' ? $calle : null,
            ':ciudad' => $ciudad !== '' ? $ciudad : null,
            ':codigo_postal' => $codigoPostal !== '' ? $codigoPostal : null,
            ':pais' => $pais !== '' ? $pais : null,
            ':id_pasajero' => $idPasajero
        ]);

        $pdo->commit();

        $_SESSION['usuario']['nombre'] = $nombre;
        $_SESSION['usuario']['apellido'] = $apellido;
        $_SESSION['usuario']['email'] = $email;

        responder(true, 'Datos actualizados correctamente');
    }

    if ($accion === 'guardar_notificaciones') {
        $notificacionesViaje = filter_var(($input['notificaciones_viaje'] ?? true), FILTER_VALIDATE_BOOLEAN);
        $notificacionesOfertas = filter_var(($input['notificaciones_ofertas'] ?? false), FILTER_VALIDATE_BOOLEAN);

        // Algunos entornos pueden no tener aun la columna newsletter creada.
        $mensajeGuardado = 'Preferencias guardadas correctamente';
        $stmtCol = $pdo->prepare("SELECT 1
                                  FROM information_schema.columns
                                  WHERE table_schema = 'public'
                                    AND table_name = 'pasajero'
                                    AND column_name = 'newsletter'
                                  LIMIT 1");
        $stmtCol->execute();
        $tieneNewsletter = (bool)$stmtCol->fetchColumn();

        if ($tieneNewsletter) {
            $stmtNotif = $pdo->prepare('UPDATE pasajero SET newsletter = :newsletter WHERE id_pasajero = :id_pasajero');
            $stmtNotif->bindValue(':newsletter', $notificacionesOfertas, PDO::PARAM_BOOL);
            $stmtNotif->bindValue(':id_pasajero', $idPasajero, PDO::PARAM_INT);
            $stmtNotif->execute();
        } else {
            $mensajeGuardado = 'Preferencias guardadas (la columna newsletter no existe en BD)';
        }

        $_SESSION['preferencias_pasajero'] = $_SESSION['preferencias_pasajero'] ?? [];
        $_SESSION['preferencias_pasajero']['notificaciones_viaje'] = $notificacionesViaje;

        responder(true, $mensajeGuardado, 200, [
            'notificaciones_viaje' => $notificacionesViaje,
            'notificaciones_ofertas' => $notificacionesOfertas
        ]);
    }

    if ($accion === 'cambiar_password') {
        $actual = (string)($input['password_actual'] ?? '');
        $nueva = (string)($input['password_nueva'] ?? '');
        $repetida = (string)($input['password_repetida'] ?? '');

        if ($actual === '' || $nueva === '' || $repetida === '') {
            responder(false, 'Completa todos los campos de contrasena', 400);
        }

        if ($nueva !== $repetida) {
            responder(false, 'La nueva contrasena no coincide', 400);
        }

        if (strlen($nueva) < 8) {
            responder(false, 'La nueva contrasena debe tener al menos 8 caracteres', 400);
        }

        $stmtPass = $pdo->prepare('SELECT password FROM usuario WHERE id_usuario = :id_usuario LIMIT 1');
        $stmtPass->execute([':id_usuario' => (int)$usuario['id_usuario']]);
        $hashActual = (string)$stmtPass->fetchColumn();

        if ($hashActual === '' || !password_verify($actual, $hashActual)) {
            responder(false, 'La contrasena actual no es correcta', 400);
        }

        $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
        $stmtUpdatePass = $pdo->prepare('UPDATE usuario SET password = :password WHERE id_usuario = :id_usuario');
        $stmtUpdatePass->execute([
            ':password' => $nuevoHash,
            ':id_usuario' => (int)$usuario['id_usuario']
        ]);

        responder(true, 'Contrasena actualizada correctamente');
    }

    responder(false, 'Accion no soportada', 400);
} catch (Throwable $e) {
    responder(false, 'Error interno en configuracion: ' . $e->getMessage(), 500);
}
