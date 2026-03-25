@extends('operario.layout')

@section('title', 'Mis Órdenes')
@section('page-title', '')

@php
    // Helper para obtener clase de estado
    function getEstadoClass($estado) {
        $estado = strtolower(trim($estado));
        if (strpos($estado, 'ejecución') !== false || strpos($estado, 'proceso') !== false) {
            return 'en-proceso';
        }
        if (strpos($estado, 'completada') !== false || strpos($estado, 'completado') !== false) {
            return 'completada';
        }
        return 'pendiente';
    }
@endphp

@section('content')
<div class="operario-dashboard {{ auth()->user()->hasRole('vista-costura') ? 'is-vista-costura' : '' }}"
     data-user-id="{{ Auth::id() }}"
     data-user-role="{{ Auth::user()->roles->first()->name ?? '' }}"
     data-user-name="{{ Auth::user()->name ?? '' }}">
    <!-- Búsqueda -->
    <div class="search-section">
        <span class="material-symbols-rounded">search</span>
        <input type="text" id="searchInput" class="search-box" placeholder="Buscar por Consecutivo, Prenda o Cliente...">
        <button id="clearFilterBtn" class="clear-filter-btn" title="Limpiar filtro" style="display: none;">
            <span class="material-symbols-rounded">close</span>
        </button>
    </div>

    <!-- Mis Prendas Section -->
    <div class="ordenes-section">
        <div class="section-title">
            <span class="material-symbols-rounded">checkroom</span>
            <h3>RECIBOS DE COSTURA</h3>
            <span class="ordenes-count">{{ count($prendasConRecibos ?? []) }}</span>
        </div>

        <!-- Filtros por tipo de recibo para costura-reflectivo, lider-reflectivo y vista-costura -->
        @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('vista-costura'))
        <div class="filtros-badges">
            @if(auth()->user()->hasRole('vista-costura'))
                <button class="badge-filtro badge-filtro-active" data-filtro="costura" onclick="filtrarPrendasPorRecibo('costura')">
                    <span class="material-symbols-rounded">checkroom</span>
                    Costura
                </button>
                <button class="badge-filtro" data-filtro="reflectivo" onclick="filtrarPrendasPorRecibo('reflectivo')">
                    <span class="material-symbols-rounded">auto_awesome</span>
                    Reflectivo
                </button>
            @else
                <!-- Para costura-reflectivo y lider-reflectivo: mostrar ambos tags -->
                <button class="badge-filtro badge-filtro-active" data-filtro="costura" onclick="filtrarPrendasPorRecibo('costura')">
                    <span class="material-symbols-rounded">checkroom</span>
                    Costura
                </button>
                <button class="badge-filtro" data-filtro="reflectivo" onclick="filtrarPrendasPorRecibo('reflectivo')">
                    <span class="material-symbols-rounded">auto_awesome</span>
                    Reflectivo
                </button>
            @endif
        </div>
        @endif

        @if(auth()->user()->hasRole('administrador-costura'))
        <div class="filtros-badges">
            <button type="button" class="badge-filtro {{ ($tab ?? 'costura') === 'costura' ? 'badge-filtro-active' : '' }}" data-admin-tab="costura">
                <span class="material-symbols-rounded">checkroom</span>
                Costura
            </button>
            <button type="button" class="badge-filtro {{ ($tab ?? 'costura') === 'sobremedida' ? 'badge-filtro-active' : '' }}" data-admin-tab="sobremedida">
                <span class="material-symbols-rounded">straighten</span>
                Sobremedida
            </button>
        </div>
        @endif

        <div class="ordenes-list" id="ordenesList">
            @if(count($prendasConRecibos ?? []) > 0)
                @foreach($prendasConRecibos as $prenda)
                    @php
                        $estadoClass = 'pendiente'; // Siempre pendiente, eliminar en-proceso
                        // Determinar tipo de recibo para filtro
                        // Para vista-costura y costura-reflectivo: una prenda puede tener ambos tipos de recibos
                        // Para otros roles: solo muestra reflectivos
                        $tiposRecibos = array_map(function($r) { return strtoupper($r['tipo_recibo']); }, $prenda['recibos']);
                        $tieneReflectivo = in_array('REFLECTIVO', $tiposRecibos);
                        $tieneCostura = in_array('COSTURA', $tiposRecibos) || in_array('COSTURA-BODEGA', $tiposRecibos);
                        
                        // Obtener el área del recibo principal para filtros
                        $reciboPrincipalFiltro = $prenda['recibos'][0] ?? null;
                        $areaReciboFiltro = strtolower(trim((string) ($reciboPrincipalFiltro['area'] ?? '')));
                        
                        // Para vista-costura y costura-reflectivo, guardar ambos tipos en el atributo data
                        if (auth()->user()->hasRole('vista-costura') || auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo')) {
                            // Guardar tipos separados por comas para poder filtrar correctamente
                            $tiposParaFiltro = [];
                            if ($tieneCostura) $tiposParaFiltro[] = 'costura';
                            if ($tieneReflectivo) $tiposParaFiltro[] = 'reflectivo';
                            $esReflectivo = implode(',', $tiposParaFiltro); // "costura,reflectivo" o "costura" o "reflectivo"
                        } else {
                            // Para otros roles, solo mostrar reflectivos
                            $esReflectivo = $tieneReflectivo ? 'reflectivo' : 'costura';
                        }
                        
                        // Por defecto:
                        // - costura-reflectivo: mostrar COSTURA por defecto (pero incluir las que tienen ambos)
                        // - vista-costura: mostrar COSTURA por defecto (pero incluir las que tienen ambos)
                        // - costurero: mostrar COSTURA por defecto
                        // - cortador: mostrar prendas con área "Corte" (independientemente del tipo de recibo)
                        $displayInicial = '';
                        if (auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('vista-costura') || auth()->user()->hasRole('costurero') || auth()->user()->hasRole('administrador-costura')) {
                            // Mostrar por defecto las que tienen costura (incluyendo las que tienen ambos)
                            $displayInicial = $tieneCostura ? '' : 'none';
                        } elseif (auth()->user()->hasRole('cortador')) {
                            // Para cortadores: mostrar las que tienen área "Corte"
                            $displayInicial = $areaReciboFiltro === 'corte' ? '' : 'none';
                        } else {
                            $displayInicial = $tieneReflectivo ? '' : 'none';
                        }
                    @endphp

                    @if(auth()->user()->hasRole('vista-costura') && $areaReciboFiltro === 'corte')
                        @continue
                    @endif

                    @php
                        // Definir variables necesarias para el card
                        $reciboPrincipalCard = $prenda['recibos'][0] ?? null;
                        $reciboCompletadoCostura = (bool) ($reciboPrincipalCard['completado_costura'] ?? false);
                    @endphp

                    <div class="orden-card-simple {{ ((auth()->user()->hasRole('costurero') || auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('administrador-costura')) && $reciboCompletadoCostura) ? 'card-completado-costura' : '' }} {{ $tieneReflectivo ? 'borde-reflectivo' : '' }}" 
                         data-numero="{{ $prenda['numero_pedido'] }}" 
                         data-prenda="{{ strtolower($prenda['nombre_prenda']) }}"
                         data-prenda-id="{{ $prenda['prenda_id'] }}"
                         data-cliente="{{ strtolower($prenda['cliente']) }}"
                         data-tipo-recibo="{{ $esReflectivo }}"
                         data-numero-recibo="{{ $prenda['recibos'][0]['consecutivo_actual'] ?? $prenda['numero_pedido'] }}"
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
                        @endphp
                        <div class="orden-body {{ ($reciboCompletadoArea || (auth()->user()->hasRole('vista-costura') && $completadoVistaSegunArea)) ? 'recibo-completado-area' : '' }}">
                            @php
                                $encargadoVista = null;
                                if ($areaReciboNormalizada === 'corte') {
                                    $encargadoVista = $reciboPrincipal['encargado_corte'] ?? null;
                                } elseif ($areaReciboNormalizada === 'costura') {
                                    $encargadoVista = $reciboPrincipal['encargado_costura'] ?? null;
                                } elseif (in_array($areaReciboNormalizada, ['control calidad', 'control de calidad'], true)) {
                                    $encargadoVista = $reciboPrincipal['encargado_control_calidad'] ?? null;
                                }
                                $encargadoVista = is_string($encargadoVista) ? trim($encargadoVista) : $encargadoVista;
                                
                                // Obtener encargado de corte para mostrar en el card (excepto cortadores)
                                $encargadoCorte = $reciboPrincipal['encargado_corte'] ?? null;
                                $encargadoCorte = is_string($encargadoCorte) ? trim($encargadoCorte) : $encargadoCorte;
                            @endphp
                            @if(!auth()->user()->hasRole('vista-costura') && !auth()->user()->hasRole('cortador') && !auth()->user()->hasRole('costurero'))
                                <div class="orden-encargado-corner" onclick="event.stopPropagation();">
                                    <strong>Encargado:</strong>
                                    <span>{{ $encargadoVista ? strtoupper($encargadoVista) : 'SIN ENCARGADO' }}</span>
                                </div>
                            @endif
                            {{-- Mostrar encargado de corte para todos excepto cortadores --}}
                            @if(!auth()->user()->hasRole('cortador'))
                                <div class="orden-encargado-corte" onclick="event.stopPropagation();" style="background: #fef3c7; padding: 6px 10px; border-radius: 6px; margin-bottom: 8px; display: inline-flex; align-items: center; gap: 8px; width: fit-content;">
                                    <strong style="color: #92400e; font-size: 12px;">Encargado Corte:</strong>
                                    <span style="color: #78350f; font-size: 12px; font-weight: 600;">{{ $encargadoCorte ? strtoupper($encargadoCorte) : 'SIN ASIGNAR' }}</span>
                                </div>
                            @endif
                            @if(auth()->user()->hasRole('vista-costura'))
                                <div class="orden-top-badges" onclick="event.stopPropagation();">
                                    <span class="badge-area">{{ strtoupper($labelAreaVista) }}</span>
                                    <span class="badge-completado-corte {{ $completadoVistaSegunArea ? 'is-on' : '' }}">
                                        {{ $labelEstadoVista }}
                                    </span>
                                    <strong class="label-encargado">Encargado:</strong>
                                    <span class="badge-encargado">
                                        {{ $encargadoVista ? strtoupper($encargadoVista) : 'SIN ENCARGADO' }}
                                    </span>
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
                                        @if(auth()->user()->hasRole('costurero') && $reciboCompletadoCostura)
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
                                    @if(auth()->user()->hasRole('costurero') && $reciboCompletadoCostura)
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
                                    <!-- Botón de más opciones para mobile -->
                                    <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] }})">
                                        <span class="material-symbols-rounded">more_horiz</span>
                                    </button>
                                </div>

                                <div class="orden-cliente">
                                    <p class="cliente-label">CLIENTE</p>
                                    <p class="cliente-name">{{ $prenda['cliente'] }}</p>
                                </div>

                                <!-- Botón Ver Recibo (debajo del estado para mobile) -->
                                <div class="mobile-ver-recibo-section">
                                    <button class="btn-ver-recibos mobile-under-state" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', '{{ $prenda['recibos'][0]['tipo_recibo'] ?? '' }}', {{ !empty($prenda['recibos'][0]['pedido_parcial_id']) ? (int)$prenda['recibos'][0]['pedido_parcial_id'] : 'null' }})">
                                        <span class="material-symbols-rounded">visibility</span>
                                        VER RECIBO
                                    </button>
                                </div>

                                <div class="orden-prendas">
                                    <p class="prendas-label">
                                        <strong>{{ $prenda['nombre_prenda'] }}</strong>
                                        @if($prenda['descripcion'])
                                            {{ $prenda['descripcion'] }}
                                        @endif
                                    </p>        
                                </div>

                                <!-- Contenedor de Botones -->
                                <div class="orden-buttons">
                                    @if(auth()->user()->hasRole('cortador'))
                                        @php
                                            $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                            $areaRecibo = strtolower(trim((string)($reciboPrincipal['area'] ?? '')));
                                            $esCorteRecibo = $areaRecibo === 'corte';
                                            $esCosturaRecibo = $areaRecibo === 'costura';
                                            $reciboId = $reciboPrincipal['id'] ?? null;
                                        @endphp
                                        
                                        {{-- Botón para cortadores: Marcar como completado (pasa a Costura) --}}
                                        @if($esCorteRecibo && $reciboId)
                                            <button class="btn-completar-corte" 
                                                    id="btn-completar-{{ $prenda['prenda_id'] }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    onclick="completarCorte(this)">
                                                <span class="material-symbols-rounded">check_circle</span>
                                                MARCAR COMPLETADO
                                            </button>
                                        @endif
                                        
                                        {{-- Botón para cortadores: Deshacer (regresa a Corte) --}}
                                        @if($esCosturaRecibo && $reciboId)
                                            <button class="btn-deshacer-corte" 
                                                    id="btn-deshacer-{{ $prenda['prenda_id'] }}"
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
                                    
                                    @if(auth()->user()->hasRole('costurero'))
                                        @php
                                            $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                            $areaRecibo = strtolower(trim((string)($reciboPrincipal['area'] ?? '')));
                                            $esCosturaRecibo = $areaRecibo === 'costura';
                                            $reciboId = $reciboPrincipal['id'] ?? null;
                                            $reciboCompletadoCostura = (bool) ($reciboPrincipal['completado_costura'] ?? false);
                                        @endphp
                                        
                                        {{-- Botón para costureros: Marcar como completado (sin cambiar de área) --}}
                                        @if($esCosturaRecibo && $reciboId && !$reciboCompletadoCostura)
                                            <button class="btn-completar-costura" 
                                                    id="btn-completar-costura-{{ $prenda['prenda_id'] }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    onclick="completarCostura(this)">
                                                <span class="material-symbols-rounded">check_circle</span>
                                                COMPLETAR
                                            </button>
                                        @endif
                                        
                                        {{-- Botón para costureros: Deshacer (regresa a pendiente) --}}
                                        @if($esCosturaRecibo && $reciboId && $reciboCompletadoCostura)
                                            <button class="btn-deshacer-costura" 
                                                    id="btn-deshacer-costura-{{ $prenda['prenda_id'] }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    onclick="deshacerCostura(this)">
                                                <span class="material-symbols-rounded">undo</span>
                                                DESHACER
                                            </button>
                                        @endif
                                    @endif
                                    
                                    @if(auth()->user()->hasRole('vista-costura'))
                                        @php
                                            $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                            $areaActual = $prenda['recibos'][0]['area'] ?? null;
                                            $procesoId = $prenda['recibos'][0]['proceso_id_costura'] ?? null;
                                            $encargadoCostura = $prenda['recibos'][0]['encargado_costura'] ?? null;
                                            $tipoRecibo = strtoupper($tiposUnicos->first() ?? 'COSTURA');
                                            $esCC = in_array(strtolower(trim($areaActual ?? '')), ['control calidad', 'control de calidad']);
                                            $esCosturaProceso = strtolower(trim($areaActual ?? '')) === 'costura';
                                            $esTipoReciboCostura = in_array('COSTURA', $tiposUnicos->toArray());
                                            $encargadoCostura = is_string($encargadoCostura) ? trim($encargadoCostura) : $encargadoCostura;
                                            $tieneEncargadoCostura = !empty($encargadoCostura);
                                            $mostrarComoDeshacerCostura = $esCosturaProceso && $tieneEncargadoCostura;
                                        @endphp

                                        {{-- Botón "Pasar a Costura" o "DESHACER COSTURA" solo para recibos tipo COSTURA --}}
                                        @if($esTipoReciboCostura)
                                            <button class="btn-pasar-costura {{ $mostrarComoDeshacerCostura ? 'btn-deshacer-costura' : '' }}" 
                                                    id="btn-costura-{{ $prenda['prenda_id'] }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-numero-pedido="{{ $prenda['numero_pedido'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    data-tipo-recibo="COSTURA"
                                                    data-recibo="{{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }}"
                                                    data-area="{{ $areaActual ?? '' }}"
                                                    data-proceso-id="{{ $procesoId }}"
                                                    data-encargado-costura="{{ is_string($encargadoCostura ?? null) ? trim($encargadoCostura) : ($encargadoCostura ?? '') }}"
                                                    onclick="manejarPasarACostura(this)">
                                                <span class="material-symbols-rounded">{{ $mostrarComoDeshacerCostura ? 'undo' : 'checkroom' }}</span>
                                                {{ $mostrarComoDeshacerCostura ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                                            </button>
                                        @endif

                                        {{-- Botón "Pasar a C.C" o "DESHACER" --}}
                                        <button class="btn-pasar-cc" 
                                                id="btn-cc-{{ $prenda['prenda_id'] }}"
                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                data-tipo-recibo="{{ $tipoRecibo }}"
                                                data-recibo="{{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }}"
                                                data-area="{{ $areaActual ?? 'COSTURA' }}"
                                                data-proceso-id="{{ $procesoId }}"
                                                onclick="pasarAControlCalidad(this)">
                                            <span class="material-symbols-rounded">{{ $esCC ? 'undo' : 'check_circle' }}</span>
                                            {{ $esCC ? 'DESHACER' : 'PASAR A C.C' }}
                                        </button>

                                        {{-- Botón "Ver Distribución" para vista-costura --}}
                                        @php
                                            $reciboId = $prenda['recibos'][0]['id'] ?? null;
                                        @endphp
                                        @if($reciboId)
                                            <button class="btn-ver-distribucion" 
                                                    id="btn-distribucion-{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-numero-recibo="{{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }}"
                                                    onclick="abrirDistribucionRecibo(this)">
                                                <span class="material-symbols-rounded">share</span>
                                                VER DISTRIBUCIÓN
                                            </button>
                                        @endif
                                    @endif
                                    
                                    <button class="btn-agregar-novedad" onclick="abrirModalNovedad('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', {{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }})">
                                        <span class="material-symbols-rounded">comment</span>
                                        AGREGAR NOVEDAD
                                    </button>
                                    @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('vista-costura'))
                                        @php
                                            $reciboReflectivo = collect($prenda['recibos'] ?? [])->first(function($r) {
                                                return strtoupper((string)($r['tipo_recibo'] ?? '')) === 'REFLECTIVO';
                                            });
                                            $tieneReciboReflectivo = !empty($reciboReflectivo);
                                            $encargadoCosturaRef = $reciboReflectivo['encargado_costura'] ?? null;
                                            $encargadoCosturaRef = is_string($encargadoCosturaRef) ? trim($encargadoCosturaRef) : $encargadoCosturaRef;
                                            $tieneEncargadoCosturaRef = !empty($encargadoCosturaRef);
                                            $areaReciboRef = $reciboReflectivo['area'] ?? '';
                                            $esCosturaAreaRef = strtolower(trim((string)$areaReciboRef)) === 'costura';
                                        @endphp
                                        
                                        {{-- Botón PASAR A COSTURA/DESHACER COSTURA para vista-costura --}}
                                        @if($tieneReciboReflectivo && auth()->user()->hasRole('vista-costura'))
                                            @php
                                                $pedidoParcialId = $reciboReflectivo['pedido_parcial_id'] ?? null;
                                            @endphp
                                            
                                            {{-- Botón VER RECIBO para vista-costura --}}
                                            <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', 'VER RECIBO', {{ $pedidoParcialId ? (int)$pedidoParcialId : 'null' }})">
                                                <span class="material-symbols-rounded">visibility</span>
                                                VER RECIBO
                                            </button>
                                            
                                            <button class="btn-pasar-costura {{ $tieneEncargadoCosturaRef ? 'btn-deshacer-costura' : '' }}" 
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
                                                    onclick="manejarPasarACostura(this)">
                                                <span class="material-symbols-rounded">{{ $tieneEncargadoCosturaRef ? 'undo' : 'checkroom' }}</span>
                                                {{ $tieneEncargadoCosturaRef ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                                            </button>
                                        @endif
                                        
                                        {{-- Botones de completar/deshacer para REFLECTIVO (solo para costura-reflectivo y lider-reflectivo) --}}
                                        @if($tieneReciboReflectivo && (auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo')))
                                            @php
                                                $reciboId = $reciboReflectivo['id'] ?? null;
                                                $reciboCompletadoArea = false;
                                                
                                                // Verificar si está completado según el área
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
                                                $pedidoParcialId = $reciboReflectivo['pedido_parcial_id'] ?? null;
                                            @endphp
                                            
                                            {{-- Botón VER RECIBO para REFLECTIVO --}}
                                            <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', 'VER RECIBO', {{ $pedidoParcialId ? (int)$pedidoParcialId : 'null' }})">
                                                <span class="material-symbols-rounded">visibility</span>
                                                VER RECIBO
                                            </button>
                                            
                                            @if($reciboId && $esCosturaAreaRef && $tieneEncargadoAsignado)
                                                @if(!$reciboCompletadoArea)
                                                    <button class="btn-completar-costura" 
                                                            id="btn-completar-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                            data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                            data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                            data-recibo-id="{{ $reciboId }}"
                                                            data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                            data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                            onclick="completarCostura(this)">
                                                        <span class="material-symbols-rounded">check_circle</span>
                                                        COMPLETAR {{ strtoupper('REFLECTIVO') }}
                                                    </button>
                                                @else
                                                    <button class="btn-deshacer-costura" 
                                                            id="btn-deshacer-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                            data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                            data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                            data-recibo-id="{{ $reciboId }}"
                                                            data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                            data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                            onclick="deshacerCostura(this)">
                                                        <span class="material-symbols-rounded">undo</span>
                                                        DESHACER {{ strtoupper('REFLECTIVO') }}
                                                    </button>
                                                @endif
                                            @endif
                                        @endif
                                    @endif
                                    @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('vista-costura') || auth()->user()->hasRole('administrador-costura'))
                                        {{-- Para costura-reflectivo/lider-reflectivo/vista-costura/administrador-costura, crear un botón por cada TIPO de recibo (sin duplicados) --}}
                                        @php
                                            $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                        @endphp
                                        @foreach($tiposUnicos as $tipoReciboUnico)
                                            @php
                                                // Omitir REFLECTIVO porque ya tiene su propio bloque arriba
                                                if (strtoupper($tipoReciboUnico) === 'REFLECTIVO') {
                                                    continue;
                                                }
                                                
                                                $reciboTipo = collect($prenda['recibos'] ?? [])->first(function($r) use ($tipoReciboUnico) {
                                                    return strtoupper((string)($r['tipo_recibo'] ?? '')) === strtoupper((string)$tipoReciboUnico);
                                                });
                                                $pedidoParcialId = $reciboTipo['pedido_parcial_id'] ?? null;
                                            @endphp
                                            <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', 'VER RECIBO', {{ $pedidoParcialId ? (int)$pedidoParcialId : 'null' }})">
                                                <span class="material-symbols-rounded">visibility</span>
                                                VER RECIBO
                                            </button>
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
                                                    
                                                    $reciboTipo = collect($prenda['recibos'] ?? [])->first(function($r) use ($tipoReciboUnico) {
                                                        return strtoupper((string)($r['tipo_recibo'] ?? '')) === strtoupper((string)$tipoReciboUnico);
                                                    });
                                                    $reciboId = $reciboTipo['id'] ?? null;
                                                    $areaRecibo = strtolower(trim((string)($reciboTipo['area'] ?? '')));
                                                    $esCosturaArea = $areaRecibo === 'costura';
                                                    $reciboCompletadoArea = false;
                                                    
                                                    // Verificar si está completado según el área
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
                                                            $encargadoUsuario = \App\Models\User::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($encargadoCostura)])->first();
                                                            $tieneEncargadoAsignado = $encargadoUsuario && $encargadoUsuario->hasRole('costura-reflectivo');
                                                        }
                                                    } else {
                                                        // Para costura-reflectivo y administrador-costura: permitir siempre
                                                        $tieneEncargadoAsignado = true;
                                                    }
                                                    
                                                    $tipoReciboNormalizado = strtolower($tipoReciboUnico);
                                                @endphp
                                                
                                                @if($reciboId && $esCosturaArea && $tieneEncargadoAsignado)
                                                    @if(!$reciboCompletadoArea)
                                                        <button class="btn-completar-costura" 
                                                                id="btn-completar-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                data-recibo-id="{{ $reciboId }}"
                                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                onclick="completarCostura(this)">
                                                            <span class="material-symbols-rounded">check_circle</span>
                                                            COMPLETAR {{ strtoupper($tipoReciboUnico) }}
                                                        </button>
                                                    @else
                                                        <button class="btn-deshacer-costura" 
                                                                id="btn-deshacer-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                data-recibo-id="{{ $reciboId }}"
                                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                onclick="deshacerCostura(this)">
                                                            <span class="material-symbols-rounded">undo</span>
                                                            DESHACER {{ strtoupper($tipoReciboUnico) }}
                                                        </button>
                                                    @endif
                                                @endif
                                            @endforeach
                                        @endif
                                    @else
                                        {{-- Para otros operarios, un solo botón con tipo de recibo --}}
                                        <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', '{{ $prenda['recibos'][0]['tipo_recibo'] ?? '' }}', {{ !empty($prenda['recibos'][0]['pedido_parcial_id']) ? (int)$prenda['recibos'][0]['pedido_parcial_id'] : 'null' }})">
                                            <span class="material-symbols-rounded">visibility</span>
                                            VER RECIBOS
                                        </button>
                                    @endif
                                </div>

                                <!-- Mobile Actions Drawer -->
                                <div class="mobile-actions-drawer" id="mobile-drawer-{{ $prenda['prenda_id'] }}">
                                    @if(auth()->user()->hasRole('cortador'))
                                        @php
                                            $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                            $areaRecibo = strtolower(trim((string)($reciboPrincipal['area'] ?? '')));
                                            $esCorteRecibo = $areaRecibo === 'corte';
                                            $esCosturaRecibo = $areaRecibo === 'costura';
                                            $reciboId = $reciboPrincipal['id'] ?? null;
                                        @endphp
                                        
                                        {{-- Botón para cortadores: Marcar como completado (pasa a Costura) --}}
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
                                        
                                        {{-- Botón para cortadores: Deshacer (regresa a Corte) --}}
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
                                    
                                    @if(auth()->user()->hasRole('costurero'))
                                        @php
                                            $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                            $areaRecibo = strtolower(trim((string)($reciboPrincipal['area'] ?? '')));
                                            $esCosturaRecibo = $areaRecibo === 'costura';
                                            $reciboId = $reciboPrincipal['id'] ?? null;
                                            $reciboCompletadoCostura = (bool) ($reciboPrincipal['completado_costura'] ?? false);
                                        @endphp
                                        
                                        {{-- Botón para costureros: Marcar como completado (sin cambiar de área) --}}
                                        @if($esCosturaRecibo && $reciboId && !$reciboCompletadoCostura)
                                            <button class="btn-completar-costura" 
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    onclick="completarCostura(this)">
                                                <span class="material-symbols-rounded">check_circle</span>
                                                COMPLETAR
                                            </button>
                                        @endif
                                        
                                        {{-- Botón para costureros: Deshacer (regresa a pendiente) --}}
                                        @if($esCosturaRecibo && $reciboId && $reciboCompletadoCostura)
                                            <button class="btn-deshacer-costura" 
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    onclick="deshacerCostura(this)">
                                                <span class="material-symbols-rounded">undo</span>
                                                DESHACER
                                            </button>
                                        @endif
                                    @endif
                                    
                                    {{-- Botones mobile para costura-reflectivo --}}
                                    @if(auth()->user()->hasRole('costura-reflectivo'))
                                        @php
                                            $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                        @endphp
                                        @foreach($tiposUnicos as $tipoReciboUnico)
                                            @php
                                                $reciboTipo = collect($prenda['recibos'] ?? [])->first(function($r) use ($tipoReciboUnico) {
                                                    return strtoupper((string)($r['tipo_recibo'] ?? '')) === strtoupper((string)$tipoReciboUnico);
                                                });
                                                $reciboId = $reciboTipo['id'] ?? null;
                                                $areaRecibo = strtolower(trim((string)($reciboTipo['area'] ?? '')));
                                                $esCosturaArea = $areaRecibo === 'costura';
                                                $reciboCompletadoArea = false;
                                                
                                                // Verificar si está completado según el área
                                                if ($esCosturaArea) {
                                                    $reciboCompletadoArea = (bool) ($reciboTipo['completado_costura'] ?? false);
                                                } else {
                                                    $reciboCompletadoArea = (bool) ($reciboTipo['completado_area'] ?? false);
                                                }
                                                
                                                $tipoReciboNormalizado = strtolower($tipoReciboUnico);
                                            @endphp
                                            
                                            @if($reciboId && $esCosturaArea)
                                                @if(!$reciboCompletadoArea)
                                                    <button class="btn-completar-costura" 
                                                            data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                            data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                            data-recibo-id="{{ $reciboId }}"
                                                            data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                            data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                            onclick="completarCostura(this)">
                                                        <span class="material-symbols-rounded">check_circle</span>
                                                        COMPLETAR {{ strtoupper($tipoReciboUnico) }}
                                                    </button>
                                                @else
                                                    <button class="btn-deshacer-costura" 
                                                            data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                            data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                            data-recibo-id="{{ $reciboId }}"
                                                            data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                            data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                            onclick="deshacerCostura(this)">
                                                        <span class="material-symbols-rounded">undo</span>
                                                        DESHACER {{ strtoupper($tipoReciboUnico) }}
                                                    </button>
                                                @endif
                                            @endif
                                        @endforeach
                                    @endif
                                    
                                    @if(auth()->user()->hasRole('vista-costura'))
                                        @php
                                            $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                            $areaActual = $prenda['recibos'][0]['area'] ?? null;
                                            $procesoId = $prenda['recibos'][0]['proceso_id_costura'] ?? null;
                                            $encargadoCostura = $prenda['recibos'][0]['encargado_costura'] ?? null;
                                            $tipoRecibo = strtoupper($tiposUnicos->first() ?? 'COSTURA');
                                            $esCC = in_array(strtolower(trim($areaActual ?? '')), ['control calidad', 'control de calidad']);
                                            $esCosturaProceso = strtolower(trim($areaActual ?? '')) === 'costura';
                                            $esTipoReciboCostura = in_array('COSTURA', $tiposUnicos->toArray());
                                            $encargadoCostura = is_string($encargadoCostura) ? trim($encargadoCostura) : $encargadoCostura;
                                            $tieneEncargadoCostura = !empty($encargadoCostura);
                                            $mostrarComoDeshacerCostura = $esCosturaProceso && $tieneEncargadoCostura;
                                        @endphp

                                        {{-- Botón "Pasar a Costura" o "DESHACER COSTURA" solo para recibos tipo COSTURA --}}
                                        @if($esTipoReciboCostura)
                                            <button class="btn-pasar-costura {{ $mostrarComoDeshacerCostura ? 'btn-deshacer-costura' : '' }}" 
                                                    id="btn-costura-{{ $prenda['prenda_id'] }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                    data-tipo-recibo="COSTURA"
                                                    data-recibo="{{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }}"
                                                    data-area="{{ $areaActual ?? '' }}"
                                                    data-proceso-id="{{ $procesoId }}"
                                                    data-encargado-costura="{{ is_string($encargadoCostura ?? null) ? trim($encargadoCostura) : ($encargadoCostura ?? '') }}"
                                                    onclick="manejarPasarACostura(this)">
                                                <span class="material-symbols-rounded">{{ $mostrarComoDeshacerCostura ? 'undo' : 'checkroom' }}</span>
                                                {{ $mostrarComoDeshacerCostura ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                                            </button>
                                        @endif

                                        {{-- Botón "Pasar a C.C" o "DESHACER" --}}
                                        <button class="btn-pasar-cc" 
                                                id="btn-cc-{{ $prenda['prenda_id'] }}"
                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                data-tipo-recibo="{{ $tipoRecibo }}"
                                                data-recibo="{{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }}"
                                                data-area="{{ $areaActual ?? 'COSTURA' }}"
                                                data-proceso-id="{{ $procesoId }}"
                                                onclick="pasarAControlCalidad(this)">
                                            <span class="material-symbols-rounded">{{ $esCC ? 'undo' : 'check_circle' }}</span>
                                            {{ $esCC ? 'DESHACER' : 'PASAR A C.C' }}
                                        </button>
                                    @endif
                                    
                                    @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('vista-costura'))
                                        @php
                                            $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                        @endphp
                                        @foreach($tiposUnicos as $tipoReciboUnico)
                                            @php
                                                $reciboTipo = collect($prenda['recibos'] ?? [])->first(function($r) use ($tipoReciboUnico) {
                                                    return strtoupper((string)($r['tipo_recibo'] ?? '')) === strtoupper((string)$tipoReciboUnico);
                                                });
                                                $pedidoParcialId = $reciboTipo['pedido_parcial_id'] ?? null;
                                            @endphp
                                            <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', 'VER RECIBO', {{ $pedidoParcialId ? (int)$pedidoParcialId : 'null' }})">
                                                <span class="material-symbols-rounded">visibility</span>
                                                VER RECIBO
                                            </button>
                                        @endforeach
                                    @else
                                        <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', '{{ $prenda['recibos'][0]['tipo_recibo'] ?? '' }}', {{ !empty($prenda['recibos'][0]['pedido_parcial_id']) ? (int)$prenda['recibos'][0]['pedido_parcial_id'] : 'null' }})">
                                            <span class="material-symbols-rounded">visibility</span>
                                            VER RECIBOS
                                        </button>
                                    @endif
                                    
                                    @if(auth()->user()->hasRole('cortador'))
                                    @php
                                        $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                        $areaRecibo = strtolower(trim((string)($reciboPrincipal['area'] ?? '')));
                                        $esCorteRecibo = $areaRecibo === 'corte';
                                        $esCosturaRecibo = $areaRecibo === 'costura';
                                        $reciboId = $reciboPrincipal['id'] ?? null;
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
                                        <button class="btn-agregar-novedad" onclick="abrirModalNovedad('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', {{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }})">
                                            <span class="material-symbols-rounded">comment</span>
                                            AGREGAR NOVEDAD
                                        </button>
                                    @endif
                                @else
                                    <button class="btn-agregar-novedad" onclick="abrirModalNovedad('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', {{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }})">
                                        <span class="material-symbols-rounded">comment</span>
                                        AGREGAR NOVEDAD
                                    </button>
                                @endif
                                    <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] }})">
                                        <span class="material-symbols-rounded">more_horiz</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Contenido Derecho -->
                            <div class="orden-right">
                                <div class="orden-right-center">
                                    <a href="#" class="action-arrow" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', '{{ $prenda['recibos'][0]['tipo_recibo'] ?? '' }}'); return false;">
                                        <span class="material-symbols-rounded">arrow_forward</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="empty-state">
                    <span class="material-symbols-rounded">inbox</span>
                    <p>No hay prendas con recibos de costura asignadas</p>
                </div>
            @endif
        </div>
    </div>
 </div>

<!-- Modales -->
<!-- Modal de Mensaje Genérico -->
<div id="modalMensaje" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div id="modalMensajeContenido" style="background: white; padding: 2rem; border-radius: 12px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <div id="modalMensajeIcono" style="font-size: 3rem; margin-bottom: 1rem;"></div>
        <h3 id="modalMensajeTitulo" style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600;"></h3>
        <p id="modalMensajeTexto" style="margin: 0 0 1.5rem 0; color: #666;"></p>
    </div>
</div>

<!-- Modal de Novedades -->
<div id="modalNovedad" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; max-width: 760px; width: 92%; max-height: 85vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25);">
        <div style="background: #111827; color: white; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
            <div id="modalNovedadHeaderTitulo" style="font-weight: 800; letter-spacing: 0.5px; font-size: 0.95rem; text-transform: uppercase;">NOVEDADES</div>
            <button type="button" onclick="cerrarModalNovedad()" style="background: transparent; border: none; color: white; cursor: pointer; font-size: 1.25rem; line-height: 1; padding: 0.25rem;">×</button>
        </div>

        <div style="padding: 1.25rem; overflow-y: auto; max-height: calc(85vh - 56px);">
            <input type="hidden" id="novedadNumeroPedido">
            <input type="hidden" id="novedadPrendaId">

            <div style="margin-bottom: 1rem;">
                <div style="color: #111827; font-weight: 700; font-size: 0.95rem; margin-bottom: 0.5rem;">Historial:</div>
                <div id="novedadesHistorial" style="max-height: 220px; overflow-y: auto; padding-right: 0.25rem;"></div>
            </div>

            <div style="height: 1px; background: #e5e7eb; margin: 1rem 0;"></div>

            <div style="color: #111827; font-weight: 800; font-size: 1rem; margin-bottom: 0.75rem;">Agregar Nueva Novedad:</div>

            <div style="margin-bottom: 1rem;">
                <textarea id="novedadDescripcionText" rows="5" style="width: 100%; padding: 0.9rem; border: 1px solid #d1d5db; border-radius: 10px; resize: vertical; font-size: 0.95rem;" placeholder="Escribe tu novedad aquí..."></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <button type="button" id="btnGuardarNovedad" onclick="guardarNovedad()" style="padding: 0.85rem 1rem; background: #22c55e; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 800;">Guardar Novedad</button>
                <button type="button" onclick="cerrarModalNovedad()" style="padding: 0.85rem 1rem; background: #94a3b8; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 800;">Cancelar</button>
            </div>

            <div style="display: none;">
                <div id="novedadPrendaNombre"></div>
                <div id="novedadReciboNumero"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Costura -->
<div id="modalCostura" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 0.5rem;">
    <div id="modalCosturaContent" style="background: white; padding: 0; border-radius: 16px; max-width: 900px; width: 100%; max-height: 98vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); display: flex; flex-direction: column; transition: all 0.3s ease;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 1rem 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
            <div style="flex: 1; min-width: 0;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; line-height: 1.2;">Asignar a Costura</h3>
                <p style="margin: 0.25rem 0 0 0; opacity: 0.9; font-size: 0.75rem;" id="modalSubtitulo">Seleccione el tipo de asignación</p>
            </div>
            <button type="button" onclick="cerrarModalCostura()" style="background: rgba(255,255,255,0.2); border: none; border-radius: 8px; padding: 0.5rem; cursor: pointer; color: white; transition: background 0.2s; flex-shrink: 0;">
                <span class="material-symbols-rounded" style="font-size: 1.25rem;">close</span>
            </button>
        </div>
        
        <!-- Contenido principal -->
        <div id="modalMainContent" style="flex: 1; overflow-y: auto; padding: 1rem;">
            <!-- Opciones de Asignación -->
            <div id="opcionesAsignacion" style="margin-bottom: 1.5rem;">
                <h4 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 600; color: #1e293b;">Tipo de Asignación</h4>
                <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                    <button type="button" id="btnModuloCompleto" onclick="seleccionarOpcionAsignacion('completo')" style="padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s; text-align: left;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 40px; height: 40px; background: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">inventory_2</span>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <h5 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1e293b; line-height: 1.3;">Módulo Completo</h5>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">Asignar todas las prendas a un solo módulo</p>
                            </div>
                        </div>
                    </button>
                    
                    <button type="button" id="btnDistribuirModulos" onclick="seleccionarOpcionAsignacion('distribuir')" style="padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s; text-align: left;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 40px; height: 40px; background: #10b981; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">share</span>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <h5 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1e293b; line-height: 1.3;">Distribuir por Módulos</h5>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">Repartir prendas entre múltiples módulos</p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
            
            <!-- Contenido dinámico según opción -->
            <div id="contenidoAsignacion">
                <!-- Se cargará dinámicamente -->
            </div>
        </div>
        
        <!-- Footer -->
        <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 0.75rem; flex-shrink: 0;">
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; flex-wrap: wrap;">
                <button type="button" id="btnVolver" onclick="volverAOpciones()" style="padding: 0.625rem 1rem; border: 1px solid #d1d5db; background: white; border-radius: 8px; cursor: pointer; font-weight: 500; color: #374151; transition: all 0.2s; display: none; font-size: 0.875rem;">
                    <span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle; margin-right: 0.5rem;">arrow_back</span>
                    Volver
                </button>
                <button type="button" onclick="cerrarModalCostura()" style="padding: 0.625rem 1rem; border: 1px solid #d1d5db; background: white; border-radius: 8px; cursor: pointer; font-weight: 500; color: #374151; transition: all 0.2s; font-size: 0.875rem;">Cancelar</button>
                <button type="button" id="btnConfirmarAsignacion" onclick="confirmarAsignacion()" style="padding: 0.625rem 1rem; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s; font-size: 0.875rem;" disabled>Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos responsive para mobile -->
<style>
@media (max-width: 768px) {
    #modalCostura {
        padding: 0.5rem;
        align-items: flex-start;
        padding-top: 2rem;
    }
    
    #modalCosturaContent {
        max-width: 100%;
        max-height: 95vh;
        border-radius: 12px;
    }
    
    #modalMainContent {
        padding: 1rem;
    }
    
    #opcionesAsignacion h4 {
        font-size: 0.875rem;
        margin-bottom: 0.75rem;
    }
    
    #opcionesAsignacion button {
        padding: 0.75rem;
    }
    
    #opcionesAsignacion button .material-symbols-rounded {
        font-size: 1.125rem !important;
    }
    
    #opcionesAsignacion button h5 {
        font-size: 0.8rem !important;
    }
    
    #opcionesAsignacion button p {
        font-size: 0.7rem !important;
    }
}

@media (max-width: 480px) {
    #modalCosturaContent {
        max-height: 98vh;
    }
    
    #modalMainContent {
        padding: 0.75rem;
    }
    
    #opcionesAsignacion {
        margin-bottom: 1rem;
    }
    
    #opcionesAsignacion button {
        padding: 0.625rem;
    }
    
    #opcionesAsignacion button div {
        gap: 0.75rem;
    }
    
    #opcionesAsignacion button div > div:first-child {
        width: 36px !important;
        height: 36px !important;
    }
}

/* Distribución por módulos - estilos */
.dist-talla-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
}

.dist-talla-row.is-selected {
    border-color: #2563eb;
    box-shadow: 0 0 0 1px #2563eb inset;
}

.dist-talla-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
    flex: 1;
}

.dist-talla-check {
    width: 16px;
    height: 16px;
    accent-color: #2563eb;
    flex-shrink: 0;
}

.dist-talla-text {
    min-width: 0;
}

.dist-talla-title {
    font-weight: 700;
    color: #111827;
    font-size: 0.875rem;
    line-height: 1.1;
}

.dist-talla-sub {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.15rem;
}

.dist-talla-disp {
    color: #dc2626;
    font-weight: 700;
}

.dist-talla-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.dist-talla-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dist-talla-input {
    width: 64px;
    padding: 0.25rem 0.4rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    text-align: center;
    font-size: 0.875rem;
    background: white;
}

.dist-talla-input:disabled {
    background: #f3f4f6;
    color: #9ca3af;
}

@media (max-width: 480px) {
    .dist-talla-row {
        padding: 0.6rem;
        border-radius: 12px;
    }

    .dist-talla-title {
        font-size: 0.85rem;
    }

    .dist-talla-input {
        width: 58px;
        font-size: 0.85rem;
    }

    /* En mobile, ocultar +/- para que no se apriete la UI (como la referencia) */
    .dist-talla-btn {
        display: none;
    }

    .dist-talla-right {
        gap: 0;
    }
}
</style>

@endsection
