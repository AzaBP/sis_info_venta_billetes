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
                <button class="action-btn" id="btnIniciarVenta" onclick="iniciarNuevaVenta()"><i class="fa-solid fa-cart-plus"></i><span>Nueva venta</span></button>
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
                            <div class="progress-bar-container" style="margin-bottom: 18px;">
                                <div class="step completed"><span class="step-num">1</span> Viaje</div>
                                <div class="step completed"><span class="step-num">2</span> Asiento</div>
                                <div class="step active"><span class="step-num">3</span> Resumen y compra</div>
                            </div>
                            <div class="payment-container" style="max-width: 500px; margin: 0 auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                                <div class="payment-header" style="border-bottom: none; margin-bottom: 10px;">
                                    <h3 style="color:#0a2a66;"><i class="fa-solid fa-list-check"></i> Resumen y Descuentos</h3>
                                </div>
                                <div class="trip-details" style="margin-bottom: 18px; padding: 12px; background: #f4f6f8; border-radius: 8px; font-size: 1.05rem;">
                                    <p style="margin: 5px 0;"><strong>Viaje:</strong> <span id="resumenViajeVendedor">--</span></p>
                                    <p style="margin: 5px 0;"><strong>Asiento:</strong> <span id="resumenAsientoVendedor">--</span></p>
                                    <p style="margin: 5px 0;"><strong>Precio base:</strong> <span id="precioBaseCompra">-</span> €</p>
                                </div>
                                <div class="discounts-section" style="margin-bottom: 18px;">
                                    <label for="descuentoCompra" style="font-weight: bold;">Descuento (%)</label>
                                    <input type="number" id="descuentoCompra" min="0" max="100" value="0" style="width: 80px; margin-left: 10px; border-radius: 5px; border: 1px solid #ccc; padding: 4px 8px;">
                                    <span id="promoMsgVendedor" style="display: block; margin-top: 5px; font-size: 0.9rem;"></span>
                                </div>
                                <div class="summary-box" style="margin-bottom: 18px; font-size: 1.15rem; background: #e9ecef; padding: 12px; border-radius: 8px; text-align: center;">
                                    <p style="margin: 0;">Total a pagar: <strong id="precioFinalCompra" style="color: #0a2a66; font-size: 1.3rem;">-</strong> €</p>
                                </div>
                                <form id="formDatosCompra" autocomplete="off">
                                    <div class="form-group full-width">
                                        <label for="facturaNombre">Nombre completo</label>
                                        <input type="text" id="facturaNombre" name="facturaNombre" required placeholder="Ej: Juan Pérez" style="width:100%;padding:8px;border-radius:5px;border:1px solid #ccc;">
                                    </div>
                                    <div class="form-group full-width">
                                        <label for="facturaNif">NIF/CIF</label>
                                        <input type="text" id="facturaNif" name="facturaNif" required placeholder="Ej: 12345678A" style="width:100%;padding:8px;border-radius:5px;border:1px solid #ccc;">
                                    </div>
                                    <div class="form-group full-width">
                                        <label for="facturaDireccion">Dirección</label>
                                        <input type="text" id="facturaDireccion" name="facturaDireccion" required placeholder="Ej: Calle Mayor 1, Madrid" style="width:100%;padding:8px;border-radius:5px;border:1px solid #ccc;">
                                    </div>
                                    <div class="form-group full-width">
                                        <label for="facturaEmail">Email</label>
                                        <input type="email" id="facturaEmail" name="facturaEmail" required placeholder="Ej: correo@ejemplo.com" style="width:100%;padding:8px;border-radius:5px;border:1px solid #ccc;">
                                    </div>
                                    <button type="submit" class="btn-primary" style="width:100%;margin-top:10px;font-size:1.1rem;">Confirmar compra</button>
                                </form>
                                <div id="compraResultado" style="margin-top:10px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <script src="js/venta_vendedor.js"></script>
                <button class="action-btn" id="btnModificarBillete"><i class="fa-solid fa-pen-to-square"></i><span>Modificar billete</span></button>
                <button class="action-btn" id="btnCancelarReserva"><i class="fa-solid fa-ban"></i><span>Cancelar reserva</span></button>
                <button class="action-btn" id="btnGenerarFactura"><i class="fa-solid fa-file-invoice"></i><span>Generar factura</span></button>
            </div>

            <!-- Modal de gestión de billetes del cliente -->
            <div id="modalGestionarBilletes" class="modal-vendedor hidden">
                <div class="modal-content" style="max-width: 600px;">
                    <span class="close-modal" id="cerrarGestionarBilletes">&times;</span>
                    <h2 id="tituloGestionarBilletes">Gestionar Billetes del Cliente</h2>
                    
                    <!-- Buscador por localizador -->
                    <div id="buscadorLocalizador" style="margin-bottom: 20px;">
                        <label>Buscar por localizador:</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="inputLocalizador" placeholder="Ej: TW-20260422-ABC123" style="flex: 1; padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                            <button type="button" id="btnBuscarLocalizador" style="padding: 8px 16px; background: #0a2a66; color: white; border: none; border-radius: 5px; cursor: pointer;">Buscar</button>
                        </div>
                        <div id="resultadoBusqueda" style="margin-top: 10px;"></div>
                    </div>

                    <!-- Lista de billetes del cliente -->
                    <div id="listaBilletesCliente" style="max-height: 300px; overflow-y: auto;">
                        <h4>Billetes del cliente</h4>
                        <div id="contenidoListaBilletes">
                            <p style="color: #666;">Busca un billete por localizador o espera a que se carguen todos los billetes.</p>
                        </div>
                    </div>

                    <!-- Acciones del billete -->
                    <div id="accionesBillete" class="hidden" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <h4 id="tituloAccionesBillete">Acciones</h4>
                        <div id="infoBilleteSeleccionado" style="margin-bottom: 15px;"></div>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="button" id="btnAccionModificar" style="padding: 10px 20px; background: #ffc107; color: #333; border: none; border-radius: 5px; cursor: pointer;"><i class="fa-solid fa-pen-to-square"></i> Modificar</button>
                            <button type="button" id="btnAccionCancelar" style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;"><i class="fa-solid fa-ban"></i> Cancelar</button>
                            <button type="button" id="btnAccionFactura" style="padding: 10px 20px; background: #17a2b8; color: white; border: none; border-radius: 5px; cursor: pointer;"><i class="fa-solid fa-file-invoice"></i> Generar Factura</button>
                        </div>
                    </div>

                    <!-- Modal de modificación de billete -->
                    <div id="modalModificarBillete" class="modal-vendedor hidden" style="background: rgba(0,0,0,0.5);">
                        <div class="modal-content" style="max-width: 500px;">
                            <span class="close-modal" id="cerrarModificarBillete">&times;</span>
                            <h2>Modificar Billete</h2>
                            <div id="infoBilleteAModificar" style="margin-bottom: 15px; padding: 10px; background: #e9ecef; border-radius: 5px;"></div>
                            <div id="modificarPaso1">
                                <h3>Seleccionar nuevo viaje</h3>
                                <form id="formBuscarViajesModificar">
                                    <label>Origen: <input type="text" name="origen" required></label>
                                    <label>Destino: <input type="text" name="destino" required></label>
                                    <label>Fecha: <input type="date" name="fecha" required></label>
                                    <button type="submit">Buscar viajes</button>
                                </form>
                                <div id="resultadosViajesModificar"></div>
                            </div>
                            <div id="modificarPaso2" class="hidden">
                                <h3>Seleccionar nuevo asiento</h3>
                                <div id="infoViajeModificar"></div>
                                <div id="asientosGridModificar" class="asientos-grid"></div>
                                <input type="hidden" id="inputAsientoModificar">
                                <button type="button" id="btnConfirmarModificacion" style="margin-top: 15px; padding: 10px 20px; background: #0a2a66; color: white; border: none; border-radius: 5px; cursor: pointer;">Confirmar modificación</button>
                            </div>
                            <div id="resultadoModificacion" style="margin-top: 15px;"></div>
                        </div>
                    </div>
                </div>
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

        // Función para iniciar nueva venta (redirige a la interfaz completa)
        function iniciarNuevaVenta() {
            if (typeof clienteBuscado === 'undefined' || !clienteBuscado) {
                alert('Por favor, busca y selecciona un cliente usando su DNI antes de iniciar una venta.');
                return;
            }
            
            // Redirigir a la nueva interfaz de venta con los datos del cliente
            const params = new URLSearchParams({
                id_cliente: clienteBuscado.id_usuario || clienteBuscado.id_pasajero,
                nombre: clienteBuscado.nombre + (clienteBuscado.apellido ? ' ' + clienteBuscado.apellido : ''),
                dni: clienteBuscado.dni || '',
                email: clienteBuscado.email || ''
            });
            
            window.location.href = 'venta_billete_vendedor.php?' + params.toString();
        }
    </script>
</body>
</html>
<script src="js/vendedor.js"></script>
<script src="js/gestionar_billetes.js"></script>
