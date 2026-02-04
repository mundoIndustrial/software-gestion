<!-- Sidebar Bordado de Pedidos -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('bordado.index') }}" class="logo-wrapper" aria-label="Ir a Gestión de Bordado">
            <img src="{{ asset('images/logo2.png') }}"
                 alt="Logo"
                 class="header-logo"
                 data-logo-light="{{ asset('images/logo2.png') }}"
                 data-logo-dark="https://prueba.mundoindustrial.co/wp-content/uploads/2024/07/logo-mundo-industrial-white.png" />
        </a>
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Colapsar menú">
            <span class="material-symbols-rounded">chevron_left</span>
        </button>
    </div>

    <div class="sidebar-nav">
        <!-- Sección Principal -->
        <div class="nav-section">
            <div class="nav-section-title">Gestión de Bordado</div>
            
            <!-- Pedidos -->
            <a href="{{ route('bordado.index') }}"
               class="nav-item {{ Route::currentRouteName() === 'bordado.index' ? 'active' : '' }}">
                <span class="material-symbols-rounded nav-icon">assignment</span>
                <span class="nav-label">Pedidos</span>
            </a>

            <!-- Cotizaciones - Submenu -->
            <div class="nav-submenu-container">
                <button class="nav-item submenu-toggle" onclick="toggleSubmenu(event, 'cotizacionesSubmenu')" 
                        style="width: 100%; text-align: left; background: none; border: none; cursor: pointer;">
                    <span class="material-symbols-rounded nav-icon">description</span>
                    <span class="nav-label">Cotizaciones</span>
                    <span class="material-symbols-rounded submenu-arrow" style="margin-left: auto; transition: transform 0.3s;">expand_more</span>
                </button>
                
                <!-- Submenú items -->
                <div class="nav-submenu" id="cotizacionesSubmenu" style="display:none;">
                    <a href="{{ route('bordado.cotizaciones.lista') }}"
                       class="nav-submenu-item {{ Route::currentRouteName() === 'bordado.cotizaciones.lista' ? 'active' : '' }}">
                        <span class="material-symbols-rounded" style="font-size: 1.1rem;">list</span>
                        <span>Lista de Cotizaciones</span>
                    </a>
                    <a href="{{ route('bordado.cotizaciones.medidas') }}"
                       class="nav-submenu-item {{ Route::currentRouteName() === 'bordado.cotizaciones.medidas' ? 'active' : '' }}">
                        <span class="material-symbols-rounded" style="font-size: 1.1rem;">straighten</span>
                        <span>Medidas</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</aside>

<script>
    console.log(' [BORDADO-SIDEBAR] Inicializando sidebar...');

    // Función para alternar submenús
    function toggleSubmenu(event, submenuId) {
        console.log('🔄 [TOGGLESUBMENU] Alternando submenú:', submenuId);
        event.preventDefault();
        const submenu = document.getElementById(submenuId);
        const arrow = event.currentTarget.querySelector('.submenu-arrow');
        
        if (!submenu) {
            console.error(' [TOGGLESUBMENU] Submenú no encontrado:', submenuId);
            return;
        }

        console.log(' [TOGGLESUBMENU] Estado actual:', submenu.style.display);
        console.log('[TOGGLESUBMENU] Arrow encontrado:', !!arrow);
        
        if (submenu.style.display === 'none') {
            console.log(' [TOGGLESUBMENU] Abriendo submenú:', submenuId);
            submenu.style.display = 'block';
            if (arrow) {
                arrow.style.transform = 'rotate(180deg)';
                console.log('🔄 [TOGGLESUBMENU] Arrow rotado a 180deg');
            }
        } else {
            console.log(' [TOGGLESUBMENU] Cerrando submenú:', submenuId);
            submenu.style.display = 'none';
            if (arrow) {
                arrow.style.transform = 'rotate(0deg)';
                console.log('🔄 [TOGGLESUBMENU] Arrow rotado a 0deg');
            }
        }
    }

    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    console.log('[SIDEBAR-INIT] SidebarToggle encontrado:', !!sidebarToggle);
    console.log('[SIDEBAR-INIT] Sidebar encontrado:', !!sidebar);

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            const isCollapsed = sidebar.classList.contains('collapsed');
            console.log('🔄 [SIDEBAR-TOGGLE] Toggling sidebar. Estado anterior:', isCollapsed);
            sidebar.classList.toggle('collapsed');
            const newState = sidebar.classList.contains('collapsed');
            console.log(' [SIDEBAR-TOGGLE] Nuevo estado collapsed:', newState);
            
            // Guardar estado
            localStorage.setItem('bordado-sidebar-collapsed', newState);
            console.log(' [SIDEBAR-TOGGLE] Estado guardado en localStorage');
        });

        // Restaurar estado
        const savedState = localStorage.getItem('bordado-sidebar-collapsed');
        console.log('📂 [SIDEBAR-INIT] Estado guardado en localStorage:', savedState);
        
        if (savedState === 'true') {
            sidebar.classList.add('collapsed');
            console.log(' [SIDEBAR-INIT] Sidebar restaurado como collapsed');
        }
    } else {
        console.error(' [SIDEBAR-INIT] No se pudo inicializar sidebar. sidebarToggle:', !!sidebarToggle, 'sidebar:', !!sidebar);
    }

    // Auto-expandir submenú si hay una ruta activa
    document.addEventListener('DOMContentLoaded', function() {
        console.log(' [DOM-LOADED] Verificando rutas activas...');
        
        // Buscar por la nueva estructura
        const activeSubmenuItems = document.querySelectorAll('.nav-submenu .nav-submenu-item.active');
        console.log('🔍 [DOM-LOADED] Items de submenú activos encontrados:', activeSubmenuItems.length);
        
        activeSubmenuItems.forEach((link, index) => {
            console.log(` [DOM-LOADED] Item activo ${index}:`, link.textContent.trim());
            const submenu = link.closest('.nav-submenu');
            if (submenu) {
                console.log(` [DOM-LOADED] Submenú encontrado para item ${index}`);
                submenu.style.display = 'block';
                console.log(` [DOM-LOADED] Abriendo submenú`);
                
                // Buscar el button del toggle
                const button = submenu.previousElementSibling;
                console.log(`[DOM-LOADED] Button encontrado:`, !!button);
                
                if (button) {
                    const arrow = button.querySelector('.submenu-arrow');
                    console.log(`[DOM-LOADED] Arrow en button:`, !!arrow);
                    if (arrow) {
                        arrow.style.transform = 'rotate(180deg)';
                        console.log(` [DOM-LOADED] Arrow rotado a 180deg`);
                    }
                }
            }
        });

        // Log de clases CSS encontradas
        console.log(' [DOM-LOADED] Clases CSS verificadas:');
        console.log('  - .sidebar:', document.querySelectorAll('.sidebar').length);
        console.log('  - .nav-item:', document.querySelectorAll('.nav-item').length);
        console.log('  - .nav-submenu:', document.querySelectorAll('.nav-submenu').length);
        console.log('  - .nav-submenu-item:', document.querySelectorAll('.nav-submenu-item').length);
        console.log('  - .submenu-toggle:', document.querySelectorAll('.submenu-toggle').length);
        console.log('  - .submenu-arrow:', document.querySelectorAll('.submenu-arrow').length);

        // LOG DE ESTILOS APLICADOS
        console.log(' [ESTILOS] Verificando estilos CSS aplicados...');
        
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            const sidebarStyles = window.getComputedStyle(sidebar);
            console.log(' [SIDEBAR-STYLES]');
            console.log('  - width:', sidebarStyles.width);
            console.log('  - background:', sidebarStyles.background);
            console.log('  - display:', sidebarStyles.display);
            console.log('  - flex-direction:', sidebarStyles.flexDirection);
        }

        const navItems = document.querySelectorAll('.nav-item');
        console.log(' [NAV-ITEM-STYLES] Encontrados:', navItems.length);
        if (navItems.length > 0) {
            const navItemStyles = window.getComputedStyle(navItems[0]);
            console.log('  - display:', navItemStyles.display);
            console.log('  - padding:', navItemStyles.padding);
            console.log('  - color:', navItemStyles.color);
            console.log('  - background-color:', navItemStyles.backgroundColor);
            console.log('  - gap:', navItemStyles.gap);
        }

        const navSubmenu = document.querySelector('.nav-submenu');
        if (navSubmenu) {
            const submenuStyles = window.getComputedStyle(navSubmenu);
            console.log(' [NAV-SUBMENU-STYLES]');
            console.log('  - display:', submenuStyles.display);
            console.log('  - background-color:', submenuStyles.backgroundColor);
            console.log('  - flex-direction:', submenuStyles.flexDirection);
            console.log('  - border-left:', submenuStyles.borderLeft);
        }

        const navSubmenuItem = document.querySelector('.nav-submenu-item');
        if (navSubmenuItem) {
            const submenuItemStyles = window.getComputedStyle(navSubmenuItem);
            console.log(' [NAV-SUBMENU-ITEM-STYLES]');
            console.log('  - display:', submenuItemStyles.display);
            console.log('  - padding:', submenuItemStyles.padding);
            console.log('  - color:', submenuItemStyles.color);
            console.log('  - font-size:', submenuItemStyles.fontSize);
        }

        console.log(' [DOM-LOADED] Verificación de estilos completada');
    });

    // Monitoreo de cambios en clases
    console.log('👁️ [SIDEBAR-MONITOR] Configurando observador de cambios...');
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                console.log(' [SIDEBAR-MONITOR] Cambio de clase detectado en:', mutation.target.id || mutation.target.className);
                console.log('  - Nuevas clases:', mutation.target.className);
            }
        });
    });

    if (sidebar) {
        observer.observe(sidebar, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    console.log(' [BORDADO-SIDEBAR] Sidebar script cargado exitosamente');
</script>
