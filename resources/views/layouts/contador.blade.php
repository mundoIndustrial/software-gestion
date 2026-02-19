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


<!-- Lightbox para Imágenes de Prendas -->
<div id="lightboxImagenes" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.95); z-index: 10000; justify-content: center; align-items: center;">
    <button onclick="cerrarLightboxImagenes()" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; font-size: 2rem; cursor: pointer; padding: 10px 20px; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; z-index: 10002;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
        ×
    </button>
    
    <button id="lightboxAnterior" onclick="lightboxImagenAnterior()" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); border: none; color: white; font-size: 2rem; cursor: pointer; padding: 15px 20px; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; z-index: 10002;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
        ‹
    </button>
    
    <div style="max-width: 90%; max-height: 90%; display: flex; flex-direction: column; align-items: center; gap: 1rem;">
        <img id="lightboxImagen" src="" alt="Imagen de prenda" style="max-width: 100%; max-height: 85vh; object-fit: contain; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
        <div id="lightboxContador" style="color: white; font-size: 1.1rem; font-weight: 600; background: rgba(0,0,0,0.5); padding: 8px 20px; border-radius: 20px; backdrop-filter: blur(10px);">
            1 / 1
        </div>
    </div>
    
    <button id="lightboxSiguiente" onclick="lightboxImagenSiguiente()" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); border: none; color: white; font-size: 2rem; cursor: pointer; padding: 15px 20px; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; z-index: 10002;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
        ›
    </button>
</div>

<!-- Script para Funciones Globales de Modales -->
<script>
    function cerrarVisorCostos() {
        document.getElementById('visorCostosModal').style.display = 'none';
    }

    function closeCotizacionModal() {
        document.getElementById('cotizacionModal').style.display = 'none';
    }
</script>

<!-- Vite App Bundle (incluye Bootstrap.js con Echo initialization) -->
@vite(['resources/js/app.js'])

@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contador/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contador/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contador/cotizacion-modal.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/contador/editar-tallas.js') }}"></script>
    <script src="{{ asset('js/contador/editar-tallas-personalizado.js') }}"></script>
    <script src="{{ asset('js/contador/cotizacion.js') }}"></script>
    <script src="{{ asset('js/contador/contador.js') }}"></script>
    <script src="{{ asset('js/contador/notifications.js') }}"></script>
    <script src="{{ asset('js/contador/modal-calculo-costos.js') }}"></script>
    <script src="{{ asset('js/contador/visor-costos.js') }}"></script>
    <script src="{{ asset('js/contador/lightbox-imagenes.js') }}"></script>
    <script src="{{ asset('js/contador/busqueda-header.js') }}"></script>
    <!-- realtime-cotizaciones.js se carga en la vista específica, no aquí para evitar duplicación -->
    <script>
        /**
         * Cargar contador de cotizaciones pendientes - Solo al inicio
         * El tiempo real se maneja via WebSocket en realtime-cotizaciones.js
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

        // Cargar contador al cargar la página (solo una vez)
        document.addEventListener('DOMContentLoaded', cargarContadorPendientes);

        // NOTA: La actualización en tiempo real del badge se maneja via WebSocket
        // en el archivo realtime-cotizaciones.js
    </script>
@endpush
