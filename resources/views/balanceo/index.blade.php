@extends('layouts.app')

@push('styles')
<!-- Preload SOLO el CSS crítico de balanceo -->
<link rel="preload" href="{{ asset('css/balanceo.css') }}" as="style">
@endpush

@section('content')
<!-- CSS crítico de balanceo cargado inmediatamente -->
<link rel="stylesheet" href="{{ asset('css/balanceo.css') }}">

<!-- CSS no crítico con lazy loading agresivo -->
<script>
(function(){
    // Cargar tableros.css solo cuando sea necesario
    if(window.requestIdleCallback){
        requestIdleCallback(function(){
            var link=document.createElement('link');
            link.rel='stylesheet';
            link.href='{{ asset('css/tableros.css') }}';
            document.head.appendChild(link);
        });
    }else{
        setTimeout(function(){
            var link=document.createElement('link');
            link.rel='stylesheet';
            link.href='{{ asset('css/tableros.css') }}';
            document.head.appendChild(link);
        },1);
    }
})();
</script>

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
        <form method="GET" action="{{ route('balanceo.index') }}" style="padding: 18px 0;">
            <div style="position: relative;">
                <span class="material-symbols-rounded" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--color-text-placeholder); font-size: 22px;">search</span>
                <input type="text" 
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Buscar por nombre, referencia o tipo de prenda..."
                       style="width: 100%; padding: 12px 16px 12px 48px; border: 1px solid var(--color-border-hr); border-radius: 8px; font-size: 15px; transition: all 0.3s ease; background: var(--color-bg-sidebar); color: var(--color-text-primary);"
                       onfocus="this.style.borderColor='rgba(255, 157, 88, 0.4)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                       onblur="this.style.borderColor='var(--color-border-hr)'; this.style.boxShadow='none'"
                       onchange="this.form.submit()">
                @if(request('search'))
                <button type="button" 
                        onclick="window.location='{{ route('balanceo.index') }}'"
                        style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--color-text-placeholder); cursor: pointer; padding: 4px;">
                    <span class="material-symbols-rounded" style="font-size: 20px;">close</span>
                </button>
                @endif
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
    <div class="prendas-grid">
        @forelse($prendas as $prenda)
        <div class="prenda-card" onclick="window.location='{{ route('balanceo.show', $prenda->id) }}'">
            
            <!-- Imagen de la prenda -->
            <div class="prenda-card__image">
                @if($prenda->imagen)
                <img data-src="{{ asset($prenda->imagen) }}" 
                     src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 180'%3E%3Crect fill='%23f0f0f0' width='300' height='180'/%3E%3C/svg%3E"
                     alt="{{ $prenda->nombre }}"
                     loading="lazy"
                     decoding="async"
                     width="300"
                     height="180"
                     class="lazy-image">
                @else
                <div class="prenda-card__image-placeholder">
                    <span class="material-symbols-rounded icon-placeholder">checkroom</span>
                </div>
                @endif
                
                <!-- Badge del tipo -->
                <div class="prenda-card__badge">
                    {{ $prenda->tipo }}
                </div>
            </div>

            <!-- Contenido de la tarjeta -->
            <div class="prenda-card__content">
                <h3 class="prenda-card__title">{{ $prenda->nombre }}</h3>
                
                @if($prenda->referencia)
                <p class="prenda-card__reference">
                    <strong>Ref:</strong> {{ $prenda->referencia }}
                </p>
                @endif

                @if($prenda->descripcion)
                <p class="prenda-card__description">
                    {{ Str::limit($prenda->descripcion, 100) }}
                </p>
                @endif

                <!-- Información del balanceo -->
                @if($prenda->balanceoActivo)
                <div class="prenda-card__metrics">
                    <div>
                        <p class="metric-label">Operaciones</p>
                        <p class="metric-value">
                            {{ $prenda->balanceoActivo->operaciones_count }}
                        </p>
                    </div>
                    <div>
                        <p class="metric-label">SAM Total</p>
                        <p class="metric-value">
                            {{ number_format($prenda->balanceoActivo->sam_total, 1) }}s
                        </p>
                    </div>
                    <div>
                        <p class="metric-label">Operarios</p>
                        <p class="metric-value">
                            {{ $prenda->balanceoActivo->total_operarios }}
                        </p>
                    </div>
                    <div>
                        <p class="metric-label">Meta Real</p>
                        <p class="metric-value">
                            {{ $prenda->balanceoActivo->meta_real ?? 'N/A' }}
                        </p>
                    </div>
                </div>
                @else
                <div class="prenda-card__no-balanceo">
                    <p>Sin balanceo configurado</p>
                </div>
                @endif

                <!-- Botón de acción -->
                <button class="prenda-card__button">
                    <span class="material-symbols-rounded">visibility</span>
                    Ver Balanceo
                </button>
            </div>
        </div>
        @empty
        <div class="empty-state">
            <span class="material-symbols-rounded empty-state__icon">checkroom</span>
            <h3 class="empty-state__title">No hay prendas registradas</h3>
            <p class="empty-state__description">Comienza creando tu primera prenda para gestionar su balanceo</p>
            <a href="{{ route('balanceo.prenda.create') }}" class="empty-state__button">
                <span class="material-symbols-rounded">add</span>
                Nueva Prenda
            </a>
        </div>
        @endforelse
    </div>

    <!-- Paginación -->
    @if($prendas->hasPages())
    <div class="table-pagination" style="margin-top: 40px;">
        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ ($prendas->currentPage() / $prendas->lastPage()) * 100 }}%"></div>
        </div>
        <div class="pagination-info">
            <span>Mostrando {{ $prendas->firstItem() }}-{{ $prendas->lastItem() }} de {{ $prendas->total() }} prendas</span>
        </div>
        <div class="pagination-controls">
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

<!-- Intersection Observer para lazy loading de imágenes -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lazy loading de imágenes con Intersection Observer
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.getAttribute('data-src');
                    if (src) {
                        img.src = src;
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });

        // Observar todas las imágenes lazy
        document.querySelectorAll('img.lazy-image').forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback para navegadores sin IntersectionObserver
        document.querySelectorAll('img.lazy-image').forEach(img => {
            const src = img.getAttribute('data-src');
            if (src) img.src = src;
        });
    }

    // Fade in de cards cuando entran en viewport
    if ('IntersectionObserver' in window) {
        const cardObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '0';
                    entry.target.style.transform = 'translateY(20px)';
                    entry.target.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, 50);
                    
                    cardObserver.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '0px',
            threshold: 0.1
        });

        document.querySelectorAll('.prenda-card').forEach((card, index) => {
            card.style.opacity = '0';
            setTimeout(() => {
                cardObserver.observe(card);
            }, index * 50);
        });
    }
});
</script>

@endsection
