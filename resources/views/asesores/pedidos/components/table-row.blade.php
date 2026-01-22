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
     data-numero-pedido="{{ $numeroPedidoBusqueda }}" 
     data-cliente="{{ $clienteBusqueda }}"
     data-prenda-info="{{ $prendasJson }}"
     data-procesos-info="{{ $procesosJson }}"
     style="
    display: grid;
    grid-template-columns: {{ request('tipo') === 'logo' ? '140px 140px 160px 180px 190px 260px 160px 170px' : '120px 120px 120px 140px 110px 170px 160px 120px 130px 130px' }};
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
    <button onclick="confirmarAnularPedido({{ $numeroPedido }})" title="Anular Pedido" style="
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

    <!-- Botón Eliminar -->
    <button onclick="eliminarPedido({{ $pedido->id }})" title="Eliminar Pedido" style="
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
        box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
    " onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(239, 68, 68, 0.4)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(239, 68, 68, 0.3)'">
        <i class="fas fa-trash"></i>
    </button>
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
                echo ucfirst($pedido->estado ?? 'pendiente');
            } else {
                echo $pedido->estado ?? 'Pendiente';
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

<!-- Descripción -->
@php
    $descripcionConTallas = '';
    
    // Verificar si es LogoPedido
    if (get_class($pedido) === 'App\Models\LogoPedido') {
        // Para LogoPedido, mostrar el campo descripción directamente
        $descripcionConTallas = $pedido->descripcion ?? 'Logo personalizado';
    } else {
        // Para PedidoProduccion, usar descripcion_prendas tal como viene del backend
        // Ya incluye formatos correctos (con o sin "PRENDA X:" según el total de prendas)
        $descripcionConTallas = $pedido->descripcion_prendas ?? '';
    }
    
    if (empty($descripcionConTallas)) {
        $descripcionConTallas = get_class($pedido) === 'App\Models\LogoPedido' ? 'Logo personalizado' : '-';
    }
@endphp
<div style="display: flex; align-items: center; color: #6b7280; font-size: 0.8rem; cursor: pointer; max-width: {{ request('tipo') === 'logo' ? '220px' : '130px' }}; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" onclick="abrirModalDescripcion({{ $pedido->id }}, '{{ get_class($pedido) === 'App\\Models\\LogoPedido' ? 'logo' : 'prenda' }}')" title="Click para ver completo">
    @php
        if (get_class($pedido) === 'App\Models\LogoPedido') {
            echo 'Logo personalizado <span style="color: #3b82f6; font-weight: 600;">...</span>';
        } elseif ($pedido->prendas && $pedido->prendas->count() > 0) {
            $prendasInfo = $pedido->prendas->map(function($prenda) {
                return $prenda->nombre_prenda ?? 'Prenda sin nombre';
            })->unique()->toArray();
            $descripcion = !empty($prendasInfo) ? implode(', ', $prendasInfo) : '-';
            echo $descripcion . ' <span style="color: #3b82f6; font-weight: 600;">...</span>';
        } else {
            echo '-';
        }
    @endphp
</div>

<!-- Cantidad (solo si no es logo) -->
@if(request('tipo') !== 'logo')
<div style="color: #374151; font-weight: 600; font-size: 0.8rem; text-align: center; white-space: nowrap;">
    @php
        if (get_class($pedido) === 'App\Models\LogoPedido') {
            echo '<span style="color: #3b82f6;">LOGO</span>';
        } elseif ($pedido->prendas->count() > 0) {
            // Calcular cantidad real desde cantidad_talla de prendas
            $cantidadReal = 0;
            foreach ($pedido->prendas as $prenda) {
                $cantidadReal += $prenda->cantidad_total;
            }
            
            // Si la cantidad real difiere de cantidad_total, mostrar la real
            $cantidadMostrada = $cantidadReal;
            
            // Agregar indicador visual si hay discrepancia
            $indicador = '';
            if ($cantidadMostrada !== $pedido->cantidad_total && $pedido->cantidad_total > 0) {
                $indicador = ' <span style="color: #ef4444; font-weight: bold; cursor: help;" title="Ajustada de ' . $pedido->cantidad_total . '">*</span>';
            }
            
            echo '<span>' . $cantidadMostrada . ' und' . $indicador . '</span>';
        } else {
            echo '<span style="color: #d1d5db;">-</span>';
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
