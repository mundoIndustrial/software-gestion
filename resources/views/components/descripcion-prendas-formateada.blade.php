@props(['descripcion'])

@php
    // Parsear la descripción de prendas
    $prendas = array_filter(explode("\n\n", $descripcion ?? ''));
@endphp

<div class="descripcion-prendas-cell">
    @foreach($prendas as $index => $prendaText)
        @php
            // Parsear cada prenda
            preg_match('/^(\d+)\.\s+Prenda:\s*(.+?)$/m', $prendaText, $prendaMatch);
            $prendaNum = $prendaMatch[1] ?? ($index + 1);
            $prendaNombre = $prendaMatch[2] ?? '';
            
            // Buscar Color
            preg_match('/Color:\s*(.+?)(?:\n|$)/i', $prendaText, $colorMatch);
            $color = $colorMatch[1] ?? '';
            
            // Buscar Tela
            preg_match('/Tela:\s*(.+?)(?:\n|$)/i', $prendaText, $telaMatch);
            $tela = $telaMatch[1] ?? '';
            
            // Buscar Manga
            preg_match('/Manga:\s*(.+?)(?:\n|$)/i', $prendaText, $mangaMatch);
            $manga = $mangaMatch[1] ?? '';
            
            // Buscar Descripción
            preg_match('/Descripción:\s*(.+?)(?=\n|$)/i', $prendaText, $descMatch);
            $desc = $descMatch[1] ?? '';
            
            // Buscar Bolsillos y Reflectivo
            preg_match('/Bolsillos:\s*(.+?)(?:\n|$)/i', $prendaText, $bolsillosMatch);
            $bolsillos = $bolsillosMatch[1] ?? '';
            
            preg_match('/Reflectivo:\s*(.+?)(?:\n|$)/i', $prendaText, $reflectivoMatch);
            $reflectivo = $reflectivoMatch[1] ?? '';
            
            // Buscar Tallas
            preg_match('/Tallas:\s*(.+?)$/s', $prendaText, $tallasMatch);
            $tallas = $tallasMatch[1] ?? '';
        @endphp
        
        <div class="prenda-item">
            {{-- Nombre de la prenda --}}
            @if($prendaNombre)
                <div class="prenda-nombre">
                    Prenda {{ $prendaNum }}: {{ $prendaNombre }}
                </div>
            @endif
            
            {{-- Atributos (Color, Tela, Manga) --}}
            @if($color || $tela || $manga)
                <div class="prenda-atributos">
                    @if($color)
                        <span class="prenda-atributo-label">Color:</span> {{ $color }}
                    @endif
                    @if($color && $tela)
                        |
                    @endif
                    @if($tela)
                        <span class="prenda-atributo-label">Tela:</span> {{ $tela }}
                    @endif
                    @if(($color || $tela) && $manga)
                        |
                    @endif
                    @if($manga)
                        <span class="prenda-atributo-label">Manga:</span> {{ $manga }}
                    @endif
                </div>
            @endif
            
            {{-- Descripción --}}
            @if($desc)
                <div class="prenda-descripcion">
                    <span class="prenda-descripcion-label">Descripción:</span> {{ $desc }}
                </div>
            @endif
            
            {{-- Detalles (Bolsillos, Reflectivo) --}}
            @if($bolsillos || $reflectivo)
                <div class="prenda-detalles">
                    @if($bolsillos)
                        <strong>Bolsillos:</strong> {{ $bolsillos }}
                    @endif
                    @if($bolsillos && $reflectivo)
                        -
                    @endif
                    @if($reflectivo)
                        <strong>Reflectivo:</strong> {{ $reflectivo }}
                    @endif
                </div>
            @endif
            
            {{-- Tallas --}}
            @if($tallas)
                <div class="prenda-tallas">
                    <span class="prenda-tallas-label">Tallas:</span>
                    <span class="prenda-tallas-value">{{ $tallas }}</span>
                </div>
            @endif
        </div>
    @endforeach
</div>
