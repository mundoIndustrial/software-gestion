<!-- Header de Recibos de Costura -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Recibos de Costura</h1>
        <p class="text-muted mb-0">Listado de recibos de costura por número de recibo</p>
    </div>
    
    <!-- Barra de búsqueda con botón de limpiar filtros -->
    <div class="nav-search-wrapper">
        <span class="material-symbols-rounded search-icon" aria-hidden="true">search</span>
        <input type="text" id="navSearchInput" class="nav-search-input" placeholder="Buscar recibos por número o cliente..." autocomplete="off" aria-label="Búsqueda de recibos">
        <button class="nav-search-clear" id="navSearchClear" style="display: none;" aria-label="Limpiar búsqueda">
            <span class="material-symbols-rounded" aria-hidden="true">close</span>
        </button>
        <button class="nav-search-filter" id="navSearchFilter" onclick="clearAllFilters()" title="Limpiar filtros" aria-label="Limpiar filtros">
            <span class="material-symbols-rounded" style="font-size: 18px;">filter_alt_off</span>
        </button>
    </div>
</div>

<style>
.nav-search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 12px;
    transition: all 0.3s ease;
    min-width: 300px;
    max-width: 400px;
}

.nav-search-wrapper:focus-within {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-icon {
    color: #6b7280;
    margin-right: 8px;
    font-size: 20px;
}

.nav-search-input {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    font-size: 14px;
    color: #374151;
    padding: 4px 0;
}

.nav-search-input::placeholder {
    color: #9ca3af;
}

.nav-search-clear,
.nav-search-filter {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-search-clear:hover,
.nav-search-filter:hover {
    background: #e5e7eb;
    color: #374151;
}

.nav-search-filter {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    margin-left: 8px;
}

.nav-search-filter:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: white;
}

.nav-search-clear span,
.nav-search-filter span {
    font-size: 18px;
}

@media (max-width: 768px) {
    .nav-search-wrapper {
        min-width: 250px;
        max-width: 100%;
    }
    
    .nav-search-wrapper span {
        font-size: 18px;
    }
    
    .nav-search-input {
        font-size: 13px;
    }
}
</style>

<script>
// Función para limpiar todos los filtros (disponible globalmente)
function clearAllFilters() {
    console.log('[Filtros] Limpiando todos los filtros');
    
    // Limpiar filtros activos
    window.activeFilters = {};
    
    // Mostrar todas las filas
    const tbody = document.getElementById('tablaRecibosBody');
    if (tbody) {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
            row.style.display = '';
        });
        console.log(`[Filtros] Mostrando todas las ${rows.length} filas`);
    }
    
    // Limpiar checkboxes del modal (si está abierto)
    const checkboxes = document.querySelectorAll('#filterOptions input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = false);
    
    // Mostrar notificación
    showFilterNotification('Todos los filtros han sido limpiados');
}

// Función para mostrar notificación de filtros
function showFilterNotification(message) {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = 'filter-notification';
    notification.textContent = message;
    
    // Estilos
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        z-index: 10000;
        font-weight: 500;
        font-size: 14px;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Mostrar notificación
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Ocultar y eliminar después de 3 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Funcionalidad de búsqueda
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('navSearchInput');
    const clearBtn = document.getElementById('navSearchClear');
    const filterBtn = document.getElementById('navSearchFilter');
    
    if (searchInput) {
        // Mostrar botón de limpiar cuando hay texto
        searchInput.addEventListener('input', function() {
            if (this.value.trim()) {
                clearBtn.style.display = 'flex';
            } else {
                clearBtn.style.display = 'none';
            }
        });
        
        // Limpiar búsqueda
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchInput.focus();
            clearBtn.style.display = 'none';
            
            // Disparar evento de búsqueda
            const event = new Event('input', { bubbles: true });
            searchInput.dispatchEvent(event);
        });
        
        // Búsqueda en tiempo real
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value);
            }, 300);
        });
    }
    
    // Función de búsqueda
    function performSearch(searchTerm) {
        const rows = document.querySelectorAll('#tablaRecibosBody tr');
        const lowerSearchTerm = searchTerm.toLowerCase().trim();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(lowerSearchTerm) || !lowerSearchTerm) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        console.log(`[Búsqueda] Buscando: "${searchTerm}" - Filas visibles: ${Array.from(rows).filter(r => r.style.display !== 'none').length}`);
    }
});
</script>
