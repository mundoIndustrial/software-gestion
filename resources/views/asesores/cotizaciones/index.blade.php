@extends('asesores.layout')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones y Borradores')

@push('styles')
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

    <!-- TABS PROFESIONALES -->
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

    <!-- COTIZACIONES ENVIADAS -->
    <div id="tab-cotizaciones" class="tab-content">
        @if($cotizaciones->count() > 0)
            <!-- VISTA TARJETAS -->
            <div id="vista-tarjetas-cot" style="display: none; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px;">
                @foreach($cotizaciones as $cot)
                    <div style="background: white; border: 1px solid #ecf0f1; border-radius: 6px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); transition: all 0.3s;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="margin: 0; color: #333; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $cot->cliente ?? 'Sin cliente' }}</h4>
                                @if(auth()->user() && auth()->user()->role && auth()->user()->role->name === 'asesor')
                                    <p style="margin: 2px 0 0 0; color: #999; font-size: 0.8rem;">ID: #{{ $cot->id }}</p>
                                @endif
                            </div>
                            <span style="background: #d4edda; color: #155724; padding: 3px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: bold; white-space: nowrap; margin-left: 5px;">
                                {{ ucfirst($cot->estado) }}
                            </span>
                        </div>
                        <div style="background: #f8f9fa; padding: 8px; border-radius: 4px; margin-bottom: 10px; font-size: 0.8rem;">
                            <p style="margin: 2px 0;"><strong>Fecha:</strong> {{ $cot->created_at->format('d/m/Y') }}</p>
                            <p style="margin: 2px 0;"><strong>Asesora:</strong> {{ $cot->usuario->name ?? 'N/A' }}</p>
                        </div>
                        <a href="{{ route('asesores.cotizaciones.show', $cot->id) }}" style="display: block; background: #3498db; color: white; padding: 6px; border-radius: 4px; text-align: center; text-decoration: none; font-size: 0.85rem; font-weight: bold;">
                            👁️ Ver
                        </a>
                    </div>
                @endforeach
            </div>

            <!-- VISTA TABLA -->
            <div id="vista-tabla-cot" style="display: block; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                    <thead style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); border-bottom: 3px solid #1e3a8a;">
                        <tr>
                            <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem; letter-spacing: 0.5px;">Fecha</th>
                            @if(auth()->user() && auth()->user()->role && auth()->user()->role->name === 'asesor')
                                <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem; letter-spacing: 0.5px;">Código</th>
                            @endif
                            <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem; letter-spacing: 0.5px;">Cliente</th>
                            <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem; letter-spacing: 0.5px;">Estado</th>
                            <th style="padding: 14px 12px; text-align: center; font-weight: 700; color: white; font-size: 0.9rem; letter-spacing: 0.5px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cotizaciones as $cot)
                            <tr style="border-bottom: 1px solid #ecf0f1; transition: background 0.2s;">
                                <td style="padding: 12px; color: #666; font-size: 0.9rem;">{{ $cot->created_at->format('d/m/Y') }}</td>
                                @if(auth()->user() && auth()->user()->role && auth()->user()->role->name === 'asesor')
                                    <td style="padding: 12px; color: #1e40af; font-size: 0.9rem; font-weight: 700;">{{ $cot->numero_cotizacion ?? 'Por asignar' }}</td>
                                @endif
                                <td style="padding: 12px; color: #333; font-size: 0.9rem; font-weight: 500;">{{ $cot->cliente ?? 'Sin cliente' }}</td>
                                <td style="padding: 12px;">
                                    <span style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                        {{ ucfirst($cot->estado) }}
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <a href="{{ route('asesores.cotizaciones.show', $cot->id) }}" style="background: #1e40af; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s; box-shadow: 0 2px 4px rgba(30, 64, 175, 0.2);" onmouseover="this.style.background='#1e3a8a'; this.style.boxShadow='0 4px 8px rgba(30, 64, 175, 0.3)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#1e40af'; this.style.boxShadow='0 2px 4px rgba(30, 64, 175, 0.2)'; this.style.transform='translateY(0)'">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div style="margin-top: 30px;">
                <div class="pagination-info" style="text-align: center; margin-bottom: 15px; color: #666; font-size: 0.9rem;">
                    Mostrando {{ $cotizaciones->firstItem() ?? 0 }}-{{ $cotizaciones->lastItem() ?? 0 }} de {{ $cotizaciones->total() }} registros
                </div>
                @if($cotizaciones->hasPages())
                    <div style="display: flex; justify-content: center; gap: 5px; flex-wrap: wrap;">
                        {{-- Botón primera página --}}
                        <a href="{{ $cotizaciones->url(1) }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $cotizaciones->currentPage() == 1 ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $cotizaciones->currentPage() == 1 ? 'onclick="return false;"' : '' }}>
                            ⟨⟨
                        </a>

                        {{-- Botón página anterior --}}
                        <a href="{{ $cotizaciones->previousPageUrl() }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $cotizaciones->currentPage() == 1 ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $cotizaciones->currentPage() == 1 ? 'onclick="return false;"' : '' }}>
                            ⟨
                        </a>

                        {{-- Números de página --}}
                        @php
                            $start = max(1, $cotizaciones->currentPage() - 2);
                            $end = min($cotizaciones->lastPage(), $cotizaciones->currentPage() + 2);
                        @endphp

                        @for($i = $start; $i <= $end; $i++)
                            <a href="{{ $cotizaciones->url($i) }}" style="padding: 8px 12px; border: 1px solid {{ $i == $cotizaciones->currentPage() ? '#3498db' : '#ddd' }}; border-radius: 4px; text-decoration: none; color: {{ $i == $cotizaciones->currentPage() ? 'white' : '#333' }}; background: {{ $i == $cotizaciones->currentPage() ? '#3498db' : 'white' }}; font-size: 0.9rem; font-weight: {{ $i == $cotizaciones->currentPage() ? 'bold' : 'normal' }}; transition: all 0.3s; cursor: pointer;">
                                {{ $i }}
                            </a>
                        @endfor

                        {{-- Botón página siguiente --}}
                        <a href="{{ $cotizaciones->nextPageUrl() }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $cotizaciones->currentPage() == $cotizaciones->lastPage() ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $cotizaciones->currentPage() == $cotizaciones->lastPage() ? 'onclick="return false;"' : '' }}>
                            ⟩
                        </a>

                        {{-- Botón última página --}}
                        <a href="{{ $cotizaciones->url($cotizaciones->lastPage()) }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $cotizaciones->currentPage() == $cotizaciones->lastPage() ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $cotizaciones->currentPage() == $cotizaciones->lastPage() ? 'onclick="return false;"' : '' }}>
                            ⟩⟩
                        </a>
                    </div>
                @endif
            </div>
        @else
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
        @if($borradores->count() > 0)
            <!-- VISTA TARJETAS -->
            <div id="vista-tarjetas-bor" style="display: none; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px;">
                @foreach($borradores as $borrador)
                    <div style="background: white; border: 1px solid #ecf0f1; border-radius: 6px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); transition: all 0.3s;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="margin: 0; color: #333; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $borrador->cliente ?? 'Sin cliente' }}</h4>
                                <p style="margin: 2px 0 0 0; color: #999; font-size: 0.8rem;">ID: #{{ $borrador->id }}</p>
                            </div>
                            <span style="background: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: bold; white-space: nowrap; margin-left: 5px;">
                                BORRADOR
                            </span>
                        </div>
                        <div style="background: #f8f9fa; padding: 8px; border-radius: 4px; margin-bottom: 10px; font-size: 0.8rem;">
                            <p style="margin: 2px 0;"><strong>Fecha:</strong> {{ $borrador->created_at->format('d/m/Y') }}</p>
                            <p style="margin: 2px 0;"><strong>Asesora:</strong> {{ $borrador->usuario->name ?? 'N/A' }}</p>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <a href="{{ route('asesores.cotizaciones.edit-borrador', $borrador->id) }}" style="flex: 1; background: #f39c12; color: white; padding: 6px; border-radius: 4px; text-align: center; text-decoration: none; font-size: 0.8rem; font-weight: bold;">
                                ✏️ Editar
                            </a>
                            <button onclick="eliminarBorrador({{ $borrador->id }})" style="flex: 1; background: #e74c3c; color: white; padding: 6px; border-radius: 4px; border: none; cursor: pointer; font-size: 0.8rem; font-weight: bold;">
                                🗑️
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- VISTA TABLA -->
            <div id="vista-tabla-bor" style="display: block; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                    <thead style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); border-bottom: 3px solid #1e3a8a;">
                        <tr>
                            <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem; letter-spacing: 0.5px;">Fecha</th>
                            <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem; letter-spacing: 0.5px;">Cliente</th>
                            <th style="padding: 14px 12px; text-align: left; font-weight: 700; color: white; font-size: 0.9rem; letter-spacing: 0.5px;">Estado</th>
                            <th style="padding: 14px 12px; text-align: center; font-weight: 700; color: white; font-size: 0.9rem; letter-spacing: 0.5px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($borradores as $borrador)
                            <tr style="border-bottom: 1px solid #ecf0f1; transition: background 0.2s;">
                                <td style="padding: 12px; color: #666; font-size: 0.9rem;">{{ $borrador->created_at->format('d/m/Y') }}</td>
                                <td style="padding: 12px; color: #333; font-size: 0.9rem; font-weight: 500;">{{ $borrador->cliente ?? 'Sin cliente' }}</td>
                                <td style="padding: 12px;">
                                    <span style="background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                        BORRADOR
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center; display: flex; gap: 8px; justify-content: center;">
                                    <a href="{{ route('asesores.cotizaciones.edit-borrador', $borrador->id) }}" style="background: #f59e0b; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s; box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);" onmouseover="this.style.background='#d97706'; this.style.boxShadow='0 4px 8px rgba(245, 158, 11, 0.3)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#f59e0b'; this.style.boxShadow='0 2px 4px rgba(245, 158, 11, 0.2)'; this.style.transform='translateY(0)'">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        Editar
                                    </a>
                                    <button onclick="eliminarBorrador({{ $borrador->id }})" style="background: #ef4444; color: white; padding: 8px 14px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);" onmouseover="this.style.background='#dc2626'; this.style.boxShadow='0 4px 8px rgba(239, 68, 68, 0.3)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#ef4444'; this.style.boxShadow='0 2px 4px rgba(239, 68, 68, 0.2)'; this.style.transform='translateY(0)'">
                                        <i class="fas fa-trash" style="font-size: 14px;"></i>
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div style="margin-top: 30px;">
                <div class="pagination-info" style="text-align: center; margin-bottom: 15px; color: #666; font-size: 0.9rem;">
                    Mostrando {{ $borradores->firstItem() ?? 0 }}-{{ $borradores->lastItem() ?? 0 }} de {{ $borradores->total() }} registros
                </div>
                @if($borradores->hasPages())
                    <div style="display: flex; justify-content: center; gap: 5px; flex-wrap: wrap;">
                        {{-- Botón primera página --}}
                        <a href="{{ $borradores->url(1) }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $borradores->currentPage() == 1 ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $borradores->currentPage() == 1 ? 'onclick="return false;"' : '' }}>
                            ⟨⟨
                        </a>

                        {{-- Botón página anterior --}}
                        <a href="{{ $borradores->previousPageUrl() }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $borradores->currentPage() == 1 ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $borradores->currentPage() == 1 ? 'onclick="return false;"' : '' }}>
                            ⟨
                        </a>

                        {{-- Números de página --}}
                        @php
                            $start = max(1, $borradores->currentPage() - 2);
                            $end = min($borradores->lastPage(), $borradores->currentPage() + 2);
                        @endphp

                        @for($i = $start; $i <= $end; $i++)
                            <a href="{{ $borradores->url($i) }}" style="padding: 8px 12px; border: 1px solid {{ $i == $borradores->currentPage() ? '#3498db' : '#ddd' }}; border-radius: 4px; text-decoration: none; color: {{ $i == $borradores->currentPage() ? 'white' : '#333' }}; background: {{ $i == $borradores->currentPage() ? '#3498db' : 'white' }}; font-size: 0.9rem; font-weight: {{ $i == $borradores->currentPage() ? 'bold' : 'normal' }}; transition: all 0.3s; cursor: pointer;">
                                {{ $i }}
                            </a>
                        @endfor

                        {{-- Botón página siguiente --}}
                        <a href="{{ $borradores->nextPageUrl() }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $borradores->currentPage() == $borradores->lastPage() ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $borradores->currentPage() == $borradores->lastPage() ? 'onclick="return false;"' : '' }}>
                            ⟩
                        </a>

                        {{-- Botón última página --}}
                        <a href="{{ $borradores->url($borradores->lastPage()) }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $borradores->currentPage() == $borradores->lastPage() ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $borradores->currentPage() == $borradores->lastPage() ? 'onclick="return false;"' : '' }}>
                            ⟩⟩
                        </a>
                    </div>
                @endif
            </div>
        @else
            <div style="background: #f0f7ff; border: 2px dashed #3498db; border-radius: 8px; padding: 40px; text-align: center;">
                <p style="margin: 0; color: #666; font-size: 1.1rem;">
                    📭 No hay borradores guardados
                </p>
                <a href="{{ route('asesores.pedidos.create') }}" style="display: inline-block; margin-top: 15px; background: #3498db; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none;">
                    Crear Nuevo Borrador
                </a>
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
</script>
@endsection
