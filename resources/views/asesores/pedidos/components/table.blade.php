<!-- Tabla con Scroll Horizontal -->
<div style="background: #e5e7eb; border-radius: 8px; overflow: visible; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 0.75rem; width: 100%; max-width: 100%;">
    <!-- Contenedor con Scroll -->
    <div class="table-scroll-container" style="overflow-x: auto; overflow-y: auto; width: 100%; max-width: 100%; max-height: {{ request('tipo') === 'logo' ? '400px' : 'none' }}; border-radius: 6px; scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
        <!-- Header Azul -->
        <div style="
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: white;
            padding: 0.75rem 1rem;
            display: grid;
            grid-template-columns: {{ request('tipo') === 'logo' ? '140px 140px 160px 180px 190px 260px 160px 170px' : '120px 120px 120px 140px 110px 170px 160px 120px 130px 130px' }};
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
            <div class="th-wrapper">
                <span>Descripción</span>
                <button type="button" class="btn-filter-column" title="Filtrar Descripción">
                    <span class="material-symbols-rounded">filter_alt</span>
                </button>
            </div>
            @if(request('tipo') !== 'logo')
            <div class="th-wrapper">
                <span>Cantidad</span>
                <button type="button" class="btn-filter-column" title="Filtrar Cantidad">
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
