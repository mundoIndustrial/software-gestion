@php
    $columnTemplate = '60px 220px 130px 140px 120px 220px 150px 150px 150px 150px 150px 150px';
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

    .sp-date-cell {
        white-space: nowrap;
        display: inline-block;
        font-size: 0.85rem;
        color: #6b7280;
    }
</style>
<!-- Tabla de Ã“rdenes - DiseÃ±o asesores/pedidos -->
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
                <span>Aprob. Cartera</span></div>
            <div class="th-wrapper" style="display: flex; align-items: center;">
                <span>DÃ­as Restantes</span>
            </div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>NÃºmero</span></div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>Cliente</span></div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>Asesora</span></div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>Estado</span></div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>Novedades</span>
            </div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>Forma Pago</span></div>
            <div class="th-wrapper" style="display: flex; align-items: center;">
                <span>Aprob. Supervisor</span>
            </div>
            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                <span>Fecha</span></div>
        </div>

        <!-- Filas - Se cargan dinÃ¡micamente con JavaScript -->
        <div data-ordenes-body style="display: flex; flex-direction: column;">
            <!-- Las Ã³rdenes se cargarÃ¡n aquÃ­ vÃ­a API -->
            @if($ordenes->isEmpty())
            <div style="padding: 2rem; text-align: center; color: #9ca3af;">
                <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 20px; height: 20px; border: 2px solid #e5e7eb; border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    Cargando Ã³rdenes...
                </div>
            </div>
            @endif

            @foreach($ordenes as $orden)
                @php
                    $estaSeleccionado = in_array($orden->id, $pedidosSeleccionados);
                    $estadoFila = strtoupper((string) ($orden->estado ?? ''));
                    $pendientesEntregaFila = is_numeric($orden->prendas_pendientes_entrega_count ?? null)
                        ? (int) $orden->prendas_pendientes_entrega_count
                        : null;
                    $estaEntregadoFila = $pendientesEntregaFila !== null
                        ? $pendientesEntregaFila <= 0
                        : in_array($estadoFila, ['ENTREGADO', 'FINALIZADA', 'FINALIZADO'], true);
                    $rowBg = $estaSeleccionado
                        ? ($estaEntregadoFila ? '#86efac' : '#d1d5db')
                        : ($estaEntregadoFila ? '#dcfce7' : 'white');
                    $rowBgHover = $estaEntregadoFila ? '#bbf7d0' : '#f9fafb';
                    $rowBgSelected = $estaEntregadoFila ? '#86efac' : '#d1d5db';
                    $rowBgUnselected = $estaEntregadoFila ? '#dcfce7' : 'white';
                    $diasRestantes = null;
                    $fechaEstimadaEntrega = null;
                    if (!empty($orden->created_at) && !empty($orden->dia_de_entrega)) {
                        try {
                            $diasHabilesTranscurridos = \App\Services\CalculadorDiasService::calcularDiasHabiles(
                                \Carbon\Carbon::parse($orden->created_at)->startOfDay(),
                                now()->startOfDay()
                            );
                            $diasRestantes = max(0, ((int) $orden->dia_de_entrega) - (int) $diasHabilesTranscurridos);
                        } catch (\Throwable $e) {
                            $diasRestantes = null;
                        }
                    }

                    $fechaEstimadaRaw = $orden->fecha_estimada_de_entrega
                        ?? $orden->fecha_estimada_entrega
                        ?? $orden->fecha_estimada
                        ?? null;
                    if (!empty($fechaEstimadaRaw)) {
                        try {
                            $fechaEstimadaEntrega = \Carbon\Carbon::parse($fechaEstimadaRaw)
                                ->timezone('America/Bogota')
                                ->format('d/m/Y');
                        } catch (\Throwable $e) {
                            $fechaEstimadaEntrega = null;
                        }
                    }

                    // Fallback: si falta dia_de_entrega pero existe fecha estimada,
                    // calcular restantes directamente desde hoy hasta la fecha estimada.
                    if ($diasRestantes === null && !empty($fechaEstimadaRaw)) {
                        try {
                            $diasRestantes = \App\Services\CalculadorDiasService::calcularDiasHabiles(
                                now()->startOfDay(),
                                \Carbon\Carbon::parse($fechaEstimadaRaw)->startOfDay()
                            );
                        } catch (\Throwable $e) {
                            $diasRestantes = null;
                        }
                    }
                @endphp
                <div class="sp-orders-grid" style="
                    padding: 1rem;
                    border-bottom: 1px solid #e5e7eb;
                    align-items: center;
                    background: {{ $rowBg }};
                    transition: background 0.2s ease;
                "
                onmouseover="if (!this.dataset.seleccionado || this.dataset.seleccionado === 'false') this.style.background='{{ $rowBgHover }}'"
                onmouseout="this.style.background = (this.dataset.seleccionado === 'true') ? '{{ $rowBgSelected }}' : '{{ $rowBgUnselected }}'"
                data-seleccionado="{{ $estaSeleccionado ? 'true' : 'false' }}"
                data-entregado="{{ $estaEntregadoFila ? 'true' : 'false' }}"
                data-pedido-row="true"
                data-pedido-id="{{ $orden->id }}"
                >

                    <!-- Checkbox de selecciÃ³n -->
                    <div style="display: flex; align-items: center; justify-content: center;">
                        <input type="checkbox" class="pedido-checkbox" data-pedido-id="{{ $orden->id }}" title="Seleccionar pedido" style="width: 18px; height: 18px; cursor: pointer;" {{ $estaSeleccionado ? 'checked' : '' }}>
                    </div>

                    <!-- Acciones -->
                    <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                        <!-- BotÃ³n Ver (con dropdown) -->
                        @php
                            $numeroPedido = $orden->numero_pedido ?? 'sin-numero';
                            $pedidoId = $orden->id;
                            $estado = $orden->estado ?? 'Pendiente';
                            $pendientesEntrega = is_numeric($orden->prendas_pendientes_entrega_count ?? null)
                                ? (int) $orden->prendas_pendientes_entrega_count
                                : null;
                            $canBulkDeliver = $pendientesEntrega !== null
                                ? $pendientesEntrega > 0
                                : !in_array(strtoupper((string) $estado), ['ENTREGADO', 'FINALIZADA', 'FINALIZADO'], true);
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

                        <!-- BotÃ³n Aprobar (solo si estÃ¡ pendiente de aprobaciÃ³n Y no es solo EPP) -->
                        @if($estado === 'PENDIENTE_SUPERVISOR')
                            @if(!$orden->es_solo_epp)
                            <button class="btn-accion btn-accion--aprobar"
                                data-action="aprobar"
                                data-pedido-id="{{ $orden->id }}"
                                data-pedido-numero="{{ str_replace('#', '', $numeroPedido) }}"
                                title="Aprobar Pedido">
                                <i class="fas fa-check"></i>
                            </button>
                            @endif
                        @endif

                        <!-- BotÃ³n Anular (solo si estÃ¡ pendiente de aprobaciÃ³n Y no es solo EPP) -->
                        @if($estado === 'PENDIENTE_SUPERVISOR')
                            @if(!$orden->es_solo_epp)
                            <button class="btn-accion btn-accion--anular"
                                data-action="anular"
                                data-pedido-id="{{ $orden->id }}"
                                data-pedido-numero="{{ $numeroPedido }}"
                                title="Pasar a RevisiÃ³n">
                                <i class="fas fa-ban"></i>
                            </button>
                            @endif
                        @endif

                        <button class="btn-accion {{ $canBulkDeliver ? '' : 'btn-accion--disabled' }}"
                            data-action="entregar"
                            data-pedido-id="{{ $orden->id }}"
                            data-pedido-numero="{{ str_replace('#', '', $numeroPedido) }}"
                            title="{{ $canBulkDeliver ? 'Marcar todas las prendas entregadas' : 'Todas las prendas ya fueron entregadas' }}"
                            {{ $canBulkDeliver ? '' : 'disabled aria-disabled=true' }}
                            style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); color: #ffffff;">
                            <i class="fas fa-check-double"></i>
                        </button>

                        <!-- BotÃ³n Ocultar -->
                        <button class="btn-accion btn-accion--ocultar"
                            data-action="ocultar"
                            data-pedido-id="{{ $orden->id }}"
                            data-pedido-numero="{{ str_replace('#', '', $numeroPedido) }}"
                            title="Ocultar Pedido">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>

                    <!-- Fecha aprobacion cartera -->
                    <div>
                        <span class="sp-date-cell">
                            {{ $orden->aprobado_por_cartera_en ? \Carbon\Carbon::parse($orden->aprobado_por_cartera_en)->timezone('America/Bogota')->format('d/m/Y h:i A') : '-' }}
                        </span>
                    </div>

                    <!-- DÃ­as restantes (desde Aprob. Cartera) -->
                    <div>
                        @if($diasRestantes !== null)
                        <span style="display: inline-flex; flex-direction: column; line-height: 1.1; color: #dc2626; font-weight: 700; font-size: 0.78rem;">
                            <span>{{ $diasRestantes }} dÃ­as</span>
                            <span>hÃ¡biles restantes</span>
                            <span style="margin-top: 0.2rem; color: #6b7280; font-weight: 600; font-size: 0.72rem;">Est.: {{ $fechaEstimadaEntrega ?? '-' }}</span>
                        </span>
                    @else
                        <span style="display: inline-flex; flex-direction: column; line-height: 1.1; color: #6b7280; font-weight: 600; font-size: 0.78rem;">
                            <span>-</span>
                            <span style="margin-top: 0.2rem; font-size: 0.72rem;">Est.: {{ $fechaEstimadaEntrega ?? '-' }}</span>
                        </span>
                    @endif
                    </div>

                    <!-- Numero -->
                    <div>
                        <span style="font-weight: 600; color: #1e5ba8;">#{{ $orden->numero_pedido ?? '-' }}</span>
                    </div>

                    <!-- Cliente -->
                    <div>
                        <span>{{ $orden->cliente }}</span>
                    </div>

                    <!-- Asesora -->
                    <div>
                        <span>{{ $orden->asesora?->name ?? 'N/A' }}</span>
                    </div>

                    <!-- Estado -->
                    <div>
                        @php
                            $estadoColors = [
                                'PENDIENTE_SUPERVISOR' => ['bg' => '#fff3cd', 'text' => '#856404', 'label' => 'Pendiente Supervisor'],
                                'PENDIENTE_INSUMOS' => ['bg' => '#d1ecf1', 'text' => '#0c5460', 'label' => 'Pendiente Insumos'],
                                'En EjecuciÃ³n' => ['bg' => '#d4edda', 'text' => '#155724', 'label' => 'En EjecuciÃ³n'],
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
                                data-has-novedades="1"
                                data-novedades-count="{{ (int) $orden->novedades_count }}"
                                style="background: #e8f3ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; border: 1px solid #bfdbfe; cursor: pointer; transition: all 0.2s ease;">
                                {{ $orden->novedades_count }} novedades
                            </button>
                        @else
                            <span style="background: #f3f4f6; color: #9ca3af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap;">
                                Sin novedades
                            </span>
                        @endif
                    </div>

                    <!-- Forma Pago -->
                    <div>
                        <span>{{ $orden->forma_de_pago ?? 'N/A' }}</span>
                    </div>

                    <!-- Fecha aprobacion supervisor -->
                    <div>
                        <span class="sp-date-cell">
                            {{ $orden->aprobado_por_supervisor_en ? \Carbon\Carbon::parse($orden->aprobado_por_supervisor_en)->timezone('America/Bogota')->format('d/m/Y h:i A') : '-' }}
                        </span>
                    </div>

                    <!-- Fecha -->
                    <div>
                        <span class="sp-date-cell">{{ \Carbon\Carbon::parse($orden->created_at)->timezone('America/Bogota')->format('d/m/Y h:i A') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- PaginaciÃ³n - Se actualiza dinÃ¡micamente con JavaScript -->
<div data-ordenes-pagination style="margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 8px; flex-wrap: wrap;">
    <!-- Los botones de paginaciÃ³n se cargarÃ¡n aquÃ­ vÃ­a JavaScript -->
</div>

@if(false)
    <div style="margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 8px; flex-wrap: wrap;">
        <!-- BotÃ³n Primera PÃ¡gina (<<) -->
        @if($ordenes->onFirstPage())
            <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                &laquo;&laquo;
            </button>
        @else
            <a href="{{ $ordenes->url(1) }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                &laquo;&laquo;
            </a>
        @endif

        <!-- BotÃ³n Anterior -->
        @if($ordenes->onFirstPage())
            <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                â† Anterior
            </button>
        @else
            <a href="{{ $ordenes->previousPageUrl() }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                â† Anterior
            </a>
        @endif

        <!-- NÃºmeros de PÃ¡gina -->
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

        <!-- BotÃ³n Siguiente -->
        @if($ordenes->hasMorePages())
            <a href="{{ $ordenes->nextPageUrl() }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                Siguiente â†’
            </a>
        @else
            <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                Siguiente â†’
            </button>
        @endif

        <!-- BotÃ³n Ãšltima PÃ¡gina (>>) -->
        @if($ordenes->currentPage() == $ordenes->lastPage())
            <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                &raquo;&raquo;
            </button>
        @else
            <a href="{{ $ordenes->url($ordenes->lastPage()) }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                &raquo;&raquo;
            </a>
        @endif

        <!-- Info de PÃ¡gina -->
        <span style="margin-left: 1rem; color: #666; font-size: 14px; font-weight: 500;">
            PÃ¡gina {{ $ordenes->currentPage() }} de {{ $ordenes->lastPage() }} | Total: {{ $ordenes->total() }} registros
        </span>
    </div>
@endif

<!-- Estilos para animaciÃ³n de carga y paginaciÃ³n -->
<style>
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .pagination-btn {
        padding: 10px 12px;
        border: 1.5px solid #d1d5db;
        border-radius: 6px;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-width: 44px;
        height: 44px;
        color: #374151;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .pagination-btn:hover:not(:disabled) {
        border-color: #3b82f6;
        background: #eff6ff;
        color: #3b82f6;
        box-shadow: 0 4px 6px rgba(59, 130, 246, 0.1);
        transform: translateY(-1px);
    }

    .pagination-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        background: #f3f4f6;
        border-color: #e5e7eb;
    }

    .pagination-btn.active {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border-color: #2563eb;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
</style>



