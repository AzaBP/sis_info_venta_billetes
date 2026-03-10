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
                <div class="price-full">Tren Completo</div>
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
                <div class="locomotive-indicator"><i class="fa-solid fa-train"></i> Cabina</div>
                
                <div class="wagon-frame">
                    
                    <div class="wagon-body active wagon-premium" id="wagon1">
                        <div class="info-message-premium">PRIMERA CLASE (Filas 1-10)</div>
                        
                        <div class="wagon-super-row">
                            <div class="seat-block">
                                <div class="seat-row-tight premium-row">
                                    <div class="seat seat-premium seat-left" data-seat="1A">1A</div>
                                    <div class="seat seat-premium seat-left" data-seat="2A">2A</div>
                                    <div class="seat seat-premium seat-left" data-seat="3A">3A</div>
                                    <div class="seat seat-premium seat-left" data-seat="4A">4A</div>
                                    <div class="seat seat-premium seat-left" data-seat="5A">5A</div>
                                </div>
                                <div class="seat-row-tight premium-row">
                                    <div class="seat seat-premium seat-left" data-seat="1B">1B</div>
                                    <div class="seat seat-premium seat-left" data-seat="2B">2B</div>
                                    <div class="seat seat-premium seat-left" data-seat="3B">3B</div>
                                    <div class="seat seat-premium seat-left" data-seat="4B">4B</div>
                                    <div class="seat seat-premium seat-left" data-seat="5B">5B</div>
                                </div>
                            </div>
                            <div class="long-table-wide">MESA</div>
                            <div class="seat-block">
                                <div class="seat-row-tight premium-row">
                                    <div class="seat seat-premium seat-right" data-seat="6A">6A</div>
                                    <div class="seat seat-premium seat-right" data-seat="7A">7A</div>
                                    <div class="seat seat-premium seat-right" data-seat="8A">8A</div>
                                    <div class="seat seat-premium seat-right" data-seat="9A">9A</div>
                                    <div class="seat seat-premium seat-right" data-seat="10A">10A</div>
                                </div>
                                <div class="seat-row-tight premium-row">
                                    <div class="seat seat-premium seat-right" data-seat="6B">6B</div>
                                    <div class="seat seat-premium seat-right" data-seat="7B">7B</div>
                                    <div class="seat seat-premium seat-right" data-seat="8B">8B</div>
                                    <div class="seat seat-premium seat-right" data-seat="9B">9B</div>
                                    <div class="seat seat-premium seat-right" data-seat="10B">10B</div>
                                </div>
                            </div>
                        </div>

                            <div class="aisle-horizontal" style="width: 40px;"></div>

                        <div class="wagon-super-row">
                            <div class="seat-block">
                                <div class="seat-row-tight premium-row">
                                    <div class="seat seat-premium seat-left" data-seat="1C">1C</div>
                                    <div class="seat seat-premium seat-left" data-seat="2C">2C</div>
                                    <div class="seat seat-premium seat-left" data-seat="3C">3C</div>
                                    <div class="seat seat-premium seat-left" data-seat="4C">4C</div>
                                    <div class="seat seat-premium seat-left" data-seat="5C">5C</div>
                                </div>
                                <div class="seat-row-tight premium-row">
                                    <div class="seat seat-premium seat-left" data-seat="1D">1D</div>
                                    <div class="seat seat-premium seat-left" data-seat="2D">2D</div>
                                    <div class="seat seat-premium seat-left" data-seat="3D">3D</div>
                                    <div class="seat seat-premium seat-left" data-seat="4D">4D</div>
                                    <div class="seat seat-premium seat-left" data-seat="5D">5D</div>
                                </div>
                            </div>
                            <div class="long-table-wide">MESA</div>
                            <div class="seat-block">
                                <div class="seat-row-tight premium-row">
                                    <div class="seat seat-premium seat-right" data-seat="6C">6C</div>
                                    <div class="seat seat-premium seat-right" data-seat="7C">7C</div>
                                    <div class="seat seat-premium seat-right" data-seat="8C">8C</div>
                                    <div class="seat seat-premium seat-right" data-seat="9C">9C</div>
                                    <div class="seat seat-premium seat-right" data-seat="10C">10C</div>
                                </div>
                                <div class="seat-row-tight premium-row">
                                    <div class="seat seat-premium seat-right" data-seat="6D">6D</div>
                                    <div class="seat seat-premium seat-right" data-seat="7D">7D</div>
                                    <div class="seat seat-premium seat-right" data-seat="8D">8D</div>
                                    <div class="seat seat-premium seat-right" data-seat="9D">9D</div>
                                    <div class="seat seat-premium seat-right" data-seat="10D">10D</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="wagon-body hidden" id="wagon2">
                        <div class="info-message">CLASE TURISTA (Filas 11-22)</div>
                        
                        <div class="wagon-super-row">
                            <div class="seat-block"> <div class="seat-row-tight"><div class="seat seat-left" data-seat="11A">11A</div><div class="seat seat-left" data-seat="12A">12A</div><div class="seat seat-left" data-seat="13A">13A</div><div class="seat seat-left" data-seat="14A">14A</div><div class="seat seat-left" data-seat="15A">15A</div><div class="seat seat-left" data-seat="16A">16A</div></div>
                                <div class="seat-row-tight"><div class="seat seat-left" data-seat="11B">11B</div><div class="seat seat-left" data-seat="12B">12B</div><div class="seat seat-left" data-seat="13B">13B</div><div class="seat seat-left" data-seat="14B">14B</div><div class="seat seat-left" data-seat="15B">15B</div><div class="seat seat-left" data-seat="16B">16B</div></div>
                            </div>

                            <div class="long-table">MESA</div> <div class="seat-block"> <div class="seat-row-tight"><div class="seat seat-right" data-seat="17A">17A</div><div class="seat seat-right" data-seat="18A">18A</div><div class="seat seat-right" data-seat="19A">19A</div><div class="seat seat-right" data-seat="20A">20A</div><div class="seat seat-right" data-seat="21A">21A</div><div class="seat seat-right" data-seat="22A">22A</div></div>
                                <div class="seat-row-tight"><div class="seat seat-right" data-seat="17B">17B</div><div class="seat seat-right" data-seat="18B">18B</div><div class="seat seat-right" data-seat="19B">19B</div><div class="seat seat-right" data-seat="20B">20B</div><div class="seat seat-right" data-seat="21B">21B</div><div class="seat seat-right" data-seat="22B">22B</div></div>
                            </div>
                        </div>
                        
                        <div class="aisle-horizontal"></div>
                        
                        <div class="wagon-super-row">
                             <div class="seat-block"> <div class="seat-row-tight"><div class="seat seat-left" data-seat="11C">11C</div><div class="seat seat-left" data-seat="12C">12C</div><div class="seat seat-left" data-seat="13C">13C</div><div class="seat seat-left" data-seat="14C">14C</div><div class="seat seat-left" data-seat="15C">15C</div><div class="seat seat-left" data-seat="16C">16C</div></div>
                                <div class="seat-row-tight"><div class="seat seat-left" data-seat="11D">11D</div><div class="seat seat-left" data-seat="12D">12D</div><div class="seat seat-left" data-seat="13D">13D</div><div class="seat seat-left" data-seat="14D">14D</div><div class="seat seat-left" data-seat="15D">15D</div><div class="seat seat-left" data-seat="16D">16D</div></div>
                            </div>

                            <div class="long-table">MESA</div> <div class="seat-block"> <div class="seat-row-tight"><div class="seat seat-right" data-seat="17C">17C</div><div class="seat seat-right" data-seat="18C">18C</div><div class="seat seat-right" data-seat="19C">19C</div><div class="seat seat-right" data-seat="20C">20C</div><div class="seat seat-right" data-seat="21C">21C</div><div class="seat seat-right" data-seat="22C">22C</div></div>
                                <div class="seat-row-tight"><div class="seat seat-right" data-seat="17D">17D</div><div class="seat seat-right" data-seat="18D">18D</div><div class="seat seat-right" data-seat="19D">19D</div><div class="seat seat-right" data-seat="20D">20D</div><div class="seat seat-right" data-seat="21D">21D</div><div class="seat seat-right" data-seat="22D">22D</div></div>
                            </div>
                        </div>
                    </div>

                    <div class="wagon-body hidden" id="wagon3">
                        <div class="info-message">CLASE TURISTA (Filas 23-34)</div>
                        
                        <div class="wagon-super-row">
                            <div class="seat-block"> 
                                <div class="seat-row-tight"><div class="seat seat-left" data-seat="23A">23A</div><div class="seat seat-left" data-seat="24A">24A</div><div class="seat seat-left" data-seat="25A">25A</div><div class="seat seat-left" data-seat="26A">26A</div><div class="seat seat-left" data-seat="27A">27A</div><div class="seat seat-left" data-seat="28A">28A</div></div>
                                <div class="seat-row-tight"><div class="seat seat-left" data-seat="23B">23B</div><div class="seat seat-left" data-seat="24B">24B</div><div class="seat seat-left" data-seat="25B">25B</div><div class="seat seat-left" data-seat="26B">26B</div><div class="seat seat-left" data-seat="27B">27B</div><div class="seat seat-left" data-seat="28B">28B</div></div>
                            </div>
                            <div class="long-table">MESA</div>
                            <div class="seat-block">
                                <div class="seat-row-tight"><div class="seat seat-right" data-seat="29A">29A</div><div class="seat seat-right" data-seat="30A">30A</div><div class="seat seat-right" data-seat="31A">31A</div><div class="seat seat-right" data-seat="32A">32A</div><div class="seat seat-right" data-seat="33A">33A</div><div class="seat seat-right" data-seat="34A">34A</div></div>
                                <div class="seat-row-tight"><div class="seat seat-right" data-seat="29B">29B</div><div class="seat seat-right" data-seat="30B">30B</div><div class="seat seat-right" data-seat="31B">31B</div><div class="seat seat-right" data-seat="32B">32B</div><div class="seat seat-right" data-seat="33B">33B</div><div class="seat seat-right" data-seat="34B">34B</div></div>
                            </div>
                        </div>
                        
                        <div class="aisle-horizontal"></div>
                        
                        <div class="wagon-super-row">
                             <div class="seat-block"> 
                                <div class="seat-row-tight"><div class="seat seat-left" data-seat="23C">23C</div><div class="seat seat-left" data-seat="24C">24C</div><div class="seat seat-left" data-seat="25C">25C</div><div class="seat seat-left" data-seat="26C">26C</div><div class="seat seat-left" data-seat="27C">27C</div><div class="seat seat-left" data-seat="28C">28C</div></div>
                                <div class="seat-row-tight"><div class="seat seat-left" data-seat="23D">23D</div><div class="seat seat-left" data-seat="24D">24D</div><div class="seat seat-left" data-seat="25D">25D</div><div class="seat seat-left" data-seat="26D">26D</div><div class="seat seat-left" data-seat="27D">27D</div><div class="seat seat-left" data-seat="28D">28D</div></div>
                            </div>
                            <div class="long-table">MESA</div>
                            <div class="seat-block">
                                <div class="seat-row-tight"><div class="seat seat-right" data-seat="29C">29C</div><div class="seat seat-right" data-seat="30C">30C</div><div class="seat seat-right" data-seat="31C">31C</div><div class="seat seat-right" data-seat="32C">32C</div><div class="seat seat-right" data-seat="33C">33C</div><div class="seat seat-right" data-seat="34C">34C</div></div>
                                <div class="seat-row-tight"><div class="seat seat-right" data-seat="29D">29D</div><div class="seat seat-right" data-seat="30D">30D</div><div class="seat seat-right" data-seat="31D">31D</div><div class="seat seat-right" data-seat="32D">32D</div><div class="seat seat-right" data-seat="33D">33D</div><div class="seat seat-right" data-seat="34D">34D</div></div>
                            </div>
                        </div>
                    </div>

                </div>
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
                    <div class="form-group full-width"><label>Titular</label><input type="text" required></div>
                    <div class="form-group full-width"><label>Número</label><div class="input-icon"><input type="text" maxlength="19" required><i class="fa-solid fa-lock"></i></div></div>
                    <div class="form-row">
                        <div class="form-group expand"><label>Caducidad</label><input type="text" required></div>
                        <div class="form-group expand"><label>CVV</label><input type="password" required></div>
                    </div>
                    <div class="summary-box"><p>Total: <strong id="finalPrice">0,00 €</strong></p></div>
                    <div class="promo-section">
                <div class="booking-actions" style="margin-top: 20px; text-align: center;">
                    <button id="btnComprar" class="btn-primary" disabled onclick="confirmarReserva()">
                        Confirmar Reserva
                    </button>
                </div>
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

