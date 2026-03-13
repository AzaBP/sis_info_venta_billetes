<?php
session_start();

require_once __DIR__ . '/php/Conexion.php';
require_once __DIR__ . '/php/auth_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro_empleado.php?error=metodo_invalido');
    exit;
}

$usuarioSesion = $_SESSION['usuario'] ?? null;
$adminSimple = !empty($_SESSION['admin_simple_auth']) && $_SESSION['admin_simple_auth'] === true;
$adminPorUsuario = $usuarioSesion && trainwebEsAdministrador($usuarioSesion);

if (!$adminSimple && !$adminPorUsuario) {
    header('Location: admin_login.php?error=no_autorizado');
    exit;
}

function redirigirAdmin(string $mensaje, bool $ok = false): void
{
    $key = $ok ? 'ok' : 'error';
    header('Location: registro_empleado.php?' . $key . '=' . urlencode($mensaje));
    exit;
}

function valor(string $key, string $default = ''): string
{
    return trim($_POST[$key] ?? $default);
}

$accion = valor('accion');

try {
    $pdo = (new Conexion())->conectar();
    if (!$pdo) {
        redirigirAdmin('conexion');
    }

    if ($accion === 'crear_empleado') {
        $nombre = valor('nombre');
        $apellido = valor('apellido');
        $email = valor('email');
        $telefono = valor('telefono');
        $passwordPlano = $_POST['password'] ?? '';
        $tipoEmpleado = valor('tipo_empleado');

        if ($nombre === '' || $apellido === '' || $email === '' || $telefono === '' || $passwordPlano === '' || $tipoEmpleado === '') {
            redirigirAdmin('campos_obligatorios');
        }
        if (!in_array($tipoEmpleado, ['vendedor', 'mantenimiento', 'maquinista'], true)) {
            redirigirAdmin('tipo_empleado_no_permitido');
        }

        $stmtExiste = $pdo->prepare('SELECT 1 FROM usuario WHERE email = :email');
        $stmtExiste->execute([':email' => $email]);
        if ($stmtExiste->fetchColumn()) {
            redirigirAdmin('email_existente');
        }

        $pdo->beginTransaction();

        $stmtUsuario = $pdo->prepare(
            "INSERT INTO usuario (nombre, apellido, email, password, telefono, tipo_usuario)
             VALUES (:nombre, :apellido, :email, :password, :telefono, 'empleado')
             RETURNING id_usuario"
        );
        $stmtUsuario->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':email' => $email,
            ':password' => password_hash($passwordPlano, PASSWORD_DEFAULT),
            ':telefono' => $telefono
        ]);
        $idUsuario = (int)$stmtUsuario->fetchColumn();

        $stmtEmpleado = $pdo->prepare(
            'INSERT INTO empleado (id_usuario, tipo_empleado) VALUES (:id_usuario, :tipo_empleado) RETURNING id_empleado'
        );
        $stmtEmpleado->execute([
            ':id_usuario' => $idUsuario,
            ':tipo_empleado' => $tipoEmpleado
        ]);
        $idEmpleado = (int)$stmtEmpleado->fetchColumn();

        if ($tipoEmpleado === 'vendedor') {
            $comision = (float)($_POST['comision_porcentaje'] ?? 0);
            $region = valor('region', 'General');
            $stmtRol = $pdo->prepare(
                'INSERT INTO vendedor (id_empleado, comision_porcentaje, region) VALUES (:id_empleado, :comision, :region)'
            );
            $stmtRol->execute([
                ':id_empleado' => $idEmpleado,
                ':comision' => $comision,
                ':region' => $region
            ]);
        } else if ($tipoEmpleado === 'mantenimiento') {
            $especialidad = valor('especialidad', 'General');
            $turno = valor('turno', 'manana');
            $certificaciones = valor('certificaciones');
            $stmtRol = $pdo->prepare(
                'INSERT INTO mantenimiento (id_empleado, especialidad, turno, certificaciones) VALUES (:id_empleado, :especialidad, :turno, :certificaciones)'
            );
            $stmtRol->execute([
                ':id_empleado' => $idEmpleado,
                ':especialidad' => $especialidad,
                ':turno' => $turno,
                ':certificaciones' => $certificaciones
            ]);
        } else {
            $licencia = valor('licencia');
            $experiencia = (int)($_POST['experiencia_anos'] ?? 0);
            $horario = valor('horario_preferido', 'diurno');
            $stmtRol = $pdo->prepare(
                'INSERT INTO maquinista (id_empleado, licencia, experiencia_años, horario_preferido) VALUES (:id_empleado, :licencia, :experiencia, :horario)'
            );
            $stmtRol->execute([
                ':id_empleado' => $idEmpleado,
                ':licencia' => $licencia,
                ':experiencia' => $experiencia,
                ':horario' => $horario
            ]);
            $asignarViaje = (int)($_POST['asignar_viaje'] ?? 0);
            if ($asignarViaje > 0) {
                $stmtViaje = $pdo->prepare('UPDATE viaje SET id_maquinista = :id_maquinista WHERE id_viaje = :id_viaje');
                $stmtViaje->execute([
                    ':id_maquinista' => $idEmpleado,
                    ':id_viaje' => $asignarViaje
                ]);
            }
        }

        $pdo->commit();
        redirigirAdmin('empleado_creado', true);
    }

    if ($accion === 'crear_pasajero') {
        $nombre = valor('nombre');
        $apellido = valor('apellido');
        $email = valor('email');
        $telefono = valor('telefono');
        $passwordPlano = $_POST['password'] ?? '';
        $fechaNacimiento = valor('fecha_nacimiento');
        $genero = valor('genero');
        $tipoDocumento = valor('tipo_documento');
        $numeroDocumento = valor('numero_documento');
        $calle = valor('calle');
        $ciudad = valor('ciudad');
        $codigoPostal = valor('codigo_postal');
        $pais = valor('pais');

        if ($nombre === '' || $apellido === '' || $email === '' || $telefono === '' || $passwordPlano === '' || $fechaNacimiento === '' || $genero === '' || $tipoDocumento === '') {
            redirigirAdmin('campos_obligatorios');
        }

        $stmtExiste = $pdo->prepare('SELECT 1 FROM usuario WHERE email = :email');
        $stmtExiste->execute([':email' => $email]);
        if ($stmtExiste->fetchColumn()) {
            redirigirAdmin('email_existente');
        }

        $pdo->beginTransaction();

        $stmtUsuario = $pdo->prepare(
            "INSERT INTO usuario (nombre, apellido, email, password, telefono, tipo_usuario)
             VALUES (:nombre, :apellido, :email, :password, :telefono, 'pasajero')
             RETURNING id_usuario"
        );
        $stmtUsuario->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':email' => $email,
            ':password' => password_hash($passwordPlano, PASSWORD_DEFAULT),
            ':telefono' => $telefono
        ]);
        $idUsuario = (int)$stmtUsuario->fetchColumn();

        $stmtPasajero = $pdo->prepare(
            'INSERT INTO pasajero (id_usuario, fecha_nacimiento, genero, tipo_documento, numero_documento, calle, ciudad, codigo_postal, pais, acepta_terminos, acepta_privacidad, newsletter)
             VALUES (:id_usuario, :fecha_nacimiento, :genero, :tipo_documento, :numero_documento, :calle, :ciudad, :codigo_postal, :pais, true, true, false)'
        );
        $stmtPasajero->execute([
            ':id_usuario' => $idUsuario,
            ':fecha_nacimiento' => $fechaNacimiento,
            ':genero' => $genero,
            ':tipo_documento' => $tipoDocumento,
            ':numero_documento' => $numeroDocumento,
            ':calle' => $calle,
            ':ciudad' => $ciudad,
            ':codigo_postal' => $codigoPostal,
            ':pais' => $pais
        ]);

        $pdo->commit();
        redirigirAdmin('pasajero_creado', true);
    }

    if ($accion === 'actualizar_usuario') {
        $idUsuario = (int)($_POST['id_usuario'] ?? 0);
        $nombre = valor('nombre');
        $apellido = valor('apellido');
        $email = valor('email');
        $telefono = valor('telefono');

        if ($idUsuario <= 0 || $nombre === '' || $apellido === '' || $email === '') {
            redirigirAdmin('datos_invalidos');
        }

        $stmtTipo = $pdo->prepare(
            'SELECT u.tipo_usuario, e.id_empleado, e.tipo_empleado
             FROM usuario u
             LEFT JOIN empleado e ON e.id_usuario = u.id_usuario
             WHERE u.id_usuario = :id_usuario'
        );
        $stmtTipo->execute([':id_usuario' => $idUsuario]);
        $tipoData = $stmtTipo->fetch(PDO::FETCH_ASSOC);
        if (!$tipoData) {
            redirigirAdmin('usuario_no_encontrado');
        }

        $pdo->beginTransaction();

        $stmtUsuario = $pdo->prepare(
            'UPDATE usuario SET nombre = :nombre, apellido = :apellido, email = :email, telefono = :telefono WHERE id_usuario = :id_usuario'
        );
        $stmtUsuario->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':email' => $email,
            ':telefono' => $telefono,
            ':id_usuario' => $idUsuario
        ]);

        if (($tipoData['tipo_usuario'] ?? '') === 'pasajero') {
            $stmtPasajero = $pdo->prepare(
                'UPDATE pasajero
                 SET fecha_nacimiento = :fecha_nacimiento, genero = :genero, tipo_documento = :tipo_documento, numero_documento = :numero_documento,
                     calle = :calle, ciudad = :ciudad, codigo_postal = :codigo_postal, pais = :pais
                 WHERE id_usuario = :id_usuario'
            );
            $stmtPasajero->execute([
                ':fecha_nacimiento' => valor('fecha_nacimiento'),
                ':genero' => valor('genero'),
                ':tipo_documento' => valor('tipo_documento'),
                ':numero_documento' => valor('numero_documento'),
                ':calle' => valor('calle'),
                ':ciudad' => valor('ciudad'),
                ':codigo_postal' => valor('codigo_postal'),
                ':pais' => valor('pais'),
                ':id_usuario' => $idUsuario
            ]);
        } else {
            $idEmpleado = (int)($tipoData['id_empleado'] ?? 0);
            $tipoEmpleado = $tipoData['tipo_empleado'] ?? '';

            if ($idEmpleado > 0 && $tipoEmpleado === 'vendedor') {
                $stmtVendedor = $pdo->prepare(
                    'UPDATE vendedor SET comision_porcentaje = :comision, region = :region WHERE id_empleado = :id_empleado'
                );
                $stmtVendedor->execute([
                    ':comision' => (float)($_POST['comision_porcentaje'] ?? 0),
                    ':region' => valor('region'),
                    ':id_empleado' => $idEmpleado
                ]);
            } elseif ($idEmpleado > 0 && $tipoEmpleado === 'mantenimiento') {
                $stmtMant = $pdo->prepare(
                    'UPDATE mantenimiento SET especialidad = :especialidad, turno = :turno, certificaciones = :certificaciones WHERE id_empleado = :id_empleado'
                );
                $stmtMant->execute([
                    ':especialidad' => valor('especialidad'),
                    ':turno' => valor('turno'),
                    ':certificaciones' => valor('certificaciones'),
                    ':id_empleado' => $idEmpleado
                ]);
            } elseif ($idEmpleado > 0 && $tipoEmpleado === 'maquinista') {
                $stmtMaq = $pdo->prepare(
                    'UPDATE maquinista SET licencia = :licencia, experiencia_años = :experiencia, horario_preferido = :horario WHERE id_empleado = :id_empleado'
                );
                $stmtMaq->execute([
                    ':licencia' => valor('licencia'),
                    ':experiencia' => (int)($_POST['experiencia_anos'] ?? 0),
                    ':horario' => valor('horario_preferido'),
                    ':id_empleado' => $idEmpleado
                ]);
                $asignarViaje = (int)($_POST['asignar_viaje'] ?? 0);
                if ($asignarViaje > 0) {
                    $stmtViaje = $pdo->prepare('UPDATE viaje SET id_maquinista = :id_maquinista WHERE id_viaje = :id_viaje');
                    $stmtViaje->execute([
                        ':id_maquinista' => $idEmpleado,
                        ':id_viaje' => $asignarViaje
                    ]);
                }
            }
        }

        $pdo->commit();
        redirigirAdmin('usuario_actualizado', true);
    }

    if ($accion === 'eliminar_usuario') {
        $idUsuario = (int)($_POST['id_usuario'] ?? 0);
        if ($idUsuario <= 0) {
            redirigirAdmin('id_invalido');
        }

        $stmt = $pdo->prepare(
            'SELECT u.id_usuario, u.email, u.tipo_usuario, p.id_pasajero, e.id_empleado, e.tipo_empleado
             FROM usuario u
             LEFT JOIN pasajero p ON p.id_usuario = u.id_usuario
             LEFT JOIN empleado e ON e.id_usuario = u.id_usuario
             WHERE u.id_usuario = :id_usuario'
        );
        $stmt->execute([':id_usuario' => $idUsuario]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            redirigirAdmin('usuario_no_encontrado');
        }

        if ($usuarioSesion && strcasecmp((string)$data['email'], (string)($usuarioSesion['email'] ?? '')) === 0) {
            redirigirAdmin('no_puedes_eliminar_tu_propio_usuario');
        }

        $pdo->beginTransaction();

        if (($data['tipo_usuario'] ?? '') === 'pasajero') {
            $idPasajero = (int)($data['id_pasajero'] ?? 0);
            if ($idPasajero > 0) {
                $stmtAbono = $pdo->prepare('DELETE FROM abono WHERE id_pasajero = :id_pasajero');
                $stmtAbono->execute([':id_pasajero' => $idPasajero]);
            }

            $stmtPas = $pdo->prepare('DELETE FROM pasajero WHERE id_usuario = :id_usuario');
            $stmtPas->execute([':id_usuario' => $idUsuario]);
        } else {
            $idEmpleado = (int)($data['id_empleado'] ?? 0);
            $tipoEmpleado = $data['tipo_empleado'] ?? '';

            if ($idEmpleado > 0) {
                if ($tipoEmpleado === 'vendedor') {
                    $stmtUso = $pdo->prepare('SELECT COUNT(*) FROM viaje WHERE id_vendedor = :id_empleado');
                    $stmtUso->execute([':id_empleado' => $idEmpleado]);
                    if ((int)$stmtUso->fetchColumn() > 0) {
                        throw new RuntimeException('vendedor_con_viajes_asociados');
                    }

                    $stmtUso2 = $pdo->prepare('SELECT COUNT(*) FROM ruta WHERE id_vendedor = :id_empleado');
                    $stmtUso2->execute([':id_empleado' => $idEmpleado]);
                    if ((int)$stmtUso2->fetchColumn() > 0) {
                        throw new RuntimeException('vendedor_con_rutas_asociadas');
                    }

                    $stmtSub = $pdo->prepare('DELETE FROM vendedor WHERE id_empleado = :id_empleado');
                    $stmtSub->execute([':id_empleado' => $idEmpleado]);
                } elseif ($tipoEmpleado === 'mantenimiento') {
                    $stmtUso = $pdo->prepare('SELECT COUNT(*) FROM incidencia WHERE id_mantenimiento = :id_empleado');
                    $stmtUso->execute([':id_empleado' => $idEmpleado]);
                    if ((int)$stmtUso->fetchColumn() > 0) {
                        throw new RuntimeException('mantenimiento_con_incidencias_asociadas');
                    }

                    $stmtSub = $pdo->prepare('DELETE FROM mantenimiento WHERE id_empleado = :id_empleado');
                    $stmtSub->execute([':id_empleado' => $idEmpleado]);
                } elseif ($tipoEmpleado === 'maquinista') {
                    $stmtUso = $pdo->prepare('SELECT COUNT(*) FROM viaje WHERE id_maquinista = :id_empleado');
                    $stmtUso->execute([':id_empleado' => $idEmpleado]);
                    if ((int)$stmtUso->fetchColumn() > 0) {
                        throw new RuntimeException('maquinista_con_viajes_asociados');
                    }
                    $stmtUso2 = $pdo->prepare('SELECT COUNT(*) FROM incidencia WHERE id_maquinista = :id_empleado');
                    $stmtUso2->execute([':id_empleado' => $idEmpleado]);
                    if ((int)$stmtUso2->fetchColumn() > 0) {
                        throw new RuntimeException('maquinista_con_incidencias_asociadas');
                    }

                    $stmtSub = $pdo->prepare('DELETE FROM maquinista WHERE id_empleado = :id_empleado');
                    $stmtSub->execute([':id_empleado' => $idEmpleado]);
                }

                $stmtEmp = $pdo->prepare('DELETE FROM empleado WHERE id_empleado = :id_empleado');
                $stmtEmp->execute([':id_empleado' => $idEmpleado]);
            }
        }

        $stmtUsuario = $pdo->prepare('DELETE FROM usuario WHERE id_usuario = :id_usuario');
        $stmtUsuario->execute([':id_usuario' => $idUsuario]);

        $pdo->commit();
        redirigirAdmin('usuario_eliminado', true);
    }

    redirigirAdmin('accion_no_soportada');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirigirAdmin($e->getMessage() !== '' ? $e->getMessage() : 'error_interno');
}
