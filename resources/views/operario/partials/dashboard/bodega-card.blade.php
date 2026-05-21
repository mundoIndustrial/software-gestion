<div class="orden-card-simple card-bodega"
     data-numero="{{ $bodega['consecutivo'] }}"
     data-prenda="{{ strtolower((string) $prenda['nombre_prenda']) }}"
     data-prenda-id="{{ $prenda['prenda_id'] }}"
     data-cliente="{{ strtolower((string) $prenda['cliente']) }}"
     data-tipo-recibo="corte-para-bodega"
     data-numero-recibo="{{ $bodega['consecutivo'] }}"
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
                    PENDIENTE COSTURA
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
                        onclick="abrirDetallesRecibos('{{ $bodega['consecutivo'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $bodega['texto_prenda']) }}', 'CORTE-PARA-BODEGA', null, '{{ $bodega['consecutivo'] }}', {{ $bodega['recibo_id'] ?? 'null' }}); return false;">
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
                            data-pedido-id="{{ $bodega['pedido_id_accion'] }}"
                            data-prenda-id="{{ $prenda['prenda_id'] }}"
                            data-prenda-bodega-id="{{ $prenda['prenda_id'] }}"
                            data-nombre="{{ $bodega['texto_prenda'] }}"
                            data-tipo-recibo="CORTE-PARA-BODEGA"
                            data-recibo="{{ $bodega['consecutivo'] }}"
                            data-area="Costura"
                            data-proceso-id="{{ $bodega['proceso_id_costura'] ?? '' }}"
                            type="button"
                            onclick="pasarAControlCalidad(this); return false;">
                        <span class="material-symbols-rounded">check_circle</span>
                        PASAR A C.C
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
                        onclick="abrirModalNovedad('{{ $bodega['consecutivo'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $bodega['texto_prenda']) }}', {{ $bodega['consecutivo'] }}); return false;">
                    <span class="material-symbols-rounded">comment</span>
                    AGREGAR NOVEDAD
                </button>

                <button type="button" class="btn-ver-recibos"
                        onclick="abrirDetallesRecibos('{{ $bodega['consecutivo'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $bodega['texto_prenda']) }}', 'CORTE-PARA-BODEGA', null, '{{ $bodega['consecutivo'] }}', {{ $bodega['recibo_id'] ?? 'null' }}); return false;">
                    <span class="material-symbols-rounded">visibility</span>
                    VER RECIBO
                </button>
            </div>

            <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] }})">
                <span class="material-symbols-rounded">more_horiz</span>
            </button>
        </div>

        <div class="orden-right">
            <div class="orden-right-center">
                <a href="#"
                   class="action-arrow"
                   onclick="abrirDetallesRecibos('{{ $bodega['consecutivo'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $bodega['texto_prenda']) }}', 'CORTE-PARA-BODEGA', null, '{{ $bodega['consecutivo'] }}', {{ $bodega['recibo_id'] ?? 'null' }}); return false;">
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
                        data-pedido-id="{{ $bodega['pedido_id_accion'] }}"
                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                        data-prenda-bodega-id="{{ $prenda['prenda_id'] }}"
                        data-nombre="{{ $bodega['texto_prenda'] }}"
                        data-tipo-recibo="CORTE-PARA-BODEGA"
                        data-recibo="{{ $bodega['consecutivo'] }}"
                        data-area="Costura"
                        data-proceso-id="{{ $bodega['proceso_id_costura'] ?? '' }}"
                        onclick="pasarAControlCalidad(this); return false;">
                    <span class="material-symbols-rounded">check_circle</span>
                    PASAR A C.C
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
                    onclick="abrirDetallesRecibos('{{ $bodega['consecutivo'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $bodega['texto_prenda']) }}', 'CORTE-PARA-BODEGA', null, '{{ $bodega['consecutivo'] }}', {{ $bodega['recibo_id'] ?? 'null' }}); return false;">
                <span class="material-symbols-rounded">visibility</span>
                VER RECIBO
            </button>

            <button type="button" class="btn-agregar-novedad"
                    onclick="abrirModalNovedad('{{ $bodega['consecutivo'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $bodega['texto_prenda']) }}', {{ $bodega['consecutivo'] }}); return false;">
                <span class="material-symbols-rounded">comment</span>
                AGREGAR NOVEDAD
            </button>

            <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] }})">
                <span class="material-symbols-rounded">more_horiz</span>
            </button>
        </div>
    </div>
</div>
