@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/tableros.css') }}">
<link rel="stylesheet" href="{{ asset('css/modern-table.css') }}">

<div class="tableros-container">
    <div class="page-header" style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1 class="tableros-title" style="margin: 0;">
                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 10px;">schedule</span>
                    Balanceo de Líneas
                </h1>
                <p class="page-subtitle" style="font-size: 16px; margin-top: 10px;">
                    Gestión de prendas y balanceo de operaciones
                </p>
            </div>
            <a href="{{ route('balanceo.prenda.create') }}" 
               style="background: #ff9d58; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; text-decoration: none; font-weight: 500; box-shadow: 0 2px 4px rgba(255, 157, 88, 0.3); transition: background 0.2s;"
               onmouseover="this.style.background='#e88a47'" onmouseout="this.style.background='#ff9d58'">
                <span class="material-symbols-rounded">add</span>
                Nueva Prenda
            </a>
        </div>

        <!-- Buscador -->
        <form method="GET" action="{{ route('balanceo.index') }}" style="padding: 18px 0;">
            <div style="position: relative;">
                <span class="material-symbols-rounded" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--color-text-placeholder); font-size: 22px;">search</span>
                <input type="text" 
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Buscar por nombre, referencia o tipo de prenda..."
                       style="width: 100%; padding: 12px 16px 12px 48px; border: 1px solid var(--color-border-hr); border-radius: 8px; font-size: 15px; transition: all 0.3s ease; background: var(--color-bg-sidebar); color: var(--color-text-primary);"
                       onfocus="this.style.borderColor='rgba(255, 157, 88, 0.4)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                       onblur="this.style.borderColor='var(--color-border-hr)'; this.style.boxShadow='none'"
                       onchange="this.form.submit()">
                @if(request('search'))
                <button type="button" 
                        onclick="window.location='{{ route('balanceo.index') }}'"
                        style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--color-text-placeholder); cursor: pointer; padding: 4px;">
                    <span class="material-symbols-rounded" style="font-size: 20px;">close</span>
                </button>
                @endif
            </div>
        </form>
    </div>

    @if(session('success'))
    <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <span class="material-symbols-rounded">check_circle</span>
        {{ session('success') }}
    </div>
    @endif

    <!-- Grid de prendas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
        @forelse($prendas as $prenda)
        <div class="prenda-card" 
             style="background: var(--color-bg-sidebar); border-radius: 12px; overflow: hidden; border: 1px solid var(--color-border-hr); transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease; cursor: pointer; box-shadow: 0 1px 3px var(--color-shadow);"
             onclick="window.location='{{ route('balanceo.show', $prenda->id) }}'">
            
            <!-- Imagen de la prenda -->
            <div style="height: 180px; background: white; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                @if($prenda->imagen)
                <img src="{{ asset($prenda->imagen) }}" 
                     alt="{{ $prenda->nombre }}"
                     style="width: 100%; height: 100%; object-fit: contain;">
                @else
                <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                    <span class="material-symbols-rounded" style="font-size: 80px; color: #ccc;">checkroom</span>
                </div>
                @endif
                
                <!-- Badge del tipo -->
                <div style="position: absolute; top: 12px; right: 12px; background: #ff9d58; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase;">
                    {{ $prenda->tipo }}
                </div>
            </div>

            <!-- Contenido de la tarjeta -->
            <div style="padding: 20px;">
                <h3 style="margin: 0 0 8px 0; font-size: 20px; color: var(--color-text-primary); font-weight: 700;">{{ $prenda->nombre }}</h3>
                
                @if($prenda->referencia)
                <p style="margin: 0 0 12px 0; color: var(--color-text-placeholder); font-size: 14px;">
                    <strong style="color: var(--color-text-primary); opacity: 0.8;">Ref:</strong> {{ $prenda->referencia }}
                </p>
                @endif

                @if($prenda->descripcion)
                <p style="margin: 0 0 16px 0; color: var(--color-text-placeholder); font-size: 14px; line-height: 1.5;">
                    {{ Str::limit($prenda->descripcion, 100) }}
                </p>
                @endif

                <!-- Información del balanceo -->
                @if($prenda->balanceoActivo)
                <div style="border-top: 1px solid var(--color-border-hr); padding-top: 16px; margin-top: 16px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
                        <div>
                            <p style="margin: 0; color: var(--color-text-placeholder); font-size: 11px; text-transform: uppercase; font-weight: 600;">Operaciones</p>
                            <p style="margin: 4px 0 0 0; font-weight: 700; color: #ff9d58; font-size: 18px;">
                                {{ $prenda->balanceoActivo->operaciones->count() }}
                            </p>
                        </div>
                        <div>
                            <p style="margin: 0; color: var(--color-text-placeholder); font-size: 11px; text-transform: uppercase; font-weight: 600;">SAM Total</p>
                            <p style="margin: 4px 0 0 0; font-weight: 700; color: #ff9d58; font-size: 18px;">
                                {{ number_format($prenda->balanceoActivo->sam_total, 1) }}s
                            </p>
                        </div>
                        <div>
                            <p style="margin: 0; color: var(--color-text-placeholder); font-size: 11px; text-transform: uppercase; font-weight: 600;">Operarios</p>
                            <p style="margin: 4px 0 0 0; font-weight: 700; color: #ff9d58; font-size: 18px;">
                                {{ $prenda->balanceoActivo->total_operarios }}
                            </p>
                        </div>
                        <div>
                            <p style="margin: 0; color: var(--color-text-placeholder); font-size: 11px; text-transform: uppercase; font-weight: 600;">Meta Real</p>
                            <p style="margin: 4px 0 0 0; font-weight: 700; color: #ff9d58; font-size: 18px;">
                                {{ $prenda->balanceoActivo->meta_real ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
                @else
                <div style="border-top: 1px solid var(--color-border-hr); padding-top: 16px; margin-top: 16px; text-align: center;">
                    <p style="margin: 0; color: var(--color-text-placeholder); font-size: 13px;">Sin balanceo configurado</p>
                </div>
                @endif

                <!-- Botón de acción -->
                <button style="width: 100%; margin-top: 16px; background: #ff9d58; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 8px; transition: background 0.2s;" onmouseover="this.style.background='#e88a47'" onmouseout="this.style.background='#ff9d58'">
                    <span class="material-symbols-rounded" style="font-size: 18px;">visibility</span>
                    Ver Balanceo
                </button>
            </div>
        </div>
        @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--color-bg-sidebar); border-radius: 12px; border: 1px solid var(--color-border-hr); box-shadow: 0 1px 3px var(--color-shadow);">
            <span class="material-symbols-rounded" style="font-size: 64px; color: var(--color-text-placeholder); opacity: 0.5; display: block; margin-bottom: 16px;">checkroom</span>
            <h3 style="color: var(--color-text-primary); margin-bottom: 8px; font-weight: 700;">No hay prendas registradas</h3>
            <p style="color: var(--color-text-placeholder); margin-bottom: 24px;">Comienza creando tu primera prenda para gestionar su balanceo</p>
            <a href="{{ route('balanceo.prenda.create') }}" 
               style="background: #ff9d58; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-weight: 500; box-shadow: 0 2px 4px rgba(255, 157, 88, 0.3);">
                <span class="material-symbols-rounded">add</span>
                Nueva Prenda
            </a>
        </div>
        @endforelse
    </div>

    <!-- Paginación -->
    @if($prendas->hasPages())
    <div class="table-pagination" style="margin-top: 40px;">
        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ ($prendas->currentPage() / $prendas->lastPage()) * 100 }}%"></div>
        </div>
        <div class="pagination-info">
            <span>Mostrando {{ $prendas->firstItem() }}-{{ $prendas->lastItem() }} de {{ $prendas->total() }} prendas</span>
        </div>
        <div class="pagination-controls">
            {{ $prendas->appends(request()->query())->links('vendor.pagination.custom') }}
        </div>
    </div>
    @endif
</div>

<style>
.prenda-card:hover {
    transform: translateY(-5px);
    border-color: #ff9d58 !important;
    box-shadow: 0 8px 16px rgba(255, 157, 88, 0.25) !important;
}

.page-subtitle {
    color: var(--color-text-placeholder);
    font-size: 16px;
    margin-top: 10px;
}

/* Estilos de paginación (heredados de tableros.css) */
.table-pagination {
    background: #1e293b;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.progress-bar {
    background: #334155;
    height: 6px;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 1.25rem;
}

.progress-fill {
    background: linear-gradient(90deg, #f97316 0%, #fb923c 100%);
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.pagination-info {
    color: #94a3b8;
    font-size: 14px;
    margin-bottom: 1.25rem;
}

.pagination-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

/* Estilos de paginación mejorados */
.pagination-controls .pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
}

.pagination-controls .pagination button,
.pagination-controls .pagination a {
    background: #334155;
    color: #cbd5e1;
    border: none;
    padding: 10px 16px;
    min-width: 44px;
    height: 44px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.pagination-controls .pagination a:hover:not(:disabled),
.pagination-controls .pagination button:hover:not(:disabled) {
    background: #475569;
    transform: translateY(-1px);
}

.pagination-controls .pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-controls .pagination button.active,
.pagination-controls .pagination a.active {
    background: linear-gradient(135deg, #f97316 0%, #fb923c 100%) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
}

.pagination-controls .pagination .nav-btn {
    padding: 10px 20px;
    min-width: auto;
}

.pagination-controls .pagination .dots {
    color: #64748b;
    padding: 0 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Responsive */
@media (max-width: 768px) {
    .pagination-controls .pagination {
        gap: 4px;
    }

    .pagination-controls .pagination button,
    .pagination-controls .pagination a {
        padding: 8px 12px;
        min-width: 40px;
        height: 40px;
        font-size: 13px;
    }

    .pagination-controls .pagination .nav-btn {
        padding: 8px 16px;
    }
}
</style>

@endsection
