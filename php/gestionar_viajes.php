<?php
session_start();
require_once __DIR__ . '/php/Conexion.php';

// 1. SEGURIDAD: Comprobar que es un empleado logueado
$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'empleado') {
    header('Location: inicio_sesion.html?error=no_autorizado');
    exit;
}

$pdo = (new Conexion())->conectar();
$mensaje_exito = '';
$mensaje_error = '';

// 2. OBTENER EL ID DEL VENDEDOR ACTUAL (Para registrar quién creó el viaje)
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
            $sql = "INSERT INTO VIAJE (id_ruta, id_tren, id_maquinista, id_vendedor, fecha_salida, fecha_llegada, precio, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['id_ruta'],
                $_POST['id_tren'],
                $_POST['id_maquinista'],
                $id_vendedor,
                $_POST['fecha_salida'],
                $_POST['fecha_llegada'],
                $_POST['precio'],
                $_POST['estado']
            ]);
            $mensaje_exito = "Viaje programado correctamente.";

        } elseif ($accion === 'editar') {
            $sql = "UPDATE VIAJE SET id_ruta = ?, id_tren = ?, id_maquinista = ?, fecha_salida = ?, fecha_llegada = ?, precio = ?, estado = ? 
                    WHERE id_viaje = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['id_ruta'],
                $_POST['id_tren'],
                $_POST['id_maquinista'],
                $_POST['fecha_salida'],
                $_POST['fecha_llegada'],
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

// 4. OBTENER DATOS PARA LOS DESPLEGABLES (Rutas, Trenes, Maquinistas)
$rutas = []; $trenes = []; $maquinistas = []; $viajes = [];
try {
    // Obtener Rutas
    $rutas = $pdo->query("SELECT id_ruta, origen, destino FROM RUTA ORDER BY origen ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener Trenes (Suponemos que tienes tabla TREN con modelo y capacidad)
    // Si da error, lo capturamos
    $stmtTrenes = $pdo->query("SELECT id_tren, modelo, capacidad FROM TREN");
    if($stmtTrenes) $trenes = $stmtTrenes->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener Maquinistas (Cruzando EMPLEADO con USUARIO para tener el nombre)
    $sqlMaquinistas = "SELECT e.id_empleado, u.nombre, u.apellido 
                       FROM EMPLEADO e 
                       JOIN USUARIO u ON e.id_usuario = u.id_usuario 
                       WHERE e.tipo_empleado = 'maquinista'";
    $stmtMaq = $pdo->query($sqlMaquinistas);
    if($stmtMaq) $maquinistas = $stmtMaq->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todos los VIAJES para la tabla
    $sqlViajes = "SELECT v.id_viaje, r.origen, r.destino, v.fecha_salida, v.fecha_llegada, v.precio, v.estado,
                         v.id_ruta, v.id_tren, v.id_maquinista 
                  FROM VIAJE v
                  JOIN RUTA r ON v.id_ruta = r.id_ruta
                  ORDER BY v.fecha_salida DESC";
    $viajes = $pdo->query($sqlViajes)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si alguna tabla no existe (ej. TREN), ignoramos el error para no romper la web
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Viajes - TrainWeb</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        .admin-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .admin-card { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        .data-table th { background: #f4f6f8; color: #0a2a66; }
        
        .btn { padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; border: none; color: white; display: inline-flex; align-items: center; gap: 8px; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; padding: 6px 10px; }
        .btn-edit { background: #ffc107; color: #333; padding: 6px 10px; }
        .btn-cancel { background: #6c757d; display: none; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9rem; color: #333;}
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; color: white; }
        .bg-programado { background: #17a2b8; }
        .bg-en_curso { background: #f39c12; }
        .bg-completado { background: #28a745; }
        .bg-cancelado { background: #dc3545; }
    </style>
</head>
<body style="background: #f4f6f8;">

    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb <span>| Portal Vendedor</span></div>
        <nav class="nav"><a href="vendedor.php"><i class="fa-solid fa-arrow-left"></i> Volver al panel</a></nav>
    </header>

    <main class="admin-container">
        <h1><i class="fa-solid fa-calendar-days"></i> Programación de Viajes</h1>

        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= $mensaje_exito ?></div>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= $mensaje_error ?></div>
        <?php endif; ?>

        <section class="admin-card">
            <h3 id="form-title" style="margin-top:0; color:#0a2a66;"><i class="fa-solid fa-plus-circle"></i> Programar Nuevo Viaje</h3>
            
            <form method="POST" id="viajeForm">
                <input type="hidden" name="accion" id="form-accion" value="crear">
                <input type="hidden" name="id_viaje" id="form-id-viaje" value="">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Ruta Asignada</label>
                        <select name="id_ruta" id="id_ruta" required>
                            <option value="">-- Selecciona una ruta --</option>
                            <?php foreach ($rutas as $r): ?>
                                <option value="<?= $r['id_ruta'] ?>"><?= htmlspecialchars($r['origen']) ?> ➔ <?= htmlspecialchars($r['destino']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tren Asignado</label>
                        <select name="id_tren" id="id_tren" required>
                            <option value="">-- Selecciona un tren --</option>
                            <?php foreach ($trenes as $t): ?>
                                <option value="<?= $t['id_tren'] ?>">Tren #<?= $t['id_tren'] ?> (<?= htmlspecialchars($t['modelo'] ?? 'Estándar') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Maquinista Responsable</label>
                        <select name="id_maquinista" id="id_maquinista" required>
                            <option value="">-- Selecciona un maquinista --</option>
                            <?php foreach ($maquinistas as $m): ?>
                                <option value="<?= $m['id_empleado'] ?>"><?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Fecha y Hora de Salida</label>
                        <input type="datetime-local" name="fecha_salida" id="fecha_salida" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Hora de Llegada (Aprox)</label>
                        <input type="time" name="fecha_llegada" id="fecha_llegada" required>
                    </div>

                    <div class="form-group">
                        <label>Precio Base del Billete (€)</label>
                        <input type="number" name="precio" id="precio" step="0.01" min="1" placeholder="Ej: 45.50" required>
                    </div>

                    <div class="form-group">
                        <label>Estado del Viaje</label>
                        <select name="estado" id="estado" required>
                            <option value="programado">Programado</option>
                            <option value="en_curso">En Curso</option>
                            <option value="completado">Completado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success" id="btn-submit">
                        <i class="fa-solid fa-save"></i> Programar Viaje
                    </button>
                    <button type="button" class="btn btn-cancel" id="btn-cancel" onclick="cancelarEdicion()">
                        <i class="fa-solid fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </section>

        <section class="admin-card">
            <h3 style="margin-top:0; color:#0a2a66;">Viajes Programados</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Trayecto</th>
                        <th>Salida</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($viajes)): ?>
                        <tr><td colspan="6" style="text-align:center;">No hay viajes registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($viajes as $v): ?>
                            <tr>
                                <td>#<?= $v['id_viaje'] ?></td>
                                <td><strong><?= htmlspecialchars($v['origen']) ?> ➔ <?= htmlspecialchars($v['destino']) ?></strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($v['fecha_salida'])) ?></td>
                                <td><strong><?= number_format($v['precio'], 2, ',', '.') ?> €</strong></td>
                                <td>
                                    <span class="badge bg-<?= $v['estado'] ?>">
                                        <?= strtoupper(str_replace('_', ' ', $v['estado'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button class="btn btn-edit" onclick="editarViaje(
                                            <?= $v['id_viaje'] ?>, 
                                            <?= $v['id_ruta'] ?>, 
                                            <?= $v['id_tren'] ?>, 
                                            <?= $v['id_maquinista'] ?>, 
                                            '<?= date('Y-m-d\TH:i', strtotime($v['fecha_salida'])) ?>', 
                                            '<?= $v['fecha_llegada'] ?>', 
                                            <?= $v['precio'] ?>, 
                                            '<?= $v['estado'] ?>'
                                        )" title="Editar"><i class="fa-solid fa-pen"></i></button>

                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que quieres borrar este viaje?');">
                                            <input type="hidden" name="accion" value="borrar">
                                            <input type="hidden" name="id_viaje" value="<?= $v['id_viaje'] ?>">
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
        function editarViaje(id, ruta, tren, maq, salida, llegada, precio, estado) {
            document.getElementById('form-title').innerHTML = '<i class="fa-solid fa-pen"></i> Editando Viaje #' + id;
            document.getElementById('form-accion').value = 'editar';
            document.getElementById('form-id-viaje').value = id;
            
            document.getElementById('id_ruta').value = ruta;
            document.getElementById('id_tren').value = tren;
            document.getElementById('id_maquinista').value = maq;
            document.getElementById('fecha_salida').value = salida;
            
            // La base de datos devuelve el TIME como HH:MM:SS, el input type="time" necesita HH:MM
            document.getElementById('fecha_llegada').value = llegada.substring(0, 5); 
            document.getElementById('precio').value = precio;
            document.getElementById('estado').value = estado;
            
            document.getElementById('btn-submit').innerHTML = '<i class="fa-solid fa-save"></i> Actualizar Viaje';
            document.getElementById('btn-submit').classList.remove('btn-success');
            document.getElementById('btn-submit').classList.add('btn-edit');
            document.getElementById('btn-cancel').style.display = 'inline-flex';
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function cancelarEdicion() {
            document.getElementById('viajeForm').reset();
            document.getElementById('form-title').innerHTML = '<i class="fa-solid fa-plus-circle"></i> Programar Nuevo Viaje';
            document.getElementById('form-accion').value = 'crear';
            document.getElementById('form-id-viaje').value = '';
            
            document.getElementById('btn-submit').innerHTML = '<i class="fa-solid fa-save"></i> Programar Viaje';
            document.getElementById('btn-submit').classList.add('btn-success');
            document.getElementById('btn-submit').classList.remove('btn-edit');
            document.getElementById('btn-cancel').style.display = 'none';
        }
    </script>
</body>
</html>