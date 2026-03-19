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
            $pdo->exec("DELETE FROM incidencia WHERE origen = 'iot' AND estado = 'reportado' AND fecha_reporte < (NOW() - INTERVAL '24 hours')");
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
    <link rel="stylesheet" href="css/mantenimiento.css?v=20260314k">
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

<main class="maint-container" data-maint-id="<?php echo $idEmpleado ? (int)$idEmpleado : 0; ?>">
    <div class="page-title">
        <h1><i class="fa-solid fa-wrench"></i> Centro de control de unidades</h1>
        <p>Sesion activa: <?php echo htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?> | Especialidad: <?php echo htmlspecialchars((string)$especialidad, ENT_QUOTES, 'UTF-8'); ?> | Turno: <?php echo htmlspecialchars((string)$turno, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($certificaciones !== ''): ?>
            <p>Certificaciones: <?php echo htmlspecialchars((string)$certificaciones, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </div>

    <div class="maint-grid">
        <section class="panel profile-panel collapsed">
    <button type="button" id="profileToggle" class="profile-toggle">
        <div class="profile-toggle-left">
            <div class="profile-avatar">
                <i class="fa-solid fa-helmet-safety"></i>
            </div>
            <div>
                <div class="profile-toggle-title">Informacion personal</div>
                <div class="profile-toggle-sub">
                    <?php echo htmlspecialchars(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    | ID #<?php echo $idEmpleado ? (int)$idEmpleado : 0; ?>
                    | Asignadas: <?php echo count($incidencias); ?>
                </div>
            </div>
        </div>
        <i class="fa-solid fa-chevron-down"></i>
    </button>
    <div class="profile-body">
        <form id="profileForm" class="profile-form">
            <div class="profile-grid">
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
                    Telefono
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </label>
                <label>
                    Especialidad
                    <input type="text" name="especialidad" value="<?php echo htmlspecialchars((string)$especialidad, ENT_QUOTES, 'UTF-8'); ?>">
                </label>
                <label>
                    Turno
                    <?php
                        $turnoLower = strtolower((string)$turno);
                        if ($turnoLower === 'maÃ±ana') {
                            $turnoLower = 'manana';
                        }
                    ?>
                    <select name="turno">
                        <option value="manana" <?php echo $turnoLower === 'manana' ? 'selected' : ''; ?>>Manana</option>
                        <option value="tarde" <?php echo $turnoLower === 'tarde' ? 'selected' : ''; ?>>Tarde</option>
                        <option value="noche" <?php echo $turnoLower === 'noche' ? 'selected' : ''; ?>>Noche</option>
                        <option value="rotativo" <?php echo $turnoLower === 'rotativo' ? 'selected' : ''; ?>>Rotativo</option>
                    </select>
                </label>
            </div>
            <label class="profile-full">
                Certificaciones
                <textarea name="certificaciones" rows="2"><?php echo htmlspecialchars((string)$certificaciones, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
            <div class="profile-actions">
                <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button>
                <span id="profileStatus" class="profile-status"></span>
            </div>
        </form>
    </div>
</section>

        <section class="panel issues-list">
            <h2>Incidencias pendientes</h2>
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

        <section class="panel issues-list">
            <h2>Incidencias automaticas (IOT)</h2>
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

        <section class="panel ops-panel">
            <h2>Filtrado de incidencias</h2>
            <div class="filter-row">
                <button class="filter-btn active" data-filter="all">Todas</button>
                <button class="filter-btn" data-filter="reportado">Reportado</button>
                <button class="filter-btn" data-filter="en_proceso">Confirmado</button>
                <button class="filter-btn" data-filter="resuelto">Resuelto</button>
            </div>
            <div class="carousel-box">
                <div class="carousel-track">
                    <div class="carousel-item">Prioriza incidencias de frenos y puertas.</div>
                    <div class="carousel-item">Confirma para marcar como confirmado.</div>
                    <div class="carousel-item">Anade resolucion para trazabilidad.</div>
                    <div class="carousel-item">Revisa alertas de senalizacion.</div>
                </div>
            </div>
            <div class="quick-actions">
                <button class="quick-btn" id="refreshNow"><i class="fa-solid fa-rotate"></i> Refrescar</button>
                <button class="quick-btn" id="scrollTop"><i class="fa-solid fa-arrow-up"></i> Arriba</button>
            </div>
        </section>

        <section class="panel history-panel">
            <h2>Historico de incidencias</h2>
            <div id="incidenciasHistorico">
                <?php if (count($incidenciasHistorico) === 0): ?>
                    <div class="issue-item low-priority">
                        <p class="issue-desc">No hay incidencias resueltas.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($incidenciasHistorico as $inc): ?>
                        <div class="issue-item low-priority" data-incidencia-id="<?php echo (int)$inc['id_incidencia']; ?>" data-estado="resuelto">
                            <div class="issue-header">
                                <span class="issue-id">#INC-<?php echo (int)$inc['id_incidencia']; ?></span>
                                <span class="priority-tag">RESUELTO</span>
                            </div>
                            <p class="issue-desc"><?php echo htmlspecialchars((string)$inc['descripcion'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="issue-actions">
                                <button class="btn-detail" data-incidencia-id="<?php echo (int)$inc['id_incidencia']; ?>">Ver detalles</button>
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
</body>
</html>
