// ================= VARIABLES GLOBALES =================
let viajeSeleccionado = null;
let asientoSeleccionadoNum = null;
let precioBaseViaje = 0;
let precioCalculadoAsiento = 0; 
let precioFinalConDescuento = 0; 

let estado = {
    pasoActual: 1,
    trenSeleccionado: null,
    precioBase: 0,
    asientoSeleccionado: null,
    vagonActual: 1,
    maxVagones: 3
};

function tr(key, fallback, params = {}) {
    const i18n = window.trainwebI18n;
    let text = (i18n && typeof i18n.t === 'function') ? i18n.t(key) : null;
    if (!text) text = fallback;
    Object.keys(params).forEach((k) => {
        text = text.replace(`{${k}}`, params[k]);
    });
    return text;
}

function localizeAbonoOptions() {
    const select = document.getElementById('select-abono');
    if (!select) return;

    Array.from(select.options).forEach((opt, idx) => {
        if (idx === 0) return;
        let label = opt.textContent || '';
        label = label.replace(/^\s*Abono\s+/i, `${tr('abono_label', 'Abono')} `);
        label = label.replace(/\((\d+)\s+viajes rest\.\)/i, (_m, n) => `(${n} ${tr('viajes_rest_short', 'viajes rest.')})`);
        label = label.replace(/\(Ilimitado\)/i, `(${tr('ilimitado', 'Ilimitado')})`);
        opt.textContent = label.trim();
    });
}

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

    document.querySelectorAll('.seat').forEach(s => {
        s.classList.remove('selected', 'occupied');
    });

    estado.vagonActual = 1;
    for(let i=1; i<=estado.maxVagones; i++) {
        const wagon = document.getElementById(`wagon${i}`);
        if(wagon) {
            if(i === 1) wagon.classList.remove('hidden');
            else wagon.classList.add('hidden');
        }
    }
    const currentWagon = document.getElementById('currentWagonNum');
    if(currentWagon) currentWagon.textContent = '1';
    actualizarEstadoFlechas();

    // Cargar asientos ocupados de la BD
    fetch(`./php/api_asientos_ocupados.php?id_viaje=${id_viaje}`)
        .then(res => res.json())
        .then(data => {
            if (data.exito && data.ocupados) {
                data.ocupados.forEach(item => {
                    let asientoReal = item.numero_asiento !== undefined ? item.numero_asiento : item;
                    let numFormateado = String(asientoReal).padStart(3, '0');
                    let asientoHtml = document.querySelector(`.seat[data-seat='${numFormateado}']`);
                    if (asientoHtml) asientoHtml.classList.add('occupied'); 
                });
            }
        })
        .catch(err => console.error("Error cargando asientos ocupados:", err));

    irAPaso(2);
}

// ================= NAVEGACIÓN ENTRE PASOS =================
function irAPaso(numeroPaso) {
    if (numeroPaso > estado.pasoActual && numeroPaso > 1 && !estado.trenSeleccionado) return;
    if (numeroPaso >= 3 && !estado.asientoSeleccionado) return;

    // 1. Ocultar todas las secciones
    document.getElementById('sectionTrains').classList.add('hidden');
    document.getElementById('sectionSeats').classList.add('hidden');
    document.getElementById('sectionSummary').classList.add('hidden');
    document.getElementById('sectionPayment').classList.add('hidden');

    document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));

    // 2. Mostrar solo la sección del paso actual
    if (numeroPaso === 1) {
        document.getElementById('sectionTrains').classList.remove('hidden');
    } else if (numeroPaso === 2) {
        document.getElementById('sectionSeats').classList.remove('hidden');
        actualizarEstadoFlechas();
        const currentWagon = document.getElementById('currentWagonNum');
        if(currentWagon) currentWagon.textContent = estado.vagonActual.toString();
    } else if (numeroPaso === 3) {
        document.getElementById('sectionSummary').classList.remove('hidden');
        cargarDatosResumen(); 
    } else if (numeroPaso === 4) {
        document.getElementById('sectionPayment').classList.remove('hidden');
        document.getElementById('finalPaymentPrice').textContent = precioFinalConDescuento.toFixed(2) + " €"; 
    }

    const pasoElement = document.getElementById(`step${numeroPaso}`);
    if(pasoElement) pasoElement.classList.add('active');
    
    for(let i=1; i < numeroPaso; i++) {
        const prevStep = document.getElementById(`step${i}`);
        if(prevStep) prevStep.classList.add('completed');
    }

    estado.pasoActual = numeroPaso;
}

// ================= PASO 2: ASIENTOS =================
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
    const todosSeleccionados = document.querySelectorAll('.seat.selected');
    todosSeleccionados.forEach(asiento => asiento.classList.remove('selected'));
    elementoHtml.classList.add('selected');

    asientoSeleccionadoNum = numero_asiento;
    estado.asientoSeleccionado = elementoHtml; 

    // Sumar 15€ si es Primera Clase (Vagón 1)
    let precioFinal = parseFloat(precioBaseViaje) || 0; 
    if(estado.vagonActual === 1) precioFinal += 15; 

    const displaySeat = document.getElementById('displaySeat');
    const displayPrice = document.getElementById('displayPrice');
    
    if (displaySeat) displaySeat.textContent = `Vagón ${estado.vagonActual} - ${numero_asiento}`;
    if (displayPrice) displayPrice.textContent = precioFinal.toFixed(2) + " €";

    const btnToPayment = document.getElementById('btnToPayment');
    if (btnToPayment) btnToPayment.disabled = false;
    
    precioCalculadoAsiento = precioFinal;
    precioFinalConDescuento = precioFinal;
}

// ================= INICIALIZACIÓN =================
document.addEventListener('DOMContentLoaded', function() {
    // 1. Asientos
    document.querySelectorAll('.seat').forEach(asiento => {
        asiento.addEventListener('click', function() {
            if (this.classList.contains('occupied')) return;
            const numero_asiento = this.getAttribute('data-seat');
            seleccionarAsiento(this, numero_asiento);
        });
    });

    // 2. Escuchadores de los Desplegables del Paso 3
    const selectPromo = document.getElementById('codigoPromo');
    if (selectPromo) selectPromo.addEventListener('change', calcularPrecioFinal);
    
    const selectAbono = document.getElementById('abonoActivo') || document.getElementById('select-abono');
    if (selectAbono) selectAbono.addEventListener('change', calcularPrecioFinal);

    localizeAbonoOptions();

    // // 3. Cargar abonos del usuario logueado usando tu API original
    // fetch('php/abonos_usuario_api.php')
    //     .then(res => { if(res.ok) return res.json(); else throw new Error(); })
    //     .then(abonos => {
    //         if(!selectAbono) return;
    //         selectAbono.innerHTML = '<option value="">No usar abono</option>';
            
    //         // Filtrar los abonos activos con viajes disponibles
    //         const abonosActivos = abonos.filter(a => a.estado === 'activo' && (a.viajes_restantes > 0 || a.viajes_totales === 0));
            
    //         abonosActivos.forEach(a => {
    //             let option = document.createElement('option');
    //             option.value = a.id_abono;
    //             option.text = `${a.tipo} (Quedan ${a.viajes_restantes} viajes)`;
    //             selectAbono.appendChild(option);
    //         });
    //     })
    //     .catch(err => {
    //         console.warn("No se encontraron abonos o usuario no logueado.");
    //         if(selectAbono) selectAbono.innerHTML = '<option value="">Sin abonos disponibles</option>';
    //     });
});

// ================= PASO 3: RESUMEN Y CÁLCULO DINÁMICO =================
function cargarDatosResumen() {
    document.getElementById('summaryTrain').textContent = "Tren #" + String(viajeSeleccionado).padStart(4, '0');
    document.getElementById('summarySeat').textContent = `${tr('vagon', 'Vagón')} ${estado.vagonActual} - ${tr('asiento', 'Asiento')} ${asientoSeleccionadoNum}`;
    document.getElementById('summaryBasePrice').textContent = precioCalculadoAsiento.toFixed(2) + " €";
    
    // Ejecutar por si hay opciones preseleccionadas
    calcularPrecioFinal();
}

function calcularPrecioFinal() {
    let descuento = 0;
    const msg = document.getElementById('promoMsg');
    
    // Leer datos del desplegable de promociones
    const selectPromo = document.getElementById('codigoPromo');
    const optionPromo = selectPromo.options[selectPromo.selectedIndex];
    const porcentajeDescuento = parseFloat(optionPromo.getAttribute('data-descuento')) || 0;
    const codigoPromo = selectPromo.value;

    // Leer datos del abono
    const selectAbono = document.getElementById('abonoActivo') || document.getElementById('select-abono');
    const abonoActivo = selectAbono ? selectAbono.value : "";

    msg.textContent = "";

    // PRIORIDAD 1: Si selecciona Abono, el viaje es gratis
    if (abonoActivo !== "") {
        descuento = precioCalculadoAsiento; 
        msg.textContent = tr('abono_aplicado', '¡Abono aplicado! El viaje se descontará de tu saldo.');
        msg.style.color = "#17632A"; 
        
        // Desmarcamos la promo si elige abono para no duplicar/confundir
        if (codigoPromo !== "") selectPromo.value = ""; 
    } 
    // PRIORIDAD 2: Si elige una promoción de la Base de Datos
    else if (codigoPromo !== "") {
        descuento = precioCalculadoAsiento * (porcentajeDescuento / 100);
        msg.textContent = tr('promo_aplicada', 'Promoción aplicada: -{pct}%', { pct: porcentajeDescuento });
        msg.style.color = "#17632A";
    }

    // Calcular y actualizar interfaz
    precioFinalConDescuento = Math.max(0, precioCalculadoAsiento - descuento);
    document.getElementById('summaryFinalPrice').textContent = precioFinalConDescuento.toFixed(2) + " €";
    
    // Cambiar texto del botón si el importe es 0€
    const btnPaso3 = document.getElementById('btnPaso3');
    if (btnPaso3) {
        btnPaso3.textContent = precioFinalConDescuento === 0
            ? tr('confirmar_reserva_gratis', 'Confirmar Reserva Gratis')
            : tr('continuar_pago_seguro', 'Continuar al Pago Seguro');
    }
}

// ================= PASO 4: PAGO FINAL =================
function confirmarReserva() {
    // Validación básica antes de enviar
    if (!viajeSeleccionado || !asientoSeleccionadoNum) {
        alert(tr('faltan_datos_reserva', 'Faltan datos para la reserva.'));
        return;
    }

    // Deshabilitar botón para evitar dobles envíos
    const btn = document.querySelector('.btn-pay-confirm');
    if (btn) btn.disabled = true;

    fetch('php/api_reservar.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_viaje: viajeSeleccionado,
            numero_asiento: asientoSeleccionadoNum,
            precio: precioFinalConDescuento
        })
    })
    .then(async (res) => {
        const raw = await res.text();
        let data;

        try {
            data = raw ? JSON.parse(raw) : {};
        } catch (_parseError) {
            throw new Error(raw || `Respuesta no JSON (${res.status})`);
        }

        if (!res.ok) {
            throw new Error(data.error || `HTTP ${res.status}`);
        }

        return data;
    })
    .then(data => {
        if (data.exito) {
            alert(tr('reserva_exito', '¡Reserva realizada con éxito!\nID de reserva: {id}', { id: data.id_mongo }));
            window.location.href = 'index.php';
        } else {
            const err = data.error || tr('error_desconocido', 'Error desconocido.');
            alert(tr('error_reservar', 'Error al reservar: {error}', { error: err }));
            if (btn) btn.disabled = false;
        }
    })
    .catch(err => {
        alert(tr('error_reservar', 'Error al reservar: {error}', { error: err.message || err }));
        if (btn) btn.disabled = false;
    });
}