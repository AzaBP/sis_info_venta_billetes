document.addEventListener("DOMContentLoaded", function () {

    /* =========================
       IDA / IDA Y VUELTA
    ========================= */

    const tripRadios = document.querySelectorAll('input[name="trip"]');
    const dateContainer = document.getElementById("date-container");

    function activarValidacionFechas() {

        const fechaIda = document.getElementById("fecha-ida");
        const fechaVuelta = document.getElementById("fecha-vuelta");

        if (!fechaIda) return;

        // No permitir fechas pasadas
        const hoy = new Date().toISOString().split("T")[0];
        fechaIda.min = hoy;

        fechaIda.addEventListener("change", function () {

            if (fechaVuelta) {
                fechaVuelta.min = this.value;

                // Si la vuelta es menor que la ida → limpiar
                if (fechaVuelta.value && fechaVuelta.value < this.value) {
                    fechaVuelta.value = "";
                }
            }
        });

        if (fechaVuelta) {
            fechaVuelta.addEventListener("change", function () {
                if (this.value < fechaIda.value) {
                    this.value = "";
                }
            });
        }
    }

    function crearInputsFecha(tipoViaje) {

        if (!dateContainer) return;

        dateContainer.innerHTML = "";

        const fechaIda = document.createElement("input");
        fechaIda.type = "date";
        fechaIda.id = "fecha-ida";
        fechaIda.name = "fecha";
        fechaIda.required = true;
        dateContainer.appendChild(fechaIda);

        if (tipoViaje === "roundtrip") {
            const fechaVuelta = document.createElement("input");
            fechaVuelta.type = "date";
            fechaVuelta.id = "fecha-vuelta";
            fechaVuelta.name = "fecha_vuelta";
            fechaVuelta.required = true;
            dateContainer.appendChild(fechaVuelta);
        }

        activarValidacionFechas();
    }

    // Forzar que "Solo ida" esté marcado por defecto al cargar
    if (tripRadios.length) {
        tripRadios.forEach(radio => {
            if (radio.value === 'oneway') {
                radio.checked = true;
            } else {
                radio.checked = false;
            }
        });
    }

    // Crear inputs de fecha según el valor por defecto (solo ida)
    crearInputsFecha('oneway');

    // Añadir eventos para cambiar inputs de fecha según el tipo de viaje
    tripRadios.forEach(function (radio) {
        radio.addEventListener("change", function () {
            crearInputsFecha(this.value);
            // No mostrar error al cambiar tipo de viaje si el usuario no ha interactuado
            if (typeof usuarioHaInteractuado !== 'undefined') usuarioHaInteractuado = false;
        });
    });



    /* =========================
       AUTOCOMPLETE LUGARES (API)
    ========================= */

    const inputOrigen = document.getElementById('origen');
    const suggOrigen = document.getElementById('suggestions-origen');
    const inputDestino = document.getElementById('destino');
    const suggDestino = document.getElementById('suggestions-destino');

    if (!inputOrigen || !inputDestino) return;

    let origenesDb = [];
    let destinosDb = [];

    // 1. Pedir los datos a PostgreSQL
    fetch('./php/api_origenes_destinos.php')
        .then(res => res.json())
        .then(data => {
            if (data.exito) {
                origenesDb = data.origenes;
                destinosDb = data.destinos;
                console.log('origenesDb:', origenesDb);
            }
        })
        .catch(err => console.error("Error cargando ciudades:", err));

    // 2. Función para mostrar sugerencias
    function mostrarSugerencias(input, container, lista, mostrarTodas = false) {
        const valor = input.value.toLowerCase();
        container.innerHTML = '';
        let filtrados;
        if (mostrarTodas || (input === document.activeElement && valor === '')) {
            filtrados = lista;
        } else {
            filtrados = lista.filter(ciudad => ciudad.toLowerCase().includes(valor));
        }
        if (filtrados.length === 0) {
            container.style.display = 'none';
            return;
        }
        filtrados.forEach(ciudad => {
            const div = document.createElement('div');
            div.textContent = ciudad;
            div.style.padding = '10px';
            div.style.cursor = 'pointer';
            div.style.borderBottom = '1px solid #eee';
            div.addEventListener('click', () => {
                input.value = ciudad;
                container.style.display = 'none';
            });
            div.addEventListener('mouseenter', () => div.style.backgroundColor = '#f4f6f8');
            div.addEventListener('mouseleave', () => div.style.backgroundColor = 'white');
            container.appendChild(div);
        });
        container.style.display = 'block';
        container.style.position = 'absolute';
        container.style.backgroundColor = 'white';
        container.style.border = '1px solid #ccc';
        container.style.width = input.offsetWidth + 'px';
        container.style.zIndex = '1000';
        container.style.maxHeight = '200px';
        container.style.overflowY = 'auto';
    }

    // 3. Eventos para Origen
    inputOrigen.addEventListener('input', () => mostrarSugerencias(inputOrigen, suggOrigen, origenesDb));
    inputOrigen.addEventListener('focus', () => mostrarSugerencias(inputOrigen, suggOrigen, origenesDb, true));

    // 4. Eventos para Destino
    inputDestino.addEventListener('input', () => mostrarSugerencias(inputDestino, suggDestino, destinosDb));
    inputDestino.addEventListener('focus', () => mostrarSugerencias(inputDestino, suggDestino, destinosDb, true));

    // 5. Ocultar sugerencias si haces clic fuera
    document.addEventListener('click', function(e) {
        if (e.target !== inputOrigen) suggOrigen.style.display = 'none';
        if (e.target !== inputDestino) suggDestino.style.display = 'none';
    });

    // =========================
    // VALIDACIÓN DE FORMULARIO DE BÚSQUEDA
    // =========================

    const form = document.querySelector('form.search-form');
    if (form) {
        // Crear contenedor de error visual si no existe
        let errorDiv = document.getElementById('form-error-msg');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'form-error-msg';
            errorDiv.style.color = '#b30000';
            errorDiv.style.background = '#fff0f0';
            errorDiv.style.padding = '8px 12px';
            errorDiv.style.margin = '10px 0 0 0';
            errorDiv.style.borderRadius = '6px';
            errorDiv.style.fontWeight = 'bold';
            errorDiv.style.display = 'none';
            form.appendChild(errorDiv);
        }

        const btnBuscar = form.querySelector('button[type="submit"]');
        if (btnBuscar) {
            btnBuscar.disabled = true;
            btnBuscar.style.background = '#cccccc';
            btnBuscar.style.cursor = 'not-allowed';
            btnBuscar.style.color = '#888';
        }

        // Variable global para evitar mostrar error tras cambiar tipo de viaje
        window.usuarioHaInteractuado = false;

        function validarFormulario() {
            const origen = inputOrigen.value.trim();
            const destino = inputDestino.value.trim();
            const fechaIda = document.getElementById('fecha-ida') || document.getElementById('fecha');
            const fechaVuelta = document.getElementById('fecha-vuelta');
            const pasajeros = form.querySelector('select[name="pasajeros"]')?.value;

            const t_err = (key) => window.trainwebI18n ? window.trainwebI18n.t(key) : key;

            let isFormComplete = true;
            if (!origen || !destino || !fechaIda || !fechaIda.value || !pasajeros || isNaN(parseInt(pasajeros))) {
                isFormComplete = false;
            }
            if (fechaVuelta && document.querySelector('input[name="trip"]:checked')?.value === 'roundtrip' && !fechaVuelta.value) {
                isFormComplete = false;
            }

            let errorMsg = '';
            
            // Validate origin against database
            if (origen && origenesDb.length > 0) {
                const isValidOrigen = origenesDb.some(c => c.toLowerCase() === origen.toLowerCase());
                if (!isValidOrigen) {
                    errorMsg = t_err('error_origen');
                }
            }
            
            // Validate destination against database
            if (destino && destinosDb.length > 0 && !errorMsg) {
                const isValidDestino = destinosDb.some(c => c.toLowerCase() === destino.toLowerCase());
                if (!isValidDestino) {
                    errorMsg = t_err('error_destino');
                }
            }

            if (fechaIda && fechaIda.value && !errorMsg) {
                const hoy = new Date();
                const fIda = new Date(fechaIda.value);
                hoy.setHours(0,0,0,0);
                if (fIda < hoy) {
                    errorMsg = t_err('error_fecha_pasada');
                }
                if (fechaVuelta && fechaVuelta.value) {
                    const fVuelta = new Date(fechaVuelta.value);
                    if (fVuelta < fIda) {
                        errorMsg = t_err('error_fecha_orden');
                    }
                }
            }

            if (btnBuscar) {
                if (!isFormComplete || errorMsg) {
                    btnBuscar.disabled = true;
                    btnBuscar.style.background = '#cccccc';
                    btnBuscar.style.cursor = 'not-allowed';
                    btnBuscar.style.color = '#888';
                } else {
                    btnBuscar.disabled = false;
                    btnBuscar.style.background = '';
                    btnBuscar.style.cursor = '';
                    btnBuscar.style.color = '';
                }
            }

            if (errorMsg) {
                errorDiv.textContent = errorMsg;
                errorDiv.style.display = 'block';
            } else {
                errorDiv.textContent = '';
                errorDiv.style.display = 'none';
            }
            
            return isFormComplete && !errorMsg;
        }

        // Validar en cada cambio de campo relevante
        [inputOrigen, inputDestino].forEach(input => {
            input.addEventListener('input', function() {
                // No marcamos usuarioHaInteractuado en 'input' para evitar mostrar error del siguiente campo mientras se teclea
                validarFormulario();
            });
            input.addEventListener('blur', function() {
                window.usuarioHaInteractuado = true;
                validarFormulario();
            });
        });
        
        form.querySelector('select[name="pasajeros"]')?.addEventListener('change', function() {
            window.usuarioHaInteractuado = true;
            validarFormulario();
        });
        
        form.addEventListener('change', function(e) {
            validarFormulario();
        });
        
        form.addEventListener('input', function(e) {
            validarFormulario();
        });

        // Validar al intentar enviar
        form.addEventListener('submit', function(e) {
            window.usuarioHaInteractuado = true;
            if (!validarFormulario()) {
                e.preventDefault();
                return false;
            }
        });

        // Validar al cargar (sin mostrar error)
        validarFormulario();
    }

});
