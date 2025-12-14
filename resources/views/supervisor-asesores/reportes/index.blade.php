@extends('layouts.supervisor-asesores')

@section('title', 'Reportes')
@section('page-title', 'Reportes y Análisis')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <h1>Reportes y Análisis</h1>
        <p>Análisis de desempeño por asesor y período</p>
    </div>

    <!-- Filtros -->
    <div class="filters-section">
        <div class="filter-group">
            <label>Período</label>
            <select id="filterPeriod" onchange="actualizarReportes()">
                <option value="week">Esta Semana</option>
                <option value="month" selected>Este Mes</option>
                <option value="quarter">Este Trimestre</option>
                <option value="year">Este Año</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Asesor</label>
            <select id="filterAsesor" onchange="actualizarReportes()">
                <option value="">Todos los Asesores</option>
                <option value="">Cargando...</option>
            </select>
        </div>

        <div class="filter-group">
            <button onclick="actualizarReportes()" class="btn-primary">
                <span class="material-symbols-rounded">refresh</span>
                Actualizar
            </button>
        </div>
    </div>

    <!-- Grid de Reportes -->
    <div class="reportes-grid">
        <!-- Resumen General -->
        <div class="reporte-card">
            <div class="card-header">
                <h3>Resumen General</h3>
                <span class="material-symbols-rounded">dashboard</span>
            </div>
            <div class="card-body">
                <div class="stat-item">
                    <span class="stat-label">Total Cotizaciones</span>
                    <span class="stat-value" id="totalCotizaciones">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Pedidos</span>
                    <span class="stat-value" id="totalPedidos">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Tasa Conversión</span>
                    <span class="stat-value" id="tasaConversion">0%</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Ingresos Generados</span>
                    <span class="stat-value" id="ingresosGenerados">$0</span>
                </div>
            </div>
        </div>

        <!-- Cotizaciones por Estado -->
        <div class="reporte-card">
            <div class="card-header">
                <h3>Cotizaciones por Estado</h3>
                <span class="material-symbols-rounded">category</span>
            </div>
            <div class="card-body">
                <div id="cotizacionesPorEstado">
                    <p style="text-align: center; color: #999;">Cargando...</p>
                </div>
            </div>
        </div>

        <!-- Top Asesores -->
        <div class="reporte-card">
            <div class="card-header">
                <h3>Top 5 Asesores</h3>
                <span class="material-symbols-rounded">trending_up</span>
            </div>
            <div class="card-body">
                <table id="topAsesores" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Asesor</th>
                            <th>Cotizaciones</th>
                            <th>Pedidos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="3" style="text-align: center; color: #999;">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Prendas Más Cotizadas -->
        <div class="reporte-card">
            <div class="card-header">
                <h3>Prendas Más Cotizadas</h3>
                <span class="material-symbols-rounded">checkroom</span>
            </div>
            <div class="card-body">
                <table id="prendasMasCotizadas" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Prenda</th>
                            <th>Cantidad</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="3" style="text-align: center; color: #999;">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Técnicas Más Usadas -->
        <div class="reporte-card">
            <div class="card-header">
                <h3>Técnicas Más Usadas</h3>
                <span class="material-symbols-rounded">palette</span>
            </div>
            <div class="card-body">
                <table id="tecnicasMasUsadas" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Técnica</th>
                            <th>Cantidad</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="3" style="text-align: center; color: #999;">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Clientes Principales -->
        <div class="reporte-card">
            <div class="card-header">
                <h3>Top 10 Clientes</h3>
                <span class="material-symbols-rounded">person_add</span>
            </div>
            <div class="card-body">
                <table id="topClientes" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Cotizaciones</th>
                            <th>Monto Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="3" style="text-align: center; color: #999;">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .content-wrapper {
        padding: 2rem;
    }

    .content-header {
        margin-bottom: 2rem;
    }

    .content-header h1 {
        font-size: 2rem;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .content-header p {
        color: #666;
    }

    .filters-section {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
    }

    .filter-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #333;
        font-size: 0.9rem;
    }

    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn-primary {
        background: #667eea;
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: auto;
    }

    .btn-primary:hover {
        background: #5568d3;
    }

    .reportes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 2rem;
    }

    .reporte-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header h3 {
        margin: 0;
        font-size: 1.1rem;
    }

    .card-header .material-symbols-rounded {
        font-size: 1.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    .stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #eee;
    }

    .stat-item:last-child {
        border-bottom: none;
    }

    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }

    .stat-value {
        font-weight: 700;
        font-size: 1.2rem;
        color: #333;
    }

    .reporte-card table {
        width: 100%;
        border-collapse: collapse;
    }

    .reporte-card table thead th {
        background: #f5f5f5;
        padding: 0.75rem;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #eee;
    }

    .reporte-card table tbody td {
        padding: 0.75rem;
        border-bottom: 1px solid #eee;
    }

    .reporte-card table tbody tr:hover {
        background: #fafafa;
    }

    @media (max-width: 768px) {
        .filters-section {
            flex-direction: column;
        }

        .reportes-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        cargarAsesoresFilter();
        actualizarReportes();
    });

    function cargarAsesoresFilter() {
        fetch('{{ route("supervisor-asesores.asesores.data") }}')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('filterAsesor');
                select.innerHTML = '<option value="">Todos los Asesores</option>';
                data.forEach(asesor => {
                    const option = document.createElement('option');
                    option.value = asesor.id;
                    option.textContent = asesor.name;
                    select.appendChild(option);
                });
            })
            .catch(error => console.error('Error:', error));
    }

    function actualizarReportes() {
        const period = document.getElementById('filterPeriod').value;
        const asesorId = document.getElementById('filterAsesor').value;

        fetch(`{{ route("supervisor-asesores.reportes.data") }}?period=${period}&asesor_id=${asesorId}`)
            .then(response => response.json())
            .then(data => {
                // Actualizar resumen general
                document.getElementById('totalCotizaciones').textContent = data.summary.total_cotizaciones || 0;
                document.getElementById('totalPedidos').textContent = data.summary.total_pedidos || 0;
                document.getElementById('tasaConversion').textContent = data.summary.conversion_rate || '0%';
                document.getElementById('ingresosGenerados').textContent = '$' + (data.summary.total_ingresos || 0).toLocaleString();

                // Actualizar cotizaciones por estado
                const cotizacionesHtml = data.summary.cotizaciones_por_estado.map(item => 
                    `<div class="stat-item"><span class="stat-label">${item.estado}</span><span class="stat-value">${item.cantidad}</span></div>`
                ).join('');
                document.getElementById('cotizacionesPorEstado').innerHTML = cotizacionesHtml || '<p style="color: #999;">No hay datos</p>';

                // Actualizar top asesores
                const topAsesoresHtml = data.top_asesores.map(asesor => 
                    `<tr><td>${asesor.name}</td><td>${asesor.cotizaciones_count}</td><td>${asesor.pedidos_count}</td></tr>`
                ).join('');
                document.getElementById('topAsesores').querySelector('tbody').innerHTML = topAsesoresHtml || '<tr><td colspan="3" style="text-align: center; color: #999;">No hay datos</td></tr>';

                // Actualizar prendas más cotizadas
                const prendasHtml = data.prendas_mas_cotizadas.map(prenda => 
                    `<tr><td>${prenda.tipo}</td><td>${prenda.cantidad}</td><td>${prenda.porcentaje}%</td></tr>`
                ).join('');
                document.getElementById('prendasMasCotizadas').querySelector('tbody').innerHTML = prendasHtml || '<tr><td colspan="3" style="text-align: center; color: #999;">No hay datos</td></tr>';

                // Actualizar técnicas más usadas
                const tecnicasHtml = data.tecnicas_mas_usadas.map(tecnica => 
                    `<tr><td>${tecnica.nombre}</td><td>${tecnica.cantidad}</td><td>${tecnica.porcentaje}%</td></tr>`
                ).join('');
                document.getElementById('tecnicasMasUsadas').querySelector('tbody').innerHTML = tecnicasHtml || '<tr><td colspan="3" style="text-align: center; color: #999;">No hay datos</td></tr>';

                // Actualizar top clientes
                const clientesHtml = data.top_clientes.map(cliente => 
                    `<tr><td>${cliente.nombre}</td><td>${cliente.cotizaciones_count}</td><td>$${cliente.monto_total.toLocaleString()}</td></tr>`
                ).join('');
                document.getElementById('topClientes').querySelector('tbody').innerHTML = clientesHtml || '<tr><td colspan="3" style="text-align: center; color: #999;">No hay datos</td></tr>';
            })
            .catch(error => console.error('Error:', error));
    }
</script>
@endpush
@endsection
