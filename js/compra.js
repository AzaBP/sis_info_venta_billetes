let precioBase = 0;
let asientoSeleccionado = null;

// 1. SELECCIONAR UN TREN
function seleccionarTren(trainId, precio) {
    // Guardar datos
    precioBase = precio;
    document.getElementById('selectedTrainId').textContent = trainId;
    
    // Cambiar interfaz
    document.getElementById('trainResults').classList.add('hidden');
    document.getElementById('seatSelection').classList.remove('hidden');
    
    // Actualizar barra progreso
    document.getElementById('step1').classList.remove('active');
    document.getElementById('step2').classList.add('active');

    // Resetear asiento previo si hubiera
    resetAsientos();
}

// 2. VOLVER ATRÁS
function volverAResultados() {
    document.getElementById('seatSelection').classList.add('hidden');
    document.getElementById('trainResults').classList.remove('hidden');
    
    document.getElementById('step2').classList.remove('active');
    document.getElementById('step1').classList.add('active');
}

// 3. SELECCIONAR ASIENTO (Lógica del mapa)
function toggleSeat(asientoDiv) {
    // Si ya hay uno seleccionado, lo desmarcamos
    if (asientoSeleccionado) {
        asientoSeleccionado.classList.remove('selected');
    }

    // Marcar el nuevo
    asientoDiv.classList.add('selected');
    asientoSeleccionado = asientoDiv;

    // Calcular precio final (Asiento normal vs Mesa)
    const suplemento = parseFloat(asientoDiv.dataset.price);
    const precioFinal = precioBase + suplemento;

    // Actualizar UI
    document.getElementById('selectedSeatId').textContent = asientoDiv.textContent;
    document.getElementById('totalPrice').textContent = precioFinal.toFixed(2);
    
    // Habilitar botón de pago
    document.getElementById('btnPay').disabled = false;
}

function resetAsientos() {
    asientoSeleccionado = null;
    document.getElementById('selectedSeatId').textContent = "Ninguno";
    document.getElementById('totalPrice').textContent = "0.00";
    document.getElementById('btnPay').disabled = true;
    
    // Quitar clase visual
    document.querySelectorAll('.seat.selected').forEach(s => s.classList.remove('selected'));
}

// 4. IR A PAGAR (Placeholder)
function irAlPago() {
    alert("¡Perfecto! Redirigiendo a pasarela de pago...");
    // Aquí iríamos a la lógica que te preguntaba antes
}