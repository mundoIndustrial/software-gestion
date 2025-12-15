@extends('layouts.base')

@section('module', 'contador')

@section('body')
<div class="contador-wrapper">
    <!-- Sidebar Contador -->
    @include('contador.sidebar')

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header Contador (Con notificaciones y perfil) -->
        @include('components.headers.header-contador')

        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>
</div>

<!-- Modales Compartidos para Contador -->
<!-- Modal de Cotización -->
<div id="cotizacionModal" class="modal fullscreen" style="display: none;">
    <div class="modal-content" style="background: white;">
        <div class="modal-header">
            <img src="{{ asset('images/logo2.png') }}" alt="Logo Mundo Industrial" class="modal-header-logo" width="150" height="60">
            <div style="display: flex; gap: 3rem; align-items: center; flex: 1; margin-left: 2rem; color: white; font-size: 0.85rem;">
                <div>
                    <p style="margin: 0; opacity: 0.8;">Cotización #</p>
                    <p id="modalHeaderNumber" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                </div>
                <div>
                    <p style="margin: 0; opacity: 0.8;">Fecha</p>
                    <p id="modalHeaderDate" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                </div>
                <div>
                    <p style="margin: 0; opacity: 0.8;">Cliente</p>
                    <p id="modalHeaderClient" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                </div>
                <div>
                    <p style="margin: 0; opacity: 0.8;">Asesora</p>
                    <p id="modalHeaderAdvisor" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                </div>
            </div>
            <button onclick="closeCotizacionModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.5rem 1rem; border-radius: 4px; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                ✕
            </button>
        </div>
        <div id="modalBody" style="padding: 2rem; overflow-y: auto; background: white;"></div>
    </div>
</div>

<!-- Modal de Visor de Costos -->
<div id="visorCostosModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9998; justify-content: center; align-items: flex-start; padding: 2rem; overflow: hidden; padding-top: 4rem;">
    <div style="position: relative; width: 90%; max-width: 900px;">
        <!-- Tabs de Prendas que sobresalen del modal -->
        <div id="visorCostosTabsContainer" style="display: flex; gap: 0.75rem; margin-bottom: -1.5rem; position: relative; z-index: 11; flex-wrap: wrap; justify-content: flex-start;">
            <!-- Tabs generados dinámicamente por visor-costos.js -->
        </div>
        
        <!-- Contenedor principal del modal -->
        <div class="modal-content" id="visorCostosModalContent" style="width: 100%; max-height: 85vh; overflow: visible; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); display: flex; flex-direction: column; position: relative;">
            <button onclick="cerrarVisorCostos()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #7f8c8d; z-index: 10;">
                <span class="material-symbols-rounded">close</span>
            </button>
            <div id="visorCostosContenido" style="overflow-y: auto; flex: 1; padding: 1.5rem;">
                <!-- Contenido cargado dinámicamente por visor-costos.js -->
            </div>
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

<!-- Script para Funciones Globales de Modales -->
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


    function cerrarVisorCostos() {
        document.getElementById('visorCostosModal').style.display = 'none';
    }

    function closeCotizacionModal() {
        document.getElementById('cotizacionModal').style.display = 'none';
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

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contador/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contador/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contador/cotizacion-modal.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/contador/editar-tallas.js') }}"></script>
    <script src="{{ asset('js/contador/cotizacion.js') }}"></script>
    <script src="{{ asset('js/contador/contador.js') }}"></script>
    <script src="{{ asset('js/contador/notifications.js') }}"></script>
    <script src="{{ asset('js/contador/modal-calculo-costos.js') }}"></script>
    <script src="{{ asset('js/contador/visor-costos.js') }}"></script>
    <script src="{{ asset('js/contador/busqueda-header.js') }}"></script>
    <script>
        /**
         * Cargar contador de cotizaciones pendientes
         */
        function cargarContadorPendientes() {
            fetch('{{ route("contador.cotizaciones-pendientes-count") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.count > 0) {
                        const badge = document.getElementById('cotizacionesPendientesCount');
                        if (badge) {
                            badge.textContent = data.count;
                            badge.style.display = 'inline-flex';
                        }
                    }
                })
                .catch(error => console.error('Error al cargar contador:', error));
        }

        // Cargar contador al cargar la página
        document.addEventListener('DOMContentLoaded', cargarContadorPendientes);

        // Recargar contador cada 30 segundos
        setInterval(cargarContadorPendientes, 30000);
    </script>
@endpush
