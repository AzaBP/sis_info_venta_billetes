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
        dateContainer.appendChild(fechaIda);

        if (tipoViaje === "roundtrip") {
            const fechaVuelta = document.createElement("input");
            fechaVuelta.type = "date";
            fechaVuelta.id = "fecha-vuelta";
            dateContainer.appendChild(fechaVuelta);
        }

        activarValidacionFechas();
    }

    tripRadios.forEach(function (radio) {
        radio.addEventListener("change", function () {
            crearInputsFecha(this.value);
        });
    });

    // Activar validación al cargar
    activarValidacionFechas();



    /* =========================
       AUTOCOMPLETE LUGARES
    ========================= */

    const lugares = [
        "Madrid",
        "Barcelona",
        "Sevilla",
        "Valencia",
        "Bilbao",
        "Zaragoza",
        "Málaga",
        "Granada",
        "Alicante",
        "Córdoba"
    ];

    function activarAutocomplete(inputId, suggestionsId) {

        const input = document.getElementById(inputId);
        const suggestionsBox = document.getElementById(suggestionsId);

        if (!input || !suggestionsBox) return;

        input.addEventListener("input", function () {

            const valor = this.value.toLowerCase();
            suggestionsBox.innerHTML = "";

            if (!valor) return;

            const filtrados = lugares.filter(function (lugar) {
                return lugar.toLowerCase().includes(valor);
            });

            filtrados.forEach(function (lugar) {

                const div = document.createElement("div");
                div.textContent = lugar;

                div.addEventListener("click", function () {
                    input.value = lugar;
                    suggestionsBox.innerHTML = "";
                });

                suggestionsBox.appendChild(div);
            });
        });

        document.addEventListener("click", function (e) {
            if (!input.contains(e.target)) {
                suggestionsBox.innerHTML = "";
            }
        });
    }

    activarAutocomplete("origen", "suggestions-origen");
    activarAutocomplete("destino", "suggestions-destino");

});
