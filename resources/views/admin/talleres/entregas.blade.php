@extends('layouts.base')

@section('title', 'Detalle de Entregas - ' . $recibo->numero_recibo)
@section('page-title', 'Gestión de Talleres')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/top-nav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-entregas.css') }}">
@endpush

@section('body')
    <!-- Dashboard Top Nav -->
    @include('components.top-nav')

    <!-- Main Content -->
    <main class="main-container">
        
        <div class="page-header-entregas">
            <a href="{{ route('talleres.show', $taller->id) }}" class="btn-back" title="Volver a Recibos">
                <span class="material-symbols-rounded">arrow_back</span>
            </a>
            <div class="title-group">
                <h1>Detalle de Entregas</h1>
                <p class="subtitle">Recibo: {{ $recibo->numero_recibo }} — {{ $recibo->cliente }}</p>
            </div>
        </div>

        <div class="entregas-card">
            <div class="card-header">
                <div class="header-left-title">
                    <div class="icon">
                        <span class="material-symbols-rounded" style="font-size: 18px;">inventory_2</span>
                    </div>
                    <h2>Historial de Entregas Semanales - {{ $recibo->nombre_prenda }}</h2>
                </div>
                <div class="header-total">
                    TOTAL: {{ $totalGeneral }} UND
                </div>
            </div>
            
            <div class="table-container">
                <table class="table-entregas">
                    @forelse($entregasAgrupadas as $semana => $entregas)
                        <thead>
                            <tr class="semana-header">
                                <td colspan="4">
                                    <div class="semana-title">
                                        <span class="material-symbols-rounded">calendar_month</span>
                                        {{ $semana }}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>FECHA</th>
                                <th>DESCRIPCIÓN</th>
                                <th>TALLA</th>
                                <th>CANTIDAD</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entregas as $entrega)
                                <tr>
                                    <td class="data-cell col-fecha">{{ $entrega['fecha_formateada'] }}</td>
                                    <td class="data-cell col-desc">{{ $entrega['descripcion'] }}</td>
                                    <td class="data-cell">
                                        <span class="badge-talla">{{ $entrega['talla'] }}</span>
                                    </td>
                                    <td class="data-cell col-cantidad">
                                        {{ $entrega['cantidad'] }} <small>UND</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8;">
                                    No hay entregas registradas para este recibo.
                                </td>
                            </tr>
                        </tbody>
                    @endforelse
                </table>
            </div>
        </div>
        
    </main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicialización adicional si se necesita
    });
</script>
@endpush
