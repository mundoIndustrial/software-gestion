@extends('layouts.supervisor-asesores')

@section('title', $asesor->name)
@section('page-title', 'Detalle del Asesor')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="header-top">
            <a href="{{ route('supervisor-asesores.asesores.index') }}" class="btn-back">
                <span class="material-symbols-rounded">arrow_back</span>
                Volver
            </a>
            <h1>{{ $asesor->name }}</h1>
        </div>
        <p>{{ $asesor->email }}</p>
    </div>

    <div class="detail-grid">
        <!-- Información Personal -->
        <div class="detail-section">
            <div class="section-header">
                <h2>Información Personal</h2>
            </div>
            <div class="section-body">
                <div class="info-row">
                    <span class="info-label">Nombre:</span>
                    <span class="info-value">{{ $asesor->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $asesor->email }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value">{{ $asesor->phone ?? 'No registrado' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Rol:</span>
                    <span class="info-value badge badge-info">{{ $asesor->role?->name ?? $asesor->role }}</span>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="detail-section">
            <div class="section-header">
                <h2>Estadísticas</h2>
            </div>
            <div class="section-body">
                <div class="stat-box">
                    <div class="stat-number">{{ $cotizacionesCount }}</div>
                    <div class="stat-label">Cotizaciones</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">{{ $pedidosCount }}</div>
                    <div class="stat-label">Pedidos</div>
                </div>
            </div>
        </div>

        <!-- Últimas Cotizaciones -->
        <div class="detail-section full-width">
            <div class="section-header">
                <h2>Últimas Cotizaciones</h2>
                <a href="{{ route('supervisor-asesores.cotizaciones.index') }}?asesor_id={{ $asesor->id }}" class="btn-link">Ver todas</a>
            </div>
            <div class="section-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ultimasCotizaciones as $cotizacion)
                            <tr>
                                <td>{{ $cotizacion->numero_cotizacion }}</td>
                                <td>{{ $cotizacion->cliente }}</td>
                                <td>
                                    <span class="badge badge-{{ strtolower($cotizacion->estado) }}">
                                        {{ $cotizacion->estado }}
                                    </span>
                                </td>
                                <td>{{ $cotizacion->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: #999;">No hay cotizaciones</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Últimos Pedidos -->
        <div class="detail-section full-width">
            <div class="section-header">
                <h2>Últimos Pedidos</h2>
                <a href="{{ route('supervisor-asesores.pedidos.index') }}?asesor_id={{ $asesor->id }}" class="btn-link">Ver todas</a>
            </div>
            <div class="section-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Número Pedido</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ultimosPedidos as $pedido)
                            <tr>
                                <td>{{ $pedido->numero_pedido ?? '-' }}</td>
                                <td>{{ $pedido->cliente }}</td>
                                <td>
                                    <span class="badge badge-{{ strtolower($pedido->estado) }}">
                                        {{ $pedido->estado }}
                                    </span>
                                </td>
                                <td>{{ $pedido->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: #999;">No hay pedidos</td>
                            </tr>
                        @endforelse
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

    .header-top {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.5rem;
    }

    .btn-back {
        background: none;
        border: none;
        padding: 0.5rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #0084ff;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-back:hover {
        color: #0066cc;
    }

    .content-header h1 {
        font-size: 2rem;
        color: #333;
    }

    .content-header p {
        color: #666;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
    }

    .detail-section {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .detail-section.full-width {
        grid-column: 1 / -1;
    }

    .section-header {
        background: linear-gradient(135deg, #0084ff 0%, #0066cc 100%);
        color: white;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-header h2 {
        margin: 0;
        font-size: 1.1rem;
    }

    .btn-link {
        background: none;
        border: none;
        color: white;
        text-decoration: underline;
        cursor: pointer;
        font-size: 0.9rem;
        padding: 0;
    }

    .btn-link:hover {
        opacity: 0.8;
    }

    .section-body {
        padding: 1.5rem;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #eee;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #333;
    }

    .info-value {
        color: #666;
    }

    .badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .badge-primary {
        background: #e3f2fd;
        color: #1976d2;
    }

    .badge-draft {
        background: #e3f2fd;
        color: #0084ff;
    }

    .badge-pending {
        background: #fff3e0;
        color: #f57c00;
    }

    .badge-approved {
        background: #e8f5e9;
        color: #388e3c;
    }

    .stat-box {
        text-align: center;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 6px;
        margin-bottom: 1rem;
    }

    .stat-box:last-child {
        margin-bottom: 0;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: #0084ff;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #666;
        margin-top: 0.25rem;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table thead th {
        background: #f5f5f5;
        padding: 0.75rem;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #eee;
    }

    .data-table tbody td {
        padding: 0.75rem;
        border-bottom: 1px solid #eee;
    }

    .data-table tbody tr:hover {
        background: #fafafa;
    }

    @media (max-width: 768px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }

        .header-top {
            flex-direction: column;
            align-items: flex-start;
        }

        .header-top h1 {
            margin: 0;
        }
    }
</style>
@endsection
