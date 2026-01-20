@extends('layouts.asesores')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/create-friendly-refactored.css') }}">
<link rel="stylesheet" href="{{ asset('css/asesores/create-friendly.css') }}">
<link rel="stylesheet" href="{{ asset('css/asesores/create-prenda.css') }}">
<style>
    /* Desactivar navbar */
    header {
        display: none !important;
    }

    /* Animaciones para toast */
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }

    /* Responsive para COLOR, TELA Y REFERENCIA y VARIACIONES ESPECÍFICAS */
    @media (min-width: 769px) {
        .color-tela-tabla-desktop {
            display: table !important;
        }
        .color-tela-cards-mobile {
            display: none !important;
        }
        .variaciones-tabla-desktop {
            display: table !important;
        }
        .variaciones-cards-mobile {
            display: none !important;
        }
    }

    @media (max-width: 768px) {
        .color-tela-tabla-desktop {
            display: none !important;
        }
        .color-tela-cards-mobile {
            display: block !important;
        }
        .variaciones-tabla-desktop {
            display: none !important;
        }
        .variaciones-cards-mobile {
            display: block !important;
        }
        
        /* Mejorar responsive para dispositivos pequeños */
        .form-row {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .form-col {
            width: 100% !important;
        }
        
        /* Tallas - Responsive mejorado */
        .talla-tipo-select,
        .talla-genero-select,
        .talla-modo-select,
        .talla-desde,
        .talla-hasta {
            max-width: 100% !important;
            width: 100% !important;
            font-size: 0.9rem !important;
        }
        
        .talla-rango-selectors {
            flex-direction: column !important;
        }
        
        .talla-rango-selectors select,
        .btn-agregar-rango {
            width: 100% !important;
        }
        
        .talla-botones-container {
            width: 100% !important;
            justify-content: flex-start;
        }
        
        .btn-agregar-tallas-seleccionadas {
            width: 100% !important;
            margin-top: 0.5rem;
        }
        
        /* Selectores en una línea scrolleable en móvil */
        div[style*="display: flex"][style*="gap: 0.75rem"] {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
    }
    
    /* Breakpoint para tablets y dispositivos medianos */
    @media (max-width: 1024px) {
        /* Mejorar espaciado en tablets */
        .producto-section {
            padding: 1rem;
        }
        
        .section-title {
            font-size: 0.95rem;
        }
    }
</style>

@endpush

@section('content')
<div class="page-wrapper">
    <div class="content-wrapper">
        <!-- Header Moderno -->
        <div class="header-prenda">
            <div class="header-title">
                <span class="material-symbols-rounded header-icon">checkroom</span>
                <div>
                    <h2>Cotización de Prenda</h2>
                    <p>Completa los datos de la cotización de prendas</p>
                </div>
            </div>
            
            <!-- Campos del Header en una fila -->
            <div class="header-grid">
                <!-- Cliente -->
                <div class="header-field">
                    <label class="header-label">Cliente</label>
                    <input type="text" id="header-cliente" placeholder="Nombre del cliente" class="header-input">
                </div>
                
                <!-- Asesor -->
                <div class="header-field">
                    <label class="header-label">Asesor</label>
                    <input type="text" id="header-asesor" value="{{ auth()->user()->name }}" readonly class="header-input">
                </div>
                
                <!-- Tipo de Cotización -->
                <div class="header-field">
                    <label class="header-label">Tipo</label>
                    <div>
                        <select id="header-tipo-venta" name="tipo_venta" class="header-select">
                            <option value="">Selecciona</option>
                            <option value="M">M</option>
                            <option value="D">D</option>
                            <option value="X">X</option>
                        </select>
                        <div id="error-tipo-venta" class="header-error">Campo requerido</div>
                    </div>
                </div>
                
                <!-- Fecha -->
                <div class="header-field">
                    <label class="header-label">Fecha</label>
                    <input type="date" id="header-fecha" value="{{ date('Y-m-d') }}" class="header-input">
                </div>
            </div>
        </div>

    <div class="form-container">
        <form id="cotizacionPrendaForm">
            @csrf

            <!-- Campos ocultos para sincronizar con el header -->
            <input type="text" id="cliente" name="cliente" class="hidden-input">
            <input type="text" id="asesora" name="asesora" value="{{ auth()->user()->name }}" readonly class="hidden-input">
            <input type="date" id="fecha" name="fecha" class="hidden-input">
            <input type="text" id="tipo_cotizacion" name="tipo_cotizacion" class="hidden-input">

            <!-- TIPO DE COTIZACIÓN -->
            <!-- Ahora está en el header -->

            <!-- CONTENEDOR DE PRENDAS -->
            <div class="form-section">
                <div class="productos-container" id="productosContainer">
                    <!-- Los productos se agregan aquí dinámicamente -->
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="location.href='{{ route('asesores.pedidos.index') }}'">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <div style="display: flex; gap: 0.5rem; flex: 1; justify-content: flex-end;">
                    <button type="button" class="btn btn-success" id="btnGuardarBorrador">
                        <i class="fas fa-save"></i> Guardar Borrador
                    </button>
                    <button type="button" class="btn btn-primary" id="btnEnviar">
                        <i class="fas fa-paper-plane"></i> Enviar
                    </button>
                </div>
            </div>
        </form>
    </div>
    </div>

    <!-- Botón flotante para agregar prenda (IGUAL AL PASO 2) -->
    <div style="position: fixed; bottom: 30px; right: 30px; z-index: 1000;">
        <!-- Menú flotante -->
        <div id="menuFlotante" style="display: none; position: absolute; bottom: 70px; right: 0; background: white; border-radius: 12px; box-shadow: 0 5px 40px rgba(0,0,0,0.16); overflow: hidden; min-width: 200px;">
            <button type="button" onclick="agregarProductoPrenda(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 12px 16px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.9rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s; border-bottom: 1px solid #f0f0f0;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                <i class="fas fa-plus" style="color: #1e40af; font-size: 1.1rem;"></i>
                <span>Agregar Prenda</span>
            </button>
            <button type="button" onclick="abrirModalEspecificaciones(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 12px 16px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.9rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                <i class="fas fa-sliders-h" style="color: #3B82F6; font-size: 1.1rem;"></i>
                <span>Especificaciones</span>
            </button>
        </div>
        
        <!-- Botón principal flotante -->
        <button type="button" id="btnFlotante" onclick="console.log('🔵 CLICK EN BOTÓN'); const menu = document.getElementById('menuFlotante'); console.log('Display actual:', menu.style.display); console.log('Computed display:', window.getComputedStyle(menu).display); menu.style.display = menu.style.display === 'none' ? 'block' : 'none'; console.log('Display nuevo:', menu.style.display); console.log('Computed display nuevo:', window.getComputedStyle(menu).display); this.style.transform = menu.style.display === 'block' ? 'scale(1) rotate(45deg)' : 'scale(1) rotate(0deg)'; console.log('Transform:', this.style.transform); setTimeout(() => { console.log('Después de 100ms - Display:', menu.style.display, 'Computed:', window.getComputedStyle(menu).display); }, 100);" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #0ea5e9); color: white; border: none; cursor: pointer; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.4); transition: all 0.3s ease; position: relative;" onmouseover="this.style.boxShadow='0 6px 20px rgba(30, 64, 175, 0.5)'; this.style.transform='scale(1.1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')" onmouseout="this.style.boxShadow='0 4px 12px rgba(30, 64, 175, 0.4)'; this.style.transform='scale(1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')">
            <i class="fas fa-plus"></i>
        </button>
    </div>
</div>

<!-- MODAL: ESPECIFICACIONES DE LA ORDEN -->
<div id="modalEspecificaciones" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 950px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 3px solid #0066cc; padding-bottom: 1.5rem;">
            <h3 style="margin: 0; color: #0066cc; font-size: 1.4rem; font-weight: 700;"><i class="fas fa-cog" style="margin-right: 10px;"></i>ESPECIFICACIONES DE LA ORDEN</h3>
            <button type="button" onclick="cerrarModalEspecificaciones()" style="background: #f0f0f0; border: none; font-size: 1.5rem; cursor: pointer; color: #666; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <table class="tabla-control-compacta" style="width: 100%; border-collapse: collapse; background: white;">
            <thead>
                <tr style="background: linear-gradient(135deg, #0066cc, #0052a3); color: white;">
                    <th style="width: 20%; text-align: left; padding: 12px; font-weight: 600; border: none;">CONCEPTO</th>
                    <th style="width: 10%; text-align: center; padding: 12px; font-weight: 600; border: none;">APLICA</th>
                    <th style="width: 60%; text-align: left; padding: 12px; font-weight: 600; border: none;">OBSERVACIONES</th>
                    <th style="width: 10%; text-align: center; padding: 12px; font-weight: 600; border: none;">ACCIÓN</th>
                </tr>
            </thead>
            <tbody>
                <!-- DISPONIBILIDAD -->
                <tr class="fila-grupo">
                    <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0066cc;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #0066cc;"><i class="fas fa-warehouse" style="margin-right: 8px;"></i>DISPONIBILIDAD</span>
                            <button type="button" onclick="agregarFilaEspecificacion('disponibilidad')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                            </button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_disponibilidad">
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Bodega</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[bodega_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Cúcuta</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[cucuta_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Lafayette</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[lafayette_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Fábrica</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[fabrica_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>

                <!-- PAGO -->
                <tr class="fila-grupo">
                    <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0066cc;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #0066cc;"><i class="fas fa-credit-card" style="margin-right: 8px;"></i>FORMA DE PAGO</span>
                            <button type="button" onclick="agregarFilaEspecificacion('pago')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                            </button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_pago">
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Contado</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[pago_contado_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Crédito</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[pago_credito_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>

                <!-- RÉGIMEN -->
                <tr class="fila-grupo">
                    <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0066cc;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #0066cc;"><i class="fas fa-building" style="margin-right: 8px;"></i>RÉGIMEN</span>
                            <button type="button" onclick="agregarFilaEspecificacion('regimen')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                            </button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_regimen">
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Común</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[regimen_comun_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Simplificado</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[regimen_simp_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>

                <!-- SE HA VENDIDO -->
                <tr class="fila-grupo">
                    <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0066cc;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #0066cc;"><i class="fas fa-chart-bar" style="margin-right: 8px;"></i>SE HA VENDIDO</span>
                            <button type="button" onclick="agregarFilaEspecificacion('vendido')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                            </button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_vendido">
                    <tr>
                        <td><input type="text" name="tabla_orden[vendido_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[vendido_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>

                <!-- ÚLTIMA VENTA -->
                <tr class="fila-grupo">
                    <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0066cc;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #0066cc;"><i class="fas fa-money-bill-wave" style="margin-right: 8px;"></i>ÚLTIMA VENTA</span>
                            <button type="button" onclick="agregarFilaEspecificacion('ultima_venta')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                            </button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_ultima_venta">
                    <tr>
                        <td><input type="text" name="tabla_orden[ultima_venta_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[ultima_venta_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>

                <!-- FLETE DE ENVÍO -->
                <tr class="fila-grupo">
                    <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0066cc;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #0066cc;"><i class="fas fa-truck" style="margin-right: 8px;"></i>FLETE DE ENVÍO</span>
                            <button type="button" onclick="agregarFilaEspecificacion('flete')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                            </button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_flete">
                    <tr>
                        <td><input type="text" name="tabla_orden[flete_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[flete_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
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

<!-- TEMPLATE PARA PRODUCTO DE PRENDA -->
<template id="productoTemplate">
    <div class="producto-card" data-producto-id="">
        <div class="producto-header">
            <h4 class="producto-titulo">PRENDA <span class="numero-producto">1</span></h4>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn-toggle-product" onclick="toggleProductoBody(this)" title="Expandir/Contraer" style="font-size: 1.5rem; line-height: 1; font-weight: bold;">▼</button>
                <button type="button" class="btn-remove-product" onclick="eliminarProductoPrenda(this)" title="Eliminar prenda">&times;</button>
            </div>
        </div>
        <div class="producto-body" style="display: block;">
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-shirt"></i> TIPO DE PRENDA</div>
                <div class="form-row tipo-prenda-row" style="display: flex; gap: 12px; align-items: flex-start;">
                    <div class="form-col full" style="flex: 1;">
                        <label style="font-size: 0.7rem; font-weight: 600; margin-bottom: 0.3rem; display: block;"><i class="fas fa-list"></i> SELECCIONA O ESCRIBE EL TIPO *</label>
                        <div class="prenda-search-container">
                            <input type="text" name="productos_prenda[][nombre_producto]" class="prenda-search-input input-large" placeholder="BUSCA O ESCRIBE (CAMISA, CAMISETA, POLO...)" required onkeyup="buscarPrendas(this); mostrarSelectorVariantes(this);" onchange="actualizarResumenPrenda(); mostrarSelectorVariantes(this);" style="font-size: 0.75rem; padding: 0.4rem;">
                            <div class="prenda-suggestions">
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👔 CAMISA', this)">👔 CAMISA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda(' CAMISETA', this)"> CAMISETA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🎽 POLO', this)">🎽 POLO</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👖 PANTALÓN', this)">👖 PANTALÓN</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👗 FALDA', this)">👗 FALDA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🧥 CHAQUETA', this)">🧥 CHAQUETA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🧢 SUDADERA', this)">🧢 SUDADERA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('❓ OTRO', this)">❓ OTRO</div>
                            </div>
                        </div>
                        <small class="help-text" style="font-size: 0.65rem; margin-top: 0.2rem; display: block;">PUEDES BUSCAR, SELECCIONAR O ESCRIBIR UNA PRENDA PERSONALIZADA</small>
                    </div>
                    <!-- Selector de Tipo de JEAN/PANTALÓN - Oculto por defecto -->
                    <div class="tipo-jean-pantalon-inline" style="display: none; width: 280px; padding: 0; background: transparent; border: none; border-radius: 0; margin-left: 12px; flex-shrink: 0;">
                        <div class="tipo-jean-pantalon-inline-container" style="display: flex; flex-direction: column; gap: 4px;">
                            <!-- El selector se inserta aquí dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE PRENDA DE BODEGA -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-warehouse"></i> PRENDA DE BODEGA</div>
                <div class="form-row" style="display: flex; gap: 12px; align-items: center;">
                    <div class="form-col full" style="flex: 1;">
                        <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; font-size: 0.85rem;">
                            <input type="checkbox" name="productos_prenda[][variantes][prenda_bodega]" class="prenda-bodega-checkbox" value="true" style="width: 18px; height: 18px; accent-color: #0066cc; cursor: pointer;">
                            <span style="color: #333; font-weight: 500;">Marcar si esta prenda viene de bodega</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE DESCRIPCIÓN -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-sticky-note"></i> DESCRIPCIÓN</div>
                    <div class="form-col full">
                        <textarea name="productos_prenda[][descripcion]" class="input-large" placeholder="DESCRIPCIÓN DE LA PRENDA..." rows="2" style="font-size: 0.75rem; padding: 0.4rem; min-height: 50px;"></textarea>
                        <small class="help-text" style="font-size: 0.65rem; margin-top: 0.2rem; display: block;">DESCRIBE LA PRENDA, DETALLES ESPECIALES, ETC.</small>
                    </div>
            </div>

            <!-- SECCIÓN DE COLOR, TELA Y REFERENCIA -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-palette"></i> COLOR, TELA Y REFERENCIA</div>
                <div class="form-row">
                    <div class="form-col full">
                        <!-- Vista Desktop (tabla) -->
                        <table style="width: 100%; border-collapse: collapse; background: white; display: none;" class="color-tela-tabla-desktop">
                            <thead>
                                <tr style="background-color: #f0f0f0; border-bottom: 2px solid #0066cc;">
                                    <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd; font-size: 0.7rem;">
                                        <i class="fas fa-palette"></i> Color
                                    </th>
                                    <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd; font-size: 0.7rem;">
                                        <i class="fas fa-cloth"></i> Tela
                                    </th>
                                    <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd; font-size: 0.7rem;">
                                        <i class="fas fa-barcode"></i> Referencia
                                    </th>
                                    <th style="padding: 6px 8px; text-align: center; font-weight: 600; color: #0066cc; font-size: 0.7rem;">
                                        <i class="fas fa-image"></i> Imagen Tela
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="telas-tbody">
                                <tr style="border-bottom: 1px solid #ddd;" class="fila-tela" data-tela-index="0">
                                    <td style="padding: 6px 8px; border-right: 1px solid #ddd;">
                                        <div style="position: relative;">
                                            <input type="text" name="productos_prenda[][variantes][color]" class="color-input" placeholder="Buscar o crear color..." required style="width: 100%; padding: 0.4rem; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.75rem;">
                                        </div>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid #ddd;">
                                        <div style="position: relative;">
                                            <input type="text" name="productos_prenda[][variantes][tela]" class="tela-input" placeholder="Buscar o crear tela..." required style="width: 100%; padding: 0.4rem; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.75rem;">
                                        </div>
                                    </td>
                                    <td style="padding: 6px 8px; border-right: 1px solid #ddd;">
                                        <input type="text" name="productos_prenda[][variantes][referencia]" class="referencia-input" placeholder="Ej: REF-NAP-001" required style="width: 100%; padding: 0.4rem; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.75rem;">
                                    </td>
                                    <td style="padding: 6px 8px; text-align: center;">
                                        <label style="display: block; min-height: 45px; padding: 0.3rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="event.preventDefault(); if(event.dataTransfer.files) this.querySelector('input').files = event.dataTransfer.files; this.querySelector('input').onchange && this.querySelector('input').onchange();" ondragover="event.preventDefault(); this.style.background='#e8f4f8';" ondragleave="this.style.background='#f0f7ff';">
                                            <input type="file" name="productos_prenda[][telas][]" class="input-file-tela" accept="image/*" multiple onchange="agregarFotosAlProducto(this)" style="display: none;">
                                            <div class="drop-zone-content" style="font-size: 0.6rem;">
                                                <i class="fas fa-cloud-upload-alt" style="font-size: 0.7rem; color: #0066cc;"></i>
                                                <p style="margin: 0.1rem 0; color: #0066cc; font-weight: 500; font-size: 0.65rem;">ARRASTRA O CLIC</p>
                                                <small style="color: #666; font-size: 0.6rem;">(Máx. 3)</small>
                                            </div>
                                        </label>
                                        <div class="foto-tela-preview" style="display: grid; grid-template-columns: repeat(3, 40px); gap: 0.3rem; margin-top: 0.3rem; justify-content: center;"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Vista Mobile (cards) -->
                        <div style="display: none;" class="color-tela-cards-mobile">
                            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 14px;">
                                <label style="font-size: 0.95rem; font-weight: 700; color: #0066cc; display: block; margin-bottom: 10px;"><i class="fas fa-palette"></i> COLOR</label>
                                <input type="text" name="productos_prenda[][variantes][color]" class="color-input" placeholder="Buscar o crear color..." required style="width: 100%; padding: 14px 12px; border: 2px solid #0066cc; border-radius: 6px; font-size: 1rem; box-sizing: border-box; min-height: 48px; transition: all 0.2s ease;">
                            </div>
                            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 14px;">
                                <label style="font-size: 0.95rem; font-weight: 700; color: #0066cc; display: block; margin-bottom: 10px;"><i class="fas fa-cloth"></i> TELA</label>
                                <input type="text" name="productos_prenda[][variantes][tela]" class="tela-input" placeholder="Buscar o crear tela..." required style="width: 100%; padding: 14px 12px; border: 2px solid #0066cc; border-radius: 6px; font-size: 1rem; box-sizing: border-box; min-height: 48px; transition: all 0.2s ease;">
                            </div>
                            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 14px;">
                                <label style="font-size: 0.95rem; font-weight: 700; color: #0066cc; display: block; margin-bottom: 10px;"><i class="fas fa-barcode"></i> REFERENCIA</label>
                                <input type="text" name="productos_prenda[][variantes][referencia]" class="referencia-input" placeholder="Ej: REF-NAP-001" required style="width: 100%; padding: 14px 12px; border: 2px solid #0066cc; border-radius: 6px; font-size: 1rem; box-sizing: border-box; min-height: 48px; transition: all 0.2s ease;">
                            </div>
                            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 16px;">
                                <label style="font-size: 0.95rem; font-weight: 700; color: #0066cc; display: block; margin-bottom: 12px;"><i class="fas fa-image"></i> IMAGEN TELA</label>
                                <label style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 120px; padding: 16px; border: 3px dashed #0066cc; border-radius: 8px; cursor: pointer; text-align: center; background: #f0f7ff; transition: all 0.2s ease;" ondrop="event.preventDefault(); if(event.dataTransfer.files) this.querySelector('input').files = event.dataTransfer.files; this.querySelector('input').onchange && this.querySelector('input').onchange();" ondragover="event.preventDefault(); this.style.background='#e8f4f8'; this.style.borderColor='#0052a3';" ondragleave="this.style.background='#f0f7ff'; this.style.borderColor='#0066cc';">
                                    <input type="file" name="productos_prenda[][telas][]" class="input-file-tela" accept="image/*" multiple onchange="agregarFotosAlProducto(this)" style="display: none;">
                                    <div class="drop-zone-content" style="font-size: 1rem;">
                                        <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #0066cc; margin-bottom: 8px; display: block;"></i>
                                        <p style="margin: 6px 0; color: #0066cc; font-weight: 600; font-size: 0.95rem;">ARRASTRA O CLIC</p>
                                        <small style="color: #666; font-size: 0.85rem;">(Máx. 3 imágenes)</small>
                                    </div>
                                </label>
                                <div class="foto-tela-preview" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 12px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE FOTOS DE LA PRENDA -->
            <div class="producto-section">
                <button type="button" class="section-title-btn" onclick="toggleSeccion(this)" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer; padding: 0;">
                    <div class="section-title">
                        <i class="fas fa-images"></i> FOTOS DE LA PRENDA (MÁX. 3)
                        <i class="fas fa-chevron-down" style="margin-left: auto; transition: transform 0.3s ease;"></i>
                    </div>
                </button>
                <div class="section-content">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.3rem; color: #0066cc; font-size: 0.7rem;">
                            <i class="fas fa-image"></i> FOTOS PRENDA
                        </label>
                        <label style="display: block; min-height: 50px; padding: 0.4rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="event.preventDefault(); if(event.dataTransfer.files) this.querySelector('input').files = event.dataTransfer.files; this.querySelector('input').onchange && this.querySelector('input').onchange();" ondragover="event.preventDefault(); this.style.background='#e8f4f8';" ondragleave="this.style.background='#f0f7ff';">
                            <input type="file" name="productos_prenda[][fotos][]" class="input-file-single" accept="image/*" multiple onchange="agregarFotosAlProducto(this)" style="display: none;">
                            <div class="drop-zone-content" style="font-size: 0.6rem;">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 0.8rem; color: #0066cc;"></i>
                                <p style="margin: 0.1rem 0; color: #0066cc; font-weight: 500; font-size: 0.65rem;">ARRASTRA O CLIC</p>
                            </div>
                        </label>
                        <div class="fotos-preview" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.3rem; margin-top: 0.3rem;"></div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE VARIACIONES ESPECÍFICAS -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-sliders-h"></i> VARIACIONES ESPECÍFICAS</div>
                <div class="form-row">
                    <div class="form-col full">
                        <!-- Vista Desktop (tabla) -->
                        <table style="width: 100%; border-collapse: collapse; background: white; margin: 0; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; display: none;" class="variaciones-tabla-desktop">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #0066cc, #0052a3); border-bottom: 2px solid #0066cc;">
                                    <th style="padding: 14px 12px; text-align: center; font-weight: 600; color: white; border-right: 1px solid #0052a3; width: 60px;">
                                        <i class="fas fa-check-circle"></i>
                                    </th>
                                    <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: white; border-right: 1px solid #0052a3; width: 160px;">
                                        <i class="fas fa-list"></i> Variación
                                    </th>
                                    <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: white;">
                                        <i class="fas fa-comment"></i> Observación
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- MANGA -->
                                <tr style="border-bottom: 1px solid #eee; background-color: #fafafa;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" name="productos_prenda[][variantes][aplica_manga]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;" onchange="toggleMangaInput(this)">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-shirt"></i> Manga
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <div style="display: flex; gap: 8px; align-items: flex-start;">
                                            <div style="position: relative; flex: 1;">
                                                <input type="text" class="manga-input" placeholder="Buscar tipo..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s; opacity: 0.5; pointer-events: none;" onkeyup="buscarManga(this)" onkeypress="if(event.key==='Enter') crearMangaDesdeInput(this)" disabled>
                                                <input type="hidden" name="productos_prenda[][variantes][tipo_manga_id]" class="manga-id-input" value="">
                                                <div class="manga-suggestions" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 150px; overflow-y: auto; z-index: 1000; width: 100%; display: none; margin-top: 2px; top: 100%; left: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                                            </div>
                                            <input type="text" name="productos_prenda[][variantes][obs_manga]" placeholder="Ej: manga larga..." style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- BOLSILLOS -->
                                <tr style="border-bottom: 1px solid #eee; background-color: white;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" name="productos_prenda[][variantes][aplica_bolsillos]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-square"></i> Bolsillos
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="text" name="productos_prenda[][variantes][obs_bolsillos]" placeholder="Ej: 4 bolsillos, con cierre..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                                    </td>
                                </tr>
                                
                                <!-- BROCHE/BOTÓN -->
                                <tr style="border-bottom: 1px solid #eee; background-color: #fafafa;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" name="productos_prenda[][variantes][aplica_broche]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-link"></i> Broche/Botón
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <div style="display: flex; gap: 8px; align-items: center;">
                                            <select name="productos_prenda[][variantes][tipo_broche_id]" style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; background-color: white; cursor: pointer; transition: border-color 0.2s; box-sizing: border-box;">
                                                <option value="">Seleccionar...</option>
                                                <option value="1">Broche</option>
                                                <option value="2">Botón</option>
                                            </select>
                                            <input type="text" name="productos_prenda[][variantes][obs_broche]" placeholder="Ej: Botones de madera..." style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- REFLECTIVO -->
                                <tr style="border-bottom: 1px solid #eee; background-color: white;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" name="productos_prenda[][variantes][aplica_reflectivo]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-star"></i> Reflectivo
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="text" name="productos_prenda[][variantes][obs_reflectivo]" placeholder="Ej: En brazos y espalda..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Vista Mobile (cards) -->
                        <div style="display: none;" class="variaciones-cards-mobile">
                            <!-- MANGA -->
                            <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                    <input type="checkbox" name="productos_prenda[][variantes][aplica_manga]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;" onchange="toggleMangaInput(this)">
                                    <label style="font-weight: 600; color: #0066cc; cursor: pointer; flex: 1;"><i class="fas fa-shirt"></i> Manga</label>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <div style="position: relative;">
                                        <input type="text" class="manga-input" placeholder="Buscar tipo..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s; opacity: 0.5; pointer-events: none;" onkeyup="buscarManga(this)" onkeypress="if(event.key==='Enter') crearMangaDesdeInput(this)" disabled>
                                        <input type="hidden" name="productos_prenda[][variantes][tipo_manga_id]" class="manga-id-input" value="">
                                        <div class="manga-suggestions" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 150px; overflow-y: auto; z-index: 1000; width: 100%; display: none; margin-top: 2px; top: 100%; left: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                                    </div>
                                    <input type="text" name="productos_prenda[][variantes][obs_manga]" placeholder="Ej: manga larga..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                                </div>
                            </div>

                            <!-- BOLSILLOS -->
                            <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                    <input type="checkbox" name="productos_prenda[][variantes][aplica_bolsillos]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    <label style="font-weight: 600; color: #0066cc; cursor: pointer; flex: 1;"><i class="fas fa-square"></i> Bolsillos</label>
                                </div>
                                <input type="text" name="productos_prenda[][variantes][obs_bolsillos]" placeholder="Ej: 4 bolsillos, con cierre..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                            </div>

                            <!-- BROCHE/BOTÓN -->
                            <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                    <input type="checkbox" name="productos_prenda[][variantes][aplica_broche]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    <label style="font-weight: 600; color: #0066cc; cursor: pointer; flex: 1;"><i class="fas fa-link"></i> Broche/Botón</label>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <select name="productos_prenda[][variantes][tipo_broche_id]" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; background-color: white; cursor: pointer; transition: border-color 0.2s; box-sizing: border-box;">
                                        <option value="">Seleccionar...</option>
                                        <option value="1">Broche</option>
                                        <option value="2">Botón</option>
                                    </select>
                                    <input type="text" name="productos_prenda[][variantes][obs_broche]" placeholder="Ej: Botones de madera..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                                </div>
                            </div>

                            <!-- REFLECTIVO -->
                            <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                    <input type="checkbox" name="productos_prenda[][variantes][aplica_reflectivo]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    <label style="font-weight: 600; color: #0066cc; cursor: pointer; flex: 1;"><i class="fas fa-star"></i> Reflectivo</label>
                                </div>
                                <input type="text" name="productos_prenda[][variantes][obs_reflectivo]" placeholder="Ej: En brazos y espalda..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                            </div>
                        </div>

                        <style>
                            @media (max-width: 768px) {
                                .variaciones-tabla-desktop {
                                    display: none !important;
                                }
                                .variaciones-cards-mobile {
                                    display: block !important;
                                }
                            }
                            @media (min-width: 769px) {
                                .variaciones-tabla-desktop {
                                    display: table !important;
                                }
                                .variaciones-cards-mobile {
                                    display: none !important;
                                }
                            }
                        </style>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE TALLAS -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-ruler"></i> TALLAS A COTIZAR</div>
                <div class="form-row">
                    <div class="form-col full">
                        <!-- Fila 1: Selectores de tipo, género y modo -->
                        <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
                            <select class="talla-tipo-select" onchange="actualizarSelectTallas(this)" style="padding: 0.4rem 0.6rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.75rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 280px;">
                                <option value="">Selecciona tipo de talla</option>
                                <option value="letra">LETRAS (XS, S, M, L, XL...)</option>
                                <option value="numero">NÚMEROS (DAMA/CABALLERO)</option>
                            </select>
                            
                            <select class="talla-genero-select" style="padding: 0.4rem 0.6rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.75rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 180px; display: none;">
                                <option value="">Selecciona género</option>
                                <option value="dama">Dama</option>
                                <option value="caballero">Caballero</option>
                            </select>
                            
                            <select class="talla-modo-select" style="padding: 0.4rem 0.6rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.75rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 180px; display: none;">
                                <option value="">Selecciona modo</option>
                                <option value="manual">Manual</option>
                                <option value="rango">Rango (Desde-Hasta)</option>
                            </select>
                            
                            <!-- Selectores de rango (aparecen cuando se selecciona Rango) -->
                            <div class="talla-rango-selectors" style="display: none; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                                <select class="talla-desde" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 150px;">
                                    <option value="">Desde</option>
                                </select>
                                <span style="color: #0066cc; font-weight: 600;">hasta</span>
                                <select class="talla-hasta" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 150px;">
                                    <option value="">Hasta</option>
                                </select>
                                <button type="button" class="btn-agregar-rango" onclick="agregarTallasRango(this)" style="padding: 0.6rem 1rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; white-space: nowrap;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Fila 2: Botones de tallas (Modo Manual) -->
                        <div class="talla-botones" style="display: none; margin-bottom: 1.5rem;">
                            <p style="margin: 0 0 0.75rem 0; font-size: 0.85rem; font-weight: 600; color: #0066cc;">Selecciona tallas:</p>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                                <div class="talla-botones-container" style="display: flex; flex-wrap: wrap; gap: 0.5rem; flex: 1;">
                                </div>
                                <button type="button" class="btn-agregar-tallas-seleccionadas" onclick="agregarTallasSeleccionadas(this)" style="padding: 0.6rem 1rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; white-space: nowrap; flex-shrink: 0;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Fila 3: Tallas agregadas -->
                        <div class="tallas-section" style="display: none; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 0.75rem 0; font-size: 0.85rem; font-weight: 600; color: #0066cc;">Tallas seleccionadas:</p>
                            <div class="tallas-agregadas" style="display: flex; flex-wrap: wrap; gap: 0.5rem; min-height: 35px;">
                                <input type="hidden" name="productos_prenda[][tallas]" class="tallas-hidden" value="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
// Funciones de compatibilidad - Estos mapean a los módulos SOLID
// Se mantienen para compatibilidad con código existente

// Agregar producto (delegado a CotizacionPrendaApp)
function agregarProductoPrenda() {
    if (window.app && typeof window.app.onAgregarProducto === 'function') {
        window.app.onAgregarProducto();
    }
}

// Eliminar producto (delegado a ProductoModule)
function eliminarProductoPrenda(btn) {
    if (window.productoModule && typeof window.productoModule.eliminarProducto === 'function') {
        const card = btn.closest('.producto-card');
        window.productoModule.eliminarProducto(card);
    }
}

// Toggle producto (delegado a ProductoModule)
function toggleProductoBody(btn) {
    if (window.productoModule && typeof window.productoModule.toggleProductoBody === 'function') {
        const card = btn.closest('.producto-card');
        window.productoModule.toggleProductoBody(card);
    }
}

// Actualizar selector de tallas (delegado a TallasModule)
function actualizarSelectTallas(select) {
    if (window.tallasModule && typeof window.tallasModule.actualizarSelectTallas === 'function') {
        window.tallasModule.actualizarSelectTallas(select);
    }
}

// Agregar tallas desde rango (delegado a TallasModule)
function agregarTallasRango(btn) {
    if (window.tallasModule && typeof window.tallasModule.agregarTallasRango === 'function') {
        window.tallasModule.agregarTallasRango(btn);
    }
}

// Agregar tallas seleccionadas (delegado a TallasModule)
function agregarTallasSeleccionadas(btn) {
    if (window.tallasModule && typeof window.tallasModule.agregarTallasSeleccionadas === 'function') {
        window.tallasModule.agregarTallasSeleccionadas(btn);
    }
}

// Guardar cotización (delegado a CotizacionPrendaApp)
function guardarCotizacionPrenda(action) {
    if (window.app && typeof window.app.guardar === 'function') {
        window.app.guardar(action);
    }
}

// Actualizar resumen (función legacy)
function actualizarResumenPrenda() {
    console.log('Resumen actualizado');
}

// Buscar prendas
function buscarPrendas(input) {
    const valor = input.value.toLowerCase();
    const suggestions = input.closest('.prenda-search-container').querySelector('.prenda-suggestions');
    const items = suggestions.querySelectorAll('.prenda-suggestion-item');
    
    items.forEach(item => {
        if (item.textContent.toLowerCase().includes(valor)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Seleccionar prenda
function seleccionarPrenda(prenda, element) {
    const input = element.closest('.prenda-search-container').querySelector('.prenda-search-input');
    input.value = prenda;
    element.closest('.prenda-search-container').querySelector('.prenda-suggestions').style.display = 'none';
    mostrarSelectorVariantes(input);
    actualizarResumenPrenda();
}

// Mostrar selector de variantes (JEAN/PANTALÓN)
function mostrarSelectorVariantes(input) {
    const valor = input.value.toUpperCase();
    const container = input.closest('.tipo-prenda-row').querySelector('.tipo-jean-pantalon-inline');
    const innerContainer = container.querySelector('.tipo-jean-pantalon-inline-container');
    
    // Buscar variaciones de jean y pantalón (con y sin tilde, singular y plural)
    if (valor.includes('JEAN') || valor.includes('JEANS') || valor.includes('JEANES') || 
        valor.includes('PANTALON') || valor.includes('PANTALONES') || valor.includes('PANTALÓN') || valor.includes('PANTALÓNES')) {
        container.style.display = 'block';
        
        if (innerContainer.innerHTML === '') {
            innerContainer.innerHTML = `
                <label style="font-weight: 600; color: #0066cc; font-size: 0.85rem;">TIPO DE PRENDA</label>
                <input type="hidden" name="productos_prenda[][variantes][es_jean_pantalon]" class="es-jean-pantalon-hidden" value="0">
                <select name="productos_prenda[][variantes][tipo_jean_pantalon]" onchange="marcarEsJeanPantalon(this)" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600;">
                    <option value="">Selecciona</option>
                    <option value="METÁLICO">METÁLICO</option>
                    <option value="PLÁSTICO">PLÁSTICO</option>
                    <option value="SKINNY">SKINNY</option>
                    <option value="SLIM">SLIM</option>
                    <option value="RECTO">RECTO</option>
                    <option value="BOOTCUT">BOOTCUT</option>
                    <option value="FLARE">FLARE</option>
                    <option value="MOM">MOM</option>
                    <option value="OVERSIZE">OVERSIZE</option>
                    <option value="OTRO">OTRO</option>
                    <option value="NO APLICA">NO APLICA</option>
                </select>
            `;
        }
    } else {
        container.style.display = 'none';
        innerContainer.innerHTML = '';
    }
}

// Marcar es_jean_pantalon cuando se selecciona un tipo
function marcarEsJeanPantalon(select) {
    const container = select.closest('.tipo-jean-pantalon-inline-container');
    if (!container) return;
    
    const hiddenInput = container.querySelector('.es-jean-pantalon-hidden');
    if (!hiddenInput) return;
    
    // Si tiene un valor seleccionado (no vacío), marcar como 1
    hiddenInput.value = select.value && select.value !== '' ? '1' : '0';
    
    console.log(' es_jean_pantalon actualizado:', {
        tipo_jean_seleccionado: select.value,
        es_jean_pantalon: hiddenInput.value
    });
}

// Gestión de imágenes (delegado a scripts heredados)
function agregarFotosAlProducto(input) {
    const isTela = input.classList.contains('input-file-tela');
    const isFoto = input.classList.contains('input-file-single');
    
    if (isTela && typeof agregarFotoTela === 'function') {
        agregarFotoTela(input);
    } else if (isFoto && typeof agregarFotos === 'function') {
        const dropZone = input.closest('label');
        if (dropZone) {
            agregarFotos(input.files, dropZone);
        }
    }
}

// Modal de especificaciones (delegado a ModalModule)
function abrirModalEspecificaciones() {
    if (window.modalModule && typeof window.modalModule.openModal === 'function') {
        window.modalModule.openModal();
    }
}

function cerrarModalEspecificaciones() {
    if (window.modalModule && typeof window.modalModule.closeModal === 'function') {
        window.modalModule.closeModal();
    }
}

function guardarEspecificaciones() {
    if (window.modalModule && typeof window.modalModule.saveModal === 'function') {
        window.modalModule.saveModal();
    }
}

function agregarFilaEspecificacion(categoria) {
    if (window.especificacionesModule && typeof window.especificacionesModule.agregarFila === 'function') {
        window.especificacionesModule.agregarFila(categoria);
    }
}

// Monitorear cambios en menuFlotante
document.addEventListener('DOMContentLoaded', function() {
    const menu = document.getElementById('menuFlotante');
    if (menu) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    console.log(' ATRIBUTO STYLE CAMBIÓ:', menu.style.display);
                    console.trace('Stack trace:');
                }
            });
        });
        
        observer.observe(menu, {
            attributes: true,
            attributeFilter: ['style']
        });
    }
});

// Toggle de secciones
function toggleSeccion(btn) {
    const section = btn.closest('.producto-section');
    if (!section) return;
    
    const content = section.querySelector('.section-content');
    if (content) {
        const isVisible = content.style.display !== 'none';
        content.style.display = isVisible ? 'none' : 'block';
        btn.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
    }
}

// Función para manejar el autocompletado de manga
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('manga-input')) {
        const input = e.target;
        const valor = input.value.toLowerCase();
        const suggestions = input.closest('div').querySelector('.manga-suggestions');
        const items = suggestions.querySelectorAll('.manga-suggestion-item');
        
        // Mostrar sugerencias si hay texto
        if (valor.length > 0) {
            suggestions.style.display = 'block';
            items.forEach(item => {
                if (item.textContent.toLowerCase().includes(valor)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        } else {
            suggestions.style.display = 'none';
        }
    }
});

// Función para seleccionar manga
function seleccionarManga(valor, element) {
    const suggestionsDiv = element.closest('.manga-suggestions');
    const parentDiv = suggestionsDiv.closest('div');
    const input = parentDiv.querySelector('.manga-input');
    const idInput = parentDiv.querySelector('.manga-id-input');
    
    // Buscar el ID en los datos de mangas disponibles
    const mangaData = [
        { id: 1, nombre: 'Larga' },
        { id: 2, nombre: 'Corta' },
        { id: 3, nombre: '3/4' }
    ];
    
    // Buscar si existe en la lista conocida
    let mangaId = null;
    const existente = mangaData.find(m => m.nombre.toLowerCase() === valor.toLowerCase());
    if (existente) {
        mangaId = existente.id;
    } else {
        // Si no existe, usar el próximo ID disponible
        mangaId = Math.max(...mangaData.map(m => m.id), 0) + 1;
    }
    
    input.value = valor;
    if (idInput) {
        idInput.value = mangaId;
        console.log(` Manga seleccionada: ${valor} (ID: ${mangaId})`);
    }
    suggestionsDiv.style.display = 'none';
}

// Cerrar modal al hacer click fuera
document.addEventListener('click', function(e) {
    const modal = document.getElementById('modalEspecificaciones');
    if (e.target === modal && window.cerrarModalEspecificaciones) {
        cerrarModalEspecificaciones();
    }
});
</script>

@push('scripts')
<!-- Configuración de rutas -->
<script>
    window.routes = window.routes || {};
    window.routes.guardarCotizacion = '{{ route("asesores.cotizaciones.guardar") }}';
    window.routes.cotizacionesIndex = '{{ route("asesores.cotizaciones.index") }}';
    window.tipoCotizacionGlobal = 'P'; // Prenda
    
    @if(isset($cotizacion))
    // Datos de cotización para edición
    window.cotizacionParaEditar = {!! json_encode($cotizacion->toArray()) !!};
    console.log(' Cotización cargada para editar:', window.cotizacionParaEditar);
    @endif
</script>

<!-- Módulos SOLID - Orden de dependencias -->
<script src="{{ asset('js/asesores/cotizaciones/modules/ValidationModule.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/modules/TallasModule.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/modules/EspecificacionesModule.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/modules/ProductoModule.js') }}"></script>
<!-- Servicios HTTP -->
<script src="{{ asset('js/asesores/cotizaciones/services/HttpService.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/services/DebugService.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/modules/FormModule.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/modules/UIModule.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/modules/ModalModule.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/modules/CotizacionPrendaApp.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/modules/index.js') }}"></script>

<!-- Scripts de compatibilidad (mantener por ahora) -->
<script src="{{ asset('js/asesores/cotizaciones/tallas.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/persistencia.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/rutas.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/cotizaciones.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/productos.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/imagenes.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/especificaciones.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/guardado.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/cargar-borrador.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/imagen-borrador.js') }}"></script>
<script src="{{ asset('js/asesores/variantes-prendas.js') }}"></script>
<script src="{{ asset('js/asesores/color-tela-referencia.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/integracion-variantes-inline.js') }}"></script>

<script>
    // Configuración UI
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.remove('collapsed');
            console.log('✓ Sidebar expandido');
        }
        
        // Cargar datos de cotización si estamos editando
        if (window.cotizacionParaEditar) {
            console.log('🔄 Detectada cotización para editar, cargando datos...');
            setTimeout(() => {
                if (typeof cargarBorrador === 'function') {
                    cargarBorrador(window.cotizacionParaEditar);
                    console.log(' Datos de cotización cargados en el formulario');
                } else {
                    console.error(' Función cargarBorrador no disponible');
                }
            }, 500);
        }
    });
</script>
@endpush
@endsection