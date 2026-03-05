document.addEventListener('DOMContentLoaded', () => {
    cargarPromociones();
    cargarAbonos();
});

// Obtener promociones desde tu archivo PHP
async function cargarPromociones() {
    try {
        const respuesta = await fetch('obtener_promociones.php');
        
        if (!respuesta.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        
        const promociones = await respuesta.json();
        const contenedor = document.getElementById('promociones-container');
        
        if (promociones.length === 0) {
            contenedor.innerHTML = '<p>No hay promociones activas en este momento.</p>';
            return;
        }
        
        promociones.forEach(promo => {
            const card = document.createElement('div');
            card.className = 'card';
            card.innerHTML = `
                <h3>Código Especial</h3>
                <div class="codigo">${promo.codigo}</div>
                <div class="descuento">${promo.descuento_porcentaje}% de descuento</div>
                <p>Válido hasta: ${new Date(promo.fecha_fin).toLocaleDateString()}</p>
                <button onclick="copiarCodigo('${promo.codigo}')">Copiar Código</button>
            `;
            contenedor.appendChild(card);
        });
    } catch (error) {
        console.error('Error cargando promociones:', error);
        document.getElementById('promociones-container').innerHTML = '<p>Hubo un problema al cargar las promociones.</p>';
    }
}

// Obtener los tipos de abonos desde tu archivo PHP
async function cargarAbonos() {
    try {
        const respuesta = await fetch('obtener_abonos.php');
        const abonos = await respuesta.json();
        
        const contenedor = document.getElementById('abonos-container');
        
        abonos.forEach(abono => {
            // Reemplazamos guiones bajos por espacios para que se vea mejor (ej. Viajes_limitados -> Viajes limitados)
            const nombreAbono = abono.tipo.replace('_', ' '); 
            
            const card = document.createElement('div');
            card.className = 'card';
            card.innerHTML = `
                <h3>Abono ${nombreAbono}</h3>
                <p>${abono.descripcion}</p>
                <button onclick="comprarAbono('${abono.tipo}')">Comprar Abono</button>
            `;
            contenedor.appendChild(card);
        });
    } catch (error) {
        console.error('Error cargando abonos:', error);
    }
}

function copiarCodigo(codigo) {
    navigator.clipboard.writeText(codigo);
    alert(`Código ${codigo} copiado al portapapeles. ¡Úsalo en tu próxima compra!`);
}

function comprarAbono(tipo) {
    // Redirigir al flujo de compra del abono (puedes cambiar esta URL más adelante)
    window.location.href = `comprar_abono.php?tipo=${tipo.toLowerCase()}`;
}