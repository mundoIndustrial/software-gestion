<div class="cotizacion-detail">
    <!-- Encabezado Principal -->
    <div class="detail-section">
        <div class="detail-header">UNIFORMES MUNDO INDUSTRIAL</div>
        <div class="detail-row">
            <div class="detail-label">Asesora:</div>
            <div class="detail-value">{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? 'N/A') }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">NIT:</div>
            <div class="detail-value">1.093.738.433-3 Régimen Común</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Cliente:</div>
            <div class="detail-value">{{ $cotizacion->cliente ?? 'N/A' }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Fecha:</div>
            <div class="detail-value">{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y H:i') : 'N/A' }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Cotización:</div>
            <div class="detail-value">COT-{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}</div>
        </div>
    </div>

    <!-- Cotizar Según Indicaciones -->
    @if($cotizacion->cotizar_segun_indicaciones)
    <div class="detail-section">
        <div class="detail-row">
            <div class="detail-label">Cotizar Según Indicaciones:</div>
            <div class="detail-value" style="font-size: 1.1rem; font-weight: 700; color: #1e5ba8;">
                {{ $cotizacion->cotizar_segun_indicaciones }}
            </div>
        </div>
    </div>
    @endif

    <!-- Productos de la Cotización (desde JSON) -->
    @if($cotizacion->productos && count($cotizacion->productos) > 0)
    <div class="detail-section">
        <div class="detail-header">📦 Productos</div>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #1e5ba8;">
                    <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd; font-weight: 700; color: white;">Producto</th>
                    <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd; font-weight: 700; color: white;">Descripción</th>
                    <th style="padding: 0.75rem; text-align: center; border: 1px solid #ddd; font-weight: 700; color: white;">Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cotizacion->productos as $producto)
                <tr style="background-color: #ffffff;">
                    <td style="padding: 0.75rem; border: 1px solid #ddd; color: #333; font-weight: 500;">{{ $producto['nombre_producto'] ?? 'N/A' }}</td>
                    <td style="padding: 0.75rem; border: 1px solid #ddd; color: #333;">{{ $producto['descripcion'] ?? '-' }}</td>
                    <td style="padding: 0.75rem; border: 1px solid #ddd; color: #333; text-align: center;">{{ $producto['cantidad'] ?? 1 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Técnicas -->
    @if($cotizacion->tecnicas && count($cotizacion->tecnicas) > 0)
    <div class="detail-section">
        <div class="detail-header">🎨 Técnicas</div>
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
            @foreach($cotizacion->tecnicas as $tecnica)
            <span style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 700;">
                {{ $tecnica }}
            </span>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Observaciones Técnicas -->
    @if($cotizacion->observaciones_tecnicas)
    <div class="detail-section">
        <div class="detail-header">📝 Observaciones Técnicas</div>
        <div style="padding: 1rem; background-color: #f8f9fa; border-radius: 4px; border-left: 4px solid #1e5ba8;">
            <p style="color: #333; line-height: 1.6; margin: 0;">
                {{ $cotizacion->observaciones_tecnicas }}
            </p>
        </div>
    </div>
    @endif

    <!-- Observaciones Generales -->
    @if($cotizacion->observaciones_generales && count($cotizacion->observaciones_generales) > 0)
    <div class="detail-section">
        <div class="detail-header">💬 Observaciones Generales</div>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @foreach($cotizacion->observaciones_generales as $obs)
            <div style="padding: 0.75rem; background-color: #f8f9fa; border-radius: 4px; border-left: 4px solid #1e5ba8; color: #333;">
                • {{ $obs }}
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Prendas de la Cotización (si existen en relación) -->
    @if($cotizacion->prendas && count($cotizacion->prendas) > 0)
    <div class="detail-section">
        <div class="detail-header">👕 Prendas Detalladas</div>
        @foreach($cotizacion->prendas as $index => $prenda)
        <div style="margin-bottom: 1.5rem; padding: 1rem; background-color: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;">
            <h4 style="color: #1e5ba8; margin: 0 0 1rem 0;">Prenda {{ $index + 1 }}</h4>
            
            <!-- Imagen de la Prenda -->
            @if($prenda->imagen_url)
            <div style="margin-bottom: 1rem; text-align: center;">
                <img src="{{ $prenda->imagen_url }}" alt="Prenda" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            </div>
            @endif

            <!-- Descripción -->
            @if($prenda->descripcion)
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; color: #1e5ba8; display: block; margin-bottom: 0.5rem;">Descripción:</label>
                <p style="color: #333; line-height: 1.6; margin: 0;">{{ $prenda->descripcion }}</p>
            </div>
            @endif

            <!-- Aspectos a Verificar -->
            @if($prenda->aspectos_a_verificar && count($prenda->aspectos_a_verificar) > 0)
            <div>
                <label style="font-weight: 600; color: #1e5ba8; display: block; margin-bottom: 0.5rem;">Aspectos a Verificar:</label>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="background-color: #1e5ba8;">
                            <th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd; font-weight: 700; color: white;">Aspecto</th>
                            <th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd; font-weight: 700; color: white;">Descripción</th>
                            <th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd; font-weight: 700; color: white;">Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prenda->aspectos_a_verificar as $aspecto)
                        <tr style="background-color: #ffffff;">
                            <td style="padding: 0.5rem; border: 1px solid #ddd; color: #333;">{{ $aspecto['aspecto'] ?? '-' }}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; color: #333;">{{ $aspecto['descripcion'] ?? '-' }}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; color: #333;">{{ $aspecto['observacion'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <!-- Botón Cotizar Prendas -->
    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #e0e0e0; text-align: center;">
        <button type="button" onclick="abrirModalCotizarPrendas({{ $cotizacion->id }})" style="padding: 0.75rem 2rem; background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 1rem; box-shadow: 0 4px 12px rgba(30, 91, 168, 0.3); transition: all 0.3s ease;">
            📋 COTIZAR PRENDAS
        </button>
    </div>

</div>
