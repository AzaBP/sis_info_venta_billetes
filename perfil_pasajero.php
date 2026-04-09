<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/php/auth_helpers.php';
require_once __DIR__ . '/php/Conexion.php';

$assetVersion = (string)@filemtime(__FILE__);

$usuarioSesion = $_SESSION['usuario'] ?? null;
if (!$usuarioSesion) {
    header('Location: inicio_sesion.html');
    exit;
}

if (($usuarioSesion['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($usuarioSesion));
    exit;
}

$perfil = [
    'nombre' => $usuarioSesion['nombre'] ?? 'Usuario',
    'apellido' => $usuarioSesion['apellido'] ?? '',
    'email' => $usuarioSesion['email'] ?? '',
    'telefono' => '',
    'numero_documento' => '',
    'fecha_nacimiento' => '',
    'calle' => '',
    'ciudad' => '',
    'codigo_postal' => '',
    'pais' => '',
    'notificaciones_ofertas' => false
];

$idUsuarioSesion = (int)($usuarioSesion['id_usuario'] ?? 0);

try {
    $conexion = new Conexion();
    $pdo = $conexion->conectar();

    if ($pdo && $idUsuarioSesion > 0) {
        $sql = "SELECT u.nombre, u.apellido, u.email, u.telefono,
                   p.numero_documento, p.fecha_nacimiento, p.calle, p.ciudad, p.codigo_postal, p.pais,
                   p.newsletter
                FROM usuario u
                LEFT JOIN pasajero p ON p.id_usuario = u.id_usuario
                WHERE u.id_usuario = :id_usuario
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuarioSesion]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fila) {
            $perfil['nombre'] = $fila['nombre'] ?? $perfil['nombre'];
            $perfil['apellido'] = $fila['apellido'] ?? $perfil['apellido'];
            $perfil['email'] = $fila['email'] ?? $perfil['email'];
            $perfil['telefono'] = $fila['telefono'] ?? '';
            $perfil['numero_documento'] = $fila['numero_documento'] ?? '';
            $perfil['fecha_nacimiento'] = $fila['fecha_nacimiento'] ?? '';
            $perfil['calle'] = $fila['calle'] ?? '';
            $perfil['ciudad'] = $fila['ciudad'] ?? '';
            $perfil['codigo_postal'] = $fila['codigo_postal'] ?? '';
            $perfil['pais'] = $fila['pais'] ?? '';
            $perfil['notificaciones_ofertas'] = (bool)($fila['newsletter'] ?? false);
        }
    }
} catch (Throwable $e) {
    // Si falla la carga del perfil, se muestran los datos de sesion basicos.
}

$nombreSesion = $perfil['nombre'] ?: 'Usuario';
$fechaNacimientoVista = '-';
$fechaNacimientoInput = '';

if (!empty($perfil['fecha_nacimiento'])) {
    $ts = strtotime($perfil['fecha_nacimiento']);
    if ($ts !== false) {
        $fechaNacimientoVista = date('d/m/Y', $ts);
        $fechaNacimientoInput = date('Y-m-d', $ts);
    }
}

$notificacionesViaje = (bool)($_SESSION['preferencias_pasajero']['notificaciones_viaje'] ?? true);
$notificacionesOfertas = (bool)$perfil['notificaciones_ofertas'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Mi Perfil</title>
    <style>
        .content-panel[hidden] { display: none !important; }
    </style>
    <link rel="stylesheet" href="css/index.css?v=<?php echo urlencode($assetVersion); ?>">
    <link rel="stylesheet" href="css/session_menu.css?v=<?php echo urlencode($assetVersion); ?>">
    <link rel="stylesheet" href="css/perfil_pasajero.css?v=<?php echo urlencode($assetVersion); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb</div>
        <nav class="nav">
            <a href="index.php">Inicio</a>
            <a href="compra.php">Billetes</a>
            <a href="ofertas.php">Ofertas</a>
            <a href="ayuda.php">Ayuda</a>
        </nav>
        <div class="user-actions" id="userActions">
            <div class="account-dropdown open-on-hover">
                <button type="button" class="account-toggle">
                    <span class="account-avatar"><?php echo strtoupper(substr($nombreSesion, 0, 1)); ?></span>
                    <span class="account-name"><?php echo htmlspecialchars($nombreSesion, ENT_QUOTES, 'UTF-8'); ?></span>
                    <i class="fa-solid fa-caret-down"></i>
                </button>
                <div class="account-menu">
                    <a href="perfil_pasajero.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
                    <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesion</a>
                </div>
            </div>
        </div>
    </header>

    <main class="perfil-main">
        <section class="perfil-layout">
            <aside class="perfil-sidebar">
                <div class="sidebar-user-card">
                    <div class="avatar-large"><?php echo strtoupper(substr($nombreSesion, 0, 1)); ?></div>
                    <h1><?php echo htmlspecialchars($perfil['nombre'] . ' ' . $perfil['apellido'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p><?php echo htmlspecialchars($perfil['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <nav class="sidebar-nav" aria-label="Apartados del perfil">
                    <button class="sidebar-link active" data-target="panel-datos" type="button">
                        <i class="fa-regular fa-id-card"></i>
                        <span>Datos personales</span>
                    </button>
                    <button class="sidebar-link" data-target="panel-bonos" type="button">
                        <i class="fa-solid fa-ticket"></i>
                        <span>Mis bonos</span>
                    </button>
                    <button class="sidebar-link" data-target="panel-viajes" type="button">
                        <i class="fa-solid fa-train-subway"></i>
                        <span>Mis viajes y billetes</span>
                    </button>
                    <button class="sidebar-link" data-target="panel-config" type="button">
                        <i class="fa-solid fa-gear"></i>
                        <span>Configuracion</span>
                    </button>
                </nav>
            </aside>

            <section class="perfil-content">
                <div class="content-panel active" id="panel-datos">
                    <div class="panel-header">
                        <h2>Datos personales</h2>
                        <p>Consulta tu informacion de cuenta y facturacion.</p>
                    </div>

                    <div class="info-grid">
                        <article class="info-card">
                            <label>Nombre</label>
                            <strong><?php echo htmlspecialchars($perfil['nombre'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label>Apellidos</label>
                            <strong><?php echo htmlspecialchars($perfil['apellido'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label>Documento</label>
                            <strong><?php echo htmlspecialchars($perfil['numero_documento'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label>Fecha de nacimiento</label>
                            <strong><?php echo htmlspecialchars($fechaNacimientoVista, ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label>Email</label>
                            <strong><?php echo htmlspecialchars($perfil['email'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label>Telefono</label>
                            <strong><?php echo htmlspecialchars($perfil['telefono'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card info-card-wide">
                            <label>Direccion</label>
                            <strong><?php echo htmlspecialchars($perfil['calle'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label>Ciudad</label>
                            <strong><?php echo htmlspecialchars($perfil['ciudad'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label>Codigo postal</label>
                            <strong><?php echo htmlspecialchars($perfil['codigo_postal'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label>Pais</label>
                            <strong><?php echo htmlspecialchars($perfil['pais'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                    </div>
                </div>

                <div class="content-panel" id="panel-bonos" hidden>
                    <div class="panel-header">
                        <h2>Mis bonos</h2>
                        <p>Revisa tus abonos activos, caducados y viajes restantes.</p>
                    </div>
                    <div id="abonos-list" class="cards-grid"></div>
                </div>

                <div class="content-panel" id="panel-viajes" hidden>
                    <div class="panel-header">
                        <h2>Mis viajes y billetes</h2>
                        <p>Billetes comprados y avisos que afectan a tus trayectos.</p>
                    </div>

                    <h3 class="section-subtitle">Viajes proximos</h3>
                    <div id="viajes-proximos-list" class="cards-grid"></div>

                    <h3 class="section-subtitle">Viajes finalizados</h3>
                    <div id="viajes-finalizados-list" class="cards-grid"></div>

                    <h3 class="section-subtitle">Incidencias de viaje</h3>
                    <div id="incidencias-viaje" class="cards-grid"></div>
                </div>

                <div class="content-panel" id="panel-config" hidden>
                    <div class="panel-header">
                        <h2>Configuracion</h2>
                        <p>Gestiona notificaciones, datos de perfil y seguridad.</p>
                    </div>

                    <div class="profile-panel accordion-wrapper">
                        <div class="accordion-item">
                            <div class="accordion-header">
                                <div class="header-title">
                                    <i class="fa-regular fa-bell"></i> Preferencias de avisos
                                </div>
                                <i class="fa-solid fa-chevron-down arrow-icon"></i>
                            </div>
                            <div class="accordion-content">
                                <div class="notifications-list">
                                    <p id="config-status-notificaciones" class="config-status" aria-live="polite"></p>
                                    <div class="notification-option">
                                        <div class="notif-text">
                                            <strong>Avisos de viaje</strong>
                                            <p>Retrasos, cambios de via e incidencias.</p>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" id="notif_viaje" <?php echo $notificacionesViaje ? 'checked' : ''; ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                    <hr class="trip-separator">
                                    <div class="notification-option">
                                        <div class="notif-text">
                                            <strong>Ofertas comerciales</strong>
                                            <p>Promociones exclusivas y descuentos.</p>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" id="notif_ofertas" <?php echo $notificacionesOfertas ? 'checked' : ''; ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                    <div class="form-full">
                                        <button type="button" class="btn-primary" id="btn-guardar-notificaciones">Guardar preferencias</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <div class="accordion-header">
                                <div class="header-title">
                                    <i class="fa-solid fa-user-pen"></i> Modificar datos personales
                                </div>
                                <i class="fa-solid fa-chevron-down arrow-icon"></i>
                            </div>
                            <div class="accordion-content">
                                <form class="form-grid" id="form-datos-perfil">
                                    <p id="config-status-datos" class="config-status full-width" aria-live="polite"></p>
                                    <div class="form-group">
                                        <label>Nombre</label>
                                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($perfil['nombre'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Apellidos</label>
                                        <input type="text" name="apellido" value="<?php echo htmlspecialchars($perfil['apellido'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Documento (DNI/NIE)</label>
                                        <input type="text" value="<?php echo htmlspecialchars($perfil['numero_documento'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Fecha de nacimiento</label>
                                        <input type="date" name="fecha_nacimiento" value="<?php echo htmlspecialchars($fechaNacimientoInput, ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Email de contacto</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($perfil['email'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Telefono movil</label>
                                        <input type="tel" name="telefono" value="<?php echo htmlspecialchars($perfil['telefono'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                                    </div>
                                    <div class="form-group full-width">
                                        <label>Direccion</label>
                                        <input type="text" name="calle" value="<?php echo htmlspecialchars($perfil['calle'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Ciudad</label>
                                        <input type="text" name="ciudad" value="<?php echo htmlspecialchars($perfil['ciudad'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Codigo postal</label>
                                        <input type="text" name="codigo_postal" value="<?php echo htmlspecialchars($perfil['codigo_postal'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Pais</label>
                                        <input type="text" name="pais" value="<?php echo htmlspecialchars($perfil['pais'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                                    </div>
                                    <div class="form-full">
                                        <button type="submit" class="btn-primary" id="btn-guardar-datos">Guardar cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <div class="accordion-header">
                                <div class="header-title">
                                    <i class="fa-solid fa-lock"></i> Seguridad y contrasena
                                </div>
                                <i class="fa-solid fa-chevron-down arrow-icon"></i>
                            </div>
                            <div class="accordion-content">
                                <form class="form-grid" id="form-cambiar-contrasena">
                                    <p id="config-status-password" class="config-status full-width" aria-live="polite"></p>
                                    <div class="form-group full-width">
                                        <label>Contrasena actual</label>
                                        <input type="password" name="password_actual" placeholder="********" class="form-input" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Nueva contrasena</label>
                                        <input type="password" name="password_nueva" class="form-input" required minlength="8">
                                    </div>
                                    <div class="form-group">
                                        <label>Repetir nueva contrasena</label>
                                        <input type="password" name="password_repetida" class="form-input" required minlength="8">
                                    </div>
                                    <div class="form-full">
                                        <button type="submit" class="btn-primary" id="btn-cambiar-contrasena">Cambiar contrasena</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <div class="accordion-header">
                                <div class="header-title danger-title">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Eliminar la cuenta
                                </div>
                                <i class="fa-solid fa-chevron-down arrow-icon"></i>
                            </div>
                            <div class="accordion-content">
                                <div class="danger-zone">
                                    <p>Si eliminas tu cuenta, perderas acceso a tus billetes y abonos activos.</p>
                                    <button type="button" class="btn-danger" id="btn-eliminar-cuenta">Eliminar mi cuenta</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </section>
    </main>

    <div class="modal-overlay" id="delete-account-modal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="delete-account-title">
            <div class="modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <h3 id="delete-account-title">Eliminar cuenta</h3>
            <p>Vas a eliminar tu cuenta de forma permanente. Se borraran tus datos, abonos y billetes.</p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="btn-cancelar-eliminar">Cancelar</button>
                <button type="button" class="btn-danger" id="btn-confirmar-eliminar">Eliminar</button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>TrainWeb</h3>
                <p>Plataforma digital para la busqueda y compra de billetes de tren en todo el territorio nacional.</p>
            </div>
            <div class="footer-column">
                <h4>Servicios</h4>
                <a href="#"><i class="fa-solid fa-ticket"></i> Billetes</a>
                <a href="#"><i class="fa-solid fa-clock"></i> Horarios</a>
                <a href="ofertas.php"><i class="fa-solid fa-tags"></i> Ofertas</a>
                <a href="#"><i class="fa-solid fa-headset"></i> Atencion al cliente</a>
            </div>
            <div class="footer-column">
                <h4>Informacion legal</h4>
                <a href="#"><i class="fa-solid fa-scale-balanced"></i> Aviso legal</a>
                <a href="#"><i class="fa-solid fa-user-shield"></i> Privacidad</a>
                <a href="#"><i class="fa-solid fa-cookie-bite"></i> Cookies</a>
                <a href="#"><i class="fa-solid fa-file-contract"></i> Terminos y condiciones</a>
            </div>
            <div class="footer-column">
                <h4>Redes sociales</h4>
                <a href="#"><i class="fa-brands fa-facebook-f"></i> Facebook</a>
                <a href="#"><i class="fa-brands fa-x-twitter"></i> Twitter</a>
                <a href="#"><i class="fa-brands fa-instagram"></i> Instagram</a>
                <a href="#"><i class="fa-brands fa-linkedin-in"></i> LinkedIn</a>
            </div>
        </div>
        <div class="footer-bottom">© 2026 TrainWeb · Todos los derechos reservados</div>
    </footer>

    <script src="scripts/session_menu.js?v=<?php echo urlencode($assetVersion); ?>"></script>
    <script src="scripts/perfil_pasajero_ui.js?v=<?php echo urlencode($assetVersion); ?>"></script>
    <script src="scripts/perfil_configuracion.js?v=<?php echo urlencode($assetVersion); ?>"></script>
    <script src="scripts/carga_abonos_perfil.js?v=<?php echo urlencode($assetVersion); ?>"></script>
    <script src="scripts/carga_billetes_perfil.js?v=<?php echo urlencode($assetVersion); ?>"></script>
    <script src="scripts/carga_incidencias_pasajero.js?v=<?php echo urlencode($assetVersion); ?>"></script>
</body>
</html>
