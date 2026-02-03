@extends('layouts.supervisor-asesores')

@section('title', 'Dashboard - Supervisor de Asesores')
@section('page-title', 'Dashboard de Supervisor de Asesores')

@section('content')
<div class="dashboard-container">
    <!-- Tarjetas de Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card stat-month">
            <div class="stat-icon">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-content">
                <h3>Cotizaciones Este Mes</h3>
                <p class="stat-value" id="cotizacionesMes">0</p>
                <span class="stat-label">Este mes</span>
            </div>
        </div>

        <div class="stat-card stat-month">
            <div class="stat-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-content">
                <h3>Total Pedidos Mes</h3>
                <p class="stat-value" id="totalPedidosMes">0</p>
                <span class="stat-label">Sistema</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        cargarEstadisticas();
    });

    function cargarEstadisticas() {
        fetch('{{ route("supervisor-asesores.dashboard-stats") }}')
            .then(response => response.json())
            .then(data => {
                document.getElementById('cotizacionesMes').textContent = data.cotizaciones_mes || 0;
                document.getElementById('totalPedidosMes').textContent = data.total_pedidos_mes || 0;
            })
            .catch(error => console.error('Error cargando estadísticas:', error));
    }
</script>
@endpush
@endsection
