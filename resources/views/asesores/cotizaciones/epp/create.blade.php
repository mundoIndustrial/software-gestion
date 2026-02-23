@extends('layouts.asesores')

@section('title', 'Cotización EPP')
@section('page-title', 'Cotizaciones')

@section('extra_styles')
<link rel="stylesheet" href="{{ asset('css/modulos/epp-modal.css') }}">
<style>
    .top-nav {
        display: none !important;
    }

    .form-section {
        background: #ffffff;
        border-radius: 10px;
        padding: 16px;
        border-left: 4px solid #1d4ed8;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        margin-bottom: 1rem;
    }

    .form-section h2 {
        margin: 0 0 1rem 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
    }

    .form-section h2 > span {
        width: 26px;
        height: 26px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 800;
        color: white;
        background: #1d4ed8;
    }

    .items-pedido-box {
        border-radius: 10px;
        padding: 12px;
        background: #ffffff;
    }

    .items-pedido-empty {
        border: 2px dashed #e5e7eb;
        border-radius: 10px;
        padding: 22px 16px;
        text-align: center;
        color: #6b7280;
        font-weight: 500;
        background: #fafafa;
    }

    .obs-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .obs-header label {
        font-weight: 700;
        color: #111827;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .btn-add {
        background: #1d4ed8;
        color: white;
        border: none;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1rem;
        font-weight: bold;
        transition: all 0.2s;
    }

    .btn-add:hover {
        background: #1e40af;
        transform: scale(1.1);
    }

    .observacion-item {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        align-items: center;
    }

    .observacion-item input {
        flex: 1;
        padding: 0.5rem;
        border: 2px solid #e5e7eb;
        border-radius: 6px;
        font-size: 0.9rem;
    }

    .observacion-item button {
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 0.4rem 0.6rem;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.2s;
    }

    .observacion-item button:hover {
        background: #dc2626;
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <script>
        window.__EPP_COTIZACION_MODE__ = true;
        window.__EPP_COTIZACION_EDIT__ = {{ isset($cotizacion) ? 'true' : 'false' }};
        window.__EPP_COTIZACION_ID__ = {{ isset($cotizacion) ? (int)$cotizacion->id : 'null' }};
        window.__EPP_COTIZACION_ITEMS__ = {!! json_encode($itemsUi ?? []) !!};
        window.__EPP_COTIZACION_TIPO_VENTA__ = {!! json_encode($eppCot->tipo_venta ?? ($cotizacion->tipo_venta ?? null)) !!};
        window.__EPP_COTIZACION_CLIENTE__ = {!! json_encode($cotizacion->cliente?->nombre ?? null) !!};
        window.__EPP_COTIZACION_IVA__ = {!! json_encode($iva ?? null) !!};
    </script>
    <div style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); border-radius: 12px; padding: 1.25rem 1.75rem; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 1.5rem; align-items: start;">
            <div style="display: flex; align-items: center; gap: 0.75rem; grid-column: 1 / -1;">
                <span class="material-symbols-rounded" style="font-size: 1.75rem; color: white;">engineering</span>
                <div>
                    <h2 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 700;">Cotización EPP</h2>
                    <p style="margin: 0.2rem 0 0 0; color: rgba(255,255,255,0.85); font-size: 0.8rem;">Completa los datos de la cotización</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; grid-column: 1 / -1;">
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Cliente</label>
                    <input type="text" id="header-cliente" placeholder="Nombre del cliente" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>

                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Asesor</label>
                    <input type="text" id="header-asesor" value="{{ auth()->user()->name }}" readonly style="width: 100%; background: rgba(255,255,255,0.9); border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; cursor: not-allowed;">
                </div>

                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Fecha</label>
                    <input type="date" id="header-fecha" value="{{ date('Y-m-d') }}" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>

                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Tipo para Cotizar</label>
                    <select id="header-tipo-venta" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s; cursor: pointer;">
                        <option value="">-- SELECCIONA --</option>
                        <option value="M">M</option>
                        <option value="D">D</option>
                        <option value="X">X</option>
                    </select>
                </div>
            </div>

            <!-- Campos adicionales del cliente -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; grid-column: 1 / -1;">
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">CC/NIT</label>
                    <input type="text" id="header-nit" placeholder="CC/NIT del cliente" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>

                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Dirección</label>
                    <input type="text" id="header-direccion" placeholder="Dirección del cliente" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>

                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Teléfono</label>
                    <input type="text" id="header-telefono" placeholder="Teléfono del cliente" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para agregar información adicional -->
    <div id="modalInformacionAdicional" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 2rem; width: 90%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
            <h3 style="margin: 0 0 1.5rem 0; color: #111827; font-size: 1.25rem; font-weight: 700;">Agregar Información Adicional</h3>
            
            <div style="margin-bottom: 1rem;">
                <label for="titulo-informacion" style="display: block; color: #111827; font-size: 0.75rem; font-weight: 800; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.4px;">Título</label>
                <input 
                    type="text" 
                    id="titulo-informacion" 
                    placeholder="Ej: Garantía, Devoluciones, etc."
                    style="width: 100%; background: white; border: 2px solid #e5e7eb; padding: 0.6rem 0.75rem; border-radius: 8px; font-weight: 600; color: #111827; font-size: 0.9rem; transition: all 0.2s;"
                >
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label for="contenido-informacion" style="display: block; color: #111827; font-size: 0.75rem; font-weight: 800; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.4px;">Contenido</label>
                <textarea 
                    id="contenido-informacion" 
                    placeholder="Ingrese la información adicional..."
                    rows="4"
                    style="width: 100%; background: white; border: 2px solid #e5e7eb; padding: 0.6rem 0.75rem; border-radius: 8px; font-weight: 600; color: #111827; font-size: 0.9rem; transition: all 0.2s; resize: vertical;"
                ></textarea>
            </div>
            
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="button" onclick="cerrarModalInformacionAdicional()" style="padding: 0.5rem 1.2rem; background: #6b7280; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.2s;">
                    Cancelar
                </button>
                <button type="button" onclick="guardarInformacionAdicional()" style="padding: 0.5rem 1.2rem; background: #1d4ed8; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.2s;">
                    Agregar
                </button>
            </div>
        </div>
    </div>

    <div class="form-container">
        <form id="cotizacionEppForm">
            @csrf

            <div class="form-section">
                <div class="items-pedido-box">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 1rem;">
                        <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                            <span>1</span>
                            Ítems del Pedido
                        </h2>

                        <button type="button" onclick="abrirModalAgregarEPP()" style="padding: 0.55rem 0.9rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); border: 2px solid #003d7a; border-radius: 8px; cursor: pointer; font-weight: 700; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; white-space: nowrap;" onmouseover="this.style.background='linear-gradient(135deg, #0052a3 0%, #003d7a 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 12px rgba(0, 102, 204, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0066cc 0%, #0052a3 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                            <span class="material-symbols-rounded" style="font-size: 18px;">add_circle</span>
                            Agregar
                        </button>
                    </div>

                    <div id="lista-items-pedido" style="display: flex; flex-direction: column; gap: 0.75rem;"></div>

                    <div id="prendas-container-editable" style="margin-top: 1.25rem;">
                        <div class="items-pedido-empty empty-state">
                            Agrega ítems al pedido
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 style="margin: 0 0 1rem 0;">
                    <span>2</span>
                    Totales de la cotización
                </h2>

                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; align-items: end;">
                    <div style="grid-column: span 1;">
                        <label style="display: block; color: #111827; font-size: 0.75rem; font-weight: 800; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.4px;">Subtotal</label>
                        <input
                            type="text"
                            id="subtotal-epp"
                            value="0"
                            readonly
                            style="width: 100%; background: #f9fafb; border: 2px solid #e5e7eb; padding: 0.6rem 0.75rem; border-radius: 8px; font-weight: 800; color: #111827; font-size: 0.95rem;"
                        >
                    </div>

                    <div style="grid-column: span 1;">
                        <label for="valor-iva-epp" style="display: block; color: #111827; font-size: 0.75rem; font-weight: 800; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.4px;">IVA</label>
                        <input
                            type="number"
                            id="valor-iva-epp"
                            min="0"
                            step="1"
                            placeholder="0"
                            style="width: 100%; background: white; border: 2px solid #e5e7eb; padding: 0.6rem 0.75rem; border-radius: 8px; font-weight: 800; color: #111827; font-size: 0.95rem; transition: all 0.2s;"
                        >
                    </div>

                    <div style="grid-column: span 1;">
                        <label style="display: block; color: #111827; font-size: 0.75rem; font-weight: 800; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.4px;">Total</label>
                        <input
                            type="text"
                            id="total-epp"
                            value="0"
                            readonly
                            style="width: 100%; background: #ecfeff; border: 2px solid #06b6d4; padding: 0.6rem 0.75rem; border-radius: 8px; font-weight: 900; color: #0e7490; font-size: 1rem;"
                        >
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="obs-header">
                    <label for="observaciones_generales">Observaciones Generales</label>
                </div>
                <textarea 
                    id="observaciones_generales" 
                    name="observaciones_generales" 
                    placeholder="Observaciones Generales"
                    rows="3"
                    style="width: 100%; background: white; border: 2px solid #e5e7eb; padding: 0.6rem 0.75rem; border-radius: 8px; font-weight: 600; color: #111827; font-size: 0.9rem; transition: all 0.2s; resize: vertical; margin-bottom: 1rem;"
                >OFERTA SUJETA A CAMBIOS Y DISPONIBILIDAD AL MOMENTO DE REALIZAR LA COMPRA

NO REALIZAMOS DESPACHO SIN PREVIO PAGO</textarea>
                <div id="observaciones-container">
                    <!-- Aquí se agregarán las observaciones dinámicamente -->
                </div>
            </div>

            <div class="form-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2 style="margin: 0;">
                        <span>3</span>
                        Información Adicional
                    </h2>
                    <button type="button" class="btn-add" onclick="agregarInformacionAdicional()">+</button>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <label for="condiciones_pago" style="display: block; color: #111827; font-size: 0.75rem; font-weight: 800; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.4px;">Condiciones de pago</label>
                        <input type="text" id="condiciones_pago" name="condiciones_pago" placeholder="Condiciones de pago" style="width: 100%; background: white; border: 2px solid #e5e7eb; padding: 0.6rem 0.75rem; border-radius: 8px; font-weight: 600; color: #111827; font-size: 0.9rem; transition: all 0.2s;">
                    </div>

                    <div>
                        <label for="tiempo_entrega" style="display: block; color: #111827; font-size: 0.75rem; font-weight: 800; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.4px;">Tiempo de entrega</label>
                        <input type="text" id="tiempo_entrega" name="tiempo_entrega" placeholder="Tiempo de entrega" style="width: 100%; background: white; border: 2px solid #e5e7eb; padding: 0.6rem 0.75rem; border-radius: 8px; font-weight: 600; color: #111827; font-size: 0.9rem; transition: all 0.2s;">
                    </div>
                </div>

                <div style="margin-top: 1rem;">
                    <label for="cuentas_autorizadas" style="display: block; color: #111827; font-size: 0.75rem; font-weight: 800; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.4px;">Cuentas Autorizadas</label>
                    <textarea 
                        id="cuentas_autorizadas" 
                        name="cuentas_autorizadas" 
                        placeholder="Cuentas Autorizadas"
                        rows="4"
                        style="width: 100%; background: white; border: 2px solid #e5e7eb; padding: 0.6rem 0.75rem; border-radius: 8px; font-weight: 600; color: #111827; font-size: 0.9rem; transition: all 0.2s; resize: vertical;"
                    >B. BOGOTA # 614027860 – CTA AHORROS
BANCOLOMBIA # 088-000575-67 – CTA AHORROS
A NOMBRE DE: LENIS RUTH MAHECHA ACOSTA 
NIT 1.093.738.433-3</textarea>
                </div>
                
                <div id="informacion-adicional-container" style="margin-top: 1rem;">
                    <!-- Aquí se agregarán los campos adicionales dinámicamente -->
                </div>
            </div>
            </div>

            <div class="form-actions">
                <div style="display: flex; gap: 0.5rem; flex: 1; justify-content: flex-end;">
                    <button id="btnGuardarBorradorEpp" type="button" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); border: 2px solid #3d8b40; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #45a049 0%, #3d8b40 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(76, 175, 80, 0.2)';" onmouseout="this.style.background='linear-gradient(135deg, #4CAF50 0%, #45a049 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="fas fa-save" style="font-size: 0.9rem;"></i> Guardar Borrador
                    </button>
                    <button id="btnEnviarCotizacionEpp" type="button" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); border: 2px solid #003d7a; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #0052a3 0%, #003d7a 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 12px rgba(0, 102, 204, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0066cc 0%, #0052a3 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="fas fa-paper-plane" style="font-size: 0.9rem;"></i> Enviar
                    </button>
                </div>
            </div>
        </form>
    </div>

    @include('asesores.cotizaciones.epp.components.modal-agregar-epp')
</div>
@endsection

@push('scripts')
<script defer src="{{ asset('js/utilidades/dom-utils.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/services/epp/EppHttpService.js') }}"></script>

<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-api-service.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-state-manager.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-modal-manager.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-item-manager.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-imagen-manager.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-service.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-notification-service.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-creation-service.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-form-manager.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-menu-handlers.js') }}"></script>

<script defer src="{{ asset('js/modulos/crear-pedido/epp/templates/epp-modal-template.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/interfaces/epp-modal-interface.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/epp-init.js') }}"></script>

<script>
    // Funciones globales para el modal
    function agregarInformacionAdicional() {
        document.getElementById('modalInformacionAdicional').style.display = 'flex';
        document.getElementById('titulo-informacion').value = '';
        document.getElementById('contenido-informacion').value = '';
        document.getElementById('titulo-informacion').focus();
    }

    function cerrarModalInformacionAdicional() {
        document.getElementById('modalInformacionAdicional').style.display = 'none';
    }

    function guardarInformacionAdicional() {
        const titulo = document.getElementById('titulo-informacion').value.trim();
        const contenido = document.getElementById('contenido-informacion').value.trim();
        
        if (!titulo || !contenido) {
            alert('Por favor complete ambos campos: título y contenido.');
            return;
        }
        
        const container = document.getElementById('informacion-adicional-container');
        const infoDiv = document.createElement('div');
        infoDiv.className = 'informacion-adicional-item';
        infoDiv.style.marginBottom = '1rem';
        infoDiv.style.padding = '1rem';
        infoDiv.style.background = '#f8fafc';
        infoDiv.style.border = '2px solid #e5e7eb';
        infoDiv.style.borderRadius = '8px';
        
        // Header con título y botón eliminar
        const headerDiv = document.createElement('div');
        headerDiv.style.display = 'flex';
        headerDiv.style.justifyContent = 'space-between';
        headerDiv.style.alignItems = 'center';
        headerDiv.style.marginBottom = '0.5rem';
        
        const tituloLabel = document.createElement('label');
        tituloLabel.style.display = 'block';
        tituloLabel.style.color = '#111827';
        tituloLabel.style.fontSize = '0.75rem';
        tituloLabel.style.fontWeight = '800';
        tituloLabel.style.marginBottom = '0.35rem';
        tituloLabel.style.textTransform = 'uppercase';
        tituloLabel.style.letterSpacing = '0.4px';
        tituloLabel.textContent = titulo;
        
        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.textContent = '×';
        btnEliminar.style.background = '#ef4444';
        btnEliminar.style.color = 'white';
        btnEliminar.style.border = 'none';
        btnEliminar.style.borderRadius = '4px';
        btnEliminar.style.padding = '0.25rem 0.5rem';
        btnEliminar.style.cursor = 'pointer';
        btnEliminar.style.fontSize = '1rem';
        btnEliminar.style.fontWeight = 'bold';
        btnEliminar.onclick = function() {
            infoDiv.remove();
        };
        
        headerDiv.appendChild(tituloLabel);
        headerDiv.appendChild(btnEliminar);
        
        // Campo oculto para título
        const inputTitulo = document.createElement('input');
        inputTitulo.type = 'hidden';
        inputTitulo.name = 'informacion_adicional_titulo[]';
        inputTitulo.value = titulo;
        
        // Campo oculto para contenido
        const inputContenido = document.createElement('input');
        inputContenido.type = 'hidden';
        inputContenido.name = 'informacion_adicional_contenido[]';
        inputContenido.value = contenido;
        
        // Contenido visible
        const contenidoDiv = document.createElement('div');
        contenidoDiv.style.color = '#111827';
        contenidoDiv.style.fontSize = '0.9rem';
        contenidoDiv.style.fontWeight = '500';
        contenidoDiv.style.whiteSpace = 'pre-wrap';
        contenidoDiv.textContent = contenido;
        
        infoDiv.appendChild(headerDiv);
        infoDiv.appendChild(inputTitulo);
        infoDiv.appendChild(inputContenido);
        infoDiv.appendChild(contenidoDiv);
        
        container.appendChild(infoDiv);
        
        // Cerrar modal
        cerrarModalInformacionAdicional();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const emptySelector = '#prendas-container-editable .empty-state';
        const listSelector = '#lista-items-pedido';

        const formatearNumero = (num) => {
            if (!Number.isFinite(num)) return '0';
            if (Number.isInteger(num)) return String(num);
            const s = num.toFixed(2);
            return s.replace(/\.00$/, '').replace(/(\.[0-9])0$/, '$1');
        };

        function calcularSubtotalEpp() {
            const itemsPedido = Array.isArray(window.itemsPedido) ? window.itemsPedido : [];
            let epps = itemsPedido.filter(i => (i?.tipo || '').toLowerCase() === 'epp');
            if (epps.length === 0 && itemsPedido.length > 0) {
                epps = itemsPedido;
            }

            let subtotal = 0;
            for (const it of epps) {
                const cantidad = Number(it?.cantidad || 0);
                const vu = (it?.valor_unitario !== undefined && it?.valor_unitario !== null && String(it.valor_unitario).trim() !== '' && !isNaN(Number(it.valor_unitario)))
                    ? Number(it.valor_unitario)
                    : null;
                const total = (it?.total !== undefined && it?.total !== null && String(it.total).trim() !== '' && !isNaN(Number(it.total)))
                    ? Number(it.total)
                    : null;

                if (total !== null) {
                    subtotal += total;
                } else if (vu !== null && cantidad > 0) {
                    subtotal += (vu * cantidad);
                }
            }

            return subtotal;
        }

        function syncTotales() {
            const subtotalEl = document.getElementById('subtotal-epp');
            const ivaEl = document.getElementById('valor-iva-epp');
            const totalEl = document.getElementById('total-epp');
            if (!subtotalEl || !ivaEl || !totalEl) return;

            const subtotal = calcularSubtotalEpp();
            const ivaRaw = ivaEl.value;
            const iva = (ivaRaw !== undefined && ivaRaw !== null && String(ivaRaw).trim() !== '' && !isNaN(Number(ivaRaw)))
                ? Number(ivaRaw)
                : 0;
            const total = subtotal + iva;

            subtotalEl.value = formatearNumero(subtotal);
            totalEl.value = formatearNumero(total);
        }

        try {
            const clienteEl = document.getElementById('header-cliente');
            const tipoVentaEl = document.getElementById('header-tipo-venta');
            const ivaEl = document.getElementById('valor-iva-epp');

            if (clienteEl && window.__EPP_COTIZACION_CLIENTE__) {
                clienteEl.value = window.__EPP_COTIZACION_CLIENTE__;
            }

            if (tipoVentaEl && window.__EPP_COTIZACION_TIPO_VENTA__) {
                tipoVentaEl.value = window.__EPP_COTIZACION_TIPO_VENTA__;
            }

            if (ivaEl && window.__EPP_COTIZACION_IVA__ !== null && window.__EPP_COTIZACION_IVA__ !== undefined) {
                ivaEl.value = window.__EPP_COTIZACION_IVA__;
            }
        } catch (e) {
            // noop
        }

        try {
            const items = Array.isArray(window.__EPP_COTIZACION_ITEMS__) ? window.__EPP_COTIZACION_ITEMS__ : [];
            if (!window.itemsPedido) window.itemsPedido = [];

            if (items.length > 0) {
                window.itemsPedido = items;
                if (window.eppItemManager && typeof window.eppItemManager.crearItem === 'function') {
                    const lista = document.getElementById('lista-items-pedido');
                    if (lista) lista.innerHTML = '';

                    items.forEach((it) => {
                        window.eppItemManager.crearItem(
                            it.id,
                            it.nombre_epp || it.nombre || 'Sin nombre',
                            it.categoria || null,
                            it.cantidad || 1,
                            it.observaciones || null,
                            it.imagenes || [],
                            it.id,
                            it.valor_unitario ?? null,
                            it.total ?? null
                        );
                    });
                }
            }
        } catch (e) {
            // noop
        }

        async function enviarCotizacionEpp(accion) {
            const btnEnviar = document.getElementById('btnEnviarCotizacionEpp');
            const btnBorrador = document.getElementById('btnGuardarBorradorEpp');
            const cliente = document.getElementById('header-cliente')?.value?.trim();
            const tipoVenta = document.getElementById('header-tipo-venta')?.value?.trim();
            const ivaRaw = document.getElementById('valor-iva-epp')?.value;
            
            // Capturar campos adicionales del cliente
            const clienteNit = document.getElementById('header-nit')?.value?.trim() || '';
            const clienteDireccion = document.getElementById('header-direccion')?.value?.trim() || '';
            const clienteTelefono = document.getElementById('header-telefono')?.value?.trim() || '';

            async function convertirImagenAFile(img, fallbackName = 'epp_imagen.webp') {
                try {
                    if (!img) return null;

                    if (img instanceof File) {
                        return img;
                    }

                    const src = (typeof img === 'string')
                        ? img
                        : (img?.previewUrl || img?.base64 || img?.url || img?.ruta_web || img?.ruta_webp || img?.ruta_original || null);

                    if (!src || typeof src !== 'string') return null;

                    // DataURL
                    if (src.startsWith('data:')) {
                        const res = await fetch(src);
                        const blob = await res.blob();
                        return new File([blob], fallbackName, { type: blob.type || 'image/webp' });
                    }

                    // Blob URL o URL normal (misma origen o accesible)
                    if (src.startsWith('blob:') || src.startsWith('http') || src.startsWith('/')) {
                        const res = await fetch(src);
                        const blob = await res.blob();
                        const name = (img?.nombre_archivo || img?.name || fallbackName);
                        return new File([blob], name, { type: blob.type || 'image/webp' });
                    }

                    return null;
                } catch (e) {
                    return null;
                }
            }

            const itemsPedido = Array.isArray(window.itemsPedido) ? window.itemsPedido : [];
            let epps = itemsPedido.filter(i => (i?.tipo || '').toLowerCase() === 'epp');
            if (epps.length === 0 && itemsPedido.length > 0) {
                epps = itemsPedido;
            }

            if (!cliente) {
                if (window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'Cliente requerido', text: 'Por favor ingresa el nombre del cliente' });
                }
                return;
            }

            if (!tipoVenta) {
                if (window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'Tipo requerido', text: 'Por favor selecciona el tipo para cotizar (M/D/X)' });
                }
                return;
            }

            if (epps.length === 0) {
                if (window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'Sin ítems', text: 'Agrega al menos un EPP a la cotización' });
                }
                return;
            }

            const formData = new FormData();
            formData.append('_token', document.querySelector('input[name="_token"]')?.value || '');
            formData.append('accion', accion);
            formData.append('cliente', cliente);
            formData.append('cliente_nit', clienteNit);
            formData.append('cliente_direccion', clienteDireccion);
            formData.append('cliente_telefono', clienteTelefono);
            formData.append('tipo_venta', tipoVenta);

            if (window.__EPP_COTIZACION_EDIT__ && window.__EPP_COTIZACION_ID__) {
                formData.append('cotizacion_id', String(window.__EPP_COTIZACION_ID__));
            }

            const ivaValor = (ivaRaw !== undefined && ivaRaw !== null && String(ivaRaw).trim() !== '' && !isNaN(Number(ivaRaw)))
                ? Number(ivaRaw)
                : null;
            
            // Capturar observaciones generales del textarea
            const observacionesGeneralesTexto = document.getElementById('observaciones_generales')?.value?.trim() || '';
            
            // Capturar campos adicionales
            const condicionesPago = document.getElementById('condiciones_pago')?.value?.trim() || '';
            const tiempoEntrega = document.getElementById('tiempo_entrega')?.value?.trim() || '';
            const cuentasAutorizadas = document.getElementById('cuentas_autorizadas')?.value?.trim() || '';
            
            // Capturar información adicional dinámica
            const titulosAdicionales = [];
            const contenidosAdicionales = [];
            const elementosAdicionales = document.querySelectorAll('#informacion-adicional-container .informacion-adicional-item');
            
            elementosAdicionales.forEach(elemento => {
                const tituloElement = elemento.querySelector('input[name="informacion_adicional_titulo[]"]');
                const contenidoElement = elemento.querySelector('input[name="informacion_adicional_contenido[]"]');
                
                if (tituloElement && contenidoElement) {
                    const titulo = tituloElement.value?.trim() || '';
                    const contenido = contenidoElement.value?.trim() || '';
                    
                    if (titulo && contenido) {
                        titulosAdicionales.push(titulo);
                        contenidosAdicionales.push(contenido);
                    }
                }
            });
            
            // Crear objeto de observaciones generales con IVA
            const observacionesGenerales = {};
            if (ivaValor !== null) {
                observacionesGenerales.valor_iva = ivaValor;
            }
            
            formData.append('observaciones_generales', JSON.stringify(observacionesGenerales));
            
            // Agregar campos adicionales al formData
            if (condicionesPago) {
                formData.append('condiciones_pago', condicionesPago);
            }
            if (tiempoEntrega) {
                formData.append('tiempo_entrega', tiempoEntrega);
            }
            if (cuentasAutorizadas) {
                formData.append('cuentas_autorizadas', cuentasAutorizadas);
            }
            
            // Agregar información adicional
            if (titulosAdicionales.length > 0) {
                titulosAdicionales.forEach((titulo, index) => {
                    formData.append(`informacion_adicional_titulo[${index}]`, titulo);
                });
            }
            if (contenidosAdicionales.length > 0) {
                contenidosAdicionales.forEach((contenido, index) => {
                    formData.append(`informacion_adicional_contenido[${index}]`, contenido);
                });
            }
            
            // Agregar observaciones generales del textarea como campo separado
            formData.append('observaciones_generales_texto', observacionesGeneralesTexto);

            // Items (sin archivos, esos van aparte)
            const itemsPayload = epps.map((epp) => ({
                imagenes_keep: (() => {
                    const imgs = Array.isArray(epp.imagenes) ? epp.imagenes : [];
                    const keep = [];
                    for (const im of imgs) {
                        const src = (typeof im === 'string')
                            ? im
                            : (im?.previewUrl || im?.url || im?.ruta_web || im?.ruta_webp || im?.ruta_original || null);
                        if (!src || typeof src !== 'string') continue;
                        // Convertir URL pública /storage/... a ruta relativa en disk('public')
                        const idx = src.indexOf('/storage/');
                        if (idx !== -1) {
                            const rel = src.substring(idx + '/storage/'.length);
                            if (rel) keep.push(rel);
                        }
                    }
                    return keep;
                })(),
                clear_imagenes: !(Array.isArray(epp.imagenes) && epp.imagenes.length > 0),
                id: epp.id || epp.pedidoEppId || null,
                nombre: epp.nombre_epp || epp.nombre_completo || epp.nombre || 'Sin nombre',
                cantidad: epp.cantidad || 1,
                valor_unitario: (epp.valor_unitario !== undefined && epp.valor_unitario !== null && String(epp.valor_unitario).trim() !== '')
                    ? Number(epp.valor_unitario)
                    : null,
                total: (epp.total !== undefined && epp.total !== null && String(epp.total).trim() !== '')
                    ? Number(epp.total)
                    : null,
                observaciones: epp.observaciones || null,
            }));
            formData.append('items', JSON.stringify(itemsPayload));

            // Archivos: items[i][imagenes][]
            for (let idx = 0; idx < epps.length; idx++) {
                const epp = epps[idx];
                const imagenes = Array.isArray(epp.imagenes) ? epp.imagenes : [];
                for (let j = 0; j < imagenes.length; j++) {
                    const file = await convertirImagenAFile(imagenes[j], `epp_${idx + 1}_${j + 1}.webp`);
                    if (file) {
                        formData.append(`items[${idx}][imagenes][]`, file, file.name);
                    }
                }
            }

            const url = `{{ url('/asesores/cotizaciones-epp') }}`;
            try {
                if (btnEnviar) btnEnviar.disabled = true;
                if (btnBorrador) btnBorrador.disabled = true;

                const res = await fetch(url, {
                    method: 'POST',
                    body: formData,
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    const msg = data.message || 'Error guardando cotización EPP';
                    if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    }
                    return;
                }

                const cotizacionId = data.cotizacionId;
                const redirectUrl = data.redirect || `{{ url('/asesores/cotizaciones') }}?tab=${accion === 'borrador' ? 'borradores' : 'cotizaciones'}&highlight=${cotizacionId}`;

                if (window.Swal) {
                    const params = new URLSearchParams(window.location.search);
                    const esEdicionCotizacionCreada = params.get('editar_cotizacion') === '1';

                    const numero = data?.numero_cotizacion || data?.numeroCotizacion || cotizacionId;

                    const title = accion === 'borrador'
                        ? 'Borrador guardado'
                        : (esEdicionCotizacionCreada ? 'Cotización actualizada' : 'Cotización enviada');
                    const text = data.message || (accion === 'borrador'
                        ? 'La cotización EPP fue guardada como borrador'
                        : (esEdicionCotizacionCreada
                            ? `Cotización número ${numero} actualizada correctamente`
                            : 'La cotización EPP fue enviada correctamente'));

                    const result = await Swal.fire({
                        icon: 'success',
                        title,
                        text,
                        confirmButtonText: 'OK',
                    });

                    if (result.isConfirmed) {
                        window.location.href = redirectUrl;
                    }
                } else {
                    if (confirm(data.message || 'Proceso exitoso')) {
                        window.location.href = redirectUrl;
                    }
                }
            } finally {
                if (btnEnviar) btnEnviar.disabled = false;
                if (btnBorrador) btnBorrador.disabled = false;
            }
        }

        function syncEmptyState() {
            const list = document.querySelector(listSelector);
            const empty = document.querySelector(emptySelector);
            if (!list || !empty) return;

            const hasItems = list.querySelectorAll('.item-epp, .item-epp-card').length > 0;
            empty.style.display = hasItems ? 'none' : 'flex';
        }

        function agregarObservacion() {
            const container = document.getElementById('observaciones-container');
            const observacionDiv = document.createElement('div');
            observacionDiv.className = 'observacion-item';
            
            const input = document.createElement('input');
            input.type = 'text';
            input.placeholder = 'Ingrese una observación...';
            input.name = 'observaciones_generales[]';
            
            const btnEliminar = document.createElement('button');
            btnEliminar.type = 'button';
            btnEliminar.textContent = '×';
            btnEliminar.onclick = function() {
                observacionDiv.remove();
            };
            
            observacionDiv.appendChild(input);
            observacionDiv.appendChild(btnEliminar);
            container.appendChild(observacionDiv);
            
            // Enfocar el nuevo input
            input.focus();
        }

        // Actualizar totales cuando cambie el IVA
        try {
            const ivaEl = document.getElementById('valor-iva-epp');
            if (ivaEl) {
                ivaEl.addEventListener('input', syncTotales);
            }
        } catch (e) {
            // noop
        }

        if (typeof window.finalizarAgregarEPP === 'function' && !window.__eppCotizacionFinalizarWrapped) {
            const original = window.finalizarAgregarEPP;
            window.finalizarAgregarEPP = async function (...args) {
                const result = await original.apply(this, args);
                syncEmptyState();
                syncTotales();
                return result;
            };
            window.__eppCotizacionFinalizarWrapped = true;
        }

        if (typeof window.guardarEdicionEPP === 'function' && !window.__eppCotizacionGuardarEdicionWrapped) {
            const original = window.guardarEdicionEPP;
            window.guardarEdicionEPP = function (...args) {
                const result = original.apply(this, args);
                syncEmptyState();
                syncTotales();
                return result;
            };
            window.__eppCotizacionGuardarEdicionWrapped = true;
        }

        const list = document.querySelector(listSelector);
        if (list && typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(syncEmptyState);
            observer.observe(list, { childList: true, subtree: true, characterData: true });
        }

        const listTot = document.querySelector(listSelector);
        if (listTot && typeof MutationObserver !== 'undefined') {
            const observerTot = new MutationObserver(syncTotales);
            observerTot.observe(listTot, { childList: true, subtree: true, characterData: true });
        }

        const btnEnviar = document.getElementById('btnEnviarCotizacionEpp');
        if (btnEnviar) {
            btnEnviar.addEventListener('click', function () {
                enviarCotizacionEpp('enviar');
            });
        }

        const btnBorrador = document.getElementById('btnGuardarBorradorEpp');
        if (btnBorrador) {
            btnBorrador.addEventListener('click', function () {
                enviarCotizacionEpp('borrador');
            });
        }

        // Ajustes UI para edición de cotización ya creada (NO borrador)
        try {
            const params = new URLSearchParams(window.location.search);
            const esEdicionCotizacionCreada = params.get('editar_cotizacion') === '1';
            if (esEdicionCotizacionCreada) {
                const btnBorradorUi = document.getElementById('btnGuardarBorradorEpp');
                if (btnBorradorUi) {
                    btnBorradorUi.style.display = 'none';
                }

                const btnEnviarUi = document.getElementById('btnEnviarCotizacionEpp');
                if (btnEnviarUi) {
                    btnEnviarUi.innerHTML = '<i class="fas fa-save" style="font-size: 0.9rem;"></i> Guardar cambios';
                }
            }
        } catch (e) {
            // noop
        }

        syncEmptyState();
        syncTotales();
    });
</script>
@endpush
