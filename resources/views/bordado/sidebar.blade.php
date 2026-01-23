<aside class="sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <span class="material-symbols-rounded">thread_needle</span>
            <span class="brand-text">Bordado</span>
        </div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <span class="material-symbols-rounded">chevron_right</span>
        </button>
    </div>

    <!-- Sidebar Content -->
    <nav class="sidebar-nav">
        <div class="nav-section">
            <!-- Cotizaciones -->
            <a href="{{ route('bordado.cotizaciones') }}" 
               class="nav-item {{ Route::currentRouteName() === 'bordado.cotizaciones' ? 'active' : '' }}">
                <span class="nav-icon material-symbols-rounded">assignment</span>
                <span class="nav-label">Cotizaciones</span>
            </a>

            <!-- Pedidos -->
            <a href="{{ route('bordado.index') }}" 
               class="nav-item {{ Route::currentRouteName() === 'bordado.index' ? 'active' : '' }}">
                <span class="nav-icon material-symbols-rounded">orders</span>
                <span class="nav-label">Pedidos</span>
            </a>
        </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">{{ substr(auth()->user()->name, 0, 1) }}</div>
            <div class="user-info">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">Bordado</div>
            </div>
        </div>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('collapsed');
        
        // Guardar estado en localStorage
        localStorage.setItem('bordado-sidebar-collapsed', sidebar.classList.contains('collapsed'));
    }

    // Restaurar estado del sidebar al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const isCollapsed = localStorage.getItem('bordado-sidebar-collapsed') === 'true';
        const sidebar = document.querySelector('.sidebar');
        
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }
    });
</script>
