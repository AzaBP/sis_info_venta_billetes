<?php
session_start();

require_once __DIR__ . '/php/Conexion.php';
require_once __DIR__ . '/php/auth_helpers.php';

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'empleado') {
    header('Location: inicio_sesion.html?error=no_autorizado');
    exit;
}

if (($usuario['tipo_empleado'] ?? '') !== 'mantenimiento' && !trainwebEsAdministrador($usuario)) {
    header('Location: index.php?error=acceso_denegado');
    exit;
}

$idEmpleado = null;
$especialidad = 'General';
$turno = 'No asignado';
$certificaciones = '';

try {
    $pdo = (new Conexion())->conectar();
    if ($pdo) {
        $stmt = $pdo->prepare(
            "SELECT e.id_empleado, m.especialidad, m.turno, m.certificaciones
             FROM empleado e
             LEFT JOIN mantenimiento m ON m.id_empleado = e.id_empleado
             WHERE e.id_usuario = :id_usuario
             LIMIT 1"
        );
        $stmt->execute([':id_usuario' => (int)$usuario['id_usuario']]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            $idEmpleado = $fila['id_empleado'] ?? null;
            $especialidad = $fila['especialidad'] ?? $especialidad;
            $turno = $fila['turno'] ?? $turno;
            $certificaciones = $fila['certificaciones'] ?? '';
        }
    }
} catch (Throwable $e) {
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Gestion de Mantenimiento</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/mantenimiento.css">
</head>
<body>
<header class="header">
    <div class="logo">
        <i class="fa-solid fa-train"></i> TrainWeb
        <span style="font-size: .8rem; opacity: .8; font-weight: normal; margin-left: 10px;">| Mantenimiento</span>
    </div>
    <nav class="nav">
        <div class="user-display" style="color: white; margin-right: 20px; font-weight: 500;">
            <i class="fa-solid fa-helmet-safety"></i>
            <?php echo htmlspecialchars(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            <?php if ($idEmpleado): ?>
                | ID #<?php echo (int)$idEmpleado; ?>
            <?php endif; ?>
        </div>
        <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
    </nav>
</header>

<main class="maint-container">
    <div class="page-title">
        <h1><i class="fa-solid fa-wrench"></i> Centro de control de unidades</h1>
        <p>Sesion activa: <?php echo htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?> | Especialidad: <?php echo htmlspecialchars((string)$especialidad, ENT_QUOTES, 'UTF-8'); ?> | Turno: <?php echo htmlspecialchars((string)$turno, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($certificaciones !== ''): ?>
            <p>Certificaciones: <?php echo htmlspecialchars((string)$certificaciones, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </div>

    <div class="maint-grid">
        <section class="panel issues-list">
            <h2>Incidencias pendientes</h2>
            <div id="incidenciasContainer">
                <div class="issue-item high-priority">
                    <div class="issue-header">
                        <span class="issue-id">#INC-8892</span>
                        <span class="priority-tag">URGENTE</span>
                    </div>
                    <p class="issue-desc">Fallo en sistema de frenado auxiliar.</p>
                    <div class="issue-meta">
                        <span><i class="fa-solid fa-train"></i> AVE-208</span>
                        <button onclick="resolverIncidencia(this)" class="btn-resolve">Reparado</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel report-form">
            <h2><i class="fa-solid fa-pen-to-square"></i> Nueva incidencia</h2>
            <form id="formMantenimiento">
                <div class="form-group">
                    <label>ID Tren</label>
                    <select id="trainSelect">
                        <option value="AVE-102">AVE-102</option>
                        <option value="ALVIA-405">ALVIA-405</option>
                        <option value="AVE-208">AVE-208</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Prioridad</label>
                    <select id="prioritySelect">
                        <option value="low">Baja</option>
                        <option value="medium">Media</option>
                        <option value="high">Alta</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Descripcion</label>
                    <textarea id="issueDesc" rows="4" placeholder="Describe la averia..."></textarea>
                </div>
                <button type="submit" class="btn-submit">Registrar incidencia</button>
            </form>
        </section>
    </div>
</main>

<script src="js/mantenimiento.js"></script>
</body>
</html>

