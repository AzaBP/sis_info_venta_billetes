<?php
session_start();
require_once __DIR__ . '/php/auth_helpers.php';

$usuarioSesion = $_SESSION['usuario'] ?? null;

if (!$usuarioSesion) {
    header('Location: inicio_sesion.html');
    exit;
}

if (($usuarioSesion['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($usuarioSesion));
    exit;
}

header('Location: mis_billetes.php');
exit;
