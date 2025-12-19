@extends('layouts.asesores')

@include('components.modal-imagen')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
@endsection

@section('content')
<!-- Header Full Width -->
<div class="page-header">
    <h1>📋 Crear Pedido de Producción</h1>
    <p>Selecciona una cotización y agrega las cantidades por talla</p>
</div>

<div style="width: 100%; padding: 1.5rem;">
    <form id="formCrearPedido" class="space-y-6">
        @csrf

        <!-- PASO 1: Seleccionar Cotización -->
        <div class="form-section">
            <h2>
                <span>1</span> Seleccionar Cotización
            </h2>

            <div class="form-group">
                <label for="cotizacion_search" class="block text-sm font-medium text-gray-700 mb-2">
                    Cotización <span class="text-red-500">*</span>
                </label>
                <div style="position: relative;">
                    <input type="text" id="cotizacion_search" placeholder="🔍 Buscar por número, cliente o asesora..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                    <input type="hidden" id="cotizacion_id" name="cotizacion_id" required>
                    <div id="cotizacion_dropdown" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #d1d5db; border-top: none; border-radius: 0 0 8px 8px; max-height: 300px; overflow-y: auto; display: none; z-index: 1000; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    </div>
                </div>
                <div id="cotizacion_selected" style="margin-top: 0.75rem; padding: 0.75rem; background: #f0f9ff; border-left: 3px solid #0066cc; border-radius: 4px; display: none;">
                    <div style="font-size: 0.875rem; color: #1e40af;"><strong>Seleccionada:</strong> <span id="cotizacion_selected_text"></span></div>
                </div>
            </div>
        </div>

        <!-- PASO 2: Información del Pedido -->
        <div class="form-section">
            <h2>
                <span>2</span> Información del Pedido
            </h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="numero_cotizacion">Número de Cotización</label>
                    <input type="text" id="numero_cotizacion" name="numero_cotizacion" readonly>
                </div>

                <div class="form-group">
                    <label for="cliente">Cliente</label>
                    <input type="text" id="cliente" name="cliente" readonly>
                </div>

                <div class="form-group">
                    <label for="asesora">Asesora</label>
                    <input type="text" id="asesora" name="asesora" readonly>
                </div>

                <div class="form-group">
                    <label for="forma_de_pago">Forma de Pago</label>
                    <input type="text" id="forma_de_pago" name="forma_de_pago" readonly>
                </div>

                <div class="form-group">
                    <label for="numero_pedido">Número de Pedido</label>
                    <input type="text" id="numero_pedido" name="numero_pedido" readonly placeholder="Se asignará automáticamente" style="background-color: #f3f4f6; cursor: not-allowed;">
                </div>
            </div>
        </div>

        <!-- PASO 3: Prendas y Cantidades por Talla O Campos de LOGO -->
        <div class="form-section">
            <h2>
                <span>3</span> <span id="paso3_titulo">Prendas y Cantidades por Talla</span>
            </h2>

            <div id="prendas-container">
                <div class="empty-state">
                    <p>Selecciona una cotización para ver las prendas</p>
                </div>
            </div>

            <!-- Contenedor para campos LOGO (inicialmente oculto) -->
            <div id="logo-fields-container" style="display: none;">
                <!-- DESCRIPCIÓN -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="logo_descripcion" style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.5rem; font-size: 0.9rem;">
                        DESCRIPCIÓN
                    </label>
                    <textarea id="logo_descripcion" readonly style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; background-color: #f8fafc; min-height: 80px;"></textarea>
                </div>

                <!-- IMÁGENES -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.5rem; font-size: 0.9rem;">
                        IMÁGENES (MÁXIMO 5)
                    </label>
                    <div id="logo-galeria-imagenes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 10px;">
                        <!-- Las imágenes se cargarán aquí -->
                    </div>
                </div>

                <!-- TÉCNICAS -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.5rem; font-size: 0.9rem;">
                        Técnicas disponibles
                    </label>
                    <div id="logo-tecnicas-seleccionadas" style="display: flex; flex-wrap: wrap; gap: 8px; padding: 0.75rem; background: #f8fafc; border-radius: 6px; min-height: 40px;">
                        <!-- Las técnicas se mostrarán aquí -->
                    </div>
                </div>

                <!-- OBSERVACIONES DE TÉCNICAS -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="logo_observaciones_tecnicas" style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.3rem; font-size: 0.9rem;">
                        Observaciones de Técnicas
                    </label>
                    <textarea id="logo_observaciones_tecnicas" readonly style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; background-color: #f8fafc; min-height: 60px;"></textarea>
                </div>

                <!-- UBICACIONES -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.5rem; font-size: 0.9rem;">
                        Ubicación
                    </label>
                    <div id="logo-ubicaciones-seleccionadas" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; padding: 0.75rem; background: #f8fafc; border-radius: 6px;">
                        <!-- Las ubicaciones se mostrarán aquí -->
                    </div>
                </div>

                <!-- OBSERVACIONES GENERALES -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.5rem; font-size: 0.9rem;">
                        Observaciones Generales
                    </label>
                    <div id="logo-observaciones-generales" style="display: flex; flex-direction: column; gap: 8px; padding: 0.75rem; background: #f8fafc; border-radius: 6px;">
                        <!-- Las observaciones se mostrarán aquí -->
                    </div>
                </div>
            </div>
        </div>

        <!-- PASO 4: Botones de Acción -->
        <div class="btn-actions">
            <button type="submit" class="btn btn-primary">
                ✓ Crear Pedido de Producción
            </button>
            <a href="{{ route('asesores.cotizaciones.index') }}" class="btn btn-secondary">
                ✕ Cancelar
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script>
        // Pasar datos de PHP a JavaScript
        window.asesorActualNombre = '{{ Auth::user()->name ?? '' }}';
        window.cotizacionesData = {!! json_encode($cotizaciones->map(function($cot) {
            // Extraer forma_pago de especificaciones
            $formaPago = '';
            if (is_array($cot->especificaciones) && isset($cot->especificaciones['forma_pago'])) {
                $formaPagoArray = $cot->especificaciones['forma_pago'];
                if (is_array($formaPagoArray) && count($formaPagoArray) > 0) {
                    $formaPago = $formaPagoArray[0]['valor'] ?? '';
                }
            }
            
            // Verificar si tiene logo
            $tieneLogoCotizacion = $cot->logoCotizacion !== null;
            $prendasCount = $cot->prendasCotizaciones->count();
            
            return [
                'id' => $cot->id,
                'numero_cotizacion' => $cot->numero_cotizacion,
                'numero' => $cot->numero_cotizacion ?: 'COT-' . $cot->id,
                'cliente' => $cot->cliente ? $cot->cliente->nombre : '',
                'asesora' => $cot->asesor ? $cot->asesor->name : Auth::user()->name,
                'formaPago' => $formaPago,
                'prendasCount' => $prendasCount,
                'tieneLogoCotizacion' => $tieneLogoCotizacion,
                'tienePrendas' => $prendasCount > 0
            ];
        })->toArray()) !!};
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/crear-pedido.js') }}"></script>
@endpush
