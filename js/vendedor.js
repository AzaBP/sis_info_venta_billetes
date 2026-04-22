let clienteBuscado = null;

document.addEventListener('DOMContentLoaded', () => {
    const btnBuscar = document.getElementById('btnBuscar');
    const inputDni = document.getElementById('dniInput');
    
    // Elementos del DOM a manipular
    const clientInfo = document.getElementById('clientInfo');
    const clientError = document.getElementById('clientError');
    const operationsPanel = document.getElementById('operationsPanel');
    const clientDniValue = document.getElementById('clientDniValue');
    const clientActionsBox = document.getElementById('clientActionsBox');
    
    // Elementos de datos
    const elName = document.getElementById('clientName');
    const elEmail = document.getElementById('clientEmail');
    const elPhone = document.getElementById('clientPhone');
    const elCard = document.getElementById('clientCard');
    const listTrips = document.getElementById('recentTrips');

    // Función de búsqueda
    const realizarBusqueda = () => {
        const busqueda = inputDni.value.trim();
        
        // Resetear vista
        clientInfo.classList.add('hidden');
        clientError.classList.add('hidden');
        operationsPanel.classList.add('disabled');
        clientActionsBox.classList.add('disabled');
        listTrips.innerHTML = '';
        clienteBuscado = null;
        
        if (!busqueda) {
            clientError.textContent = 'Introduce un correo electrónico o DNI válido.';
            clientError.classList.remove('hidden');
            return;
        }
        
        fetch('php/api_buscar_usuario_tramites.php?dni=' + encodeURIComponent(busqueda))
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    clientError.textContent = data.error;
                    clientError.classList.remove('hidden');
                } else {
                    elName.textContent = data.usuario.nombre;
                    clientDniValue.textContent = data.usuario.dni;
                    elEmail.textContent = data.usuario.email || '---';
                    elPhone.textContent = data.usuario.telefono || '---';
                    elCard.textContent = data.usuario.tarjeta || '****';
                    
                    // Viajes
                    if (data.viajes && data.viajes.length > 0) {
                        data.viajes.forEach(viaje => {
                            const li = document.createElement('li');
                            li.innerHTML = `
                                <span>${viaje.ruta} <br><small>${viaje.fecha}</small></span>
                                <span class="status-ok">${viaje.estado}</span>
                            `;
                            listTrips.appendChild(li);
                        });
                    } else {
                        listTrips.innerHTML = '<li style="color:#999">Sin viajes recientes</li>';
                    }
                    
                    clientInfo.classList.remove('hidden');
                    operationsPanel.classList.remove('disabled');
                    clientActionsBox.classList.remove('disabled');
                    clienteBuscado = data.usuario;
                }
            })
            .catch(() => {
                clientError.textContent = 'Error al buscar el usuario.';
                clientError.classList.remove('hidden');
            });
    };

    // Event listeners
    btnBuscar.addEventListener('click', realizarBusqueda);
    
    // Enter key para búsqueda
    inputDni.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            realizarBusqueda();
        }
    });

    // Cerrar modal de modificación de billete
    const btnCerrarModificar = document.getElementById('cerrarModificarBillete');
    if (btnCerrarModificar) {
        btnCerrarModificar.addEventListener('click', () => {
            cerrarModalModificarBillete();
        });
    }
    
    // Botón modificar datos del pasajero
    const btnModificarDatos = document.getElementById('btnModificarDatos');
    if (btnModificarDatos) {
        btnModificarDatos.addEventListener('click', () => {
            mostrarFormularioModificarDatos();
        });
    }
    
    // Botón cambiar asiento
    const btnModificarAsiento = document.getElementById('btnModificarAsiento');
    if (btnModificarAsiento) {
        btnModificarAsiento.addEventListener('click', () => {
            cargarAsientosParaModificacion();
        });
    }
    
    // Botón confirmar modificación de asiento
    const btnConfirmarModificacion = document.getElementById('btnConfirmarModificacion');
    if (btnConfirmarModificacion) {
        btnConfirmarModificacion.addEventListener('click', () => {
            confirmarModificacionBillete();
        });
    }
    
    // Formulario de modificación de datos del pasajero
    const formModificarDatos = document.getElementById('formModificarDatosPasajero');
    if (formModificarDatos) {
        formModificarDatos.addEventListener('submit', (e) => {
            guardarDatosModificadosPasajero(e);
        });
    }

    // Cerrar modal de nueva venta
    const btnCerrarNuevaVenta = document.getElementById('cerrarModalNuevaVenta');
    if (btnCerrarNuevaVenta) {
        btnCerrarNuevaVenta.addEventListener('click', () => {
            document.getElementById('modalNuevaVenta').classList.add('hidden');
        });
    }
    
    // Cambio de origen en modal de nueva venta
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
            
            const idPasajero = clienteBuscado.id_pasajero;
            window.location.href = `compra.php?id_pasajero_gestionado=${idPasajero}&origen=${encodeURIComponent(origen)}&destino=${encodeURIComponent(destino)}&fecha=${encodeURIComponent(fecha)}`;
        });
    }

            // Re-inicializar event listeners cuando se vuelva a abrir el modal de nueva venta
            const modalNuevaVenta = document.getElementById('modalNuevaVenta');
            if (modalNuevaVenta) {
                window._nuevaVentaListenersReady = true;
            }
});

// Función auxiliar para reasignar event listeners si es necesario
function ensureNuevaVentaListeners() {
    if (window._nuevaVentaListenersReady) return;
    
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
    
    window._nuevaVentaListenersReady = true;
}

// Función para los botones de acción
