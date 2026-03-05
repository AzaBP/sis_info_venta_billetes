document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formCompraAbono');
    const mensajeDiv = document.getElementById('mensajeCompra');

    form.addEventListener('submit', async (e) => {
        e.preventDefault(); // Evitamos que la página se recargue

        const tipoAbono = document.getElementById('tipoAbono').value;
        const precio = document.getElementById('precioAbono').value;

        // Deshabilitamos el botón para evitar doble envío
        const btnSubmit = form.querySelector('button[type="submit"]');
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Procesando pago...';

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
                mensajeDiv.textContent = '¡Pago completado! Tu abono ha sido activado con éxito.';
                
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
            mensajeDiv.textContent = 'Hubo un error: ' + error.message;
            
            // Reactivamos el botón
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Confirmar Pago';
        }
    });
});