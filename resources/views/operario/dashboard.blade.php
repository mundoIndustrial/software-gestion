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
                        throw new Error('No se encontró #ordenesList en HTML');
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
                    console.warn('[Operario Dashboard] Falló actualizar lista sin recargar, recargando página', e);
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

                    // Fallback público: evento de asignación de corte (compara por nombre)
                    window.EchoInstance.channel('operarios.corte')
                        .subscribed(() => {
                            console.log('[Operario Dashboard] Suscrito OK a canal público', 'operarios.corte');
                        })
                        .error((err) => {
                            console.error('[Operario Dashboard] Error suscribiendo canal público operarios.corte', err);
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

                    // Vista-costura: escuchar cuando insumos aprueba/envía el recibo a producción (área Corte)
                    // Evento broadcast: App\Events\ReciboAprobado -> channel('recibos-costura') -> 'recibo.aprobado'
                    if (String(window.USUARIO_ACTUAL?.rol || '').toLowerCase() === 'vista-costura') {
                        window.EchoInstance.channel('recibos-costura')
                            .subscribed(() => {
                                console.log('[Operario Dashboard] Suscrito OK a canal público', 'recibos-costura');
                            })
                            .error((err) => {
                                console.error('[Operario Dashboard] Error suscribiendo canal público recibos-costura', err);
                            })
                            .listen('.recibo.aprobado', (e) => {
                                console.log('[Operario Dashboard] Evento recibo.aprobado recibido:', e);
                                // Vista-costura ya no muestra recibos en área Corte; no refrescar aquí.
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
                                    console.warn('[Operario Dashboard] Error creando notificación push', err);
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
    <!-- Búsqueda -->
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
            <button class="badge-filtro badge-filtro-active" data-filtro="costura" onclick="filtrarPrendasPorRecibo('costura')">
                <span class="material-symbols-rounded">checkroom</span>
                Costura
            </button>
            <button class="badge-filtro" data-filtro="reflectivo" onclick="filtrarPrendasPorRecibo('reflectivo')">
                <span class="material-symbols-rounded">auto_awesome</span>
                Reflectivo
            </button>
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
                        
                        // Por defecto, ocultar tarjetas REFLECTIVO
                        $displayInicial = $esReflectivo === 'reflectivo' ? 'none' : '';

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
                            $labelAreaVista = $areaReciboActual ?: 'â€”';
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
                            @if(!auth()->user()->hasRole('vista-costura') && !auth()->user()->hasRole('cortador'))
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
                                </div>

                                <div class="orden-cliente">
                                    <p class="cliente-label">CLIENTE</p>
                                    <p class="cliente-name">{{ $prenda['cliente'] }}</p>
                                </div>

                                <div class="orden-prendas">
                                    <p class="prendas-label">
                                        <strong>{{ $prenda['nombre_prenda'] }}</strong>
                                        @if($prenda['descripcion'])
                                            â€” {{ $prenda['descripcion'] }}
                                        @endif
                                    </p>
                                </div>



                                <!-- Contenedor de Botones -->
                                <div class="orden-buttons" style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-start;">
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
                                        @if($tieneReciboReflectivo && $esCosturaAreaRef)
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
                                        {{-- Para costura-reflectivo/vista-costura, crear un botón por cada TIPO de recibo (sin duplicados) --}}
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
                                        {{-- Para otros operarios, un solo botón con tipo de recibo --}}
                                        <button class="btn-ver-recibos" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ $prenda['nombre_prenda'] }}', '{{ $prenda['recibos'][0]['tipo_recibo'] ?? '' }}', {{ !empty($prenda['recibos'][0]['pedido_parcial_id']) ? (int)$prenda['recibos'][0]['pedido_parcial_id'] : 'null' }})">
                                            <span class="material-symbols-rounded">receipt</span>
                                            VER RECIBOS
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Contenido Derecho -->
                            <div class="orden-right">
                                {{-- Pie de página con número de pedido --}}
                                <div class="orden-pedido-footer">
                                    <small>PEDIDO #{{ $prenda['numero_pedido'] }}</small>
                                </div>

                                <div class="orden-right-center">
                                    @if(isset($prenda['recibos'][0]['consecutivo_actual']))
                                        {{-- Mostrar número del recibo --}}
                                        <div class="orden-fecha">
                                            <span class="orden-fecha-label">RECIBO</span>
                                            <span>#{{ $prenda['recibos'][0]['consecutivo_actual'] }}</span>
                                        </div>
                                    @else
                                        {{-- Para otros roles, mostrar número del pedido --}}
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

                                @php
                                    $encargadoCosturaAcciones = $reciboPrincipal['encargado_costura'] ?? null;
                                    $encargadoCosturaAcciones = is_string($encargadoCosturaAcciones) ? trim($encargadoCosturaAcciones) : $encargadoCosturaAcciones;
                                    $tieneEncargadoCosturaAcciones = !empty($encargadoCosturaAcciones);
                                    $puedeMarcarRecibo = auth()->user()->hasAnyRole(['cortador', 'costurero', 'administrador-costura']);
                                @endphp

                                @if($puedeMarcarRecibo && (auth()->user()->hasRole('administrador-costura') ? $tieneEncargadoCosturaAcciones : true))
                                    <div class="orden-right-actions">
                                        <button class="btn-completar-recibo" 
                                                data-recibo-id="{{ $reciboPrincipal['id'] ?? '' }}"
                                                data-completado="{{ $reciboCompletadoArea ? '1' : '0' }}"
                                                onclick="toggleCompletarRecibo(this); event.stopPropagation();">
                                            <span class="material-symbols-rounded">done</span>
                                            {{ $reciboCompletadoArea ? 'COMPLETADO' : 'COMPLETAR' }}
                                        </button>
                                        <button class="btn-deshacer-recibo" 
                                                data-recibo-id="{{ $reciboPrincipal['id'] ?? '' }}"
                                                style="{{ $reciboCompletadoArea ? '' : 'display: none;' }}"
                                                onclick="deshacerCompletarRecibo(this); event.stopPropagation();">
                                            <span class="material-symbols-rounded">undo</span>
                                            DESHACER
                                        </button>
                                    </div>
                                @endif
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

<style>
    .operario-dashboard {
        padding: 1.5rem;
        max-width: 1000px;
        margin: 0 auto;
    }

    /* Búsqueda */
    .search-section {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .search-box {
        width: 100%;
        padding: 0.6rem 1rem 0.6rem 2.5rem;
        border: 1px solid #ddd;
        border-radius: 24px;
        font-size: 0.85rem;
        background: #f9f9f9;
        transition: all 0.3s ease;
    }

    .search-box:focus {
        outline: none;
        background: white;
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
    }

    .search-section .material-symbols-rounded {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 18px;
        pointer-events: none;
    }

    .search-section > .material-symbols-rounded:first-child {
        left: 0.75rem;
    }

    .clear-filter-btn {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .clear-filter-btn:hover {
        background: rgba(0, 0, 0, 0.05);
        color: #666;
    }

    .clear-filter-btn .material-symbols-rounded {
        font-size: 18px;
        position: static;
        transform: none;
        pointer-events: auto;
    }

    /* Órdenes Section */
    .ordenes-section {
        background: white;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #eee;
    }

    .section-title .material-symbols-rounded {
        color: #333;
        font-size: 20px;
    }

    .section-title h3 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .ordenes-count {
        margin-left: auto;
        background: transparent;
        color: #999;
        padding: 0;
        border-radius: 0;
        font-size: 0.85rem;
        font-weight: 600;
    }

    /* Órdenes List */
    .ordenes-list {
        display: grid;
        gap: 0.75rem;
    }

    .orden-card-simple {
        display: flex;
        background: white;
        border: 1px solid #eee;
        border-radius: 6px;
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .orden-card-simple:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border-color: #ddd;
    }

    .orden-border {
        width: 4px;
        flex-shrink: 0;
    }

    .orden-border.en-proceso {
        background: #2196F3;
    }

    .orden-border.pendiente {
        background: #FFC107;
    }

    .orden-border.completada {
        background: #4CAF50;
    }

    .orden-body {
        flex: 1;
        padding: 0.9rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
    }

    .orden-left {
        flex: 1;
    }

    .orden-top {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.4rem;
    }

    .orden-numero-section {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .orden-numero {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #333;
    }

    .estado-badge {
        display: inline-block;
        padding: 0.3rem 0.6rem;
        border-radius: 3px;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .estado-badge.en-proceso {
        background: #E3F2FD;
        color: #1976D2;
    }

    .estado-badge.pendiente {
        background: #FFF3E0;
        color: #F57C00;
    }

    .estado-badge.completada {
        background: #E8F5E9;
        color: #388E3C;
    }

    .orden-cliente {
        margin-bottom: 0;
    }

    .cliente-label {
        margin: 0;
        font-size: 0.65rem;
        color: #999;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .cliente-name {
        margin: 0.15rem 0 0;
        font-size: 0.85rem;
        font-weight: 600;
        color: #333;
    }

    .orden-prendas {
        margin-bottom: 0;
    }

    .prendas-label {
        margin: 0;
        font-size: 0.75rem;
        color: #666;
        line-height: 1.3;
    }

    .orden-right {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-left: 1rem;
    }

    .orden-fecha {
        text-align: right;
        font-size: 0.75rem;
        color: #999;
        font-weight: 500;
        white-space: nowrap;
    }

    .orden-fecha-label {
        display: block;
        font-size: 0.65rem;
        color: #ccc;
        margin-bottom: 0.2rem;
    }

    .action-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #f0f0f0;
        color: #999;
        text-decoration: none;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .action-arrow:hover {
        background: #1976d2;
        color: white;
        transform: translateX(2px);
    }

    .action-arrow .material-symbols-rounded {
        font-size: 16px;
    }

    .orden-pedido-footer {
        position: absolute;
        bottom: 8px;
        right: 12px;
        font-size: 0.65rem;
        color: #bbb;
        background: rgba(255, 255, 255, 0.7);
        padding: 2px 6px;
        border-radius: 3px;
        white-space: nowrap;
        font-weight: 500;
    }

    /* Botón Reportar Pendiente */
    .orden-actions {
        padding: 0.75rem 1rem;
        border-top: 1px solid #f0f0f0;
        background: #fafafa;
    }

    .orden-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.6rem;
        flex-wrap: wrap;
    }

    .btn-reportar-pendiente {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin-top: 0.6rem;
        padding: 0.5rem 0.8rem;
        background: #FFEBEE;
        color: #EF5350;
        border: 1px solid #EF5350;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: fit-content;
        box-shadow: 0 2px 4px rgba(239, 83, 80, 0.15);
    }

    .btn-reportar-pendiente:hover {
        background: #FFCDD2;
        box-shadow: 0 4px 8px rgba(239, 83, 80, 0.25);
        transform: translateY(-1px);
    }

    .btn-reportar-pendiente .material-symbols-rounded {
        font-size: 14px;
    }

    .btn-completar-proceso {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.8rem;
        background: #E8F5E9;
        color: #388E3C;
        border: 1px solid #388E3C;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: fit-content;
        box-shadow: 0 2px 4px rgba(56, 142, 60, 0.15);
    }

    .btn-completar-proceso:hover {
        background: #C8E6C9;
        box-shadow: 0 4px 8px rgba(56, 142, 60, 0.25);
        transform: translateY(-1px);
    }

    .btn-completar-proceso .material-symbols-rounded {
        font-size: 14px;
    }

    .recibo-completado-area {
        background: #E3F2FD;
    }

    .orden-body {
        position: relative;
    }

    .orden-encargado-corner {
        position: absolute;
        top: 10px;
        right: 12px;
        font-size: 0.72rem;
        font-weight: 700;
        color: #111827;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 4px 8px;
        z-index: 2;
        user-select: none;
        max-width: 55%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .orden-top-badges {
        position: absolute;
        top: 8px;
        right: 12px;
        display: flex;
        gap: 6px;
        align-items: center;
        z-index: 2;
    }

    .orden-top-badges span {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 0.62rem;
        font-weight: 800;
        letter-spacing: 0.3px;
        border: 1px solid rgba(15, 23, 42, 0.12);
        background: rgba(255, 255, 255, 0.65);
        color: #0F172A;
        backdrop-filter: blur(2px);
    }

    .label-encargado {
        font-size: 0.62rem;
        font-weight: 800;
        letter-spacing: 0.3px;
        color: #0F172A;
        display: inline-flex;
        align-items: center;
    }

    .badge-completado-corte.is-on {
        background: #BBDEFB;
        border-color: rgba(25, 118, 210, 0.25);
    }

    .orden-right {
        position: relative;
        padding-top: 24px;
        padding-bottom: 56px;
        min-width: 230px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .orden-right-center {
        display: flex;
        align-items: center;
        gap: 1.1rem;
        margin-top: 10px;
    }

    .btn-completar-recibo {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        border: none;
        border-radius: 10px;
        padding: 0.45rem 0.75rem;
        font-size: 0.66rem;
        font-weight: 700;
        letter-spacing: 0.4px;
        cursor: pointer;
        background: #EEF2F7;
        color: #334155;
        transition: all 0.2s ease;
    }

    .orden-right-actions {
        position: absolute;
        right: 12px;
        bottom: 2px;
        display: flex;
        gap: 0.45rem;
        justify-content: flex-end;
        flex-wrap: nowrap;
    }

    .orden-pedido-footer {
        position: absolute;
        top: 34px;
        right: 12px;
        bottom: auto;
    }

    .is-vista-costura .orden-right .orden-pedido-footer {
        top: auto;
        bottom: 8px;
    }

    .btn-completar-recibo .material-symbols-rounded,
    .btn-deshacer-recibo .material-symbols-rounded {
        font-size: 16px;
    }

    .btn-completar-recibo[data-completado="1"] {
        background: #BBDEFB;
        color: #0F172A;
    }

    .btn-deshacer-recibo {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        border: none;
        border-radius: 10px;
        padding: 0.45rem 0.75rem;
        font-size: 0.66rem;
        font-weight: 700;
        letter-spacing: 0.4px;
        cursor: pointer;
        background: #E2E8F0;
        color: #0F172A;
        transition: all 0.2s ease;
    }

    /* Botón Agregar Novedad */
    .btn-agregar-novedad {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.8rem;
        background: #EF5350;
        color: white;
        border: 1px solid #EF5350;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: fit-content;
        box-shadow: 0 2px 4px rgba(239, 83, 80, 0.15);
    }

    .btn-agregar-novedad:hover {
        background: #E53935;
        box-shadow: 0 4px 8px rgba(239, 83, 80, 0.25);
        transform: translateY(-1px);
    }

    .btn-agregar-novedad .material-symbols-rounded {
        font-size: 14px;
    }

    /* Recibos de Costura */
    .recibos-info {
        margin-top: 0.5rem;
        margin-bottom: 0.6rem;
    }

    .recibos-lista {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }

    .recibo-badge {
        display: inline-block;
        padding: 0.3rem 0.6rem;
        border-radius: 3px;
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    .recibo-costura {
        background: #E3F2FD;
        color: #1976D2;
        border: 1px solid #90CAF9;
    }

    .recibo-costura_bodega {
        background: #FFF3E0;
        color: #F57C00;
        border: 1px solid #FFB74D;
    }

    .recibo-estampado {
        background: #F3E5F5;
        color: #7B1FA2;
        border: 1px solid #CE93D8;
    }

    .recibo-bordado {
        background: #E0F2F1;
        color: #00796B;
        border: 1px solid #80DEEA;
    }

    .recibo-reflectivo {
        background: #FCE4EC;
        color: #C2185B;
        border: 1px solid #F48FB1;
    }

    .recibo-dtf {
        background: #E8F5E9;
        color: #388E3C;
        border: 1px solid #81C784;
    }

    .recibo-sublimado {
        background: #EDE7F6;
        color: #512DA8;
        border: 1px solid #B39DDB;
    }

    /* Botón Ver Recibos */
    .btn-ver-recibos {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.8rem;
        background: #E3F2FD;
        color: #1976D2;
        border: 1px solid #1976D2;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: fit-content;
        box-shadow: 0 2px 4px rgba(25, 118, 210, 0.15);
    }

    .btn-ver-recibos:hover {
        background: #BBDEFB;
        box-shadow: 0 4px 8px rgba(25, 118, 210, 0.25);
        transform: translateY(-1px);
    }

    .btn-ver-recibos .material-symbols-rounded {
        font-size: 14px;
    }

    /* Botón Pasar a Control Calidad */
    .btn-pasar-cc {
        background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        border: none;
        padding: 0.6rem 1.2rem;
        border-radius: 4px;
        color: white;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.3px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: fit-content;
        box-shadow: 0 2px 4px rgba(76, 175, 80, 0.15);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-pasar-cc:hover {
        background: linear-gradient(135deg, #45a049 0%, #3d8b40 100%);
        box-shadow: 0 4px 8px rgba(76, 175, 80, 0.25);
        transform: translateY(-1px);
    }

    .btn-pasar-cc .material-symbols-rounded {
        font-size: 14px;
    }

    /* Botón Pasar a Costura */
    .btn-pasar-costura {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        border: none;
        padding: 0.6rem 1.2rem;
        border-radius: 4px;
        color: white;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.3px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: fit-content;
        box-shadow: 0 2px 4px rgba(33, 150, 243, 0.15);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-pasar-costura:hover {
        background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
        box-shadow: 0 4px 8px rgba(33, 150, 243, 0.25);
        transform: translateY(-1px);
    }

    .btn-pasar-costura .material-symbols-rounded {
        font-size: 14px;
    }

    .btn-pasar-costura.btn-deshacer-costura {
        background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        box-shadow: 0 2px 4px rgba(255, 152, 0, 0.15);
    }

    .btn-pasar-costura.btn-deshacer-costura:hover {
        background: linear-gradient(135deg, #F57C00 0%, #EF6C00 100%);
        box-shadow: 0 4px 8px rgba(255, 152, 0, 0.25);
    }

    /* Animación de cargando para botones deshacer */
    @keyframes spin-loading {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .spin-icon {
        animation: spin-loading 1s linear infinite;
        display: inline-block;
    }
    button:disabled {
        cursor: not-allowed !important;
    }

    /* Modal Costura Encargado */
    .modal-costura-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }

    .modal-costura-content {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        width: 90%;
        max-width: 400px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }

    .modal-costura-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .modal-costura-header .modal-icon {
        font-size: 48px;
        color: #2196F3;
    }

    .modal-costura-header h2 {
        font-size: 1.1rem;
        color: #333;
        margin-top: 0.5rem;
    }

    .modal-costura-body .campo-grupo {
        margin-bottom: 1rem;
    }

    .modal-costura-body .campo-grupo label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.4rem;
    }

    .modal-costura-body .campo-grupo input {
        width: 100%;
        padding: 0.7rem 1rem;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
    }

    .modal-costura-body .campo-grupo input:focus {
        border-color: #2196F3;
        outline: none;
    }

    .modal-costura-body .info-prenda {
        background: #f5f5f5;
        padding: 0.8rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-size: 0.85rem;
        color: #555;
    }

    .modal-costura-body .info-prenda strong {
        color: #333;
    }

    .modal-costura-footer {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }

    .modal-costura-footer .btn-cancelar-costura {
        flex: 1;
        padding: 0.7rem;
        border: 2px solid #e0e0e0;
        background: white;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        text-transform: uppercase;
        transition: all 0.3s ease;
    }

    .modal-costura-footer .btn-cancelar-costura:hover {
        border-color: #bbb;
        background: #f5f5f5;
    }

    .modal-costura-footer .btn-confirmar-costura {
        flex: 1;
        padding: 0.7rem;
        border: none;
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        color: white;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        text-transform: uppercase;
        transition: all 0.3s ease;
    }

    .modal-costura-footer .btn-confirmar-costura:hover {
        background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
        box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #999;
    }

    .empty-state .material-symbols-rounded {
        font-size: 48px;
        margin-bottom: 0.75rem;
        opacity: 0.5;
    }

    .empty-state p {
        font-size: 0.9rem;
        margin: 0;
    }

    /* Filtros Badges */
    .filtros-badges {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding: 0.5rem 0;
    }

    .badge-filtro {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.8rem;
        background: #f5f5f5;
        color: #666;
        border: 1px solid #ddd;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .badge-filtro:hover {
        background: #e0e0e0;
        border-color: #bbb;
        transform: translateY(-1px);
    }

    .badge-filtro .material-symbols-rounded {
        font-size: 16px;
    }

    .badge-filtro-active {
        background: #1976D2;
        color: white;
        border-color: #1976D2;
        box-shadow: 0 2px 4px rgba(25, 118, 210, 0.2);
    }

    .badge-filtro-active:hover {
        background: #1565C0;
        border-color: #1565C0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .operario-dashboard {
            padding: 1rem;
        }

        .operario-header {
            margin-bottom: 1rem;
        }

        .operario-name {
            font-size: 1rem;
        }

        .orden-body {
            flex-direction: column;
            align-items: flex-start;
        }

        .orden-right {
            width: 100%;
            margin-left: 0;
            margin-top: 0.5rem;
            justify-content: space-between;
        }

        .orden-fecha {
            text-align: left;
        }
    }
</style>

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

    // Función para filtrar prendas por tipo de recibo
    window.filtrarPrendasPorRecibo = function(filtro) {
        console.log(' [FILTRO] Iniciando filtro:', filtro);
        
        // Actualizar estado de botones
        document.querySelectorAll('.badge-filtro').forEach(btn => {
            btn.classList.remove('badge-filtro-active');
        });
        document.querySelector(`[data-filtro="${filtro}"]`).classList.add('badge-filtro-active');

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

    // Función para abrir detalles de recibos
    function abrirDetallesRecibos(numeroPedido, prendaId, nombrePrenda, tipoRecibo) {
        console.log(' [ABRIR DETALLES RECIBOS] ===== INICIANDO =====');
        console.log(' Parámetros recibidos:', {
            numeroPedido: numeroPedido,
            prendaId: prendaId,
            nombrePrenda: nombrePrenda,
            tipoRecibo: tipoRecibo,
            tipoNumeroPedido: typeof numeroPedido,
            tipoPrendaId: typeof prendaId,
            tipoNombrePrenda: typeof nombrePrenda
        });
        
        // Validar que tengamos el número de pedido
        if (!numeroPedido || numeroPedido === '' || numeroPedido === null || numeroPedido === undefined) {
            console.error(' ERROR: numeroPedido está vacío o undefined', numeroPedido);
            alert('Error: No se pudo determinar el número de pedido');
            return false;
        }
        
        // Convertir a string si es número
        const numeroPedidoStr = String(numeroPedido).trim();
        console.log('ðŸ“ numeroPedido normalizado:', numeroPedidoStr);
        
        // Construir la URL con prenda_id y tipo de recibo si se proporcionan
        let url = '/operario/pedido/' + numeroPedidoStr;
        const params = new URLSearchParams();
        
        if (prendaId) {
            params.append('prenda_id', prendaId);
            console.log('ðŸ“ Prenda ID:', prendaId);
        }
        
        if (tipoRecibo) {
            params.append('tipo_recibo', tipoRecibo);
            console.log('ðŸ“ Tipo de recibo:', tipoRecibo);
        }
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        console.log('ðŸŒ URL a navegar:', url);
        console.log(' Navegando a:', url);
        
        // Navegar
        try {
            console.log('â³ Iniciando navegación...');
            window.location.href = url;
            console.log(' Navegación iniciada exitosamente');
            return false;
        } catch (error) {
            console.error(' Error al navegar:', error);
            return false;
        }
    }

    // Modal Reportar Pendiente
    function abrirModalReportar(numeroPedido, cliente) {
        const modal = document.getElementById('modalReportar');
        document.getElementById('numeroPedidoReportar').value = numeroPedido;
        document.getElementById('numeroPedidoDisplay').textContent = '#' + numeroPedido;
        document.getElementById('clienteReportar').textContent = cliente;
        document.getElementById('novedadText').value = '';
        document.getElementById('novedadesAnteriores').innerHTML = '<p style="color: #999;">Cargando novedades...</p>';
        
        // Cargar novedades anteriores
        fetch('/operario/api/novedades/' + numeroPedido)
            .then(response => response.json())
            .then(data => {
                const contenedor = document.getElementById('novedadesAnteriores');
                if (data.success && data.novedades) {
                    const novedades = data.novedades.split('\n\n').filter(n => n.trim());
                    if (novedades.length > 0) {
                        contenedor.innerHTML = '<div class="novedades-list">' + 
                            novedades.map(novedad => `<div class="novedad-item">${novedad}</div>`).join('') + 
                            '</div>';
                    } else {
                        contenedor.innerHTML = '<p style="color: #999;">No hay novedades anteriores</p>';
                    }
                } else {
                    contenedor.innerHTML = '<p style="color: #999;">No hay novedades anteriores</p>';
                }
            })
            .catch(error => {
                document.getElementById('novedadesAnteriores').innerHTML = '<p style="color: #999;">No hay novedades anteriores</p>';
            });
        
        modal.style.display = 'flex';
    }

    function cerrarModalReportar() {
        const modal = document.getElementById('modalReportar');
        modal.style.display = 'none';
        document.getElementById('novedadText').value = '';
    }

    function enviarReporte() {
        const numeroPedido = document.getElementById('numeroPedidoReportar').value;
        const novedad = document.getElementById('novedadText').value.trim();

        if (!novedad) {
            alert('Por favor describe el problema o novedad');
            return;
        }

        // Enviar al servidor
        fetch('{{ route("operario.reportar-pendiente") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                numero_pedido: numeroPedido,
                novedad: novedad
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cerrarModalReportar();
                abrirModalExito('Novedad reportada correctamente', 'El estado ha sido cambiado a Pendiente.');
                
                // Recargar después de 2 segundos para que se actualice con los nuevos datos
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                alert('Error: ' + (data.message || 'No se pudo reportar la novedad'));
            }
        })
        .catch(error => {
            alert('Error al reportar la novedad');
        });
    }

    // Cerrar modal al hacer click fuera
    window.onclick = function(event) {
        const modal = document.getElementById('modalReportar');
        const modalExito = document.getElementById('modalExito');
        if (event.target === modal) {
            cerrarModalReportar();
        }
        if (event.target === modalExito) {
            cerrarModalExito();
        }
    }

    // Modal de éxito
    function abrirModalExito(titulo, mensaje) {
        document.getElementById('exitoTitulo').textContent = titulo;
        document.getElementById('exitoMensaje').textContent = mensaje;
        document.getElementById('modalExito').style.display = 'flex';
    }

    function cerrarModalExito() {
        document.getElementById('modalExito').style.display = 'none';
    }

    // Cerrar modal exitoal hacer click fuera

    // Función para marcar proceso como completado
    window.marcarProcesoCompletado = async function(numeroPedido) {
        try {
            const response = await fetch(`/operario/api/completar-proceso/${numeroPedido}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (response.ok) {
                // Mostrar modal de éxito
                document.getElementById('exitoTitulo').textContent = '¡Proceso Completado!';
                document.getElementById('exitoMensaje').textContent = 'El proceso ha sido marcado como completado correctamente.';
                document.getElementById('modalExito').style.display = 'flex';
            } else {
                // Mostrar modal de error
                document.getElementById('exitoTitulo').textContent = ' Error';
                document.getElementById('exitoMensaje').textContent = data.message || 'No se pudo completar el proceso';
                document.getElementById('modalExito').style.display = 'flex';
            }
        } catch (error) {
            // Mostrar modal de error
            document.getElementById('exitoTitulo').textContent = ' Error';
            document.getElementById('exitoMensaje').textContent = 'Error al completar el proceso';
            document.getElementById('modalExito').style.display = 'flex';
        }
    };

    window.cerrarModalExito = function() {
        const modal = document.getElementById('modalExito');
        modal.style.display = 'none';
        
        // Recargar la página si fue exitoso (cuando el título es "¡Proceso Completado!")
        const titulo = document.getElementById('exitoTitulo').textContent;
        if (titulo === '¡Proceso Completado!') {
            setTimeout(() => {
                location.reload();
            }, 500);
        }
    };

    window.toggleCompletarRecibo = async function(btn) {
        const reciboId = btn.dataset.reciboId;
        if (!reciboId) {
            return;
        }

        const yaCompletado = btn.dataset.completado === '1';
        if (yaCompletado) {
            return;
        }

        try {
            const response = await fetch(`/operario/api/recibos/${reciboId}/completar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();
            if (!data.success) {
                document.getElementById('exitoTitulo').textContent = ' Error';
                document.getElementById('exitoMensaje').textContent = data.message || 'No se pudo completar el recibo';
                document.getElementById('modalExito').style.display = 'flex';
                return;
            }

            btn.dataset.completado = '1';
            btn.innerHTML = '<span class="material-symbols-rounded">done</span>COMPLETADO';
            btn.setAttribute('data-completado', '1');

            const cardBody = btn.closest('.orden-body');
            if (cardBody) {
                cardBody.classList.add('recibo-completado-area');
            }

            const btnDeshacer = btn.parentElement?.querySelector('.btn-deshacer-recibo');
            if (btnDeshacer) {
                btnDeshacer.style.display = '';
            }
        } catch (error) {
            document.getElementById('exitoTitulo').textContent = ' Error';
            document.getElementById('exitoMensaje').textContent = 'Error al completar el recibo';
            document.getElementById('modalExito').style.display = 'flex';
        }
    };

    window.deshacerCompletarRecibo = async function(btn) {
        const reciboId = btn.dataset.reciboId;
        if (!reciboId) {
            return;
        }

        try {
            const response = await fetch(`/operario/api/recibos/${reciboId}/deshacer`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();
            if (!data.success) {
                document.getElementById('exitoTitulo').textContent = ' Error';
                document.getElementById('exitoMensaje').textContent = data.message || 'No se pudo deshacer';
                document.getElementById('modalExito').style.display = 'flex';
                return;
            }

            const container = btn.parentElement;
            const btnCompletar = container?.querySelector('.btn-completar-recibo');
            if (btnCompletar) {
                btnCompletar.dataset.completado = '0';
                btnCompletar.setAttribute('data-completado', '0');
                btnCompletar.innerHTML = '<span class="material-symbols-rounded">done</span>COMPLETAR';
            }

            const cardBody = btn.closest('.orden-body');
            if (cardBody) {
                cardBody.classList.remove('recibo-completado-area');
            }

            btn.style.display = 'none';
        } catch (error) {
            document.getElementById('exitoTitulo').textContent = ' Error';
            document.getElementById('exitoMensaje').textContent = 'Error al deshacer el recibo';
            document.getElementById('modalExito').style.display = 'flex';
        }
    };
</script>

<!-- Modal Reportar Pendiente -->
<div id="modalReportar" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <span class="material-symbols-rounded modal-icon">warning</span>
            <h2>REPORTAR PENDIENTE</h2>
        </div>

        <div class="modal-body">
            <p class="modal-description">Describe el problema o novedad. La orden pasará a estado <strong>Pendiente</strong>.</p>
            
            <div class="modal-info">
                <p class="info-label">ORDEN:</p>
                <p class="info-value" id="numeroPedidoDisplay"></p>
                <p class="info-label">CLIENTE:</p>
                <p class="info-value" id="clienteReportar"></p>
            </div>

            <!-- Novedades anteriores -->
            <div style="margin: 15px 0; padding: 10px; background: #f9f9f9; border-radius: 4px; border-left: 3px solid #3498db;">
                <p class="info-label" style="margin-top: 0;">NOVEDADES ANTERIORES:</p>
                <div id="novedadesAnteriores" style="max-height: 150px; overflow-y: auto; font-size: 13px;"></div>
            </div>

            <textarea 
                id="novedadText" 
                class="modal-textarea" 
                placeholder="Ej: Falta talla M, insumo hilo rojo, error en medidas..."
                rows="5"></textarea>
            
            <input type="hidden" id="numeroPedidoReportar">
        </div>

        <div class="modal-footer">
            <button class="btn-cancelar" onclick="cerrarModalReportar()">CANCELAR</button>
            <button class="btn-enviar" onclick="enviarReporte()">ENVIAR</button>
        </div>
    </div>
</div>

<style>
    .novedades-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .novedad-item {
        padding: 8px;
        background: white;
        border-radius: 3px;
        border-left: 2px solid #27ae60;
        font-size: 12px;
        color: #2c3e50;
        line-height: 1.4;
    }
    
    /* Modal Overlay */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        max-width: 450px;
        width: 90%;
        overflow: hidden;
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1.5rem;
        background: #fafafa;
        border-bottom: 1px solid #eee;
    }

    .modal-icon {
        color: #EF5350;
        font-size: 28px;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #EF5350;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-description {
        margin: 0 0 1rem;
        font-size: 0.85rem;
        color: #666;
        line-height: 1.5;
    }

    .modal-info {
        background: #f9f9f9;
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }

    .info-label {
        margin: 0 0 0.25rem;
        font-size: 0.7rem;
        color: #999;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .info-value {
        margin: 0 0 0.75rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: #333;
    }

    .info-value:last-child {
        margin-bottom: 0;
    }

    .modal-textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.85rem;
        color: #333;
        resize: vertical;
        transition: all 0.3s ease;
    }

    .modal-textarea:focus {
        outline: none;
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
    }

    .modal-textarea::placeholder {
        color: #999;
    }

    .modal-footer {
        display: flex;
        gap: 0.75rem;
        padding: 1rem 1.5rem;
        background: #fafafa;
        border-top: 1px solid #eee;
    }

    .btn-cancelar {
        flex: 1;
        padding: 0.75rem;
        background: #f0f0f0;
        color: #666;
        border: none;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-cancelar:hover {
        background: #e0e0e0;
    }

    .btn-enviar {
        flex: 1;
        padding: 0.75rem;
        background: #EF5350;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-enviar:hover {
        background: #E53935;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(239, 83, 80, 0.3);
    }

    /* Modal de Éxito */
    .modal-exito {
        max-width: 400px;
    }

    .modal-header-exito {
        background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        border: none;
    }

    .modal-header-exito h2 {
        color: white;
    }

    .modal-icon-exito {
        color: white;
        font-size: 32px;
    }

    /* Filtros Badges */
    .filtros-badges {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        padding: 0.75rem 0;
        border-bottom: 1px solid #eee;
    }

    .badge-filtro {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1rem;
        background: #f5f5f5;
        color: #666;
        border: 1px solid #e0e0e0;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .badge-filtro .material-symbols-rounded {
        font-size: 16px;
    }

    .badge-filtro:hover {
        background: #efefef;
        border-color: #d0d0d0;
    }

    .badge-filtro-active {
        background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
        color: white;
        border-color: #1565c0;
        box-shadow: 0 2px 8px rgba(25, 118, 210, 0.2);
    }

    .badge-filtro-active:hover {
        background: linear-gradient(135deg, #1565c0 0%, #1565c0 100%);
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
    }
</style>

<!-- Modal Éxito -->
<div id="modalExito" class="modal-overlay">
    <div class="modal-content modal-exito">
        <div class="modal-header modal-header-exito">
            <span class="material-symbols-rounded modal-icon-exito">check_circle</span>
            <h2 id="exitoTitulo">¡Éxito!</h2>
        </div>

        <div class="modal-body">
            <p id="exitoMensaje" style="text-align: center; color: #2c3e50; font-size: 16px; margin: 30px 0;">
                La novedad ha sido guardada correctamente.
            </p>
        </div>

        <div class="modal-footer" style="justify-content: center;">
            <button class="btn-enviar" style="width: 150px;" onclick="cerrarModalExito()">ACEPTAR</button>
        </div>
    </div>
</div>

<script>
// Función para filtrar pedidos por tipo
function filtrarPedidos(filtro) {
    const ordenesList = document.getElementById('ordenesList');
    const cards = ordenesList.querySelectorAll('.orden-card-simple');
    const badges = document.querySelectorAll('.badge-filtro');

    // Actualizar estado de badges
    badges.forEach(badge => {
        badge.classList.remove('badge-filtro-active');
        if (badge.dataset.filtro === filtro) {
            badge.classList.add('badge-filtro-active');
        }
    });

    // Filtrar tarjetas
    cards.forEach(card => {
        const tipoCard = card.dataset.tipo;
        
        if (filtro === 'todos') {
            card.style.display = 'flex';
        } else if (filtro === tipoCard) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });

    // Actualizar contador
    const cardosVisibles = Array.from(cards).filter(card => card.style.display !== 'none').length;
    const ordenesCont = document.querySelector('.ordenes-count');
    if (ordenesCont) {
        ordenesCont.textContent = cardosVisibles;
    }
}

// Búsqueda global (mantener funcionando con filtros)
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const clearBtn = document.getElementById('clearFilterBtn');
            
            // Mostrar/ocultar botón de limpiar
            if (clearBtn) {
                clearBtn.style.display = searchTerm ? 'flex' : 'none';
            }
            
            const ordenesList = document.getElementById('ordenesList');
            const cards = ordenesList.querySelectorAll('.orden-card-simple');
            let visibles = 0;

            cards.forEach(card => {
                // Si el filtro activo no es 'todos', aplicar también el filtro de tipo
                const badgeActivo = document.querySelector('.badge-filtro-active');
                const filtroActivo = badgeActivo ? badgeActivo.dataset.filtro : 'todos';
                const debeVisiblePorTipo = filtroActivo === 'todos' || card.dataset.tipoRecibo === filtroActivo;

                // Extraer número de RECIBO del DOM (desde .orden-right)
                const reciboDom = card.querySelector('.orden-right .orden-fecha span:not(.orden-fecha-label)');
                const numeroRecibo = reciboDom ? reciboDom.textContent.toLowerCase().trim() : '';
                const cliente = card.dataset.cliente ? card.dataset.cliente.toLowerCase() : '';
                
                // Extraer nombre de PRENDA del DOM (desde .prendas-label strong)
                const prendaDom = card.querySelector('.orden-prendas .prendas-label strong');
                const nombrePrenda = prendaDom ? prendaDom.textContent.toLowerCase().trim() : '';
                
                const coincide = numeroRecibo.includes(searchTerm) || cliente.includes(searchTerm) || nombrePrenda.includes(searchTerm);

                if (coincide && debeVisiblePorTipo) {
                    card.style.display = 'flex';
                    visibles++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Actualizar contador
            const ordenesCont = document.querySelector('.ordenes-count');
            if (ordenesCont) {
                ordenesCont.textContent = visibles;
            }
        });
    }
});
</script>

<!-- Modal Agregar Novedad -->
<div id="modalNovedad" class="modal-overlay">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header" style="background: #1e293b; border-bottom: 1px solid #e2e8f0;">
            <h2 style="color: white; margin: 0; font-size: 1rem; font-weight: 600;">
                ðŸ’¬ Novedades - Pedido <span id="prendaNumeroPedidoDisplay">#</span> - Recibo <span id="prendaNumeroReciboDisplay"></span>
            </h2>
            <button onclick="cerrarModalNovedad()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem; padding: 0; margin: 0;">âœ•</button>
        </div>

        <div class="modal-body" style="max-height: 600px; overflow-y: auto;">
            <!-- Historial de Novedades -->
            <div id="novedadesHistorial" style="margin-bottom: 2rem; max-height: 350px; overflow-y: auto;">
                <p style="color: #999; text-align: center; padding: 1rem;">Cargando...</p>
            </div>
            
            <div style="border-top: 1px solid #e2e8f0; padding-top: 1.5rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #0f172a; margin-bottom: 0.75rem;">Agregar Nueva Novedad:</label>
                <textarea 
                    id="novedadDescripcionText" 
                    style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.875rem; font-family: 'Poppins', sans-serif; resize: none; focus: outline none;"
                    placeholder="Escribe tu novedad aquí..." 
                    rows="4"></textarea>

                <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                    <button type="button" onclick="guardarNovedad()" style="flex: 1; padding: 0.5rem 1rem; background: #22c55e; color: white; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease;">
                        âœ“ Guardar Novedad
                    </button>
                    <button type="button" onclick="cerrarModalNovedad()" style="flex: 1; padding: 0.5rem 1rem; background: #94a3b8; color: white; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease;">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Novedad -->
<div id="modalEditarNovedad" class="modal-overlay">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header" style="background: #1e293b; border-bottom: 1px solid #e2e8f0;">
            <h2 style="color: white; margin: 0; font-size: 1rem; font-weight: 600;">
                âœï¸ Editar Novedad
            </h2>
            <button onclick="cerrarModalEditarNovedad()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem; padding: 0; margin: 0;">âœ•</button>
        </div>

        <div class="modal-body" style="padding: 1.5rem;">
            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem;">DESCRIPCIÓN</label>
            <textarea 
                id="editarNovedadText" 
                style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.875rem; font-family: 'Poppins', sans-serif; resize: none;"
                placeholder="Edita la novedad..." 
                rows="4"></textarea>
            
            <div style="margin-bottom: 1rem; margin-top: 1rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem;">TIPO DE NOVEDAD</label>
                <select id="editarTipoSelect" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-family: 'Poppins', sans-serif; font-size: 0.85rem; color: #333; background: white;">
                    <option value="observacion">Observación</option>
                    <option value="problema">Problema</option>
                    <option value="cambio">Cambio</option>
                    <option value="correccion">Corrección</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                <button type="button" onclick="guardarNovedadEditada()" style="flex: 1; padding: 0.5rem 1rem; background: #22c55e; color: white; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease;">
                    âœ“ Guardar Cambios
                </button>
                <button type="button" onclick="cerrarModalEditarNovedad()" style="flex: 1; padding: 0.5rem 1rem; background: #94a3b8; color: white; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease;">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .modal-select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.85rem;
        color: #333;
        background: white;
        transition: all 0.3s ease;
    }

    .modal-select:focus {
        outline: none;
        border-color: #3F51B5;
        box-shadow: 0 0 0 3px rgba(63, 81, 181, 0.1);
    }

    /* Modal de Mensajes */
    .modal-mensaje-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        z-index: 10000;
    }

    .modal-mensaje-contenido {
        background: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        max-width: 400px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        text-align: center;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-mensaje-titulo {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
        color: #1f2937;
    }

    .modal-mensaje-texto {
        font-size: 0.9rem;
        color: #6b7280;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }

    .modal-mensaje-exito .modal-mensaje-titulo {
        color: #15803d;
    }

    .modal-mensaje-error .modal-mensaje-titulo {
        color: #b91c1c;
    }

    .modal-mensaje-info .modal-mensaje-titulo {
        color: #0369a1;
    }

    .modal-mensaje-icono {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }

    .modal-mensaje-boton {
        background: #3F51B5;
        color: white;
        border: none;
        padding: 0.5rem 1.5rem;
        border-radius: 0.25rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.85rem;
        text-transform: uppercase;
    }

    .modal-mensaje-boton:hover {
        background: #3949AB;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(63, 81, 181, 0.3);
    }
</style>

<!-- Modal Pasar a Costura (Encargado) -->
<div id="modalCostura" class="modal-costura-overlay">
    <div class="modal-costura-content">
        <div class="modal-costura-header">
            <span class="material-symbols-rounded modal-icon">checkroom</span>
            <h2>PASAR A COSTURA</h2>
        </div>
        <div class="modal-costura-body">
            <div class="info-prenda">
                <strong>Prenda:</strong> <span id="costuraPrendaNombre"></span><br>
                <strong>Recibo:</strong> #<span id="costuraReciboNumero"></span>
            </div>
            <div class="campo-grupo">
                <label for="costuraEncargado">Encargado del proceso</label>
                <input type="text" id="costuraEncargado" placeholder="Nombre del encargado..." autocomplete="off" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
            </div>
        </div>
        <div class="modal-costura-footer">
            <button class="btn-cancelar-costura" onclick="cerrarModalCostura()">CANCELAR</button>
            <button class="btn-confirmar-costura" onclick="confirmarPasarACostura()">CONFIRMAR</button>
        </div>
    </div>
</div>

<!-- Modal de Mensajes -->
<div id="modalMensaje" class="modal-mensaje-overlay">
    <div class="modal-mensaje-contenido" id="modalMensajeContenido" style="position: relative;">
        <button onclick="cerrarModalMensaje()" style="position: absolute; top: 0.75rem; right: 0.75rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999; transition: all 0.3s ease;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#999'">âœ•</button>
        <div class="modal-mensaje-icono" id="modalMensajeIcono">âœ“</div>
        <div class="modal-mensaje-titulo" id="modalMensajeTitulo">Éxito</div>
        <div class="modal-mensaje-texto" id="modalMensajeTexto">La operación se realizó correctamente</div>
        <button class="modal-mensaje-boton" onclick="cerrarModalMensaje()">Aceptar</button>
    </div>
</div>

<script>
    // Variables globales para el modal
    window.novedadActual = {
        numeroPedido: null,
        prendaId: null
    };

    // Funciones para manejar Modal de Mensajes
    function mostrarMensaje(titulo, texto, tipo = 'exito', icono = 'âœ“') {
        const modal = document.getElementById('modalMensaje');
        const contenido = document.getElementById('modalMensajeContenido');
        const iconoEl = document.getElementById('modalMensajeIcono');
        const tituloEl = document.getElementById('modalMensajeTitulo');
        const textoEl = document.getElementById('modalMensajeTexto');
        const boton = document.querySelector('.modal-mensaje-boton');
        
        if (!modal) return;
        
        // Limpiar clases
        contenido.classList.remove('modal-mensaje-exito', 'modal-mensaje-error', 'modal-mensaje-info');
        
        // Asignar nueva clase
        contenido.classList.add(`modal-mensaje-${tipo}`);
        
        // Llenar contenido
        iconoEl.textContent = icono;
        tituloEl.textContent = titulo;
        textoEl.textContent = texto;
        
        // Resetear botón a valores por defecto (si existe)
        if (boton) {
            boton.textContent = 'Aceptar';
            boton.style.background = '#3F51B5';
            boton.style.color = 'white';
            boton.style.border = 'none';
            boton.style.padding = '0.5rem 1.5rem';
            boton.style.borderRadius = '0.25rem';
            boton.style.fontWeight = '700';
            boton.style.cursor = 'pointer';
            boton.style.transition = 'all 0.3s ease';
            boton.style.fontSize = '0.85rem';
            boton.style.textTransform = 'uppercase';
            boton.onmouseover = function() { this.style.background = '#3949AB'; };
            boton.onmouseout = function() { this.style.background = '#3F51B5'; };
            boton.onclick = cerrarModalMensaje;
        }
        
        // Mostrar modal
        modal.style.display = 'flex';
    }

    function cerrarModalMensaje() {
        const modal = document.getElementById('modalMensaje');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Función auxiliar para mensaje de éxito
    function mostrarExito(titulo, texto = '') {
        mostrarMensaje(titulo, texto, 'exito', 'âœ“');
    }

    // Función auxiliar para mensaje de error
    function mostrarError(titulo, texto = '') {
        mostrarMensaje(titulo, texto, 'error', 'âœ•');
    }

    // Función auxiliar para mensaje de información
    function mostrarInfo(titulo, texto = '') {
        mostrarMensaje(titulo, texto, 'info', 'â„¹');
    }

    // Cerrar modal de mensajes al hacer click fuera
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('modalMensaje');
        if (modal && event.target === modal) {
            cerrarModalMensaje();
        }
    });

    // Variables globales para el modal


    // Función para abrir modal de agregar novedad
    function abrirModalNovedad(numeroPedido, prendaId, nombrePrenda, numeroRecibo) {
        console.log('ðŸ“ Abriendo modal novedad', {numeroPedido, prendaId, nombrePrenda, numeroRecibo});
        
        const modal = document.getElementById('modalNovedad');
        if (!modal) {
            console.error('Modal no encontrado');
            mostrarError('Error', 'Modal de novedades no cargado');
            return;
        }
        
        window.novedadActual.numeroPedido = numeroPedido;
        window.novedadActual.prendaId = prendaId;
        
        // Establecer valores con validación
        const prendaNumeroDisplay = document.getElementById('prendaNumeroPedidoDisplay');
        const reciboNumeroDisplay = document.getElementById('prendaNumeroReciboDisplay');
        const textareaDescripcion = document.getElementById('novedadDescripcionText');
        
        if (prendaNumeroDisplay) prendaNumeroDisplay.textContent = '#' + numeroPedido;
        if (reciboNumeroDisplay) reciboNumeroDisplay.textContent = numeroRecibo;
        if (textareaDescripcion) textareaDescripcion.value = '';
        
        // Cargar novedades existentes del usuario actual
        cargarNovedadesDelUsuario(numeroPedido, prendaId);
        
        modal.style.display = 'flex';
    }

    function cargarNovedadesDelUsuario(numeroPedido, prendaId) {
        console.log('ðŸ“‹ Cargando novedades', {numeroPedido, prendaId});
        
        fetch(`/operario/api/novedades/${numeroPedido}/${prendaId}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('âœ“ Novedades obtenidas:', data);
            
            if (window.USUARIO_ACTUAL && window.USUARIO_ACTUAL.rol === 'cortador') {
                data.novedades = data.novedades.filter(n => n.creado_por === window.USUARIO_ACTUAL.id);
            }
            mostrarNovedades(data.novedades || []);
        })
        .catch(error => {
            console.error('âŒ Error cargando novedades:', error);
        });
    }

    function mostrarNovedades(novedades) {
        const historial = document.getElementById('novedadesHistorial');
        if (!historial) {
            console.error('Historial no encontrado');
            return;
        }
        
        if (novedades.length === 0) {
            historial.innerHTML = '<p style="color: #999; text-align: center; padding: 1rem;">No hay novedades registradas</p>';
            return;
        }
        
        historial.innerHTML = novedades.map(novedad => {
            const puedeEditar = window.USUARIO_ACTUAL && novedad.creado_por === window.USUARIO_ACTUAL.id;
            const tipoColorMap = {
                'observacion': 'background: #dbeafe; color: #1e40af;',
                'problema': 'background: #fee2e2; color: #7f1d1d;',
                'cambio': 'background: #fef3c7; color: #92400e;',
                'correccion': 'background: #dcfce7; color: #166534;',
                'aprobacion': 'background: #dcfce7; color: #166534;',
                'rechazo': 'background: #fee2e2; color: #7f1d1d;'
            };
            
            const tipoColor = tipoColorMap[novedad.tipo_novedad] || 'background: #f3f4f6; color: #1f2937;';
            const esEditada = novedad.editado ? ' <span style="display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 700; border-radius: 0.25rem; background: #fed7aa; color: #92400e;">EDITADO</span>' : '';
            
            const botones = puedeEditar ? `
                <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                    <button onclick="editarNovedad(${novedad.id}, '${novedad.novedad_texto.replace(/'/g, "\\'")}', '${novedad.tipo_novedad}')" style="padding: 0.25rem 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 0.25rem; font-size: 0.75rem; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'" title="Editar novedad">
                        Editar
                    </button>
                    <button onclick="eliminarNovedad(${novedad.id})" style="padding: 0.25rem 0.75rem; background: #ef4444; color: white; border: none; border-radius: 0.25rem; font-size: 0.75rem; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'" title="Eliminar novedad">
                        Eliminar
                    </button>
                </div>
            ` : '';
            
            const textoEditado = novedad.editado ? `<div style="font-size: 0.75rem; color: #b45309; font-style: italic; margin-top: 0.5rem;">Editado por ${novedad.editado_por_nombre || 'Usuario'} el ${novedad.editado_en || ''}</div>` : '';
            
            return `
                <div style="background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem; margin-bottom: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <span style="display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 700; border-radius: 0.25rem; ${tipoColor}">
                                ${novedad.tipo_novedad.toUpperCase()}
                            </span>
                            <span style="font-size: 0.75rem; color: #6b7280;">
                                ${novedad.creado_por_nombre || 'Usuario'}
                                <span style="display: inline-block; background: #e0e7ff; color: #3730a3; padding: 0.15rem 0.4rem; border-radius: 0.2rem; font-weight: 600; font-size: 0.65rem; margin-left: 0.25rem;">
                                    ${novedad.creado_por_rol || 'USUARIO'}
                                </span>
                            </span>
                            ${esEditada}
                        </div>
                        <span style="font-size: 0.75rem; color: #9ca3af;">${novedad.creado_en}</span>
                    </div>
                    <div style="font-size: 0.875rem; color: #374151; white-space: pre-wrap;">${novedad.novedad_texto}</div>
                    ${textoEditado}
                    ${botones}
                </div>
            `;
        }).join('');
    }

    function cerrarModalNovedad() {
        const modal = document.getElementById('modalNovedad');
        if (modal) {
            modal.style.display = 'none';
            const textarea = document.getElementById('novedadDescripcionText');
            if (textarea) textarea.value = '';
        }
    }

    function guardarNovedad() {
        const textareaDescripcion = document.getElementById('novedadDescripcionText');
        
        if (!textareaDescripcion) {
            mostrarError('Error', 'Elementos del formulario no encontrados');
            return;
        }
        
        const descripcion = textareaDescripcion.value.trim();
        const tipoNovedad = 'observacion';

        if (!descripcion) {
            mostrarError('Validación', 'Por favor describe la novedad');
            return;
        }

        // Enviar al servidor
        fetch('/operario/api/novedades/crear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                numero_pedido: window.novedadActual.numeroPedido,
                prenda_id: window.novedadActual.prendaId,
                numero_recibo: document.getElementById('prendaNumeroReciboDisplay').textContent,
                novedad_texto: descripcion,
                tipo_novedad: tipoNovedad
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (textareaDescripcion) textareaDescripcion.value = '';
                
                // Recargar novedades
                cargarNovedadesDelUsuario(window.novedadActual.numeroPedido, window.novedadActual.prendaId);
                mostrarExito('Éxito', 'Novedad guardada correctamente');
            } else {
                mostrarError('Error', (data.message || 'No se pudo guardar la novedad'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error', 'Error al guardar la novedad');
        });
    }

    function editarNovedad(id, texto, tipo) {
        // Guardar datos de la novedad en edición
        window.novedadEnEdicion = { id, texto, tipo };
        
        // Mostrar modal de edición
        const editModal = document.getElementById('modalEditarNovedad');
        if (!editModal) {
            mostrarError('Error', 'Modal de edición no encontrado');
            return;
        }
        
        // Llenar formulario con datos actuales
        const editTextarea = document.getElementById('editarNovedadText');
        const editSelect = document.getElementById('editarTipoSelect');
        
        if (editTextarea) editTextarea.value = texto;
        if (editSelect) editSelect.value = tipo;
        
        editModal.style.display = 'flex';
    }

    function cerrarModalEditarNovedad() {
        const modal = document.getElementById('modalEditarNovedad');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function guardarNovedadEditada() {
        if (!window.novedadEnEdicion) {
            mostrarError('Error', 'Datos de novedad no encontrados');
            return;
        }
        
        const editTextarea = document.getElementById('editarNovedadText');
        const editSelect = document.getElementById('editarTipoSelect');
        
        if (!editTextarea || !editSelect) {
            mostrarError('Error', 'Elementos del formulario no encontrados');
            return;
        }
        
        const nuevoTexto = editTextarea.value.trim();
        const nuevoTipo = editSelect.value;
        
        if (!nuevoTexto) {
            mostrarError('Validación', 'Por favor describe la novedad');
            return;
        }
        
        // Enviar actualización
        fetch(`/operario/api/novedades/${window.novedadEnEdicion.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                novedad_texto: nuevoTexto,
                tipo_novedad: nuevoTipo
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cerrarModalEditarNovedad();
                cargarNovedadesDelUsuario(window.novedadActual.numeroPedido, window.novedadActual.prendaId);
                mostrarExito('Éxito', 'Novedad actualizada correctamente');
            } else {
                mostrarError('Error', (data.message || 'No se pudo actualizar la novedad'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error', 'Error al actualizar la novedad');
        });
    }

    function eliminarNovedad(id) {
        // Modal de confirmación
        const modal = document.getElementById('modalMensaje');
        const contenido = document.getElementById('modalMensajeContenido');
        const iconoEl = document.getElementById('modalMensajeIcono');
        const tituloEl = document.getElementById('modalMensajeTitulo');
        const textoEl = document.getElementById('modalMensajeTexto');
        const boton = document.querySelector('.modal-mensaje-boton');
        
        contenido.classList.remove('modal-mensaje-exito', 'modal-mensaje-error', 'modal-mensaje-info');
        contenido.classList.add('modal-mensaje-info');
        
        iconoEl.textContent = '?';
        tituloEl.textContent = 'Confirmar';
        textoEl.textContent = 'Está seguro de que desea eliminar esta novedad?';
        
        boton.textContent = 'Eliminar';
        boton.style.background = '#ef4444';
        boton.style.color = 'white';
        boton.style.border = 'none';
        boton.style.padding = '0.5rem 1.5rem';
        boton.style.borderRadius = '0.25rem';
        boton.style.fontWeight = '700';
        boton.style.cursor = 'pointer';
        boton.style.transition = 'all 0.3s ease';
        boton.style.fontSize = '0.85rem';
        boton.style.textTransform = 'uppercase';
        boton.onmouseover = function() { this.style.background = '#dc2626'; };
        boton.onmouseout = function() { this.style.background = '#ef4444'; };
        
        boton.onclick = function() {
            fetch(`/operario/api/novedades/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cargarNovedadesDelUsuario(window.novedadActual.numeroPedido, window.novedadActual.prendaId);
                    mostrarExito('Éxito', 'Novedad eliminada correctamente');
                } else {
                    mostrarError('Error', 'Error al eliminar la novedad');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error', 'Error al procesar la eliminación');
            });
        };
        
        modal.style.display = 'flex';
    }

    // ==========================================
    // PASAR A COSTURA - Funciones
    // ==========================================
    
    // Variable global para datos temporales del modal costura
    window.costuraPendiente = null;

    function esAreaCostura(area) {
        const norm = (area || '').toLowerCase().trim();
        return norm === 'costura';
    }

    function manejarPasarACostura(btn) {
        const pedidoId = btn.dataset.pedidoId;
        const prendaId = btn.dataset.prendaId;
        const nombre = btn.dataset.nombre;
        const tipoRecibo = btn.dataset.tipoRecibo;
        const recibo = btn.dataset.recibo;
        const area = btn.dataset.area;
        const procesoId = btn.dataset.procesoId;
        const encargadoCostura = (btn.dataset.encargadoCostura || '').trim();
        const btnId = btn.id;

        if (esAreaCostura(area) && encargadoCostura) {
            // DESHACER COSTURA
            deshacerCostura(pedidoId, prendaId, tipoRecibo, btnId);
            return;
        }

        // Abrir modal para pedir encargado
        abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, btnId);
    }

    function abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, btnId) {
        const modal = document.getElementById('modalCostura');
        if (!modal) return;

        document.getElementById('costuraPrendaNombre').textContent = nombre;
        document.getElementById('costuraReciboNumero').textContent = recibo;
        document.getElementById('costuraEncargado').value = '';

        window.costuraPendiente = {
            pedidoId: pedidoId,
            prendaId: prendaId,
            tipoRecibo: tipoRecibo,
            recibo: recibo,
            btnId: btnId
        };

        modal.style.display = 'flex';
        setTimeout(() => document.getElementById('costuraEncargado').focus(), 100);
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

        const { pedidoId, prendaId, tipoRecibo, recibo, btnId } = window.costuraPendiente;

        // Deshabilitar botón mientras se procesa
        const btnConfirmar = document.querySelector('.btn-confirmar-costura');
        if (btnConfirmar) {
            btnConfirmar.disabled = true;
            btnConfirmar.textContent = 'PROCESANDO...';
        }

        fetch('/recibos-novedades/' + pedidoId + '/' + recibo + '/pasar-a-costura', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                prenda_id: prendaId,
                tipo_recibo: tipoRecibo,
                encargado: encargado
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar dinámicamente el botón a DESHACER COSTURA
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.dataset.area = btn.dataset.area || 'Costura';
                    btn.dataset.procesoId = data.data.proceso_id;
                    btn.classList.add('btn-deshacer-costura');
                    btn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER COSTURA';

                    const dashboard = document.querySelector('.operario-dashboard');
                    if (dashboard && dashboard.classList.contains('is-vista-costura')) {
                        const card = btn.closest('.orden-card-simple');
                        const body = card ? card.querySelector('.orden-body') : null;
                        const badgeArea = body ? body.querySelector('.orden-top-badges .badge-area') : null;
                        const badgeEstado = body ? body.querySelector('.orden-top-badges .badge-completado-corte') : null;

                        if (badgeArea) {
                            badgeArea.textContent = 'COSTURA';
                        }

                        if (badgeEstado) {
                            badgeEstado.textContent = 'PENDIENTE COSTURA';
                            badgeEstado.classList.remove('is-on');
                        }

                        if (body) {
                            body.classList.remove('recibo-completado-area');
                        }
                    }
                }

                cerrarModalCostura();
                mostrarExito('Éxito', 'Recibo enviado a Costura correctamente');
                console.log('âœ“ Prenda enviada a Costura. Encargado: ' + encargado);
            } else {
                mostrarError('Error', data.message || 'Error al pasar a Costura');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error', 'Error al procesar la solicitud');
        })
        .finally(() => {
            if (btnConfirmar) {
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = 'CONFIRMAR';
            }
        });
    }

    function deshacerCostura(pedidoId, prendaId, tipoRecibo, btnId) {
        const btn = document.getElementById(btnId);
        if (!btn || btn.disabled) return;

        // Bloquear botón y mostrar cargando
        btn.disabled = true;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<span class="material-symbols-rounded spin-icon">sync</span> PROCESANDO...';
        btn.style.opacity = '0.6';
        btn.style.pointerEvents = 'none';

        fetch('/recibos-novedades/' + pedidoId + '/' + prendaId + '/limpiar-encargado-costura', {
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
                // Actualizar botón a PASAR A COSTURA (solo se limpió encargado)
                if (btn) {
                    btn.dataset.encargadoCostura = '';
                    btn.classList.remove('btn-deshacer-costura');
                    btn.innerHTML = '<span class="material-symbols-rounded">checkroom</span> PASAR A COSTURA';

                    const dashboard = document.querySelector('.operario-dashboard');
                    if (dashboard && dashboard.classList.contains('is-vista-costura')) {
                        const card = btn.closest('.orden-card-simple');
                        const body = card ? card.querySelector('.orden-body') : null;
                        const badgeArea = body ? body.querySelector('.orden-top-badges .badge-area') : null;
                        const badgeEstado = body ? body.querySelector('.orden-top-badges .badge-completado-corte') : null;
                        const badgeEncargado = body ? body.querySelector('.orden-top-badges .badge-encargado') : null;

                        if (badgeEncargado) {
                            badgeEncargado.textContent = 'SIN ENCARGADO';
                        }

                        if (badgeEstado) {
                            badgeEstado.textContent = 'PENDIENTE COSTURA';
                            badgeEstado.classList.remove('is-on');
                        }

                        if (body) {
                            body.classList.remove('recibo-completado-area');
                        }
                    }
                }

                mostrarExito('Éxito', 'Encargado de Costura eliminado correctamente');
                console.log('âœ“ Encargado de Costura eliminado');
            } else {
                btn.innerHTML = originalHTML;
                mostrarError('Error', data.message || 'Error al deshacer Costura');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error al procesar la solicitud');
        })
        .finally(() => {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.pointerEvents = '';
        });
    }

    // Cerrar modal costura al click fuera
    window.addEventListener('click', function(event) {
        const modalCostura = document.getElementById('modalCostura');
        if (modalCostura && event.target === modalCostura) {
            cerrarModalCostura();
        }
    });

    // ==========================================
    // CONTROL CALIDAD - Funciones
    // ==========================================

    // Función dinámica para pasar recibo a Control Calidad o Deshacer
    function esAreaControlCalidad(area) {
        const norm = (area || '').toLowerCase().trim().replace(/[-_]/g, ' ').replace(/\s+/g, ' ');
        return norm === 'control calidad' || norm === 'control de calidad';
    }

    function pasarAControlCalidad(btn) {
        const pedidoId = btn.dataset.pedidoId;
        const prendaId = btn.dataset.prendaId;
        const nombre = btn.dataset.nombre;
        const tipoRecibo = btn.dataset.tipoRecibo;
        const recibo = btn.dataset.recibo;
        const area = btn.dataset.area;
        const procesoId = btn.dataset.procesoId;
        const btnId = btn.id;

        if (esAreaControlCalidad(area)) {
            // DESHACER
            const btnCC = document.getElementById(btnId);
            if (!btnCC || btnCC.disabled) return;

            // Bloquear botón y mostrar cargando
            btnCC.disabled = true;
            const originalCCHTML = btnCC.innerHTML;
            btnCC.innerHTML = '<span class="material-symbols-rounded spin-icon">sync</span> PROCESANDO...';
            btnCC.style.opacity = '0.6';
            btnCC.style.pointerEvents = 'none';

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
                    // Actualizar dinámicamente el botón
                    const nuevoArea = data.data.area_nueva;
                    btnCC.dataset.area = nuevoArea;
                    btnCC.dataset.procesoId = '';
                    
                    btnCC.innerHTML = '<span class="material-symbols-rounded">check_circle</span> PASAR A C.C';
                    
                    console.log('âœ“ Control Calidad deshecho. Ãrea restaurada a: ' + nuevoArea);
                } else {
                    btnCC.innerHTML = originalCCHTML;
                    console.error('âŒ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btnCC.innerHTML = originalCCHTML;
            })
            .finally(() => {
                btnCC.disabled = false;
                btnCC.style.opacity = '1';
                btnCC.style.pointerEvents = '';
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
                        mostrarExito('Éxito', 'Recibo enviado a Control de Calidad');
                        return;
                    }

                    const btnEl = document.getElementById(btnId);
                    if (!btnEl) {
                        mostrarExito('Éxito', 'Recibo enviado a Control de Calidad');
                        return;
                    }
                    btnEl.dataset.area = 'Control Calidad';
                    btnEl.dataset.procesoId = data.data.proceso_id;
                    
                    const icon = btnEl.querySelector('.material-symbols-rounded');
                    icon.textContent = 'undo';
                    btnEl.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER';
                    
                    console.log('âœ“ Prenda enviada a Control Calidad');
                } else {
                    console.error('âŒ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    }

    // Cerrar modal al hacer click fuera
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('modalNovedad');
        if (modal && event.target === modal) {
            cerrarModalNovedad();
        }
        
        const editModal = document.getElementById('modalEditarNovedad');
        if (editModal && event.target === editModal) {
            cerrarModalEditarNovedad();
        }
    });

</script>

@endsection
