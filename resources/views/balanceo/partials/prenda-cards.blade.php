@forelse($prendas as $prenda)
@php
    // Mostrar indicador rojo SOLO si el usuario hizo click en "INCOMPLETO"
    // null = no marcado (sin indicador)
    // true = completo (sin indicador)
    // false = incompleto marcado manualmente (CON indicador rojo)
    $balanceoIncompleto = $prenda->balanceoActivo && 
                          $prenda->balanceoActivo->estado_completo === false;
@endphp
<div class="prenda-card {{ $balanceoIncompleto ? 'prenda-card--incompleto' : '' }}" 
     onclick="window.location='{{ route('balanceo.show', $prenda->id) }}'">
    
    <!-- Indicador de balanceo incompleto -->
    @if($balanceoIncompleto)
    <div class="prenda-card__alert">
        <span class="material-symbols-rounded">warning</span>
        <span>Balanceo Incompleto</span>
    </div>
    @endif
    
    <!-- Imagen de la prenda -->
    <div class="prenda-card__image">
        @if($prenda->imagen)
        <img src="{{ asset($prenda->imagen) }}" 
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
