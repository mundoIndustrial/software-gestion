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
// Función para recargar las tablas del dashboard
function recargarDashboardCorte() {
    console.log('🔄 Recargando dashboard de Corte...');
    
    // Obtener los filtros activos desde la URL actual
    const currentUrl = new URL(window.location.href);
    const params = new URLSearchParams();
    
    // Capturar specific_date de la URL (tiene prioridad)
    const specificDate = currentUrl.searchParams.get('specific_date');
    if (specificDate) {
        params.set('fecha', specificDate);
        console.log('📅 Filtro desde URL (specific_date):', specificDate);
    } else {
        // Capturar fecha (si existe en input)
        const fechaInput = document.querySelector('input[name="fecha"]');
        if (fechaInput && fechaInput.value) {
            params.set('fecha', fechaInput.value);
            console.log('📅 Filtro fecha desde input:', fechaInput.value);
        }
        
        // Capturar fecha_inicio (si existe)
        const fechaInicioInput = document.querySelector('input[name="fecha_inicio"]');
        if (fechaInicioInput && fechaInicioInput.value) {
            params.set('fecha_inicio', fechaInicioInput.value);
            console.log('📅 Filtro fecha_inicio:', fechaInicioInput.value);
        }
        
        // Capturar fecha_fin (si existe)
        const fechaFinInput = document.querySelector('input[name="fecha_fin"]');
        if (fechaFinInput && fechaFinInput.value) {
            params.set('fecha_fin', fechaFinInput.value);
            console.log('📅 Filtro fecha_fin:', fechaFinInput.value);
        }
        
        // Si no hay filtros, usar fecha actual
        if (!params.has('fecha') && !params.has('fecha_inicio')) {
            params.set('fecha', new Date().toISOString().split('T')[0]);
            console.log('📅 Sin filtros, usando fecha actual');
        }
    }
    
    const url = `/tableros/corte/dashboard?${params.toString()}`;
    console.log('🌐 URL de recarga:', url);
    
    // Hacer petición AJAX para obtener datos actualizados
    fetch(`/tableros/corte/dashboard?fecha=${fecha}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Datos del dashboard recibidos:', data);
        
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
        console.error('Error al recargar dashboard:', error);
    });
}

// Función para actualizar la tabla de horas completa
function actualizarTablaHorasCompleta(horas) {
    const tbody = document.getElementById('horasTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    horas.forEach(hora => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${hora.hora}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${hora.cantidad}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${Math.round(hora.meta * 100) / 100}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getEficienciaClass(hora.eficiencia)}">
                    ${hora.eficiencia}%
                </span>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Función para actualizar la tabla de operarios completa
function actualizarTablaOperariosCompleta(operarios) {
    const tbody = document.getElementById('operariosTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    operarios.forEach(operario => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${operario.operario}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${operario.cantidad}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${Math.round(operario.meta * 100) / 100}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getEficienciaClass(operario.eficiencia)}">
                    ${operario.eficiencia}%
                </span>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function getEficienciaClass(eficiencia) {
    const eficienciaNum = parseFloat(eficiencia);
    if (eficienciaNum >= 100) return 'bg-green-100 text-green-800';
    if (eficienciaNum >= 80) return 'bg-yellow-100 text-yellow-800';
    return 'bg-red-100 text-red-800';
}

// Listen for real-time updates with detailed debugging
// Esperar a que Echo esté disponible (se inicializa en bootstrap.js)
function initializeCorteChannel() {
    console.log('=== DASHBOARD CORTE - Inicializando Echo ===');
    console.log('window.Echo disponible:', !!window.Echo);

    if (window.Echo) {
        console.log('Suscribiéndose al canal "corte"...');
        
        const channel = window.Echo.channel('corte');
        
        channel.subscribed(() => {
            console.log('✅ Suscrito exitosamente al canal "corte"');
        });
        
        channel.error((error) => {
            console.error('❌ Error en el canal "corte":', error);
        });
        
        channel.listen('CorteRecordCreated', (e) => {
            console.log('🎉 Evento CorteRecordCreated recibido!');
            console.log('Datos del evento:', e);
            console.log('Registro:', e.registro);
            
            // Recargar las tablas del dashboard (tanto para crear/actualizar como para eliminar)
            console.log('Recargando tablas del dashboard...');
            recargarDashboardCorte();
        });
        
        // Listener para TODOS los eventos (debugging)
        channel.listen('.App\\Events\\CorteRecordCreated', (e) => {
            console.log('🔔 Evento recibido con nombre completo:', e);
        });
        
        console.log('Listeners configurados. Esperando eventos...');
    } else {
        console.error('❌ Echo NO está disponible todavía. Reintentando en 500ms...');
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

// Escuchar evento de eliminación para recargar el dashboard
window.addEventListener('registro-eliminado', (e) => {
    if (e.detail.section === 'corte') {
        console.log('Registro eliminado, recargando dashboard...');
        recargarDashboardCorte();
    }
});

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
