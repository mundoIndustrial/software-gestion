<!-- Filtros Rápidos -->
<div class="filtros-rapidos-asesores">
    <span class="filtros-rapidos-asesores-label">Filtrar por tipo:</span>
    <a href="{{ route('asesores.pedidos.index') }}" class="btn-filtro-rapido-asesores {{ !request('tipo') ? 'active' : '' }}" onclick="return navegarFiltro(this.href, event)">
        <span class="material-symbols-rounded">shopping_cart</span>
        Todos
    </a>
    <a href="{{ route('asesores.pedidos.index', ['tipo' => 'logo']) }}" class="btn-filtro-rapido-asesores {{ request('tipo') === 'logo' ? 'active' : '' }}" onclick="return navegarFiltro(this.href, event)">
        <span class="material-symbols-rounded">palette</span>
        Logo
    </a>
</div>

<!-- Botón Flotante para Limpiar Todos los Filtros -->
<button id="btnClearAllFilters" class="floating-clear-filters" onclick="clearAllFilters()">
    <span class="material-symbols-rounded" style="font-size: 24px;">filter_alt_off</span>
    <div class="floating-clear-filters-tooltip">Limpiar todos los filtros</div>
</button>
