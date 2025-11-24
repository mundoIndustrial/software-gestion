@extends('layouts.contador')

@section('content')
<!-- Sección de Pedidos -->
<section id="pedidos-section" class="section-content active">
    <div class="table-container">
        <div class="table-header">
            <h2>📋 Mis Cotizaciones</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Asesora</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Paginar manualmente: 25 por página
                    // (El filtrado ya se hace en el controlador)
                    $perPage = 25;
                    $currentPage = request()->get('page', 1);
                    $total = $cotizaciones->count();
                    $totalPages = ceil($total / $perPage);
                    $offset = ($currentPage - 1) * $perPage;
                    $cotizacionesPaginadas = $cotizaciones->slice($offset, $perPage);
                @endphp
                
                @forelse($cotizacionesPaginadas as $cotizacion)
                    <tr>
                        <td><strong>COT-{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}</strong></td>
                        <td>{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                        <td>{{ $cotizacion->cliente ?? 'N/A' }}</td>
                        <td>{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? 'N/A') }}</td>
                        <td>
                            <select class="estado-dropdown" data-cotizacion-id="{{ $cotizacion->id }}" onchange="cambiarEstadoCotizacion(this)" style="padding: 0.5rem 0.8rem; border-radius: 4px; border: 2px solid #ddd; font-weight: 600; cursor: pointer; background: white; transition: all 0.2s;">
                                <option value="enviada" {{ $cotizacion->estado === 'enviada' ? 'selected' : '' }} style="background: #3b82f6; color: white;">✓ Enviada</option>
                                <option value="entregar" {{ $cotizacion->estado === 'entregar' ? 'selected' : '' }} style="background: #10b981; color: white;">📦 Entregar</option>
                                <option value="anular" {{ $cotizacion->estado === 'anular' ? 'selected' : '' }} style="background: #ef4444; color: white;">✕ Anular</option>
                            </select>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                                <button class="btn btn-primary" onclick="openCotizacionModal({{ $cotizacion->id }})" style="padding: 0.6rem 0.8rem; background: #1e5ba8; color: white; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" title="Ver Detalles" onmouseover="this.style.background='#1e40af'" onmouseout="this.style.background='#1e5ba8'">
                                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">visibility</span>
                                </button>
                                <button class="btn btn-edit" onclick="abrirModalCalculoCostos({{ $cotizacion->id }}, '{{ $cotizacion->cliente }}')" style="padding: 0.6rem 0.8rem; background: #f59e0b; color: white; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" title="Calcular Costos" onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'">
                                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">edit</span>
                                </button>
                                <button class="btn btn-pdf" onclick="abrirModalPDF({{ $cotizacion->id }})" style="padding: 0.6rem 0.8rem; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" title="Ver PDF" onmouseover="this.style.background='#991b1b'" onmouseout="this.style.background='#dc2626'">
                                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">picture_as_pdf</span>
                                </button>
                                <button class="btn btn-danger" onclick="eliminarCotizacion({{ $cotizacion->id }}, '{{ $cotizacion->cliente }}')" style="padding: 0.6rem 0.8rem; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" title="Eliminar" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: #999;">
                            <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">inbox</span>
                            No hay cotizaciones disponibles
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Paginación -->
        @if($totalPages > 1)
        <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
            <!-- Botón Anterior -->
            @if($currentPage > 1)
                <a href="?page=1" class="pagination-btn" style="padding: 0.5rem 0.75rem; background: #1e5ba8; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: 600;">
                    « Primera
                </a>
                <a href="?page={{ $currentPage - 1 }}" class="pagination-btn" style="padding: 0.5rem 0.75rem; background: #1e5ba8; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: 600;">
                    ‹ Anterior
                </a>
            @else
                <span style="padding: 0.5rem 0.75rem; background: #e0e0e0; color: #999; border-radius: 4px; font-weight: 600;">
                    « Primera
                </span>
                <span style="padding: 0.5rem 0.75rem; background: #e0e0e0; color: #999; border-radius: 4px; font-weight: 600;">
                    ‹ Anterior
                </span>
            @endif
            
            <!-- Números de página -->
            <div style="display: flex; gap: 0.25rem; align-items: center;">
                @for($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++)
                    @if($i == $currentPage)
                        <span style="padding: 0.5rem 0.75rem; background: #1e5ba8; color: white; border-radius: 4px; font-weight: 700; min-width: 2.5rem; text-align: center;">
                            {{ $i }}
                        </span>
                    @else
                        <a href="?page={{ $i }}" style="padding: 0.5rem 0.75rem; background: white; color: #1e5ba8; border: 1px solid #1e5ba8; border-radius: 4px; text-decoration: none; font-weight: 600; min-width: 2.5rem; text-align: center; transition: all 0.2s;">
                            {{ $i }}
                        </a>
                    @endif
                @endfor
            </div>
            
            <!-- Botón Siguiente -->
            @if($currentPage < $totalPages)
                <a href="?page={{ $currentPage + 1 }}" class="pagination-btn" style="padding: 0.5rem 0.75rem; background: #1e5ba8; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: 600;">
                    Siguiente ›
                </a>
                <a href="?page={{ $totalPages }}" class="pagination-btn" style="padding: 0.5rem 0.75rem; background: #1e5ba8; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: 600;">
                    Última »
                </a>
            @else
                <span style="padding: 0.5rem 0.75rem; background: #e0e0e0; color: #999; border-radius: 4px; font-weight: 600;">
                    Siguiente ›
                </span>
                <span style="padding: 0.5rem 0.75rem; background: #e0e0e0; color: #999; border-radius: 4px; font-weight: 600;">
                    Última »
                </span>
            @endif
        </div>
        
        <!-- Info de paginación -->
        <div style="text-align: center; margin-top: 1rem; color: #666; font-size: 0.9rem;">
            Mostrando {{ ($offset + 1) }} a {{ min($offset + $perPage, $total) }} de {{ $total }} cotizaciones
        </div>
        @endif
    </div>
</section>

<!-- Modal de Cálculo de Costos por Prenda -->
<div id="calculoCostosModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; justify-content: center; align-items: center;">
    <div class="modal-content" style="width: 90%; max-width: 900px; max-height: 90vh; overflow-y: auto; background: white; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <!-- Header del Modal -->
        <div style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white; padding: 2rem; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700;">💰 CÁLCULO DE PRECIOS POR PRENDA</h2>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; opacity: 0.9;" id="modalCostosCliente">Cliente: -</p>
            </div>
            <button onclick="cerrarModalCalculoCostos()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.5rem 1rem; border-radius: 4px; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                ✕
            </button>
        </div>

        <!-- Contenido del Modal -->
        <div style="padding: 2rem;">
            <!-- Tabs de Prendas -->
            <div id="prendasTabs" style="display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 2px solid #e5e7eb; overflow-x: auto;">
                <!-- Se llena dinámicamente -->
            </div>

            <!-- Contenido de Prendas -->
            <div id="prendasContent">
                <!-- Se llena dinámicamente -->
            </div>

            <!-- Botones de Acción -->
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
                <button onclick="cerrarModalCalculoCostos()" style="padding: 0.75rem 1.5rem; background: #e5e7eb; color: #374151; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#d1d5db'" onmouseout="this.style.background='#e5e7eb'">
                    Cancelar
                </button>
                <button onclick="guardarCalculoCostos()" style="padding: 0.75rem 1.5rem; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                    ✓ Guardar Costos
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cotización -->
<div id="cotizacionModal" class="modal fullscreen" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <img src="{{ asset('images/logo2.png') }}" alt="Logo Mundo Industrial" class="modal-header-logo">
            <div style="display: flex; gap: 3rem; align-items: center; flex: 1; margin-left: 2rem; color: white; font-size: 0.85rem;">
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                    <span style="font-weight: 600; opacity: 0.9;">COTIZACIÓN #</span>
                    <span style="font-size: 1rem; font-weight: 700;" id="modalHeaderNumber">-</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                    <span style="font-weight: 600; opacity: 0.9;">FECHA</span>
                    <span style="font-size: 1rem; font-weight: 700;" id="modalHeaderDate">-</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                    <span style="font-weight: 600; opacity: 0.9;">CLIENTE</span>
                    <span style="font-size: 1rem; font-weight: 700;" id="modalHeaderClient">-</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                    <span style="font-weight: 600; opacity: 0.9;">ASESORA</span>
                    <span style="font-size: 1rem; font-weight: 700;" id="modalHeaderAdvisor">-</span>
                </div>
            </div>
            <button class="modal-back-btn" onclick="closeCotizacionModal()">
                <span class="material-symbols-rounded">arrow_back</span>
                VOLVER
            </button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Contenido dinámico -->
        </div>
    </div>
</div>

<!-- Modal PDF Fullscreen -->
<div id="modalPDF" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 9999; padding: 0; margin: 0;">
    <div style="position: absolute; top: 0; left: 0; right: 0; background: #1e5ba8; color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; z-index: 10000;">
        <h2 style="margin: 0; font-size: 1.3rem;">📄 Visualizar Cotización PDF</h2>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <button onclick="recortarAlContenido()" style="padding: 0.75rem 1.5rem; background: #f59e0b; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'" title="Recortar la altura al contenido">
                <span class="material-symbols-rounded" style="font-size: 1.2rem;">crop</span>
                Recortar
            </button>
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

<!-- Script para Modal de Cálculo de Costos -->
<script src="{{ asset('js/contador/modal-calculo-costos.js') }}"></script>

<!-- Script para Modal PDF -->
<script>
    let cotizacionIdActualPDF = null;
    let pdfRecortado = false;

    function abrirModalPDF(cotizacionId) {
        cotizacionIdActualPDF = cotizacionId;
        const modalPDF = document.getElementById('modalPDF');
        const pdfViewer = document.getElementById('pdfViewer');
        
        // Mostrar modal
        modalPDF.style.display = 'block';
        pdfRecortado = false;
        
        // Cargar PDF en iframe con zoom 125%
        pdfViewer.src = `/contador/cotizacion/${cotizacionId}/pdf#zoom=125`;
    }

    function cerrarModalPDF() {
        const modalPDF = document.getElementById('modalPDF');
        const pdfViewer = document.getElementById('pdfViewer');
        
        modalPDF.style.display = 'none';
        pdfViewer.src = '';
        cotizacionIdActualPDF = null;
        pdfRecortado = false;
    }

    function recortarAlContenido() {
        if (cotizacionIdActualPDF) {
            const pdfViewer = document.getElementById('pdfViewer');
            pdfRecortado = !pdfRecortado;
            
            if (pdfRecortado) {
                // Cargar PDF recortado
                pdfViewer.src = `/contador/cotizacion/${cotizacionIdActualPDF}/pdf?recortar=1#zoom=125`;
            } else {
                // Cargar PDF completo
                pdfViewer.src = `/contador/cotizacion/${cotizacionIdActualPDF}/pdf#zoom=125`;
            }
        }
    }

    function descargarPDF() {
        if (cotizacionIdActualPDF) {
            const link = document.createElement('a');
            const url = pdfRecortado 
                ? `/contador/cotizacion/${cotizacionIdActualPDF}/pdf?descargar=1&recortar=1`
                : `/contador/cotizacion/${cotizacionIdActualPDF}/pdf?descargar=1`;
            link.href = url;
            link.download = `Cotizacion_${cotizacionIdActualPDF}_${new Date().toISOString().split('T')[0]}.pdf`;
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

@endsection
