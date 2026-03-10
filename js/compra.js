// Al principio del archivo js/compra.js
let viajeSeleccionado = null;
let asientoSeleccionadoNum = null;
function confirmarReserva() {
    // 1. Validar que tenemos los datos
    if (!viajeSeleccionado || !asientoSeleccionadoNum) {
        alert("Por favor, selecciona un asiento antes de pagar.");
        return;
    }

    // 2. Cambiar visualmente el botón para que el usuario sepa que está cargando
    const btnPagar = document.querySelector('.btn-pay-confirm');
    if (btnPagar) {
        btnPagar.innerText = "Procesando pago...";
        btnPagar.disabled = true;
    }

    // 3. Enviar la petición a nuestro backend (MongoDB)
    fetch('php/api_reservar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_viaje: viajeSeleccionado,
            numero_asiento: asientoSeleccionadoNum
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.exito) {
            alert("¡Pago exitoso! Billete Reservado con ID: " + data.id_mongo);
            // 4. AHORA SÍ, recargamos la página o mandamos al inicio
            window.location.href = "compra.php"; // Te devuelve al inicio de compra para ver el asiento gris
        } else {
            alert("Error en la reserva: " + data.error);
            if(btnPagar) {
                btnPagar.innerText = "Pagar";
                btnPagar.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error("Error Fetch:", error);
        alert("Hubo un error de conexión con el servidor.");
        if(btnPagar) {
            btnPagar.innerText = "Pagar";
            btnPagar.disabled = false;
        }
    });
}
// Interceptar el envío del formulario de pago
document.querySelector('.payment-form').addEventListener('submit', function(e) {
    e.preventDefault(); // Detiene el envío normal del formulario
    if (!asientoSeleccionadoNum) {
        alert("Por favor, selecciona un asiento.");
        return;
    }
    confirmarReserva(); // Llama a la función que guarda en MongoDB
});

// Nueva función robusta para confirmar la reserva y refrescar
function confirmarReserva() {
    const btnPagar = document.querySelector('.btn-pay-confirm');
    btnPagar.innerText = "Procesando...";
    btnPagar.disabled = true;

    fetch('php/api_reservar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_viaje: viajeSeleccionado,
            numero_asiento: asientoSeleccionadoNum
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.exito) {
            alert("¡Compra realizada con éxito!");
            window.location.reload(); // Refresca para ver el asiento ocupado
        } else {
            alert("Error: " + data.error);
            btnPagar.disabled = false;
            btnPagar.innerText = "Pagar";
        }
    })
    .catch(error => {
        console.error("Error en Fetch:", error);
        alert("No se pudo conectar con el servidor de reserva.");
    });
}
// Variables globales para asiento y precio
// SOLO AL PRINCIPIO DEL ARCHIVO
let viajeSeleccionado = null;
let asientoSeleccionadoNum = null;
let precioBaseViaje = 0;

// 1. Modificar seleccionarAsiento para habilitar el botón
function seleccionarAsiento(elementoHtml, numero_asiento) {
    // 1. Gestionar lo visual
    const todosSeleccionados = document.querySelectorAll('.seat.selected');
    todosSeleccionados.forEach(asiento => asiento.classList.remove('selected'));
    elementoHtml.classList.add('selected');

    // 2. Guardar el dato en AMBAS variables (¡Aquí estaba el fallo!)
    asientoSeleccionadoNum = numero_asiento;
    estado.asientoSeleccionado = elementoHtml; // <-- AÑADE ESTA LÍNEA

    // 3. ACTUALIZAR PRECIOS EN LA UI (Para evitar los 0.00€)
    const precioAsientoElemento = document.getElementById('precioAsiento');
    const totalPagoElemento = document.getElementById('totalPago');

    if (precioAsientoElemento) precioAsientoElemento.innerText = precioBaseViaje.toFixed(2) + ' €';
    if (totalPagoElemento) totalPagoElemento.innerText = precioBaseViaje.toFixed(2) + ' €';

    // 4. Habilitar tu botón de "Pagar"
    const btnPagar = document.querySelector('.btn-pay-confirm');
    if (btnPagar) {
        btnPagar.disabled = false;
        btnPagar.style.opacity = "1";
    }
}

// VARIABLES DE ESTADO
let estado = {
    pasoActual: 1,
    trenSeleccionado: null,
    precioBase: 0,
    asientoSeleccionado: null,
    vagonActual: 1,
    maxVagones: 3
};

// ================= NAVEGACIÓN ENTRE PASOS =================
function irAPaso(numeroPaso) {
    if (numeroPaso > estado.pasoActual && numeroPaso > 1 && !estado.trenSeleccionado) return;
    if (numeroPaso === 3 && !estado.asientoSeleccionado) return;

    document.getElementById('sectionTrains').classList.add('hidden');
    document.getElementById('sectionSeats').classList.add('hidden');
    document.getElementById('sectionPayment').classList.add('hidden');

    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active');
    });

    if (numeroPaso === 1) {
        document.getElementById('sectionTrains').classList.remove('hidden');
    } else if (numeroPaso === 2) {
        document.getElementById('sectionSeats').classList.remove('hidden');
        actualizarEstadoFlechas();
        // Corregir el texto del panel de vagón al cargar el primer vagón
        document.getElementById('currentWagonNum').textContent = '1';
    } else if (numeroPaso === 3) {
        document.getElementById('sectionPayment').classList.remove('hidden');
    }

    const pasoElement = document.getElementById(`step${numeroPaso}`);
    pasoElement.classList.add('active');
    
    for(let i=1; i < numeroPaso; i++) {
        document.getElementById(`step${i}`).classList.add('completed');
    }

    estado.pasoActual = numeroPaso;
}

// ================= PASO 1: SELECCIÓN DE TREN =================
function seleccionarTren(id_viaje, tipo_tren, precio) {
    // Si PHP envía los datos, los capturamos aquí
    viajeSeleccionado = id_viaje;
    precioBaseViaje = parseFloat(precio) || 0;

    // Actualizamos el estado interno de tu objeto 'estado' si lo usas
    if (typeof estado !== 'undefined') {
        estado.trenSeleccionado = id_viaje;
        estado.precioBase = precioBaseViaje;
    }

    const lblTren = document.getElementById('lblTrenSeleccionado');
    if (lblTren) lblTren.textContent = id_viaje;

    irAPaso(2); // Esto debería moverte a la pantalla de asientos
}

// ================= PASO 2: SELECCIÓN DE ASIENTO =================
function cambiarVagon(direccion) {
    let nuevoVagon = estado.vagonActual + direccion;
    if (nuevoVagon < 1) return;
    if (nuevoVagon > estado.maxVagones) return;

    document.getElementById(`wagon${estado.vagonActual}`).classList.add('hidden');
    document.getElementById(`wagon${nuevoVagon}`).classList.remove('hidden');

    document.getElementById('currentWagonNum').textContent = `${nuevoVagon}`;
    estado.vagonActual = nuevoVagon;
    actualizarEstadoFlechas();
}

function actualizarEstadoFlechas() {
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');

    if (estado.vagonActual === 1) btnPrev.disabled = true;
    else btnPrev.disabled = false;

    if (estado.vagonActual === estado.maxVagones) btnNext.disabled = true;
    else btnNext.disabled = false;
}

    // Lógica de click en asiento
    document.querySelectorAll('.seat').forEach(asiento => {
        asiento.addEventListener('click', function() {
            if (this.classList.contains('occupied')) return;

            // Usar nueva función para seleccionar asiento
            const numero_asiento = this.getAttribute('data-seat');
            seleccionarAsiento(this, numero_asiento);

            // PRECIO: Suplemento Primera Clase (Vagon 1)
            let precioFinal = parseFloat(estado.precioBase) || 0;
            if(estado.vagonActual === 1) precioFinal += 15; 

            // Actualizamos la interfaz
            document.getElementById('displaySeat').textContent = `Vagón ${estado.vagonActual} - ${numero_asiento}`;
            document.getElementById('displayPrice').textContent = precioFinal.toFixed(2) + " €";
            const txtFinal = document.getElementById('finalPrice');
            if (txtFinal) txtFinal.textContent = precioFinal.toFixed(2) + " €";

            document.getElementById('btnToPayment').disabled = false;
        });
    });

    // Eliminar texto de pasillo premium en primera clase
    const premiumAisle = document.querySelector('.aisle-horizontal.premium');
    if (premiumAisle && estado.vagonActual === 1) {
        premiumAisle.textContent = '';
    }

// ================= PASO 3: FINALIZAR =================
function finalizarCompra() {
    alert(`✅ ¡BILLETE PAGADO!\n\nHas comprado un billete para el tren ${estado.trenSeleccionado}.\nAsiento: ${estado.asientoSeleccionado.getAttribute('data-seat')}.\n\n(Simulación finalizada)`);
    window.location.href = 'index.html';
}

document.addEventListener('DOMContentLoaded', function() {
    // Cargar abonos activos del usuario en el select
    fetch('php/abonos_usuario_api.php')
        .then(res => res.json())
        .then(abonos => {
            const selectAbono = document.getElementById('abonoActivo');
            // Limpiar opciones previas
            selectAbono.innerHTML = '<option value="">No usar abono</option>';
            
            // Filtrar solo los activos y con viajes restantes (si aplica)
            const abonosActivos = abonos.filter(a => a.estado === 'activo' && (a.viajes_restantes > 0 || a.viajes_totales === 0));
            
            abonosActivos.forEach(a => {
                let option = document.createElement('option');
                option.value = a.id_abono; // El value debe ser la ID real
                option.textContent = `${a.tipo.toUpperCase()} (Válido hasta ${a.fecha_fin})`;
                selectAbono.appendChild(option);
            });
        })
        .catch(err => console.error("Error cargando abonos:", err));
});

document.addEventListener('DOMContentLoaded', function() {
    
    const btnAplicarPromo = document.querySelector('.promo-section button');
    const inputPromo = document.getElementById('codigoPromo');
    const msgPromo = document.querySelector('.promo-msg');
    const selectAbono = document.getElementById('abonoActivo');
    const spanFinalPrice = document.getElementById('finalPrice');
    
    // Variables para gestionar el estado del pago
    let descuentoAplicado = 0; 
    let precioBase = 50.00; // OJO: Esto deberás cargarlo dinámicamente según el billete que haya elegido
    
    // Función para recalcular el precio total en la interfaz
    function actualizarPrecioFinal() {
        let precioCalculado = precioBase;
        
        // Si hay un abono seleccionado, el precio es 0€ y se anula el código promocional
        if (selectAbono && selectAbono.value !== "") {
            precioCalculado = 0;
            inputPromo.value = '';
            descuentoAplicado = 0;
            msgPromo.textContent = "Descuento por abono aplicado (100%)";
            msgPromo.style.color = "blue";
        } 
        // Si no hay abono, aplicamos el porcentaje de la promoción (si existe)
        else if (descuentoAplicado > 0) {
            precioCalculado = precioBase - (precioBase * (descuentoAplicado / 100));
        }
        
        // Actualizamos el HTML
        spanFinalPrice.textContent = precioCalculado.toFixed(2).replace('.', ',') + " €";
    }

    // Si el usuario cambia el selector de abonos, actualizamos el precio
    if(selectAbono) {
        selectAbono.addEventListener('change', actualizarPrecioFinal);
    }

    // Evento al hacer click en "Aplicar" código
    if(btnAplicarPromo) {
        btnAplicarPromo.addEventListener('click', function() {
            const codigo = inputPromo.value.trim();
            
            if (codigo === "") {
                msgPromo.textContent = "Introduce un código.";
                msgPromo.style.color = "red";
                descuentoAplicado = 0;
                actualizarPrecioFinal();
                return;
            }

            // Llamamos a nuestra nueva API
            fetch(`php/validar_promocion_api.php?codigo=${encodeURIComponent(codigo)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.valido) {
                        msgPromo.textContent = `¡Descuento aplicado! (-${data.descuento_porcentaje}%)`;
                        msgPromo.style.color = "green";
                        descuentoAplicado = parseFloat(data.descuento_porcentaje);
                        
                        // Si se aplica un promo, deseleccionamos el abono para que no se pisen
                        if(selectAbono) selectAbono.value = "";
                        
                        actualizarPrecioFinal();
                    } else {
                        msgPromo.textContent = data.mensaje;
                        msgPromo.style.color = "red";
                        descuentoAplicado = 0;
                        actualizarPrecioFinal();
                    }
                })
                .catch(err => {
                    console.error("Error al validar:", err);
                    msgPromo.textContent = "Error de conexión.";
                    msgPromo.style.color = "red";
                });
        });
    }
});