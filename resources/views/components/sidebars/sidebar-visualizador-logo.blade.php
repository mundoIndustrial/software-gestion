<!-- Sidebar Visualizador Logo -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('visualizador-logo.dashboard') }}" class="logo-wrapper" aria-label="Ir al Dashboard">
            <img src="{{ asset('images/logo2.png') }}"
                 alt="Logo"
                 class="header-logo"
                 data-logo-light="{{ asset('images/logo2.png') }}"
                 data-logo-dark="{{ asset('images/logo2.png') }}" />
        </a>
        <!-- Botón chevron para colapsar (visible en desktop, oculto en móvil) -->
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Colapsar menú">
            <span class="material-symbols-rounded">chevron_left</span>
        </button>
    </div>

    <div class="sidebar-content">
        @if(!(Auth::user()->hasRole('diseñador-logos') || Auth::user()->hasRole('bordador')))
            <div class="menu-section">
                <span class="menu-section-title">Principal</span>
                <ul class="menu-list" role="navigation">
                    <li class="menu-item">
                        <a href="{{ route('visualizador-logo.dashboard') }}"
                           class="menu-link {{ request()->routeIs('visualizador-logo.dashboard') || request()->routeIs('visualizador-logo.cotizaciones') ? 'active' : '' }}">
                            <span class="material-symbols-rounded">description</span>
                            <span class="menu-label">Cotizaciones</span>
                        </a>
                    </li>
                </ul>
            </div>
        @endif

        <div class="menu-section">
            <span class="menu-section-title">Pedidos</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('visualizador-logo.pedidos-logo') }}"
                       class="menu-link {{ request()->routeIs('visualizador-logo.pedidos-logo') && request()->query('vista') !== 'todos' ? 'active' : '' }}">
                        <span class="material-symbols-rounded">edit</span>
                        <span class="menu-label">Pedidos Logo</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('visualizador-logo.pedidos-logo', ['vista' => 'todos']) }}"
                       class="menu-link {{ request()->routeIs('visualizador-logo.pedidos-logo') && request()->query('vista') === 'todos' ? 'active' : '' }}">
                        <span class="material-symbols-rounded">list_alt</span>
                        <span class="menu-label">Todos</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('visualizador-logo.logos-confirmados') }}"
                       class="menu-link {{ request()->routeIs('visualizador-logo.logos-confirmados') ? 'active' : '' }}">
                        <span class="material-symbols-rounded">image</span>
                        <span class="menu-label">Logos Confirmados</span>
                        <span id="badge-logos-no-revisados" class="badge" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 800; margin-left: auto; display: none;">0</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-section">
            <span class="menu-section-title">Supervisión</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('visualizador-logo.pedidos-visualizacion') }}"
                       class="menu-link {{ request()->routeIs('visualizador-logo.pedidos-visualizacion') ? 'active' : '' }}">
                        <span class="material-symbols-rounded">visibility</span>
                        <span class="menu-label">Ver Pedidos</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-footer">
        <!-- Puedes agregar información adicional del footer aquí si lo necesitas -->
    </div>
</aside>

<script>
    window.__actualizarBadgeLogos = function(countData) {
        const badge = document.getElementById('badge-logos-no-revisados');
        if (!badge) return;
        
        // countData puede ser un número (retrocompatibilidad) o un objeto {confirmados, devueltos, total}
        const total = typeof countData === 'object' ? (countData.total || 0) : (countData || 0);
        
        if (total > 0) {
            badge.textContent = total;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    };
    
    // Cargar el conteo inicial
    document.addEventListener('DOMContentLoaded', function() {
        fetch('{{ route('visualizador-logo.logos-confirmados.data') }}?per_page=1&tab=confirmados')
            .then(r => r.json())
            .then(json => {
                if (json && json.success === true) {
                    window.__actualizarBadgeLogos(json.conteo_no_revisados || 0);
                }
            });
    });
</script>
