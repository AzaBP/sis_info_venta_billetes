<?php
header('Content-Type: application/json');

// Aquí definimos los abonos que tienes en tu base de datos con una descripción para la web
$abonos = [
    [
        "tipo" => "Mensual",
        "descripcion" => "Viajes ilimitados durante 30 días."
    ],
    [
        "tipo" => "Trimestral",
        "descripcion" => "Ahorra más viajando durante 90 días."
    ],
    [
        "tipo" => "Anual",
        "descripcion" => "La mejor opción para viajeros frecuentes."
    ],
    [
        "tipo" => "Estudiante",
        "descripcion" => "Descuentos especiales presentando tu carnet."
    ],
    [
        "tipo" => "Viajes_limitados",
        "descripcion" => "Paquete de viajes a precio reducido."
    ]
];

// Devolvemos el array en formato JSON
echo json_encode($abonos);
?>