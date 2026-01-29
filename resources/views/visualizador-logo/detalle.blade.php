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
<div style="background: white; color: #0f172a; padding: 1.5rem 2rem; margin: 0; border-bottom: 3px solid #e11d48;">
    <div class="container-fluid">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <!-- Izquierda: Logo y Cotización -->
            <div style="display: flex; align-items: center; gap: 3rem;">
                <div>
                    <img src="{{ asset('images/logo.png') }}" alt="Mundo Industrial" style="height: 170px; width: 50;">
                </div>
                <div>
                    <div style="font-size: 1.5rem; font-weight: 700; letter-spacing: 0.05em; color: #0f172a;">
                        #{{ $cotizacion->numero_cotizacion }}
                    </div>
                    <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.5rem; font-size: 0.95rem; color: #0f172a;">
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
            <div style="text-align: right;">
                <div style="font-size: 0.9rem; color: #64748b;">Fecha</div>
                <div style="font-size: 1.3rem; font-weight: 700; color: #0f172a;">
                    {{ $cotizacion->fecha_envio ? $cotizacion->fecha_envio->format('d M Y') : $cotizacion->created_at->format('d M Y') }}
                </div>
            </div>

            <!-- Derecha: Botones -->
            <div style="display: flex; gap: 0.5rem;">
                <a href="{{ route('visualizador-logo.dashboard') }}" 
                   style="
                       background: transparent;
                       color: #0f172a;
                       border: 1px solid #e11d48;
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
                   onmouseover="this.style.background='#e11d48'; this.style.color='white';"
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
            <h4 class="mb-4"><i class="fas fa-shirt me-2"></i>Prendas con Logo</h4>
            
            @foreach($cotizacion->logoCotizacion->tecnicasPrendas->groupBy('prenda_cot_id') as $prendasAgrupadas)
                @php $prenda = $prendasAgrupadas->first()->prenda; @endphp
                
                <div class="card border-0 shadow-sm mb-4" style="background: #fff;">
                    <!-- Card Header -->
                    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; padding: 1.5rem; border-radius: 8px 8px 0 0;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1" style="font-size: 1.3rem; font-weight: 700;">{{ $prenda->nombre_producto }}</h5>
                                @if($prenda->descripcion)
                                <p class="mb-0" style="color: #cbd5e1; font-size: 0.9rem;">{{ $prenda->descripcion }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Variantes de la Prenda -->
                        @if($prenda->variantes && count($prenda->variantes) > 0)
                        <div class="row mb-4">
                            @foreach($prenda->variantes as $variante)
                            <div class="col-md-6">
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem;">
                                    <h6 style="color: #0f172a; font-weight: 600; margin-bottom: 1rem; border-bottom: 2px solid #0ea5e9; padding-bottom: 0.5rem;">
                                        <i class="fas fa-cogs me-2" style="color: #0ea5e9;"></i>Especificaciones
                                    </h6>
                                    
                                    @if($variante->tipo_prenda)
                                    <div class="mb-2">
                                        <span style="color: #64748b; font-size: 0.9rem;"><strong>Tipo:</strong></span>
                                        <span style="color: #0f172a; font-weight: 500;">{{ $variante->tipo_prenda }}</span>
                                    </div>
                                    @endif

                                    @if($variante->color)
                                    <div class="mb-2">
                                        <span style="color: #64748b; font-size: 0.9rem;"><strong>Color:</strong></span>
                                        <span style="color: #0f172a; font-weight: 500;">{{ $variante->color }}</span>
                                    </div>
                                    @endif

                                    @if($variante->genero_id)
                                    <div class="mb-2">
                                        <span style="color: #64748b; font-size: 0.9rem;"><strong>Género:</strong></span>
                                        <span style="color: #0f172a; font-weight: 500;">{{ $variante->genero->nombre ?? '-' }}</span>
                                    </div>
                                    @endif

                                    <!-- Manga -->
                                    @if($variante->aplica_manga)
                                    <div class="mb-2">
                                        <span style="color: #64748b; font-size: 0.9rem;"><strong>Manga:</strong></span>
                                        <span style="color: #0f172a; font-weight: 500;">{{ $variante->tipo_manga ?? '-' }}</span>
                                        @if($variante->obs_manga)
                                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">{{ $variante->obs_manga }}</div>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Broche/Botón -->
                                    @if($variante->aplica_broche)
                                    <div class="mb-2">
                                        <span style="color: #64748b; font-size: 0.9rem;"><strong>Broche/Botón:</strong></span>
                                        <span style="color: #0f172a; font-weight: 500;">{{ $variante->broche->nombre ?? '-' }}</span>
                                        @if($variante->obs_broche)
                                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">{{ $variante->obs_broche }}</div>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Bolsillos -->
                                    @if($variante->tiene_bolsillos)
                                    <div class="mb-2">
                                        <span style="color: #64748b; font-size: 0.9rem;"><strong>Bolsillos:</strong></span>
                                        <span style="color: #0f172a; font-weight: 500;">Sí</span>
                                        @if($variante->obs_bolsillos)
                                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">{{ $variante->obs_bolsillos }}</div>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Reflectivo -->
                                    @if($variante->tiene_reflectivo)
                                    <div class="mb-0">
                                        <span style="color: #64748b; font-size: 0.9rem;"><strong>Reflectivo:</strong></span>
                                        <span style="color: #0f172a; font-weight: 500;">Sí</span>
                                        @if($variante->obs_reflectivo)
                                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">{{ $variante->obs_reflectivo }}</div>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        <!-- Técnicas de Logo y Ubicaciones -->
                        <div class="row">
                            @foreach($prendasAgrupadas as $tecnicaPrenda)
                            <div class="col-12 mb-3">
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem;">
                                    <h6 style="color: #0f172a; font-weight: 600; margin-bottom: 1rem; border-bottom: 2px solid #e11d48; padding-bottom: 0.5rem;">
                                        <i class="fas fa-palette me-2" style="color: #e11d48;"></i>
                                        {{ $tecnicaPrenda->tipoLogo->nombre ?? 'Logo' }}
                                    </h6>

                                    @if($tecnicaPrenda->ubicaciones)
                                    <div>
                                        <p style="color: #64748b; font-size: 0.9rem; font-weight: 600; margin-bottom: 0.5rem;">
                                            <i class="fas fa-map-pin me-2"></i>Ubicaciones:
                                        </p>
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                            @php
                                                $ubicaciones = is_array($tecnicaPrenda->ubicaciones) 
                                                    ? $tecnicaPrenda->ubicaciones 
                                                    : json_decode($tecnicaPrenda->ubicaciones, true) ?? [];
                                            @endphp
                                            @foreach($ubicaciones as $ubicacion)
                                            <span style="
                                                background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
                                                color: white;
                                                padding: 0.4rem 0.8rem;
                                                border-radius: 20px;
                                                font-size: 0.85rem;
                                                font-weight: 500;
                                            ">
                                                • {{ is_array($ubicacion) ? ($ubicacion['ubicacion'] ?? $ubicacion['nombre'] ?? $ubicacion) : $ubicacion }}
                                            </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    @if($tecnicaPrenda->observaciones)
                                    <div style="margin-top: 1rem;">
                                        <p style="color: #64748b; font-size: 0.9rem; font-weight: 600; margin-bottom: 0.5rem;">
                                            <i class="fas fa-note-sticky me-2"></i>Observaciones:
                                        </p>
                                        <p style="color: #334155; background: white; padding: 0.75rem; border-left: 3px solid #e11d48; border-radius: 4px; margin: 0;">
                                            {{ $tecnicaPrenda->observaciones }}
                                        </p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
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
                    <p style="color: #64748b; margin: 0; font-size: 1rem;">{{ $obs }}</p>
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
