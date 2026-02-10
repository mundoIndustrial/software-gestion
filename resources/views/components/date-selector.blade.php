<!-- Date selector component -->
<div class="date-selector-section" 
     x-show="!showRecords"
     x-data="{
        filterType: '{{ request('filter_type', 'range') }}',
        startDate: '{{ request('start_date', now()->format('Y-m-d')) }}',
        endDate: '{{ request('end_date', now()->format('Y-m-d')) }}',
        specificDate: '{{ request('specific_date', now()->format('Y-m-d')) }}',
        month: '{{ request('month', now()->format('Y-m')) }}',
        specificDates: '{{ request('specific_dates', '') }}'
    }">

    <div class="filters-row">
        <!-- Tipo de filtro -->
        <div class="filter-type-group">
            <label class="filter-type-label">Tipo de filtro</label>
            <select x-model="filterType" class="filter-select">
                <option value="range">Rango de fechas</option>
                <option value="day">Día específico</option>
                <option value="month">Mes completo</option>
                <option value="specific">Días específicos</option>
            </select>
        </div>

        <!-- Rango -->
        <template x-if="filterType === 'range'">
            <div class="date-inputs-inline">
                <div class="date-input-group">
                    <label for="startDate">Inicio</label>
                    <input type="date" id="startDate" x-model="startDate">
                </div>
                <div class="date-input-group">
                    <label for="endDate">Fin</label>
                    <input type="date" id="endDate" x-model="endDate">
                </div>
            </div>
        </template>

        <!-- Día -->
        <template x-if="filterType === 'day'">
            <div class="date-inputs-inline">
                <div class="date-input-group">
                    <label for="specificDate">Día</label>
                    <input type="date" id="specificDate" x-model="specificDate">
                </div>
            </div>
        </template>

        <!-- Mes -->
        <template x-if="filterType === 'month'">
            <div class="date-inputs-inline">
                <div class="date-input-group">
                    <label for="month">Mes</label>
                    <input type="month" id="month" x-model="month">
                </div>
            </div>
        </template>

        <button class="btn-apply" onclick="filtrarPorFechas()">Aplicar Filtro</button>
    </div>

    <!-- Calendario solo para días específicos -->
    <template x-if="filterType === 'specific'">
        <div class="calendar-container">
            <div class="calendar-wrapper">
                <div id="calendar" class="calendar-widget"></div>
            </div>
        </div>
    </template>
</div>

<style>
/* === General layout === */
.date-selector-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 10px auto;
    padding: 15px 20px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
    width: 100%;
}

.filters-row {
    display: flex;
    align-items: flex-end;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
    width: 100%;
}

/* === Filter group === */
.filter-type-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-type-label {
    color: #9ca3af;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.filter-select {
    background: #374151;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 10px 12px;
    color: #fff;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
}
.filter-select:hover { background: #4b5563; }

/* === Date inputs inline === */
.date-inputs-inline {
    display: flex;
    gap: 15px;
}

.date-input-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.date-input-group label {
    color: #9ca3af;
    font-size: 12px;
    text-transform: uppercase;
}

.date-input-group input[type="date"],
.date-input-group input[type="month"] {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 8px 12px;
    color: #fff;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
}
.date-input-group input:hover { background: rgba(255, 255, 255, 0.12); }

/* === Button === */
.btn-apply {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-apply:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
}

/* === Calendar compact modern === */
.calendar-container {
    display: flex;
    justify-content: center;
    margin-top: 15px;
    width: 100%;
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
@media (max-width: 768px) {
    .filters-row {
        flex-direction: column;
        align-items: stretch;
    }
    .date-inputs-inline {
        flex-direction: column;
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
let selectedDatesDateSelector = new Set();

function initCalendar() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    const now = new Date();
    renderCalendar(now.getFullYear(), now.getMonth());
}

function renderCalendar(year, month) {
    const calendarEl = document.getElementById('calendar');
    const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                        'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const dayNames = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
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
        const isSelected = selectedDatesDateSelector.has(dateStr);
        html += `<div class="day ${isCurrentMonth ? '' : 'other-month'} ${isSelected ? 'selected' : ''}"
                 onclick="toggleDate('${dateStr}')">${currentDate.getDate()}</div>`;
        currentDate.setDate(currentDate.getDate() + 1);
    }

    html += '</div>';
    calendarEl.innerHTML = html;
}

function changeMonth(delta) {
    const header = document.querySelector('.calendar-header span');
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
    if (selectedDatesDateSelector.has(dateStr)) selectedDatesDateSelector.delete(dateStr);
    else selectedDatesDateSelector.add(dateStr);

    const header = document.querySelector('.calendar-header span');
    const [monthName, yearStr] = header.textContent.split(' ');
    const months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    renderCalendar(parseInt(yearStr), months.indexOf(monthName));
}

function filtrarPorFechas() {
    const filterType = document.querySelector('.filter-select').value;
    const url = new URL(window.location);
    url.search = '';

    url.searchParams.set('filter_type', filterType);

    if (filterType === 'range') {
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;
        if (!start || !end) return alert('Selecciona ambas fechas');
        url.searchParams.set('start_date', start);
        url.searchParams.set('end_date', end);
    }
    else if (filterType === 'day') {
        const day = document.getElementById('specificDate').value;
        if (!day) return alert('Selecciona un día');
        url.searchParams.set('specific_date', day);
    }
    else if (filterType === 'month') {
        const month = document.getElementById('month').value;
        if (!month) return alert('Selecciona un mes');
        url.searchParams.set('month', month);
    }
    else if (filterType === 'specific') {
        if (selectedDatesDateSelector.size === 0) return alert('Selecciona al menos una fecha');
        url.searchParams.set('specific_dates', Array.from(selectedDatesDateSelector).join(','));
    }

    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('.filter-select').addEventListener('change', e => {
        if (e.target.value === 'specific') initCalendar();
    });
});
</script>
