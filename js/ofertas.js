document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res = await fetch('php/obtener_ofertas.php');
        const data = await res.json();

        // Renderizar Promociones
        const pContainer = document.getElementById('promociones-container');
        data.promociones.forEach(p => {
            pContainer.innerHTML += `
                <div class="card">
                    <h3>Código Descuento</h3>
                    <div class="descuento">${parseFloat(p.descuento_porcentaje)}%</div>
                    <div class="codigo">${p.codigo}</div>
                    <p>Válido hasta: ${p.fecha_fin}</p>
                    <button onclick="navigator.clipboard.writeText('${p.codigo}'); alert('Copiado')">Copiar Código</button>
                </div>`;
        });

        // Renderizar Abonos
        const aContainer = document.getElementById('abonos-container');
        data.abonos.forEach(a => {
            aContainer.innerHTML += `
                <div class="card">
                    <h3>${a.nombre}</h3>
                    <p>${a.desc}</p>
                    <button onclick="window.location.href='comprar_abono.php?tipo=${a.tipo}'">Comprar Ahora</button>
                </div>`;
        });
    } catch (error) {
        console.error("Error cargando ofertas:", error);
    }
});