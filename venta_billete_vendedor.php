<?php
// Nueva interfaz de venta de billete para el vendedor (atención al cliente)
session_start();

require_once __DIR__ . '/php/Conexion.php';
require_once __DIR__ . '/php/auth_helpers.php';

$usuario = $_SESSION['usuario'] ?? null;

// Verificar que sea empleado
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'empleado') {
    header('Location: employee_login.php?error=no_autorizado');
    exit;
}

// Obtener datos del cliente desde la URL (pasados desde vendedor.php)
$id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;
$nombre_cliente = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';
$dni_cliente = isset($_GET['dni']) ? trim($_GET['dni']) : '';
$email_cliente = isset($_GET['email']) ? trim($_GET['email']) : '';

if (!$id_cliente) {
    header('Location: vendedor.php?error=cliente_no_seleccionado');
    exit;
}

$nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''));
if ($nombreCompleto === '') $nombreCompleto = 'Vendedor Desconocido';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainWeb - Venta de Billete (Atención al Cliente)</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/compra.css">
    <style>
        body { background: #f4f6f8; }
        .header { background: #0a2a66; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header .logo { font-size: 1.5rem; font-weight: bold; }
        .header .nav a { color: white; text-decoration: none; margin-left: 20px; }
        
        .breadcrumb { padding: 15px 20px; background: white; border-bottom: 1px solid #ddd; }
        .breadcrumb a { color: #0a2a66; text-decoration: none; }
        
        .client-info-bar {
            background: linear-gradient(135deg, #0a2a66 0%, #1a4a8f 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .client-info-bar .client-details { display: flex; gap: 30px; }
        .client-info-bar .client-details span { display: flex; align-items: center; gap: 8px; }
        
        .main-container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        
        .booking-section { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        
        .search-form { display: grid; grid-template-columns: repeat(4, 1fr) auto; gap: 15px; align-items: end; }
        .search-form .form-group { display: flex; flex-direction: column; }
        .search-form label { font-weight: bold; color: #0a2a66; margin-bottom: 5px; font-size: 0.9rem; }
        .search-form input, .search-form select { padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; }
        .search-form button { padding: 12px 25px; background: #0a2a66; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .search-form button:hover { background: #1a4a8f; }
        
        .suggestions-container {
            position: absolute;
            background: white;
            border: 1px solid #ccc;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            width: 100%;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .suggestions-container div { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
        .suggestions-container div:hover { background: #f4f6f8; }
        
        .form-group { position: relative; }
        
        .step-indicator { display: flex; justify-content: center; gap: 20px; margin-bottom: 30px; }
        .step { display: flex; align-items: center; gap: 8px; padding: 12px 20px; background: #e9ecef; border-radius: 25px; color: #666; font-weight: 500; }
        .step.active { background: #0a2a66; color: white; }
        .step.completed { background: #28a745; color: white; }
        .step-num { width: 25px; height: 25px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; }
        
        .trip-results { margin-top: 20px; }
        .trip-card { border: 2px solid #e9ecef; border-radius: 10px; padding: 15px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s; }
        .trip-card:hover { border-color: #0a2a66; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .trip-card.selected { border-color: #0a2a66; background: #f0f4ff; }
        .trip-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .trip-card-header h4 { color: #0a2a66; margin: 0; }
        .trip-card-header .price { font-size: 1.3rem; font-weight: bold; color: #0a2a66; }
        .trip-card-details { display: flex; gap: 30px; color: #666; font-size: 0.95rem; }
        
        .seat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .seat-header h3 { color: #0a2a66; margin: 0; }
        
        .seat-leg-switcher { display: flex; align-items: center; gap: 15px; }
        .seat-leg-buttons { display: flex; gap: 5px; }
        .tramo-btn { padding: 8px 16px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 5px; }
        .tramo-btn.active { background: #0a2a66; color: white; border-color: #0a2a66; }
        
        .wagon-navigator { display: flex; align-items: center; gap: 15px; }
        .nav-arrow { width: 40px; height: 40px; border: 1px solid #ddd; background: white; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .nav-arrow:hover { background: #0a2a66; color: white; border-color: #0a2a66; }
        .wagon-title { font-weight: bold; color: #0a2a66; }
        
        .train-horizontal-container { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0; }
        
        .wagon-body { display: none; }
        .wagon-body.active { display: block; }
        
        .info-message { text-align: center; padding: 10px; background: #e9ecef; border-radius: 5px; margin-bottom: 15px; font-weight: bold; color: #0a2a66; }
        
        .wagon-layout { display: flex; flex-direction: column; gap: 15px; }
        
        .wagon-super-row { display: flex; justify-content: center; gap: 20px; }
        
        .seat-block { display: flex; flex-direction: column; gap: 8px; }
        
        .seat-row-tight { display: flex; gap: 5px; }
        
        .seat { 
            width: 50px; height: 45px; 
            display: flex; align-items: center; justify-content: center; 
            border: 2px solid #ddd; border-radius: 8px; 
            cursor: pointer; font-weight: bold; font-size: 0.9rem;
            background: white; color: #333;
            transition: all 0.2s;
        }
        .seat:hover:not(.occupied):not(.selected) { border-color: #0a2a66; transform: scale(1.05); }
        .seat.selected { background: #0a2a66; color: white; border-color: #0a2a66; }
        .seat.occupied { background: #e9ecef; color: #aaa; cursor: not-allowed; border-color: #ddd; }
        .seat.premium { background: #fff3cd; border-color: #ffc107; }
        .seat.premium.selected { background: #ffc107; color: #333; border-color: #ffc107; }
        .seat.premium.occupied { background: #f8f9fa; color: #aaa; border-color: #ddd; }
        
        .long-table { width: 80px; display: flex; align-items: center; justify-content: center; background: #e9ecef; border-radius: 8px; font-size: 0.8rem; color: #666; }
        .long-table-wide { width: 100px; }
        
        .aisle-horizontal { width: 30px; }
        .aisle-horizontal-wide { width: 40px; }
        
        .tail-indicator { text-align: center; padding: 10px; background: #e9ecef; border-radius: 5px; margin-top: 15px; color: #666; font-weight: bold; }
        
        .booking-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .selection-info { font-size: 1.1rem; }
        .selection-info strong { color: #0a2a66; }
        
        .btn-next { padding: 12px 30px; background: #0a2a66; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1rem; }
        .btn-next:hover { background: #1a4a8f; }
        .btn-next:disabled { background: #ccc; cursor: not-allowed; }
        
        .payment-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .payment-header { border-bottom: 2px solid #0a2a66; margin-bottom: 20px; padding-bottom: 10px; }
        .payment-header h3 { color: #0a2a66; margin: 0; }
        
        .trip-details { margin-bottom: 20px; padding: 15px; background: #f4f6f8; border-radius: 8px; }
        .trip-details p { margin: 8px 0; }
        
        .discounts-section { margin-bottom: 20px; }
        .discounts-section label { display: block; font-weight: bold; color: #0a2a66; margin-bottom: 8px; }
        .discounts-section input { padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 100px; }
        
        .summary-box { background: #e9ecef; padding: 15px; border-radius: 8px; text-align: center; font-size: 1.2rem; margin-bottom: 20px; }
        .summary-box strong { color: #0a2a66; font-size: 1.5rem; }
        
        .form-group-full { margin-bottom: 15px; }
        .form-group-full label { display: block; font-weight: bold; color: #0a2a66; margin-bottom: 5px; }
        .form-group-full input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        
        .result-message { padding: 15px; border-radius: 8px; margin-top: 15px; text-align: center; }
        .result-message.success { background: #d4edda; color: #155724; }
        .result-message.error { background: #f8d7da; color: #721c24; }
        
        .hidden { display: none !important; }
    </style>
</head>
<body>

    <header class="header">
        <a href="index.php" class="logo">
            <i class="fa-solid fa-train"></i> TrainWeb 
            <span style="font-size: 0.8rem; opacity: 0.8; font-weight: normal; margin-left: 10px;">| Venta de Billete</span>
        </a>
        <nav class="nav">
            <a href="vendedor.php"><i class="fa-solid fa-arrow-left"></i> Volver al panel</a>
        </nav>
    </header>

    <div class="breadcrumb">
        <a href="vendedor.php">Panel de Vendedor</a> > <span>Atención al Cliente > Nueva Venta</span>
    </div>

    <div class="client-info-bar">
        <div class="client-details">
            <span><i class="fa-solid fa-user"></i> <strong>Cliente:</strong> <?= htmlspecialchars($nombre_cliente) ?></span>
            <span><i class="fa-solid fa-id-card"></i> <strong>DNI:</strong> <?= htmlspecialchars($dni_cliente) ?></span>
            <span><i class="fa-solid fa-envelope"></i> <strong>Email:</strong> <?= htmlspecialchars($email_cliente) ?></span>
        </div>
        <div>
            <span style="opacity: 0.8;">Vendedor: <?= htmlspecialchars($nombreCompleto) ?></span>
        </div>
    </div>

    <div class="main-container">
        
        <!-- Paso 1: Buscar viaje -->
        <section id="sectionSearch" class="booking-section">
            <div class="step-indicator">
                <div class="step active" id="step1"><span class="step-num">1</span> Buscar Viaje</div>
                <div class="step" id="step2"><span class="step-num">2</span> Seleccionar Asiento</div>
                <div class="step" id="step3"><span class="step-num">3</span> Datos de Compra</div>
            </div>

            <h3><i class="fa-solid fa-magnifying-glass"></i> Buscar Viaje</h3>
            
            <form id="formBuscarViaje" class="search-form">
                <div class="form-group">
                    <label>Origen</label>
                    <input type="text" id="inputOrigen" name="origen" placeholder="Ciudad de origen" required autocomplete="off">
                    <div id="suggOrigen" class="suggestions-container"></div>
                </div>
                <div class="form-group">
                    <label>Destino</label>
                    <input type="text" id="inputDestino" name="destino" placeholder="Ciudad de destino" required autocomplete="off">
                    <div id="suggDestino" class="suggestions-container"></div>
                </div>
                <div class="form-group">
                    <label>Fecha</label>
                    <input type="date" id="inputFecha" name="fecha" required>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit">Buscar</button>
                </div>
            </form>

            <div id="resultadosViajes" class="trip-results"></div>
        </section>

        <!-- Paso 2: Seleccionar asiento -->
        <section id="sectionSeats" class="booking-section hidden">
            <div class="step-indicator">
                <div class="step completed" id="step1"><span class="step-num">1</span> Buscar Viaje</div>
                <div class="step active" id="step2"><span class="step-num">2</span> Seleccionar Asiento</div>
                <div class="step" id="step3"><span class="step-num">3</span> Datos de Compra</div>
            </div>

            <div class="seat-header">
                <h3><i class="fa-solid fa-chair"></i> Selecciona tu plaza en <span id="lblTrenSeleccionado">--</span></h3>
                
                <div class="wagon-navigator">
                    <button class="nav-arrow" id="btnPrev" onclick="cambiarVagon(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                    <span class="wagon-title">Vagón <span id="currentWagonNum">1</span></span>
                    <button class="nav-arrow" id="btnNext" onclick="cambiarVagon(1)"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>

            <div class="train-horizontal-container">
                <!-- Wagon 1 - Premium -->
                <div id="wagon1" class="wagon-body active">
                    <div class="info-message">Primera Clase</div>
                    <div class="wagon-layout">
                        <!-- Filas superiores -->
                        <div class="wagon-super-row">
                            <div class="seat-block">
                                <div class="seat-row-tight">
                                    <div class="seat seat-left" data-seat="001" data-wagon="1">001</div>
                                    <div class="seat seat-left" data-seat="002" data-wagon="1">002</div>
                                    <div class="seat seat-left" data-seat="003" data-wagon="1">003</div>
                                    <div class="seat seat-left" data-seat="004" data-wagon="1">004</div>
                                    <div class="seat seat-left" data-seat="005" data-wagon="1">005</div>
                                </div>
                                <div class="seat-row-tight">
                                    <div class="seat seat-left" data-seat="006" data-wagon="1">006</div>
                                    <div class="seat seat-left" data-seat="007" data-wagon="1">007</div>
                                    <div class="seat seat-left" data-seat="008" data-wagon="1">008</div>
                                    <div class="seat seat-left" data-seat="009" data-wagon="1">009</div>
                                    <div class="seat seat-left" data-seat="010" data-wagon="1">010</div>
                                </div>
                            </div>
                            <div class="long-table-long-wide">MESA</div>
                            <div class="seat-block">
                                <div class="seat-row-tight">
                                    <div class="seat seat-right" data-seat="011" data-wagon="1">011</div>
                                    <div class="seat seat-right" data-seat="012" data-wagon="1">012</div>
                                    <div class="seat seat-right" data-seat="013" data-wagon="1">013</div>
                                    <div class="seat seat-right" data-seat="014" data-wagon="1">014</div>
                                    <div class="seat seat-right" data-seat="015" data-wagon="1">015</div>
                                </div>
                                <div class="seat-row-tight">
                                    <div class="seat seat-right" data-seat="016" data-wagon="1">016</div>
                                    <div class="seat seat-right" data-seat="017" data-wagon="1">017</div>
                                    <div class="seat seat-right" data-seat="018" data-wagon="1">018</div>
                                    <div class="seat seat-right" data-seat="019" data-wagon="1">019</div>
                                    <div class="seat seat-right" data-seat="020" data-wagon="1">020</div>
                                </div>
                            </div>
                        </div>
                        <div class="aisle-horizontal-wide"></div>
                        <!-- Filas inferiores -->
                        <div class="wagon-super-row">
                            <div class="seat-block">
                                <div class="seat-row-tight">
                                    <div class="seat seat-left" data-seat="021" data-wagon="1">021</div>
                                    <div class="seat seat-left" data-seat="022" data-wagon="1">022</div>
                                    <div class="seat seat-left" data-seat="023" data-wagon="1">023</div>
                                    <div class="seat seat-left" data-seat="024" data-wagon="1">024</div>
                                    <div class="seat seat-left" data-seat="025" data-wagon="1">025</div>
                                </div>
                                <div class="seat-row-tight">
                                    <div class="seat seat-left" data-seat="026" data-wagon="1">026</div>
                                    <div class="seat seat-left" data-seat="027" data-wagon="1">027</div>
                                    <div class="seat seat-left" data-seat="028" data-wagon="1">028</div>
                                    <div class="seat seat-left" data-seat="029" data-wagon="1">029</div>
                                    <div class="seat seat-left" data-seat="030" data-wagon="1">030</div>
                                </div>
                            </div>
                            <div class="long-table-long-wide">MESA</div>
                            <div class="seat-block">
                                <div class="seat-row-tight">
                                    <div class="seat seat-right" data-seat="031" data-wagon="1">031</div>
                                    <div class="seat seat-right" data-seat="032" data-wagon="1">032</div>
                                    <div class="seat seat-right" data-seat="033" data-wagon="1">033</div>
                                    <div class="seat seat-right" data-seat="034" data-wagon="1">034</div>
                                    <div class="seat seat-right" data-seat="035" data-wagon="1">035</div>
                                </div>
                                <div class="seat-row-tight">
                                    <div class="seat seat-right" data-seat="036" data-wagon="1">036</div>
                                    <div class="seat seat-right" data-seat="037" data-wagon="1">037</div>
                                    <div class="seat seat-right" data-seat="038" data-wagon="1">038</div>
                                    <div class="seat seat-right" data-seat="039" data-wagon="1">039</div>
                                    <div class="seat seat-right" data-seat="040" data-wagon="1">040</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wagon 2 - Standard -->
                <div id="wagon2" class="wagon-body">
                    <div class="info-message">Segunda Clase</div>
                    <div class="wagon-layout">
                        <div class="wagon-super-row">
                            <div class="seat-block">
                                <div class="seat-row-tight">
                                    <div class="seat seat-left" data-seat="101" data-wagon="2">101</div>
                                    <div class="seat seat-left" data-seat="102" data-wagon="2">102</div>
                                    <div class="seat seat-left" data-seat="103" data-wagon="2">103</div>
                                    <div class="seat seat-left" data-seat="104" data-wagon="2">104</div>
                                    <div class="seat seat-left" data-seat="105" data-wagon="2">105</div>
                                    <div class="seat seat-left" data-seat="106" data-wagon="2">106</div>
                                </div>
                                <div class="seat-row-tight">
                                    <div class="seat seat-left" data-seat="107" data-wagon="2">107</div>
                                    <div class="seat seat-left" data-seat="108" data-wagon="2">108</div>
                                    <div class="seat seat-left" data-seat="109" data-wagon="2">109</div>
                                    <div class="seat seat-left" data-seat="110" data-wagon="2">110</div>
                                    <div class="seat seat-left" data-seat="111" data-wagon="2">111</div>
                                    <div class="seat seat-left" data-seat="112" data-wagon="2">112</div>
                                </div>
                            </div>
                            <div class="long-table">MESA</div>
                            <div class="seat-block">
                                <div class="seat-row-tight">
                                    <div class="seat seat-right" data-seat="113" data-wagon="2">113</div>
                                    <div class="seat seat-right" data-seat="114" data-wagon="2">114</div>
                                    <div class="seat seat-right" data-seat="115" data-wagon="2">115</div>
                                    <div class="seat seat-right" data-seat="116" data-wagon="2">116</div>
                                    <div class="seat seat-right" data-seat="117" data-wagon="2">117</div>
                                    <div class="seat seat-right" data-seat="118" data-wagon="2">118</div>
                                </div>
                                <div class="seat-row-tight">
                                    <div class="seat seat-right" data-seat="119" data-wagon="2">119</div>
                                    <div class="seat seat-right" data-seat="120" data-wagon="2">120</div>
                                    <div class="seat seat-right" data-seat="121" data-wagon="2">121</div>
                                    <div class="seat seat-right" data-seat="122" data-wagon="2">122</div>
                                    <div class="seat seat-right" data-seat="123" data-wagon="2">123</div>
                                    <div class="seat seat-right" data-seat="124" data-wagon="2">124</div>
                                </div>
                            </div>
                        </div>
                        <div class="aisle-horizontal"></div>
                        <div class="wagon-super-row">
                            <div class="seat-block">
                                <div class="seat-row-tight">
                                    <div class="seat seat-left" data-seat="125" data-wagon="2">125</div>
                                    <div class="seat seat-left" data-seat="126" data-wagon="2">126</div>
                                    <div class="seat seat-left" data-seat="127" data-wagon="2">127</div>
                                    <div class="seat seat-left" data-seat="128" data-wagon="2">128</div>
                                    <div class="seat seat-left" data-seat="129" data-wagon="2">129</div>
                                    <div class="seat seat-left" data-seat="130" data-wagon="2">130</div>
                                </div>
                                <div class="seat-row-tight">
                                    <div class="seat seat-left" data-seat="131" data-wagon="2">131</div>
                                    <div class="seat seat-left" data-seat="132" data-wagon="2">132</div>
                                    <div class="seat seat-left" data-seat="133" data-wagon="2">133</div>
                                    <div class="seat seat-left" data-seat="134" data-wagon="2">134</div>
                                    <div class="seat seat-left" data-seat="135" data-wagon="2">135</div>
                                    <div class="seat seat-left" data-seat="136" data-wagon="2">136</div>
                                </div>
                            </div>
                            <div class="long-table">MESA</div>
                            <div class="seat-block">
                                <div class="seat-row-tight">
                                    <div class="seat seat-right" data-seat="137" data-wagon="2">137</div>
                                    <div class="seat seat-right" data-seat="138" data-wagon="2">138</div>
                                    <div class="seat seat-right" data-seat="139" data-wagon="2">139</div>
                                    <div class="seat seat-right" data-seat="140" data-wagon="2">140</div>
                                    <div class="seat seat-right" data-seat="141" data-wagon="2">141</div>
                                    <div class="seat seat-right" data-seat="142" data-wagon="2">142</div>
                                </div>
                                <div class="seat-row-tight">
                                    <div class="seat seat-right" data-seat="143" data-wagon="2">143</div>
                                    <div class="seat seat-right" data-seat="144" data-wagon="2">144</div>
                                    <div class="seat seat-right" data-seat="145" data-wagon="2">145</div>
                                    <div class="seat seat-right" data-seat="146" data-wagon="2">146</div>
                                    <div class="seat seat-right" data-seat="147" data-wagon="2">147</div>
                                    <div class="seat seat-right" data-seat="148" data-wagon="2">148</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wagon 3 - Standard -->
                <div id="wagon3" class="wagon-body">
                    <div class="info-message">Segunda Clase</div>
                    <div class="wagon-layout">
                        <div class="wagon-super-row">
                            <div class="seat-block">
                                <div class="seat-row-tight">
                                    <div class="seat seat-left" data-seat="201" data-wagon="3">201</div>
                                    <div class="seat seat-left" data-seat="202" data-wagon="3">202</div>
                                    <div class="seat seat-left" data-seat="203" data-wagon="3">203</div>
                                    <div class="seat seat-left" data-seat="204" data-wagon="3">204</div>
                                    <div class="seat seat-left" data-seat="205" data-wagon="3">205</div>
                                    <div class="seat seat-left" data-seat="206" data-wagon="3">206</div>
                                </div>
                                <div class="seat-row-tight">
                                    <div class="seat seat-left" data-seat="207" data-wagon="3">207</div>
                                    <div class="seat seat-left" data-seat="208" data-wagon="3">208</div>
                                    <div class="seat seat-left" data-seat="209" data-wagon="3">209</div>
                                    <div class="seat seat-left" data-seat="210" data-wagon="3">210</div>
                                    <div class="seat seat-left" data-seat="211" data-wagon="3">211</div>
                                    <div class="seat seat-left" data-seat="212" data-wagon="3">212</div>
                                </div>
                            </div>
                            <div class="long-table">MESA</div>
                            <div class="seat-block">
                                <div class="seat-row-tight">
                                    <div class="seat seat-right" data-seat="213" data-wagon="3">213</div>
                                    <div class="seat seat-right" data-seat="214" data-wagon="3">214</div>
                                    <div class="seat seat-right" data-seat="215" data-wagon="3">215</div>
                                    <div class="seat seat-right" data-seat="216" data-wagon="3">216</div>
                                    <div class="seat seat-right" data-seat="217" data-wagon="3">217</div>
                                    <div class="seat seat-right" data-seat="218" data-wagon="3">218</div>
                                </div>
                                <div class="seat-row-tight">
                                    <div class="seat seat-right" data-seat="219" data-wagon="3">219</div>
                                    <div class="seat seat-right" data-seat="220" data-wagon="3">220</div>
                                    <div class="seat seat-right" data-seat="221" data-wagon="3">221</div>
                                    <div class="seat seat-right" data-seat="222" data-wagon="3">222</div>
                                    <div class="seat seat-right" data-seat="223" data-wagon="3">223</div>
                                    <div class="seat seat-right" data-seat="224" data-wagon="3">224</div>
                                </div>
                            </div>
                        </div>
                        <div class="aisle-horizontal"></div>
                        <div class="wagon-super-row">
                            <div class="seat-block">
                                <div class="seat-row-tight">
                                    <div class="seat seat-left" data-seat="225" data-wagon="3">225</div>
                                    <div class="seat seat-left" data-seat="226" data-wagon="3">226</div>
                                    <div class="seat seat-left" data-seat="227" data-wagon="3">227</div>
                                    <div class="seat seat-left" data-seat="228" data-wagon="3">228</div>
                                    <div class="seat seat-left" data-seat="229" data-wagon="3">229</div>
                                    <div class="seat seat-left" data-seat="230" data-wagon="3">230</div>
                                </div>
                                <div class="seat-row-tight">
                                    <div class="seat seat-left" data-seat="231" data-wagon="3">231</div>
                                    <div class="seat seat-left" data-seat="232" data-wagon="3">232</div>
                                    <div class="seat seat-left" data-seat="233" data-wagon="3">233</div>
                                    <div class="seat seat-left" data-seat="234" data-wagon="3">234</div>
                                    <div class="seat seat-left" data-seat="235" data-wagon="3">235</div>
                                    <div class="seat seat-left" data-seat="236" data-wagon="3">236</div>
                                </div>
                            </div>
                            <div class="long-table">MESA</div>
                            <div class="seat-block">
                                <div class="seat-row-tight">
                                    <div class="seat seat-right" data-seat="237" data-wagon="3">237</div>
                                    <div class="seat seat-right" data-seat="238" data-wagon="3">238</div>
                                    <div class="seat seat-right" data-seat="239" data-wagon="3">239</div>
                                    <div class="seat seat-right" data-seat="240" data-wagon="3">240</div>
                                    <div class="seat seat-right" data-seat="241" data-wagon="3">241</div>
                                    <div class="seat seat-right" data-seat="242" data-wagon="3">242</div>
                                </div>
                                <div class="seat-row-tight">
                                    <div class="seat seat-right" data-seat="243" data-wagon="3">243</div>
                                    <div class="seat seat-right" data-seat="244" data-wagon="3">244</div>
                                    <div class="seat seat-right" data-seat="245" data-wagon="3">245</div>
                                    <div class="seat seat-right" data-seat="246" data-wagon="3">246</div>
                                    <div class="seat seat-right" data-seat="247" data-wagon="3">247</div>
                                    <div class="seat seat-right" data-seat="248" data-wagon="3">248</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tail-indicator">Cola del tren</div>
            </div>
            
            <div class="booking-footer">
                <div class="selection-info">
                    <span>Asiento seleccionado:</span> <strong id="displaySeat">Ninguno</strong>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn-next" type="button" onclick="volverASearch()" style="background: #666;">Volver</button>
                    <button class="btn-next" id="btnToPayment" disabled onclick="irAPaso(3)">Continuar a Datos de Compra</button>
                </div>
            </div>
        </section>

        <!-- Paso 3: Datos de compra -->
        <section id="sectionPayment" class="booking-section hidden">
            <div class="step-indicator">
                <div class="step completed" id="step1"><span class="step-num">1</span> Buscar Viaje</div>
                <div class="step completed" id="step2"><span class="step-num">2</span> Seleccionar Asiento</div>
                <div class="step active" id="step3"><span class="step-num">3</span> Datos de Compra</div>
            </div>

            <div class="payment-container">
                <div class="payment-header">
                    <h3><i class="fa-solid fa-list-check"></i> Resumen y Descuentos</h3>
                </div>
                
                <div class="trip-details">
                    <p><strong>Viaje:</strong> <span id="summaryViaje">--</span></p>
                    <p><strong>Asiento:</strong> <span id="summarySeat">--</span></p>
                    <p><strong>Precio base:</strong> <span id="summaryBasePrice">--</span> €</p>
                </div>

                <div class="discounts-section">
                    <label for="descuento">Descuento (%)</label>
                    <input type="number" id="descuento" min="0" max="100" value="0">
                    <span id="promoMsg" style="display: block; margin-top: 5px; font-size: 0.9rem;"></span>
                </div>

                <div class="summary-box">
                    <p style="margin: 0;">Total a pagar: <strong id="precioFinal">--</strong> €</p>
                </div>

                <h4 style="color: #0a2a66; margin-bottom: 15px;"><i class="fa-solid fa-file-invoice"></i> Datos de Facturación</h4>
                
                <form id="formDatosCompra">
                    <div class="form-group-full">
                        <label for="facturaNombre">Nombre completo</label>
                        <input type="text" id="facturaNombre" name="facturaNombre" value="<?= htmlspecialchars($nombre_cliente) ?>" required>
                    </div>
                    <div class="form-group-full">
                        <label for="facturaNif">NIF/CIF</label>
                        <input type="text" id="facturaNif" name="facturaNif" value="<?= htmlspecialchars($dni_cliente) ?>" required>
                    </div>
                    <div class="form-group-full">
                        <label for="facturaDireccion">Dirección</label>
                        <input type="text" id="facturaDireccion" name="facturaDireccion" placeholder="Calle, número, ciudad" required>
                    </div>
                    <div class="form-group-full">
                        <label for="facturaEmail">Email</label>
                        <input type="email" id="facturaEmail" name="facturaEmail" value="<?= htmlspecialchars($email_cliente) ?>" required>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="button" onclick="volverASeats()" style="padding: 12px 25px; background: #666; color: white; border: none; border-radius: 6px; cursor: pointer;">Volver</button>
                        <button type="submit" class="btn-next" style="flex: 1;">Confirmar Compra</button>
                    </div>
                </form>

                <div id="compraResultado"></div>
            </div>
        </section>

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
                <a href="aviso_legal.php"><i class="fa-solid fa-scale-balanced"></i> <span data-i18n="footer_aviso">Aviso legal</span></a>
                <a href="politica_privacidad.php"><i class="fa-solid fa-user-shield"></i> <span data-i18n="footer_privacidad">Privacidad</span></a>
                <a href="politica_cookies.php"><i class="fa-solid fa-cookie-bite"></i> <span data-i18n="footer_cookies">Cookies</span></a>
                <a href="terminos_y_condiciones.php"><i class="fa-solid fa-file-contract"></i> <span data-i18n="footer_terminos">Términos y condiciones</span></a>
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
    <script src="scripts/session_menu.js"></script>
    <script>
        // Datos del cliente desde PHP
        const idCliente = <?= $id_cliente ?>;
        const nombreCliente = "<?= htmlspecialchars($nombre_cliente) ?>";
        const dniCliente = "<?= htmlspecialchars($dni_cliente) ?>";
        const emailCliente = "<?= htmlspecialchars($email_cliente) ?>";

        // Variables globales
        let viajeSeleccionado = null;
        let asientoSeleccionado = null;
        let origenesDb = [];
        let destinosDb = [];
        let asientosOcupados = [];

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            // Establecer fecha mínima a hoy
            const hoy = new Date().toISOString().split('T')[0];
            document.getElementById('inputFecha').min = hoy;
            document.getElementById('inputFecha').value = hoy;

            // Cargar orígenes y destinos
            cargarOrigenesDestinos();

            // Evento de búsqueda de viaje
            document.getElementById('formBuscarViaje').addEventListener('submit', buscarViajes);

            // Evento de cambio de origen para actualizar destinos
            document.getElementById('inputOrigen').addEventListener('change', actualizarDestinos);

            // Evento de descuento
            document.getElementById('descuento').addEventListener('input', actualizarPrecio);

            // Evento de formulario de compra
            document.getElementById('formDatosCompra').addEventListener('submit', confirmarCompra);

            // Eventos de asientos
            inicializarAsientos();
        });

        // Cargar orígenes y destinos
        function cargarOrigenesDestinos() {
            fetch('php/api_origenes_destinos.php')
                .then(res => res.json())
                .then(data => {
                    if (data.exito) {
                        origenesDb = data.origenes;
                        destinosDb = data.destinos;
                        inicializarSugerencias();
                    }
                })
                .catch(err => console.error('Error cargando ciudades:', err));
        }

        // Inicializar sugerencias
        function inicializarSugerencias() {
            const inputOrigen = document.getElementById('inputOrigen');
            const inputDestino = document.getElementById('inputDestino');
            const suggOrigen = document.getElementById('suggOrigen');
            const suggDestino = document.getElementById('suggDestino');

            inputOrigen.addEventListener('input', () => mostrarSugerencias(inputOrigen, suggOrigen, origenesDb));
            inputOrigen.addEventListener('focus', () => mostrarSugerencias(inputOrigen, suggOrigen, origenesDb, true));

            inputDestino.addEventListener('input', () => mostrarSugerencias(inputDestino, suggDestino, destinosDb));
            inputDestino.addEventListener('focus', () => mostrarSugerencias(inputDestino, suggDestino, destinosDb, true));

            document.addEventListener('click', function(e) {
                if (e.target !== inputOrigen) suggOrigen.style.display = 'none';
                if (e.target !== inputDestino) suggDestino.style.display = 'none';
            });
        }

        // Mostrar sugerencias
        function mostrarSugerencias(input, container, lista, mostrarTodas = false) {
            const valor = input.value.toLowerCase();
            container.innerHTML = '';
            let filtrados;
            if (mostrarTodas || (input === document.activeElement && valor === '')) {
                filtrados = lista;
            } else {
                filtrados = lista.filter(ciudad => ciudad.toLowerCase().includes(valor));
            }
            if (filtrados.length === 0) {
                container.style.display = 'none';
                return;
            }
            filtrados.forEach(ciudad => {
                const div = document.createElement('div');
                div.textContent = ciudad;
                div.style.padding = '10px';
                div.style.cursor = 'pointer';
                div.style.borderBottom = '1px solid #eee';
                div.addEventListener('click', () => {
                    input.value = ciudad;
                    container.style.display = 'none';
                    // Si es el origen, actualizar destinos
                    if (input.id === 'inputOrigen') {
                        actualizarDestinos();
                    }
                });
                div.addEventListener('mouseenter', () => div.style.backgroundColor = '#f4f6f8');
                div.addEventListener('mouseleave', () => div.style.backgroundColor = 'white');
                container.appendChild(div);
            });
            container.style.display = 'block';
            container.style.position = 'absolute';
            container.style.backgroundColor = 'white';
            container.style.border = '1px solid #ccc';
            container.style.width = input.offsetWidth + 'px';
            container.style.zIndex = '1000';
            container.style.maxHeight = '200px';
            container.style.overflowY = 'auto';
        }

        // Actualizar destinos según origen
        function actualizarDestinos() {
            const origen = document.getElementById('inputOrigen').value;
            if (!origen) return;

            fetch('php/api_destinos_por_origen.php?origen=' + encodeURIComponent(origen))
                .then(res => res.json())
                .then(data => {
                    if (data.exito) {
                        destinosDb = data.destinos;
                    }
                })
                .catch(err => console.error('Error cargando destinos:', err));
        }

        // Buscar viajes
        function buscarViajes(e) {
            e.preventDefault();
            const origen = document.getElementById('inputOrigen').value;
            const destino = document.getElementById('inputDestino').value;
            const fecha = document.getElementById('inputFecha').value;

            if (!origen || !destino || !fecha) {
                alert('Por favor, completa todos los campos.');
                return;
            }

            fetch('php/api_buscar_viajes.php?origen=' + encodeURIComponent(origen) + '&destino=' + encodeURIComponent(destino) + '&fecha=' + encodeURIComponent(fecha))
                .then(res => res.json())
                .then(data => {
                    const resultados = document.getElementById('resultadosViajes');
                    if (data.error || !data.viajes || data.viajes.length === 0) {
                        resultados.innerHTML = '<p style="color: #c00; text-align: center;">No hay viajes disponibles para esta ruta en la fecha seleccionada.</p>';
                        return;
                    }

                    window._viajes = data.viajes;
                    resultados.innerHTML = '<h4 style="color: #0a2a66; margin-bottom: 15px;">Viajes disponibles</h4>' + 
                        data.viajes.map((v, i) => `
                            <div class="trip-card" onclick="seleccionarViaje(${i})">
                                <div class="trip-card-header">
                                    <h4>${v.origen} → ${v.destino}</h4>
                                    <span class="price">${v.precio_base}€</span>
                                </div>
                                <div class="trip-card-details">
                                    <span><i class="fa-solid fa-calendar"></i> ${v.fecha}</span>
                                    <span><i class="fa-solid fa-clock"></i> ${v.hora_salida} - ${v.hora_llegada}</span>
                                    <span><i class="fa-solid fa-train"></i> ${v.tipo_tren}</span>
                                </div>
                            </div>
                        `).join('');
                })
                .catch(err => {
                    document.getElementById('resultadosViajes').innerHTML = '<p style="color: #c00;">Error al buscar viajes.</p>';
                });
        }

        // Seleccionar viaje
        function seleccionarViaje(index) {
            const viajes = window._viajes || [];
            viajeSeleccionado = viajes[index];

            // Marcar visualmente
            document.querySelectorAll('.trip-card').forEach((card, i) => {
                card.classList.toggle('selected', i === index);
            });

            // Cargar asientos ocupados
            cargarAsientosOcupados(viajeSeleccionado.id_viaje);
        }

        // Cargar asientos ocupados
        function cargarAsientosOcupados(id_viaje) {
            fetch('php/api_asientos_ocupados.php?id_viaje=' + id_viaje)
                .then(res => res.json())
                .then(data => {
                    asientosOcupados = data.asientos || [];
                    // Mostrar botón para ir a asientos
                    const resultados = document.getElementById('resultadosViajes');
                    resultados.innerHTML += `
                        <div style="text-align: center; margin-top: 20px;">
                            <button class="btn-next" onclick="irAPaso(2)">Seleccionar Asiento</button>
                        </div>
                    `;
                })
                .catch(err => console.error('Error cargando asientos:', err));
        }

        // Inicializar asientos
        function inicializarAsientos() {
            document.querySelectorAll('.seat').forEach(seat => {
                seat.addEventListener('click', function() {
                    if (this.classList.contains('occupied')) return;
                    
                    // Deseleccionar anterior
                    document.querySelectorAll('.seat.selected').forEach(s => s.classList.remove('selected'));
                    
                    // Seleccionar nuevo
                    this.classList.add('selected');
                    asientoSeleccionado = this.dataset.seat;
                    
                    // Actualizar display
                    document.getElementById('displaySeat').textContent = this.textContent;
                    document.getElementById('btnToPayment').disabled = false;
                });
            });
        }

        // Cambiar vagón
        function cambiarVagon(direction) {
            const wagons = document.querySelectorAll('.wagon-body');
            let currentIndex = 0;
            
            wagons.forEach((w, i) => {
                if (w.classList.contains('active')) currentIndex = i;
            });

            wagons[currentIndex].classList.remove('active');
            let newIndex = currentIndex + direction;
            if (newIndex < 0) newIndex = wagons.length - 1;
            if (newIndex >= wagons.length) newIndex = 0;
            
            wagons[newIndex].classList.add('active');
            document.getElementById('currentWagonNum').textContent = newIndex + 1;
        }

        // Actualizar precio
        function actualizarPrecio() {
            if (!viajeSeleccionado) return;
            
            const base = parseFloat(viajeSeleccionado.precio_base);
            const desc = parseFloat(document.getElementById('descuento').value) || 0;
            const final = Math.max(0, base - (base * desc / 100));
            
            document.getElementById('precioFinal').textContent = final.toFixed(2);
            
            const promoMsg = document.getElementById('promoMsg');
            if (desc > 0) {
                promoMsg.textContent = `Descuento aplicado: -${desc}%`;
                promoMsg.style.color = '#17632A';
            } else {
                promoMsg.textContent = '';
            }
        }

        // Ir a paso
        function irAPaso(paso) {
            if (paso === 2 && !viajeSeleccionado) {
                alert('Por favor, selecciona un viaje primero.');
                return;
            }

            document.getElementById('sectionSearch').classList.toggle('hidden', paso !== 1);
            document.getElementById('sectionSeats').classList.toggle('hidden', paso !== 2);
            document.getElementById('sectionPayment').classList.toggle('hidden', paso !== 3);

            // Actualizar indicadores de paso
            document.getElementById('step1').className = paso >= 1 ? 'step completed' : 'step';
            document.getElementById('step2').className = paso >= 2 ? 'step completed' : (paso === 2 ? 'step active' : 'step');
            document.getElementById('step3').className = paso === 3 ? 'step active' : 'step';

            if (paso === 2) {
                document.getElementById('lblTrenSeleccionado').textContent = viajeSeleccionado.tipo_tren;
                // Marcar asientos ocupados
                marcarAsientosOcupados();
            }

            if (paso === 3) {
                if (!asientoSeleccionado) {
                    alert('Por favor, selecciona un asiento.');
                    irAPaso(2);
                    return;
                }
                // Mostrar resumen
                document.getElementById('summaryViaje').textContent = `${viajeSeleccionado.origen} → ${viajeSeleccionado.destino} (${viajeSeleccionado.fecha} ${viajeSeleccionado.hora_salida})`;
                document.getElementById('summarySeat').textContent = asientoSeleccionado;
                document.getElementById('summaryBasePrice').textContent = viajeSeleccionado.precio_base;
                actualizarPrecio();
            }
        }

        // Marcar asientos ocupados
        function marcarAsientosOcupados() {
            document.querySelectorAll('.seat').forEach(seat => {
                const num = seat.dataset.seat;
                if (asientosOcupados.includes(parseInt(num))) {
                    seat.classList.add('occupied');
                } else {
                    seat.classList.remove('occupied');
                }
            });
        }

        // Volver a búsqueda
        function volverASearch() {
            irAPaso(1);
        }

        // Volver a asientos
        function volverASeats() {
            irAPaso(2);
        }

        // Confirmar compra
        function confirmarCompra(e) {
            e.preventDefault();
            
            if (!viajeSeleccionado || !asientoSeleccionado) {
                alert('Por favor, completa todos los pasos.');
                return;
            }

            const descuento = parseFloat(document.getElementById('descuento').value) || 0;
            const facturaNombre = document.getElementById('facturaNombre').value;
            const facturaNif = document.getElementById('facturaNif').value;
            const facturaDireccion = document.getElementById('facturaDireccion').value;
            const facturaEmail = document.getElementById('facturaEmail').value;

            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Procesando...';

            fetch('php/api_comprar_billete_vendedor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    id_usuario: idCliente,
                    id_viaje: viajeSeleccionado.id_viaje,
                    numero_asiento: parseInt(asientoSeleccionado),
                    descuento: descuento,
                    facturaNombre: facturaNombre,
                    facturaNif: facturaNif,
                    facturaDireccion: facturaDireccion,
                    facturaEmail: facturaEmail
                })
            })
            .then(res => res.json())
            .then(data => {
                const resultado = document.getElementById('compraResultado');
                if (data.ok) {
                    const mensaje = data.codigo_billete 
                        ? `<div class="result-message success">
                            <h3>¡Compra realizada correctamente!</h3>
                            <p><strong>Localizador:</strong> ${data.codigo_billete}</p>
                            <p>Este localizador es necesario para gestionar el billete (modificación, cancelación o factura).</p>
                            <button class="btn-next" onclick="window.location.href='vendedor.php'" style="margin-top: 15px;">Volver al panel</button>
                           </div>`
                        : '<div class="result-message success"><h3>¡Compra realizada correctamente!</h3></div>';
                    resultado.innerHTML = mensaje;
                    e.target.style.display = 'none';
                } else {
                    resultado.innerHTML = `<div class="result-message error"><strong>Error:</strong> ${data.error}</div>`;
                    btn.disabled = false;
                    btn.textContent = 'Confirmar Compra';
                }
            })
            .catch(err => {
                document.getElementById('compraResultado').innerHTML = '<div class="result-message error">Error de conexión.</div>';
                btn.disabled = false;
                btn.textContent = 'Confirmar Compra';
            });
        }
    </script>
</body>
</html>