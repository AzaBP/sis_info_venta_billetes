// Base de datos simulada (Mock Data)
const mockClientes = {
    "12345678A": {
        nombre: "María García López",
        email: "m.garcia@email.com",
        telefono: "+34 600 123 456",
        tarjeta: "8891", // Solo los últimos 4 dígitos
        viajes: [
            { ruta: "Zaragoza - Madrid", fecha: "12/02/2026", estado: "Confirmado" },
            { ruta: "Madrid - Sevilla", fecha: "10/01/2026", estado: "Finalizado" }
        ]
    },
    "87654321B": {
        nombre: "Juan Pérez Galdós",
        email: "juan.p@email.com",
        telefono: "+34 611 999 888",
        tarjeta: "1023",
        viajes: []
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const btnBuscar = document.getElementById('btnBuscar');
    const inputDni = document.getElementById('dniInput');
    
    // Elementos del DOM a manipular
    const clientInfo = document.getElementById('clientInfo');
    const clientError = document.getElementById('clientError');
    const operationsPanel = document.getElementById('operationsPanel');
    
    // Elementos de datos
    const elName = document.getElementById('clientName');
    const elEmail = document.getElementById('clientEmail');
    const elPhone = document.getElementById('clientPhone');
    const elCard = document.getElementById('clientCard');
    const listTrips = document.getElementById('recentTrips');

    btnBuscar.addEventListener('click', () => {
        const dni = inputDni.value.trim().toUpperCase();
        
        // Resetear vista
        clientInfo.classList.add('hidden');
        clientError.classList.add('hidden');
        operationsPanel.classList.add('disabled');
        
        if (mockClientes[dni]) {
            // CLIENTE ENCONTRADO
            const data = mockClientes[dni];
            
            // 1. Rellenar datos
            elName.textContent = data.nombre;
            elEmail.textContent = data.email;
            elPhone.textContent = data.telefono;
            elCard.textContent = data.tarjeta; // Privacidad: Solo 4 dígitos
            
            // 2. Rellenar historial de viajes
            listTrips.innerHTML = ''; // Limpiar anterior
            if(data.viajes.length > 0) {
                data.viajes.forEach(viaje => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <span>${viaje.ruta} <br><small>${viaje.fecha}</small></span>
                        <span class="status-ok">${viaje.estado}</span>
                    `;
                    listTrips.appendChild(li);
                });
            } else {
                listTrips.innerHTML = '<li style="color:#999">Sin viajes recientes</li>';
            }

            // 3. Mostrar paneles
            clientInfo.classList.remove('hidden');
            operationsPanel.classList.remove('disabled'); // Desbloquea la consola
            
        } else {
            // CLIENTE NO ENCONTRADO
            clientError.classList.remove('hidden');
        }
    });
});

// Función auxiliar para los botones de acción
function openModal(tipo) {
    // Aquí iría la lógica para abrir un modal real o redirigir
    const dni = document.getElementById('dniInput').value;
    alert(`Abriendo proceso de ${tipo.toUpperCase()} para el cliente DNI: ${dni}`);
}