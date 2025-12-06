{{-- Logo/Bordado Tab --}}
<div id="tab-bordado" class="tab-content {{ $esLogo ? 'active' : '' }}">
    @if($logo)
        {{-- Imágenes --}}
        @if($logo->imagenes && is_array($logo->imagenes) && count($logo->imagenes) > 0)
            <div style="
                font-size: 1.4rem;
                font-weight: 800;
                color: #1e293b;
                margin-bottom: 1.75rem;
                padding-bottom: 1rem;
                border-bottom: 3px solid #0ea5e9;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            ">
                <i class="fas fa-images" style="color: #0ea5e9; font-size: 1.4rem;"></i> Imágenes
            </div>
            <div style="
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            ">
                @foreach($logo->imagenes as $imagen)
                    <div style="border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 6px 16px rgba(0, 0, 0, 0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'">
                        <img src="{{ asset($imagen) }}" alt="{{ $esLogo ? 'Logo' : 'Bordado' }}" 
                             style="width: 100%; height: 150px; object-fit: cover; cursor: pointer; transition: transform 0.3s ease;"
                             onmouseover="this.style.transform='scale(1.05)'"
                             onmouseout="this.style.transform=''"
                             onclick="abrirModalImagen('{{ asset($imagen) }}', '{{ $esLogo ? 'Logo' : 'LOGO' }}')">
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 3rem 2rem; color: #94a3b8; font-style: italic; font-size: 0.95rem;">
                <i class="fas fa-images" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                {{ $esLogo ? 'Sin imágenes de logo' : 'Sin imágenes de LOGO' }}
            </div>
        @endif

        @include('components.cotizaciones.show.logo-tecnicas', ['logo' => $logo, 'esLogo' => $esLogo])
        @include('components.cotizaciones.show.logo-ubicaciones', ['logo' => $logo])
        @include('components.cotizaciones.show.logo-observaciones', ['logo' => $logo])
    @else
        <div style="text-align: center; padding: 3rem 2rem; color: #94a3b8; font-style: italic; font-size: 0.95rem;">
            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
            {{ $esLogo ? 'Sin información de logo' : 'Sin información de LOGO' }}
        </div>
    @endif
</div>
