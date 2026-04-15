<?php
session_start();

$usuarioSesion = $_SESSION['usuario'] ?? null;
$nombreSesion = $usuarioSesion['nombre'] ?? '';
require_once __DIR__ . '/php/auth_helpers.php';
if (isset($_SESSION['usuario']) && ($_SESSION['usuario']['tipo_usuario'] ?? '') === 'empleado') {
    header('Location: ' . trainwebRutaPorRol($_SESSION['usuario']));
    exit;
}
require_once 'php/Conexion.php';

$conexion = new Conexion();
$pdo = $conexion->conectar();


// 1. Obtener trayectos filtrados por parámetros GET
$origen = isset($_GET['origen']) ? trim($_GET['origen']) : '';
$destino = isset($_GET['destino']) ? trim($_GET['destino']) : '';
$fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : '';
$pasajeros = isset($_GET['pasajeros']) ? intval($_GET['pasajeros']) : 1;

$where = [];
$params = [];
if ($origen !== '') {
    $where[] = 'r.origen ILIKE :origen';
    $params[':origen'] = $origen;
}
if ($destino !== '') {
    $where[] = 'r.destino ILIKE :destino';
    $params[':destino'] = $destino;
}
if ($fecha !== '') {
    $where[] = 'v.fecha = :fecha';
    $params[':fecha'] = $fecha;
}

$sql = "SELECT 
            v.id_viaje, v.fecha, v.hora_salida, v.hora_llegada, v.precio as precio_base, v.estado as estado_viaje,
            t.modelo as tipo_tren, t.id_tren as codigo_tren,
            r.origen, 
            r.destino
        FROM VIAJE v
        JOIN TREN t ON v.id_tren = t.id_tren
        JOIN RUTA r ON v.id_ruta = r.id_ruta";
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY v.hora_salida ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trayectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Obtener las promociones activas

// --- INICIO CÓDIGO CARRUSEL DE FECHAS ---
// 1. Determinar la fecha base (la que buscó el usuario, o la actual si viene vacía)
$fecha_base = !empty($fecha) ? $fecha : date('Y-m-d');
$fechas_carrusel = [];

// 2. Generar un array con los 5 días (-2, -1, 0, +1, +2)
for ($i = -2; $i <= 2; $i++) {
    $fechas_carrusel[] = date('Y-m-d', strtotime("$fecha_base $i days"));
}

// 3. Consultar el precio mínimo para cada una de esas fechas
$precios_por_fecha = [];
$stmt_min_precio = $pdo->prepare("
    SELECT v.fecha, MIN(v.precio) as precio_min 
    FROM VIAJE v
    JOIN RUTA r ON v.id_ruta = r.id_ruta
    WHERE r.origen ILIKE :origen AND r.destino ILIKE :destino AND v.fecha = :fecha
    GROUP BY v.fecha
");

foreach ($fechas_carrusel as $f) {
    // Reutilizamos $origen y $destino de tu código principal
    $stmt_min_precio->execute([
        ':origen' => '%' . $origen . '%', 
        ':destino' => '%' . $destino . '%', 
        ':fecha' => $f
    ]);
    $res = $stmt_min_precio->fetch(PDO::FETCH_ASSOC);
    $precios_por_fecha[$f] = $res ? $res['precio_min'] : null;
}
// --- FIN CÓDIGO CARRUSEL DE FECHAS ---

$sql_promos = "SELECT codigo, descuento_porcentaje FROM PROMOCION WHERE fecha_fin >= CURRENT_DATE"; 
$stmt_promos = $pdo->query($sql_promos);
$promociones = $stmt_promos->fetchAll(PDO::FETCH_ASSOC);


// 3. Obtener los abonos ACTIVOS del pasajero actual
$abonos_usuario = [];
if (isset($_SESSION['usuario']['id_usuario'])) {
    $id_usuario = $_SESSION['usuario']['id_usuario'];
    
    // Obtener ID del pasajero
    $stmtPasajero = $pdo->prepare("SELECT id_pasajero FROM PASAJERO WHERE id_usuario = :id_usuario");
    $stmtPasajero->execute([':id_usuario' => $id_usuario]);
    $pasajero = $stmtPasajero->fetch(PDO::FETCH_ASSOC);

    if ($pasajero) {
        // Hemos quitado la columna 'estado' de la condición
        $sql_abonos = "SELECT id_abono, tipo, viajes_restantes 
                       FROM ABONO 
                       WHERE id_pasajero = :id_pasajero 
                         AND fecha_fin >= CURRENT_DATE 
                         AND (viajes_restantes > 0 OR viajes_restantes IS NULL)";
                         
        $stmt_abonos = $pdo->prepare($sql_abonos);
        $stmt_abonos->execute([':id_pasajero' => $pasajero['id_pasajero']]);
        $abonos_usuario = $stmt_abonos->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Compra de Billetes</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="css/index.css">
    
    <link rel="stylesheet" href="css/session_menu.css">
    
    <link rel="stylesheet" href="css/compra.css">
</head>
<body>

    <header class="header">
        <div class="logo">
            <i class="fa-solid fa-train"></i> TrainWeb 
            <span style="font-size: 0.8rem; opacity: 0.8; font-weight: normal; margin-left: 10px;">| Área de Cliente</span>
        </div>
        
        <nav class="nav">
            <a href="index.php" data-i18n="inicio">Inicio</a>
            <a href="ayuda.php" data-i18n="ayuda">Ayuda</a>
        </nav>

        <div class="user-actions" id="userActions">
            <?php if ($usuarioSesion): ?>
                <div class="account-dropdown open-on-hover">
                    <button type="button" class="account-toggle">
                        <span class="account-avatar"><?php echo strtoupper(substr($nombreSesion, 0, 1)); ?></span>
                        <span class="account-name"><?php echo htmlspecialchars($nombreSesion, ENT_QUOTES, 'UTF-8'); ?></span>
                        <i class="fa-solid fa-caret-down"></i>
                    </button>
                    <div class="account-menu">
                        <a href="perfil_pasajero.php"><i class="fa-solid fa-user"></i> <span data-i18n="mi_perfil">Mi perfil</span></a>
                        <a href="mis_billetes.php"><i class="fa-solid fa-ticket"></i> <span data-i18n="mis_billetes">Mis billetes</span></a>
                        
                        <?php if (($usuarioSesion['tipo_usuario'] ?? '') === 'empleado'): ?>
                            <a href="vendedor.php"><i class="fa-solid fa-briefcase"></i> Panel Empleado</a>
                        <?php endif; ?>
                        
                        <a href="php/logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span data-i18n="cerrar_sesion">Cerrar sesión</span></a>
                    </div>
                </div>
            <?php else: ?>
                <a href="inicio_sesion.html" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> <span data-i18n="iniciar_sesion">Iniciar sesión</span></a>
            <?php endif; ?>
        </div>
    </header>

    <main class="booking-container">
        
        <div class="progress-bar-container">
            <div class="step active" id="step1" onclick="irAPaso(1)" style="cursor: pointer;"><span class="step-num">1</span> Trenes disponibles</div>
            <div class="step" id="step2" onclick="irAPaso(2)" style="cursor: pointer;"><span class="step-num">2</span> Selección de asientos</div>
            <div class="step" id="step3" onclick="irAPaso(3)" style="cursor: pointer;"><span class="step-num">3</span> Resumen y Descuentos</div>
            <div class="step" id="step4" onclick="irAPaso(4)" style="cursor: pointer;"><span class="step-num">4</span> Pago seguro</div>
        </div>


        <div class="search-summary">
            <div class="summary-text">
                <h2>
                    <?php echo $origen ? htmlspecialchars($origen) : 'Origen'; ?> 
                    <i class="fa-solid fa-arrow-right"></i> 
                    <?php echo $destino ? htmlspecialchars($destino) : 'Destino'; ?>
                </h2>
                <p>
                    <?php 
                        if ($fecha) {
                            $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
                            echo $fechaObj ? $fechaObj->format('l, d \d\e F') : htmlspecialchars($fecha);
                        } else {
                            echo 'Fecha no seleccionada';
                        }
                    ?>
                    | <?php echo $pasajeros; ?> <?php echo ($pasajeros == 1) ? 'Pasajero' : 'Pasajeros'; ?>
                </p>
            </div>
            <button class="btn-modify" onclick="window.location.href='index.php'">
                <i class="fa-solid fa-pen-to-square"></i> Modificar datos
            </button>
        </div>

        <!-- Carrusel de fechas debajo del resumen de búsqueda -->
        <div class="date-carousel" style="display: flex; gap: 10px; margin-bottom: 25px; overflow-x: auto; padding-bottom: 10px; justify-content: space-between;">
            <?php foreach ($fechas_carrusel as $f): 
                $es_activa = ($f === $fecha_base);
                $precio_dia = $precios_por_fecha[$f];
                // Nombres de los días en español para darle un toque pro
                $dias_es = ['Sun'=>'Dom', 'Mon'=>'Lun', 'Tue'=>'Mar', 'Wed'=>'Mié', 'Thu'=>'Jue', 'Fri'=>'Vie', 'Sat'=>'Sáb'];
                $dia_semana = $dias_es[date('D', strtotime($f))];
                $dia_mes = date('d/m', strtotime($f));
                // Construimos la URL para recargar la página con esa nueva fecha manteniendo el resto de datos
                $url_dia = "?origen=" . urlencode($origen) . "&destino=" . urlencode($destino) . "&pasajeros=" . $pasajeros . "&fecha=" . $f;
            ?>
                <a href="<?= $url_dia ?>" style="flex: 1; min-width: 90px; text-align: center; padding: 12px 5px; border-radius: 8px; text-decoration: none; border: 2px solid <?= $es_activa ? '#0a2a66' : '#e0e0e0' ?>; background-color: <?= $es_activa ? '#f4f6f8' : 'white' ?>; color: #333; transition: all 0.2s;">
                    <div style="font-size: 0.85rem; color: #666; margin-bottom: 5px;"><?= $dia_semana ?> <?= $dia_mes ?></div>
                    <?php if ($precio_dia): ?>
                        <div style="display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: center; gap: 4px; min-width: 0;">
                            <span style="font-size: 0.8rem; color: #888; line-height: 1; white-space: nowrap;">Desde</span>
                            <span style="font-size: 1.1rem; font-weight: bold; color: <?= $es_activa ? '#0a2a66' : '#333' ?>; line-height: 1; white-space: nowrap;"><?= number_format($precio_dia, 2, ',', '') . ' €' ?></span>
                        </div>
                    <?php else: ?>
                        <div style="font-size: 1.1rem; font-weight: bold; color: #bbb;">---</div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <section id="sectionTrains" class="booking-section">
            <div class="train-list">
            <?php if (empty($trayectos)): ?>
                <div class="no-trains-message" style="text-align: center; padding: 50px; background: #fff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <i class="fa-solid fa-train-track" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                    <h3 style="color: #0a2a66;">Lo sentimos, no hay trenes disponibles</h3>
                    <p style="color: #666;">No hemos encontrado ningún viaje programado para la ruta y fecha seleccionadas.</p>
                    <a href="index.php" class="btn-primary" style="display: inline-block; margin-top: 15px; text-decoration: none;">Volver al buscador</a>
                </div>
            <?php else: ?>
                <?php foreach ($trayectos as $trayecto): 
                    $hora_salida = date('H:i', strtotime($trayecto['hora_salida']));
                    $hora_llegada = date('H:i', strtotime($trayecto['hora_llegada']));
                    $dteStart = new DateTime($trayecto['hora_salida']);
                    $dteEnd   = new DateTime($trayecto['hora_llegada']);
                    $duracion = $dteStart->diff($dteEnd)->format('%hh %Imin');
                    $precio = number_format($trayecto['precio_base'], 2, ',', '');

                    $icono_amenity = 'fa-train'; 
                    if (strtolower($trayecto['tipo_tren']) == 'ave') $icono_amenity = 'fa-wifi';
                    if (strtolower($trayecto['tipo_tren']) == 'avlo') $icono_amenity = 'fa-plug';
                    if (strtolower($trayecto['tipo_tren']) == 'alvia') $icono_amenity = 'fa-person-walking-luggage';

                    $isFull = ($trayecto['estado_viaje'] === 'completado');
                    $cardClass = $isFull ? "ticket-card full-train" : "ticket-card";
                ?>
                <div class="<?= $cardClass ?>">
                    <div class="col-train-info">
                        <span class="train-type type-<?= strtolower($trayecto['tipo_tren']) ?>"><?= strtoupper($trayecto['tipo_tren']) ?></span> 
                        <span class="train-id"><?= htmlspecialchars(str_pad($trayecto['codigo_tren'], 4, '0', STR_PAD_LEFT)) ?></span>
                        <div class="amenities"><i class="fa-solid <?= $icono_amenity ?>"></i></div>
                    </div>
                    <div class="col-schedule">
                        <div class="time-group"><span class="hour"><?= $hora_salida ?></span><span class="city">MAD</span></div>
                        <div class="duration-line"><span class="duration-text"><?= $duracion ?></span><div class="line"><i class="fa-solid fa-train"></i></div></div>
                        <div class="time-group"><span class="hour"><?= $hora_llegada ?></span><span class="city">BCN</span></div>
                    </div>
                    <div class="col-price">
                        <?php if ($isFull): ?>
                            <div class="price-full">Completo</div>
                            <button class="btn-select" disabled>Agotado</button>
                        <?php else: ?>
                            <div class="price"><?= $precio ?> €</div>
                            <button class="btn-select" onclick="seleccionarTren(<?= $trayecto['id_viaje'] ?>, '<?= $trayecto['tipo_tren'] ?>', <?= $trayecto['precio_base'] ?>)">Elegir</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </section>

        <section id="sectionSeats" class="booking-section hidden">
            <div class="seat-header">
                <h3>Selecciona tu plaza en <span id="lblTrenSeleccionado">--</span></h3>
                <div class="wagon-navigator">
                    <button class="nav-arrow" id="btnPrev" onclick="cambiarVagon(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                    <span class="wagon-title">Vagón <span id="currentWagonNum">1</span></span>
                    <button class="nav-arrow" id="btnNext" onclick="cambiarVagon(1)"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>

            <div class="train-horizontal-container">
                <?php
                $numero_asiento_global = 1;

                for ($w = 1; $w <= 3; $w++) {
                    $isPremium = ($w == 1);
                    $wagonClass = $isPremium ? "wagon-premium" : "wagon-standard";
                    $wagonTitle = $isPremium ? "Primera Clase" : "Segunda Clase";
                    $displayClass = ($w == 1) ? "" : "hidden";
                    $asientosPorBloque = $isPremium ? 5 : 6;
                    $claseMesa = $isPremium ? "long-table-wide" : "long-table";
                    $clasePasillo = $isPremium ? "aisle-horizontal-wide" : "aisle-horizontal";

                    echo "<div id='wagon$w' class='wagon-body $wagonClass $displayClass'>";
                    echo "<div class='info-message'>$wagonTitle</div>";
                    echo "<div class='wagon-layout'>";

                    // PARTE SUPERIOR (Filas A y B)
                    echo "<div class='wagon-super-row'>";
                        echo "<div class='seat-block'>";
                            foreach (['A', 'B'] as $letra) {
                                echo "<div class='seat-row-tight " . ($isPremium ? "premium-row" : "") . "'>";
                                for ($i = 0; $i < $asientosPorBloque; $i++) {
                                    $id = sprintf("%03d", $numero_asiento_global++);
                                    $p = $isPremium ? "seat-premium" : "";
                                    echo "<div class='seat $p seat-left' data-seat='$id'>$id</div>";
                                }
                                echo "</div>";
                            }
                        echo "</div>";
                        echo "<div class='$claseMesa'>MESA</div>";
                        echo "<div class='seat-block'>";
                            foreach (['A', 'B'] as $letra) {
                                echo "<div class='seat-row-tight " . ($isPremium ? "premium-row" : "") . "'>";
                                for ($i = 0; $i < $asientosPorBloque; $i++) {
                                    $id = sprintf("%03d", $numero_asiento_global++);
                                    $p = $isPremium ? "seat-premium" : "";
                                    echo "<div class='seat $p seat-right' data-seat='$id'>$id</div>";
                                }
                                echo "</div>";
                            }
                        echo "</div>";
                    echo "</div>";

                    echo "<div class='$clasePasillo'></div>";

                    // PARTE INFERIOR (Filas C y D)
                    echo "<div class='wagon-super-row'>";
                        echo "<div class='seat-block'>";
                            foreach (['C', 'D'] as $letra) {
                                echo "<div class='seat-row-tight " . ($isPremium ? "premium-row" : "") . "'>";
                                for ($i = 0; $i < $asientosPorBloque; $i++) {
                                    $id = sprintf("%03d", $numero_asiento_global++);
                                    $p = $isPremium ? "seat-premium" : "";
                                    echo "<div class='seat $p seat-left' data-seat='$id'>$id</div>";
                                }
                                echo "</div>";
                            }
                        echo "</div>";
                        echo "<div class='$claseMesa'>MESA</div>";
                        echo "<div class='seat-block'>";
                            foreach (['C', 'D'] as $letra) {
                                echo "<div class='seat-row-tight " . ($isPremium ? "premium-row" : "") . "'>";
                                for ($i = 0; $i < $asientosPorBloque; $i++) {
                                    $id = sprintf("%03d", $numero_asiento_global++);
                                    $p = $isPremium ? "seat-premium" : "";
                                    echo "<div class='seat $p seat-right' data-seat='$id'>$id</div>";
                                }
                                echo "</div>";
                            }
                        echo "</div>";
                    echo "</div>";

                    echo "</div></div>"; 
                }
                ?>
                <div class="tail-indicator">Cola</div>
            </div>
            
            <div class="booking-footer">
                <div class="selection-info">
                    Asiento: <strong id="displaySeat">Ninguno</strong> <br>Precio Base: <strong id="displayPrice">0,00 €</strong>
                </div>
                <button class="btn-next" id="btnToPayment" disabled onclick="irAPaso(3)">Continuar al Resumen</button>
            </div>
        </section>

        <section id="sectionSummary" class="booking-section hidden">
            <div class="payment-container" style="max-width: 600px; margin: 0 auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <div class="payment-header" style="border-bottom: none; margin-bottom: 10px;">
                    <h3><i class="fa-solid fa-list-check"></i> Resumen y Descuentos</h3>
                </div>
                
                <div class="trip-details" style="margin-bottom: 25px; padding: 15px; background: #f4f6f8; border-radius: 8px; font-size: 1.1rem;">
                    <p style="margin: 5px 0;"><strong>Tren:</strong> <span id="summaryTrain">--</span></p>
                    <p style="margin: 5px 0;"><strong>Asiento:</strong> <span id="summarySeat">--</span></p>
                    <p style="margin: 5px 0;"><strong>Precio del billete:</strong> <span id="summaryBasePrice">0,00 €</span></p>
                </div>

                <div class="discounts-section">
                    <h4 style="color: #0a2a66; margin-bottom: 15px;"><i class="fa-solid fa-tags"></i> Aplicar Descuentos</h4>
                    
                    <div class="promo-section">
                        <label for="codigoPromo" style="display: block; margin-bottom: 5px; font-weight: bold;">Promoción a aplicar</label>
                        
                        <select id="codigoPromo" name="codigoPromo" onchange="aplicarPromocion()" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-family: inherit;">
                            <option value="" data-descuento="0">Sin promoción</option>
                            <?php foreach ($promociones as $promo): ?>
                                <option value="<?= htmlspecialchars($promo['codigo']) ?>" data-descuento="<?= htmlspecialchars($promo['descuento_porcentaje']) ?>">
                                    <?= htmlspecialchars($promo['codigo']) ?> (-<?= (float)$promo['descuento_porcentaje'] ?>%)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <span id="promoMsg" style="display: block; margin-top: 5px; font-size: 0.9rem;"></span>
                    </div>

                    <div class="abono-selector" style="margin-top: 20px;">
                        <?php if (!isset($_SESSION['usuario'])): ?>
                            <label for="abonoActivo" style="display: block; margin-bottom: 5px; font-weight: bold;">Usar abono activo</label>
                            <select id="abonoActivo" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-family: inherit;" disabled>
                                <option value="">Inicie sesión para aplicar descuentos por abonos</option>
                            </select>
                        
                        <?php else: ?>
                            
                            <?php if (!empty($abonos_usuario)): ?>
                                <div class="form-group" style="padding: 15px; background: #eef2f7; border-radius: 8px; border: 1px solid #cce5ff;">
                                    <label style="color: #0a2a66; font-weight: bold; display: block; margin-bottom: 5px;"><i class="fa-solid fa-ticket"></i> Aplicar un Abono de mi cuenta</label>
                                    <select id="select-abono" name="id_abono_usado" onchange="recalcularPrecio()" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                                        <option value="">No usar abono (Pagar precio normal)</option>
                                        <?php foreach ($abonos_usuario as $abono): ?>
                                            <option value="<?= $abono['id_abono'] ?>" data-tipo="<?= $abono['tipo'] ?>">
                                                Abono <?= ucfirst(str_replace('_', ' ', $abono['tipo'])) ?> 
                                                <?= $abono['viajes_restantes'] !== null ? "({$abono['viajes_restantes']} viajes rest.)" : "(Ilimitado)" ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <p style="font-size: 0.9em; color: #666; padding: 10px; background: #f8f9fa; border-radius: 5px; border: 1px dashed #ccc;">
                                    <i class="fa-solid fa-info-circle"></i> No tienes abonos activos para aplicar a esta compra.
                                </p>
                            <?php endif; ?>

                        <?php endif; ?>
                    </div>
                    </div>

                <div class="summary-box" style="margin-top: 25px; font-size: 1.2rem; background: #e9ecef; padding: 15px; border-radius: 8px; text-align: center;">
                    <p style="margin: 0;">Total a pagar: <strong id="summaryFinalPrice" style="color: #0a2a66; font-size: 1.5rem;">0,00 €</strong></p>
                </div>

                <button id="btnPaso3" class="btn-pay-confirm" onclick="irAPaso(4)" style="margin-top: 15px; width: 100%;">Continuar al Pago Seguro</button>
            </div>
        </section>

        <section id="sectionPayment" class="booking-section hidden">
            <div class="payment-container" style="max-width: 500px; margin: 0 auto;">
                <div class="payment-header">
                    <h3><i class="fa-regular fa-credit-card"></i> Datos de Pago</h3>
                    <div class="card-icons">
                        <i class="fa-brands fa-cc-visa"></i>
                        <i class="fa-brands fa-cc-mastercard"></i>
                    </div>
                </div>
                
                <form class="payment-form" onsubmit="event.preventDefault(); confirmarReserva();" autocomplete="off">
                    <div class="form-group full-width">
                        <label for="cardHolder">Titular</label>
                        <input type="text" id="cardHolder" required placeholder="Ej: Juan Pérez">
                        <span class="input-error" id="errCardHolder" style="display:none;"></span>
                    </div>
                    <div class="form-group full-width">
                        <label for="cardNumber">Número de Tarjeta</label>
                        <div class="input-icon">
                            <input type="text" id="cardNumber" maxlength="19" required placeholder="1234 5678 9012 3456" inputmode="numeric">
                            <i class="fa-solid fa-lock" style="position: absolute; right: 10px; top: 12px; color: #ccc;"></i>
                        </div>
                        <span class="input-error" id="errCardNumber" style="display:none;"></span>
                    </div>
                    <div class="form-row" style="display: flex; gap: 15px;">
                        <div class="form-group expand">
                            <label for="cardExpiry">Caducidad</label>
                            <input type="text" id="cardExpiry" required placeholder="MM/AA" maxlength="5" inputmode="numeric">
                            <span class="input-error" id="errCardExpiry" style="display:none;"></span>
                        </div>
                        <div class="form-group expand">
                            <label for="cardCVV">CVV</label>
                            <input type="password" id="cardCVV" required placeholder="Ej: 123" maxlength="3" inputmode="numeric">
                            <span class="input-error" id="errCardCVV" style="display:none;"></span>
                        </div>
                    </div>
                    <div class="summary-box" style="text-align: center; margin-top: 20px;">
                        <p style="margin: 0; font-size: 1.2rem;">Importe final a cargar: <strong id="finalPaymentPrice" style="color: #17632A;">0,00 €</strong></p>
                    </div>
                    <button type="submit" class="btn-pay-confirm" style="margin-top: 15px; width: 100%;">Procesar Pago y Reservar</button>
                </form>
            </div>
        </section>

    </main>

    <script src="scripts/i18n.js?v=<?php echo @filemtime(__DIR__ . '/scripts/i18n.js'); ?>"></script>
    <script src="js/compra.js"></script>
    <script>
    // --- Formato y validación de pago seguro ---
    document.addEventListener('DOMContentLoaded', function() {
        const cardNumber = document.getElementById('cardNumber');
        const cardExpiry = document.getElementById('cardExpiry');
        const cardCVV = document.getElementById('cardCVV');
        const cardHolder = document.getElementById('cardHolder');
        const errCardNumber = document.getElementById('errCardNumber');
        const errCardExpiry = document.getElementById('errCardExpiry');
        const errCardCVV = document.getElementById('errCardCVV');
        const errCardHolder = document.getElementById('errCardHolder');
        const paymentForm = document.querySelector('.payment-form');

        // Formato número de tarjeta
        cardNumber.addEventListener('input', function(e) {
            let value = cardNumber.value.replace(/\D/g, '');
            if (value.length > 16) value = value.slice(0, 16);
            let formatted = value.replace(/(.{4})/g, '$1 ').trim();
            cardNumber.value = formatted;
        });

        // Formato caducidad MM/AA
        cardExpiry.addEventListener('input', function(e) {
            let value = cardExpiry.value.replace(/[^\d]/g, '');
            if (value.length > 4) value = value.slice(0, 4);
            if (value.length > 2) {
                value = value.slice(0,2) + '/' + value.slice(2);
            }
            cardExpiry.value = value;
        });

        // Solo números en CVV
        cardCVV.addEventListener('input', function(e) {
            let value = cardCVV.value.replace(/\D/g, '');
            if (value.length > 3) value = value.slice(0, 3);
            cardCVV.value = value;
        });

        // Validación visual

        function validateCardNumber() {
            const value = cardNumber.value.replace(/\s/g, '');
            if (value === '') {
                errCardNumber.style.display = 'none';
                cardNumber.classList.add('input-invalid');
                return false;
            }
            if (!/^\d{16}$/.test(value)) {
                errCardNumber.textContent = 'Introduce 16 dígitos válidos.';
                errCardNumber.style.display = 'block';
                cardNumber.classList.add('input-invalid');
                return false;
            }
            errCardNumber.style.display = 'none';
            cardNumber.classList.remove('input-invalid');
            return true;
        }
        function validateCardExpiry() {
            const value = cardExpiry.value;
            if (value === '') {
                errCardExpiry.style.display = 'none';
                cardExpiry.classList.add('input-invalid');
                return false;
            }
            if (!/^\d{2}\/\d{2}$/.test(value)) {
                errCardExpiry.textContent = 'Formato MM/AA.';
                errCardExpiry.style.display = 'block';
                cardExpiry.classList.add('input-invalid');
                return false;
            }
            // Validar mes y año
            const [mes, anio] = value.split('/').map(Number);
            if (mes < 1 || mes > 12) {
                errCardExpiry.textContent = 'Mes inválido.';
                errCardExpiry.style.display = 'block';
                cardExpiry.classList.add('input-invalid');
                return false;
            }
            // Validar que no sea pasado
            const hoy = new Date();
            const expYear = 2000 + anio;
            const expDate = new Date(expYear, mes - 1, 1);
            if (expDate < new Date(hoy.getFullYear(), hoy.getMonth(), 1)) {
                errCardExpiry.textContent = 'Tarjeta caducada.';
                errCardExpiry.style.display = 'block';
                cardExpiry.classList.add('input-invalid');
                return false;
            }
            errCardExpiry.style.display = 'none';
            cardExpiry.classList.remove('input-invalid');
            return true;
        }
        function validateCardCVV() {
            const value = cardCVV.value;
            if (value === '') {
                errCardCVV.style.display = 'none';
                cardCVV.classList.add('input-invalid');
                return false;
            }
            if (!/^\d{3}$/.test(value)) {
                errCardCVV.textContent = 'CVV de 3 dígitos.';
                errCardCVV.style.display = 'block';
                cardCVV.classList.add('input-invalid');
                return false;
            }
            errCardCVV.style.display = 'none';
            cardCVV.classList.remove('input-invalid');
            return true;
        }
        function validateCardHolder() {
            const value = cardHolder.value.trim();
            if (value === '') {
                errCardHolder.style.display = 'none';
                cardHolder.classList.add('input-invalid');
                return false;
            }
            if (value.length < 3) {
                errCardHolder.textContent = 'Introduce el nombre y apellidos del titular.';
                errCardHolder.style.display = 'block';
                cardHolder.classList.add('input-invalid');
                return false;
            }
            errCardHolder.style.display = 'none';
            cardHolder.classList.remove('input-invalid');
            return true;
        }

        cardNumber.addEventListener('blur', validateCardNumber);
        cardExpiry.addEventListener('blur', validateCardExpiry);
        cardCVV.addEventListener('blur', validateCardCVV);
        cardHolder.addEventListener('blur', validateCardHolder);

        paymentForm.addEventListener('submit', function(e) {
            let valid = true;
            if (!validateCardHolder()) valid = false;
            if (!validateCardNumber()) valid = false;
            if (!validateCardExpiry()) valid = false;
            if (!validateCardCVV()) valid = false;
            if (!valid) {
                e.preventDefault();
                return false;
            }
        });
    });
    </script>
    <style>
    .input-invalid {
        border-color: #e74c3c !important;
        background: #fff6f6 !important;
    }
    .input-error {
        color: #e74c3c;
        font-size: 0.9em;
        margin-top: 2px;
        display: block;
    }
    </style>
</body>
</html>