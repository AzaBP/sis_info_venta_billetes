<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/php/Utils/Mailer.php';

// Ajusta destinatario para la prueba
$to = $argv[1] ?? getenv('TEST_MAIL_TO') ?? 'tu@correo.destino';
$name = 'Destinatario Prueba';
$subject = 'Prueba de envío desde SIS';
$body = '<p>Este es un correo de prueba enviado por la aplicación.</p>';

$m = new Mailer();
$result = $m->send($to, $name, $subject, $body);
if ($result) {
    echo "Mail enviado correctamente a $to\n";
    exit(0);
} else {
    echo "Error al enviar mail. Revisa logs y configuración SMTP.\n";
    exit(1);
}
