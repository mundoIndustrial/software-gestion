                        @php
                            $prendaOriginal = $prenda ?? [];
                            $prenda = array_merge([
                                'numero_pedido' => '',
                                'nombre_prenda' => '',
                                'descripcion' => '',
                                'prenda_id' => 0,
                                'pedido_id' => ($prendaOriginal['pedido_id_accion'] ?? 0),
                                'cliente' => '',
                                'recibos' => [],
                            ], $prendaOriginal);
                            $normal = $prenda['normal_view'] ?? [];
                            $estadoClass = (string) ($normal['estado_class'] ?? 'pendiente');
                            $tieneReflectivo = (bool) ($normal['tiene_reflectivo'] ?? false);
                            $tieneCostura = (bool) ($normal['tiene_costura'] ?? false);
                            $esReflectivo = (string) ($normal['es_reflectivo_filtro'] ?? 'costura');
                            $displayInicial = (string) ($normal['display_inicial'] ?? '');
                            $reciboPrincipalCard = $normal['recibo_principal_card'] ?? ($prenda['recibos'][0] ?? null);
                            $reciboCompletadoCostura = (bool) ($normal['recibo_completado_costura'] ?? false);
                            $reciboCosturaFiltroCard = $normal['recibo_costura_filtro'] ?? null;
                            $reciboReflectivoFiltroCard = $normal['recibo_reflectivo_filtro'] ?? null;
                            $reciboCompletadoReflectivo = (bool) ($normal['recibo_completado_reflectivo'] ?? false);
                            $reciboParaBusqueda = $normal['recibo_para_busqueda'] ?? $reciboPrincipalCard;
                            $tipoReciboPreferido = (string) ($normal['tipo_recibo_preferido'] ?? '');
                            $parcialIdPreferido = $normal['parcial_id_preferido'] ?? 'null';
                            $consecutivoPreferido = (string) ($normal['consecutivo_preferido'] ?? '');
                            $numeroReciboBusqueda = (string) ($normal['numero_recibo_busqueda'] ?? ($prenda['numero_pedido'] ?? ''));
                            $numerosRecibosBusqueda = (string) ($normal['numeros_recibos_busqueda'] ?? '');
                            $sinEncargadoCosturaCard = (bool) ($normal['sin_encargado_costura'] ?? false);
                            $sinEncargadoReflectivoCard = (bool) ($normal['sin_encargado_reflectivo'] ?? false);
                            $recibosCorteAsignadosCortador = (int) ($normal['recibos_corte_asignados'] ?? 0);
                            $tiposUnicos = collect($normal['tipos_unicos'] ?? []);
                            $recibosPreferidosPorTipo = is_array($normal['recibos_preferidos_por_tipo'] ?? null) ? $normal['recibos_preferidos_por_tipo'] : [];
                            $tipoReciboPrincipal = strtoupper((string) ($prenda['recibos'][0]['tipo_recibo'] ?? ''));
                            $esReciboBodega = $tipoReciboPrincipal === 'CORTE-PARA-BODEGA';
                            $clienteCard = trim((string) ($prenda['cliente'] ?? ''));
                            if ($esReciboBodega && ($clienteCard === '' || strtoupper($clienteCard) === 'SIN CLIENTE')) {
                                $clienteCard = 'BODEGA';
                            } elseif ($clienteCard === '') {
                                $clienteCard = 'SIN CLIENTE';
                            }
                        @endphp

                        @if(!(bool) ($normal['debe_omitirse_lider_reflectivo'] ?? false))
                        <div class="orden-card-simple {{ ((auth()->user()->hasAnyRole(['costurero', 'confeccion-sobremedida']) || auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('administrador-costura')) && $reciboCompletadoCostura) ? 'card-completado-costura' : '' }} {{ $tieneReflectivo ? 'borde-reflectivo' : '' }}" 
                             data-numero="{{ $prenda['numero_pedido'] }}" 
                             data-prenda="{{ strtolower($prenda['nombre_prenda']) }}"
                             data-prenda-id="{{ $prenda['prenda_id'] }}"
                             data-pedido-parcial-id="{{ $prenda['recibos'][0]['pedido_parcial_id'] ?? '' }}"
                             data-cliente="{{ strtolower($clienteCard) }}"
                             data-tipo-recibo="{{ $esReflectivo }}"
                             data-sin-encargado-costura="{{ $sinEncargadoCosturaCard ? '1' : '0' }}"
                             data-sin-encargado-reflectivo="{{ $sinEncargadoReflectivoCard ? '1' : '0' }}"
                             data-completado-costura="{{ $reciboCompletadoCostura ? '1' : '0' }}"
                             data-completado-reflectivo="{{ $reciboCompletadoReflectivo ? '1' : '0' }}"
                             data-numero-recibo="{{ trim($numeroReciboBusqueda . ' ' . $numerosRecibosBusqueda) }}"
                             data-fecha-completado-reflectivo="{{ ($reciboReflectivoFiltroCard && isset($reciboReflectivoFiltroCard['fecha_completado_costura'])) ? strtotime($reciboReflectivoFiltroCard['fecha_completado_costura']) : 0 }}"
                             data-fecha-creacion-reflectivo="{{ ($reciboReflectivoFiltroCard['created_at'] ?? ($reciboReflectivoFiltroCard['creado_en'] ?? '')) ? strtotime($reciboReflectivoFiltroCard['created_at'] ?? $reciboReflectivoFiltroCard['creado_en']) : 0 }}"
                             data-fecha-creacion-costura="{{ ($reciboCosturaFiltroCard['created_at'] ?? ($prenda['fecha_creacion'] ?? '')) ? strtotime($reciboCosturaFiltroCard['created_at'] ?? ($prenda['fecha_creacion'] ?? '')) : 0 }}"
                             data-fecha-asignacion-costura="{{ ($reciboCosturaFiltroCard['fecha_asignacion_costura'] ?? ($reciboCosturaFiltroCard['fecha_proceso_costura_created_at'] ?? ($prenda['fecha_creacion'] ?? ''))) ? strtotime($reciboCosturaFiltroCard['fecha_asignacion_costura'] ?? ($reciboCosturaFiltroCard['fecha_proceso_costura_created_at'] ?? ($prenda['fecha_creacion'] ?? ''))) : 0 }}"
                             data-recibos-corte-asignados="{{ $recibosCorteAsignadosCortador }}"
                             data-area-actual="{{ strtoupper(trim((string) ($reciboPrincipalCard['area'] ?? 'SIN AREA'))) }}"
                             style="display: {{ $displayInicial }}">

                            <!-- Borde izquierdo eliminado -->
                            <!-- <div class="orden-border {{ $estadoClass }}"></div> -->

                            <!-- Contenido Izquierdo -->
                            @php
                                $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                $reciboCompletadoArea = (bool) ($reciboPrincipal['completado_area'] ?? false);
                                $reciboCompletadoCorte = (bool) ($reciboPrincipal['completado_corte'] ?? false);
                                $areaReciboActual = (string) ($reciboPrincipal['area'] ?? '');
                                $reciboCompletadoCostura = (bool) ($reciboPrincipal['completado_costura'] ?? false);
                                if (($filtroReciboActual ?? '') === 'bodega' && is_array($reciboCosturaFiltroCard ?? null)) {
                                    $reciboCompletadoCostura = (bool) ($reciboCosturaFiltroCard['completado_costura'] ?? $reciboCompletadoCostura);
                                }
                                $reciboCompletadoControlCalidad = (bool) ($reciboPrincipal['completado_control_calidad'] ?? false);
                                $areaReciboNormalizada = strtolower(trim($areaReciboActual));
                                $completadoVistaSegunArea = $areaReciboNormalizada === 'costura'
                                    ? $reciboCompletadoCostura
                                    : ($areaReciboNormalizada === 'corte'
                                        ? $reciboCompletadoCorte
                                        : (in_array($areaReciboNormalizada, ['control calidad', 'control de calidad'], true)
                                            ? $reciboCompletadoControlCalidad
                                            : false));
                                $labelAreaVista = $areaReciboActual ?: '-';
                                $labelEstadoVista = $completadoVistaSegunArea
                                    ? ('COMPLETADO ' . strtoupper($labelAreaVista))
                                    : ('PENDIENTE ' . strtoupper($labelAreaVista));
                                $labelEstadoVistaCostura = $reciboCompletadoCostura
                                    ? 'COMPLETADO COSTURA'
                                    : 'PENDIENTE COSTURA';
                            @endphp
                            <div class="orden-body {{ ($reciboCompletadoArea || (auth()->user()->hasRole('vista-costura') && $completadoVistaSegunArea)) ? 'recibo-completado-area' : '' }}">
                                @php
                                    $encargadoVista = null;
                                    if (($filtroReciboActual ?? '') === 'bodega') {
                                        $encargadoVista = $reciboCosturaFiltroCard['encargado_costura']
                                            ?? ($prenda['encargado_costura'] ?? null)
                                            ?? ($reciboPrincipal['encargado_costura'] ?? null);
                                    } elseif ($areaReciboNormalizada === 'corte') {
                                        $encargadoVista = $reciboPrincipal['encargado_corte'] ?? null;
                                    } elseif ($areaReciboNormalizada === 'costura') {
                                        $encargadoVista = $reciboPrincipal['encargado_costura'] ?? null;
                                    } elseif (in_array($areaReciboNormalizada, ['control calidad', 'control de calidad'], true)) {
                                        $encargadoVista = $reciboPrincipal['encargado_control_calidad'] ?? null;
                                    }
                                    $encargadoVista = is_string($encargadoVista) ? trim($encargadoVista) : $encargadoVista;
                                    
                                    // Obtener tipo de distribución
                                    $tipoDistribucion = $normal['tipo_distribucion'] ?? null;
                                    
                                    // Determinar el texto a mostrar
                                    if ($tipoDistribucion === 'modulos') {
                                        $textoEncargadoVista = 'DISTRIBUIDO EN MÓDULOS';
                                    } elseif ($tipoDistribucion === 'talleres') {
                                        $textoEncargadoVista = 'DISTRIBUIDO EN TALLERES';
                                    } else {
                                        $textoEncargadoVista = $encargadoVista ? strtoupper($encargadoVista) : 'SIN ENCARGADO';
                                    }

                                    // Obtener encargado de corte para mostrar en el card (excepto cortadores)
                                    $encargadoCorte = $reciboPrincipal['encargado_corte'] ?? null;
                                    $encargadoCorte = is_string($encargadoCorte) ? trim($encargadoCorte) : $encargadoCorte;
                                    $encargadoCosturaCard = is_string($reciboCosturaFiltroCard['encargado_costura'] ?? null) ? trim((string) $reciboCosturaFiltroCard['encargado_costura']) : ($reciboCosturaFiltroCard['encargado_costura'] ?? null);
                                    $encargadoReflectivoCard = is_string($reciboReflectivoFiltroCard['encargado_costura'] ?? null) ? trim((string) $reciboReflectivoFiltroCard['encargado_costura']) : ($reciboReflectivoFiltroCard['encargado_costura'] ?? null);
                                    $textoEncargadoCosturaCard = $reciboCosturaFiltroCard
                                        ? ($encargadoCosturaCard ? strtoupper($encargadoCosturaCard) : 'SIN ENCARGADO')
                                        : 'SIN ENCARGADO';
                                    $textoEncargadoReflectivoCard = $reciboReflectivoFiltroCard
                                        ? ($encargadoReflectivoCard ? strtoupper($encargadoReflectivoCard) : 'SIN ENCARGADO')
                                        : 'SIN ENCARGADO';
                                @endphp
                                @if(!auth()->user()->hasRole('vista-costura') && !auth()->user()->hasRole('cortador') && !auth()->user()->hasRole('visualizador_plooter') && !auth()->user()->hasAnyRole(['costurero', 'confeccion-sobremedida']))
                                    <div class="orden-encargado-corner" onclick="event.stopPropagation();">
                                        <strong>Encargado:</strong>
                                        @if(auth()->user()->hasRole('lider-reflectivo'))
                                            <span data-visible-filtro="costura">{{ $textoEncargadoCosturaCard }}</span>
                                            <span data-visible-filtro="reflectivo" style="display: none;">{{ $textoEncargadoReflectivoCard }}</span>
                                        @else
                                            <span>{{ $encargadoVista ? strtoupper($encargadoVista) : 'SIN ENCARGADO' }}</span>
                                        @endif
                                    </div>
                                @endif
                                @if(auth()->user()->hasRole('vista-costura'))
                                    <div class="vista-resumen-card" onclick="event.stopPropagation();">
                                        <div class="vista-encargados-row">
                                            @if(!auth()->user()->hasRole('cortador'))
                                                <div class="vista-encargado-pill vista-encargado-pill-corte">
                                                    <span class="vista-encargado-pill-label">Corte</span>
                                                    <span class="vista-encargado-pill-name">{{ $encargadoCorte ? strtoupper($encargadoCorte) : 'SIN ASIGNAR' }}</span>
                                                </div>
                                            @endif

                                            <div class="vista-encargado-pill vista-encargado-pill-costura">
                                                <span class="vista-encargado-pill-label">Costura</span>
                                                <span class="vista-encargado-pill-name">{{ $textoEncargadoVista }}</span>
                                            </div>
                                        </div>

                                        <div class="vista-estado-linea">
                                            <span class="vista-estado-etiqueta">Estado:</span>
                                            <span class="badge-completado-corte {{ $reciboCompletadoCostura ? 'is-on' : '' }}">
                                                {{ $labelEstadoVistaCostura }}
                                            </span>
                                        </div>
                                    </div>
                                @elseif(
                                        !auth()->user()->hasRole('cortador')
                                        && !auth()->user()->hasRole('visualizador_plooter')
                                        && !auth()->user()->hasRole('lider-reflectivo')
                                        && !auth()->user()->hasRole('administrador-costura')
                                    )
                                        <div class="orden-encargado-corte" onclick="event.stopPropagation();">
                                            <strong>Encargado Corte:</strong>
                                            <span>{{ $encargadoCorte ? strtoupper($encargadoCorte) : 'SIN ASIGNAR' }}</span>
                                        </div>
                                @endif
                                <div class="orden-left">
                                    <div class="orden-top">
                                        <div class="orden-numero-section">
                                            @if(isset($prenda['recibos'][0]['consecutivo_actual']))
                                                <h4 class="orden-numero">#{{ $prenda['recibos'][0]['consecutivo_actual'] }}</h4>
                                            @else
                                                <h4 class="orden-numero">#{{ $prenda['numero_pedido'] }}</h4>
                                            @endif
                                            <span class="estado-badge {{ $estadoClass }}" data-estado="recibo-costura">
                                                {{ count(array_unique(array_map(fn($r) => strtoupper($r['tipo_recibo']), $prenda['recibos']))) }} RECIBOS
                                            </span>
                                            @if(auth()->user()->hasAnyRole(['costurero', 'confeccion-sobremedida']) && $reciboCompletadoCostura)
                                                <span class="badge-completado-costura is-on">COMPLETADO</span>
                                            @endif
                                            @if(auth()->user()->hasRole('costura-reflectivo') && $reciboCompletadoCostura)
                                                <span class="badge-completado-costura is-on">COMPLETADO</span>
                                            @endif
                                            @if(auth()->user()->hasRole('lider-reflectivo') && $reciboCompletadoCostura)
                                                <span class="badge-completado-costura is-on">COMPLETADO</span>
                                            @endif
                                            @if(auth()->user()->hasRole('administrador-costura') && $reciboCompletadoCostura)
                                                <span class="badge-completado-costura is-on">COMPLETADO</span>
                                            @endif
                                        </div>
                                        <!-- Badge completado para costurero - posicionado en esquina superior derecha solo en mobile -->
                                        @if(auth()->user()->hasAnyRole(['costurero', 'confeccion-sobremedida']) && $reciboCompletadoCostura)
                                            <span class="badge-completado-costura is-on mobile-top-right">COMPLETADO</span>
                                        @endif
                                        <!-- Badge completado para costura-reflectivo - posicionado en esquina superior derecha solo en mobile -->
                                        @if(auth()->user()->hasRole('costura-reflectivo') && $reciboCompletadoCostura)
                                            <span class="badge-completado-costura is-on mobile-top-right">COMPLETADO</span>
                                        @endif
                                        <!-- Badge completado para lider-reflectivo - posicionado en esquina superior derecha solo en mobile -->
                                        @if(auth()->user()->hasRole('lider-reflectivo') && $reciboCompletadoCostura)
                                            <span class="badge-completado-costura is-on mobile-top-right">COMPLETADO</span>
                                        @endif
                                        <!-- Badge completado para administrador-costura - posicionado en esquina superior derecha solo en mobile -->
                                        @if(auth()->user()->hasRole('administrador-costura') && $reciboCompletadoCostura)
                                            <span class="badge-completado-costura is-on mobile-top-right">COMPLETADO</span>
                                        @endif
                                        <!-- Boton de mas opciones para mobile -->
                                        <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] }})">
                                            <span class="material-symbols-rounded">more_horiz</span>
                                        </button>
                                    </div>

                                    <div class="orden-cliente">
                                        <p class="cliente-label">{{ $esReciboBodega ? 'SERVICIO' : 'CLIENTE' }}</p>
                                        <p class="cliente-name">{{ $clienteCard }}</p>
                                    </div>
                                    <div class="dashboard-search-area-hint" style="display: none;">
                                        <span class="material-symbols-rounded">location_on</span>
                                        <span>EN AREA: {{ strtoupper(trim((string) ($reciboPrincipalCard['area'] ?? 'SIN AREA'))) }}</span>
                                    </div>

                                    @if(auth()->user()->hasRole('lider-reflectivo'))
                                        <div class="lider-encargado-mobile" onclick="event.stopPropagation();">
                                            <span class="lider-encargado-mobile-label">Encargado</span>
                                            <span class="lider-encargado-mobile-value" data-visible-filtro="costura">{{ $textoEncargadoCosturaCard }}</span>
                                            <span class="lider-encargado-mobile-value" data-visible-filtro="reflectivo" style="display: none;">{{ $textoEncargadoReflectivoCard }}</span>
                                        </div>
                                    @endif

                                    <!-- Boton Ver Recibo (debajo del estado para mobile) -->
                                    <div class="mobile-ver-recibo-section">
                                        @component('components.botones.ver-recibo', [
                                            'numeroPedido' => $prenda['numero_pedido'],
                                            'prendaId' => $prenda['prenda_id'],
                                            'nombrePrenda' => addslashes((string)$prenda['nombre_prenda']),
                                            'tipoRecibo' => $tipoReciboPreferido,
                                            'idParcial' => $parcialIdPreferido,
                                            'consecutivo' => $consecutivoPreferido,
                                            'reciboId' => $reciboParaBusqueda['id'] ?? null,
                                            'clase' => 'mobile-under-state',
                                        ])@endcomponent
                                    </div>

                                    <div class="orden-prendas">
                                        <p class="prendas-label">
                                            <strong>{{ $prenda['nombre_prenda'] }}</strong>
                                            @if($prenda['descripcion'])
                                                @php
                                                    $descripcionOperarioRaw = (string) ($prenda['descripcion'] ?? '');
                                                    $descripcionOperarioNormalizada = preg_replace("/\r\n?/", "\n", $descripcionOperarioRaw);
                                                    $descripcionOperarioPermitida = strip_tags($descripcionOperarioNormalizada, '<span><br>');
                                                    $descripcionOperarioRender = nl2br($descripcionOperarioPermitida, false);
                                                @endphp
                                                {!! $descripcionOperarioRender !!}
                                            @endif
                                        </p>        
                                    </div>

                                    <!-- Contenedor de Botones -->
                                    <div class="orden-buttons">
                                        {{-- Botones para cortador (desde backend) --}}
                                        @php
                                            $accionesCortador = $normal['acciones']['cortador'] ?? [];
                                        @endphp
                                        @foreach($accionesCortador as $accion)
                                            <button class="{{ $accion['clase'] }}" 
                                                    type="button"
                                                    data-pedido-id="{{ $accion['datos']['pedido_id'] }}"
                                                    data-prenda-id="{{ $accion['datos']['prenda_id'] }}"
                                                    data-recibo-id="{{ $accion['datos']['recibo_id'] }}"
                                                    data-nombre="{{ $accion['datos']['nombre'] }}"
                                                    @if($accion['tipo'] === 'completar_corte')
                                                        onclick="completarCorte(this)"
                                                    @elseif($accion['tipo'] === 'deshacer_corte')
                                                        onclick="deshacerCorte(this)"
                                                    @endif>
                                                <span class="material-symbols-rounded">{{ $accion['icono'] }}</span>
                                                {{ $accion['texto'] }}
                                            </button>
                                        @endforeach

                                        {{-- Botones para costurero (desde backend) --}}
                                        @php
                                            $accionesCosturero = $normal['acciones']['costurero'] ?? [];
                                        @endphp
                                        @foreach($accionesCosturero as $accion)
                                            <button class="{{ $accion['clase'] }}" 
                                                    type="button"
                                                    data-pedido-id="{{ $accion['datos']['pedido_id'] }}"
                                                    data-prenda-id="{{ $accion['datos']['prenda_id'] }}"
                                                    data-recibo-id="{{ $accion['datos']['recibo_id'] }}"
                                                    data-es-parcial="{{ $accion['datos']['es_parcial'] }}"
                                                    data-nombre="{{ $accion['datos']['nombre'] }}"
                                                    @if($accion['tipo'] === 'completar_costura')
                                                        onclick="completarCostura(this); return false;"
                                                    @elseif($accion['tipo'] === 'deshacer_costura')
                                                        onclick="deshacerCostura(this); return false;"
                                                    @endif>
                                                <span class="material-symbols-rounded">{{ $accion['icono'] }}</span>
                                                {{ $accion['texto'] }}
                                            </button>
                                        @endforeach

                                        {{-- Botones para administrador-sobremedida (desde backend) --}}
                                        @php
                                            $accionesAdminSobremedida = $normal['acciones']['administrador_sobremedida'] ?? [];
                                        @endphp
                                        @foreach($accionesAdminSobremedida as $accion)
                                            <button class="{{ $accion['clase'] }}" 
                                                    type="button"
                                                    data-pedido-id="{{ $accion['datos']['pedido_id'] }}"
                                                    data-prenda-id="{{ $accion['datos']['prenda_id'] }}"
                                                    data-recibo-id="{{ $accion['datos']['recibo_id'] }}"
                                                    data-nombre="{{ $accion['datos']['nombre'] }}"
                                                    @if($accion['tipo'] === 'completar_corte_sobremedida')
                                                        onclick="completarReciboCorteSobremedida(this)"
                                                    @elseif($accion['tipo'] === 'deshacer_corte_sobremedida')
                                                        onclick="deshacerReciboCorteSobremedida(this)"
                                                    @endif>
                                                <span class="material-symbols-rounded">{{ $accion['icono'] }}</span>
                                                {{ $accion['texto'] }}
                                            </button>
                                        @endforeach

                                        {{-- Botones para vista-costura (desde backend) --}}
                                        @php
                                            $accionesVistaCostura = $normal['acciones']['vista_costura'] ?? [];
                                        @endphp
                                        @foreach($accionesVistaCostura as $accion)
                                            <button class="{{ $accion['clase'] }}" 
                                                    type="button"
                                                    data-visible-filtro="{{ $accion['visible_filtro'] }}"
                                                    data-pedido-id="{{ $accion['datos']['pedido_id'] }}"
                                                    data-prenda-id="{{ $accion['datos']['prenda_id'] }}"
                                                    data-nombre="{{ $accion['datos']['nombre'] }}"
                                                    data-tipo-recibo="{{ $accion['datos']['tipo_recibo'] }}"
                                                    data-recibo="{{ $accion['datos']['recibo'] }}"
                                                    data-area="{{ $accion['datos']['area'] }}"
                                                    data-proceso-id="{{ $accion['datos']['proceso_id'] }}"
                                                    @if($accion['tipo'] === 'pasar_costura')
                                                        data-numero-pedido="{{ $accion['datos']['numero_pedido'] }}"
                                                        data-encargado-costura="{{ $accion['datos']['encargado_costura'] }}"
                                                        data-parcial-id="{{ $accion['datos']['parcial_id'] }}"
                                                        onclick="manejarPasarACostura(this); return false;"
                                                    @elseif($accion['tipo'] === 'pasar_cc')
                                                        onclick="pasarAControlCalidad(this); return false;"
                                                    @endif>
                                                <span class="material-symbols-rounded">{{ $accion['icono'] }}</span>
                                                {{ $accion['texto'] }}
                                            </button>
                                        @endforeach

                                        {{-- Componentes adicionales para vista-costura (ver-distribucion, editar-encargados) --}}
                                        @php
                                            $reciboCosturaVista = $normal['recibo_costura_filtro'] ?? null;
                                            $reciboIdVista = $reciboCosturaVista['id'] ?? null;
                                            $tieneParcialesVista = $reciboCosturaVista['tiene_parciales'] ?? false;
                                            $consecutivoVista = $reciboCosturaVista['consecutivo_actual'] ?? $prenda['numero_pedido'];
                                        @endphp
                                        @if(auth()->user()->hasRole('vista-costura') && $reciboIdVista && $tieneParcialesVista)
                                            @component('components.botones.ver-distribucion', [
                                                'filtro' => 'costura',
                                                'prendaId' => $prenda['prenda_id'],
                                                'reciboId' => $reciboIdVista,
                                                'numeroRecibo' => $consecutivoVista,
                                                'tipoRecibo' => 'COSTURA',
                                            ])@endcomponent
                                            @component('components.botones.editar-encargados', [
                                                'filtro' => 'costura',
                                                'prendaId' => $prenda['prenda_id'],
                                                'reciboId' => $reciboIdVista,
                                                'pedidoId' => $prenda['pedido_id'],
                                                'numeroPedido' => $prenda['numero_pedido'],
                                                'numeroRecibo' => $consecutivoVista,
                                                'nombrePrenda' => $prenda['nombre_prenda'],
                                                'tipoRecibo' => 'COSTURA',
                                            ])@endcomponent
                                        @endif

                                        @component('components.botones.agregar-novedad', [
                                            'numeroPedido' => $prenda['numero_pedido'],
                                            'prendaId' => $prenda['prenda_id'],
                                            'nombrePrenda' => $prenda['nombre_prenda'],
                                            'consecutivo' => isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'],
                                        ])@endcomponent
                                        @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('vista-costura'))
                                            @php
                                                $reciboReflectivo = $normal['recibo_reflectivo'] ?? ($recibosPreferidosPorTipo['REFLECTIVO'] ?? null);
                                                $tieneReciboReflectivo = !empty($reciboReflectivo);
                                                $reciboReflectivoId = $reciboReflectivo['id'] ?? null;
                                                $tieneParcialesReflectivo = $reciboReflectivo['tiene_parciales'] ?? false;
                                                $encargadoCosturaRef = $reciboReflectivo['encargado_costura'] ?? null;
                                                $encargadoCosturaRef = is_string($encargadoCosturaRef) ? trim($encargadoCosturaRef) : $encargadoCosturaRef;
                                                $tieneEncargadoCosturaRef = !empty($encargadoCosturaRef);
                                                $areaReciboRef = $reciboReflectivo['area'] ?? '';
                                                $esCosturaAreaRef = strtolower(trim((string) $areaReciboRef)) === 'costura';
                                                $esControlCalidadRef = in_array(strtolower(trim((string) $areaReciboRef)), ['control calidad', 'control de calidad'], true);
                                            @endphp

                                            {{-- Boton PASAR A COSTURA/DESHACER COSTURA para vista-costura --}}
                                                @if($tieneReciboReflectivo && auth()->user()->hasRole('vista-costura'))
                                                    @php
                                                        $pedidoParcialId = isset($reciboReflectivo['pedido_parcial_id']) ? (int) $reciboReflectivo['pedido_parcial_id'] : 0;
                                                        $consecutivoParcial = $reciboReflectivo['consecutivo_parcial'] ?? ($reciboReflectivo['consecutivo_actual'] ?? null);
                                                        $esReciboReflectivoParcial = false;
                                                        $reciboReflectivoAccionId = $reciboReflectivoId;
                                                    @endphp

                                                    {{-- Boton VER RECIBO para vista-costura --}}
                                                    @component('components.botones.ver-recibo', [
                                                        'numeroPedido' => $prenda['numero_pedido'],
                                                        'prendaId' => $prenda['prenda_id'],
                                                        'nombrePrenda' => $prenda['nombre_prenda'],
                                                        'tipoRecibo' => 'REFLECTIVO',
                                                        'idParcial' => $pedidoParcialId ? (int) $pedidoParcialId : null,
                                                        'consecutivo' => $consecutivoParcial ?? '',
                                                        'reciboId' => $reciboReflectivo['id'] ?? null,
                                                    ])@endcomponent

                                                    @if($reciboReflectivoId && $tieneParcialesReflectivo)
                                                        @component('components.botones.ver-distribucion', [
                                                            'filtro' => 'reflectivo',
                                                            'prendaId' => $prenda['prenda_id'],
                                                            'reciboId' => $reciboReflectivoId,
                                                            'numeroRecibo' => $reciboReflectivo['consecutivo_actual'] ?? $prenda['numero_pedido'],
                                                            'tipoRecibo' => 'REFLECTIVO',
                                                        ])@endcomponent
                                                        @component('components.botones.editar-encargados', [
                                                            'filtro' => 'reflectivo',
                                                            'prendaId' => $prenda['prenda_id'],
                                                            'reciboId' => $reciboReflectivoId,
                                                            'pedidoId' => $prenda['pedido_id'],
                                                            'numeroPedido' => $prenda['numero_pedido'],
                                                            'numeroRecibo' => $reciboReflectivo['consecutivo_actual'] ?? $prenda['numero_pedido'],
                                                            'nombrePrenda' => $prenda['nombre_prenda'],
                                                            'tipoRecibo' => 'REFLECTIVO',
                                                        ])@endcomponent
                                                    @endif

                                                    @if(!$tieneParcialesReflectivo)
                                                        @if(!auth()->user()->hasRole('vista-costura'))
                                                            <button class="btn-pasar-costura {{ $tieneEncargadoCosturaRef ? 'btn-deshacer-costura' : '' }}" 
                                                                    data-visible-filtro="reflectivo"
                                                                    id="btn-costura-reflectivo-{{ $prenda['prenda_id'] }}"
                                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                    data-numero-pedido="{{ $prenda['numero_pedido'] }}"
                                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                    data-tipo-recibo="REFLECTIVO"
                                                                    data-recibo="{{ $reciboReflectivo['consecutivo_actual'] ?? $prenda['numero_pedido'] }}"
                                                                    data-area="{{ $areaReciboRef }}"
                                                                    data-proceso-id="{{ $reciboReflectivo['proceso_id_costura'] ?? '' }}"
                                                                    data-encargado-costura="{{ $encargadoCosturaRef ?? '' }}"
                                                                    type="button"
                                                                    onclick="manejarPasarACostura(this); return false;">
                                                                <span class="material-symbols-rounded">{{ $tieneEncargadoCosturaRef ? 'undo' : 'checkroom' }}</span>
                                                                {{ $tieneEncargadoCosturaRef ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                                                            </button>
                                                        @endif

                                                            <button class="btn-pasar-cc" 
                                                                    data-visible-filtro="reflectivo"
                                                                    id="btn-cc-reflectivo-{{ $prenda['prenda_id'] }}"
                                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                    data-tipo-recibo="REFLECTIVO"
                                                                    data-recibo="{{ $reciboReflectivo['consecutivo_actual'] ?? $prenda['numero_pedido'] }}"
                                                                    data-area="{{ $areaReciboRef ?? 'REFLECTIVO' }}"
                                                                    data-proceso-id="{{ $reciboReflectivo['proceso_id'] ?? '' }}"
                                                                    type="button"
                                                                    onclick="pasarAControlCalidad(this); return false;">
                                                                <span class="material-symbols-rounded">{{ $esControlCalidadRef ? 'undo' : 'check_circle' }}</span>
                                                                {{ $esControlCalidadRef ? 'DESHACER' : 'PASAR A C.C' }}
                                                            </button>
                                                    @endif
                                                @endif

                                            {{-- Botones de completar/deshacer para REFLECTIVO (solo para costura-reflectivo y lider-reflectivo) --}}
                                            @if($tieneReciboReflectivo && (auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo')))
                                                @php
                                                    $reciboId = $reciboReflectivo['id'] ?? null;
                                                    $pedidoParcialId = isset($reciboReflectivo['pedido_parcial_id']) ? (int) $reciboReflectivo['pedido_parcial_id'] : 0;
                                                    $consecutivoParcial = $reciboReflectivo['consecutivo_parcial'] ?? ($reciboReflectivo['consecutivo_actual'] ?? null);
                                                    $reciboCompletadoArea = false;

                                                    // Verificar si esta completado segun el Area
                                                    if ($esCosturaAreaRef) {
                                                        $reciboCompletadoArea = (bool) ($reciboReflectivo['completado_costura'] ?? false);
                                                    } else {
                                                        $reciboCompletadoArea = (bool) ($reciboReflectivo['completado_area'] ?? false);
                                                    }

                                                    $tieneEncargadoAsignado = false;

                                                    // Para REFLECTIVO: verificar que tenga encargado asignado
                                                    $encargadoReflectivo = $reciboReflectivo['encargado_costura'] ?? null;
                                                    $encargadoReflectivo = is_string($encargadoReflectivo) ? trim($encargadoReflectivo) : $encargadoReflectivo;
                                                    $tieneEncargadoAsignado = !empty($encargadoReflectivo);

                                                    // Para administrador-costura: siempre permitir
                                                    if (auth()->user()->hasRole('administrador-costura')) {
                                                        $tieneEncargadoAsignado = true;
                                                    }

                                                    $tipoReciboNormalizado = strtolower('REFLECTIVO');
                                                    $reciboReflectivoAccionId = $reciboReflectivo['id'] ?? ($reciboReflectivo['pedido_parcial_id'] ?? null);
                                                    $esReciboReflectivoParcial = !empty($reciboReflectivo['es_parcial']);
                                                @endphp

                                                {{-- Boton VER RECIBO para REFLECTIVO --}}
                                                @component('components.botones.ver-recibo', [
                                                    'numeroPedido' => $prenda['numero_pedido'],
                                                    'prendaId' => $prenda['prenda_id'],
                                                    'nombrePrenda' => $prenda['nombre_prenda'],
                                                    'tipoRecibo' => 'REFLECTIVO',
                                                    'idParcial' => $pedidoParcialId ? (int) $pedidoParcialId : null,
                                                    'consecutivo' => $consecutivoParcial ?? '',
                                                    'reciboId' => $reciboReflectivo['id'] ?? null,
                                                ])@endcomponent

                                                @if($reciboReflectivoAccionId && $esCosturaAreaRef && $tieneEncargadoAsignado)
                                                    @if(!$reciboCompletadoArea)
                                                        <button class="btn-completar-costura" 
                                                                type="button"
                                                                id="btn-completar-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                data-recibo-id="{{ $reciboReflectivoAccionId }}"
                                                                data-es-parcial="{{ $esReciboReflectivoParcial ? '1' : '0' }}"
                                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                onclick="completarCostura(this); return false;">
                                                            <span class="material-symbols-rounded">check_circle</span>
                                                            COMPLETAR {{ strtoupper('REFLECTIVO') }}
                                                        </button>
                                                    @else
                                                        <button class="btn-deshacer-costura" 
                                                                type="button"
                                                                id="btn-deshacer-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                data-recibo-id="{{ $reciboReflectivoAccionId }}"
                                                                data-es-parcial="{{ $esReciboReflectivoParcial ? '1' : '0' }}"
                                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                onclick="deshacerCostura(this); return false;">
                                                            <span class="material-symbols-rounded">undo</span>
                                                            DESHACER {{ strtoupper('REFLECTIVO') }}
                                                        </button>
                                                    @endif
                                                @endif
                                            @endif
                                        @endif
                                        @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('vista-costura') || auth()->user()->hasRole('administrador-costura'))
                                            {{-- Para costura-reflectivo/lider-reflectivo/vista-costura/administrador-costura, crear un Boton por cada TIPO de recibo (sin duplicados) --}}
                                            @php
                                                $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                            @endphp
                                            @foreach($tiposUnicos as $tipoReciboUnico)
                                                @php
                                                    // Omitir REFLECTIVO porque ya tiene su propio bloque arriba
                                                    if (strtoupper($tipoReciboUnico) === 'REFLECTIVO') {
                                                        continue;
                                                    }

                                                    $reciboTipo = \App\Support\Operario\OperarioDashboardViewHelper::seleccionarRecibo(
                                                        $prenda['recibos'] ?? [],
                                                        (string) $tipoReciboUnico,
                                                        auth()->user()->hasAnyRole(['vista-costura', 'administrador-costura'])
                                                    );
                                                    if (!$reciboTipo) {
                                                        continue;
                                                    }
                                                    $pedidoParcialId = $reciboTipo['pedido_parcial_id'] ?? null;
                                                    $consecutivoParcial = $reciboTipo['consecutivo_parcial'] ?? ($reciboTipo['consecutivo_actual'] ?? null);
                                                @endphp
                                                @component('components.botones.ver-recibo', [
                                                    'numeroPedido' => $prenda['numero_pedido'],
                                                    'prendaId' => $prenda['prenda_id'],
                                                    'nombrePrenda' => $prenda['nombre_prenda'],
                                                    'tipoRecibo' => strtoupper((string) $tipoReciboUnico),
                                                    'idParcial' => $pedidoParcialId ? (int) $pedidoParcialId : null,
                                                    'consecutivo' => $consecutivoParcial ?? '',
                                                    'reciboId' => $reciboTipo['recibo_id'] ?? ($reciboTipo['id'] ?? null),
                                                ])@endcomponent
                                            @endforeach

                                            {{-- Botones de completar/deshacer para costura-reflectivo, lider-reflectivo y administrador-costura --}}
                                            @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('administrador-costura'))
                                                @php
                                                    $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                                @endphp
                                                @foreach($tiposUnicos as $tipoReciboUnico)
                                                    @php
                                                        // Omitir REFLECTIVO porque ya tiene su propio bloque arriba
                                                        if (strtoupper($tipoReciboUnico) === 'REFLECTIVO') {
                                                            continue;
                                                        }

                                                        $reciboTipo = \App\Support\Operario\OperarioDashboardViewHelper::seleccionarRecibo(
                                                            $prenda['recibos'] ?? [],
                                                            (string) $tipoReciboUnico,
                                                            auth()->user()->hasAnyRole(['vista-costura', 'administrador-costura'])
                                                        );
                                                        $reciboAccionId = $reciboTipo['id'] ?? ($reciboTipo['pedido_parcial_id'] ?? null);
                                                        $esReciboParcial = !empty($reciboTipo['es_parcial']);
                                                        $areaRecibo = strtolower(trim((string) ($reciboTipo['area'] ?? '')));
                                                        $esCosturaArea = $areaRecibo === 'costura';
                                                        $reciboCompletadoArea = false;

                                                        // Verificar si esta completado segun el Area
                                                        if ($esCosturaArea) {
                                                            $reciboCompletadoArea = (bool) ($reciboTipo['completado_costura'] ?? false);
                                                        } else {
                                                            $reciboCompletadoArea = (bool) ($reciboTipo['completado_area'] ?? false);
                                                        }

                                                        // Para COSTURA: verificar que el encargado tenga rol costura-reflectivo si es lider-reflectivo
                                                        // Para costura-reflectivo y administrador-costura: permitir siempre
                                                        $tieneEncargadoAsignado = false;
                                                        $esLiderReflectivo = auth()->user()->hasRole('lider-reflectivo');

                                                        if ($esLiderReflectivo) {
                                                            $encargadoCostura = $reciboTipo['encargado_costura'] ?? null;
                                                            $encargadoCostura = is_string($encargadoCostura) ? trim($encargadoCostura) : $encargadoCostura;
                                                            if (!empty($encargadoCostura)) {
                                                                $nombresCosturaReflectivoNormalizados = collect($nombresCosturaReflectivo ?? [])
                                                                    ->filter(fn($nombre) => is_string($nombre) && trim($nombre) !== '')
                                                                    ->map(fn($nombre) => strtolower(trim($nombre)))
                                                                    ->values()
                                                                    ->all();
                                                                $tieneEncargadoAsignado = in_array(
                                                                    strtolower($encargadoCostura),
                                                                    $nombresCosturaReflectivoNormalizados,
                                                                    true
                                                                );
                                                            }
                                                        } else {
                                                            // Para costura-reflectivo y administrador-costura: permitir siempre
                                                            $tieneEncargadoAsignado = true;
                                                        }

                                                        $tipoReciboNormalizado = strtolower($tipoReciboUnico);
                                                    @endphp

                                                    @if($reciboAccionId && $esCosturaArea && $tieneEncargadoAsignado)
                                                        @if(!$reciboCompletadoArea)
                                                            <button class="btn-completar-costura" 
                                                                    type="button"
                                                                    id="btn-completar-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                    data-recibo-id="{{ $reciboAccionId }}"
                                                                    data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                    data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                     onclick="completarCostura(this); return false;">
                                                                <span class="material-symbols-rounded">check_circle</span>
                                                                COMPLETAR {{ strtoupper($tipoReciboUnico) }}
                                                            </button>
                                                        @else
                                                            <button class="btn-deshacer-costura" 
                                                                    type="button"
                                                                    id="btn-deshacer-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                    data-recibo-id="{{ $reciboAccionId }}"
                                                                    data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                    data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                     onclick="deshacerCostura(this); return false;">
                                                                <span class="material-symbols-rounded">undo</span>
                                                                DESHACER {{ strtoupper($tipoReciboUnico) }}
                                                            </button>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            @endif
                                        @else
                                            {{-- Para otros operarios, un solo Boton con tipo de recibo --}}
                                            @component('components.botones.ver-recibo', [
                                                'numeroPedido' => $prenda['numero_pedido'],
                                                'prendaId' => $prenda['prenda_id'],
                                                'nombrePrenda' => $prenda['nombre_prenda'],
                                                'tipoRecibo' => $tipoReciboPreferido,
                                                'idParcial' => $parcialIdPreferido,
                                                'consecutivo' => $consecutivoPreferido,
                                                'reciboId' => $reciboParaBusqueda['recibo_id'] ?? ($reciboParaBusqueda['id'] ?? null),
                                                'texto' => 'VER RECIBOS',
                                            ])@endcomponent
                                        @endif
                                    </div>

                                    <!-- Mobile Actions Drawer -->
                                    <div class="mobile-actions-drawer" id="mobile-drawer-{{ $prenda['prenda_id'] }}">
                                        @if(auth()->user()->hasRole('cortador'))
                                            @php
                                                $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                                $areaRecibo = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));
                                                $esCorteRecibo = $areaRecibo === 'corte';
                                                $esCosturaRecibo = $areaRecibo === 'costura';
                                                $reciboId = $reciboPrincipal['id'] 
                                                    ?? $reciboPrincipal['recibo_id'] 
                                                    ?? $reciboPrincipal['consecutivo_actual'] 
                                                    ?? null;
                                            @endphp

                                            {{-- Boton para cortadores: Marcar como completado (pasa a Costura) --}}
                                            @if($esCorteRecibo && $reciboId)
                                                <button class="btn-completar-corte" 
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboId }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="completarCorte(this)">
                                                    <span class="material-symbols-rounded">check_circle</span>
                                                    MARCAR COMPLETADO
                                                </button>
                                            @endif

                                            {{-- Boton para cortadores: Deshacer (regresa a Corte) --}}
                                            @if($esCosturaRecibo && $reciboId)
                                                <button class="btn-deshacer-corte" 
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboId }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="deshacerCorte(this)">
                                                    <span class="material-symbols-rounded">undo</span>
                                                    DESHACER
                                                </button>
                                            @endif
                                        @endif

                                        @if(auth()->user()->hasAnyRole(['costurero', 'confeccion-sobremedida']))
                                            @php
                                                $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                                $areaRecibo = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));
                                                $esCosturaRecibo = $areaRecibo === 'costura';
                                                $reciboAccionId = $reciboPrincipal['id'] ?? null;
                                                $esReciboParcial = false;
                                                $reciboCompletadoCostura = (bool) ($reciboPrincipal['completado_costura'] ?? false);
                                            @endphp

                                            {{-- Boton para costureros: Marcar como completado (sin cambiar de Area) --}}
                                            @if($esCosturaRecibo && $reciboAccionId && !$reciboCompletadoCostura)
                                                <button class="btn-completar-costura" 
                                                        type="button"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboAccionId }}"
                                                        data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                         onclick="completarCostura(this); return false;">
                                                    <span class="material-symbols-rounded">check_circle</span>
                                                    COMPLETAR
                                                </button>
                                            @endif

                                            {{-- Boton para costureros: Deshacer (regresa a pendiente) --}}
                                            @if($esCosturaRecibo && $reciboAccionId && $reciboCompletadoCostura)
                                                <button class="btn-deshacer-costura" 
                                                        type="button"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboAccionId }}"
                                                        data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                         onclick="deshacerCostura(this); return false;">
                                                    <span class="material-symbols-rounded">undo</span>
                                                    DESHACER
                                                </button>
                                            @endif
                                        @endif

                                        {{-- Botones mobile para costura-reflectivo, lider-reflectivo, administrador-costura --}}
                                        @if(auth()->user()->hasAnyRole(['costura-reflectivo', 'lider-reflectivo', 'administrador-costura']))
                                            @php
                                                $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                            @endphp
                                            @foreach($tiposUnicos as $tipoReciboUnico)
                                                @php
                                                    $reciboTipo = \App\Support\Operario\OperarioDashboardViewHelper::seleccionarRecibo(
                                                        $prenda['recibos'] ?? [],
                                                        (string) $tipoReciboUnico,
                                                        auth()->user()->hasAnyRole(['vista-costura', 'administrador-costura'])
                                                    );
                                                    $reciboAccionId = $reciboTipo['id'] ?? null;
                                                    $esReciboParcial = false;
                                                    $areaRecibo = strtolower(trim((string) ($reciboTipo['area'] ?? '')));
                                                    $esCosturaArea = $areaRecibo === 'costura';
                                                    $reciboCompletadoArea = false;

                                                    // Verificar si esta completado segun el Area
                                                    if ($esCosturaArea) {
                                                        $reciboCompletadoArea = (bool) ($reciboTipo['completado_costura'] ?? false);
                                                    } else {
                                                        $reciboCompletadoArea = (bool) ($reciboTipo['completado_area'] ?? false);
                                                    }

                                                    $tipoReciboNormalizado = strtolower($tipoReciboUnico);
                                                @endphp

                                                @if($reciboAccionId && $esCosturaArea)
                                                    @if(!$reciboCompletadoArea)
                                                        <button class="btn-completar-costura" 
                                                                type="button"
                                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                data-recibo-id="{{ $reciboAccionId }}"
                                                                data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                 onclick="completarCostura(this); return false;">
                                                            <span class="material-symbols-rounded">check_circle</span>
                                                            COMPLETAR {{ strtoupper($tipoReciboUnico) }}
                                                        </button>
                                                    @else
                                                        <button class="btn-deshacer-costura" 
                                                                type="button"
                                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                data-recibo-id="{{ $reciboAccionId }}"
                                                                data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                 onclick="deshacerCostura(this); return false;">
                                                            <span class="material-symbols-rounded">undo</span>
                                                            DESHACER {{ strtoupper($tipoReciboUnico) }}
                                                        </button>
                                                    @endif
                                                @endif
                                            @endforeach
                                        @endif

                                        @if(auth()->user()->hasRole('vista-costura'))
                                            @foreach($prenda['recibos'] ?? [] as $reciboItem)
                                                @php
                                                    if (strtoupper((string) ($reciboItem['tipo_recibo'] ?? '')) !== 'COSTURA') {
                                                        continue;
                                                    }

                                                    $areaActual = $reciboItem['area'] ?? null;
                                                    $procesoId = $reciboItem['proceso_id_costura'] ?? null;
                                                    $encargadoCostura = $reciboItem['encargado_costura'] ?? null;
                                                    $consecutivoActual = $reciboItem['consecutivo_actual'] ?? $prenda['numero_pedido'];

                                                    $esCC = in_array(strtolower(trim($areaActual ?? '')), ['control calidad', 'control de calidad']);
                                                    $esCosturaProceso = strtolower(trim($areaActual ?? '')) === 'costura';
                                                    $encargadoCostura = is_string($encargadoCostura) ? trim($encargadoCostura) : $encargadoCostura;
                                                    $tieneEncargadoCostura = !empty($encargadoCostura);
                                                    $mostrarComoDeshacerCostura = $esCosturaProceso && $tieneEncargadoCostura;
                                                @endphp

                                                {{-- Boton "Pasar a Costura" o "DESHACER COSTURA" solo para recibos tipo COSTURA --}}
                                                <button type="button" class="btn-pasar-costura {{ $mostrarComoDeshacerCostura ? 'btn-deshacer-costura' : '' }}"
                                                        id="btn-costura-{{ $prenda['prenda_id'] }}-{{ $consecutivoActual }}"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        data-tipo-recibo="COSTURA"
                                                        data-recibo="{{ $consecutivoActual }}"
                                                        data-area="{{ $areaActual ?? '' }}"
                                                        data-proceso-id="{{ $procesoId }}"
                                                        data-encargado-costura="{{ is_string($encargadoCostura ?? null) ? trim($encargadoCostura) : ($encargadoCostura ?? '') }}"
                                                        data-parcial-id="{{ $reciboItem['pedido_parcial_id'] ?? '' }}"
                                                        onclick="manejarPasarACostura(this); return false;">
                                                    <span class="material-symbols-rounded">{{ $mostrarComoDeshacerCostura ? 'undo' : 'checkroom' }}</span>
                                                    {{ $mostrarComoDeshacerCostura ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                                                </button>

                                                {{-- Boton "Pasar a C.C" o "DESHACER" --}}
                                                <button type="button" class="btn-pasar-cc"
                                                        id="btn-cc-{{ $prenda['prenda_id'] }}-{{ $consecutivoActual }}"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        data-tipo-recibo="COSTURA"
                                                        data-recibo="{{ $consecutivoActual }}"
                                                        data-area="{{ $areaActual ?? 'COSTURA' }}"
                                                        data-proceso-id="{{ $procesoId }}"
                                                        onclick="pasarAControlCalidad(this); return false;">
                                                    <span class="material-symbols-rounded">{{ $esCC ? 'undo' : 'check_circle' }}</span>
                                                    {{ $esCC ? 'DESHACER' : 'PASAR A C.C' }}
                                                </button>
                                            @endforeach
                                        @endif

                                        @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('vista-costura') || auth()->user()->hasRole('administrador-costura'))
                                            @php
                                                $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                            @endphp
                                            @foreach($tiposUnicos as $tipoReciboUnico)
                                                @php
                                                    $reciboTipo = \App\Support\Operario\OperarioDashboardViewHelper::seleccionarRecibo(
                                                        $prenda['recibos'] ?? [],
                                                        (string) $tipoReciboUnico,
                                                        auth()->user()->hasAnyRole(['vista-costura', 'administrador-costura'])
                                                    );
                                                    if (!$reciboTipo) {
                                                        continue;
                                                    }
                                                    $pedidoParcialId = $reciboTipo['pedido_parcial_id'] ?? null;
                                                    $consecutivoParcial = $reciboTipo['consecutivo_parcial'] ?? ($reciboTipo['consecutivo_actual'] ?? null);
                                                @endphp
                                                @component('components.botones.ver-recibo', [
                                                    'numeroPedido' => $prenda['numero_pedido'],
                                                    'prendaId' => $prenda['prenda_id'],
                                                    'nombrePrenda' => $prenda['nombre_prenda'],
                                                    'tipoRecibo' => strtoupper((string) $tipoReciboUnico),
                                                    'idParcial' => $pedidoParcialId ? (int) $pedidoParcialId : null,
                                                    'consecutivo' => $consecutivoParcial ?? '',
                                                    'reciboId' => $reciboTipo['recibo_id'] ?? ($reciboTipo['id'] ?? null),
                                                ])@endcomponent
                                            @endforeach
                                        @else
                                            @component('components.botones.ver-recibo', [
                                                'numeroPedido' => $prenda['numero_pedido'],
                                                'prendaId' => $prenda['prenda_id'],
                                                'nombrePrenda' => $prenda['nombre_prenda'],
                                                'tipoRecibo' => $tipoReciboPreferido,
                                                'idParcial' => $parcialIdPreferido,
                                                'consecutivo' => $consecutivoPreferido,
                                                'reciboId' => $reciboParaBusqueda['recibo_id'] ?? ($reciboParaBusqueda['id'] ?? null),
                                                'texto' => 'VER RECIBOS',
                                            ])@endcomponent
                                        @endif

                                        @if(auth()->user()->hasRole('cortador'))
                                            @php
                                                $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                                $areaRecibo = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));
                                                $esCorteRecibo = $areaRecibo === 'corte';
                                                $esCosturaRecibo = $areaRecibo === 'costura';
                                                $reciboId = $reciboPrincipal['id'] 
                                                    ?? $reciboPrincipal['recibo_id'] 
                                                    ?? $reciboPrincipal['consecutivo_actual'] 
                                                    ?? null;
                                            @endphp

                                            @if($esCorteRecibo && $reciboId)
                                                <button class="btn-completar-corte" 
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboId }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="completarCorte(this)">
                                                    <span class="material-symbols-rounded">check_circle</span>
                                                    MARCAR COMPLETADO
                                                </button>
                                            @elseif($esCosturaRecibo && $reciboId)
                                                <button class="btn-deshacer-corte" 
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboId }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="deshacerCorte(this)">
                                                    <span class="material-symbols-rounded">undo</span>
                                                    DESHACER
                                                </button>
                                            @else
                                                @component('components.botones.agregar-novedad', [
                                                    'numeroPedido' => $prenda['numero_pedido'],
                                                    'prendaId' => $prenda['prenda_id'],
                                                    'nombrePrenda' => $prenda['nombre_prenda'],
                                                    'consecutivo' => isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'],
                                                ])@endcomponent
                                            @endif
                                        @else
                                        @component('components.botones.agregar-novedad', [
                                            'numeroPedido' => $prenda['numero_pedido'],
                                            'prendaId' => $prenda['prenda_id'],
                                            'nombrePrenda' => $prenda['nombre_prenda'],
                                            'consecutivo' => isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'],
                                        ])@endcomponent
                                    @endif
                                        <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] ?? 'null' }})">
                                            <span class="material-symbols-rounded">more_horiz</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Contenido Derecho -->
                                <div class="orden-right">
                                    <div class="orden-right-center">
                                        <a href="#" class="action-arrow" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] ?? 'null' }}, '{{ $prenda['nombre_prenda'] }}', '{{ $tipoReciboPreferido }}', {{ $parcialIdPreferido ?? 'null' }}, '{{ $consecutivoPreferido }}', {{ $reciboParaBusqueda['recibo_id'] ?? ($reciboParaBusqueda['id'] ?? 'null') }}); return false;">
                                            <span class="material-symbols-rounded">arrow_forward</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
