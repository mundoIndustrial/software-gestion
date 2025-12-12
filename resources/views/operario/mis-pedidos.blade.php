@extends('operario.layout')

@section('title', 'Mis Pedidos')
@section('page-title', 'Mis Pedidos - ' . $operario->areaOperario)

@section('content')
<div class="mis-pedidos-container">
    <!-- Filtros -->
    <div class="filtros-section">
        <div class="filtro-grupo">
            <label for="filtroEstado">Estado:</label>
            <select id="filtroEstado" class="filtro-select">
                <option value="">Todos</option>
                <option value="En Ejecución">En Ejecución</option>
                <option value="Completada">Completada</option>
                <option value="Pendiente">Pendiente</option>
            </select>
        </div>

        <div class="filtro-grupo">
            <label for="filtroOrdenamiento">Ordenar por:</label>
            <select id="filtroOrdenamiento" class="filtro-select">
                <option value="reciente">Más Reciente</option>
                <option value="antiguo">Más Antiguo</option>
                <option value="cliente">Cliente (A-Z)</option>
            </select>
        </div>
    </div>

    <!-- Tabla de Pedidos -->
    <div class="tabla-pedidos">
        <table class="pedidos-table">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Cantidad</th>
                    <th>Estado</th>
                    <th>Entrega</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="pedidosTableBody">
                @forelse($operario->pedidos as $pedido)
                    <tr class="pedido-row" data-numero="{{ $pedido['numero_pedido'] }}" data-estado="{{ $pedido['estado'] }}">
                        <td class="numero-pedido">
                            <strong>#{{ $pedido['numero_pedido'] }}</strong>
                        </td>
                        <td class="cliente">{{ $pedido['cliente'] }}</td>
                        <td class="fecha">{{ $pedido['fecha_creacion'] }}</td>
                        <td class="cantidad">{{ $pedido['cantidad'] }} unidades</td>
                        <td class="estado">
                            <span class="estado-badge {{ strtolower(str_replace(' ', '-', $pedido['estado'])) }}">
                                {{ $pedido['estado'] }}
                            </span>
                        </td>
                        <td class="entrega">{{ $pedido['fecha_estimada'] ?? '-' }}</td>
                        <td class="acciones">
                            <a href="{{ route('operario.ver-pedido', $pedido['numero_pedido']) }}" class="btn-accion ver">
                                <span class="material-symbols-rounded">visibility</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="7" class="empty-message">
                            <span class="material-symbols-rounded">inbox</span>
                            <p>No hay pedidos asignados</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
    .mis-pedidos-container {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Filtros */
    .filtros-section {
        display: flex;
        gap: 2rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .filtro-grupo {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .filtro-grupo label {
        font-weight: 600;
        color: #333;
        font-size: 0.9rem;
    }

    .filtro-select {
        padding: 0.75rem 1rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.9rem;
        background: white;
        cursor: pointer;
        min-width: 150px;
    }

    .filtro-select:focus {
        outline: none;
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
    }

    /* Tabla */
    .tabla-pedidos {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .pedidos-table {
        width: 100%;
        border-collapse: collapse;
    }

    .pedidos-table thead {
        background: #f5f5f5;
        border-bottom: 2px solid #ddd;
    }

    .pedidos-table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #333;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .pedidos-table tbody tr {
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s ease;
    }

    .pedidos-table tbody tr:hover {
        background-color: #f9f9f9;
    }

    .pedidos-table td {
        padding: 1rem;
        color: #555;
        font-size: 0.9rem;
    }

    .numero-pedido {
        color: #1976d2;
        font-weight: 600;
    }

    .cliente {
        font-weight: 500;
    }

    .cantidad {
        text-align: center;
    }

    .estado-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .estado-badge.en-ejecución {
        background: #fff3e0;
        color: #f57c00;
    }

    .estado-badge.completada {
        background: #e8f5e9;
        color: #388e3c;
    }

    .estado-badge.pendiente {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .acciones {
        text-align: center;
    }

    .btn-accion {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e3f2fd;
        color: #1976d2;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .btn-accion:hover {
        background: #1976d2;
        color: white;
        transform: scale(1.1);
    }

    .btn-accion .material-symbols-rounded {
        font-size: 20px;
    }

    /* Empty State */
    .empty-row {
        background: #f9f9f9 !important;
    }

    .empty-message {
        text-align: center;
        padding: 3rem 2rem !important;
        color: #999;
    }

    .empty-message .material-symbols-rounded {
        display: block;
        font-size: 48px;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-message p {
        margin: 0;
        font-size: 1.1rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .mis-pedidos-container {
            padding: 1rem;
        }

        .filtros-section {
            gap: 1rem;
        }

        .filtro-select {
            min-width: 120px;
        }

        .pedidos-table {
            font-size: 0.8rem;
        }

        .pedidos-table th,
        .pedidos-table td {
            padding: 0.75rem 0.5rem;
        }

        .btn-accion {
            width: 32px;
            height: 32px;
        }

        .btn-accion .material-symbols-rounded {
            font-size: 18px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filtroEstado = document.getElementById('filtroEstado');
        const filtroOrdenamiento = document.getElementById('filtroOrdenamiento');
        const tableBody = document.getElementById('pedidosTableBody');

        filtroEstado.addEventListener('change', filtrarPedidos);
        filtroOrdenamiento.addEventListener('change', ordenarPedidos);

        function filtrarPedidos() {
            const estado = filtroEstado.value;
            const filas = tableBody.querySelectorAll('.pedido-row');

            filas.forEach(fila => {
                if (estado === '' || fila.dataset.estado === estado) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        }

        function ordenarPedidos() {
            const tipo = filtroOrdenamiento.value;
            const filas = Array.from(tableBody.querySelectorAll('.pedido-row'));

            filas.sort((a, b) => {
                switch(tipo) {
                    case 'reciente':
                        return b.dataset.numero - a.dataset.numero;
                    case 'antiguo':
                        return a.dataset.numero - b.dataset.numero;
                    case 'cliente':
                        const clienteA = a.querySelector('.cliente').textContent;
                        const clienteB = b.querySelector('.cliente').textContent;
                        return clienteA.localeCompare(clienteB);
                    default:
                        return 0;
                }
            });

            tableBody.innerHTML = '';
            filas.forEach(fila => tableBody.appendChild(fila));
        }
    });
</script>
@endsection
