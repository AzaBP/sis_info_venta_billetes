<?php
require_once 'php/Conexion.php';

$conexion = new Conexion();
$pdo = $conexion->conectar();

// Consulta adaptada a tus tablas (VIAJE, RUTA, TREN)
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
            <span style="font-size: 0.8rem; opacity: 0.8; font-weight: normal; margin-left: 10px; vertical-align: middle; position: relative; bottom: 2px;">| Área de Cliente</span>
        </div>
        <nav class="nav">
            <a href="index.php">Inicio</a>
            <a href="ayuda.php">Ayuda</a>
            <div class="user-actions" id="userActions">
                <a href="inicio_sesion.html" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> Iniciar sesión</a>
            </div>
        </nav>
    </header>

    <main class="booking-container">
        
        <div class="progress-bar-container">
            <div class="step active" id="step1" onclick="irAPaso(1)">
                <span class="step-num">1</span> Trenes disponibles
            </div>
            <div class="step" id="step2" onclick="irAPaso(2)">
                <span class="step-num">2</span> Selección de asientos
            </div>
            <div class="step" id="step3">
                <span class="step-num">3</span> Pago seguro
            </div>
        </div>

        <div class="search-summary">
            <div class="summary-text">
                <h2>Madrid (Todas) <i class="fa-solid fa-arrow-right"></i> Barcelona Sants</h2>
                <p>Jueves, 12 de Febrero | 1 Pasajero</p>
            </div>
            <button class="btn-modify" onclick="window.location.href='index.html'">
                <i class="fa-solid fa-pen-to-square"></i> Modificar datos
            </button>
        </div>

        <section id="sectionTrains" class="booking-section">
            
    <?php foreach ($trayectos as $trayecto): 
        // Formatear horas
        $hora_salida = date('H:i', strtotime($trayecto['hora_salida']));
        $hora_llegada = date('H:i', strtotime($trayecto['hora_llegada']));
        
        // Calcular duración
        $dteStart = new DateTime($trayecto['hora_salida']);
        $dteEnd   = new DateTime($trayecto['hora_llegada']);
        $dteDiff  = $dteStart->diff($dteEnd);
        $duracion = $dteDiff->format('%hh %Imin');

        // Formatear precio (sustituyendo el punto por la coma para Europa)
        $precio = number_format($trayecto['precio_base'], 2, ',', '');

        // Lógica de iconos
        $icono_amenity = 'fa-train'; 
        if (strtolower($trayecto['tipo_tren']) == 'ave') $icono_amenity = 'fa-wifi';
        if (strtolower($trayecto['tipo_tren']) == 'avlo') $icono_amenity = 'fa-plug';
        if (strtolower($trayecto['tipo_tren']) == 'alvia') $icono_amenity = 'fa-person-walking-luggage';

        // Lógica de tren lleno (en tu BD pusimos estado "completado" o "en_curso")
        $isFull = ($trayecto['estado_viaje'] === 'completado');
        $cardClass = $isFull ? "ticket-card full-train" : "ticket-card";
    ?>

    <div class="<?= $cardClass ?>">
        <div class="col-train-info">
            <span class="train-type type-<?= strtolower($trayecto['tipo_tren']) ?>">
                <?= strtoupper($trayecto['tipo_tren']) ?>
            </span> 
            <span class="train-id"><?= htmlspecialchars(str_pad($trayecto['codigo_tren'], 4, '0', STR_PAD_LEFT)) ?></span>
            <div class="amenities"><i class="fa-solid <?= $icono_amenity ?>"></i></div>
        </div>
        <div class="col-schedule">
            <div class="time-group">
                <span class="hour"><?= $hora_salida ?></span>
                <span class="city">MAD</span> </div>
            <div class="duration-line">
                <span class="duration-text"><?= $duracion ?></span>
                <div class="line"><i class="fa-solid fa-train"></i></div>
            </div>
            <div class="time-group">
                <span class="hour"><?= $hora_llegada ?></span>
                <span class="city">BCN</span>
            </div>
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
                // Contador global que no se reinicia entre vagones
                $numero_asiento_global = 1;

                for ($w = 1; $w <= 3; $w++) {
                    $isPremium = ($w == 1);
                    // Configuración dinámica según la clase
                    $wagonClass = $isPremium ? "wagon-premium" : "wagon-standard";
                    $wagonTitle = $isPremium ? "Primera Clase" : "Segunda Clase";
                    $displayClass = ($w == 1) ? "" : "hidden";
                    // 1ª Clase: 5 asientos por bloque (10 por fila). 2ª Clase: 6 asientos (12 por fila).
                    $asientosPorBloque = $isPremium ? 5 : 6;
                    $claseMesa = $isPremium ? "long-table-wide" : "long-table";
                    $clasePasillo = $isPremium ? "aisle-horizontal-wide" : "aisle-horizontal";

                    echo "<div id='wagon$w' class='wagon-body $wagonClass $displayClass'>";
                    echo "<div class='info-message'>$wagonTitle</div>";
                    echo "<div class='wagon-layout'>";

                    // --- PARTE SUPERIOR (Filas A y B) ---
                    echo "<div class='wagon-super-row'>";
                        // Bloque Izquierdo (Antes de la mesa)
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

                        // Bloque Derecho (Después de la mesa)
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

                    // --- PASILLO ---
                    echo "<div class='$clasePasillo'></div>";

                    // --- PARTE INFERIOR (Filas C y D) ---
                    echo "<div class='wagon-super-row'>";
                        // Bloque Izquierdo
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

                        // Bloque Derecho
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

                    echo "</div></div>"; // Fin wagon-layout y wagon-body
                }
                ?>
                <div class="tail-indicator">Cola</div>
            </div>
            
            <div class="booking-footer">
                <div class="selection-info">
                    Asiento: <strong id="displaySeat">Ninguno</strong> <br>Total: <strong id="displayPrice">0,00 €</strong>
                </div>
                <button class="btn-next" id="btnToPayment" disabled onclick="irAPaso(3)">Continuar al Pago</button>
            </div>
        </section>

        <section id="sectionPayment" class="booking-section hidden">
            <div class="payment-container">
                <div class="payment-header">
                    <h3><i class="fa-regular fa-credit-card"></i> Datos de Pago</h3>
                    <div class="card-icons">
                        <i class="fa-brands fa-cc-visa brand-visa"></i>
                        <i class="fa-brands fa-cc-mastercard brand-mastercard"></i>
                    </div>
                </div>
                <form class="payment-form" onsubmit="event.preventDefault(); finalizarCompra();">
                    <div class="form-group full-width"><label>Titular</label><input type="text" required placeholder="Ej: Juan Pérez"></div>
                    <div class="form-group full-width"><label>Número</label><div class="input-icon"><input type="text" maxlength="19" required placeholder="Ej: 1234 5678 9012 3456"><i class="fa-solid fa-lock"></i></div></div>
                    <div class="form-row">
                        <div class="form-group expand"><label>Caducidad</label><input type="text" required placeholder="MM/AA"></div>
                        <div class="form-group expand"><label>CVV</label><input type="password" required placeholder="Ej: 123"></div>
                    </div>
                    <div class="summary-box"><p>Total: <strong id="finalPrice">0,00 €</strong></p></div>
                    <div class="promo-section">
                        <label for="codigoPromo">Código promocional</label>
                        <input type="text" id="codigoPromo" name="codigoPromo" placeholder="Introduce tu código">
                        <button type="button">Aplicar</button>
                        <span class="promo-msg"></span>
                    </div>

                    <div class="abono-selector">
                        <label for="abonoActivo">Usar abono activo</label>
                        <select id="abonoActivo" name="abonoActivo">
                            <option value="">No usar abono</option>
                            <option value="mensual">Mensual (válido hasta 31/03/2026)</option>
                            <option value="anual">Anual (válido hasta 01/03/2027)</option>
                        </select>
                    </div>

                    <button type="button" class="btn-pay-confirm" onclick="confirmarReserva()">Pagar</button>
                </form>
            </div>
        </section>
    </main>

    <script src="scripts/session_menu.js"></script>
    <script src="js/compra.js"></script>
</body>
</html>

