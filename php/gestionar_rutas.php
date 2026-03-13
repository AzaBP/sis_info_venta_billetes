<?php
session_start();
require_once 'Conexion.php';
require_once __DIR__ . '/auth_helpers.php';

// 1. SEGURIDAD: Comprobar que es un empleado logueado
$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'empleado') {
    header('Location: employee_login.php?error=no_autorizado');
    exit;
}
if (($usuario['tipo_empleado'] ?? '') !== 'vendedor' && !trainwebEsAdministrador($usuario)) {
    header('Location: index.php?error=acceso_denegado');
    exit;
}

$pdo = (new Conexion())->conectar();
$mensaje_exito = '';
$mensaje_error = '';

// 2. PROCESAR EL FORMULARIO (CREAR, EDITAR, BORRAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'crear') {
            $stmt = $pdo->prepare("INSERT INTO RUTA (origen, destino, distancia, duracion_estimada) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                trim($_POST['origen']),
                trim($_POST['destino']),
                $_POST['distancia'],
                $_POST['duracion']
            ]);
            $mensaje_exito = "Ruta creada correctamente.";

        } elseif ($accion === 'editar') {
            $stmt = $pdo->prepare("UPDATE RUTA SET origen = ?, destino = ?, distancia = ?, duracion_estimada = ? WHERE id_ruta = ?");
            $stmt->execute([
                trim($_POST['origen']),
                trim($_POST['destino']),
                $_POST['distancia'],
                $_POST['duracion'],
                $_POST['id_ruta']
            ]);
            $mensaje_exito = "Ruta actualizada correctamente.";

        } elseif ($accion === 'borrar') {
            $stmt = $pdo->prepare("DELETE FROM RUTA WHERE id_ruta = ?");
            $stmt->execute([$_POST['id_ruta']]);
            $mensaje_exito = "Ruta eliminada del sistema.";
        }
    } catch (PDOException $e) {
        // Si hay viajes programados usando esta ruta, PostgreSQL bloqueará el borrado por la clave foránea
        if ($e->getCode() == 23503) {
            $mensaje_error = "No puedes eliminar esta ruta porque hay viajes asignados a ella.";
        } else {
            $mensaje_error = "Error de base de datos: " . $e->getMessage();
        }
    }
}

// 3. OBTENER TODAS LAS RUTAS PARA LA TABLA
$stmt = $pdo->query("SELECT * FROM RUTA ORDER BY origen ASC, destino ASC");
$rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Rutas - TrainWeb</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        .admin-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .admin-card { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        
        /* Tabla */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        .data-table th { background: #f4f6f8; color: #0a2a66; }
        
        /* Botones */
        .btn { padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; border: none; transition: 0.3s; color: white; display: inline-flex; align-items: center; gap: 8px; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; padding: 6px 10px; }
        .btn-danger:hover { background: #c82333; }
        .btn-edit { background: #ffc107; color: #333; padding: 6px 10px; }
        .btn-edit:hover { background: #e0a800; }
        .btn-cancel { background: #6c757d; display: none; }
        
        /* Formulario */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9rem; color: #333;}
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        
        /* Alertas */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body style="background: #f4f6f8;">

    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb <span>| Portal Vendedor</span></div>
        <nav class="nav">
            <a href="vendedor.php"><i class="fa-solid fa-arrow-left"></i> Volver al panel</a>
        </nav>
    </header>

    <main class="admin-container">
        <div class="admin-header">
            <h1><i class="fa-solid fa-route"></i> Gestión de Rutas</h1>
        </div>

        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= $mensaje_exito ?></div>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= $mensaje_error ?></div>
        <?php endif; ?>

        <section class="admin-card" id="form-section">
            <h3 id="form-title" style="margin-top:0; color:#0a2a66;"><i class="fa-solid fa-plus-circle"></i> Añadir Nueva Ruta</h3>
            
            <form method="POST" id="rutaForm">
                <input type="hidden" name="accion" id="form-accion" value="crear">
                <input type="hidden" name="id_ruta" id="form-id-ruta" value="">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Estación de Origen</label>
                        <input type="text" name="origen" id="origen" placeholder="Ej: Madrid-Atocha" required>
                    </div>
                    <div class="form-group">
                        <label>Estación de Destino</label>
                        <input type="text" name="destino" id="destino" placeholder="Ej: Barcelona-Sants" required>
                    </div>
                    <div class="form-group">
                        <label>Distancia (Km)</label>
                        <input type="number" name="distancia" id="distancia" step="0.01" min="1" placeholder="Ej: 600.5" required>
                    </div>
                    <div class="form-group">
                        <label>Duración Estimada</label>
                        <input type="time" name="duracion" id="duracion" required>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success" id="btn-submit">
                        <i class="fa-solid fa-save"></i> Guardar Ruta
                    </button>
                    <button type="button" class="btn btn-cancel" id="btn-cancel" onclick="cancelarEdicion()">
                        <i class="fa-solid fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </section>

        <section class="admin-card">
            <h3 style="margin-top:0; color:#0a2a66;">Rutas Disponibles</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Distancia</th>
                        <th>Duración</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rutas)): ?>
                        <tr><td colspan="6" style="text-align:center;">No hay rutas registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rutas as $r): ?>
                            <tr>
                                <td>#<?= $r['id_ruta'] ?></td>
                                <td><strong><?= htmlspecialchars($r['origen']) ?></strong></td>
                                <td><strong><?= htmlspecialchars($r['destino']) ?></strong></td>
                                <td><?= number_format($r['distancia'], 2, ',', '.') ?> km</td>
                                <td>
                                    <i class="fa-regular fa-clock"></i> <?= substr($r['duracion_estimada'], 0, 5) ?> h
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button class="btn btn-edit" onclick="editarRuta(
                                            <?= $r['id_ruta'] ?>, 
                                            '<?= addslashes(htmlspecialchars($r['origen'])) ?>', 
                                            '<?= addslashes(htmlspecialchars($r['destino'])) ?>', 
                                            <?= $r['distancia'] ?>, 
                                            '<?= $r['duracion_estimada'] ?>'
                                        )" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que quieres borrar la ruta <?= htmlspecialchars($r['origen']) ?> - <?= htmlspecialchars($r['destino']) ?>?');">
                                            <input type="hidden" name="accion" value="borrar">
                                            <input type="hidden" name="id_ruta" value="<?= $r['id_ruta'] ?>">
                                            <button type="submit" class="btn btn-danger" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <script>
        // Función para cargar los datos en el formulario y cambiar a "Modo Edición"
        function editarRuta(id, origen, destino, distancia, duracion) {
            document.getElementById('form-title').innerHTML = '<i class="fa-solid fa-pen"></i> Editando Ruta #' + id;
            document.getElementById('form-accion').value = 'editar';
            document.getElementById('form-id-ruta').value = id;
            
            document.getElementById('origen').value = origen;
            document.getElementById('destino').value = destino;
            document.getElementById('distancia').value = distancia;
            
            // Format time for the input (needs HH:MM)
            document.getElementById('duracion').value = duracion.substring(0, 5);
            
            document.getElementById('btn-submit').innerHTML = '<i class="fa-solid fa-save"></i> Actualizar Ruta';
            document.getElementById('btn-submit').classList.remove('btn-success');
            document.getElementById('btn-submit').classList.add('btn-edit');
            
            document.getElementById('btn-cancel').style.display = 'inline-flex';
            
            // Hacer scroll hasta el formulario
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Función para limpiar el formulario y volver al "Modo Creación"
        function cancelarEdicion() {
            document.getElementById('rutaForm').reset();
            
            document.getElementById('form-title').innerHTML = '<i class="fa-solid fa-plus-circle"></i> Añadir Nueva Ruta';
            document.getElementById('form-accion').value = 'crear';
            document.getElementById('form-id-ruta').value = '';
            
            document.getElementById('btn-submit').innerHTML = '<i class="fa-solid fa-save"></i> Guardar Ruta';
            document.getElementById('btn-submit').classList.add('btn-success');
            document.getElementById('btn-submit').classList.remove('btn-edit');
            
            document.getElementById('btn-cancel').style.display = 'none';
        }
    </script>
</body>
</html>
