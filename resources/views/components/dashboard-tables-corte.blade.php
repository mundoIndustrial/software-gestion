@php
    // Los datos dinámicos de horas y operarios se pasan desde el controlador
    $totalCantidadHoras = array_sum(array_column($horasData, 'cantidad'));
    $totalMetaHoras = array_sum(array_column($horasData, 'meta'));

    $totalCantidadOperarios = array_sum(array_column($operariosData, 'cantidad'));
    $totalMetaOperarios = array_sum(array_column($operariosData, 'meta'));
@endphp

<style>
/* Variables para tema claro/oscuro en dashboard-corte */
:root {
    --corte-container-bg: rgba(255, 255, 255, 0.03);
    --corte-container-border: rgba(255, 107, 53, 0.15);
    --corte-table-bg: rgba(26,29,41,0.8);
    --corte-table-border: rgba(255,107,53,0.1);
    --corte-table-title: #ffffff;
    --corte-table-row-bg: rgba(255,255,255,0.02);
    --corte-table-row-border: rgba(255,255,255,0.05);
    --corte-table-text: #ffffff;
    --corte-table-text-secondary: #94a3b8;
}

body:not(.dark-theme) {
    --corte-container-bg: #ffffff;
    --corte-container-border: #e5e7eb;
    --corte-table-bg: #ffffff;
    --corte-table-border: #e5e7eb;
    --corte-table-title: #1f2937;
    --corte-table-row-bg: #f9fafb;
    --corte-table-row-border: #e5e7eb;
    --corte-table-text: #1f2937;
    --corte-table-text-secondary: #6b7280;
}
</style>

<div class="records-table-container">
    <div class="table-scroll-container">
        <div style="display: flex; gap: 24px; padding: 24px; background: var(--corte-container-bg); border-radius: 16px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); border: 1px solid var(--corte-container-border);" id="dashboard-tables-corte">
            <!-- Tabla de Horas -->
            <div style="flex: 1; background: var(--corte-table-bg); border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 1px solid var(--corte-table-border);">
                <h3 style="color: var(--corte-table-title); font-size: 20px; font-weight: 700; margin-bottom: 20px; text-align: center;">Producción por Horas</h3>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0; font-size: 14px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #374151, #4b5563);">
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: left; color: #ffffff; border-radius: 8px 0 0 0;">HORA</th>
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: center; color: #ffffff;">CANTIDAD</th>
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: center; color: #ffffff;">META</th>
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: center; color: #ffffff; border-radius: 0 8px 0 0;">EFICIENCIA</th>
                            </tr>
                        </thead>
                        <tbody id="horasTableBody">
                            @foreach($horasData as $row)
                            <tr style="background: var(--corte-table-row-bg); transition: background-color 0.2s ease;">
                                <td style="padding: 14px 20px; border-bottom: 1px solid var(--corte-table-row-border); color: var(--corte-table-text); font-weight: 500;">{{ $row['hora'] }}</td>
                                <td style="padding: 14px 20px; border-bottom: 1px solid var(--corte-table-row-border); text-align: center; color: var(--corte-table-text-secondary); font-weight: 500;">{{ number_format($row['cantidad']) }}</td>
                                <td style="padding: 14px 20px; border-bottom: 1px solid var(--corte-table-row-border); text-align: center; color: var(--corte-table-text-secondary); font-weight: 500;">{{ number_format($row['meta']) }}</td>
                                <td style="padding: 0; border-bottom: 1px solid var(--corte-table-row-border); text-align: center; background: {{ $row['eficiencia'] >= 80 ? '#3b82f6' : ($row['eficiencia'] >= 70 ? '#eab308' : '#ef4444') }}; color: {{ $row['eficiencia'] >= 70 && $row['eficiencia'] < 80 ? '#000000' : '#ffffff' }}; font-weight: 600; font-size: 13px;">
                                    <div style="padding: 14px 20px; width: 100%; height: 100%;">{{ $row['eficiencia'] > 0 ? number_format($row['eficiencia'], 1) . '%' : '-' }}</div>
                                </td>
                            </tr>
                            @endforeach
                            <tr style="background: linear-gradient(135deg, #374151, #4b5563); font-weight: 600; border-radius: 0 0 8px 8px;">
                                <td style="padding: 16px 20px; border-bottom: none; color: #ffffff; border-radius: 0 0 0 8px;">TOTAL</td>
                                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;" id="totalCantidadHoras">{{ number_format($totalCantidadHoras) }}</td>
                                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;" id="totalMetaHoras">{{ number_format($totalMetaHoras) }}</td>
                                @php
                                    $eficienciaTotalHoras = $totalMetaHoras > 0 ? ($totalCantidadHoras / $totalMetaHoras) * 100 : 0;
                                    $bgColorTotalHoras = $eficienciaTotalHoras >= 80 ? '#3b82f6' : ($eficienciaTotalHoras >= 70 ? '#eab308' : '#ef4444');
                                    $textColorTotalHoras = ($eficienciaTotalHoras >= 70 && $eficienciaTotalHoras < 80) ? '#000000' : '#ffffff';
                                @endphp
                                <td style="padding: 0; border-bottom: none; border-radius: 0 0 8px 0; text-align: center; background: {{ $bgColorTotalHoras }}; color: {{ $textColorTotalHoras }}; font-weight: 600; font-size: 13px;" id="eficienciaTotalHoras">
                                    <div style="padding: 16px 20px; width: 100%; height: 100%;">{{ number_format($eficienciaTotalHoras, 1) }}%</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla de Operarios -->
            <div style="flex: 1; background: var(--corte-table-bg); border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 1px solid var(--corte-table-border);">
                <h3 style="color: var(--corte-table-title); font-size: 20px; font-weight: 700; margin-bottom: 20px; text-align: center;">Producción por Operarios</h3>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0; font-size: 14px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #374151, #4b5563);">
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: left; color: #ffffff; border-radius: 8px 0 0 0;">OPERARIO</th>
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: center; color: #ffffff;">CANTIDAD</th>
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: center; color: #ffffff;">META</th>
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: center; color: #ffffff; border-radius: 0 8px 0 0;">EFICIENCIA</th>
                            </tr>
                        </thead>
                        <tbody id="operariosTableBody">
                            @foreach($operariosData as $row)
                            <tr style="background: var(--corte-table-row-bg); transition: background-color 0.2s ease;">
                                <td style="padding: 14px 20px; border-bottom: 1px solid var(--corte-table-row-border); color: var(--corte-table-text); font-weight: 500;">{{ $row['operario'] }}</td>
                                <td style="padding: 14px 20px; border-bottom: 1px solid var(--corte-table-row-border); text-align: center; color: var(--corte-table-text-secondary); font-weight: 500;">{{ number_format($row['cantidad']) }}</td>
                                <td style="padding: 14px 20px; border-bottom: 1px solid var(--corte-table-row-border); text-align: center; color: var(--corte-table-text-secondary); font-weight: 500;">{{ number_format($row['meta']) }}</td>
                                <td style="padding: 0; border-bottom: 1px solid var(--corte-table-row-border); text-align: center; background: {{ $row['eficiencia'] >= 80 ? '#3b82f6' : ($row['eficiencia'] >= 70 ? '#eab308' : '#ef4444') }}; color: {{ $row['eficiencia'] >= 70 && $row['eficiencia'] < 80 ? '#000000' : '#ffffff' }}; font-weight: 600; font-size: 13px;">
                                    <div style="padding: 14px 20px; width: 100%; height: 100%;">{{ $row['eficiencia'] > 0 ? number_format($row['eficiencia'], 1) . '%' : '-' }}</div>
                                </td>
                            </tr>
                            @endforeach
                            <tr style="background: linear-gradient(135deg, #374151, #4b5563); font-weight: 600; border-radius: 0 0 8px 8px;">
                                <td style="padding: 16px 20px; border-bottom: none; color: #ffffff; border-radius: 0 0 0 8px;">TOTAL</td>
                                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;" id="totalCantidadOperarios">{{ number_format($totalCantidadOperarios) }}</td>
                                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;" id="totalMetaOperarios">{{ number_format($totalMetaOperarios) }}</td>
                                @php
                                    $eficienciaTotalOperarios = $totalMetaOperarios > 0 ? ($totalCantidadOperarios / $totalMetaOperarios) * 100 : 0;
                                    $bgColorTotalOperarios = $eficienciaTotalOperarios >= 80 ? '#3b82f6' : ($eficienciaTotalOperarios >= 70 ? '#eab308' : '#ef4444');
                                    $textColorTotalOperarios = ($eficienciaTotalOperarios >= 70 && $eficienciaTotalOperarios < 80) ? '#000000' : '#ffffff';
                                @endphp
                                <td style="padding: 0; border-bottom: none; border-radius: 0 0 8px 0; text-align: center; background: {{ $bgColorTotalOperarios }}; color: {{ $textColorTotalOperarios }}; font-weight: 600; font-size: 13px;" id="eficienciaTotalOperarios">
                                    <div style="padding: 16px 20px; width: 100%; height: 100%;">{{ number_format($eficienciaTotalOperarios, 1) }}%</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Variable global para mantener los filtros a través de eventos WebSocket
let dashboardFilterParams = {};

// Función para obtener filtros desde inputs o URL
function obtenerFiltrosDashboard() {
    console.log('🔍 Obteniendo filtros del dashboard...');
    
    // Resetear filtros
    dashboardFilterParams = {};
    
    // Primero, intentar obtener desde la URL (si está en fullscreen o tiene parámetros)
    const currentUrl = new URL(window.location.href);
    const urlFilterType = currentUrl.searchParams.get('filter_type');
    
    if (urlFilterType) {
        // Si hay filtro_type en URL, capturar todos los parámetros relacionados
        dashboardFilterParams['filter_type'] = urlFilterType;
        
        if (urlFilterType === 'day') {
            const specificDate = currentUrl.searchParams.get('specific_date');
            if (specificDate) {
                dashboardFilterParams['specific_date'] = specificDate;
            }
        } else if (urlFilterType === 'range') {
            const startDate = currentUrl.searchParams.get('start_date');
            const endDate = currentUrl.searchParams.get('end_date');
            if (startDate) dashboardFilterParams['start_date'] = startDate;
            if (endDate) dashboardFilterParams['end_date'] = endDate;
        } else if (urlFilterType === 'month') {
            const month = currentUrl.searchParams.get('month');
            if (month) dashboardFilterParams['month'] = month;
        } else if (urlFilterType === 'specific') {
            const specificDates = currentUrl.searchParams.get('specific_dates');
            if (specificDates) dashboardFilterParams['specific_dates'] = specificDates;
        }
        
        console.log(' Filtros desde URL:', dashboardFilterParams);
    }
    
    // Si no hay filtros en URL, mostrar mensaje de "sin filtros"
    if (Object.keys(dashboardFilterParams).length === 0) {
        console.log('📅 Sin filtros - Se mostrarán TODOS los registros de cualquier fecha');
    }
    
    return dashboardFilterParams;
}

// Función para actualizar filtros desde top-controls (llamada desde filtrarPorFechas)
function actualizarFiltrosDashboard(filterType, specificDate, startDate, endDate, month, specificDates) {
    console.log('🔄 Actualizando filtros del dashboard desde top-controls...');
    
    dashboardFilterParams = {};
    
    if (filterType) {
        dashboardFilterParams['filter_type'] = filterType;
        
        if (filterType === 'day' && specificDate) {
            dashboardFilterParams['specific_date'] = specificDate;
        } else if (filterType === 'range' && startDate && endDate) {
            dashboardFilterParams['start_date'] = startDate;
            dashboardFilterParams['end_date'] = endDate;
        } else if (filterType === 'month' && month) {
            dashboardFilterParams['month'] = month;
        } else if (filterType === 'specific' && specificDates) {
            dashboardFilterParams['specific_dates'] = specificDates;
        }
    }
    
    console.log(' dashboardFilterParams actualizado:', Object.keys(dashboardFilterParams).length > 0 ? dashboardFilterParams : 'VACÍO - Mostrando TODOS');
}

// Función para recargar las tablas del dashboard
function recargarDashboardCorte() {
    console.log('🔄 Recargando dashboard de Corte...');
    
    // Crear URL base
    const url = new URL('/tableros/corte/dashboard', window.location.origin);
    
    // Agregar SOLO los parámetros de filtro que existen
    Object.keys(dashboardFilterParams).forEach(key => {
        url.searchParams.set(key, dashboardFilterParams[key]);
    });
    
    console.log('🌐 URL de recarga:', url.toString());
    console.log('📊 Parámetros de filtro:', Object.keys(dashboardFilterParams).length > 0 ? dashboardFilterParams : 'NINGUNO - Trayendo TODOS los datos');
    
    // Hacer petición AJAX para obtener datos actualizados
    fetch(url.toString(), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log(' Datos del dashboard recibidos:', data);
        
        // Actualizar tabla de horas
        if (data.horas) {
            actualizarTablaHorasCompleta(data.horas);
        }
        
        // Actualizar tabla de operarios
        if (data.operarios) {
            actualizarTablaOperariosCompleta(data.operarios);
        }
    })
    .catch(error => {
        console.error(' Error al recargar dashboard:', error);
    });
}

// Función para actualizar la tabla de horas completa
function actualizarTablaHorasCompleta(horas) {
    const tbody = document.getElementById('horasTableBody');
    if (!tbody) {
        console.warn('No se encontró el tbody de horas');
        return;
    }
    
    console.log('🔄 Actualizando tabla de horas sin recargar página...');
    tbody.innerHTML = '';
    
    // Calcular totales
    let totalCantidad = 0;
    let totalMeta = 0;
    
    horas.forEach(hora => {
        totalCantidad += hora.cantidad || 0;
        totalMeta += hora.meta || 0;
        
        const eficiencia = hora.eficiencia || 0;
        const bgColor = eficiencia >= 80 ? '#3b82f6' : (eficiencia >= 70 ? '#eab308' : '#ef4444');
        const textColor = (eficiencia >= 70 && eficiencia < 80) ? '#000000' : '#ffffff';
        
        const row = document.createElement('tr');
        row.style.cssText = 'background: var(--corte-table-row-bg); transition: background-color 0.2s ease;';
        row.innerHTML = `
            <td style="padding: 14px 20px; border-bottom: 1px solid var(--corte-table-row-border); color: var(--corte-table-text); font-weight: 500;">${hora.hora}</td>
            <td style="padding: 14px 20px; border-bottom: 1px solid var(--corte-table-row-border); text-align: center; color: var(--corte-table-text-secondary); font-weight: 500;">${hora.cantidad.toLocaleString()}</td>
            <td style="padding: 14px 20px; border-bottom: 1px solid var(--corte-table-row-border); text-align: center; color: var(--corte-table-text-secondary); font-weight: 500;">${hora.meta.toLocaleString()}</td>
            <td style="padding: 0; border-bottom: 1px solid var(--corte-table-row-border); text-align: center; background: ${bgColor}; color: ${textColor}; font-weight: 600; font-size: 13px;">
                <div style="padding: 14px 20px; width: 100%; height: 100%;">${eficiencia > 0 ? eficiencia.toFixed(1) + '%' : '-'}</div>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    // Agregar fila de totales
    const eficienciaTotal = totalMeta > 0 ? (totalCantidad / totalMeta) * 100 : 0;
    const bgColorTotal = eficienciaTotal >= 80 ? '#3b82f6' : (eficienciaTotal >= 70 ? '#eab308' : '#ef4444');
    const textColorTotal = (eficienciaTotal >= 70 && eficienciaTotal < 80) ? '#000000' : '#ffffff';
    
    const totalRow = document.createElement('tr');
    totalRow.style.cssText = 'background: linear-gradient(135deg, #374151, #4b5563); font-weight: 600; border-radius: 0 0 8px 8px;';
    totalRow.innerHTML = `
        <td style="padding: 16px 20px; border-bottom: none; color: #ffffff; border-radius: 0 0 0 8px;">TOTAL</td>
        <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;">${totalCantidad.toLocaleString()}</td>
        <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;">${totalMeta.toLocaleString()}</td>
        <td style="padding: 0; border-bottom: none; border-radius: 0 0 8px 0; text-align: center; background: ${bgColorTotal}; color: ${textColorTotal}; font-weight: 600; font-size: 13px;">
            <div style="padding: 16px 20px; width: 100%; height: 100%;">${eficienciaTotal.toFixed(1)}%</div>
        </td>
    `;
    tbody.appendChild(totalRow);
    
    console.log(' Tabla de horas actualizada');
}

// Función para actualizar la tabla de operarios completa
function actualizarTablaOperariosCompleta(operarios) {
    const tbody = document.getElementById('operariosTableBody');
    if (!tbody) {
        console.warn('No se encontró el tbody de operarios');
        return;
    }
    
    console.log('🔄 Actualizando tabla de operarios sin recargar página...');
    tbody.innerHTML = '';
    
    // Calcular totales
    let totalCantidad = 0;
    let totalMeta = 0;
    
    operarios.forEach(operario => {
        totalCantidad += operario.cantidad || 0;
        totalMeta += operario.meta || 0;
        
        const eficiencia = operario.eficiencia || 0;
        const bgColor = eficiencia >= 80 ? '#3b82f6' : (eficiencia >= 70 ? '#eab308' : '#ef4444');
        const textColor = (eficiencia >= 70 && eficiencia < 80) ? '#000000' : '#ffffff';
        
        const row = document.createElement('tr');
        row.style.cssText = 'background: var(--corte-table-row-bg); transition: background-color 0.2s ease;';
        row.innerHTML = `
            <td style="padding: 14px 20px; border-bottom: 1px solid var(--corte-table-row-border); color: var(--corte-table-text); font-weight: 500;">${operario.operario}</td>
            <td style="padding: 14px 20px; border-bottom: 1px solid var(--corte-table-row-border); text-align: center; color: var(--corte-table-text-secondary); font-weight: 500;">${operario.cantidad.toLocaleString()}</td>
            <td style="padding: 14px 20px; border-bottom: 1px solid var(--corte-table-row-border); text-align: center; color: var(--corte-table-text-secondary); font-weight: 500;">${operario.meta.toLocaleString()}</td>
            <td style="padding: 0; border-bottom: 1px solid var(--corte-table-row-border); text-align: center; background: ${bgColor}; color: ${textColor}; font-weight: 600; font-size: 13px;">
                <div style="padding: 14px 20px; width: 100%; height: 100%;">${eficiencia > 0 ? eficiencia.toFixed(1) + '%' : '-'}</div>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    // Agregar fila de totales
    const eficienciaTotal = totalMeta > 0 ? (totalCantidad / totalMeta) * 100 : 0;
    const bgColorTotal = eficienciaTotal >= 80 ? '#3b82f6' : (eficienciaTotal >= 70 ? '#eab308' : '#ef4444');
    const textColorTotal = (eficienciaTotal >= 70 && eficienciaTotal < 80) ? '#000000' : '#ffffff';
    
    const totalRow = document.createElement('tr');
    totalRow.style.cssText = 'background: linear-gradient(135deg, #374151, #4b5563); font-weight: 600; border-radius: 0 0 8px 8px;';
    totalRow.innerHTML = `
        <td style="padding: 16px 20px; border-bottom: none; color: #ffffff; border-radius: 0 0 0 8px;">TOTAL</td>
        <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;">${totalCantidad.toLocaleString()}</td>
        <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;">${totalMeta.toLocaleString()}</td>
        <td style="padding: 0; border-bottom: none; border-radius: 0 0 8px 0; text-align: center; background: ${bgColorTotal}; color: ${textColorTotal}; font-weight: 600; font-size: 13px;">
            <div style="padding: 16px 20px; width: 100%; height: 100%;">${eficienciaTotal.toFixed(1)}%</div>
        </td>
    `;
    tbody.appendChild(totalRow);
    
    console.log(' Tabla de operarios actualizada');
}

// Listen for real-time updates with detailed debugging
// Esperar a que Echo esté disponible (se inicializa en bootstrap.js)
let recargarDashboardTimeout = null;

function initializeCorteChannel() {
    console.log('=== DASHBOARD CORTE - Inicializando Echo ===');
    console.log('window.Echo disponible:', !!window.Echo);
    
    // Obtener y guardar filtros globales en el componente
    obtenerFiltrosDashboard();
    console.log('📊 Filtros capturados para dashboard:', dashboardFilterParams);

    if (window.Echo) {
        console.log('Suscribiéndose al canal "corte"...');
        
        const channel = window.Echo.channel('corte');
        
        channel.subscribed(() => {
            console.log(' Suscrito exitosamente al canal "corte"');
        });
        
        channel.error((error) => {
            console.error(' Error en el canal "corte":', error);
        });
        
        channel.listen('CorteRecordCreated', (e) => {
            console.log('🎉 Evento CorteRecordCreated recibido en dashboard-tables-corte!');
            console.log('📊 Usando filtros:', dashboardFilterParams);
            
            // ⚡ DEBOUNCE: Evitar múltiples recargas en corto tiempo
            // Cancelar el timeout anterior si existe
            if (recargarDashboardTimeout) {
                clearTimeout(recargarDashboardTimeout);
            }
            
            // Programar recarga en 500ms (agrupa eventos cercanos)
            recargarDashboardTimeout = setTimeout(() => {
                console.log('Recargando tablas del dashboard (debounced)...');
                recargarDashboardCorte();
                recargarDashboardTimeout = null;
            }, 500);
        });
        
        console.log('Listeners configurados. Esperando eventos...');
    } else {
        console.error(' Echo NO está disponible todavía. Reintentando en 500ms...');
        setTimeout(initializeCorteChannel, 500);
    }
}

// Intentar inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(initializeCorteChannel, 100);
    });
} else {
    setTimeout(initializeCorteChannel, 100);
}

// NO necesitamos listener de 'registro-eliminado'
// El listener del canal WebSocket ya maneja las actualizaciones

function actualizarTablaHoras(registro) {
    const horasTableBody = document.getElementById('horasTableBody');
    if (!horasTableBody) return;

    const horaOriginal = registro.hora.hora;
    // Formatear la hora como "HORA 1", "HORA 2", etc.
    const horaKey = (horaOriginal !== 'SIN HORA' && !isNaN(horaOriginal)) 
        ? 'HORA ' + horaOriginal 
        : horaOriginal;
    
    let horaRow = null;
    let totalCantidad = 0;
    let totalMeta = 0;

    // Buscar fila existente para esta hora
    const rows = horasTableBody.querySelectorAll('tr');
    for (let row of rows) {
        const cells = row.querySelectorAll('td');
        if (cells.length > 0 && cells[0].textContent.trim() === horaKey) {
            horaRow = row;
            break;
        }
    }

    if (horaRow) {
        // Actualizar fila existente
        const cells = horaRow.querySelectorAll('td');
        if (cells.length >= 4) {
            const cantidadCell = cells[1];
            const metaCell = cells[2];
            const eficienciaCell = cells[3];

            // Sumar cantidad producida
            const currentCantidad = parseInt(cantidadCell.textContent.replace(/,/g, '')) || 0;
            const newCantidad = currentCantidad + parseInt(registro.cantidad_producida);
            cantidadCell.textContent = newCantidad.toLocaleString();

            // Calcular nueva eficiencia (mantener meta igual, actualizar cantidad)
            const meta = parseInt(metaCell.textContent.replace(/,/g, '')) || 0;
            const eficiencia = meta > 0 ? (newCantidad / meta) * 100 : 0;

            // Actualizar celda de eficiencia
            eficienciaCell.querySelector('div').textContent = eficiencia > 0 ? eficiencia.toFixed(1) + '%' : '-';

            // Actualizar color de fondo y texto según eficiencia
            const bgColor = getEficienciaBackgroundColor(eficiencia);
            const textColor = getEficienciaTextColor(eficiencia);
            eficienciaCell.style.background = bgColor;
            eficienciaCell.style.color = textColor;
        }
    } else {
        // Crear nueva fila para esta hora
        const newRow = document.createElement('tr');
        newRow.style.cssText = "background: rgba(255,255,255,0.02); transition: background-color 0.2s ease;";

        const eficiencia = registro.meta_hora > 0 ? (registro.cantidad_producida / registro.meta_hora) * 100 : 0;
        const bgColor = getEficienciaBackgroundColor(eficiencia);
        const textColor = getEficienciaTextColor(eficiencia);

        newRow.innerHTML = `
            <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #ffffff; font-weight: 500;">${horaKey}</td>
            <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">${parseInt(registro.cantidad_producida).toLocaleString()}</td>
            <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">${registro.meta_hora ? parseInt(registro.meta_hora).toLocaleString() : '0'}</td>
            <td style="padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; background: ${bgColor}; color: ${textColor}; font-weight: 600; font-size: 13px;">
                <div style="padding: 14px 20px; width: 100%; height: 100%;">${eficiencia > 0 ? eficiencia.toFixed(1) + '%' : '-'}</div>
            </td>
        `;

        // Insertar antes de la fila TOTAL
        const totalRow = horasTableBody.querySelector('tr:last-child');
        if (totalRow) {
            horasTableBody.insertBefore(newRow, totalRow);
        } else {
            horasTableBody.appendChild(newRow);
        }
    }

    // Actualizar totales
    actualizarTotalesHoras();
}

function actualizarTablaOperarios(registro) {
    const operariosTableBody = document.getElementById('operariosTableBody');
    if (!operariosTableBody) return;

    const operarioKey = registro.operario.name;
    let operarioRow = null;

    // Buscar fila existente para este operario
    const rows = operariosTableBody.querySelectorAll('tr');
    for (let row of rows) {
        const cells = row.querySelectorAll('td');
        if (cells.length > 0 && cells[0].textContent.trim() === operarioKey) {
            operarioRow = row;
            break;
        }
    }

    if (operarioRow) {
        // Actualizar fila existente
        const cells = operarioRow.querySelectorAll('td');
        if (cells.length >= 4) {
            const cantidadCell = cells[1];
            const metaCell = cells[2];
            const eficienciaCell = cells[3];

            // Sumar cantidad producida
            const currentCantidad = parseInt(cantidadCell.textContent.replace(/,/g, '')) || 0;
            const newCantidad = currentCantidad + parseInt(registro.cantidad_producida);
            cantidadCell.textContent = newCantidad.toLocaleString();

            // Calcular nueva eficiencia (mantener meta igual, actualizar cantidad)
            const meta = parseInt(metaCell.textContent.replace(/,/g, '')) || 0;
            const eficiencia = meta > 0 ? (newCantidad / meta) * 100 : 0;

            // Actualizar celda de eficiencia
            eficienciaCell.querySelector('div').textContent = eficiencia > 0 ? eficiencia.toFixed(1) + '%' : '-';

            // Actualizar color de fondo y texto según eficiencia
            const bgColor = getEficienciaBackgroundColor(eficiencia);
            const textColor = getEficienciaTextColor(eficiencia);
            eficienciaCell.style.background = bgColor;
            eficienciaCell.style.color = textColor;
        }
    } else {
        // Crear nueva fila para este operario
        const newRow = document.createElement('tr');
        newRow.style.cssText = "background: rgba(255,255,255,0.02); transition: background-color 0.2s ease;";

        const eficiencia = registro.meta_operario > 0 ? (registro.cantidad_producida / registro.meta_operario) * 100 : 0;
        const bgColor = getEficienciaBackgroundColor(eficiencia);
        const textColor = getEficienciaTextColor(eficiencia);

        newRow.innerHTML = `
            <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #ffffff; font-weight: 500;">${operarioKey}</td>
            <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">${parseInt(registro.cantidad_producida).toLocaleString()}</td>
            <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">${registro.meta_operario ? parseInt(registro.meta_operario).toLocaleString() : '0'}</td>
            <td style="padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; background: ${bgColor}; color: ${textColor}; font-weight: 600; font-size: 13px;">
                <div style="padding: 14px 20px; width: 100%; height: 100%;">${eficiencia > 0 ? eficiencia.toFixed(1) + '%' : '-'}</div>
            </td>
        `;

        // Insertar antes de la fila TOTAL
        const totalRow = operariosTableBody.querySelector('tr:last-child');
        if (totalRow) {
            operariosTableBody.insertBefore(newRow, totalRow);
        } else {
            operariosTableBody.appendChild(newRow);
        }
    }

    // Actualizar totales
    actualizarTotalesOperarios();
}

function actualizarTotalesHoras() {
    const horasTableBody = document.getElementById('horasTableBody');
    if (!horasTableBody) return;

    const rows = horasTableBody.querySelectorAll('tr');
    let totalCantidad = 0;
    let totalMeta = 0;

    // Sumar todas las filas excepto la última (TOTAL)
    for (let i = 0; i < rows.length - 1; i++) {
        const cells = rows[i].querySelectorAll('td');
        if (cells.length >= 3) {
            totalCantidad += parseInt(cells[1].textContent.replace(/,/g, '')) || 0;
            totalMeta += parseInt(cells[2].textContent.replace(/,/g, '')) || 0;
        }
    }

    // Calcular eficiencia total
    const eficienciaTotal = totalMeta > 0 ? (totalCantidad / totalMeta) * 100 : 0;
    const bgColor = getEficienciaBackgroundColor(eficienciaTotal);
    const textColor = getEficienciaTextColor(eficienciaTotal);

    // Actualizar celdas de total
    const totalRow = horasTableBody.querySelector('tr:last-child');
    if (totalRow) {
        const cells = totalRow.querySelectorAll('td');
        if (cells.length >= 4) {
            cells[1].textContent = totalCantidad.toLocaleString();
            cells[2].textContent = totalMeta.toLocaleString();
            // Actualizar eficiencia total
            const eficienciaCell = cells[3];
            eficienciaCell.style.background = bgColor;
            eficienciaCell.style.color = textColor;
            const eficienciaDiv = eficienciaCell.querySelector('div');
            if (eficienciaDiv) {
                eficienciaDiv.textContent = eficienciaTotal.toFixed(1) + '%';
            }
        }
    }
}

function actualizarTotalesOperarios() {
    const operariosTableBody = document.getElementById('operariosTableBody');
    if (!operariosTableBody) return;

    const rows = operariosTableBody.querySelectorAll('tr');
    let totalCantidad = 0;
    let totalMeta = 0;

    // Sumar todas las filas excepto la última (TOTAL)
    for (let i = 0; i < rows.length - 1; i++) {
        const cells = rows[i].querySelectorAll('td');
        if (cells.length >= 3) {
            totalCantidad += parseInt(cells[1].textContent.replace(/,/g, '')) || 0;
            totalMeta += parseInt(cells[2].textContent.replace(/,/g, '')) || 0;
        }
    }

    // Calcular eficiencia total
    const eficienciaTotal = totalMeta > 0 ? (totalCantidad / totalMeta) * 100 : 0;
    const bgColor = getEficienciaBackgroundColor(eficienciaTotal);
    const textColor = getEficienciaTextColor(eficienciaTotal);

    // Actualizar celdas de total
    const totalRow = operariosTableBody.querySelector('tr:last-child');
    if (totalRow) {
        const cells = totalRow.querySelectorAll('td');
        if (cells.length >= 4) {
            cells[1].textContent = totalCantidad.toLocaleString();
            cells[2].textContent = totalMeta.toLocaleString();
            // Actualizar eficiencia total
            const eficienciaCell = cells[3];
            eficienciaCell.style.background = bgColor;
            eficienciaCell.style.color = textColor;
            const eficienciaDiv = eficienciaCell.querySelector('div');
            if (eficienciaDiv) {
                eficienciaDiv.textContent = eficienciaTotal.toFixed(1) + '%';
            }
        }
    }
}

function getEficienciaBackgroundColor(eficiencia) {
    if (eficiencia >= 80) return '#3b82f6';
    if (eficiencia >= 70) return '#eab308';
    return '#ef4444';
}

function getEficienciaTextColor(eficiencia) {
    if (eficiencia >= 70 && eficiencia < 80) return '#000000';
    return '#ffffff';
}
</script>
