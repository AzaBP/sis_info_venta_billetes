<?php
session_start();

require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/auth_helpers.php';

// =========================================================================
// 1. SEGURIDAD ESTRICTA: COMPROBAR QUE ES UN EMPLEADO LOGUEADO
// =========================================================================
$usuario = $_SESSION['usuario'] ?? null;

if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'empleado') {
    header('Location: ../employee_login.php?error=no_autorizado');
    exit;
}

if (($usuario['tipo_empleado'] ?? '') !== 'vendedor' && !trainwebEsAdministrador($usuario)) {
    header('Location: ../index.php?error=acceso_denegado');
    exit;
}
// =========================================================================

$pdo = (new Conexion())->conectar();
$mensaje_exito = '';
$mensaje_error = '';

// 2. PROCESAR EL FORMULARIO (CREAR, EDITAR, BORRAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'crear') {
            $stmt = $pdo->prepare("INSERT INTO RUTA (origen, destino) VALUES (?, ?)");
            $stmt->execute([
                trim($_POST['origen']),
                trim($_POST['destino'])
            ]);
            $mensaje_exito = "Ruta creada correctamente.";

        } elseif ($accion === 'editar') {
            $stmt = $pdo->prepare("UPDATE RUTA SET origen = ?, destino = ? WHERE id_ruta = ?");
            $stmt->execute([
                trim($_POST['origen']),
                trim($_POST['destino']),
                $_POST['id_ruta']
            ]);
            $mensaje_exito = "Ruta actualizada correctamente.";

        } elseif ($accion === 'borrar') {
            // Verificar si la ruta está siendo usada en algún viaje
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM VIAJE WHERE id_ruta = ?");
            $stmtCheck->execute([$_POST['id_ruta']]);
            if ($stmtCheck->fetchColumn() > 0) {
                $mensaje_error = "No se puede borrar esta ruta porque tiene viajes programados.";
            } else {
                $stmtDel = $pdo->prepare("DELETE FROM RUTA WHERE id_ruta = ?");
                $stmtDel->execute([$_POST['id_ruta']]);
                $mensaje_exito = "Ruta eliminada correctamente.";
            }
        }
    } catch (PDOException $e) {
        $mensaje_error = "Error de base de datos: " . $e->getMessage();
    }
}

// 3. OBTENER TODAS LAS RUTAS
$stmt = $pdo->query("SELECT id_ruta, origen, destino FROM RUTA ORDER BY id_ruta DESC");
$rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Gestión de Rutas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0a2a66;
            --secondary-color: #f39c12;
            --bg-color: #f4f7fb;
            --text-color: #333;
            --card-bg: #ffffff;
            --border-color: #e1e5eb;
            --success-color: #17632A;
            --danger-color: #dc3545;
            --edit-color: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }

        .header-admin {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-admin h1 { margin: 0; font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .header-admin a { color: white; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 5px; background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .header-admin a:hover { background: rgba(255,255,255,0.2); }

        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; display: grid; grid-template-columns: 350px 1fr; gap: 25px; }

        .admin-card { background: var(--card-bg); border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 25px; border: 1px solid var(--border-color); }
        .admin-card h2 { margin-top: 0; color: var(--primary-color); border-bottom: 2px solid var(--border-color); padding-bottom: 15px; margin-bottom: 20px; font-size: 1.25rem; display: flex; align-items: center; gap: 10px; }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-family: inherit; box-sizing: border-box; transition: border-color 0.3s; }
        .form-group input:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 3px rgba(10, 42, 102, 0.1); }

        .btn { padding: 10px 15px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-family: inherit; display: inline-flex; align-items: center; gap: 8px; justify-content: center; transition: all 0.2s; font-size: 0.95rem; }
        .btn-success { background: var(--success-color); color: white; width: 100%; }
        .btn-success:hover { background: #218838; }
        .btn-edit-mode { background: var(--edit-color); color: white; width: 100%; }
        .btn-edit-mode:hover { background: #138496; }
        .btn-cancel { background: #6c757d; color: white; width: 100%; margin-top: 10px; }
        .btn-cancel:hover { background: #5a6268; }

        .btn-sm { padding: 6px 10px; font-size: 0.85rem; border-radius: 4px; }
        .btn-action-edit { background: var(--edit-color); color: white; }
        .btn-action-delete { background: var(--danger-color); color: white; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { background-color: #f8f9fa; color: var(--primary-color); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }
        tr:hover { background-color: #f1f4f8; }
        .actions-cell { display: flex; gap: 8px; }

        @media (max-width: 900px) { .container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <header class="header-admin">
        <h1><i class="fa-solid fa-route"></i> Gestión de Rutas</h1>
        <a href="../vendedor.php"><i class="fa-solid fa-arrow-left"></i> Volver al Panel</a>
    </header>

    <div class="container">
        
        <div class="admin-card">
            <h2 id="form-title"><i class="fa-solid fa-plus-circle"></i> Añadir Nueva Ruta</h2>
            
            <?php if ($mensaje_exito): ?>
                <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= $mensaje_exito ?></div>
            <?php endif; ?>
            
            <?php if ($mensaje_error): ?>
                <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= $mensaje_error ?></div>
            <?php endif; ?>

            <form method="POST" id="rutaForm">
                <input type="hidden" name="accion" id="form-accion" value="crear">
                <input type="hidden" name="id_ruta" id="form-id-ruta" value="">

                <div class="form-group">
                    <label for="origen"><i class="fa-solid fa-location-dot"></i> Origen</label>
                    <input type="text" name="origen" id="origen" required placeholder="Ej: Madrid">
                </div>

                <div class="form-group">
                    <label for="destino"><i class="fa-solid fa-flag-checkered"></i> Destino</label>
                    <input type="text" name="destino" id="destino" required placeholder="Ej: Barcelona">
                </div>

                <button type="submit" id="btn-submit" class="btn btn-success">
                    <i class="fa-solid fa-save"></i> Guardar Ruta
                </button>
                <button type="button" id="btn-cancel" class="btn btn-cancel" style="display:none;" onclick="cancelarEdicion()">
                    <i class="fa-solid fa-xmark"></i> Cancelar Edición
                </button>
            </form>
        </div>

        <div class="admin-card">
            <h2><i class="fa-solid fa-list"></i> Rutas Registradas</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rutas)): ?>
                            <tr><td colspan="4" style="text-align: center; color: #666;">No hay rutas registradas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rutas as $r): ?>
                                <tr>
                                    <td><strong>#<?= $r['id_ruta'] ?></strong></td>
                                    <td><?= htmlspecialchars($r['origen']) ?></td>
                                    <td><?= htmlspecialchars($r['destino']) ?></td>
                                    <td class="actions-cell">
                                        <button class="btn btn-sm btn-action-edit" 
                                                onclick="prepararEdicion(<?= $r['id_ruta'] ?>, '<?= addslashes($r['origen']) ?>', '<?= addslashes($r['destino']) ?>')" 
                                                title="Editar Ruta">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta ruta?');">
                                            <input type="hidden" name="accion" value="borrar">
                                            <input type="hidden" name="id_ruta" value="<?= $r['id_ruta'] ?>">
                                            <button type="submit" class="btn btn-sm btn-action-delete" title="Eliminar Ruta">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        function prepararEdicion(id, origen, destino) {
            document.getElementById('form-id-ruta').value = id;
            document.getElementById('form-accion').value = 'editar';
            document.getElementById('form-title').innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Editando Ruta #' + id;
            
            document.getElementById('origen').value = origen;
            document.getElementById('destino').value = destino;
            
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.innerHTML = '<i class="fa-solid fa-save"></i> Actualizar Ruta';
            btnSubmit.className = 'btn btn-edit-mode';
            
            document.getElementById('btn-cancel').style.display = 'inline-flex';
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function cancelarEdicion() {
            document.getElementById('rutaForm').reset();
            
            document.getElementById('form-id-ruta').value = '';
            document.getElementById('form-accion').value = 'crear';
            document.getElementById('form-title').innerHTML = '<i class="fa-solid fa-plus-circle"></i> Añadir Nueva Ruta';
            
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.innerHTML = '<i class="fa-solid fa-save"></i> Guardar Ruta';
            btnSubmit.className = 'btn btn-success';
            
            document.getElementById('btn-cancel').style.display = 'none';
        }

        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 500);
            });
        }, 5000);
    </script>
</body>
</html>
