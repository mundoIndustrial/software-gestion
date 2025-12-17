@extends('layouts.asesores')

@section('title', 'Detalle del Pedido')
@section('page-title', 'Pedido #' . $pedidoData->pedido)

@php
    use Illuminate\Support\Facades\DB;
@endphp

@section('content')
<div class="pedido-detail-container">
    <!-- Acciones -->
    <div class="detail-actions">
        <a href="{{ route('asesores.pedidos.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Volver
        </a>
        <div class="action-group">
            <a href="{{ route('asesores.pedidos.edit', $pedidoData->pedido) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i>
                Editar
            </a>
            <button type="button" class="btn btn-danger" id="btnEliminar" data-pedido="{{ $pedidoData->pedido }}">
                <i class="fas fa-trash"></i>
                Eliminar
            </button>
        </div>
    </div>

    <!-- Información General -->
    <div class="detail-section">
        <h2 class="section-title">
            <i class="fas fa-info-circle"></i>
            Información General
        </h2>
        <div class="info-grid">
            <div class="info-item">
                <label>Número de Pedido:</label>
                <span class="value">#{{ $pedidoData->pedido }}</span>
            </div>
            <div class="info-item">
                <label>Cliente:</label>
                <span class="value">{{ $pedidoData->cliente }}</span>
            </div>
            <div class="info-item">
                <label>Asesora:</label>
                <span class="value">{{ $pedidoData->asesora }}</span>
            </div>
            <div class="info-item">
                <label>Forma de Pago:</label>
                <span class="value">{{ $pedidoData->forma_de_pago ?? 'No especificada' }}</span>
            </div>
            <div class="info-item">
                <label>Estado:</label>
                <span class="badge badge-{{ 
                    $pedidoData->estado == 'Entregado' ? 'success' : 
                    ($pedidoData->estado == 'En Ejecución' ? 'warning' : 
                    ($pedidoData->estado == 'Anulada' ? 'danger' : 'secondary'))
                }}">
                    {{ $pedidoData->estado ?? 'Sin estado' }}
                </span>
            </div>
            <div class="info-item">
                <label>Área Actual:</label>
                <span class="badge badge-light">{{ $pedidoData->area ?? 'Sin área' }}</span>
            </div>
            <div class="info-item">
                <label>Fecha de Creación:</label>
                <span class="value">
                    {{ $pedidoData->fecha_de_creacion_de_orden ? \Carbon\Carbon::parse($pedidoData->fecha_de_creacion_de_orden)->format('d/m/Y') : '-' }}
                </span>
            </div>
            <div class="info-item">
                <label>Cantidad Total:</label>
                <span class="value">{{ $pedidoData->cantidad ?? 0 }} unidades</span>
            </div>
            @if($pedidoData->descripcion)
                <div class="info-item full-width">
                    <label>Descripción:</label>
                    <p class="value">{{ $pedidoData->descripcion }}</p>
                </div>
            @endif
            @if($pedidoData->novedades)
                <div class="info-item full-width">
                    <label>Novedades:</label>
                    <p class="value">{{ $pedidoData->novedades }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Productos del Pedido -->
    <div class="detail-section">
        <h2 class="section-title">
            <i class="fas fa-box"></i>
            Productos del Pedido ({{ $pedidoData->productos->count() }})
        </h2>
        
        @if($pedidoData->productos->count() > 0)
            <div class="productos-list">
                @foreach($pedidoData->productos as $index => $producto)
                    <div class="producto-card">
                        <div class="producto-header">
                            <h3>{{ $index + 1 }}. {{ $producto->nombre_producto }}</h3>
                            @if($producto->talla)
                                <span class="badge badge-info">Talla: {{ $producto->talla }}</span>
                            @endif
                        </div>
                        <div class="producto-body">
                            @if($producto->descripcion)
                                <div class="producto-descripcion">
                                    <label>Descripción:</label>
                                    <p>{{ $producto->descripcion }}</p>
                                </div>
                            @endif
                            
                            <!-- VARIACIONES -->
                            @if($producto->color_id || $producto->tela_id || $producto->tipo_manga_id || $producto->tipo_broche_id)
                                <div class="producto-variaciones" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                                    <h4 style="margin: 0 0 0.5rem 0; color: #0066cc; font-size: 0.95rem; font-weight: 600;">
                                        <i class="fas fa-sliders-h"></i> Variaciones
                                    </h4>
                                    
                                    @if($producto->color_id && $producto->color)
                                        <div style="margin-bottom: 0.5rem;">
                                            <span style="font-weight: 600; color: #0066cc;">Color:</span>
                                            <span>{{ $producto->color->nombre }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($producto->tela_id && $producto->tela)
                                        <div style="margin-bottom: 0.5rem;">
                                            <span style="font-weight: 600; color: #0066cc;">Tela:</span>
                                            <span>{{ $producto->tela->nombre }}</span>
                                            @if($producto->tela->referencia)
                                                <span style="color: #64748b; font-size: 0.85rem;">(Ref: {{ $producto->tela->referencia }})</span>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    @if($producto->tipo_manga_id && $producto->tipoManga)
                                        <div style="margin-bottom: 0.5rem;">
                                            <span style="font-weight: 600; color: #0066cc;">Manga:</span>
                                            <span>{{ $producto->tipoManga->nombre }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($producto->tipo_broche_id && $producto->tipoBroche)
                                        <div style="margin-bottom: 0.5rem;">
                                            <span style="font-weight: 600; color: #0066cc;">Broche:</span>
                                            <span>{{ $producto->tipoBroche->nombre }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($producto->tiene_bolsillos)
                                        <div style="margin-bottom: 0.5rem;">
                                            <span style="font-weight: 600; color: #0066cc;">Bolsillos:</span>
                                            <span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem;">Sí</span>
                                        </div>
                                    @endif
                                    
                                    @if($producto->tiene_reflectivo)
                                        <div style="margin-bottom: 0.5rem;">
                                            <span style="font-weight: 600; color: #0066cc;">Reflectivo:</span>
                                            <span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem;">Sí</span>
                                        </div>
                                    @endif
                                    
                                    @if($producto->descripcion_variaciones)
                                        <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #eee;">
                                            <span style="font-weight: 600; color: #0066cc;">Observaciones:</span>
                                            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.9rem;">{{ $producto->descripcion_variaciones }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            
                            <div class="producto-details">
                                <div class="detail-item">
                                    <label>Cantidad:</label>
                                    <span>{{ $producto->cantidad }} unidades</span>
                                </div>
                                @if($producto->precio_unitario)
                                    <div class="detail-item">
                                        <label>Precio Unitario:</label>
                                        <span>${{ number_format($producto->precio_unitario, 2) }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Subtotal:</label>
                                        <strong>${{ number_format($producto->subtotal, 2) }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Resumen de Totales -->
            @if($pedidoData->productos->whereNotNull('subtotal')->count() > 0)
                <div class="totales-summary">
                    <div class="total-item">
                        <span>Total General:</span>
                        <strong>${{ number_format($pedidoData->productos->sum('subtotal'), 2) }}</strong>
                    </div>
                </div>
            @endif
        @else
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No hay productos en este pedido</p>
            </div>
        @endif
    </div>

    <!-- GALERÍA DE FOTOS ORGANIZADAS POR PRENDA -->
    <div class="detail-section">
        <h2 class="section-title">
            <i class="fas fa-images"></i>
            Galería de Fotos
        </h2>
        
        @php
            // Obtener número de pedido (compatible tanto con modelo como con array)
            $numeroPedido = $pedidoData->pedido ?? $pedidoData['pedido'] ?? null;
            
            // Obtener todas las prendas del pedido
            $prendas = DB::table('prendas_pedido')
                ->where('numero_pedido', $numeroPedido)
                ->get();
            
            \Log::info('📸 Vista show.blade: Consultando prendas', [
                'numero_pedido' => $numeroPedido,
                'total_prendas' => $prendas->count(),
            ]);
        @endphp

        @if($prendas->count() > 0)
            @foreach($prendas as $indexPrenda => $prenda)
                @php
                    // Obtener fotos de esta prenda
                    $fotosProenda = DB::table('prenda_fotos_pedido')
                        ->where('prenda_pedido_id', $prenda->id)
                        ->orderBy('orden')
                        ->get();
                    
                    // Obtener fotos de telas de esta prenda
                    $fotosTelas = DB::table('prenda_fotos_tela_pedido')
                        ->where('prenda_pedido_id', $prenda->id)
                        ->orderBy('orden')
                        ->get();
                    
                    // Obtener fotos de logos de esta prenda
                    $fotosLogos = DB::table('prenda_fotos_logo_pedido')
                        ->where('prenda_pedido_id', $prenda->id)
                        ->orderBy('orden')
                        ->get();
                @endphp

                <!-- FOTOS DE LA PRENDA -->
                @if($fotosProenda->count() > 0)
                    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0066cc;">
                        <h3 style="margin: 0 0 1rem 0; color: #0066cc; font-size: 1.1rem; font-weight: 600;">
                            Prenda {{ $indexPrenda + 1 }}: {{ $prenda->nombre_prenda }}
                        </h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                            @foreach($fotosProenda as $foto)
                                @php
                                    $urlFoto = $foto->ruta_webp ?? $foto->ruta_original;
                                @endphp
                                <div style="position: relative; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); background: white;">
                                    <img src="{{ $urlFoto }}" 
                                         alt="Foto prenda" 
                                         style="width: 100%; height: 180px; object-fit: cover; cursor: pointer; transition: transform 0.2s;"
                                         onclick="abrirImagenZoom(this.src, 'Prenda {{ $indexPrenda + 1 }}')"
                                         onmouseover="this.style.transform='scale(1.05)'"
                                         onmouseout="this.style.transform='scale(1)'">
                                    <div style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.6); color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                                        {{ $loop->iteration }}/{{ $fotosProenda->count() }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- FOTOS DE TELAS (Agrupadas por tela) -->
                @if($fotosTelas->count() > 0)
                    @php
                        $telasPorId = $fotosTelas->groupBy('tela_id');
                    @endphp
                    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
                        <h3 style="margin: 0 0 1rem 0; color: #10b981; font-size: 1.1rem; font-weight: 600;">
                            Telas - Prenda {{ $indexPrenda + 1 }}
                        </h3>
                        
                        @foreach($telasPorId as $telaId => $fotosTelaGrupo)
                            @php
                                $primeraFoto = $fotosTelaGrupo->first();
                                $telaNombre = DB::table('telas_prenda')
                                    ->where('id', $telaId)
                                    ->value('nombre') ?? 'Tela #' . $telaId;
                            @endphp
                            <div style="margin-bottom: 1rem;">
                                <p style="margin: 0 0 0.75rem 0; color: #047857; font-weight: 600; font-size: 0.95rem;">
                                    {{ $telaNombre }}
                                </p>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.75rem;">
                                    @foreach($fotosTelaGrupo as $foto)
                                        @php
                                            $urlFoto = $foto->ruta_webp ?? $foto->ruta_original;
                                        @endphp
                                        <div style="border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); background: white;">
                                            <img src="{{ $urlFoto }}" 
                                                 alt="Foto tela" 
                                                 style="width: 100%; height: 120px; object-fit: cover; cursor: pointer; transition: transform 0.2s;"
                                                 onclick="abrirImagenZoom(this.src, '{{ $telaNombre }}')"
                                                 onmouseover="this.style.transform='scale(1.05)'"
                                                 onmouseout="this.style.transform='scale(1)'">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- FOTOS DE LOGOS/BORDADOS -->
                @if($fotosLogos->count() > 0)
                    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        <h3 style="margin: 0 0 1rem 0; color: #d97706; font-size: 1.1rem; font-weight: 600;">
                            Bordados - Prenda {{ $indexPrenda + 1 }}
                        </h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem;">
                            @foreach($fotosLogos as $foto)
                                @php
                                    $urlFoto = $foto->ruta_webp ?? $foto->ruta_original;
                                @endphp
                                <div style="border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); background: white;">
                                    <img src="{{ $urlFoto }}" 
                                         alt="Foto logo/bordado" 
                                         style="width: 100%; height: 120px; object-fit: cover; cursor: pointer; transition: transform 0.2s;"
                                         onclick="abrirImagenZoom(this.src, 'Bordado Prenda {{ $indexPrenda + 1 }}')"
                                         onmouseover="this.style.transform='scale(1.05)'"
                                         onmouseout="this.style.transform='scale(1)'">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @else
            <div class="empty-state">
                <i class="fas fa-image"></i>
                <p>No hay fotos cargadas en este pedido</p>
            </div>
        @endif
    </div>
@endsection

<!-- MODAL DE ZOOM PARA IMÁGENES -->
<div id="modal-imagen-zoom" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
    <div style="position: relative; max-width: 90vw; max-height: 90vh;">
        <img id="imagen-zoom-src" src="" alt="Zoom" style="max-width: 100%; max-height: 100%; border-radius: 8px;">
        <button onclick="cerrarImagenZoom()" style="position: absolute; top: -40px; right: 0; background: white; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; font-weight: bold;">×</button>
        <div id="titulo-imagen-zoom" style="color: white; text-align: center; margin-top: 1rem; font-size: 0.95rem;"></div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
<style>
    #modal-imagen-zoom {
        display: none !important;
    }
    #modal-imagen-zoom.activo {
        display: flex !important;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/asesores/pedidos-list.js') }}"></script>
<script>
    // Función para abrir imagen en zoom
    function abrirImagenZoom(src, titulo = '') {
        const modal = document.getElementById('modal-imagen-zoom');
        const imagen = document.getElementById('imagen-zoom-src');
        const tituloDiv = document.getElementById('titulo-imagen-zoom');
        
        imagen.src = src;
        tituloDiv.textContent = titulo;
        modal.classList.add('activo');
        
        // Permitir cerrar con ESC
        document.addEventListener('keydown', cerrarConEsc);
    }
    
    // Función para cerrar imagen
    function cerrarImagenZoom() {
        const modal = document.getElementById('modal-imagen-zoom');
        modal.classList.remove('activo');
        document.removeEventListener('keydown', cerrarConEsc);
    }
    
    // Cerrar con ESC
    function cerrarConEsc(event) {
        if (event.key === 'Escape') {
            cerrarImagenZoom();
        }
    }
    
    // Cerrar al hacer click fuera de la imagen
    document.getElementById('modal-imagen-zoom').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarImagenZoom();
        }
    });
</script>
@endpush
