@extends('layouts.base')

@section('title', 'Recibos Asignados a: ' . $taller->name)
@section('page-title', 'Gestión de Talleres')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/top-nav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-show.css') }}">
@endpush

@section('body')
    <!-- Dashboard Top Nav -->
    @include('components.top-nav')

    <!-- Main Content -->
    <main class="main-container">
        
        <div class="page-header-taller">
            <div class="header-left">
                <a href="{{ route('talleres.index') }}" class="btn-back" title="Volver a Talleres">
                    <span class="material-symbols-rounded">arrow_back</span>
                </a>
                <div class="title-group">
                    <span class="subtitle">Recibos Asignados a:</span>
                    <h1>{{ $taller->name }}</h1>
                </div>
            </div>
            
            <div class="header-stats">
                <div class="stat-box blue">
                    <span class="stat-label">TOTAL CARGA</span>
                    <span class="stat-number">{{ $totalCarga }}</span>
                </div>
                <div class="stat-box green">
                    <span class="stat-label">COMPLETADOS</span>
                    <span class="stat-number">{{ $completados }}</span>
                </div>
            </div>
        </div>

        <div class="recibos-card">
            <div class="card-header">
                <div class="icon">
                    <span class="material-symbols-rounded" style="font-size: 18px;">receipt_long</span>
                </div>
                <h2>Listado de Recibos Asignados</h2>
            </div>
            
            <div class="table-container">
                <table class="table-recibos">
                    <thead>
                        <tr>
                            <th>N° RECIBO</th>
                            <th>CLIENTE</th>
                            <th>DESCRIPCIÓN PRENDA</th>
                            <th class="col-progreso">PROGRESO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recibos as $recibo)
                            <tr>
                                <td class="col-recibo">{{ $recibo->numero_recibo }}</td>
                                <td class="col-cliente">{{ $recibo->cliente }}</td>
                                <td>
                                    <div class="prenda-nombre">{{ $recibo->nombre_prenda }}</div>
                                    <p class="prenda-desc">{{ $recibo->descripcion_prenda }}</p>
                                </td>
                                <td class="col-progreso">
                                    <div class="progress-container">
                                        <div class="progress-info">
                                            <span class="progress-text">Entregado: <b>{{ $recibo->cantidad_entregada }}</b> | Falta: <b>{{ $recibo->cantidad_pendiente }}</b></span>
                                            <span class="progress-percentage">{{ $recibo->porcentaje }}%</span>
                                        </div>
                                        <div class="progress-bar-wrapper">
                                            <div class="progress-bar-fill" style="width: {{ $recibo->porcentaje }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('talleres.entregas', ['id' => $taller->id, 'recibo_id' => $recibo->id, 'es_parcial' => $recibo->es_parcial]) }}" class="btn-action">
                                        VER ENTREGAS <span style="font-size: 10px;">&#10095;</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                                    No hay recibos asignados a este taller por el momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
    </main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Scripts if needed
    });
</script>
@endpush
