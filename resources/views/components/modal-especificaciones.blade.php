<!-- MODAL: ESPECIFICACIONES DE LA ORDEN -->
<div id="modalEspecificaciones" class="modal-especificaciones" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 1000px; width: 98%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 3px solid #0066cc; padding-bottom: 1.5rem; background: white;">
            <h3 style="margin: 0; color: #333; font-size: 1.3rem; font-weight: 700;"><i class="fas fa-clipboard-check" style="margin-right: 10px;"></i>ESPECIFICACIONES DE LA ORDEN</h3>
            <button type="button" onclick="cerrarModalEspecificaciones()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999; transition: all 0.2s; flex-shrink: 0;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#999'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table class="tabla-control-compacta" style="width: 100%; border-collapse: collapse; background: white; font-size: 0.9rem;">
            <thead>
                <tr style="background: white; border-bottom: 2px solid #ddd;">
                    <th style="width: 20%; text-align: left; padding: 12px; font-weight: 600; border: none; color: #333;">CONCEPTO</th>
                    <th style="width: 10%; text-align: center; padding: 12px; font-weight: 600; border: none; color: #333;">APLICA</th>
                    <th style="width: 60%; text-align: left; padding: 12px; font-weight: 600; border: none; color: #333;">OBSERVACIONES</th>
                    <th style="width: 10%; text-align: center; padding: 12px; font-weight: 600; border: none; color: #333;">ACCIÓN</th>
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
        </div>

        <!-- Footer -->
        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 3px solid #0066cc; display: flex; gap: 1rem; justify-content: flex-end; flex-wrap: wrap;">
            <button type="button" onclick="cerrarModalEspecificaciones()" style="padding: 0.6rem 1.5rem; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 600; color: #333; transition: all 0.2s;" onmouseover="this.style.background='#e0e0e0'" onmouseout="this.style.background='#f0f0f0'">
                CANCELAR
            </button>
            <button type="button" onclick="guardarEspecificaciones()" style="padding: 0.6rem 1.5rem; background: linear-gradient(135deg, #0066cc, #0052a3); border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; transition: all 0.2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0, 102, 204, 0.3)'" onmouseout="this.style.boxShadow='none'">
                GUARDAR
            </button>
        </div>
    </div>
</div>
