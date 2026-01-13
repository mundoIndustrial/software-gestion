@php
    // DEBUG: Ver qué variables están disponibles en la vista
    \Log::info('🔍 [VISTA] Variables disponibles:', [
        'tiene_cotizaciones' => isset($cotizaciones),
        'tipo_cotizaciones' => isset($cotizaciones) ? get_class($cotizaciones) : 'no definido',
        'count_cotizaciones' => isset($cotizaciones) ? $cotizaciones->count() : 0,
        'tiene_tipoInicial' => isset($tipoInicial),
        'valor_tipoInicial' => $tipoInicial ?? 'no definido',
        'todas_las_variables' => array_keys(get_defined_vars())
    ]);
@endphp

@extends('layouts.asesores')

@include('components.modal-imagen')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <style>
        /* ============================================================
           LOADING SCREEN DE PÁGINA COMPLETA
           ============================================================ */
        #page-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.98);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 99999;
            transition: opacity 0.3s ease-out;
        }
        
        #page-loading-overlay.fade-out {
            opacity: 0;
            pointer-events: none;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #e5e7eb;
            border-top-color: #0066cc;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        .loading-text {
            margin-top: 1.5rem;
            font-size: 1.125rem;
            color: #374151;
            font-weight: 500;
        }
        
        .loading-subtext {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ============================================================
           FIN LOADING SCREEN
           ============================================================ */
    </style>
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
@endsection

@section('content')
<!-- Loading Overlay de Página Completa -->
<div id="page-loading-overlay">
    <div class="loading-spinner"></div>
    <div class="loading-text">Cargando sistema de pedidos...</div>
    <div class="loading-subtext">Por favor espera mientras preparamos todo</div>
</div>

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
            @php
                $tipoInicial = $tipoInicial ?? 'cotizacion';
                $tituloTipo = match($tipoInicial) {
                    'cotizacion' => 'Pedido desde Cotización',
                    'nuevo' => 'Nuevo Pedido',
                    default => 'Tipo de Pedido'
                };
            @endphp
            
            <h2>
                <span>1</span> {{ $tituloTipo }}
            </h2>

            <!-- Radio Buttons para elegir tipo de pedido (OCULTOS si viene de ruta específica) -->
            @if(!isset($tipoInicial) || $tipoInicial === null)
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
            @else
            <!-- Input hidden para mantener el tipo seleccionado -->
            <input type="hidden" name="tipo_pedido_editable" id="tipo_pedido_hidden" value="{{ $tipoInicial }}">
            <div style="margin-bottom: 1rem; padding: 0.75rem; background: #f0f9ff; border-left: 3px solid #0066cc; border-radius: 4px;">
                <p style="margin: 0; color: #1e40af; font-size: 0.875rem;">
                    <strong>Tipo:</strong> {{ $tituloTipo }}
                </p>
            </div>
            @endif

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
                        <!-- Loading State -->
                        <div id="tipo-pedido-loading" style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
                            <div style="width: 20px; height: 20px; border: 3px solid #e5e7eb; border-top-color: #0066cc; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                            <span style="color: #6b7280; font-size: 0.875rem;">Cargando opciones...</span>
                        </div>
                        <!-- Select (oculto inicialmente) -->
                        <select id="tipo_pedido_nuevo" name="tipo_pedido_nuevo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="manejarCambiaTipoPedido()" style="display: none;" disabled>
                            <option value="">-- Selecciona un tipo de pedido --</option>
                            <option value="P">PRENDA</option>
                            <option value="R">REFLECTIVO</option>
                            <option value="B">BORDADO</option>
                            <option value="E">ESTAMPADO</option>
                            <option value="EPP">EPP</option>
                        </select>
                    </div>
                </div>
                
                <!-- CSS para la animación del spinner -->
                <style>
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>

                <!-- SECCIÓN DE ÍTEMS DEL PEDIDO -->
                <div id="seccion-items-pedido" style="margin-top: 2rem; display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3 style="font-size: 1.125rem; font-weight: 600; color: #1e40af;">Ítems del Pedido</h3>
                        <div style="display: flex; gap: 0.75rem;">
                            <button type="button" id="btn-agregar-item-cotizacion" style="display: none; padding: 0.5rem 1rem; background: #0066cc; color: white; border: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: background 0.2s;" onmouseover="this.style.background='#0052a3'" onmouseout="this.style.background='#0066cc'">
                                <span style="font-size: 1.25rem;">+</span>
                                Agregar Cotización
                            </button>
                            <button type="button" id="btn-agregar-item-tipo" style="display: none; padding: 0.5rem 1rem; background: #059669; color: white; border: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: background 0.2s;" onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                                <span style="font-size: 1.25rem;">+</span>
                                Agregar Tipo
                            </button>
                        </div>
                    </div>

                    <!-- Lista de ítems -->
                    <div id="lista-items-pedido" style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <!-- Los ítems se agregarán aquí dinámicamente -->
                    </div>

                    <!-- Mensaje cuando no hay ítems -->
                    <div id="mensaje-sin-items" style="padding: 2rem; text-align: center; background: #f9fafb; border: 2px dashed #d1d5db; border-radius: 8px; color: #6b7280;">
                        <p style="margin: 0; font-size: 0.875rem;">No hay ítems agregados. Usa los botones de arriba para agregar cotizaciones o tipos nuevos.</p>
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

<!-- MODAL: Seleccionar Prendas de Cotización -->
<div id="modal-seleccion-prendas" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <!-- Header -->
        <div style="padding: 1.5rem; border-bottom: 2px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);">
            <h3 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 600;">
                📋 Seleccionar Prendas de la Cotización
            </h3>
            <button onclick="cerrarModalPrendas()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='none'">
                ✕
            </button>
        </div>
        
        <!-- Body -->
        <div style="padding: 1.5rem;">
            <div id="modal-cotizacion-info" style="padding: 1rem; background: #f0f9ff; border-left: 4px solid #0066cc; border-radius: 6px; margin-bottom: 1.5rem;">
                <div style="font-weight: 600; color: #1e40af; margin-bottom: 0.25rem;">Cotización: <span id="modal-cot-numero"></span></div>
                <div style="font-size: 0.875rem; color: #6b7280;">Cliente: <span id="modal-cot-cliente"></span></div>
            </div>
            
            <div id="lista-prendas-modal" style="display: flex; flex-direction: column; gap: 1rem;">
                <!-- Las prendas se cargarán aquí dinámicamente -->
            </div>
        </div>
        
        <!-- Footer -->
        <div style="padding: 1.5rem; border-top: 2px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 1rem; background: #f9fafb;">
            <button onclick="cerrarModalPrendas()" style="padding: 0.75rem 1.5rem; background: #6b7280; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#4b5563'" onmouseout="this.style.background='#6b7280'">
                Cancelar
            </button>
            <button onclick="agregarPrendasSeleccionadas()" style="padding: 0.75rem 1.5rem; background: #0066cc; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#0052a3'" onmouseout="this.style.background='#0066cc'">
                ✓ Agregar Prendas Seleccionadas
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Agregar Prenda Nueva (Sin Cotización) -->
<div id="modal-agregar-prenda-nueva" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; overflow-y: auto; padding: 2rem 0;">
    <div style="background: white; border-radius: 12px; max-width: 900px; width: 95%; max-height: 95vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3); margin: auto;">
        <!-- Header -->
        <div style="padding: 1.5rem; border-bottom: 2px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #059669 0%, #047857 100%); position: sticky; top: 0; z-index: 10;">
            <h3 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 600;">
                ✨ Agregar Prenda Nueva
            </h3>
            <button onclick="cerrarModalPrendaNueva()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='none'">
                ✕
            </button>
        </div>
        
        <!-- Body -->
        <div style="padding: 1.5rem;">
            <form id="form-prenda-nueva">
                <!-- Nombre de la prenda -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; color: #059669; margin-bottom: 0.5rem; font-size: 0.95rem;">
                        👔 NOMBRE DE LA PRENDA *
                    </label>
                    <input type="text" id="nueva-prenda-nombre" required placeholder="Ej: CAMISA DRILL, POLO, PANTALÓN..." style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; text-transform: uppercase;">
                </div>
                
                <!-- Origen y Descripción en fila -->
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <!-- Origen -->
                    <div>
                        <label style="display: block; font-weight: 700; color: #059669; margin-bottom: 0.5rem; font-size: 0.95rem;">
                            📍 ORIGEN *
                        </label>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; border: 2px solid #d1d5db; border-radius: 6px; transition: all 0.2s;" onmouseover="this.style.borderColor='#059669'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#d1d5db'">
                                <input type="radio" name="nueva-prenda-origen" value="bodega" checked style="width: 18px; height: 18px; accent-color: #059669;">
                                <span style="font-weight: 500;">🏪 Bodega</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; border: 2px solid #d1d5db; border-radius: 6px; transition: all 0.2s;" onmouseover="this.style.borderColor='#059669'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#d1d5db'">
                                <input type="radio" name="nueva-prenda-origen" value="confeccion" style="width: 18px; height: 18px; accent-color: #059669;">
                                <span style="font-weight: 500;">✂️ Confección</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Descripción -->
                    <div>
                        <label style="display: block; font-weight: 700; color: #059669; margin-bottom: 0.5rem; font-size: 0.95rem;">
                            📝 DESCRIPCIÓN
                        </label>
                        <textarea id="nueva-prenda-descripcion" placeholder="Descripción de la prenda, detalles especiales..." style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; resize: vertical; min-height: 80px;"></textarea>
                    </div>
                </div>

                <!-- Color, Tela y Referencia -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; color: #059669; margin-bottom: 0.75rem; font-size: 0.95rem;">
                        🎨 COLOR, TELA Y REFERENCIA
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem; font-weight: 600;">Color</label>
                            <input type="text" id="nueva-prenda-color" placeholder="Ej: AZUL MARINO" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; text-transform: uppercase;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem; font-weight: 600;">Tela</label>
                            <input type="text" id="nueva-prenda-tela" placeholder="Ej: DRILL" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; text-transform: uppercase;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem; font-weight: 600;">Referencia</label>
                            <input type="text" id="nueva-prenda-referencia" placeholder="Ej: REF-001" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; text-transform: uppercase;">
                        </div>
                    </div>
                </div>

                <!-- Tallas y Cantidades -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; color: #059669; margin-bottom: 0.75rem; font-size: 0.95rem;">
                        📏 TALLAS Y CANTIDADES *
                    </label>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; border: 2px solid #e5e7eb;">
                        <!-- Selectores de tipo y género -->
                        <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap;">
                            <select id="prenda-tipo-talla" onchange="actualizarTallasPrenda()" style="padding: 0.6rem 0.8rem; border: 2px solid #059669; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background: white; color: #059669; font-weight: 600;">
                                <option value="letra">LETRAS (XS-XXXL)</option>
                                <option value="numero">NÚMEROS (DAMA/CABALLERO)</option>
                            </select>
                            <select id="prenda-genero-talla" onchange="actualizarTallasPrenda()" style="padding: 0.6rem 0.8rem; border: 2px solid #059669; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background: white; color: #059669; font-weight: 600; display: none;">
                                <option value="">Selecciona género</option>
                                <option value="dama">Dama</option>
                                <option value="caballero">Caballero</option>
                            </select>
                        </div>
                        
                        <!-- Contenedor de tallas dinámico -->
                        <div id="tallas-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.75rem;">
                            <!-- Las tallas se generan dinámicamente -->
                        </div>
                        
                        <div style="margin-top: 0.75rem; padding: 0.5rem; background: #dbeafe; border-radius: 4px; text-align: center;">
                            <span style="font-size: 0.875rem; font-weight: 600; color: #1e40af;">Total: <span id="total-prendas">0</span> unidades</span>
                        </div>
                    </div>
                </div>

                <!-- Variaciones -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; color: #059669; margin-bottom: 0.75rem; font-size: 0.95rem;">
                        🔧 VARIACIONES ESPECÍFICAS
                    </label>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
                        <div style="padding: 0.75rem; background: #f9fafb; border-radius: 6px; border: 2px solid #e5e7eb;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-bottom: 0.5rem;">
                                <input type="checkbox" id="aplica-manga" style="width: 18px; height: 18px; accent-color: #059669;">
                                <span style="font-weight: 600; font-size: 0.875rem;">👕 Manga</span>
                            </label>
                            <input type="text" id="manga-input" placeholder="Ej: manga larga..." disabled style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem; opacity: 0.5;">
                        </div>
                        <div style="padding: 0.75rem; background: #f9fafb; border-radius: 6px; border: 2px solid #e5e7eb;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-bottom: 0.5rem;">
                                <input type="checkbox" id="aplica-bolsillos" style="width: 18px; height: 18px; accent-color: #059669;">
                                <span style="font-weight: 600; font-size: 0.875rem;">👜 Bolsillos</span>
                            </label>
                            <input type="text" id="bolsillos-input" placeholder="Ej: 2 bolsillos..." disabled style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem; opacity: 0.5;">
                        </div>
                        <div style="padding: 0.75rem; background: #f9fafb; border-radius: 6px; border: 2px solid #e5e7eb;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-bottom: 0.5rem;">
                                <input type="checkbox" id="aplica-broche" style="width: 18px; height: 18px; accent-color: #059669;">
                                <span style="font-weight: 600; font-size: 0.875rem;">🔘 Broche/Botón</span>
                            </label>
                            <input type="text" id="broche-input" placeholder="Ej: botones metálicos..." disabled style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem; opacity: 0.5;">
                        </div>
                        <div style="padding: 0.75rem; background: #f9fafb; border-radius: 6px; border: 2px solid #e5e7eb;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-bottom: 0.5rem;">
                                <input type="checkbox" id="aplica-puno" style="width: 18px; height: 18px; accent-color: #059669;">
                                <span style="font-weight: 600; font-size: 0.875rem;">⭕ Puño</span>
                            </label>
                            <input type="text" id="puno-input" placeholder="Ej: puño elástico..." disabled style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem; opacity: 0.5;">
                        </div>
                    </div>
                </div>
                
                <!-- Procesos -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; color: #059669; margin-bottom: 0.75rem; font-size: 0.95rem;">
                        ⚙️ PROCESOS (Opcional)
                    </label>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; padding: 1rem; background: #f9fafb; border-radius: 6px; border: 2px solid #e5e7eb;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 1rem; background: white; border: 2px solid #d1d5db; border-radius: 6px; transition: all 0.2s;" onmouseover="this.style.borderColor='#059669'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#d1d5db'">
                            <input type="checkbox" name="nueva-prenda-procesos" value="Bordado" style="width: 18px; height: 18px; accent-color: #059669;">
                            <span style="font-weight: 500;">🧵 Bordado</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 1rem; background: white; border: 2px solid #d1d5db; border-radius: 6px; transition: all 0.2s;" onmouseover="this.style.borderColor='#059669'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#d1d5db'">
                            <input type="checkbox" name="nueva-prenda-procesos" value="Estampado" style="width: 18px; height: 18px; accent-color: #059669;">
                            <span style="font-weight: 500;">🎨 Estampado</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 1rem; background: white; border: 2px solid #d1d5db; border-radius: 6px; transition: all 0.2s;" onmouseover="this.style.borderColor='#059669'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#d1d5db'">
                            <input type="checkbox" name="nueva-prenda-procesos" value="Reflectivo" style="width: 18px; height: 18px; accent-color: #059669;">
                            <span style="font-weight: 500;">💡 Reflectivo</span>
                        </label>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div style="padding: 1.5rem; border-top: 2px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 1rem; background: #f9fafb; position: sticky; bottom: 0;">
            <button onclick="cerrarModalPrendaNueva()" style="padding: 0.75rem 1.5rem; background: #6b7280; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#4b5563'" onmouseout="this.style.background='#6b7280'">
                Cancelar
            </button>
            <button onclick="agregarPrendaNueva()" style="padding: 0.75rem 1.5rem; background: #059669; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                ✓ Agregar Prenda
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Agregar Reflectivo -->
<div id="modal-agregar-reflectivo" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; overflow-y: auto; padding: 2rem 0;">
    <div style="background: white; border-radius: 12px; max-width: 800px; width: 95%; max-height: 95vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3); margin: auto;">
        <!-- Header -->
        <div style="padding: 1.5rem; border-bottom: 2px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); position: sticky; top: 0; z-index: 10;">
            <h3 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 600;">
                💡 Agregar Reflectivo
            </h3>
            <button onclick="cerrarModalReflectivo()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='none'">
                ✕
            </button>
        </div>
        
        <!-- Body -->
        <div style="padding: 1.5rem;">
            <form id="form-reflectivo-nuevo">
                <!-- Nombre de la prenda -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; color: #f59e0b; margin-bottom: 0.5rem; font-size: 0.95rem;">
                        👔 NOMBRE DE LA PRENDA *
                    </label>
                    <input type="text" id="reflectivo-prenda-nombre" required placeholder="Ej: CAMISA, CHALECO, PANTALÓN..." style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; text-transform: uppercase;">
                </div>

                <!-- Origen -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; color: #f59e0b; margin-bottom: 0.5rem; font-size: 0.95rem;">
                        📍 ORIGEN *
                    </label>
                    <div style="display: flex; gap: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem 1.5rem; border: 2px solid #d1d5db; border-radius: 6px; flex: 1; transition: all 0.2s;" onmouseover="this.style.borderColor='#f59e0b'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#d1d5db'">
                            <input type="radio" name="reflectivo-origen" value="bodega" checked style="width: 18px; height: 18px; accent-color: #f59e0b;">
                            <span style="font-weight: 500;">🏪 Bodega</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem 1.5rem; border: 2px solid #d1d5db; border-radius: 6px; flex: 1; transition: all 0.2s;" onmouseover="this.style.borderColor='#f59e0b'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#d1d5db'">
                            <input type="radio" name="reflectivo-origen" value="confeccion" style="width: 18px; height: 18px; accent-color: #f59e0b;">
                            <span style="font-weight: 500;">✂️ Confección</span>
                        </label>
                    </div>
                </div>

                <!-- Tallas y Cantidades -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; color: #f59e0b; margin-bottom: 0.75rem; font-size: 0.95rem;">
                        📏 TALLAS Y CANTIDADES *
                    </label>
                    <div style="background: #fef3c7; padding: 1rem; border-radius: 6px; border: 2px solid #fde68a;">
                        <!-- Selectores de tipo y género -->
                        <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap;">
                            <select id="reflectivo-tipo-talla" onchange="actualizarTallasReflectivo()" style="padding: 0.6rem 0.8rem; border: 2px solid #f59e0b; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background: white; color: #f59e0b; font-weight: 600;">
                                <option value="letra">LETRAS (XS-XXXL)</option>
                                <option value="numero">NÚMEROS (DAMA/CABALLERO)</option>
                            </select>
                            <select id="reflectivo-genero-talla" onchange="actualizarTallasReflectivo()" style="padding: 0.6rem 0.8rem; border: 2px solid #f59e0b; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background: white; color: #f59e0b; font-weight: 600; display: none;">
                                <option value="">Selecciona género</option>
                                <option value="dama">Dama</option>
                                <option value="caballero">Caballero</option>
                            </select>
                        </div>
                        
                        <!-- Contenedor de tallas dinámico -->
                        <div id="reflectivo-tallas-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.75rem;">
                            <!-- Las tallas se generan dinámicamente -->
                        </div>
                        
                        <div style="margin-top: 0.75rem; padding: 0.5rem; background: #fbbf24; border-radius: 4px; text-align: center;">
                            <span style="font-size: 0.875rem; font-weight: 600; color: #78350f;">Total: <span id="total-reflectivo">0</span> unidades</span>
                        </div>
                    </div>
                </div>

                <!-- Ubicaciones del reflectivo -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; color: #f59e0b; margin-bottom: 0.75rem; font-size: 0.95rem;">
                        📍 UBICACIONES DEL REFLECTIVO
                    </label>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; padding: 1rem; background: #fef3c7; border-radius: 6px; border: 2px solid #fde68a;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; background: white; border: 2px solid #d1d5db; border-radius: 6px;">
                            <input type="checkbox" name="reflectivo-ubicacion" value="Pecho" style="width: 18px; height: 18px; accent-color: #f59e0b;">
                            <span style="font-weight: 500;">Pecho</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; background: white; border: 2px solid #d1d5db; border-radius: 6px;">
                            <input type="checkbox" name="reflectivo-ubicacion" value="Espalda" style="width: 18px; height: 18px; accent-color: #f59e0b;">
                            <span style="font-weight: 500;">Espalda</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; background: white; border: 2px solid #d1d5db; border-radius: 6px;">
                            <input type="checkbox" name="reflectivo-ubicacion" value="Mangas" style="width: 18px; height: 18px; accent-color: #f59e0b;">
                            <span style="font-weight: 500;">Mangas</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; background: white; border: 2px solid #d1d5db; border-radius: 6px;">
                            <input type="checkbox" name="reflectivo-ubicacion" value="Piernas" style="width: 18px; height: 18px; accent-color: #f59e0b;">
                            <span style="font-weight: 500;">Piernas</span>
                        </label>
                    </div>
                </div>

                <!-- Observaciones -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; color: #f59e0b; margin-bottom: 0.5rem; font-size: 0.95rem;">
                        📝 OBSERVACIONES
                    </label>
                    <textarea id="reflectivo-observaciones" placeholder="Observaciones adicionales sobre el reflectivo..." style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; resize: vertical; min-height: 80px;"></textarea>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div style="padding: 1.5rem; border-top: 2px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 1rem; background: #fef3c7; position: sticky; bottom: 0;">
            <button onclick="cerrarModalReflectivo()" style="padding: 0.75rem 1.5rem; background: #6b7280; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#4b5563'" onmouseout="this.style.background='#6b7280'">
                Cancelar
            </button>
            <button onclick="agregarReflectivo()" style="padding: 0.75rem 1.5rem; background: #f59e0b; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'">
                ✓ Agregar Reflectivo
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <!-- IMPORTANTE: Cargar módulos PRIMERO -->
    <script src="{{ asset('js/modulos/crear-pedido/gestion-items-pedido.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/modal-seleccion-prendas.js') }}"></script>
    
    <!-- IMPORTANTE: Definir cotizaciones ANTES de cargar cualquier script -->
    <script>
        // ========== DEBUG DETALLADO ==========
        console.log('🔍 [BLADE] isset($cotizaciones):', {{ isset($cotizaciones) ? 'true' : 'false' }});
        console.log('🔍 [BLADE] Tipo de $cotizaciones:', '{{ isset($cotizaciones) ? get_class($cotizaciones) : "no definido" }}');
        console.log('🔍 [BLADE] Count de cotizaciones:', {{ isset($cotizaciones) ? $cotizaciones->count() : 0 }});
        
        @if(isset($cotizaciones) && $cotizaciones->count() > 0)
            console.log('✅ [BLADE] Hay cotizaciones, procesando...');
            @foreach($cotizaciones as $index => $cot)
                console.log('📋 [BLADE] Cotización {{ $index + 1 }}:', {
                    id: {{ $cot->id }},
                    numero: '{{ $cot->numero_cotizacion }}',
                    estado: '{{ $cot->estado }}',
                    cliente: '{{ $cot->cliente ? $cot->cliente->nombre : "Sin cliente" }}'
                });
            @endforeach
        @else
            console.error('❌ [BLADE] NO hay cotizaciones o $cotizaciones no está definido');
        @endif
        
        @php
            $cotizacionesArray = [];
            if (isset($cotizaciones) && $cotizaciones->count() > 0) {
                foreach ($cotizaciones as $cot) {
                    $formaPago = '';
                    if (is_array($cot->especificaciones) && isset($cot->especificaciones['forma_pago'])) {
                        $formaPagoArray = $cot->especificaciones['forma_pago'];
                        if (is_array($formaPagoArray) && count($formaPagoArray) > 0) {
                            $formaPago = $formaPagoArray[0]['valor'] ?? '';
                        }
                    }
                    
                    $cotizacionesArray[] = [
                        'id' => $cot->id,
                        'numero_cotizacion' => $cot->numero_cotizacion,
                        'numero' => $cot->numero_cotizacion ?: 'COT-' . $cot->id,
                        'cliente' => $cot->cliente ? $cot->cliente->nombre : '',
                        'asesora' => $cot->asesor ? $cot->asesor->name : Auth::user()->name,
                        'formaPago' => $formaPago,
                        'prendasCount' => $cot->prendasCotizaciones->count()
                    ];
                }
            }
        @endphp
        
        // 🔍 DEBUG: Verificar JSON generado
        console.log('🔍 [DEBUG] JSON RAW:', '{!! json_encode($cotizacionesArray) !!}');
        console.log('🔍 [DEBUG] Tipo de JSON:', typeof {!! json_encode($cotizacionesArray) !!});
        
        window.cotizacionesData = {!! json_encode($cotizacionesArray) !!};
        
        // Debug: Ver qué se asignó a window.cotizacionesData
        console.log('📋 window.cotizacionesData:', window.cotizacionesData);
        console.log('📏 Cantidad de cotizaciones:', window.cotizacionesData.length);
        console.log('🔍 [DEBUG] Array.isArray:', Array.isArray(window.cotizacionesData));
        
        window.asesorActualNombre = '{{ Auth::user()->name ?? '' }}';
    </script>
    <script>
        // Configuración inicial
        document.addEventListener('DOMContentLoaded', function() {
            // ========== BUSCADOR DE COTIZACIONES ==========
            const searchInput = document.getElementById('cotizacion_search_editable');
            const dropdown = document.getElementById('cotizacion_dropdown_editable');
            const selectedDiv = document.getElementById('cotizacion_selected_editable');
            const selectedText = document.getElementById('cotizacion_selected_text_editable');
            const hiddenInput = document.getElementById('cotizacion_id_editable');
            
            console.log('🔍 [BUSCADOR] Elementos encontrados:', {
                searchInput: !!searchInput,
                dropdown: !!dropdown,
                selectedDiv: !!selectedDiv,
                selectedText: !!selectedText,
                hiddenInput: !!hiddenInput
            });
            
            if (!searchInput) {
                console.error('❌ [BUSCADOR] No se encontró el input de búsqueda');
                return;
            }
            
            let cotizacionSeleccionada = null;
            
            // Mostrar todas las cotizaciones al hacer focus
            searchInput.addEventListener('focus', function() {
                console.log('🔍 [BUSCADOR] Focus en el campo de búsqueda');
                mostrarCotizaciones('');
            });
            
            // Filtrar cotizaciones al escribir
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                console.log('🔍 [BUSCADOR] Búsqueda:', searchTerm);
                mostrarCotizaciones(searchTerm);
            });
            
            // Función para mostrar cotizaciones filtradas
            function mostrarCotizaciones(searchTerm) {
                if (searchTerm.length === 0) {
                    // Mostrar todas las cotizaciones
                    renderizarDropdown(window.cotizacionesData);
                    return;
                }
                
                const filtered = window.cotizacionesData.filter(cot => {
                    return cot.numero_cotizacion.toLowerCase().includes(searchTerm) ||
                           cot.cliente.toLowerCase().includes(searchTerm) ||
                           cot.asesora.toLowerCase().includes(searchTerm);
                });
                
                renderizarDropdown(filtered);
            }
            
            // Función para renderizar el dropdown
            function renderizarDropdown(cotizaciones) {
                console.log('🔍 [BUSCADOR] Renderizando dropdown con', cotizaciones.length, 'cotizaciones');
                
                if (cotizaciones.length === 0) {
                    dropdown.innerHTML = '<div style="padding: 1rem; text-align: center; color: #6b7280;">No se encontraron cotizaciones</div>';
                    dropdown.style.display = 'block';
                    console.log('🔍 [BUSCADOR] Mostrando mensaje de "no encontrado"');
                    return;
                }
                
                dropdown.innerHTML = cotizaciones.map(cot => `
                    <div class="cotizacion-item" data-id="${cot.id}" style="padding: 0.75rem; cursor: pointer; border-bottom: 1px solid #e5e7eb; transition: background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='white'">
                        <div style="font-weight: 600; color: #1e40af;">${cot.numero_cotizacion}</div>
                        <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">
                            Cliente: ${cot.cliente} | Asesora: ${cot.asesora}
                        </div>
                    </div>
                `).join('');
                
                dropdown.style.display = 'block';
                console.log('✅ [BUSCADOR] Dropdown mostrado con', cotizaciones.length, 'resultados');
                
                // Agregar event listeners a los items
                dropdown.querySelectorAll('.cotizacion-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const cotId = parseInt(this.dataset.id);
                        const cotizacion = window.cotizacionesData.find(c => c.id === cotId);
                        seleccionarCotizacion(cotizacion);
                    });
                });
            }
            
            // Función para seleccionar cotización
            function seleccionarCotizacion(cotizacion) {
                cotizacionSeleccionada = cotizacion;
                hiddenInput.value = cotizacion.id;
                searchInput.value = cotizacion.numero_cotizacion;
                selectedText.textContent = `${cotizacion.numero_cotizacion} - ${cotizacion.cliente}`;
                selectedDiv.style.display = 'block';
                dropdown.style.display = 'none';
                
                console.log('✅ Cotización seleccionada:', cotizacion);
                
                // Abrir modal de selección de prendas
                if (typeof window.abrirModalSeleccionPrendas === 'function') {
                    window.abrirModalSeleccionPrendas(cotizacion);
                } else {
                    console.error('❌ Función abrirModalSeleccionPrendas no está disponible');
                }
            }
            
            // Cerrar dropdown al hacer click fuera
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });
            
            // ========== RESTO DE LA CONFIGURACIÓN ==========
            // Configurar asesora
            document.getElementById('asesora_editable').value = '{{ Auth::user()->name ?? '' }}';
            
            // Mostrar botón submit
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.textContent = '✓ Crear Pedido';
            btnSubmit.style.display = 'block';

            // ========== OCULTAR LOADING Y MOSTRAR SELECT DE TIPO DE PEDIDO ==========
            const tipoPedidoLoading = document.getElementById('tipo-pedido-loading');
            const tipoPedidoSelect = document.getElementById('tipo_pedido_nuevo');
            
            if (tipoPedidoLoading && tipoPedidoSelect) {
                setTimeout(() => {
                    tipoPedidoLoading.style.display = 'none';
                    tipoPedidoSelect.style.display = 'block';
                    tipoPedidoSelect.removeAttribute('disabled');
                    console.log('✅ Selector de tipo de pedido listo');
                }, 500);
            }

            // ========== DETECTAR TIPO INICIAL DESDE RUTA ==========
            const tipoInicial = '{{ $tipoInicial ?? "cotizacion" }}';
            
            // ========== MANEJAR CAMBIO DE TIPO DE PEDIDO ==========
            const tipoDesdeRadio = document.getElementById('tipo_desde_cotizacion');
            const tipoNuevoRadio = document.getElementById('tipo_nuevo_pedido');
            const tipoHidden = document.getElementById('tipo_pedido_hidden');
            const seccionBuscarCotizacion = document.getElementById('seccion-buscar-cotizacion');
            const seccionTipoPedidoNuevo = document.getElementById('seccion-tipo-pedido-nuevo');
            const selectTipoPedidoNuevo = document.getElementById('tipo_pedido_nuevo');
            const campNumeroCotizacion = document.getElementById('campo-numero-cotizacion');

            function actualizarVistaPedido() {
                // Si hay tipo inicial forzado, usar ese
                let tipoActual = tipoInicial;
                
                // Si no hay tipo inicial, usar los radio buttons
                if (!tipoHidden && tipoDesdeRadio && tipoNuevoRadio) {
                    tipoActual = tipoDesdeRadio.checked ? 'cotizacion' : 'nuevo';
                }
                
                if (tipoActual === 'cotizacion') {
                    // Mostrar buscador de cotización
                    if (seccionBuscarCotizacion) seccionBuscarCotizacion.style.display = 'block';
                    if (seccionTipoPedidoNuevo) seccionTipoPedidoNuevo.style.display = 'none';
                    if (campNumeroCotizacion) campNumeroCotizacion.style.display = 'block';
                } else if (tipoActual === 'nuevo') {
                    // Mostrar selector de tipo de pedido nuevo
                    if (seccionBuscarCotizacion) seccionBuscarCotizacion.style.display = 'none';
                    if (seccionTipoPedidoNuevo) seccionTipoPedidoNuevo.style.display = 'block';
                    if (campNumeroCotizacion) campNumeroCotizacion.style.display = 'none';
                }
            }

            // Ejecutar al cargar para configurar vista inicial
            actualizarVistaPedido();

            // Listener para cambios en radio buttons (solo si existen)
            if (tipoDesdeRadio) tipoDesdeRadio.addEventListener('change', actualizarVistaPedido);
            if (tipoNuevoRadio) tipoNuevoRadio.addEventListener('change', actualizarVistaPedido);

            // ========== GESTIÓN DE ÍTEMS DINÁMICOS ==========
            // ⚠️ REFACTORIZADO: El código de gestión de ítems ahora está en gestion-items-pedido.js
            // Solo mantenemos las referencias al DOM que se necesitan aquí
            const seccionItems = document.getElementById('seccion-items-pedido');
            const btnAgregarItemCotizacion = document.getElementById('btn-agregar-item-cotizacion');
            const btnAgregarItemTipo = document.getElementById('btn-agregar-item-tipo');
            
            // ⚠️ itemsPedido ahora es window.itemsPedido (definido en gestion-items-pedido.js)

            // Mostrar sección de ítems según el tipo
            function mostrarSeccionItems() {
                if (seccionItems) {
                    seccionItems.style.display = 'block';
                    
                    // Mostrar botones según el tipo
                    if (tipoInicial === 'cotizacion') {
                        btnAgregarItemCotizacion.style.display = 'flex';
                        btnAgregarItemTipo.style.display = 'none';
                    } else if (tipoInicial === 'nuevo') {
                        btnAgregarItemCotizacion.style.display = 'none';
                        btnAgregarItemTipo.style.display = 'flex';
                    }
                    
                    window.actualizarVistaItems(); // ⚠️ Ahora es función global del módulo
                }
            }

            // ⚠️ REFACTORIZADO: Las siguientes funciones ahora están en gestion-items-pedido.js
            // - window.actualizarVistaItems()
            // - renderizarItems()
            // - determinarCategoria()
            // - obtenerColorCategoria()
            // - window.eliminarItem()
            // - window.obtenerItemsPedido()
            // - window.tieneItems()

            /* ============================================================
               CÓDIGO COMENTADO - AHORA EN gestion-items-pedido.js
               ============================================================
               Las siguientes funciones fueron movidas al módulo:
               - actualizarVistaItems()
               - renderizarItems()
               - determinarCategoria()
               - obtenerColorCategoria()
               - eliminarItem()
               - obtenerItemsPedido()
               - tieneItems()
               ============================================================ */

            // Agregar ítem desde cotización
            btnAgregarItemCotizacion.addEventListener('click', function() {
                // Mostrar el buscador de cotización si está oculto
                if (seccionBuscarCotizacion.style.display === 'none') {
                    seccionBuscarCotizacion.style.display = 'block';
                }
                // Focus en el input de búsqueda
                document.getElementById('cotizacion_search_editable').focus();
            });

            // Agregar ítem de tipo nuevo
            btnAgregarItemTipo.addEventListener('click', function() {
                const tipoPedido = selectTipoPedidoNuevo.value;
                
                if (!tipoPedido) {
                    alert('Por favor selecciona un tipo de pedido primero');
                    return;
                }
                
                console.log('🎯 Abriendo modal para tipo:', tipoPedido);
                
                // Manejar diferentes tipos de pedido
                if (tipoPedido === 'P') {
                    window.abrirModalPrendaNueva();
                } else if (tipoPedido === 'R') {
                    window.abrirModalReflectivo();
                } else {
                    alert('Tipo de pedido "' + tipoPedido + '" en desarrollo');
                }
            });

            // Función para agregar cotización a la lista
            window.agregarCotizacionAItems = function(cotizacion) {
                const item = {
                    tipo: 'cotizacion',
                    id: cotizacion.id,
                    numero: cotizacion.numero_cotizacion,
                    cliente: cotizacion.cliente,
                    data: cotizacion
                };
                itemsPedido.push(item);
                actualizarVistaItems();
            };

            // Función para agregar tipo nuevo a la lista
            window.agregarTipoAItems = function(tipo, nombre) {
                const item = {
                    tipo: 'nuevo',
                    tipoId: tipo,
                    nombre: nombre
                };
                itemsPedido.push(item);
                actualizarVistaItems();
            };

            // Manejar cambio de tipo de pedido nuevo
            window.manejarCambiaTipoPedido = function() {
                const tipoPedido = selectTipoPedidoNuevo.value;
                
                if (!tipoPedido) return;
                
                console.log('🔄 Tipo de pedido seleccionado:', tipoPedido);
                
                // Mostrar botón de agregar tipo
                const btnAgregarTipo = document.getElementById('btn-agregar-item-tipo');
                if (btnAgregarTipo) {
                    btnAgregarTipo.style.display = 'flex';
                }
            };
            
            // ========== MODAL DE PRENDA NUEVA ==========
            window.abrirModalPrendaNueva = function() {
                const modal = document.getElementById('modal-agregar-prenda-nueva');
                if (modal) {
                    modal.style.display = 'flex';
                    limpiarFormularioPrendaNueva();
                    configurarEventosFormulario();
                }
            };
            
            function limpiarFormularioPrendaNueva() {
                document.getElementById('nueva-prenda-nombre').value = '';
                document.getElementById('nueva-prenda-descripcion').value = '';
                document.getElementById('nueva-prenda-color').value = '';
                document.getElementById('nueva-prenda-tela').value = '';
                document.getElementById('nueva-prenda-referencia').value = '';
                
                // Reset selectores de talla
                document.getElementById('prenda-tipo-talla').value = 'letra';
                document.getElementById('prenda-genero-talla').value = '';
                document.getElementById('prenda-genero-talla').style.display = 'none';
                
                // Inicializar tallas por defecto (letras)
                actualizarTallasPrenda();
                
                // Limpiar variaciones
                document.querySelectorAll('#modal-agregar-prenda-nueva input[type="checkbox"]').forEach(cb => cb.checked = false);
                document.querySelectorAll('#manga-input, #bolsillos-input, #broche-input, #puno-input').forEach(input => {
                    input.value = '';
                    input.disabled = true;
                    input.style.opacity = '0.5';
                });
                
                // Reset origen
                document.querySelector('input[name="nueva-prenda-origen"][value="bodega"]').checked = true;
            }
            
            function configurarEventosFormulario() {
                // Habilitar/deshabilitar inputs de variaciones
                const mangaCb = document.getElementById('aplica-manga');
                const bolsillosCb = document.getElementById('aplica-bolsillos');
                const brocheCb = document.getElementById('aplica-broche');
                const punoCb = document.getElementById('aplica-puno');
                
                // Remover listeners anteriores si existen
                if (mangaCb._configured) return;
                
                mangaCb.addEventListener('change', function() {
                    const input = document.getElementById('manga-input');
                    input.disabled = !this.checked;
                    input.style.opacity = this.checked ? '1' : '0.5';
                });
                
                bolsillosCb.addEventListener('change', function() {
                    const input = document.getElementById('bolsillos-input');
                    input.disabled = !this.checked;
                    input.style.opacity = this.checked ? '1' : '0.5';
                });
                
                brocheCb.addEventListener('change', function() {
                    const input = document.getElementById('broche-input');
                    input.disabled = !this.checked;
                    input.style.opacity = this.checked ? '1' : '0.5';
                });
                
                punoCb.addEventListener('change', function() {
                    const input = document.getElementById('puno-input');
                    input.disabled = !this.checked;
                    input.style.opacity = this.checked ? '1' : '0.5';
                });
                
                // Marcar como configurado
                mangaCb._configured = true;
            }
            
            function actualizarTotalPrendas() {
                let total = 0;
                document.querySelectorAll('#tallas-container input[type="number"]').forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                document.getElementById('total-prendas').textContent = total;
            }
            
            window.cerrarModalPrendaNueva = function() {
                const modal = document.getElementById('modal-agregar-prenda-nueva');
                if (modal) {
                    modal.style.display = 'none';
                }
            };
            
            window.agregarPrendaNueva = function() {
                const nombre = document.getElementById('nueva-prenda-nombre').value.trim().toUpperCase();
                const descripcion = document.getElementById('nueva-prenda-descripcion').value.trim();
                const color = document.getElementById('nueva-prenda-color').value.trim().toUpperCase();
                const tela = document.getElementById('nueva-prenda-tela').value.trim().toUpperCase();
                const referencia = document.getElementById('nueva-prenda-referencia').value.trim().toUpperCase();
                const origen = document.querySelector('input[name="nueva-prenda-origen"]:checked').value;
                
                if (!nombre) {
                    alert('Por favor ingresa el nombre de la prenda');
                    return;
                }
                
                // Obtener tallas y cantidades
                const tallas = [];
                let cantidadTotal = 0;
                document.querySelectorAll('#tallas-container input[type="number"]').forEach(input => {
                    const cantidad = parseInt(input.value) || 0;
                    if (cantidad > 0) {
                        const talla = input.name.replace('talla-', '').toUpperCase();
                        tallas.push({ talla, cantidad });
                        cantidadTotal += cantidad;
                    }
                });
                
                if (cantidadTotal === 0) {
                    alert('Por favor ingresa al menos una cantidad en las tallas');
                    return;
                }
                
                // Obtener variaciones
                const variaciones = {};
                if (document.getElementById('aplica-manga').checked) {
                    variaciones.manga = document.getElementById('manga-input').value.trim();
                }
                if (document.getElementById('aplica-bolsillos').checked) {
                    variaciones.bolsillos = document.getElementById('bolsillos-input').value.trim();
                }
                if (document.getElementById('aplica-broche').checked) {
                    variaciones.broche = document.getElementById('broche-input').value.trim();
                }
                if (document.getElementById('aplica-puno').checked) {
                    variaciones.puno = document.getElementById('puno-input').value.trim();
                }
                
                // Obtener procesos seleccionados
                const procesos = [];
                document.querySelectorAll('input[name="nueva-prenda-procesos"]:checked').forEach(cb => {
                    procesos.push(cb.value);
                });
                
                console.log('➕ Agregando prenda nueva:', { nombre, cantidadTotal, origen, procesos, tallas, variaciones });
                
                // Estructura completa de la prenda
                const prendaData = {
                    nombre: nombre,
                    descripcion: descripcion,
                    color: color,
                    tela: tela,
                    referencia: referencia,
                    cantidad: cantidadTotal,
                    tallas: tallas,
                    variaciones: variaciones
                };
                
                // REGLA DE SPLIT: Si tiene procesos, crear 2 ítems
                if (procesos.length > 0) {
                    // ÍTEM 1: Prenda BASE (sin procesos)
                    window.itemsPedido.push({
                        tipo: 'nuevo',
                        prenda: prendaData,
                        origen: origen,
                        procesos: [],
                        es_proceso: false
                    });
                    
                    // ÍTEM 2: Prenda PROCESO (con procesos)
                    window.itemsPedido.push({
                        tipo: 'nuevo',
                        prenda: prendaData,
                        origen: origen,
                        procesos: procesos,
                        es_proceso: true
                    });
                    
                    console.log(`✅ Prenda "${nombre}" agregada como 2 ítems (BASE + PROCESO)`);
                } else {
                    // Sin procesos: 1 solo ítem
                    window.itemsPedido.push({
                        tipo: 'nuevo',
                        prenda: prendaData,
                        origen: origen,
                        procesos: [],
                        es_proceso: false
                    });
                    
                    console.log(`✅ Prenda "${nombre}" agregada como 1 ítem (sin procesos)`);
                }
                
                // Actualizar vista
                window.actualizarVistaItems();
                
                // Cerrar modal
                window.cerrarModalPrendaNueva();
            };

            // ========== MODAL DE REFLECTIVO ==========
            window.abrirModalReflectivo = function() {
                const modal = document.getElementById('modal-agregar-reflectivo');
                if (modal) {
                    modal.style.display = 'flex';
                    limpiarFormularioReflectivo();
                    configurarEventosReflectivo();
                }
            };
            
            function limpiarFormularioReflectivo() {
                document.getElementById('reflectivo-prenda-nombre').value = '';
                document.getElementById('reflectivo-observaciones').value = '';
                
                // Reset selectores de talla
                document.getElementById('reflectivo-tipo-talla').value = 'letra';
                document.getElementById('reflectivo-genero-talla').value = '';
                document.getElementById('reflectivo-genero-talla').style.display = 'none';
                
                // Inicializar tallas por defecto (letras)
                actualizarTallasReflectivo();
                
                // Limpiar ubicaciones
                document.querySelectorAll('input[name="reflectivo-ubicacion"]').forEach(cb => cb.checked = false);
                
                // Reset origen
                document.querySelector('input[name="reflectivo-origen"][value="bodega"]').checked = true;
            }
            
            function configurarEventosReflectivo() {
                // No necesita configurar eventos adicionales
                // Los eventos onchange ya están en los inputs dinámicos
            }
            
            function actualizarTotalReflectivo() {
                let total = 0;
                document.querySelectorAll('#reflectivo-tallas-container input[type="number"]').forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                document.getElementById('total-reflectivo').textContent = total;
            }
            
            window.cerrarModalReflectivo = function() {
                const modal = document.getElementById('modal-agregar-reflectivo');
                if (modal) {
                    modal.style.display = 'none';
                }
            };
            
            window.agregarReflectivo = function() {
                const nombre = document.getElementById('reflectivo-prenda-nombre').value.trim().toUpperCase();
                const observaciones = document.getElementById('reflectivo-observaciones').value.trim();
                const origen = document.querySelector('input[name="reflectivo-origen"]:checked').value;
                
                if (!nombre) {
                    alert('Por favor ingresa el nombre de la prenda');
                    return;
                }
                
                // Obtener tallas y cantidades
                const tallas = [];
                let cantidadTotal = 0;
                document.querySelectorAll('#reflectivo-tallas-container input[type="number"]').forEach(input => {
                    const cantidad = parseInt(input.value) || 0;
                    if (cantidad > 0) {
                        const talla = input.name.replace('reflectivo-talla-', '').toUpperCase();
                        tallas.push({ talla, cantidad });
                        cantidadTotal += cantidad;
                    }
                });
                
                if (cantidadTotal === 0) {
                    alert('Por favor ingresa al menos una cantidad en las tallas');
                    return;
                }
                
                // Obtener ubicaciones del reflectivo
                const ubicaciones = [];
                document.querySelectorAll('input[name="reflectivo-ubicacion"]:checked').forEach(cb => {
                    ubicaciones.push(cb.value);
                });
                
                console.log('➕ Agregando reflectivo:', { nombre, cantidadTotal, origen, ubicaciones, observaciones });
                
                // Estructura del reflectivo
                const reflectivoData = {
                    nombre: nombre,
                    cantidad: cantidadTotal,
                    tallas: tallas,
                    ubicaciones: ubicaciones,
                    observaciones: observaciones
                };
                
                // REFLECTIVO SIEMPRE TIENE PROCESO
                // ÍTEM 1: Prenda BASE (sin procesos)
                window.itemsPedido.push({
                    tipo: 'nuevo',
                    prenda: reflectivoData,
                    origen: origen,
                    procesos: [],
                    es_proceso: false
                });
                
                // ÍTEM 2: REFLECTIVO (con proceso reflectivo)
                window.itemsPedido.push({
                    tipo: 'nuevo',
                    prenda: reflectivoData,
                    origen: origen,
                    procesos: ['Reflectivo'],
                    es_proceso: true
                });
                
                console.log(`✅ Reflectivo "${nombre}" agregado como 2 ítems (BASE + REFLECTIVO)`);
                
                // Actualizar vista
                window.actualizarVistaItems();
                
                // Cerrar modal
                window.cerrarModalReflectivo();
            };

            // ========== TALLAS DINÁMICAS POR GÉNERO ==========
            const TALLAS_LETRAS = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
            const TALLAS_NUMEROS_DAMA = ['2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28'];
            const TALLAS_NUMEROS_CABALLERO = ['30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50', '52', '54', '56'];
            
            // Función para actualizar tallas de PRENDA
            window.actualizarTallasPrenda = function() {
                const tipoTalla = document.getElementById('prenda-tipo-talla').value;
                const generoSelect = document.getElementById('prenda-genero-talla');
                const container = document.getElementById('tallas-container');
                
                // Mostrar/ocultar selector de género
                if (tipoTalla === 'numero') {
                    generoSelect.style.display = 'block';
                } else {
                    generoSelect.style.display = 'none';
                    generoSelect.value = '';
                }
                
                // Generar tallas
                let tallas = [];
                if (tipoTalla === 'letra') {
                    tallas = TALLAS_LETRAS;
                } else if (tipoTalla === 'numero') {
                    const genero = generoSelect.value;
                    if (genero === 'dama') {
                        tallas = TALLAS_NUMEROS_DAMA;
                    } else if (genero === 'caballero') {
                        tallas = TALLAS_NUMEROS_CABALLERO;
                    }
                }
                
                // Renderizar inputs de tallas
                container.innerHTML = '';
                tallas.forEach(talla => {
                    const div = document.createElement('div');
                    div.style.cssText = 'display: flex; flex-direction: column; gap: 0.25rem;';
                    div.innerHTML = `
                        <label style="font-size: 0.75rem; font-weight: 600; color: #6b7280;">${talla}</label>
                        <input type="number" name="talla-${talla.toLowerCase()}" min="0" value="0" onchange="actualizarTotalPrendas()" style="padding: 0.5rem; border: 2px solid #d1d5db; border-radius: 4px; text-align: center; font-weight: 600;">
                    `;
                    container.appendChild(div);
                });
                
                actualizarTotalPrendas();
            };
            
            // Función para actualizar tallas de REFLECTIVO
            window.actualizarTallasReflectivo = function() {
                const tipoTalla = document.getElementById('reflectivo-tipo-talla').value;
                const generoSelect = document.getElementById('reflectivo-genero-talla');
                const container = document.getElementById('reflectivo-tallas-container');
                
                // Mostrar/ocultar selector de género
                if (tipoTalla === 'numero') {
                    generoSelect.style.display = 'block';
                } else {
                    generoSelect.style.display = 'none';
                    generoSelect.value = '';
                }
                
                // Generar tallas
                let tallas = [];
                if (tipoTalla === 'letra') {
                    tallas = TALLAS_LETRAS;
                } else if (tipoTalla === 'numero') {
                    const genero = generoSelect.value;
                    if (genero === 'dama') {
                        tallas = TALLAS_NUMEROS_DAMA;
                    } else if (genero === 'caballero') {
                        tallas = TALLAS_NUMEROS_CABALLERO;
                    }
                }
                
                // Renderizar inputs de tallas
                container.innerHTML = '';
                tallas.forEach(talla => {
                    const div = document.createElement('div');
                    div.style.cssText = 'display: flex; flex-direction: column; gap: 0.25rem;';
                    div.innerHTML = `
                        <label style="font-size: 0.75rem; font-weight: 600; color: #92400e;">${talla}</label>
                        <input type="number" name="reflectivo-talla-${talla.toLowerCase()}" min="0" value="0" onchange="actualizarTotalReflectivo()" style="padding: 0.5rem; border: 2px solid #d1d5db; border-radius: 4px; text-align: center; font-weight: 600;">
                    `;
                    container.appendChild(div);
                });
                
                actualizarTotalReflectivo();
            };

            // Eliminar ítem
            function eliminarItem(index) {
                itemsPedido.splice(index, 1);
                actualizarVistaItems();
            }

            // Mostrar sección de ítems al cargar
            setTimeout(() => {
                mostrarSeccionItems();
            }, 1000);

            // Función global para obtener ítems del pedido
            window.obtenerItemsPedido = function() {
                return itemsPedido;
            };

            // Función global para verificar si hay ítems
            window.tieneItems = function() {
                return itemsPedido.length > 0;
            };

            // ========== MODAL DE SELECCIÓN DE PRENDAS ==========
            // ⚠️ REFACTORIZADO: Todo el código del modal ahora está en modal-seleccion-prendas.js
            // Funciones disponibles:
            // - window.abrirModalSeleccionPrendas(cotizacion)
            // - window.cerrarModalPrendas()
            // - window.agregarPrendasSeleccionadas()
            // - window.togglePrendaSeleccion(index)
            // - window.actualizarOrigenPrenda(index, origen)

            // ========== OCULTAR LOADING SCREEN ==========
            setTimeout(() => {
                const loadingOverlay = document.getElementById('page-loading-overlay');
                if (loadingOverlay) {
                    loadingOverlay.classList.add('fade-out');
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                    }, 300);
                }
            }, 500);
        });
    </script>
@endpush