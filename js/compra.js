// ================= VARIABLES GLOBALES =================
let viajeSeleccionado = null;
let asientoSeleccionadoNum = null;
let precioBaseViaje = 0;

let estado = {
    pasoActual: 1,
    trenSeleccionado: null,
    precioBase: 0,
    asientoSeleccionado: null,
    vagonActual: 1,
    maxVagones: 3
};

// ================= PASO 1: SELECCIÓN DE TREN =================
function seleccionarTren(id_viaje, tipo_tren, precio) {
    if(typeof tipo_tren === 'number') {
        precio = tipo_tren;
    }

    viajeSeleccionado = id_viaje;
    precioBaseViaje = parseFloat(precio) || 0;
    
    estado.trenSeleccionado = id_viaje;
    estado.precioBase = precioBaseViaje;

    const lblTren = document.getElementById('lblTrenSeleccionado');
    if (lblTren) lblTren.textContent = id_viaje;

    // 1. Limpiamos visualmente todos los asientos por si venimos de otro tren
    document.querySelectorAll('.seat').forEach(s => {
        s.classList.remove('selected', 'occupied');
    });

        // Reiniciar vagonActual y mostrar vagon 1
        estado.vagonActual = 1;
        for(let i=1; i<=estado.maxVagones; i++) {
            const wagon = document.getElementById(`wagon${i}`);
            if(wagon) {
                if(i === 1) {
                    wagon.classList.remove('hidden');
                } else {
                    wagon.classList.add('hidden');
                }
            }
        }
        const currentWagon = document.getElementById('currentWagonNum');
        if(currentWagon) currentWagon.textContent = '1';
        actualizarEstadoFlechas();

        // 2. PEDIMOS A LA BASE DE DATOS LOS ASIENTOS OCUPADOS
        fetch(`./php/api_asientos_ocupados.php?id_viaje=${id_viaje}`)
            .then(res => res.json())
            .then(data => {
                if (data.exito && data.ocupados) {
                    data.ocupados.forEach(item => {
                        // 1. Extraemos el número real del objeto que devuelve MongoDB
                        let asientoReal = item.numero_asiento !== undefined ? item.numero_asiento : item;
                        // 2. Lo convertimos a texto con 3 cifras (Ej: 62 -> "062")
                        let numFormateado = String(asientoReal).padStart(3, '0');
                        // 3. Buscamos el div y lo pintamos de gris
                        let asientoHtml = document.querySelector(`.seat[data-seat='${numFormateado}']`);
                        if (asientoHtml) {
                            asientoHtml.classList.add('occupied'); 
                        }
                    });
                }
            })
            .catch(err => console.error("Error cargando asientos ocupados:", err));

    irAPaso(2);
}

// ================= NAVEGACIÓN ENTRE PASOS =================
function irAPaso(numeroPaso) {
    if (numeroPaso > estado.pasoActual && numeroPaso > 1 && !estado.trenSeleccionado) return;
    if (numeroPaso === 3 && !estado.asientoSeleccionado) return;

    document.getElementById('sectionTrains').classList.add('hidden');
    document.getElementById('sectionSeats').classList.add('hidden');
    document.getElementById('sectionPayment').classList.add('hidden');

    document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));

    if (numeroPaso === 1) {
        document.getElementById('sectionTrains').classList.remove('hidden');
    } else if (numeroPaso === 2) {
        document.getElementById('sectionSeats').classList.remove('hidden');
        actualizarEstadoFlechas();
        const currentWagon = document.getElementById('currentWagonNum');
        if(currentWagon) currentWagon.textContent = estado.vagonActual.toString();
    } else if (numeroPaso === 3) {
        document.getElementById('sectionPayment').classList.remove('hidden');
    }

    const pasoElement = document.getElementById(`step${numeroPaso}`);
    if(pasoElement) pasoElement.classList.add('active');
    
    for(let i=1; i < numeroPaso; i++) {
        const prevStep = document.getElementById(`step${i}`);
        if(prevStep) prevStep.classList.add('completed');
    }

    estado.pasoActual = numeroPaso;
}

// ================= PASO 2: SELECCIÓN DE ASIENTO =================
function cambiarVagon(direccion) {
    let nuevoVagon = estado.vagonActual + direccion;
    if (nuevoVagon < 1 || nuevoVagon > estado.maxVagones) return;

    document.getElementById(`wagon${estado.vagonActual}`).classList.add('hidden');
    document.getElementById(`wagon${nuevoVagon}`).classList.remove('hidden');
    
    const currentWagon = document.getElementById('currentWagonNum');
    if(currentWagon) currentWagon.textContent = `${nuevoVagon}`;
    
    estado.vagonActual = nuevoVagon;
    actualizarEstadoFlechas();
}

function actualizarEstadoFlechas() {
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');
    if(btnPrev) btnPrev.disabled = (estado.vagonActual === 1);
    if(btnNext) btnNext.disabled = (estado.vagonActual === estado.maxVagones);
}

function seleccionarAsiento(elementoHtml, numero_asiento) {
    // 1. Gestionar lo visual
    const todosSeleccionados = document.querySelectorAll('.seat.selected');
    todosSeleccionados.forEach(asiento => asiento.classList.remove('selected'));
    elementoHtml.classList.add('selected');

    // 2. Guardar variables
    asientoSeleccionadoNum = numero_asiento;
    estado.asientoSeleccionado = elementoHtml; 

    // 3. PRECIO: Suplemento Primera Clase (Vagon 1)
    let precioFinal = parseFloat(precioBaseViaje) || 0; 
    if(estado.vagonActual === 1) precioFinal += 15; 

    // 4. Actualizar textos de precios
    const displaySeat = document.getElementById('displaySeat');
    const displayPrice = document.getElementById('displayPrice');
    const precioAsientoElemento = document.getElementById('precioAsiento');
    const totalPagoElemento = document.getElementById('totalPago');
    const txtFinal = document.getElementById('finalPrice');

    if (displaySeat) displaySeat.textContent = `Vagón ${estado.vagonActual} - ${numero_asiento}`;
    if (displayPrice) displayPrice.textContent = precioFinal.toFixed(2) + " €";
    if (precioAsientoElemento) precioAsientoElemento.innerText = precioFinal.toFixed(2) + " €";
    if (totalPagoElemento) totalPagoElemento.innerText = precioFinal.toFixed(2) + " €";
    if (txtFinal) txtFinal.textContent = precioFinal.toFixed(2) + " €";

    // 5. Habilitar botones de avanzar y pagar
    const btnToPayment = document.getElementById('btnToPayment');
    if (btnToPayment) btnToPayment.disabled = false;
    
    const btnPagar = document.querySelector('.btn-pay-confirm');
    if (btnPagar) {
        btnPagar.disabled = false;
        btnPagar.style.opacity = "1";
    }
}

// ================= PASO 3: FINALIZAR Y GUARDAR EN MONGO =================
function confirmarReserva() {
    if (!viajeSeleccionado || !asientoSeleccionadoNum) {
        alert("Por favor, selecciona un asiento antes de pagar.");
        return;
    }

    const btnPagar = document.querySelector('.btn-pay-confirm');
    if (btnPagar) {
        btnPagar.innerText = "Procesando...";
        btnPagar.disabled = true;
    }

    fetch('./php/api_reservar.php', {
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
            alert("¡Compra realizada con éxito! ID de reserva: " + data.id_mongo);
            window.location.href = "compra.php"; // Te redirige al principio para ver el asiento bloqueado
        } else {
            alert("Error: " + data.error);
            if(btnPagar) {
                btnPagar.disabled = false;
                btnPagar.innerText = "Pagar";
            }
        }
    })
    .catch(error => {
        console.error("Error en Fetch:", error);
        alert("No se pudo conectar con el servidor de reserva.");
        if(btnPagar) {
            btnPagar.disabled = false;
            btnPagar.innerText = "Pagar";
        }
    });
}

// ================= INICIALIZACIÓN (Al cargar la página) =================
document.addEventListener('DOMContentLoaded', function() {
    // 1. Asignar clics a los asientos
    document.querySelectorAll('.seat').forEach(asiento => {
        asiento.addEventListener('click', function() {
            if (this.classList.contains('occupied')) return;
            const numero_asiento = this.getAttribute('data-seat');
            seleccionarAsiento(this, numero_asiento);
        });
    });

    // 2. Validación y asistencia en el formulario de pago (mensajes solo en blur)
    const formPago = document.querySelector('.payment-form');
    if(formPago) {
        // Definición de campos y mensajes
        const campos = [
            { selector: 'input[type="text"]', name: 'titular', error: 'Introduce el nombre del titular.' },
            { selector: 'input[type="text"][maxlength="19"]', name: 'numero', error: 'Introduce un número de tarjeta válido.' },
            { selector: 'input[type="text"]', name: 'caducidad', error: 'Introduce la fecha de caducidad (MM/AA).' },
            { selector: 'input[type="password"]', name: 'cvv', error: 'Introduce el CVV (3 dígitos).' }
        ];
        // Asignar mensajes de error
        formPago.querySelectorAll('.form-group').forEach((group, idx) => {
            if(!group.querySelector('.form-error')) {
                let errorSpan = document.createElement('span');
                errorSpan.className = 'form-error';
                errorSpan.innerText = campos[idx] ? campos[idx].error : 'Campo obligatorio';
                group.appendChild(errorSpan);
            }
        });

        // Validación individual
        function validarCampo(input, idx, mostrarError) {
            let value = input.value.trim();
            let group = input.closest('.form-group');
            let errorSpan = group.querySelector('.form-error');
            let valid = true;
            let errorMsg = null;
            if(idx === 0) { // Titular
                valid = value.length > 2;
            } else if(idx === 1) { // Número tarjeta
                valid = /^\d{16,19}$/.test(value.replace(/\s+/g, ''));
            } else if(idx === 2) { // Caducidad
                // Validar formato MM/AA
                if(!/^\d{2}\/\d{2}$/.test(value)) {
                    valid = false;
                    errorMsg = 'Formato inválido. Usa MM/AA.';
                } else {
                    let [mm, aa] = value.split('/');
                    mm = parseInt(mm, 10);
                    aa = parseInt(aa, 10);
                    let now = new Date();
                    let currMM = now.getMonth() + 1;
                    let currAA = parseInt(String(now.getFullYear()).slice(-2), 10);
                    if(mm < 1 || mm > 12) {
                        valid = false;
                        errorMsg = 'Mes inválido.';
                    } else if(aa < currAA || (aa === currAA && mm < currMM)) {
                        valid = false;
                        errorMsg = 'La fecha debe ser posterior a la actual.';
                    }
                }
            } else if(idx === 3) { // CVV
                valid = /^\d{3}$/.test(value);
            }
            if(!valid) {
                group.classList.add('error');
                if(errorMsg) errorSpan.innerText = errorMsg;
                else errorSpan.innerText = campos[idx] ? campos[idx].error : 'Campo obligatorio';
                if(mostrarError) errorSpan.style.display = 'block';
                else errorSpan.style.display = 'none';
            } else {
                group.classList.remove('error');
                errorSpan.innerText = campos[idx] ? campos[idx].error : 'Campo obligatorio';
                errorSpan.style.display = 'none';
            }
            return valid;
        }

        // Validación en tiempo real (solo borde rojo)
        formPago.querySelectorAll('input').forEach((input, idx) => {
            input.addEventListener('input', function() {
                // Formateo número de tarjeta en bloques de 4
                if(idx === 1) {
                    let raw = input.value.replace(/\D/g, '');
                    let formatted = raw.replace(/(.{4})/g, '$1 ').trim();
                    input.value = formatted;
                }
                // Formateo automático de barra en caducidad tras el segundo dígito
                if(idx === 2) {
                    let raw = input.value.replace(/[^\d]/g, '');
                    if(raw.length > 2 && input.value.indexOf('/') === -1) {
                        input.value = raw.slice(0,2) + '/' + raw.slice(2);
                    }
                }
                validarCampo(input, idx, false); // No mostrar mensaje, solo borde
            });
            input.addEventListener('blur', function() {
                validarCampo(input, idx, true); // Mostrar mensaje en blur
            });
        });

        // Validación al enviar
        formPago.addEventListener('submit', function(e) {
            let valid = true;
            formPago.querySelectorAll('input').forEach((input, idx) => {
                if(!validarCampo(input, idx, true)) valid = false;
            });
            if(!valid) {
                e.preventDefault();
                return;
            }
            e.preventDefault();
            confirmarReserva();
        });
    }

    // 3. Lógica visual de primera clase
    const premiumAisle = document.querySelector('.aisle-horizontal.premium');
    if (premiumAisle && estado.vagonActual === 1) { 
        premiumAisle.textContent = ''; 
    }

    // 4. Lógica de Abonos (Tu código original)
    fetch('php/abonos_usuario_api.php')
    .then(res => { if(res.ok) return res.json(); else throw new Error(); })
    .then(abonos => {
        const selectAbono = document.getElementById('abonoActivo');
        if(!selectAbono) return;
        selectAbono.innerHTML = '<option value="">No usar abono</option>';
        const abonosActivos = abonos.filter(a => a.estado === 'activo' && (a.viajes_restantes > 0 || a.viajes_totales === 0));
        abonosActivos.forEach(a => {
            let option = document.createElement('option');
            option.value = a.id_abono;
            option.textContent = `${a.tipo.toUpperCase()} (Válido hasta ${a.fecha_fin})`;
            selectAbono.appendChild(option);
        });
    })
    .catch(err => console.log("Abonos no disponibles en este momento."));
});