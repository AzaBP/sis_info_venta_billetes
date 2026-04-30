<?php
// Lee la configuración SMTP desde variables de entorno.
// Para entornos de desarrollo puedes crear un archivo .env y exportar las variables.

return [
    'host' => getenv('SMTP_HOST') ?: 'smtp.example.com',
    'username' => getenv('SMTP_USER') ?: 'usuario@example.com',
    'password' => getenv('SMTP_PASS') ?: 'tu_password',
    'port' => getenv('SMTP_PORT') ?: 587,
    'secure' => getenv('SMTP_SECURE') ?: 'tls', // 'tls' o 'ssl' o ''
    'from_email' => getenv('SMTP_FROM') ?: 'no-reply@example.com',
    'from_name' => getenv('SMTP_FROM_NAME') ?: 'SISTEMA BILLETES'
];

