<header class="top-nav">
    <div class="nav-left">
        <button class="mobile-toggle" id="mobileToggle">
            <span class="material-symbols-rounded">menu</span>
        </button>
        <div class="breadcrumb-section">
            @if(request()->is('recibos-costura'))
                <h1 class="page-title">Recibos de Costura</h1>
            @elseif(request()->is('entregas-completas*'))
                <h1 class="page-title">Seguimiento Entregas Despacho</h1>
            @else
                <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
            @endif
        </div>

        @php
            $currentRoute = Route::currentRouteName();
            $currentPath = request()->path();
            $isCotizacionesPendientes = $currentRoute === 'cotizaciones.pendientes';
            $isRecibosCostura = $currentPath === 'recibos-costura';
            $searchInputId = $isCotizacionesPendientes ? 'searchInput' : 'navSearchInput';
            $searchPlaceholder = $isRecibosCostura ? 'Buscar recibos por número o cliente...' : ($isCotizacionesPendientes ? 'Buscar por número, cliente o asesora...' : 'Buscar por número o cliente...');
            $searchAriaLabel = $isRecibosCostura ? 'Búsqueda de recibos' : ($isCotizacionesPendientes ? 'Búsqueda de cotizaciones' : 'Búsqueda de órdenes');
        @endphp
        @if($currentRoute === 'registros.index' || $currentRoute === 'bodega.index' || $currentRoute === 'cotizaciones.pendientes' || $isRecibosCostura)
        <div class="nav-search-container">
            <div class="nav-search-wrapper">
                <span class="material-symbols-rounded search-icon" aria-hidden="true">search</span>
                <input 
                    type="text" 
                    id="{{ $searchInputId }}" 
                    class="nav-search-input" 
                    placeholder="{{ $searchPlaceholder }}"
                    autocomplete="off"
                    aria-label="{{ $searchAriaLabel }}"
                >
                <button class="nav-search-clear" id="navSearchClear" style="display: none;" aria-label="Limpiar búsqueda">
                    <span class="material-symbols-rounded" aria-hidden="true">close</span>
                </button>
            </div>
            @if(Route::currentRouteName() !== 'cotizaciones.pendientes')
            <div class="nav-search-results" id="navSearchResults" style="display: none;" role="region" aria-live="polite" aria-label="Resultados de búsqueda"></div>
            @endif
        </div>
        @endif
    </div>

    <div class="nav-right">
        <!-- Campana de Costura -->
        @if(request()->is('recibos-costura'))
        <div class="costura-notification-container">
            <button id="costuraBellBtn" class="costura-bell-btn" title="Recibos en ejecución - Área Corte" aria-label="Notificaciones de costura">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span id="costuraBadge" class="costura-badge">0</span>
            </button>
            <div id="costuraDropdown" class="costura-dropdown">
                <div class="costura-dropdown-header">
                    <span>Área Corte - En Ejecución</span>
                    <button id="costuraClearBtn" class="costura-dropdown-clear">✕</button>
                </div>
                <div id="costuraNotifList" class="costura-notif-list">
                    <div class="costura-notif-empty">Cargando...</div>
                </div>
            </div>
        </div>
        @endif

        @if(Route::currentRouteName() === 'cotizaciones.pendientes' || request()->is('recibos-costura'))
        <button 
            id="btnLimpiarFiltros"
            onclick="limpiarTodosFiltros()"
            style="
                padding: 8px 16px;
                background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
                color: white;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.875rem;
                transition: all 0.3s ease;
                opacity: 0;
                visibility: hidden;
                transform: scale(0);
                white-space: nowrap;
                margin-right: 12px;
            "
            onmouseover="if(this.style.opacity === '1') { this.style.transform='scale(1) translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(249, 115, 22, 0.3)'; }"
            onmouseout="if(this.style.opacity === '1') { this.style.transform='scale(1)'; this.style.boxShadow='none'; }"
        >
            <i class="fas fa-redo" style="margin-right: 6px;"></i>Limpiar Filtros
        </button>
        @endif

        {{-- Campana de notificaciones para Bodega y Despacho --}}
        @if(request()->is('gestion-bodega/*') || request()->is('despacho') || request()->is('despacho/*'))
        <div class="bodega-notification-container" style="position:relative; margin-right:12px;">
            <button id="bodegaBellBtn" class="bodega-bell-btn" title="Notificaciones" aria-label="Notificaciones de bodega" style="
                background:none; border:none; cursor:pointer; position:relative; padding:8px;
                color:#475569; transition:color 0.2s;">
                <span class="material-symbols-rounded" style="font-size:24px;">notifications</span>
                <span id="bodegaBellBadge" style="
                    display:none; position:absolute; top:2px; right:2px;
                    background:#ef4444; color:#fff; font-size:0.65rem; font-weight:700;
                    min-width:18px; height:18px; border-radius:9px; 
                    display:flex; align-items:center; justify-content:center;
                    padding:0 4px; line-height:1;">0</span>
            </button>
            <div id="bodegaBellDropdown" style="
                display:none; position:absolute; top:calc(100% + 8px); right:0;
                width:400px; max-height:500px; background:#fff;
                border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.15);
                z-index:9999; overflow:hidden; border:1px solid #e2e8f0;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding:0.8rem 1rem; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
                    <h3 style="margin:0; font-size:0.95rem; font-weight:600; color:#1e293b;">Notificaciones</h3>
                    <button id="bodegaMarkAllBtn" style="background:none; border:none; cursor:pointer; font-size:0.8rem; color:#2563eb; font-weight:500;">Marcar todas</button>
                </div>
                <div id="bodegaBellList" style="overflow-y:auto; max-height:420px;">
                    <div style="padding:2rem; text-align:center; color:#94a3b8;">
                        <span class="material-symbols-rounded" style="font-size:2rem; display:block; margin-bottom:0.5rem;">notifications_off</span>
                        <p style="margin:0;">Sin notificaciones</p>
                    </div>
                </div>
            </div>
        </div>
        <script>
        (function() {
            let bodegaTabActiva = 'novedades';
            const rutaBase = window.location.pathname.startsWith('/despacho') ? '/despacho' : '/gestion-bodega';

            const bellBtn = document.getElementById('bodegaBellBtn');
            const dropdown = document.getElementById('bodegaBellDropdown');
            const markAllBtn = document.getElementById('bodegaMarkAllBtn');

            if (!bellBtn) return;

            bellBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isOpen = dropdown.style.display === 'block';
                dropdown.style.display = isOpen ? 'none' : 'block';
                if (!isOpen) cargarNotificacionesBodega();
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.bodega-notification-container')) {
                    dropdown.style.display = 'none';
                }
            });

            markAllBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                fetch(rutaBase + '/notificaciones/marcar-todas-leidas', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(data => { if (data.success) cargarNotificacionesBodega(); })
                .catch(err => console.error('Error marcar todas:', err));
            });

            function cargarNotificacionesBodega() {
                console.log('[BODEGA NOTIF] Iniciando carga de notificaciones desde:', rutaBase + '/notificaciones');
                
                fetch(rutaBase + '/notificaciones', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'credentials': 'include'
                    }
                })
                    .then(r => {
                        console.log('[BODEGA NOTIF] Response status:', r.status);
                        return r.json().then(data => ({status: r.status, data}));
                    })
                    .then(({status, data}) => {
                        console.log('[BODEGA NOTIF] Respuesta recibida:', data);
                        console.log('[BODEGA NOTIF] data.notificaciones:', data.notificaciones?.length || 0, 'items');
                        console.log('[BODEGA NOTIF] data.novedades:', data.novedades?.length || 0, 'items');
                        
                        if (status === 401) {
                            console.warn('[BODEGA NOTIF] No autenticado (401)');
                            document.getElementById('bodegaBellList').innerHTML = `
                                <div style="padding:1rem; text-align:center; color:#e74c3c;">
                                    <p>Sesión expirada. Por favor, recarga la página.</p>
                                </div>`;
                            return;
                        }
                        
                        if (!data.success) {
                            console.warn('[BODEGA NOTIF] API respondió con success: false', data);
                            return;
                        }

                        const badge = document.getElementById('bodegaBellBadge');
                        const list = document.getElementById('bodegaBellList');

                        badge.textContent = data.totalGeneral;
                        badge.style.display = data.totalGeneral > 0 ? 'flex' : 'none';

                        let html = ``;

                        // Renderizar TODAS las novedades (incluye anuladas, pedidos creados, etc.)
                        const todasLasNovedades = data.novedades || [];
                        
                        html += `<div class="bodega-tab-content" data-content="novedades" style="display:block; max-height:350px; overflow-y:auto;">`;
                        
                        if (todasLasNovedades.length > 0) {
                            html += todasLasNovedades.map(nov => `
                                <div style="padding:0.7rem 1rem; border-bottom:1px solid #f0f0f0; ${nov.visto ? 'opacity:0.55;' : ''}">
                                    <div style="display:flex; gap:0.6rem; align-items:start;">
                                        <label style="display:flex; align-items:center; cursor:pointer; margin-top:2px; flex-shrink:0;" onclick="event.stopPropagation()">
                                            <input type="checkbox" class="bodega-news-check" data-news-id="${nov.id}" data-source="${nov.source || 'news'}" ${nov.visto ? 'checked' : ''}
                                                style="width:16px; height:16px; accent-color:#10b981; cursor:pointer;">
                                        </label>
                                        <span class="material-symbols-rounded" style="color:${nov.color}; font-size:1.3rem; margin-top:2px; flex-shrink:0;">${nov.icono}</span>
                                        <div style="flex:1; min-width:0;">
                                            <p style="margin:0 0 0.2rem 0; font-size:0.83rem; color:#2c3e50; line-height:1.3; word-break:break-word;">${nov.descripcion}</p>
                                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                                ${nov.pedido ? `<small style="color:#2563eb; font-weight:600;">Orden #${nov.pedido}</small>` : ''}
                                                <small style="color:#999;">${nov.fecha}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('');
                        } else {
                            console.warn('[BODEGA NOTIF] Sin novedades para mostrar');
                            html += `<div style="padding:2rem; text-align:center; color:#7f8c8d;">
                                <span class="material-symbols-rounded" style="font-size:2rem; display:block; margin-bottom:0.5rem;">notifications_off</span>
                                <p>Sin novedades recientes</p>
                            </div>`;
                        }
                        html += `</div>`;

                        list.innerHTML = html;
                        console.log('[BODEGA NOTIF] HTML renderizado. Total items: ' + todasLasNovedades.length);

                        // Checkbox novedades
                        list.querySelectorAll('.bodega-news-check').forEach(chk => {
                            chk.addEventListener('change', function(e) {
                                e.stopPropagation();
                                const source = this.dataset.source;
                                let url;
                                if (source === 'anulada') {
                                    const pedidoId = String(this.dataset.newsId).replace('anulada_', '');
                                    url = `${rutaBase}/notificaciones/pedido/${pedidoId}/toggle-visto`;
                                } else {
                                    url = `${rutaBase}/notificaciones/news/${this.dataset.newsId}/toggle-visto`;
                                }
                                toggleVistoBodega(this, url);
                            });
                        });
                    })
                    .catch(err => {
                        console.error('[BODEGA NOTIF] Error en fetch:', err);
                        document.getElementById('bodegaBellList').innerHTML = `
                            <div style="padding:1rem; text-align:center; color:#e74c3c;">
                                <p>Error al cargar notificaciones</p>
                                <small style="color:#999; display:block; margin-top:0.5rem;">Ver consola (F12) para detalles</small>
                            </div>`;
                    });
            }

            // Auto-load on page and refresh every 30s
            document.addEventListener('DOMContentLoaded', function() {
                cargarNotificacionesBodega();
                setInterval(cargarNotificacionesBodega, 30000);
            });
        })();
        </script>
        @endif

        <div class="user-dropdown">
            <button class="user-btn" id="userBtn" aria-label="Menú de usuario" aria-expanded="false" aria-controls="userMenu">
                <div class="user-avatar">
                    @if(Auth::user()->avatar)
                        <img src="{{ route('storage.serve', ['path' => 'avatars/' . Auth::user()->avatar]) }}" alt="Avatar de {{ Auth::user()->name }}">
                    @else
                        <div class="avatar-placeholder" aria-label="Avatar">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="user-info">
                    <span class="user-name">{{ Auth::user()->name }}</span>
                    <span class="user-role">{{ Auth::user()->role->name ?? 'Usuario' }}</span>
                </div>
            </button>
            <div class="user-menu" id="userMenu" role="region" aria-label="Menú de usuario">
                <div class="user-menu-header">
                    <div class="user-avatar-large">
                        @if(Auth::user()->avatar)
                            <img src="{{ route('storage.serve', ['path' => 'avatars/' . Auth::user()->avatar]) }}" alt="{{ Auth::user()->name }}">
                        @else
                            <div class="avatar-placeholder">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <p class="user-menu-name">{{ Auth::user()->name }}</p>
                        <p class="user-menu-email">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <div class="menu-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="menu-item logout">
                        <span class="material-symbols-rounded">logout</span>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
