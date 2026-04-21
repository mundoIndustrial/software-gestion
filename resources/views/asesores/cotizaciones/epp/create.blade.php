@extends('layouts.asesores')

@section('title', 'Cotización Para Cliente')
@section('page-title', 'Cotizaciones')

@section('extra_styles')
<link rel="stylesheet" href="{{ asset('css/modulos/epp-modal.css') }}">
<link rel="stylesheet" href="{{ asset('css/modulos/cotizacion-epp-create.css') }}">
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
        window.__EPP_COTIZACION_CONDICIONES_PAGO__ = {!! json_encode($condicionesPago ?? '') !!};
        window.__EPP_COTIZACION_TIEMPO_ENTREGA__ = {!! json_encode($tiempoEntrega ?? '') !!};
        window.__EPP_COTIZACION_CUENTAS_AUTORIZADAS__ = {!! json_encode($cuentasAutorizadas ?? '') !!};
        window.__EPP_COTIZACION_CLIENTE_NIT__ = {!! json_encode($cotizacion->cliente_nit ?? '') !!};
        window.__EPP_COTIZACION_CLIENTE_DIRECCION__ = {!! json_encode($cotizacion->cliente_direccion ?? '') !!};
        window.__EPP_COTIZACION_CLIENTE_TELEFONO__ = {!! json_encode($cotizacion->cliente_telefono ?? '') !!};
        window.__EPP_COTIZACION_ENDPOINTS__ = {
            guardar: @json(url('/api/asesores/cotizaciones-epp')),
            index: @json(url('/asesores/cotizaciones')),
        };
    </script>
    <div style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); border-radius: 12px; padding: 1.25rem 1.75rem; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 1.5rem; align-items: start;">
            <div style="display: flex; align-items: center; gap: 0.75rem; grid-column: 1 / -1;">
                <span class="material-symbols-rounded" style="font-size: 1.75rem; color: white;">engineering</span>
                <div>
                    <h2 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 700;">Cotización Cliente</h2>
                    <p style="margin: 0.2rem 0 0 0; color: rgba(255,255,255,0.85); font-size: 0.8rem;">Completa los datos de la cotización</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; grid-column: 1 / -1;">
                <div>
                    <label for="header-cliente" style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Cotización Cliente</label>
                    <input type="text" id="header-cliente" placeholder="Nombre del cliente" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>

                <div>
                    <label for="header-asesor" style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Asesor</label>
                    <input type="text" id="header-asesor" value="{{ auth()->user()->name }}" readonly style="width: 100%; background: rgba(255,255,255,0.9); border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; cursor: not-allowed;">
                </div>

                <div>
                    <label for="header-fecha" style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Fecha</label>
                    <input type="date" id="header-fecha" value="{{ date('Y-m-d') }}" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>

                <div>
                    <label for="header-tipo-venta" style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Tipo para Cotizar</label>
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
                    <label for="header-nit" style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">CC/NIT</label>
                    <input type="text" id="header-nit" placeholder="CC/NIT del cliente" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>

                <div>
                    <label for="header-direccion" style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Dirección</label>
                    <input type="text" id="header-direccion" placeholder="Dirección del cliente" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>

                <div>
                    <label for="header-telefono" style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Teléfono</label>
                    <input type="tel" id="header-telefono" placeholder="Teléfono del cliente" maxlength="15" pattern="[0-9]+" inputmode="numeric" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;" onkeypress="return event.charCode >= 48 && event.charCode <= 57" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
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
                            Ítems y Totales del Pedido
                        </h2>

                        <button type="button" onclick="abrirModalSeleccion()" style="padding: 0.55rem 0.9rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); border: 2px solid #003d7a; border-radius: 8px; cursor: pointer; font-weight: 700; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; white-space: nowrap;" onmouseover="this.style.background='linear-gradient(135deg, #0052a3 0%, #003d7a 100%)'; this.style.transform='translateY(-1px)'; this.boxShadow='0 6px 12px rgba(0, 102, 204, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0066cc 0%, #0052a3 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                            <span class="material-symbols-rounded" style="font-size: 18px;">add_circle</span>
                            Agregar
                        </button>
                    </div>

                    <!-- Tabla unificada de items y totales -->
                    <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                        <thead>
                            <tr style="background: #f8fafc; color: #1f2937;">
                                <th style="padding: 12px 16px; text-align: left; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb;">ÍTEM</th>
                                <th style="padding: 12px 16px; text-align: center; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb;">IMAGEN</th>
                                <th style="padding: 12px 16px; text-align: left; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb;">DESCRIPCIÓN</th>
                                <th style="padding: 12px 16px; text-align: center; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb;">CANTIDAD</th>
                                <th style="padding: 12px 16px; text-align: left; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb;">OBSERVACIONES</th>
                                <th style="padding: 12px 16px; text-align: center; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb;">V. UNITARIO</th>
                                <th style="padding: 12px 16px; text-align: center; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb;">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-items-pedido">
                            <!-- Los items se cargarán aquí dinámicamente -->
                        </tbody>
                        <tfoot>
                            <tr style="background: #f1f5f9;">
                                <td colspan="3" style="padding: 8px; text-align: left; font-size: 10px; font-weight: 600; color: #64748b; border-top: 1px solid #e5e7eb;">
                                    <strong>Subtotal</strong>
                                </td>
                                <td colspan="4" style="padding: 8px; text-align: right; font-size: 10px; font-weight: 600; color: #64748b; border-top: 1px solid #e5e7eb;">
                                    <input type="text" id="subtotal-epp" value="0" readonly style="width: 150px; background: #f9fafb; border: 1px solid #e5e7eb; padding: 6px 8px; border-radius: 4px; font-weight: 700; color: #111827; font-size: 11px; text-align: right;">
                                </td>
                            </tr>
                            <tr style="background: #f1f5f9;">
                                <td colspan="3" style="padding: 8px; text-align: left; font-size: 10px; font-weight: 600; color: #64748b; border-top: 1px solid #e5e7eb;">
                                    <strong>IVA</strong>
                                </td>
                                <td colspan="4" style="padding: 8px; text-align: right; font-size: 10px; font-weight: 600; color: #64748b; border-top: 1px solid #e5e7eb;">
                                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <label for="valor-iva-epp" style="font-size: 9px; color: #64748b; white-space: nowrap;">%</label>
                                            <input type="number" id="valor-iva-epp" min="0" step="1" value="19" placeholder="19" style="width: 80px; background: white; border: 1px solid #e5e7eb; padding: 6px 8px; border-radius: 4px; font-weight: 700; color: #111827; font-size: 11px; text-align: center;">
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <label for="valor-iva-calculado" style="font-size: 9px; color: #64748b; white-space: nowrap;">Valor</label>
                                            <input type="text" id="valor-iva-calculado" value="0" readonly style="width: 100px; background: #f8fafc; border: 1px solid #e5e7eb; padding: 6px 8px; border-radius: 4px; font-weight: 700; color: #111827; font-size: 11px; text-align: right;">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr style="background: #0c8cc7ff;">
                                <td colspan="3" style="padding: 8px; text-align: left; font-size: 10px; font-weight: 700; color: #ffffff; border-top: 1px solid #e5e7eb;">
                                    <strong>Total</strong>
                                </td>
                                <td colspan="4" style="padding: 8px; text-align: right; font-size: 10px; font-weight: 700; color: #ffffff; border-top: 1px solid #e5e7eb;">
                                    <input type="text" id="total-epp" value="0" readonly style="width: 150px; background: #0284c7; border: 2px solid #0369a1; padding: 6px 8px; border-radius: 4px; font-weight: 900; color: #ffffff; font-size: 12px; text-align: right;">
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                    <div id="prendas-container-editable" style="margin-top: 1.25rem;">
                        <div class="items-pedido-empty empty-state" style="display: none;">
                            Agrega ítems al pedido
                        </div>
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
                    @if(!isset($cotizacion) || !$cotizacion)
                    <button id="btnGuardarBorradorEpp" type="button" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); border: 2px solid #3d8b40; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #45a049 0%, #3d8b40 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(76, 175, 80, 0.2)';" onmouseout="this.style.background='linear-gradient(135deg, #4CAF50 0%, #45a049 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';" onfocus="this.style.background='linear-gradient(135deg, #45a049 0%, #3d8b40 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(76, 175, 80, 0.2)';" onblur="this.style.background='linear-gradient(135deg, #4CAF50 0%, #45a049 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="fas fa-save" style="font-size: 0.9rem;"></i> Guardar Borrador
                    </button>
                    @endif
                    <button id="btnEnviarCotizacionEpp" type="button" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); border: 2px solid #003d7a; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #0052a3 0%, #003d7a 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 12px rgba(0, 102, 204, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0066cc 0%, #0052a3 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';" onfocus="this.style.background='linear-gradient(135deg, #0052a3 0%, #003d7a 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 12px rgba(0, 102, 204, 0.3)';" onblur="this.style.background='linear-gradient(135deg, #0066cc 0%, #0052a3 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="fas fa-paper-plane" style="font-size: 0.9rem;"></i> {{ (isset($cotizacion) && $cotizacion) ? 'Guardar cambios' : 'Crear' }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    @include('asesores.cotizaciones.epp.components.modal-agregar-epp')
</div>
@endsection

@push('scripts')
@php $v = config('app.asset_version'); @endphp
<script defer src="{{ js_asset('js/utilidades/dom-utils.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/services/epp/EppHttpService.js') }}?v={{ $v }}"></script>

<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/epp-store.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-api-service.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-state-manager.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-modal-manager.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-item-manager-tabla.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/pedido-items-state.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/gestion-items-pedido-core-services.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/prenda-modal-service.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/prenda-flow-service.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/epp-flow-service.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/items-sync-service.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/item-removal-service.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/gestion-items-pedido.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-modal-edicion-adapter.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-imagen-manager.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-service.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-notification-service.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-creation-service.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-form-manager.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-menu-handler-base.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-menu-handlers-tabla.js') }}?v={{ $v }}"></script>

<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/templates/epp-modal-template.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/interfaces/epp-modal-interface.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/epp/epp-init.js') }}?v={{ $v }}"></script>

<!-- Wrapper para abrirModalEditarEPP en contexto de cotizaciones -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Envolver la función abrirModalEditarEPP para trabajar con cotizaciones
    const originalAbrirModalEditarEPP = window.abrirModalEditarEPP;
    
    window.abrirModalEditarEPP = function(eppData) {
        console.log('[Cotizacion] abrirModalEditarEPP interceptado con EPP:', eppData);
        
        // Si estamos en modo cotización, buscar en __EPP_COTIZACION_ITEMS__
        if (window.__EPP_COTIZACION_MODE__) {
            const epp_id = eppData?.id || eppData?.epp_id || eppData?.tarjetaId;
            console.log('[Cotizacion] Buscando EPP en modo cotización, ID:', epp_id);
            
            // Usar window.itemsPedido (sincronizado con eppStore) como fuente principal, fallback a __EPP_COTIZACION_ITEMS__
            const items = Array.isArray(window.itemsPedido) ? window.itemsPedido : (Array.isArray(window.__EPP_COTIZACION_ITEMS__) ? window.__EPP_COTIZACION_ITEMS__ : []);
            const eppEncontrado = items.find(item => {
                const itemId = item.epp_id || item.id || item.pedidoEppId || item.tarjetaId;
                return String(itemId) === String(epp_id);
            });
            
            if (eppEncontrado) {
                console.log('[Cotizacion] EPP encontrado en __EPP_COTIZACION_ITEMS__:', eppEncontrado);
                
                // Cargar el EPP encontrado en las variables globales que usa el modal
                window.eppEnEdicionIndividual = {
                    ...eppEncontrado,
                    id: epp_id,
                    epp_id: epp_id,
                    pedido_epp_id: eppData?.pedido_epp_id || epp_id,
                    tarjetaId: eppData?.tarjetaId || epp_id,
                    nombre: eppEncontrado.nombre || eppEncontrado.nombre_epp || '',
                    nombre_epp: eppEncontrado.nombre_epp || eppEncontrado.nombre || '',
                    nombre_completo: eppEncontrado.nombre_completo || eppEncontrado.nombre_epp || eppEncontrado.nombre || '',
                    tipo: eppEncontrado.tipo || (eppData?.esPrenda ? 'prenda' : 'epp')
                };
                
                const esPrenda = String(window.eppEnEdicionIndividual.tipo || '').toLowerCase().includes('prenda');
                console.log('[Cotizacion] Tipo de item detectado:', window.eppEnEdicionIndividual.tipo, '¿Es prenda?:', esPrenda);

                // Personalizar textos del modal según el tipo
                const tituloModal = document.getElementById('modalEditarEPPTitulo');
                const labelBuscador = document.querySelector('label[for="modalEditarEPPBuscador"]');
                const labelNombreSel = document.getElementById('modalEditarEPPNombreSeleccionado');
                const labelFotos = document.querySelector('label[for="modalEditarEPPInputFotos"]');

                if (tituloModal) tituloModal.textContent = esPrenda ? 'Editar Prenda' : 'Editar EPP';
                if (labelBuscador) labelBuscador.textContent = esPrenda ? 'Nombre de la Prenda' : 'Nombre del EPP';
                if (labelNombreSel) {
                    labelNombreSel.innerHTML = `${esPrenda ? 'Prenda' : 'EPP'}: <span id="modalEditarEPPNombre" class="font-semibold text-gray-900">${window.eppEnEdicionIndividual.nombre_completo || window.eppEnEdicionIndividual.nombre}</span>`;
                }
                if (labelFotos) labelFotos.textContent = esPrenda ? 'Fotos de la Prenda' : 'Fotos del EPP';
                
                // Cargar imágenes
                window.fotosEnEdicionIndividual = (eppEncontrado.imagenes || []).filter(img => img);
                window.imagenesEditadasEnModalEPP = false;
                window.tarjetaEppIdEnEdicion = eppData?.tarjetaId || epp_id;
                window.indiceEPPEnEdicion = items.findIndex(item => {
                    const itemId = item.epp_id || item.id;
                    return String(itemId) === String(epp_id);
                });
                
                // Rellenar campos del modal
                const campos = {
                    'modalEditarEPPBuscador': window.eppEnEdicionIndividual.nombre_completo || window.eppEnEdicionIndividual.nombre,
                    'modalEditarEPPNombre': window.eppEnEdicionIndividual.nombre_completo || window.eppEnEdicionIndividual.nombre,
                    'modalEditarEPPCantidad': eppEncontrado.cantidad || 1,
                    'modalEditarEPPObservaciones': eppEncontrado.observaciones || '-',
                    'modalEditarEPPValorUnitario': eppEncontrado.valor_unitario || ''
                };
                
                // Mostrar contenedor de valor unitario si estamos en modo cotización
                const contenedorVU = document.getElementById('contenedorModalEditarEPPValorUnitario');
                if (contenedorVU) {
                    contenedorVU.style.display = 'grid';
                    console.log('[Cotizacion] Campo Valor Unitario activado en modal');
                }
                
                for (const [elementId, valor] of Object.entries(campos)) {
                    const el = document.getElementById(elementId);
                    if (el) {
                        if (elementId.endsWith('Nombre')) {
                            el.textContent = valor;
                        } else {
                            el.value = valor;
                        }
                    }
                }
                
                // Calcular total inicial si existe la función
                if (typeof window.calcularTotalEstimadoModalEditar === 'function') {
                    window.calcularTotalEstimadoModalEditar();
                }
                
                // IMPORTANTE: Cargar imágenes en la variable del modal
                // Las imágenes guardadas pueden ser Data URLs o blob URLs
                let imagenesParaCargar = [];
                if (eppEncontrado.imagenes && Array.isArray(eppEncontrado.imagenes) && eppEncontrado.imagenes.length > 0) {
                    // Normalizar: pueden venir como objetos o como strings
                    imagenesParaCargar = eppEncontrado.imagenes.map(img => {
                        if (typeof img === 'string') return { url: img, previewUrl: img, nombre: 'Imagen' };
                        return img;
                    }).filter(img => img && (img.url || img.previewUrl || img.base64));
                } 
                
                // Si no se encontraron imágenes en el array, buscar en propiedades individuales
                if (imagenesParaCargar.length === 0) {
                    const posibleUrl = eppEncontrado.imagen_url || eppEncontrado.imagen || eppEncontrado.foto || eppEncontrado.foto_url || eppEncontrado.url_imagen;
                    if (posibleUrl) {
                        console.log('[Cotizacion] Imagen encontrada en propiedad individual:', posibleUrl);
                        imagenesParaCargar = [{
                            url: posibleUrl,
                            previewUrl: posibleUrl,
                            nombre: 'Imagen Principal'
                        }];
                    }
                }

                if (imagenesParaCargar.length > 0) {
                    // Usar la función del modal para cargar imágenes
                    if (typeof window.cargarImagenesEnModalEditar === 'function') {
                        console.log('[Cotizacion] Cargando', imagenesParaCargar.length, 'imágenes al modal');
                        window.cargarImagenesEnModalEditar(imagenesParaCargar);
                    } else {
                        console.warn('[Cotizacion] Función cargarImagenesEnModalEditar no disponible');
                    }
                } else {
                    console.log('[Cotizacion] No hay imágenes para cargar');
                    // Limpiar galería si no hay fotos
                    if (typeof window.mostrarFotosEnModalEditar === 'function') {
                        window.fotosEnEdicionIndividual = [];
                        window.mostrarFotosEnModalEditar();
                    }
                }
                
                // Mostrar imágenes si existe la función
                if (typeof window.mostrarFotosEnModalEditar === 'function' && !eppEncontrado.imagenes) {
                    window.mostrarFotosEnModalEditar();
                }
                
                // Abrir el modal
                const modal = document.getElementById('modalEditarEPP');
                if (modal) {
                    modal.classList.remove('hidden');
                    console.log('[Cotizacion] Modal de edición abierto');
                }
                
                return;
            } else {
                console.warn('[Cotizacion] EPP no encontrado en __EPP_COTIZACION_ITEMS__');
            }
        }
        
        // Fallback: intentar con la función original si existe
        if (typeof originalAbrirModalEditarEPP === 'function') {
            console.log('[Cotizacion] Fallback a originalAbrirModalEditarEPP');
            originalAbrirModalEditarEPP(eppData);
        } else {
            console.warn('[Cotizacion] No se pudo abrir el modal de edición');
        }
    };
    
    // Envolver guardarEdicionEnModalEditarEPP para cotizaciones
    const originalGuardarEdicionEnModalEditarEPP = window.guardarEdicionEnModalEditarEPP;
    
    window.guardarEdicionEnModalEditarEPP = async function() {
        console.log('[Cotizacion] guardarEdicionEnModalEditarEPP interceptado');
        
        // Si estamos en modo cotización
        if (window.__EPP_COTIZACION_MODE__) {
            if (!window.eppEnEdicionIndividual) {
                console.error('[Cotizacion] No hay EPP en edicion');
                return;
            }
            
            const cantidad = parseInt(document.getElementById('modalEditarEPPCantidad').value) || 1;
            const observaciones = document.getElementById('modalEditarEPPObservaciones').value || '-';
            const valorUnitario = parseFloat(document.getElementById('modalEditarEPPValorUnitario')?.value) || 0;
            const totalItem = cantidad * valorUnitario;
            const epp_id = window.eppEnEdicionIndividual.epp_id || window.eppEnEdicionIndividual.id;
            
            console.log('[Cotizacion] Guardando edicion para EPP ID:', epp_id, { cantidad, observaciones, valorUnitario, totalItem });
            
            // Extraer imágenes desde el DOM del modal (galería)
            const imagenes = [];
            const galeriaItems = document.querySelectorAll('#modalEditarEPPFotosGaleria img');
            console.log('[Cotizacion] Imágenes encontradas en galería:', galeriaItems.length);
            
            // Convertir blob URLs a Data URLs (base64) para que persistan
            const convertirImagenADataURL = async (img, idx) => {
                return new Promise((resolve) => {
                    const src = img.src;
                    const alt = img.alt || `imagen_${idx}.jpg`;
                    
                    if (!src) {
                        resolve(null);
                        return;
                    }
                    
                    // Si ya es una data URL, usarla directamente
                    if (src.startsWith('data:')) {
                        console.log(`[Cotizacion] Imagen ${idx} ya es Data URL`);
                        imagenes.push({
                            previewUrl: src,
                            url: src,
                            nombre: alt,
                            ruta_web: src,
                            base64: src
                        });
                        resolve();
                        return;
                    }
                    
                    // Si es una blob URL, convertir a canvas y luego a data URL
                    if (src.startsWith('blob:')) {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        const tempImg = new Image();
                        
                        tempImg.crossOrigin = 'anonymous';
                        tempImg.onload = function() {
                            canvas.width = tempImg.width;
                            canvas.height = tempImg.height;
                            ctx.drawImage(tempImg, 0, 0);
                            const dataURL = canvas.toDataURL('image/jpeg', 0.85);
                            
                            console.log(`[Cotizacion] Imagen ${idx} convertida a Data URL (${dataURL.length} bytes)`);
                            imagenes.push({
                                previewUrl: dataURL,
                                url: dataURL,
                                nombre: alt,
                                ruta_web: dataURL,
                                base64: dataURL
                            });
                            resolve();
                        };
                        
                        tempImg.onerror = function() {
                            console.warn(`[Cotizacion] Error convirtiendo blob URL a imagen ${idx}`);
                            // Fallback: usar blob URL directamente
                            imagenes.push({
                                previewUrl: src,
                                url: src,
                                nombre: alt,
                                ruta_web: src
                            });
                            resolve();
                        };
                        
                        tempImg.src = src;
                    } else {
                        // URL normal (http/https)
                        console.log(`[Cotizacion] Imagen ${idx} es URL normal: ${src}`);
                        imagenes.push({
                            previewUrl: src,
                            url: src,
                            nombre: alt,
                            ruta_web: src
                        });
                        resolve();
                    }
                });
            };
            
            // Procesar todas las imágenes de forma síncrona pero esperar a que se conviertan
            await Promise.all(
                Array.from(galeriaItems).map((img, idx) => convertirImagenADataURL(img, idx))
            );
            
            console.log('[Cotizacion] Total de imágenes capturadas:', imagenes.length);
            
            // Actualizar en window.itemsPedido (fuente principal)
            const items = Array.isArray(window.itemsPedido) ? window.itemsPedido : [];
            const indice = items.findIndex(item => {
                const itemId = item.epp_id || item.id || item.pedidoEppId || item.tarjetaId;
                return String(itemId) === String(epp_id);
            });
            
            if (indice !== -1) {
                // Actualizar en window.itemsPedido
                window.itemsPedido[indice] = {
                    ...window.itemsPedido[indice],
                    cantidad: cantidad,
                    observaciones: observaciones,
                    imagenes: imagenes,
                    valor_unitario: valorUnitario,
                    total: totalItem
                };
                console.log('[Cotizacion] window.itemsPedido actualizado con nuevo precio');
            } else {
                console.warn('[Cotizacion] No se encontró el EPP en itemsPedido, buscando en __EPP_COTIZACION_ITEMS__');
            }
            
            // Sincronizar también __EPP_COTIZACION_ITEMS__ para mantener coherencia
            if (Array.isArray(window.__EPP_COTIZACION_ITEMS__)) {
                const idx = window.__EPP_COTIZACION_ITEMS__.findIndex(item => {
                    const itemId = item.epp_id || item.id;
                    return String(itemId) === String(epp_id);
                });
                
                if (idx !== -1) {
                    window.__EPP_COTIZACION_ITEMS__[idx] = {
                        ...window.__EPP_COTIZACION_ITEMS__[idx],
                        cantidad: cantidad,
                        observaciones: observaciones,
                        imagenes: imagenes,
                        valor_unitario: valorUnitario,
                        total: totalItem
                    };
                    console.log('[Cotizacion] window.__EPP_COTIZACION_ITEMS__ sincronizado');
                }
            }
            
            // Re-renderizar la tabla
            const tabla = document.getElementById('tabla-items-pedido');
            if (tabla) {
                const fila = tabla.querySelector(`tr[data-item-id="${epp_id}"]`);
                if (fila) {
                    // Actualizar cantidad
                    const celdaCantidad = fila.querySelectorAll('td')[3];
                    if (celdaCantidad) {
                        celdaCantidad.textContent = cantidad;
                    }
                    
                    // Actualizar observaciones
                    const celdaObservaciones = fila.querySelectorAll('td')[4];
                    if (celdaObservaciones) {
                        celdaObservaciones.textContent = observaciones;
                    }

                    // Actualizar Valor Unitario
                    const celdaVU = fila.querySelectorAll('td')[5];
                    if (celdaVU) {
                        celdaVU.textContent = valorUnitario.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
                    }

                    // Actualizar Total
                    const celdaTotal = fila.querySelectorAll('td')[6]?.querySelector('span');
                    if (celdaTotal) {
                        celdaTotal.textContent = totalItem.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
                    }
                    
                    // Actualizar imagen
                    const celdaImagen = fila.querySelectorAll('td')[1];
                    if (celdaImagen) {
                        if (imagenes.length > 0) {
                            const miniatura = imagenes[0].previewUrl || imagenes[0].url || imagenes[0].base64 || imagenes[0].ruta_web || '';
                            if (miniatura) {
                                const itemNombre = (items[indice] && (items[indice].nombre || items[indice].nombre_epp)) || 'Imagen';
                                celdaImagen.innerHTML = `
                                    <img src="${miniatura}" alt="${itemNombre}" 
                                         style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; cursor: pointer;" 
                                         onclick="event.preventDefault(); event.stopPropagation(); if (window.mostrarImagenProcesoGrande) window.mostrarImagenProcesoGrande('${miniatura}'); else if (window.abrirImagenGrande) window.abrirImagenGrande('${miniatura}', 'galeria-epp-${epp_id}', 0);">
                                `;
                                console.log('[Cotizacion] Imagen actualizada en tabla');
                            }
                        } else {
                            // Si se eliminaron todas las imágenes, mostrar placeholder o vacío
                            celdaImagen.innerHTML = `
                                <div style="width: 40px; height: 40px; background: #f9fafb; border-radius: 4px; display: flex; align-items: center; justify-content: center; border: 1px dashed #d1d5db;" title="Sin imagen">
                                    <i class="material-symbols-rounded" style="font-size: 20px; color: #9ca3af;">image_not_supported</i>
                                </div>
                            `;
                            console.log('[Cotizacion] Imagen eliminada de la tabla');
                        }
                    }
                    
                    console.log('[Cotizacion] Fila actualizada en tabla');
                }
            }
            
            // Recalcular totales
            if (typeof globalThis.syncTotales === 'function') {
                globalThis.syncTotales();
                console.log('[Cotizacion] Totales recalculados');
            }
            
            // Cerrar el modal
            if (typeof window.cerrarModalEditarEPP === 'function') {
                window.cerrarModalEditarEPP();
            } else {
                const modal = document.getElementById('modalEditarEPP');
                if (modal) {
                    modal.classList.add('hidden');
                }
            }
            
            console.log('[Cotizacion] Edicion guardada exitosamente');
            return;
        }
        
        // Fallback: intentar con la función original si existe
        if (typeof originalGuardarEdicionEnModalEditarEPP === 'function') {
            console.log('[Cotizacion] Fallback a originalGuardarEdicionEnModalEditarEPP');
            originalGuardarEdicionEnModalEditarEPP();
        } else {
            console.warn('[Cotizacion] No se pudo guardar la edicion');
        }
    };
    
    console.log('[Cotizacion] abrirModalEditarEPP y guardarEdicionEnModalEditarEPP redefinidas para cotizaciones');
});
</script>

<script defer src="{{ js_asset('js/modulos/cotizaciones/epp/cotizacion-epp-create-page.js') }}?v={{ $v }}"></script>
@endpush
