@php
    // Calcular número de pedido para búsqueda
    $numeroPedidoBusqueda = '';
    if (request('tipo') === 'logo') {
        if (get_class($pedido) === 'App\\Models\\LogoPedido') {
            $numeroPedidoBusqueda = $pedido->numero_pedido;
        } else {
            $numeroPedidoBusqueda = '#' . ($pedido->numero_pedido_mostrable ?? ($pedido->numero_pedido ?? '-'));
        }
    } else {
        if (get_class($pedido) === 'App\\Models\\LogoPedido') {
            $prod = $pedido->pedidoProduccion ?? null;
            if ($prod && isset($prod->numero_pedido)) {
                $numeroPedidoBusqueda = '#' . $prod->numero_pedido;
            } elseif (!empty($pedido->numero_pedido_cost)) {
                $numeroPedidoBusqueda = '#' . ltrim($pedido->numero_pedido_cost, '#');
            } else {
                $numeroPedidoBusqueda = $pedido->numero_pedido ?? '-';
            }
        } else {
            $numeroPedidoBusqueda = isset($pedido->numero_pedido) ? ('#' . $pedido->numero_pedido) : ('#' . ($pedido->numero_pedido_mostrable ?? '-'));
        }
    }
    $clienteBusqueda = $pedido->cliente ?? '-';
    
    // Preparar información de prendas para búsqueda inteligente
    $prendasInfo = [];
    $procesosInfo = [];
    if ($pedido->prendas && $pedido->prendas->count() > 0) {
        foreach ($pedido->prendas as $prenda) {
            $prendasInfo[] = [
                'nombre_prenda' => $prenda->nombre_prenda ?? '',
                'tela' => $prenda->tela ?? '',
                'color' => $prenda->color ?? '',
                'descripcion' => $prenda->descripcion ?? ''
            ];
            // Recopilar procesos
            if ($prenda->procesos && $prenda->procesos->count() > 0) {
                foreach ($prenda->procesos as $proceso) {
                    $procesosInfo[] = $proceso->descripcion ?? '';
                }
            }
        }
    }
    $prendasJson = json_encode($prendasInfo);
    $procesosJson = json_encode($procesosInfo);
@endphp

<div data-pedido-row 
     data-pedido-id="{{ $pedido->id }}"
     data-numero-pedido="{{ $numeroPedidoBusqueda }}" 
     data-cliente="{{ $clienteBusqueda }}"
     data-estado="{{ $pedido->estado ?? 'Pendiente' }}"
     data-forma-pago="{{ $pedido->forma_de_pago ?? '-' }}"
     data-asesor="{{ $pedido->asesora?->name ?? '-' }}"
     data-prenda-info="{{ $prendasJson }}"
     data-procesos-info="{{ $procesosJson }}"
     data-prendas="{{ json_encode($pedido->prendas ?? []) }}"
     style="
    display: grid;
    grid-template-columns: {{ request('tipo') === 'logo' ? '120px 120px 120px 140px 110px 120px 130px' : '120px 120px 120px 140px 110px 120px 130px 130px 130px' }};
    gap: 1.2rem;
    padding: 0.75rem 1rem;
    align-items: center;
    transition: all 0.3s ease;
    min-width: min-content;
    background: white;
    border-radius: 6px;
    margin-bottom: 0.75rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    position: relative;
    z-index: 1;
" onmouseover="this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.1)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 2px 4px rgba(0, 0, 0, 0.05)'; this.style.transform='translateY(0)'">

<!-- Acciones -->
<div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
    <!-- Botón Ver (con dropdown) -->
    @php
        $numeroPedido = get_class($pedido) === 'App\Models\LogoPedido' ? $pedido->numero_pedido : $pedido->numero_pedido;
        $pedidoId = $pedido->id;
        $tipoDocumento = get_class($pedido) === 'App\Models\LogoPedido' ? 'L' : ($pedido->cotizacion?->tipoCotizacion?->codigo ?? '');
        $esLogo = get_class($pedido) === 'App\Models\LogoPedido' ? '1' : '0';
        // logoPedidos está deprecado - no se usa
        $logoPedidoId = get_class($pedido) === 'App\Models\LogoPedido' ? $pedido->id : '';
    @endphp
    <button class="btn-ver-dropdown" data-menu-id="menu-ver-{{ str_replace('#', '', $numeroPedido) }}" data-pedido="{{ str_replace('#', '', $numeroPedido) }}" data-pedido-id="{{ $pedidoId }}" data-logo-pedido-id="{{ $logoPedidoId }}" data-tipo-cotizacion="{{ $tipoDocumento }}" data-es-logo="{{ $esLogo }}" title="Ver Opciones" style="
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
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
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
    " onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(37, 99, 235, 0.4)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(37, 99, 235, 0.3)'">
        <i class="fas fa-eye"></i>
    </button>

    <!-- Botón Anular (solo si no está anulado) -->
    @php
        $estado = get_class($pedido) === 'App\Models\LogoPedido' ? ($pedido->estado ?? 'pendiente') : ($pedido->estado ?? 'Pendiente');
    @endphp
    @if($estado !== 'Anulada' && $estado !== 'anulada')
    <button onclick="confirmarAnularPedido({{ $pedido->id }}, '{{ $numeroPedido }}')" title="Anular Pedido" style="
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
        box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
    " onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(245, 158, 11, 0.4)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(245, 158, 11, 0.3)'">
        <i class="fas fa-ban"></i>
    </button>
    @endif

    <!-- Botón Editar -->
    <button onclick="editarPedido({{ $pedido->id }})" title="Editar Pedido" style="
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
    </button>

    <!-- Botón Generar Script SQL -->
    @if(get_class($pedido) !== 'App\Models\LogoPedido')
    <button onclick="generarScriptSQL({{ $pedido->id }})" title="Generar Script SQL" style="
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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
        box-shadow: 0 2px 4px rgba(139, 92, 246, 0.3);
    " onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(139, 92, 246, 0.4)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(139, 92, 246, 0.3)'">
        <i class="fas fa-database"></i>
    </button>
    @endif

    <!-- Botón Confirmar Corrección (solo si está en DEVUELTO_A_ASESORA) -->
    @if(trim($estado) === 'DEVUELTO_A_ASESORA')
    <button onclick="confirmarCorreccionPedido({{ $pedido->id }}, '{{ $numeroPedido }}')" title="Confirmar Corrección" style="
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
    " onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.4)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(16, 185, 129, 0.3)'">
        <i class="fas fa-check"></i>
    </button>
    @endif
</div>

<!-- Estado -->
<div style="display: flex; align-items: center;">
    <span style="
        background: #fef3c7;
        color: #92400e;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-block;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 90px;
        white-space: nowrap;
    ">
        @php
            if (get_class($pedido) === 'App\Models\LogoPedido') {
                $estado = $pedido->estado ?? 'pendiente';
                echo str_replace('_', ' ', ucfirst($estado));
            } else {
                $estado = $pedido->estado ?? 'Pendiente';
                echo str_replace('_', ' ', $estado);
            }
        @endphp
    </span>
</div>

<!-- Área -->
<div style="display: flex; align-items: center;">
    @php
        $area = '-';
        
        // Verificar si es LogoPedido (tiene campo 'numero_pedido' pero no 'prendas')
        if (get_class($pedido) === 'App\Models\LogoPedido') {
            // Es un LogoPedido
            $area = $pedido->area ?? 'Creación de Orden';
        } else {
            // Es un PedidoProduccion
            $area = $pedido->area ?? '-';
        }
    @endphp
    <span style="
        background: #dbeafe;
        color: #1e40af;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-block;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100px;
        white-space: nowrap;
    ">
        {{ $area }}
    </span>
</div>

<!-- Pedido -->
<div style="display: flex; align-items: center; color: #2563eb; font-weight: 700; font-size: 0.8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
    @php
        // Si estamos en la pestaña de logo, mostramos el identificador de logo (#LOGO-...)
        if (request('tipo') === 'logo') {
            if (get_class($pedido) === 'App\\Models\\LogoPedido') {
                echo $pedido->numero_pedido; // #LOGO-xxxxx
            } else {
                // En lista 'logo' si hay un PedidoProduccion mostramos su numero_pedido_mostrable
                echo '#' . ($pedido->numero_pedido_mostrable ?? ($pedido->numero_pedido ?? '-'));
            }
        } else {
            // En la vista principal (Todos) y otras pestañas, mostrar el numero_pedido de pedidos_produccion
            if (get_class($pedido) === 'App\\Models\\LogoPedido') {
                // Intentar obtener el pedido de producción relacionado
                $prod = $pedido->pedidoProduccion ?? null;
                if ($prod && isset($prod->numero_pedido)) {
                    echo '#' . $prod->numero_pedido;
                } elseif (!empty($pedido->numero_pedido_cost)) {
                    // Fallback al campo numero_pedido_cost si existe
                    echo '#' . ltrim($pedido->numero_pedido_cost, '#');
                } else {
                    echo $pedido->numero_pedido ?? '-';
                }
            } else {
                // Es un PedidoProduccion
                echo isset($pedido->numero_pedido) ? ('#' . $pedido->numero_pedido) : ('#' . ($pedido->numero_pedido_mostrable ?? '-'));
            }
        }
    @endphp
</div>

<!-- Cliente -->
<div style="display: flex; align-items: center; color: #374151; font-size: 0.85rem; font-weight: 500; cursor: pointer; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" onclick="abrirModalCelda('Cliente', '{{ $pedido->cliente }}')" title="Click para ver completo">
    @php
        echo $pedido->cliente ?? '-';
    @endphp
</div>

<!-- Novedades (solo si no es logo) -->
@if(request('tipo') !== 'logo')
<div style="color: #374151; font-weight: 500; font-size: 0.8rem; text-align: left; white-space: normal; word-wrap: break-word; max-width: 150px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; cursor: pointer;" onclick="abrirModalNovedades('{{ $pedido->numero_pedido }}', `{{ addslashes($pedido->novedades ?? '') }}`)" title="Click para ver completo">
    @php
        if (get_class($pedido) === 'App\Models\LogoPedido') {
            echo '<span style="color: #3b82f6;">LOGO</span>';
        } else {
            $novedades = $pedido->novedades ?? null;
            if (!empty($novedades)) {
                echo $novedades;
            } else {
                echo '<span style="color: #d1d5db;">-</span>';
            }
        }
    @endphp
</div>
@endif

<!-- Forma Pago -->
<div style="display: flex; align-items: center; color: #374151; font-size: 0.8rem; cursor: pointer; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" onclick="abrirModalCelda('Forma de Pago', '{{ $pedido->forma_de_pago ?? '-' }}')" title="Click para ver completo">
    {{ $pedido->forma_de_pago ?? '-' }}
</div>

<!-- Fecha Creación -->
<div style="display: flex; align-items: center; color: #6b7280; font-size: 0.75rem; white-space: nowrap;">
    @php
        if (get_class($pedido) === 'App\Models\LogoPedido') {
            echo $pedido->created_at ? $pedido->created_at->format('d/m/Y') : '-';
        } else {
            echo $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('d/m/Y') : '-';
        }
    @endphp
</div>

<!-- Fecha Estimada de Entrega (solo si no es logo) -->
@if(request('tipo') !== 'logo')
<div style="display: flex; align-items: center; color: #6b7280; font-size: 0.75rem; white-space: nowrap;">
    @php
        if (get_class($pedido) === 'App\Models\LogoPedido') {
            echo '-'; // LogoPedido no tiene fecha estimada
        } else {
            if ($pedido->fecha_estimada_de_entrega) {
                try {
                    $fecha = \Carbon\Carbon::parse($pedido->fecha_estimada_de_entrega);
                    echo $fecha->format('d/m/Y');
                } catch (\Exception $e) {
                    echo $pedido->fecha_estimada_de_entrega;
                }
            } else {
                echo '-';
            }
        }
    @endphp
</div>
@endif

</div>
