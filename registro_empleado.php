<?php
session_start();

require_once __DIR__ . '/php/auth_helpers.php';
require_once __DIR__ . '/php/Conexion.php';

$usuarioSesion = $_SESSION['usuario'] ?? null;
$adminSimple = !empty($_SESSION['admin_simple_auth']) && $_SESSION['admin_simple_auth'] === true;
$adminPorUsuario = $usuarioSesion && trainwebEsAdministrador($usuarioSesion);

if (!$adminSimple && !$adminPorUsuario) {
    header('Location: admin_login.php?error=no_autorizado');
    exit;
}

function h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function maskDocumento(?string $doc): string
{
    $doc = trim((string)$doc);
    if ($doc === '') {
        return '';
    }
    $len = strlen($doc);
    if ($len <= 4) {
        return str_repeat('*', $len);
    }
    return str_repeat('*', $len - 4) . substr($doc, -4);
}

$ok = trim($_GET['ok'] ?? '');
$error = trim($_GET['error'] ?? '');
$q = trim($_GET['q'] ?? '');
$editId = (int)($_GET['edit_id'] ?? 0);

$usuarios = [];
$usuarioEdit = null;

try {
    $pdo = (new Conexion())->conectar();
    if ($pdo) {
        $sql = "SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.telefono, u.tipo_usuario,
                       e.id_empleado, e.tipo_empleado,
                       p.id_pasajero, p.fecha_nacimiento, p.genero, p.tipo_documento, p.numero_documento, p.ciudad, p.pais
                FROM usuario u
                LEFT JOIN empleado e ON e.id_usuario = u.id_usuario
                LEFT JOIN pasajero p ON p.id_usuario = u.id_usuario";

        if ($q !== '') {
            $sql .= " WHERE u.email ILIKE :like OR u.nombre ILIKE :like OR u.apellido ILIKE :like OR CAST(u.id_usuario AS TEXT) = :exact";
        }

        $sql .= " ORDER BY u.id_usuario DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        if ($q !== '') {
            $stmt->execute([':like' => '%' . $q . '%', ':exact' => $q]);
        } else {
            $stmt->execute();
        }
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if ($editId > 0) {
            $stmtEdit = $pdo->prepare(
                "SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.telefono, u.tipo_usuario,
                        e.id_empleado, e.tipo_empleado,
                        p.fecha_nacimiento, p.genero, p.tipo_documento, p.numero_documento, p.calle, p.ciudad, p.codigo_postal, p.pais,
                        v.comision_porcentaje, v.region,
                        m.especialidad, m.turno, m.certificaciones
                 FROM usuario u
                 LEFT JOIN empleado e ON e.id_usuario = u.id_usuario
                 LEFT JOIN pasajero p ON p.id_usuario = u.id_usuario
                 LEFT JOIN vendedor v ON v.id_empleado = e.id_empleado
                 LEFT JOIN mantenimiento m ON m.id_empleado = e.id_empleado
                 WHERE u.id_usuario = :id_usuario
                 LIMIT 1"
            );
            $stmtEdit->execute([':id_usuario' => $editId]);
            $usuarioEdit = $stmtEdit->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }
} catch (Throwable $e) {
    $error = 'error_cargando_datos';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Panel de Administrador</title>
    <link rel="stylesheet" href="css/index.css">
    <style>
        body { background: #f4f7fb; }
        .admin-wrap { max-width: 1200px; margin: 28px auto; padding: 0 14px; }
        .card { background: #fff; border-radius: 14px; box-shadow: 0 8px 20px rgba(0,0,0,.08); padding: 20px; margin-bottom: 18px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
        .full { grid-column: 1 / -1; }
        h1 { margin-bottom: 8px; color: #1f2d3d; }
        h2 { margin-bottom: 10px; color: #1f2d3d; font-size: 1.2rem; }
        p { color: #4a5568; margin-bottom: 14px; }
        label { display: block; margin-bottom: 6px; color: #1f2d3d; font-weight: 600; }
        input, select { width: 100%; box-sizing: border-box; border: 1px solid #d0d7e2; border-radius: 8px; padding: 9px 10px; }
        .actions { margin-top: 12px; display: flex; gap: 8px; }
        .btn { border: 0; border-radius: 8px; padding: 9px 14px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #0d6efd; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #1f2d3d; }
        .msg-ok { border: 1px solid #86efac; background: #f0fdf4; color: #166534; padding: 10px; border-radius: 8px; margin-bottom: 12px; }
        .msg-error { border: 1px solid #fca5a5; background: #fef2f2; color: #991b1b; padding: 10px; border-radius: 8px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px; text-align: left; font-size: .92rem; }
        th { background: #f8fafc; color: #334155; }
        .table-wrap { overflow-x: auto; }
        @media (max-width: 900px) {
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<header class="header">
    <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb</div>
    <nav class="nav">
        <a href="index.php">Inicio</a>
        <a href="registro_empleado.php">Panel admin</a>
        <?php if ($adminSimple): ?>
            <a href="admin_logout.php">Cerrar sesion admin</a>
        <?php else: ?>
            <a href="cerrar_sesion.php">Cerrar sesion</a>
        <?php endif; ?>
    </nav>
</header>

<main class="admin-wrap">
    <div class="card">
        <h1>Panel de administrador</h1>
        <p>Gestiona empleados, pasajeros y usuarios del sistema.</p>
        <?php if ($ok !== ''): ?><div class="msg-ok"><?php echo h($ok); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="msg-error"><?php echo h($error); ?></div><?php endif; ?>
    </div>

    <div class="grid-2">
        <section class="card">
            <h2>Crear empleado</h2>
            <form method="POST" action="procesar_admin_usuario.php">
                <input type="hidden" name="accion" value="crear_empleado">
                <div class="grid-3">
                    <div><label>Nombre</label><input name="nombre" required></div>
                    <div><label>Apellido</label><input name="apellido" required></div>
                    <div><label>Email</label><input type="email" name="email" required></div>
                    <div><label>Telefono</label><input name="telefono" required></div>
                    <div><label>Contrasena</label><input type="password" name="password" minlength="8" required></div>
                    <div>
                        <label>Tipo empleado</label>
                        <select name="tipo_empleado" required>
                            <option value="vendedor">Vendedor</option>
                            <option value="mantenimiento">Mantenimiento</option>
                        </select>
                    </div>
                    <div><label>Comision (%)</label><input name="comision_porcentaje" type="number" min="0" max="100" step="0.01" value="0"></div>
                    <div><label>Region</label><input name="region" value="General"></div>
                    <div><label>Especialidad</label><input name="especialidad" value="General"></div>
                    <div><label>Turno</label><input name="turno" value="manana"></div>
                    <div class="full"><label>Certificaciones</label><input name="certificaciones"></div>
                </div>
                <div class="actions"><button class="btn btn-primary" type="submit">Crear empleado</button></div>
            </form>
        </section>

        <section class="card">
            <h2>Crear pasajero</h2>
            <form method="POST" action="procesar_admin_usuario.php">
                <input type="hidden" name="accion" value="crear_pasajero">
                <div class="grid-3">
                    <div><label>Nombre</label><input name="nombre" required></div>
                    <div><label>Apellido</label><input name="apellido" required></div>
                    <div><label>Email</label><input type="email" name="email" required></div>
                    <div><label>Telefono</label><input name="telefono" required></div>
                    <div><label>Contrasena</label><input type="password" name="password" minlength="8" required></div>
                    <div><label>Fecha nacimiento</label><input type="date" name="fecha_nacimiento" required></div>
                    <div>
                        <label>Genero</label>
                        <select name="genero" required>
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                            <option value="otro">Otro</option>
                            <option value="no_especificar">No especificar</option>
                        </select>
                    </div>
                    <div>
                        <label>Tipo documento</label>
                        <select name="tipo_documento" required>
                            <option value="dni">DNI</option>
                            <option value="nie">NIE</option>
                            <option value="pasaporte">Pasaporte</option>
                        </select>
                    </div>
                    <div><label>Numero documento</label><input name="numero_documento"></div>
                    <div><label>Calle</label><input name="calle"></div>
                    <div><label>Ciudad</label><input name="ciudad"></div>
                    <div><label>Codigo postal</label><input name="codigo_postal"></div>
                    <div><label>Pais</label><input name="pais"></div>
                </div>
                <div class="actions"><button class="btn btn-primary" type="submit">Crear pasajero</button></div>
            </form>
        </section>
    </div>

    <section class="card">
        <h2>Consulta de usuarios</h2>
        <form method="GET" class="actions">
            <input type="text" name="q" value="<?php echo h($q); ?>" placeholder="Buscar por ID, nombre, apellido o email">
            <button class="btn btn-secondary" type="submit">Buscar</button>
            <a class="btn btn-secondary" href="registro_empleado.php">Limpiar</a>
        </form>
        <p>Vista limitada a datos operativos. No se muestran contrasenas ni metodos de pago.</p>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Rol</th>
                    <th>Documento</th>
                    <th>Ciudad/Pais</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?php echo (int)$u['id_usuario']; ?></td>
                        <td><?php echo h($u['nombre'] . ' ' . $u['apellido']); ?></td>
                        <td><?php echo h((string)$u['email']); ?></td>
                        <td><?php echo h((string)$u['tipo_usuario']); ?></td>
                        <td><?php echo h((string)($u['tipo_empleado'] ?? '')); ?></td>
                        <td><?php echo h(maskDocumento((string)($u['numero_documento'] ?? ''))); ?></td>
                        <td><?php echo h(trim((string)($u['ciudad'] ?? '') . ' / ' . (string)($u['pais'] ?? ''))); ?></td>
                        <td>
                            <a class="btn btn-secondary" href="registro_empleado.php?edit_id=<?php echo (int)$u['id_usuario']; ?>&q=<?php echo urlencode($q); ?>">Editar</a>
                            <form method="POST" action="procesar_admin_usuario.php" style="display:inline-block;" onsubmit="return confirm('Se eliminara el usuario. Continuar?');">
                                <input type="hidden" name="accion" value="eliminar_usuario">
                                <input type="hidden" name="id_usuario" value="<?php echo (int)$u['id_usuario']; ?>">
                                <button class="btn btn-danger" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($usuarioEdit): ?>
        <section class="card">
            <h2>Editar usuario #<?php echo (int)$usuarioEdit['id_usuario']; ?></h2>
            <form method="POST" action="procesar_admin_usuario.php">
                <input type="hidden" name="accion" value="actualizar_usuario">
                <input type="hidden" name="id_usuario" value="<?php echo (int)$usuarioEdit['id_usuario']; ?>">
                <div class="grid-3">
                    <div><label>Nombre</label><input name="nombre" value="<?php echo h((string)$usuarioEdit['nombre']); ?>" required></div>
                    <div><label>Apellido</label><input name="apellido" value="<?php echo h((string)$usuarioEdit['apellido']); ?>" required></div>
                    <div><label>Email</label><input type="email" name="email" value="<?php echo h((string)$usuarioEdit['email']); ?>" required></div>
                    <div><label>Telefono</label><input name="telefono" value="<?php echo h((string)($usuarioEdit['telefono'] ?? '')); ?>"></div>
                    <div><label>Tipo usuario</label><input value="<?php echo h((string)$usuarioEdit['tipo_usuario']); ?>" disabled></div>
                    <div><label>Tipo empleado</label><input value="<?php echo h((string)($usuarioEdit['tipo_empleado'] ?? '')); ?>" disabled></div>
                </div>

                <?php if (($usuarioEdit['tipo_usuario'] ?? '') === 'pasajero'): ?>
                    <div class="grid-3" style="margin-top:10px;">
                        <div><label>Fecha nacimiento</label><input type="date" name="fecha_nacimiento" value="<?php echo h((string)($usuarioEdit['fecha_nacimiento'] ?? '')); ?>"></div>
                        <div>
                            <label>Genero</label>
                            <select name="genero">
                                <?php $g = (string)($usuarioEdit['genero'] ?? ''); ?>
                                <option value="masculino" <?php echo $g === 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                                <option value="femenino" <?php echo $g === 'femenino' ? 'selected' : ''; ?>>Femenino</option>
                                <option value="otro" <?php echo $g === 'otro' ? 'selected' : ''; ?>>Otro</option>
                                <option value="no_especificar" <?php echo $g === 'no_especificar' ? 'selected' : ''; ?>>No especificar</option>
                            </select>
                        </div>
                        <div>
                            <label>Tipo documento</label>
                            <?php $td = (string)($usuarioEdit['tipo_documento'] ?? ''); ?>
                            <select name="tipo_documento">
                                <option value="dni" <?php echo $td === 'dni' ? 'selected' : ''; ?>>DNI</option>
                                <option value="nie" <?php echo $td === 'nie' ? 'selected' : ''; ?>>NIE</option>
                                <option value="pasaporte" <?php echo $td === 'pasaporte' ? 'selected' : ''; ?>>Pasaporte</option>
                            </select>
                        </div>
                        <div><label>Numero documento</label><input name="numero_documento" value="<?php echo h((string)($usuarioEdit['numero_documento'] ?? '')); ?>"></div>
                        <div><label>Calle</label><input name="calle" value="<?php echo h((string)($usuarioEdit['calle'] ?? '')); ?>"></div>
                        <div><label>Ciudad</label><input name="ciudad" value="<?php echo h((string)($usuarioEdit['ciudad'] ?? '')); ?>"></div>
                        <div><label>Codigo postal</label><input name="codigo_postal" value="<?php echo h((string)($usuarioEdit['codigo_postal'] ?? '')); ?>"></div>
                        <div><label>Pais</label><input name="pais" value="<?php echo h((string)($usuarioEdit['pais'] ?? '')); ?>"></div>
                    </div>
                <?php elseif (($usuarioEdit['tipo_empleado'] ?? '') === 'vendedor'): ?>
                    <div class="grid-3" style="margin-top:10px;">
                        <div><label>Comision (%)</label><input name="comision_porcentaje" type="number" min="0" max="100" step="0.01" value="<?php echo h((string)($usuarioEdit['comision_porcentaje'] ?? '0')); ?>"></div>
                        <div><label>Region</label><input name="region" value="<?php echo h((string)($usuarioEdit['region'] ?? '')); ?>"></div>
                    </div>
                <?php elseif (($usuarioEdit['tipo_empleado'] ?? '') === 'mantenimiento'): ?>
                    <div class="grid-3" style="margin-top:10px;">
                        <div><label>Especialidad</label><input name="especialidad" value="<?php echo h((string)($usuarioEdit['especialidad'] ?? '')); ?>"></div>
                        <div><label>Turno</label><input name="turno" value="<?php echo h((string)($usuarioEdit['turno'] ?? '')); ?>"></div>
                        <div class="full"><label>Certificaciones</label><input name="certificaciones" value="<?php echo h((string)($usuarioEdit['certificaciones'] ?? '')); ?>"></div>
                    </div>
                <?php endif; ?>

                <div class="actions"><button class="btn btn-primary" type="submit">Guardar cambios</button></div>
            </form>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
