<?php
session_start();
header('Content-Type: application/json');
ob_start();

require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/ConexionMongo.php';

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'pasajero') {
    http_response_code(403);
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(['ok' => false, 'mensaje' => 'Sesion no valida']);
    exit;
}

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        throw new RuntimeException('No se pudo conectar con la base de datos SQL');
    }

    $stmtPasajero = $pdo->prepare('SELECT id_pasajero FROM pasajero WHERE id_usuario = :id_usuario LIMIT 1');
    $stmtPasajero->execute([':id_usuario' => (int)$usuario['id_usuario']]);
    $idPasajero = (int)$stmtPasajero->fetchColumn();

    if ($idPasajero <= 0) {
        http_response_code(404);
        if (ob_get_length()) {
            ob_clean();
        }
        echo json_encode(['ok' => false, 'mensaje' => 'No se encontro el perfil de pasajero']);
        exit;
    }

    $mongo = new ConexionMongo();
    $dbMongo = $mongo->conectar();
    if (!$dbMongo) {
        throw new RuntimeException('No se pudo conectar con MongoDB');
    }

    $coleccionBilletes = $dbMongo->selectCollection('billetes');
    $coleccionBilletes->deleteMany(['id_pasajero' => $idPasajero]);

    $pdo->beginTransaction();

    $stmtEliminarPasajero = $pdo->prepare('DELETE FROM pasajero WHERE id_pasajero = :id_pasajero');
    $stmtEliminarPasajero->execute([':id_pasajero' => $idPasajero]);

    $stmtEliminarUsuario = $pdo->prepare('DELETE FROM usuario WHERE id_usuario = :id_usuario');
    $stmtEliminarUsuario->execute([':id_usuario' => (int)$usuario['id_usuario']]);

    $pdo->commit();

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();

    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(['ok' => true, 'mensaje' => 'Cuenta eliminada correctamente', 'redirect' => 'inicio_sesion.html']);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(['ok' => false, 'mensaje' => 'Error al eliminar la cuenta: ' . $e->getMessage()]);
} finally {
    restore_error_handler();
}