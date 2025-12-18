@extends('layouts.asesores')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones y Borradores')

@push('styles')
{{-- CSS específicos del listado de cotizaciones - lazy loaded --}}
<link rel="stylesheet" href="{{ asset('css/cotizaciones/filtros-embudo.css') }}?v={{ time() }}" media="print" onload="this.media='all'">
<link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-tabs.css') }}?v={{ time() }}" media="print" onload="this.media='all'">
<link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-index.css') }}?v={{ time() }}" media="print" onload="this.media='all'">
<link rel="stylesheet" href="{{ asset('css/realtime-cotizaciones.css') }}?v={{ time() }}">
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
            ['code' => 'RF', 'label' => 'Reflectivo', 'icon' => 'fas fa-lightbulb', 'active' => false],
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

        <div id="seccion-rf" class="seccion-tipo" style="display: none;">
            @include('components.cotizaciones.table', [
                'sectionId' => 'rf',
                'title' => 'Reflectivo',
                'cotizaciones' => $cotizacionesReflectivo,
                'pageParameterName' => $pageNameCotRF ?? 'page',
                'emptyMessage' => 'No hay cotizaciones de reflectivo',
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

        <div id="seccion-bor-rf" class="seccion-tipo" style="display: none;">
            @include('components.cotizaciones.table', [
                'sectionId' => 'bor-rf',
                'title' => 'Reflectivo',
                'cotizaciones' => $borradoresReflectivo,
                'pageParameterName' => $pageNameBorRF ?? 'page',
                'emptyMessage' => 'No hay borradores de reflectivo',
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

<script>
    // Variables globales para PDF
    window.cotizacionIdActualPDF = null;
    window.tipoPDFActual = null;

    // Toggle del menú PDF para cotizaciones combinadas
    function toggleMenuPDF(cotizacionId, tipo) {
        if (tipo === 'PL') {
            // Para combinadas, mostrar menú
            const menu = document.getElementById(`menu-pdf-${cotizacionId}`);
            if (menu) {
                menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
                // Cerrar otros menús
                document.querySelectorAll('.menu-pdf').forEach(m => {
                    if (m.id !== `menu-pdf-${cotizacionId}`) {
                        m.style.display = 'none';
                    }
                });
            }
        } else if (tipo === 'RF') {
            // Reflectivo: abrir PDF Prenda en nueva pestaña
            abrirPDFEnPestana(cotizacionId, 'prenda');
        } else if (tipo === 'L') {
            // Logo: abrir PDF Logo en nueva pestaña
            abrirPDFEnPestana(cotizacionId, 'logo');
        }
    }

    // Abrir PDF en nueva pestaña
    function abrirPDFEnPestana(cotizacionId, tipoPDF) {
        const url = `/asesores/cotizacion/${cotizacionId}/pdf?tipo=${tipoPDF}`;
        window.open(url, '_blank');
    }

    // Cerrar menú PDF al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('button[onclick*="toggleMenuPDF"]') && !e.target.closest('.menu-pdf')) {
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
</style>

@endsection

