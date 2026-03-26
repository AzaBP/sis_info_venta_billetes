document.addEventListener('DOMContentLoaded', function() {
    cargarAbonos();
});

function cargarAbonos() {
    fetch('php/abonos_usuario_api.php')
        .then(res => res.json())
        .then(abonos => {
            const contenedor = document.getElementById('abonos-list');
            
            // Le añadimos la clase grid-container para que las tarjetas se alineen correctamente
            contenedor.className = 'grid-container'; 

            // Si no tiene abonos, mostramos un mensaje bonito invitándole a comprar
            if (!abonos || abonos.length === 0) {
                contenedor.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                        <i class="fa-solid fa-ticket-alt" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                        <h3 style="color: #0a2a66; margin-bottom: 10px;">Aún no tienes abonos activos</h3>
                        <p style="color: #666; margin-bottom: 20px;">Viaja más por menos adquiriendo uno de nuestros bonos.</p>
                        <a href="ofertas.php" style="background: #f39c12; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: 0.3s;">Ver Ofertas</a>
                    </div>
                `;
                return;
            }

            // Si tiene abonos, generamos las tarjetas
            contenedor.innerHTML = abonos.map(a => {
                const info = obtenerDisenoAbono(a.tipo);
                
                // Comprobamos si está activo comparando la fecha actual con la de fin
                const fechaFin = new Date(a.fecha_fin);
                const hoy = new Date();
                const esActivo = fechaFin >= hoy;
                
                const colorEstado = esActivo ? '#17632A' : '#dc3545'; // Verde o Rojo
                const textoEstado = esActivo ? 'Activo' : 'Caducado';

                // Lógica de los viajes restantes
                let textoViajes = '<p style="margin: 8px 0; color: #555;"><strong>Viajes:</strong> Ilimitados</p>';
                if (a.viajes_totales !== null) {
                    textoViajes = `<p style="margin: 8px 0; color: #555;"><strong>Viajes restantes:</strong> ${a.viajes_restantes} de ${a.viajes_totales}</p>`;
                }

                return `
                    <div class="card" style="border-top-color: ${info.color}; text-align: left; padding: 25px; background: white; border-radius: 12px; box-shadow: 0 8px 16px rgba(0,0,0,0.08); position: relative;">
                        
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                            <i class="fa-solid ${info.icon}" style="font-size: 2.5rem; color: ${info.color};"></i>
                            <span style="background: ${colorEstado}; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                ${textoEstado}
                            </span>
                        </div>
                        
                        <h3 style="color: #0a2a66; font-size: 1.4rem; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                            ${info.nombre}
                        </h3>
                        
                        <p style="margin: 8px 0; color: #555;"><strong>Válido desde:</strong> ${formatearFecha(a.fecha_inicio)}</p>
                        <p style="margin: 8px 0; color: #555;"><strong>Válido hasta:</strong> ${formatearFecha(a.fecha_fin)}</p>
                        ${textoViajes}
                    </div>
                `;
            }).join('');
        })
        .catch(err => {
            console.error("Error cargando abonos:", err);
            document.getElementById('abonos-list').innerHTML = `
                <div style="color: #dc3545; padding: 20px; text-align: center;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Error al cargar tus abonos. Inténtalo más tarde.
                </div>
            `;
        });
}

// Función auxiliar para darle la estética correcta según el tipo
function obtenerDisenoAbono(tipo) {
    const disenios = {
        'mensual': { icon: 'fa-calendar-alt', color: '#0a2a66', nombre: 'Abono Mensual' },
        'trimestral': { icon: 'fa-calendar-days', color: '#17a2b8', nombre: 'Abono Trimestral' },
        'anual': { icon: 'fa-infinity', color: '#f39c12', nombre: 'Abono Anual' },
        'viajes_limitados': { icon: 'fa-ticket', color: '#3156fc', nombre: 'Bono Viajes' },
        'estudiante': { icon: 'fa-user-graduate', color: '#17632A', nombre: 'Abono Estudiante' }
    };
    
    // Si el tipo no coincide, le damos un diseño genérico
    return disenios[tipo] || { icon: 'fa-id-card', color: '#6c757d', nombre: 'Abono ' + tipo.replace('_', ' ') };
}

// Función auxiliar para poner la fecha en formato DD/MM/YYYY
function formatearFecha(fechaSql) {
    if (!fechaSql) return 'N/A';
    const partes = fechaSql.split('-'); // Asume YYYY-MM-DD
    if (partes.length === 3) {
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }
    return fechaSql;
}