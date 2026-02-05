// VARIABLES DE ESTADO
let estado = {
    pasoActual: 1,
    trenSeleccionado: null,
    precioBase: 0,
    asientoSeleccionado: null,
    vagonActual: 1
};

// ================= NAVEGACIÓN ENTRE PASOS =================
function irAPaso(numeroPaso) {
    // Solo permitir volver atrás o avanzar si ya se completó el paso previo
    if (numeroPaso > estado.pasoActual && numeroPaso > 1 && !estado.trenSeleccionado) return;
    if (numeroPaso === 3 && !estado.asientoSeleccionado) return;

    // Ocultar todas las secciones
    document.getElementById('sectionTrains').classList.add('hidden');
    document.getElementById('sectionSeats').classList.add('hidden');
    document.getElementById('sectionPayment').classList.add('hidden');

    // Desactivar estilos de pasos
    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active');
    });

    // Mostrar sección actual
    if (numeroPaso === 1) {
        document.getElementById('sectionTrains').classList.remove('hidden');
    } else if (numeroPaso === 2) {
        document.getElementById('sectionSeats').classList.remove('hidden');
    } else if (numeroPaso === 3) {
        document.getElementById('sectionPayment').classList.remove('hidden');
    }

    // Actualizar estilo barra progreso
    const pasoElement = document.getElementById(`step${numeroPaso}`);
    pasoElement.classList.add('active');
    
    // Marcar anteriores como completados (clicables)
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
    
    // Ir al paso 2
    irAPaso(2);
}

// ================= PASO 2: SELECCIÓN DE ASIENTO =================

// Cambiar de vagón (flechas)
function cambiarVagon(direccion) {
    let nuevoVagon = estado.vagonActual + direccion;
    if (nuevoVagon < 1) nuevoVagon = 1;
    if (nuevoVagon > 3) nuevoVagon = 3;

    // Ocultar el actual
    document.getElementById(`wagon${estado.vagonActual}`).classList.add('hidden');
    // Mostrar el nuevo
    document.getElementById(`wagon${nuevoVagon}`).classList.remove('hidden');
    
    // Actualizar texto
    document.getElementById('currentWagonNum').textContent = nuevoVagon;
    estado.vagonActual = nuevoVagon;
}

// Lógica de click en asiento
document.querySelectorAll('.seat').forEach(asiento => {
    asiento.addEventListener('click', function() {
        if (this.classList.contains('occupied')) return;

        // Desmarcar previo
        if (estado.asientoSeleccionado) {
            estado.asientoSeleccionado.classList.remove('selected');
        }

        // Marcar nuevo
        this.classList.add('selected');
        estado.asientoSeleccionado = this;

        // Actualizar UI
        const nombreAsiento = this.getAttribute('data-seat');
        document.getElementById('displaySeat').textContent = `Vagón ${estado.vagonActual} - ${nombreAsiento}`;
        document.getElementById('displayPrice').textContent = estado.precioBase.toFixed(2) + " €";
        document.getElementById('finalPrice').textContent = estado.precioBase.toFixed(2) + " €";

        // Habilitar botón Continuar
        document.getElementById('btnToPayment').disabled = false;
    });
});

// ================= PASO 3: FINALIZAR =================
function finalizarCompra() {
    alert(`✅ ¡PAGO EXITOSO!\n\nBillete emitido para el tren ${estado.trenSeleccionado}.\nAsiento: ${estado.asientoSeleccionado.getAttribute('data-seat')}.\nSe ha enviado un correo con el billete.`);
    window.location.href = 'index.html';
}