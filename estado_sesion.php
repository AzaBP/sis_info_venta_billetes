<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$usuario = $_SESSION['usuario'] ?? null;

if (!$usuario) {
    echo json_encode([
        'logeado' => false
    ]);
    exit;
}

echo json_encode([
    'logeado' => true,
    'usuario' => [
        'id_usuario' => $usuario['id_usuario'] ?? null,
        'nombre' => $usuario['nombre'] ?? '',
        'apellido' => $usuario['apellido'] ?? '',
        'email' => $usuario['email'] ?? '',
        'tipo_usuario' => $usuario['tipo_usuario'] ?? '',
        'tipo_empleado' => $usuario['tipo_empleado'] ?? ''
    ]
]);
?>
