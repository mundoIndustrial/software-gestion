@extends('layouts.visualizador-logo')

@section('title', 'Detalle Cotización Logo')
@section('page-title')
    <i class="fas fa-image"></i> Cotización #{{ $cotizacion->numero_cotizacion }}
@endsection

@section('content')
<div class="container-fluid py-4" style="background: #f5f5f5;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-0">Información de Logo</p>
                </div>
                <div>
                    <a href="{{ route('visualizador-logo.dashboard') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                    <a href="{{ route('visualizador-logo.cotizaciones.pdf-logo', $cotizacion->id) }}" 
                       class="btn btn-danger" 
                       target="_blank">
                        <i class="fas fa-file-pdf me-1"></i> Descargar PDF Logo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información General -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información General</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Cliente</label>
                        <p class="mb-0 fw-bold">{{ $cotizacion->cliente->nombre ?? '-' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Asesor</label>
                        <p class="mb-0">{{ $cotizacion->asesor->name ?? '-' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Tipo de Cotización</label>
                        <p class="mb-0">
                            @if($cotizacion->tipoCotizacion->codigo == 'L')
                                <span class="badge bg-info">Logo</span>
                            @else
                                <span class="badge bg-info">Combinada (Logo)</span>
                            @endif
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Estado</label>
                        <p class="mb-0">
                            @switch($cotizacion->estado)
                                @case('pendiente')
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                    @break
                                @case('aprobado')
                                    <span class="badge bg-success">Aprobado</span>
                                    @break
                                @case('rechazado')
                                    <span class="badge bg-danger">Rechazado</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ $cotizacion->estado }}</span>
                            @endswitch
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Fecha de Inicio</label>
                        <p class="mb-0">{{ $cotizacion->fecha_inicio ? $cotizacion->fecha_inicio->format('d/m/Y') : '-' }}</p>
                    </div>
                    @if($cotizacion->fecha_envio)
                    <div class="mb-0">
                        <label class="text-muted small">Fecha de Envío</label>
                        <p class="mb-0">{{ $cotizacion->fecha_envio->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Información del Logo -->
        <div class="col-md-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-palette me-2"></i>Información del Logo</h5>
                </div>
                <div class="card-body">
                    @if($cotizacion->logoCotizacion)
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Técnicas</label>
                                <p class="mb-0">
                                    @if(is_array($cotizacion->logoCotizacion->tecnicas))
                                        @foreach($cotizacion->logoCotizacion->tecnicas as $tecnica)
                                            <span class="badge bg-secondary me-1">{{ $tecnica }}</span>
                                        @endforeach
                                    @else
                                        {{ $cotizacion->logoCotizacion->tecnicas ?? '-' }}
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Tipo de Venta</label>
                                <p class="mb-0">
                                    @switch($cotizacion->logoCotizacion->tipo_venta)
                                        @case('M')
                                            <span class="badge bg-primary">Muestra</span>
                                            @break
                                        @case('D')
                                            <span class="badge bg-success">Definitivo</span>
                                            @break
                                        @case('X')
                                            <span class="badge bg-warning text-dark">Mixto</span>
                                            @break
                                        @default
                                            {{ $cotizacion->logoCotizacion->tipo_venta ?? '-' }}
                                    @endswitch
                                </p>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="text-muted small">Ubicaciones</label>
                                <p class="mb-0">
                                    @if(is_array($cotizacion->logoCotizacion->ubicaciones))
                                        @foreach($cotizacion->logoCotizacion->ubicaciones as $ubicacion)
                                            <span class="badge bg-light text-dark me-1">{{ $ubicacion }}</span>
                                        @endforeach
                                    @else
                                        {{ $cotizacion->logoCotizacion->ubicaciones ?? '-' }}
                                    @endif
                                </p>
                            </div>
                            @if($cotizacion->logoCotizacion->observaciones_tecnicas)
                            <div class="col-12 mb-3">
                                <label class="text-muted small">Observaciones Técnicas</label>
                                <p class="mb-0">{{ $cotizacion->logoCotizacion->observaciones_tecnicas }}</p>
                            </div>
                            @endif
                            @if($cotizacion->logoCotizacion->observaciones_generales)
                            <div class="col-12 mb-3">
                                <label class="text-muted small">Observaciones Generales</label>
                                <p class="mb-0">
                                    @if(is_array($cotizacion->logoCotizacion->observaciones_generales))
                                        @foreach($cotizacion->logoCotizacion->observaciones_generales as $obs)
                                            <span class="d-block">• {{ $obs }}</span>
                                        @endforeach
                                    @else
                                        {{ $cotizacion->logoCotizacion->observaciones_generales }}
                                    @endif
                                </p>
                            </div>
                            @endif
                        </div>

                        <!-- Imágenes del Logo -->
                        @if($cotizacion->logoCotizacion->fotos && count($cotizacion->logoCotizacion->fotos) > 0)
                        <div class="mt-4">
                            <h6 class="mb-3"><i class="fas fa-images me-2"></i>Imágenes del Logo</h6>
                            <div class="row g-3">
                                @foreach($cotizacion->logoCotizacion->fotos as $foto)
                                <div class="col-md-4 col-sm-6">
                                    <div class="card border-0 shadow-sm">
                                        <img src="{{ Storage::url($foto->ruta_webp ?? $foto->ruta_original) }}" 
                                             class="card-img-top" 
                                             alt="Logo"
                                             style="height: 200px; object-fit: cover; cursor: pointer;"
                                             onclick="verImagenCompleta('{{ Storage::url($foto->ruta_webp ?? $foto->ruta_original) }}')">
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Esta cotización no tiene información de logo disponible.
                        </div>
                    @endif
                </div>
            </div>
        </div>
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
