@php
    $columnTemplate = '60px 220px 120px 200px 150px 140px 150px 150px 150px';
    $gridGap = '1.2rem';
@endphp

<style>
    .sp-orders-grid {
        display: grid;
        grid-template-columns: {{ $columnTemplate }};
        gap: {{ $gridGap }};
        min-width: max-content;
        box-sizing: border-box;
    }

    .sp-orders-grid > div {
        min-width: 0;
    }
</style>
<!-- Tabla de Órdenes - Diseño asesores/pedidos -->
<div style="background: #e5e7eb; border-radius: 8px; overflow: visible; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 0.75rem; width: 100%; max-width: 100%;">
    <!-- Contenedor con Scroll -->
    <div class="table-scroll-container" style="overflow-x: auto; overflow-y: auto; width: 100%; max-width: 100%; max-height: 800px; border-radius: 6px; scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
        <!-- Header Azul -->
        <div class="sp-orders-grid" style="
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: white;
            padding: 0.75rem 1rem;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 6px;
        ">
            <div class="th-wrapper" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <span>Listo</span>
            </div>
            <div class="th-wrapper" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <span>Acciones</span>
            </div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>Fecha</span>
                <button type="button" class="btn-filter-column" data-col="fecha" title="Filtrar Fecha" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                </button>
            </div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>Número</span>
                <button type="button" class="btn-filter-column" data-col="numero" title="Filtrar Número" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                </button>
            </div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>Cliente</span>
                <button type="button" class="btn-filter-column" data-col="cliente" title="Filtrar Cliente" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                </button>
            </div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>Estado</span>
                <button type="button" class="btn-filter-column" data-col="estado" title="Filtrar Estado" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                </button>
            </div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>Novedades</span>
            </div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>Asesora</span>
                <button type="button" class="btn-filter-column" data-col="asesora" title="Filtrar Asesora" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                </button>
            </div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>Forma Pago</span>
                <button type="button" class="btn-filter-column" data-col="forma_pago" title="Filtrar Forma Pago" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                </button>
            </div>
        </div>

        <!-- Filas -->
        @if($ordenes->isEmpty())
            <div style="padding: 3rem 2rem; text-align: center; color: #6b7280;">
                <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                <p style="font-size: 1rem; margin: 0;">No hay órdenes disponibles</p>
            </div>
        @else
            @foreach($ordenes as $orden)
                @php $estaSeleccionado = in_array($orden->id, $pedidosSeleccionados); @endphp
                <div class="sp-orders-grid" style="
                    padding: 1rem;
                    border-bottom: 1px solid #e5e7eb;
                    align-items: center;
                    background: {{ $estaSeleccionado ? '#d1d5db' : 'white' }};
                    transition: background 0.2s ease;
                "
                onmouseover="if (!this.dataset.seleccionado || this.dataset.seleccionado === 'false') this.style.background='#f9fafb'"
                onmouseout="this.style.background = (this.dataset.seleccionado === 'true') ? '#d1d5db' : 'white'"
                data-seleccionado="{{ $estaSeleccionado ? 'true' : 'false' }}"
                data-pedido-row="true"
                data-pedido-id="{{ $orden->id }}"
                >

                    <!-- Checkbox de selección -->
                    <div style="display: flex; align-items: center; justify-content: center;">
                        <input type="checkbox" class="pedido-checkbox" data-pedido-id="{{ $orden->id }}" title="Seleccionar pedido" style="width: 18px; height: 18px; cursor: pointer;" {{ $estaSeleccionado ? 'checked' : '' }}>
                    </div>

                    <!-- Acciones -->
                    <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                        <!-- Botón Ver (con dropdown) -->
                        @php
                            $numeroPedido = $orden->numero_pedido ?? 'sin-numero';
                            $pedidoId = $orden->id;
                            $estado = $orden->estado ?? 'Pendiente';
                        @endphp
                        <button class="btn-accion btn-accion--ver btn-ver-dropdown"
                            data-menu-id="menu-ver-{{ str_replace('#', '', $numeroPedido) }}"
                            data-pedido="{{ str_replace('#', '', $numeroPedido) }}"
                            data-pedido-id="{{ $pedidoId }}"
                            title="Ver Opciones"
                            style="position: relative; overflow: visible;">
                            <i class="fas fa-eye"></i>
                            <span class="btn-ver-bodega-badge" data-bodega-button-badge style="display:none; position:absolute; top:-7px; right:-7px; min-width:18px; height:18px; padding:0 5px; border-radius:999px; background:#dc2626; color:#fff; font-size:10px; font-weight:700; line-height:18px; text-align:center; box-shadow:0 2px 6px rgba(0,0,0,.25);">0</span>
                        </button>

                        {{-- <!-- Botón Editar -->
                        <button onclick="editarPedido({{ $orden->id }})" title="Editar Pedido" style="
                            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                            color: white;
                            border: none;
                            padding: 0.5rem;
                            border-radius: 6px;
                            cursor: pointer;
                            font-size: 1rem;
                            transition: all 0.3s ease;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            width: 36px;
                            height: 36px;
                            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
                        " onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(59, 130, 246, 0.3)'">
                            <i class="fas fa-edit"></i>
                        </button> --}}

                        <!-- Botón Aprobar (solo si está pendiente de aprobación Y no es solo EPP) -->
                        @if($estado === 'PENDIENTE_SUPERVISOR')
                            @if(!$orden->es_solo_epp)
                            <button class="btn-accion btn-accion--aprobar"
                                onclick="abrirModalAprobacion({{ $orden->id }}, '{{ str_replace('#', '', $numeroPedido) }}')"
                                title="Aprobar Pedido">
                                <i class="fas fa-check"></i>
                            </button>
                            @endif
                        @endif

                        <!-- Botón Anular (solo si está pendiente de aprobación Y no es solo EPP) -->
                        @if($estado === 'PENDIENTE_SUPERVISOR')
                            @if(!$orden->es_solo_epp)
                            <button class="btn-accion btn-accion--anular"
                                onclick="abrirModalAnulacion({{ $orden->id }}, '{{ $numeroPedido }}')"
                                title="Pasar a Revisión">
                                <i class="fas fa-ban"></i>
                            </button>
                            @endif
                        @endif

                        <!-- Botón Ocultar -->
                        <button class="btn-accion btn-accion--ocultar"
                            onclick="abrirModalOcultar({{ $orden->id }}, '{{ str_replace('#', '', $numeroPedido) }}')"
                            title="Ocultar Pedido">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>

                    <!-- Fecha -->
                    <div>
                        <span style="font-size: 0.85rem; color: #6b7280;">{{ \Carbon\Carbon::parse($orden->created_at)->format('d/m/Y H:i') }}</span>
                    </div>

                    <!-- Número -->
                    <div>
                        <span style="font-weight: 600; color: #1e5ba8;">#{{ $orden->numero_pedido ?? '-' }}</span>
                    </div>

                    <!-- Cliente -->
                    <div>
                        <span>{{ $orden->cliente }}</span>
                    </div>

                    <!-- Estado -->
                    <div>
                        @php
                            $estadoColors = [
                                'PENDIENTE_SUPERVISOR' => ['bg' => '#fff3cd', 'text' => '#856404', 'label' => 'Pendiente Supervisor'],
                                'PENDIENTE_INSUMOS' => ['bg' => '#d1ecf1', 'text' => '#0c5460', 'label' => 'Pendiente Insumos'],
                                'En Ejecución' => ['bg' => '#d4edda', 'text' => '#155724', 'label' => 'En Ejecución'],
                                'No iniciado' => ['bg' => '#e2e3e5', 'text' => '#383d41', 'label' => 'No Iniciado'],
                                'Entregado' => ['bg' => '#d4edda', 'text' => '#155724', 'label' => 'Entregado'],
                                'Finalizada' => ['bg' => '#d4edda', 'text' => '#155724', 'label' => 'Finalizada'],
                                'Anulada' => ['bg' => '#f8d7da', 'text' => '#721c24', 'label' => 'Anulada'],
                                'DEVUELTO_A_ASESORA' => ['bg' => '#f8d7da', 'text' => '#721c24', 'label' => 'Devuelto'],
                            ];
                            $estadoInfo = $estadoColors[$estado] ?? ['bg' => '#e2e3e5', 'text' => '#383d41', 'label' => $estado];
                        @endphp
                        <span style="background: {{ $estadoInfo['bg'] }}; color: {{ $estadoInfo['text'] }}; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">
                            {{ $estadoInfo['label'] }}
                        </span>
                    </div>

                    <!-- Novedades -->
                    <div>
                        @if($orden->novedades_count > 0)
                            <button class="btn-novedades" type="button"
                                data-orden-id="{{ $orden->id }}"
                                data-novedades="{{ json_encode($orden->novedades, JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) }}"
                                style="background: #e8f3ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; border: 1px solid #bfdbfe; cursor: pointer; transition: all 0.2s ease;">
                                {{ $orden->novedades_count }} novedades
                            </button>
                        @else
                            <span style="background: #f3f4f6; color: #9ca3af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap;">
                                Sin novedades
                            </span>
                        @endif
                    </div>

                    <!-- Asesora -->
                    <div>
                        <span>{{ $orden->asesora?->name ?? 'N/A' }}</span>
                    </div>

                    <!-- Forma Pago -->
                    <div>
                        <span>{{ $orden->forma_de_pago ?? 'N/A' }}</span>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

<!-- Paginación Personalizada -->
@if($ordenes->lastPage() > 1 || $ordenes->count() > 0)
    <div style="margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 8px; flex-wrap: wrap;">
        <!-- Botón Primera Página (<<) -->
        @if($ordenes->onFirstPage())
            <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                &laquo;&laquo;
            </button>
        @else
            <a href="{{ $ordenes->url(1) }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                &laquo;&laquo;
            </a>
        @endif

        <!-- Botón Anterior -->
        @if($ordenes->onFirstPage())
            <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                ← Anterior
            </button>
        @else
            <a href="{{ $ordenes->previousPageUrl() }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                ← Anterior
            </a>
        @endif

        <!-- Números de Página -->
        @if($ordenes->lastPage() > 1)
            @foreach($ordenes->getUrlRange(1, $ordenes->lastPage()) as $page => $url)
                @if($page == $ordenes->currentPage())
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
        @if($ordenes->hasMorePages())
            <a href="{{ $ordenes->nextPageUrl() }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                Siguiente →
            </a>
        @else
            <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                Siguiente →
            </button>
        @endif

        <!-- Botón Última Página (>>) -->
        @if($ordenes->currentPage() == $ordenes->lastPage())
            <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                &raquo;&raquo;
            </button>
        @else
            <a href="{{ $ordenes->url($ordenes->lastPage()) }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                &raquo;&raquo;
            </a>
        @endif

        <!-- Info de Página -->
        <span style="margin-left: 1rem; color: #666; font-size: 14px; font-weight: 500;">
            Página {{ $ordenes->currentPage() }} de {{ $ordenes->lastPage() }} | Total: {{ $ordenes->total() }} registros
        </span>
    </div>
@endif

