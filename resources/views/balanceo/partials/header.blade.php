<div class="prenda-header-container">
    <div class="prenda-header-main">
        <div class="prenda-header-content">
            <div class="prenda-header-top">
                <a href="{{ route('balanceo.index') }}" 
                   class="prenda-header-back-btn"
                   onmouseover="this.style.background='rgba(255, 157, 88, 0.2)'; this.style.transform='translateX(-5px)'" 
                   onmouseout="this.style.background='rgba(255, 157, 88, 0.1)'; this.style.transform='translateX(0)'">
                    <span class="material-symbols-rounded" style="font-size: 24px;">arrow_back</span>
                </a>
                <h1 class="prenda-header-title">{{ $prenda->nombre }}</h1>
                <span class="prenda-header-badge">
                    {{ $prenda->tipo }}
                </span>
                
                <div class="prenda-header-actions">
                    <!-- Botón de estado completo/incompleto (solo visible si hay balanceo) -->
                    <button x-show="balanceoId !== null" 
                       @click="toggleEstadoCompleto()" 
                       class="prenda-estado-btn"
                       :title="balanceo.estado_completo === true ? 'Marcar como incompleto' : (balanceo.estado_completo === false ? 'Desmarcar' : 'Marcar estado')"
                       :style="'box-shadow: 0 2px 4px ' + (balanceo.estado_completo === true ? 'rgba(67, 233, 123, 0.3)' : (balanceo.estado_completo === false ? 'rgba(239, 68, 68, 0.3)' : 'rgba(156, 163, 175, 0.3)')) + '; background: ' + (balanceo.estado_completo === true ? 'linear-gradient(135deg, #43e97b 0%, #38d16a 100%)' : (balanceo.estado_completo === false ? 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' : 'linear-gradient(135deg, #9ca3af 0%, #6b7280 100%)'))"
                       onmouseover="this.style.transform='scale(1.05)'"
                       onmouseout="this.style.transform='scale(1)'">
                        <span class="material-symbols-rounded" style="font-size: 16px;" x-text="balanceo.estado_completo === true ? 'check_circle' : (balanceo.estado_completo === false ? 'cancel' : 'radio_button_unchecked')"></span>
                        <span x-text="balanceo.estado_completo === true ? 'Completo' : (balanceo.estado_completo === false ? 'Incompleto' : 'Sin Marcar')"></span>
                    </button>
                
                    <a href="{{ route('balanceo.prenda.edit', $prenda->id) }}" 
                       title="Editar Prenda"
                       class="prenda-action-btn"
                       onmouseover="this.style.background='rgba(255, 157, 88, 0.2)'; this.style.transform='scale(1.1)'" 
                       onmouseout="this.style.background='rgba(255, 157, 88, 0.1)'; this.style.transform='scale(1)'">
                        <span class="material-symbols-rounded" style="font-size: 20px;">edit</span>
                    </a>
                    <button onclick="deletePrenda({{ $prenda->id }})" 
                       title="Eliminar Prenda"
                       class="prenda-action-btn prenda-delete-btn"
                       onmouseover="this.style.background='rgba(245, 87, 108, 0.2)'; this.style.transform='scale(1.1)'" 
                       onmouseout="this.style.background='rgba(245, 87, 108, 0.1)'; this.style.transform='scale(1)'">
                        <span class="material-symbols-rounded" style="font-size: 20px;">delete</span>
                    </button>
                </div>
            </div>
            
            @if($prenda->referencia)
            <p class="prenda-header-info" style="margin-bottom: 8px;"><strong style="color: #ff9d58;">Referencia:</strong> {{ $prenda->referencia }}</p>
            @endif
            
            @if($prenda->descripcion)
            <p class="prenda-header-info">{{ $prenda->descripcion }}</p>
            @endif
        </div>

        <div class="prenda-header-image">
            @if($prenda->imagen)
                <img src="{{ asset($prenda->imagen) }}" 
                     alt="{{ $prenda->nombre }}" 
                     style="width: 100%; height: 100%; object-fit: cover;"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div style="display: none; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: #f0f0f0;">
                    <span class="material-symbols-rounded" style="font-size: 40px; color: #ccc;">checkroom</span>
                </div>
            @else
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: #f0f0f0;">
                    <span class="material-symbols-rounded" style="font-size: 40px; color: #ccc;">checkroom</span>
                </div>
            @endif
        </div>
    </div>
</div>
