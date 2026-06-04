@php
    $estadoControlCalidadBodega = strtolower((string) ($bodega['estado_control_calidad'] ?? 'pendiente'));
    $ccEsCompleto = $estadoControlCalidadBodega === 'completo';
    $ccEsParcial = $estadoControlCalidadBodega === 'parcial';
    $ccIcono = $ccEsCompleto ? 'undo' : ($ccEsParcial ? 'edit' : 'check_circle');
    $ccTexto = $ccEsCompleto ? 'DESHACER' : ($ccEsParcial ? 'EDITAR C.C' : 'PASAR A C.C');
    $ccArea = ($ccEsCompleto || $ccEsParcial) ? 'Control Calidad' : 'Costura';
@endphp
<div class="orden-card-simple card-bodega"
     data-numero="{{ $bodega['consecutivo'] }}"
     data-prenda="{{ strtolower((string) $prenda['nombre_prenda']) }}"
     data-prenda-id="{{ $prenda['prenda_id'] }}"
     data-prenda-bodega-id="{{ $bodega['prenda_bodega_id'] ?? ($prenda['prenda_bodega_id'] ?? '') }}"
     data-cliente="{{ strtolower((string) $prenda['cliente']) }}"
     data-tipo-recibo="corte-para-bodega"
     data-numero-recibo="{{ $bodega['consecutivo'] }}"
     data-recibo-id="{{ $bodega['recibo_id'] ?? '' }}"
     data-tallas='@json($bodega["tallas"] ?? [])'
     data-sin-encargado-costura="{{ $bodega['encargado_costura'] === '' ? '1' : '0' }}"
     data-sin-encargado-reflectivo="0"
     data-search-text="{{ $bodega['search_text'] }}">
    <div class="orden-body">
        <div class="vista-resumen-card" onclick="event.stopPropagation();">
            <div class="vista-encargados-row">
                <div class="vista-encargado-pill vista-encargado-pill-corte">
                    <span class="vista-encargado-pill-label">Corte</span>
                    <span class="vista-encargado-pill-name">CORTADORES</span>
                </div>
                <div class="vista-encargado-pill vista-encargado-pill-costura">
                    <span class="vista-encargado-pill-label">Costura</span>
                    <span class="vista-encargado-pill-name">{{ $bodega['texto_encargado_costura'] }}</span>
                </div>
            </div>
            <div class="vista-estado-linea">
                <span class="vista-estado-etiqueta">Estado:</span>
                <span class="badge-completado-corte {{ $bodega['mostrar_como_deshacer'] ? 'is-on' : '' }}">
                    {{ $bodega['mostrar_como_deshacer'] ? 'COMPLETADO COSTURA' : 'PENDIENTE COSTURA' }}
                </span>
            </div>
        </div>

        <div class="orden-left">
            <div class="orden-top">
                <div class="orden-numero-section">
                    <h4 class="orden-numero">#{{ $bodega['consecutivo'] }}</h4>
                    <span class="estado-badge pendiente" data-estado="recibo-bodega">BODEGA</span>
                </div>
                <span class="badge-completado-corte is-on">CORTE-PARA-BODEGA</span>
            </div>

            <div class="orden-cliente">
                <p class="cliente-label">CLIENTE</p>
                <p class="cliente-name">BODEGA</p>
            </div>

            <div class="orden-prendas">
                <p class="prendas-label">{!! nl2br(e($bodega['texto_prenda'])) !!}</p>
            </div>

            <div class="mobile-ver-recibo-section">
                <button type="button" class="btn-ver-recibos mobile-under-state"
                        onclick="abrirDetallesRecibos(@js($bodega['consecutivo']), {{ $prenda['prenda_id'] ?? 'null' }}, @js($bodega['texto_prenda']), 'CORTE-PARA-BODEGA', null, @js($bodega['consecutivo']), {{ $bodega['recibo_id'] ?? 'null' }}); return false;">
                    <span class="material-symbols-rounded">visibility</span>
                    VER RECIBO
                </button>
            </div>

            <div class="orden-buttons">
                @if(!$bodega['tiene_parciales'])
                    <button type="button" class="btn-pasar-costura {{ $bodega['mostrar_como_deshacer'] ? 'btn-deshacer-costura' : '' }}"
                            data-visible-filtro="bodega"
                            id="btn-costura-bodega-{{ $prenda['prenda_id'] }}-{{ $bodega['consecutivo'] }}"
                            data-pedido-id="{{ $bodega['pedido_id_accion'] }}"
                            data-numero-pedido="{{ $bodega['consecutivo'] }}"
                            data-prenda-id="{{ $prenda['prenda_id'] }}"
                            data-prenda-bodega-id="{{ $prenda['prenda_id'] }}"
                            data-nombre="{{ $bodega['texto_prenda'] }}"
                            data-tipo-recibo="CORTE-PARA-BODEGA"
                            data-recibo="{{ $bodega['consecutivo'] }}"
                            data-area="Costura"
                            data-proceso-id="{{ $bodega['proceso_id_costura'] ?? '' }}"
                            data-encargado-costura="{{ $bodega['encargado_costura'] }}"
                            data-parcial-id=""
                            onclick="manejarPasarACostura(this); return false;">
                        <span class="material-symbols-rounded">{{ $bodega['mostrar_como_deshacer'] ? 'undo' : 'checkroom' }}</span>
                        {{ $bodega['mostrar_como_deshacer'] ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                    </button>

                    <button class="btn-pasar-cc"
                            data-visible-filtro="bodega"
                            id="btn-cc-bodega-{{ $prenda['prenda_id'] }}-{{ $bodega['consecutivo'] }}"
                            data-recibo-id="{{ $bodega['recibo_id'] ?? '' }}"
                            data-pedido-id="{{ $bodega['pedido_id_accion'] }}"
                            data-prenda-id="{{ $prenda['prenda_id'] }}"
                            data-prenda-bodega-id="{{ $prenda['prenda_id'] }}"
                            data-nombre="{{ $bodega['texto_prenda'] }}"
                            data-tipo-recibo="CORTE-PARA-BODEGA"
                            data-recibo="{{ $bodega['consecutivo'] }}"
                            data-area="{{ $ccArea }}"
                            data-proceso-id="{{ $bodega['proceso_id_costura'] ?? '' }}"
                            data-estado-control-calidad="{{ $bodega['estado_control_calidad'] ?? '' }}"
                            data-tallas-control-calidad='@json($bodega["tallas_control_calidad"] ?? [])'
                            type="button"
                            onclick="pasarAControlCalidad(this); return false;">
                        <span class="material-symbols-rounded">{{ $ccIcono }}</span>
                        {{ $ccTexto }}
                    </button>
                @endif

                @if($bodega['tiene_parciales'] && $bodega['recibo_id'])
                    @component('components.botones.ver-distribucion', [
                        'filtro' => 'bodega',
                        'prendaId' => $prenda['prenda_id'],
                        'reciboId' => $bodega['recibo_id'],
                        'numeroRecibo' => $bodega['consecutivo'],
                        'tipoRecibo' => 'CORTE-PARA-BODEGA',
                    ])@endcomponent
                    @component('components.botones.editar-encargados', [
                        'filtro' => 'bodega',
                        'prendaId' => $prenda['prenda_id'],
                        'reciboId' => $bodega['recibo_id'],
                        'pedidoId' => $bodega['pedido_id_accion'],
                        'numeroPedido' => $bodega['numero_pedido_accion'],
                        'numeroRecibo' => $bodega['consecutivo'],
                        'nombrePrenda' => $bodega['texto_prenda'],
                        'tipoRecibo' => 'CORTE-PARA-BODEGA',
                    ])@endcomponent
                @endif

                <button type="button" class="btn-agregar-novedad"
                        data-numero-pedido="{{ $bodega['numero_pedido_accion'] ?? $bodega['consecutivo'] }}"
                        data-prenda-id="{{ $prenda['prenda_id'] ?? '' }}"
                        data-prenda-bodega-id="{{ $bodega['prenda_bodega_id'] ?? ($prenda['prenda_bodega_id'] ?? '') }}"
                        data-pedido-id="{{ $bodega['pedido_id_accion'] ?? '' }}"
                        data-recibo-id="{{ $bodega['recibo_id'] ?? '' }}"
                        data-nombre-prenda="{{ $bodega['texto_prenda'] }}"
                        data-numero-recibo="{{ $bodega['consecutivo'] }}"
                        onclick="abrirModalNovedadDesdeElemento(this); return false;">
                    <span class="material-symbols-rounded">comment</span>
                    AGREGAR NOVEDAD
                </button>

                <button type="button" class="btn-ver-recibos"
                        onclick="abrirDetallesRecibos(@js($bodega['consecutivo']), {{ $prenda['prenda_id'] ?? 'null' }}, @js($bodega['texto_prenda']), 'CORTE-PARA-BODEGA', null, @js($bodega['consecutivo']), {{ $bodega['recibo_id'] ?? 'null' }}); return false;">
                    <span class="material-symbols-rounded">visibility</span>
                    VER RECIBO
                </button>
            </div>

            <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] ?? 'null' }})">
                <span class="material-symbols-rounded">more_horiz</span>
            </button>
        </div>

        <div class="orden-right">
            <div class="orden-right-center">
                <a href="#"
                   class="action-arrow"
                   onclick="abrirDetallesRecibos(@js($bodega['consecutivo']), {{ $prenda['prenda_id'] ?? 'null' }}, @js($bodega['texto_prenda']), 'CORTE-PARA-BODEGA', null, @js($bodega['consecutivo']), {{ $bodega['recibo_id'] ?? 'null' }}); return false;">
                    <span class="material-symbols-rounded">arrow_forward</span>
                </a>
            </div>
        </div>

        <div class="mobile-actions-drawer" id="mobile-drawer-{{ $prenda['prenda_id'] }}">
            @if(!$bodega['tiene_parciales'])
                <button type="button" class="btn-pasar-costura {{ $bodega['mostrar_como_deshacer'] ? 'btn-deshacer-costura' : '' }}"
                        id="btn-costura-bodega-mobile-{{ $prenda['prenda_id'] }}-{{ $bodega['consecutivo'] }}"
                        data-pedido-id="{{ $bodega['pedido_id_accion'] }}"
                        data-numero-pedido="{{ $bodega['consecutivo'] }}"
                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                        data-prenda-bodega-id="{{ $prenda['prenda_id'] }}"
                        data-nombre="{{ $bodega['texto_prenda'] }}"
                        data-tipo-recibo="CORTE-PARA-BODEGA"
                        data-recibo="{{ $bodega['consecutivo'] }}"
                        data-area="Costura"
                        data-proceso-id="{{ $bodega['proceso_id_costura'] ?? '' }}"
                        data-encargado-costura="{{ $bodega['encargado_costura'] }}"
                        data-parcial-id=""
                        onclick="manejarPasarACostura(this); return false;">
                    <span class="material-symbols-rounded">{{ $bodega['mostrar_como_deshacer'] ? 'undo' : 'checkroom' }}</span>
                    {{ $bodega['mostrar_como_deshacer'] ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                </button>

                <button type="button" class="btn-pasar-cc"
                        id="btn-cc-bodega-mobile-{{ $prenda['prenda_id'] }}-{{ $bodega['consecutivo'] }}"
                        data-recibo-id="{{ $bodega['recibo_id'] ?? '' }}"
                        data-pedido-id="{{ $bodega['pedido_id_accion'] }}"
                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                        data-prenda-bodega-id="{{ $prenda['prenda_id'] }}"
                        data-nombre="{{ $bodega['texto_prenda'] }}"
                        data-tipo-recibo="CORTE-PARA-BODEGA"
                        data-recibo="{{ $bodega['consecutivo'] }}"
                        data-area="{{ $ccArea }}"
                        data-proceso-id="{{ $bodega['proceso_id_costura'] ?? '' }}"
                        data-estado-control-calidad="{{ $bodega['estado_control_calidad'] ?? '' }}"
                        data-tallas-control-calidad='@json($bodega["tallas_control_calidad"] ?? [])'
                        onclick="pasarAControlCalidad(this); return false;">
                    <span class="material-symbols-rounded">{{ $ccIcono }}</span>
                    {{ $ccTexto }}
                </button>
            @endif

            @if($bodega['tiene_parciales'] && $bodega['recibo_id'])
                @component('components.botones.ver-distribucion', [
                    'filtro' => 'bodega',
                    'prendaId' => $prenda['prenda_id'],
                    'reciboId' => $bodega['recibo_id'],
                    'numeroRecibo' => $bodega['consecutivo'],
                    'tipoRecibo' => 'CORTE-PARA-BODEGA',
                ])@endcomponent
                @component('components.botones.editar-encargados', [
                    'filtro' => 'bodega',
                    'prendaId' => $prenda['prenda_id'],
                    'reciboId' => $bodega['recibo_id'],
                    'pedidoId' => $bodega['pedido_id_accion'],
                    'numeroPedido' => $bodega['numero_pedido_accion'],
                    'numeroRecibo' => $bodega['consecutivo'],
                    'nombrePrenda' => $bodega['texto_prenda'],
                    'tipoRecibo' => 'CORTE-PARA-BODEGA',
                ])@endcomponent
            @endif

            <button type="button" class="btn-ver-recibos"
                    onclick="abrirDetallesRecibos(@js($bodega['consecutivo']), {{ $prenda['prenda_id'] ?? 'null' }}, @js($bodega['texto_prenda']), 'CORTE-PARA-BODEGA', null, @js($bodega['consecutivo']), {{ $bodega['recibo_id'] ?? 'null' }}); return false;">
                <span class="material-symbols-rounded">visibility</span>
                VER RECIBO
            </button>

            <button type="button" class="btn-agregar-novedad"
                    data-numero-pedido="{{ $bodega['numero_pedido_accion'] ?? $bodega['consecutivo'] }}"
                    data-prenda-id="{{ $prenda['prenda_id'] ?? '' }}"
                    data-prenda-bodega-id="{{ $bodega['prenda_bodega_id'] ?? ($prenda['prenda_bodega_id'] ?? '') }}"
                    data-pedido-id="{{ $bodega['pedido_id_accion'] ?? '' }}"
                    data-recibo-id="{{ $bodega['recibo_id'] ?? '' }}"
                    data-nombre-prenda="{{ $bodega['texto_prenda'] }}"
                    data-numero-recibo="{{ $bodega['consecutivo'] }}"
                    onclick="abrirModalNovedadDesdeElemento(this); return false;">
                <span class="material-symbols-rounded">comment</span>
                AGREGAR NOVEDAD
            </button>

            <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] ?? 'null' }})">
                <span class="material-symbols-rounded">more_horiz</span>
            </button>
        </div>
    </div>
</div>
