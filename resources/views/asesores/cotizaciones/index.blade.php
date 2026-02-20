@extends('layouts.asesores')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones y Borradores')

@push('styles')
{{-- CSS específicos del listado de cotizaciones - lazy loaded --}}
<link rel="stylesheet" href="{{ asset('css/cotizaciones/filtros-embudo.css') }}?v={{ time() }}" media="print" onload="this.media='all'">
<link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-tabs.css') }}?v={{ time() }}" media="print" onload="this.media='all'">
<link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-index.css') }}?v={{ time() }}" media="print" onload="this.media='all'">
<link rel="stylesheet" href="{{ asset('css/realtime-cotizaciones.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('css/contador/cotizacion-modal.css') }}?v={{ time() }}">
<noscript>
    <link rel="stylesheet" href="{{ asset('css/cotizaciones/filtros-embudo.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-tabs.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-index.css') }}?v={{ time() }}">
</noscript>
@endpush

@section('content')
    {{-- Header --}}
    @include('components.cotizaciones.header', [
        'title' => 'Cotizaciones',
        'subtitle' => 'Gestiona tus cotizaciones',
        'searchUrl' => route('asesores.cotizaciones.index'),
        'searchPlaceholder' => 'Buscar por cliente o número...',
        'actionButton' => [
            'url' => route('asesores.pedidos.create'),
            'label' => 'Registrar'
        ]
    ])

    {{-- Filtros por tipo --}}
    @include('components.cotizaciones.filters', [
        'filters' => [
            ['code' => 'todas', 'label' => 'Todas', 'icon' => 'fas fa-list', 'active' => true],
            ['code' => 'PL', 'label' => 'Combinada', 'icon' => 'fas fa-layer-group', 'active' => false],
            ['code' => 'L', 'label' => 'Logo', 'icon' => 'fas fa-palette', 'active' => false],
        ]
    ])

    {{-- Cotizaciones --}}
    <div id="tab-cotizaciones" class="tab-content">
        <div id="seccion-todas" class="seccion-tipo" style="display: block;">
            @include('components.cotizaciones.table', [
                'sectionId' => 'todas',
                'title' => 'Todas las Cotizaciones',
                'cotizaciones' => $cotizacionesTodas,
                'pageParameterName' => $pageNameCotTodas ?? 'page',
                'emptyMessage' => 'No hay cotizaciones',
                'columns' => [
                    ['key' => 'fecha', 'label' => 'Fecha', 'filterable' => true, 'align' => 'left'],
                    ['key' => 'codigo', 'label' => 'Código', 'filterable' => true, 'align' => 'left'],
                    ['key' => 'cliente', 'label' => 'Cliente', 'filterable' => true, 'align' => 'left'],
                    ['key' => 'tipo', 'label' => 'Tipo', 'filterable' => true, 'align' => 'left'],
                    ['key' => 'estado', 'label' => 'Estado', 'filterable' => true, 'align' => 'left'],
                    ['key' => 'accion', 'label' => 'Acción', 'align' => 'center'],
                ]
            ])
        </div>

        <div id="seccion-prenda" class="seccion-tipo" style="display: none;">
            @include('components.cotizaciones.table', [
                'sectionId' => 'prenda',
                'title' => 'Combinada',
                'cotizaciones' => $cotizacionesPrenda,
                'pageParameterName' => $pageNameCotPrenda ?? 'page',
                'emptyMessage' => 'No hay cotizaciones combinadas',
                'columns' => [
                    ['key' => 'fecha', 'label' => 'Fecha', 'align' => 'left'],
                    ['key' => 'codigo', 'label' => 'Código', 'align' => 'left'],
                    ['key' => 'cliente', 'label' => 'Cliente', 'align' => 'left'],
                    ['key' => 'tipo', 'label' => 'Tipo', 'align' => 'left'],
                    ['key' => 'estado', 'label' => 'Estado', 'align' => 'left'],
                    ['key' => 'accion', 'label' => 'Acción', 'align' => 'center'],
                ]
            ])
        </div>

        <div id="seccion-logo" class="seccion-tipo" style="display: none;">
            @include('components.cotizaciones.table', [
                'sectionId' => 'logo',
                'title' => 'Logo',
                'cotizaciones' => $cotizacionesLogo,
                'pageParameterName' => $pageNameCotLogo ?? 'page',
                'emptyMessage' => 'No hay cotizaciones de logo',
                'columns' => [
                    ['key' => 'fecha', 'label' => 'Fecha', 'align' => 'left'],
                    ['key' => 'codigo', 'label' => 'Código', 'align' => 'left'],
                    ['key' => 'cliente', 'label' => 'Cliente', 'align' => 'left'],
                    ['key' => 'tipo', 'label' => 'Tipo', 'align' => 'left'],
                    ['key' => 'estado', 'label' => 'Estado', 'align' => 'left'],
                    ['key' => 'accion', 'label' => 'Acción', 'align' => 'center'],
                ]
            ])
        </div>

        <div id="seccion-combinada" class="seccion-tipo" style="display: none;">
            @include('components.cotizaciones.table', [
                'sectionId' => 'combinada',
                'title' => 'Combinada',
                'cotizaciones' => $cotizacionesPrendaBordado,
                'pageParameterName' => $pageNameCotPB ?? 'page',
                'emptyMessage' => 'No hay cotizaciones combinadas',
                'columns' => [
                    ['key' => 'fecha', 'label' => 'Fecha', 'align' => 'left'],
                    ['key' => 'codigo', 'label' => 'Código', 'align' => 'left'],
                    ['key' => 'cliente', 'label' => 'Cliente', 'align' => 'left'],
                    ['key' => 'tipo', 'label' => 'Tipo', 'align' => 'left'],
                    ['key' => 'estado', 'label' => 'Estado', 'align' => 'left'],
                    ['key' => 'accion', 'label' => 'Acción', 'align' => 'center'],
                ]
            ])
        </div>
    </div>

    {{-- Borradores --}}
    <div id="tab-borradores" class="tab-content" style="display: none;">
        <div id="seccion-bor-todas" class="seccion-tipo" style="display: none;">
            @include('components.cotizaciones.table', [
                'sectionId' => 'bor-todas',
                'title' => 'Todos los Borradores',
                'cotizaciones' => $borradoresTodas,
                'pageParameterName' => $pageNameBorTodas ?? 'page',
                'emptyMessage' => 'No hay borradores',
                'columns' => [
                    ['key' => 'fecha', 'label' => 'Fecha', 'align' => 'left'],
                    ['key' => 'cliente', 'label' => 'Cliente', 'align' => 'left'],
                    ['key' => 'tipo', 'label' => 'Tipo', 'align' => 'left'],
                    ['key' => 'estado', 'label' => 'Estado', 'align' => 'left'],
                    ['key' => 'accion', 'label' => 'Acciones', 'align' => 'center'],
                ]
            ])
        </div>

        <div id="seccion-bor-prenda" class="seccion-tipo" style="display: none;">
            @include('components.cotizaciones.table', [
                'sectionId' => 'bor-prenda',
                'title' => 'Combinada',
                'cotizaciones' => $borradorespPrenda,
                'pageParameterName' => $pageNameBorPrenda ?? 'page',
                'emptyMessage' => 'No hay borradores combinados',
                'columns' => [
                    ['key' => 'fecha', 'label' => 'Fecha', 'align' => 'left'],
                    ['key' => 'cliente', 'label' => 'Cliente', 'align' => 'left'],
                    ['key' => 'tipo', 'label' => 'Tipo', 'align' => 'left'],
                    ['key' => 'estado', 'label' => 'Estado', 'align' => 'left'],
                    ['key' => 'accion', 'label' => 'Acciones', 'align' => 'center'],
                ]
            ])
        </div>

        <div id="seccion-bor-logo" class="seccion-tipo" style="display: none;">
            @include('components.cotizaciones.table', [
                'sectionId' => 'bor-logo',
                'title' => 'Logo',
                'cotizaciones' => $borradoresLogo,
                'pageParameterName' => $pageNameBorLogo ?? 'page',
                'emptyMessage' => 'No hay borradores de logo',
                'columns' => [
                    ['key' => 'fecha', 'label' => 'Fecha', 'align' => 'left'],
                    ['key' => 'cliente', 'label' => 'Cliente', 'align' => 'left'],
                    ['key' => 'tipo', 'label' => 'Tipo', 'align' => 'left'],
                    ['key' => 'estado', 'label' => 'Estado', 'align' => 'left'],
                    ['key' => 'accion', 'label' => 'Acciones', 'align' => 'center'],
                ]
            ])
        </div>

        <div id="seccion-bor-combinada" class="seccion-tipo" style="display: none;">
            @include('components.cotizaciones.table', [
                'sectionId' => 'bor-combinada',
                'title' => 'Combinada',
                'cotizaciones' => $borradorespPrendaBordado,
                'pageParameterName' => $pageNameBorPB ?? 'page',
                'emptyMessage' => 'No hay borradores combinados',
                'columns' => [
                    ['key' => 'fecha', 'label' => 'Fecha', 'align' => 'left'],
                    ['key' => 'cliente', 'label' => 'Cliente', 'align' => 'left'],
                    ['key' => 'tipo', 'label' => 'Tipo', 'align' => 'left'],
                    ['key' => 'estado', 'label' => 'Estado', 'align' => 'left'],
                    ['key' => 'accion', 'label' => 'Acciones', 'align' => 'center'],
                ]
            ])
        </div>
    </div>

<div id="btnLimpiarFiltros" onclick="limpiarTodosFiltros()">
    <i class="fas fa-times"></i> Limpiar Filtros
</div>

<!-- PDF se abre en nueva pestaña - Sin modal -->

<script src="{{ asset('js/asesores/cotizaciones/filtros-embudo.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones-index.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones-anular.js') }}"></script>
<script src="{{ asset('js/realtime-cotizaciones.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/contador/cotizacion.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/contador/lightbox-imagenes.js') }}?v={{ time() }}"></script>

<script>
    // Variables globales para PDF
    window.cotizacionIdActualPDF = null;
    window.tipoPDFActual = null;

    // Toggle del menú PDF para cotizaciones combinadas
    function toggleMenuPDF(cotizacionId, tipo) {
        if (tipo === 'PL') {
            // Para combinadas, crear menú emergente dinámico
            createPDFDropdown(cotizacionId);
        } else if (tipo === 'L') {
            // Logo: abrir PDF Logo en nueva pestaña
            abrirPDFEnPestana(cotizacionId, 'logo');
        }
    }

    // Crear menú emergente dinámico para PDF (Lee los datos JSON guardados)
    function createPDFDropdown(cotizacionId) {
        // Verificar si ya existe un dropdown
        const existingDropdown = document.querySelector(`.pdf-menu-dropdown[data-cot-id="${cotizacionId}"]`);
        if (existingDropdown) {
            existingDropdown.remove();
            return;
        }

        // Obtener datos de botones PDF desde el JSON incrustado
        const pdfDataScript = document.querySelector(`.pdf-buttons-data[data-cot-id="${cotizacionId}"]`);
        let pdfButtons = [];
        
        if (pdfDataScript) {
            try {
                pdfButtons = JSON.parse(pdfDataScript.textContent);
            } catch (e) {
                console.error('Error parsing PDF buttons data:', e);
            }
        }

        // Crear dropdown dinámicamente
        const dropdown = document.createElement('div');
        dropdown.className = 'pdf-menu-dropdown';
        dropdown.dataset.cotId = cotizacionId;
        
        let dropdownHTML = '';
        pdfButtons.forEach(btn => {
            dropdownHTML += `
                <a href="#" onclick="abrirPDFEnPestana(${cotizacionId}, '${btn.tipo}'); return false;" class="pdf-menu-option">
                    <i class="fas ${btn.icon}"></i> ${btn.label}
                </a>
            `;
        });
        
        dropdown.innerHTML = dropdownHTML;

        // Buscar el botón PDF
        const pdfButton = document.querySelector(`.pdf-menu-btn[data-cot-id="${cotizacionId}"]`);
        if (pdfButton) {
            const rect = pdfButton.getBoundingClientRect();
            dropdown.style.position = 'fixed';
            dropdown.style.top = (rect.top + 45) + 'px'; // Bajar el menú debajo del botón
            dropdown.style.left = (rect.left - 10) + 'px'; // Posicionar a la derecha del botón
            dropdown.style.zIndex = '9999';
            document.body.appendChild(dropdown);
            // Cerrar dropdown al hacer click fuera
            setTimeout(() => {
                document.addEventListener('click', function closeDropdown(e) {
                    if (!dropdown.contains(e.target) && !pdfButton.contains(e.target)) {
                        dropdown.remove();
                        document.removeEventListener('click', closeDropdown);
                    }
                });
            }, 0);
        }
    }

    // Event listener para botones PDF combinados
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(e) {
            const pdfBtn = e.target.closest('.pdf-menu-btn');
            if (pdfBtn) {
                e.preventDefault();
                createPDFDropdown(pdfBtn.dataset.cotId);
            }
        });
    });

    // Abrir PDF en nueva pestaña según el tipo
    function abrirPDFEnPestana(cotizacionId, tipoPDF) {
        let url = '';
        
        // Construir la URL según el tipo de PDF
        switch(tipoPDF) {
            case 'combinada':
                url = `/asesores/cotizacion/${cotizacionId}/pdf/combinada`;
                break;
            case 'prenda':
                url = `/asesores/cotizacion/${cotizacionId}/pdf/prenda`;
                break;
            case 'logo':
                url = `/asesores/cotizacion/${cotizacionId}/pdf/logo`;
                break;
            case 'epp':
                url = `/asesores/cotizacion/${cotizacionId}/pdf/epp`;
                break;
            default:
                url = `/asesores/cotizacion/${cotizacionId}/pdf/prenda`;
        }
        
        window.open(url, '_blank');
        
        // Cerrar el dropdown si está abierto
        const dropdown = document.querySelector(`.pdf-menu-dropdown[data-cot-id="${cotizacionId}"]`);
        if (dropdown) {
            dropdown.remove();
        }
    }

    // Cerrar menú PDF al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('button[onclick*="toggleMenuPDF"]') && 
            !e.target.closest('.pdf-menu-btn') && 
            !e.target.closest('.pdf-menu-dropdown') &&
            !e.target.closest('.menu-pdf')) {
            document.querySelectorAll('.pdf-menu-dropdown').forEach(m => m.remove());
            document.querySelectorAll('.menu-pdf').forEach(m => m.style.display = 'none');
        }
    });
</script>

<style>
    .menu-pdf {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .menu-pdf a {
        display: block;
        padding: 10px 12px;
        color: #374151;
        text-decoration: none;
        font-size: 0.85rem;
        transition: background 0.2s;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .menu-pdf a:last-child {
        border-bottom: none;
    }
    
    .menu-pdf a:hover {
        background: #f3f4f6;
    }

    /* Estilos para menú PDF emergente */
    .pdf-menu-dropdown {
        background: #ffffff;
        border: 2px solid #10b981;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        min-width: 180px;
        z-index: 9999;
        animation: slideDown 0.2s ease;
        display: flex;
        flex-direction: column;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .pdf-menu-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        color: #1f2937;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        border-bottom: 1px solid #e5e7eb;
        transition: all 0.2s ease;
    }

    .pdf-menu-option:last-child {
        border-bottom: none;
    }

    .pdf-menu-option:hover {
        background-color: #f0fdf4;
        color: #10b981;
        padding-left: 20px;
    }
</style>

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
        <div id="modalBody" class="modal-body" style="padding: 2rem; overflow-y: auto; background: white;"></div>
    </div>
</div>

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

<style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
    }

    .modal-content {
        background: #ffffff;
        border-radius: 16px;
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 40px 0 rgba(0, 0, 0, 0.15);
    }

    .modal-header {
        padding: 2rem;
        display: flex;
        justify-content: center;
        align-items: center;
        background: linear-gradient(135deg, #1e5ba8 0%, #1e40af 100%);
        color: #ffffff;
        border-radius: 16px 16px 0 0;
        position: relative;
    }

    .modal-header-logo {
        height: 70px;
        width: auto;
        object-fit: contain;
        flex-shrink: 0;
        filter: brightness(0) invert(1);
    }

    .modal.fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: 100vh;
        z-index: 9999;
    }

    .modal.fullscreen .modal-content {
        width: 100%;
        height: 100%;
        max-width: 100%;
        max-height: 100vh;
        border-radius: 0;
        display: flex;
        flex-direction: column;
    }

    .modal.fullscreen .modal-header {
        border-radius: 0;
        flex-shrink: 0;
        padding: 1.5rem 2rem;
    }

    .modal.fullscreen .modal-body {
        flex: 1;
        overflow-y: auto;
        padding: 2rem;
    }
</style>

@endsection


