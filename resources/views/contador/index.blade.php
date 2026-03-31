@extends('layouts.contador')

@section('content')

<!-- Event listener para botones "Editar Costos" - DEBE ESTAR AL INICIO -->
<script>
    // Función para registrar event listeners
    function registrarEventListeners() {
        // Event delegation para botones "Editar Costos"
        document.addEventListener('click', function(event) {
            if (event.target.closest('.btn-editar-costos')) {
                const boton = event.target.closest('.btn-editar-costos');
                const cotizacionId = boton.getAttribute('data-cotizacion-id');
                const cliente = boton.getAttribute('data-cliente');
                if (typeof abrirModalCalculoCostos === 'function') {
                    abrirModalCalculoCostos(cotizacionId, cliente);
                } else {
                    alert('Función abrirModalCalculoCostos no disponible');
                }
            }

            const pdfLink = event.target.closest('a.action-menu-item[data-action="pdf"]');
            if (pdfLink) {
                event.preventDefault();
                const url = pdfLink.getAttribute('href');
                if (url) {
                    const urlWithDownload = setUrlQueryParam(url, 'download', '1');
                    window.location.href = urlWithDownload;
                }
            }
        });
    }

    function openPdfModal(url) {
        const modal = document.getElementById('pdfPreviewModal');
        const frame = document.getElementById('pdfPreviewFrame');
        if (!modal || !frame) return;
        window.__pdfPreviewBaseUrl = url;
        const scaleInput = document.getElementById('pdfScaleInput');
        if (scaleInput) {
            scaleInput.value = '1';
        }
        updatePdfScalePreview();
        modal.style.display = 'flex';
    }

    function closePdfModal() {
        const modal = document.getElementById('pdfPreviewModal');
        const frame = document.getElementById('pdfPreviewFrame');
        if (frame) frame.setAttribute('src', '');
        window.__pdfPreviewBaseUrl = null;
        if (modal) modal.style.display = 'none';
    }

    function setUrlQueryParam(url, key, value) {
        try {
            const u = new URL(url, window.location.origin);
            if (value === null || value === undefined || value === '') {
                u.searchParams.delete(key);
            } else {
                u.searchParams.set(key, String(value));
            }
            return u.toString();
        } catch (e) {
            return url;
        }
    }

    function updatePdfScalePreview() {
        const baseUrl = window.__pdfPreviewBaseUrl;
        const frame = document.getElementById('pdfPreviewFrame');
        if (!baseUrl || !frame) return;

        const scaleInput = document.getElementById('pdfScaleInput');
        const scaleLabel = document.getElementById('pdfScaleLabel');
        const scale = scaleInput ? parseFloat(scaleInput.value || '1') : 1;

        if (scaleLabel) {
            scaleLabel.textContent = Math.round(scale * 100) + '%';
        }

        const url = setUrlQueryParam(baseUrl, 'scale', scale);
        frame.setAttribute('src', url);
    }

    window.openPdfModal = openPdfModal;
    window.closePdfModal = closePdfModal;
    window.updatePdfScalePreview = updatePdfScalePreview;

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('pdfPreviewModal');
            if (modal && modal.style.display === 'flex') {
                closePdfModal();
            }
        }
    });
    
    // Registrar listeners al cargar
    registrarEventListeners();
    
    // Re-registrar cuando se recargue la tabla
    document.addEventListener('tablaPendientesRecargada', function() {
        registrarEventListeners();
    });
</script>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contador/tabla-index.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/realtime-cotizaciones.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/contador/cotizacion-tabs.css') }}?v={{ time() }}">
@endpush

<script>
// Función global para toggle del dropdown de Ver
window.toggleViewDropdown = function(button) {
    const dropdown = button.closest('div').querySelector('.view-dropdown');
    const allDropdowns = document.querySelectorAll('.view-dropdown');
    
    allDropdowns.forEach(d => {
        if (d !== dropdown) {
            d.style.display = 'none';
        }
    });
    
    if (dropdown.style.display === 'none' || dropdown.style.display === '') {
        // Calcular posición del botón
        const rect = button.getBoundingClientRect();
        dropdown.style.display = 'block';
        dropdown.style.top = (rect.bottom + 4) + 'px';
        dropdown.style.left = rect.left + 'px';
    } else {
        dropdown.style.display = 'none';
    }
};

// Cerrar dropdowns al hacer clic afuera
document.addEventListener('click', function(event) {
    if (!event.target.closest('.view-dropdown') && !event.target.closest('button[onclick*="toggleViewDropdown"]')) {
        document.querySelectorAll('.view-dropdown').forEach(d => {
            d.style.display = 'none';
        });
    }
});
</script>

<!-- Sección de Pendientes -->
<section id="pedidos-section" class="section-content active" style="display: block;">
    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-head" id="tableHead">
                <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                    @php
                        $columns = [
                            ['key' => 'acciones', 'label' => 'Acciones', 'flex' => '0 0 150px', 'justify' => 'flex-start'],
                            ['key' => 'estado', 'label' => 'Estado', 'flex' => '0 0 150px', 'justify' => 'center'],
                            ['key' => 'numero', 'label' => 'Número', 'flex' => '0 0 140px', 'justify' => 'center'],
                            ['key' => 'tipo', 'label' => 'Tipo', 'flex' => '0 0 120px', 'justify' => 'center'],
                            ['key' => 'fecha', 'label' => 'Fecha', 'flex' => '0 0 180px', 'justify' => 'center'],
                            ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '0 0 200px', 'justify' => 'center'],
                            ['key' => 'asesora', 'label' => 'Asesora', 'flex' => '0 0 150px', 'justify' => 'center'],
                            ['key' => 'novedades', 'label' => 'Novedades', 'flex' => '0 0 180px', 'justify' => 'center'],
                        ];
                    @endphp
                                        @foreach($columns as $column)
                        <div class="table-header-cell{{ $column['key'] === 'acciones' ? ' acciones-column' : '' }}" style="flex: {{ $column['flex'] }}; justify-content: {{ $column['justify'] }};">
                            <div class="th-wrapper" style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                <span class="header-text">{{ $column['label'] }}</span>
                                @if($column['key'] !== 'acciones')
                                    <button type="button" class="btn-filter-column" data-filter-column="{{ $column['key'] }}" onclick="abrirFiltroColumna('{{ $column['key'] }}', obtenerValoresColumna('{{ $column['key'] }}'))" title="Filtrar {{ $column['label'] }}">
                                        <span class="material-symbols-rounded">filter_alt</span>
                                        <div class="filter-badge"></div>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="table-scroll-container">
                <div class="modern-table">
                    <div id="tablaCotizacionesBody" class="table-body">
                        @php
                            $perPage = 25;
                            $currentPage = request()->get('page', 1);
                            $total = $cotizaciones->count();
                            $totalPages = ceil($total / $perPage);
                            $offset = ($currentPage - 1) * $perPage;
                            $cotizacionesPaginadas = $cotizaciones->slice($offset, $perPage);
                        @endphp
                        
                        @forelse($cotizacionesPaginadas as $cotizacion)
                            @php
                                $tipoId = (int) ($cotizacion->tipo_cotizacion_id ?? 0);
                                $tipoLabel = match ($tipoId) {
                                    1 => 'Combinada',
                                    2 => 'Logo',
                                    3 => 'Prenda',
                                    default => (function () use ($cotizacion) {
                                        $tipoCodigo = $cotizacion->tipoCotizacion?->codigo ?? $cotizacion->obtenerTipoCotizacion();
                                        return in_array($tipoCodigo, ['PB', 'prenda_logo']) ? 'Combinada' : (in_array($tipoCodigo, ['B', 'logo']) ? 'Logo' : 'Prenda');
                                    })(),
                                };

                                $tipoStyle = match ($tipoLabel) {
                                    'Combinada' => 'background:#ede9fe;color:#5b21b6;',
                                    'Logo' => 'background:#dbeafe;color:#1e40af;',
                                    default => 'background:#dcfce7;color:#166534;',
                                };
                            @endphp
                            <div class="table-row" data-cotizacion-id="{{ $cotizacion->id }}" data-numero="{{ $cotizacion->numero_cotizacion ?? 'N/A' }}" data-tipo="{{ $tipoLabel }}" data-cliente="{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '') }}" data-asesora="{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? '') }}" data-fecha="{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y') : '' }}" data-estado="{{ $cotizacion->estado }}" data-novedades="{{ $cotizacion->novedades ?? '-' }}">
                                <!-- Acciones -->
                                <div class="table-cell acciones-column" style="flex: 0 0 150px; justify-content: center; position: relative; display: flex; gap: 0.5rem;">
                                    <button class="action-view-btn" title="Ver opciones" data-cotizacion-id="{{ $cotizacion->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="action-menu" data-cotizacion-id="{{ $cotizacion->id }}">
                                        <a href="#" class="action-menu-item" data-action="cotizacion" onclick="openCotizacionModal({{ $cotizacion->id }}); return false;">
                                            <i class="fas fa-file-alt"></i>
                                            <span>Ver Cotización</span>
                                        </a>
                                        <a href="#" class="action-menu-item" data-action="costos" onclick="abrirModalVisorCostos({{ $cotizacion->id }}, '{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '') }}'); return false;">
                                            <i class="fas fa-chart-bar"></i>
                                            <span>Ver Costos</span>
                                        </a>
                                        <a href="/asesores/contador/cotizacion/{{ $cotizacion->id }}/pdf?tipo=prenda" class="action-menu-item" data-action="pdf">
                                            <i class="fas fa-file-pdf"></i>
                                            <span>Ver PDF</span>
                                        </a>
                                    </div>
                                    <button class="btn-action btn-edit btn-editar-costos" data-cotizacion-id="{{ $cotizacion->id }}" data-cliente="{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '') }}" title="Editar Costos">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-success" onclick="aprobarCotizacionEnLinea({{ $cotizacion->id }})" title="Aprobar Cotización">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                </div>
                                
                                <!-- Estado -->
                                <div class="table-cell" style="flex: 0 0 150px;" data-estado="{{ $cotizacion->estado }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        @php
                                            $estadoColors = [
                                                'ENVIADA_CONTADOR' => ['bg' => '#fff3cd', 'color' => '#856404'],
                                                'EN_CORRECCION' => ['bg' => '#f8d7da', 'color' => '#721c24'],
                                                'APROBADA_CONTADOR' => ['bg' => '#d4edda', 'color' => '#155724'],
                                                'RECHAZADA' => ['bg' => '#f8d7da', 'color' => '#721c24'],
                                            ];
                                            $colors = $estadoColors[$cotizacion->estado] ?? ['bg' => '#e3f2fd', 'color' => '#1e40af'];
                                        @endphp
                                        <span style="background: {{ $colors['bg'] }}; color: {{ $colors['color'] }}; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                            {{ str_replace('_', ' ', $cotizacion->estado) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Número -->
                                <div class="table-cell" style="flex: 0 0 140px;" data-numero="{{ $cotizacion->numero_cotizacion ?? 'N/A' }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span style="font-weight: 600;">{{ $cotizacion->numero_cotizacion ?? 'Por asignar' }}</span>
                                    </div>
                                </div>

                                <!-- Tipo -->
                                <div class="table-cell" style="flex: 0 0 120px;" data-tipo="{{ $tipoLabel }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span style="{{ $tipoStyle }} padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 700;">{{ $tipoLabel }}</span>
                                    </div>
                                </div>
                                
                                <!-- Fecha -->
                                <div class="table-cell" style="flex: 0 0 180px;" data-fecha="{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y') : '' }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y H:i') : '-' }}</span>
                                    </div>
                                </div>
                                
                                <!-- Cliente -->
                                <div class="table-cell" style="flex: 0 0 200px;" data-cliente="{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '') }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '-') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Asesora -->
                                <div class="table-cell" style="flex: 0 0 150px;" data-asesora="{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? '') }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? '-') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Novedades -->
                                <div class="table-cell" style="flex: 0 0 180px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span style="font-size: 0.85rem;">{{ $cotizacion->novedades ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="padding: 40px; text-align: center; color: #9ca3af;">
                                <p>No hay cotizaciones disponibles</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="table-pagination" id="tablePagination">
                <div class="pagination-info">
                    <span id="paginationInfo">Mostrando 1-25 de {{ $total }} registros</span>
                </div>
                <div class="pagination-controls" id="paginationControls">
                    @if($totalPages > 1)
                        <button class="pagination-btn" data-page="1" {{ $currentPage == 1 ? 'disabled' : '' }}>
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button class="pagination-btn" data-page="{{ $currentPage - 1 }}" {{ $currentPage == 1 ? 'disabled' : '' }}>
                            <i class="fas fa-angle-left"></i>
                        </button>
                        
                        @php
                            $start = max(1, $currentPage - 2);
                            $end = min($totalPages, $currentPage + 2);
                        @endphp
                        
                        @for($i = $start; $i <= $end; $i++)
                            <button class="pagination-btn page-number {{ $i == $currentPage ? 'active' : '' }}" data-page="{{ $i }}">
                                {{ $i }}
                            </button>
                        @endfor
                        
                        <button class="pagination-btn" data-page="{{ $currentPage + 1 }}" {{ $currentPage == $totalPages ? 'disabled' : '' }}>
                            <i class="fas fa-angle-right"></i>
                        </button>
                        <button class="pagination-btn" data-page="{{ $totalPages }}" {{ $currentPage == $totalPages ? 'disabled' : '' }}>
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal de Cálculo de Costos por Prenda -->
<div id="calculoCostosModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9997; justify-content: center; align-items: center; padding: 2rem; flex-direction: column;">
    <div style="background: white; border-radius: 12px; width: 100%; max-width: 700px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,0.3); border: 2px solid #1e5ba8; overflow: hidden;">
        
        <!-- Contenedor con scroll general -->
        <div style="overflow-y: auto; overflow-x: hidden; flex: 1; display: flex; flex-direction: column;">
            
            <!-- Tabs de Prendas con Scroll Horizontal -->
            <div id="prendasTabs" style="display: flex; gap: 0.75rem; padding: 1.5rem 1.5rem 0 1.5rem; overflow-x: auto; overflow-y: hidden; flex-wrap: nowrap; min-height: 50px; align-items: center; border-bottom: 2px solid #e5e7eb; flex-shrink: 0;">
                <!-- Se llenará dinámicamente -->
            </div>

            <!-- Descripción de Prenda -->
            <div id="prendasDescripcion" style="padding: 1rem 1.5rem; color: #475569; font-size: 0.85rem; line-height: 1.6; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 2px solid #e5e7eb; min-height: 80px; flex-shrink: 0; background: #f9fafb;">
                <!-- Se llenará dinámicamente -->
            </div>

            <!-- Tabla de Precios -->
            <div style="padding: 1.5rem 1.5rem 0 1.5rem; display: flex; flex-direction: column; gap: 0;">
            <!-- Header de tabla -->
            <div style="display: grid; grid-template-columns: 1fr 150px 80px; gap: 0; padding: 1rem; color: white; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.3px; background: linear-gradient(135deg, #1e5ba8 0%, #1a4a8f 100%); border-radius: 12px 12px 0 0; border: 2px solid #1e5ba8; border-bottom: none;">
                <div style="padding-right: 1rem; border-right: 1px solid rgba(255,255,255,0.3);">Items a evaluar</div>
                <div style="text-align: center; padding: 0 1rem; border-right: 1px solid rgba(255,255,255,0.3);">Precio</div>
                <div style="text-align: center; padding-left: 1rem;">Acción</div>
            </div>

            <!-- Filas de tabla -->
            <div id="tablaPreciosBody" style="display: flex; flex-direction: column; gap: 0.75rem; padding: 1rem; background: white; border: 2px solid #1e5ba8; border-top: none; min-height: 100px;">
                <!-- Se llenará dinámicamente -->
            </div>

            <!-- Botón Agregar -->
            <div style="padding: 1rem; text-align: center; background: white; border: 2px solid #1e5ba8; border-top: none;">
                <button onclick="agregarFilaItem()" style="background: linear-gradient(135deg, #1e5ba8 0%, #1a4a8f 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.3px;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <span>+</span> Agregar
                </button>
            </div>

            <!-- Total -->
            <div style="background: linear-gradient(135deg, #1e5ba8 0%, #1a4a8f 100%); padding: 1rem; color: white; display: flex; justify-content: space-between; align-items: center; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; border: 2px solid #1e5ba8; border-top: none; border-radius: 0 0 12px 12px; gap: 1rem;">
                <span>Total Costo:</span>
                <span id="totalCosto" style="font-size: 1.2rem;">$0.00</span>
                <div style="display: flex; gap: 0.75rem; margin-left: auto;">
                    <button onclick="cerrarModalCalculoCostos()" style="background: #94a3b8; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.3px;" onmouseover="this.style.background='#64748b'" onmouseout="this.style.background='#94a3b8'">
                        Cancelar ✕
                    </button>
                    <button onclick="guardarCalculoCostos()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.3px;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        Guardar ✓
                    </button>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>

<style>
    /* Estilos para el scrollbar general del modal de costos */
    #calculoCostosModal > div > div:first-child::-webkit-scrollbar {
        width: 10px;
    }
    #calculoCostosModal > div > div:first-child::-webkit-scrollbar-track {
        background: #f5f5f5;
        border-radius: 4px;
    }
    #calculoCostosModal > div > div:first-child::-webkit-scrollbar-thumb {
        background: #1e5ba8;
        border-radius: 4px;
        border: 2px solid #f5f5f5;
    }
    #calculoCostosModal > div > div:first-child::-webkit-scrollbar-thumb:hover {
        background: #1a4a8f;
    }
</style>

<div id="pdfPreviewModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center; padding: 0;">
    <div style="background: white; border-radius: 0; width: 100vw; height: 100vh; display: flex; flex-direction: column; box-shadow: none; border: none; overflow: hidden;">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; background: linear-gradient(135deg, #1e5ba8 0%, #1a4a8f 100%); color: white;">
            <div style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; font-size: 0.9rem;">Vista previa PDF</div>
            <div style="display: flex; gap: 0.5rem;">
                <div style="display:flex; align-items:center; gap: 0.5rem; margin-right: 0.5rem;">
                    <div style="font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.2px;">Escalar</div>
                    <input id="pdfScaleInput" type="range" min="0.75" max="1" step="0.01" value="1" oninput="updatePdfScalePreview()" style="width: 180px;" />
                    <div id="pdfScaleLabel" style="min-width: 44px; text-align: right; font-weight: 700;">100%</div>
                </div>
                <button type="button" onclick="closePdfModal()" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.35); padding: 0.4rem 0.75rem; border-radius: 6px; cursor: pointer; font-weight: 700;">
                    Cerrar
                </button>
            </div>
        </div>
        <div style="flex: 1; background: #0b1220;">
            <iframe id="pdfPreviewFrame" title="PDF" src="" style="width: 100%; height: 100%; border: 0; background: white;"></iframe>
        </div>
    </div>
</div>

<script>
    document.addEventListener('click', function (event) {
        const modal = document.getElementById('pdfPreviewModal');
        if (!modal || modal.style.display !== 'flex') return;
        if (event.target === modal) {
            closePdfModal();
        }
    });
</script>

<!-- Script de Tabla de Cotizaciones -->
<script src="{{ asset('js/contador/tabla-cotizaciones.js') }}"></script>

<!-- Script de Real-time -->
<script src="{{ asset('js/realtime-cotizaciones.js') }}?v={{ time() }}"></script>

@endsection
