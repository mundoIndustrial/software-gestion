{{-- Componente: Filtros por Tipo --}}
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
                onclick="mostrarTipo('{{ $filter['code'] }}')" 
                style="background: {{ $filter['active'] ? '#2c3e50' : '#ecf0f1' }}; color: {{ $filter['active'] ? 'white' : '#2c3e50' }}; padding: 8px 16px; border-radius: 20px; border: none; cursor: pointer; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 6px; transition: all 0.3s;">
                <i class="{{ $filter['icon'] }}" style="font-size: 14px;"></i>
                <span>{{ $filter['label'] }}</span>
            </button>
        @endforeach
    </div>
</div>
