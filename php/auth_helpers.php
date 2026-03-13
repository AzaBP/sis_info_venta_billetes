<?php

function trainwebAdminEmails(): array
{
    $raw = getenv('TRAINWEB_ADMIN_EMAILS');
    if ($raw === false) {
        $raw = '';
    }

    $emails = array_filter(array_map('trim', explode(',', $raw)));
    return array_map('strtolower', $emails);
}

function trainwebEsAdministrador(array $usuario): bool
{
    $email = strtolower(trim($usuario['email'] ?? ''));
    if ($email === '') {
        return false;
    }

    $admins = trainwebAdminEmails();
    if (count($admins) === 0) {
        // Sin lista explicita de admins, nadie obtiene rol admin por defecto.
        return false;
    }

    return in_array($email, $admins, true);
}

function trainwebRutaPorRol(array $usuario): string
{
    $tipoUsuario = strtolower(trim($usuario['tipo_usuario'] ?? ''));
    $tipoEmpleado = strtolower(trim($usuario['tipo_empleado'] ?? ''));

    if ($tipoUsuario === 'pasajero') {
        return 'perfil_pasajero.php';
    }

    if ($tipoUsuario === 'empleado') {
        if (trainwebEsAdministrador($usuario)) {
            return 'registro_empleado.php';
        }

        if ($tipoEmpleado === 'vendedor') {
            return 'vendedor.php';
        }

        if ($tipoEmpleado === 'mantenimiento') {
            return 'mantenimiento.php';
        }

        if ($tipoEmpleado === 'maquinista') {
            return 'maquinista.php';
        }
    }

    return 'index.php';
}
