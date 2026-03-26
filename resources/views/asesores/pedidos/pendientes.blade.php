@extends('layouts.asesores')

@section('title', 'Pedidos Pendientes')
@section('page-title', 'Mis Pedidos Pendientes')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/asesores/pedidos/index.css') }}">
    <style>
        .pendientes-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
        }

        .filtros-bar {
            display: flex;
            gap: 12px;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            background: white;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            opacity: 0.5;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .pendientes-table {
            width: 100%;
            border-collapse: collapse;
        }

        .pendientes-table thead {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 2px solid #e2e8f0;
        }

        .pendientes-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pendientes-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .pendientes-table tbody tr:hover {
            background: #f8fafc;
        }

        .pendientes-table tbody tr:last-child {
            border-bottom: none;
        }

        .pendientes-table td {
            padding: 16px;
            font-size: 14px;
            color: #334155;
        }

        .pedido-numero {
            font-weight: 600;
            color: #1e293b;
        }

        .pedido-tipo {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .tipo-costura {
            background: #dbeafe;
            color: #1e40af;
        }

        .tipo-epp {
            background: #fef3c7;
            color: #92400e;
        }

        .tipo-mixto {
            background: #e9d5ff;
            color: #6b21a8;
        }

        .pendientes-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            padding: 4px 8px;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
        }

        .btn-ver {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-ver:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }

        .loading-container {
            text-align: center;
            padding: 60px 20px;
        }

        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #e5e7eb;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            border: 2px dashed #e5e7eb;
        }

        .empty-state .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            color: #9ca3af;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }

        .pagination-btn {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            color: #64748b;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .pagination-btn:hover:not(:disabled) {
            border-color: #3b82f6;
            color: #3b82f6;
        }

        .pagination-btn.active {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
@endsection

@section('content')
    <div class="pendientes-container">
        {{-- Barra de filtros --}}
        <div class="filtros-bar">
            <div class="search-box">
                <input type="text" 
                       id="searchInput" 
                       placeholder="Buscar por número de pedido, cliente o prenda..."
                       value="{{ $search }}">
            </div>
        </div>

        {{-- Grid de pedidos --}}
        <div id="pedidosContainer">
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <p style="color: #6b7280; font-size: 14px;">Cargando pendientes...</p>
            </div>
        </div>

        {{-- Paginación --}}
        <div id="paginationContainer"></div>
    </div>
@endsection

@push('scripts')
    <script>
        console.log('🔵 Script de pendientes iniciando...');
        
        let currentPage = 1;
        let searchTimeout = null;

        console.log('🔵 Variables inicializadas');

        document.addEventListener('DOMContentLoaded', function() {
            console.log('🟢 DOMContentLoaded disparado');
            console.log('🟢 Vista cargada, iniciando carga de pendientes...');
            cargarPendientes();
            
            // Búsqueda con debounce
            document.getElementById('searchInput').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage = 1;
                    cargarPendientes();
                }, 500);
            });
        });

        function cargarPendientes() {
            const search = document.getElementById('searchInput').value;
            const container = document.getElementById('pedidosContainer');
            
            console.log('Cargando pendientes...', {
                page: currentPage,
                search: search
            });
            
            container.innerHTML = `
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <p style="color: #6b7280; font-size: 14px;">Cargando pendientes...</p>
                </div>
            `;

            const params = new URLSearchParams({
                page: currentPage,
                tipo: 'todos',
                search: search,
                per_page: 20
            });

            const url = `/asesores/api/pendientes-asesor?${params}`;
            console.log('Llamando a:', url);

            fetch(url)
                .then(response => {
                    console.log('Respuesta recibida:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Datos recibidos:', data);
                    if (data.success) {
                        mostrarPedidos(data.data);
                        mostrarPaginacion(data.meta);
                    } else {
                        mostrarError(data.message || 'Error al cargar pendientes');
                    }
                })
                .catch(error => {
                    console.error('Error en fetch:', error);
                    mostrarError('Error al cargar los datos: ' + error.message);
                });
        }

        function mostrarPedidos(pedidos) {
            const container = document.getElementById('pedidosContainer');
            
            if (pedidos.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <h3>No hay pedidos pendientes</h3>
                        <p>No se encontraron pedidos que coincidan con tus filtros</p>
                    </div>
                `;
                return;
            }

            const html = `
                <div class="table-container">
                    <table class="pendientes-table">
                        <thead>
                            <tr>
                                <th>N° Pedido</th>
                                <th>Cliente</th>
                                <th>Tipo</th>
                                <th>Fecha Pedido</th>
                                <th>Pendientes</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pedidos.map(pedido => crearPedidoFila(pedido)).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            
            container.innerHTML = html;
        }

        function crearPedidoFila(pedido) {
            const tipoClass = pedido.areas.length > 1 ? 'tipo-mixto' : 
                            pedido.areas.includes('Costura') ? 'tipo-costura' : 'tipo-epp';
            
            return `
                <tr>
                    <td>
                        <span class="pedido-numero">#${pedido.numero_pedido}</span>
                    </td>
                    <td>${pedido.cliente}</td>
                    <td>
                        <span class="pedido-tipo ${tipoClass}">
                            ${pedido.tipo}
                        </span>
                    </td>
                    <td>${pedido.fecha_creacion || '-'}</td>
                    <td>
                        <span class="pendientes-badge">${pedido.total_pendientes}</span>
                    </td>
                    <td>
                        <button class="btn-ver" onclick="verDetallePedido(${pedido.id})">
                            👁️ Ver
                        </button>
                    </td>
                </tr>
            `;
        }

        function mostrarPaginacion(meta) {
            const container = document.getElementById('paginationContainer');
            
            if (meta.last_page <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '<div class="pagination">';
            
            // Botón anterior
            html += `
                <button class="pagination-btn" 
                        ${meta.current_page === 1 ? 'disabled' : ''} 
                        onclick="cambiarPagina(${meta.current_page - 1})">
                    ← Anterior
                </button>
            `;
            
            // Números de página
            for (let i = 1; i <= meta.last_page; i++) {
                if (i === 1 || i === meta.last_page || (i >= meta.current_page - 2 && i <= meta.current_page + 2)) {
                    html += `
                        <button class="pagination-btn ${i === meta.current_page ? 'active' : ''}" 
                                onclick="cambiarPagina(${i})">
                            ${i}
                        </button>
                    `;
                } else if (i === meta.current_page - 3 || i === meta.current_page + 3) {
                    html += '<span style="padding: 8px;">...</span>';
                }
            }
            
            // Botón siguiente
            html += `
                <button class="pagination-btn" 
                        ${meta.current_page === meta.last_page ? 'disabled' : ''} 
                        onclick="cambiarPagina(${meta.current_page + 1})">
                    Siguiente →
                </button>
            `;
            
            html += '</div>';
            container.innerHTML = html;
        }

        function cambiarPagina(page) {
            currentPage = page;
            cargarPendientes();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function verDetallePedido(pedidoId) {
            window.location.href = `/asesores/pendientes/${pedidoId}`;
        }

        function mostrarError(mensaje) {
            const container = document.getElementById('pedidosContainer');
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon"></div>
                    <h3>Error al cargar pendientes</h3>
                    <p>${mensaje}</p>
                </div>
            `;
        }
    </script>
@endpush
