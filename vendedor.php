<?php
session_start();

require_once __DIR__ . '/php/Conexion.php';
require_once __DIR__ . '/php/auth_helpers.php';

$usuario = $_SESSION['usuario'] ?? null;

// 1. Verificamos que sea empleado a nivel general (esto viene de la tabla USUARIO)
if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'empleado') {
    header('Location: employee_login.php?error=no_autorizado');
    exit;
}

$idEmpleado = null;
$region = 'Sin asignar';
$comision = '0.00';
$esVendedor = false;

try {
    $pdo = (new Conexion())->conectar();
    if ($pdo) {
        // 2. Buscamos sus datos específicos en la tabla EMPLEADO y VENDEDOR
        $stmt = $pdo->prepare(
            "SELECT e.id_empleado, e.tipo_empleado, v.region, v.comision_porcentaje
             FROM empleado e
             LEFT JOIN vendedor v ON v.id_empleado = e.id_empleado
             WHERE e.id_usuario = :id_usuario
             LIMIT 1"
        );
        $stmt->execute([':id_usuario' => (int)$usuario['id_usuario']]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fila) {
            $idEmpleado = $fila['id_empleado'] ?? null;
            $region = $fila['region'] ?? 'Sin asignar';
            $comision = $fila['comision_porcentaje'] ?? '0.00';
            
            // Comprobamos si en la base de datos realmente figura como vendedor
            if (($fila['tipo_empleado'] ?? '') === 'vendedor') {
                $esVendedor = true;
            }
        }
    }
} catch (PDOException $e) {
    // Si hay error de BD, el script continuará y echará al usuario por seguridad
}

// 3. Expulsar si NO es vendedor y NO es administrador
if (!$esVendedor && !trainwebEsAdministrador($usuario)) {
    header('Location: ' . trainwebRutaPorRol($usuario));
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
    <title>TrainWeb - Panel de Vendedor</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/vendedor.css">
    <style>
        /* Estilos adicionales para organizar las secciones de operaciones */
        .section-subtitle {
            font-size: 1.1rem;
            color: #0a2a66;
            margin: 20px 0 10px 0;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
        }
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .admin-btn {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: #333;
            font-weight: bold;
        }
        .admin-btn i { font-size: 1.8rem; color: #0a2a66; }
        .admin-btn:hover { background: #e9ecef; border-color: #0a2a66; transform: translateY(-3px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .badge-info { background: #17a2b8; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; margin-left: 10px; }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo">
            <i class="fa-solid fa-train"></i> TrainWeb 
            <span style="font-size: 0.8rem; opacity: 0.8; font-weight: normal; margin-left: 10px; vertical-align: middle; position: relative; bottom: 2px;">| Portal Vendedor</span>
        </div>
        <nav class="nav">
            <div class="user-display" style="color: white; margin-right: 20px; font-weight: 500; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-id-badge" style="font-size: 1.2rem;"></i> 
                <?= htmlspecialchars($nombreCompleto) ?> 
                <span class="badge-info"><?= htmlspecialchars($region) ?></span>
            </div>
            <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
        </nav>
    </header>

    <div class="dashboard-container">
        
        <section class="card search-section">
            <h2><i class="fa-solid fa-users"></i> Buscar Cliente</h2>
            <div class="search-box">
                <input type="text" id="dniInput" placeholder="Introduce el DNI o correo...">
                <button class="btn-search" id="btnBuscar"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
            <div id="clientInfo" class="client-profile hidden">
                <div class="profile-header">
                    <div class="avatar"><i class="fa-solid fa-user"></i></div>
                    <div>
                        <h3 id="clientName">---</h3>
                        <p class="text-muted" id="clientDni">DNI: <span id="clientDniValue">---</span></p>
                    </div>
                </div>
                <hr>
                <p><i class="fa-solid fa-envelope"></i> <span id="clientEmail">---</span></p>
                <p><i class="fa-solid fa-phone"></i> <span id="clientPhone">---</span></p>
                <hr>
                <p class="payment-method"><i class="fa-brands fa-cc-visa"></i> VISA terminada en <span id="clientCard">****</span></p>
            </div>
            <div id="clientError" class="error-msg hidden">
                <i class="fa-solid fa-circle-exclamation"></i> Cliente no encontrado.
            </div>
        </section>

        <section class="card operations-section" id="operationsPanel">
            <h2><i class="fa-solid fa-desktop"></i> Consola de Control</h2>
            
            <h3 class="section-subtitle"><i class="fa-solid fa-cogs"></i> Administración del Sistema</h3>
            <div class="admin-grid">
                <button class="admin-btn" onclick="window.location.href='php/gestionar_rutas.php'">
                    <i class="fa-solid fa-route"></i>
                    <span>Gestionar Rutas</span>
                </button>
                <button class="admin-btn" onclick="window.location.href='php/gestionar_viajes.php'">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Gestionar Viajes</span>
                </button>
                <button class="admin-btn" onclick="window.location.href='php/gestionar_ofertas.php'">
                    <i class="fa-solid fa-tags"></i>
                    <span>Ofertas y Abonos</span>
                </button>
            </div>

            <h3 class="section-subtitle"><i class="fa-solid fa-headset"></i> Atención al Cliente (Requiere buscar cliente)</h3>
            <div class="actions-grid disabled" id="clientActionsBox">
                <button class="action-btn" id="btnIniciarVenta"><i class="fa-solid fa-cart-plus"></i><span>Nueva venta</span></button>
                <!-- Modal de venta vendedor -->
                <style>
                    .modal-vendedor {
                        position: fixed; z-index: 1000; left: 0; top: 0; width: 100vw; height: 100vh;
                        background: rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;
                    }
                    .modal-vendedor .modal-content {
                        background: #fff; border-radius: 12px; padding: 32px 28px 24px 28px; min-width: 380px; max-width: 95vw; box-shadow: 0 8px 32px rgba(0,0,0,0.18);
                        position: relative;
                    }
                    .modal-vendedor .close-modal {
                        position: absolute; right: 18px; top: 12px; font-size: 2rem; color: #888; cursor: pointer;
                    }
                    .modal-vendedor label { display: block; margin: 10px 0 6px 0; }
                    .modal-vendedor input, .modal-vendedor select, .modal-vendedor button { margin-bottom: 10px; }
                    .modal-vendedor h2 { margin-top: 0; color: #0a2a66; }
                    .modal-vendedor .asientos-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 6px; margin: 18px 0; }
                    .modal-vendedor .asiento-btn {
                        background: #e9ecef; border: 1px solid #bbb; border-radius: 6px; padding: 8px 0; cursor: pointer;
                        font-weight: bold; color: #0a2a66; transition: background 0.2s, border 0.2s;
                    }
                    .modal-vendedor .asiento-btn.selected { background: #0a2a66; color: #fff; border-color: #0a2a66; }
                    .modal-vendedor .asiento-btn:disabled { background: #eee; color: #aaa; border-color: #ddd; cursor: not-allowed; }
                </style>
                <div id="modalVentaVendedor" class="modal-vendedor hidden">
                    <div class="modal-content">
                        <span class="close-modal" id="cerrarVentaVendedor">&times;</span>
                        <h2>Venta de billete para cliente</h2>
                        <div id="ventaVendedorPaso1">
                            <h3>Buscar viajes</h3>
                            <form id="formBuscarViajes">
                                <label>Origen: <input type="text" name="origen" required></label>
                                <label>Destino: <input type="text" name="destino" required></label>
                                <label>Fecha: <input type="date" name="fecha" required></label>
                                <label>Pasajeros: <input type="number" name="pasajeros" min="1" max="10" value="1" required></label>
                                <button type="submit">Buscar viajes</button>
                            </form>
                            <div id="resultadosViajes"></div>
                        </div>
                        <div id="ventaVendedorPaso2" class="hidden">
                            <h3>Selecciona viaje y asiento</h3>
                            <form id="formSeleccionAsiento">
                                <div id="infoViajeSeleccionado"></div>
                                <div id="asientosGrid" class="asientos-grid"></div>
                                <input type="hidden" name="numero_asiento" id="inputAsientoSeleccionado" required>
                                <button type="button" id="btnPasoDatosCompra">Siguiente: Datos de compra</button>
                            </form>
                        </div>
                        <div id="ventaVendedorPaso3" class="hidden">
                            <h3>Datos de compra y descuento</h3>
                            <form id="formDatosCompra">
                                <label>Precio base: <span id="precioBaseCompra">-</span> €</label><br>
                                <label>Descuento (%): <input type="number" id="descuentoCompra" min="0" max="100" value="0"></label><br>
                                <label>Precio final: <span id="precioFinalCompra">-</span> €</label><br>
                                <button type="submit">Confirmar compra</button>
                            </form>
                            <div id="compraResultado"></div>
                        </div>
                    </div>
                </div>
                <script src="js/venta_vendedor.js"></script>
                <button class="action-btn" onclick="openModal('cambio')"><i class="fa-solid fa-repeat"></i><span>Cambio billete</span></button>
                <button class="action-btn" onclick="alert('Funcionalidad en construccion')"><i class="fa-solid fa-ban"></i><span>Cancelar reserva</span></button>
                <button class="action-btn" onclick="alert('Factura enviada')"><i class="fa-solid fa-file-invoice"></i><span>Reenviar factura</span></button>
            </div>
            
            <div class="mini-list-container disabled" id="recentTripsBox">
                <h4>Últimos viajes del cliente</h4>
                <ul class="mini-list" id="recentTrips"></ul>
            </div>
        </section>
    </div>

    <script>
        // Simulador de búsqueda
        function searchClient() {
            const val = document.getElementById('searchInput').value;
            const profile = document.getElementById('clientProfile');
            const error = document.getElementById('clientError');
            const clientActions = document.getElementById('clientActionsBox');
            const recentTrips = document.getElementById('recentTripsBox');

            if(val.trim().length > 2) {
                error.classList.add('hidden');
                profile.classList.remove('hidden');
                
                // Rellenamos datos simulados
                document.getElementById('clientName').innerText = 'Carlos García';
                document.getElementById('clientDni').innerText = 'DNI: ' + val.toUpperCase();
                document.getElementById('clientEmail').innerText = 'carlos@example.com';
                document.getElementById('clientPhone').innerText = '+34 600 123 456';
                document.getElementById('clientCard').innerText = '4092';
                
                // Habilitamos las acciones que requieren cliente
                clientActions.classList.remove('disabled');
                recentTrips.classList.remove('disabled');
                
                document.getElementById('recentTrips').innerHTML = `
                    <li><span>MAD - BCN</span> <span style="color:#17632A">Completado</span></li>
                    <li><span>BCN - VAL</span> <span style="color:#f39c12">Pendiente</span></li>
                `;
            } else {
                profile.classList.add('hidden');
                error.classList.remove('hidden');
                
                // Deshabilitamos las acciones si no hay cliente válido
                clientActions.classList.add('disabled');
                recentTrips.classList.add('disabled');
            }
        }

        function openModal(type) {
            const modal = document.getElementById('actionModal');
            const title = document.getElementById('modalTitle');
            if(type === 'venta') title.innerText = 'Nueva Venta en Taquilla';
            if(type === 'cambio') title.innerText = 'Cambio o Devolución de Billete';
            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('actionModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('actionModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</body>
<script src="js/vendedor.js"></script>
</html>
