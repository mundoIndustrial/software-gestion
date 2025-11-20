@extends('asesores.layout')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones')

@push('styles')
<style>
    /* Estilos personalizados para SweetAlert2 en create-friendly */
    .swal-custom-popup {
        width: 90% !important;
        max-width: 380px !important;
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
        font-size: 1rem !important;
        color: #4b5563 !important;
        line-height: 1.6 !important;
    }
    
    .swal2-icon {
        width: 40px !important;
        height: 40px !important;
        margin: 0 auto 16px !important;
    }
    
    .swal2-icon.swal2-question {
        border-color: #f59e0b !important;
        color: #f59e0b !important;
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
        background-color: #10b981 !important;
        color: white !important;
    }
    
    .swal-custom-confirm:hover {
        background-color: #059669 !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3) !important;
    }
    
    .swal-custom-cancel {
        background-color: #d1d5db !important;
        color: #374151 !important;
        margin-right: 8px !important;
    }
    
    .swal-custom-cancel:hover {
        background-color: #9ca3af !important;
        transform: translateY(-2px) !important;
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
    
    @media (max-width: 640px) {
        .swal-custom-popup {
            width: 95% !important;
            max-width: 320px !important;
            padding: 20px !important;
        }
        
        .swal-custom-title {
            font-size: 1.1rem !important;
        }
        
        .swal2-html-container {
            font-size: 0.95rem !important;
        }
    }
</style>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/create-friendly.css') }}">
<style>
    .imagen-preview {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: 6px;
        overflow: hidden;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .imagen-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .imagen-preview .btn-eliminar-imagen {
        position: absolute;
        top: 2px;
        right: 2px;
        background: #f44336;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        cursor: pointer;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .imagen-preview:hover .btn-eliminar-imagen {
        opacity: 1;
    }
</style>
@endpush

@section('content')

<!-- Script para almacenar imágenes en memoria -->
<script>
    // Variables globales para almacenar imágenes en memoria
    window.imagenesEnMemoria = {
        prenda: [],
        tela: [],
        general: []
    };
    
    console.log('🔵 Sistema de imágenes en memoria inicializado');
</script>

<div class="friendly-form-fullscreen">
    <!-- TÍTULO PRINCIPAL -->
    <div style="text-align: center; margin-bottom: 15px; padding: 10px 0; border-bottom: 2px solid #3498db;">
        <h1 style="margin: 0; font-size: 1.5rem; color: #333; font-weight: bold;">COTIZACIONES</h1>
        <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Crea una nueva cotización para tu cliente</p>
    </div>

    <!-- STEPPER VISUAL - CLICKEABLE -->
    <div class="stepper-container">
        <div class="stepper">
            <div class="step active" data-step="1" onclick="irAlPaso(1)" onkeypress="if(event.key==='Enter') irAlPaso(1)" tabindex="0" role="tab" aria-selected="true" style="cursor: pointer;">
                <div class="step-number">1</div>
                <div class="step-label">CLIENTE</div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="2" onclick="irAlPaso(2)" onkeypress="if(event.key==='Enter') irAlPaso(2)" tabindex="0" role="tab" aria-selected="false" style="cursor: pointer;">
                <div class="step-number">2</div>
                <div class="step-label">PRENDAS</div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="3" onclick="irAlPaso(3)" onkeypress="if(event.key==='Enter') irAlPaso(3)" tabindex="0" role="tab" aria-selected="false" style="cursor: pointer;">
                <div class="step-number">3</div>
                <div class="step-label">BORDADO/ESTAMPADO <span style="font-size: 0.7rem; font-weight: normal;">(OPCIONAL)</span></div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="4" onclick="irAlPaso(4)" onkeypress="if(event.key==='Enter') irAlPaso(4)" tabindex="0" role="tab" aria-selected="false" style="cursor: pointer;">
                <div class="step-number">4</div>
                <div class="step-label">REVISAR</div>
            </div>
        </div>
    </div>

    <!-- FORMULARIO PASO A PASO -->
    <form id="formCrearPedidoFriendly" class="friendly-form">
        @csrf

        <!-- PASO 1: INFORMACIÓN DEL CLIENTE -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <h2>PASO 1: INFORMACIÓN DEL CLIENTE</h2>
                <p>CUÉNTANOS QUIÉN ES TU CLIENTE</p>
            </div>

            <!-- INFORMACIÓN DE LA ASESORA Y FECHA -->
            <div style="background: #f0f7ff; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <p style="margin: 0; font-size: 0.9rem; color: #666;">
                            <strong>{{ Auth::user()->genero === 'F' ? 'ASESORA COMERCIAL' : 'ASESOR COMERCIAL' }}:</strong>
                            {{ Auth::user()->name }}
                        </p>
                    </div>
                    <div>
                        <p style="margin: 0; font-size: 0.9rem; color: #666;">
                            <strong>FECHA:</strong>
                            <span id="fechaActual"></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-group-large">
                    <label for="cliente">
                        <i class="fas fa-user"></i>
                        NOMBRE DEL CLIENTE *
                    </label>
                    <input type="text" id="cliente" name="cliente" class="input-large" placeholder="EJ: JUAN GARCÍA, EMPRESA ABC..." required>
                    <small class="help-text">EL NOMBRE DE TU CLIENTE O EMPRESA</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-next" onclick="irAlPaso(2)">
                    SIGUIENTE <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- PASO 2: AGREGAR PRENDAS -->
        <div class="form-step" data-step="2">
            <div class="step-header">
                <h2>PASO 2: PRENDAS DEL PEDIDO</h2>
                <p>AGREGA LAS PRENDAS QUE TU CLIENTE QUIERE</p>
            </div>

            <!-- BOTONES DE ACCIÓN AL INICIO -->
            <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                <button type="button" class="btn-add-product-friendly" onclick="agregarProductoFriendly()" style="flex: 1; min-width: 200px;">
                    <i class="fas fa-plus-circle"></i> AGREGAR PRENDA
                </button>
                <button type="button" class="btn-add-product-friendly" onclick="abrirModalEspecificaciones()" style="flex: 1; min-width: 200px;">
                    <i class="fas fa-clipboard-check"></i> ESPECIFICACIONES DE LA ORDEN
                </button>
            </div>

            <!-- TIPO DE COTIZACIÓN DE PRENDAS -->
            <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: center; gap: 1rem;">
                <label for="cotizar_segun_indicaciones" style="font-weight: 700; font-size: 1rem; color: #333; white-space: nowrap;">
                    <i class="fas fa-tag"></i> Favor cotizar según indicaciones
                </label>
                <select id="cotizar_segun_indicaciones" name="cotizar_segun_indicaciones" style="width: 120px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; cursor: pointer; background-color: white; text-align: center;">
                    <option value="">Selecciona</option>
                    <option value="M">M</option>
                    <option value="D">D</option>
                    <option value="X">X</option>
                </select>
            </div>

            <div class="form-section">
                <div class="productos-container" id="productosContainer"></div>
            </div>

            <!-- MODAL: ESPECIFICACIONES DE LA ORDEN -->
            <div id="modalEspecificaciones" class="modal-especificaciones" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
                <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 900px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 2px solid #ffc107; padding-bottom: 1rem;">
                        <h3 style="margin: 0; color: #333; font-size: 1.3rem;"><i class="fas fa-clipboard-check"></i> ESPECIFICACIONES DE LA ORDEN</h3>
                        <button type="button" onclick="cerrarModalEspecificaciones()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <table class="tabla-control-compacta">
                        <thead>
                            <tr>
                                <th style="width: 30%; text-align: left;"></th>
                                <th style="width: 15%; text-align: center;">SELECCIONAR</th>
                                <th style="width: 55%; text-align: left;">OBSERVACIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DISPONIBILIDAD -->
                            <tr class="fila-grupo">
                                <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span>📦 DISPONIBILIDAD</span>
                                        <button type="button" onclick="agregarFilaEspecificacion('disponibilidad')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tbody id="tbody_disponibilidad">
                                <tr>
                                    <td><label style="margin: 0; font-size: 0.8rem;">Bodega</label></td>
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                                    </td>
                                    <td style="display: flex; gap: 5px;">
                                        <input type="text" name="tabla_orden[bodega_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                                    </td>
                                </tr>
                                <tr>
                                    <td><label style="margin: 0; font-size: 0.8rem;">Cúcuta</label></td>
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                                    </td>
                                    <td style="display: flex; gap: 5px;">
                                        <input type="text" name="tabla_orden[cucuta_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                                    </td>
                                </tr>
                                <tr>
                                    <td><label style="margin: 0; font-size: 0.8rem;">Lafayette</label></td>
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                                    </td>
                                    <td style="display: flex; gap: 5px;">
                                        <input type="text" name="tabla_orden[lafayette_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                                    </td>
                                </tr>
                                <tr>
                                    <td><label style="margin: 0; font-size: 0.8rem;">Fábrica</label></td>
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                                    </td>
                                    <td style="display: flex; gap: 5px;">
                                        <input type="text" name="tabla_orden[fabrica_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                                    </td>
                                </tr>
                            </tbody>

                            <!-- PAGO -->
                            <tr class="fila-grupo">
                                <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span>💳 FORMA DE PAGO</span>
                                        <button type="button" onclick="agregarFilaEspecificacion('pago')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tbody id="tbody_pago">
                                <tr>
                                    <td><label style="margin: 0; font-size: 0.8rem;">Contado</label></td>
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                                    </td>
                                    <td style="display: flex; gap: 5px;">
                                        <input type="text" name="tabla_orden[pago_contado_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                                    </td>
                                </tr>
                                <tr>
                                    <td><label style="margin: 0; font-size: 0.8rem;">Crédito</label></td>
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                                    </td>
                                    <td style="display: flex; gap: 5px;">
                                        <input type="text" name="tabla_orden[pago_credito_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                                    </td>
                                </tr>
                            </tbody>

                            <!-- RÉGIMEN -->
                            <tr class="fila-grupo">
                                <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span>🏛️ RÉGIMEN</span>
                                        <button type="button" onclick="agregarFilaEspecificacion('regimen')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tbody id="tbody_regimen">
                                <tr>
                                    <td><label style="margin: 0; font-size: 0.8rem;">Común</label></td>
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                                    </td>
                                    <td style="display: flex; gap: 5px;">
                                        <input type="text" name="tabla_orden[regimen_comun_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                                    </td>
                                </tr>
                                <tr>
                                    <td><label style="margin: 0; font-size: 0.8rem;">Simplificado</label></td>
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                                    </td>
                                    <td style="display: flex; gap: 5px;">
                                        <input type="text" name="tabla_orden[regimen_simp_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                                    </td>
                                </tr>
                            </tbody>

                            <!-- SE HA VENDIDO -->
                            <tr class="fila-grupo">
                                <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span>📊 SE HA VENDIDO</span>
                                        <button type="button" onclick="agregarFilaEspecificacion('vendido')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tbody id="tbody_vendido">
                                <tr>
                                    <td><input type="text" name="tabla_orden[vendido_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                                    </td>
                                    <td style="display: flex; gap: 5px;">
                                        <input type="text" name="tabla_orden[vendido_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                                    </td>
                                </tr>
                            </tbody>

                            <!-- ÚLTIMA VENTA -->
                            <tr class="fila-grupo">
                                <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span>💰 ÚLTIMA VENTA</span>
                                        <button type="button" onclick="agregarFilaEspecificacion('ultima_venta')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tbody id="tbody_ultima_venta">
                                <tr>
                                    <td><input type="text" name="tabla_orden[ultima_venta_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                                    </td>
                                    <td style="display: flex; gap: 5px;">
                                        <input type="text" name="tabla_orden[ultima_venta_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                                    </td>
                                </tr>
                            </tbody>

                            <!-- FLETE DE ENVÍO -->
                            <tr class="fila-grupo">
                                <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span>🚚 FLETE DE ENVÍO</span>
                                        <button type="button" onclick="agregarFilaEspecificacion('flete')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tbody id="tbody_flete">
                                <tr>
                                    <td><input type="text" name="tabla_orden[flete_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                                    </td>
                                    <td style="display: flex; gap: 5px;">
                                        <input type="text" name="tabla_orden[flete_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                                    </td>
                                </tr>
                            </tbody>
                        </tbody>
                    </table>

                    <!-- Footer -->
                    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid #ffc107; display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" onclick="cerrarModalEspecificaciones()" style="padding: 0.6rem 1.5rem; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 600; color: #333;">
                            CANCELAR
                        </button>
                        <button type="button" onclick="guardarEspecificaciones()" style="padding: 0.6rem 1.5rem; background: #0066cc; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: white;">
                            GUARDAR
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-prev" onclick="irAlPaso(1)">
                    <i class="fas fa-arrow-left"></i> ANTERIOR
                </button>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-next" onclick="irAlPaso(4)" style="background: #95a5a6;">
                        SALTAR <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="button" class="btn-next" onclick="irAlPaso(3)">
                        SIGUIENTE <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- PASO 3: BORDADO/ESTAMPADO -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h2>PASO 3: BORDADO/ESTAMPADO</h2>
                <p>ESPECIFICA LOS DETALLES DE BORDADO Y ESTAMPADO</p>
            </div>

            <div class="form-section">
                <!-- IMÁGENES (5 máximo) - DRAG AND DROP -->
                <div class="form-group-large">
                    <label for="imagenes_bordado">
                        <i class="fas fa-images"></i>
                        IMÁGENES (MÁXIMO 5)
                    </label>
                    <div id="drop_zone_imagenes" style="border: 2px dashed #3498db; border-radius: 8px; padding: 30px; text-align: center; background: #f0f7ff; cursor: pointer; transition: all 0.3s ease; margin-bottom: 10px;">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: #3498db; margin-bottom: 10px; display: block;"></i>
                        <p style="margin: 10px 0; color: #3498db; font-weight: 600;">ARRASTRA IMÁGENES AQUÍ O HAZ CLIC</p>
                        <p style="margin: 5px 0; color: #666; font-size: 0.9rem;">Máximo 5 imágenes</p>
                        <input type="file" id="imagenes_bordado" name="imagenes_bordado[]" accept="image/*" multiple style="display: none;">
                    </div>
                    <div id="galeria_imagenes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 10px;">
                    </div>
                </div>

                <!-- TARJETA: TÉCNICAS Y OBSERVACIONES -->
                <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">
                            Logo personalizado: Técnicas disponibles
                        </label>
                        <button type="button" onclick="agregarTecnica()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
                    </div>
                    
                    <!-- Selector de técnicas -->
                    <select id="selector_tecnicas" class="input-large" style="width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">-- SELECCIONA UNA TÉCNICA --</option>
                        <option value="BORDADO">BORDADO</option>
                        <option value="DTF">DTF</option>
                        <option value="ESTAMPADO">ESTAMPADO</option>
                        <option value="SUBLIMADO">SUBLIMADO</option>
                    </select>
                    
                    <!-- Técnicas seleccionadas -->
                    <div id="tecnicas_seleccionadas" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; min-height: 30px;">
                    </div>
                    
                    <!-- Observaciones -->
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">
                        Observaciones
                    </label>
                    <textarea id="observaciones_tecnicas" name="observaciones_tecnicas" class="input-large" rows="2" placeholder="Observaciones..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;"></textarea>
                </div>

                <!-- TARJETA: UBICACIÓN POR SECCIÓN -->
                <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">
                            Ubicación
                        </label>
                        <button type="button" onclick="agregarSeccion()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
                    </div>
                    
                    <!-- Selector de sección -->
                    <label for="seccion_prenda" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Selecciona la sección a agregar:</label>
                    <select id="seccion_prenda" class="input-large" style="width: 100%; margin-bottom: 12px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">-- SELECCIONA UNA OPCIÓN --</option>
                        <option value="CAMISA">CAMISA</option>
                        <option value="JEAN_SUDADERA">JEAN/SUDADERA</option>
                        <option value="GORRAS">GORRAS</option>
                    </select>
                    
                    <!-- SECCIONES AGREGADAS -->
                    <div id="secciones_agregadas" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    </div>
                </div>

                <!-- TARJETA: OBSERVACIONES GENERALES -->
                <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">
                            Observaciones Generales
                        </label>
                        <button type="button" onclick="agregarObservacion()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
                    </div>
                    
                    <!-- Observaciones agregadas -->
                    <div id="observaciones_lista" style="display: flex; flex-direction: column; gap: 10px;">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-prev" onclick="irAlPaso(2)">
                    <i class="fas fa-arrow-left"></i> ANTERIOR
                </button>
                <button type="button" class="btn-next" onclick="irAlPaso(4)">
                    REVISAR <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- PASO 4: REVISAR Y CONFIRMAR -->
        <div class="form-step" data-step="4">
            <div class="step-header">
                <h2>PASO 4: REVISAR COTIZACIÓN</h2>
                <p>VERIFICA QUE TODO ESTÉ CORRECTO ANTES DE GUARDAR O ENVIAR</p>
            </div>

            <div class="form-section">
                <div style="background: #f0f7ff; border-left: 4px solid #3498db; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #333;">
                        <strong>✓ Resumen de tu cotización:</strong>
                    </p>
                    <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #666;">
                        <li>Cliente: <strong id="resumenCliente">-</strong></li>
                        <li>Productos agregados: <strong id="resumenProductos">0</strong></li>
                        <li>Asesora: <strong>{{ Auth::user()->name }}</strong></li>
                        <li>Fecha: <strong id="resumenFecha"></strong></li>
                    </ul>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-prev" onclick="irAlPaso(3)">
                    <i class="fas fa-arrow-left"></i> ANTERIOR
                </button>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-submit" onclick="guardarCotizacion()" style="background: #95a5a6;">
                        <i class="fas fa-save"></i> GUARDAR (BORRADOR)
                    </button>
                    <button type="button" class="btn-submit" onclick="enviarCotizacion()">
                        <i class="fas fa-paper-plane"></i> ENVIAR
                    </button>
                </div>
            </div>
        </div>

        <!-- PASO 4: REVISAR Y CONFIRMAR - COMENTADO -->
        <!-- 
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h2>PASO 3: REVISAR TU PEDIDO</h2>
                <p>VERIFICA QUE TODO ESTÉ CORRECTO ANTES DE CREAR</p>
            </div>

            <div class="form-section erp-review-section">
                <!-- ENCABEZADO FACTURA
                <div class="factura-header">
                    <div class="factura-titulo">
                        <h2>PEDIDO DE COMPRA</h2>
                        <p>Resumen y confirmación del pedido</p>
                    </div>
                    <div class="factura-datos">
                        <div class="factura-item">
                            <span class="factura-label">NÚMERO:</span>
                            <span class="factura-valor">NUEVO</span>
                        </div>
                        <div class="factura-item">
                            <span class="factura-label">FECHA:</span>
                            <span class="factura-valor" id="reviewFecha"></span>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN: INFORMACIÓN DEL CLIENTE
                <div class="factura-seccion">
                    <div class="factura-seccion-titulo">
                        <i class="fas fa-user-tie"></i> CLIENTE
                    </div>
                    <div class="factura-cliente-info">
                        <div class="factura-item-row">
                            <span class="factura-label">Nombre:</span>
                            <span class="factura-valor" id="reviewCliente">-</span>
                        </div>
                        <div class="factura-item-row">
                            <span class="factura-label">Forma de Pago:</span>
                            <span class="factura-valor" id="reviewFormaPago">-</span>
                        </div>
                    </div>
                </div>

                <!-- TABLA PRINCIPAL - PRODUCTOS CON DETALLES
                <div class="factura-seccion">
                    <div class="factura-seccion-titulo">
                        <i class="fas fa-list"></i> DETALLE DE PRENDAS
                    </div>
                    <div class="productos-factura-container" id="productosFacturaContainer">
                        <!-- Se llena dinámicamente con JavaScript
                    </div>
                </div>

                <!-- RESUMEN FINAL
                <div class="factura-resumen">
                    <div class="resumen-item">
                        <span class="resumen-label">TOTAL PRENDAS</span>
                        <span class="resumen-valor" id="reviewProductosCount">0</span>
                    </div>
                    <div class="resumen-item highlight">
                        <span class="resumen-label">TOTAL PRENDAS</span>
                        <span class="resumen-valor" id="reviewCantidadTotal">0</span>
                    </div>
                </div>

                <!-- OBSERVACIONES
                <div class="factura-seccion">
                    <div class="factura-seccion-titulo">
                        <i class="fas fa-sticky-note"></i> OBSERVACIONES
                    </div>
                    <div class="factura-observaciones" id="reviewObservaciones">
                        <p style="color: #999; font-style: italic;">Sin observaciones adicionales.</p>
                    </div>
                </div>

                <!-- PIE FACTURA
                <div class="factura-pie">
                    <div class="info-box success">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>¡PEDIDO LISTO PARA CREAR!</strong>
                            <p>Revisa toda la información. Si todo es correcto, haz clic en "CREAR PEDIDO" para guardar.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-prev" onclick="irAlPaso(2)">
                    <i class="fas fa-arrow-left"></i> ANTERIOR
                </button>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-check"></i> CREAR PEDIDO
                </button>
            </div>
        </div>
        -->
    </form>
</div>

<!-- TEMPLATE PARA PRODUCTO -->
<template id="productoTemplate">
    <div class="producto-card" data-producto-id="">
        <div class="producto-header">
            <h4 class="producto-titulo">PRENDA <span class="numero-producto">1</span></h4>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn-toggle-product" onclick="toggleProductoBody(this)" title="Expandir/Contraer" style="font-size: 1.5rem; line-height: 1; font-weight: bold;">
                    ▼
                </button>
                <button type="button" class="btn-remove-product" onclick="eliminarProductoFriendly(this)" title="Eliminar prenda">
                    &times;
                </button>
            </div>
        </div>
        <div class="producto-body" style="display: block;">
            <!-- SECCIÓN 1: Tipo de Prenda -->
            <div class="producto-section">
                <div class="section-title">
                    <i class="fas fa-shirt"></i> PASO 1: TIPO DE PRENDA
                </div>
                <div class="form-row">
                    <div class="form-col full">
                        <label title="Tipo de prenda"><i class="fas fa-list"></i> SELECCIONA O ESCRIBE EL TIPO *</label>
                        <div class="prenda-search-container">
                            <input type="text" name="productos_friendly[][nombre_producto]" class="prenda-search-input input-large" placeholder="BUSCA O ESCRIBE (CAMISA, CAMISETA, POLO...)" title="Tipo de prenda" required onkeyup="buscarPrendas(this)" onchange="actualizarResumenFriendly()">
                            <div class="prenda-suggestions">
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👔 CAMISA', this)">👔 CAMISA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👕 CAMISETA', this)">👕 CAMISETA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🎽 POLO', this)">🎽 POLO</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👖 PANTALÓN', this)">👖 PANTALÓN</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👗 FALDA', this)">👗 FALDA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🧥 CHAQUETA', this)">🧥 CHAQUETA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🧢 SUDADERA', this)">🧢 SUDADERA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('� OTRO', this)">� OTRO</div>
                            </div>
                        </div>
                        <small class="help-text">PUEDES BUSCAR, SELECCIONAR O ESCRIBIR UNA PRENDA PERSONALIZADA</small>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: DESCRIPCIÓN -->
            <div class="producto-section">
                <div class="section-title">
                    <i class="fas fa-sticky-note"></i> PASO 2: DESCRIPCIÓN
                </div>
                <div class="form-row">
                    <div class="form-col full">
                        <label><i class="fas fa-pen"></i> DESCRIPCIÓN</label>
                        <textarea name="productos_friendly[][descripcion]" class="input-medium" placeholder="DESCRIPCIÓN DE LA PRENDA..." rows="2"></textarea>
                        <small class="help-text">DESCRIBE LA PRENDA, DETALLES ESPECIALES, LOGO, BORDADO, ESTAMPADO, ETC.</small>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2.5: TALLAS -->
            <div class="producto-section">
                <div class="section-title">
                    <i class="fas fa-ruler"></i> TALLAS A COTIZAR
                </div>
                <div class="form-row">
                    <div class="form-col full">
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                            <label style="display: flex; align-items: center; gap: 0.3rem; cursor: pointer;">
                                <input type="checkbox" name="productos_friendly[][tallas]" value="S" style="width: 18px; height: 18px; cursor: pointer;">
                                <span>S</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.3rem; cursor: pointer;">
                                <input type="checkbox" name="productos_friendly[][tallas]" value="M" style="width: 18px; height: 18px; cursor: pointer;">
                                <span>M</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.3rem; cursor: pointer;">
                                <input type="checkbox" name="productos_friendly[][tallas]" value="L" style="width: 18px; height: 18px; cursor: pointer;">
                                <span>L</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.3rem; cursor: pointer;">
                                <input type="checkbox" name="productos_friendly[][tallas]" value="XL" style="width: 18px; height: 18px; cursor: pointer;">
                                <span>XL</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.3rem; cursor: pointer;">
                                <input type="checkbox" name="productos_friendly[][tallas]" value="XXL" style="width: 18px; height: 18px; cursor: pointer;">
                                <span>XXL</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.3rem; cursor: pointer; font-weight: 700; color: #0066cc; margin-left: 1rem;">
                                <input type="checkbox" name="productos_friendly[][tallas]" value="TODAS" style="width: 18px; height: 18px; cursor: pointer;">
                                <span>TODAS</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.3rem; cursor: pointer; font-weight: 700; color: #f59e0b;">
                                <input type="checkbox" name="productos_friendly[][tallas]" value="NO APLICA" style="width: 18px; height: 18px; cursor: pointer;">
                                <span>NO APLICA</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: Fotos de la Prenda -->
            <div class="producto-section">
                <button type="button" class="section-title-btn" onclick="toggleSeccion(this)">
                    <div class="section-title">
                        <i class="fas fa-images"></i> PASO 3: FOTOS DE LA PRENDA (MÁX. 3)
                        <i class="fas fa-chevron-down" style="margin-left: auto; transition: transform 0.3s ease;"></i>
                    </div>
                </button>
                <div class="section-content">
                <!-- Grid de 2 columnas para las fotos -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
                    <!-- Fotos de la Prenda -->
                    <div>
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #0066cc; font-size: 0.85rem;">
                            <i class="fas fa-image"></i> FOTOS PRENDA
                        </label>
                        <label style="display: block; min-height: 80px; padding: 0.75rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="manejarDrop(event)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')">
                            <input type="file" name="productos_friendly[][fotos][]" class="input-file-single" accept="image/*" multiple onchange="agregarFotos(this.files, this.closest('label').nextElementSibling)" style="display: none;">
                            <div class="drop-zone-content" style="font-size: 0.75rem;">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 1rem; color: #0066cc;"></i>
                                <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">ARRASTRA O CLIC</p>
                            </div>
                        </label>
                        <div class="fotos-preview" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.4rem; margin-top: 0.5rem;"></div>
                    </div>

                    <!-- Imagen de Tela -->
                    <div>
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #0066cc; font-size: 0.85rem;">
                            <i class="fas fa-fiber-manual-record"></i> TELA
                        </label>
                        <label style="display: block; min-height: 80px; padding: 0.75rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="manejarDrop(event)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')">
                            <input type="file" name="productos_friendly[][imagen_tela]" class="input-file-single" accept="image/*" onchange="agregarFotoTela(this)" style="display: none;">
                            <div class="drop-zone-content" style="font-size: 0.75rem;">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 1rem; color: #0066cc;"></i>
                                <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">ARRASTRA O CLIC</p>
                            </div>
                        </label>
                        <div class="foto-tela-preview" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.4rem; margin-top: 0.5rem;"></div>
                    </div>
                </div>
                </div>
            </div>

        </div>
    </div>
</template>

@push('scripts')
<script>
// Ocultar navbar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    const topNav = document.querySelector('.top-nav');
    if (topNav) {
        topNav.style.display = 'none';
    }
    
    // Ocultar también la barra de navegación secundaria (page-header)
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) {
        pageHeader.style.display = 'none';
    }
});

// Mostrar navbar cuando se vuelve a la lista
window.addEventListener('beforeunload', function() {
    const topNav = document.querySelector('.top-nav');
    if (topNav) {
        topNav.style.display = '';
    }
    
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) {
        pageHeader.style.display = '';
    }
});

let productosCount = 0;

// Ir al paso especificado (sin validación - libre navegación)
function irAlPaso(paso) {
    // Ocultar todos los pasos
    document.querySelectorAll('.form-step').forEach(step => {
        step.classList.remove('active');
    });

    // Mostrar paso seleccionado
    const formStep = document.querySelector(`.form-step[data-step="${paso}"]`);
    if (formStep) {
        formStep.classList.add('active');
    }

    // Actualizar stepper
    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active');
    });
    const stepElement = document.querySelector(`.step[data-step="${paso}"]`);
    if (stepElement) {
        stepElement.classList.add('active');
    }

    // Si es el paso 4 (revisar), actualizar resumen
    if (paso === 4) {
        setTimeout(() => actualizarResumenFriendly(), 100);
    }
}

// Agregar producto
function agregarProductoFriendly() {
    productosCount++;
    const template = document.getElementById('productoTemplate');
    const clone = template.content.cloneNode(true);

    // Actualizar número de prenda
    clone.querySelector('.numero-producto').textContent = productosCount;
    
    // Asignar ID único al producto
    const productoId = 'producto-' + Date.now() + '-' + productosCount;
    clone.querySelector('.producto-card').dataset.productoId = productoId;
    
    // Inicializar array de fotos para este producto
    fotosSeleccionadas[productoId] = [];

    // Agregar al contenedor
    document.getElementById('productosContainer').appendChild(clone);
}

// Eliminar producto
function eliminarProductoFriendly(btn) {
    btn.closest('.producto-card').remove();
    actualizarResumenFriendly();
}

// Almacenar fotos seleccionadas
let fotosSeleccionadas = {};

// Manejar drag & drop
function manejarDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const dropZone = event.currentTarget;
    dropZone.classList.remove('drag-over');
    
    const files = event.dataTransfer.files;
    agregarFotos(files, dropZone);
}

// Agregar fotos
function agregarFotos(files, dropZone) {
    const productoCard = dropZone.closest('.producto-card');
    const productoId = productoCard ? productoCard.dataset.productoId : 'default';
    
    if (!fotosSeleccionadas[productoId]) {
        fotosSeleccionadas[productoId] = [];
    }
    
    console.log('📁 Agregando fotos de prenda a memoria');
    
    // Agregar nuevas fotos (máximo 3 total)
    Array.from(files).forEach(file => {
        if (fotosSeleccionadas[productoId].length < 3) {
            fotosSeleccionadas[productoId].push(file);
            
            // Guardar en memoria
            window.imagenesEnMemoria.prenda.push(file);
            console.log(`✅ Foto de prenda guardada en memoria: ${file.name}`);
        }
    });
    
    if (Array.from(files).length > 3 - fotosSeleccionadas[productoId].length + Array.from(files).length) {
        alert('Máximo 3 fotos permitidas.');
    }
    
    // Actualizar preview
    actualizarPreviewFotos(dropZone);
}

// Actualizar preview de fotos
function actualizarPreviewFotos(input) {
    const productoCard = input.closest('.producto-card');
    if (!productoCard) return;
    
    const productoId = productoCard.dataset.productoId || 'default';
    
    // Buscar el contenedor de preview
    let container = null;
    
    // Intentar encontrar el preview más cercano
    const label = input.closest('label');
    if (label && label.parentElement) {
        container = label.parentElement.querySelector('.fotos-preview');
    }
    
    // Si no lo encuentra, buscar en toda la tarjeta
    if (!container) {
        container = productoCard.querySelector('.fotos-preview');
    }
    
    if (!container) return;
    
    container.innerHTML = '';
    
    const fotos = fotosSeleccionadas[productoId] || [];
    
    fotos.forEach((file, index) => {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.setAttribute('data-foto', 'true');
            preview.style.cssText = 'position: relative; width: 60px; height: 60px; border-radius: 4px; overflow: hidden; background: #f0f0f0;';
            const numeroFoto = index + 1;
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Foto ${numeroFoto}" style="width: 100%; height: 100%; object-fit: cover;">
                <span class="foto-numero" style="position: absolute; top: 1px; left: 1px; background: #0066cc; color: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold; transition: opacity 0.2s;">${numeroFoto}</span>
                <button type="button" class="btn-eliminar-foto" onclick="eliminarFoto('${productoId}', ${index})" style="position: absolute; top: 1px; left: 1px; background: #f44336; color: white; border: none; border-radius: 50%; width: 16px; height: 16px; cursor: pointer; font-size: 11px; display: flex; align-items: center; justify-content: center; padding: 0; line-height: 1; opacity: 0; transition: opacity 0.2s;">✕</button>
            `;
            
            // Agregar evento hover
            preview.addEventListener('mouseenter', function() {
                this.querySelector('.foto-numero').style.opacity = '0';
                this.querySelector('.btn-eliminar-foto').style.opacity = '1';
            });
            
            preview.addEventListener('mouseleave', function() {
                this.querySelector('.foto-numero').style.opacity = '1';
                this.querySelector('.btn-eliminar-foto').style.opacity = '0';
            });
            
            container.appendChild(preview);
        };
        
        reader.readAsDataURL(file);
    });
}

// Eliminar foto individual
function eliminarFoto(productoId, index) {
    if (fotosSeleccionadas[productoId]) {
        fotosSeleccionadas[productoId].splice(index, 1);
        
        // Actualizar el input file
        const productoCard = document.querySelector(`[data-producto-id="${productoId}"]`);
        if (productoCard) {
            const input = productoCard.querySelector('input[type="file"]');
            if (input) {
                actualizarPreviewFotos(input);
            }
        }
    }
}

// Actualizar resumen (versión simplificada)
function actualizarResumenFriendly() {
    // Actualizar resumen en Paso 4 si existen los elementos
    const resumenCliente = document.getElementById('resumenCliente');
    const resumenProductos = document.getElementById('resumenProductos');
    const resumenFecha = document.getElementById('resumenFecha');
    
    if (resumenCliente) {
        const cliente = document.getElementById('cliente');
        resumenCliente.textContent = cliente ? cliente.value || '-' : '-';
    }
    
    if (resumenProductos) {
        const productos = document.querySelectorAll('.producto-card');
        resumenProductos.textContent = productos.length;
    }
    
    if (resumenFecha) {
        const hoy = new Date();
        const fechaFormato = hoy.toLocaleDateString('es-ES', { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit' 
        });
        resumenFecha.textContent = fechaFormato;
    }
}

// Formatear forma de pago
function formatearFormaPago(valor) {
    const opciones = {
        'CONTADO': '💵 Contado',
        'CRÉDITO': '📋 Crédito',
        '50/50': '⚖️ 50/50',
        'ANTICIPO': '🎯 Anticipo'
    };
    return opciones[valor] || '-';
}

// Obtener color hex aproximado desde nombre
function obtenerColorHex(colorNombre) {
    const colores = {
        'blanco': '#ffffff',
        'negro': '#000000',
        'rojo': '#ff0000',
        'azul': '#0066cc',
        'verde': '#00cc00',
        'amarillo': '#ffff00',
        'naranja': '#ff9800',
        'rosa': '#ff69b4',
        'gris': '#808080',
        'morado': '#800080',
        'marrón': '#8b4513',
        'beige': '#f5f5dc'
    };
    
    const clave = colorNombre.toLowerCase().trim();
    return colores[clave] || '#e0e0e0';
}

// Mostrar detalle del producto
function mostrarDetalleProducto(index) {
    const productos = document.querySelectorAll('.producto-card');
    if (productos[index]) {
        // Aquí puedes agregar un modal o expandir la vista
        alert('Detalles del producto ' + (index + 1));
    }
}

// ============ BÚSQUEDA DE PRENDAS ============
function buscarPrendas(input) {
    const valor = input.value.toLowerCase();
    const suggestions = input.closest('.prenda-search-container').querySelector('.prenda-suggestions');
    const items = suggestions.querySelectorAll('.prenda-suggestion-item');
    
    if (valor.length === 0) {
        suggestions.classList.remove('show');
        return;
    }
    
    let hayCoincidencias = false;
    items.forEach(item => {
        const texto = item.textContent.toLowerCase();
        if (texto.includes(valor)) {
            item.style.display = 'block';
            hayCoincidencias = true;
        } else {
            item.style.display = 'none';
        }
    });
    
    if (hayCoincidencias) {
        suggestions.classList.add('show');
    } else {
        suggestions.classList.remove('show');
    }
}

function seleccionarPrenda(valor, element) {
    const input = element.closest('.prenda-search-container').querySelector('.prenda-search-input');
    input.value = valor;
    input.closest('.prenda-search-container').querySelector('.prenda-suggestions').classList.remove('show');
    actualizarResumenFriendly();
}

// Cerrar sugerencias al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.prenda-search-container')) {
        document.querySelectorAll('.prenda-suggestions').forEach(s => s.classList.remove('show'));
    }
});

// ============ IMÁGENES ADICIONALES ============
function toggleAdditionalImages(btn) {
    const section = btn.closest('.producto-section').querySelector('.additional-images-section');
    section.classList.toggle('show');
    
    if (section.classList.contains('show')) {
        btn.innerHTML = '<i class="fas fa-minus"></i> Ocultar Imágenes Adicionales';
    } else {
        btn.innerHTML = '<i class="fas fa-plus"></i> Agregar Imagen de Tela o Bordado';
    }
}

function agregarFotoTela(input) {
    const productoCard = input.closest('.producto-card');
    if (!productoCard) return;
    
    const files = input.files;
    console.log('📁 Agregando foto de tela a memoria');
    
    // Guardar en memoria
    Array.from(files).forEach(file => {
        window.imagenesEnMemoria.tela.push(file);
        console.log(`✅ Foto de tela guardada en memoria: ${file.name}`);
    });
    
    const container = productoCard.querySelector('.foto-tela-preview');
    if (container) {
        mostrarPreviewFoto(input, container);
    }
}

function agregarFotoBordado(input) {
    const productoCard = input.closest('.producto-card');
    if (!productoCard) return;
    
    const container = productoCard.querySelector('.foto-bordado-preview');
    if (container) {
        mostrarPreviewFoto(input, container);
    }
}

function agregarFotoEstampado(input) {
    const productoCard = input.closest('.producto-card');
    if (!productoCard) return;
    
    const container = productoCard.querySelector('.foto-estampado-preview');
    if (container) {
        mostrarPreviewFoto(input, container);
    }
}

function mostrarPreviewFoto(input, container) {
    // Obtener fotos existentes en el contenedor
    const fotosExistentes = container.querySelectorAll('div[data-foto]').length;
    const fotosNuevas = input.files.length;
    const totalFotos = fotosExistentes + fotosNuevas;
    
    // Limitar a máximo 3 fotos totales
    if (totalFotos > 3) {
        alert('Máximo 3 fotos permitidas por sección. Ya tienes ' + fotosExistentes + ' foto(s).');
        return;
    }
    
    // Configurar el contenedor como grid 3 columnas con tamaño fijo
    if (!container.style.display) {
        container.style.cssText = 'display: grid; grid-template-columns: repeat(3, 60px); gap: 0.4rem; margin-top: 0.5rem;';
    }
    
    Array.from(input.files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.setAttribute('data-foto', 'true');
            preview.style.cssText = 'position: relative; width: 60px; height: 60px; border-radius: 4px; overflow: hidden; background: #f0f0f0;';
            const numeroFoto = fotosExistentes + index + 1;
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                <span class="foto-numero" style="position: absolute; top: 1px; left: 1px; background: #0066cc; color: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold; transition: opacity 0.2s;">${numeroFoto}</span>
                <button type="button" class="btn-eliminar-foto" onclick="this.closest('div').remove()" style="position: absolute; top: 1px; left: 1px; background: #f44336; color: white; border: none; border-radius: 50%; width: 16px; height: 16px; cursor: pointer; font-size: 11px; display: flex; align-items: center; justify-content: center; padding: 0; line-height: 1; opacity: 0; transition: opacity 0.2s;">✕</button>
            `;
            
            // Agregar evento hover
            preview.addEventListener('mouseenter', function() {
                this.querySelector('.foto-numero').style.opacity = '0';
                this.querySelector('.btn-eliminar-foto').style.opacity = '1';
            });
            
            preview.addEventListener('mouseleave', function() {
                this.querySelector('.foto-numero').style.opacity = '1';
                this.querySelector('.btn-eliminar-foto').style.opacity = '0';
            });
            container.appendChild(preview);
        };
        reader.readAsDataURL(file);
    });
    
    // Limpiar el input después de procesar
    input.value = '';
}

// ============ TABLA DE TALLAS ============
function agregarFilaTalla(btn) {
    const tbody = btn.closest('.producto-section').querySelector('.tallas-tbody');
    const ultimaFila = tbody.querySelector('.talla-row:last-child');
    const nuevaFila = ultimaFila.cloneNode(true);
    
    // Copiar valores de la última fila (excepto cantidad y talla)
    // Cantidad se mantiene en 1, Talla se deja vacía para que el usuario la seleccione
    const inputs = nuevaFila.querySelectorAll('input, select');
    inputs.forEach((input, index) => {
        if (index === 0) {
            // Cantidad: siempre 1
            input.value = '1';
        } else if (index === 1) {
            // Talla: dejar vacía para que seleccione
            input.value = '';
        }
        // El resto mantiene los valores de la última fila (Color, Manga, Tela, Ref. Hilo)
    });
    
    tbody.appendChild(nuevaFila);
    actualizarResumenFriendly();
}

function eliminarFilaTalla(btn) {
    const fila = btn.closest('.talla-row');
    const tbody = fila.closest('.tallas-tbody');
    
    // No permitir eliminar si es la única fila
    if (tbody.querySelectorAll('.talla-row').length > 1) {
        fila.remove();
        actualizarResumenFriendly();
    } else {
        alert('Debe haber al menos una talla');
    }
}

// ============ TOGGLE IMÁGENES CON CHECKBOX ============
function toggleImagenTela(checkbox) {
    const zone = checkbox.closest('.image-upload-group').querySelector('.imagen-tela-zone');
    const preview = checkbox.closest('.image-upload-group').querySelector('.foto-tela-preview');
    const fileInput = checkbox.closest('.image-upload-group').querySelector('input[type="file"]');
    
    if (checkbox.checked) {
        zone.style.display = 'none';
        preview.style.display = 'none';
        fileInput.value = '';
        preview.innerHTML = '';
    } else {
        zone.style.display = 'block';
        preview.style.display = 'block';
    }
}

function toggleImagenBordado(checkbox) {
    const parentDiv = checkbox.closest('div');
    const zone = parentDiv.querySelector('label');
    const preview = parentDiv.parentElement.querySelector('.foto-bordado-preview');
    const fileInput = zone.querySelector('input[type="file"]');
    
    if (checkbox.checked) {
        zone.style.display = 'none';
        preview.style.display = 'none';
        fileInput.value = '';
        preview.innerHTML = '';
    } else {
        zone.style.display = 'flex';
        preview.style.display = 'block';
    }
}

function toggleImagenEstampado(checkbox) {
    const parentDiv = checkbox.closest('div');
    const zone = parentDiv.querySelector('label');
    const preview = parentDiv.parentElement.querySelector('.foto-estampado-preview');
    const fileInput = zone.querySelector('input[type="file"]');
    
    if (checkbox.checked) {
        zone.style.display = 'none';
        preview.style.display = 'none';
        fileInput.value = '';
        preview.innerHTML = '';
    } else {
        zone.style.display = 'flex';
        preview.style.display = 'block';
    }
}

// ============ ARCHIVOS DE LA ORDEN ============
function agregarFotosOrden(files, input) {
    const container = input.closest('.producto-section').querySelector('.fotos-preview-orden');
    container.innerHTML = '';
    
    Array.from(files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.style.cssText = 'position: relative; width: 100%; height: 100px; border-radius: 6px; overflow: hidden; background: #f0f0f0;';
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                <button type="button" style="position: absolute; top: 0.25rem; right: 0.25rem; width: 24px; height: 24px; background: #f44336; color: white; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; line-height: 1; font-weight: bold;" onclick="this.closest('div').remove();">&times;</button>
            `;
            container.appendChild(preview);
        };
        reader.readAsDataURL(file);
    });
}

function agregarDocumentosOrden(files, input) {
    const container = input.closest('.producto-section') ? input.closest('.producto-section').querySelector('.documentos-preview-orden') : document.querySelector('.documentos-preview-orden');
    container.innerHTML = '';
    
    Array.from(files).forEach((file, index) => {
        const item = document.createElement('div');
        item.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 6px;';
        item.innerHTML = `
            <i class="fas fa-file" style="color: #ff9800; font-size: 1.2rem;"></i>
            <span style="flex: 1; font-size: 0.9rem; color: #333;">${file.name}</span>
            <button type="button" style="width: 24px; height: 24px; background: #f44336; color: white; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem; line-height: 1; font-weight: bold;" onclick="this.closest('div').remove();">&times;</button>
        `;
        container.appendChild(item);
    });
}

// ============ EXPANDIR/CONTRAER TABLA DE ORDEN ============
function toggleTablaOrden(btn) {
    const content = btn.closest('div').querySelector('.tabla-orden-content');
    const icon = btn.querySelector('i.fa-chevron-down');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

function toggleSubseccion(btn) {
    const content = btn.closest('div').querySelector('.subseccion-content');
    const icon = btn.querySelector('i.fa-chevron-down');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

// ============ EXPANDIR/CONTRAER SECCIONES DE PRENDA ============
function toggleSeccion(btn) {
    const content = btn.closest('.producto-section').querySelector('.section-content');
    const icon = btn.querySelector('i.fa-chevron-down');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

// ============ EXPANDIR/CONTRAER PRENDA COMPLETA ============
function toggleProductoBody(btn) {
    const body = btn.closest('.producto-card').querySelector('.producto-body');
    
    if (body.style.display === 'none') {
        body.style.display = 'block';
        btn.style.transform = 'rotate(180deg)';
    } else {
        body.style.display = 'none';
        btn.style.transform = 'rotate(0deg)';
    }
}

// ============ FUNCIONES PARA BORDADO/ESTAMPADO ============

// Drag and Drop para imágenes
const dropZone = document.getElementById('drop_zone_imagenes');
const fileInput = document.getElementById('imagenes_bordado');
const galeria = document.getElementById('galeria_imagenes');
let archivosAcumulados = []; // Array para guardar las imágenes

// Click en drop zone abre file input
dropZone.addEventListener('click', () => fileInput.click());

// Drag over
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.background = '#e3f2fd';
    dropZone.style.borderColor = '#2196F3';
});

// Drag leave
dropZone.addEventListener('dragleave', () => {
    dropZone.style.background = '#f0f7ff';
    dropZone.style.borderColor = '#3498db';
});

// Drop
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.style.background = '#f0f7ff';
    dropZone.style.borderColor = '#3498db';
    
    const files = e.dataTransfer.files;
    agregarImagenes(files);
});

// File input change
fileInput.addEventListener('change', function() {
    agregarImagenes(this.files);
});

// Agregar imágenes sin borrar las anteriores
function agregarImagenes(newFiles) {
    const newFilesArray = Array.from(newFiles);
    
    // Validar máximo 5
    if (archivosAcumulados.length + newFilesArray.length > 5) {
        alert('Máximo 5 imágenes permitidas. Ya tienes ' + archivosAcumulados.length + ' imagen(es).');
        fileInput.value = '';
        return;
    }
    
    // Agregar nuevos archivos al array
    archivosAcumulados = archivosAcumulados.concat(newFilesArray);
    
    // Actualizar file input con DataTransfer
    const dt = new DataTransfer();
    archivosAcumulados.forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
    
    // Mostrar todas las imágenes
    mostrarImagenes(archivosAcumulados);
    
    // Limpiar el input para permitir seleccionar el mismo archivo de nuevo
    fileInput.value = '';
}

// Mostrar imágenes en galería
function mostrarImagenes(files) {
    const filesArray = Array.from(files);
    
    galeria.innerHTML = '';
    
    // Crear un array para almacenar las imágenes cargadas con su índice correcto
    let imagenesLoaded = [];
    let imagenesCount = 0;
    
    filesArray.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(event) {
            // Guardar la imagen con su índice correcto
            imagenesLoaded[index] = {
                src: event.target.result,
                index: index
            };
            
            imagenesCount++;
            
            // Cuando todas las imágenes se hayan cargado, renderizarlas en orden
            if (imagenesCount === filesArray.length) {
                imagenesLoaded.forEach((imgData, posicion) => {
                    if (imgData) {
                        const div = document.createElement('div');
                        div.style.position = 'relative';
                        div.style.width = '100%';
                        div.style.paddingBottom = '100%';
                        div.style.overflow = 'hidden';
                        div.style.borderRadius = '8px';
                        div.style.border = '1px solid #ddd';
                        
                        const img = document.createElement('img');
                        img.src = imgData.src;
                        img.style.position = 'absolute';
                        img.style.top = '0';
                        img.style.left = '0';
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = 'cover';
                        
                        // Número de imagen (basado en la posición en el array, no en el orden de carga)
                        const numero = document.createElement('div');
                        numero.innerHTML = posicion + 1;
                        numero.style.position = 'absolute';
                        numero.style.bottom = '5px';
                        numero.style.left = '5px';
                        numero.style.background = '#3498db';
                        numero.style.color = 'white';
                        numero.style.borderRadius = '50%';
                        numero.style.width = '28px';
                        numero.style.height = '28px';
                        numero.style.display = 'flex';
                        numero.style.alignItems = 'center';
                        numero.style.justifyContent = 'center';
                        numero.style.fontWeight = 'bold';
                        numero.style.fontSize = '14px';
                        
                        const btnEliminar = document.createElement('button');
                        btnEliminar.type = 'button';
                        btnEliminar.innerHTML = '✕';
                        btnEliminar.style.position = 'absolute';
                        btnEliminar.style.top = '5px';
                        btnEliminar.style.right = '5px';
                        btnEliminar.style.background = '#f44336';
                        btnEliminar.style.color = 'white';
                        btnEliminar.style.border = 'none';
                        btnEliminar.style.borderRadius = '50%';
                        btnEliminar.style.width = '24px';
                        btnEliminar.style.height = '24px';
                        btnEliminar.style.cursor = 'pointer';
                        btnEliminar.style.fontSize = '16px';
                        btnEliminar.style.display = 'flex';
                        btnEliminar.style.alignItems = 'center';
                        btnEliminar.style.justifyContent = 'center';
                        btnEliminar.style.padding = '0';
                        
                        btnEliminar.addEventListener('click', (e) => {
                            e.preventDefault();
                            // Eliminar del array acumulado usando el índice correcto
                            archivosAcumulados.splice(posicion, 1);
                            
                            // Actualizar file input
                            const dt = new DataTransfer();
                            archivosAcumulados.forEach(f => dt.items.add(f));
                            fileInput.files = dt.files;
                            
                            // Mostrar imágenes actualizadas
                            mostrarImagenes(archivosAcumulados);
                        });
                        
                        div.appendChild(img);
                        div.appendChild(numero);
                        div.appendChild(btnEliminar);
                        galeria.appendChild(div);
                    }
                });
            }
        };
        reader.readAsDataURL(file);
    });
}

// Agregar técnica
function agregarTecnica() {
    const selector = document.getElementById('selector_tecnicas');
    const tecnica = selector.value;
    
    if (!tecnica) {
        alert('Por favor selecciona una técnica');
        return;
    }
    
    const contenedor = document.getElementById('tecnicas_seleccionadas');
    
    // Verificar si ya existe
    const existe = Array.from(contenedor.children).some(tag => tag.textContent.includes(tecnica));
    if (existe) {
        alert('Esta técnica ya está agregada');
        return;
    }
    
    // Crear etiqueta de técnica
    const tag = document.createElement('div');
    tag.style.background = '#3498db';
    tag.style.color = 'white';
    tag.style.padding = '6px 12px';
    tag.style.borderRadius = '20px';
    tag.style.display = 'flex';
    tag.style.alignItems = 'center';
    tag.style.gap = '8px';
    tag.style.fontSize = '0.9rem';
    tag.style.fontWeight = '600';
    tag.innerHTML = `
        <input type="hidden" name="tecnicas[]" value="${tecnica}">
        <span>${tecnica}</span>
        <button type="button" onclick="this.closest('div').remove()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem; padding: 0; line-height: 1;">✕</button>
    `;
    
    contenedor.appendChild(tag);
    selector.value = '';
}

// Agregar sección con sus ubicaciones
function agregarSeccion() {
    const seccion = document.getElementById('seccion_prenda').value;
    const contenedor = document.getElementById('secciones_agregadas');
    
    if (!seccion) {
        alert('Por favor selecciona una sección');
        return;
    }
    
    let ubicaciones = [];
    
    if (seccion === 'CAMISA') {
        ubicaciones = ['LADO IZQUIERDO', 'LADO DERECHO', 'ESPALDA', 'MANGA'];
    } else if (seccion === 'JEAN_SUDADERA') {
        ubicaciones = ['PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO'];
    } else if (seccion === 'GORRAS') {
        ubicaciones = ['FRONTAL', 'LATERAL'];
    }
    
    // Crear contenedor de sección
    const seccionDiv = document.createElement('div');
    seccionDiv.style.background = '#f9f9f9';
    seccionDiv.style.border = '2px solid #3498db';
    seccionDiv.style.borderRadius = '8px';
    seccionDiv.style.padding = '15px';
    seccionDiv.style.position = 'relative';
    
    // Título de sección
    const titulo = document.createElement('div');
    titulo.style.fontWeight = 'bold';
    titulo.style.fontSize = '1.1rem';
    titulo.style.marginBottom = '10px';
    titulo.innerHTML = `${seccion}`;
    seccionDiv.appendChild(titulo);
    
    // Tabla de ubicaciones
    const tabla = document.createElement('table');
    tabla.style.width = '100%';
    tabla.style.borderCollapse = 'collapse';
    tabla.style.marginBottom = '10px';
    
    const thead = document.createElement('thead');
    thead.innerHTML = `
        <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
            <th style="padding: 10px; text-align: left;">Ubicación</th>
            <th style="padding: 10px; text-align: center; width: 50px;">Acción</th>
        </tr>
    `;
    tabla.appendChild(thead);
    
    const tbody = document.createElement('tbody');
    ubicaciones.forEach(ubicacion => {
        const fila = document.createElement('tr');
        fila.style.borderBottom = '1px solid #ddd';
        fila.innerHTML = `
            <td style="padding: 10px;">
                <input type="hidden" name="ubicaciones_seccion[]" value="${seccion}">
                <input type="hidden" name="ubicaciones[]" value="${ubicacion}">
                ${ubicacion}
            </td>
            <td style="padding: 10px; text-align: center;">
                <button type="button" class="btn-eliminar" onclick="this.closest('tr').remove()" style="background: #f44336; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">
                    ✕
                </button>
            </td>
        `;
        tbody.appendChild(fila);
    });
    tabla.appendChild(tbody);
    seccionDiv.appendChild(tabla);
    
    // Botón agregar ubicación (esquina superior derecha)
    const btnAgregarEsquina = document.createElement('button');
    btnAgregarEsquina.type = 'button';
    btnAgregarEsquina.textContent = '+';
    btnAgregarEsquina.style.position = 'absolute';
    btnAgregarEsquina.style.top = '10px';
    btnAgregarEsquina.style.right = '10px';
    btnAgregarEsquina.style.background = '#3498db';
    btnAgregarEsquina.style.color = 'white';
    btnAgregarEsquina.style.border = 'none';
    btnAgregarEsquina.style.borderRadius = '50%';
    btnAgregarEsquina.style.width = '36px';
    btnAgregarEsquina.style.height = '36px';
    btnAgregarEsquina.style.cursor = 'pointer';
    btnAgregarEsquina.style.fontSize = '1.5rem';
    btnAgregarEsquina.style.fontWeight = 'bold';
    btnAgregarEsquina.style.display = 'flex';
    btnAgregarEsquina.style.alignItems = 'center';
    btnAgregarEsquina.style.justifyContent = 'center';
    btnAgregarEsquina.style.lineHeight = '1';
    btnAgregarEsquina.onclick = function() {
        const fila = document.createElement('tr');
        fila.style.borderBottom = '1px solid #ddd';
        fila.innerHTML = `
            <td style="padding: 10px;">
                <input type="text" name="ubicaciones[]" class="input-large" style="width: 100%; padding: 5px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Ubicación...">
            </td>
            <td style="padding: 10px; text-align: center;">
                <button type="button" class="btn-eliminar" onclick="this.closest('tr').remove()" style="background: #f44336; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">
                    ✕
                </button>
            </td>
        `;
        tbody.appendChild(fila);
    };
    seccionDiv.appendChild(btnAgregarEsquina);
    
    // Botón eliminar sección (esquina superior derecha, más a la izquierda)
    const btnEliminarSeccion = document.createElement('button');
    btnEliminarSeccion.type = 'button';
    btnEliminarSeccion.textContent = '✕';
    btnEliminarSeccion.style.position = 'absolute';
    btnEliminarSeccion.style.top = '10px';
    btnEliminarSeccion.style.right = '50px';
    btnEliminarSeccion.style.background = '#f44336';
    btnEliminarSeccion.style.color = 'white';
    btnEliminarSeccion.style.border = 'none';
    btnEliminarSeccion.style.borderRadius = '50%';
    btnEliminarSeccion.style.width = '36px';
    btnEliminarSeccion.style.height = '36px';
    btnEliminarSeccion.style.cursor = 'pointer';
    btnEliminarSeccion.style.fontSize = '1.2rem';
    btnEliminarSeccion.style.display = 'flex';
    btnEliminarSeccion.style.alignItems = 'center';
    btnEliminarSeccion.style.justifyContent = 'center';
    btnEliminarSeccion.style.lineHeight = '1';
    btnEliminarSeccion.onclick = function() {
        seccionDiv.remove();
    };
    seccionDiv.appendChild(btnEliminarSeccion);
    
    // Observación
    const obsDiv = document.createElement('div');
    obsDiv.style.marginTop = '10px';
    obsDiv.innerHTML = `
        <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">
            <i class="fas fa-sticky-note"></i>
            Observación
        </label>
        <textarea name="observaciones_seccion[]" class="input-large" rows="2" placeholder="Observación..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;"></textarea>
    `;
    seccionDiv.appendChild(obsDiv);
    
    contenedor.appendChild(seccionDiv);
    
    // Limpiar selector
    document.getElementById('seccion_prenda').value = '';
}

// Agregar observación general
function agregarObservacion() {
    const contenedor = document.getElementById('observaciones_lista');
    
    // Crear fila de observación
    const fila = document.createElement('div');
    fila.style.display = 'flex';
    fila.style.gap = '10px';
    fila.style.alignItems = 'center';
    fila.style.padding = '10px';
    fila.style.background = 'white';
    fila.style.borderRadius = '6px';
    fila.style.border = '1px solid #ddd';
    
    // Crear ID único para esta fila
    const filaId = 'obs_' + Date.now();
    
    fila.id = filaId;
    fila.innerHTML = `
        <input type="text" name="observaciones_generales[]" class="input-large" placeholder="Escribe una observación..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
        
        <!-- Contenedor para checkbox o campo de texto -->
        <div class="obs-toggle-container" style="display: flex; gap: 5px; align-items: center; flex-shrink: 0;">
            <!-- Checkbox (visible por defecto) -->
            <div class="obs-checkbox-mode" style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="observaciones_check[]" style="width: 20px; height: 20px; cursor: pointer;">
            </div>
            
            <!-- Campo de texto (oculto por defecto) -->
            <div class="obs-text-mode" style="display: none; flex: 1;">
                <input type="text" name="observaciones_valor[]" placeholder="Valor..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
            </div>
            
            <!-- Botón toggle -->
            <button type="button" class="obs-toggle-btn" style="background: #3498db; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">✓/✎</button>
        </div>
        
        <!-- Botón eliminar -->
        <button type="button" onclick="this.closest('div').remove()" style="background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 1rem; flex-shrink: 0;">✕</button>
    `;
    
    contenedor.appendChild(fila);
    
    // Agregar evento al botón toggle
    const toggleBtn = fila.querySelector('.obs-toggle-btn');
    const checkboxMode = fila.querySelector('.obs-checkbox-mode');
    const textMode = fila.querySelector('.obs-text-mode');
    
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Alternar visibilidad
        if (checkboxMode.style.display === 'none') {
            // Cambiar a checkbox
            checkboxMode.style.display = 'block';
            textMode.style.display = 'none';
            toggleBtn.textContent = '✓/✎';
            toggleBtn.style.background = '#3498db';
        } else {
            // Cambiar a texto
            checkboxMode.style.display = 'none';
            textMode.style.display = 'block';
            toggleBtn.textContent = '✓/✎';
            toggleBtn.style.background = '#ff9800';
        }
    });
}

// Manejar envío del formulario
document.getElementById('formCrearPedidoFriendly').addEventListener('submit', function(e) {
    e.preventDefault();

    // Recolectar datos
    const formData = new FormData(this);

    // Enviar
    fetch('{{ route("asesores.pedidos.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('¡Pedido creado exitosamente!');
            window.location.href = '{{ route("asesores.pedidos.index") }}';
        } else {
            alert('Error: ' + (data.message || 'No se pudo crear el pedido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al crear el pedido. Por favor intenta de nuevo.');
    });
});

// Cargar datos del borrador si existe
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM cargado, inicializando...');
    
    @if(isset($cotizacion))
        // Cargar cliente
        const clienteInput = document.getElementById('cliente');
        if (clienteInput) {
            clienteInput.value = '{{ $cotizacion->cliente }}';
        }
        
        // Cargar productos
        const productos = @json($cotizacion->productos ?? []);
        if (Array.isArray(productos) && productos.length > 0) {
            // Limpiar primer producto
            document.querySelectorAll('.producto-card').forEach(el => el.remove());
            
            // Agregar productos
            productos.forEach(producto => {
                agregarProductoFriendly();
                const lastCard = document.querySelector('.producto-card:last-child');
                if (lastCard) {
                    const nombreInput = lastCard.querySelector('input[name*="nombre_producto"]');
                    const descInput = lastCard.querySelector('textarea[name*="descripcion"]');
                    const cantInput = lastCard.querySelector('input[name*="cantidad"]');
                    
                    if (nombreInput) nombreInput.value = producto.nombre_producto || '';
                    if (descInput) descInput.value = producto.descripcion || '';
                    if (cantInput) cantInput.value = producto.cantidad || 1;
                }
            });
        } else {
            agregarProductoFriendly();
        }
        
        // Cargar técnicas
        const tecnicas = @json($cotizacion->tecnicas ?? []);
        if (Array.isArray(tecnicas)) {
            tecnicas.forEach(tecnica => {
                document.getElementById('selector_tecnicas').value = tecnica;
                agregarTecnica();
            });
        }
        
        // Cargar observaciones generales
        const observaciones = @json($cotizacion->observaciones_generales ?? []);
        if (Array.isArray(observaciones)) {
            observaciones.forEach(obs => {
                agregarObservacion();
                const lastObs = document.querySelector('#observaciones_lista > div:last-child');
                if (lastObs) {
                    const input = lastObs.querySelector('input[name="observaciones_generales[]"]');
                    if (input) input.value = obs;
                }
            });
        }
    @else
        agregarProductoFriendly();
    @endif
    
    // Verificar que las funciones existan
    console.log('✅ guardarCotizacion existe:', typeof guardarCotizacion);
    console.log('✅ enviarCotizacion existe:', typeof enviarCotizacion);
});

// ============ MODAL: ESPECIFICACIONES DE LA ORDEN ============
function abrirModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    modal.style.display = 'flex';
}

function cerrarModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    modal.style.display = 'none';
}

function guardarEspecificaciones() {
    // Recopilar especificaciones marcadas
    const especificaciones = [];
    const modal = document.getElementById('modalEspecificaciones');
    
    // Buscar todos los checkboxes marcados en el modal
    const checkboxesMarcados = modal.querySelectorAll('input[type="checkbox"]:checked');
    
    checkboxesMarcados.forEach(checkbox => {
        const fila = checkbox.closest('tr');
        if (fila) {
            // Obtener el texto del item (primera columna)
            const itemCell = fila.querySelector('td:first-child');
            const obsCell = fila.querySelector('td:last-child');
            
            const item = itemCell ? itemCell.textContent.trim() : '';
            const obs = obsCell ? obsCell.querySelector('input[type="text"]')?.value || '' : '';
            
            if (item || obs) {
                especificaciones.push({
                    item: item,
                    observaciones: obs
                });
            }
        }
    });
    
    // Guardar en variable global para enviar con la cotización
    window.especificacionesSeleccionadas = especificaciones;
    
    console.log('✅ Especificaciones guardadas:', especificaciones);
    console.log('📋 Se enviarán cuando hagas clic en ENVIAR la cotización');
    
    cerrarModalEspecificaciones();
}

// Agregar fila a una categoría de especificaciones
function agregarFilaEspecificacion(categoria) {
    const tbodyId = 'tbody_' + categoria;
    const tbody = document.getElementById(tbodyId);
    
    if (!tbody) return;
    
    const fila = document.createElement('tr');
    fila.innerHTML = `
        <td><input type="text" name="tabla_orden[${categoria}_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
        <td style="text-align: center;">
            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
        </td>
        <td style="display: flex; gap: 5px;">
            <input type="text" name="tabla_orden[${categoria}_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
        </td>
    `;
    
    tbody.appendChild(fila);
}

// Eliminar fila de especificaciones
function eliminarFilaEspecificacion(btn) {
    btn.closest('tr').remove();
}

// Mostrar fecha actual
document.addEventListener('DOMContentLoaded', function() {
    const fechaActualElement = document.getElementById('fechaActual');
    if (fechaActualElement) {
        const hoy = new Date();
        const dia = String(hoy.getDate()).padStart(2, '0');
        const mes = String(hoy.getMonth() + 1).padStart(2, '0');
        const año = hoy.getFullYear();
        fechaActualElement.textContent = `${dia}/${mes}/${año}`;
    }
    
    // Actualizar resumen en paso 4
    actualizarResumenCotizacion();
});

// Actualizar resumen de cotización
function actualizarResumenCotizacion() {
    const clienteInput = document.getElementById('cliente');
    const productosCount = document.querySelectorAll('#productosAgregados .producto-item').length;
    const fechaElement = document.getElementById('resumenFecha');
    
    if (document.getElementById('resumenCliente')) {
        document.getElementById('resumenCliente').textContent = clienteInput ? clienteInput.value || '-' : '-';
    }
    if (document.getElementById('resumenProductos')) {
        document.getElementById('resumenProductos').textContent = productosCount;
    }
    if (fechaElement) {
        const hoy = new Date();
        const dia = String(hoy.getDate()).padStart(2, '0');
        const mes = String(hoy.getMonth() + 1).padStart(2, '0');
        const año = hoy.getFullYear();
        fechaElement.textContent = `${dia}/${mes}/${año}`;
    }
}

// Recopilar datos del formulario
function recopilarDatos() {
    const cliente = document.getElementById('cliente').value;
    
    // Recopilar tipo de cotización (M, D, X)
    const cotizar_segun_indicaciones = document.getElementById('cotizar_segun_indicaciones').value || '';
    
    // Recopilar productos (buscar en .producto-card que es donde se agregan)
    const productos = [];
    document.querySelectorAll('.producto-card').forEach((item, index) => {
        const nombre = item.querySelector('input[name*="nombre_producto"]')?.value || '';
        const descripcion = item.querySelector('textarea[name*="descripcion"]')?.value || '';
        const cantidad = item.querySelector('input[name*="cantidad"]')?.value || 1;
        const tela = item.querySelector('input[name*="tela"]')?.value || '';
        const imagenTela = item.querySelector('input[name*="imagen_tela"]')?.value || '';
        
        // Recopilar tallas seleccionadas
        const tallasSeleccionadas = [];
        item.querySelectorAll('input[name*="tallas"]:checked').forEach(checkbox => {
            tallasSeleccionadas.push(checkbox.value);
        });
        
        if (nombre.trim()) { // Solo agregar si tiene nombre
            productos.push({
                nombre_producto: nombre,
                descripcion: descripcion,
                cantidad: parseInt(cantidad) || 1,
                tela: tela,
                imagen_tela: imagenTela,
                tallas: tallasSeleccionadas
            });
        }
    });
    
    // Recopilar técnicas
    const tecnicas = [];
    document.querySelectorAll('#tecnicas_seleccionadas .tecnica-tag').forEach(tag => {
        tecnicas.push(tag.textContent.replace('✕', '').trim());
    });
    
    // Recopilar observaciones generales
    const observaciones_generales = [];
    document.querySelectorAll('#observaciones_lista > div').forEach(obs => {
        const valor = obs.querySelector('input[name="observaciones_generales[]"]')?.value || '';
        if (valor.trim()) {
            observaciones_generales.push(valor);
        }
    });
    
    return {
        cliente: cliente,
        cotizar_segun_indicaciones: cotizar_segun_indicaciones,
        productos: productos,
        tecnicas: tecnicas,
        observaciones_generales: observaciones_generales
    };
}

// Guardar cotización como borrador
async function guardarCotizacion() {
    // Deshabilitar botones para evitar múltiples clics
    const btnGuardar = document.querySelector('button[onclick="guardarCotizacion()"]');
    const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
    
    if (btnGuardar) btnGuardar.disabled = true;
    if (btnEnviar) btnEnviar.disabled = true;
    
    // Mostrar indicador de carga
    Swal.fire({
        title: 'Guardando...',
        html: '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;"><div style="width: 12px; height: 12px; border-radius: 50%; background: #1e40af; animation: pulse 1.5s infinite;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #1e40af; animation: pulse 1.5s infinite 0.3s;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #1e40af; animation: pulse 1.5s infinite 0.6s;"></div></div><style>@keyframes pulse { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }</style>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: (modal) => {
            modal.style.pointerEvents = 'none';
        }
    });
    
    const datos = recopilarDatos();
    
    console.log('🔵 guardarCotizacion() llamado');
    console.log('📸 Imágenes en memoria:', {
        prenda: window.imagenesEnMemoria.prenda.length,
        tela: window.imagenesEnMemoria.tela.length,
        general: window.imagenesEnMemoria.general.length
    });
    
    try {
        // Crear cotización
        console.log('📤 Enviando cotización...');
        const response = await fetch('{{ route("asesores.cotizaciones.guardar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
                tipo: 'borrador',
                cliente: datos.cliente,
                productos: datos.productos,
                tecnicas: datos.tecnicas,
                observaciones_generales: datos.observaciones_generales
            })
        });
        
        const data = await response.json();
        
        console.log('📋 Respuesta del servidor:', data);
        
        if (data.success && data.cotizacion_id) {
            console.log('✅ Cotización creada con ID:', data.cotizacion_id);
            
            // Subir todas las imágenes de memoria
            const totalImagenes = window.imagenesEnMemoria.prenda.length + 
                                 window.imagenesEnMemoria.tela.length + 
                                 window.imagenesEnMemoria.general.length;
            
            if (totalImagenes > 0) {
                console.log('📸 Subiendo', totalImagenes, 'imágenes...');
                
                // Subir imágenes de prenda
                if (window.imagenesEnMemoria.prenda.length > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.prenda, 'prenda');
                }
                
                // Subir imágenes de tela
                if (window.imagenesEnMemoria.tela.length > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.tela, 'tela');
                }
                
                // Subir imágenes generales (Paso 3)
                if (window.imagenesEnMemoria.general.length > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.general, 'general');
                }
                
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: '¡Cotización guardada en borradores!',
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
                console.log('⚠️ Sin imágenes para subir');
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: '¡Cotización guardada en borradores!',
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
            }
            
            // Redirigir a la tabla de borradores en paralelo (sin esperar al toast)
            setTimeout(() => {
                window.location.href = '{{ route("asesores.cotizaciones.index") }}#borradores';
            }, 2000);
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Error al guardar: ' + (data.message || 'Error desconocido'),
                icon: 'error',
                confirmButtonColor: '#1e40af',
                customClass: {
                    popup: 'swal-custom-popup',
                    title: 'swal-custom-title',
                    confirmButton: 'swal-custom-confirm'
                }
            });
        }
    } catch (error) {
        console.error('❌ Error:', error);
        Swal.fire({
            title: 'Error',
            text: 'Error al guardar la cotización',
            icon: 'error',
            confirmButtonColor: '#1e40af',
            customClass: {
                popup: 'swal-custom-popup',
                title: 'swal-custom-title',
                confirmButton: 'swal-custom-confirm'
            }
        });
    }
}

// Función auxiliar para subir imágenes
async function subirImagenesAlServidor(cotizacionId, archivos, tipo) {
    console.log(`📤 Subiendo ${archivos.length} imágenes de tipo "${tipo}"...`);
    
    const formData = new FormData();
    archivos.forEach((file, index) => {
        console.log(`  - ${tipo} ${index + 1}: ${file.name}`);
        formData.append('imagenes[]', file);
    });
    formData.append('tipo', tipo);
    
    try {
        const response = await fetch(`/asesores/cotizaciones/${cotizacionId}/imagenes`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        const data = await response.json();
        if (data.success) {
            console.log(`✅ ${archivos.length} imágenes de tipo "${tipo}" guardadas`);
        } else {
            console.error(`❌ Error al guardar imágenes de tipo "${tipo}":`, data.message);
        }
    } catch (error) {
        console.error(`❌ Error al subir imágenes de tipo "${tipo}":`, error);
    }
}

// Enviar cotización
async function enviarCotizacion() {
    const datos = recopilarDatos();
    
    if (!datos.cliente.trim()) {
        Swal.fire({
            title: 'Campo requerido',
            text: 'Por favor ingresa el nombre del cliente',
            icon: 'warning',
            confirmButtonColor: '#1e40af',
            customClass: {
                popup: 'swal-custom-popup',
                title: 'swal-custom-title',
                confirmButton: 'swal-custom-confirm'
            }
        });
        return;
    }
    
    if (datos.productos.length === 0) {
        Swal.fire({
            title: 'Productos requeridos',
            text: 'Por favor agrega al menos un producto',
            icon: 'warning',
            confirmButtonColor: '#1e40af',
            customClass: {
                popup: 'swal-custom-popup',
                title: 'swal-custom-title',
                confirmButton: 'swal-custom-confirm'
            }
        });
        return;
    }
    
    // Confirmación antes de enviar
    Swal.fire({
        title: '¿Listo para enviar?',
        html: '<p style="margin: 0 0 14px 0; font-size: 0.95rem; color: #4b5563; line-height: 1.6;">Antes de continuar, ten en cuenta que una vez enviada la cotización <span style="color: #ef4444; font-weight: 700;">no podrá editarse ni eliminarse</span>.</p><p style="margin: 0 0 14px 0; font-size: 0.95rem; color: #4b5563; line-height: 1.6;">Solo será posible hacer cambios si <span style="color: #ef4444; font-weight: 700;">el contador la anula</span>.</p><p style="margin: 0; font-size: 0.95rem; color: #4b5563; line-height: 1.6;">¿Estás seguro/a de que todo está correcto?</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Revisar primero',
        customClass: {
            popup: 'swal-custom-popup',
            title: 'swal-custom-title',
            confirmButton: 'swal-custom-confirm',
            cancelButton: 'swal-custom-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            procederEnviarCotizacion(datos);
        }
    });
}

// Función auxiliar para proceder con el envío
async function procederEnviarCotizacion(datos) {
    // Deshabilitar botones para evitar múltiples clics
    const btnGuardar = document.querySelector('button[onclick="guardarCotizacion()"]');
    const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
    
    if (btnGuardar) btnGuardar.disabled = true;
    if (btnEnviar) btnEnviar.disabled = true;
    
    // Mostrar indicador de carga
    Swal.fire({
        title: 'Enviando...',
        html: '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;"><div style="width: 12px; height: 12px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite 0.3s;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite 0.6s;"></div></div><style>@keyframes pulse { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }</style>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: (modal) => {
            modal.style.pointerEvents = 'none';
        }
    });
    
    console.log('🔵 enviarCotizacion() llamado');
    console.log('📸 Imágenes en memoria:', {
        prenda: window.imagenesEnMemoria.prenda.length,
        tela: window.imagenesEnMemoria.tela.length,
        general: window.imagenesEnMemoria.general.length
    });
    
    // Obtener especificaciones guardadas de la variable global
    let especificaciones = window.especificacionesSeleccionadas || [];
    if (especificaciones.length > 0) {
        console.log('📋 Especificaciones encontradas:', especificaciones);
    }
    
    try {
        // Crear cotización
        console.log('📤 Enviando cotización...');
        const response = await fetch('{{ route("asesores.cotizaciones.guardar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
                tipo: 'enviada',
                cliente: datos.cliente,
                productos: datos.productos,
                tecnicas: datos.tecnicas,
                observaciones_generales: datos.observaciones_generales,
                especificaciones: especificaciones
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.cotizacion_id) {
            console.log('✅ Cotización creada con ID:', data.cotizacion_id);
            
            // Subir todas las imágenes de memoria
            const totalImagenes = window.imagenesEnMemoria.prenda.length + 
                                 window.imagenesEnMemoria.tela.length + 
                                 window.imagenesEnMemoria.general.length;
            
            if (totalImagenes > 0) {
                console.log('📸 Subiendo', totalImagenes, 'imágenes...');
                
                // Subir imágenes de prenda
                if (window.imagenesEnMemoria.prenda.length > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.prenda, 'prenda');
                }
                
                // Subir imágenes de tela
                if (window.imagenesEnMemoria.tela.length > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.tela, 'tela');
                }
                
                // Subir imágenes generales (Paso 3)
                if (window.imagenesEnMemoria.general.length > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.general, 'general');
                }
            }
            
            // Mostrar mensaje de éxito
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '¡Cotización enviada!',
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
            
            // Redirigir a la tabla de cotizaciones en paralelo (sin esperar al toast)
            setTimeout(() => {
                window.location.href = '{{ route("asesores.cotizaciones.index") }}#cotizaciones';
            }, 2000);
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Error al enviar: ' + (data.message || 'Error desconocido'),
                icon: 'error',
                confirmButtonColor: '#1e40af',
                customClass: {
                    popup: 'swal-custom-popup',
                    title: 'swal-custom-title',
                    confirmButton: 'swal-custom-confirm'
                }
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error',
            text: 'Error al enviar la cotización',
            icon: 'error',
            confirmButtonColor: '#1e40af',
            customClass: {
                popup: 'swal-custom-popup',
                title: 'swal-custom-title',
                confirmButton: 'swal-custom-confirm'
            }
        });
    }
}

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(e) {
    const modal = document.getElementById('modalEspecificaciones');
    if (e.target === modal) {
        cerrarModalEspecificaciones();
    }
});

// ============ MANEJO DE IMÁGENES ============

// Cargar imágenes existentes si es un borrador
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔵 Inicializando formulario...');
    
    // Cargar imágenes existentes
    @if(isset($cotizacion) && $cotizacion->imagenes)
        const imagenesExistentes = @json($cotizacion->imagenes ?? []);
        if (Array.isArray(imagenesExistentes) && imagenesExistentes.length > 0) {
            mostrarImagenesExistentes(imagenesExistentes);
        }
    @endif
    
    // Configurar drag and drop
    configurarDragAndDrop();
});

// Mostrar imágenes existentes
function mostrarImagenesExistentes(imagenes) {
    const galeria = document.getElementById('galeria_imagenes');
    if (!galeria) return;
    
    imagenes.forEach((url, index) => {
        if (url && typeof url === 'string') {
            const div = document.createElement('div');
            div.className = 'imagen-preview';
            div.innerHTML = `
                <img src="${url}" alt="Imagen ${index + 1}">
                <button type="button" class="btn-eliminar-imagen" onclick="eliminarImagenExistente('${url}', this)">✕</button>
            `;
            galeria.appendChild(div);
        }
    });
}

// Configurar drag and drop
function configurarDragAndDrop() {
    const dropZone = document.getElementById('drop_zone_imagenes');
    const inputFile = document.getElementById('imagenes_bordado');
    
    if (!dropZone || !inputFile) return;
    
    // Prevenir comportamiento por defecto
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });
    
    // Resaltar zona de drop
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.background = '#e3f2fd';
            dropZone.style.borderColor = '#2196f3';
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.background = '#f0f7ff';
            dropZone.style.borderColor = '#3498db';
        }, false);
    });
    
    // Manejar drop
    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        inputFile.files = files;
        procesarArchivos(files);
    }, false);
    
    // Manejar click
    dropZone.addEventListener('click', () => {
        inputFile.click();
    });
    
    // Manejar selección de archivos
    inputFile.addEventListener('change', (e) => {
        procesarArchivos(e.target.files);
    });
}

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

// Procesar archivos
function procesarArchivos(files) {
    const galeria = document.getElementById('galeria_imagenes');
    if (!galeria) return;
    
    console.log('📁 Procesando', files.length, 'archivos de Paso 3');
    
    let contador = 0;
    
    for (let file of files) {
        console.log(`  Archivo ${contador + 1}:`, file.name, file.size, file.type);
        
        // Validar tipo
        if (!file.type.startsWith('image/')) {
            alert(`${file.name} no es una imagen válida`);
            continue;
        }
        
        // Validar tamaño (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert(`${file.name} es muy grande (máximo 5MB)`);
            continue;
        }
        
        // Limitar a 5 imágenes
        if (galeria.children.length >= 5) {
            alert('Máximo 5 imágenes permitidas');
            break;
        }
        
        // Guardar en memoria
        window.imagenesEnMemoria.general.push(file);
        console.log(`✅ Imagen guardada en memoria: ${file.name}`);
        
        // Mostrar preview
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'imagen-preview';
            div.innerHTML = `
                <img src="${e.target.result}" alt="Imagen">
                <button type="button" class="btn-eliminar-imagen" onclick="eliminarImagenNueva(this)">✕</button>
            `;
            galeria.appendChild(div);
        };
        reader.readAsDataURL(file);
        
        contador++;
    }
    
    console.log(`📸 Total en memoria (general): ${window.imagenesEnMemoria.general.length}`);
}

// Eliminar imagen nueva
function eliminarImagenNueva(btn) {
    const div = btn.closest('.imagen-preview');
    const img = div.querySelector('img');
    const src = img.src;
    
    // Encontrar y eliminar del array
    const index = imagenesNuevas.findIndex(f => {
        const reader = new FileReader();
        reader.onload = (e) => e.target.result === src;
        return false; // Simplificar: solo eliminar del DOM
    });
    
    div.remove();
}

// Eliminar imagen existente
function eliminarImagenExistente(url, btn) {
    btn.closest('.imagen-preview').remove();
    // Marcar para eliminación (se puede hacer en backend)
}

</script>
@endpush

@endsection
