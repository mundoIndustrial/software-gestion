@extends('layouts.asesores')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos/create-reflectivo.css') }}">
@endpush

@section('content')
<div class="page-wrapper">
    <!-- Header Moderno -->
    <div style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); border-radius: 12px; padding: 1.25rem 1.75rem; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 1.5rem; align-items: start;">
            <!-- Título y descripción -->
            <div style="display: flex; align-items: center; gap: 0.75rem; grid-column: 1 / -1;">
                <span class="material-symbols-rounded" style="font-size: 1.75rem; color: white;">light_mode</span>
                <div>
                    <h2 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 700;">Cotización de Reflectivo</h2>
                    <p style="margin: 0.2rem 0 0 0; color: rgba(255,255,255,0.85); font-size: 0.8rem;">Completa los datos de la cotización</p>
                </div>
            </div>
            
            <!-- Campos del Header en una fila -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; grid-column: 1 / -1;">
                <!-- Cliente -->
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Cliente</label>
                    <input type="text" id="header-cliente" placeholder="Nombre del cliente" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>
                
                <!-- Asesor -->
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Asesor</label>
                    <input type="text" id="header-asesor" value="{{ auth()->user()->name }}" readonly style="width: 100%; background: rgba(255,255,255,0.9); border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; cursor: not-allowed;">
                </div>
                
                <!-- Fecha -->
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Fecha</label>
                    <input type="date" id="header-fecha" value="{{ date('Y-m-d') }}" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>
                
                <!-- Tipo para Cotizar -->
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
        </div>
    </div>

    <div class="form-container">
        <form id="cotizacionReflectivoForm">
            @csrf

            <!-- Campos ocultos para sincronizar con el header -->
            <input type="text" id="cliente" name="cliente" style="display: none;">
            <input type="text" id="asesora" name="asesora" value="{{ auth()->user()->name }}" readonly style="display: none;">
            <input type="date" id="fecha" name="fecha" style="display: none;">
            <input type="text" id="tipo_venta_reflectivo" name="tipo_venta_reflectivo" style="display: none;">
            <textarea id="especificaciones" name="especificaciones" style="display: none;"></textarea>
            <!-- CONTENEDOR DE PRENDAS -->
            <div id="prendas-contenedor">
                <!-- Las prendas se agregan aquí dinámicamente -->
            </div>

            <!-- Botones -->
            <div class="form-actions">
                <a href="{{ route('asesores.cotizaciones.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%); border: 2px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 600; color: #333; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                    <i class="fas fa-times" style="font-size: 0.9rem;"></i> Cancelar
                </a>
                <div style="display: flex; gap: 0.5rem; flex: 1; justify-content: flex-end;">
                    <button type="submit" name="action" value="borrador" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); border: 2px solid #3d8b40; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-save" style="font-size: 0.9rem;"></i> Guardar Borrador
                    </button>
                    <button type="submit" name="action" value="enviar" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); border: 2px solid #003d7a; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-paper-plane" style="font-size: 0.9rem;"></i> Enviar
                    </button>
                </div>
            </div>

            <!-- MENÚ FLOTANTE PARA AGREGAR PRENDA O ESPECIFICACIONES -->
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
                <button type="button" id="btnFlotante" onclick="const menu = document.getElementById('menuFlotante'); menu.style.display = menu.style.display === 'none' ? 'block' : 'none'; this.style.transform = menu.style.display === 'block' ? 'scale(1) rotate(45deg)' : 'scale(1) rotate(0deg)'" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #0ea5e9); color: white; border: none; cursor: pointer; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.4); transition: all 0.3s ease; position: relative;" onmouseover="this.style.boxShadow='0 6px 20px rgba(30, 64, 175, 0.5)'; this.style.transform='scale(1.1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')" onmouseout="this.style.boxShadow='0 4px 12px rgba(30, 64, 175, 0.4)'; this.style.transform='scale(1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- MODAL: ESPECIFICACIONES DEL REFLECTIVO -->
    <div id="modalEspecificaciones" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 950px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 3px solid #0284c7; padding-bottom: 1.5rem;">
                <h3 style="margin: 0; color: #0284c7; font-size: 1.4rem; font-weight: 700;"><i class="fas fa-cog" style="margin-right: 10px;"></i>ESPECIFICACIONES DEL REFLECTIVO</h3>
                <button type="button" onclick="cerrarModalEspecificaciones()" style="background: #f0f0f0; border: none; font-size: 1.5rem; cursor: pointer; color: #666; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <table class="tabla-control-compacta" style="width: 100%; border-collapse: collapse; background: white;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #0284c7, #0166a0); color: white;">
                        <th style="width: 20%; text-align: left; padding: 12px; font-weight: 600; border: none;">CONCEPTO</th>
                        <th style="width: 10%; text-align: center; padding: 12px; font-weight: 600; border: none;">APLICA</th>
                        <th style="width: 60%; text-align: left; padding: 12px; font-weight: 600; border: none;">OBSERVACIONES</th>
                        <th style="width: 10%; text-align: center; padding: 12px; font-weight: 600; border: none;">ACCIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DISPONIBILIDAD -->
                    <tr class="fila-grupo">
                        <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0284c7;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #0284c7;"><i class="fas fa-warehouse" style="margin-right: 8px;"></i>DISPONIBILIDAD</span>
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
                                <input type="checkbox" name="tabla_orden[bodega]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
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
                                <input type="checkbox" name="tabla_orden[cucuta]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
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
                                <input type="checkbox" name="tabla_orden[lafayette]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
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
                                <input type="checkbox" name="tabla_orden[fabrica]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
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
                        <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0284c7;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #0284c7;"><i class="fas fa-credit-card" style="margin-right: 8px;"></i>FORMA DE PAGO</span>
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
                                <input type="checkbox" name="tabla_orden[contado]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
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
                                <input type="checkbox" name="tabla_orden[credito]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
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
                        <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0284c7;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #0284c7;"><i class="fas fa-building" style="margin-right: 8px;"></i>RÉGIMEN</span>
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
                                <input type="checkbox" name="tabla_orden[comun]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
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
                                <input type="checkbox" name="tabla_orden[simplificado]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
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
                        <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0284c7;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #0284c7;"><i class="fas fa-chart-bar" style="margin-right: 8px;"></i>SE HA VENDIDO</span>
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
                                <input type="checkbox" name="tabla_orden[vendido]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
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
                        <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0284c7;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #0284c7;"><i class="fas fa-money-bill-wave" style="margin-right: 8px;"></i>ÚLTIMA VENTA</span>
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
                                <input type="checkbox" name="tabla_orden[ultima_venta]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
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
                        <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0284c7;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #0284c7;"><i class="fas fa-truck" style="margin-right: 8px;"></i>FLETE DE ENVÍO</span>
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
                                <input type="checkbox" name="tabla_orden[flete]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
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

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" onclick="cerrarModalEspecificaciones()" style="padding: 0.65rem 1.5rem; background: #f1f5f9; border: 2px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-weight: 600; color: #64748b; font-size: 0.9rem; transition: all 0.2s ease;">Cancelar</button>
                <button type="button" onclick="guardarEspecificacionesReflectivo()" style="padding: 0.65rem 1.5rem; background: linear-gradient(135deg, #0284c7 0%, #0166a0 100%); border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.9rem; transition: all 0.2s ease;">Guardar Especificaciones</button>
            </div>
        </div>
    </div>

    <!-- MODAL DE CONFIRMACIÓN DE ELIMINACIÓN -->
    <div id="modalConfirmarEliminar" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10002; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; padding: 2.5rem; max-width: 450px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); text-align: center; animation: slideIn 0.3s ease;">
            <div style="font-size: 3.5rem; margin-bottom: 1.5rem; color: #ef4444;"></div>
            <h2 style="margin: 0 0 1rem 0; font-size: 1.4rem; color: #1e293b; font-weight: 700;">¿Eliminar esta foto?</h2>
            <p style="margin: 0 0 2rem 0; color: #64748b; font-size: 0.95rem; line-height: 1.6;">Esta acción no se puede deshacer. La foto será eliminada definitivamente de todos lados.</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button type="button" onclick="cerrarModalConfirmarEliminar()" style="padding: 0.75rem 1.5rem; background: #f1f5f9; border: 2px solid #cbd5e1; border-radius: 8px; cursor: pointer; font-weight: 600; color: #64748b; font-size: 0.95rem; transition: all 0.2s ease;">Cancelar</button>
                <button type="button" id="btnConfirmarEliminar" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; border-radius: 8px; cursor: pointer; font-weight: 600; color: white; font-size: 0.95rem; transition: all 0.2s ease;">Eliminar</button>
            </div>
        </div>
    </div>

    <!-- MODAL DE ÉXITO -->
    <div id="modalExito" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10001; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; padding: 3rem; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); text-align: center; animation: slideIn 0.3s ease;">
            <div style="font-size: 4rem; margin-bottom: 1.5rem; color: #10b981;">✓</div>
            <h2 id="modalExitoTitulo" style="margin: 0 0 1rem 0; font-size: 1.5rem; color: #1e293b; font-weight: 700;">Cotización guardada exitosamente</h2>
            <p id="modalExitoMensaje" style="margin: 0 0 1.5rem 0; color: #64748b; font-size: 1rem; line-height: 1.6;"></p>
            <div id="modalExitoNumero" style="display: none; margin: 1.5rem 0; padding: 1rem; background: #f0fdf4; border: 2px solid #10b981; border-radius: 8px;">
                <p style="margin: 0 0 0.5rem 0; color: #64748b; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Número de Cotización:</p>
                <p id="modalExitoNumeroCotizacion" style="margin: 0; font-size: 1.8rem; color: #10b981; font-weight: 700;"></p>
            </div>
            <button type="button" onclick="cerrarModalExito()" style="padding: 0.75rem 2rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 8px; cursor: pointer; font-weight: 600; color: white; font-size: 1rem; transition: all 0.2s ease;">Aceptar</button>
        </div>
    </div>

    <!-- MODAL GLOBAL PARA UBICACIÓN -->
    <div id="modalUbicacionReflectivo" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideIn 0.3s ease;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.2rem; color: #1e40af; font-weight: 700;">Agregar Ubicación</h3>
                <button type="button" onclick="cerrarModalUbicacion()" style="background: none; border: none; font-size: 1.8rem; cursor: pointer; color: #999; line-height: 1; padding: 0;">×</button>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 700; color: #334155; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">Ubicación:</label>
                <div id="modalUbicacionNombre" style="padding: 0.75rem; background: #f1f5f9; border: 2px solid #0ea5e9; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 1rem;">PECHO</div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 700; color: #334155; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">Observación/Detalles:</label>
                <textarea id="modalUbicacionTextarea" placeholder="Ej: Franja vertical de 10cm, color plateado, lado izquierdo..." style="width: 100%; padding: 0.75rem; border: 2px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; resize: vertical; min-height: 120px; font-family: inherit; transition: all 0.2s ease;" onkeydown="if(event.key==='Escape') cerrarModalUbicacion()"></textarea>
            </div>
            
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" onclick="cerrarModalUbicacion()" style="padding: 0.65rem 1.5rem; background: #f1f5f9; border: 2px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-weight: 600; color: #64748b; font-size: 0.9rem; transition: all 0.2s ease;">Cancelar</button>
                <button type="button" onclick="guardarUbicacionReflectivo()" style="padding: 0.65rem 1.5rem; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.9rem; transition: all 0.2s ease;">Guardar Ubicación</button>
            </div>
        </div>
    </div>

    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</div>
</div>

<!-- TEMPLATE PARA PRODUCTO DE REFLECTIVO -->
<template id="productoReflectivoTemplate">
    <div class="producto-card" data-producto-id="">
        <div class="producto-header">
            <h4 class="producto-titulo">PRENDA <span class="numero-producto">1</span></h4>
            <div class="producto-header-buttons">
                <button type="button" class="btn-toggle-product" onclick="toggleProductoBody(this)" title="Expandir/Contraer" style="font-size: 1rem; padding: 0.4rem 0.6rem;">▼</button>
                <button type="button" class="btn-remove-product btn-eliminar-producto" onclick="eliminarProductoPrenda(this)" title="Eliminar prenda">✕</button>
            </div>
        </div>
        <div class="producto-body" style="display: block;">
            <!-- TIPO DE PRENDA -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-tshirt"></i> TIPO DE PRENDA</div>
                <input type="text" name="productos_reflectivo[][tipo_prenda]" class="tipo-prenda-input" placeholder="Ej: Camiseta, Pantalón, Chaqueta...">
            </div>

            <!-- DESCRIPCIÓN -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-sticky-note"></i> DESCRIPCIÓN</div>
                <textarea name="productos_reflectivo[][descripcion]" class="input-large" placeholder="Describe el reflectivo para esta prenda (tipo, tamaño, color, ubicación, etc.)..." rows="3" style="font-size: 0.9rem; width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; resize: vertical;"></textarea>
            </div>

            <!-- IMÁGENES -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-images"></i> IMÁGENES (MÁXIMO 3)</div>
                <label style="display: block; min-height: 50px; padding: 0.75rem; border: 2px dashed #0ea5e9; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="event.preventDefault(); if(event.dataTransfer.files) this.querySelector('input').files = event.dataTransfer.files; this.querySelector('input').onchange && this.querySelector('input').onchange();" ondragover="event.preventDefault(); this.style.background='#e8f4f8';" ondragleave="this.style.background='#f0f7ff';">
                    <input type="file" class="input-file-reflectivo" accept="image/*" multiple onchange="agregarFotosAlProductoReflectivo(this)" style="display: none;">
                    <div class="drop-zone-content" style="font-size: 0.75rem;">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 0.9rem; color: #0ea5e9; display: block; margin-bottom: 0.2rem;"></i>
                        <p style="margin: 0.1rem 0; color: #0ea5e9; font-weight: 500; font-size: 0.8rem;">ARRASTRA IMÁGENES AQUÍ O HAZ CLIC</p>
                        <small style="color: #666; font-size: 0.7rem;">(Máx. 3)</small>
                    </div>
                </label>
                <div class="fotos-preview-reflectivo" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-top: 0.75rem;"></div>
            </div>

            <!-- TALLAS A COTIZAR -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-ruler"></i> TALLAS A COTIZAR</div>
                <div class="form-row">
                    <div class="form-col full">
                        <!-- Input oculto para guardar el género seleccionado -->
                        <input type="hidden" name="productos_reflectivo[][variantes][genero_id]" class="genero-id-hidden-reflectivo" value="">
                        
                        <!-- Fila 1: Selectores de tipo, género y modo -->
                        <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
                            <select class="talla-tipo-select-reflectivo" onchange="actualizarSelectTallasReflectivo(this)" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 300px;">
                                <option value="">Selecciona tipo de talla</option>
                                <option value="letra">LETRAS (XS, S, M, L, XL...)</option>
                                <option value="numero">NÚMEROS (DAMA/CABALLERO)</option>
                            </select>
                            
                            <select class="talla-genero-select-reflectivo" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 200px; display: none;">
                                <option value="">Selecciona género</option>
                                <option value="dama">Dama</option>
                                <option value="caballero">Caballero</option>
                            </select>
                            
                            <select class="talla-modo-select-reflectivo" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 200px; display: none;">
                                <option value="">Selecciona modo</option>
                                <option value="manual">Manual</option>
                                <option value="rango">Rango (Desde-Hasta)</option>
                            </select>
                            
                            <!-- Selectores de rango (aparecen cuando se selecciona Rango) -->
                            <div class="talla-rango-selectors-reflectivo" style="display: none; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                                <select class="talla-desde-reflectivo" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 150px;">
                                    <option value="">Desde</option>
                                </select>
                                <span style="color: #0066cc; font-weight: 600;">hasta</span>
                                <select class="talla-hasta-reflectivo" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 150px;">
                                    <option value="">Hasta</option>
                                </select>
                                <button type="button" class="btn-agregar-rango-reflectivo" onclick="agregarTallasRangoReflectivo(this)" style="padding: 0.6rem 1rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; white-space: nowrap;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Fila 2: Botones de tallas (Modo Manual) -->
                        <div class="talla-botones-reflectivo" style="display: none; margin-bottom: 1.5rem;">
                            <p style="margin: 0 0 0.75rem 0; font-size: 0.85rem; font-weight: 600; color: #0066cc;">Selecciona tallas:</p>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                                <div class="talla-botones-container-reflectivo" style="display: flex; flex-wrap: wrap; gap: 0.5rem; flex: 1;">
                                </div>
                                <button type="button" class="btn-agregar-tallas-seleccionadas-reflectivo" onclick="agregarTallasSeleccionadasReflectivo(this)" style="padding: 0.6rem 1rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; white-space: nowrap; flex-shrink: 0;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Fila 3: Tallas agregadas -->
                        <div class="tallas-section-reflectivo" style="display: none; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 0.75rem 0; font-size: 0.85rem; font-weight: 600; color: #0066cc;">Tallas seleccionadas:</p>
                            <div class="tallas-agregadas-reflectivo" style="display: flex; flex-wrap: wrap; gap: 0.5rem; min-height: 35px;">
                            </div>
                            <input type="hidden" name="productos_reflectivo[][tallas]" class="tallas-hidden-reflectivo" value="">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE COLOR, TELA Y REFERENCIA (Tabla con imagen) -->
            <div class="producto-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div class="section-title"><i class="fas fa-palette"></i> COLOR, TELA Y REFERENCIA</div>
                    <button type="button" class="btn-agregar-tela-reflectivo" onclick="agregarFilaTelaReflectivo(this)" style="padding: 0.5rem 1rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-plus"></i> Agregar Tela
                    </button>
                </div>
                <div class="form-row" style="overflow-x: auto;">
                    <div class="form-col full" style="min-width: 0;">
                        <table style="width: 100%; border-collapse: collapse; background: white; min-width: 800px;">
                            <thead>
                                <tr style="background-color: #f0f0f0; border-bottom: 2px solid #0066cc;">
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd; width: 150px; white-space: nowrap;">
                                        <i class="fas fa-palette"></i> Color
                                    </th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd; width: 150px; white-space: nowrap;">
                                        <i class="fas fa-cloth"></i> Tela
                                    </th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd; width: 120px; white-space: nowrap;">
                                        <i class="fas fa-barcode"></i> Referencia
                                    </th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd; width: 200px; white-space: nowrap;">
                                        <i class="fas fa-image"></i> Imagen Tela
                                    </th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; color: #0066cc; width: 50px; white-space: nowrap;">
                                        <i class="fas fa-trash"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="telas-tbody-reflectivo">
                                <tr style="border-bottom: 1px solid #ddd;" class="fila-tela-reflectivo" data-tela-index="0">
                                    <td style="padding: 14px; border-right: 1px solid #ddd;">
                                        <div style="position: relative;">
                                            <label for="color-input-reflectivo" class="sr-only">Color</label>
                                            <input type="text" id="color-input-reflectivo" class="color-input-reflectivo" placeholder="Color..." style="width: 100%; padding: 12px; border: 2px solid #0066cc; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box; min-height: 44px;" onkeyup="buscarColorReflectivo(this)" onkeypress="if(event.key==='Enter') crearColorDesdeInputReflectivo(this)" aria-label="Selecciona o escribe un color">
                                            <input type="hidden" name="productos_reflectivo[][telas][0][color_id]" class="color-id-input-reflectivo" value="">
                                            <div class="color-suggestions-reflectivo" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 150px; overflow-y: auto; z-index: 1000; min-width: 100%; display: none; margin-top: 2px; top: 100%;"></div>
                                        </div>
                                    </td>
                                    <td style="padding: 14px; border-right: 1px solid #ddd;">
                                        <div style="position: relative;">
                                            <label for="tela-input-reflectivo" class="sr-only">Tela</label>
                                            <input type="text" id="tela-input-reflectivo" class="tela-input-reflectivo" placeholder="Tela..." style="width: 100%; padding: 12px; border: 2px solid #0066cc; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box; min-height: 44px;" onkeyup="buscarTelaReflectivo(this)" onkeypress="if(event.key==='Enter') crearTelaDesdeInputReflectivo(this)" aria-label="Selecciona o escribe el tipo de tela">
                                            <input type="hidden" name="productos_reflectivo[][telas][0][tela_id]" class="tela-id-input-reflectivo" value="">
                                            <div class="tela-suggestions-reflectivo" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 150px; overflow-y: auto; z-index: 1000; min-width: 100%; display: none; margin-top: 2px; top: 100%;"></div>
                                        </div>
                                    </td>
                                    <td style="padding: 14px; border-right: 1px solid #ddd;">
                                        <label for="referencia-input-reflectivo" class="sr-only">Referencia</label>
                                        <input type="text" id="referencia-input-reflectivo" name="productos_reflectivo[][telas][0][referencia]" class="referencia-input-reflectivo" placeholder="Ref..." style="width: 100%; padding: 12px; border: 2px solid #0066cc; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box; min-height: 44px;" aria-label="Referencia del producto">
                                    </td>
                                    <td style="padding: 14px; text-align: center; border-right: 1px solid #ddd;">
                                        <label style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80px; padding: 8px; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="manejarDropReflectivo(event, this)" ondragover="event.preventDefault(); this.style.background='#e8f4f8';" ondragleave="this.style.background='#f0f7ff'">
                                            <input type="file" name="productos_reflectivo[][telas][0][fotos][]" class="input-file-tela-reflectivo" accept="image/*" multiple onchange="agregarFotoTelaReflectivo(this)" style="display: none;">
                                            <div class="drop-zone-content" style="font-size: 0.8rem;">
                                                <i class="fas fa-cloud-upload-alt" style="font-size: 1.2rem; color: #0066cc; margin-bottom: 4px;"></i>
                                                <p style="margin: 4px 0; color: #0066cc; font-weight: 600; font-size: 0.8rem;">CLIC</p>
                                                <small style="color: #666; font-size: 0.75rem;">(Máx. 3)</small>
                                            </div>
                                        </label>
                                        <div class="foto-tela-preview-reflectivo" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 6px;"></div>
                                    </td>
                                    <td style="padding: 14px; text-align: center;">
                                        <button type="button" class="btn-eliminar-tela-reflectivo" onclick="eliminarFilaTelaReflectivo(this)" style="padding: 10px 12px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; display: none; min-width: 44px; min-height: 44px;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- UBICACIÓN -->
            <div class="producto-section">
                <div class="section-title" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleSeccionReflectivo(this)">
                    <span><i class="fas fa-map-marker-alt"></i> UBICACIÓN</span>
                    <i class="fas fa-chevron-down" style="transition: transform 0.3s ease;"></i>
                </div>
                <div class="ubicacion-seccion-reflectivo" style="display: block;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1e40af; font-size: 0.9rem;">Selecciona la sección a agregar:</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <input type="text" class="ubicacion-input-reflectivo" placeholder="Ej: PECHO, ESPALDA, MANGA, etc." style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                        <button type="button" class="btn-add-ubicacion-reflectivo" onclick="abrirModalUbicacion(this)" style="background: #0ea5e9; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 600;">+</button>
                    </div>
                    <div class="ubicaciones-agregadas-reflectivo" style="display: flex; flex-wrap: wrap; gap: 0.5rem;"></div>
                </div>
            </div>

            <!-- VARIACIONES -->
            <div class="producto-section">
                <div class="section-title" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleSeccionReflectivo(this)">
                    <span><i class="fas fa-list-check"></i> VARIACIONES</span>
                    <i class="fas fa-chevron-down" style="transition: transform 0.3s ease;"></i>
                </div>
                <div class="variaciones-seccion-reflectivo" style="display: block;">
                    <table class="variaciones-tabla-reflectivo" style="width: 100%; border-collapse: collapse; margin-top: 0.75rem;">
                        <thead>
                            <tr style="background-color: #f0f9ff; border-bottom: 2px solid #0ea5e9;">
                                <th style="padding: 0.75rem; text-align: left; font-weight: 700; color: #1e40af; font-size: 0.9rem;">Variación</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 700; color: #1e40af; font-size: 0.9rem;">Observación</th>
                            </tr>
                        </thead>
                        <tbody class="variaciones-tbody-reflectivo">
                            <!-- Filas de variaciones se agregan aquí -->
                        </tbody>
                    </table>
                    <button type="button" class="btn-agregar-variacion" onclick="agregarFilaVariacionReflectivo(this)" style="margin-top: 0.75rem; background: #10b981; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.9rem;">+ Agregar Variación</button>
                    
                    <!-- Campo oculto para almacenar JSON de variaciones -->
                    <input type="hidden" class="variaciones-json-reflectivo" name="productos_reflectivo[][variaciones]" value="[]">
                </div>
            </div>


        </div>
    </div>
</template>

<script src="{{ asset('js/asesores/pedidos/create-reflectivo.js') }}"></script>

<!-- Loading Spinner -->
<div id="loadingSpinner" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.95); display: flex; align-items: center; justify-content: center; z-index: 99999; backdrop-filter: blur(2px);">
    <div style="text-align: center;">
        <div class="spinner"></div>
        <div class="spinner-text">Cargando cotización...</div>
    </div>
</div>

<script src="{{ asset('js/asesores/pedidos/create-reflectivo-spinner.js') }}"></script>

@endsection

