@extends('layouts.supervisor-asesores')

@section('title', 'Asesores')
@section('page-title', 'Gestión de Asesores')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <h1>Asesores del Equipo</h1>
        <p>Visualiza información de todos los asesores</p>
    </div>

    <!-- Grid de Asesores -->
    <div class="asesores-grid" id="asesoresGrid">
        <div style="text-align: center; padding: 2rem; grid-column: 1/-1;">
            <span class="material-symbols-rounded" style="font-size: 2rem; color: #999;">hourglass_empty</span>
            <p>Cargando asesores...</p>
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

    .asesores-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
    }

    .asesor-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .asesor-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }

    .asesor-header {
        background: linear-gradient(135deg, #0084ff 0%, #0066cc 100%);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .asesor-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 2rem;
        font-weight: bold;
    }

    .asesor-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .asesor-name {
        font-size: 1.3rem;
        font-weight: 600;
        margin: 0;
    }

    .asesor-email {
        font-size: 0.9rem;
        opacity: 0.9;
        margin: 0.5rem 0 0 0;
    }

    .asesor-body {
        padding: 1.5rem;
    }

    .asesor-stat {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #eee;
    }

    .asesor-stat:last-child {
        border-bottom: none;
    }

    .asesor-stat-label {
        color: #666;
        font-size: 0.9rem;
    }

    .asesor-stat-value {
        font-weight: 600;
        font-size: 1.1rem;
        color: #333;
    }

    .asesor-actions {
        padding: 1rem;
        display: flex;
        gap: 0.5rem;
    }

    .btn-action {
        flex: 1;
        padding: 0.75rem;
        border: 1px solid #ddd;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
        text-align: center;
    }

    .btn-action:hover {
        background: #f5f5f5;
        border-color: #999;
    }

    .btn-action.primary {
        background: #0084ff;
        color: white;
        border-color: #0084ff;
    }

    .btn-action.primary:hover {
        background: #0066cc;
        border-color: #0066cc;
    }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        cargarAsesores();
    });

    function cargarAsesores() {
        fetch('{{ route("supervisor-asesores.asesores.data") }}')
            .then(response => response.json())
            .then(data => {
                const grid = document.getElementById('asesoresGrid');
                grid.innerHTML = '';

                if (data.length === 0) {
                    grid.innerHTML = '<div style="text-align: center; padding: 2rem; grid-column: 1/-1; color: #999;">No hay asesores disponibles</div>';
                    return;
                }

                data.forEach(asesor => {
                    const card = document.createElement('div');
                    card.className = 'asesor-card';
                    card.innerHTML = `
                        <div class="asesor-header">
                            <div class="asesor-avatar">
                                ${asesor.avatar ? `<img src="/storage/${asesor.avatar}" alt="${asesor.name}">` : asesor.name.charAt(0).toUpperCase()}
                            </div>
                            <p class="asesor-name">${asesor.name}</p>
                            <p class="asesor-email">${asesor.email}</p>
                        </div>
                        <div class="asesor-body">
                            <div class="asesor-stat">
                                <span class="asesor-stat-label">Cotizaciones</span>
                                <span class="asesor-stat-value">${asesor.cotizaciones_count || 0}</span>
                            </div>
                            <div class="asesor-stat">
                                <span class="asesor-stat-label">Pedidos</span>
                                <span class="asesor-stat-value">${asesor.pedidos_count || 0}</span>
                            </div>
                        </div>
                        <div class="asesor-actions">
                            <a href="{{ url('supervisor-asesores/asesores') }}/${asesor.id}" class="btn-action primary">Ver Detalle</a>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            })
            .catch(error => console.error('Error:', error));
    }
</script>
@endpush
@endsection
