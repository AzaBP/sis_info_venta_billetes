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

// 1. Obtener todos los trayectos programados
$sql = "SELECT 
            v.id_viaje, v.fecha, v.hora_salida, v.hora_llegada, v.precio as precio_base, v.estado as estado_viaje,
            t.modelo as tipo_tren, t.id_tren as codigo_tren,
            r.origen, 
            r.destino
        FROM VIAJE v
        JOIN TREN t ON v.id_tren = t.id_tren
        JOIN RUTA r ON v.id_ruta = r.id_ruta
        ORDER BY v.hora_salida ASC";

$stmt = $pdo->query($sql);
$trayectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Obtener las promociones activas
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
            <a href="index.php">Inicio</a>
            <a href="ayuda.php">Ayuda</a>
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
                        <a href="perfil_pasajero.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
                        <a href="mis_billetes.php"><i class="fa-solid fa-ticket"></i> Mis billetes</a>
                        
                        <?php if (($usuarioSesion['tipo_usuario'] ?? '') === 'empleado'): ?>
                            <a href="vendedor.php"><i class="fa-solid fa-briefcase"></i> Panel Empleado</a>
                        <?php endif; ?>
                        
                        <a href="php/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="inicio_sesion.html" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="booking-container">
        
        <div class="progress-bar-container">
            <div class="step active" id="step1"><span class="step-num">1</span> Trenes disponibles</div>
            <div class="step" id="step2"><span class="step-num">2</span> Selección de asientos</div>
            <div class="step" id="step3"><span class="step-num">3</span> Resumen y Descuentos</div>
            <div class="step" id="step4"><span class="step-num">4</span> Pago seguro</div>
        </div>

        <div class="search-summary">
            <div class="summary-text">
                <h2>Madrid (Todas) <i class="fa-solid fa-arrow-right"></i> Barcelona Sants</h2>
                <p>Jueves, 12 de Febrero | 1 Pasajero</p>
            </div>
            <button class="btn-modify" onclick="irAPaso(1)">
                <i class="fa-solid fa-train"></i> Cambiar de tren
            </button>
        </div>

        <section id="sectionTrains" class="booking-section">
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
                    <h3><i class="fa-solid fa-list-check"></i> 3. Resumen y Descuentos</h3>
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
                    <h3><i class="fa-regular fa-credit-card"></i> 4. Datos de Pago</h3>
                    <div class="card-icons">
                        <i class="fa-brands fa-cc-visa"></i>
                        <i class="fa-brands fa-cc-mastercard"></i>
                    </div>
                </div>
                
                <form class="payment-form" onsubmit="event.preventDefault(); confirmarReserva();">
                    <div class="form-group full-width">
                        <label>Titular</label>
                        <input type="text" required placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="form-group full-width">
                        <label>Número de Tarjeta</label>
                        <div class="input-icon">
                            <input type="text" maxlength="19" required placeholder="1234 5678 9012 3456">
                            <i class="fa-solid fa-lock" style="position: absolute; right: 10px; top: 12px; color: #ccc;"></i>
                        </div>
                    </div>
                    <div class="form-row" style="display: flex; gap: 15px;">
                        <div class="form-group expand">
                            <label>Caducidad</label>
                            <input type="text" required placeholder="MM/AA">
                        </div>
                        <div class="form-group expand">
                            <label>CVV</label>
                            <input type="password" required placeholder="Ej: 123" maxlength="3">
                        </div>
                    </div>
                    
                    <div class="summary-box" style="text-align: center; margin-top: 20px;">
                        <p style="margin: 0; font-size: 1.2rem;">Importe final a cargar: <strong id="finalPaymentPrice" style="color: #28a745;">0,00 €</strong></p>
                    </div>

                    <button type="submit" class="btn-pay-confirm" style="margin-top: 15px; width: 100%;">Procesar Pago y Reservar</button>
                </form>
            </div>
        </section>

    </main>

    <script src="js/compra.js"></script>
</body>
</html>