document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formCompraAbono');
    const mensajeDiv = document.getElementById('mensajeCompra');
    if (!form || !mensajeDiv) return;

    function tr(key, fallback, params = {}) {
        const i18n = window.trainwebI18n;
        let text = (i18n && typeof i18n.t === 'function') ? i18n.t(key) : null;
        if (!text) text = fallback;
        Object.keys(params).forEach((k) => {
            text = text.replace(`{${k}}`, params[k]);
        });
        return text;
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault(); // Evitamos que la página se recargue

        const tipoAbono = document.getElementById('tipoAbono').value;
        const precio = document.getElementById('precioAbono').value;

        // Deshabilitamos el botón para evitar doble envío
        const btnSubmit = form.querySelector('button[type="submit"]');
        btnSubmit.disabled = true;
        btnSubmit.textContent = tr('pago_procesando', 'Procesando pago...');

        try {
            // Enviamos los datos al backend usando fetch
            const respuesta = await fetch('php/procesar_compra_abono.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tipo: tipoAbono,
                    precio: precio
                })
            });

            const resultado = await respuesta.json();

            if (resultado.exito) {
                mensajeDiv.style.color = 'green';
                mensajeDiv.textContent = tr('abono_pago_completado_exito', '¡Pago completado! Tu abono ha sido activado con éxito.');
                
                // Redirigir al área de cliente después de 2 segundos
                setTimeout(() => {
                    window.location.href = 'index.php'; // Cambia esto por la URL de tu perfil de usuario
                }, 2000);
            } else {
                throw new Error(resultado.error || 'Error desconocido al procesar el abono');
            }

        } catch (error) {
            console.error('Error:', error);
            mensajeDiv.style.color = 'red';
            mensajeDiv.textContent = tr('error_hubo_un_error', 'Hubo un error: {error}', { error: error.message });
            
            // Reactivamos el botón
            btnSubmit.disabled = false;
            btnSubmit.textContent = tr('pago_confirmar', 'Confirmar Pago');
        }
    });
});