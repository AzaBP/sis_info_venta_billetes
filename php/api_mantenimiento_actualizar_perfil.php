<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/auth_helpers.php';

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'empleado') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if (($usuario['tipo_empleado'] ?? '') !== 'mantenimiento' && !trainwebEsAdministrador($usuario)) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$especialidad = trim($_POST['especialidad'] ?? '');
$turno = strtolower(trim($_POST['turno'] ?? ''));
$certificaciones = trim($_POST['certificaciones'] ?? '');

if ($nombre === '' || $apellido === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre, apellido y email son obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email no valido.']);
    exit;
}

$turnosValidos = ['manana', 'tarde', 'noche', 'rotativo'];
if ($turno !== '' && !in_array($turno, $turnosValidos, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Turno no valido.']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('Conexion no disponible');
    }

    $stmt = $pdo->prepare(
        "SELECT e.id_empleado
         FROM empleado e
         WHERE e.id_usuario = :id_usuario
         LIMIT 1"
    );
    $stmt->execute([':id_usuario' => (int)$usuario['id_usuario']]);
    $idEmpleado = (int)$stmt->fetchColumn();
    if ($idEmpleado <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Empleado no valido.']);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "UPDATE usuario
         SET nombre = :nombre, apellido = :apellido, email = :email, telefono = :telefono
         WHERE id_usuario = :id_usuario"
    );
    $stmt->execute([
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':email' => $email,
        ':telefono' => $telefono !== '' ? $telefono : null,
        ':id_usuario' => (int)$usuario['id_usuario']
    ]);

    $stmt = $pdo->prepare(
        "UPDATE mantenimiento
         SET especialidad = :especialidad, turno = :turno, certificaciones = :certificaciones
         WHERE id_empleado = :id_empleado"
    );
    $stmt->execute([
        ':especialidad' => $especialidad !== '' ? $especialidad : null,
        ':turno' => $turno !== '' ? $turno : null,
        ':certificaciones' => $certificaciones !== '' ? $certificaciones : null,
        ':id_empleado' => $idEmpleado
    ]);

    $pdo->commit();

    $_SESSION['usuario']['nombre'] = $nombre;
    $_SESSION['usuario']['apellido'] = $apellido;
    $_SESSION['usuario']['email'] = $email;
    $_SESSION['usuario']['telefono'] = $telefono;

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar perfil.']);
}
