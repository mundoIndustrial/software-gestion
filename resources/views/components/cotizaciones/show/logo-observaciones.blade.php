{{-- Logo Observaciones Generales --}}
@php
    $observaciones_generales = $logo ? $logo->observaciones_generales : null;
    if (is_string($observaciones_generales)) {
        $observaciones_generales = json_decode($observaciones_generales, true) ?? [];
    }
    $observaciones_generales = is_array($observaciones_generales) ? $observaciones_generales : [];
@endphp
@if(!empty($observaciones_generales) && count($observaciones_generales) > 0)
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
        <i class="fas fa-comment" style="color: #0ea5e9; font-size: 1.4rem;"></i> Observaciones Generales
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
                <th style="width: 70%; padding: 1rem; text-align: left; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0ea5e9;">Observación</th>
                <th style="width: 30%; padding: 1rem; text-align: center; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0ea5e9;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($observaciones_generales as $obs)
                @php
                    $texto = is_array($obs) ? ($obs['texto'] ?? $obs) : $obs;
                    $tipo = is_array($obs) ? ($obs['tipo'] ?? 'texto') : 'texto';
                    $valor = is_array($obs) ? ($obs['valor'] ?? '') : '';
                    
                    if (is_array($texto)) {
                        $texto = implode(', ', $texto);
                    }
                    if (is_array($valor)) {
                        $valor = implode(', ', $valor);
                    }
                @endphp
                <tr style="border-bottom: 1px solid #e2e8f0;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                    <td style="padding: 1.2rem; font-size: 1.05rem;">
                        <div style="color: #64748b; font-size: 0.9rem; line-height: 1.6;">
                            <p style="margin: 0; color: #475569; font-size: 0.95rem;">{{ $texto ?? '-' }}</p>
                        </div>
                    </td>
                    <td style="padding: 1.2rem; font-size: 1.05rem; text-align: center;">
                        @if($tipo === 'checkbox')
                            <span style="color: #2e7d32; font-weight: 600; font-size: 1.5rem; display: inline-block;">✓</span>
                        @elseif(!empty($valor))
                            <span style="background: #f5f5f5; padding: 8px 14px; border-radius: 4px; font-size: 0.9rem; color: #333; font-weight: 600; display: inline-block;">{{ $valor }}</span>
                        @else
                            <span style="color: #999; font-size: 0.9rem; font-style: italic;">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
