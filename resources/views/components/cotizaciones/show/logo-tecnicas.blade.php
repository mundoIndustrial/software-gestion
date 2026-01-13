{{-- Logo Tecnicas --}}
@php
    // Obtener técnicas de las prendas técnicas (nuevo sistema)
    $prendas_tecnicas = $logo->prendas ?? [];
    $tecnicas = [];
    foreach ($prendas_tecnicas as $prenda_tecnica) {
        if ($prenda_tecnica->tipo_logo) {
            $tecnicas[] = [
                'tipo' => $prenda_tecnica->tipo_logo->nombre ?? 'Desconocido',
                'prenda' => $prenda_tecnica->prendaCot?->nombre_producto ?? 'Prenda sin nombre',
                'observaciones' => $prenda_tecnica->observaciones
            ];
        }
    }
@endphp
@if(!empty($tecnicas) && count($tecnicas) > 0)
    <div style="
        font-size: 1.4rem;
        font-weight: 800;
        color: #1e293b;
        margin-top: 2rem;
        margin-bottom: 1.75rem;
        padding-bottom: 1rem;
        border-bottom: 3px solid #0ea5e9;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    ">
        <i class="fas fa-tools" style="color: #0ea5e9; font-size: 1.4rem;"></i> Técnicas Disponibles
    </div>
    <table style="
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2.5rem;
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    ">
        <thead style="background: #1e40af; color: white;">
            <tr>
                <th style="padding: 1rem; text-align: left; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0ea5e9; width: 70%;">Técnica</th>
                <th style="padding: 1rem; text-align: left; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0ea5e9; width: 30%;">Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tecnicas as $index => $tecnica)
                <tr style="border-bottom: 1px solid #e2e8f0;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                    <td style="padding: 1.2rem; font-size: 1.05rem;">
                        <div style="color: #64748b; font-size: 0.9rem; line-height: 1.6;">
                            <p style="margin: 0; color: #475569; font-size: 0.95rem;">
                                @if(is_array($tecnica))
                                    {{ implode(', ', $tecnica) ?? '-' }}
                                @else
                                    {{ $tecnica ?? '-' }}
                                @endif
                            </p>
                        </div>
                    </td>
                    <td style="padding: 1.2rem; font-size: 1.05rem;">
                        @if($logo->observaciones_tecnicas && $index === 0)
                            <div style="color: #64748b; font-size: 0.9rem; line-height: 1.6;">
                                <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                    @if(is_array($logo->observaciones_tecnicas))
                                        {{ implode(', ', $logo->observaciones_tecnicas) ?? '-' }}
                                    @else
                                        {{ $logo->observaciones_tecnicas ?? '-' }}
                                    @endif
                                </p>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@elseif($logo->observaciones_tecnicas)
    <div style="
        font-size: 1.4rem;
        font-weight: 800;
        color: #1e293b;
        margin-top: 2rem;
        margin-bottom: 1.75rem;
        padding-bottom: 1rem;
        border-bottom: 3px solid #0ea5e9;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    ">
        <i class="fas fa-wrench" style="color: #0ea5e9; font-size: 1.4rem;"></i> Observaciones Técnicas
    </div>
    <div style="
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        border-left: 4px solid #0ea5e9;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    ">
        <p style="color: #475569; margin: 0; line-height: 1.7; font-size: 0.95rem;">
            @if(is_array($logo->observaciones_tecnicas))
                {{ implode(', ', $logo->observaciones_tecnicas) ?? '-' }}
            @else
                {{ $logo->observaciones_tecnicas ?? '-' }}
            @endif
        </p>
    </div>
@endif
