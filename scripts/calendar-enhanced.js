document.addEventListener('DOMContentLoaded', () => {
    const origenInput = document.getElementById('origen');
    const destinoInput = document.getElementById('destino');
    const fechaIdaInput = document.getElementById('fecha-ida');
    const dateContainer = document.getElementById('date-container');

    let today = new Date();
    let currentMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    let fechasDisponibles = [];

    const minDate = today.toISOString().split('T')[0];
    if (fechaIdaInput) {
        fechaIdaInput.setAttribute('min', minDate);
    }

    async function cargarFechasDisponibles() {
        if (!origenInput || !destinoInput || !origenInput.value || !destinoInput.value) {
            return;
        }

        try {
            const response = await fetch(
                `php/api_fechas_disponibles.php?origen=${encodeURIComponent(origenInput.value)}&destino=${encodeURIComponent(destinoInput.value)}&fecha_desde=${minDate}`,
                { credentials: 'same-origin' }
            );
            const data = await response.json();

            if (data.exito) {
                fechasDisponibles = data.fechas || [];
            } else {
                fechasDisponibles = [];
            }

            renderCalendar();
        } catch (error) {
            console.error('Error cargando fechas disponibles:', error);
            fechasDisponibles = [];
        }
    }

    function renderCalendar() {
        const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                           'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

        const year = currentMonth.getFullYear();
        const month = currentMonth.getMonth();
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        let calendarHTML = `
            <div class="calendar-container">
                <div class="calendar-header">
                    <button type="button" class="calendar-nav prev-month" onclick="event.preventDefault();"><i class="fa-solid fa-chevron-left"></i></button>
                    <h3>${monthNames[month]} ${year}</h3>
                    <button type="button" class="calendar-nav next-month" onclick="event.preventDefault();"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
                <div class="calendar-weekdays">
        `;

        dayNames.forEach(day => {
            calendarHTML += `<div class="weekday">${day}</div>`;
        });

        calendarHTML += '</div><div class="calendar-days">';

        for (let i = 0; i < firstDay; i++) {
            calendarHTML += '<div class="calendar-day empty"></div>';
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dateStr = date.toISOString().split('T')[0];
            const isPast = date < new Date(new Date().toDateString());
            const isAvailable = fechasDisponibles.includes(dateStr);
            const isToday = dateStr === minDate;

            let className = 'calendar-day';
            if (isPast) className += ' past';
            if (isAvailable) className += ' available';
            if (isToday) className += ' today';
            if (dateStr === fechaIdaInput?.value) className += ' selected';

            const onclick = !isPast ? `onclick="seleccionarFecha('${dateStr}')"` : 'onclick="event.preventDefault();"';

            calendarHTML += `
                <div class="${className}" ${onclick}>
                    ${day}
                    ${isAvailable && !isPast ? '<span class="available-indicator"></span>' : ''}
                </div>
            `;
        }

        calendarHTML += '</div></div>';

        dateContainer.innerHTML = calendarHTML;

        const prevBtn = document.querySelector('.calendar-nav.prev-month');
        const nextBtn = document.querySelector('.calendar-nav.next-month');

        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
                renderCalendar();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
                renderCalendar();
            });
        }
    }

    window.seleccionarFecha = function(dateStr) {
        if (fechaIdaInput) {
            fechaIdaInput.value = dateStr;
            fechaIdaInput.dispatchEvent(new Event('change', { bubbles: true }));
            renderCalendar();
        }
    };

    if (origenInput) {
        origenInput.addEventListener('change', cargarFechasDisponibles);
        origenInput.addEventListener('blur', cargarFechasDisponibles);
    }

    if (destinoInput) {
        destinoInput.addEventListener('change', cargarFechasDisponibles);
        destinoInput.addEventListener('blur', cargarFechasDisponibles);
    }

    renderCalendar();
});
