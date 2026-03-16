<?php
session_start();
require_once __DIR__ . '/php/auth_helpers.php';
if (isset($_SESSION['usuario']) && ($_SESSION['usuario']['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($_SESSION['usuario']));
    exit;
}
require_once __DIR__ . '/php/Conexion.php';

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
    'pais' => ''
];

$idUsuarioSesion = $_SESSION['usuario']['id_usuario'] ?? 0;

try {
    $conexion = new Conexion();
    $pdo = $conexion->conectar();

    if ($pdo && $idUsuarioSesion > 0) {
        $sql = "SELECT u.nombre, u.apellido, u.email, u.telefono,
                       p.numero_documento, p.fecha_nacimiento, p.calle, p.ciudad, p.codigo_postal, p.pais
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
        }
    }
} catch (Throwable $e) {
    // Si falla la carga de perfil, se muestran los datos de sesión básicos.
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
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TrainWeb - Página Usuario</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/session_menu.css">
    <link rel="stylesheet" href="css/perfil_pasajero.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <div class="logo"><i class="fa-solid fa-train"></i> TrainWeb</div>
        <nav class="nav">
            <a href="index.php">Inicio</a>
            <a href="#">Billetes</a>
            <div class="dropdown">
                <a href="#">Idiomas <i class="fa-solid fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="#">Español</a>
                    <a href="#">Inglés</a>
                    <a href="#">Francés</a>
                    <a href="#">Alemán</a>
                </div>
            </div>
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
                    <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
                </div>
            </div>
        </div>
    </header>

    <!-- CONTENIDO PERFIL -->
    <main class="perfil-main">

        <!--BIENVENIDA USUARIO-->
        <section class="welcome-section">
            <div class="welcome-text">
                <h1>Hola <?php echo htmlspecialchars($nombreSesion, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="welcome-subtitle">Aquí tienes el resumen de tu actividad y próximos viajes.</p>
            </div>
                
        </section>

        <!--VIAJES COMPRADOS POR EL USUARIO-->
         <div class="profile-container" style="margin-top: 20px;">
            <h2 class="section-title">Avisos de tus viajes</h2>
            <div id="incidencias-viaje" class="profile-panel"></div>
        </div>

        <!--ABONOS COMPRADOS POR EL USUARIO-->
        <div class="profile-container" style="margin-top: 40px;">
            <h2 class="section-title">Mis Abonos</h2>
            <div id="abonos-list"></div>
        </div>
            

        <!--DATOS DEL USUARIO-->
        <div class="profile-container" style="margin-top: 40px;"> 
            <h2 class="section-title">Mis Datos</h2>
            <div class="profile-panel accordion-wrapper">
                <!-- Datos del usuario-->
                <div class="accordion-item">
                    <div class="accordion-header">
                        <div class="header-title">
                            <i class="fa-regular fa-id-card"></i> Información Personal
                        </div>
                        <i class="fa-solid fa-chevron-down arrow-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <div class="form-grid">
                            <div class="form-group read-only-group">
                                <label>Nombre</label>
                                <div class="static-value"><?php echo htmlspecialchars($perfil['nombre'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="form-group read-only-group">
                                <label>Apellidos</label>
                                <div class="static-value"><?php echo htmlspecialchars($perfil['apellido'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="form-group read-only-group">
                                <label>Documento (DNI/NIE)</label>
                                <div class="static-value"><?php echo htmlspecialchars($perfil['numero_documento'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="form-group read-only-group">
                                <label>Fecha de Nacimiento</label>
                                <div class="static-value"><?php echo htmlspecialchars($fechaNacimientoVista, ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="form-group read-only-group">
                                <label>Email</label>
                                <div class="static-value"><?php echo htmlspecialchars($perfil['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="form-group read-only-group">
                                <label>Teléfono Móvil</label>
                                <div class="static-value"><?php echo htmlspecialchars($perfil['telefono'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Dirección de facturación -->
                <div class="accordion-item">
                    <div class="accordion-header">
                        <div class="header-title">
                            <i class="fa-solid fa-file-invoice-dollar"></i> Dirección de Facturación
                        </div>
                        <i class="fa-solid fa-chevron-down arrow-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <div class="form-grid">
                            <div class="form-group full-width read-only-group">
                                <label>Dirección Postal</label>
                                <div class="static-value"><?php echo htmlspecialchars($perfil['calle'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="form-group read-only-group">
                                <label>Código Postal</label>
                                <div class="static-value"><?php echo htmlspecialchars($perfil['codigo_postal'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="form-group read-only-group">
                                <label>Localidad</label>
                                <div class="static-value"><?php echo htmlspecialchars($perfil['ciudad'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!--CONFIGURACIÓN DE LA CUENTA-->
        <div class="profile-container" style="margin-top: 40px; margin-bottom: 60px;"> 
            <h2 class="section-title">Configuración de Cuenta</h2>
            <div class="profile-panel accordion-wrapper">
                <!--notificaciones-->
                <div class="accordion-item">
                    <div class="accordion-header">
                        <div class="header-title">
                            <i class="fa-regular fa-bell"></i> Preferencias de Avisos
                        </div>
                        <i class="fa-solid fa-chevron-down arrow-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <div class="notifications-list">
                            <div class="notification-option">
                                <div class="notif-text">
                                    <strong>Avisos de viaje</strong>
                                    <p>Retrasos, cambios de vía e incidencias.</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" checked>
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
                                    <input type="checkbox">
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!--modificar datos personales-->
                <div class="accordion-item">
                    <div class="accordion-header">
                        <div class="header-title">
                            <i class="fa-solid fa-user-pen"></i> Modificar Datos Personales
                        </div>
                        <i class="fa-solid fa-chevron-down arrow-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <form class="form-grid">
                            <div class="form-group">
                                <label>Nombre</label>
                                <input type="text" value="<?php echo htmlspecialchars($perfil['nombre'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                            </div>
                            <div class="form-group">
                                <label>Apellidos</label>
                                <input type="text" value="<?php echo htmlspecialchars($perfil['apellido'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                            </div>
                            <div class="form-group">
                                <label>Documento (DNI/NIE)</label>
                                <input type="text" value="<?php echo htmlspecialchars($perfil['numero_documento'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input" disabled style="background-color: #f0f0f0; cursor: not-allowed;">
                            </div>
                            <div class="form-group">
                                <label>Fecha de Nacimiento</label>
                                <input type="date" value="<?php echo htmlspecialchars($fechaNacimientoInput, ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                            </div>
                            <div class="form-group">
                                <label>Email de Contacto</label>
                                <input type="email" value="<?php echo htmlspecialchars($perfil['email'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                            </div>
                            <div class="form-group">
                                <label>Teléfono Móvil</label>
                                <input type="tel" value="<?php echo htmlspecialchars($perfil['telefono'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input">
                            </div>
                            <div class="form-full">
                                <button type="button" class="btn-primary">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!--cambio de contraseña-->
                <div class="accordion-item">
                    <div class="accordion-header">
                        <div class="header-title">
                            <i class="fa-solid fa-lock"></i> Seguridad y Contraseña
                        </div>
                        <i class="fa-solid fa-chevron-down arrow-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <form class="form-grid">
                            <div class="form-group full-width">
                                <label>Contraseña Actual</label>
                                <input type="password" placeholder="••••••••" class="form-input">
                            </div>
                            <div class="form-group">
                                <label>Nueva Contraseña</label>
                                <input type="password" class="form-input">
                            </div>
                            <div class="form-group">
                                <label>Repetir Nueva Contraseña</label>
                                <input type="password" class="form-input">
                            </div>
                            <div class="form-full">
                                <button type="button" class="btn-primary">Cambiar Contraseña</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!--eliminar cuenta-->
                <div class="accordion-item">
                    <div class="accordion-header">
                        <div class="header-title" style="color: #d9534f;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Eliminar la cuenta
                        </div>
                        <i class="fa-solid fa-chevron-down arrow-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <div style="padding: 15px 0;">
                            <p style="margin-bottom: 15px; color: #666;">Si eliminas tu cuenta, perderás acceso a tus billetes y abonos activos.</p>
                            <button type="button" class="btn-danger">Eliminar mi cuenta</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!--SCRIPT ACORDEON -->
        <script>
            const accordions = document.querySelectorAll('.accordion-header');

            accordions.forEach(header => {
                header.addEventListener('click', () => {
                    const item = header.parentElement;
                    // Alternar la clase 'active' para abrir/cerrar
                    item.classList.toggle('active');
                });
            });
        </script>

    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>TrainWeb</h3>
                <p>Plataforma digital para la búsqueda y compra de billetes de tren en todo el territorio nacional.</p>
            </div>
            <div class="footer-column">
                <h4>Servicios</h4>
                <a href="#"><i class="fa-solid fa-ticket"></i> Billetes</a>
                <a href="#"><i class="fa-solid fa-clock"></i> Horarios</a>
                <a href="ofertas.html"><i class="fa-solid fa-tags"></i> Ofertas</a>
                <a href="#"><i class="fa-solid fa-headset"></i> Atención al cliente</a>
            </div>
            <div class="footer-column">
                <h4>Información legal</h4>
                <a href="#"><i class="fa-solid fa-scale-balanced"></i> Aviso legal</a>
                <a href="#"><i class="fa-solid fa-user-shield"></i> Privacidad</a>
                <a href="#"><i class="fa-solid fa-cookie-bite"></i> Cookies</a>
                <a href="#"><i class="fa-solid fa-file-contract"></i> Términos y condiciones</a>
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
    <script src="scripts/session_menu.js"></script>
    <script src="scripts/carga_abonos_perfil.js"></script>
    <script src="scripts/carga_incidencias_pasajero.js"></script>

</body>
</html>