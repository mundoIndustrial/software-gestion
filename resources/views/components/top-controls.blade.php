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
            filterType: 'range',
            startDate: '',
            endDate: '',
            specificDate: '',
            month: '',
            specificDates: '',
            selectedDates: new Set(),
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

                // 🔑 LIMPIAR dashboardFilterParams GLOBAL para mostrar todos los datos
                if (typeof dashboardFilterParams !== 'undefined') {
                    dashboardFilterParams = {};
                    console.log(' dashboardFilterParams limpiado - Mostrando TODOS los datos');
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

                // Construir parámetros de filtro
                const params = new URLSearchParams();
                params.set('filter_type', this.filterType);

                if (this.filterType === 'range') {
                    const start = this.startDate;
                    const end = this.endDate;

                    console.log('Start date:', start);
                    console.log('End date:', end);

                    if (!start || !end) {
                        showFilterModal('Selecciona ambas fechas (inicio y fin)');
                        return;
                    }

                    params.set('start_date', start);
                    params.set('end_date', end);
                }
                else if (this.filterType === 'day') {
                    const day = this.specificDate;
                    console.log('Specific date:', day);

                    if (!day) {
                        showFilterModal('Selecciona un día específico');
                        return;
                    }

                    params.set('specific_date', day);
                }
                else if (this.filterType === 'month') {
                    const month = this.month;
                    console.log('Month:', month);

                    if (!month) {
                        showFilterModal('Selecciona un mes');
                        return;
                    }

                    params.set('month', month);
                }
                else if (this.filterType === 'specific') {
                    // Sincronizar con window.selectedDatesTopControls
                    this.selectedDates = new Set(window.selectedDatesTopControls);
                    
                    console.log('Selected dates size:', this.selectedDates.size);
                    console.log('Selected dates:', Array.from(this.selectedDates));
                    
                    if (this.selectedDates.size === 0) {
                        showFilterModal('Selecciona al menos una fecha en el calendario');
                        return;
                    }

                    const datesArray = Array.from(this.selectedDates).sort();
                    console.log('Specific dates to send:', datesArray);
                    params.set('specific_dates', datesArray.join(','));
                }

                console.log('Filtros aplicados:', params.toString());

                // 🔑 ACTUALIZAR dashboardFilterParams GLOBAL para que persista en tiempo real
                if (typeof dashboardFilterParams !== 'undefined') {
                    dashboardFilterParams = {};
                    
                    if (params.has('filter_type')) {
                        dashboardFilterParams['filter_type'] = params.get('filter_type');
                        
                        if (params.has('specific_date')) {
                            dashboardFilterParams['specific_date'] = params.get('specific_date');
                        }
                        if (params.has('start_date')) {
                            dashboardFilterParams['start_date'] = params.get('start_date');
                        }
                        if (params.has('end_date')) {
                            dashboardFilterParams['end_date'] = params.get('end_date');
                        }
                        if (params.has('month')) {
                            dashboardFilterParams['month'] = params.get('month');
                        }
                        if (params.has('specific_dates')) {
                            dashboardFilterParams['specific_dates'] = params.get('specific_dates');
                        }
                    }
                    
                    console.log(' dashboardFilterParams actualizado para tiempo real:', dashboardFilterParams);
                }
                
                // Llamar a la función para actualizar las tablas del dashboard
                if (typeof updateDashboardTablesFromFilter === 'function') {
                    updateDashboardTablesFromFilter(params);
                }

                // Actualizar tabla de seguimiento de módulos
                if (typeof updateSeguimientoTable === 'function') {
                    updateSeguimientoTable(params);
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
/* === Variables para Top Controls === */
:root {
    --top-controls-bg: #ffffff;
    --top-controls-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    --icon-btn-bg: #f3f4f6;
    --icon-btn-border: #e5e7eb;
    --icon-btn-hover-bg: #e5e7eb;
    --icon-btn-text: #374151;
    --filter-select-bg: #f9fafb;
    --filter-select-border: #d1d5db;
    --filter-select-text: #1f2937;
    --calendar-bg: #ffffff;
    --calendar-border: #e5e7eb;
    --calendar-header-text: #1f2937;
    --calendar-day-text: #374151;
    --calendar-day-hover: #f3f4f6;
}

body.dark-theme {
    --top-controls-bg: rgba(255, 255, 255, 0.03);
    --top-controls-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    --icon-btn-bg: #374151;
    --icon-btn-border: rgba(255, 255, 255, 0.15);
    --icon-btn-hover-bg: #4b5563;
    --icon-btn-text: #fff;
    --filter-select-bg: #374151;
    --filter-select-border: rgba(255, 255, 255, 0.15);
    --filter-select-text: #fff;
    --calendar-bg: #1f2937;
    --calendar-border: rgba(255, 255, 255, 0.1);
    --calendar-header-text: #f3f4f6;
    --calendar-day-text: #fff;
    --calendar-day-hover: rgba(255, 255, 255, 0.08);
}

/* === Top Controls Bar === */
.top-controls {
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    gap: 2rem;
    margin-bottom: 1rem;
    padding: 0.8rem 1.2rem;
    background: var(--top-controls-bg);
    border-radius: 12px;
    box-shadow: var(--top-controls-shadow);
    border: 1px solid var(--filter-select-border);
    width: 100%;
    box-sizing: border-box;
}

/* === Action Icons (LEFT) === */
.action-icons {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-shrink: 0;
}

.icon-btn {
    background: var(--icon-btn-bg);
    border: 1px solid var(--icon-btn-border);
    border-radius: 10px;
    padding: 0.6rem;
    color: var(--icon-btn-text);
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-btn:hover {
    background: var(--icon-btn-hover-bg);
    transform: scale(1.05);
    border-color: rgba(255, 107, 53, 0.4);
}

.icon-btn svg {
    stroke: var(--icon-btn-text);
}

/* === Date Selector (RIGHT) === */
.date-selector-section {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-self: end;
    gap: 1rem;
}

.filters-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 1rem;
    flex-wrap: nowrap;
}

.filter-type-group {
    display: flex;
    align-items: center;
}

.filter-select {
    background: var(--filter-select-bg);
    border: 1px solid var(--filter-select-border);
    border-radius: 8px;
    padding: 0.5rem 0.8rem;
    color: var(--filter-select-text);
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 160px;
}

.filter-select:hover {
    background: var(--icon-btn-hover-bg);
    border-color: rgba(255, 107, 53, 0.3);
}

/* === Date Inputs === */
.date-inputs-inline {
    display: flex;
    gap: 0.8rem;
    align-items: center;
    flex-wrap: wrap;
}

.date-inputs-inline input[type="date"],
.date-inputs-inline input[type="month"] {
    background: var(--filter-select-bg);
    border: 1px solid var(--filter-select-border);
    border-radius: 8px;
    padding: 0.5rem 0.8rem;
    color: var(--filter-select-text);
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 140px;
    width: auto;
}

.date-inputs-inline input:hover {
    background: var(--icon-btn-hover-bg);
    border-color: rgba(255, 107, 53, 0.3);
}

/* Color scheme para inputs date en navegadores webkit */
body.dark-theme .date-inputs-inline input[type="date"]::-webkit-calendar-picker-indicator,
body.dark-theme .date-inputs-inline input[type="month"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}

.date-inputs-inline input[type="date"]::-webkit-calendar-picker-indicator,
.date-inputs-inline input[type="month"]::-webkit-calendar-picker-indicator {
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
    margin-left: 0.5rem;
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
    background: var(--calendar-bg);
    border: 1px solid var(--calendar-border);
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
    color: var(--calendar-header-text);
}

.calendar-widget .calendar-header button {
    background: var(--icon-btn-bg);
    border: 1px solid var(--icon-btn-border);
    color: var(--icon-btn-text);
    font-size: 16px;
    cursor: pointer;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    transition: background 0.3s;
}

.calendar-widget .calendar-header button:hover {
    background: var(--icon-btn-hover-bg);
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
    color: var(--calendar-day-text);
}

.calendar-widget .day.other-month {
    color: #9ca3af;
    opacity: 0.5;
}

.calendar-widget .day:hover {
    background: var(--calendar-day-hover);
}

.calendar-widget .day.selected {
    background: #6366f1;
    color: white;
    font-weight: 600;
    box-shadow: 0 0 6px rgba(99, 102, 241, 0.6);
}

/* === Responsive === */
@media (min-width: 1201px) {
    .top-controls {
        display: grid;
        grid-template-columns: auto 1fr;
    }
    
    .filters-row {
        flex-wrap: nowrap;
    }
}

@media (max-width: 1400px) {
    .filters-row {
        flex-wrap: wrap;
        gap: 0.8rem;
    }
    
    .filter-select {
        min-width: 140px;
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
        display: flex;
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
        flex-wrap: wrap;
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

/* === Modal Styles === */
.modal-overlay-filter {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
}

.modal-overlay-filter.active {
    display: flex;
}

.modal-content-filter {
    background: white;
    border-radius: 12px;
    padding: 30px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease-out;
}

body.dark-theme .modal-content-filter {
    background: #1f2937;
    color: #f3f4f6;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header-filter {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.modal-icon-filter {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #f39c12, #e67e22);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.modal-icon-filter svg {
    width: 28px;
    height: 28px;
    color: white;
}

.modal-title-filter {
    font-size: 20px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

body.dark-theme .modal-title-filter {
    color: #f3f4f6;
}

.modal-message-filter {
    color: #555;
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 25px;
}

body.dark-theme .modal-message-filter {
    color: #d1d5db;
}

.modal-button-filter {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    width: 100%;
    transition: all 0.3s;
}

.modal-button-filter:hover {
    background: linear-gradient(135deg, #2980b9, #21618c);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}
</style>

<script>
// Funciones del modal de alerta
function showFilterModal(message) {
    const modal = document.getElementById('filterAlertModal');
    const messageEl = document.getElementById('filterModalMessage');
    if (modal && messageEl) {
        messageEl.textContent = message;
        modal.classList.add('active');
    }
}

function closeFilterModal() {
    const modal = document.getElementById('filterAlertModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Limpiar TODOS los filtros de la URL al cargar la página
// Esto asegura que al recargar (F5), los filtros se limpien automáticamente
document.addEventListener('DOMContentLoaded', function() {
    const url = new URL(window.location);
    let hasFilters = false;
    
    // Verificar si hay parámetros de filtro (de fecha o de columnas)
    if (url.searchParams.has('filter_type') || 
        url.searchParams.has('start_date') || 
        url.searchParams.has('end_date') || 
        url.searchParams.has('specific_date') || 
        url.searchParams.has('month') || 
        url.searchParams.has('specific_dates') ||
        url.searchParams.has('filters')) {
        hasFilters = true;
    }
    
    // Si hay filtros, limpiarlos de la URL
    if (hasFilters) {
        // Limpiar filtros de fecha del selector
        url.searchParams.delete('filter_type');
        url.searchParams.delete('start_date');
        url.searchParams.delete('end_date');
        url.searchParams.delete('specific_date');
        url.searchParams.delete('month');
        url.searchParams.delete('specific_dates');
        
        // Limpiar filtros de columnas
        url.searchParams.delete('filters');
        
        // Actualizar la URL sin recargar
        window.history.replaceState({}, '', url.toString());
        console.log(' Todos los filtros limpiados al recargar la página');
        
        // También limpiar sessionStorage de filtros de columnas
        const section = url.searchParams.get('section') || 'produccion';
        sessionStorage.removeItem(`tableros_filters_${section}`);
        
        // Recargar la página para aplicar los cambios
        window.location.reload();
    }
});

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
    // 🔑 Actualizar dashboardFilterParams con los nuevos filtros
    if (typeof actualizarFiltrosDashboard === 'function') {
        actualizarFiltrosDashboard(
            params.get('filter_type'),
            params.get('specific_date'),
            params.get('start_date'),
            params.get('end_date'),
            params.get('month'),
            params.get('specific_dates')
        );
    }
    
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
            showFilterModal('Error: Datos inválidos recibidos del servidor');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showFilterModal('Error al filtrar los datos del dashboard. Por favor, recarga la página.');
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
    
    // Usar ruta diferente para corte (tiene 2 tablas) vs producción/polos (seguimiento de módulos)
    let url;
    if (activeTab === 'corte') {
        url = `/tableros/corte-fullscreen?${currentParams.toString()}`;
    } else {
        currentParams.set('section', activeTab);
        url = `/tableros/fullscreen?${currentParams.toString()}`;
    }
    
    // Cargar en la misma ventana
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

<!-- Modal de Alerta para Filtros -->
<div id="filterAlertModal" class="modal-overlay-filter" onclick="if(event.target === this) closeFilterModal()">
    <div class="modal-content-filter">
        <div class="modal-header-filter">
            <div class="modal-icon-filter">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="modal-title-filter">Atención</h3>
        </div>
        <p id="filterModalMessage" class="modal-message-filter"></p>
        <button class="modal-button-filter" onclick="closeFilterModal()">Entendido</button>
    </div>
</div>
