{{-- Componente: Tabla de Cotizaciones Genérica --}}
@php
    $isBorrador = str_contains($sectionId, 'bor-');
    $headerColor = $isBorrador ? '#f39c12' : '#1e40af';
    $headerGradient = $isBorrador ? 'linear-gradient(135deg, #f39c12 0%, #e67e22 100%)' : 'linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%)';
    $headerBorder = $isBorrador ? '#e67e22' : '#1e3a8a';
    $buttonColor = $isBorrador ? '#f39c12' : '#1e40af';
@endphp

<div id="tab-contenedor-{{ $sectionId }}" class="tab-content">
    @if($cotizaciones->count() > 0)
        <div id="vista-tabla-{{ $sectionId }}" style="overflow-x: auto; margin-bottom: 20px;">
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                <thead style="background: {{ $headerGradient }}; border-bottom: 3px solid {{ $headerBorder }};">
                    <tr>
                        @foreach($columns as $column)
                            <th style="padding: 14px 12px; text-align: {{ $column['align'] ?? 'left' }}; font-weight: 700; color: white; font-size: 0.9rem;">
                                @if(($column['filterable'] ?? false) && !$isBorrador)
                                    <div class="table-header-with-filter">
                                        <span>{{ $column['label'] }}</span>
                                        <button class="filter-funnel-btn" data-filter-column="{{ $column['key'] }}" 
                                            onclick="abrirFiltro('{{ $column['key'] }}')" title="Filtrar por {{ strtolower($column['label']) }}">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                    </div>
                                @else
                                    {{ $column['label'] }}
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($cotizaciones as $cot)
                        <tr style="border-bottom: 1px solid #ecf0f1;">
                            @foreach($columns as $column)
                                @if($column['key'] === 'fecha')
                                    <td style="padding: 12px; color: #666; font-size: 0.9rem;" data-filter-column="fecha">
                                        {{ $cot->created_at->format('d/m/Y') }}
                                    </td>
                                @elseif($column['key'] === 'codigo')
                                    <td style="padding: 12px; color: {{ $buttonColor }}; font-size: 0.9rem; font-weight: 700;" data-filter-column="codigo">
                                        {{ $cot->numero_cotizacion ?? 'Por asignar' }}
                                    </td>
                                @elseif($column['key'] === 'cliente')
                                    <td style="padding: 12px; color: #333; font-size: 0.9rem;" data-filter-column="cliente">
                                        {{ $cot->cliente ?? 'Sin cliente' }}
                                    </td>
                                @elseif($column['key'] === 'tipo')
                                    <td style="padding: 12px;" data-filter-column="tipo">
                                        <span style="background: #e3f2fd; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                            @if($cot->obtenerTipoCotizacion() === 'P')
                                                Prenda
                                            @elseif($cot->obtenerTipoCotizacion() === 'B')
                                                Logo
                                            @elseif($cot->obtenerTipoCotizacion() === 'PB')
                                                Prenda/Bordado
                                            @else
                                                {{ $cot->tipoCotizacion?->nombre ?? 'Sin tipo' }}
                                            @endif
                                        </span>
                                    </td>
                                @elseif($column['key'] === 'estado')
                                    <td style="padding: 12px;" data-filter-column="estado">
                                        @if($isBorrador)
                                            <span style="background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                                Borrador
                                            </span>
                                        @else
                                            <span style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                                @estadoLabelCotizacion($cot->estado)
                                            </span>
                                        @endif
                                    </td>
                                @elseif($column['key'] === 'accion')
                                    <td style="padding: 12px; text-align: center;">
                                        @if($isBorrador)
                                            <a href="{{ route('asesores.cotizaciones.edit-borrador', $cot->id) }}" 
                                                style="background: {{ $buttonColor }}; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600; margin-right: 5px;">
                                                Editar
                                            </a>
                                            <a href="#" onclick="eliminarBorrador({{ $cot->id }}); return false;" 
                                                style="background: #e74c3c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600;">
                                                Eliminar
                                            </a>
                                        @else
                                            <a href="{{ route('asesores.cotizaciones.show', $cot->id) }}" 
                                                style="background: {{ $buttonColor }}; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">
                                                Ver
                                            </a>
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div style="display: flex; justify-content: center; margin-bottom: 30px;">
            {{ $cotizaciones->links('pagination::bootstrap-custom', ['pageName' => $pageParameterName ?? 'page']) }}
        </div>
    @else
        <div style="background: #f0f7ff; border: 2px dashed #3498db; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 30px;">
            <p style="margin: 0; color: #666;">📭 {{ $emptyMessage ?? 'No hay registros' }}</p>
        </div>
    @endif
</div>
