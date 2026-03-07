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

// 2. PROCESAR FORMULARIOS (CREAR, EDITAR Y BORRAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo_gestion'] ?? ''; // 'promo' o 'abono'
    $accion = $_POST['accion'] ?? '';     // 'crear', 'editar' o 'borrar'

    try {
        // ==========================================
        // GESTIÓN DE PROMOCIONES
        // ==========================================
        if ($tipo === 'promo') {
            $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
            $descuento = $_POST['descuento'] ?? 0;
            $fecha_fin = $_POST['fecha_fin'] ?? '';
            $usos_max = !empty($_POST['usos_maximos']) ? $_POST['usos_maximos'] : null;

            if ($accion === 'crear') {
                $stmt = $pdo->prepare("INSERT INTO PROMOCION (codigo, descuento_porcentaje, fecha_inicio, fecha_fin, usos_maximos, usos_actuales) VALUES (?, ?, CURRENT_DATE, ?, ?, 0)");
                $stmt->execute([$codigo, $descuento, $fecha_fin, $usos_max]);
                $mensaje_exito = "Promoción '$codigo' creada con éxito.";
                
            } elseif ($accion === 'editar') {
                $stmt = $pdo->prepare("UPDATE PROMOCION SET codigo = ?, descuento_porcentaje = ?, fecha_fin = ?, usos_maximos = ? WHERE id_promocion = ?");
                $stmt->execute([$codigo, $descuento, $fecha_fin, $usos_max, $_POST['id_promocion']]);
                $mensaje_exito = "Promoción '$codigo' actualizada.";
                
            } elseif ($accion === 'borrar') {
                $stmt = $pdo->prepare("DELETE FROM PROMOCION WHERE id_promocion = ?");
                $stmt->execute([$_POST['id_promocion']]);
                $mensaje_exito = "Promoción eliminada.";
            }
        }
        
        // ==========================================
        // GESTIÓN DE ABONOS
        // ==========================================
        elseif ($tipo === 'abono') {
            $tipo_codigo = strtolower(trim($_POST['tipo_codigo'] ?? ''));
            $nombre = $_POST['nombre'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $precio = $_POST['precio'] ?? 0;
            $icono = $_POST['icono'] ?? 'fa-ticket';
            $color = $_POST['color'] ?? '#0a2a66';

            if ($accion === 'crear') {
                $stmt = $pdo->prepare("INSERT INTO TIPO_ABONO (tipo_codigo, nombre, descripcion, precio, icono, color) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$tipo_codigo, $nombre, $descripcion, $precio, $icono, $color]);
                $mensaje_exito = "Abono '$nombre' creado con éxito.";
                
            } elseif ($accion === 'editar') {
                // Nota: No actualizamos el 'tipo_codigo' porque es la clave primaria y está vinculada a los usuarios
                $stmt = $pdo->prepare("UPDATE TIPO_ABONO SET nombre = ?, descripcion = ?, precio = ?, icono = ?, color = ? WHERE tipo_codigo = ?");
                $stmt->execute([$nombre, $descripcion, $precio, $icono, $color, $tipo_codigo]);
                $mensaje_exito = "Abono '$nombre' actualizado correctamente.";
                
            } elseif ($accion === 'borrar') {
                $stmt = $pdo->prepare("DELETE FROM TIPO_ABONO WHERE tipo_codigo = ?");
                $stmt->execute([$_POST['tipo_codigo']]);
                $mensaje_exito = "Abono eliminado del catálogo.";
            }
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23503) { 
            $mensaje_error = "No puedes eliminar este registro porque ya está siendo usado por los pasajeros (ej. Abonos comprados).";
        } else {
            $mensaje_error = "Error DB: " . $e->getMessage();
        }
    }
}

// 3. OBTENER DATOS ACTUALIZADOS
$promociones = $pdo->query("SELECT * FROM PROMOCION ORDER BY fecha_fin DESC")->fetchAll(PDO::FETCH_ASSOC);
$abonos = $pdo->query("SELECT * FROM TIPO_ABONO ORDER BY precio ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ofertas - TrainWeb</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        .admin-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .admin-card { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        .data-table th { background: #f4f6f8; color: #0a2a66; }
        
        .btn { padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; border: none; color: white; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;}
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; padding: 6px 10px; }
        .btn-edit { background: #ffc107; color: #333; padding: 6px 10px; }
        .btn-cancel { background: #6c757d; display: none; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9rem; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;}
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .form-box { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #0a2a66; }
    </style>
</head>
<body style="background: #f4f6f8;">

    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb <span>| Portal Vendedor</span></div>
        <nav class="nav"><a href="vendedor.php"><i class="fa-solid fa-arrow-left"></i> Volver al panel</a></nav>
    </header>

    <main class="admin-container">
        <h1>Gestión de Promociones y Abonos</h1>

        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= $mensaje_exito ?></div>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= $mensaje_error ?></div>
        <?php endif; ?>

        <section class="admin-card">
            <h2><i class="fa-solid fa-tags"></i> Códigos Promocionales</h2>
            
            <div class="form-box">
                <h4 id="promo-form-title" style="margin-top:0; color:#0a2a66;"><i class="fa-solid fa-plus"></i> Añadir Nueva Promoción</h4>
                <form method="POST" id="promoForm">
                    <input type="hidden" name="tipo_gestion" value="promo">
                    <input type="hidden" name="accion" id="promo-accion" value="crear">
                    <input type="hidden" name="id_promocion" id="promo-id" value="">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Código (ej: VERANO20)</label>
                            <input type="text" name="codigo" id="promo-codigo" required>
                        </div>
                        <div class="form-group">
                            <label>Descuento (%)</label>
                            <input type="number" name="descuento" id="promo-descuento" min="1" max="100" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha de Fin</label>
                            <input type="date" name="fecha_fin" id="promo-fecha" required>
                        </div>
                        <div class="form-group">
                            <label>Usos Máximos (vacío = sin límite)</label>
                            <input type="number" name="usos_maximos" id="promo-usos" min="1">
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-success" id="promo-btn-submit"><i class="fa-solid fa-save"></i> Guardar Promoción</button>
                        <button type="button" class="btn btn-cancel" id="promo-btn-cancel" onclick="cancelarPromo()"><i class="fa-solid fa-times"></i> Cancelar</button>
                    </div>
                </form>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descuento</th>
                        <th>Válido hasta</th>
                        <th>Usos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promociones as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['codigo']) ?></strong></td>
                            <td><?= (float)$p['descuento_porcentaje'] ?>%</td>
                            <td><?= date('d/m/Y', strtotime($p['fecha_fin'])) ?></td>
                            <td><?= $p['usos_actuales'] ?> / <?= $p['usos_maximos'] ?: '∞' ?></td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn btn-edit" onclick="editarPromo(
                                        <?= $p['id_promocion'] ?>, 
                                        '<?= addslashes($p['codigo']) ?>', 
                                        <?= $p['descuento_porcentaje'] ?>, 
                                        '<?= $p['fecha_fin'] ?>', 
                                        '<?= $p['usos_maximos'] ?? '' ?>'
                                    )" title="Editar"><i class="fa-solid fa-pen"></i></button>

                                    <form method="POST" onsubmit="return confirm('¿Borrar promoción <?= $p['codigo'] ?>?');" style="margin:0;">
                                        <input type="hidden" name="tipo_gestion" value="promo">
                                        <input type="hidden" name="accion" value="borrar">
                                        <input type="hidden" name="id_promocion" value="<?= $p['id_promocion'] ?>">
                                        <button type="submit" class="btn btn-danger" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="admin-card">
            <h2><i class="fa-solid fa-ticket-alt"></i> Catálogo de Abonos</h2>

            <div class="form-box" style="border-left-color: #f39c12;">
                <h4 id="abono-form-title" style="margin-top:0; color:#0a2a66;"><i class="fa-solid fa-plus"></i> Añadir Nuevo Abono</h4>
                <form method="POST" id="abonoForm">
                    <input type="hidden" name="tipo_gestion" value="abono">
                    <input type="hidden" name="accion" id="abono-accion" value="crear">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Código Interno (ej: joven_pass)</label>
                            <input type="text" name="tipo_codigo" id="abono-codigo" required>
                        </div>
                        <div class="form-group">
                            <label>Nombre Comercial</label>
                            <input type="text" name="nombre" id="abono-nombre" required>
                        </div>
                        <div class="form-group">
                            <label>Precio (€)</label>
                            <input type="number" name="precio" id="abono-precio" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Color (HEX)</label>
                            <input type="color" name="color" id="abono-color" value="#0a2a66" style="height: 40px; padding: 2px;">
                        </div>
                        <div class="form-group">
                            <label>Icono (Clase FontAwesome)</label>
                            <input type="text" name="icono" id="abono-icono" placeholder="ej: fa-ticket" value="fa-ticket" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Descripción corta</label>
                        <input type="text" name="descripcion" id="abono-desc" required>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-success" id="abono-btn-submit" style="background: #f39c12;"><i class="fa-solid fa-save"></i> Guardar Abono</button>
                        <button type="button" class="btn btn-cancel" id="abono-btn-cancel" onclick="cancelarAbono()"><i class="fa-solid fa-times"></i> Cancelar</button>
                    </div>
                </form>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código / Icono</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Color</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($abonos as $a): ?>
                        <tr>
                            <td><i class="fa-solid <?= htmlspecialchars($a['icono']) ?>"></i> <?= htmlspecialchars($a['tipo_codigo']) ?></td>
                            <td><strong><?= htmlspecialchars($a['nombre']) ?></strong></td>
                            <td><?= number_format($a['precio'], 2, ',', '.') ?> €</td>
                            <td><span style="display:inline-block; width:20px; height:20px; background:<?= $a['color'] ?>; border-radius:5px;"></span></td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn btn-edit" onclick="editarAbono(
                                        '<?= addslashes($a['tipo_codigo']) ?>', 
                                        '<?= addslashes($a['nombre']) ?>', 
                                        <?= $a['precio'] ?>, 
                                        '<?= addslashes($a['color']) ?>', 
                                        '<?= addslashes($a['icono']) ?>', 
                                        '<?= addslashes($a['descripcion']) ?>'
                                    )" title="Editar"><i class="fa-solid fa-pen"></i></button>

                                    <form method="POST" onsubmit="return confirm('¿Borrar abono <?= $a['nombre'] ?>?');" style="margin:0;">
                                        <input type="hidden" name="tipo_gestion" value="abono">
                                        <input type="hidden" name="accion" value="borrar">
                                        <input type="hidden" name="tipo_codigo" value="<?= $a['tipo_codigo'] ?>">
                                        <button type="submit" class="btn btn-danger" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

    <script>
        // ==========================================
        // JS PARA EDITAR PROMOCIONES
        // ==========================================
        function editarPromo(id, codigo, descuento, fecha, usos) {
            document.getElementById('promo-form-title').innerHTML = '<i class="fa-solid fa-pen"></i> Editando Promoción: ' + codigo;
            document.getElementById('promo-accion').value = 'editar';
            document.getElementById('promo-id').value = id;
            
            document.getElementById('promo-codigo').value = codigo;
            document.getElementById('promo-descuento').value = descuento;
            document.getElementById('promo-fecha').value = fecha;
            document.getElementById('promo-usos').value = usos;
            
            document.getElementById('promo-btn-submit').innerHTML = '<i class="fa-solid fa-save"></i> Actualizar';
            document.getElementById('promo-btn-submit').className = 'btn btn-edit';
            document.getElementById('promo-btn-cancel').style.display = 'inline-flex';
        }

        function cancelarPromo() {
            document.getElementById('promoForm').reset();
            document.getElementById('promo-form-title').innerHTML = '<i class="fa-solid fa-plus"></i> Añadir Nueva Promoción';
            document.getElementById('promo-accion').value = 'crear';
            document.getElementById('promo-id').value = '';
            
            document.getElementById('promo-btn-submit').innerHTML = '<i class="fa-solid fa-save"></i> Guardar Promoción';
            document.getElementById('promo-btn-submit').className = 'btn btn-success';
            document.getElementById('promo-btn-cancel').style.display = 'none';
        }

        // ==========================================
        // JS PARA EDITAR ABONOS
        // ==========================================
        function editarAbono(codigo, nombre, precio, color, icono, desc) {
            document.getElementById('abono-form-title').innerHTML = '<i class="fa-solid fa-pen"></i> Editando Abono: ' + nombre;
            document.getElementById('abono-accion').value = 'editar';
            
            // El código no se debe poder cambiar porque es la Primary Key
            document.getElementById('abono-codigo').value = codigo;
            document.getElementById('abono-codigo').readOnly = true; 
            document.getElementById('abono-codigo').style.background = '#e9ecef';
            
            document.getElementById('abono-nombre').value = nombre;
            document.getElementById('abono-precio').value = precio;
            document.getElementById('abono-color').value = color;
            document.getElementById('abono-icono').value = icono;
            document.getElementById('abono-desc').value = desc;
            
            document.getElementById('abono-btn-submit').innerHTML = '<i class="fa-solid fa-save"></i> Actualizar';
            document.getElementById('abono-btn-submit').className = 'btn btn-edit';
            document.getElementById('abono-btn-submit').style.background = '#ffc107'; // Reset orange color to yellow edit
            document.getElementById('abono-btn-cancel').style.display = 'inline-flex';
        }

        function cancelarAbono() {
            document.getElementById('abonoForm').reset();
            document.getElementById('abono-form-title').innerHTML = '<i class="fa-solid fa-plus"></i> Añadir Nuevo Abono';
            document.getElementById('abono-accion').value = 'crear';
            
            document.getElementById('abono-codigo').readOnly = false;
            document.getElementById('abono-codigo').style.background = 'white';
            
            document.getElementById('abono-btn-submit').innerHTML = '<i class="fa-solid fa-save"></i> Guardar Abono';
            document.getElementById('abono-btn-submit').className = 'btn btn-success';
            document.getElementById('abono-btn-submit').style.background = '#f39c12';
            document.getElementById('abono-btn-cancel').style.display = 'none';
        }
    </script>
</body>
</html>