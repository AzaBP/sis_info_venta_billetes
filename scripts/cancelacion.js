// ARCHIVO: scripts/cancelacion.js

document.addEventListener("DOMContentLoaded", function() {
    const btnCancelar = document.getElementById("btn_cancelar_billete");
    const inputCodigo = document.getElementById("codigo_cancelacion");
    const msgDiv = document.getElementById("cancel_msg");

    if (btnCancelar && inputCodigo) {
        btnCancelar.addEventListener("click", function() {
            const codigo = inputCodigo.value.trim();

            if (!codigo) {
                mostrarMensaje("Por favor, introduce un código de billete válido.", "error");
                return;
            }

            // Cambiar estado del botón mientras carga
            btnCancelar.disabled = true;
            btnCancelar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';

            // Petición al backend
            fetch("php/procesar_cancelacion.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ codigo: codigo })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarMensaje(data.message, "success");
                    inputCodigo.value = ""; // Limpiar el input
                } else {
                    mostrarMensaje(data.message, "error");
                }
            })
            .catch(error => {
                mostrarMensaje("Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
                console.error("Error:", error);
            })
            .finally(() => {
                // Restaurar el botón
                btnCancelar.disabled = false;
                btnCancelar.innerHTML = 'Cancelar viaje';
            });
        });
    }

    function mostrarMensaje(texto, tipo) {
        msgDiv.textContent = texto;
        msgDiv.style.display = "block";
        if (tipo === "error") {
            msgDiv.style.backgroundColor = "#ffe6e6";
            msgDiv.style.color = "#cc0000";
            msgDiv.style.border = "1px solid #ff9999";
        } else {
            msgDiv.style.backgroundColor = "#e6ffe6";
            msgDiv.style.color = "#008000";
            msgDiv.style.border = "1px solid #99ff99";
        }
    }
});