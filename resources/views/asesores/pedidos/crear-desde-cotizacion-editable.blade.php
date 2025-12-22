@extends('layouts.asesores')

@include('components.modal-imagen')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ============================================================
           ESTILOS GLOBALES Y CONSISTENTES
           ============================================================ */
        
        /* Colores del diseño */
        :root {
            --color-primary: #1e40af;
            --color-primary-dark: #0052a3;
            --color-danger: #dc3545;
            --color-danger-dark: #c82333;
            --color-text: #333333;
            --color-text-secondary: #666666;
            --color-border: #d0d0d0;
            --color-bg-light: #f5f5f5;
            --color-bg-input: #ffffff;
            --secondary: #0ea5e9;
            --accent: #06b6d4;
        }

        /* ============================================================
           ESTILOS TABS - IGUAL A COTIZACIONES
           ============================================================ */
        
        .tabs-container {
            display: flex;
            gap: 0;
            margin-bottom: 0;
            border-bottom: 2px solid #e2e8f0;
            background: white;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            width: 100%;
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            color: #64748b;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 3px solid transparent;
            position: relative;
            bottom: -2px;
            white-space: nowrap;
        }

        .tab-button:hover {
            background: #f8fafc;
            color: var(--color-primary);
        }

        .tab-button.active {
            color: white;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--secondary) 100%);
            border-bottom-color: var(--secondary);
        }

        .tab-button i {
            font-size: 1rem;
        }

        .tab-content-wrapper {
            background: white;
            border-radius: 0 0 12px 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            width: 100%;
            display: block;
            box-sizing: border-box;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
    <p>Selecciona una cotización y personaliza las prendas antes de crear el pedido</p>
</div>

<div style="width: 100%; padding: 1.5rem;">
    <form id="formCrearPedidoEditable" class="space-y-6">
        @csrf

        <!-- PASO 1: Seleccionar Cotización -->
        <div class="form-section">
            <h2>
                <span>1</span> Seleccionar Cotización
            </h2>

            <div class="form-group">
                <label for="cotizacion_search_editable" class="block text-sm font-medium text-gray-700 mb-2">
                    Cotización <span class="text-red-500">*</span>
                </label>
                <div style="position: relative;">
                    <input type="text" id="cotizacion_search_editable" placeholder="🔍 Buscar por número, cliente o asesora..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                    <input type="hidden" id="cotizacion_id_editable" name="cotizacion_id" required>
                    <div id="cotizacion_dropdown_editable" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #d1d5db; border-top: none; border-radius: 0 0 8px 8px; max-height: 300px; overflow-y: auto; display: none; z-index: 1000; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    </div>
                </div>
                <div id="cotizacion_selected_editable" style="margin-top: 0.75rem; padding: 0.75rem; background: #f0f9ff; border-left: 3px solid #0066cc; border-radius: 4px; display: none;">
                    <div style="font-size: 0.875rem; color: #1e40af;"><strong>Seleccionada:</strong> <span id="cotizacion_selected_text_editable"></span></div>
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
                    <label for="numero_cotizacion_editable">Número de Cotización</label>
                    <input type="text" id="numero_cotizacion_editable" name="numero_cotizacion" readonly>
                </div>

                <div class="form-group">
                    <label for="cliente_editable">Cliente</label>
                    <input type="text" id="cliente_editable" name="cliente" readonly>
                </div>

                <div class="form-group">
                    <label for="asesora_editable">Asesora</label>
                    <input type="text" id="asesora_editable" name="asesora" readonly>
                </div>

                <div class="form-group">
                    <label for="forma_de_pago_editable">Forma de Pago</label>
                    <input type="text" id="forma_de_pago_editable" name="forma_de_pago" readonly>
                </div>

                <div class="form-group">
                    <label for="numero_pedido_editable">Número de Pedido</label>
                    <input type="text" id="numero_pedido_editable" name="numero_pedido" readonly placeholder="Se asignará automáticamente" style="background-color: #f3f4f6; cursor: not-allowed;">
                </div>
            </div>
        </div>

        <!-- PASO 3: Prendas y Logo con TABS -->
        <div class="form-section">
            <div class="step-header">
                <div class="step-number-container">
                    <span class="step-number">3</span>
                </div>
                <h2 id="paso3_titulo" class="step-title text-center">Prendas y Logo (Editables)</h2>
            </div>

            <!-- TABS NAVIGATION -->
            <div class="tabs-container" id="tabs-pedido-container" style="display: none;">
                <!-- Tab Prendas -->
                <button type="button" class="tab-button active" onclick="cambiarTabPedido('prendas', event)">
                    <i class="fas fa-box"></i> PRENDAS
                </button>
                
                <!-- Tab Logo (se muestra solo si es combinada) -->
                <button type="button" class="tab-button" id="tab-logo-btn" onclick="cambiarTabPedido('logo', event)" style="display: none;">
                    <i class="fas fa-tools"></i> LOGO
                </button>
            </div>

            <!-- TABS CONTENT -->
            <div class="tab-content-wrapper" id="tab-content-wrapper">
                
                <!-- TAB: PRENDAS -->
                <div id="tab-prendas" class="tab-content active">
                    <div class="alert-info">
                        ℹ️ Edita los campos de cada prenda, cambia cantidades por talla, o elimina prendas que no desees incluir en el pedido.
                    </div>

                    <div id="prendas-container-editable">
                        <div class="empty-state">
                            <p>Selecciona una cotización para ver las prendas</p>
                        </div>
                    </div>
                </div>

                <!-- TAB: LOGO -->
                <div id="tab-logo" class="tab-content">
                    <div class="alert-info">
                        ℹ️ A continuación se muestra la información del logo de la cotización. Puedes editar los detalles si es necesario.
                    </div>

                    <!-- Contenedor para mostrar información cargada del logo -->
                    <div id="logo-tab-content" style="margin-bottom: 2rem;">
                        <div style="text-align: center; padding: 3rem; color: #999;">
                            <p>Cargando información del logo...</p>
                        </div>
                    </div>

                    <!-- Formulario para editar datos del logo (opcional) -->
                    <div id="logo-form-container" style="display: none; margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e0e0e0;">
                        <h3 style="margin-bottom: 1.5rem; color: #0066cc;">Editar Información del Logo</h3>
                        
                        <div class="form-group-editable">
                            <label for="logo_descripcion">Descripción del Logo</label>
                            <textarea id="logo_descripcion" name="logo_descripcion" placeholder="Describe el logo..." rows="4"></textarea>
                        </div>

                        <div class="form-group-inline" style="margin-top: 1.5rem;">
                            <div class="form-group-editable">
                                <label>Técnicas</label>
                                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal;">
                                        <input type="checkbox" name="logo_tecnicas" value="BORDADO"> Bordado
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal;">
                                        <input type="checkbox" name="logo_tecnicas" value="DTF"> DTF
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal;">
                                        <input type="checkbox" name="logo_tecnicas" value="ESTAMPADO"> Estampado
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal;">
                                        <input type="checkbox" name="logo_tecnicas" value="SUBLIMADO"> Sublimado
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group-editable" style="margin-top: 1.5rem;">
                            <label>Ubicaciones (JSON)</label>
                            <textarea id="logo_ubicaciones" name="logo_ubicaciones" placeholder="Ej: [{'ubicacion': 'CAMISA', 'opciones': ['PECHO', 'ESPALDA']}]" rows="3"></textarea>
                        </div>

                        <div class="form-group-editable" style="margin-top: 1.5rem;">
                            <label for="logo_observaciones">Observaciones Técnicas</label>
                            <textarea id="logo_observaciones" name="logo_observaciones" placeholder="Observaciones..." rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

                        <div class="form-group-editable" style="margin-top: 1.5rem;">
                            <label>Fotos del Logo</label>
                            <div id="logo-fotos-container" style="display: flex; flex-direction: column; gap: 1rem;">
                                <input type="file" name="logo_fotos[]" multiple accept="image/*" style="padding: 0.75rem; border: 1px solid #d0d0d0; border-radius: 4px;">
                                <div id="logo-fotos-preview" style="display: flex; gap: 1rem; flex-wrap: wrap;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PASO 4: Botones de Acción -->
        <div class="btn-actions">
            <button type="submit" class="btn btn-primary" id="btn-crear-pedido">
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
        // Pasar datos de PHP a JavaScript
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
                'prendasCount' => $cot->prendas->count(),
                'tipo_cotizacion_codigo' => $cot->tipo_cotizacion_codigo ?? '',
                'tieneLogoData' => ($cot->logo && ($cot->logo->descripcion || ($cot->logo->fotos && $cot->logo->fotos->count() > 0)))
            ];
        })->toArray()) !!};
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/crear-pedido-editable.js') }}?v={{ time() }}"></script>
@endpush
