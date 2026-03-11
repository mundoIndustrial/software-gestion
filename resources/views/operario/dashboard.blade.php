@extends('operario.layout')

@section('title', 'Mis Ã“rdenes')
@section('page-title', '')

@php
    // Helper para obtener clase de estado
    function getEstadoClass($estado) {
        $estado = strtolower(trim($estado));
        if (strpos($estado, 'ejecuciÃ³n') !== false || strpos($estado, 'proceso') !== false) {
            return 'en-proceso';
        }
        if (strpos($estado, 'completada') !== false || strpos($estado, 'completado') !== false) {
            return 'completada';
        }
        return 'pendiente';
    }
@endphp

@section('content')
<div class="operario-dashboard {{ auth()->user()->hasRole('vista-costura') ? 'is-vista-costura' : '' }}">
    <!-- Usuario Logueado en Variable Global -->
    <script>
        window.USUARIO_ACTUAL = {
            id: {{ Auth::id() }},
            rol: '{{ Auth::user()->roles->first()->name ?? '' }}',
            nombre: '{{ Auth::user()->name ?? '' }}'
        };

        // Tiempo real: escuchar cuando se asignen recibos/procesos al operario
        (function () {
            let intentos = 0;
            const maxIntentos = 100;

            async function actualizarListaSinRecargar() {
                try {
                    const url = window.location.href;
                    const resp = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!resp.ok) {
                        throw new Error('HTTP ' + resp.status);
                    }

                    const html = await resp.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    const nuevoOrdenesList = doc.getElementById('ordenesList');
                    const actualOrdenesList = document.getElementById('ordenesList');
                    if (!nuevoOrdenesList || !actualOrdenesList) {
                        throw new Error('No se encontrÃ³ #ordenesList en HTML');
                    }

                    actualOrdenesList.innerHTML = nuevoOrdenesList.innerHTML;

                    const nuevoCount = doc.querySelector('.ordenes-count');
                    const actualCount = document.querySelector('.ordenes-count');
                    if (nuevoCount && actualCount) {
                        actualCount.textContent = nuevoCount.textContent;
                    }

                    if (typeof window.__initDashboardSearch === 'function') {
                        window.__initDashboardSearch();
                    }

                    if (typeof window.filtrarPrendasPorRecibo === 'function') {
                        const badgeActivo = document.querySelector('.badge-filtro-active');
                        const filtroActivo = badgeActivo ? badgeActivo.dataset.filtro : null;
                        if (filtroActivo) {
                            window.filtrarPrendasPorRecibo(filtroActivo);
                        }
                    }

                    console.log('[Operario Dashboard] Lista actualizada sin recargar');
                } catch (e) {
                    console.warn('[Operario Dashboard] FallÃ³ actualizar lista sin recargar, recargando pÃ¡gina', e);
                    window.location.reload();
                }
            }

            function initRealtimeListeners() {
                try {
                    intentos += 1;

                    if (!window.USUARIO_ACTUAL?.id) {
                        if (intentos < maxIntentos) {
                            return setTimeout(initRealtimeListeners, 200);
                        }
                        console.warn('[Operario Dashboard] No se pudo iniciar realtime: USUARIO_ACTUAL no disponible');
                        return;
                    }

                    if (!window.EchoInstance) {
                        if (intentos < maxIntentos) {
                            return setTimeout(initRealtimeListeners, 200);
                        }
                        console.warn('[Operario Dashboard] No se pudo iniciar realtime: EchoInstance no disponible');
                        return;
                    }

                    console.log('[Operario Dashboard] Inicializando listeners Echo', {
                        usuario: window.USUARIO_ACTUAL,
                        privateChannel: `private-App.Models.User.${window.USUARIO_ACTUAL.id}`,
                        publicChannel: 'operarios.corte',
                    });

                    window.EchoInstance.private(`App.Models.User.${window.USUARIO_ACTUAL.id}`)
                        .subscribed(() => {
                            console.log('[Operario Dashboard] Suscrito OK a canal privado', `App.Models.User.${window.USUARIO_ACTUAL.id}`);
                        })
                        .error((err) => {
                            console.error('[Operario Dashboard] Error suscribiendo canal privado', err);
                        })
                        .listen('.operario.recibos.actualizados', (e) => {
                            console.log('[Operario Dashboard] Evento operario.recibos.actualizados recibido (privado):', e);
                            actualizarListaSinRecargar();
                        });

                    // Fallback pÃºblico: evento de asignaciÃ³n de corte (compara por nombre)
                    window.EchoInstance.channel('operarios.corte')
                        .subscribed(() => {
                            console.log('[Operario Dashboard] Suscrito OK a canal pÃºblico', 'operarios.corte');
                        })
                        .error((err) => {
                            console.error('[Operario Dashboard] Error suscribiendo canal pÃºblico operarios.corte', err);
                        })
                        .listen('.corte.asignado', (e) => {
                            const encargadoEvento = String(e?.encargado || '').trim().toLowerCase();
                            const nombreActual = String(window.USUARIO_ACTUAL?.nombre || '').trim().toLowerCase();

                            console.log('[Operario Dashboard] Evento corte.asignado recibido:', e);
                            console.log('[Operario Dashboard] Comparando encargado vs usuario:', {
                                encargadoEvento,
                                nombreActual,
                            });

                            if (encargadoEvento && nombreActual && encargadoEvento === nombreActual) {
                                console.log('[Operario Dashboard] Coincide encargado con usuario, actualizando lista sin recargar');
                                actualizarListaSinRecargar();
                            }
                        });

                    // Vista-costura: escuchar cuando insumos aprueba/envÃ­a el recibo a producciÃ³n (Ã¡rea Corte)
                    // Evento broadcast: App\Events\ReciboAprobado -> channel('recibos-costura') -> 'recibo.aprobado'
                    if (String(window.USUARIO_ACTUAL?.rol || '').toLowerCase() === 'vista-costura') {
                        window.EchoInstance.channel('recibos-costura')
                            .subscribed(() => {
                                console.log('[Operario Dashboard] Suscrito OK a canal pÃºblico', 'recibos-costura');
                            })
                            .error((err) => {
                                console.error('[Operario Dashboard] Error suscribiendo canal pÃºblico recibos-costura', err);
                            })
                            .listen('.recibo.aprobado', (e) => {
                                console.log('[Operario Dashboard] Evento recibo.aprobado recibido:', e);
                                // Vista-costura ya no muestra recibos en Ã¡rea Corte; no refrescar aquÃ­.
                            })
                            .listen('.recibo.completado', (e) => {
                                console.log('[Operario Dashboard] Evento recibo.completado recibido:', e);
                                try {
                                    if (window.NotificacionesPush && typeof window.NotificacionesPush.add === 'function') {
                                        const area = String(e?.area || '').trim();
                                        const consecutivo = e?.consecutivo ? `#${e.consecutivo}` : '';
                                        const operario = String(e?.nombre_operario || '').trim();
                                        let titulo = 'Recibo completado';
                                        let detalle = `Recibo ${consecutivo}`.trim();

                                        if (area.toLowerCase() === 'corte') {
                                            titulo = 'Recibo enviado a Costura';
                                            detalle = `Recibo ${consecutivo} completado en CORTE${operario ? ' por ' + operario : ''}`.trim();
                                        } else if (area.toLowerCase() === 'costura') {
                                            titulo = 'Recibo completado en Costura';
                                            detalle = `Recibo ${consecutivo} completado en COSTURA${operario ? ' por ' + operario : ''}`.trim();
                                        } else if (area.toLowerCase() === 'control de calidad' || area.toLowerCase() === 'control calidad') {
                                            titulo = 'Recibo completado en Control de Calidad';
                                            detalle = `Recibo ${consecutivo} completado en CONTROL DE CALIDAD${operario ? ' por ' + operario : ''}`.trim();
                                        }

                                        window.NotificacionesPush.add({
                                            id: `recibo-completado-${String(e?.recibo_id || '')}-${String(e?.area || '')}-${String(e?.timestamp || '')}`,
                                            titulo,
                                            detalle,
                                            fecha: '',
                                        });
                                    }
                                } catch (err) {
                                    console.warn('[Operario Dashboard] Error creando notificaciÃ³n push', err);
                                }
                                actualizarListaSinRecargar();
                            });
                    }
                } catch (e) {
                    console.error('[Operario Dashboard] Error initRealtimeListeners', e);
                }
            }

            initRealtimeListeners();
        })();
    </script>
    <!-- BÃºsqueda -->
    <div class="search-section">
        <span class="material-symbols-rounded">search</span>
        <input type="text" id="searchInput" class="search-box" placeholder="Buscar por # Recibo, Prenda o Cliente...">
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

        <!-- Filtros por tipo de recibo para costura-reflectivo y vista-costura -->
        @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('vista-costura'))
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
                <button class="badge-filtro badge-filtro-active" data-filtro="reflectivo" onclick="filtrarPrendasPorRecibo('reflectivo')">
                    <span class="material-symbols-rounded">auto_awesome</span>
                    Reflectivo
                </button>
            @endif
        </div>
        @endif

        <div class="ordenes-list" id="ordenesList">
            @if(count($prendasConRecibos ?? []) > 0)
                @foreach($prendasConRecibos as $prenda)
                    @php
                        $estadoClass = 'pendiente'; // Por defecto pendiente
                        if ($prenda['recibos']) {
                            $estadoClass = count($prenda['recibos']) > 0 ? 'en-proceso' : 'pendiente';
                        }
                        
                        // Determinar tipo de recibo para filtro
                        // Si ALGUNO de los recibos es REFLECTIVO, marcar la tarjeta como REFLECTIVO
                        $tiposRecibos = array_map(function($r) { return strtoupper($r['tipo_recibo']); }, $prenda['recibos']);
                        $tieneReflectivo = in_array('REFLECTIVO', $tiposRecibos);
                        $esReflectivo = $tieneReflectivo ? 'reflectivo' : 'costura';
                        
                        // Por defecto:
                        // - costura-reflectivo: mostrar solo tarjetas REFLECTIVO
                        // - vista-costura: mantener comportamiento actual (mostrar COSTURA por defecto)
                        $displayInicial = '';
                        if (auth()->user()->hasRole('costura-reflectivo')) {
                            $displayInicial = $esReflectivo === 'reflectivo' ? '' : 'none';
                        } else {
                            $displayInicial = $esReflectivo === 'reflectivo' ? 'none' : '';
                        }

                        $reciboPrincipalFiltro = $prenda['recibos'][0] ?? null;
                        $areaReciboFiltro = strtolower(trim((string) ($reciboPrincipalFiltro['area'] ?? '')));
                    @endphp

                    @if(auth()->user()->hasRole('vista-costura') && $areaReciboFiltro === 'corte')
                        @continue
                    @endif

                    <div class="orden-card-simple" 
                         data-numero="{{ $prenda['numero_pedido'] }}" 
                         data-prenda="{{ strtolower($prenda['nombre_prenda']) }}"
                         data-cliente="{{ strtolower($prenda['cliente']) }}"
                         data-tipo-recibo="{{ $esReflectivo }}"
                         style="display: {{ $displayInicial }}">
                        
                        <!-- Borde izquierdo coloreado -->
                        <div class="orden-border {{ $estadoClass }}"></div>

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
                            @endphp
                            @if(!auth()->user()->hasRole('vista-costura') && !auth()->user()->hasRole('cortador') && !auth()->user()->hasRole('costura-reflectivo'))
                                <div class="orden-encargado-corner" onclick="event.stopPropagation();">
                                    <strong>Encargado:</strong>
                                    <span>{{ $encargadoVista ? strtoupper($encargadoVista) : 'SIN ENCARGADO' }}</span>
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
                                    </div>
                                    <!-- BotÃ³n de mÃ¡s opciones para mobile -->
                                    <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] }})">
                                        <span class="material-symbols-rounded">more_horiz</span>
                                    </button>
                                </div>

                                <div class="orden-cliente">
                                    <p class="cliente-label">CLIENTE</p>
                                    <p class="cliente-name">{{ $prenda['cliente'] }}</p>
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

                                        {{-- BotÃ³n "Pasar a Costura" o "DESHACER COSTURA" solo para recibos tipo COSTURA --}}
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

                                        {{-- BotÃ³n "Pasar a C.C" o "DESHACER" --}}
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
                                    
                                    <button class="btn-agregar-novedad" onclick="abrirModalNovedad('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', {{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }})">
                                        <span class="material-symbols-rounded">comment</span>
                                        AGREGAR NOVEDAD
                                    </button>
                                    @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('vista-costura'))
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
                                        @if($tieneReciboReflectivo && $esCosturaAreaRef && auth()->user()->hasRole('vista-costura'))
                                            <button class="btn-pasar-costura {{ $tieneEncargadoCosturaRef ? 'btn-deshacer-costura' : '' }}" 
                                                    id="btn-costura-reflectivo-{{ $prenda['prenda_id'] }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
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
                                    @endif
                                    @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('vista-costura'))
                                        {{-- Para costura-reflectivo/vista-costura, crear un botÃ³n por cada TIPO de recibo (sin duplicados) --}}
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
                                            <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', '{{ $tipoReciboUnico }}', {{ $pedidoParcialId ? (int)$pedidoParcialId : 'null' }})">
                                                <span class="material-symbols-rounded">receipt</span>
                                                {{ $tipoReciboUnico }}
                                            </button>
                                        @endforeach
                                    @else
                                        {{-- Para otros operarios, un solo botÃ³n con tipo de recibo --}}
                                        <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', '{{ $prenda['recibos'][0]['tipo_recibo'] ?? '' }}', {{ !empty($prenda['recibos'][0]['pedido_parcial_id']) ? (int)$prenda['recibos'][0]['pedido_parcial_id'] : 'null' }})">
                                            <span class="material-symbols-rounded">receipt</span>
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

                                        {{-- BotÃ³n "Pasar a Costura" o "DESHACER COSTURA" solo para recibos tipo COSTURA --}}
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

                                        {{-- BotÃ³n "Pasar a C.C" o "DESHACER" --}}
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
                                    
                                    <button class="btn-agregar-novedad" onclick="abrirModalNovedad('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', {{ isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'] }})">
                                        <span class="material-symbols-rounded">comment</span>
                                        AGREGAR NOVEDAD
                                    </button>
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
                                            <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', '{{ $tipoReciboUnico }}', {{ $pedidoParcialId ? (int)$pedidoParcialId : 'null' }})">
                                                <span class="material-symbols-rounded">receipt</span>
                                                {{ $tipoReciboUnico }}
                                            </button>
                                        @endforeach
                                    @else
                                        <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', '{{ $prenda['recibos'][0]['tipo_recibo'] ?? '' }}', {{ !empty($prenda['recibos'][0]['pedido_parcial_id']) ? (int)$prenda['recibos'][0]['pedido_parcial_id'] : 'null' }})">
                                            <span class="material-symbols-rounded">receipt</span>
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
                                {{-- Pie de pÃ¡gina con nÃºmero de pedido --}}
                                <div class="orden-pedido-footer">
                                    <small>PEDIDO #{{ $prenda['numero_pedido'] }}</small>
                                </div>

                                <div class="orden-right-center">
                                    @if(isset($prenda['recibos'][0]['consecutivo_actual']))
                                        {{-- Mostrar nÃºmero del recibo --}}
                                        <div class="orden-fecha">
                                            <span class="orden-fecha-label">RECIBO</span>
                                            <span>#{{ $prenda['recibos'][0]['consecutivo_actual'] }}</span>
                                        </div>
                                    @else
                                        {{-- Para otros roles, mostrar nÃºmero del pedido --}}
                                        <div class="orden-fecha">
                                            <span class="orden-fecha-label">PEDIDO</span>
                                            <span>#{{ $prenda['numero_pedido'] }}</span>
                                        </div>
                                    @endif
                                    <div class="orden-fecha">
                                        <span class="orden-fecha-label">REGISTRO</span>
                                        @php
                                            $esParcial = !empty($prenda['recibos'][0]['es_parcial']);
                                            $creadoEnRecibo = $prenda['recibos'][0]['creado_en'] ?? null;
                                            $fechaRegistro = $esParcial && $creadoEnRecibo
                                                ? \Carbon\Carbon::parse($creadoEnRecibo)
                                                : ($prenda['fecha_creacion'] ?? null);
                                        @endphp
                                        <span>{{ $fechaRegistro ? $fechaRegistro->format('d/m/Y') : '-' }}</span>
                                    </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.__initDashboardSearch = function() {
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearFilterBtn');

            const ordenesList = document.getElementById('ordenesList');
            const ordenCards = ordenesList ? ordenesList.querySelectorAll('.orden-card-simple') : [];

            console.log('=== TARJETAS CARGADAS EN DASHBOARD ===');
            console.log('Total de tarjetas:', ordenCards.length);
            ordenCards.forEach((card, index) => {
                console.log(`Tarjeta ${index + 1}:`, {
                    numero: card.dataset.numero,
                    prenda: card.dataset.prenda,
                    cliente: card.dataset.cliente,
                    'data-tipo-recibo': card.dataset.tipoRecibo
                });
            });
            console.log('=====================================\n');

            if (!searchInput) {
                return;
            }

            if (window.__dashboardClearHandler && clearBtn) {
                clearBtn.removeEventListener('click', window.__dashboardClearHandler);
            }
            if (window.__dashboardSearchHandler) {
                searchInput.removeEventListener('input', window.__dashboardSearchHandler);
            }

            window.__dashboardClearHandler = function() {
                searchInput.value = '';
                if (clearBtn) {
                    clearBtn.style.display = 'none';
                }

                const event = new Event('input', { bubbles: true });
                searchInput.dispatchEvent(event);
            };

            if (clearBtn) {
                clearBtn.addEventListener('click', window.__dashboardClearHandler);
            }

            window.__dashboardSearchHandler = function(e) {
                const busqueda = e.target.value.toLowerCase().trim();

                if (clearBtn) {
                    clearBtn.style.display = busqueda ? 'flex' : 'none';
                }

                const ordenesListActual = document.getElementById('ordenesList');
                const cardsActuales = ordenesListActual ? ordenesListActual.querySelectorAll('.orden-card-simple') : [];

                cardsActuales.forEach(card => {
                    const reciboDom = card.querySelector('.orden-right .orden-fecha span:not(.orden-fecha-label)');
                    const numeroRecibo = reciboDom ? reciboDom.textContent.toLowerCase().trim() : '';
                    const cliente = String(card.dataset.cliente || '').toLowerCase();

                    const prendaDom = card.querySelector('.orden-prendas .prendas-label strong');
                    const nombrePrenda = prendaDom ? prendaDom.textContent.toLowerCase().trim() : '';

                    if (numeroRecibo.includes(busqueda) || cliente.includes(busqueda) || nombrePrenda.includes(busqueda) || busqueda === '') {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            };

            searchInput.addEventListener('input', window.__dashboardSearchHandler);
        };

        if (typeof window.__initDashboardSearch === 'function') {
            window.__initDashboardSearch();
        }
    });

    // FunciÃ³n para filtrar prendas por tipo de recibo
    window.filtrarPrendasPorRecibo = function(filtro) {
        console.log(' [FILTRO] Iniciando filtro:', filtro);
        
        // Actualizar estado de botones
        document.querySelectorAll('.badge-filtro').forEach(btn => {
            btn.classList.remove('badge-filtro-active');
        });
        const btnFiltro = document.querySelector(`[data-filtro="${filtro}"]`);
        if (btnFiltro) {
            btnFiltro.classList.add('badge-filtro-active');
        }

        // Filtrar tarjetas
        const ordenesList = document.getElementById('ordenesList');
        if (!ordenesList) {
            console.error(' ordenesList no encontrado');
            return;
        }

        const ordenCards = ordenesList.querySelectorAll('.orden-card-simple');
        console.log(' Total de tarjetas:', ordenCards.length);
        
        let mostradas = 0;
        let ocultadas = 0;
        
        ordenCards.forEach((card, index) => {
            const tipoRecibo = card.dataset.tipoRecibo;
            const numeroPedido = card.dataset.numero;
            const nombrePrenda = card.dataset.prenda;
            
            console.log(`Tarjeta ${index + 1}: Pedido=${numeroPedido}, Prenda=${nombrePrenda}, data-tipo-recibo="${tipoRecibo}"`);
            
            if (filtro === 'todos') {
                card.style.display = '';
                mostradas++;
            } else {
                if (tipoRecibo === filtro) {
                    console.log(`  âœ“ Mostrando (coincide con filtro "${filtro}")`);
                    card.style.display = '';
                    mostradas++;
                } else {
                    console.log(`  âœ— Ocultando (tipo="${tipoRecibo}" !== filtro="${filtro}")`);
                    card.style.display = 'none';
                    ocultadas++;
                }
            }
        });
        
        console.log(` Filtro completado: ${mostradas} mostradas, ${ocultadas} ocultadas`);
    };

    // FunciÃ³n para abrir detalles de recibos
    function abrirDetallesRecibos(numeroPedido, prendaId, nombrePrenda, tipoRecibo, pedidoParcialId = null) {
        console.log(' [ABRIR DETALLES RECIBOS] ===== INICIANDO =====');
        console.log(' ParÃ¡metros recibidos:', {
            numeroPedido: numeroPedido,
            prendaId: prendaId,
            nombrePrenda: nombrePrenda,
            tipoRecibo: tipoRecibo,
            pedidoParcialId: pedidoParcialId
        });
        
        // Validar que tengamos el nÃºmero de pedido
        if (!numeroPedido || numeroPedido === '' || numeroPedido === null || numeroPedido === undefined) {
            console.error(' ERROR: numeroPedido estÃ¡ vacÃ­o o undefined', numeroPedido);
            alert('Error: No se pudo determinar el nÃºmero de pedido');
            return false;
        }
        
        // Convertir a string si es nÃºmero
        const numeroPedidoStr = String(numeroPedido).trim();
        console.log(' numeroPedido normalizado:', numeroPedidoStr);
        
        // Construir la URL con prenda_id y tipo de recibo si se proporcionan
        let url = '/operario/pedido/' + numeroPedidoStr;
        const params = new URLSearchParams();
        
        if (prendaId) {
            params.append('prenda_id', prendaId);
            console.log(' Prenda ID:', prendaId);
        }
        
        if (tipoRecibo) {
            params.append('tipo_recibo', tipoRecibo);
            console.log(' Tipo de recibo:', tipoRecibo);
        }

        if (pedidoParcialId) {
            params.append('pedido_parcial_id', pedidoParcialId);
            console.log(' Pedido Parcial ID:', pedidoParcialId);
        }
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        console.log(' URL a navegar:', url);
        
        // Navegar
        try {
            console.log(' Iniciando navegaciÃ³n...');
            window.location.href = url;
            console.log(' NavegaciÃ³n iniciada exitosamente');
            return false;
        } catch (error) {
            console.error(' Error al navegar:', error);
            return false;
        }
    }

    // FunciÃ³n para abrir modal de novedades
    function abrirModalNovedad(numeroPedido, prendaId, nombrePrenda, numeroRecibo) {
        console.log(' Abriendo modal novedad', {numeroPedido, prendaId, nombrePrenda, numeroRecibo});
        
        const modal = document.getElementById('modalNovedad');
        if (!modal) {
            console.error('Modal no encontrado');
            return;
        }
        
        // Configurar datos del modal
        document.getElementById('novedadNumeroPedido').value = numeroPedido;
        document.getElementById('novedadPrendaId').value = prendaId;
        document.getElementById('novedadPrendaNombre').textContent = nombrePrenda;
        document.getElementById('novedadReciboNumero').textContent = numeroRecibo;
        
        // Cargar novedades existentes
        cargarNovedadesDelUsuario(numeroPedido, prendaId);
        
        // Mostrar modal
        modal.style.display = 'flex';
    }

    // FunciÃ³n para cargar novedades del usuario
    function cargarNovedadesDelUsuario(numeroPedido, prendaId) {
        console.log(' Cargando novedades', {numeroPedido, prendaId});
        
        fetch(`/operario/api/novedades/${numeroPedido}/${prendaId}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log(' Novedades cargadas:', data);
            mostrarNovedades(data.novedades || []);
        })
        .catch(error => {
            console.error(' Error cargando novedades:', error);
            const historial = document.getElementById('novedadesHistorial');
            if (historial) {
                historial.innerHTML = '<p style="color: #999;">Error cargando novedades</p>';
            }
        });
    }

    // FunciÃ³n para mostrar novedades
    function mostrarNovedades(novedades) {
        const historial = document.getElementById('novedadesHistorial');
        if (!historial) {
            console.error('Historial no encontrado');
            return;
        }
        
        if (novedades.length === 0) {
            historial.innerHTML = '<p style="color: #999;">No hay novedades registradas</p>';
            return;
        }
        
        let html = '';
        novedades.forEach(novedad => {
            const fecha = new Date(novedad.created_at).toLocaleString();
            const esMia = novedad.es_mia;
            html += `
                <div style="padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.5rem; background: ${esMia ? '#f0f9ff' : '#f9fafb'}">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                        <div>
                            <strong style="color: #1f2937; font-size: 0.875rem;">${novedad.usuario_nombre}</strong>
                            <span style="color: #6b7280; font-size: 0.75rem; margin-left: 0.5rem;">${fecha}</span>
                        </div>
                        ${esMia ? `
                            <div style="display: flex; gap: 0.25rem;">
                                <button onclick="editarNovedad(${novedad.id}, '${novedad.descripcion.replace(/'/g, "\\'")}', '${novedad.tipo}')" style="background: none; border: none; color: #3b82f6; cursor: pointer; padding: 0.25rem;">
                                    <span class="material-symbols-rounded" style="font-size: 1rem;">edit</span>
                                </button>
                                <button onclick="eliminarNovedad(${novedad.id})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0.25rem;">
                                    <span class="material-symbols-rounded" style="font-size: 1rem;">delete</span>
                                </button>
                            </div>
                        ` : ''}
                    </div>
                    <p style="color: #374151; font-size: 0.875rem; line-height: 1.4; margin: 0;">${novedad.descripcion}</p>
                </div>
            `;
        });
        
        historial.innerHTML = html;
    }

    // FunciÃ³n para cerrar modal de novedades
    function cerrarModalNovedad() {
        const modal = document.getElementById('modalNovedad');
        if (modal) {
            modal.style.display = 'none';
            const textarea = document.getElementById('novedadDescripcionText');
            if (textarea) textarea.value = '';
        }
    }

    // FunciÃ³n para guardar novedad
    function guardarNovedad() {
        const textareaDescripcion = document.getElementById('novedadDescripcionText');
        
        if (!textareaDescripcion) {
            mostrarError('Error', 'Elementos del formulario no encontrados');
            return;
        }
        
        const descripcion = textareaDescripcion.value.trim();
        if (!descripcion) {
            mostrarError('Error', 'Debes describir la novedad');
            return;
        }
        
        const numeroPedido = document.getElementById('novedadNumeroPedido').value;
        const prendaId = document.getElementById('novedadPrendaId').value;
        
        const btnGuardar = document.getElementById('btnGuardarNovedad');
        const textoOriginal = btnGuardar.innerHTML;
        
        // Deshabilitar botÃ³n y mostrar loading
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Guardando...';
        
        fetch('/operario/api/novedades', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                numero_pedido: numeroPedido,
                prenda_id: prendaId,
                descripcion: descripcion,
                tipo: 'general'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                textareaDescripcion.value = '';
                cargarNovedadesDelUsuario(numeroPedido, prendaId);
                mostrarExito('Ã‰xito', 'Novedad registrada correctamente');
            } else {
                mostrarError('Error', data.message || 'Error registrando novedad');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error', 'Error de conexiÃ³n');
        })
        .finally(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = textoOriginal;
        });
    }

    // Funciones para manejar costura
    function manejarPasarACostura(btn) {
        const pedidoId = btn.dataset.pedidoId;
        const prendaId = btn.dataset.prendaId;
        const nombre = btn.dataset.nombre;
        const tipoRecibo = btn.dataset.tipoRecibo;
        const recibo = btn.dataset.recibo;
        const area = btn.dataset.area;
        const procesoId = btn.dataset.procesoId;
        const encargadoCostura = btn.dataset.encargadoCostura;
        const btnId = btn.id;

        console.log(' Manejar pasar a costura:', {
            pedidoId, prendaId, nombre, tipoRecibo, recibo, area, procesoId, encargadoCostura, btnId
        });

        const esDeshacer = btn.classList.contains('btn-deshacer-costura');
        
        if (esDeshacer) {
            deshacerCostura(pedidoId, prendaId, tipoRecibo, btnId);
        } else {
            abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, btnId);
        }
    }

    function abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, btnId) {
        const modal = document.getElementById('modalCostura');
        if (!modal) return;

        document.getElementById('costuraPrendaNombre').textContent = nombre;
        document.getElementById('costuraReciboNumero').textContent = recibo;
        document.getElementById('costuraTipoRecibo').textContent = tipoRecibo;
        document.getElementById('costuraEncargado').value = '';
        document.getElementById('costuraObservaciones').value = '';
        
        window.costuraPendiente = { pedidoId, prendaId, tipoRecibo, btnId };
        modal.style.display = 'flex';
    }

    function cerrarModalCostura() {
        const modal = document.getElementById('modalCostura');
        if (modal) modal.style.display = 'none';
        window.costuraPendiente = null;
    }

    function confirmarPasarACostura() {
        const encargado = document.getElementById('costuraEncargado').value.trim();
        if (!encargado) {
            mostrarError('Error', 'Debes ingresar el nombre del encargado');
            return;
        }

        if (!window.costuraPendiente) return;

        const { pedidoId, prendaId, tipoRecibo, btnId } = window.costuraPendiente;
        const observaciones = document.getElementById('costuraObservaciones').value.trim();

        const btn = document.getElementById(btnId);
        const originalHTML = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Procesando...';

        fetch('/recibos-novedades/pasar-a-costura', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                pedido_id: pedidoId,
                prenda_id: prendaId,
                tipo_recibo: tipoRecibo,
                encargado_costura: encargado,
                observaciones: observaciones
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                btn.dataset.encargadoCostura = encargado;
                btn.dataset.procesoId = data.data.proceso_id || '';
                btn.classList.add('btn-deshacer-costura');
                btn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER COSTURA';
                cerrarModalCostura();
                mostrarExito('Ã‰xito', 'Prenda asignada a costura correctamente');
            } else {
                btn.innerHTML = originalHTML;
                mostrarError('Error', data.message || 'Error asignando a costura');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error de conexiÃ³n');
        })
        .finally(() => {
            btn.disabled = false;
        });
    }

    function deshacerCostura(pedidoId, prendaId, tipoRecibo, btnId) {
        const btn = document.getElementById(btnId);
        if (!btn || btn.disabled) return;

        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Deshaciendo...';

        fetch('/recibos-novedades/deshacer-costura', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                pedido_id: pedidoId,
                prenda_id: prendaId,
                tipo_recibo: tipoRecibo
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                btn.classList.remove('btn-deshacer-costura');
                btn.dataset.encargadoCostura = '';
                btn.dataset.procesoId = '';
                btn.innerHTML = '<span class="material-symbols-rounded">checkroom</span> PASAR A COSTURA';
                mostrarExito('Ã‰xito', 'AsignaciÃ³n a costura deshecha correctamente');
            } else {
                btn.innerHTML = originalHTML;
                mostrarError('Error', data.message || 'Error deshaciendo costura');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error de conexiÃ³n');
        })
        .finally(() => {
            btn.disabled = false;
        });
    }

    // FunciÃ³n para pasar a control de calidad
    function pasarAControlCalidad(btn) {
        const pedidoId = btn.dataset.pedidoId;
        const prendaId = btn.dataset.prendaId;
        const nombre = btn.dataset.nombre;
        const tipoRecibo = btn.dataset.tipoRecibo;
        const recibo = btn.dataset.recibo;
        const area = btn.dataset.area;
        const procesoId = btn.dataset.procesoId;
        const btnId = btn.id;

        const esDeshacer = btn.textContent.includes('DESHACER');
        
        if (esDeshacer) {
            // DESHACER C.C
            const originalCCHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Deshaciendo...';
            btn.style.opacity = '0.6';
            btn.style.pointerEvents = 'none';

            fetch('/recibos-novedades/' + pedidoId + '/' + prendaId + '/deshacer-control-calidad', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    tipo_recibo: tipoRecibo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const nuevoArea = data.data.area_nueva;
                    btn.dataset.area = nuevoArea;
                    btn.dataset.procesoId = '';
                    btn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> PASAR A C.C';
                    console.log('âœ“ Control Calidad deshecho. Ãrea restaurada a: ' + nuevoArea);
                } else {
                    btn.innerHTML = originalCCHTML;
                    console.error('âœ— Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = originalCCHTML;
            })
            .finally(() => {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.pointerEvents = '';
            });
        } else {
            // PASAR A C.C
            fetch('/recibos-novedades/' + pedidoId + '/' + recibo + '/cambiar-area-control-calidad', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    prenda_id: prendaId,
                    tipo_recibo: tipoRecibo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const dashboard = document.querySelector('.operario-dashboard');
                    if (dashboard && dashboard.classList.contains('is-vista-costura')) {
                        const card = btn.closest('.orden-card-simple');
                        if (card) {
                            card.remove();
                        }
                        mostrarExito('Ã‰xito', 'Recibo enviado a Control de Calidad');
                        return;
                    }

                    const btnEl = document.getElementById(btnId);
                    if (!btnEl) {
                        mostrarExito('Ã‰xito', 'Recibo enviado a Control de Calidad');
                        return;
                    }
                    btnEl.dataset.area = 'Control Calidad';
                    btnEl.dataset.procesoId = data.data.proceso_id;
                    btnEl.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER';
                    console.log('âœ“ Prenda enviada a Control Calidad');
                } else {
                    console.error('âœ— Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    }

    // Funciones de utilidad para modales
    function mostrarExito(titulo, texto = '') {
        mostrarMensaje(titulo, texto, 'exito', 'âœ“');
    }

    function mostrarError(titulo, texto = '') {
        mostrarMensaje(titulo, texto, 'error', 'âœ—');
    }

    function mostrarMensaje(titulo, texto, tipo = 'exito', icono = 'âœ“') {
        const modal = document.getElementById('modalMensaje');
        const contenido = document.getElementById('modalMensajeContenido');
        const iconoEl = document.getElementById('modalMensajeIcono');
        const tituloEl = document.getElementById('modalMensajeTitulo');
        const textoEl = document.getElementById('modalMensajeTexto');

        if (!modal || !contenido) {
            console.error('Modal de mensaje no encontrado');
            return;
        }

        // Configurar contenido
        if (iconoEl) iconoEl.textContent = icono;
        if (tituloEl) tituloEl.textContent = titulo;
        if (textoEl) textoEl.textContent = texto;

        // Configurar estilos segÃºn tipo
        const colores = {
            exito: { bg: '#10b981', border: '#059669' },
            error: { bg: '#ef4444', border: '#dc2626' },
            info: { bg: '#3b82f6', border: '#2563eb' }
        };

        const color = colores[tipo] || colores.info;
        contenido.style.borderColor = color.border;

        // Crear botÃ³n de cerrar
        const boton = document.createElement('button');
        boton.textContent = 'CERRAR';
        boton.style.cssText = `
            background: ${color.bg};
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            transition: background 0.2s;
        `;
        boton.onmouseover = function() { this.style.background = color.border; };
        boton.onmouseout = function() { this.style.background = color.bg; };
        boton.onclick = cerrarModalMensaje;
        
        // Eliminar botÃ³n anterior si existe
        const botonAnterior = contenido.querySelector('button');
        if (botonAnterior) botonAnterior.remove();
        
        contenido.appendChild(boton);
        
        // Mostrar modal
        modal.style.display = 'flex';
    }

    function cerrarModalMensaje() {
        const modal = document.getElementById('modalMensaje');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Cerrar modales al hacer click fuera
    window.addEventListener('click', function(event) {
        const modalNovedad = document.getElementById('modalNovedad');
        const modalCostura = document.getElementById('modalCostura');
        const modalMensaje = document.getElementById('modalMensaje');
        
        if (modalNovedad && event.target === modalNovedad) {
            cerrarModalNovedad();
        }
        if (modalCostura && event.target === modalCostura) {
            cerrarModalCostura();
        }
        if (modalMensaje && event.target === modalMensaje) {
            cerrarModalMensaje();
        }
    });

    // FunciÃ³n para toggle de acciones mobile
    window.toggleMobileActions = function(prendaId) {
        const drawer = document.getElementById(`mobile-drawer-${prendaId}`);
        const primaryAction = document.getElementById(`mobile-primary-${prendaId}`);
        const toggleBtns = document.querySelectorAll(`.mobile-actions-toggle[onclick*="${prendaId}"]`);
        
        if (!drawer || !primaryAction) return;
        
        const isActive = drawer.classList.contains('active');
        
        // Cerrar todos los demÃ¡s drawers y mostrar sus acciones primarias
        document.querySelectorAll('.mobile-actions-drawer.active').forEach(d => {
            d.classList.remove('active');
        });
        document.querySelectorAll('.mobile-actions-toggle.active').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelectorAll('.mobile-primary-action').forEach(action => {
            action.style.display = 'flex';
        });
        
        // Abrir/cerrar el drawer actual y ocultar/mostrar acciÃ³n primaria
        if (!isActive) {
            drawer.classList.add('active');
            toggleBtns.forEach(btn => btn.classList.add('active'));
            primaryAction.style.display = 'none'; // Ocultar acciÃ³n primaria cuando drawer estÃ¡ abierto
        } else {
            drawer.classList.remove('active');
            toggleBtns.forEach(btn => btn.classList.remove('active'));
            primaryAction.style.display = 'flex'; // Mostrar acciÃ³n primaria cuando drawer estÃ¡ cerrado
        }
    };

    // Función para cortadores: Marcar como completado (pasa a Costura)
    window.completarCorte = function(btn) {
        const reciboId = btn.dataset.reciboId;
        const pedidoId = btn.dataset.pedidoId;
        const prendaId = btn.dataset.prendaId;
        const nombre = btn.dataset.nombre;
        const card = btn.closest('.orden-card-simple');
        
        if (!reciboId) {
            return;
        }
        
        // Deshabilitar botón y mostrar loading
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-rounded spin">refresh</span> PROCESANDO...';
        
        // Enviar solicitud AJAX a la ruta correcta
        fetch(`/operario/api/recibos/${reciboId}/completar`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la interfaz dinámicamente
                actualizarInterfazCorte(card, 'completado', btn);
                
                // Actualizar también el drawer mobile si existe
                const drawerBtn = document.querySelector(`#mobile-drawer-${prendaId} .btn-completar-corte[data-recibo-id="${reciboId}"]`);
                if (drawerBtn) {
                    actualizarInterfazCorte(drawerBtn.closest('.mobile-actions-drawer'), 'completado', drawerBtn);
                }
                
                // Actualizar acción primaria mobile si existe
                const primaryBtn = document.querySelector(`#mobile-primary-${prendaId} .btn-completar-corte[data-recibo-id="${reciboId}"]`);
                if (primaryBtn) {
                    actualizarInterfazCorte(primaryBtn.closest('.mobile-primary-action'), 'completado', primaryBtn);
                }
            } else {
                // En caso de error, restaurar botón
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error completando corte:', error);
            // Restaurar botón en caso de error
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };
    
    // Función para cortadores: Deshacer (regresa a Corte)
    window.deshacerCorte = function(btn) {
        const reciboId = btn.dataset.reciboId;
        const pedidoId = btn.dataset.pedidoId;
        const prendaId = btn.dataset.prendaId;
        const nombre = btn.dataset.nombre;
        const card = btn.closest('.orden-card-simple');
        
        if (!reciboId) {
            return;
        }
        
        // Deshabilitar botón y mostrar loading
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-rounded spin">refresh</span> PROCESANDO...';
        
        // Enviar solicitud AJAX a la ruta correcta
        fetch(`/operario/api/recibos/${reciboId}/deshacer`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la interfaz dinámicamente
                actualizarInterfazCorte(card, 'deshacer', btn);
                
                // Actualizar también el drawer mobile si existe
                const drawerBtn = document.querySelector(`#mobile-drawer-${prendaId} .btn-deshacer-corte[data-recibo-id="${reciboId}"]`);
                if (drawerBtn) {
                    actualizarInterfazCorte(drawerBtn.closest('.mobile-actions-drawer'), 'deshacer', drawerBtn);
                }
                
                // Actualizar acción primaria mobile si existe
                const primaryBtn = document.querySelector(`#mobile-primary-${prendaId} .btn-deshacer-corte[data-recibo-id="${reciboId}"]`);
                if (primaryBtn) {
                    actualizarInterfazCorte(primaryBtn.closest('.mobile-primary-action'), 'deshacer', primaryBtn);
                }
            } else {
                // En caso de error, restaurar botón
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error deshaciendo corte:', error);
            // Restaurar botón en caso de error
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };
    
    // Función para actualizar la interfaz dinámicamente
    function actualizarInterfazCorte(container, accion, btnActual) {
        if (!container) return;
        
        const prendaId = btnActual.dataset.prendaId;
        const reciboId = btnActual.dataset.reciboId;
        const nombre = btnActual.dataset.nombre;
        
        if (accion === 'completado') {
            // Cambiar botón de completar a deshacer
            const nuevoBtn = document.createElement('button');
            nuevoBtn.className = 'btn-deshacer-corte';
            nuevoBtn.setAttribute('data-pedido-id', btnActual.dataset.pedidoId);
            nuevoBtn.setAttribute('data-prenda-id', prendaId);
            nuevoBtn.setAttribute('data-recibo-id', reciboId);
            nuevoBtn.setAttribute('data-nombre', nombre);
            nuevoBtn.setAttribute('onclick', 'deshacerCorte(this)');
            nuevoBtn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER';
            
            // Reemplazar botón en el contenedor
            if (btnActual.parentNode) {
                btnActual.parentNode.replaceChild(nuevoBtn, btnActual);
            }
            
            // Actualizar badges de estado si existen
            const badges = container.querySelectorAll('.badge-completado-corte, .badge-estado');
            badges.forEach(badge => {
                badge.classList.add('is-on');
                badge.textContent = 'COMPLETADO';
            });
            
        } else if (accion === 'deshacer') {
            // Cambiar botón de deshacer a completar
            const nuevoBtn = document.createElement('button');
            nuevoBtn.className = 'btn-completar-corte';
            nuevoBtn.setAttribute('data-pedido-id', btnActual.dataset.pedidoId);
            nuevoBtn.setAttribute('data-prenda-id', prendaId);
            nuevoBtn.setAttribute('data-recibo-id', reciboId);
            nuevoBtn.setAttribute('data-nombre', nombre);
            nuevoBtn.setAttribute('onclick', 'completarCorte(this)');
            nuevoBtn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> MARCAR COMPLETADO';
            
            // Reemplazar botón en el contenedor
            if (btnActual.parentNode) {
                btnActual.parentNode.replaceChild(nuevoBtn, btnActual);
            }
            
            // Actualizar badges de estado si existen
            const badges = container.querySelectorAll('.badge-completado-corte, .badge-estado');
            badges.forEach(badge => {
                badge.classList.remove('is-on');
                badge.textContent = 'PENDIENTE';
            });
        }
    }

    // Cerrar drawers al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.mobile-actions-toggle') && !e.target.closest('.mobile-actions-drawer')) {
            document.querySelectorAll('.mobile-actions-drawer.active').forEach(d => {
                d.classList.remove('active');
            });
            document.querySelectorAll('.mobile-actions-toggle.active').forEach(btn => {
                btn.classList.remove('active');
            });
            // Mostrar todas las acciones primarias al cerrar
            document.querySelectorAll('.mobile-primary-action').forEach(action => {
                action.style.display = 'flex';
            });
        }
    });

    // Agregar estilos para animaciÃ³n de spin
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
</script>

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
    <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <h3 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; font-weight: 600;">Agregar Novedad</h3>
        
        <input type="hidden" id="novedadNumeroPedido">
        <input type="hidden" id="novedadPrendaId">
        
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Descripción de la novedad:</label>
            <textarea id="novedadDescripcionText" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; resize: vertical;" placeholder="Describe la novedad..."></textarea>
        </div>
        
        <div id="novedadesHistorial" style="margin-bottom: 1.5rem; max-height: 200px; overflow-y: auto;">
            <!-- Historial de novedades se cargará aquí -->
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button type="button" onclick="cerrarModalNovedad()" style="padding: 0.75rem 1.5rem; border: 1px solid #ddd; background: white; border-radius: 8px; cursor: pointer;">Cancelar</button>
            <button type="button" id="btnGuardarNovedad" onclick="guardarNovedad()" style="padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer;">Guardar</button>
        </div>
    </div>
</div>

<!-- Modal de Costura -->
<div id="modalCostura" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%;">
        <h3 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; font-weight: 600;">Asignar a Costura</h3>
        
        <div style="margin-bottom: 1rem;">
            <p style="margin: 0 0 0.5rem 0; color: #666;">Prenda: <strong id="costuraPrendaNombre"></strong></p>
            <p style="margin: 0 0 0.5rem 0; color: #666;">Recibo: <strong id="costuraReciboNumero"></strong></p>
            <p style="margin: 0 0 1rem 0; color: #666;">Tipo: <strong id="costuraTipoRecibo"></strong></p>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nombre del encargado:</label>
            <input type="text" id="costuraEncargado" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;" placeholder="Ingrese el nombre del encargado">
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Observaciones (opcional):</label>
            <textarea id="costuraObservaciones" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; resize: vertical;" placeholder="Observaciones adicionales..."></textarea>
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button type="button" onclick="cerrarModalCostura()" style="padding: 0.75rem 1.5rem; border: 1px solid #ddd; background: white; border-radius: 8px; cursor: pointer;">Cancelar</button>
            <button type="button" onclick="confirmarPasarACostura()" style="padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer;">Asignar</button>
        </div>
    </div>
</div>

@endsection
