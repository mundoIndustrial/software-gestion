@php
    // Los datos dinámicos de horas y operarios se pasan desde el controlador
    $totalCantidadHoras = array_sum(array_column($horasData, 'cantidad'));
    $totalMetaHoras = array_sum(array_column($horasData, 'meta'));

    $totalCantidadOperarios = array_sum(array_column($operariosData, 'cantidad'));
    $totalMetaOperarios = array_sum(array_column($operariosData, 'meta'));
@endphp

<div class="records-table-container">
    <div class="table-scroll-container">
        <div style="display: flex; gap: 24px; padding: 24px; background: rgba(255, 255, 255, 0.03); border-radius: 16px; box-shadow: 0 8px 20px rgba(0,0,0,0.3); border: 1px solid rgba(255, 107, 53, 0.15);" id="dashboard-tables-corte">
            <!-- Tabla de Horas -->
            <div style="flex: 1; background: rgba(26,29,41,0.8); border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); border: 1px solid rgba(255,107,53,0.1);">
                <h3 style="color: #ffffff; font-size: 20px; font-weight: 700; margin-bottom: 20px; text-align: center;">Producción por Horas</h3>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0; font-size: 14px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
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
                            <tr style="background: rgba(255,255,255,0.02); transition: background-color 0.2s ease;">
                                <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #ffffff; font-weight: 500;">{{ $row['hora'] }}</td>
                                <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">{{ number_format($row['cantidad']) }}</td>
                                <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">{{ number_format($row['meta']) }}</td>
                                <td style="padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; background: {{ $row['eficiencia'] < 70 ? '#7f1d1d' : ($row['eficiencia'] >= 70 && $row['eficiencia'] < 80 ? '#92400e' : ($row['eficiencia'] >= 80 && $row['eficiencia'] < 100 ? '#166534' : ($row['eficiencia'] >= 100 ? '#0c4a6e' : '#374151'))) }}; color: #ffffff; font-weight: 600; font-size: 13px;">
                                    <div style="padding: 14px 20px; width: 100%; height: 100%;">{{ $row['eficiencia'] > 0 ? number_format($row['eficiencia'], 1) . '%' : '-' }}</div>
                                </td>
                            </tr>
                            @endforeach
                            <tr style="background: linear-gradient(135deg, #1f2937, #374151); font-weight: 600; border-radius: 0 0 8px 8px;">
                                <td style="padding: 16px 20px; border-bottom: none; color: #ffffff; border-radius: 0 0 0 8px;">TOTAL</td>
                                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;" id="totalCantidadHoras">{{ number_format($totalCantidadHoras) }}</td>
                                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;" id="totalMetaHoras">{{ number_format($totalMetaHoras) }}</td>
                                <td style="padding: 16px 20px; border-bottom: none; border-radius: 0 0 8px 0;"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla de Operarios -->
            <div style="flex: 1; background: rgba(26,29,41,0.8); border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); border: 1px solid rgba(255,107,53,0.1);">
                <h3 style="color: #ffffff; font-size: 20px; font-weight: 700; margin-bottom: 20px; text-align: center;">Producción por Operarios</h3>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0; font-size: 14px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
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
                            <tr style="background: rgba(255,255,255,0.02); transition: background-color 0.2s ease;">
                                <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #ffffff; font-weight: 500;">{{ $row['operario'] }}</td>
                                <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">{{ number_format($row['cantidad']) }}</td>
                                <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">{{ number_format($row['meta']) }}</td>
                                <td style="padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; background: {{ $row['eficiencia'] < 70 ? '#7f1d1d' : ($row['eficiencia'] >= 70 && $row['eficiencia'] < 80 ? '#92400e' : ($row['eficiencia'] >= 80 && $row['eficiencia'] < 100 ? '#166534' : ($row['eficiencia'] >= 100 ? '#0c4a6e' : '#374151'))) }}; color: #ffffff; font-weight: 600; font-size: 13px;">
                                    <div style="padding: 14px 20px; width: 100%; height: 100%;">{{ $row['eficiencia'] > 0 ? number_format($row['eficiencia'], 1) . '%' : '-' }}</div>
                                </td>
                            </tr>
                            @endforeach
                            <tr style="background: linear-gradient(135deg, #1f2937, #374151); font-weight: 600; border-radius: 0 0 8px 8px;">
                                <td style="padding: 16px 20px; border-bottom: none; color: #ffffff; border-radius: 0 0 0 8px;">TOTAL</td>
                                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;" id="totalCantidadOperarios">{{ number_format($totalCantidadOperarios) }}</td>
                                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;" id="totalMetaOperarios">{{ number_format($totalMetaOperarios) }}</td>
                                <td style="padding: 16px 20px; border-bottom: none; border-radius: 0 0 8px 0;"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Función para actualizar las tablas del dashboard en tiempo real
window.actualizarTablaCorte = function(registro) {
    console.log('Actualizando tabla de corte con registro:', registro);

    // Actualizar tabla de horas
    if (registro.hora && registro.cantidad_producida) {
        actualizarTablaHoras(registro);
    }

    // Actualizar tabla de operarios
    if (registro.operario && registro.cantidad_producida) {
        actualizarTablaOperarios(registro);
    }
};

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
            
            // Prepare registro data for actualizarTablaCorte
            const registro = {
                hora: { hora: e.registro.hora.hora },
                cantidad_producida: e.registro.cantidad,
                operario: { name: e.registro.operario.name },
                meta_hora: e.registro.meta,
                meta_operario: e.registro.meta
            };
            
            console.log('Datos preparados para actualizar:', registro);
            window.actualizarTablaCorte(registro);
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

function actualizarTablaHoras(registro) {
    const horasTableBody = document.getElementById('horasTableBody');
    if (!horasTableBody) return;

    const horaKey = registro.hora.hora;
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

            // Actualizar color de fondo según eficiencia
            const bgColor = getEficienciaBackgroundColor(eficiencia);
            eficienciaCell.style.background = bgColor;
        }
    } else {
        // Crear nueva fila para esta hora
        const newRow = document.createElement('tr');
        newRow.style.cssText = "background: rgba(255,255,255,0.02); transition: background-color 0.2s ease;";

        const eficiencia = registro.meta_hora > 0 ? (registro.cantidad_producida / registro.meta_hora) * 100 : 0;
        const bgColor = getEficienciaBackgroundColor(eficiencia);

        newRow.innerHTML = `
            <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #ffffff; font-weight: 500;">${horaKey}</td>
            <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">${parseInt(registro.cantidad_producida).toLocaleString()}</td>
            <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">${registro.meta_hora ? parseInt(registro.meta_hora).toLocaleString() : '0'}</td>
            <td style="padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; background: ${bgColor}; color: #ffffff; font-weight: 600; font-size: 13px;">
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

            // Actualizar color de fondo según eficiencia
            const bgColor = getEficienciaBackgroundColor(eficiencia);
            eficienciaCell.style.background = bgColor;
        }
    } else {
        // Crear nueva fila para este operario
        const newRow = document.createElement('tr');
        newRow.style.cssText = "background: rgba(255,255,255,0.02); transition: background-color 0.2s ease;";

        const eficiencia = registro.meta_operario > 0 ? (registro.cantidad_producida / registro.meta_operario) * 100 : 0;
        const bgColor = getEficienciaBackgroundColor(eficiencia);

        newRow.innerHTML = `
            <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #ffffff; font-weight: 500;">${operarioKey}</td>
            <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">${parseInt(registro.cantidad_producida).toLocaleString()}</td>
            <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">${registro.meta_operario ? parseInt(registro.meta_operario).toLocaleString() : '0'}</td>
            <td style="padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; background: ${bgColor}; color: #ffffff; font-weight: 600; font-size: 13px;">
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

    // Actualizar celdas de total
    const totalRow = horasTableBody.querySelector('tr:last-child');
    if (totalRow) {
        const cells = totalRow.querySelectorAll('td');
        if (cells.length >= 3) {
            cells[1].textContent = totalCantidad.toLocaleString();
            cells[2].textContent = totalMeta.toLocaleString();
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

    // Actualizar celdas de total
    const totalRow = operariosTableBody.querySelector('tr:last-child');
    if (totalRow) {
        const cells = totalRow.querySelectorAll('td');
        if (cells.length >= 3) {
            cells[1].textContent = totalCantidad.toLocaleString();
            cells[2].textContent = totalMeta.toLocaleString();
        }
    }
}

function getEficienciaBackgroundColor(eficiencia) {
    if (eficiencia < 70) return '#7f1d1d';
    if (eficiencia >= 70 && eficiencia < 80) return '#92400e';
    if (eficiencia >= 80 && eficiencia < 100) return '#166534';
    if (eficiencia >= 100) return '#0c4a6e';
    return '#374151';
}
</script>
