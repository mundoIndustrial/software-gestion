{{-- Componente: Filtros por Tipo --}}
<style>
    .cotizacion-tab-btn {
        padding: 10px 18px !important;
        border-radius: 20px !important;
        border: none !important;
        cursor: pointer !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        transition: all 0.3s !important;
        background: #ecf0f1 !important;
        color: #2c3e50 !important;
    }
    
    .cotizacion-tab-btn.active {
        background: #1e40af !important;
        color: white !important;
        box-shadow: 0 4px 6px rgba(30, 64, 175, 0.2) !important;
    }
    
    .cotizacion-tab-btn:hover {
        transform: translateY(-2px);
    }
</style>

<div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin: 20px 0 25px 0; flex-wrap: wrap;">
    <p style="margin: 0; color: #666; font-weight: 600; font-size: 0.9rem; white-space: nowrap;">
        <i class="fas fa-layer-group"></i> FILTRAR POR TIPO:
    </p>
    
    <div class="cotizaciones-tabs-container" style="margin: 0; padding: 0; gap: 10px; display: flex; flex-wrap: wrap; justify-content: center;">
        {{-- Generar botones dinámicamente --}}
        @foreach($filters as $filter)
            <button type="button" 
                class="cotizacion-tab-btn {{ $filter['active'] ? 'active' : '' }}" 
                data-tipo="{{ $filter['code'] }}" 
                onclick="mostrarTipo('{{ $filter['code'] }}')">
                <i class="{{ $filter['icon'] }}" style="font-size: 14px;"></i>
                <span>{{ $filter['label'] }}</span>
            </button>
        @endforeach
    </div>
</div>
