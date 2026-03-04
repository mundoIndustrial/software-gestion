@extends('layouts.asesores')

@section('title', 'Cotización Para Cliente')
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
        window.__EPP_COTIZACION_CONDICIONES_PAGO__ = {!! json_encode($condicionesPago ?? '') !!};
        window.__EPP_COTIZACION_TIEMPO_ENTREGA__ = {!! json_encode($tiempoEntrega ?? '') !!};
        window.__EPP_COTIZACION_CUENTAS_AUTORIZADAS__ = {!! json_encode($cuentasAutorizadas ?? '') !!};
        window.__EPP_COTIZACION_CLIENTE_NIT__ = {!! json_encode($cotizacion->cliente_nit ?? '') !!};
        window.__EPP_COTIZACION_CLIENTE_DIRECCION__ = {!! json_encode($cotizacion->cliente_direccion ?? '') !!};
        window.__EPP_COTIZACION_CLIENTE_TELEFONO__ = {!! json_encode($cotizacion->cliente_telefono ?? '') !!};
        
        // Logging inicial de variables
        console.log('[EPP Form] Variables iniciales establecidas:', {
            '__EPP_COTIZACION_EDIT__': window.__EPP_COTIZACION_EDIT__,
            '__EPP_COTIZACION_MODE__': window.__EPP_COTIZACION_MODE__,
            '__EPP_COTIZACION_ID__': window.__EPP_COTIZACION_ID__
        });
        
        // Verificar si las variables se mantienen después de un timeout
        setTimeout(() => {
            console.log('[EPP Form] Variables después de 500ms:', {
                '__EPP_COTIZACION_EDIT__': window.__EPP_COTIZACION_EDIT__,
                '__EPP_COTIZACION_MODE__': window.__EPP_COTIZACION_MODE__,
                '__EPP_COTIZACION_ID__': window.__EPP_COTIZACION_ID__
            });
        }, 500);
        
        // Verificar si las variables se mantienen después de 1 segundo
        setTimeout(() => {
            console.log('[EPP Form] Variables después de 1000ms:', {
                '__EPP_COTIZACION_EDIT__': window.__EPP_COTIZACION_EDIT__,
                '__EPP_COTIZACION_MODE__': window.__EPP_COTIZACION_MODE__,
                '__EPP_COTIZACION_ID__': window.__EPP_COTIZACION_ID__
            });
        }, 1000);
        
        // Verificar si las variables se mantienen después de 2 segundos
        setTimeout(() => {
            console.log('[EPP Form] Variables después de 2000ms:', {
                '__EPP_COTIZACION_EDIT__': window.__EPP_COTIZACION_EDIT__,
                '__EPP_COTIZACION_MODE__': window.__EPP_COTIZACION_MODE__,
                '__EPP_COTIZACION_ID__': window.__EPP_COTIZACION_ID__
            });
        }, 2000);
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
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Cotización Cliente</label>
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
                                            <label style="font-size: 9px; color: #64748b; white-space: nowrap;">%</label>
                                            <input type="number" id="valor-iva-epp" min="0" step="1" value="19" placeholder="19" style="width: 80px; background: white; border: 1px solid #e5e7eb; padding: 6px 8px; border-radius: 4px; font-weight: 700; color: #111827; font-size: 11px; text-align: center;">
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <label style="font-size: 9px; color: #64748b; white-space: nowrap;">Valor</label>
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
                    <button id="btnGuardarBorradorEpp" type="button" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); border: 2px solid #3d8b40; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #45a049 0%, #3d8b40 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(76, 175, 80, 0.2)';" onmouseout="this.style.background='linear-gradient(135deg, #4CAF50 0%, #45a049 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="fas fa-save" style="font-size: 0.9rem;"></i> Guardar Borrador
                    </button>
                    @endif
                    <button id="btnEnviarCotizacionEpp" type="button" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); border: 2px solid #003d7a; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #0052a3 0%, #003d7a 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 12px rgba(0, 102, 204, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0066cc 0%, #0052a3 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="fas fa-paper-plane" style="font-size: 0.9rem;"></i> Crear
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

<script defer src="{{ asset('js/modulos/crear-pedido/epp/epp-store.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-api-service.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-state-manager.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-modal-manager.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-item-manager.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/procesos/gestion-items-pedido.js') }}"></script>
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
        const listSelector = '#tabla-items-pedido';

        const formatearNumero = (num) => {
            if (!Number.isFinite(num)) return '0';
            if (Number.isInteger(num)) return String(num);
            const s = num.toFixed(2);
            return s.replace(/\.00$/, '').replace(/(\.[0-9])0$/, '$1');
        };

        function calcularSubtotalEpp() {
            // Leer TODOS los items (EPP + Prendas) desde window.itemsPedido
            const items = Array.isArray(window.itemsPedido) ? window.itemsPedido : [];
            return items.reduce((sum, it) => {
                const t = Number(it.total);
                if (isFinite(t) && t > 0) return sum + t;
                const vu = Number(it.valor_unitario);
                const c = Number(it.cantidad);
                if (isFinite(vu) && isFinite(c) && c > 0) return sum + (vu * c);
                return sum;
            }, 0);
        }

        function syncTotales() {
            const subtotalEl = document.getElementById('subtotal-epp');
            const ivaEl = document.getElementById('valor-iva-epp');
            const ivaCalculadoEl = document.getElementById('valor-iva-calculado');
            const totalEl = document.getElementById('total-epp');
            if (!subtotalEl || !ivaEl || !totalEl || !ivaCalculadoEl) return;

            const subtotal = calcularSubtotalEpp();
            const ivaPorcentaje = (ivaEl.value !== undefined && ivaEl.value !== null && String(ivaEl.value).trim() !== '' && !isNaN(Number(ivaEl.value)))
                ? Number(ivaEl.value)
                : 0;
            
            // Calcular el valor del IVA como porcentaje del subtotal
            const ivaValor = (subtotal * ivaPorcentaje) / 100;
            const total = subtotal + ivaValor;

            subtotalEl.value = formatearNumero(subtotal);
            ivaCalculadoEl.value = formatearNumero(ivaValor);
            totalEl.value = formatearNumero(total);
        }

        // Exponer syncTotales al objeto window para que sea accesible desde otros scripts
        window.syncTotales = syncTotales;

        // Registrar syncTotales como listener del store para auto-actualización
        if (window.eppStore) {
            window.eppStore.onChange(function() {
                syncTotales();
                syncEmptyState();
            });
            console.log('[EPP Form] syncTotales registrado como listener de eppStore');
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

            if (ivaEl) {
                // Si el IVA viene vacío, null, undefined o 0, usar 19 como default
                const ivaValue = window.__EPP_COTIZACION_IVA__ || 19;
                ivaEl.value = ivaValue;
            }
            
            // Cargar información adicional en modo edición
            console.log('[EPP Form] Iniciando carga de datos en modo edición:', window.__EPP_COTIZACION_EDIT__);
            console.log('[EPP Form] Tipo de __EPP_COTIZACION_EDIT__:', typeof window.__EPP_COTIZACION_EDIT__);
            console.log('[EPP Form] Valor de __EPP_COTIZACION_EDIT__:', window.__EPP_COTIZACION_EDIT__);
            console.log('[EPP Form] Stack trace:', new Error().stack);
            
            // Verificar si el valor cambia después de un pequeño timeout
            setTimeout(() => {
                console.log('[EPP Form] Valor de __EPP_COTIZACION_EDIT__ después de timeout:', window.__EPP_COTIZACION_EDIT__);
            }, 100);
            
            // Modificar la condición para aceptar tanto booleano como string
            if (window.__EPP_COTIZACION_EDIT__ === 'true' || window.__EPP_COTIZACION_EDIT__ === true) {
                console.log('[EPP Form] Modo edición detectado, cargando campos...');
                
                const condicionesPagoEl = document.getElementById('condiciones_pago');
                const tiempoEntregaEl = document.getElementById('tiempo_entrega');
                const cuentasAutorizadasEl = document.getElementById('cuentas_autorizadas');
                
                if (condicionesPagoEl && window.__EPP_COTIZACION_CONDICIONES_PAGO__) {
                    condicionesPagoEl.value = window.__EPP_COTIZACION_CONDICIONES_PAGO__;
                    console.log('[EPP Form] Condiciones de pago cargadas:', window.__EPP_COTIZACION_CONDICIONES_PAGO__);
                }
                
                if (tiempoEntregaEl && window.__EPP_COTIZACION_TIEMPO_ENTREGA__) {
                    tiempoEntregaEl.value = window.__EPP_COTIZACION_TIEMPO_ENTREGA__;
                    console.log('[EPP Form] Tiempo de entrega cargado:', window.__EPP_COTIZACION_TIEMPO_ENTREGA__);
                }
                
                if (cuentasAutorizadasEl && window.__EPP_COTIZACION_CUENTAS_AUTORIZADAS__) {
                    cuentasAutorizadasEl.value = window.__EPP_COTIZACION_CUENTAS_AUTORIZADAS__;
                    console.log('[EPP Form] Cuentas autorizadas cargadas:', window.__EPP_COTIZACION_CUENTAS_AUTORIZADAS__);
                }
                
                // Cargar campos adicionales del cliente
                const nitEl = document.getElementById('header-nit');
                const direccionEl = document.getElementById('header-direccion');
                const telefonoEl = document.getElementById('header-telefono');
                
                console.log('[EPP Form] Elementos del cliente encontrados:', {
                    'nitEl': !!nitEl,
                    'direccionEl': !!direccionEl,
                    'telefonoEl': !!telefonoEl
                });
                
                // Logging para depurar datos del cliente
                console.log('[EPP Form] Datos del cliente:', {
                    'cliente_nit': window.__EPP_COTIZACION_CLIENTE_NIT__,
                    'cliente_direccion': window.__EPP_COTIZACION_CLIENTE_DIRECCION__,
                    'cliente_telefono': window.__EPP_COTIZACION_CLIENTE_TELEFONO__,
                    'es_edicion': window.__EPP_COTIZACION_EDIT__,
                    'cotizacion_id': window.__EPP_COTIZACION_ID__
                });
                
                if (nitEl && window.__EPP_COTIZACION_CLIENTE_NIT__) {
                    nitEl.value = window.__EPP_COTIZACION_CLIENTE_NIT__;
                    console.log('[EPP Form] NIT cargado:', window.__EPP_COTIZACION_CLIENTE_NIT__);
                } else {
                    console.warn('[EPP Form] No se encontró elemento NIT o no hay datos');
                }
                
                if (direccionEl && window.__EPP_COTIZACION_CLIENTE_DIRECCION__) {
                    direccionEl.value = window.__EPP_COTIZACION_CLIENTE_DIRECCION__;
                    console.log('[EPP Form] Dirección cargada:', window.__EPP_COTIZACION_CLIENTE_DIRECCION__);
                } else {
                    console.warn('[EPP Form] No se encontró elemento Dirección o no hay datos');
                }
                
                if (telefonoEl && window.__EPP_COTIZACION_CLIENTE_TELEFONO__) {
                    telefonoEl.value = window.__EPP_COTIZACION_CLIENTE_TELEFONO__;
                    console.log('[EPP Form] Teléfono cargado:', window.__EPP_COTIZACION_CLIENTE_TELEFONO__);
                } else {
                    console.warn('[EPP Form] No se encontró elemento Teléfono o no hay datos');
                }
                
                console.log('[EPP Form] Carga de datos del cliente completada');
            } else {
                console.log('[EPP Form] No está en modo edición, omitiendo carga de datos');
                console.log('[EPP Form] Stack trace en modo no edición:', new Error().stack);
            }
        } catch (e) {
            console.error('[EPP Form] Error durante la carga de datos del cliente:', e);
            console.error('[EPP Form] Stack trace:', e.stack);
        }

        try {
            const items = Array.isArray(window.__EPP_COTIZACION_ITEMS__) ? window.__EPP_COTIZACION_ITEMS__ : [];

            if (items.length > 0) {
                // Cargar items al store (fuente única de verdad)
                if (window.eppStore) {
                    window.eppStore.cargarItems(items);
                } else {
                    if (!window.itemsPedido) window.itemsPedido = [];
                    window.itemsPedido = items;
                }

                if (window.eppItemManager && typeof window.eppItemManager.crearItem === 'function') {
                    const lista = document.getElementById('tabla-items-pedido');
                    if (lista) lista.innerHTML = '';

                    items.forEach((it) => {
                        const eppId = it.epp_id || it.id;
                        window.eppItemManager.crearItem(
                            eppId,
                            it.nombre_epp || it.nombre || 'Sin nombre',
                            it.categoria || null,
                            it.cantidad || 1,
                            it.observaciones || null,
                            it.imagenes || [],
                            eppId,
                            it.valor_unitario ?? null,
                            it.total ?? null,
                            it.tipo || 'epp'
                        );
                    });
                }
                // syncTotales se dispara automáticamente via eppStore.onChange
                // Llamar manualmente por si el store ya notificó antes de que el DOM estuviera listo
                syncTotales();
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

                    // PRIORIDAD 1: Si ya es un File object, retornarlo directamente
                    if (img instanceof File) {
                        return img;
                    }

                    // PRIORIDAD 2: Si tiene un file object guardado (desde window.fotosEPP), usarlo sin fetch
                    if (img?.file && img.file instanceof File) {
                        console.log(`[convertirImagenAFile] Usando File object guardado: ${img.nombre}`);
                        return img.file;
                    }

                    const src = (typeof img === 'string')
                        ? img
                        : (img?.previewUrl || img?.base64 || img?.url || img?.ruta_web || img?.ruta_webp || img?.ruta_original || null);

                    if (!src || typeof src !== 'string') return null;

                    // PRIORIDAD 3: DataURL (puede hacer fetch, no viola CSP)
                    if (src.startsWith('data:')) {
                        console.log(`[convertirImagenAFile] Convirtiendo DataURL a File: ${fallbackName}`);
                        const res = await fetch(src);
                        const blob = await res.blob();
                        return new File([blob], fallbackName, { type: blob.type || 'image/webp' });
                    }

                    // PRIORIDAD 4: URLs normales (NO blob URLs - violarían CSP en producción)
                    // Blob URLs no pueden ser fetched en producción, se saltan
                    if (src.startsWith('blob:')) {
                        console.warn(`[convertirImagenAFile] Blob URL detectado sin File object. Ignorando fetch para cumplir CSP: ${src.substring(0, 50)}...`);
                        return null;
                    }

                    // URLs normales (http/https/relativas)
                    if (src.startsWith('http') || src.startsWith('/')) {
                        console.log(`[convertirImagenAFile] Fetching URL normal: ${src.substring(0, 50)}...`);
                        const res = await fetch(src);
                        const blob = await res.blob();
                        const name = (img?.nombre_archivo || img?.name || fallbackName);
                        return new File([blob], name, { type: blob.type || 'image/webp' });
                    }

                    return null;
                } catch (e) {
                    console.error(`[convertirImagenAFile] Error procesando imagen:`, e);
                    return null;
                }
            }

            const itemsPedido = Array.isArray(window.itemsPedido) ? window.itemsPedido : [];
            
            // Procesar tanto EPPs como prendas (todos los items)
            let items = itemsPedido.filter(i => {
                const tipo = (i?.tipo || '').toLowerCase();
                return tipo === 'epp' || tipo === 'prenda';
            });
            
            // Si no hay filtrado por tipo, asumir que todos son items válidos
            if (items.length === 0 && itemsPedido.length > 0) {
                items = itemsPedido;
            }

            if (!cliente) {
                if (window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'Cliente requerido', text: 'Por favor ingresa el nombre del cliente' });
                }
                return;
            }

            // Tipo de venta es OPCIONAL
            // Si no se selecciona, se guardará como null en la BD

            if (items.length === 0) {
                if (window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'Sin ítems', text: 'Agrega al menos un artículo (EPP o Prenda) a la cotización' });
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
            const itemsPayload = items.map((item) => ({
                tipo: item.tipo || 'epp',  // Asegurar que el tipo se incluya (epp o prenda)
                imagenes_keep: (() => {
                    const imgs = Array.isArray(item.imagenes) ? item.imagenes : [];
                    const keep = [];
                    for (const im of imgs) {
                        // Identificar si es una imagen existente (sin file object) o nueva (con file object)
                        if (im?.file && im.file instanceof File) {
                            // Imagen nueva - será subida como archivo, no agregar a keep
                            continue;
                        }
                        
                        const src = (typeof im === 'string')
                            ? im
                            : (im?.previewUrl || im?.url || im?.ruta_web || im?.ruta_webp || im?.ruta_original || null);
                        if (!src || typeof src !== 'string') continue;
                        
                        // Solo mantener URLs que NO son blob URLs
                        // Las blob URLs son temporales y no se pueden usar después
                        if (src.startsWith('blob:')) {
                            console.log(`[itemsPayload] Ignorando blob URL temporal (será resubida si tiene file object):`, src.substring(0, 50));
                            continue;
                        }
                        
                        // Convertir URL pública /storage/... a ruta relativa en disk('public')
                        if (src.includes('/storage/')) {
                            const idx = src.indexOf('/storage/');
                            const rel = src.substring(idx + '/storage/'.length);
                            if (rel) {
                                keep.push(rel);
                                console.log(`[itemsPayload] Imagen a mantener: ${rel}`);
                            }
                        } else if (src.startsWith('http') || src.startsWith('/')) {
                            // Para URLs que no están en /storage/, intentar extraer la ruta relativa
                            // o agregarlas tal cual si son URLs accesibles
                            keep.push(src);
                            console.log(`[itemsPayload] Imagen URL a mantener: ${src.substring(0, 50)}`);
                        }
                    }
                    return keep;
                })(),
                clear_imagenes: !(Array.isArray(item.imagenes) && item.imagenes.length > 0),
                id: item.id || item.pedidoEppId || null,
                nombre: item.nombre_epp || item.nombre_completo || item.nombre || 'Sin nombre',
                cantidad: item.cantidad || 1,
                valor_unitario: (item.valor_unitario !== undefined && item.valor_unitario !== null && String(item.valor_unitario).trim() !== '')
                    ? Number(item.valor_unitario)
                    : null,
                total: (item.total !== undefined && item.total !== null && String(item.total).trim() !== '')
                    ? Number(item.total)
                    : null,
                observaciones: item.observaciones || null,
            }));
            formData.append('items', JSON.stringify(itemsPayload));

            // Archivos: items[i][imagenes][]
            // SOLO subir imágenes NUEVAS (que tienen file object)
            // Las URLs existentes (imagenes_keep) se mantienen automáticamente en el backend
            for (let idx = 0; idx < items.length; idx++) {
                const item = items[idx];
                const imagenes = Array.isArray(item.imagenes) ? item.imagenes : [];
                for (let j = 0; j < imagenes.length; j++) {
                    const img = imagenes[j];
                    
                    // SOLO procesar si es nuevas (tiene file object) o es data URL
                    if (img instanceof File) {
                        // Ya es un File, agregar directo
                        formData.append(`items[${idx}][imagenes][]`, img, img.name);
                    } else if (img?.file && img.file instanceof File) {
                        // Tiene file object guardado, es nueva
                        formData.append(`items[${idx}][imagenes][]`, img.file, img.file.name);
                    } else if (typeof img === 'string' && img.startsWith('data:')) {
                        // Es DataURL, convertir a File
                        const file = await convertirImagenAFile(img, `epp_${idx + 1}_${j + 1}.webp`);
                        if (file) {
                            formData.append(`items[${idx}][imagenes][]`, file, file.name);
                        }
                    }
                    // Si es solo URL o ruta (imagenes_keep), NO hacer nada aquí
                    // El backend ya la mantiene automáticamente
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

                // Si es una edición, actualizar los datos del cliente en el encabezado
                if (window.__EPP_COTIZACION_EDIT__ === 'true' || window.__EPP_COTIZACION_EDIT__ === true) {
                    console.log('✏️ [enviarCotizacionEpp] Actualizando datos del cliente en encabezado (modo edición)');
                    
                    // Actualizar campos del cliente si vienen en la respuesta
                    if (data.cliente) {
                        const clienteEl = document.getElementById('header-cliente');
                        if (clienteEl) {
                            clienteEl.value = data.cliente;
                            console.log('✏️ [enviarCotizacionEpp] Cliente actualizado:', data.cliente);
                        }
                    }
                    
                    if (data.cliente_nit !== undefined) {
                        const nitEl = document.getElementById('header-nit');
                        if (nitEl) {
                            nitEl.value = data.cliente_nit;
                            console.log('✏️ [enviarCotizacionEpp] NIT actualizado:', data.cliente_nit);
                        }
                    }
                    
                    if (data.cliente_direccion !== undefined) {
                        const direccionEl = document.getElementById('header-direccion');
                        if (direccionEl) {
                            direccionEl.value = data.cliente_direccion;
                            console.log('✏️ [enviarCotizacionEpp] Dirección actualizada:', data.cliente_direccion);
                        }
                    }
                    
                    if (data.cliente_telefono !== undefined) {
                        const telefonoEl = document.getElementById('header-telefono');
                        if (telefonoEl) {
                            telefonoEl.value = data.cliente_telefono;
                            console.log('✏️ [enviarCotizacionEpp] Teléfono actualizado:', data.cliente_telefono);
                        }
                    }
                    
                    // Actualizar variables globales
                    if (data.cliente_nit !== undefined) {
                        window.__EPP_COTIZACION_CLIENTE_NIT__ = data.cliente_nit;
                    }
                    if (data.cliente_direccion !== undefined) {
                        window.__EPP_COTIZACION_CLIENTE_DIRECCION__ = data.cliente_direccion;
                    }
                    if (data.cliente_telefono !== undefined) {
                        window.__EPP_COTIZACION_CLIENTE_TELEFONO__ = data.cliente_telefono;
                    }
                    
                    console.log('✏️ [enviarCotizacionEpp] Datos del cliente actualizados en encabezado y variables globales');
                }

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
                // syncTotales se dispara automáticamente via eppStore.onChange
                return result;
            };
            window.__eppCotizacionFinalizarWrapped = true;
        }

        // Función para guardar cambios de edición de EPP
        window.guardarEdicionEPP = function() {
            console.log('✏️ [guardarEdicionEPP] Iniciando actualización de EPP en modo edición de cotización...');
            console.log('✏️ [guardarEdicionEPP] NOTA: Solo actualizando en memoria, no guardando en BD');
            
            try {
                // Obtener datos del formulario
                const nombre = document.getElementById('nombreProductoEPP')?.value?.trim() || '';
                const cantidad = parseInt(document.getElementById('cantidadEPP')?.value) || 1;
                const observaciones = document.getElementById('observacionesEPP')?.value?.trim() || '';
                const valorUnitario = parseFloat(document.getElementById('valorUnitarioEPP')?.value) || null;
                const total = parseFloat(document.getElementById('totalEPP')?.value) || 0;
                
                // Obtener imágenes del array temporal o stateManager (prioridad invertida)
                let imagenes = [];
                console.log('✏️ [guardarEdicionEPP] Verificando fuentes de imágenes:');
                console.log('✏️ [guardarEdicionEPP] - window.fotosEPP existe:', !!window.fotosEPP);
                console.log('✏️ [guardarEdicionEPP] - window.fotosEPP es array:', Array.isArray(window.fotosEPP));
                console.log('✏️ [guardarEdicionEPP] - window.eppStateManager existe:', !!window.eppStateManager);
                console.log('✏️ [guardarEdicionEPP] - window.eppStateManager.getImagenesSubidas es función:', typeof window.eppStateManager?.getImagenesSubidas);
                
                // Prioridad 1: Array temporal (donde se guardan las imágenes nuevas)
                if (window.fotosEPP && Array.isArray(window.fotosEPP) && window.fotosEPP.length > 0) {
                    imagenes = window.fotosEPP;
                    console.log('✏️ [guardarEdicionEPP] Imágenes obtenidas desde array temporal:', imagenes.length);
                    console.log('✏️ [guardarEdicionEPP] Detalle imágenes array temporal:', imagenes.map(img => ({id: img.id, nombre: img.nombre, tieneFile: !!img.file})));
                } 
                // Prioridad 2: StateManager (si el array temporal está vacío)
                else if (window.eppStateManager && typeof window.eppStateManager.getImagenesSubidas === 'function') {
                    imagenes = window.eppStateManager.getImagenesSubidas() || [];
                    console.log('✏️ [guardarEdicionEPP] Imágenes obtenidas desde stateManager:', imagenes.length);
                    console.log('✏️ [guardarEdicionEPP] Detalle imágenes stateManager:', imagenes.map(img => ({id: img.id, nombre: img.nombre, tieneFile: !!img.file})));
                } 
                // Si no hay imágenes en ninguna fuente
                else {
                    console.log('✏️ [guardarEdicionEPP] No se encontraron imágenes en ninguna fuente');
                }
                
                console.log('✏️ [guardarEdicionEPP] Imágenes finales a guardar:', imagenes.length);
                
                // Obtener el ID del EPP en edición (puede venir como epp_id o id)
                const eppEnEdicion = window.eppEnEdicion;
                const eppId = eppEnEdicion ? (eppEnEdicion.epp_id || eppEnEdicion.id) : null;
                if (!eppEnEdicion || !eppId) {
                    console.error('✏️ [guardarEdicionEPP] No hay EPP en edición');
                    return;
                }
                
                console.log('✏️ [guardarEdicionEPP] Datos a guardar:', {
                    id: eppId,
                    nombre,
                    cantidad,
                    observaciones,
                    valorUnitario,
                    total,
                    imagenes: imagenes.length
                });
                
                // Actualizar la fila en la tabla
                const fila = document.querySelector(`tr.item-epp[data-item-id="${eppId}"]`);
                if (fila) {
                    console.log('✏️ [guardarEdicionEPP] Fila encontrada, actualizando...');
                    const cells = fila.querySelectorAll('td');
                    console.log('✏️ [guardarEdicionEPP] Celdas encontradas:', cells.length);
                    
                    // Actualizar celda de imagen (celda 1)
                    if (cells[1]) {
                        console.log('✏️ [guardarEdicionEPP] Actualizando celda imagen...');
                        if (imagenes.length > 0) {
                            const imagenPrincipal = imagenes.find(img => img.principal === 1) || imagenes[0];
                            if (imagenPrincipal) {
                                console.log('✏️ [guardarEdicionEPP] Agregando imagen principal a la tabla:', imagenPrincipal.nombre);
                                cells[1].innerHTML = `
                                    <img src="${imagenPrincipal.previewUrl || imagenPrincipal.ruta}" alt="${imagenPrincipal.nombre}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; cursor: pointer;" 
                                         onclick="event.preventDefault(); event.stopPropagation(); if (window.mostrarImagenProcesoGrande) window.mostrarImagenProcesoGrande('${imagenPrincipal.previewUrl || imagenPrincipal.ruta}'); else if (window.abrirImagenGrande) window.abrirImagenGrande('${imagenPrincipal.previewUrl || imagenPrincipal.ruta}', 'galeria-epp-${eppId}', 0);">
                                `;
                                console.log('✏️ [guardarEdicionEPP] Imagen agregada al DOM de la tabla');
                            }
                        } else {
                            cells[1].innerHTML = '<span style="color: #9ca3af;">Sin imagen</span>';
                            console.log('✏️ [guardarEdicionEPP] Sin imagen, mostrando placeholder');
                        }
                    }
                    
                    // Actualizar celda de descripción (celda 2)
                    if (cells[2]) {
                        console.log('✏️ [guardarEdicionEPP] Actualizando celda descripción...');
                        const nombreSpan = cells[2].querySelector('span');
                        if (nombreSpan) {
                            nombreSpan.textContent = nombre;
                            console.log('✏️ [guardarEdicionEPP] Nombre actualizado en span:', nombre);
                        } else {
                            cells[2].textContent = nombre;
                            console.log('✏️ [guardarEdicionEPP] Nombre actualizado en celda:', nombre);
                        }
                    }
                    
                    // Actualizar celda de cantidad (celda 3)
                    if (cells[3]) {
                        cells[3].textContent = cantidad;
                        console.log('✏️ [guardarEdicionEPP] Cantidad actualizada:', cantidad);
                    }
                    
                    // Actualizar celda de observaciones (celda 4)
                    if (cells[4]) {
                        cells[4].textContent = observaciones || '-';
                        console.log('✏️ [guardarEdicionEPP] Observaciones actualizadas:', observaciones || '-');
                    }
                    
                    // Actualizar celda de valor unitario (celda 5)
                    if (cells[5]) {
                        cells[5].textContent = valorUnitario !== null ? valorUnitario : 'N/A';
                        console.log('✏️ [guardarEdicionEPP] Valor unitario actualizado:', valorUnitario);
                    }
                    
                    // Actualizar celda de total (celda 6)
                    if (cells[6]) {
                        const totalSpan = cells[6].querySelector('span');
                        if (totalSpan) {
                            totalSpan.textContent = total;
                            console.log('✏️ [guardarEdicionEPP] Total actualizado en span:', total);
                        } else {
                            // Buscar el span dentro del div
                            const div = cells[6].querySelector('div');
                            if (div) {
                                const span = div.querySelector('span');
                                if (span) {
                                    span.textContent = total;
                                    console.log('✏️ [guardarEdicionEPP] Total actualizado en span anidado:', total);
                                }
                            }
                        }
                    }
                    
                    console.log('✏️ [guardarEdicionEPP] Fila actualizada correctamente en memoria');
                } else {
                    console.error('✏️ [guardarEdicionEPP] No se encontró la fila para actualizar:', eppId);
                }
                
                // Actualizar el item via eppStore (fuente única de verdad)
                if (window.eppStore) {
                    // eppStore.actualizarItem notifica listeners → syncTotales se ejecuta automáticamente
                    const actualizado = window.eppStore.actualizarItem(eppId, {
                        nombre_epp: nombre,
                        nombre: nombre,
                        cantidad: cantidad,
                        observaciones: observaciones,
                        valor_unitario: valorUnitario,
                        total: total,
                        imagenes: imagenes
                    });
                    if (!actualizado) {
                        console.warn('✏️ [guardarEdicionEPP] Item NO encontrado en eppStore');
                    }
                } else {
                    // Fallback sin store
                    if (window.itemsPedido && Array.isArray(window.itemsPedido)) {
                        const itemIndex = window.itemsPedido.findIndex(item => 
                            String(item.epp_id || item.id) === String(eppId)
                        );
                        if (itemIndex !== -1) {
                            Object.assign(window.itemsPedido[itemIndex], {
                                nombre_epp: nombre, nombre, cantidad,
                                observaciones, valor_unitario: valorUnitario,
                                total, imagenes
                            });
                        }
                    }
                    syncTotales();
                }
                
                // Cerrar modal
                if (typeof window.cerrarModalAgregarEPP === 'function') {
                    window.cerrarModalAgregarEPP();
                }
                
                // Resetear estado de edición
                window.eppEnEdicion = null;
                
                console.log('✏️ [guardarEdicionEPP] Cambios actualizados en memoria exitosamente');
                console.log('✏️ [guardarEdicionEPP] Para guardar en BD, use "Enviar Cotización"');
                
            } catch (error) {
                console.error('✏️ [guardarEdicionEPP] Error al guardar cambios:', error);
            }
        };

        // Función para editar un EPP agregado
        window.editarEPPAgregado = function(eppData) {
            console.log('✏️ [editarEPPAgregado] INICIANDO - Editando EPP:', eppData);
            
            // Guardar referencia del EPP en edición a nivel global
            window.eppEnEdicion = eppData;
            console.log('✏️ [editarEPPAgregado] window.eppEnEdicion configurado:', !!window.eppEnEdicion);
            
            // Limpiar buscador
            const buscador = document.getElementById('inputBuscadorEPP');
            if (buscador) buscador.value = '';
            const resultados = document.getElementById('resultadosBuscadorEPP');
            if (resultados) resultados.style.display = 'none';
            
            // Mostrar el producto seleccionado
            if (window.mostrarProductoEPP) {
                window.mostrarProductoEPP({
                    id: eppData.epp_id || eppData.id,
                    nombre_completo: eppData.nombre_epp || eppData.nombre,
                    nombre: eppData.nombre_epp || eppData.nombre,
                    imagen: ''
                });
            }
            
            // Cargar valores en el formulario
            const cantidadInput = document.getElementById('cantidadEPP');
            const obsInput = document.getElementById('observacionesEPP');
            if (cantidadInput) cantidadInput.value = eppData.cantidad || 1;
            if (obsInput) obsInput.value = eppData.observaciones || '';
            
            // Precargar valor unitario / total si existen
            const valorUnitarioInput = document.getElementById('valorUnitarioEPP');
            const totalInput = document.getElementById('totalEPP');
            if (valorUnitarioInput && eppData.valor_unitario) {
                valorUnitarioInput.value = eppData.valor_unitario;
            }
            if (totalInput && eppData.total) {
                totalInput.value = eppData.total;
            }
            
            // Limpiar contenedor de fotos y array antes de cargar
            window.fotosEPP = [];
            const contenedorFotosEdit = document.getElementById('contenedorFotosEPP');
            if (contenedorFotosEdit) {
                const fotosExistentes = contenedorFotosEdit.querySelectorAll('.foto-epp-item');
                fotosExistentes.forEach(el => el.remove());
            }
            const mensajeDragDropEdit = document.getElementById('mensajeDragDrop');
            
            // Cargar imágenes existentes si hay
            if (eppData.imagenes && Array.isArray(eppData.imagenes) && eppData.imagenes.length > 0) {
                console.log('✏️ [editarEPPAgregado] Cargando imágenes existentes:', eppData.imagenes.length);
                
                // Ocultar mensaje drag-drop
                if (mensajeDragDropEdit) mensajeDragDropEdit.style.display = 'none';
                
                eppData.imagenes.forEach((imagen, index) => {
                    // Determinar la URL correcta para mostrar
                    let imageUrl = imagen.previewUrl || imagen.ruta_webp || imagen.ruta_original || imagen.url || null;
                    
                    if (imageUrl) {
                        // Crear objeto para window.fotosEPP
                        const imagenObj = {
                            id: imagen.id || `existing-${index}`,
                            previewUrl: imageUrl,
                            nombre: imagen.nombre || `Foto ${index + 1}`,
                            file: imagen.file || null,
                            extension: (imagen.nombre || '').split('.').pop().toLowerCase() || 'jpg',
                            tamaño: imagen.tamaño || 0,
                            pedido_epp_id: imagen.pedido_epp_id || null,
                            ruta_original: imagen.ruta_original || null,
                            ruta_webp: imagen.ruta_webp || null,
                            principal: imagen.principal || 0,
                            orden: imagen.orden || 0
                        };
                        window.fotosEPP.push(imagenObj);
                        
                        // Usar mostrarVistaPreviaFoto si existe, sino crear manualmente
                        if (typeof mostrarVistaPreviaFoto === 'function') {
                            mostrarVistaPreviaFoto(imagenObj);
                        }
                        console.log(`✏️ [editarEPPAgregado] Imagen cargada: ${imagenObj.nombre}`);
                    }
                });
                
                console.log('✏️ [editarEPPAgregado] Total imágenes cargadas en window.fotosEPP:', window.fotosEPP.length);
            } else {
                console.log('✏️ [editarEPPAgregado] No hay imágenes existentes para cargar');
                if (mensajeDragDropEdit) mensajeDragDropEdit.style.display = 'flex';
            }
            
            // Actualizar contador de fotos
            const contadorFotosEdit = document.getElementById('contadorFotosEPP');
            if (contadorFotosEdit) contadorFotosEdit.textContent = window.fotosEPP.length;
            
            // Mostrar los contenedores de campos que están ocultos por defecto
            const formularioContainer = document.getElementById('formularioAgregarEPP');
            const valorContainer = document.getElementById('valorUnitarioTotalContainer');
            const obsContainer = document.getElementById('observacionesContainer');
            const seccionFotos = document.getElementById('seccionFotosEPP');
            
            if (formularioContainer) {
                formularioContainer.style.display = 'grid';
                console.log('✏️ [editarEPPAgregado] Formulario mostrado');
            }
            if (valorContainer) {
                valorContainer.style.display = 'block';
            }
            if (obsContainer) {
                obsContainer.style.display = 'block';
                console.log('✏️ [editarEPPAgregado] Contenedor observaciones mostrado');
            }
            if (seccionFotos) {
                seccionFotos.style.display = 'block';
            }
            
            // Habilitar campos para edición
            if (cantidadInput) cantidadInput.disabled = false;
            if (obsInput) obsInput.disabled = false;
            if (valorUnitarioInput) valorUnitarioInput.disabled = false;
            console.log('✏️ [editarEPPAgregado] Campos habilitados');
            
            // Ocultar botón de agregar a lista
            const btnAgregar = document.getElementById('btnAgregarALista');
            if (btnAgregar) {
                btnAgregar.style.display = 'none';
                console.log('✏️ [editarEPPAgregado] Botón agregar a lista ocultado');
            }
            
            // Mostrar botón guardar cambios
            const btnGuardarCambios = document.getElementById('btnGuardarCambiosEPP');
            if (btnGuardarCambios) {
                btnGuardarCambios.style.display = 'flex';
                btnGuardarCambios.disabled = false;
                console.log('✏️ [editarEPPAgregado] Botón guardar cambios mostrado y habilitado');
            }
            
            // Ocultar botón finalizar
            const btnFinalizar = document.getElementById('btnFinalizarAgregarEPP');
            if (btnFinalizar) {
                btnFinalizar.style.display = 'none';
                btnFinalizar.disabled = true;
                console.log('✏️ [editarEPPAgregado] Botón finalizar ocultado');
            }
            
            // Ocultar tabla de EPPs agregados (solo en modo add, no en modo edición)
            const listaEPPAgregados = document.getElementById('listaEPPAgregados');
            if (listaEPPAgregados) {
                listaEPPAgregados.removeAttribute('style');
                listaEPPAgregados.style.setProperty('display', 'none', 'important');
                listaEPPAgregados.style.setProperty('visibility', 'hidden', 'important');
                console.log('✏️ [editarEPPAgregado] Tabla listaEPPAgregados ocultada');
            }
            
            // OCULTAR buscador en modo edición (no se busca, se edita un EPP existente)
            const buscadorSection = document.getElementById('buscadorEPPSection');
            if (buscadorSection) {
                buscadorSection.style.display = 'none';
                console.log('✏️ [editarEPPAgregado] Buscador ocultado en modo edición');
            }
            const formularioCrearEPP = document.getElementById('formularioCrearEPP');
            if (formularioCrearEPP) {
                formularioCrearEPP.style.display = 'none';
            }
            
            // MOSTRAR sección de fotos en modo edición
            const seccionFotosEPPEdit = document.getElementById('seccionFotosEPP');
            if (seccionFotosEPPEdit) {
                seccionFotosEPPEdit.style.display = 'block';
                console.log('✏️ [editarEPPAgregado] Sección de fotos habilitada');
            }
            
            // Abrir modal
            if (window.abrirModalAgregarEPP) {
                window.abrirModalAgregarEPP();
            }
            
            console.log('✏️ [editarEPPAgregado] FINALIZADO - Modal abierto en modo edición');
        };

        // Función para abrir modal de agregar EPP
        window.abrirModalAgregarEPP = function() {
            console.log('📖 [abrirModalAgregarEPP] Abriendo modal');
            const modal = document.getElementById('modalAgregarEPP');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            // IMPORTANTE: Obtener EPPs ya agregados en el formulario para filtrarlos
            if (typeof obtenerEPPsYaAgregadosEnFormulario === 'function') {
                obtenerEPPsYaAgregadosEnFormulario();
                console.log('📖 [abrirModalAgregarEPP] EPPs en formulario a excluir:', eppYaAgregadosEnFormulario);
            }

            // Solo resetear si no estamos en modo edición
            if (!window.eppEnEdicion) {
                console.log('📖 [abrirModalAgregarEPP] Modo normal - resetear modal');
                resetearModalAgregarEPP();
            } else {
                console.log('📖 [abrirModalAgregarEPP] Modo edición - NO resetear modal');
            }
        };

        // Función para cerrar modal de agregar EPP
        window.cerrarModalAgregarEPP = function() {
            console.log('📖 [cerrarModalAgregarEPP] Cerrando modal');
            const modal = document.getElementById('modalAgregarEPP');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            
            // Resetear estado de edición
            window.eppEnEdicion = null;
            
            // Resetear formulario
            resetearModalAgregarEPP();
        };

        // Función para resetear el modal
        function resetearModalAgregarEPP() {
            console.log('🔄 [resetearModalAgregarEPP] Resetear formulario');
            
            // CRÍTICO: Limpiar las listas globales del modal
            if (typeof eppAgregadosList !== 'undefined') {
                eppAgregadosList = [];
            }
            if (typeof eppDisponiblesList !== 'undefined') {
                eppDisponiblesList = [];
            }
            
            // CRÍTICO: Limpiar la tabla interna del modal (cuerpoTablaEPP)
            const cuerpoTabla = document.getElementById('cuerpoTablaEPP');
            if (cuerpoTabla) {
                cuerpoTabla.innerHTML = '';
                console.log('🔄 [resetearModalAgregarEPP] cuerpoTablaEPP limpiado');
            }
            
            // Limpiar buscador (nuevo formato con inputBuscadorEPPTabla)
            const buscadorTabla = document.getElementById('inputBuscadorEPPTabla');
            if (buscadorTabla) buscadorTabla.value = '';
            
            // Limpiar buscador antiguo por si existe
            const buscador = document.getElementById('inputBuscadorEPP');
            if (buscador) buscador.value = '';
            const resultados = document.getElementById('resultadosBuscadorEPP');
            if (resultados) resultados.style.display = 'none';
            
            // Limpiar dropdown
            const opcionesContainer = document.getElementById('opcionesDropdownEPP');
            if (opcionesContainer) opcionesContainer.innerHTML = '';
            const dropdown = document.getElementById('dropdownEPP');
            if (dropdown) dropdown.classList.add('hidden');
            
            // Mostrar mensaje sin resultados
            const mensajeSinResultados = document.getElementById('mensajeSinResultados');
            if (mensajeSinResultados) mensajeSinResultados.classList.remove('hidden');
            
            // Ocultar tarjeta de producto
            const tarjeta = document.getElementById('productoCardEPP');
            if (tarjeta) tarjeta.style.display = 'none';
            
            // Resetear campos
            const cantidadInput = document.getElementById('cantidadEPP');
            const obsInput = document.getElementById('observacionesEPP');
            const valorUnitarioInput = document.getElementById('valorUnitarioEPP');
            const totalInput = document.getElementById('totalEPP');
            
            if (cantidadInput) {
                cantidadInput.value = '1';
                cantidadInput.disabled = true;
            }
            if (obsInput) {
                obsInput.value = '';
                obsInput.disabled = true;
            }
            if (valorUnitarioInput) {
                valorUnitarioInput.value = '';
                valorUnitarioInput.disabled = true;
            }
            if (totalInput) {
                totalInput.value = '0';
            }
            
            // Ocultar contenedores
            const formularioContainer = document.getElementById('formularioAgregarEPP');
            const valorContainer = document.getElementById('valorUnitarioTotalContainer');
            const obsContainer = document.getElementById('observacionesContainer');
            
            if (formularioContainer) formularioContainer.style.display = 'none';
            if (valorContainer) valorContainer.style.display = 'none';
            if (obsContainer) obsContainer.style.display = 'none';
            
            // Resetear contadores
            const contadorEPP = document.getElementById('contadorEPP');
            if (contadorEPP) contadorEPP.textContent = '0';
            const totalSeleccionados = document.getElementById('totalSeleccionados');
            if (totalSeleccionados) totalSeleccionados.textContent = '0';
            
            // Resetear botones
            const btnAgregar = document.getElementById('btnAgregarALista');
            const btnFinalizar = document.getElementById('btnFinalizarAgregarEPP');
            const btnGuardarCambios = document.getElementById('btnGuardarCambiosEPP');
            
            if (btnAgregar) {
                btnAgregar.style.display = 'flex';
                btnAgregar.disabled = true;
            }
            if (btnFinalizar) {
                btnFinalizar.style.display = 'flex';
                btnFinalizar.disabled = true;
            }
            if (btnGuardarCambios) {
                btnGuardarCambios.style.display = 'none';
                btnGuardarCambios.disabled = true;
            }
            
            // Limpiar fotos
            const contenedorFotos = document.getElementById('contenedorFotosEPP');
            if (contenedorFotos) {
                const imagenes = contenedorFotos.querySelectorAll('.foto-epp-item');
                imagenes.forEach(img => img.remove());
            }
            
            // Resetear visibilidad de tabla EPPs agregados
            const listaEPPAgregados = document.getElementById('listaEPPAgregados');
            if (listaEPPAgregados) {
                if (!window.eppEnEdicion) {
                    console.log('🔄 [resetearModalAgregarEPP] Ocultando tabla (vacía - sin EPPs seleccionados)');
                    listaEPPAgregados.style.display = 'none';
                } else {
                    console.log('🔄 [resetearModalAgregarEPP] Manteniendo tabla oculta (modo edición)');
                    listaEPPAgregados.style.setProperty('display', 'none', 'important');
                    listaEPPAgregados.style.setProperty('visibility', 'hidden', 'important');
                }
            }
            
            // Resetear state manager
            if (window.eppStateManager && typeof window.eppStateManager.limpiarImagenes === 'function') {
                window.eppStateManager.limpiarImagenes();
            }
            
            // Restaurar visibilidad del buscador (oculto en modo edición)
            const buscadorSection = document.getElementById('buscadorEPPSection');
            if (buscadorSection) {
                buscadorSection.style.display = '';
            }
            
            // Ocultar sección de fotos (solo para modo edición)
            const seccionFotos = document.getElementById('seccionFotosEPP');
            if (seccionFotos) {
                seccionFotos.style.display = 'none';
            }
            
            // Limpiar fotosEPP array
            window.fotosEPP = [];
            
            // Restaurar mensaje drag-drop
            const mensajeDragDrop = document.getElementById('mensajeDragDrop');
            if (mensajeDragDrop) {
                mensajeDragDrop.style.display = 'flex';
            }
            
            // Resetear contador de fotos
            const contadorFotos = document.getElementById('contadorFotosEPP');
            if (contadorFotos) contadorFotos.textContent = '0';
            
            console.log('🔄 [resetearModalAgregarEPP] Reset COMPLETO');
        }

        // Función para manejar la subida de fotos de EPP
        window.manejarSubidaFotosEPP = function(input) {
            const archivos = input.files;
            const pedidoId = window.__EPP_COTIZACION_ID__ || 31;
            
            console.log(`📸 [manejarSubidaFotosEPP] Seleccionados ${archivos.length} archivos para el pedido ${pedidoId}`);
            console.log(`📸 [manejarSubidaFotosEPP] Estado de window.eppStateManager:`, !!window.eppStateManager);
            console.log(`📸 [manejarSubidaFotosEPP] Estado de window.fotosEPP:`, !!window.fotosEPP);
            
            Array.from(archivos).forEach((archivo, index) => {
                const nombreArchivo = archivo.name;
                const extension = nombreArchivo.split('.').pop().toLowerCase();
                
                // Validar que sea una imagen
                if (!['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'jfif'].includes(extension)) {
                    console.warn(`[manejarSubidaFotosEPP] Archivo no válido: ${nombreArchivo}`);
                    return;
                }
                
                // Crear URL blob para la imagen
                const previewUrl = URL.createObjectURL(archivo);
                
                // Crear objeto de imagen con URL blob
                const imagen = {
                    id: Date.now() + '_' + index,
                    file: archivo, // Mantener referencia al archivo original
                    previewUrl: previewUrl, // URL blob para mostrar
                    nombre: nombreArchivo,
                    extension: extension,
                    tamaño: archivo.size,
                    pedido_epp_id: null, // Se asignará al guardar
                    ruta_original: null,
                    ruta_webp: null,
                    principal: 0,
                    orden: 0
                };
                
                console.log(`📸 [manejarSubidaFotosEPP] Procesando imagen:`, {
                    id: imagen.id,
                    nombre: imagen.nombre,
                    tamaño: imagen.tamaño
                });
                
                // Agregar al stateManager si existe, sino a array temporal
                if (window.eppStateManager && typeof window.eppStateManager.agregarImagen === 'function') {
                    console.log(`📸 [manejarSubidaFotosEPP] Usando stateManager.agregarImagen`);
                    window.eppStateManager.agregarImagen(imagen);
                    console.log(`📸 [manejarSubidaFotosEPP] Imágenes en stateManager después de agregar:`, window.eppStateManager.getImagenesSubidas()?.length || 0);
                } else {
                    // Array temporal como fallback
                    console.log(`📸 [manejarSubidaFotosEPP] Usando array temporal window.fotosEPP`);
                    if (!window.fotosEPP) window.fotosEPP = [];
                    window.fotosEPP.push(imagen);
                    console.log(`📸 [manejarSubidaFotosEPP] Imágenes en array temporal después de agregar:`, window.fotosEPP.length);
                }
                
                // Mostrar vista previa
                mostrarVistaPreviaFotoEPP(imagen);
                
                console.log(`📸 [manejarSubidaFotosEPP] Foto agregada: ${nombreArchivo} (${(archivo.size / 1024).toFixed(2)} KB)`);
            });
            
            // Limpiar input para permitir seleccionar el mismo archivo nuevamente
            input.value = '';
        };

        // Función para mostrar vista previa de foto
        function mostrarVistaPreviaFotoEPP(imagen) {
            const contenedor = document.getElementById('contenedorFotosEPP');
            if (!contenedor) return;
            
            // Ocultar mensaje inicial si está visible
            const mensajeDragDrop = document.getElementById('mensajeDragDrop');
            if (mensajeDragDrop) {
                mensajeDragDrop.style.display = 'none';
            }
            
            const fotoElement = document.createElement('div');
            fotoElement.className = 'relative group foto-epp-item';
            fotoElement.setAttribute('data-foto-id', imagen.id);
            
            fotoElement.innerHTML = `
                <div class="relative overflow-hidden rounded-lg border-2 border-gray-200">
                    <img src="${imagen.previewUrl}" alt="Foto EPP" class="w-full h-32 object-cover">
                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <button type="button" onclick="eliminarFotoEPPCotizacion('${imagen.id}')" class="bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                            <i class="material-symbols-rounded text-sm">delete</i>
                        </button>
                    </div>
                    <div class="absolute bottom-0 right-0 bg-blue-600 text-white text-xs px-2 py-1 rounded-tl">
                        ${imagen.nombre}
                    </div>
                </div>
            `;
            
            contenedor.appendChild(fotoElement);
        }

        // Función para eliminar foto (cotización - diferente de la de la tabla EPP)
        window.eliminarFotoEPPCotizacion = function(fotoId) {
            console.log(`🗑️ [eliminarFotoEPPCotizacion] Eliminando foto: ${fotoId}`);
            
            // Eliminar del DOM
            const fotoElement = document.querySelector(`[data-foto-id="${fotoId}"]`);
            if (fotoElement) {
                fotoElement.remove();
            }
            
            // Eliminar del stateManager si existe
            if (window.eppStateManager && typeof window.eppStateManager.eliminarImagen === 'function') {
                window.eppStateManager.eliminarImagen(fotoId);
            } else {
                // Eliminar del array temporal
                if (window.fotosEPP) {
                    window.fotosEPP = window.fotosEPP.filter(img => img.id !== fotoId);
                }
            }
            
            // Mostrar mensaje inicial si no hay más fotos
            const contenedor = document.getElementById('contenedorFotosEPP');
            if (contenedor) {
                const fotosRestantes = contenedor.querySelectorAll('.foto-epp-item');
                if (fotosRestantes.length === 0) {
                    const mensajeDragDrop = document.getElementById('mensajeDragDrop');
                    if (mensajeDragDrop) {
                        mensajeDragDrop.style.display = 'flex';
                    }
                }
            }
        };

        // Función para agregar foto (botón)
        window.agregarFotoEPP = function() {
            const input = document.getElementById('inputFotosEPP');
            if (input) {
                input.click();
            }
        };

        if (typeof window.guardarEdicionEPP === 'function' && !window.__eppCotizacionGuardarEdicionWrapped) {
            const original = window.guardarEdicionEPP;
            window.guardarEdicionEPP = function (...args) {
                const result = original.apply(this, args);
                syncEmptyState();
                // syncTotales se dispara automáticamente via eppStore.onChange
                return result;
            };
            window.__eppCotizacionGuardarEdicionWrapped = true;
        }

        const list = document.querySelector(listSelector);
        if (list && typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(syncEmptyState);
            observer.observe(list, { childList: true, subtree: true, characterData: true });
        }

        // MutationObserver para totales: detecta cambios de prendas (que no pasan por eppStore)
        const listTot = document.querySelector(listSelector);
        if (listTot && typeof MutationObserver !== 'undefined') {
            let totDebounce = null;
            const observerTot = new MutationObserver(function() {
                clearTimeout(totDebounce);
                totDebounce = setTimeout(syncTotales, 150);
            });
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
