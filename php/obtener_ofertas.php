<?php
header('Content-Type: application/json');
require_once 'Conexion.php';

try {
    $pdo = (new Conexion())->conectar();
    
    // 1. Obtener Promociones
    $stmtP = $pdo->query("SELECT codigo, descuento_porcentaje, fecha_fin FROM PROMOCION WHERE fecha_fin >= CURRENT_DATE");
    $promociones = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Definir Abonos (puedes sacarlos de la DB si tienes una tabla de tipos, o enviarlos fijos)
    $abonos = [
        ["tipo" => "mensual", "nombre" => "Abono Mensual", "desc" => "30 días de viajes ilimitados."],
        ["tipo" => "anual", "nombre" => "Abono Anual", "desc" => "La mejor opción para todo el año."],
        ["tipo" => "estudiante", "nombre" => "Abono Estudiante", "desc" => "Descuento especial para jóvenes."]
    ];

    echo json_encode([
        "promociones" => $promociones,
        "abonos" => $abonos
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}