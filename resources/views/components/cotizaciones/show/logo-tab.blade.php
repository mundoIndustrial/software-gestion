{{-- Logo/Bordado Tab --}}
<div id="tab-bordado" class="tab-content {{ $esLogo ? 'active' : '' }}">
    @if($logo)
        {{-- Descripción del Logo --}}
        @if($logo->descripcion)
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
                <i class="fas fa-pen" style="color: #0ea5e9; font-size: 1.4rem;"></i> Descripción
            </div>
            <div style="
                background: white;
                padding: 1.5rem;
                border-radius: 10px;
                border-left: 4px solid #0ea5e9;
                margin-bottom: 2rem;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            ">
                <p style="color: #475569; margin: 0; line-height: 1.7; font-size: 0.95rem;">
                    {{ $logo->descripcion }}
                </p>
            </div>
        @endif

        @include('components.cotizaciones.show.logo-tecnicas', ['logo' => $logo, 'esLogo' => $esLogo])
        @include('components.cotizaciones.show.logo-ubicaciones', ['logo' => $logo])
        @include('components.cotizaciones.show.logo-observaciones', ['logo' => $logo])

        {{-- Imágenes desde fotos --}}
        @if($logo->fotos && $logo->fotos->count() > 0)
            <div style="
                font-size: 1.4rem;
                font-weight: 800;
                color: #1e293b;
                margin-top: 2rem;
                margin-bottom: 1.75rem;
                padding-bottom: 1rem;
                border-bottom: 3px solid #0ea5e9;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            ">
                <i class="fas fa-images" style="color: #0ea5e9; font-size: 1.4rem;"></i> Imágenes ({{ $logo->fotos->count() }})
            </div>
            <div style="
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            ">
                @php
                    $fotosArray = $logo->fotos->map(fn($f) => '/storage/' . $f->ruta_webp)->toArray();
                    $fotosJson = json_encode($fotosArray);
                @endphp
                @foreach($logo->fotos as $index => $foto)
                    <div style="border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 6px 16px rgba(0, 0, 0, 0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'">
                        <img src="/storage/{{ $foto->ruta_webp }}" alt="Logo" 
                             width="300" height="150"
                             style="width: 100%; height: 150px; object-fit: cover; cursor: pointer; transition: transform 0.3s ease;"
                             onmouseover="this.style.transform='scale(1.05)'"
                             onmouseout="this.style.transform=''"
                             onclick="abrirModalImagen('/storage/{{ $foto->ruta_webp }}', 'Logo - Imagen {{ $index + 1 }}', {{ $fotosJson }}, {{ $index }})">
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 3rem 2rem; color: #94a3b8; font-style: italic; font-size: 0.95rem;">
                <i class="fas fa-images" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                Sin imágenes de logo
            </div>
        @endif
    @else
        <div style="text-align: center; padding: 3rem 2rem; color: #94a3b8; font-style: italic; font-size: 0.95rem;">
            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
            {{ $esLogo ? 'Sin información de logo' : 'Sin información de LOGO' }}
        </div>
    @endif
</div>
