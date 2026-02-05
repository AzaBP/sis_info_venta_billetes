document.addEventListener('DOMContentLoaded', () => {
    
    const form = document.getElementById('formMantenimiento');
    const listaIncidencias = document.getElementById('incidenciasContainer');

    // 1. FUNCIÓN PARA CREAR NUEVA INCIDENCIA
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        // Obtener valores
        const trainId = document.getElementById('trainSelect').value;
        const priority = document.getElementById('prioritySelect').value;
        const desc = document.getElementById('issueDesc').value;

        if(!desc) return alert("Por favor, describe la incidencia.");

        // Generar ID aleatorio
        const ticketId = '#INC-' + Math.floor(Math.random() * 9000 + 1000);

        // Determinar texto y clase de prioridad
        let priorityText = "";
        let priorityClass = "";

        if(priority === 'high') { priorityText = 'URGENTE'; priorityClass = 'high-priority'; }
        else if(priority === 'medium') { priorityText = 'MEDIA'; priorityClass = 'medium-priority'; }
        else { priorityText = 'BAJA'; priorityClass = 'low-priority'; }

        // Crear el HTML de la tarjeta
        const newCard = document.createElement('div');
        newCard.className = `issue-item ${priorityClass}`;
        newCard.innerHTML = `
            <div class="issue-header">
                <span class="issue-id">${ticketId}</span>
                <span class="priority-tag">${priorityText}</span>
            </div>
            <p class="issue-desc">${desc}</p>
            <div class="issue-meta">
                <span><i class="fa-solid fa-train"></i> ${trainId}</span>
                <button onclick="resolverIncidencia(this)" class="btn-resolve">Reparado</button>
            </div>
        `;

        // Añadir al principio de la lista (animación simple)
        listaIncidencias.prepend(newCard);

        // Limpiar formulario
        form.reset();
    });

});

// 2. FUNCIÓN PARA RESOLVER (Global para que el HTML la encuentre)
function resolverIncidencia(button) {
    const card = button.closest('.issue-item');
    
    // Cambiar estilo para indicar completado
    card.style.opacity = '0.5';
    card.style.transform = 'scale(0.95)';
    button.textContent = "Cerrando...";
    button.disabled = true;

    // Eliminar tras un pequeño retraso
    setTimeout(() => {
        card.remove();
    }, 500);
}