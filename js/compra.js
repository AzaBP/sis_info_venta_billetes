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