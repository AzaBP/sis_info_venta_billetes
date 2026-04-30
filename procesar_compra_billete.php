<?php
// Procesa la compra de billete para un pasajero gestionado por el vendedor
require_once 'php/Conexion.php';
$pdo = (new Conexion())->conectar();
require_once 'php/Utils/Mailer.php';
require_once 'php/DAO/EmailCodeDAO.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pasajero = (int)($_POST['id_pasajero'] ?? 0);
    $id_ruta = (int)($_POST['id_ruta'] ?? 0);
    $fecha = $_POST['fecha'] ?? '';
    $numero_asiento = (int)($_POST['numero_asiento'] ?? 0);

    // Validar datos mínimos
    if (!$id_pasajero || !$id_ruta || !$fecha || !$numero_asiento) {
        header('Location: compra.php?error=datos_invalidos');
        exit;
    }

    // Buscar un viaje que coincida con la ruta y fecha
    $stmt = $pdo->prepare('SELECT id_viaje, id_tren FROM VIAJE WHERE id_ruta = :id_ruta AND fecha = :fecha LIMIT 1');
    $stmt->execute([':id_ruta' => $id_ruta, ':fecha' => $fecha]);
    $viaje = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$viaje) {
        header('Location: compra.php?error=viaje_no_encontrado');
        exit;
    }

    // Comprobar si el asiento está disponible
    $stmt = $pdo->prepare('SELECT estado FROM ASIENTO WHERE numero_asiento = :numero_asiento AND id_tren = :id_tren');
    $stmt->execute([':numero_asiento' => $numero_asiento, ':id_tren' => $viaje['id_tren']]);
    $asiento = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$asiento || $asiento['estado'] !== 'disponible') {
        header('Location: compra.php?error=asiento_no_disponible');
        exit;
    }

    // Marcar asiento como ocupado
    $stmt = $pdo->prepare('UPDATE ASIENTO SET estado = :estado WHERE numero_asiento = :numero_asiento AND id_tren = :id_tren');
    $stmt->execute([':estado' => 'ocupado', ':numero_asiento' => $numero_asiento, ':id_tren' => $viaje['id_tren']]);

    // Insertar billete (deberías tener una tabla BILLETE o similar, aquí ejemplo genérico)
    $stmt = $pdo->prepare('INSERT INTO BILLETE (id_pasajero, id_viaje, numero_asiento, fecha_compra) VALUES (:id_pasajero, :id_viaje, :numero_asiento, NOW())');
    $stmt->execute([
        ':id_pasajero' => $id_pasajero,
        ':id_viaje' => $viaje['id_viaje'],
        ':numero_asiento' => $numero_asiento
    ]);

    // Enviar código al email del pasajero
    $stmt = $pdo->prepare('SELECT u.email, u.nombre, u.apellido FROM usuario u JOIN pasajero p ON p.id_usuario = u.id_usuario WHERE p.id_pasajero = :id_pasajero LIMIT 1');
    $stmt->execute([':id_pasajero' => $id_pasajero]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && !empty($user['email'])) {
        $codigo = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
        $emailCodeDAO = new EmailCodeDAO();
        $emailCodeDAO->crearCodigo($id_pasajero, $user['email'], $codigo, 'ticket');

        $mailer = new Mailer();
        $subject = 'Tu código de billete';
        $body = "<p>Hola {$user['nombre']},</p><p>Tu código de billete es <b>$codigo</b>. Guárdalo para mostrar en el embarque.</p>";
        $sent = $mailer->send($user['email'], $user['nombre'].' '.$user['apellido'], $subject, $body);
        if (!$sent) {
            // Registrar fallo explícito para facilitar depuración
            $msg = sprintf("[MAIL ERROR] Failed to send ticket to %s (user id: %d) at %s\n", $user['email'], $id_pasajero, date('Y-m-d H:i:s'));
            error_log($msg);
        } else {
            $msg = sprintf("[MAIL OK] Ticket sent to %s (user id: %d) at %s\n", $user['email'], $id_pasajero, date('Y-m-d H:i:s'));
            error_log($msg);
        }
    }

    header('Location: vendedor.php?exito=billete_comprado');
    exit;
}
header('Location: compra.php?error=acceso');
exit;
