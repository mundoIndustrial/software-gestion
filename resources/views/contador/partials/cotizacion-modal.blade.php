<div class="cotizacion-detail">
    <!-- Encabezado Principal -->
    <div class="detail-section">
        <div class="detail-header">UNIFORMES MUNDO INDUSTRIAL</div>
        <div class="detail-row">
            <div class="detail-label">Asesora:</div>
            <div class="detail-value">{{ $cotizacion->asesora }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">NIT:</div>
            <div class="detail-value">1.093.738.433-3 Régimen Común</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Cliente:</div>
            <div class="detail-value">{{ $cotizacion->cliente }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Fecha:</div>
            <div class="detail-value">{{ $cotizacion->fecha->format('d/m/Y') }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Cotización:</div>
            <div class="detail-value">{{ $cotizacion->numero_cotizacion }}</div>
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

    <!-- Prendas de la Cotización -->
    @forelse($cotizacion->prendas as $prenda)
    <div class="detail-section">
        <!-- Imagen de la Prenda -->
        @if($prenda->imagen_url)
        <div style="margin-bottom: 1.5rem; text-align: center;">
            <img src="{{ $prenda->imagen_url }}" alt="Prenda" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        </div>
        @endif

        <!-- Descripción -->
        <div class="detail-header">Descripción</div>
        <div style="padding: 1rem; background-color: #ffffff; border-radius: 4px; border: 1px solid #e0e0e0;">
            <p style="color: #333; font-weight: 500; line-height: 1.6; margin: 0;">
                {{ $prenda->descripcion }}
            </p>
        </div>


        <!-- Aspectos a Verificar -->
        @if($prenda->aspectos_a_verificar)
        <div style="margin-top: 1.5rem;">
            <div class="detail-header">Aspectos a Verificar</div>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #1e5ba8;">
                        <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd; font-weight: 700; color: white;">Aspecto</th>
                        <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd; font-weight: 700; color: white;">Descripción</th>
                        <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd; font-weight: 700; color: white;">Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prenda->aspectos_a_verificar as $aspecto)
                    <tr style="background-color: #ffffff;">
                        <td style="padding: 0.75rem; border: 1px solid #ddd; color: #333; font-weight: 500;">{{ $aspecto['aspecto'] ?? '' }}</td>
                        <td style="padding: 0.75rem; border: 1px solid #ddd; color: #333; font-weight: 500;">{{ $aspecto['descripcion'] ?? '' }}</td>
                        <td style="padding: 0.75rem; border: 1px solid #ddd; color: #333; font-weight: 500;">{{ $aspecto['observacion'] ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @empty
    <div class="detail-section">
        <p style="color: #999; text-align: center;">No hay prendas en esta cotización</p>
    </div>
    @endforelse

    <!-- Botón Cotizar Prendas -->
    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #e0e0e0; text-align: center;">
        <button type="button" onclick="abrirModalCotizarPrendas({{ $cotizacion->id }})" style="padding: 0.75rem 2rem; background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 1rem; box-shadow: 0 4px 12px rgba(30, 91, 168, 0.3); transition: all 0.3s ease;">
            📋 COTIZAR PRENDAS
        </button>
    </div>

</div>
