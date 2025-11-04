<div style="background: var(--color-bg-sidebar); padding: 28px; border-radius: 12px; margin-bottom: 24px; border: 1px solid var(--color-border-hr); box-shadow: 0 1px 3px var(--color-shadow);">
    <div style="display: flex; justify-content: space-between; align-items: start; gap: 24px;">
        <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <a href="{{ route('balanceo.index') }}" 
                   style="color: #ff9d58; text-decoration: none; display: flex; align-items: center; transition: all 0.2s; padding: 8px; border-radius: 8px; background: rgba(255, 157, 88, 0.1);" 
                   onmouseover="this.style.background='rgba(255, 157, 88, 0.2)'; this.style.transform='translateX(-5px)'" 
                   onmouseout="this.style.background='rgba(255, 157, 88, 0.1)'; this.style.transform='translateX(0)'">
                    <span class="material-symbols-rounded" style="font-size: 24px;">arrow_back</span>
                </a>
                <h1 style="margin: 0; font-size: 28px; color: var(--color-text-primary); font-weight: 700; flex: 1;">{{ $prenda->nombre }}</h1>
                <span style="background: #ff9d58; color: white; padding: 6px 14px; border-radius: 20px; font-size: 12px; text-transform: uppercase; font-weight: 600; box-shadow: 0 2px 4px rgba(255, 157, 88, 0.3);">
                    {{ $prenda->tipo }}
                </span>
                <a href="{{ route('balanceo.prenda.edit', $prenda->id) }}" 
                   title="Editar Prenda"
                   style="color: var(--color-text-primary); text-decoration: none; display: flex; align-items: center; transition: all 0.2s; padding: 8px; border-radius: 8px; background: rgba(255, 157, 88, 0.1);" 
                   onmouseover="this.style.background='rgba(255, 157, 88, 0.2)'; this.style.transform='scale(1.1)'" 
                   onmouseout="this.style.background='rgba(255, 157, 88, 0.1)'; this.style.transform='scale(1)'">
                    <span class="material-symbols-rounded" style="font-size: 20px;">edit</span>
                </a>
                <button onclick="deletePrenda({{ $prenda->id }})" 
                   title="Eliminar Prenda"
                   style="color: var(--color-text-primary); text-decoration: none; display: flex; align-items: center; transition: all 0.2s; padding: 8px; border-radius: 8px; background: rgba(245, 87, 108, 0.1); border: none; cursor: pointer;" 
                   onmouseover="this.style.background='rgba(245, 87, 108, 0.2)'; this.style.transform='scale(1.1)'" 
                   onmouseout="this.style.background='rgba(245, 87, 108, 0.1)'; this.style.transform='scale(1)'">
                    <span class="material-symbols-rounded" style="font-size: 20px;">delete</span>
                </button>
            </div>
            
            @if($prenda->referencia)
            <p style="margin: 0 0 8px 0; color: var(--color-text-placeholder); font-size: 15px;"><strong style="color: #ff9d58;">Referencia:</strong> {{ $prenda->referencia }}</p>
            @endif
            
            @if($prenda->descripcion)
            <p style="margin: 0; color: var(--color-text-placeholder); line-height: 1.6;">{{ $prenda->descripcion }}</p>
            @endif
        </div>

        <div style="width: 120px; height: 120px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(255, 157, 88, 0.3); border: 2px solid rgba(255, 157, 88, 0.3); background: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
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
