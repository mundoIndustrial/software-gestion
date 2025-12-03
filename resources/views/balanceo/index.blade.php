@extends('layouts.app')

@push('styles')
<!-- Optimizaciones SOLO para módulo balanceo -->
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="preload" href="{{ asset('css/balanceo.css') }}" as="style">
@endpush

@section('content')
<!-- CSS de balanceo -->
<link rel="stylesheet" href="{{ asset('css/balanceo.css') }}">
<link rel="stylesheet" href="{{ asset('css/tableros.css') }}">
<link rel="stylesheet" href="{{ asset('css/orders styles/modern-table.css') }}">

<!-- CSS Crítico Inline SOLO para balanceo -->
<style>
    /* Estilos críticos para primera pintura de balanceo */
    .prendas-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}
    .prenda-card{background:#fff;border-radius:12px;border:1px solid #e5e7eb;cursor:pointer;overflow:hidden;transition:all .3s}
    .prenda-card__image{height:180px;background:#ffffff;position:relative;display:flex;align-items:center;justify-content:center}
    .skeleton{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:loading 1.5s infinite}
    @keyframes loading{0%{background-position:200% 0}100%{background-position:-200% 0}}
    
    /* Dark theme - tarjeta oscura pero imagen BLANCA */
    html[data-theme="dark"] .prenda-card{background:#1e293b;border-color:#334155}
    html[data-theme="dark"] .prenda-card__image{background:#ffffff}
</style>

<div class="tableros-container">
    <div class="page-header" style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1 class="tableros-title" style="margin: 0;">
                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 10px;">schedule</span>
                    Balanceo de Líneas
                </h1>
                <p class="page-subtitle" style="font-size: 16px; margin-top: 10px;">
                    Gestión de prendas y balanceo de operaciones
                </p>
            </div>
            <a href="{{ route('balanceo.prenda.create') }}" 
               style="background: #ff9d58; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; text-decoration: none; font-weight: 500; box-shadow: 0 2px 4px rgba(255, 157, 88, 0.3); transition: background 0.2s;"
               onmouseover="this.style.background='#e88a47'" onmouseout="this.style.background='#ff9d58'">
                <span class="material-symbols-rounded">add</span>
                Nueva Prenda
            </a>
        </div>

        <!-- Buscador -->
        <form method="GET" action="{{ route('balanceo.index') }}" id="searchForm" style="padding: 18px 0;">
            <div style="position: relative;">
                <span class="material-symbols-rounded" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--color-text-placeholder); font-size: 22px;">search</span>
                <input type="text" 
                       id="searchInput"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Buscar por nombre, referencia o tipo de prenda..."
                       style="width: 100%; padding: 12px 16px 12px 48px; border: 1px solid var(--color-border-hr); border-radius: 8px; font-size: 15px; transition: all 0.3s ease; background: var(--color-bg-sidebar); color: var(--color-text-primary);"
                       onfocus="this.style.borderColor='rgba(255, 157, 88, 0.4)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                       onblur="this.style.borderColor='var(--color-border-hr)'; this.style.boxShadow='none'">
                <button type="button" 
                        id="clearSearchBtn"
                        onclick="clearSearch()"
                        style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--color-text-placeholder); cursor: pointer; padding: 4px; display: {{ request('search') ? 'block' : 'none' }};">
                    <span class="material-symbols-rounded" style="font-size: 20px;">close</span>
                </button>
            </div>
        </form>
    </div>

    @if(session('success'))
    <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <span class="material-symbols-rounded">check_circle</span>
        {{ session('success') }}
    </div>
    @endif

    <!-- Grid de prendas -->
    <div class="prendas-grid" id="prendasGrid">
        @include('balanceo.partials.prenda-cards')
    </div>

    <!-- Paginación -->
    @if($prendas->hasPages())
    <div class="table-pagination" id="pagination-balanceo" style="margin-top: 40px;">
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: {{ ($prendas->currentPage() / $prendas->lastPage()) * 100 }}%"></div>
        </div>
        <div class="pagination-info">
            <span id="paginationInfo">Mostrando {{ $prendas->firstItem() }}-{{ $prendas->lastItem() }} de {{ $prendas->total() }} prendas</span>
        </div>
        <div class="pagination-controls" id="paginationControls">
            {{ $prendas->appends(request()->query())->links('vendor.pagination.custom') }}
        </div>
    </div>
    @endif
</div>

<style>
.prenda-card:hover {
    transform: translateY(-5px);
    border-color: #ff9d58 !important;
    box-shadow: 0 8px 16px rgba(255, 157, 88, 0.25) !important;
}

/* Tarjeta con balanceo incompleto */
.prenda-card--incompleto {
    border: 2px solid #ef4444 !important;
    background: linear-gradient(to bottom, rgba(239, 68, 68, 0.05), transparent) !important;
}

.prenda-card--incompleto:hover {
    border-color: #dc2626 !important;
    box-shadow: 0 8px 16px rgba(239, 68, 68, 0.3) !important;
}

/* Alerta de balanceo incompleto */
.prenda-card__alert {
    position: absolute;
    top: 12px;
    left: 12px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    animation: pulse-alert 2s infinite;
}

.prenda-card__alert .material-symbols-rounded {
    font-size: 16px;
}

@keyframes pulse-alert {
    0%, 100% {
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    }
    50% {
        box-shadow: 0 2px 12px rgba(239, 68, 68, 0.6);
    }
}

.page-subtitle {
    color: var(--color-text-placeholder);
    font-size: 16px;
    margin-top: 10px;
}

/* Estilos de paginación (heredados de tableros.css) */
.table-pagination {
    background: #1e293b;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.progress-bar {
    background: #334155;
    height: 6px;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 1.25rem;
}

.progress-fill {
    background: linear-gradient(90deg, #f97316 0%, #fb923c 100%);
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.pagination-info {
    color: #94a3b8;
    font-size: 14px;
    margin-bottom: 1.25rem;
}

.pagination-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

/* Estilos de paginación mejorados */
.pagination-controls .pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
}

.pagination-controls .pagination button,
.pagination-controls .pagination a {
    background: #334155;
    color: #cbd5e1;
    border: none;
    padding: 10px 16px;
    min-width: 44px;
    height: 44px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.pagination-controls .pagination a:hover:not(:disabled),
.pagination-controls .pagination button:hover:not(:disabled) {
    background: #475569;
    transform: translateY(-1px);
}

.pagination-controls .pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-controls .pagination button.active,
.pagination-controls .pagination a.active {
    background: linear-gradient(135deg, #f97316 0%, #fb923c 100%) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
}

.pagination-controls .pagination .nav-btn {
    padding: 10px 20px;
    min-width: auto;
}

.pagination-controls .pagination .dots {
    color: #64748b;
    padding: 0 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Responsive */
@media (max-width: 768px) {
    .pagination-controls .pagination {
        gap: 4px;
    }

    .pagination-controls .pagination button,
    .pagination-controls .pagination a {
        padding: 8px 12px;
        min-width: 40px;
        height: 40px;
        font-size: 13px;
    }

    .pagination-controls .pagination .nav-btn {
        padding: 8px 16px;
    }
}
</style>

<!-- Optimizaciones SOLO para módulo balanceo -->
<script>
// Lazy loading nativo de imágenes ya está habilitado con loading="lazy"
// Fade in suave de cards (opcional - solo para balanceo)
document.addEventListener('DOMContentLoaded', function() {
    if ('IntersectionObserver' in window) {
        const cardObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    cardObserver.unobserve(entry.target);
                }
            });
        }, { rootMargin: '50px', threshold: 0.1 });

        document.querySelectorAll('.prenda-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            setTimeout(() => cardObserver.observe(card), index * 30);
        });
    }

    // Búsqueda AJAX en tiempo real con debounce
    const searchInput = document.getElementById('searchInput');
    const prendasGrid = document.getElementById('prendasGrid');
    const paginationContainer = document.getElementById('pagination-balanceo');
    let searchTimeout;
    let isSearching = false;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Mostrar/ocultar botón de limpiar
            const clearBtn = document.getElementById('clearSearchBtn');
            if (clearBtn) {
                clearBtn.style.display = this.value.length > 0 ? 'block' : 'none';
            }
            
            // Limpiar timeout anterior
            clearTimeout(searchTimeout);
            
            // Esperar 300ms después de que el usuario deje de escribir
            searchTimeout = setTimeout(function() {
                performAjaxSearch(searchInput.value);
            }, 300);
        });
    }

    function clearSearch() {
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearSearchBtn');
        
        if (searchInput) {
            searchInput.value = '';
            clearBtn.style.display = 'none';
            performAjaxSearch('');
        }
    }

    function performAjaxSearch(searchTerm) {
        if (isSearching) return;
        isSearching = true;

        // Mostrar indicador de carga
        if (prendasGrid) {
            prendasGrid.style.opacity = '0.6';
            prendasGrid.style.pointerEvents = 'none';
        }

        // Hacer petición AJAX
        fetch(`{{ route('balanceo.index') }}?search=${encodeURIComponent(searchTerm)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Actualizar grid de prendas
            if (prendasGrid && data.cards_html) {
                prendasGrid.innerHTML = data.cards_html;
                
                // Re-aplicar observador de intersección a las nuevas tarjetas
                if ('IntersectionObserver' in window) {
                    const cardObserver = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.style.opacity = '1';
                                entry.target.style.transform = 'translateY(0)';
                                cardObserver.unobserve(entry.target);
                            }
                        });
                    }, { rootMargin: '50px', threshold: 0.1 });

                    document.querySelectorAll('.prenda-card').forEach((card, index) => {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                        setTimeout(() => cardObserver.observe(card), index * 30);
                    });
                }
            }

            // Actualizar paginación
            if (paginationContainer && data.pagination) {
                const paginationInfo = document.getElementById('paginationInfo');
                const paginationControls = document.getElementById('paginationControls');
                const progressFill = document.getElementById('progressFill');

                if (paginationInfo) {
                    paginationInfo.textContent = `Mostrando ${data.pagination.first_item || 0}-${data.pagination.last_item || 0} de ${data.pagination.total} prendas`;
                }

                if (paginationControls) {
                    paginationControls.innerHTML = data.pagination.links_html;
                }

                if (progressFill) {
                    const progress = (data.pagination.current_page / data.pagination.last_page) * 100;
                    progressFill.style.width = progress + '%';
                }
            }

            // Restaurar estado visual
            if (prendasGrid) {
                prendasGrid.style.opacity = '1';
                prendasGrid.style.pointerEvents = 'auto';
            }

            isSearching = false;
        })
        .catch(error => {
            console.error('Error en búsqueda:', error);
            if (prendasGrid) {
                prendasGrid.style.opacity = '1';
                prendasGrid.style.pointerEvents = 'auto';
            }
            isSearching = false;
        });
    }
});
</script>

<!-- Script de paginación AJAX optimizada -->
<script src="{{ asset('js/balanceo-pagination.js') }}"></script>

@endsection
