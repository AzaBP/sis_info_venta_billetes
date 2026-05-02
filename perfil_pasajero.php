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
        <a href="index.php" class="logo"><i class="fa-solid fa-train"></i> TrainWeb</a>
        <nav class="nav">
            <a href="index.php" data-i18n="inicio">Inicio</a>
            <a href="mis_billetes.php" data-i18n="mis_billetes">Mis billetes</a>
            <div class="dropdown">
                <a href="#"><i class="fa-solid fa-earth-europe"></i> <span data-i18n="idiomas">Idiomas</span> <i class="fa-solid fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="#" data-lang="es" data-i18n="es">Español</a>
                    <a href="#" data-lang="en" data-i18n="en">Inglés</a>
                    <a href="#" data-lang="fr" data-i18n="fr">Francés</a>
                    <a href="#" data-lang="de" data-i18n="de">Alemán</a>
                </div>
            </div>
            <a href="ofertas.php" data-i18n="ofertas">Ofertas</a>
            <a href="ayuda.php" data-i18n="ayuda">Ayuda</a>
        </nav>
        <div class="user-actions" id="userActions">
            <div class="account-dropdown open-on-hover">
                <button type="button" class="account-toggle">
                    <span class="account-avatar"><?php echo strtoupper(substr($nombreSesion, 0, 1)); ?></span>
                    <span class="account-name"><?php echo htmlspecialchars($nombreSesion, ENT_QUOTES, 'UTF-8'); ?></span>
                    <i class="fa-solid fa-caret-down"></i>
                </button>
                <div class="account-menu">
                    <a href="perfil_pasajero.php"><i class="fa-solid fa-user"></i> <span data-i18n="mi_perfil">Mi perfil</span></a>
                    <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> <span data-i18n="cerrar_sesion">Cerrar sesión</span></a>
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
                        <span data-i18n="perfil_datos_personales">Datos personales</span>
                    </button>
                    <button class="sidebar-link" data-target="panel-bonos" type="button">
                        <i class="fa-solid fa-ticket"></i>
                        <span data-i18n="perfil_mis_bonos">Mis bonos</span>
                    </button>
                    <button class="sidebar-link" data-target="panel-viajes" type="button">
                        <i class="fa-solid fa-train-subway"></i>
                        <span data-i18n="perfil_mis_viajes_billetes">Mis viajes y billetes</span>
                    </button>
                    <button class="sidebar-link" data-target="panel-config" type="button">
                        <i class="fa-solid fa-gear"></i>
                        <span data-i18n="perfil_configuracion">Configuracion</span>
                    </button>
                </nav>
            </aside>

            <section class="perfil-content">
                <div class="content-panel active" id="panel-datos">
                    <div class="panel-header">
                        <h2 data-i18n="perfil_datos_personales">Datos personales</h2>
                        <p data-i18n="perfil_consulta_info">Consulta tu informacion de cuenta y facturacion.</p>
                    </div>

                    <div class="info-grid">
                        <article class="info-card">
                            <label data-i18n="perfil_nombre">Nombre</label>
                            <strong><?php echo htmlspecialchars($perfil['nombre'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label data-i18n="perfil_apellidos">Apellidos</label>
                            <strong><?php echo htmlspecialchars($perfil['apellido'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label data-i18n="perfil_documento">Documento</label>
                            <strong><?php echo htmlspecialchars($perfil['numero_documento'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label data-i18n="perfil_fecha_nacimiento">Fecha de nacimiento</label>
                            <strong><?php echo htmlspecialchars($fechaNacimientoVista, ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label data-i18n="perfil_email">Email</label>
                            <strong><?php echo htmlspecialchars($perfil['email'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label data-i18n="perfil_telefono">Telefono</label>
                            <strong><?php echo htmlspecialchars($perfil['telefono'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card info-card-wide">
                            <label data-i18n="perfil_direccion">Direccion</label>
                            <strong><?php echo htmlspecialchars($perfil['calle'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label data-i18n="perfil_ciudad">Ciudad</label>
                            <strong><?php echo htmlspecialchars($perfil['ciudad'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label data-i18n="perfil_codigo_postal">Codigo postal</label>
                            <strong><?php echo htmlspecialchars($perfil['codigo_postal'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="info-card">
                            <label data-i18n="perfil_pais">Pais</label>
                            <strong><?php echo htmlspecialchars($perfil['pais'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                    </div>
                </div>

                <div class="content-panel" id="panel-bonos" hidden>
                    <div class="panel-header">
                        <h2 data-i18n="perfil_mis_bonos">Mis bonos</h2>
                        <p data-i18n="perfil_bonos_desc">Revisa tus abonos activos, caducados y viajes restantes.</p>
                    </div>
                    <div id="abonos-list" class="cards-grid"></div>
                </div>

                <div class="content-panel" id="panel-viajes" hidden>
                    <div class="panel-header">
                        <h2 data-i18n="perfil_mis_viajes_billetes">Mis viajes y billetes</h2>
                        <p data-i18n="perfil_viajes_desc">Billetes comprados y avisos que afectan a tus trayectos.</p>
                    </div>

                    <h3 class="section-subtitle" data-i18n="perfil_viajes_proximos">Viajes proximos</h3>
                    <div id="viajes-proximos-list" class="cards-grid"></div>

                    <h3 class="section-subtitle" data-i18n="perfil_viajes_finalizados">Viajes finalizados</h3>
                    <div id="viajes-finalizados-list" class="cards-grid"></div>

                    <h3 class="section-subtitle" data-i18n="perfil_incidencias_viaje">Incidencias de viaje</h3>
                    <div id="incidencias-viaje" class="cards-grid"></div>
                </div>

                <div class="content-panel" id="panel-config" hidden>
                    <div class="panel-header">
                        <h2 data-i18n="perfil_configuracion">Configuracion</h2>
                        <p data-i18n="perfil_config_desc">Gestiona notificaciones, datos de perfil y seguridad.</p>
                    </div>

                    <div class="profile-panel accordion-wrapper">
                        <div class="accordion-item">
                            <div class="accordion-header">
                                <div class="header-title">
                                    <i class="fa-regular fa-bell"></i> <span data-i18n="perfil_preferencias_avisos">Preferencias de avisos</span>
                                </div>
                                <i class="fa-solid fa-chevron-down arrow-icon"></i>
                            </div>
                            <div class="accordion-content">
                                <div class="notifications-list">
                                    <p id="config-status-notificaciones" class="config-status" aria-live="polite"></p>
                                    <div class="notification-option">
                                        <div class="notif-text">
                                            <strong data-i18n="perfil_avisos_viaje">Avisos de viaje</strong>
                                            <p data-i18n="perfil_avisos_viaje_desc">Retrasos, cambios de via e incidencias.</p>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" id="notif_viaje" <?php echo $notificacionesViaje ? 'checked' : ''; ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                    <hr class="trip-separator">
                                    <div class="notification-option">
                                        <div class="notif-text">
                                            <strong data-i18n="perfil_ofertas_comerciales">Ofertas comerciales</strong>
                                            <p data-i18n="perfil_ofertas_desc">Promociones exclusivas y descuentos.</p>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" id="notif_ofertas" <?php echo $notificacionesOfertas ? 'checked' : ''; ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                    <div class="form-full">
                                        <button type="button" class="btn-primary" id="btn-guardar-notificaciones" data-i18n="perfil_guardar_preferencias">Guardar preferencias</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <div class="accordion-header">
                                <div class="header-title">
                                    <i class="fa-solid fa-user-pen"></i> <span data-i18n="perfil_modificar_datos">Modificar datos personales</span>
                                </div>
                                <i class="fa-solid fa-chevron-down arrow-icon"></i>
                            </div>
                            <div class="accordion-content">
                                <form class="form-grid" id="form-datos-perfil">
                                    <p id="config-status-datos" class="config-status full-width" aria-live="polite"></p>
                                    <div class="form-group">
                                        <label data-i18n="perfil_nombre">Nombre</label>
                                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($perfil['nombre'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input" required>
                                    </div>
                                    <div class="form-group">
                                        <label data-i18n="perfil_apellidos">Apellidos</label>
                                        <input type="text" name="apellido" value="<?php echo htmlspecialchars($perfil['apellido'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input" required>
                                    </div>
                                    <div class="form-group">
                                        <label data-i18n="perfil_documento_dni">Documento (DNI/NIE)</label>
                                        <input type="text" value="<?php echo htmlspecialchars($perfil['numero_documento'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label data-i18n="perfil_fecha_nacimiento">Fecha de nacimiento</label>
                                        <input type="date" name="fecha_nacimiento" value="<?php echo htmlspecialchars($fechaNacimientoInput, ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label data-i18n="perfil_email_contacto">Email de contacto</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($perfil['email'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input" required>
                                    </div>
                                    <div class="form-group">
                                        <label data-i18n="perfil_telefono_movil">Telefono movil</label>
                                        <input type="tel" name="telefono" value="<?php echo htmlspecialchars($perfil['telefono'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                                    </div>
                                    <div class="form-group full-width">
                                        <label data-i18n="perfil_direccion">Direccion</label>
                                        <input type="text" name="calle" value="<?php echo htmlspecialchars($perfil['calle'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label data-i18n="perfil_ciudad">Ciudad</label>
                                        <input type="text" name="ciudad" value="<?php echo htmlspecialchars($perfil['ciudad'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label data-i18n="perfil_codigo_postal">Codigo postal</label>
                                        <input type="text" name="codigo_postal" value="<?php echo htmlspecialchars($perfil['codigo_postal'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label data-i18n="perfil_pais">Pais</label>
                                        <input type="text" name="pais" value="<?php echo htmlspecialchars($perfil['pais'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                                    </div>
                                    <div class="form-full">
                                        <button type="submit" class="btn-primary" id="btn-guardar-datos" data-i18n="perfil_guardar_cambios">Guardar cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <div class="accordion-header">
                                <div class="header-title">
                                    <i class="fa-solid fa-lock"></i> <span data-i18n="perfil_seguridad">Seguridad y contrasena</span>
                                </div>
                                <i class="fa-solid fa-chevron-down arrow-icon"></i>
                            </div>
                            <div class="accordion-content">
                                <form class="form-grid" id="form-cambiar-contrasena">
                                    <p id="config-status-password" class="config-status full-width" aria-live="polite"></p>
                                    <div class="form-group full-width">
                                        <label data-i18n="perfil_contrasena_actual">Contrasena actual</label>
                                        <input type="password" name="password_actual" placeholder="********" class="form-input" required>
                                    </div>
                                    <div class="form-group">
                                        <label data-i18n="perfil_nueva_contrasena">Nueva contrasena</label>
                                        <input type="password" name="password_nueva" class="form-input" required minlength="8">
                                    </div>
                                    <div class="form-group">
                                        <label data-i18n="perfil_repetir_contrasena">Repetir nueva contrasena</label>
                                        <input type="password" name="password_repetida" class="form-input" required minlength="8">
                                    </div>
                                    <div class="form-full">
                                        <button type="submit" class="btn-primary" id="btn-cambiar-contrasena" data-i18n="perfil_cambiar_contrasena">Cambiar contrasena</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <div class="accordion-header">
                                <div class="header-title danger-title">
                                    <i class="fa-solid fa-triangle-exclamation"></i> <span data-i18n="perfil_eliminar_cuenta">Eliminar la cuenta</span>
                                </div>
                                <i class="fa-solid fa-chevron-down arrow-icon"></i>
                            </div>
                            <div class="accordion-content">
                                <div class="danger-zone">
                                    <p data-i18n="perfil_eliminar_desc">Si eliminas tu cuenta, perderas acceso a tus billetes y abonos activos.</p>
                                    <button type="button" class="btn-danger" id="btn-eliminar-cuenta" data-i18n="perfil_eliminar_mi_cuenta">Eliminar mi cuenta</button>
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
            <h3 id="delete-account-title" data-i18n="perfil_modal_eliminar_titulo">Eliminar cuenta</h3>
            <p data-i18n="perfil_modal_eliminar_desc">Vas a eliminar tu cuenta de forma permanente. Se borraran tus datos, abonos y billetes.</p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="btn-cancelar-eliminar" data-i18n="perfil_cancelar">Cancelar</button>
                <button type="button" class="btn-danger" id="btn-confirmar-eliminar" data-i18n="perfil_eliminar">Eliminar</button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>TrainWeb</h3>
                <p data-i18n="footer_descripcion">Plataforma digital para la búsqueda y compra de billetes de tren en todo el territorio nacional.</p>
            </div>
            <div class="footer-column">
                <h4 data-i18n="footer_services">Servicios</h4>
                <a href="mis_billetes.php"><i class="fa-solid fa-ticket"></i> <span data-i18n="footer_billetes">Billetes</span></a>
                <a href="horarios.php"><i class="fa-solid fa-clock"></i> <span data-i18n="footer_horarios">Horarios</span></a>
                <a href="ofertas.php"><i class="fa-solid fa-tags"></i> <span data-i18n="footer_ofertas">Ofertas</span></a>
                <a href="ayuda.php"><i class="fa-solid fa-headset"></i> <span data-i18n="footer_atencion">Atención al cliente</span></a>
            </div>
            <div class="footer-column">
                <h4 data-i18n="footer_legal">Información legal</h4>
                <a href="#"><i class="fa-solid fa-scale-balanced"></i> <span data-i18n="footer_aviso">Aviso legal</span></a>
                <a href="#"><i class="fa-solid fa-user-shield"></i> <span data-i18n="footer_privacidad">Privacidad</span></a>
                <a href="#"><i class="fa-solid fa-cookie-bite"></i> <span data-i18n="footer_cookies">Cookies</span></a>
                <a href="#"><i class="fa-solid fa-file-contract"></i> <span data-i18n="footer_terminos">Términos y condiciones</span></a>
            </div>
            <div class="footer-column">
                <h4 data-i18n="footer_social">Redes sociales</h4>
                <a href="julio_apruebanos.php"><i class="fa-brands fa-facebook-f"></i> Facebook</a>
                <a href="julio_apruebanos.php"><i class="fa-brands fa-x-twitter"></i> Twitter</a>
                <a href="julio_apruebanos.php"><i class="fa-brands fa-instagram"></i> Instagram</a>
                <a href="julio_apruebanos.php"><i class="fa-brands fa-linkedin-in"></i> LinkedIn</a>
            </div>
        </div>
        <div class="footer-bottom" data-i18n="footer_copyright">© 2026 TrainWeb · Todos los derechos reservados</div>
    </footer>

    <script src="scripts/i18n.js?v=<?php echo @filemtime(__DIR__ . '/scripts/i18n.js'); ?>"></script>
    <script src="scripts/session_menu.js?v=<?php echo urlencode($assetVersion); ?>"></script>
    <script src="scripts/perfil_pasajero_ui.js?v=<?php echo urlencode($assetVersion); ?>"></script>
    <script src="scripts/perfil_configuracion.js?v=<?php echo urlencode($assetVersion); ?>"></script>
    <script src="scripts/carga_abonos_perfil.js?v=<?php echo urlencode($assetVersion); ?>"></script>
    <script src="scripts/carga_billetes_perfil.js?v=<?php echo urlencode($assetVersion); ?>"></script>
    <script src="scripts/carga_incidencias_pasajero.js?v=<?php echo urlencode($assetVersion); ?>"></script>
</body>
</html>
