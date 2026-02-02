{{-- Componente: Tabla de Cotizaciones Genérica --}}
@php
    $isBorrador = str_contains($sectionId, 'bor-');
    $headerColor = $isBorrador ? '#3b82f6' : '#1e40af';
    $headerGradient = $isBorrador ? 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)' : 'linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%)';
    $headerBorder = $isBorrador ? '#2563eb' : '#1e3a8a';
    $buttonColor = $isBorrador ? '#3b82f6' : '#1e40af';
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
                                @elseif($column['key'] === 'asesor')
                                    <td style="padding: 12px; color: #333; font-size: 0.9rem;" data-filter-column="asesor">
                                        <span style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 8px;">
                                            {{ $cot->asesor_nombre ?? 'Desconocido' }}
                                        </span>
                                    </td>
                                @elseif($column['key'] === 'tipo')
                                    <td style="padding: 12px;" data-filter-column="tipo">
                                        <span style="background: #e3f2fd; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                            @if($cot->tipo === 'P')
                                                Prenda
                                            @elseif($cot->tipo === 'L')
                                                Logo
                                            @elseif($cot->tipo === 'PL')
                                                Combinada
                                            @elseif($cot->tipo === 'RF')
                                                Reflectivo
                                            @else
                                                {{ $cot->tipo ?? 'N/A' }}
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
                                        <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                        @if($isBorrador)
                                            <a href="{{ route('asesores.cotizaciones.edit-borrador', $cot->id) }}" 
                                                title="Editar Borrador"
                                                style="background: {{ $buttonColor }}; color: white; width: 36px; height: 36px; border-radius: 6px; text-decoration: none; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(30, 64, 175, 0.3);"
                                                onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(30, 64, 175, 0.4)'" 
                                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(30, 64, 175, 0.3)'">
                                                <i class="fas fa-edit" style="font-size: 1rem;"></i>
                                            </a>
                                            <a href="#" onclick="eliminarBorrador({{ $cot->id }}); return false;" 
                                                title="Eliminar Borrador"
                                                style="background: #e74c3c; color: white; width: 36px; height: 36px; border-radius: 6px; text-decoration: none; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(231, 76, 60, 0.3);"
                                                onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(231, 76, 60, 0.4)'" 
                                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(231, 76, 60, 0.3)'">
                                                <i class="fas fa-trash-alt" style="font-size: 1rem;"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('asesores.cotizaciones.show', $cot->id) }}" 
                                                title="Ver Cotización"
                                                style="background: {{ $buttonColor }}; color: white; width: 36px; height: 36px; border-radius: 6px; text-decoration: none; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(30, 64, 175, 0.3);"
                                                onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(30, 64, 175, 0.4)'" 
                                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(30, 64, 175, 0.3)'">
                                                <i class="fas fa-eye" style="font-size: 1rem;"></i>
                                            </a>
                                            
                                            <!-- Botones PDF dinámicos según tipo y relaciones -->
                                            @php
                                                $tienePrendas = $cot->prendas && count($cot->prendas) > 0;
                                                $tieneLogo = \App\Models\LogoCotizacion::where('cotizacion_id', $cot->id)->exists();
                                                $tieneReflectivo = false;
                                                
                                                if ($cot->tipo === 'RF') {
                                                    $tieneReflectivo = true;
                                                } else {
                                                    $tieneReflectivo = \App\Models\ReflectivoCotizacion::where('cotizacion_id', $cot->id)->whereNotNull('prenda_cot_id')->exists();
                                                }
                                                
                                                // Detectar si es COMBINADA (prendas + logo)
                                                $esCombinad = $tienePrendas && $tieneLogo;
                                                
                                                // Construir array de botones SOLO si NO es combinada
                                                $pdfButtons = [];
                                                if (!$esCombinad) {
                                                    if ($tienePrendas) {
                                                        $pdfButtons[] = [
                                                            'tipo' => 'prenda',
                                                            'label' => 'PDF Prenda',
                                                            'icon' => 'fa-file-pdf'
                                                        ];
                                                    }
                                                    if ($tieneReflectivo) {
                                                        $pdfButtons[] = [
                                                            'tipo' => 'reflectivo',
                                                            'label' => 'PDF Reflectivo',
                                                            'icon' => 'fa-file-pdf'
                                                        ];
                                                    }
                                                    if ($tieneLogo) {
                                                        $pdfButtons[] = [
                                                            'tipo' => 'logo',
                                                            'label' => 'PDF Logo',
                                                            'icon' => 'fa-file-pdf'
                                                        ];
                                                    }
                                                }
                                            @endphp
                                            
                                            @if($esCombinad)
                                                <!-- Cotización COMBINADA: Descargar directamente sin menú -->
                                                <button onclick="abrirPDFEnPestana({{ $cot->id }}, 'combinada')" 
                                                    title="Descargar PDF Combinada"
                                                    style="background: #10b981; color: white; width: 36px; height: 36px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);"
                                                    onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.4)'" 
                                                    onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(16, 185, 129, 0.3)'">
                                                    <i class="fas fa-file-pdf" style="font-size: 1rem;"></i>
                                                </button>
                                            @elseif(count($pdfButtons) > 0)
                                                @if(count($pdfButtons) === 1)
                                                    <!-- Un solo botón PDF -->
                                                    @php $btn = $pdfButtons[0]; @endphp
                                                    <button onclick="abrirPDFEnPestana({{ $cot->id }}, '{{ $btn['tipo'] }}')" 
                                                        title="Descargar {{ $btn['label'] }}"
                                                        style="background: #10b981; color: white; width: 36px; height: 36px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);"
                                                        onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.4)'" 
                                                        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(16, 185, 129, 0.3)'">
                                                        <i class="fas {{ $btn['icon'] }}" style="font-size: 1rem;"></i>
                                                    </button>
                                                @else
                                                    <!-- Múltiples botones PDF con menú emergente -->
                                                    <button class="pdf-menu-btn" data-cot-id="{{ $cot->id }}" data-tipo="multiple" 
                                                        title="Descargar PDF"
                                                        style="background: #10b981; color: white; width: 36px; height: 36px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);"
                                                        onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.4)'" 
                                                        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(16, 185, 129, 0.3)'">
                                                        <i class="fas fa-file-pdf" style="font-size: 1rem;"></i>
                                                    </button>
                                                    <!-- Datos para generar menú dinámicamente -->
                                                    <script type="application/json" class="pdf-buttons-data" data-cot-id="{{ $cot->id }}">
                                                        {!! json_encode($pdfButtons) !!}
                                                    </script>
                                                @endif
                                            @endif
                                            
                                            @if($cot->estado !== 'Anulada')
                                            <a href="#" onclick="confirmarAnularCotizacion({{ $cot->id }}, '{{ $cot->numero_cotizacion }}'); return false;" 
                                                title="Anular Cotización"
                                                style="background: #f59e0b; color: white; width: 36px; height: 36px; border-radius: 6px; text-decoration: none; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);"
                                                onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(245, 158, 11, 0.4)'" 
                                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(245, 158, 11, 0.3)'">
                                                <i class="fas fa-ban" style="font-size: 1rem;"></i>
                                            </a>
                                            @endif
                                        @endif
                                        </div>
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

{{-- Modales de Filtro --}}
<div id="filter-modals-container">
    @foreach($columns as $column)
        @if($column['filterable'] ?? false)
            <div id="filter-modal-{{ $column['key'] }}" class="filter-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center;">
                <div class="filter-modal-content" style="background: white; border-radius: 12px; padding: 0; width: 90%; max-width: 450px; max-height: 85vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
                    {{-- Header --}}
                    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; color: white; font-weight: 600;">Filtrar por {{ $column['label'] }}</h3>
                            <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: rgba(255,255,255,0.8);">Selecciona uno o más valores</p>
                        </div>
                        <button onclick="cerrarFiltro('{{ $column['key'] }}')" style="background: rgba(255,255,255,0.2); border: none; font-size: 1.8rem; cursor: pointer; color: white; width: 40px; height: 40px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">×</button>
                    </div>
                    
                    {{-- Content --}}
                    <div style="flex: 1; overflow-y: auto; padding: 20px;">
                        {{-- Botones de selección rápida --}}
                        <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                            <button onclick="seleccionarTodos('{{ $column['key'] }}')" style="flex: 1; padding: 8px 12px; background: #e0e7ff; color: #1e40af; border: 1px solid #c7d2fe; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 0.85rem; transition: all 0.2s;">
                                <i class="fas fa-check-double" style="margin-right: 4px;"></i>Todos
                            </button>
                            <button onclick="deseleccionarTodos('{{ $column['key'] }}')" style="flex: 1; padding: 8px 12px; background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 0.85rem; transition: all 0.2s;">
                                <i class="fas fa-times" style="margin-right: 4px;"></i>Ninguno
                            </button>
                        </div>
                        
                        <div class="filter-checkbox-group"></div>
                    </div>
                    
                    {{-- Footer --}}
                    <div style="background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 16px 20px; display: flex; gap: 12px; justify-content: flex-end;">
                        <button onclick="limpiarFiltroColumna('{{ $column['key'] }}')" style="padding: 10px 20px; border: 1px solid #d1d5db; background: white; border-radius: 6px; cursor: pointer; color: #374151; font-weight: 500; transition: all 0.2s; font-size: 0.95rem;">
                            <i class="fas fa-redo" style="margin-right: 6px;"></i>Limpiar
                        </button>
                        <button onclick="aplicarFiltroColumna('{{ $column['key'] }}')" style="padding: 10px 24px; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s; font-size: 0.95rem;">
                            <i class="fas fa-check" style="margin-right: 6px;"></i>Aplicar
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>

<style>
    .filter-modal.active {
        display: flex !important;
    }
    
    .filter-checkbox {
        display: flex;
        align-items: center;
        padding: 12px;
        gap: 12px;
        border-radius: 6px;
        transition: background 0.2s;
        margin-bottom: 6px;
    }
    
    .filter-checkbox:hover {
        background: #f3f4f6;
    }
    
    .filter-checkbox input[type="checkbox"] {
        cursor: pointer;
        width: 18px;
        height: 18px;
        accent-color: #1e40af;
    }
    
    .filter-checkbox label {
        cursor: pointer;
        margin: 0;
        flex: 1;
        color: #374151;
        font-weight: 500;
        user-select: none;
    }
    
    .filter-search-box {
        margin-bottom: 16px;
    }
    
    .filter-search-input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.95rem;
        transition: border-color 0.2s;
    }
    
    .filter-search-input:focus {
        outline: none;
        border-color: #1e40af;
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }
    
    .filter-modal-content button:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }
</style>
