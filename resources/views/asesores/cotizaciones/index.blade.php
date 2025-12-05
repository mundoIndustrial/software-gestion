@extends('layouts.asesores')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones y Borradores')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/cotizaciones/filtros-embudo.css') }}">
<link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-tabs.css') }}">
<style>
    .top-nav {
        display: none !important;
    }
    
    /* Estilos para tabla mejorada */
    table tbody tr {
        border-bottom: 1px solid #d1d5db !important;
        transition: background-color 0.2s ease;
    }
    
    table tbody tr:hover {
        background-color: #f9fafb !important;
    }
    
    table tbody tr:nth-child(even) {
        background-color: #f3f4f6;
    }
    
    table tbody tr:nth-child(even):hover {
        background-color: #e5e7eb !important;
    }
    
    /* Estilos personalizados para SweetAlert2 */
    .swal-custom-popup {
        width: 90% !important;
        max-width: 400px !important;
        padding: 24px !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15) !important;
    }
    
    .swal-custom-title {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        color: #1f2937 !important;
        margin-bottom: 12px !important;
    }
    
    .swal2-html-container {
        font-size: 0.95rem !important;
        color: #6b7280 !important;
        line-height: 1.5 !important;
    }
    
    .swal-custom-confirm,
    .swal-custom-cancel {
        padding: 10px 20px !important;
        font-size: 0.9rem !important;
        font-weight: 600 !important;
        border-radius: 6px !important;
        border: none !important;
        transition: all 0.3s ease !important;
    }
    
    .swal-custom-confirm {
        background-color: #ef4444 !important;
        color: white !important;
    }
    
    .swal-custom-confirm:hover {
        background-color: #dc2626 !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important;
    }
    
    .swal-custom-cancel {
        background-color: #e5e7eb !important;
        color: #374151 !important;
        margin-right: 8px !important;
    }
    
    .swal-custom-cancel:hover {
        background-color: #d1d5db !important;
        transform: translateY(-2px) !important;
    }
    
    .swal2-icon {
        width: 50px !important;
        height: 50px !important;
        margin: 0 auto 12px !important;
    }
    
    .swal2-icon.swal2-warning {
        border-color: #f59e0b !important;
        color: #f59e0b !important;
    }
    
    .swal2-icon.swal2-success {
        border-color: #10b981 !important;
        color: #10b981 !important;
    }
    
    .swal2-icon.swal2-error {
        border-color: #ef4444 !important;
        color: #ef4444 !important;
    }
    
    /* Estilos para Toast */
    .swal-toast-popup {
        width: auto !important;
        max-width: 350px !important;
        padding: 12px 16px !important;
        border-radius: 8px !important;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15) !important;
        background-color: #10b981 !important;
        border: none !important;
    }
    
    .swal-toast-title {
        font-size: 0.95rem !important;
        font-weight: 600 !important;
        color: white !important;
        margin: 0 !important;
    }
    
    .swal2-toast-container {
        top: 20px !important;
        right: 20px !important;
    }
    
    .swal2-toast .swal2-icon {
        width: 32px !important;
        height: 32px !important;
        margin: 0 8px 0 0 !important;
    }
    
    .swal2-toast .swal2-icon.swal2-success {
        border-color: white !important;
        color: white !important;
    }
    
    .swal2-timer-progress-bar {
        background: rgba(255, 255, 255, 0.7) !important;
    }
    
    /* Responsive */
    @media (max-width: 640px) {
        .swal-custom-popup {
            width: 95% !important;
            max-width: 350px !important;
            padding: 20px !important;
        }
        
        .swal-custom-title {
            font-size: 1.1rem !important;
        }
        
        .swal2-html-container {
            font-size: 0.9rem !important;
        }
        
        .swal2-toast-container {
            top: 10px !important;
            right: 10px !important;
        }
        
        .swal-toast-popup {
            max-width: 300px !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- HEADER PROFESIONAL -->
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); border-radius: 12px; padding: 20px 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(30, 58, 138, 0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 30px;">
            <!-- TÍTULO CON ICONO -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: rgba(255,255,255,0.15); padding: 10px 12px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-file-alt" style="color: white; font-size: 24px;"></i>
                </div>
                <div>
                    <h1 id="headerTitle" style="margin: 0; font-size: 1.5rem; color: white; font-weight: 700;">Cotizaciones</h1>
                    <p style="margin: 0; color: rgba(255,255,255,0.7); font-size: 0.85rem;">Gestiona tus cotizaciones</p>
                </div>
            </div>

            <!-- BUSCADOR Y BOTÓN -->
            <div style="display: flex; gap: 12px; align-items: center; flex: 1; max-width: 600px;">
                <div style="flex: 1; position: relative;">
                    <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" id="buscador" placeholder="Buscar por cliente..." onkeyup="filtrarCotizaciones()" style="padding: 10px 12px 10px 35px; border: none; border-radius: 6px; width: 100%; font-size: 0.9rem; background: rgba(255,255,255,0.95); transition: all 0.3s;" onfocus="this.style.background='white'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" onblur="this.style.background='rgba(255,255,255,0.95)'; this.style.boxShadow='none'">
                </div>
                
                <!-- BOTÓN REGISTRAR -->
                <a href="{{ route('asesores.pedidos.create') }}" style="background: white; color: #2c3e50; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.1); white-space: nowrap;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Registrar
                </a>
            </div>
        </div>
    </div>

    <!-- TABS PROFESIONALES - ESTADO -->
    <div style="display: flex; gap: 0; margin-bottom: 25px;">
        <button class="tab-btn active" onclick="mostrarTab('cotizaciones')" style="padding: 12px 24px; background: none; border: none; border-bottom: 3px solid #3498db; cursor: pointer; font-weight: 600; color: #333; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
            <i class="fas fa-check" style="font-size: 16px;"></i>
            Cotizaciones
        </button>
        <button class="tab-btn" onclick="mostrarTab('borradores')" style="padding: 12px 24px; background: none; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-weight: 600; color: #999; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
            <i class="fas fa-file" style="font-size: 16px;"></i>
            Borradores
        </button>
    </div>

    <!-- PASTILLAS PROFESIONALES - TIPOS DE COTIZACIÓN -->
    <div style="display: flex; align-items: center; gap: 20px; margin: 20px 0 25px 0; flex-wrap: wrap;">
        <p style="margin: 0; color: #666; font-weight: 600; font-size: 0.9rem; white-space: nowrap;">
            <i class="fas fa-layer-group"></i> FILTRAR POR TIPO:
        </p>
        
        <div class="cotizaciones-tabs-container" style="margin: 0; padding: 0; gap: 10px;">
            <!-- PASTILLA: TODAS -->
            <button type="button" class="cotizacion-tab-btn active" data-tipo="todas" onclick="mostrarTipo('todas')">
                <i class="fas fa-list cotizacion-tab-icon"></i>
                <span class="cotizacion-tab-label">TODAS</span>
            </button>

            <!-- PASTILLA: PRENDA -->
            <button type="button" class="cotizacion-tab-btn tab-prenda" data-tipo="P" onclick="mostrarTipo('P')">
                <i class="fas fa-shirt cotizacion-tab-icon"></i>
                <span class="cotizacion-tab-label">PRENDA</span>
            </button>

            <!-- PASTILLA: LOGO -->
            <button type="button" class="cotizacion-tab-btn tab-logo" data-tipo="L" onclick="mostrarTipo('L')">
                <i class="fas fa-palette cotizacion-tab-icon"></i>
                <span class="cotizacion-tab-label">LOGO</span>
            </button>

            <!-- PASTILLA: PRENDA/BORDADO -->
            <button type="button" class="cotizacion-tab-btn tab-prenda-bordado" data-tipo="PB" onclick="mostrarTipo('PB')">
                <i class="fas fa-sparkles cotizacion-tab-icon"></i>
                <span class="cotizacion-tab-label">PRENDA/BORDADO</span>
            </button>
        </div>
    </div>

    <!-- COTIZACIONES ENVIADAS -->
    <div id="tab-cotizaciones" class="tab-content">
        <!-- TODAS -->
        <div id="seccion-todas" class="seccion-tipo" style="display: block;">
            <h3 style="color: #1e40af; margin-top: 20px; margin-bottom: 15px; font-size: 1.1rem;">Todas las Cotizaciones <span style="font-size: 0.85rem; color: #666;">({{ $cotizacionesTodas->total() }} registros)</span></h3>
            @if($cotizacionesTodas->count() > 0)
                <div id="vista-tabla-todas" style="overflow-x: auto; margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                        <thead style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); border-bottom: 3px solid #1e3a8a;">
                            <tr>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">
                                    <div class="table-header-with-filter">
                                        <span>Fecha</span>
                                        <button class="filter-funnel-btn" data-filter-column="fecha" onclick="abrirFiltro('fecha')" title="Filtrar por fecha">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                    </div>
                                </th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">
                                    <div class="table-header-with-filter">
                                        <span>Código</span>
                                        <button class="filter-funnel-btn" data-filter-column="codigo" onclick="abrirFiltro('codigo')" title="Filtrar por código">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                    </div>
                                </th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">
                                    <div class="table-header-with-filter">
                                        <span>Cliente</span>
                                        <button class="filter-funnel-btn" data-filter-column="cliente" onclick="abrirFiltro('cliente')" title="Filtrar por cliente">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                    </div>
                                </th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">
                                    <div class="table-header-with-filter">
                                        <span>Tipo</span>
                                        <button class="filter-funnel-btn" data-filter-column="tipo" onclick="abrirFiltro('tipo')" title="Filtrar por tipo">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                    </div>
                                </th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">
                                    <div class="table-header-with-filter">
                                        <span>Estado</span>
                                        <button class="filter-funnel-btn" data-filter-column="estado" onclick="abrirFiltro('estado')" title="Filtrar por estado">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                    </div>
                                </th>
                                <th style="padding: 14px 12px; text-align: center; font-weight: 700; color: white; font-size: 0.9rem;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cotizacionesTodas as $cot)
                                <tr style="border-bottom: 1px solid #ecf0f1;">
                                    <td style="padding: 12px; color: #666; font-size: 0.9rem;" data-filter-column="fecha">{{ $cot->created_at->format('d/m/Y') }}</td>
                                    <td style="padding: 12px; color: #1e40af; font-size: 0.9rem; font-weight: 700;" data-filter-column="codigo">{{ $cot->numero_cotizacion ?? 'Por asignar' }}</td>
                                    <td style="padding: 12px; color: #333; font-size: 0.9rem;" data-filter-column="cliente">{{ $cot->cliente ?? 'Sin cliente' }}</td>
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
                                    <td style="padding: 12px;" data-filter-column="estado">
                                        <span style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                            {{ ucfirst($cot->estado) }}
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="{{ route('asesores.cotizaciones.show', $cot->id) }}" style="background: #1e40af; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Paginación Todas -->
                <div style="display: flex; justify-content: center; margin-bottom: 30px;">
                    {{ $cotizacionesTodas->links('pagination::bootstrap-custom', ['pageName' => $pageNameCotTodas]) }}
                </div>
            @else
                <div style="background: #f0f7ff; border: 2px dashed #3498db; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 30px;">
                    <p style="margin: 0; color: #666;">📭 No hay cotizaciones</p>
                </div>
            @endif
        </div>

        <!-- PRENDA -->
        <div id="seccion-prenda" class="seccion-tipo" style="display: none;">
            <h3 style="color: #1e40af; margin-top: 20px; margin-bottom: 15px; font-size: 1.1rem;">Prenda</h3>
            @if($cotizacionesPrenda->count() > 0)
                <div id="vista-tabla-prenda" style="overflow-x: auto; margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                        <thead style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); border-bottom: 3px solid #1e3a8a;">
                            <tr>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Fecha</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Código</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Cliente</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Tipo</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Estado</th>
                                <th style="padding: 14px 12px; text-align: center; font-weight: 700; color: white; font-size: 0.9rem;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cotizacionesPrenda as $cot)
                                <tr style="border-bottom: 1px solid #ecf0f1;">
                                    <td style="padding: 12px; color: #666; font-size: 0.9rem;">{{ $cot->created_at->format('d/m/Y') }}</td>
                                    <td style="padding: 12px; color: #1e40af; font-size: 0.9rem; font-weight: 700;">{{ $cot->numero_cotizacion ?? 'Por asignar' }}</td>
                                    <td style="padding: 12px; color: #333; font-size: 0.9rem;">{{ $cot->cliente ?? 'Sin cliente' }}</td>
                                    <td style="padding: 12px;">
                                        <span style="background: #e3f2fd; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                            Prenda
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <span style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                            {{ ucfirst($cot->estado) }}
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="{{ route('asesores.cotizaciones.show', $cot->id) }}" style="background: #1e40af; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Paginación Prenda -->
                <div style="display: flex; justify-content: center; margin-bottom: 30px;">
                    {{ $cotizacionesPrenda->links('pagination::bootstrap-custom', ['pageName' => $pageNameCotPrenda]) }}
                </div>
            @else
                <div style="background: #f0f7ff; border: 2px dashed #3498db; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 30px;">
                    <p style="margin: 0; color: #666;">📭 No hay cotizaciones de prenda</p>
                </div>
            @endif
        </div>

        <!-- LOGO -->
        <div id="seccion-logo" class="seccion-tipo" style="display: none;">
            <h3 style="color: #1e40af; margin-top: 20px; margin-bottom: 15px; font-size: 1.1rem;">Logo</h3>
            @if($cotizacionesLogo->count() > 0)
                <div id="vista-tabla-logo" style="overflow-x: auto; margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                        <thead style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); border-bottom: 3px solid #1e3a8a;">
                            <tr>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Fecha</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Código</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Cliente</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Tipo</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Estado</th>
                                <th style="padding: 14px 12px; text-align: center; font-weight: 700; color: white; font-size: 0.9rem;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cotizacionesLogo as $cot)
                                <tr style="border-bottom: 1px solid #ecf0f1;">
                                    <td style="padding: 12px; color: #666; font-size: 0.9rem;">{{ $cot->created_at->format('d/m/Y') }}</td>
                                    <td style="padding: 12px; color: #1e40af; font-size: 0.9rem; font-weight: 700;">{{ $cot->numero_cotizacion ?? 'Por asignar' }}</td>
                                    <td style="padding: 12px; color: #333; font-size: 0.9rem;">{{ $cot->cliente ?? 'Sin cliente' }}</td>
                                    <td style="padding: 12px;">
                                        <span style="background: #e3f2fd; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                            Logo
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <span style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                            {{ ucfirst($cot->estado) }}
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="{{ route('asesores.cotizaciones.show', $cot->id) }}" style="background: #1e40af; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Paginación Logo -->
                <div style="display: flex; justify-content: center; margin-bottom: 30px;">
                    {{ $cotizacionesLogo->links('pagination::bootstrap-custom', ['pageName' => $pageNameCotLogo]) }}
                </div>
            @else
                <div style="background: #f0f7ff; border: 2px dashed #3498db; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 30px;">
                    <p style="margin: 0; color: #666;">📭 No hay cotizaciones de logo</p>
                </div>
            @endif
        </div>

        <div id="seccion-pb" class="seccion-tipo" style="display: none;">
            <h3 style="color: #1e40af; margin-top: 20px; margin-bottom: 15px; font-size: 1.1rem;">Prenda/Bordado</h3>
            @if($cotizacionesPrendaBordado->count() > 0)
                <div id="vista-tabla-pb" style="overflow-x: auto; margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                        <thead style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); border-bottom: 3px solid #1e3a8a;">
                            <tr>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Fecha</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Código</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Cliente</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Tipo</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Estado</th>
                                <th style="padding: 14px 12px; text-align: center; font-weight: 700; color: white; font-size: 0.9rem;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cotizacionesPrendaBordado as $cot)
                                <tr style="border-bottom: 1px solid #ecf0f1;">
                                    <td style="padding: 12px; color: #666; font-size: 0.9rem;">{{ $cot->created_at->format('d/m/Y') }}</td>
                                    <td style="padding: 12px; color: #1e40af; font-size: 0.9rem; font-weight: 700;">{{ $cot->numero_cotizacion ?? 'Por asignar' }}</td>
                                    <td style="padding: 12px; color: #333; font-size: 0.9rem;">{{ $cot->cliente ?? 'Sin cliente' }}</td>
                                    <td style="padding: 12px;">
                                        <span style="background: #e3f2fd; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                            Prenda/Bordado
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <span style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                            {{ ucfirst($cot->estado) }}
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="{{ route('asesores.cotizaciones.show', $cot->id) }}" style="background: #1e40af; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Paginación Prenda/Bordado -->
                <div style="display: flex; justify-content: center; margin-bottom: 30px;">
                    {{ $cotizacionesPrendaBordado->links('pagination::bootstrap-custom', ['pageName' => $pageNameCotPB]) }}
                </div>
            @else
                <div style="background: #f0f7ff; border: 2px dashed #3498db; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 30px;">
                    <p style="margin: 0; color: #666;">📭 No hay cotizaciones de prenda/bordado</p>
                </div>
            @endif
        </div>

        @if($cotizacionesPrenda->isEmpty() && $cotizacionesLogo->isEmpty() && $cotizacionesPrendaBordado->isEmpty())
            <div style="background: #f0f7ff; border: 2px dashed #3498db; border-radius: 8px; padding: 40px; text-align: center;">
                <p style="margin: 0; color: #666; font-size: 1.1rem;">
                    📭 No hay cotizaciones enviadas aún
                </p>
                <a href="{{ route('asesores.pedidos.create') }}" style="display: inline-block; margin-top: 15px; background: #3498db; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none;">
                    Crear Primera Cotización
                </a>
            </div>
        @endif
    </div>

    <!-- BORRADORES -->
    <div id="tab-borradores" class="tab-content" style="display: none;">
        <!-- TODAS -->
        <div id="seccion-bor-todas" class="seccion-tipo" style="display: none;">
            <h3 style="color: #f39c12; margin-top: 20px; margin-bottom: 15px; font-size: 1.1rem;">Todos los Borradores</h3>
            @if($borradoresTodas->count() > 0)
                <div id="vista-tabla-bor-todas" style="overflow-x: auto; margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                        <thead style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); border-bottom: 3px solid #e67e22;">
                            <tr>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Fecha</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Cliente</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Tipo</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Estado</th>
                                <th style="padding: 14px 12px; text-align: center; font-weight: 700; color: white; font-size: 0.9rem;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($borradoresTodas as $bor)
                                <tr style="border-bottom: 1px solid #ecf0f1;">
                                    <td style="padding: 12px; color: #666; font-size: 0.9rem;">{{ $bor->created_at->format('d/m/Y') }}</td>
                                    <td style="padding: 12px; color: #333; font-size: 0.9rem;">{{ $bor->cliente ?? 'Sin cliente' }}</td>
                                    <td style="padding: 12px;">
                                        <span style="background: #e3f2fd; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                            @if($bor->obtenerTipoCotizacion() === 'P')
                                                Prenda
                                            @elseif($bor->obtenerTipoCotizacion() === 'B')
                                                Logo
                                            @elseif($bor->obtenerTipoCotizacion() === 'PB')
                                                Prenda/Bordado
                                            @else
                                                {{ $bor->tipoCotizacion?->nombre ?? 'Sin tipo' }}
                                            @endif
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <span style="background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                            Borrador
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="{{ route('asesores.cotizaciones.show', $bor->id) }}" style="background: #f39c12; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600; margin-right: 5px;">Editar</a>
                                        <a href="#" onclick="eliminarBorrador({{ $bor->id }}); return false;" style="background: #e74c3c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600;">Eliminar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Paginación Borradores Todas -->
                <div style="display: flex; justify-content: center; margin-bottom: 30px;">
                    {{ $borradoresTodas->links('pagination::bootstrap-custom', ['pageName' => $pageNameBorTodas]) }}
                </div>
            @else
                <div style="background: #fff3cd; border: 2px dashed #f39c12; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 30px;">
                    <p style="margin: 0; color: #856404;">📝 No hay borradores</p>
                </div>
            @endif
        </div>

        <!-- PRENDA -->
        <div id="seccion-bor-prenda" class="seccion-tipo" style="display: block;">
            <h3 style="color: #f39c12; margin-top: 20px; margin-bottom: 15px; font-size: 1.1rem;">Prenda</h3>
            @if($borradorespPrenda->count() > 0)
                <div id="vista-tabla-bor-prenda" style="overflow-x: auto; margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                        <thead style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); border-bottom: 3px solid #e67e22;">
                            <tr>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Fecha</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Cliente</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Tipo</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Estado</th>
                                <th style="padding: 14px 12px; text-align: center; font-weight: 700; color: white; font-size: 0.9rem;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($borradorespPrenda as $bor)
                                <tr style="border-bottom: 1px solid #ecf0f1;">
                                    <td style="padding: 12px; color: #666; font-size: 0.9rem;">{{ $bor->created_at->format('d/m/Y') }}</td>
                                    <td style="padding: 12px; color: #333; font-size: 0.9rem;">{{ $bor->cliente ?? 'Sin cliente' }}</td>
                                    <td style="padding: 12px;">
                                        <span style="background: #e3f2fd; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                            Prenda
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <span style="background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                            Borrador
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="{{ route('asesores.cotizaciones.show', $bor->id) }}" style="background: #f39c12; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600; margin-right: 5px;">Editar</a>
                                        <a href="#" onclick="eliminarBorrador({{ $bor->id }}); return false;" style="background: #e74c3c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600;">Eliminar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Paginación Borradores Prenda -->
                <div style="display: flex; justify-content: center; margin-bottom: 30px;">
                    {{ $borradorespPrenda->links('pagination::bootstrap-custom', ['pageName' => $pageNameBorPrenda]) }}
                </div>
            @else
                <div style="background: #fff3cd; border: 2px dashed #f39c12; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 30px;">
                    <p style="margin: 0; color: #856404;">📝 No hay borradores de prenda</p>
                </div>
            @endif
        </div>

        <!-- LOGO -->
        <div id="seccion-bor-logo" class="seccion-tipo" style="display: none;">
            <h3 style="color: #f39c12; margin-top: 20px; margin-bottom: 15px; font-size: 1.1rem;">Logo</h3>
            @if($borradoresLogo->count() > 0)
                <div id="vista-tabla-bor-logo" style="overflow-x: auto; margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                        <thead style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); border-bottom: 3px solid #e67e22;">
                            <tr>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Fecha</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Cliente</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Tipo</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Estado</th>
                                <th style="padding: 14px 12px; text-align: center; font-weight: 700; color: white; font-size: 0.9rem;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($borradoresLogo as $bor)
                                <tr style="border-bottom: 1px solid #ecf0f1;">
                                    <td style="padding: 12px; color: #666; font-size: 0.9rem;">{{ $bor->created_at->format('d/m/Y') }}</td>
                                    <td style="padding: 12px; color: #333; font-size: 0.9rem;">{{ $bor->cliente ?? 'Sin cliente' }}</td>
                                    <td style="padding: 12px;">
                                        <span style="background: #e3f2fd; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                            Logo
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <span style="background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                            Borrador
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="{{ route('asesores.cotizaciones.show', $bor->id) }}" style="background: #f39c12; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600; margin-right: 5px;">Editar</a>
                                        <a href="#" onclick="eliminarBorrador({{ $bor->id }}); return false;" style="background: #e74c3c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600;">Eliminar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Paginación Borradores Logo -->
                <div style="display: flex; justify-content: center; margin-bottom: 30px;">
                    {{ $borradoresLogo->links('pagination::bootstrap-custom', ['pageName' => $pageNameBorLogo]) }}
                </div>
            @else
                <div style="background: #fff3cd; border: 2px dashed #f39c12; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 30px;">
                    <p style="margin: 0; color: #856404;">📝 No hay borradores de logo</p>
                </div>
            @endif
        </div>

        <!-- PRENDA/BORDADO -->
        <div id="seccion-bor-pb" class="seccion-tipo" style="display: none;">
            <h3 style="color: #f39c12; margin-top: 20px; margin-bottom: 15px; font-size: 1.1rem;">Prenda/Bordado</h3>
            @if($borradorespPrendaBordado->count() > 0)
                <div id="vista-tabla-bor-pb" style="overflow-x: auto; margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                        <thead style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); border-bottom: 3px solid #e67e22;">
                            <tr>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Fecha</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Cliente</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Tipo</th>
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem;">Estado</th>
                                <th style="padding: 14px 12px; text-align: center; font-weight: 700; color: white; font-size: 0.9rem;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($borradorespPrendaBordado as $bor)
                                <tr style="border-bottom: 1px solid #ecf0f1;">
                                    <td style="padding: 12px; color: #666; font-size: 0.9rem;">{{ $bor->created_at->format('d/m/Y') }}</td>
                                    <td style="padding: 12px; color: #333; font-size: 0.9rem;">{{ $bor->cliente ?? 'Sin cliente' }}</td>
                                    <td style="padding: 12px;">
                                        <span style="background: #e3f2fd; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                            Prenda/Bordado
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <span style="background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                            Borrador
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="{{ route('asesores.cotizaciones.show', $bor->id) }}" style="background: #f39c12; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600; margin-right: 5px;">Editar</a>
                                        <a href="#" onclick="eliminarBorrador({{ $bor->id }}); return false;" style="background: #e74c3c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600;">Eliminar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Paginación Borradores Prenda/Bordado -->
                <div style="display: flex; justify-content: center; margin-bottom: 30px;">
                    {{ $borradorespPrendaBordado->links('pagination::bootstrap-custom', ['pageName' => $pageNameBorPB]) }}
                </div>
            @else
                <div style="background: #fff3cd; border: 2px dashed #f39c12; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 30px;">
                    <p style="margin: 0; color: #856404;">📝 No hay borradores de prenda/bordado</p>
                </div>
            @endif
        </div>

        @if($borradorespPrenda->isEmpty() && $borradoresLogo->isEmpty() && $borradorespPrendaBordado->isEmpty())
            <div style="background: #f0f7ff; border: 2px dashed #3498db; border-radius: 8px; padding: 40px; text-align: center;">
                <p style="margin: 0; color: #666; font-size: 1.1rem;">
                    📭 No hay borradores
                </p>
            </div>
        @endif
    </div>
</div>

<script>
let vistaActual = 'tarjetas';

// Activar tab según el hash en la URL
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.substring(1); // Obtener hash sin el #
    if (hash === 'borradores' || hash === 'cotizaciones') {
        mostrarTabPorHash(hash);
    }
});

function mostrarTabPorHash(tab) {
    // Ocultar todos los tabs
    document.getElementById('tab-cotizaciones').style.display = 'none';
    document.getElementById('tab-borradores').style.display = 'none';
    
    // Desactivar todos los botones
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.style.borderBottomColor = 'transparent';
        btn.style.color = '#999';
    });
    
    // Mostrar tab seleccionado
    document.getElementById('tab-' + tab).style.display = 'block';
    
    // Activar botón seleccionado
    const buttons = document.querySelectorAll('.tab-btn');
    if (tab === 'cotizaciones') {
        buttons[0].style.borderBottomColor = '#3498db';
        buttons[0].style.color = '#333';
    } else if (tab === 'borradores') {
        buttons[1].style.borderBottomColor = '#3498db';
        buttons[1].style.color = '#333';
    }
    
    // Actualizar título dinámicamente
    const headerTitle = document.getElementById('headerTitle');
    const headerDesc = document.querySelector('p[style*="rgba(255,255,255,0.7)"]');
    
    if (tab === 'cotizaciones') {
        headerTitle.textContent = 'Cotizaciones';
        headerDesc.textContent = 'Gestiona tus cotizaciones';
    } else if (tab === 'borradores') {
        headerTitle.textContent = 'Borradores';
        headerDesc.textContent = 'Gestiona tus borradores';
    }
}

function mostrarTab(tab) {
    // Ocultar todos los tabs
    document.getElementById('tab-cotizaciones').style.display = 'none';
    document.getElementById('tab-borradores').style.display = 'none';
    
    // Desactivar todos los botones
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.style.borderBottomColor = 'transparent';
        btn.style.color = '#999';
    });
    
    // Mostrar tab seleccionado
    document.getElementById('tab-' + tab).style.display = 'block';
    
    // Activar botón seleccionado
    event.target.style.borderBottomColor = '#3498db';
    event.target.style.color = '#333';
    
    // Actualizar título dinámicamente
    const headerTitle = document.getElementById('headerTitle');
    const headerDesc = document.querySelector('p[style*="rgba(255,255,255,0.7)"]');
    
    if (tab === 'cotizaciones') {
        headerTitle.textContent = 'Cotizaciones';
        headerDesc.textContent = 'Gestiona tus cotizaciones';
    } else if (tab === 'borradores') {
        headerTitle.textContent = 'Borradores';
        headerDesc.textContent = 'Gestiona tus borradores';
    }
}

function cambiarVista(vista) {
    vistaActual = vista;
    
    // Actualizar botones
    document.getElementById('btn-tarjetas').style.background = vista === 'tarjetas' ? '#3498db' : 'transparent';
    document.getElementById('btn-tarjetas').style.color = vista === 'tarjetas' ? 'white' : '#666';
    document.getElementById('btn-tabla').style.background = vista === 'tabla' ? '#3498db' : 'transparent';
    document.getElementById('btn-tabla').style.color = vista === 'tabla' ? 'white' : '#666';
    
    // Cambiar vista en cotizaciones
    document.getElementById('vista-tarjetas-cot').style.display = vista === 'tarjetas' ? 'grid' : 'none';
    document.getElementById('vista-tabla-cot').style.display = vista === 'tabla' ? 'block' : 'none';
    
    // Cambiar vista en borradores
    document.getElementById('vista-tarjetas-bor').style.display = vista === 'tarjetas' ? 'grid' : 'none';
    document.getElementById('vista-tabla-bor').style.display = vista === 'tabla' ? 'block' : 'none';
}

function mostrarTipo(tipo) {
    // Actualizar botones de tipo
    document.querySelectorAll('.tipo-tab-btn').forEach(btn => {
        btn.style.borderBottomColor = 'transparent';
        btn.style.color = '#666';
    });
    
    // Activar botón seleccionado
    if (event && event.target) {
        event.target.style.borderBottomColor = '#3498db';
        event.target.style.color = '#333';
    }
    
    // Ocultar todas las secciones
    const seccionesActuales = document.querySelectorAll('#tab-cotizaciones .seccion-tipo, #tab-borradores .seccion-tipo');
    seccionesActuales.forEach(seccion => {
        seccion.style.display = 'none';
    });
    
    // Mostrar sección seleccionada
    if (tipo === 'todas') {
        document.getElementById('seccion-todas').style.display = 'block';
        document.getElementById('seccion-bor-todas').style.display = 'block';
    } else if (tipo === 'P') {
        document.getElementById('seccion-prenda').style.display = 'block';
        document.getElementById('seccion-bor-prenda').style.display = 'block';
    } else if (tipo === 'L') {
        document.getElementById('seccion-logo').style.display = 'block';
        document.getElementById('seccion-bor-logo').style.display = 'block';
    } else if (tipo === 'PB') {
        document.getElementById('seccion-pb').style.display = 'block';
        document.getElementById('seccion-bor-pb').style.display = 'block';
    }
}

function filtrarCotizaciones() {
    const busqueda = document.getElementById('buscador').value.toLowerCase();
    
    // Filtrar tarjetas de cotizaciones
    const tarjetasCot = document.querySelectorAll('#vista-tarjetas-cot > div');
    tarjetasCot.forEach(tarjeta => {
        const cliente = tarjeta.querySelector('h4').textContent.toLowerCase();
        tarjeta.style.display = cliente.includes(busqueda) ? 'block' : 'none';
    });
    
    // Filtrar tabla de cotizaciones
    const filasCot = document.querySelectorAll('#vista-tabla-cot tbody tr');
    filasCot.forEach(fila => {
        const cliente = fila.querySelector('td:nth-child(2)').textContent.toLowerCase();
        fila.style.display = cliente.includes(busqueda) ? 'table-row' : 'none';
    });
    
    // Filtrar tarjetas de borradores
    const tarjetasBor = document.querySelectorAll('#vista-tarjetas-bor > div');
    tarjetasBor.forEach(tarjeta => {
        const cliente = tarjeta.querySelector('h4').textContent.toLowerCase();
        tarjeta.style.display = cliente.includes(busqueda) ? 'block' : 'none';
    });
    
    // Filtrar tabla de borradores
    const filasBor = document.querySelectorAll('#vista-tabla-bor tbody tr');
    filasBor.forEach(fila => {
        const cliente = fila.querySelector('td:nth-child(2)').textContent.toLowerCase();
        fila.style.display = cliente.includes(busqueda) ? 'table-row' : 'none';
    });
}

function eliminarCotizacion(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta cotización?')) {
        fetch(`/asesores/cotizaciones/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ Cotización eliminada');
                location.reload();
            } else {
                alert('✗ Error al eliminar');
            }
        });
    }
}

function eliminarBorrador(id) {
    Swal.fire({
        title: '¿Eliminar borrador?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal-custom-popup',
            title: 'swal-custom-title',
            confirmButton: 'swal-custom-confirm',
            cancelButton: 'swal-custom-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/asesores/cotizaciones/${id}/borrador`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Encontrar la fila del borrador y eliminarla
                    const filaTabla = document.querySelector(`#vista-tabla-bor tbody tr:has(button[onclick="eliminarBorrador(${id})"])`);
                    if (filaTabla) {
                        filaTabla.style.transition = 'opacity 0.3s ease';
                        filaTabla.style.opacity = '0';
                        setTimeout(() => filaTabla.remove(), 300);
                    }
                    
                    // Mostrar toast de éxito
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: '¡Borrador eliminado!',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        },
                        customClass: {
                            popup: 'swal-toast-popup',
                            title: 'swal-toast-title'
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudo eliminar el borrador',
                        icon: 'error',
                        confirmButtonColor: '#1e40af',
                        customClass: {
                            popup: 'swal-custom-popup',
                            title: 'swal-custom-title',
                            confirmButton: 'swal-custom-confirm'
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al eliminar el borrador',
                    icon: 'error',
                    confirmButtonColor: '#1e40af',
                    customClass: {
                        popup: 'swal-custom-popup',
                        title: 'swal-custom-title',
                        confirmButton: 'swal-custom-confirm'
                    }
                });
            });
        }
    });
}

// Debug logs para paginación
console.log('=== DEBUG PAGINACIÓN ===');
console.log('URL actual:', window.location.href);
console.log('Query params:', new URLSearchParams(window.location.search));
console.log('Sección Todas - PageName:', '{{ $pageNameCotTodas ?? "NO DEFINIDO" }}');
console.log('Sección Prenda - PageName:', '{{ $pageNameCotPrenda ?? "NO DEFINIDO" }}');
console.log('Sección Logo - PageName:', '{{ $pageNameCotLogo ?? "NO DEFINIDO" }}');
console.log('Sección PB - PageName:', '{{ $pageNameCotPB ?? "NO DEFINIDO" }}');

// Verificar los links de paginación en el DOM
const paginationLinks = document.querySelectorAll('a[href*="page_"]');
console.log('Total de links de paginación encontrados:', paginationLinks.length);
paginationLinks.forEach((link, index) => {
    console.log(`Link ${index}:`, link.href, '| Text:', link.textContent);
});

// Verificar cuántas filas hay en cada tabla
const tables = document.querySelectorAll('table tbody');
console.log('Total de tablas:', tables.length);
tables.forEach((table, index) => {
    const rows = table.querySelectorAll('tr');
    console.log(`Tabla ${index} - Filas:`, rows.length);
    // Mostrar los tipos de cotizaciones en cada tabla
    const tipos = [];
    rows.forEach(row => {
        const tipoSpan = row.querySelector('span:nth-of-type(1)');
        if (tipoSpan) tipos.push(tipoSpan.textContent.trim());
    });
    console.log(`Tabla ${index} - Tipos:`, tipos);
});

// ============================================
// MODALES DE FILTRO
// ============================================
</script>

<!-- MODALES DE FILTRO -->

<!-- Modal Filtro Fecha -->
<div id="filter-modal-fecha" class="filter-modal">
    <div class="filter-modal-content">
        <div class="filter-modal-header">
            <h3 class="filter-modal-title">📅 Filtrar por Fecha</h3>
            <button class="filter-modal-close" onclick="cerrarFiltro('fecha')">&times;</button>
        </div>
        <div class="filter-modal-body">
            <div class="filter-group">
                <label class="filter-group-label">Selecciona las fechas</label>
                <div class="filter-checkbox-group"></div>
            </div>
        </div>
        <div class="filter-modal-footer">
            <button class="filter-btn filter-btn-clear" onclick="limpiarFiltroColumna('fecha')">Limpiar</button>
            <button class="filter-btn filter-btn-apply" onclick="aplicarFiltroColumna('fecha')">Aplicar</button>
        </div>
    </div>
</div>

<!-- Modal Filtro Código -->
<div id="filter-modal-codigo" class="filter-modal">
    <div class="filter-modal-content">
        <div class="filter-modal-header">
            <h3 class="filter-modal-title">🔢 Filtrar por Código</h3>
            <button class="filter-modal-close" onclick="cerrarFiltro('codigo')">&times;</button>
        </div>
        <div class="filter-modal-body">
            <div class="filter-group">
                <label class="filter-group-label">Selecciona los códigos</label>
                <div class="filter-checkbox-group"></div>
            </div>
        </div>
        <div class="filter-modal-footer">
            <button class="filter-btn filter-btn-clear" onclick="limpiarFiltroColumna('codigo')">Limpiar</button>
            <button class="filter-btn filter-btn-apply" onclick="aplicarFiltroColumna('codigo')">Aplicar</button>
        </div>
    </div>
</div>

<!-- Modal Filtro Cliente -->
<div id="filter-modal-cliente" class="filter-modal">
    <div class="filter-modal-content">
        <div class="filter-modal-header">
            <h3 class="filter-modal-title">👤 Filtrar por Cliente</h3>
            <button class="filter-modal-close" onclick="cerrarFiltro('cliente')">&times;</button>
        </div>
        <div class="filter-modal-body">
            <div class="filter-group">
                <label class="filter-group-label">Selecciona los clientes</label>
                <div class="filter-checkbox-group"></div>
            </div>
        </div>
        <div class="filter-modal-footer">
            <button class="filter-btn filter-btn-clear" onclick="limpiarFiltroColumna('cliente')">Limpiar</button>
            <button class="filter-btn filter-btn-apply" onclick="aplicarFiltroColumna('cliente')">Aplicar</button>
        </div>
    </div>
</div>

<!-- Modal Filtro Tipo -->
<div id="filter-modal-tipo" class="filter-modal">
    <div class="filter-modal-content">
        <div class="filter-modal-header">
            <h3 class="filter-modal-title">🏷️ Filtrar por Tipo</h3>
            <button class="filter-modal-close" onclick="cerrarFiltro('tipo')">&times;</button>
        </div>
        <div class="filter-modal-body">
            <div class="filter-group">
                <label class="filter-group-label">Selecciona los tipos</label>
                <div class="filter-checkbox-group"></div>
            </div>
        </div>
        <div class="filter-modal-footer">
            <button class="filter-btn filter-btn-clear" onclick="limpiarFiltroColumna('tipo')">Limpiar</button>
            <button class="filter-btn filter-btn-apply" onclick="aplicarFiltroColumna('tipo')">Aplicar</button>
        </div>
    </div>
</div>

<!-- Modal Filtro Estado -->
<div id="filter-modal-estado" class="filter-modal">
    <div class="filter-modal-content">
        <div class="filter-modal-header">
            <h3 class="filter-modal-title">✅ Filtrar por Estado</h3>
            <button class="filter-modal-close" onclick="cerrarFiltro('estado')">&times;</button>
        </div>
        <div class="filter-modal-body">
            <div class="filter-group">
                <label class="filter-group-label">Selecciona los estados</label>
                <div class="filter-checkbox-group"></div>
            </div>
        </div>
        <div class="filter-modal-footer">
            <button class="filter-btn filter-btn-clear" onclick="limpiarFiltroColumna('estado')">Limpiar</button>
            <button class="filter-btn filter-btn-apply" onclick="aplicarFiltroColumna('estado')">Aplicar</button>
        </div>
    </div>
</div>

<!-- Botón para limpiar todos los filtros (flotante) -->
<div style="position: fixed; bottom: 30px; right: 30px; z-index: 999;">
    <button onclick="limpiarTodosFiltros()" style="
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 50px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        transition: all 0.3s ease;
        display: none;
        align-items: center;
        gap: 8px;
    " id="btnLimpiarFiltros" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(52, 152, 219, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(52, 152, 219, 0.3)'">
        <i class="fas fa-times"></i> Limpiar Filtros
    </button>
</div>

<script src="{{ asset('js/asesores/cotizaciones/filtros-embudo.js') }}"></script>

<script>
// Mostrar/ocultar botón de limpiar filtros
document.addEventListener('DOMContentLoaded', () => {
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    
    // Observar cambios en los filtros activos
    const observer = setInterval(() => {
        if (filtroEmbudo && Object.keys(filtroEmbudo.filtrosActivos).length > 0) {
            btnLimpiar.style.display = 'flex';
        } else {
            btnLimpiar.style.display = 'none';
        }
    }, 100);
});

// Cambiar tabla cuando se cambia de tab
function mostrarTab(nombreTab) {
    // Cambiar tab
    document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
    document.getElementById(`tab-${nombreTab}`).style.display = 'block';
    
    // Cambiar botones de tab
    document.querySelectorAll('.tab-btn').forEach(btn => btn.style.borderBottomColor = 'transparent');
    event.target.style.borderBottomColor = '#3498db';
    event.target.style.color = '#333';
    
    // Actualizar tabla en filtro
    if (filtroEmbudo) {
        cambiarTablaFiltro(nombreTab);
    }
}

// Cambiar tipo de cotización
function mostrarTipo(tipo) {
    // Ocultar todas las secciones
    document.querySelectorAll('.seccion-tipo').forEach(sec => sec.style.display = 'none');
    
    // Mostrar la seleccionada
    if (tipo === 'todas') {
        document.getElementById('seccion-todas').style.display = 'block';
        document.getElementById('seccion-bor-todas').style.display = 'block';
    } else if (tipo === 'P') {
        document.getElementById('seccion-prenda').style.display = 'block';
        document.getElementById('seccion-bor-prenda').style.display = 'block';
    } else if (tipo === 'L') {
        document.getElementById('seccion-logo').style.display = 'block';
    } else if (tipo === 'PB') {
        document.getElementById('seccion-pb').style.display = 'block';
    }
    
    // Cambiar pastillas activas
    document.querySelectorAll('.cotizacion-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`.cotizacion-tab-btn[data-tipo="${tipo}"]`).classList.add('active');
}
</script>

@endsection

