{{-- Componente: Header de Cotizaciones --}}
<div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); border-radius: 12px; padding: 20px 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(30, 58, 138, 0.2);">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 30px;">
        <!-- TÍTULO CON ICONO -->
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="background: rgba(255,255,255,0.15); padding: 10px 12px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-file-alt" style="color: white; font-size: 24px;"></i>
            </div>
            <div>
                <h1 id="headerTitle" style="margin: 0; font-size: 1.5rem; color: white; font-weight: 700;">
                    {{ $title ?? 'Cotizaciones' }}
                </h1>
                <p style="margin: 0; color: rgba(255,255,255,0.7); font-size: 0.85rem;">
                    {{ $subtitle ?? 'Gestiona tus cotizaciones' }}
                </p>
            </div>
        </div>

        <!-- BUSCADOR Y BOTÓN -->
        <div style="display: flex; gap: 12px; align-items: center; flex: 1; max-width: 600px;">
            <div style="flex: 1; position: relative;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="text" id="buscador" 
                    placeholder="{{ $searchPlaceholder ?? 'Buscar por cliente...' }}" 
                    onkeyup="filtrarCotizaciones()" 
                    style="padding: 10px 12px 10px 35px; border: none; border-radius: 6px; width: 100%; font-size: 0.9rem; background: rgba(255,255,255,0.95); transition: all 0.3s;" 
                    onfocus="this.style.background='white'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" 
                    onblur="this.style.background='rgba(255,255,255,0.95)'; this.style.boxShadow='none'">
            </div>
            
            <!-- BOTÓN ACCIÓN -->
            @if($actionButton)
                <a href="{{ $actionButton['url'] }}" 
                    style="background: white; color: #2c3e50; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.1); white-space: nowrap;" 
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" 
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    {{ $actionButton['label'] ?? 'Registrar' }}
                </a>
            @endif
        </div>
    </div>
</div>
