@extends('layouts.visualizador-logo')

@section('title', 'Detalle Cotización Logo')

@push('styles')
<style>
    .top-nav {
        display: none !important;
    }
    body {
        padding-top: 0 !important;
    }
</style>
@endpush

@section('content')

<!-- Header Personalizado -->
<div style="background: white; color: #0f172a; padding: 0; margin: 0; border-bottom: 3px solid #1d78e1;">
    <div class="container-fluid">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
            <!-- Izquierda: Logo y Cotización -->
            <div style="display: flex; align-items: flex-start; gap: 1.5rem;">
                <div>
                    <img src="{{ asset('images/logo.png') }}" alt="Mundo Industrial" style="height: 120px; width: auto; display: block; margin: 0;">
                </div>
                <div style="padding-top: 0;">
                    <div style="font-size: 1.2rem; font-weight: 700; letter-spacing: 0.05em; color: #0f172a; margin: 0; padding-top: 2.5rem;">
                        #{{ $cotizacion->numero_cotizacion }}
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center; margin-top: 0.25rem; font-size: 0.85rem; color: #0f172a;">
                        <span>Tipo de Venta:</span>
                        @if($cotizacion->logoCotizacion)
                            @switch($cotizacion->logoCotizacion->tipo_venta)
                                @case('M')
                                    <span style="font-weight: 600;">M</span>
                                    @break
                                @case('D')
                                    <span style="font-weight: 600;">D</span>
                                    @break
                                @case('X')
                                    <span style="font-weight: 600;">X</span>
                                    @break
                                @default
                                    <span style="font-weight: 600;">{{ $cotizacion->logoCotizacion->tipo_venta ?? '-' }}</span>
                            @endswitch
                        @endif
                    </div>
                </div>
            </div>

            <!-- Centro: Fecha -->
            <div style="text-align: center; padding-top: 3rem;">
                <div style="font-size: 0.8rem; color: #64748b; margin: 0;">Fecha</div>
                <div style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0;">
                    {{ $cotizacion->fecha_envio ? $cotizacion->fecha_envio->format('d M Y') : $cotizacion->created_at->format('d M Y') }}
                </div>
            </div>

            <!-- Derecha: Botones -->
            <div style="display: flex; gap: 0.5rem; padding-top: 3rem;">
                <a href="{{ route('visualizador-logo.dashboard') }}" 
                   style="
                       background: transparent;
                       color: #0f172a;
                       border: 1px solid #1d78e1;
                       padding: 0.6rem 1.2rem;
                       border-radius: 6px;
                       text-decoration: none;
                       font-weight: 600;
                       font-size: 0.9rem;
                       cursor: pointer;
                       transition: all 0.3s ease;
                       display: flex;
                       align-items: center;
                       gap: 0.5rem;
                   "
                   onmouseover="this.style.background='#1d78e1'; this.style.color='white';"
                   onmouseout="this.style.background='transparent'; this.style.color='#0f172a';">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid py-4" style="background: #f5f5f5;">
    <div style="max-width: 1400px; margin: 0 auto;">

    <!-- Prendas con Logo -->
    @if($cotizacion->logoCotizacion && $cotizacion->logoCotizacion->tecnicasPrendas && count($cotizacion->logoCotizacion->tecnicasPrendas) > 0)
    <div class="row mt-4">
        <div class="col-12">
            
            @foreach($cotizacion->logoCotizacion->tecnicasPrendas->groupBy('prenda_cot_id') as $prendasAgrupadas)
                @php 
                    $prenda = $prendasAgrupadas->first()->prenda; 
                    $variante = $prenda->variantes->first();
                    $telasMultiples = [];
                    if ($variante && $variante->telas_multiples) {
                        $telasMultiples = is_string($variante->telas_multiples) 
                            ? json_decode($variante->telas_multiples, true) 
                            : $variante->telas_multiples;
                        $telasMultiples = is_array($telasMultiples) ? $telasMultiples : [];
                    }
                    $telaInfo = $telasMultiples[0] ?? [];
                @endphp
                
                <div class="card border-0 shadow-sm mb-4" style="background: #fff;">
                    <!-- Card Header - Estilo como en la imagen -->
                    <div style="background: white; color: #0f172a; padding: 1.5rem; border-left: 4px solid #1d78e1; border-radius: 8px 8px 0 0;">
                        <h5 class="mb-2" style="font-size: 1.1rem; font-weight: 700; margin: 0;">{{ $prenda->nombre_producto }}</h5>
                        <div style="display: flex; gap: 2rem; flex-wrap: wrap; font-size: 0.9rem; color: #0f172a;">
                            @if($telaInfo)
                                <span><strong>Color:</strong> {{ $telaInfo['color'] ?? '-' }}</span>
                                <span><strong>Tela:</strong> {{ $telaInfo['tela'] ?? '-' }}</span>
                                <span><strong>Referencia:</strong> {{ $telaInfo['referencia'] ?? '-' }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Logo section con tabla y fotos -->
                        <div style="margin-bottom: 2rem;">
                            <h6 style="color: #0f172a; font-weight: 600; margin-bottom: 1rem;">LOGO:</h6>
                            <table style="border-collapse: collapse; width: 100%;">
                                <thead>
                                    <tr style="background: linear-gradient(135deg, #0084ff 0%, #0066cc 100%); color: white;">
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; white-space: nowrap;">Técnica(s)</th>
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Ubicaciones</th>
                                        <th style="padding: 0.75rem; text-align: center; font-weight: 600;">Imágenes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($prendasAgrupadas as $tecnicaPrenda)
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0; font-weight: 600;">{{ $tecnicaPrenda->tipoLogo->nombre ?? 'Logo' }}</td>
                                        <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0;">
                                            @if($tecnicaPrenda->ubicaciones)
                                                @php
                                                    $ubicaciones = is_array($tecnicaPrenda->ubicaciones) 
                                                        ? $tecnicaPrenda->ubicaciones 
                                                        : json_decode($tecnicaPrenda->ubicaciones, true) ?? [];
                                                @endphp
                                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                                    @foreach($ubicaciones as $ubicacion)
                                                        <span style="color: #0ea5e9; font-weight: 500;">
                                                            • {{ is_array($ubicacion) ? ($ubicacion['ubicacion'] ?? $ubicacion['nombre'] ?? $ubicacion) : $ubicacion }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                                @if($tecnicaPrenda->observaciones)
                                                    <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #0f172a;">{{ $tecnicaPrenda->observaciones }}</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            @if($tecnicaPrenda->fotos && count($tecnicaPrenda->fotos) > 0)
                                                <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                                    @foreach($tecnicaPrenda->fotos->sortBy('orden') as $foto)
                                                    <img src="{{ asset('storage/' . $foto->ruta_webp) }}" alt="Foto logo" 
                                                         style="max-width: 100px; height: auto; border-radius: 6px; border: 1px solid #e2e8f0; cursor: pointer; transition: transform 0.2s;" 
                                                         onmouseover="this.style.transform='scale(1.05)'"
                                                         onmouseout="this.style.transform='scale(1)'"
                                                         onclick="verImagenCompleta('{{ asset('storage/' . $foto->ruta_original) }}')">
                                                    @endforeach
                                                </div>
                                            @else
                                                <span style="color: #64748b;">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Variaciones de Logo Técnica (desde tabla logo_cotizacion_tecnica_prendas) -->
                        @php
                            $variacionesFormateadas = [];
                            foreach ($prendasAgrupadas as $tecnicaPrenda) {
                                $variaciones = is_string($tecnicaPrenda->variaciones_prenda) 
                                    ? json_decode($tecnicaPrenda->variaciones_prenda, true) ?? [] 
                                    : $tecnicaPrenda->variaciones_prenda;
                                
                                if (!empty($variaciones) && is_array($variaciones)) {
                                    foreach ($variaciones as $opcionNombre => $detalles) {
                                        if (is_array($detalles) && isset($detalles['opcion'])) {
                                            $nombreFormato = ucfirst(str_replace('_', ' ', $opcionNombre));
                                            $variacionesFormateadas[$nombreFormato] = [
                                                'opcion' => $detalles['opcion'],
                                                'observacion' => $detalles['observacion'] ?? ''
                                            ];
                                        }
                                    }
                                }
                            }
                        @endphp

                        @if(!empty($variacionesFormateadas))
                        <div style="margin-bottom: 2rem;">
                            <h6 style="color: #0f172a; font-weight: 600; margin-bottom: 1rem;">VARIACIONES DEL LOGO</h6>
                            <table style="border-collapse: collapse; table-layout: auto; width: 100%;">
                                <thead>
                                    <tr style="background: linear-gradient(135deg, #0084ff 0%, #0066cc 100%); color: white;">
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; min-width: 250px; white-space: nowrap;">Tipo</th>
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; min-width: 300px;">Valor</th>
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; min-width: 250px;">Observación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($variacionesFormateadas as $tipo => $datos)
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0; font-weight: 600;">{{ $tipo }}</td>
                                        <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0; color: #0ea5e9; font-weight: 500;">{{ $datos['opcion'] ?? '-' }}</td>
                                        <td style="padding: 0.75rem; color: #64748b;">{{ !empty($datos['observacion']) ? $datos['observacion'] : '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        <!-- Variaciones Específicas en tabla con imágenes (de la prenda) -->
                        @if($variante)
                        <div>
                            <h6 style="color: #0f172a; font-weight: 600; margin-bottom: 1rem;">VARIACIONES DE LA PRENDA</h6>
                            <table style="border-collapse: collapse; table-layout: auto; width: 100%;">
                                <thead>
                                    <tr style="background: linear-gradient(135deg, #0084ff 0%, #0066cc 100%); color: white;">
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; min-width: 250px; white-space: nowrap;">Variación</th>
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; min-width: 310px;">Observación</th>
                                        <th style="padding: 0.75rem; text-align: center; font-weight: 600; min-width: 250px;">Imágenes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Manga -->
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0;">Manga</td>
                                        <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0;">{{ $variante->tipo_manga ?? '-' }} @if($variante->obs_manga)<div style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">{{ $variante->obs_manga }}</div>@endif</td>
                                        <td style="padding: 0.75rem; text-align: center; vertical-align: middle;" rowspan="3">
                                            @if($prenda->fotos && count($prenda->fotos) > 0)
                                                <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                                    @foreach($prenda->fotos->sortBy('orden') as $foto)
                                                    <img src="{{ asset('storage/' . $foto->ruta_webp) }}" alt="Foto prenda" 
                                                         style="max-width: 120px; height: auto; border-radius: 6px; border: 1px solid #e2e8f0; cursor: pointer; transition: transform 0.2s;" 
                                                         onmouseover="this.style.transform='scale(1.05)'"
                                                         onmouseout="this.style.transform='scale(1)'"
                                                         onclick="verImagenCompleta('{{ asset('storage/' . $foto->ruta_original ?? $foto->ruta_webp) }}')">
                                                    @endforeach
                                                </div>
                                            @else
                                                <span style="color: #64748b;">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <!-- Bolsillos -->
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0;">Bolsillos</td>
                                        <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0;">{{ $variante->tiene_bolsillos ? 'Sí' : 'No' }} @if($variante->obs_bolsillos)<div style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">{{ $variante->obs_bolsillos }}</div>@endif</td>
                                    </tr>
                                    <!-- Broche/Botón -->
                                    <tr>
                                        <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0;">Broche/Botón</td>
                                        <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0;">{{ $variante->broche->nombre ?? '-' }} @if($variante->obs_broche)<div style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">{{ $variante->obs_broche }}</div>@endif</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<!-- Observaciones Generales - Al Final -->
@if($cotizacion->logoCotizacion && $cotizacion->logoCotizacion->observaciones_generales)
<div class="row mt-4">
    <div class="col-12">
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; padding: 1.5rem; border-radius: 8px 8px 0 0;">
                <h5 style="margin: 0; font-weight: 600; display: flex; align-items: center; gap: 0.75rem; font-size: 1.1rem;">
                    <i class="fas fa-clipboard-check" style="color: #0ea5e9; font-size: 1.3rem;"></i>Observaciones Generales
                </h5>
            </div>
            <div style="padding: 2rem;">
                @php
                    $obs = $cotizacion->logoCotizacion->observaciones_generales;
                    if (is_string($obs)) {
                        $obs = json_decode($obs, true);
                    }
                @endphp
                
                @if(is_array($obs) && count($obs) > 0)
                    <div style="display: grid; gap: 1rem;">
                        @foreach($obs as $item)
                            @php
                                $tipo = $item['tipo'] ?? 'text';
                                $texto = $item['texto'] ?? $item;
                                $valor = $item['valor'] ?? null;
                            @endphp
                            
                            @if($tipo === 'checkbox')
                                <div style="
                                    display: flex;
                                    align-items: center;
                                    gap: 0.75rem;
                                    background: white;
                                    padding: 1rem 1.25rem;
                                    border: 1px solid #e2e8f0;
                                    border-radius: 6px;
                                    transition: all 0.3s ease;
                                ">
                                    <input type="checkbox" disabled {{ $valor === 'on' ? 'checked' : '' }} 
                                        style="width: 20px; height: 20px; cursor: default; accent-color: #0ea5e9; flex-shrink: 0;">
                                    <span style="color: #334155; font-weight: 500; font-size: 1rem;">{{ $texto }}</span>
                                </div>
                            @else
                                <div style="
                                    background: white;
                                    padding: 1rem 1.25rem;
                                    border: 1px solid #e2e8f0;
                                    border-radius: 6px;
                                    border-left: 4px solid #0ea5e9;
                                    transition: all 0.3s ease;
                                ">
                                    <span style="color: #334155; font-weight: 500; font-size: 1rem;">{{ $texto }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p style="color: #64748b; margin: 0; font-size: 1rem;">{{ is_array($obs) ? json_encode($obs) : $obs }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

    </div>
</div>

<!-- Modal para ver imagen completa -->
<div class="modal fade" id="modalImagen" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Imagen del Logo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="imagenCompleta" src="" class="img-fluid" alt="Logo">
            </div>
        </div>
    </div>
</div>

<script>
function verImagenCompleta(url) {
    document.getElementById('imagenCompleta').src = url;
    const modal = new bootstrap.Modal(document.getElementById('modalImagen'));
    modal.show();
}
</script>
@endsection
