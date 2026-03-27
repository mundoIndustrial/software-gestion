@php
    $numeroPedido = !empty($pedido->numero_pedido) ? ('#' . $pedido->numero_pedido) : ('#' . $pedido->id);
    $cliente = $pedido->cliente ?? '-';
    $estado = $pedido->estado ?? 'Pendiente';
    $estadoTexto = str_replace('_', ' ', $estado);
    $area = $pedido->area ?? '-';
    $formaPago = $pedido->forma_de_pago ?? ($pedido->forma_pago ?? '-');

    $pedidoPrendas = collect();
    if (isset($pedido->prendas)) {
        if ($pedido->prendas instanceof \Illuminate\Support\Collection) {
            $pedidoPrendas = $pedido->prendas;
        } elseif (is_array($pedido->prendas)) {
            $pedidoPrendas = collect($pedido->prendas);
        }
    }

    $prendasInfo = [];
    $procesosInfo = [];
    foreach ($pedidoPrendas as $prenda) {
        $prendasInfo[] = [
            'nombre_prenda' => $prenda->nombre_prenda ?? '',
            'tela' => $prenda->tela ?? '',
            'color' => $prenda->color ?? '',
            'descripcion' => $prenda->descripcion ?? ''
        ];

        if (isset($prenda->procesos) && $prenda->procesos instanceof \Illuminate\Support\Collection) {
            foreach ($prenda->procesos as $proceso) {
                $procesosInfo[] = $proceso->descripcion ?? '';
            }
        }
    }

    $fechaCreacionRaw = $pedido->created_at ?? ($pedido->fecha_creacion ?? null);
    $fechaCreacion = '-';
    if (!empty($fechaCreacionRaw)) {
        try {
            $fechaCreacion = $fechaCreacionRaw instanceof \Carbon\CarbonInterface
                ? $fechaCreacionRaw->format('d/m/Y')
                : \Carbon\Carbon::parse($fechaCreacionRaw)->format('d/m/Y');
        } catch (\Throwable $e) {
            $fechaCreacion = '-';
        }
    }

    $fechaEstimadaRaw = $pedido->fecha_estimada_de_entrega ?? ($pedido->fecha_estimada ?? null);
    $fechaEstimada = '-';
    if (!empty($fechaEstimadaRaw)) {
        try {
            $fechaEstimada = \Carbon\Carbon::parse($fechaEstimadaRaw)->format('d/m/Y');
        } catch (\Throwable $e) {
            $fechaEstimada = (string) $fechaEstimadaRaw;
        }
    }

    $badgeStyles = match($estado) {
        'Pendiente' => 'background: #6b7280;',
        'No iniciado' => 'background: #6b7280;',
        'En Ejecución' => 'background: #3b82f6;',
        'Entregado' => 'background: #10b981;',
        'Anulada' => 'background: #f59e0b;',
        'PENDIENTE_SUPERVISOR' => 'background: #f97316;',
        'PENDIENTE_INSUMOS' => 'background: #a855f7;',
        'pendiente_cartera' => 'background: #6366f1;',
        'RECHAZADO_CARTERA' => 'background: #ef4444;',
        'DEVUELTO_A_ASESORA' => 'background: #eab308;',
        default => 'background: #6b7280;'
    };
@endphp

<div data-pedido-row
     data-pedido-id="{{ $pedido->id }}"
     data-numero-pedido="{{ $numeroPedido }}"
     data-cliente="{{ $cliente }}"
     data-estado="{{ $estado }}"
     data-forma-pago="{{ $formaPago }}"
     data-prenda-info="{{ json_encode($prendasInfo) }}"
     data-procesos-info="{{ json_encode($procesosInfo) }}"
     data-prendas="{{ json_encode($pedidoPrendas) }}"
     style="
    display: grid;
    grid-template-columns: 140px 170px 170px 140px 170px 200px 160px 170px 170px;
    gap: 1.8rem;
    padding: 0.75rem 1.25rem;
    align-items: center;
    transition: all 0.3s ease;
    min-width: min-content;
    background: white;
    border-radius: 6px;
    margin-bottom: 0.75rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
" onmouseover="this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.1)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 2px 4px rgba(0, 0, 0, 0.05)'; this.style.transform='translateY(0)'">

    <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
        @php
            $tipoDocumento = '';
            if (isset($pedido->cotizacion) && is_object($pedido->cotizacion)) {
                $tipoDocumento = $pedido->cotizacion->tipoCotizacion->codigo ?? '';
            }
        @endphp

        <button class="btn-ver-dropdown"
                data-menu-id="menu-ver-{{ str_replace('#', '', $numeroPedido) }}"
                data-pedido="{{ str_replace('#', '', $numeroPedido) }}"
                data-pedido-id="{{ $pedido->id }}"
                data-logo-pedido-id=""
                data-tipo-cotizacion="{{ $tipoDocumento }}"
                data-es-logo="0"
                title="Ver Opciones"
                style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; border: none; padding: 0.5rem; border-radius: 6px; cursor: pointer; width: 36px; height: 36px;">
            <i class="fas fa-eye"></i>
        </button>

        @if($estado !== 'Anulada' && $estado !== 'anulada')
            <button onclick="confirmarAnularPedido({{ $pedido->id }}, '{{ $numeroPedido }}')"
                    title="Anular Pedido"
                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 0.5rem; border-radius: 6px; cursor: pointer; width: 36px; height: 36px;">
                <i class="fas fa-ban"></i>
            </button>
        @endif

        <button onclick="editarPedido({{ $pedido->id }})"
                title="Editar Pedido"
                style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 0.5rem; border-radius: 6px; cursor: pointer; width: 36px; height: 36px;">
            <i class="fas fa-edit"></i>
        </button>

        @if(trim($estado) === 'DEVUELTO_A_ASESORA')
            <button onclick="confirmarCorreccionPedido({{ $pedido->id }}, '{{ $numeroPedido }}')"
                    title="Confirmar Correcion"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 0.5rem; border-radius: 6px; cursor: pointer; width: 36px; height: 36px;">
                <i class="fas fa-check"></i>
            </button>
        @endif
    </div>

    <div class="table-cell">
        <span class="estado-badge" style="{{ $badgeStyles }} color: white; padding: 0.375rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; display: inline-block; white-space: nowrap;">
            {{ $estadoTexto }}
        </span>
    </div>

    <div style="display: flex; align-items: center;">
        <span style="background: #dbeafe; color: #1e40af; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.7rem; font-weight: 600; display: inline-block; overflow: hidden; text-overflow: ellipsis; max-width: 100px; white-space: nowrap;">
            {{ $area }}
        </span>
    </div>

    <div style="display: flex; align-items: center; color: #2563eb; font-weight: 700; font-size: 0.8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
        {{ $numeroPedido }}
    </div>

    <div style="display: flex; align-items: center; color: #374151; font-size: 0.85rem; font-weight: 500; cursor: pointer; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
         onclick="abrirModalCelda('Cliente', '{{ $cliente }}')"
         title="Click para ver completo">
        {{ $cliente }}
    </div>

    <div style="color: #374151; font-weight: 500; font-size: 0.8rem; text-align: left; white-space: normal; word-wrap: break-word; max-width: 150px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; cursor: pointer;"
         onclick="abrirModalNovedades('{{ $pedido->numero_pedido }}', `{{ addslashes($pedido->novedades ?? '') }}`)"
         title="Click para ver completo">
        @if(!empty($pedido->novedades))
            {{ $pedido->novedades }}
        @else
            <span style="color: #d1d5db;">-</span>
        @endif
    </div>

    <div style="display: flex; align-items: center; color: #374151; font-size: 0.8rem; cursor: pointer; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
         onclick="abrirModalCelda('Forma de Pago', '{{ $formaPago }}')"
         title="Click para ver completo">
        {{ $formaPago }}
    </div>

    <div style="display: flex; align-items: center; color: #6b7280; font-size: 0.75rem; white-space: nowrap;">
        {{ $fechaCreacion }}
    </div>

    <div style="display: flex; align-items: center; color: #6b7280; font-size: 0.75rem; white-space: nowrap;">
        {{ $fechaEstimada }}
    </div>
</div>
