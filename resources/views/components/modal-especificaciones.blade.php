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
