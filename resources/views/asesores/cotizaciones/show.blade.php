@extends('asesores.layout')

@section('content')
<style>
    * {
        --primary: #1e40af;
        --secondary: #0ea5e9;
        --accent: #06b6d4;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
    }

    .page-wrapper {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .cotizacion-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        padding: 2.5rem;
        margin-bottom: 2rem;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(30, 64, 175, 0.15);
        position: relative;
        overflow: hidden;
    }

    .cotizacion-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 500px;
        height: 500px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        pointer-events: none;
    }

    .cotizacion-header h1 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        position: relative;
        z-index: 1;
    }

    .cotizacion-header p {
        font-size: 0.95rem;
        opacity: 0.95;
        position: relative;
        z-index: 1;
    }

    .cotizacion-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .info-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        border-top: 4px solid var(--secondary);
        transition: all 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .info-card label {
        font-size: 0.7rem;
        color: #64748b;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0.75rem;
        display: block;
    }

    .info-card .value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
    }

    .section-title {
        font-size: 1.4rem;
        font-weight: 800;
        color: #1e293b;
        margin-top: 2.5rem;
        margin-bottom: 1.75rem;
        padding-bottom: 1rem;
        border-bottom: 3px solid var(--secondary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .section-title i {
        color: var(--secondary);
        font-size: 1.4rem;
    }

    .productos-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2.5rem;
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .productos-table thead {
        background: var(--primary);
        color: white;
    }

    .productos-table th {
        padding: 1rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid var(--secondary);
    }

    .productos-table td {
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.95rem;
    }

    .productos-table tbody tr:hover {
        background: #f8fafc;
    }

    .productos-table tbody tr:last-child td {
        border-bottom: 2px solid var(--primary);
    }

    .producto-nombre {
        font-weight: 700;
        color: var(--primary);
    }

    .producto-cantidad {
        text-align: center;
        font-weight: 600;
        color: var(--secondary);
    }

    .producto-descripcion {
        color: #64748b;
        font-size: 0.9rem;
        max-width: 300px;
        word-wrap: break-word;
    }

    .tecnicas-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 2.5rem;
    }

    .tecnica-badge {
        background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
        color: white;
        padding: 0.5rem 1.1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
    }

    .imagenes-container {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        margin-bottom: 2.5rem;
    }

    .imagenes-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 1rem;
    }

    .imagen-item {
        position: relative;
        overflow: hidden;
        border-radius: 6px;
        width: 120px;
        height: 120px;
        background: #f1f5f9;
        border: 2px solid #e2e8f0;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        flex-shrink: 0;
    }

    .imagen-item:hover {
        border-color: var(--secondary);
        transform: scale(1.05);
        box-shadow: 0 8px 16px rgba(14, 165, 233, 0.2);
    }

    .imagen-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .imagen-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        background: #f1f5f9;
        color: #cbd5e1;
        font-size: 1.5rem;
    }

    .observaciones-box {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        border-left: 4px solid var(--secondary);
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .observaciones-box label {
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 0.75rem;
        display: block;
        font-size: 0.9rem;
    }

    .observaciones-box p {
        color: #475569;
        margin: 0;
        line-height: 1.7;
        font-size: 0.95rem;
    }

    .estado-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.85rem;
    }

    .estado-borrador {
        background: #fef3c7;
        color: #92400e;
    }

    .estado-enviada {
        background: #cffafe;
        color: #164e63;
    }

    .estado-aceptada {
        background: #dcfce7;
        color: #166534;
    }

    .estado-rechazada {
        background: #fee2e2;
        color: #7f1d1d;
    }

    .footer-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2.5rem;
        padding-top: 2rem;
        border-top: 2px solid #e2e8f0;
    }

    .btn-custom {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 700;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-volver {
        background: #64748b;
        color: white;
    }

    .btn-volver:hover {
        background: #475569;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }

    .btn-editar {
        background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
        color: white;
    }

    .btn-editar:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
    }

    .sin-contenido {
        text-align: center;
        padding: 3rem 2rem;
        color: #94a3b8;
        font-style: italic;
        font-size: 0.95rem;
    }

    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }
</style>

<div class="page-wrapper">

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="cotizacion-header">
        <h1>
            <i class="fas fa-file-invoice"></i> Detalle de Cotización
        </h1>
        <p style="margin: 0; opacity: 0.9;">Cotización #{{ $cotizacion->id }}</p>
    </div>

    <!-- Información Principal -->
    <div class="cotizacion-info">
        <div class="info-card">
            <label><i class="fas fa-user"></i> Cliente</label>
            <div class="value">{{ $cotizacion->cliente }}</div>
        </div>
        <div class="info-card">
            <label><i class="fas fa-tag"></i> Estado</label>
            <div class="value">
                <span class="estado-badge estado-{{ $cotizacion->es_borrador ? 'borrador' : ($cotizacion->estado === 'aceptada' ? 'aceptada' : ($cotizacion->estado === 'rechazada' ? 'rechazada' : 'enviada')) }}">
                    {{ $cotizacion->es_borrador ? 'Borrador' : ucfirst($cotizacion->estado) }}
                </span>
            </div>
        </div>
        <div class="info-card">
            <label><i class="fas fa-calendar"></i> Creada</label>
            <div class="value" style="font-size: 1rem;">{{ $cotizacion->created_at->format('d/m/Y H:i') }}</div>
        </div>
        <div class="info-card">
            <label><i class="fas fa-sync"></i> Actualizada</label>
            <div class="value" style="font-size: 1rem;">{{ $cotizacion->updated_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <!-- Productos -->
    <div class="section-title">
        <i class="fas fa-box"></i> Productos
    </div>
    @if($cotizacion->productos && count($cotizacion->productos) > 0)
        <table class="productos-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Producto</th>
                    <th style="width: 40%;">Descripción</th>
                    <th style="width: 15%; text-align: center;">Cantidad</th>
                    <th style="width: 25%; text-align: center;">Imagen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cotizacion->productos as $index => $producto)
                    <tr>
                        <td>
                            <div class="producto-nombre">{{ $producto['nombre_producto'] ?? 'Sin nombre' }}</div>
                        </td>
                        <td>
                            <div class="producto-descripcion">
                                {{ $producto['descripcion'] ?? '-' }}
                            </div>
                        </td>
                        <td>
                            <div class="producto-cantidad">
                                {{ $producto['cantidad'] ?? 1 }}
                            </div>
                        </td>
                        <td style="text-align: center;">
                            @php
                                $imagenProducto = $imagenes[$index] ?? null;
                            @endphp
                            @if($imagenProducto)
                                <img src="{{ $imagenProducto }}" alt="Producto" 
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer;" 
                                     onclick="abrirModalImagen('{{ $imagenProducto }}', '{{ $producto['nombre_producto'] ?? 'Producto' }}')">
                            @else
                                <div style="width: 60px; height: 60px; background: #f1f5f9; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #cbd5e1;">
                                    <i class="fas fa-image"></i>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="sin-contenido">
            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
            Sin productos agregados
        </div>
    @endif

    <!-- Técnicas -->
    @if($cotizacion->tecnicas && count($cotizacion->tecnicas) > 0)
        <div class="section-title">
            <i class="fas fa-tools"></i> Técnicas
        </div>
        <div class="tecnicas-list">
            @foreach($cotizacion->tecnicas as $tecnica)
                <span class="tecnica-badge">{{ $tecnica }}</span>
            @endforeach
        </div>
    @endif

    <!-- Imágenes -->
    @php
        $imagenes = [];
        if ($cotizacion->imagenes) {
            if (is_array($cotizacion->imagenes)) {
                $imagenes = $cotizacion->imagenes;
            } else {
                $imagenes = json_decode($cotizacion->imagenes, true) ?? [];
            }
        }
    @endphp
    
    <div class="imagenes-container">
        <div class="section-title" style="margin-top: 0; margin-bottom: 0;">
            <i class="fas fa-images"></i> Imágenes
        </div>
        
        @if(count($imagenes) > 0)
            <div class="imagenes-gallery">
                @foreach($imagenes as $imagen)
                    <div class="imagen-item">
                        @if(is_string($imagen))
                            <img src="{{ $imagen }}" alt="Cotización" loading="lazy">
                        @elseif(isset($imagen['url']))
                            <img src="{{ $imagen['url'] }}" alt="Cotización" loading="lazy">
                        @else
                            <div class="imagen-placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p style="color: #94a3b8; font-style: italic; margin-top: 1rem;">Sin imágenes agregadas</p>
        @endif
        
        <!-- Formulario para subir imágenes -->
        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
            <form id="form-imagenes" enctype="multipart/form-data" style="display: flex; gap: 1rem; align-items: flex-end;">
                @csrf
                <div style="flex: 1;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--primary);">Subir Imágenes</label>
                    <input type="file" name="imagenes[]" multiple accept="image/*" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; width: 100%;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--primary);">Tipo</label>
                    <select name="tipo" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px;">
                        <option value="general">General</option>
                        <option value="bordado">Bordado</option>
                        <option value="estampado">Estampado</option>
                        <option value="tela">Tela</option>
                        <option value="prenda">Prenda</option>
                    </select>
                </div>
                <button type="submit" class="btn-custom btn-editar" style="margin: 0;">
                    <i class="fas fa-upload"></i> Subir
                </button>
            </form>
        </div>
    </div>

    <!-- Modal para ver imagen -->
    <div id="modal-imagen" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 8px; padding: 2rem; max-width: 600px; max-height: 80vh; overflow: auto; position: relative;">
            <button onclick="cerrarModalImagen()" style="position: absolute; top: 1rem; right: 1rem; background: #e74c3c; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 1.2rem;">
                ✕
            </button>
            <h3 id="modal-titulo" style="margin-top: 0; color: var(--primary);">Imagen</h3>
            <img id="modal-imagen-src" src="" alt="Imagen" style="width: 100%; border-radius: 4px;">
        </div>
    </div>

    <script>
        function abrirModalImagen(src, titulo) {
            document.getElementById('modal-imagen-src').src = src;
            document.getElementById('modal-titulo').textContent = titulo;
            document.getElementById('modal-imagen').style.display = 'flex';
        }

        function cerrarModalImagen() {
            document.getElementById('modal-imagen').style.display = 'none';
        }

        // Cerrar modal al hacer click fuera
        document.getElementById('modal-imagen')?.addEventListener('click', (e) => {
            if (e.target.id === 'modal-imagen') {
                cerrarModalImagen();
            }
        });

        // Cerrar modal con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                cerrarModalImagen();
            }
        });

        console.log('Inicializando script de imágenes...');
        
        const formImagenes = document.getElementById('form-imagenes');
        console.log('Formulario encontrado:', formImagenes);
        
        if (formImagenes) {
            formImagenes.addEventListener('submit', async (e) => {
                console.log('Submit del formulario disparado');
                e.preventDefault();
                
                const archivos = document.querySelector('input[name="imagenes[]"]').files;
                console.log('Archivos seleccionados:', archivos.length);
                
                if (archivos.length === 0) {
                    alert('Por favor selecciona al menos una imagen');
                    return;
                }
                
                const formData = new FormData(e.target);
                const cotizacionId = {{ $cotizacion->id }};
                
                console.log('Enviando a:', `/asesores/cotizaciones/${cotizacionId}/imagenes`);
                console.log('FormData keys:', Array.from(formData.keys()));
                
                try {
                    const response = await fetch(`/asesores/cotizaciones/${cotizacionId}/imagenes`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        }
                    });
                    
                    console.log('Response status:', response.status);
                    
                    const data = await response.json();
                    console.log('Response data:', data);
                    
                    if (data.success) {
                        alert('Imágenes subidas correctamente');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al subir imágenes: ' + error.message);
                }
            });
        } else {
            console.warn('Formulario de imágenes no encontrado');
        }
    </script>

    <!-- Observaciones Técnicas -->
    @if($cotizacion->observaciones_tecnicas)
        <div class="section-title">
            <i class="fas fa-wrench"></i> Observaciones Técnicas
        </div>
        <div class="observaciones-box">
            <p>{{ $cotizacion->observaciones_tecnicas }}</p>
        </div>
    @endif

    <!-- Observaciones Generales -->
    @if($cotizacion->observaciones_generales && count($cotizacion->observaciones_generales) > 0)
        <div class="section-title">
            <i class="fas fa-comment"></i> Observaciones Generales
        </div>
        @foreach($cotizacion->observaciones_generales as $obs)
            <div class="observaciones-box">
                <p>{{ $obs }}</p>
            </div>
        @endforeach
    @endif

    <!-- Acciones -->
    <div class="footer-actions">
        <a href="{{ route('asesores.cotizaciones.index') }}" class="btn-custom btn-volver">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        @if($cotizacion->es_borrador)
            <a href="{{ route('asesores.cotizaciones.edit-borrador', $cotizacion->id) }}" class="btn-custom btn-editar">
                <i class="fas fa-edit"></i> Editar Borrador
            </a>
        @endif
    </div>
</div>
</div>
@endsection
