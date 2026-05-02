// ================= CONFIGURACION GLOBAL =================
const totalPasajeros = Math.max(1, Math.min(4, parseInt(window.compraConfig?.totalPasajeros || 1, 10)));
const pasajeroPrincipal = window.compraConfig?.pasajeroPrincipal || {};
const tripType = String(window.compraConfig?.tripType || 'oneway');
const esIdaVuelta = tripType === 'roundtrip';
const hasReturnOptions = Boolean(window.compraConfig?.hasReturnOptions);

let viajeSeleccionado = null;
let viajeVueltaSeleccionado = null;
let precioBaseViaje = 0;
let precioBaseViajeVuelta = 0;
let precioFinalConDescuento = 0;
let idaPendiente = null;

let estado = {
    pasoActual: 1,
    trenSeleccionado: null,
    precioBase: 0,
    asientosSeleccionados: [], // [{ numero, wagon, precio, tramo: 'ida'|'vuelta' }]
    datosPasajeros: [],
    vagonActual: 1,
    maxVagones: 3,
    tramoActual: 'ida' // para ida-vuelta, qué tramo se está seleccionando
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

function formatearEuros(valor) {
    return `${Number(valor || 0).toFixed(2)} €`;
}

function sumaPreciosAsientos() {
    if (!esIdaVuelta) {
        return estado.asientosSeleccionados.reduce((acc, s) => acc + (Number(s.precio) || 0), 0);
    }
    
    const asientosActuales = estado.asientosSeleccionados.filter(s => s.tramo === estado.tramoActual);
    return asientosActuales.reduce((acc, s) => acc + (Number(s.precio) || 0), 0);
}

function sumaPreciosAsientosTotales() {
    return estado.asientosSeleccionados.reduce((acc, s) => acc + (Number(s.precio) || 0), 0);
}

function abrirModalVuelta() {
    const modal = document.getElementById('returnTripModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
}

function cerrarModalVuelta() {
    const modal = document.getElementById('returnTripModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
}

function reservarSoloIdaDesdeModal() {
    const cfg = window.compraConfig || {};
    const params = new URLSearchParams();
    params.set('trip', 'oneway');
    params.set('origen', String(cfg.origen || ''));
    params.set('destino', String(cfg.destino || ''));
    params.set('fecha', String(cfg.fechaIda || ''));
    params.set('pasajeros', String(cfg.totalPasajeros || 1));

    if (idaPendiente && idaPendiente.id_viaje) {
        params.set('id_viaje', String(idaPendiente.id_viaje));
    }

    window.location.href = `compra.php?${params.toString()}`;
}

window.reservarSoloIdaDesdeModal = reservarSoloIdaDesdeModal;

function seleccionarTrenVuelta(id_viaje, tipo_tren, precio) {
    if (typeof tipo_tren === 'number') {
        precio = tipo_tren;
    }
    viajeVueltaSeleccionado = id_viaje;
    precioBaseViajeVuelta = parseFloat(precio) || 0;
    cerrarModalVuelta();

    if (idaPendiente) {
        const ida = idaPendiente;
        idaPendiente = null;
        seleccionarTren(ida.id_viaje, ida.tipo_tren, ida.precio, true);
    }
}

async function cargarAsientosOcupados(idViajeIda, idViajeVuelta = null) {
    const idsOcupados = new Set();

    const cargar = async (idViaje) => {
        const res = await fetch(`./php/api_asientos_ocupados.php?id_viaje=${idViaje}`);
        const data = await res.json();
        if (data.exito && data.ocupados) {
            data.ocupados.forEach((item) => {
                const asientoReal = item.numero_asiento !== undefined ? item.numero_asiento : item;
                idsOcupados.add(String(asientoReal).padStart(3, '0'));
            });
        }
    };

    await cargar(idViajeIda);
    if (idViajeVuelta) {
        await cargar(idViajeVuelta);
    }

    idsOcupados.forEach((numFormateado) => {
        const asientoHtml = document.querySelector(`.seat[data-seat='${numFormateado}']`);
        if (asientoHtml) asientoHtml.classList.add('occupied');
    });
}

// ================= PASO 1: SELECCION DE TREN =================
async function seleccionarTren(id_viaje, tipo_tren, precio, continuarConIdaVuelta = false) {
    if (typeof tipo_tren === 'number') {
        precio = tipo_tren;
    }

    if (esIdaVuelta && !continuarConIdaVuelta) {
        idaPendiente = { id_viaje, tipo_tren, precio };

        abrirModalVuelta();
        return;
    }

    if (esIdaVuelta && !viajeVueltaSeleccionado) {
        alert(tr('selecciona_vuelta_antes', 'Debes seleccionar primero un tren de vuelta.'));
        abrirModalVuelta();
        return;
    }

    viajeSeleccionado = id_viaje;
    precioBaseViaje = parseFloat(precio) || 0;

    estado.trenSeleccionado = id_viaje;
    estado.precioBase = precioBaseViaje;
    estado.asientosSeleccionados = [];
    estado.datosPasajeros = [];

    const lblTren = document.getElementById('lblTrenSeleccionado');
    if (lblTren) lblTren.textContent = id_viaje;

    document.querySelectorAll('.seat').forEach(s => {
        s.classList.remove('selected', 'occupied');
    });

    estado.vagonActual = 1;
    for (let i = 1; i <= estado.maxVagones; i++) {
        const wagon = document.getElementById(`wagon${i}`);
        if (wagon) {
            if (i === 1) wagon.classList.remove('hidden');
            else wagon.classList.add('hidden');
        }
    }

    const currentWagon = document.getElementById('currentWagonNum');
    if (currentWagon) currentWagon.textContent = '1';

    const displaySeat = document.getElementById('displaySeat');
    const displayPrice = document.getElementById('displayPrice');
    if (displaySeat) displaySeat.textContent = tr('ninguno', 'Ninguno');
    if (displayPrice) displayPrice.textContent = '0,00 €';

    const btnToPassengerData = document.getElementById('btnToPassengerData');
    if (btnToPassengerData) btnToPassengerData.disabled = true;

    actualizarEstadoFlechas();

    try {
        await cargarAsientosOcupados(id_viaje, esIdaVuelta ? viajeVueltaSeleccionado : null);
    } catch (err) {
        console.error('Error cargando asientos ocupados:', err);
    }

    irAPaso(2);
}

// ================= NAVEGACION ENTRE PASOS =================
function irAPaso(numeroPaso) {
    if (numeroPaso > 1 && !estado.trenSeleccionado) return;

    if (numeroPaso >= 3) {
        if (esIdaVuelta) {
            const asientosIda = estado.asientosSeleccionados.filter(s => s.tramo === 'ida').length;
            const asientosVuelta = estado.asientosSeleccionados.filter(s => s.tramo === 'vuelta').length;
            if (asientosIda !== totalPasajeros || asientosVuelta !== totalPasajeros) {
                alert(tr('selecciona_todos_asientos', 'Debes seleccionar {n} asientos por tramo.', { n: totalPasajeros }));
                return;
            }
        } else if (estado.asientosSeleccionados.length !== totalPasajeros) {
            alert(tr('selecciona_todos_asientos', 'Debes seleccionar {n} asientos.', { n: totalPasajeros }));
            return;
        }
    }

    if (numeroPaso >= 4 && !validarYGuardarDatosPasajeros()) {
        return;
    }

    document.getElementById('sectionTrains').classList.add('hidden');
    document.getElementById('sectionSeats').classList.add('hidden');
    document.getElementById('sectionPassengers').classList.add('hidden');
    document.getElementById('sectionSummary').classList.add('hidden');
    document.getElementById('sectionPayment').classList.add('hidden');

    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active', 'completed');
    });

    if (numeroPaso === 1) {
        document.getElementById('sectionTrains').classList.remove('hidden');
    } else if (numeroPaso === 2) {
        document.getElementById('sectionSeats').classList.remove('hidden');
        actualizarEstadoFlechas();
        const currentWagon = document.getElementById('currentWagonNum');
        if (currentWagon) currentWagon.textContent = estado.vagonActual.toString();
    } else if (numeroPaso === 3) {
        document.getElementById('sectionPassengers').classList.remove('hidden');
        renderizarFormulariosPasajeros();
    } else if (numeroPaso === 4) {
        document.getElementById('sectionSummary').classList.remove('hidden');
        cargarDatosResumen();
    } else if (numeroPaso === 5) {
        document.getElementById('sectionPayment').classList.remove('hidden');
        const finalPrice = document.getElementById('finalPaymentPrice');
        if (finalPrice) finalPrice.textContent = formatearEuros(precioFinalConDescuento);
    }

    for (let i = 1; i <= 5; i++) {
        const step = document.getElementById(`step${i}`);
        if (!step) continue;
        if (i < numeroPaso) step.classList.add('completed');
        if (i === numeroPaso) step.classList.add('active');
    }

    estado.pasoActual = numeroPaso;
}

// ================= PASO 2: ASIENTOS =================
function cambiarTramo(tramo) {
    if (!esIdaVuelta) return;
    
    estado.tramoActual = tramo;
    
    const btnIda = document.getElementById('btnTramoIda');
    const btnVuelta = document.getElementById('btnTramoVuelta');
    
    if (btnIda) {
        if (tramo === 'ida') {
            btnIda.style.background = '#0a2a66';
            btnIda.style.color = 'white';
        } else {
            btnIda.style.background = '#e7eefb';
            btnIda.style.color = '#0a2a66';
        }
    }
    
    if (btnVuelta) {
        if (tramo === 'vuelta') {
            btnVuelta.style.background = '#0a2a66';
            btnVuelta.style.color = 'white';
        } else {
            btnVuelta.style.background = '#e7eefb';
            btnVuelta.style.color = '#0a2a66';
        }
    }
    
    // Actualizar selección visual de asientos
    document.querySelectorAll('.seat:not(.occupied)').forEach(s => {
        s.classList.remove('selected');
        const asiento = s.getAttribute('data-seat');
        const estaSeleccionado = estado.asientosSeleccionados.some(
            a => a.numero === asiento && a.tramo === tramo
        );
        if (estaSeleccionado) {
            s.classList.add('selected');
        }
    });
    
    refrescarResumenAsientosSeleccionados();
}

window.cambiarTramo = cambiarTramo;

function cambiarVagon(direccion) {
    let nuevoVagon = estado.vagonActual + direccion;
    if (nuevoVagon < 1 || nuevoVagon > estado.maxVagones) return;

    document.getElementById(`wagon${estado.vagonActual}`).classList.add('hidden');
    document.getElementById(`wagon${nuevoVagon}`).classList.remove('hidden');

    const currentWagon = document.getElementById('currentWagonNum');
    if (currentWagon) currentWagon.textContent = `${nuevoVagon}`;

    estado.vagonActual = nuevoVagon;
    actualizarEstadoFlechas();
}

function actualizarEstadoFlechas() {
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');
    if (btnPrev) btnPrev.disabled = (estado.vagonActual === 1);
    if (btnNext) btnNext.disabled = (estado.vagonActual === estado.maxVagones);
}

function refrescarResumenAsientosSeleccionados() {
    const displaySeat = document.getElementById('displaySeat');
    const displayPrice = document.getElementById('displayPrice');
    const btnToPassengerData = document.getElementById('btnToPassengerData');

    const asientosTramoActual = esIdaVuelta 
        ? estado.asientosSeleccionados.filter(s => s.tramo === estado.tramoActual)
        : estado.asientosSeleccionados;

    if (asientosTramoActual.length === 0) {
        if (displaySeat) displaySeat.textContent = tr('ninguno', 'Ninguno');
        if (displayPrice) displayPrice.textContent = '0,00 €';
        if (btnToPassengerData) btnToPassengerData.disabled = true;
        return;
    }

    const seatsText = asientosTramoActual
        .map(s => `${tr('vagon', 'Vagón')} ${s.wagon} - ${s.numero}`)
        .join(' | ');

    if (displaySeat) displaySeat.textContent = seatsText;
    if (displayPrice) displayPrice.textContent = formatearEuros(sumaPreciosAsientos());
    
    // Verificar si se tienen asientos suficientes en TODOS los tramos para ida-vuelta
    if (esIdaVuelta) {
        const asientosIda = estado.asientosSeleccionados.filter(s => s.tramo === 'ida').length;
        const asientosVuelta = estado.asientosSeleccionados.filter(s => s.tramo === 'vuelta').length;
        if (btnToPassengerData) {
            btnToPassengerData.disabled = asientosIda !== totalPasajeros || asientosVuelta !== totalPasajeros;
        }
    } else {
        if (btnToPassengerData) {
            btnToPassengerData.disabled = estado.asientosSeleccionados.length !== totalPasajeros;
        }
    }
}

function seleccionarAsiento(elementoHtml, numero_asiento) {
    if (elementoHtml.classList.contains('occupied')) return;

    const wagon = parseInt(elementoHtml.getAttribute('data-wagon') || `${estado.vagonActual}`, 10);
    const suplementoClase = wagon === 1 ? 15 : 0;
    
    let precioAsiento = 0;
    if (!esIdaVuelta) {
        precioAsiento = (parseFloat(precioBaseViaje) || 0) + suplementoClase;
    } else {
        if (estado.tramoActual === 'ida') {
            precioAsiento = (parseFloat(precioBaseViaje) || 0) + suplementoClase;
        } else {
            precioAsiento = (parseFloat(precioBaseViajeVuelta) || 0) + suplementoClase;
        }
    }

    const idxExistente = estado.asientosSeleccionados.findIndex(s => 
        s.numero === numero_asiento && s.tramo === estado.tramoActual
    );
    
    if (idxExistente >= 0) {
        estado.asientosSeleccionados.splice(idxExistente, 1);
        elementoHtml.classList.remove('selected');
        refrescarResumenAsientosSeleccionados();
        return;
    }

    const asientosTramoActual = estado.asientosSeleccionados.filter(s => s.tramo === estado.tramoActual);
    if (asientosTramoActual.length >= totalPasajeros) {
        alert(tr('max_asientos', 'Solo puedes seleccionar {n} asientos.', { n: totalPasajeros }));
        return;
    }

    estado.asientosSeleccionados.push({
        numero: numero_asiento,
        wagon,
        precio: precioAsiento,
        tramo: estado.tramoActual
    });
    elementoHtml.classList.add('selected');

    refrescarResumenAsientosSeleccionados();
}

// ================= PASO 3: DATOS PASAJEROS =================
function renderizarFormulariosPasajeros() {
    const container = document.getElementById('passengersFormsContainer');
    if (!container) return;

    const datosPrevios = estado.datosPasajeros || [];
    const bloques = [];

    for (let i = 0; i < totalPasajeros; i++) {
        const previo = datosPrevios[i] || {};
        const nombre = i === 0 ? (previo.nombre || pasajeroPrincipal.nombre || '') : (previo.nombre || '');
        const apellidos = i === 0 ? (previo.apellidos || pasajeroPrincipal.apellidos || '') : (previo.apellidos || '');
        const email = i === 0 ? (previo.email || pasajeroPrincipal.email || '') : (previo.email || '');

        bloques.push(`
            <div class="passenger-card">
                <h4>${tr('pasajero_n', 'Pasajero {n}', { n: i + 1 })}</h4>
                <div class="passenger-grid">
                    <div class="form-group full-width">
                        <label>${tr('nombre', 'Nombre')}</label>
                        <input type="text" id="pasajero_nombre_${i}" value="${String(nombre).replace(/"/g, '&quot;')}" maxlength="80" required>
                        <span class="input-error" id="err_nombre_${i}" style="display:none;"></span>
                    </div>
                    <div class="form-group full-width">
                        <label>${tr('apellidos', 'Apellidos')}</label>
                        <input type="text" id="pasajero_apellidos_${i}" value="${String(apellidos).replace(/"/g, '&quot;')}" maxlength="120" required>
                        <span class="input-error" id="err_apellidos_${i}" style="display:none;"></span>
                    </div>
                    <div class="form-group full-width">
                        <label>${tr('documento_identidad', 'Documento de identidad')}</label>
                        <input type="text" id="pasajero_documento_${i}" value="${String(previo.documento || '').replace(/"/g, '&quot;')}" maxlength="20" required>
                        <span class="input-error" id="err_documento_${i}" style="display:none;"></span>
                    </div>
                    <div class="form-group full-width">
                        <label>${tr('email', 'Email')}</label>
                        <input type="email" id="pasajero_email_${i}" value="${String(email).replace(/"/g, '&quot;')}" maxlength="120" required>
                        <span class="input-error" id="err_email_${i}" style="display:none;"></span>
                    </div>
                </div>
            </div>
        `);
    }

    container.innerHTML = bloques.join('');
}

function validarDNI(dni) {
    const dniLimpio = dni.toUpperCase().replace(/\s/g, '');
    if (dniLimpio.length < 6) return false;
    if (/^\d{8}[A-Z]$/.test(dniLimpio)) return true;
    if (/^[XYZ]\d{7}[A-Z]$/.test(dniLimpio)) return true;
    if (/^[A-Z0-9]{6,20}$/.test(dniLimpio)) return true;
    return false;
}

function validarYGuardarDatosPasajeros() {
    const datos = [];
    let hayErrores = false;

    for (let i = 0; i < totalPasajeros; i++) {
        const inputNombre = document.getElementById(`pasajero_nombre_${i}`);
        const inputApellidos = document.getElementById(`pasajero_apellidos_${i}`);
        const inputDocumento = document.getElementById(`pasajero_documento_${i}`);
        const inputEmail = document.getElementById(`pasajero_email_${i}`);

        const errNombre = document.getElementById(`err_nombre_${i}`);
        const errApellidos = document.getElementById(`err_apellidos_${i}`);
        const errDocumento = document.getElementById(`err_documento_${i}`);
        const errEmail = document.getElementById(`err_email_${i}`);

        const nombre = (inputNombre?.value || '').trim();
        const apellidos = (inputApellidos?.value || '').trim();
        const documento = (inputDocumento?.value || '').trim();
        const email = (inputEmail?.value || '').trim();

        if (inputNombre) inputNombre.classList.remove('input-invalid');
        if (inputApellidos) inputApellidos.classList.remove('input-invalid');
        if (inputDocumento) inputDocumento.classList.remove('input-invalid');
        if (inputEmail) inputEmail.classList.remove('input-invalid');

        if (errNombre) errNombre.style.display = 'none';
        if (errApellidos) errApellidos.style.display = 'none';
        if (errDocumento) errDocumento.style.display = 'none';
        if (errEmail) errEmail.style.display = 'none';

        let pasajeroValido = true;

        if (!nombre || nombre.length < 2) {
            if (errNombre) {
                errNombre.textContent = 'El nombre debe tener al menos 2 caracteres.';
                errNombre.style.display = 'block';
            }
            if (inputNombre) inputNombre.classList.add('input-invalid');
            pasajeroValido = false;
        }

        if (!apellidos || apellidos.length < 2) {
            if (errApellidos) {
                errApellidos.textContent = 'Los apellidos deben tener al menos 2 caracteres.';
                errApellidos.style.display = 'block';
            }
            if (inputApellidos) inputApellidos.classList.add('input-invalid');
            pasajeroValido = false;
        }

        if (!documento) {
            if (errDocumento) {
                errDocumento.textContent = 'El documento es requerido.';
                errDocumento.style.display = 'block';
            }
            if (inputDocumento) inputDocumento.classList.add('input-invalid');
            pasajeroValido = false;
        } else if (!validarDNI(documento)) {
            if (errDocumento) {
                errDocumento.textContent = 'El documento no es valido (DNI, NIE o pasaporte).';
                errDocumento.style.display = 'block';
            }
            if (inputDocumento) inputDocumento.classList.add('input-invalid');
            pasajeroValido = false;
        }

        if (!email) {
            if (errEmail) {
                errEmail.textContent = 'El email es requerido.';
                errEmail.style.display = 'block';
            }
            if (inputEmail) inputEmail.classList.add('input-invalid');
            pasajeroValido = false;
        } else {
            const emailValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            if (!emailValido) {
                if (errEmail) {
                    errEmail.textContent = 'El email no es valido.';
                    errEmail.style.display = 'block';
                }
                if (inputEmail) inputEmail.classList.add('input-invalid');
                pasajeroValido = false;
            }
        }

        if (!pasajeroValido) {
            hayErrores = true;
        } else {
            datos.push({ nombre, apellidos, documento, email });
        }
    }

    if (hayErrores) return false;
    estado.datosPasajeros = datos;
    return true;
}

// ================= PASO 4: RESUMEN Y DESCUENTOS =================
function cargarDatosResumen() {
    const summaryTrain = document.getElementById('summaryTrain');
    const summarySeat = document.getElementById('summarySeat');
    const summaryBasePrice = document.getElementById('summaryBasePrice');

    if (summaryTrain) {
        if (esIdaVuelta && viajeVueltaSeleccionado) {
            summaryTrain.textContent =
                'Ida #' + String(viajeSeleccionado).padStart(4, '0') +
                ' | Vuelta #' + String(viajeVueltaSeleccionado).padStart(4, '0');
        } else {
            summaryTrain.textContent = 'Tren #' + String(viajeSeleccionado).padStart(4, '0');
        }
    }
    if (summarySeat) {
        summarySeat.textContent = estado.asientosSeleccionados
            .map(s => `${tr('vagon', 'Vagón')} ${s.wagon} - ${tr('asiento', 'Asiento')} ${s.numero}`)
            .join(' | ');
    }
    if (summaryBasePrice) summaryBasePrice.textContent = formatearEuros(sumaPreciosAsientosTotales());

    calcularPrecioFinal();
}

function calcularPrecioFinal() {
    const totalBase = sumaPreciosAsientosTotales();
    let descuento = 0;

    const msg = document.getElementById('promoMsg');
    const selectPromo = document.getElementById('codigoPromo');
    const optionPromo = selectPromo ? selectPromo.options[selectPromo.selectedIndex] : null;
    const porcentajeDescuento = parseFloat(optionPromo?.getAttribute('data-descuento') || '0') || 0;
    const codigoPromo = selectPromo ? selectPromo.value : '';

    const selectAbono = document.getElementById('abonoActivo') || document.getElementById('select-abono');
    const abonoActivo = selectAbono ? selectAbono.value : '';

    if (msg) msg.textContent = '';

    if (abonoActivo !== '') {
        const primerAsiento = estado.asientosSeleccionados[0];
        descuento = primerAsiento ? (Number(primerAsiento.precio) || 0) : 0;
        if (msg) {
            msg.textContent = totalPasajeros > 1
                ? tr('abono_1_pasajero', 'Abono aplicado a 1 pasajero de la reserva.')
                : tr('abono_aplicado', '¡Abono aplicado! El viaje se descontará de tu saldo.');
            msg.style.color = '#17632A';
        }
        if (codigoPromo !== '' && selectPromo) selectPromo.value = '';
    } else if (codigoPromo !== '') {
        descuento = totalBase * (porcentajeDescuento / 100);
        if (msg) {
            msg.textContent = tr('promo_aplicada', 'Promoción aplicada: -{pct}%', { pct: porcentajeDescuento });
            msg.style.color = '#17632A';
        }
    }

    precioFinalConDescuento = Math.max(0, totalBase - descuento);

    const summaryFinalPrice = document.getElementById('summaryFinalPrice');
    if (summaryFinalPrice) summaryFinalPrice.textContent = formatearEuros(precioFinalConDescuento);

    const btnPaso4 = document.getElementById('btnPaso4');
    if (btnPaso4) {
        btnPaso4.textContent = precioFinalConDescuento === 0
            ? tr('confirmar_reserva_gratis', 'Confirmar Reserva Gratis')
            : tr('continuar_pago_seguro', 'Continuar al Pago Seguro');
    }
}

function aplicarPromocion() {
    calcularPrecioFinal();
}

function recalcularPrecio() {
    calcularPrecioFinal();
}

// ================= PASO 5: PAGO Y RESERVA =================
function validarFormularioPago() {
    const cardNumber = document.getElementById('cardNumber');
    const cardExpiry = document.getElementById('cardExpiry');
    const cardCVV = document.getElementById('cardCVV');
    const cardHolder = document.getElementById('cardHolder');

    if (!cardNumber || !cardExpiry || !cardCVV || !cardHolder) {
        return true; // Si no existen los elementos, asumir que está bien
    }

    // Validar número de tarjeta
    let value = cardNumber.value.replace(/\s/g, '');
    if (!/^\d{16}$/.test(value)) {
        return false;
    }

    // Validar caducidad
    value = cardExpiry.value;
    if (!/^\d{2}\/\d{2}$/.test(value)) {
        return false;
    }
    const [mes, anio] = value.split('/').map(Number);
    if (mes < 1 || mes > 12) {
        return false;
    }
    const hoy = new Date();
    const expYear = 2000 + anio;
    const expDate = new Date(expYear, mes - 1, 1);
    if (expDate < new Date(hoy.getFullYear(), hoy.getMonth(), 1)) {
        return false;
    }

    // Validar CVV
    value = cardCVV.value;
    if (!/^\d{3}$/.test(value)) {
        return false;
    }

    // Validar titular
    value = cardHolder.value.trim();
    if (value.length < 3) {
        return false;
    }

    return true;
}

function confirmarReserva() {
    if (!viajeSeleccionado) {
        alert(tr('faltan_datos_reserva', 'Faltan datos para la reserva.'));
        return;
    }

    // Validar que hay asientos seleccionados
    if (esIdaVuelta) {
        const asientosIda = estado.asientosSeleccionados.filter(s => s.tramo === 'ida').length;
        const asientosVuelta = estado.asientosSeleccionados.filter(s => s.tramo === 'vuelta').length;
        
        if (asientosIda !== totalPasajeros) {
            alert(tr('faltan_asientos_ida', 'Debes seleccionar {n} asientos de ida.', { n: totalPasajeros }));
            return;
        }
        if (asientosVuelta !== totalPasajeros) {
            alert(tr('faltan_asientos_vuelta', 'Debes seleccionar {n} asientos de vuelta.', { n: totalPasajeros }));
            return;
        }
    } else {
        if (estado.asientosSeleccionados.length !== totalPasajeros) {
            alert(tr('faltan_datos_reserva', 'Faltan datos para la reserva.'));
            return;
        }
    }

    if (!validarYGuardarDatosPasajeros()) {
        return;
    }

    // Validar formulario de pago antes de continuar
    if (!validarFormularioPago()) {
        return;
    }

    // Verificar si hay sesión iniciada
    const hasSession = window.compraConfig && window.compraConfig.pasajeroPrincipal && window.compraConfig.pasajeroPrincipal.email;
    if (!hasSession) {
        // Setear el email del pasajero 1 en el modal
        const guestEmail = document.getElementById('guestEmail');
        if (guestEmail) {
            guestEmail.textContent = estado.datosPasajeros[0]?.email || '';
        }
        // Mostrar modal de confirmación para invitado
        const modal = document.getElementById('guestWarningModal');
        if (modal) modal.classList.remove('hidden');
        return;
    }

    // Si hay sesión, proceder directamente
    realizarReserva();
}

function realizarReserva() {
    const btn = document.querySelector('.btn-pay-confirm');
    if (btn) btn.disabled = true;

    fetch('php/api_reservar.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_viaje: viajeSeleccionado,
            id_viaje_vuelta: esIdaVuelta ? viajeVueltaSeleccionado : null,
            asientos: estado.asientosSeleccionados.map((s) => ({
                numero_asiento: s.numero,
                vagon: s.wagon,
                precio: s.precio,
                tramo: s.tramo
            })),
            pasajeros: estado.datosPasajeros,
            precio_total: precioFinalConDescuento
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
                const token = encodeURIComponent(data.token || '');
                window.location.href = `reserva_exitosa.php?token=${token}`;
                return;
            }

            const err = data.error || tr('error_desconocido', 'Error desconocido.');
            alert(tr('error_reservar', 'Error al reservar: {error}', { error: err }));
            if (btn) btn.disabled = false;
        })
        .catch(err => {
            alert(tr('error_reservar', 'Error al reservar: {error}', { error: err.message || err }));
            if (btn) btn.disabled = false;
        });
}

// ================= INICIALIZACION =================
document.addEventListener('DOMContentLoaded', function () {
    window.seleccionarTrenVuelta = seleccionarTrenVuelta;
    window.reservarSoloIdaDesdeModal = reservarSoloIdaDesdeModal;
    window.cerrarModalVuelta = cerrarModalVuelta;
    window.cerrarModalInvitado = cerrarModalInvitado;
    window.confirmarCompraInvitado = confirmarCompraInvitado;

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            cerrarModalVuelta();
            cerrarModalInvitado();
        }
    });

    const returnModal = document.getElementById('returnTripModal');
    if (returnModal) {
        returnModal.addEventListener('click', function (e) {
            if (e.target === returnModal) {
                cerrarModalVuelta();
            }
        });
    }

    const guestModal = document.getElementById('guestWarningModal');
    if (guestModal) {
        guestModal.addEventListener('click', function (e) {
            if (e.target === guestModal) {
                cerrarModalInvitado();
            }
        });
    }

    document.querySelectorAll('.seat').forEach(asiento => {
        asiento.addEventListener('click', function () {
            if (this.classList.contains('occupied')) return;
            const numero_asiento = this.getAttribute('data-seat');
            seleccionarAsiento(this, numero_asiento);
        });
    });

    const selectPromo = document.getElementById('codigoPromo');
    if (selectPromo) selectPromo.addEventListener('change', calcularPrecioFinal);

    const selectAbono = document.getElementById('abonoActivo') || document.getElementById('select-abono');
    if (selectAbono) selectAbono.addEventListener('change', calcularPrecioFinal);

    localizeAbonoOptions();

    const lbl = document.getElementById('requiredPassengersCount');
    if (lbl) lbl.textContent = String(totalPasajeros);

    refrescarResumenAsientosSeleccionados();
});

function cerrarModalInvitado() {
    const modal = document.getElementById('guestWarningModal');
    if (modal) modal.classList.add('hidden');
}

function confirmarCompraInvitado() {
    cerrarModalInvitado();
    // Proceder con la reserva
    realizarReserva();
}
