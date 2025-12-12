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
                
                console.log('Botón Editar Costos clickeado:', { cotizacionId, cliente });
                
                if (typeof abrirModalCalculoCostos === 'function') {
                    abrirModalCalculoCostos(cotizacionId, cliente);
                } else {
                    console.error('Función abrirModalCalculoCostos no disponible');
                    alert('Función abrirModalCalculoCostos no disponible');
                }
            }
        });
    }
    
    // Registrar listeners al cargar
    registrarEventListeners();
    
    // Re-registrar cuando se recargue la tabla
    document.addEventListener('tablaPendientesRecargada', function() {
        console.log('Tabla recargada, re-registrando event listeners');
        registrarEventListeners();
    });
</script>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contador/tabla-index.css') }}?v={{ time() }}">
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
                            ['key' => 'acciones', 'label' => 'Acciones', 'flex' => '0 0 120px', 'justify' => 'flex-start'],
                            ['key' => 'numero', 'label' => 'Número', 'flex' => '0 0 140px', 'justify' => 'center'],
                            ['key' => 'fecha', 'label' => 'Fecha', 'flex' => '0 0 180px', 'justify' => 'center'],
                            ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '0 0 200px', 'justify' => 'center'],
                            ['key' => 'asesora', 'label' => 'Asesora', 'flex' => '0 0 150px', 'justify' => 'center'],
                        ];
                    @endphp
                    
                    @foreach($columns as $column)
                        <div class="table-header-cell{{ $column['key'] === 'acciones' ? ' acciones-column' : '' }}" style="flex: {{ $column['flex'] }}; justify-content: {{ $column['justify'] }};">
                            <div class="th-wrapper">
                                <span class="header-text">{{ $column['label'] }}</span>
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
                            <div class="table-row" data-cotizacion-id="{{ $cotizacion->id }}">
                                <!-- Acciones -->
                                <div class="table-cell acciones-column" style="flex: 0 0 120px; justify-content: center; position: relative;">
                                    <div class="actions-group">
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
                                            <a href="#" class="action-menu-item" data-action="pdf" onclick="abrirModalPDF({{ $cotizacion->id }}); return false;">
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
                                </div>
                                
                                <!-- Número -->
                                <div class="table-cell" style="flex: 0 0 140px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span style="font-weight: 600;">COT-{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                </div>
                                
                                <!-- Fecha -->
                                <div class="table-cell" style="flex: 0 0 180px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y H:i') : '-' }}</span>
                                    </div>
                                </div>
                                
                                <!-- Cliente -->
                                <div class="table-cell" style="flex: 0 0 200px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '-') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Asesora -->
                                <div class="table-cell" style="flex: 0 0 150px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? '-') }}</span>
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


<!-- Modal de Visor de Costos por Prenda -->
<div id="visorCostosModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9998; justify-content: center; align-items: center; padding: 2rem; overflow: hidden;">
    <div class="modal-content" id="visorCostosModalContent" style="width: 90%; max-width: 800px; height: auto; overflow: visible; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); display: flex; flex-direction: column;">
        <style>
            #visorCostosModal {
                overflow: hidden;
            }
            #visorCostosModal .modal-content {
                max-height: calc(100vh - 4rem);
                overflow: visible;
            }
            #visorCostosContenido {
                overflow-x: hidden;
                overflow-y: auto;
                max-height: none;
            }
            #visorCostosContenido::-webkit-scrollbar {
                width: 8px;
            }
            #visorCostosContenido::-webkit-scrollbar-track {
                background: transparent;
            }
            #visorCostosContenido::-webkit-scrollbar-thumb {
                background: #ccc;
                border-radius: 4px;
            }
            #visorCostosContenido::-webkit-scrollbar-thumb:hover {
                background: #999;
            }
        </style>
        <!-- Header del Modal -->
        <div style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white; padding: 0.75rem 1.5rem; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; transform: scale(0.8); transform-origin: top left; width: 125%;">
            <div>
                <h2 style="margin: 0; font-size: 1.2rem; font-weight: 700;" id="visorTitulo">-</h2>
                <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; opacity: 0.9;" id="visorCliente">Cliente: -</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <button onclick="visorCostosAnterior()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 4px; transition: all 0.2s;" title="Prenda Anterior" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    ‹
                </button>
                <span style="color: white; font-weight: 600; min-width: 60px; text-align: center;" id="visorIndice">1 / 1</span>
                <button onclick="visorCostosProximo()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 4px; transition: all 0.2s;" title="Próxima Prenda" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    ›
                </button>
                <button onclick="cerrarVisorCostos()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.5rem 1rem; border-radius: 4px; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    ✕
                </button>
            </div>
        </div>

        <!-- Contenido del Modal -->
        <div style="padding: 2rem;" id="visorCostosContenido">
            <!-- Se llena dinámicamente -->
        </div>
    </div>
</div>

<!-- Modal de Cálculo de Costos por Prenda -->
<div id="calculoCostosModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9998; justify-content: center; align-items: center; padding: 2rem;">
    <div class="modal-content" style="width: 100%; max-width: 700px; background: #1a1f3a; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
        <!-- Header del Modal -->
        <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 1.5rem; display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="flex: 1;">
                <h2 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">CÁLCULO DE PRECIOS POR PRENDA</h2>
                <p style="margin: 0; font-size: 0.95rem; opacity: 0.95; text-transform: uppercase; letter-spacing: 0.3px;" id="modalCostosCliente">CLIENTE: -</p>
            </div>
            <button onclick="cerrarModalCalculoCostos()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem 0.75rem; border-radius: 4px; transition: all 0.2s; flex-shrink: 0;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                ✕
            </button>
        </div>

        <!-- Tabs de Prendas -->
        <div id="prendasTabs" style="display: flex; gap: 0.75rem; padding: 1.5rem 1.5rem 0 1.5rem; overflow-x: auto; flex-wrap: wrap;">
            <!-- Se llenará dinámicamente -->
        </div>

        <!-- Descripción de Prenda -->
        <div id="prendasDescripcion" style="padding: 0 1.5rem; color: #e5e7eb; font-size: 0.85rem; line-height: 1.6; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 1.5rem;">
            <!-- Se llenará dinámicamente -->
        </div>

        <!-- Tabla de Precios -->
        <div style="padding: 0 1.5rem 1.5rem 1.5rem; flex: 1; overflow-y: auto;">
            <div style="background: transparent; border-radius: 12px; overflow: hidden;">
                <!-- Header de tabla -->
                <div style="display: grid; grid-template-columns: 1fr 150px 80px; gap: 0; padding: 1rem; color: white; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.3px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 12px 12px 0 0; border: 2px solid #3b82f6; border-bottom: none;">
                    <div style="padding-right: 1rem; border-right: 1px solid rgba(255,255,255,0.3);">Items a evaluar</div>
                    <div style="text-align: center; padding: 0 1rem; border-right: 1px solid rgba(255,255,255,0.3);">Precio</div>
                    <div style="text-align: center; padding-left: 1rem;">Acción</div>
                </div>

                <!-- Filas de tabla -->
                <div id="tablaPreciosBody" style="display: flex; flex-direction: column; gap: 0.75rem; padding: 1rem; background: #1a1f3a; border: 2px solid #3b82f6; border-top: none; border-radius: 0 0 12px 12px;">
                    <!-- Se llenará dinámicamente -->
                </div>

                <!-- Botón Agregar -->
                <div style="padding: 1rem; text-align: center; background: #1a1f3a; border: 2px solid #3b82f6; border-top: none; border-radius: 0 0 12px 12px;">
                    <button onclick="agregarFilaItem()" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.3px;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        <span>+</span> Agregar
                    </button>
                </div>

                <!-- Total -->
                <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 1rem; color: white; display: flex; justify-content: space-between; align-items: center; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; border: 2px solid #3b82f6; border-top: none; border-radius: 0 0 12px 12px;">
                    <span>Total Costo:</span>
                    <span id="totalCosto" style="font-size: 1.2rem;">$0.00</span>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div style="padding: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end; border-top: 1px solid rgba(255,255,255,0.1);">
            <button onclick="cerrarModalCalculoCostos()" style="background: #6b7280; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.3px;" onmouseover="this.style.background='#4b5563'" onmouseout="this.style.background='#6b7280'">
                Cancelar ✕
            </button>
            <button onclick="guardarCalculoCostos()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.3px;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                Guardar ✓
            </button>
        </div>
    </div>
</div>

<!-- Modal PDF Fullscreen -->
<div id="modalPDF" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 9999; padding: 0; margin: 0;">
    <div style="position: absolute; top: 0; left: 0; right: 0; background: #1e5ba8; color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; z-index: 10000;">
        <h2 style="margin: 0; font-size: 1.3rem;">📄 Visualizar Cotización PDF</h2>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <button onclick="descargarPDF()" style="padding: 0.75rem 1.5rem; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                <span class="material-symbols-rounded" style="font-size: 1.2rem;">download</span>
                Descargar PDF
            </button>
            <button onclick="cerrarModalPDF()" style="padding: 0.75rem 1.5rem; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                <span class="material-symbols-rounded" style="font-size: 1.2rem;">close</span>
                Cerrar
            </button>
        </div>
    </div>
    <iframe id="pdfViewer" style="position: absolute; top: 60px; left: 0; right: 0; bottom: 0; width: 100%; height: calc(100% - 60px); border: none; background: white;"></iframe>
</div>

<!-- Script para Modal PDF -->
<script>
    // Variable global para acceder desde otros scripts
    window.cotizacionIdActualPDF = null;

    function abrirModalPDF(cotizacionId) {
        window.cotizacionIdActualPDF = cotizacionId;
        const modalPDF = document.getElementById('modalPDF');
        const pdfViewer = document.getElementById('pdfViewer');
        
        // Mostrar modal
        modalPDF.style.display = 'block';
        
        // Cargar PDF en iframe con zoom 125%
        pdfViewer.src = `/contador/cotizacion/${cotizacionId}/pdf#zoom=125`;
    }

    function cerrarModalPDF() {
        const modalPDF = document.getElementById('modalPDF');
        const pdfViewer = document.getElementById('pdfViewer');
        
        modalPDF.style.display = 'none';
        pdfViewer.src = '';
        window.cotizacionIdActualPDF = null;
    }

    function descargarPDF() {
        if (window.cotizacionIdActualPDF) {
            const link = document.createElement('a');
            const url = `/contador/cotizacion/${window.cotizacionIdActualPDF}/pdf?descargar=1`;
            link.href = url;
            link.download = `Cotizacion_${window.cotizacionIdActualPDF}_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }

    // Cerrar modal al presionar ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            cerrarModalPDF();
        }
    });

        // Cerrar modal al hacer clic en el fondo
    document.getElementById('modalPDF').addEventListener('click', function(event) {
        if (event.target === this) {
            cerrarModalPDF();
        }
    });

</script>

<!-- Script de Tabla de Cotizaciones -->
<script src="{{ asset('js/contador/tabla-cotizaciones.js') }}"></script>

<!-- Script de Cotizaciones -->
<script src="{{ asset('js/contador/cotizacion.js') }}"></script>

<!-- Script de Búsqueda y Filtros -->
<script src="{{ asset('js/contador/busqueda-filtros.js') }}"></script>

@endsection