<?php
session_start();

// Al estar en la carpeta php/, cargamos con __DIR__
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

// 2. OBTENER EL ID DEL VENDEDOR ACTUAL
$id_vendedor = null;
try {
    $stmt = $pdo->prepare("SELECT id_empleado FROM EMPLEADO WHERE id_usuario = ?");
    $stmt->execute([$usuario['id_usuario']]);
    $id_vendedor = $stmt->fetchColumn();
} catch (PDOException $e) {}

// 3. PROCESAR EL FORMULARIO (CREAR, EDITAR, BORRAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'crear') {
            // CORREGIDO: Usamos fecha, hora_salida y hora_llegada
            $sql = "INSERT INTO VIAJE (id_ruta, id_tren, id_maquinista, id_vendedor, fecha, hora_salida, hora_llegada, precio, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['id_ruta'],
                $_POST['id_tren'],
                $_POST['id_maquinista'],
                $id_vendedor,
                $_POST['fecha'],
                $_POST['hora_salida'],
                $_POST['hora_llegada'],
                $_POST['precio'],
                $_POST['estado']
            ]);
            $mensaje_exito = "Viaje programado correctamente.";

        } elseif ($accion === 'editar') {
            $sql = "UPDATE VIAJE SET id_ruta = ?, id_tren = ?, id_maquinista = ?, fecha = ?, hora_salida = ?, hora_llegada = ?, precio = ?, estado = ? 
                    WHERE id_viaje = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['id_ruta'],
                $_POST['id_tren'],
                $_POST['id_maquinista'],
                $_POST['fecha'],
                $_POST['hora_salida'],
                $_POST['hora_llegada'],
                $_POST['precio'],
                $_POST['estado'],
                $_POST['id_viaje']
            ]);
            $mensaje_exito = "Viaje actualizado correctamente.";

        } elseif ($accion === 'borrar') {
            $stmt = $pdo->prepare("DELETE FROM VIAJE WHERE id_viaje = ?");
            $stmt->execute([$_POST['id_viaje']]);
            $mensaje_exito = "Viaje eliminado del sistema.";
        }
    } catch (PDOException $e) {
        $mensaje_error = "Error al procesar el viaje: " . $e->getMessage();
    }
}

// 4. OBTENER DATOS PARA LOS DESPLEGABLES Y LA TABLA
$rutas = []; $trenes = []; $maquinistas = []; $viajes = [];
try {
    $rutas = $pdo->query("SELECT id_ruta, origen, destino FROM RUTA ORDER BY origen ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtTrenes = $pdo->query("SELECT id_tren, modelo, capacidad FROM TREN");
    if($stmtTrenes) $trenes = $stmtTrenes->fetchAll(PDO::FETCH_ASSOC);
    
    $sqlMaquinistas = "SELECT e.id_empleado, u.nombre, u.apellido 
                       FROM EMPLEADO e 
                       JOIN USUARIO u ON e.id_usuario = u.id_usuario 
                       WHERE e.tipo_empleado = 'maquinista'";
    $stmtMaq = $pdo->query($sqlMaquinistas);
    if($stmtMaq) $maquinistas = $stmtMaq->fetchAll(PDO::FETCH_ASSOC);

    // CORREGIDO: Seleccionamos las columnas correctas para pintar la tabla
    $sqlViajes = "SELECT v.id_viaje, r.origen, r.destino, v.fecha, v.hora_salida, v.hora_llegada, v.precio, v.estado,
                         v.id_ruta, v.id_tren, v.id_maquinista 
                  FROM VIAJE v
                  JOIN RUTA r ON v.id_ruta = r.id_ruta
                  ORDER BY v.fecha DESC, v.hora_salida DESC";
    $viajes = $pdo->query($sqlViajes)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Gestión de Viajes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0a2a66;
            --secondary-color: #f39c12;
            --bg-color: #f4f7fb;
            --text-color: #333;
            --card-bg: #ffffff;
            --border-color: #e1e5eb;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --edit-color: #17a2b8;
        }

        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-color); color: var(--text-color); margin: 0; padding: 0; }

        .header-admin { background-color: var(--primary-color); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header-admin h1 { margin: 0; font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .header-admin a { color: white; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 5px; background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .header-admin a:hover { background: rgba(255,255,255,0.2); }

        .container { max-width: 1300px; margin: 30px auto; padding: 0 20px; display: grid; grid-template-columns: 350px 1fr; gap: 25px; align-items: start; }

        .admin-card { background: var(--card-bg); border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 25px; border: 1px solid var(--border-color); }
        .admin-card h2 { margin-top: 0; color: var(--primary-color); border-bottom: 2px solid var(--border-color); padding-bottom: 15px; margin-bottom: 20px; font-size: 1.25rem; display: flex; align-items: center; gap: 10px; }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 0.9rem; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-family: inherit; box-sizing: border-box; transition: border-color 0.3s; }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 3px rgba(10, 42, 102, 0.1); }
        
        /* Grid para inputs pequeños (hora, precio) */
        .input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

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
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.95rem; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { background-color: #f8f9fa; color: var(--primary-color); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }
        tr:hover { background-color: #f1f4f8; }
        .actions-cell { display: flex; gap: 8px; }

        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; color: white; display: inline-block; text-align: center; }
        .bg-programado { background: #17a2b8; }
        .bg-en_curso { background: #f39c12; }
        .bg-completado { background: #28a745; }
        .bg-cancelado { background: #dc3545; }

        @media (max-width: 1000px) { .container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <header class="header-admin">
        <h1><i class="fa-solid fa-calendar-days"></i> Gestión de Viajes</h1>
        <a href="../vendedor.php"><i class="fa-solid fa-arrow-left"></i> Volver al Panel</a>
    </header>

    <div class="container">
        
        <div class="admin-card">
            <h2 id="form-title"><i class="fa-solid fa-plus-circle"></i> Programar Viaje</h2>
            
            <?php if ($mensaje_exito): ?>
                <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= $mensaje_exito ?></div>
            <?php endif; ?>
            <?php if ($mensaje_error): ?>
                <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= $mensaje_error ?></div>
            <?php endif; ?>

            <form method="POST" id="viajeForm">
                <input type="hidden" name="accion" id="form-accion" value="crear">
                <input type="hidden" name="id_viaje" id="form-id-viaje" value="">

                <div class="form-group">
                    <label for="id_ruta"><i class="fa-solid fa-route"></i> Ruta</label>
                    <select name="id_ruta" id="id_ruta" required>
                        <option value="">-- Selecciona una ruta --</option>
                        <?php foreach ($rutas as $r): ?>
                            <option value="<?= $r['id_ruta'] ?>"><?= htmlspecialchars($r['origen']) ?> ➔ <?= htmlspecialchars($r['destino']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_tren"><i class="fa-solid fa-train"></i> Tren</label>
                    <select name="id_tren" id="id_tren" required>
                        <option value="">-- Selecciona un tren --</option>
                        <?php foreach ($trenes as $t): ?>
                            <option value="<?= $t['id_tren'] ?>">ID: <?= $t['id_tren'] ?> (<?= htmlspecialchars($t['modelo'] ?? 'Estándar') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_maquinista"><i class="fa-solid fa-id-badge"></i> Maquinista</label>
                    <select name="id_maquinista" id="id_maquinista" required>
                        <option value="">-- Selecciona un maquinista --</option>
                        <?php foreach ($maquinistas as $m): ?>
                            <option value="<?= $m['id_empleado'] ?>"><?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="fecha"><i class="fa-solid fa-calendar"></i> Fecha del Viaje</label>
                    <input type="date" name="fecha" id="fecha" required>
                </div>

                <div class="input-row">
                    <div class="form-group">
                        <label for="hora_salida"><i class="fa-regular fa-clock"></i> Salida</label>
                        <input type="time" name="hora_salida" id="hora_salida" required>
                    </div>
                    <div class="form-group">
                        <label for="hora_llegada"><i class="fa-solid fa-flag-checkered"></i> Llegada</label>
                        <input type="time" name="hora_llegada" id="hora_llegada" required>
                    </div>
                </div>

                <div class="input-row">
                    <div class="form-group">
                        <label for="precio"><i class="fa-solid fa-euro-sign"></i> Precio</label>
                        <input type="number" name="precio" id="precio" step="0.01" min="1" placeholder="Ej: 45.50" required>
                    </div>
                    <div class="form-group">
                        <label for="estado"><i class="fa-solid fa-toggle-on"></i> Estado</label>
                        <select name="estado" id="estado" required>
                            <option value="programado">Programado</option>
                            <option value="en_curso">En Curso</option>
                            <option value="completado">Completado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>

                <button type="submit" id="btn-submit" class="btn btn-success">
                    <i class="fa-solid fa-save"></i> Programar Viaje
                </button>
                <button type="button" id="btn-cancel" class="btn btn-cancel" style="display:none;" onclick="cancelarEdicion()">
                    <i class="fa-solid fa-xmark"></i> Cancelar Edición
                </button>
            </form>
        </div>

        <div class="admin-card">
            <h2><i class="fa-solid fa-list-check"></i> Viajes Programados</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Trayecto</th>
                            <th>Fecha y Hora</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($viajes)): ?>
                            <tr><td colspan="6" style="text-align: center; color: #666;">No hay viajes registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($viajes as $v): ?>
                                <tr>
                                    <td><strong>#<?= $v['id_viaje'] ?></strong></td>
                                    <td><?= htmlspecialchars($v['origen']) ?> <i class="fa-solid fa-arrow-right" style="color:#ccc; font-size:0.8em; margin:0 5px;"></i> <?= htmlspecialchars($v['destino']) ?></td>
                                    <td>
                                        <div><i class="fa-regular fa-calendar" style="color:#888;"></i> <?= date('d/m/Y', strtotime($v['fecha'])) ?></div>
                                        <div style="font-size: 0.85em; color: #666;"><i class="fa-regular fa-clock"></i> <?= date('H:i', strtotime($v['hora_salida'])) ?> - <?= date('H:i', strtotime($v['hora_llegada'])) ?></div>
                                    </td>
                                    <td><strong><?= number_format($v['precio'], 2, ',', '.') ?> €</strong></td>
                                    <td>
                                        <span class="badge bg-<?= $v['estado'] ?>">
                                            <?= strtoupper(str_replace('_', ' ', $v['estado'])) ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <button class="btn btn-sm btn-action-edit" 
                                                onclick="prepararEdicion(<?= $v['id_viaje'] ?>, <?= $v['id_ruta'] ?>, <?= $v['id_tren'] ?>, <?= $v['id_maquinista'] ?>, '<?= $v['fecha'] ?>', '<?= date('H:i', strtotime($v['hora_salida'])) ?>', '<?= date('H:i', strtotime($v['hora_llegada'])) ?>', <?= $v['precio'] ?>, '<?= $v['estado'] ?>')" 
                                                title="Editar Viaje">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este viaje?');">
                                            <input type="hidden" name="accion" value="borrar">
                                            <input type="hidden" name="id_viaje" value="<?= $v['id_viaje'] ?>">
                                            <button type="submit" class="btn btn-sm btn-action-delete" title="Eliminar Viaje">
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
        function prepararEdicion(id, ruta, tren, maq, fecha, salida, llegada, precio, estado) {
            document.getElementById('form-id-viaje').value = id;
            document.getElementById('form-accion').value = 'editar';
            document.getElementById('form-title').innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Editando Viaje #' + id;
            
            document.getElementById('id_ruta').value = ruta;
            document.getElementById('id_tren').value = tren;
            document.getElementById('id_maquinista').value = maq;
            document.getElementById('fecha').value = fecha;
            document.getElementById('hora_salida').value = salida;
            document.getElementById('hora_llegada').value = llegada;
            document.getElementById('precio').value = precio;
            document.getElementById('estado').value = estado;
            
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.innerHTML = '<i class="fa-solid fa-save"></i> Actualizar Viaje';
            btnSubmit.className = 'btn btn-edit-mode';
            
            document.getElementById('btn-cancel').style.display = 'inline-flex';
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function cancelarEdicion() {
            document.getElementById('viajeForm').reset();
            
            document.getElementById('form-id-viaje').value = '';
            document.getElementById('form-accion').value = 'crear';
            document.getElementById('form-title').innerHTML = '<i class="fa-solid fa-plus-circle"></i> Programar Viaje';
            
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.innerHTML = '<i class="fa-solid fa-save"></i> Programar Viaje';
            btnSubmit.className = 'btn btn-success';
            
            document.getElementById('btn-cancel').style.display = 'none';
        }

        // Ocultar alertas tras 5 segundos
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
