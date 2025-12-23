{{-- Prendas Table Tab --}}
@php
    $idLogo = \App\Models\TipoCotizacion::getIdPorCodigo('L');
    $idCombinada = \App\Models\TipoCotizacion::getIdPorCodigo('PL');
    $tabActivoPorDefecto = 'prendas';
    if ($cotizacion->tipo_cotizacion_id === $idLogo) {
        $tabActivoPorDefecto = 'bordado';
    }
@endphp
<div id="tab-prendas" class="tab-content {{ $tabActivoPorDefecto === 'prendas' ? 'active' : '' }}">
    @if($cotizacion->prendas && count($cotizacion->prendas) > 0)
        {{-- Tipo Venta de Prendas --}}
        @if($cotizacion->tipo_venta)
            <div style="
                margin-bottom: 2rem;
                display: flex;
                align-items: center;
                gap: 1rem;
            ">
                <span style="
                    display: inline-block;
                    padding: 0.5rem 1rem;
                    border-radius: 6px;
                    font-weight: 700;
                    font-size: 0.85rem;
                    background: linear-gradient(135deg, #0066cc, #0052a3);
                    color: white;
                    box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
                ">
                    <i class="fas fa-tag"></i> Tipo Venta: <strong>{{ $cotizacion->tipo_venta }}</strong>
                </span>
            </div>
        @endif
        
        <table style="
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2.5rem;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        ">
            <thead style="background: #1e40af; color: white;">
                <tr>
                    <th style="width: 15%; padding: 1rem; text-align: left; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0ea5e9;">Prenda</th>
                    <th style="width: 35%; padding: 1rem; text-align: left; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0ea5e9;">Descripción & Tallas</th>
                    <th style="width: 25%; padding: 1rem; text-align: left; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0ea5e9;">Color, Tela & Variaciones</th>
                    <th style="width: 25%; padding: 1rem; text-align: center; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0ea5e9;">Imagen Prenda & Tela</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cotizacion->prendas as $index => $prenda)
                    @php
                        $variante = $prenda->variantes && $prenda->variantes->count() > 0 ? $prenda->variantes->first() : null;
                    @endphp
                    @include('components.cotizaciones.show.prenda-row', ['prenda' => $prenda, 'variante' => $variante])
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 3rem 2rem; color: #94a3b8; font-style: italic; font-size: 0.95rem;">
            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
            Sin prendas agregadas
        </div>
    @endif

    {{-- Especificaciones siempre se muestran, independientemente de si hay prendas --}}
    @include('components.cotizaciones.show.especificaciones', ['cotizacion' => $cotizacion])
</div>
