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
function seleccionarTren(id, precio) {
    estado.trenSeleccionado = id;
    estado.precioBase = precio;
    document.getElementById('lblTrenSeleccionado').textContent = id;
    irAPaso(2);
}

// ================= PASO 2: SELECCIÓN DE ASIENTO =================
function cambiarVagon(direccion) {
    let nuevoVagon = estado.vagonActual + direccion;
    
    if (nuevoVagon < 1) return;
    if (nuevoVagon > estado.maxVagones) return;

    document.getElementById(`wagon${estado.vagonActual}`).classList.add('hidden');
    document.getElementById(`wagon${nuevoVagon}`).classList.remove('hidden');
    
    let nombreVagon = "";
    if(nuevoVagon === 1) nombreVagon = "1 (Primera Clase)";
    else if(nuevoVagon === 2) nombreVagon = "2 (Turista)";
    else nombreVagon = "3 (Silencio)";

    document.getElementById('currentWagonNum').textContent = nombreVagon;
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

        if (estado.asientoSeleccionado) {
            estado.asientoSeleccionado.classList.remove('selected');
        }

        this.classList.add('selected');
        estado.asientoSeleccionado = this;

        // PRECIO: Suplemento Primera Clase (Vagon 1)
        let precioFinal = estado.precioBase;
        if(estado.vagonActual === 1) precioFinal += 15; 

        const nombreAsiento = this.getAttribute('data-seat');
        document.getElementById('displaySeat').textContent = `Vagón ${estado.vagonActual} - ${nombreAsiento}`;
        document.getElementById('displayPrice').textContent = precioFinal.toFixed(2) + " €";
        document.getElementById('finalPrice').textContent = precioFinal.toFixed(2) + " €";

        document.getElementById('btnToPayment').disabled = false;
    });
});

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