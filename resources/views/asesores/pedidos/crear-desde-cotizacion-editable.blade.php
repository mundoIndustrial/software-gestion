@extends('layouts.asesores')

@include('components.modal-imagen')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <style>
        /* ============================================================
           ESTILOS GLOBALES Y CONSISTENTES
           ============================================================ */
        
        /* Colores del diseño */
        :root {
            --color-primary: #0066cc;
            --color-primary-dark: #0052a3;
            --color-danger: #dc3545;
            --color-danger-dark: #c82333;
            --color-text: #333333;
            --color-text-secondary: #666666;
            --color-border: #d0d0d0;
            --color-bg-light: #f5f5f5;
            --color-bg-input: #ffffff;
        }

        /* Estilos para la vista editable - PROFESIONAL */
        .prenda-card-editable {
            border: 1px solid var(--color-border);
            border-radius: 6px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: var(--color-bg-input);
            position: relative;
            transition: all 0.3s ease;
        }

        .prenda-card-editable:hover {
            border-color: #999999;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .prenda-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .prenda-title {
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--color-text);
        }

        .prenda-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* ============================================================
           BOTONES - ESTILO CONSISTENTE
           ============================================================ */
        
        .btn-eliminar-prenda,
        .btn-eliminar-variacion,
        .btn-quitar-talla {
            background-color: var(--color-danger);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-eliminar-prenda:hover,
        .btn-eliminar-variacion:hover,
        .btn-quitar-talla:hover {
            background-color: var(--color-danger-dark);
            transform: translateY(-2px);
            box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);
        }

        .btn-agregar-talla-nuevo {
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 0.5rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-agregar-talla-nuevo:hover {
            background-color: var(--color-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 2px 6px rgba(0, 102, 204, 0.3);
        }

        .checkbox-item input[type="checkbox"] {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }

        /* ============================================================
           LAYOUT Y GRID RESPONSIVE
           ============================================================ */
        
        .prenda-content {
            display: grid;
            grid-template-columns: 1fr 200px;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .prenda-content {
                grid-template-columns: 1fr;
            }
        }

        .prenda-info-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .prenda-fotos-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            width: 100%;
        }

        @media (max-width: 1024px) {
            .prenda-fotos-section {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                width: 100%;
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .prenda-fotos-section {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
        }

        /* ============================================================
           FOTOS - RESPONSIVE
           ============================================================ */
        
        .prenda-foto-principal {
            width: 100%;
            max-width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--color-border);
            cursor: pointer;
            transition: all 0.2s ease;
            display: block;
        }

        .prenda-foto-principal:hover {
            border-color: #999999;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: scale(1.02);
        }

        .fotos-adicionales {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 0.5rem;
            width: 100%;
        }

        .foto-mini {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid var(--color-border);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .foto-mini:hover {
            border-color: #999999;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
            transform: scale(1.05);
        }

        /* ============================================================
           FORMULARIOS
           ============================================================ */
        
        .form-group-inline {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group-editable {
            display: flex;
            flex-direction: column;
        }

        .form-group-editable label {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--color-text-secondary);
            margin-bottom: 0.5rem;
        }

        .form-group-editable input,
        .form-group-editable select,
        .form-group-editable textarea {
            padding: 0.6rem;
            border: 1px solid var(--color-border);
            border-radius: 4px;
            font-size: 0.875rem;
            font-family: inherit;
            background: var(--color-bg-input);
            color: var(--color-text);
            transition: all 0.2s ease;
        }

        .form-group-editable input:focus,
        .form-group-editable select:focus,
        .form-group-editable textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .form-group-editable textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* ============================================================
           SECCIONES ESPECIALES - TALLAS, VARIACIONES, TELAS
           ============================================================ */
        
        .tallas-editable,
        .variaciones-section,
        .telas-seccion {
            background: var(--color-bg-light);
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
            border-left: 4px solid var(--color-primary);
            overflow-x: auto;
        }

        .tallas-editable > label,
        .variaciones-section > strong,
        .telas-seccion > strong {
            font-weight: 600;
            display: block;
            margin-bottom: 1rem;
            color: var(--color-text);
            font-size: 0.95rem;
        }

        /* Talla Item */
        .talla-item {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 0.75rem;
            padding: 0.75rem;
            background: var(--color-bg-input);
            border-radius: 4px;
            border: 1px solid var(--color-border);
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .talla-item {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .talla-item:last-child {
            margin-bottom: 0;
        }

        .talla-item input {
            flex: 0 0 auto;
            min-width: 80px;
            text-align: center;
        }

        .talla-item input[type="number"] {
            flex: 1;
            max-width: 120px;
        }

        /* Tabla de Variaciones */
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--color-bg-input);
            border: 1px solid var(--color-border);
            border-radius: 4px;
            overflow: hidden;
            font-size: 0.9rem;
        }

        table thead {
            background: #f0f0f0;
            border-bottom: 2px solid var(--color-border);
        }

        table thead th {
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            border-right: 1px solid var(--color-border);
            color: var(--color-text);
        }

        table tbody tr {
            border-bottom: 1px solid #eee;
        }

        table tbody tr:hover {
            background: #fafafa;
        }

        table tbody tr:last-child {
            border-bottom: none;
        }

        table td {
            padding: 0.75rem;
            border-right: 1px solid var(--color-border);
            vertical-align: middle;
        }

        table td:last-child {
            border-right: none;
        }

        table input,
        table textarea {
            width: 100%;
            padding: 0.4rem;
            border: 1px solid var(--color-border);
            border-radius: 3px;
            font-size: 0.85rem;
            font-family: inherit;
        }

        table textarea {
            min-height: 40px;
            resize: vertical;
        }

        /* ============================================================
           RESUMEN Y ALERTAS
           ============================================================ */
        
        .prenda-resumen {
            background: var(--color-bg-light);
            padding: 1rem;
            border-left: 3px solid var(--color-primary);
            border-radius: 4px;
            margin-top: 1rem;
            color: var(--color-text-secondary);
            font-size: 0.9rem;
        }

        .alert-info {
            background: #f0f8ff;
            border-left: 4px solid var(--color-primary);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            color: var(--color-primary);
            font-size: 0.95rem;
        }

        /* ============================================================
           RESPONSIVE FINAL
           ============================================================ */
        
        @media (max-width: 768px) {
            .prenda-card-editable {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .prenda-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .prenda-actions {
                width: 100%;
            }

            .btn-eliminar-prenda {
                flex: 1;
            }

            .prenda-content {
                gap: 1rem;
            }

            .fotos-adicionales {
                grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
                gap: 0.4rem;
            }

            .form-group-inline {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            table {
                font-size: 0.8rem;
            }

            table th,
            table td {
                padding: 0.5rem;
            }

            table input,
            table textarea {
                padding: 0.3rem;
                font-size: 0.8rem;
            }

            table textarea {
                min-height: 35px;
            }
        }
    </style>
    </style>
@endsection

@section('content')
<!-- Header Full Width -->
<div class="page-header">
    <h1>📋 Crear Pedido de Producción (Editable)</h1>
    <p>Selecciona una cotización y personaliza tu pedido</p>
</div>

<div style="width: 100%; padding: 1.5rem;">
    <form id="formCrearPedidoEditable" class="space-y-6">
        @csrf

        <!-- PASO 1: Seleccionar Cotización -->
        <div class="form-section">
            <h2>
                <span>1</span> Tipo de Pedido
            </h2>

            <!-- Radio Buttons para elegir tipo de pedido -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <div style="display: flex; gap: 2rem; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                        <input type="radio" name="tipo_pedido_editable" id="tipo_desde_cotizacion" value="cotizacion" checked style="width: 18px; height: 18px; cursor: pointer;">
                        <span>Desde Cotización</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                        <input type="radio" name="tipo_pedido_editable" id="tipo_nuevo_pedido" value="nuevo" style="width: 18px; height: 18px; cursor: pointer;">
                        <span>Nuevo Pedido</span>
                    </label>
                </div>
            </div>

            <!-- Contenedor para opciones dinámicas -->
            <div id="contenedor-opciones-pedido" style="margin-top: 1.5rem;">
                <!-- Buscador de Cotización (se muestra solo si está seleccionado "Desde Cotización") -->
                <div id="seccion-buscar-cotizacion" style="display: block;">
                    <div class="form-group">
                        <label for="cotizacion_search_editable" class="block text-sm font-medium text-gray-700 mb-2">
                            Cotización
                        </label>
                        <div style="position: relative;">
                            <input type="text" id="cotizacion_search_editable" placeholder="🔍 Buscar por número, cliente o asesora..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                            <input type="hidden" id="cotizacion_id_editable" name="cotizacion_id">
                            <input type="hidden" id="logoCotizacionId" name="logoCotizacionId">
                            <div id="cotizacion_dropdown_editable" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #d1d5db; border-top: none; border-radius: 0 0 8px 8px; max-height: 300px; overflow-y: auto; display: none; z-index: 1000; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                            </div>
                        </div>
                        <div id="cotizacion_selected_editable" style="margin-top: 0.75rem; padding: 0.75rem; background: #f0f9ff; border-left: 3px solid #0066cc; border-radius: 4px; display: none;">
                            <div style="font-size: 0.875rem; color: #1e40af;"><strong>Seleccionada:</strong> <span id="cotizacion_selected_text_editable"></span></div>
                        </div>
                    </div>
                </div>

                <!-- Selector de Tipo de Pedido (se muestra solo si está seleccionado "Nuevo Pedido") -->
                <div id="seccion-tipo-pedido-nuevo" style="display: none;">
                    <div class="form-group">
                        <label for="tipo_pedido_nuevo" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Pedido
                        </label>
                        <select id="tipo_pedido_nuevo" name="tipo_pedido_nuevo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="manejarCambiaTipoPedido()">
                            <option value="">-- Selecciona un tipo de pedido --</option>
                            <option value="P">PRENDA</option>
                            <option value="B">BORDADO</option>
                            <option value="R">REFLECTIVO</option>
                            <option value="E">ESTAMPADO</option>
                            <option value="C">COMBINADA</option>
                        </select>
                    </div>

                    <!-- Selector de Combinada (se muestra solo si está seleccionado "COMBINADA") -->
                    <div id="seccion-tipo-combinada" style="display: none; margin-top: 1rem;">
                        <div class="form-group">
                            <label for="tipo_combinada_nuevo" class="block text-sm font-medium text-gray-700 mb-2">
                                Tipo de Combinada
                            </label>
                            <select id="tipo_combinada_nuevo" name="tipo_combinada_nuevo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">-- Selecciona una opción --</option>
                                <option value="PH">Por Hacer</option>
                                <option value="SB">Sacado de Bodega</option>
                                <option value="PHB">Por Hacer y Sacado de Bodega</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

        <!-- PASO 2: Información del Pedido -->
        <div class="form-section" id="seccion-info-prenda" style="display: none;">
            <h2>
                <span>2</span> Información del Pedido
            </h2>

            <div class="form-row">
                <!-- Campo Número de Cotización (solo se muestra si viene de cotización) -->
                <div id="campo-numero-cotizacion" class="form-group">
                    <label for="numero_cotizacion_editable">Número de Cotización</label>
                    <input type="text" id="numero_cotizacion_editable" name="numero_cotizacion" readonly>
                </div>

                <div class="form-group">
                    <label for="cliente_editable">Cliente</label>
                    <input type="text" id="cliente_editable" name="cliente" required>
                </div>

                <div class="form-group">
                    <label for="asesora_editable">Asesora</label>
                    <input type="text" id="asesora_editable" name="asesora" readonly>
                </div>

                <div class="form-group">
                    <label for="forma_de_pago_editable">Forma de Pago</label>
                    <input type="text" id="forma_de_pago_editable" name="forma_de_pago">
                </div>

                <div class="form-group">
                    <label for="numero_pedido_editable">Número de Pedido</label>
                    <input type="text" id="numero_pedido_editable" name="numero_pedido" readonly placeholder="Se asignará automáticamente" style="background-color: #f3f4f6; cursor: not-allowed;">
                </div>
            </div>
        </div>

        <!-- PASO 3: Prendas Editables -->
        <div class="form-section" id="seccion-prendas" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0;">
                    <span>3</span> <span id="titulo-prendas-dinamico">Prendas Técnicas del Logo</span>
                </h2>
                <button type="button" 
                    id="btn-agregar-prenda-tecnica-logo"
                    onclick="abrirModalAgregarPrendaTecnicaLogo()"
                    style="display: none; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 0.95rem; transition: all 0.3s; box-shadow: 0 2px 4px rgba(30, 64, 175, 0.2);" 
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(30, 64, 175, 0.3)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(30, 64, 175, 0.2)'">
                    ➕ Agregar Prenda Técnica
                </button>
            </div>

            <div id="prendas-container-editable">
                <div class="empty-state">
                    <p>Selecciona una cotización para ver las prendas</p>
                </div>
            </div>
        </div>

        <!-- PASO 6: Botones de Acción -->
        <div class="btn-actions">
            <button type="submit" id="btn-submit" class="btn btn-primary" style="display: none;">
                ✓ Crear Pedido
            </button>
            <a href="{{ route('asesores.pedidos-produccion.index') }}" class="btn btn-secondary">
                ✕ Cancelar
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script>
        // Configuración inicial
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar asesora
            document.getElementById('asesora_editable').value = '{{ Auth::user()->name ?? '' }}';
            
            // Mostrar botón submit
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.textContent = '✓ Crear Pedido';
            btnSubmit.style.display = 'block';

            // ========== MANEJAR CAMBIO DE TIPO DE PEDIDO ==========
            const tipoDesdeRadio = document.getElementById('tipo_desde_cotizacion');
            const tipoNuevoRadio = document.getElementById('tipo_nuevo_pedido');
            const seccionBuscarCotizacion = document.getElementById('seccion-buscar-cotizacion');
            const seccionTipoPedidoNuevo = document.getElementById('seccion-tipo-pedido-nuevo');
            const selectTipoPedidoNuevo = document.getElementById('tipo_pedido_nuevo');
            const seccionTipoCombinada = document.getElementById('seccion-tipo-combinada');
            const campNumeroCotizacion = document.getElementById('campo-numero-cotizacion');

            function actualizarVistaPedido() {
                if (tipoDesdeRadio.checked) {
                    // Mostrar buscador de cotización, ocultar selector de tipo y mostrar número de cotización
                    seccionBuscarCotizacion.style.display = 'block';
                    seccionTipoPedidoNuevo.style.display = 'none';
                    campNumeroCotizacion.style.display = 'block';
                } else {
                    // Ocultar buscador de cotización, mostrar selector de tipo y ocultar número de cotización
                    seccionBuscarCotizacion.style.display = 'none';
                    seccionTipoPedidoNuevo.style.display = 'block';
                    campNumeroCotizacion.style.display = 'none';
                }
            }

            // Listener para cambios en radio buttons
            tipoDesdeRadio.addEventListener('change', actualizarVistaPedido);
            tipoNuevoRadio.addEventListener('change', actualizarVistaPedido);

            // ========== MANEJAR CAMBIO DE TIPO DE PEDIDO (NUEVO) ==========
            window.manejarCambiaTipoPedido = function() {
                const tipoPedido = selectTipoPedidoNuevo.value;
                if (tipoPedido === 'C') {
                    // Mostrar selector de tipo combinada
                    seccionTipoCombinada.style.display = 'block';
                } else {
                    // Ocultar selector de tipo combinada
                    seccionTipoCombinada.style.display = 'none';
                }

                // Manejar tipo PRENDA sin cotización
                if (tipoPedido === 'P') {
                    console.log('🎯 Seleccionado tipo PRENDA sin cotización');
                    if (typeof crearPedidoTipoPrendaSinCotizacion === 'function') {
                        crearPedidoTipoPrendaSinCotizacion();
                    } else {
                        console.error('❌ Función crearPedidoTipoPrendaSinCotizacion no disponible');
                    }
                }

                // Manejar tipo REFLECTIVO sin cotización
                if (tipoPedido === 'R') {
                    console.log('🎯 Seleccionado tipo REFLECTIVO sin cotización');
                    if (typeof crearPedidoTipoReflectivoSinCotizacion === 'function') {
                        crearPedidoTipoReflectivoSinCotizacion();
                    } else {
                        console.error('❌ Función crearPedidoTipoReflectivoSinCotizacion no disponible');
                    }
                }
            };
        });

        window.asesorActualNombre = '{{ Auth::user()->name ?? '' }}';
        window.cotizacionesData = {!! json_encode($cotizaciones->map(function($cot) {
            $formaPago = '';
            if (is_array($cot->especificaciones) && isset($cot->especificaciones['forma_pago'])) {
                $formaPagoArray = $cot->especificaciones['forma_pago'];
                if (is_array($formaPagoArray) && count($formaPagoArray) > 0) {
                    $formaPago = $formaPagoArray[0]['valor'] ?? '';
                }
            }
            
            return [
                'id' => $cot->id,
                'numero_cotizacion' => $cot->numero_cotizacion,
                'numero' => $cot->numero_cotizacion ?: 'COT-' . $cot->id,
                'cliente' => $cot->cliente ? $cot->cliente->nombre : '',
                'asesora' => $cot->asesor ? $cot->asesor->name : Auth::user()->name,
                'formaPago' => $formaPago,
                'prendasCount' => $cot->prendas->count()
            ];
        })->toArray()) !!};
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Scripts de configuración y utilidades (Fase 1 - Refactorización) -->
    <script src="{{ asset('js/modulos/crear-pedido/config-pedido-editable.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/helpers-pedido-editable.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestor-fotos-pedido.js') }}?v={{ time() }}"></script>
    <!-- ⭐⭐ UTILIDADES GLOBALES (DEBE CARGAR PRIMERO - inicializa fotosEliminadas, FotoHelper, CantidadesManager, ESTILOS_FOTOS) -->
    <script src="{{ asset('js/utilidades-crear-pedido.js') }}?v={{ time() }}"></script>
    <!-- Modales y diálogos -->
    <script src="{{ asset('js/modulos/crear-pedido/modales-pedido.js') }}?v={{ time() }}"></script>
    <!-- Gestores de lógica (Fase 2 - Modularización) -->
    <script src="{{ asset('js/modulos/crear-pedido/gestor-cotizacion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestor-prendas.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestor-logo.js') }}?v={{ time() }}"></script>
    <!-- Inicialización de gestores (Fase 2) -->
    <script src="{{ asset('js/modulos/crear-pedido/init-gestores-fase2.js') }}?v={{ time() }}"></script>
    <!-- Validación y envío (Fase 3) -->
    <script src="{{ asset('js/modulos/crear-pedido/validacion-envio-fase3.js') }}?v={{ time() }}"></script>
    <!-- Gestor de pedido sin cotización (Fase 3b) -->
    <script src="{{ asset('js/modulos/crear-pedido/gestor-pedido-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/init-gestor-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <!-- Gestor de prenda sin cotización tipo PRENDA (Nuevo) -->
    <script src="{{ asset('js/modulos/crear-pedido/gestor-prenda-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/renderizador-prenda-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestor-tallas-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/funciones-prenda-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/integracion-prenda-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <!-- Gestor de reflectivo sin cotización tipo REFLECTIVO (Nuevo) -->
    <script src="{{ asset('js/modulos/crear-pedido/gestor-reflectivo-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/renderizador-reflectivo-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/funciones-reflectivo-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <!-- Módulo de Reflectivo -->
    <script src="{{ asset('js/modulos/crear-pedido/reflectivo-pedido.js') }}?v={{ time() }}"></script>
    <!-- Módulo de Logo -->
    <script src="{{ asset('js/modulos/crear-pedido/logo-pedido.js') }}?v={{ time() }}"></script>
    <!-- Gestión de Fotos de Logo -->
    <script src="{{ asset('js/modulos/crear-pedido/fotos-logo-pedido.js') }}?v={{ time() }}"></script>
    <!-- Nuevo Módulo de Prendas Técnicas de Logo -->
    <script src="{{ asset('js/modulos/crear-pedido/logo-pedido-tecnicas.js') }}?v={{ time() }}"></script>
    <!-- Integración de Prendas Técnicas con Logo Pedido -->
    <script src="{{ asset('js/modulos/crear-pedido/integracion-logo-pedido-tecnicas.js') }}?v={{ time() }}"></script>
    <!-- Inicialización de Prendas Técnicas de Logo -->
    <script src="{{ asset('js/modulos/crear-pedido/init-logo-pedido-tecnicas.js') }}?v={{ time() }}"></script>
    <!-- Templates HTML (DEBE CARGARSE ANTES DE crear-pedido-editable.js) -->
    <script src="{{ asset('js/templates-pedido.js') }}?v={{ time() }}"></script>
    <!-- Validación de cambio de tipo de pedido (DEBE CARGARSE ANTES DE crear-pedido-editable.js) -->
    <script src="{{ asset('js/modulos/crear-pedido/validar-cambio-tipo-pedido.js') }}?v={{ time() }}"></script>
    <!-- Script principal -->
    <script src="{{ asset('js/crear-pedido-editable.js') }}?v={{ time() }}"></script>
@endpush
