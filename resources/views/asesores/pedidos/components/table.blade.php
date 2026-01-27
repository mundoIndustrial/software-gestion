<!-- Tabla con Scroll Horizontal -->
<div style="background: #e5e7eb; border-radius: 8px; overflow: visible; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 0.75rem; width: 100%; max-width: 100%;">
    <!-- Contenedor con Scroll -->
    <div class="table-scroll-container" style="overflow-x: auto; overflow-y: auto; width: 100%; max-width: 100%; max-height: {{ request('tipo') === 'logo' ? '600px' : '800px' }}; border-radius: 6px; scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
        <!-- Header Azul -->
        <div style="
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: white;
            padding: 0.75rem 1rem;
            display: grid;
            grid-template-columns: {{ request('tipo') === 'logo' ? '120px 120px 120px 140px 110px 120px 130px' : '120px 120px 120px 140px 110px 120px 130px 130px 130px' }};
            gap: 1.2rem;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: min-content;
            border-radius: 6px;
        ">
            <div class="th-wrapper">
                <span>Acciones</span>
                <button type="button" class="btn-filter-column" title="Filtrar Acciones">
                    <span class="material-symbols-rounded">filter_alt</span>
                </button>
            </div>
            <div class="th-wrapper">
                <span>Estado</span>
                <button type="button" class="btn-filter-column" title="Filtrar Estado">
                    <span class="material-symbols-rounded">filter_alt</span>
                </button>
            </div>
            <div class="th-wrapper">
                <span>Área</span>
                <button type="button" class="btn-filter-column" title="Filtrar Área">
                    <span class="material-symbols-rounded">filter_alt</span>
                </button>
            </div>
            <div class="th-wrapper">
                <span>Pedido</span>
                <button type="button" class="btn-filter-column" title="Filtrar Pedido">
                    <span class="material-symbols-rounded">filter_alt</span>
                </button>
            </div>
            <div class="th-wrapper">
                <span>Cliente</span>
                <button type="button" class="btn-filter-column" title="Filtrar Cliente">
                    <span class="material-symbols-rounded">filter_alt</span>
                </button>
            </div>
            @if(request('tipo') !== 'logo')
            <div class="th-wrapper">
                <span>Novedades</span>
                <button type="button" class="btn-filter-column" title="Filtrar Novedades">
                    <span class="material-symbols-rounded">filter_alt</span>
                </button>
            </div>
            @endif
            <div class="th-wrapper">
                <span>Forma Pago</span>
                <button type="button" class="btn-filter-column" title="Filtrar Forma Pago">
                    <span class="material-symbols-rounded">filter_alt</span>
                </button>
            </div>
            <div class="th-wrapper">
                <span>Fecha Creación</span>
                <button type="button" class="btn-filter-column" title="Filtrar Fecha Creación">
                    <span class="material-symbols-rounded">filter_alt</span>
                </button>
            </div>
            @if(request('tipo') !== 'logo')
            <div class="th-wrapper">
                <span>Fecha Estimada</span>
                <button type="button" class="btn-filter-column" title="Filtrar Fecha">
                    <span class="material-symbols-rounded">filter_alt</span>
                </button>
            </div>
            @endif
        </div>

        <!-- Filas -->
        @if($pedidos->isEmpty())
            <div style="padding: 3rem 2rem; text-align: center; color: #6b7280;">
                <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                <p style="font-size: 1rem; margin: 0;">No hay pedidos registrados</p>
            </div>
        @else
            @foreach($pedidos as $pedido)
                @include('asesores.pedidos.components.table-row', ['pedido' => $pedido])
            @endforeach
        @endif
    </div>
</div>

<!-- Paginación Personalizada - Mostrar si hay más de 1 página O si hay datos -->
@if($pedidos->lastPage() > 1 || $pedidos->count() > 0)
    <div style="margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 8px; flex-wrap: wrap;">
        <!-- Botón Anterior -->
        @if($pedidos->onFirstPage())
            <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                ← Anterior
            </button>
        @else
            <a href="{{ $pedidos->previousPageUrl() }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                ← Anterior
            </a>
        @endif

        <!-- Números de Página -->
        @if($pedidos->lastPage() > 1)
            @foreach($pedidos->getUrlRange(1, $pedidos->lastPage()) as $page => $url)
                @if($page == $pedidos->currentPage())
                    <button disabled style="min-width: 36px; height: 36px; padding: 0 8px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border: 1px solid #1d4ed8; border-radius: 6px; color: white; font-weight: 600; cursor: default;">
                        {{ $page }}
                    </button>
                @else
                    <a href="{{ $url }}" style="min-width: 36px; height: 36px; padding: 0 8px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                        {{ $page }}
                    </a>
                @endif
            @endforeach
        @endif

        <!-- Botón Siguiente -->
        @if($pedidos->hasMorePages())
            <a href="{{ $pedidos->nextPageUrl() }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                Siguiente →
            </a>
        @else
            <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                Siguiente →
            </button>
        @endif

        <!-- Info de Página -->
        <span style="margin-left: 1rem; color: #666; font-size: 14px; font-weight: 500;">
            Página {{ $pedidos->currentPage() }} de {{ $pedidos->lastPage() }} | Total: {{ $pedidos->total() }} registros
        </span>
    </div>
@endif
