@php
    $reciboPrincipal = $prenda['recibos'][0] ?? [];
    $fechaCreacion = $prenda['fecha_creacion'] ?? ($reciboPrincipal['created_at'] ?? $reciboPrincipal['creado_en'] ?? null);
    $fechaCreacionTimestamp = $fechaCreacion instanceof \DateTimeInterface
        ? $fechaCreacion->getTimestamp()
        : (is_numeric($fechaCreacion)
            ? (int) $fechaCreacion
            : (is_string($fechaCreacion) && trim($fechaCreacion) !== '' ? strtotime($fechaCreacion) ?: 0 : 0));
    $reciboCompletadoArea = (bool) ($reciboPrincipal['completado_area'] ?? false);
    $esParcial = (bool) ($prenda['es_parcial'] ?? false);
    $parcialId = $prenda['parcial_id'] ?? null;
    $tipoReciboControl = strtoupper((string) ($reciboPrincipal['tipo_recibo'] ?? 'COSTURA'));
    $consecutivoActual = (string) ($reciboPrincipal['consecutivo_actual'] ?? ($prenda['numero_pedido'] ?? ''));
    $numeroReciboBusqueda = $reciboPrincipal['consecutivo_parcial'] ?? $consecutivoActual;
@endphp

<div class="orden-card-simple card-control-calidad"
     data-numero="{{ $prenda['numero_pedido'] }}"
     data-numero-recibo="{{ $consecutivoActual }}"
     data-prenda="{{ strtolower((string) ($prenda['nombre_prenda'] ?? '')) }}"
     data-prenda-id="{{ $prenda['prenda_id'] }}"
     data-cliente="{{ strtolower((string) $prenda['cliente']) }}"
     data-search-text="{{ strtolower(trim(($prenda['numero_pedido'] ?? '') . ' ' . ($prenda['nombre_prenda'] ?? '') . ' ' . ($prenda['cliente'] ?? '') . ' ' . ($consecutivoActual ?? ''))) }}"
     data-tipo-recibo="{{ strtolower($tipoReciboControl) }}"
     data-fecha-creacion-costura="{{ $fechaCreacionTimestamp }}"
     data-fecha-creacion-reflectivo="{{ $fechaCreacionTimestamp }}"
     data-sin-encargado-costura="0"
     data-sin-encargado-reflectivo="0"
     data-completado-costura="{{ $reciboCompletadoArea ? '1' : '0' }}"
     data-completado-reflectivo="{{ $reciboCompletadoArea ? '1' : '0' }}">
    <div class="orden-body">
        <div class="orden-left">
            <div class="orden-top">
                <div class="orden-numero-section">
                    <h4 class="orden-numero">#{{ $consecutivoActual }}</h4>
                    <span class="estado-badge pendiente" data-estado="recibo-control-calidad">EN CC</span>
                    @if($reciboCompletadoArea)
                        <span class="badge-completado-costura is-on">COMPLETADO</span>
                    @endif
                </div>
            </div>

            <div class="orden-cliente">
                <p class="cliente-label">CLIENTE</p>
                <p class="cliente-name">{{ $prenda['cliente'] }}</p>
            </div>

            <div class="orden-prendas">
                <p class="prendas-label">
                    <strong>{{ $prenda['nombre_prenda'] }}</strong>
                    @if(!empty($prenda['descripcion']))
                        <br>{!! nl2br(e($prenda['descripcion'])) !!}
                    @endif
                </p>
            </div>

            <div class="mobile-ver-recibo-section">
                <button type="button" class="btn-ver-recibos mobile-under-state"
                        onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $prenda['nombre_prenda']) }}', '{{ $tipoReciboControl }}', {{ $parcialId ? (int) $parcialId : 'null' }}, '{{ addslashes((string) $numeroReciboBusqueda) }}', {{ $reciboPrincipal['id'] ?? 'null' }}); return false;">
                    <span class="material-symbols-rounded">visibility</span>
                    VER RECIBO
                </button>
            </div>

            <div class="orden-buttons">
                <button type="button" class="btn-ver-recibos"
                        onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $prenda['nombre_prenda']) }}', '{{ $tipoReciboControl }}', {{ $parcialId ? (int) $parcialId : 'null' }}, '{{ addslashes((string) $numeroReciboBusqueda) }}', {{ $reciboPrincipal['id'] ?? 'null' }}); return false;">
                    <span class="material-symbols-rounded">visibility</span>
                    VER RECIBO
                </button>

                <button type="button" class="btn-agregar-novedad"
                        onclick="abrirModalNovedad('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $prenda['nombre_prenda']) }}', {{ $consecutivoActual }}); return false;">
                    <span class="material-symbols-rounded">comment</span>
                    AGREGAR NOVEDAD
                </button>

                @if((bool) ($prenda['tiene_parciales'] ?? false))
                    <button class="btn-ver-distribucion"
                            onclick="abrirDistribucionReciboCC(this, '{{ $tipoReciboControl }}');"
                            data-recibo-id="{{ $reciboPrincipal['id'] ?? '' }}"
                            data-prenda-id="{{ $prenda['prenda_id'] }}"
                            data-numero-recibo="{{ $consecutivoActual }}"
                            data-tipo-recibo="{{ $tipoReciboControl }}">
                        <span class="material-symbols-rounded">share</span>
                        VER DISTRIBUCION
                    </button>
                @endif
            </div>
        </div>

        <div class="orden-right">
            <div class="orden-right-center">
                <a href="#"
                   class="action-arrow"
                   onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $prenda['nombre_prenda']) }}', '{{ $tipoReciboControl }}', {{ $parcialId ? (int) $parcialId : 'null' }}, '{{ addslashes((string) $numeroReciboBusqueda) }}', {{ $reciboPrincipal['id'] ?? 'null' }}); return false;">
                    <span class="material-symbols-rounded">arrow_forward</span>
                </a>
            </div>
        </div>
    </div>
</div>
