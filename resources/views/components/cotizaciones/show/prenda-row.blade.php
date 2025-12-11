{{-- Prenda Row --}}
<tr style="border-bottom: 1px solid #e2e8f0;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
    <td style="padding: 1.2rem; font-size: 1.05rem;">
        <div style="font-weight: 700; color: #1e40af;">{{ $prenda->nombre_producto ?? 'Sin nombre' }}</div>
    </td>
    <td style="padding: 1.2rem; font-size: 1.05rem;">
        <div style="color: #64748b; font-size: 0.9rem; line-height: 1.6;">
            <p style="margin: 0 0 8px 0; color: #475569; font-size: 0.95rem;">{{ $prenda->descripcion ?? '-' }}</p>
            @if($prenda->genero)
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                    <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">Género:</span>
                    <span style="font-size: 0.9rem; color: #1e293b; background: #f0f4f8; padding: 2px 8px; border-radius: 4px; text-transform: uppercase;">{{ $prenda->genero }}</span>
                </div>
            @endif
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">Tallas:</span>
                <span style="font-size: 0.9rem; color: #1e293b;">
                    @if($prenda->tallas && $prenda->tallas->count() > 0)
                        {{ $prenda->tallas->pluck('talla')->implode(', ') }}
                    @else
                        -
                    @endif
                </span>
            </div>
        </div>
    </td>
    <td style="padding: 1.2rem; font-size: 1.05rem;">
        @if($variante)
            @include('components.cotizaciones.show.variante-details', ['variante' => $variante])
        @else
            <span style="color: #cbd5e1;">Sin variaciones</span>
        @endif
    </td>
    <td style="padding: 1.2rem; font-size: 1.05rem; text-align: center;">
        <div style="display: flex; gap: 1rem; justify-content: center; align-items: center;">
            {{-- Imagen Prenda --}}
            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                <small style="font-size: 0.75rem; color: #64748b; font-weight: 600;">PRENDA ({{ $prenda->fotos ? $prenda->fotos->count() : 0 }})</small>
                @if($prenda->fotos && $prenda->fotos->count() > 0)
                    <div style="display: flex; gap: 0.3rem; flex-wrap: wrap; justify-content: center;">
                        @php
                            $fotosArray = $prenda->fotos->map(fn($f) => asset($f->ruta_webp))->toArray();
                            $fotosJson = json_encode($fotosArray);
                        @endphp
                        @foreach($prenda->fotos as $index => $foto)
                            <img src="{{ asset($foto->ruta_webp) }}" alt="Prenda {{ $index + 1 }}"
                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #e2e8f0;"
                                 onclick="abrirModalImagen('{{ asset($foto->ruta_webp) }}', '{{ $prenda->nombre_producto ?? 'Prenda' }} - Foto {{ $index + 1 }}', {{ $fotosJson }}, {{ $index }})">
                        @endforeach
                    </div>
                @else
                    <div style="width: 60px; height: 60px; background: #f1f5f9; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #cbd5e1;">
                        <i class="fas fa-image"></i>
                    </div>
                @endif
            </div>

            {{-- Imagen Tela --}}
            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                <small style="font-size: 0.75rem; color: #64748b; font-weight: 600;">TELA ({{ $prenda->telaFotos ? $prenda->telaFotos->count() : 0 }})</small>
                @if($prenda->telaFotos && $prenda->telaFotos->count() > 0)
                    <div style="display: flex; gap: 0.3rem; flex-wrap: wrap; justify-content: center;">
                        @php
                            $telasArray = $prenda->telaFotos->map(fn($t) => asset($t->ruta_webp))->toArray();
                            $telasJson = json_encode($telasArray);
                        @endphp
                        @foreach($prenda->telaFotos as $index => $tela)
                            <img src="{{ asset($tela->ruta_webp) }}" alt="Tela {{ $index + 1 }}"
                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #e2e8f0;"
                                 onclick="abrirModalImagen('{{ asset($tela->ruta_webp) }}', '{{ $prenda->nombre_producto ?? 'Tela' }} - Tela {{ $index + 1 }}', {{ $telasJson }}, {{ $index }})">
                        @endforeach
                    </div>
                @else
                    <div style="width: 60px; height: 60px; background: #f1f5f9; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #cbd5e1;">
                        <i class="fas fa-image"></i>
                    </div>
                @endif
            </div>
        </div>
    </td>
</tr>
