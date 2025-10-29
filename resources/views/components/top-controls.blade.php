<!-- Controles superiores: botones + selector de fechas en una sola barra -->
<div class="top-controls">
    <!-- Botones de acción (IZQUIERDA) -->
    <div class="action-icons">
        <!-- Mostrar / ocultar registros -->
        <button class="icon-btn" @click="showRecords = !showRecords" :title="showRecords ? 'Ocultar registros' : 'Mostrar registros'">
            <template x-if="!showRecords">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="14" rx="2" ry="2"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </template>
            <template x-if="showRecords">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 10h18M3 14h18M3 18h18"/>
                </svg>
            </template>
        </button>

        <!-- Nuevo registro -->
        <button class="icon-btn" @click="openFormModal()" title="Nuevo registro">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="16"/>
                <line x1="8" y1="12" x2="16" y2="12"/>
            </svg>
        </button>
    </div>

    <!-- Selector de fechas (DERECHA) -->
    <div class="date-selector-section" 
         x-show="!showRecords"
         x-data="{
            filterType: '{{ request('filter_type', 'range') }}',
            startDate: '{{ request('start_date', '') }}',
            endDate: '{{ request('end_date', '') }}',
            specificDate: '{{ request('specific_date', '') }}',
            month: '{{ request('month', '') }}',
            specificDates: '{{ request('specific_dates', '') }}'
         }">

        <div class="filters-row">
            <!-- Tipo de filtro -->
            <div class="filter-type-group">
                <select x-model="filterType" 
                        class="filter-select"
                        @change="if ($event.target.value === 'specific') { setTimeout(() => initCalendar(), 50); }">
                    <option value="range">Rango de fechas</option>
                    <option value="day">Día específico</option>
                    <option value="month">Mes completo</option>
                    <option value="specific">Días específicos</option>
                </select>
            </div>

            <!-- Rango -->
            <template x-if="filterType === 'range'">
                <div class="date-inputs-inline">
                    <input type="date" id="startDate" x-model="startDate" placeholder="Inicio">
                    <input type="date" id="endDate" x-model="endDate" placeholder="Fin">
                </div>
            </template>

            <!-- Día -->
            <template x-if="filterType === 'day'">
                <div class="date-inputs-inline">
                    <input type="date" id="specificDate" x-model="specificDate">
                </div>
            </template>

            <!-- Mes -->
            <template x-if="filterType === 'month'">
                <div class="date-inputs-inline">
                    <input type="month" id="month" x-model="month">
                </div>
            </template>

            <button class="btn-apply" onclick="filtrarPorFechas()">Aplicar</button>
        </div>

        <!-- Calendario para días específicos (dentro del selector) -->
        <template x-if="filterType === 'specific'">
            <div class="calendar-container" x-init="setTimeout(() => initCalendar(), 100)">
                <div class="calendar-wrapper">
                    <div id="calendar" class="calendar-widget"></div>
                </div>
            </div>
        </template>
    </div>
</div>

<style>
/* === Top Controls Bar === */
.top-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1rem;
    padding: 0.8rem 1.2rem;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* === Action Icons (LEFT) === */
.action-icons {
    display: flex;
    gap: 0.8rem;
    align-items: center;
}

.icon-btn {
    background: #374151;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 10px;
    padding: 0.6rem;
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-btn:hover {
    background: #4b5563;
    transform: scale(1.05);
    border-color: rgba(255, 107, 53, 0.4);
}

.icon-btn svg {
    stroke: #f0f0f0;
}

/* === Date Selector (RIGHT) === */
.date-selector-section {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    flex: 1;
    gap: 1rem;
}

.filters-row {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    flex-wrap: nowrap;
}

.filter-type-group {
    display: flex;
    align-items: center;
}

.filter-select {
    background: #374151;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 0.5rem 0.8rem;
    color: #fff;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 140px;
}

.filter-select:hover {
    background: #4b5563;
    border-color: rgba(255, 107, 53, 0.3);
}

/* === Date Inputs === */
.date-inputs-inline {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.date-inputs-inline input[type="date"],
.date-inputs-inline input[type="month"] {
    background: #374151;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 0.5rem 0.8rem;
    color: #fff;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 140px;
}

.date-inputs-inline input:hover {
    background: #4b5563;
    border-color: rgba(255, 107, 53, 0.3);
}

/* === Apply Button === */
.btn-apply {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
    border: none;
    padding: 0.55rem 1.2rem;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-apply:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
}

/* === Calendar === */
.calendar-container {
    display: flex;
    justify-content: flex-end;
    width: 100%;
    margin-top: 0.5rem;
}

.calendar-wrapper {
    background: #1f2937;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    padding: 12px 16px;
    max-width: 340px;
    width: 100%;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    transition: all 0.3s;
}

.calendar-wrapper:hover {
    transform: translateY(-2px);
}

.calendar-widget .calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.calendar-widget .calendar-header span {
    font-weight: 600;
    font-size: 14px;
    color: #f3f4f6;
}

.calendar-widget .calendar-header button {
    background: rgba(255, 255, 255, 0.08);
    border: none;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    transition: background 0.3s;
}

.calendar-widget .calendar-header button:hover {
    background: rgba(255, 255, 255, 0.2);
}

.calendar-widget .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 6px;
    text-align: center;
}

.calendar-widget .day-name {
    font-size: 11px;
    color: #9ca3af;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.calendar-widget .day {
    padding: 6px 0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 13px;
    color: #fff;
}

.calendar-widget .day.other-month {
    color: #6b7280;
}

.calendar-widget .day:hover {
    background: rgba(255, 255, 255, 0.08);
}

.calendar-widget .day.selected {
    background: #6366f1;
    color: white;
    font-weight: 600;
    box-shadow: 0 0 6px rgba(99, 102, 241, 0.6);
}

/* === Responsive === */
@media (max-width: 1024px) {
    .top-controls {
        flex-wrap: wrap;
    }
    
    .date-selector-section {
        width: 100%;
        align-items: center;
    }
    
    .calendar-container {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .top-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .action-icons {
        justify-content: center;
    }
    
    .date-selector-section {
        align-items: stretch;
    }
    
    .filters-row {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .date-inputs-inline {
        flex-direction: column;
        width: 100%;
    }
    
    .date-inputs-inline input {
        width: 100%;
    }
    
    .btn-apply {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .calendar-wrapper {
        max-width: 280px;
        padding: 10px;
    }
    
    .calendar-widget .day {
        padding: 5px 0;
        font-size: 12px;
    }
}
</style>

<script>
let selectedDatesTopControls = new Set();

function initCalendar() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) {
        console.log('Calendar element not found, retrying...');
        setTimeout(initCalendar, 100);
        return;
    }

    const now = new Date();
    renderCalendar(now.getFullYear(), now.getMonth());
}

function renderCalendar(year, month) {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                        'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const dayNames = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

    const firstDay = new Date(year, month, 1);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());

    let html = `
        <div class="calendar-header">
            <button onclick="changeMonth(-1)">‹</button>
            <span>${monthNames[month]} ${year}</span>
            <button onclick="changeMonth(1)">›</button>
        </div>
        <div class="calendar-grid">
    `;

    // Day names
    dayNames.forEach(day => {
        html += `<div class="day-name">${day}</div>`;
    });

    let currentDate = new Date(startDate);
    for (let i = 0; i < 42; i++) {
        const isCurrentMonth = currentDate.getMonth() === month;
        const dateStr = currentDate.toISOString().split('T')[0];
        const isSelected = selectedDatesTopControls.has(dateStr);
        html += `<div class="day ${isCurrentMonth ? '' : 'other-month'} ${isSelected ? 'selected' : ''}"
                 onclick="toggleDate('${dateStr}')">${currentDate.getDate()}</div>`;
        currentDate.setDate(currentDate.getDate() + 1);
    }

    html += '</div>';
    calendarEl.innerHTML = html;
}

function changeMonth(delta) {
    const header = document.querySelector('.calendar-header span');
    if (!header) return;

    const [monthName, yearStr] = header.textContent.split(' ');
    const months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const monthIndex = months.indexOf(monthName);
    const year = parseInt(yearStr);
    const newMonth = monthIndex + delta;
    const newYear = newMonth < 0 ? year - 1 : newMonth > 11 ? year + 1 : year;
    const adjustedMonth = (newMonth + 12) % 12;

    renderCalendar(newYear, adjustedMonth);
}

function toggleDate(dateStr) {
    if (selectedDatesTopControls.has(dateStr)) selectedDatesTopControls.delete(dateStr);
    else selectedDatesTopControls.add(dateStr);

    const header = document.querySelector('.calendar-header span');
    if (!header) return;

    const [monthName, yearStr] = header.textContent.split(' ');
    const months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    renderCalendar(parseInt(yearStr), months.indexOf(monthName));
}

function filtrarPorFechas() {
    const filterSelect = document.querySelector('.filter-select');
    if (!filterSelect) return;

    const filterType = filterSelect.value;
    const url = new URL(window.location);
    url.search = '';

    url.searchParams.set('filter_type', filterType);

    if (filterType === 'range') {
        const start = document.getElementById('startDate')?.value;
        const end = document.getElementById('endDate')?.value;
        if (!start || !end) return alert('Selecciona ambas fechas');
        url.searchParams.set('start_date', start);
        url.searchParams.set('end_date', end);
    }
    else if (filterType === 'day') {
        const day = document.getElementById('specificDate')?.value;
        if (!day) return alert('Selecciona un día');
        url.searchParams.set('specific_date', day);
    }
    else if (filterType === 'month') {
        const month = document.getElementById('month')?.value;
        if (!month) return alert('Selecciona un mes');
        url.searchParams.set('month', month);
    }
    else if (filterType === 'specific') {
        if (selectedDatesTopControls.size === 0) return alert('Selecciona al menos una fecha');
        url.searchParams.set('specific_dates', Array.from(selectedDatesTopControls).join(','));
    }

    window.location.href = url.toString();
}

// Inicialización al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    // Si ya está seleccionado "specific" en el request, inicializar el calendario
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('filter_type') === 'specific') {
        setTimeout(initCalendar, 200);
    }
});
</script>