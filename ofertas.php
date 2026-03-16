<?php
session_start();
require_once __DIR__ . '/php/auth_helpers.php';
if (isset($_SESSION['usuario']) && ($_SESSION['usuario']['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($_SESSION['usuario']));
    exit;
}
require_once __DIR__ . '/php/Conexion.php';

try {
    $pdo = (new Conexion())->conectar();
    
    // 1. OBTENER PROMOCIONES (Desde la tabla PROMOCION)
    $stmtP = $pdo->query("SELECT codigo, descuento_porcentaje, fecha_fin 
                          FROM PROMOCION 
                          WHERE fecha_fin >= CURRENT_DATE 
                          ORDER BY descuento_porcentaje DESC");
    $promociones = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    // 2. OBTENER ABONOS (Desde la nueva tabla TIPO_ABONO)
    $stmtA = $pdo->query("SELECT tipo_codigo, nombre, descripcion, precio, icono, color 
                          FROM TIPO_ABONO 
                          ORDER BY precio ASC");
    $abonos = $stmtA->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si hay error de base de datos, las listas quedan vacías para que no explote la web
    $promociones = [];
    $abonos = [];
    // echo "Error: " . $e->getMessage(); // Descomenta esto para ver si hay errores SQL
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Ofertas y Abonos</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/ofertas.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb</div>
        <nav class="nav">
            <a href="index.php">Inicio</a>
            <a href="compra.html">Billetes</a>
            <a href="ofertas.php">Ofertas</a>
            <a href="ayuda.html">Ayuda</a>
        </nav>
    </header>

    <main class="offers-main">
        <div class="page-header">
            <h1>Descubre Nuestras Ofertas</h1>
            <p>Ahorra en tus viajes con nuestros abonos y promociones.</p>
        </div>

        <section>
            <h2 class="section-title"><i class="fa-solid fa-tags"></i> Promociones Activas</h2>
            <div id="promociones-container" class="grid-container">
                <?php if (empty($promociones)): ?>
                    <p>No hay promociones disponibles actualmente.</p>
                <?php else: ?>
                    <?php foreach ($promociones as $p): ?>
                        <div class="card">
                            <h3>Código Descuento</h3>
                            <div class="descuento"><?= (int)$p['descuento_porcentaje'] ?>%</div>
                            <div class="codigo"><?= htmlspecialchars($p['codigo']) ?></div>
                            <p>Válido hasta: <?= date('d/m/Y', strtotime($p['fecha_fin'])) ?></p>
                            <button onclick="copyToClipboard('<?= $p['codigo'] ?>')">Copiar Código</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <h2 class="section-title"><i class="fa-solid fa-ticket-alt"></i> Nuestros Abonos</h2>
            <div id="abonos-container" class="grid-container">
                <?php foreach ($abonos as $a): ?>
                    <div class="card" style="border-top-color: #0a2a66;">
                        <h3><?= htmlspecialchars($a['nombre']) ?></h3>
                        <p><?= htmlspecialchars($a['descripcion']) ?></p>
                        <button onclick="window.location.href='comprar_abono.php?tipo=<?= $a['tipo_codigo'] ?>'">
                            Comprar Ahora
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-bottom">&copy; 2026 TrainWeb</div>
    </footer>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert("¡Código " + text + " copiado!");
            });
        }
    </script>
</body>
</html>
