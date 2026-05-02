<?php
$updatedAt = '30/04/2026';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso Legal - TrainWeb</title>
    <link rel="stylesheet" href="css/session_menu.css">
    <link rel="stylesheet" href="css/legal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="legal-page">
    <main class="legal-shell">
        <section class="legal-hero">
            <h1>Aviso Legal</h1>
            <p>Información obligatoria sobre la titularidad del sitio web y las condiciones de uso de la plataforma TrainWeb.</p>
        </section>

        <section class="legal-content">
            <h2>1. Titularidad del sitio</h2>
            <p>Este sitio web es propiedad de TrainWeb Corp., con domicilio social en la Estación Central de Ferrocarriles. Nuestra misión es facilitar el acceso al transporte ferroviario nacional.</p>

            <h2>2. Propiedad intelectual</h2>
            <p>Todos los contenidos de TrainWeb, incluyendo diseños, logotipos y código, están protegidos por derechos de propiedad intelectual. Queda prohibida su reproducción sin permiso previo.</p>

            <h2>3. Exención de responsabilidad</h2>
            <p>TrainWeb no se hace responsable de las interrupciones del servicio por causas ajenas, ni de la exactitud absoluta de los horarios proporcionados por las operadoras externas en tiempo real.</p>

            <h2>4. Enlaces externos</h2>
            <p>Nuestra plataforma puede contener enlaces a sitios de terceros. No tenemos control sobre sus contenidos ni asumimos responsabilidad por sus políticas o prácticas de privacidad.</p>

            <h2>5. Jurisdicción</h2>
            <p>Cualquier controversia relacionada con el uso de TrainWeb se someterá a la legislación nacional vigente y a los tribunales competentes de la ciudad sede.</p>

            <div class="legal-note">Última actualización: <?php echo htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8'); ?></div>
            <a class="legal-back" href="index.php"><i class="fa-solid fa-arrow-left"></i> Volver al inicio</a>
        </section>
    </main>
</body>
</html>
