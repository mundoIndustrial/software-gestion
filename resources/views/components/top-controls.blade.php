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

        <!-- Vista completa -->
        <button class="icon-btn" @click="openFullscreenView()" x-show="!showRecords" title="Vista completa">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
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
            specificDates: '{{ request('specific_dates', '') }}',
            selectedDates: new Set('{{ request('specific_dates', '') }}'.split(',').filter(d => d)),
            limpiarFiltros() {
                // Reset all filter fields to default values
                this.filterType = 'range';
                this.startDate = '';
                this.endDate = '';
                this.specificDate = '';
                this.month = '';
                this.selectedDates.clear();

                // Clear calendar selection if calendar is visible
                if (typeof clearCalendarSelection === 'function') {
                    clearCalendarSelection();
                }

                // Clear URL parameters
                const url = new URL(window.location);
                url.search = '';
                window.history.pushState({}, '', url.toString());

                // Update dashboard tables with no filters (show all data)
                if (typeof updateDashboardTablesFromFilter === 'function') {
                    updateDashboardTablesFromFilter(new URLSearchParams());
                } else {
                    // If function doesn't exist, reload page to show all data
                    window.location.href = url.toString();
                }
            },
            filtrarPorFechas() {
                console.log('Filter type:', this.filterType);

                // Construir la URL
                const url = new URL(window.location);
                url.search = '';
                url.searchParams.set('filter_type', this.filterType);

                if (this.filterType === 'range') {
                    const start = this.startDate;
                    const end = this.endDate;

                    console.log('Start date:', start);
                    console.log('End date:', end);

                    if (!start || !end) {
                        alert('Selecciona ambas fechas (inicio y fin)');
                        return;
                    }

                    url.searchParams.set('start_date', start);
                    url.searchParams.set('end_date', end);
                }
                else if (this.filterType === 'day') {
                    const day = this.specificDate;
                    console.log('Specific date:', day);

                    if (!day) {
                        alert('Selecciona un día');
                        return;
                    }

                    url.searchParams.set('specific_date', day);
                }
                else if (this.filterType === 'month') {
                    const month = this.month;
                    console.log('Month:', month);

                    if (!month) {
                        alert('Selecciona un mes');
                        return;
                    }

                    url.searchParams.set('month', month);
                }
                else if (this.filterType === 'specific') {
                    // Sincronizar con window.selectedDatesTopControls
                    this.selectedDates = new Set(window.selectedDatesTopControls);
                    
                    console.log('Selected dates size:', this.selectedDates.size);
                    console.log('Selected dates:', Array.from(this.selectedDates));
                    
                    if (this.selectedDates.size === 0) {
                        alert('Selecciona al menos una fecha en el calendario');
                        return;
                    }

                    const datesArray = Array.from(this.selectedDates).sort();
                    console.log('Specific dates to send:', datesArray);
                    url.searchParams.set('specific_dates', datesArray.join(','));
                }

                console.log('Final URL:', url.toString());

                // Actualizar la URL sin recargar la página
                window.history.pushState({}, '', url.toString());

                // Llamar a la función para actualizar las tablas del dashboard
                if (typeof updateDashboardTablesFromFilter === 'function') {
                    updateDashboardTablesFromFilter(url.searchParams);
                } else {
                    // Si no existe la función, recargar la página
                    window.location.href = url.toString();
                }

                // Actualizar tabla de seguimiento de módulos
                if (typeof updateSeguimientoTable === 'function') {
                    updateSeguimientoTable(url.searchParams);
                }
            }
         }">

        <div class="filters-row">
            <!-- Tipo de filtro -->
            <div class="filter-type-group">
                <select x-model="filterType" 
                        id="filterTypeSelect"
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

            <button class="btn-apply" @click="filtrarPorFechas()">Aplicar</button>
            <button class="btn-clear" @click="limpiarFiltros()">Limpiar Filtros</button>
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
    flex-wrap: wrap;
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
    flex-wrap: wrap;
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
    min-width: 120px;
    flex: 1;
}

.date-inputs-inline input:hover {
    background: #4b5563;
    border-color: rgba(255, 107, 53, 0.3);
}

/* Color scheme para inputs date en navegadores webkit */
.date-inputs-inline input[type="date"]::-webkit-calendar-picker-indicator,
.date-inputs-inline input[type="month"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}

/* === Apply Button === */
.btn-apply {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
    border: none;
    padding: 0.55rem 1rem;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    flex-shrink: 0;
}

.btn-apply:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
}

/* === Clear Button === */
.btn-clear {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    color: white;
    border: none;
    padding: 0.55rem 0.8rem;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    flex-shrink: 0;
}

.btn-clear:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.4);
    background: linear-gradient(135deg, #4b5563, #374151);
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
@media (max-width: 1400px) {
    .filters-row {
        flex-wrap: wrap;
        gap: 0.6rem;
    }
    
    .filter-select {
        min-width: 130px;
        font-size: 12px;
    }
    
    .date-inputs-inline input[type="date"],
    .date-inputs-inline input[type="month"] {
        min-width: 130px;
        font-size: 12px;
    }
    
    .btn-apply,
    .btn-clear {
        padding: 0.5rem 1rem;
        font-size: 12px;
    }
}

@media (max-width: 1300px) {
    .date-inputs-inline {
        flex-direction: column;
        align-items: stretch;
    }
    
    .date-inputs-inline input[type="date"],
    .date-inputs-inline input[type="month"] {
        width: 100%;
    }
}

@media (max-width: 1200px) {
    .top-controls {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .date-selector-section {
        width: 100%;
        align-items: flex-start;
    }
    
    .filters-row {
        width: 100%;
        justify-content: flex-start;
    }
}

@media (max-width: 1024px) {
    .top-controls {
        padding: 0.8rem;
    }
    
    .date-selector-section {
        align-items: center;
    }
    
    .filters-row {
        justify-content: center;
    }
    
    .calendar-container {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .top-controls {
        flex-direction: column;
        align-items: stretch;
        gap: 0.8rem;
    }
    
    .action-icons {
        justify-content: center;
    }
    
    .date-selector-section {
        align-items: stretch;
    }
    
    .filters-row {
        flex-direction: column;
        gap: 0.6rem;
    }
    
    .filter-type-group,
    .date-inputs-inline {
        width: 100%;
    }
    
    .filter-select,
    .date-inputs-inline input {
        width: 100%;
        min-width: unset;
    }
    
    .btn-apply,
    .btn-clear {
        width: 100%;
        margin-left: 0;
    }
}

@media (max-width: 900px) {
    .btn-apply,
    .btn-clear {
        padding: 0.5rem 0.9rem;
        font-size: 12px;
    }
}

@media (max-width: 640px) {
    .filters-row {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }
    
    .filter-type-group,
    .date-inputs-inline {
        width: 100%;
    }
    
    .filter-select,
    .date-inputs-inline input {
        width: 100%;
    }
    
    .btn-apply,
    .btn-clear {
        width: 100%;
        margin-left: 0 !important;
    }
}

@media (max-width: 480px) {
    .top-controls {
        padding: 0.6rem;
    }
    
    .icon-btn {
        padding: 0.5rem;
    }
    
    .filter-select,
    .date-inputs-inline input {
        font-size: 11px;
        padding: 0.4rem 0.6rem;
    }
    
    .btn-apply,
    .btn-clear {
        font-size: 11px;
        padding: 0.45rem 0.8rem;
    }
    
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
if (typeof window.selectedDatesTopControls === 'undefined') {
    window.selectedDatesTopControls = new Set();
}
if (typeof window.currentCalendarYear === 'undefined') {
    window.currentCalendarYear = null;
}
if (typeof window.currentCalendarMonth === 'undefined') {
    window.currentCalendarMonth = null;
}

function initCalendar() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) {
        console.log('Calendar element not found, retrying...');
        setTimeout(initCalendar, 100);
        return;
    }

    // Cargar fechas previamente seleccionadas desde URL
    const urlParams = new URLSearchParams(window.location.search);
    const specificDates = urlParams.get('specific_dates');
    if (specificDates) {
        window.selectedDatesTopControls = new Set(specificDates.split(','));
    }

    // Sync with Alpine.js selectedDates
    const alpineComponent = document.querySelector('[x-data]');
    if (alpineComponent && alpineComponent._x_dataStack) {
        const data = alpineComponent._x_dataStack[alpineComponent._x_dataStack.length - 1];
        if (data.selectedDates) {
            window.selectedDatesTopControls = new Set([...data.selectedDates]);
        }
    }

    const now = new Date();
    window.currentCalendarYear = now.getFullYear();
    window.currentCalendarMonth = now.getMonth();
    renderCalendar(window.currentCalendarYear, window.currentCalendarMonth);
}

function clearCalendarSelection() {
    // Clear global calendar selection
    window.selectedDatesTopControls.clear();

    // Re-render calendar to reflect cleared selection
    if (window.currentCalendarYear !== null && window.currentCalendarMonth !== null) {
        renderCalendar(window.currentCalendarYear, window.currentCalendarMonth);
    }
}

function renderCalendar(year, month) {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    window.currentCalendarYear = year;
    window.currentCalendarMonth = month;

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
        const isSelected = window.selectedDatesTopControls.has(dateStr);
        html += `<div class="day ${isCurrentMonth ? '' : 'other-month'} ${isSelected ? 'selected' : ''}"
                 onclick="toggleDate('${dateStr}')">${currentDate.getDate()}</div>`;
        currentDate.setDate(currentDate.getDate() + 1);
    }

    html += '</div>';
    calendarEl.innerHTML = html;
}

function changeMonth(delta) {
    const newMonth = window.currentCalendarMonth + delta;
    const newYear = newMonth < 0 ? window.currentCalendarYear - 1 : newMonth > 11 ? window.currentCalendarYear + 1 : window.currentCalendarYear;
    const adjustedMonth = (newMonth + 12) % 12;

    renderCalendar(newYear, adjustedMonth);
}

function toggleDate(dateStr) {
    if (window.selectedDatesTopControls.has(dateStr)) {
        window.selectedDatesTopControls.delete(dateStr);
    } else {
        window.selectedDatesTopControls.add(dateStr);
    }

    renderCalendar(window.currentCalendarYear, window.currentCalendarMonth);

    // Update Alpine.js selectedDates - buscar el componente date-selector-section
    const dateSelectorSection = document.querySelector('.date-selector-section');
    if (dateSelectorSection && dateSelectorSection.__x) {
        const alpineData = dateSelectorSection.__x.$data;
        if (alpineData && alpineData.selectedDates) {
            alpineData.selectedDates.clear();
            window.selectedDatesTopControls.forEach(date => alpineData.selectedDates.add(date));
            console.log('Fechas seleccionadas:', Array.from(alpineData.selectedDates));
        }
    }
}

function updateDashboardTablesFromFilter(params) {
    const dashboardUrl = new URL('/tableros/dashboard-tables-data', window.location.origin);
    dashboardUrl.search = params.toString();

    fetch(dashboardUrl.toString(), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.horasData && data.operariosData) {
            updateDashboardTables(data.horasData, data.operariosData);
        } else {
            console.error('Invalid data structure received:', data);
            alert('Error: Datos inválidos recibidos del servidor');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al filtrar los datos del dashboard. Por favor, recarga la página.');
    });
}

function updateDashboardTables(horasData, operariosData) {
    // Update horas table
    const horasTableBody = document.getElementById('horasTableBody');
    if (horasTableBody) {
        let html = '';
        let totalCantidadHoras = 0;
        let totalMetaHoras = 0;

        horasData.forEach(row => {
            const eficienciaClass = row.eficiencia < 70 ? '#7f1d1d' : (row.eficiencia >= 70 && row.eficiencia < 80 ? '#92400e' : (row.eficiencia >= 80 && row.eficiencia < 100 ? '#166534' : (row.eficiencia >= 100 ? '#0c4a6e' : '#374151')));
            html += `
                <tr style="background: rgba(255,255,255,0.02); transition: background-color 0.2s ease;">
                    <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #ffffff; font-weight: 500;">${row.hora}</td>
                    <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">${row.cantidad.toLocaleString()}</td>
                    <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">${row.meta.toLocaleString()}</td>
                    <td style="padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; background: ${eficienciaClass}; color: #ffffff; font-weight: 600; font-size: 13px;">
                        <div style="padding: 14px 20px; width: 100%; height: 100%;">${row.eficiencia > 0 ? row.eficiencia.toFixed(1) + '%' : '-'}</div>
                    </td>
                </tr>
            `;
            totalCantidadHoras += row.cantidad;
            totalMetaHoras += row.meta;
        });

        // Add total row
        html += `
            <tr style="background: linear-gradient(135deg, #1f2937, #374151); font-weight: 600; border-radius: 0 0 8px 8px;">
                <td style="padding: 16px 20px; border-bottom: none; color: #ffffff; border-radius: 0 0 0 8px;">TOTAL</td>
                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;" id="totalCantidadHoras">${totalCantidadHoras.toLocaleString()}</td>
                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;" id="totalMetaHoras">${totalMetaHoras.toLocaleString()}</td>
                <td style="padding: 16px 20px; border-bottom: none; border-radius: 0 0 8px 0;"></td>
            </tr>
        `;

        horasTableBody.innerHTML = html;
    }

    // Update operarios table
    const operariosTableBody = document.getElementById('operariosTableBody');
    if (operariosTableBody) {
        let html = '';
        let totalCantidadOperarios = 0;
        let totalMetaOperarios = 0;

        operariosData.forEach(row => {
            const eficienciaClass = row.eficiencia < 70 ? '#7f1d1d' : (row.eficiencia >= 70 && row.eficiencia < 80 ? '#92400e' : (row.eficiencia >= 80 && row.eficiencia < 100 ? '#166534' : (row.eficiencia >= 100 ? '#0c4a6e' : '#374151')));
            html += `
                <tr style="background: rgba(255,255,255,0.02); transition: background-color 0.2s ease;">
                    <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #ffffff; font-weight: 500;">${row.operario}</td>
                    <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">${row.cantidad.toLocaleString()}</td>
                    <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">${row.meta.toLocaleString()}</td>
                    <td style="padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; background: ${eficienciaClass}; color: #ffffff; font-weight: 600; font-size: 13px;">
                        <div style="padding: 14px 20px; width: 100%; height: 100%;">${row.eficiencia > 0 ? row.eficiencia.toFixed(1) + '%' : '-'}</div>
                    </td>
                </tr>
            `;
            totalCantidadOperarios += row.cantidad;
            totalMetaOperarios += row.meta;
        });

        // Add total row
        html += `
            <tr style="background: linear-gradient(135deg, #1f2937, #374151); font-weight: 600; border-radius: 0 0 8px 8px;">
                <td style="padding: 16px 20px; border-bottom: none; color: #ffffff; border-radius: 0 0 0 8px;">TOTAL</td>
                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;" id="totalCantidadOperarios">${totalCantidadOperarios.toLocaleString()}</td>
                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;" id="totalMetaOperarios">${totalMetaOperarios.toLocaleString()}</td>
                <td style="padding: 16px 20px; border-bottom: none; border-radius: 0 0 8px 0;"></td>
            </tr>
        `;

        operariosTableBody.innerHTML = html;
    }
}

// Función para abrir vista completa
function openFullscreenView() {
    // Intentar obtener la sección activa de diferentes formas
    let activeTab = 'produccion'; // default
    
    // Método 1: Desde Alpine.js
    try {
        const alpineEl = document.querySelector('[x-data*="tablerosApp"]');
        if (alpineEl && alpineEl.__x && alpineEl.__x.$data) {
            activeTab = alpineEl.__x.$data.activeTab || activeTab;
        }
    } catch (e) {
        console.log('No se pudo obtener activeTab desde Alpine:', e);
    }
    
    // Método 2: Desde URL params si existe
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('active_section')) {
        activeTab = urlParams.get('active_section');
    }
    
    // Método 3: Desde tab activo visible
    const activeTabElement = document.querySelector('.tab-card.active');
    if (activeTabElement) {
        const tabText = activeTabElement.textContent.toLowerCase();
        if (tabText.includes('polo')) activeTab = 'polos';
        else if (tabText.includes('corte')) activeTab = 'corte';
        else if (tabText.includes('produccion')) activeTab = 'produccion';
    }
    
    console.log('Abriendo vista completa para sección:', activeTab);
    
    // Construir URL con parámetros actuales
    const currentParams = new URLSearchParams(window.location.search);
    currentParams.set('section', activeTab);
    
    // Cargar en la misma ventana
    const url = `/tableros/fullscreen?${currentParams.toString()}`;
    window.location.href = url;
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
