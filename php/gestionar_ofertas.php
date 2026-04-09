<?php
session_start();

require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/auth_helpers.php';

// =========================================================================
// 1. SEGURIDAD ESTRICTA: COMPROBAR QUE ES UN EMPLEADO LOGUEADO
// =========================================================================
$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'empleado') {
    header('Location: employee_login.php?error=no_autorizado');
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

// 2. PROCESAR FORMULARIOS (CREAR, EDITAR Y BORRAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo_gestion'] ?? ''; 
    $accion = $_POST['accion'] ?? '';     

    try {
        // ==========================================
        // GESTIÓN DE PROMOCIONES
        // ==========================================
        if ($tipo === 'promo') {
            $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
            $descuento = $_POST['descuento'] ?? 0;
            $fecha_inicio = $_POST['fecha_inicio'] ?? '';
            $fecha_fin = $_POST['fecha_fin'] ?? '';
            $usos_maximos = !empty($_POST['usos_maximos']) ? $_POST['usos_maximos'] : null;

            if ($accion === 'crear') {
                $stmt = $pdo->prepare("INSERT INTO PROMOCION (codigo, descuento_porcentaje, fecha_inicio, fecha_fin, usos_maximos) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$codigo, $descuento, $fecha_inicio, $fecha_fin, $usos_maximos]);
                $mensaje_exito = "Promoción '$codigo' creada correctamente.";

            } elseif ($accion === 'editar') {
                $stmt = $pdo->prepare("UPDATE PROMOCION SET codigo = ?, descuento_porcentaje = ?, fecha_inicio = ?, fecha_fin = ?, usos_maximos = ? WHERE id_promocion = ?");
                $stmt->execute([$codigo, $descuento, $fecha_inicio, $fecha_fin, $usos_maximos, $_POST['id_promocion']]);
                $mensaje_exito = "Promoción actualizada correctamente.";

            } elseif ($accion === 'borrar') {
                $stmt = $pdo->prepare("DELETE FROM PROMOCION WHERE id_promocion = ?");
                $stmt->execute([$_POST['id_promocion']]);
                $mensaje_exito = "Promoción eliminada.";
            }
        } 
        
        // ==========================================
        // GESTIÓN DE TIPOS DE ABONO
        // ==========================================
        elseif ($tipo === 'abono') {
            // Adaptado estrictamente a las columnas de TIPO_ABONO de tu base de datos real
            $tipo_codigo = strtolower(trim($_POST['tipo_codigo'] ?? ''));
            $nombre = $_POST['nombre'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $precio = $_POST['precio'] ?? 0;
            $icono = $_POST['icono'] ?? 'fa-ticket';
            $color = $_POST['color'] ?? '#0a2a66';

            if ($accion === 'crear') {
                $stmt = $pdo->prepare("INSERT INTO TIPO_ABONO (tipo_codigo, nombre, descripcion, precio, icono, color) 
                                       VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$tipo_codigo, $nombre, $descripcion, $precio, $icono, $color]);
                $mensaje_exito = "Abono '$nombre' creado correctamente.";

            } elseif ($accion === 'editar') {
                // El tipo_codigo no se puede editar porque es Primary Key
                $stmt = $pdo->prepare("UPDATE TIPO_ABONO SET nombre = ?, descripcion = ?, precio = ?, icono = ?, color = ? 
                                       WHERE tipo_codigo = ?");
                $stmt->execute([$nombre, $descripcion, $precio, $icono, $color, $tipo_codigo]);
                $mensaje_exito = "Abono actualizado correctamente.";

            } elseif ($accion === 'borrar') {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM ABONO WHERE tipo = ?");
                $stmtCheck->execute([$_POST['tipo_codigo']]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $mensaje_error = "No puedes borrar este abono porque hay clientes que ya lo han comprado.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM TIPO_ABONO WHERE tipo_codigo = ?");
                    $stmt->execute([$_POST['tipo_codigo']]);
                    $mensaje_exito = "Abono eliminado del catálogo.";
                }
            }
        }

    } catch (PDOException $e) {
        $mensaje_error = "Error de Base de Datos: " . $e->getMessage();
    }
}

// 3. OBTENER DATOS PARA MOSTRAR
$promociones = $pdo->query("SELECT * FROM PROMOCION ORDER BY fecha_fin DESC")->fetchAll(PDO::FETCH_ASSOC);

// CORREGIDO: Usamos ORDER BY precio en lugar de precio_base
$abonos = $pdo->query("SELECT * FROM TIPO_ABONO ORDER BY precio ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Gestión de Ofertas y Abonos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0a2a66;
            --secondary-color: #2e2ec2;
            --bg-color: #f4f7fb;
            --text-color: #333;
            --card-bg: #ffffff;
            --border-color: #e1e5eb;
            --success-color: #17632A;
            --danger-color: #dc3545;
            --edit-color: #17a2b8;
            --promo-color: #3156fc;
        }

        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-color); color: var(--text-color); margin: 0; padding: 0; }

        .header-admin { background-color: var(--primary-color); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header-admin h1 { margin: 0; font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .header-admin a { color: white; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 5px; background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .header-admin a:hover { background: rgba(255,255,255,0.2); }

        .section-title { text-align: center; color: var(--primary-color); margin: 40px 0 20px 0; font-size: 2rem; border-bottom: 3px solid var(--secondary-color); display: inline-block; padding-bottom: 5px; }
        .title-wrapper { text-align: center; }

        .container { max-width: 1300px; margin: 0 auto 40px auto; padding: 0 20px; display: grid; grid-template-columns: 350px 1fr; gap: 25px; align-items: start; }

        .admin-card { background: var(--card-bg); border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 25px; border: 1px solid var(--border-color); }
        .admin-card h2 { margin-top: 0; border-bottom: 2px solid var(--border-color); padding-bottom: 15px; margin-bottom: 20px; font-size: 1.25rem; display: flex; align-items: center; gap: 10px; }
        .card-promo h2 { color: var(--promo-color); }
        .card-abono h2 { color: var(--secondary-color); }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; width: 90%; margin: 20px auto; max-width: 800px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-family: inherit; box-sizing: border-box; transition: border-color 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 3px rgba(10, 42, 102, 0.1); }
        .input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

        .btn { padding: 10px 15px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-family: inherit; display: inline-flex; align-items: center; gap: 8px; justify-content: center; transition: all 0.2s; font-size: 0.95rem; }
        .btn-success { background: var(--success-color); color: white; width: 100%; }
        .btn-success:hover { background: #218838; }
        
        .btn-promo { background: var(--promo-color); color: white; width: 100%; }
        .btn-promo:hover { background: #c82333; }
        
        .btn-abono { background: var(--secondary-color); color: white; width: 100%; }
        .btn-abono:hover { background: #d68910; }

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

        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; color: white; display: inline-block; }
        .badge-active { background: #17632A; }
        .badge-expired { background: #dc3545; }

        @media (max-width: 1000px) { .container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <header class="header-admin">
        <h1><i class="fa-solid fa-tags"></i> Gestión de Ofertas y Abonos</h1>
        <a href="../vendedor.php"><i class="fa-solid fa-arrow-left"></i> Volver al Panel</a>
    </header>

    <?php if ($mensaje_exito): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= $mensaje_exito ?></div>
    <?php endif; ?>
    <?php if ($mensaje_error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= $mensaje_error ?></div>
    <?php endif; ?>

    <div class="title-wrapper">
        <h2 class="section-title" style="color: var(--promo-color); border-color: var(--promo-color);"><i class="fa-solid fa-ticket-simple"></i> Códigos Promocionales</h2>
    </div>

    <div class="container">
        <div class="admin-card card-promo">
            <h2 id="promo-form-title"><i class="fa-solid fa-plus-circle"></i> Crear Promoción</h2>
            <form method="POST" id="promoForm">
                <input type="hidden" name="tipo_gestion" value="promo">
                <input type="hidden" name="accion" id="promo-accion" value="crear">
                <input type="hidden" name="id_promocion" id="promo-id" value="">

                <div class="input-row">
                    <div class="form-group">
                        <label>Código Promo</label>
                        <input type="text" name="codigo" id="promo-codigo" required placeholder="Ej: VERANO20" style="text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label>Descuento (%)</label>
                        <input type="number" name="descuento" id="promo-descuento" step="0.01" min="1" max="100" required placeholder="Ej: 20">
                    </div>
                </div>

                <div class="input-row">
                    <div class="form-group">
                        <label>Válido Desde</label>
                        <input type="date" name="fecha_inicio" id="promo-inicio" required>
                    </div>
                    <div class="form-group">
                        <label>Válido Hasta</label>
                        <input type="date" name="fecha_fin" id="promo-fin" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Límite de Usos (Opcional)</label>
                    <input type="number" name="usos_maximos" id="promo-usos" min="1" placeholder="Dejar en blanco si es ilimitado">
                </div>

                <button type="submit" id="promo-btn-submit" class="btn btn-promo">
                    <i class="fa-solid fa-save"></i> Guardar Promoción
                </button>
                <button type="button" id="promo-btn-cancel" class="btn btn-cancel" style="display:none;" onclick="cancelarPromo()">
                    <i class="fa-solid fa-xmark"></i> Cancelar Edición
                </button>
            </form>
        </div>

        <div class="admin-card card-promo">
            <h2><i class="fa-solid fa-list"></i> Promociones Activas</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Dcto.</th>
                            <th>Validez</th>
                            <th>Usos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($promociones)): ?>
                            <tr><td colspan="6" style="text-align: center; color: #666;">No hay promociones.</td></tr>
                        <?php else: ?>
                            <?php foreach ($promociones as $p): 
                                $activa = (strtotime($p['fecha_fin']) >= strtotime('today'));
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($p['codigo']) ?></strong></td>
                                    <td><b style="color: var(--promo-color);">-<?= (float)$p['descuento_porcentaje'] ?>%</b></td>
                                    <td style="font-size: 0.85em;">
                                        <?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?> <br>
                                        <?= date('d/m/Y', strtotime($p['fecha_fin'])) ?>
                                    </td>
                                    <td><?= $p['usos_actuales'] ?> / <?= $p['usos_maximos'] ?? '∞' ?></td>
                                    <td>
                                        <span class="badge <?= $activa ? 'badge-active' : 'badge-expired' ?>">
                                            <?= $activa ? 'Activa' : 'Caducada' ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <button class="btn btn-sm btn-action-edit" onclick="prepararPromo(<?= $p['id_promocion'] ?>, '<?= $p['codigo'] ?>', <?= $p['descuento_porcentaje'] ?>, '<?= $p['fecha_inicio'] ?>', '<?= $p['fecha_fin'] ?>', '<?= $p['usos_maximos'] ?? '' ?>')" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta promoción?');">
                                            <input type="hidden" name="tipo_gestion" value="promo">
                                            <input type="hidden" name="accion" value="borrar">
                                            <input type="hidden" name="id_promocion" value="<?= $p['id_promocion'] ?>">
                                            <button type="submit" class="btn btn-sm btn-action-delete" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
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

    <div class="title-wrapper">
        <h2 class="section-title"><i class="fa-solid fa-address-card"></i> Catálogo de Abonos</h2>
    </div>

    <div class="container">
        <div class="admin-card card-abono">
            <h2 id="abono-form-title"><i class="fa-solid fa-plus-circle"></i> Añadir Nuevo Abono</h2>
            <form method="POST" id="abonoForm">
                <input type="hidden" name="tipo_gestion" value="abono">
                <input type="hidden" name="accion" id="abono-accion" value="crear">

                <div class="input-row">
                    <div class="form-group">
                        <label>Identificador Único</label>
                        <input type="text" name="tipo_codigo" id="abono-codigo" required placeholder="Ej: joven_30" style="text-transform: lowercase;">
                        <small style="color: #666; font-size: 0.8em;">Sin espacios (ej: mensual, joven, 10viajes)</small>
                    </div>
                    <div class="form-group">
                        <label>Nombre Comercial</label>
                        <input type="text" name="nombre" id="abono-nombre" required placeholder="Ej: Abono Joven">
                    </div>
                </div>

                <div class="input-row">
                    <div class="form-group">
                        <label>Precio (€)</label>
                        <input type="number" name="precio" id="abono-precio" step="0.01" min="0" required placeholder="Ej: 49.99">
                    </div>
                    <div class="form-group">
                        <label>Color (Tarjeta)</label>
                        <input type="color" name="color" id="abono-color" value="#0a2a66" style="height: 40px; padding: 2px;">
                    </div>
                </div>

                <div class="form-group">
                    <label>Icono FontAwesome</label>
                    <input type="text" name="icono" id="abono-icono" value="fa-ticket" placeholder="Ej: fa-calendar-alt">
                </div>

                <div class="form-group">
                    <label>Descripción para el cliente</label>
                    <textarea name="descripcion" id="abono-desc" rows="3" required placeholder="Explica las ventajas de este abono..."></textarea>
                </div>

                <button type="submit" id="abono-btn-submit" class="btn btn-abono">
                    <i class="fa-solid fa-save"></i> Guardar Abono
                </button>
                <button type="button" id="abono-btn-cancel" class="btn btn-cancel" style="display:none;" onclick="cancelarAbono()">
                    <i class="fa-solid fa-xmark"></i> Cancelar Edición
                </button>
            </form>
        </div>

        <div class="admin-card card-abono">
            <h2><i class="fa-solid fa-list"></i> Abonos a la venta</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Diseño</th>
                            <th>Abono</th>
                            <th>Precio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($abonos)): ?>
                            <tr><td colspan="4" style="text-align: center; color: #666;">No hay abonos a la venta.</td></tr>
                        <?php else: ?>
                            <?php foreach ($abonos as $a): ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <div style="background-color: <?= htmlspecialchars($a['color']) ?>; color: white; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin: 0 auto;">
                                            <i class="fa-solid <?= htmlspecialchars($a['icono']) ?>"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($a['nombre']) ?></strong><br>
                                        <span style="font-size: 0.8em; color: #888;">ID: <?= htmlspecialchars($a['tipo_codigo']) ?></span>
                                    </td>
                                    <td><strong style="font-size: 1.1em; color: var(--primary-color);"><?= number_format($a['precio'], 2, ',', '.') ?> €</strong></td>
                                    <td class="actions-cell">
                                        <button class="btn btn-sm btn-action-edit" onclick="prepararAbono('<?= $a['tipo_codigo'] ?>', '<?= addslashes($a['nombre']) ?>', <?= $a['precio'] ?>, '<?= $a['color'] ?>', '<?= $a['icono'] ?>', '<?= addslashes($a['descripcion']) ?>')" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                        
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este Abono? Los clientes que lo compraron lo mantendrán.');">
                                            <input type="hidden" name="tipo_gestion" value="abono">
                                            <input type="hidden" name="accion" value="borrar">
                                            <input type="hidden" name="tipo_codigo" value="<?= $a['tipo_codigo'] ?>">
                                            <button type="submit" class="btn btn-sm btn-action-delete" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
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
        // ==========================================
        // JS PARA PROMOCIONES
        // ==========================================
        function prepararPromo(id, cod, desc, ini, fin, max) {
            document.getElementById('promo-id').value = id;
            document.getElementById('promo-accion').value = 'editar';
            document.getElementById('promo-form-title').innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Editando Promo: ' + cod;
            
            document.getElementById('promo-codigo').value = cod;
            document.getElementById('promo-descuento').value = desc;
            document.getElementById('promo-inicio').value = ini;
            document.getElementById('promo-fin').value = fin;
            document.getElementById('promo-usos').value = max;
            
            const btn = document.getElementById('promo-btn-submit');
            btn.innerHTML = '<i class="fa-solid fa-save"></i> Actualizar Promo';
            btn.className = 'btn btn-edit-mode';
            
            document.getElementById('promo-btn-cancel').style.display = 'inline-flex';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function cancelarPromo() {
            document.getElementById('promoForm').reset();
            document.getElementById('promo-id').value = '';
            document.getElementById('promo-accion').value = 'crear';
            document.getElementById('promo-form-title').innerHTML = '<i class="fa-solid fa-plus-circle"></i> Crear Promoción';
            
            const btn = document.getElementById('promo-btn-submit');
            btn.innerHTML = '<i class="fa-solid fa-save"></i> Guardar Promoción';
            btn.className = 'btn btn-promo';
            
            document.getElementById('promo-btn-cancel').style.display = 'none';
        }

        // ==========================================
        // JS PARA ABONOS (Actualizado sin duracion_dias ni viajes_base)
        // ==========================================
        function prepararAbono(cod, nom, pre, col, ico, desc) {
            document.getElementById('abono-accion').value = 'editar';
            document.getElementById('abono-form-title').innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Editando Abono: ' + nom;
            
            const inputCodigo = document.getElementById('abono-codigo');
            inputCodigo.value = cod;
            inputCodigo.readOnly = true; 
            inputCodigo.style.backgroundColor = '#e9ecef';
            
            document.getElementById('abono-nombre').value = nom;
            document.getElementById('abono-precio').value = pre;
            document.getElementById('abono-color').value = col;
            document.getElementById('abono-icono').value = ico;
            document.getElementById('abono-desc').value = desc;
            
            const btn = document.getElementById('abono-btn-submit');
            btn.innerHTML = '<i class="fa-solid fa-save"></i> Actualizar Abono';
            btn.className = 'btn btn-edit-mode';
            
            document.getElementById('abono-btn-cancel').style.display = 'inline-flex';
            
            // Scroll hacia la sección de abonos
            document.querySelector('.card-abono').scrollIntoView({ behavior: 'smooth' });
        }

        function cancelarAbono() {
            document.getElementById('abonoForm').reset();
            document.getElementById('abono-accion').value = 'crear';
            document.getElementById('abono-form-title').innerHTML = '<i class="fa-solid fa-plus-circle"></i> Añadir Nuevo Abono';
            
            const inputCodigo = document.getElementById('abono-codigo');
            inputCodigo.readOnly = false;
            inputCodigo.style.backgroundColor = '#ffffff';
            
            const btn = document.getElementById('abono-btn-submit');
            btn.innerHTML = '<i class="fa-solid fa-save"></i> Guardar Abono';
            btn.className = 'btn btn-abono';
            
            document.getElementById('abono-btn-cancel').style.display = 'none';
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
