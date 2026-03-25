<?php
session_start();

require_once __DIR__ . '/php/Conexion.php';
require_once __DIR__ . '/php/auth_helpers.php';

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'empleado') {
    header('Location: employee_login.php?error=no_autorizado');
    exit;
}

if (($usuario['tipo_empleado'] ?? '') !== 'mantenimiento' && !trainwebEsAdministrador($usuario)) {
    header('Location: ' . trainwebRutaPorRol($usuario));
    exit;
}

$idEmpleado = null;
$incidencias = [];
$incidenciasPendientes = [];
$incidenciasPendientesManual = [];
$incidenciasPendientesIot = [];
$incidenciasHistorico = [];
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

        if ($idEmpleado) {
            $pdo->exec("DELETE FROM incidencia WHERE origen = 'iot' AND estado != 'resuelto' AND fecha_reporte < (NOW() - INTERVAL '24 hours')");
            $stmtInc = $pdo->prepare(
                "SELECT id_incidencia, id_viaje, id_mantenimiento, id_maquinista, tipo_incidencia, origen, descripcion,
                        fecha_reporte, estado, afecta_pasajero, resolucion, fecha_resolucion
                 FROM incidencia
                 ORDER BY CASE estado WHEN 'reportado' THEN 1 WHEN 'en_proceso' THEN 2 ELSE 3 END, fecha_reporte DESC"
            );
            $stmtInc->execute();
            $incidencias = $stmtInc->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($incidencias as $inc) {
                if (($inc['estado'] ?? '') === 'resuelto') {
                    $incidenciasHistorico[] = $inc;
                } else {
                    $incidenciasPendientes[] = $inc;
                    $origen = strtolower((string)($inc['origen'] ?? ''));
                    if ($origen === 'iot') {
                        $incidenciasPendientesIot[] = $inc;
                    } else {
                        $incidenciasPendientesManual[] = $inc;
                    }
                }
            }
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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/mantenimiento.css?v=20260325c">
</head>
<body>
<header class="header">
    <div class="logo">
        <i class="fa-solid fa-train"></i> TrainWeb
        <span style="font-size: .8rem; opacity: .8; font-weight: normal; margin-left: 10px;">| Mantenimiento</span>
    </div>
    <nav class="nav">
        <div class="profile-dropdown">
            <button id="profileNavBtn" class="profile-nav-btn" title="Ver datos personales">
                <i class="fa-solid fa-user-circle"></i>
                <span class="profile-nav-name"><?php echo htmlspecialchars(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> | ID #<?php echo $idEmpleado ? (int)$idEmpleado : 0; ?></span>
            </button>
            <div id="profileMenuNav" class="profile-menu-nav hidden">
                <div class="profile-menu-header">
                    <i class="fa-solid fa-helmet-safety"></i>
                    <div>
                        <div><?php echo htmlspecialchars(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="profile-menu-id">ID #<?php echo $idEmpleado ? (int)$idEmpleado : 0; ?></div>
                    </div>
                </div>
                <form id="profileFormNav" class="profile-form-nav">
                    <label>
                        Nombre
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>
                    <label>
                        Apellido
                        <input type="text" name="apellido" value="<?php echo htmlspecialchars($usuario['apellido'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>
                    <label>
                        Email
                        <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </label>
                    <label>
                        Teléfono
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <label>
                        Especialidad
                        <input type="text" name="especialidad" value="<?php echo htmlspecialchars((string)$especialidad, ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <button type="submit" class="profile-save-btn">Guardar</button>
                </form>
            </div>
        </div>
        <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
    </nav>
</header>

<main class="maint-container" data-maint-id="<?php echo $idEmpleado ? (int)$idEmpleado : 0; ?>">
    <div class="page-title">
        <h1><i class="fa-solid fa-wrench"></i> Centro de control de unidades</h1>
        <p>Sesion activa: <?php echo htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?> | Especialidad: <?php echo htmlspecialchars((string)$especialidad, ENT_QUOTES, 'UTF-8'); ?> | Turno: <?php echo htmlspecialchars((string)$turno, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($certificaciones !== ''): ?>
            <p>Certificaciones: <?php echo htmlspecialchars((string)$certificaciones, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </div>

    <div class="maint-grid">
        <div class="filter-dropdown">
            <button id="filterToggle" class="filter-toggle-btn">
                <i class="fa-solid fa-filter"></i> Filtrar
            </button>
            <div id="filterMenu" class="filter-menu hidden">
                <button class="filter-option active" data-filter="all">
                    <i class="fa-solid fa-check"></i> Todas
                </button>
                <button class="filter-option" data-filter="reportado">
                    <i class="fa-solid fa-exclamation-circle"></i> Reportado
                </button>
                <button class="filter-option" data-filter="en_proceso">
                    <i class="fa-solid fa-hourglass-half"></i> Confirmado
                </button>
                <button class="filter-option" data-filter="resuelto">
                    <i class="fa-solid fa-check-circle"></i> Resuelto
                </button>
            </div>
        </div>

        <section class="panel issues-list">
            <div class="issues-panel-header">Incidencias pendientes</div>
            <div id="incidenciasPendientes">
                <?php if (count($incidenciasPendientesManual) === 0): ?>
                    <div class="issue-item low-priority">
                        <p class="issue-desc">No hay incidencias registradas.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($incidenciasPendientesManual as $inc): ?>
                        <?php
                            $estado = $inc['estado'] ?? '';
                            $estadoClase = $estado === 'reportado' ? 'high-priority' : ($estado === 'en_proceso' ? 'medium-priority' : 'low-priority');
                            $estadoEtiqueta = $estado === 'reportado' ? 'REPORTADO' : ($estado === 'en_proceso' ? 'CONFIRMADO' : 'RESUELTO');
                            $afecta = !empty($inc['afecta_pasajero']) ? 'Afecta pasajeros' : 'No afecta pasajeros';
                            $origenInc = strtoupper((string)($inc['origen'] ?? ''));
                        ?>
                        <div class="issue-item <?php echo $estadoClase; ?>" data-incidencia-id="<?php echo (int)$inc['id_incidencia']; ?>" data-estado="<?php echo htmlspecialchars((string)$estado, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="issue-header">
                                <span class="issue-id">#INC-<?php echo (int)$inc['id_incidencia']; ?></span>
                                <span class="priority-tag"><?php echo $estadoEtiqueta; ?></span>
                            </div>
                            <p class="issue-desc"><?php echo htmlspecialchars((string)$inc['descripcion'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="issue-meta">
                                <span><i class="fa-solid fa-train"></i> Viaje <?php echo (int)$inc['id_viaje']; ?></span>
                                <span><?php echo $origenInc; ?></span>
                                <span>Estado: <?php echo $estadoEtiqueta; ?></span>
                            </div>
                            <div class="issue-actions">
                                <button class="btn-detail" data-incidencia-id="<?php echo (int)$inc['id_incidencia']; ?>">Ver detalles</button>
                                <?php if ($estado === 'reportado'): ?>
                                    <button class="btn-resolve btn-confirm" data-action="confirmar" data-incidencia-id="<?php echo (int)$inc['id_incidencia']; ?>">Confirmar</button>
                                <?php elseif ($estado === 'en_proceso'): ?>
                                    <button class="btn-resolve btn-final" data-action="resolver" data-incidencia-id="<?php echo (int)$inc['id_incidencia']; ?>">Resuelto</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel issues-list iot-panel">
            <div class="issues-panel-header">Incidencias automáticas (IoT)</div>
            <div id="incidenciasPendientesIot">
                <?php if (count($incidenciasPendientesIot) === 0): ?>
                    <div class="issue-item low-priority">
                        <p class="issue-desc">No hay incidencias automaticas.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($incidenciasPendientesIot as $inc): ?>
                        <?php
                            $estado = $inc['estado'] ?? '';
                            $estadoClase = $estado === 'reportado' ? 'high-priority' : ($estado === 'en_proceso' ? 'medium-priority' : 'low-priority');
                            $estadoEtiqueta = $estado === 'reportado' ? 'REPORTADO' : ($estado === 'en_proceso' ? 'CONFIRMADO' : 'RESUELTO');
                            $afecta = !empty($inc['afecta_pasajero']) ? 'Afecta pasajeros' : 'No afecta pasajeros';
                            $origenInc = strtoupper((string)($inc['origen'] ?? ''));
                        ?>
                        <div class="issue-item iot <?php echo $estadoClase; ?>" data-incidencia-id="<?php echo (int)$inc['id_incidencia']; ?>" data-estado="<?php echo htmlspecialchars((string)$estado, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="issue-header">
                                <span class="issue-id">#INC-<?php echo (int)$inc['id_incidencia']; ?></span>
                                <span class="priority-tag"><?php echo $estadoEtiqueta; ?></span>
                            </div>
                            <p class="issue-desc"><?php echo htmlspecialchars((string)$inc['descripcion'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="issue-meta">
                                <span><i class="fa-solid fa-train"></i> Viaje <?php echo (int)$inc['id_viaje']; ?></span>
                                <span><?php echo $origenInc; ?></span>
                                <span>Estado: <?php echo $estadoEtiqueta; ?></span>
                            </div>
                            <div class="issue-actions">
                                <button class="btn-detail" data-incidencia-id="<?php echo (int)$inc['id_incidencia']; ?>">Ver detalles</button>
                                <?php if ($estado === 'reportado'): ?>
                                    <button class="btn-resolve btn-confirm" data-action="confirmar" data-incidencia-id="<?php echo (int)$inc['id_incidencia']; ?>">Confirmar</button>
                                <?php elseif ($estado === 'en_proceso'): ?>
                                    <button class="btn-resolve btn-final" data-action="resolver" data-incidencia-id="<?php echo (int)$inc['id_incidencia']; ?>">Resuelto</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<div id="detailModal" class="detail-modal" hidden>
    <div class="detail-modal-card">
        <button class="detail-close" id="detailClose" aria-label="Cerrar detalle">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div id="detailModalBody" class="detail-modal-body"></div>
    </div>
</div>

<script src="js/mantenimiento.js?v=20260314h"></script>
<script src="js/iot_simulador.js?v=20260325b"></script>
</body>
</html>
