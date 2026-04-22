// Lógica para gestionar los billetes del cliente (atención al vendedor)
let billeteSeleccionado = null;
let viajeModificarSeleccionado = null;

// Mostrar modal de gestión de billetes
function mostrarModalGestionarBilletes(tipoAccion) {
    if (typeof clienteBuscado === 'undefined' || !clienteBuscado) {
        alert('Por favor, busca y selecciona un cliente primero.');
        return;
    }

    // Guardar la acción que se quiere realizar
    window._accionPendiente = tipoAccion;

    // Mostrar el modal
    document.getElementById('modalGestionarBilletes').classList.remove('hidden');
    document.getElementById('buscadorLocalizador').classList.remove('hidden');
    document.getElementById('accionesBillete').classList.add('hidden');
    document.getElementById('resultadoBusqueda').innerHTML = '';
    document.getElementById('inputLocalizador').value = '';

    // Actualizar título según la acción
    const titulos = {
        'modificar': 'Modificar Billete',
        'cancelar': 'Cancelar Reserva',
        'factura': 'Generar Factura'
    };
    document.getElementById('tituloGestionarBilletes').textContent = titulos[tipoAccion] || 'Gestionar Billetes';

    // Cargar todos los billets del cliente
    cargarBilletesCliente();
}

// Cerrar modal de gestión de billetes
function cerrarModalGestionarBilletes() {
    document.getElementById('modalGestionarBilletes').classList.add('hidden');
    billeteSeleccionado = null;
}

// Cargar todos los billetes del cliente
function cargarBilletesCliente() {
    const idUsuario = clienteBuscado.id_usuario || clienteBuscado.id_pasajero;
    if (!idUsuario) return;

    fetch('php/api_billetes_cliente.php?id_usuario=' + idUsuario)
        .then(r => r.json())
        .then(data => {
            const contenedor = document.getElementById('contenidoListaBilletes');
            if (data.error || !data.billetes || data.billetes.length === 0) {
                contenedor.innerHTML = '<p style="color: #666;">No se encontraron billetes para este cliente.</p>';
                return;
            }

            let html = '<table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">';
            html += '<tr style="background: #0a2a66; color: white;"><th style="padding: 8px; text-align: left;">Localizador</th><th style="padding: 8px; text-align: left;">Ruta</th><th style="padding: 8px; text-align: left;">Fecha</th><th style="padding: 8px; text-align: left;">Estado</th><th style="padding: 8px; text-align: center;">Acción</th></tr>';

            data.billetes.forEach(b => {
                const viaje = b.viaje || {};
                const ruta = viaje.origen && viaje.destino ? `${viaje.origen} → ${viaje.destino}` : '-';
                const fecha = viaje.fecha || '-';
                const estado = b.estado === 'cancelado' ? '<span style="color: #dc3545;">Cancelado</span>' : '<span style="color: #17632A;">Confirmado</span>';
                const localizador = b.codigo_billete || '-';

                html += `<tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 8px;">${localizador}</td>
                    <td style="padding: 8px;">${ruta}</td>
                    <td style="padding: 8px;">${fecha}</td>
                    <td style="padding: 8px;">${estado}</td>
                    <td style="padding: 8px; text-align: center;">
                        <button type="button" onclick="seleccionarBillete('${localizador}', '${b.id_mongo}', '${b.id_viaje}', ${b.numero_asiento}, '${b.estado}')" 
                            style="padding: 4px 10px; background: #0a2a66; color: white; border: none; border-radius: 4px; cursor: pointer;">Seleccionar</button>
                    </td>
                </tr>`;
            });

            html += '</table>';
            contenedor.innerHTML = html;
        })
        .catch(err => {
            document.getElementById('contenidoListaBilletes').innerHTML = '<p style="color: #c00;">Error al cargar los billetes.</p>';
        });
}

// Buscar billete por localizador
function buscarBilletePorLocalizador() {
    const localizador = document.getElementById('inputLocalizador').value.trim();
    const idUsuario = clienteBuscado.id_usuario || clienteBuscado.id_pasajero;

    if (!localizador) {
        document.getElementById('resultadoBusqueda').innerHTML = '<p style="color: #c00;">Introduce un localizador.</p>';
        return;
    }

    fetch('php/api_buscar_billete_localizador.php?localizador=' + encodeURIComponent(localizador) + '&id_usuario=' + idUsuario)
        .then(r => r.json())
        .then(data => {
            const resultado = document.getElementById('resultadoBusqueda');
            if (data.error) {
                resultado.innerHTML = '<p style="color: #c00;">' + data.error + '</p>';
                return;
            }

            const b = data.billete;
            const v = data.viaje;
            const ruta = v ? `${v.origen} → ${v.destino}` : '-';
            const fecha = v ? v.fecha : '-';
            const estado = b.estado === 'cancelado' ? 'Cancelado' : 'Confirmado';

            resultado.innerHTML = `
                <div style="padding: 10px; background: #d4edda; border-radius: 5px; margin-top: 10px;">
                    <p><strong>Localizador:</strong> ${b.codigo_billete}</p>
                    <p><strong>Ruta:</strong> ${ruta}</p>
                    <p><strong>Fecha:</strong> ${fecha}</p>
                    <p><strong>Asiento:</strong> ${b.numero_asiento}</p>
                    <p><strong>Estado:</strong> ${estado}</p>
                    <button type="button" onclick="seleccionarBillete('${b.codigo_billete}', '${b.id_mongo}', ${b.id_viaje}, ${b.numero_asiento}, '${b.estado}')" 
                        style="margin-top: 10px; padding: 8px 16px; background: #0a2a66; color: white; border: none; border-radius: 5px; cursor: pointer;">Seleccionar este billete</button>
                </div>
            `;
        })
        .catch(err => {
            document.getElementById('resultadoBusqueda').innerHTML = '<p style="color: #c00;">Error en la búsqueda.</p>';
        });
}

// Seleccionar un billete para realizar acciones
function seleccionarBillete(codigo, idMongo, idViaje, numeroAsiento, estado) {
    billeteSeleccionado = {
        codigo_billete: codigo,
        id_mongo: idMongo,
        id_viaje: idViaje,
        numero_asiento: numeroAsiento,
        estado: estado
    };

    // Mostrar información del billete seleccionado
    document.getElementById('accionesBillete').classList.remove('hidden');
    document.getElementById('infoBilleteSeleccionado').innerHTML = `
        <p><strong>Localizador:</strong> ${codigo}</p>
        <p><strong>Asiento:</strong> ${numeroAsiento}</p>
        <p><strong>Estado:</strong> ${estado === 'cancelado' ? 'Cancelado' : 'Confirmado'}</p>
    `;

    // Habilitar/deshabilitar botones según el estado
    const btnModificar = document.getElementById('btnAccionModificar');
    const btnCancelar = document.getElementById('btnAccionCancelar');

    if (estado === 'cancelado') {
        btnModificar.disabled = true;
        btnModificar.style.background = '#ccc';
        btnCancelar.disabled = true;
        btnCancelar.style.background = '#ccc';
    } else {
        btnModificar.disabled = false;
        btnModificar.style.background = '#ffc107';
        btnCancelar.disabled = false;
        btnCancelar.style.background = '#dc3545';
    }
}

// Ejecutar la acción pendiente (modificar, cancelar o factura)
function ejecutarAccionBillete(tipo) {
    if (!billeteSeleccionado) {
        alert('Selecciona un billete primero.');
        return;
    }

    const idUsuario = clienteBuscado.id_usuario || clienteBuscado.id_pasajero;

    if (tipo === 'cancelar') {
        if (!confirm('¿Estás seguro de que quieres cancelar este billete? Se liberará el asiento.')) {
            return;
        }

        fetch('php/api_cancelar_billete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                localizador: billeteSeleccionado.codigo_billete,
                id_usuario: idUsuario,
                id_mongo: billeteSeleccionado.id_mongo
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                alert('Billete cancelado correctamente. El asiento ha sido liberado.');
                cerrarModalGestionarBilletes();
                // Recargar datos del cliente
                if (typeof buscarClientePorDni === 'function') {
                    buscarClientePorDni();
                }
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(err => {
            alert('Error al cancelar el billete.');
        });
    } else if (tipo === 'factura') {
        // Generar factura
        fetch('php/api_generar_factura.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                localizador: billeteSeleccionado.codigo_billete,
                id_usuario: idUsuario,
                enviar_correo: false
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok && data.pdf_base64) {
                // Descargar el PDF
                const link = document.createElement('a');
                link.href = 'data:application/pdf;base64,' + data.pdf_base64;
                link.download = data.nombre_archivo;
                link.click();
                alert('Factura descargada correctamente.');
            } else {
                alert('Error: ' + (data.error || 'Error al generar la factura'));
            }
        })
        .catch(err => {
            alert('Error al generar la factura.');
        });
    } else if (tipo === 'modificar') {
        // Abrir modal de modificación
        abrirModalModificarBillete();
    }
}

// Abrir modal de modificación de billete
function abrirModalModificarBillete() {
    if (!billeteSeleccionado) return;

    document.getElementById('modalModificarBillete').classList.remove('hidden');
    document.getElementById('modificarPaso1').classList.remove('hidden');
    document.getElementById('modificarPaso2').classList.add('hidden');
    document.getElementById('resultadosViajesModificar').innerHTML = '';
    document.getElementById('resultadoModificacion').innerHTML = '';

    // Mostrar información del billete a modificar
    document.getElementById('infoBilleteAModificar').innerHTML = `
        <p><strong>Billete actual:</strong> ${billeteSeleccionado.codigo_billete}</p>
        <p><strong>Asiento actual:</strong> ${billeteSeleccionado.numero_asiento}</p>
    `;

    // Pre-rellenar la fecha con la fecha del viaje actual
    // Esto requeriría obtener la fecha del viaje, por ahora usamos la fecha actual
    const hoy = new Date().toISOString().split('T')[0];
    const inputFecha = document.querySelector('#formBuscarViajesModificar input[name="fecha"]');
    if (inputFecha) inputFecha.value = hoy;
}

// Cerrar modal de modificación
function cerrarModalModificarBillete() {
    document.getElementById('modalModificarBillete').classList.add('hidden');
    viajeModificarSeleccionado = null;
}

// Buscar viajes para modificación
function buscarViajesParaModificar(e) {
    e.preventDefault();
    const datos = Object.fromEntries(new FormData(e.target));

    fetch('php/api_buscar_viajes.php?origen=' + encodeURIComponent(datos.origen) + '&destino=' + encodeURIComponent(datos.destino) + '&fecha=' + encodeURIComponent(datos.fecha))
        .then(r => r.json())
        .then(data => {
            const res = document.getElementById('resultadosViajesModificar');
            if (data.error || !data.viajes || data.viajes.length === 0) {
                res.innerHTML = '<p>No hay viajes disponibles.</p>';
            } else {
                res.innerHTML = '<ul>' + data.viajes.map((v, i) => `<li><button type='button' onclick='seleccionarViajeModificar(${JSON.stringify(v.id_viaje)})'>${v.origen} → ${v.destino} (${v.fecha} ${v.hora_salida}) - ${v.precio_base}€</button></li>`).join('') + '</ul>';
                window._viajesModificar = data.viajes;
            }
        });
}

// Seleccionar viaje para modificación
function seleccionarViajeModificar(id_viaje) {
    const viajes = window._viajesModificar || [];
    const viaje = viajes.find(v => v.id_viaje == id_viaje);
    if (!viaje) return;

    viajeModificarSeleccionado = viaje;

    // Cargar asientos del nuevo viaje
    fetch('php/api_asientos_todos.php?id_viaje=' + id_viaje)
        .then(r => r.json())
        .then(data => {
            const asientos = data.asientos || [];
            document.getElementById('modificarPaso1').classList.add('hidden');
            document.getElementById('modificarPaso2').classList.remove('hidden');
            document.getElementById('infoViajeModificar').innerHTML = `${viaje.origen} → ${viaje.destino} (${viaje.fecha} ${viaje.hora_salida}) - Tren: ${viaje.tipo_tren} - Precio: ${viaje.precio_base}€`;

            const grid = document.getElementById('asientosGridModificar');
            grid.innerHTML = '';
            if (asientos.length === 0) {
                grid.innerHTML = '<span style="grid-column: span 8; color: #c00;">No hay asientos en este tren.</span>';
            } else {
                asientos.forEach(a => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'asiento-btn';
                    btn.textContent = a.numero_asiento;
                    if (a.estado !== 'disponible') {
                        btn.disabled = true;
                    }
                    btn.onclick = function() {
                        if (btn.disabled) return;
                        document.querySelectorAll('#asientosGridModificar .asiento-btn').forEach(b => b.classList.remove('selected'));
                        btn.classList.add('selected');
                        document.getElementById('inputAsientoModificar').value = a.numero_asiento;
                    };
                    grid.appendChild(btn);
                });
            }
            document.getElementById('inputAsientoModificar').value = '';
        });
}

// Confirmar modificación del billete
function confirmarModificacionBillete() {
    if (!billeteSeleccionado || !viajeModificarSeleccionado) {
        alert('Selecciona un viaje y un asiento.');
        return;
    }

    const nuevoAsiento = document.getElementById('inputAsientoModificar').value;
    if (!nuevoAsiento) {
        alert('Selecciona un asiento.');
        return;
    }

    const idUsuario = clienteBuscado.id_usuario || clienteBuscado.id_pasajero;

    fetch('php/api_modificar_billete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            localizador: billeteSeleccionado.codigo_billete,
            id_usuario: idUsuario,
            id_mongo: billeteSeleccionado.id_mongo,
            id_viaje: viajeModificarSeleccionado.id_viaje,
            numero_asiento: parseInt(nuevoAsiento)
        })
    })
    .then(r => r.json())
    .then(data => {
        const resultado = document.getElementById('resultadoModificacion');
        if (data.ok) {
            resultado.innerHTML = '<p style="color: #17632A;"><strong>Billete modificado correctamente.</strong></p>';
            setTimeout(() => {
                cerrarModalModificarBillete();
                cerrarModalGestionarBilletes();
            }, 1500);
        } else {
            resultado.innerHTML = '<p style="color: #c00;"><strong>Error:</strong> ' + data.error + '</p>';
        }
    })
    .catch(err => {
        document.getElementById('resultadoModificacion').innerHTML = '<p style="color: #c00;">Error al modificar el billete.</p>';
    });
}

// Inicializar eventos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Botones de acción
    const btnModificar = document.getElementById('btnModificarBillete');
    const btnCancelar = document.getElementById('btnCancelarReserva');
    const btnFactura = document.getElementById('btnGenerarFactura');

    btnModificar && btnModificar.addEventListener('click', () => mostrarModalGestionarBilletes('modificar'));
    btnCancelar && btnCancelar.addEventListener('click', () => mostrarModalGestionarBilletes('cancelar'));
    btnFactura && btnFactura.addEventListener('click', () => mostrarModalGestionarBilletes('factura'));

    // Cerrar modales
    const cerrarGestionar = document.getElementById('cerrarGestionarBilletes');
    cerrarGestionar && cerrarGestionar.addEventListener('click', cerrarModalGestionarBilletes);

    const cerrarModificar = document.getElementById('cerrarModificarBillete');
    cerrarModificar && cerrarModificar.addEventListener('click', cerrarModalModificarBillete);

    // Buscar por localizador
    const btnBuscarLocalizador = document.getElementById('btnBuscarLocalizador');
    btnBuscarLocalizador && btnBuscarLocalizador.addEventListener('click', buscarBilletePorLocalizador);

    // Enter en el input de localizador
    const inputLocalizador = document.getElementById('inputLocalizador');
    inputLocalizador && inputLocalizador.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            buscarBilletePorLocalizador();
        }
    });

    // Botones de acción del billete
    const btnAccionModificar = document.getElementById('btnAccionModificar');
    const btnAccionCancelar = document.getElementById('btnAccionCancelar');
    const btnAccionFactura = document.getElementById('btnAccionFactura');

    btnAccionModificar && btnAccionModificar.addEventListener('click', () => ejecutarAccionBillete('modificar'));
    btnAccionCancelar && btnAccionCancelar.addEventListener('click', () => ejecutarAccionBillete('cancelar'));
    btnAccionFactura && btnAccionFactura.addEventListener('click', () => ejecutarAccionBillete('factura'));

    // Formulario de búsqueda de viajes para modificar
    const formBuscarViajesModificar = document.getElementById('formBuscarViajesModificar');
    formBuscarViajesModificar && formBuscarViajesModificar.addEventListener('submit', buscarViajesParaModificar);

    // Confirmar modificación
    const btnConfirmarModificacion = document.getElementById('btnConfirmarModificacion');
    btnConfirmarModificacion && btnConfirmarModificacion.addEventListener('click', confirmarModificacionBillete);
});