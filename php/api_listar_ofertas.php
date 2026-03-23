<?php
// api_listar_ofertas.php
header('Content-Type: application/json');
require_once 'DAO/OfertaDAO.php';

try {
    $ofertaDAO = new OfertaDAO();
    $ofertas = $ofertaDAO->getOfertasActivas(); // Debes implementar este método si no existe
    echo json_encode([
        'success' => true,
        'ofertas' => $ofertas
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
