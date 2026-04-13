<?php
session_start();
// Solo permitir si el usuario es empleado
if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['tipo_usuario'] ?? '') !== 'empleado') {
    http_response_code(403);
    echo 'No autorizado';
    exit;
}
$id_pasajero = isset($_GET['id_pasajero']) ? (int)$_GET['id_pasajero'] : 0;
if ($id_pasajero > 0) {
    $_SESSION['cliente_gestionado'] = $id_pasajero;
    header('Location: ../compra.php');
    exit;
}
header('Location: ../vendedor.php?error=cliente_no_valido');
exit;
