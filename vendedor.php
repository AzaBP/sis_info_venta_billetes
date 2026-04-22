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
        
        /* Estilos para modal de gestión de billetes */
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
        
        /* Asientos mejorados estilo compra.php */
        .modal-vendedor .asientos-grid {
            display: flex; flex-direction: column; gap: 8px; margin: 18px 0; max-height: 350px; overflow-y: auto; padding: 10px; background: #f8f9fa; border-radius: 8px;
        }
        .modal-vendedor .asiento-fila {
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .modal-vendedor .asiento-numero {
            width: 45px; text-align: center; font-size: 0.75rem; color: #666;
        }
        .modal-vendedor .asiento-btn {
            width: 40px; height: 40px; background: white; border: 2px solid #bbb; border-radius: 6px;
            cursor: pointer; font-weight: 600; color: #555; font-size: 0.85rem;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s ease;
        }
        .modal-vendedor .asiento-btn:hover:not(:disabled) {
            border-color: #0a2a66; background: #e7eefb;
        }
        .modal-vendedor .asiento-btn.selected {
            background: #0a2a66; color: white; border-color: #0a2a66;
            box-shadow: 0 2px 6px rgba(10, 42, 102, 0.4);
        }
        .modal-vendedor .asiento-btn:disabled {
            background: #d6d6d6; color: #888; border-color: #bbb; cursor: not-allowed;
        }
        .modal-vendedor .asiento-btn.current-seat {
            background: #e9ecef; color: #0a2a66; border: 2px dashed #0a2a66;
        }
        .modal-vendedor .pasillo {
            width: 30px; display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 0.7rem;
        }
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
                <input type="text" id="dniInput" placeholder="Introduce el correo electrónico o DNI...">
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
                <button class="action-btn" id="btnGestionBilletes" onclick="mostrarModalGestionarBilletes()"><i class="fa-solid fa-ticket"></i><span>Gestión de Billetes</span></button>
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
                        <div class="modal-content" style="max-width: 600px;">
                            <span class="close-modal" id="cerrarModificarBillete">&times;</span>
                            <h2>Modificar Billete</h2>
                            <div id="infoBilleteAModificar" style="margin-bottom: 15px; padding: 10px; background: #e9ecef; border-radius: 5px;"></div>
                            
                            <!-- Opciones de modificación -->
                            <div id="modificarOpciones">
                                <h3>¿Qué desea modificar?</h3>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                                    <button type="button" id="btnModificarDatos" style="padding: 12px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                        <i class="fa-solid fa-user"></i> Datos del Pasajero
                                    </button>
                                    <button type="button" id="btnModificarAsiento" style="padding: 12px 20px; background: #ffc107; color: #333; border: none; border-radius: 5px; cursor: pointer;">
                                        <i class="fa-solid fa-chair"></i> Cambiar Asiento
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Paso: Cambiar asiento -->
                            <div id="modificarAsientoPaso" class="hidden" style="margin-top: 20px;">
                                <h3>Seleccionar nuevo asiento</h3>
                                <div id="infoViajeModificar" style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;"></div>
                                <div id="asientosGridModificar" class="asientos-grid" style="max-height: 300px; overflow-y: auto;"></div>
                                <input type="hidden" id="inputAsientoModificar">
                                <button type="button" id="btnConfirmarModificacion" style="margin-top: 15px; padding: 10px 20px; background: #0a2a66; color: white; border: none; border-radius: 5px; cursor: pointer;">Confirmar cambio de asiento</button>
                            </div>
                            
                            <!-- Paso: Modificar datos del pasajero -->
                            <div id="modificarDatosPaso" class="hidden" style="margin-top: 20px;">
                                <h3>Modificar datos del pasajero</h3>
                                <form id="formModificarDatosPasajero">
                                    <label>Nombre:</label>
                                    <input type="text" id="modificarNombre" name="nombre" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; margin-bottom: 10px;">
                                    <label>Apellidos:</label>
                                    <input type="text" id="modificarApellidos" name="apellidos" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; margin-bottom: 10px;">
                                    <label>Email:</label>
                                    <input type="email" id="modificarEmail" name="email" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; margin-bottom: 10px;">
                                    <button type="submit" style="padding: 10px 20px; background: #0a2a66; color: white; border: none; border-radius: 5px; cursor: pointer;">Guardar cambios</button>
                                </form>
                            </div>
                            
                            <div id="resultadoModificacion" style="margin-top: 15px;"></div>
                        </div>
                    </div>
                    
                    <!-- Modal de nueva venta: búsqueda de destinos -->
                    <div id="modalNuevaVenta" class="modal-vendedor hidden" style="background: rgba(0,0,0,0.5);">
                        <div class="modal-content" style="max-width: 500px;">
                            <span class="close-modal" id="cerrarModalNuevaVenta">&times;</span>
                            <h2><i class="fa-solid fa-cart-plus"></i> Nueva Venta</h2>
                            <p style="color: #666; margin-bottom: 20px;">Selecciona el destino para el cliente: <strong id="nombreClienteVenta"></strong></p>
                            
                            <form id="formBuscarViajesVenta">
                                <label>Origen:</label>
                                <select id="origenVenta" name="origen" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; margin-bottom: 15px;">
                                    <option value="">Seleccionar origen...</option>
                                </select>
                                
                                <label>Destino:</label>
                                <select id="destinoVenta" name="destino" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; margin-bottom: 15px;" disabled>
                                    <option value="">Seleccionar destino...</option>
                                </select>
                                
                                <label>Fecha:</label>
                                <input type="date" id="fechaVenta" name="fecha" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; margin-bottom: 15px;">
                                
                                <button type="submit" style="width: 100%; padding: 12px; background: #0a2a66; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem;">
                                    <i class="fa-solid fa-magnifying-glass"></i> Buscar Viajes
                                </button>
                            </form>
                            
                            <div id="resultadosViajesVenta" style="margin-top: 15px;"></div>
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

        // Función para iniciar nueva venta (mostrar modal de búsqueda de destinos)
        function iniciarNuevaVenta() {
            if (typeof clienteBuscado === 'undefined' || !clienteBuscado) {
                alert('Por favor, busca y selecciona un cliente usando su DNI antes de iniciar una venta.');
                return;
            }
            
            const idPasajero = clienteBuscado.id_pasajero;
            if (!idPasajero) {
                alert('El cliente no tiene un ID de pasajero válido.');
                return;
            }
            
            // Mostrar modal de nueva venta
            document.getElementById('nombreClienteVenta').textContent = clienteBuscado.nombre;
            document.getElementById('modalNuevaVenta').classList.remove('hidden');
            document.getElementById('resultadosViajesVenta').innerHTML = '';
            
            // Cargar orígenes disponibles
            cargarOrigenesVenta();
            
            // Establecer fecha mínima (hoy)
            const hoy = new Date().toISOString().split('T')[0];
            document.getElementById('fechaVenta').min = hoy;
            document.getElementById('fechaVenta').value = hoy;
        }
        
        // Función para cargar orígenes en el modal de nueva venta
        function cargarOrigenesVenta() {
            fetch('php/api_origenes_destinos.php')
                .then(r => r.json())
                .then(data => {
                    const selectOrigen = document.getElementById('origenVenta');
                    const selectDestino = document.getElementById('destinoVenta');
                    
                    // Limpiar y añadir opción por defecto
                    selectOrigen.innerHTML = '<option value="">Seleccionar origen...</option>';
                    selectDestino.innerHTML = '<option value="">Seleccionar destino...</option>';
                    selectDestino.disabled = true;
                    
                    if (data.origenes) {
                        data.origenes.forEach(o => {
                            const option = document.createElement('option');
                            option.value = o;
                            option.textContent = o;
                            selectOrigen.appendChild(option);
                        });
                    }
                })
                .catch(err => {
                    console.error('Error al cargar orígenes:', err);
                });
        }
        
        // Event listener para cambio de origen (actualizar destinos)
        document.addEventListener('DOMContentLoaded', () => {
            const selectOrigen = document.getElementById('origenVenta');
            const selectDestino = document.getElementById('destinoVenta');
            
            if (selectOrigen && selectDestino) {
                selectOrigen.addEventListener('change', function() {
                    const origen = this.value;
                    if (!origen) {
                        selectDestino.innerHTML = '<option value="">Seleccionar destino...</option>';
                        selectDestino.disabled = true;
                        return;
                    }
                    
                    // Cargar destinos disponibles (excluyendo el origen)
                    fetch('php/api_origenes_destinos.php')
                        .then(r => r.json())
                        .then(data => {
                            selectDestino.innerHTML = '<option value="">Seleccionar destino...</option>';
                            if (data.destinos) {
                                data.destinos.forEach(d => {
                                    if (d !== origen) {
                                        const option = document.createElement('option');
                                        option.value = d;
                                        option.textContent = d;
                                        selectDestino.appendChild(option);
                                    }
                                });
                            }
                            selectDestino.disabled = false;
                        });
                });
            }
            
            // Cerrar modal de nueva venta
            const btnCerrarNuevaVenta = document.getElementById('cerrarModalNuevaVenta');
            if (btnCerrarNuevaVenta) {
                btnCerrarNuevaVenta.addEventListener('click', () => {
                    document.getElementById('modalNuevaVenta').classList.add('hidden');
                });
            }
            
            // Buscar viajes desde el modal de nueva venta
            const formBuscarViajesVenta = document.getElementById('formBuscarViajesVenta');
            if (formBuscarViajesVenta) {
                formBuscarViajesVenta.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const datos = new FormData(this);
                    const origen = datos.get('origen');
                    const destino = datos.get('destino');
                    const fecha = datos.get('fecha');
                    
                    if (!origen || !destino || !fecha) {
                        document.getElementById('resultadosViajesVenta').innerHTML = '<p style="color: #c00;">Por favor, completa todos los campos.</p>';
                        return;
                    }
                    
                    // Buscar viajes y redirigir a compra.php con parámetros
                    const idPasajero = clienteBuscado.id_pasajero;
                    window.location.href = `compra.php?id_pasajero_gestionado=${idPasajero}&origen=${encodeURIComponent(origen)}&destino=${encodeURIComponent(destino)}&fecha=${encodeURIComponent(fecha)}`;
                });
            }
        });
    </script>
</body>
</html>
<script src="js/vendedor.js"></script>
<script src="js/gestionar_billetes.js"></script>
