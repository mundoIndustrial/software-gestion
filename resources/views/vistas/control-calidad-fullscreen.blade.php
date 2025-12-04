<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Calidad - Pantalla Completa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            padding: 0;
            overflow-x: hidden;
            zoom: 0.75;
        }

        .fullscreen-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 26px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1 i {
            color: #ff6b35;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-box {
            position: relative;
            width: 350px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 40px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #ff6b35;
        }

        .search-box i.fa-search {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .close-fullscreen-btn {
            background: #ff6b35;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .close-fullscreen-btn:hover {
            background: #e55a28;
            transform: translateY(-2px);
        }

        .tables-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .table-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-card-header {
            background: #1e293b;
            color: white;
            padding: 12px 20px;
            font-size: 17px;
            font-weight: 600;
            border-bottom: 3px solid #ff6b35;
        }

        .table-scroll {
            max-height: calc(100vh - 20px);
            overflow-y: auto;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: #1e293b;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table thead::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #ff6b35;
            z-index: 11;
        }

        .data-table th {
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            color: #ffffff;
            white-space: nowrap;
            background: #1e293b;
            font-size: 13px;
        }

        .data-table tbody tr {
            border-bottom: 1px solid #cbd5e1;
            transition: background-color 0.2s;
        }

        .data-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .data-table td {
            padding: 8px 12px;
            color: #475569;
            font-size: 18px;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
            position: relative;
            line-height: 1.4;
        }

        .data-table td.expanded {
            white-space: normal;
            max-width: none;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .data-table td.full-text {
            white-space: normal;
            max-width: none;
            -webkit-line-clamp: unset;
        }

        .data-table tfoot {
            background: #1e293b;
            position: sticky;
            bottom: 0;
            z-index: 10;
        }

        .data-table tfoot::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #ff6b35;
            z-index: 11;
        }

        .data-table tfoot td {
            padding: 10px 12px;
            font-weight: 600;
            color: #ffffff;
            background: #1e293b;
            font-size: 13px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-entregado {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-en-ejecución {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-pendiente {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-cancelado {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-programado {
            background-color: #e0e7ff;
            color: #3730a3;
        }

        .badge-default {
            background-color: #f3f4f6;
            color: #6b7280;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 36px;
            margin-bottom: 10px;
            display: block;
            opacity: 0.5;
        }

        /* Tooltip para texto completo */
        .data-table td:hover::after {
            content: attr(title);
            position: absolute;
            left: 0;
            top: 100%;
            background: #1e293b;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: normal;
            max-width: 300px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            margin-top: 5px;
            display: none;
        }

        .data-table td[title]:hover::after {
            display: block;
        }

        /* Scrollbar personalizado */
        .table-scroll::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .table-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .table-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Animaciones para tiempo real */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideOutUp {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-20px);
            }
        }

        .new-row-animation {
            animation: slideInDown 0.5s ease-out;
            background-color: #dbeafe !important;
        }

        .remove-row-animation {
            animation: slideOutUp 0.3s ease-out;
        }
    </style>
</head>
<body>
    <div class="fullscreen-container">
        <div class="header">
            <h1>
                <i class="fas fa-clipboard-check"></i>
                Control de Calidad - Vista Completa
            </h1>
            <div class="header-actions">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Buscar por pedido o cliente..." value="{{ $query ?? '' }}">
                </div>
                <button class="close-fullscreen-btn" onclick="closeFullscreen()">
                    <i class="fas fa-times"></i>
                    Cerrar
                </button>
            </div>
        </div>

        <div class="tables-grid">
            <!-- Tabla de Pedidos -->
            <div class="table-card">
                <div class="table-card-header">
                    <i class="fas fa-box"></i> Órdenes de Pedidos
                </div>
                <div class="table-scroll">
                    <table class="data-table" id="tablaPedidos">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Novedad</th>
                                <th>Fecha Ingreso</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php
                            $ordenesPedido = $ordenes->filter(function($orden) {
                                return $orden->getTable() === 'tabla_original';
                            });
                        @endphp
                        @forelse($ordenesPedido as $orden)
                            <tr data-pedido="{{ $orden->pedido }}">
                                <td>
                                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $orden->estado ?? 'default')) }}">
                                        {{ $orden->estado ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    @if($orden->fecha_de_creacion_de_orden)
                                        {{ \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                            <tr>
                                <td title="{{ $orden->numero_orden ?? '-' }}">{{ $orden->numero_orden ?? '-' }}</td>
                                <td title="{{ $orden->cliente ?? '-' }}">{{ $orden->cliente ?? '-' }}</td>
                                <td title="{{ $orden->novedades ?? '-' }}">{{ $orden->novedades ?? '-' }}</td>
                                <td>
                                    @if($orden->control_de_calidad)
                                        {{ \Carbon\Carbon::parse($orden->control_de_calidad)->format('d/m/Y h:i A') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-clipboard-check"></i>
                                    <p>No hay órdenes de pedidos en Control de Calidad</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6">Total de órdenes: {{ $ordenesPedido->count() }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Tabla de Bodega -->
            <div class="table-card">
                <div class="table-card-header">
                    <i class="fas fa-warehouse"></i> Órdenes de Bodega
                </div>
                <div class="table-scroll">
                    <table class="data-table" id="tablaBodega">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Novedad</th>
                                <th>Fecha Ingreso</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php
                            $ordenesBodega = $ordenes->filter(function($orden) {
                                return $orden->getTable() === 'tabla_original_bodega';
                            });
                        @endphp
                        @forelse($ordenesBodega as $orden)
                            <tr data-pedido="{{ $orden->pedido }}">
                                <td>
                                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $orden->estado ?? 'default')) }}">
                                        {{ $orden->estado ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    @if($orden->fecha_de_creacion_de_orden)
                                        {{ \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td title="{{ $orden->pedido ?? '-' }}">{{ $orden->pedido ?? '-' }}</td>
                                <td title="{{ $orden->cliente ?? '-' }}">{{ $orden->cliente ?? '-' }}</td>
                                <td title="{{ $orden->novedades ?? '-' }}">{{ $orden->novedades ?? '-' }}</td>
                                <td>
                                    @if($orden->control_de_calidad)
                                        {{ \Carbon\Carbon::parse($orden->control_de_calidad)->format('d/m/Y h:i A') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-clipboard-check"></i>
                                    <p>No hay órdenes de bodega en Control de Calidad</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6">Total de órdenes: {{ $ordenesBodega->count() }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/app.js'])
    <script>
        console.log('✅ Echo cargado desde Vite:', typeof window.Echo);

        document.addEventListener('DOMContentLoaded', function() {
            function closeFullscreen() {
                const currentParams = new URLSearchParams(window.location.search);
                const url = `/vistas/control-calidad?${currentParams.toString()}`;
                window.location.href = url;
            }
            
            // Hacer closeFullscreen global
            window.closeFullscreen = closeFullscreen;

            // Búsqueda
            const searchInput = document.getElementById('searchInput');
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                const query = this.value;
                clearTimeout(searchTimeout);
                
                searchTimeout = setTimeout(() => {
                    const url = new URL(window.location.href);
                    if (query) {
                        url.searchParams.set('search', query);
                    } else {
                        url.searchParams.delete('search');
                    }
                    window.location.href = url.toString();
                }, 500);
            });

            // Doble click para expandir/contraer texto
            document.querySelectorAll('.data-table td').forEach(cell => {
                cell.addEventListener('dblclick', function() {
                    if (this.classList.contains('full-text')) {
                        this.classList.remove('full-text');
                        this.classList.remove('expanded');
                    } else if (this.classList.contains('expanded')) {
                        this.classList.add('full-text');
                    } else {
                        this.classList.add('expanded');
                    }
                });
            });

            // Funciones para tiempo real
            function agregarOrden(orden, tipo) {
            const tbody = tipo === 'pedido' 
                ? document.querySelector('#tablaPedidos tbody')
                : document.querySelector('#tablaBodega tbody');
            
            if (!tbody) return;

            // Verificar si ya existe
            const existingRow = tbody.querySelector(`tr[data-pedido="${orden.pedido}"]`);
            if (existingRow) return;

            // Crear nueva fila
            const tr = document.createElement('tr');
            tr.setAttribute('data-pedido', orden.pedido);
            tr.classList.add('new-row-animation');
            
            const estadoBadge = orden.estado ? orden.estado.toLowerCase().replace(/ /g, '-') : 'default';
            const fechaCreacion = orden.fecha_de_creacion_de_orden 
                ? new Date(orden.fecha_de_creacion_de_orden).toLocaleDateString('es-ES')
                : '-';
            const fechaControlCalidad = orden.control_de_calidad
                ? new Date(orden.control_de_calidad).toLocaleString('es-ES')
                : '-';

            tr.innerHTML = `
                <td>
                    <span class="badge badge-${estadoBadge}">
                        ${orden.estado || '-'}
                    </span>
                </td>
                <td>${fechaCreacion}</td>
                <td title="${orden.pedido || '-'}">${orden.pedido || '-'}</td>
                <td title="${orden.cliente || '-'}">${orden.cliente || '-'}</td>
                <td title="${orden.novedades || '-'}">${orden.novedades || '-'}</td>
                <td>${fechaControlCalidad}</td>
            `;

            // Insertar al inicio de la tabla
            const firstRow = tbody.querySelector('tr:not(.empty-state)');
            if (firstRow) {
                tbody.insertBefore(tr, firstRow);
            } else {
                // Si solo hay mensaje de vacío, reemplazarlo
                const emptyRow = tbody.querySelector('tr.empty-state');
                if (emptyRow) {
                    emptyRow.remove();
                }
                tbody.appendChild(tr);
            }

            // Actualizar contador del footer
            actualizarContador(tipo);

            // Remover animación después de 2 segundos
            setTimeout(() => {
                tr.classList.remove('new-row-animation');
            }, 2000);

            // Agregar evento de doble click a las nuevas celdas
            tr.querySelectorAll('td').forEach(cell => {
                cell.addEventListener('dblclick', function() {
                    if (this.classList.contains('full-text')) {
                        this.classList.remove('full-text');
                        this.classList.remove('expanded');
                    } else if (this.classList.contains('expanded')) {
                        this.classList.add('full-text');
                    } else {
                        this.classList.add('expanded');
                    }
                });
            });
            }

            function removerOrden(pedido, tipo) {
            const tbody = tipo === 'pedido'
                ? document.querySelector('#tablaPedidos tbody')
                : document.querySelector('#tablaBodega tbody');
            
            if (!tbody) return;

            const row = tbody.querySelector(`tr[data-pedido="${pedido}"]`);
            if (row) {
                row.classList.add('remove-row-animation');
                setTimeout(() => {
                    row.remove();
                    
                    // Si no quedan filas, mostrar mensaje vacío
                    const remainingRows = tbody.querySelectorAll('tr:not(.empty-state)');
                    if (remainingRows.length === 0) {
                        const emptyRow = document.createElement('tr');
                        emptyRow.classList.add('empty-state');
                        emptyRow.innerHTML = `
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-clipboard-check"></i>
                                <p>No hay órdenes de ${tipo === 'pedido' ? 'pedidos' : 'bodega'} en Control de Calidad</p>
                            </td>
                        `;
                        tbody.appendChild(emptyRow);
                    }
                    
                    // Actualizar contador del footer
                    actualizarContador(tipo);
                }, 300);
                }
            }

            function actualizarContador(tipo) {
            const tbody = tipo === 'pedido'
                ? document.querySelector('#tablaPedidos tbody')
                : document.querySelector('#tablaBodega tbody');
            
            const tfoot = tipo === 'pedido'
                ? document.querySelector('#tablaPedidos tfoot td')
                : document.querySelector('#tablaBodega tfoot td');
            
            if (tbody && tfoot) {
                const count = tbody.querySelectorAll('tr:not(.empty-state)').length;
                tfoot.textContent = `Total de órdenes: ${count}`;
                }
            }

            // Tiempo real
            if (typeof window.Echo !== 'undefined') {
                window.Echo.channel('control-calidad')
                    .listen('ControlCalidadUpdated', (e) => {
                        console.log('Control de Calidad actualizado:', e);
                        
                        if (e.action === 'added') {
                            // Agregar nueva orden a la tabla correspondiente
                            agregarOrden(e.orden, e.tipo);
                        } else if (e.action === 'removed') {
                            // Remover orden de la tabla
                            removerOrden(e.orden.pedido, e.tipo);
                        }
                    });
            }
        });
    </script>
</body>
</html>
