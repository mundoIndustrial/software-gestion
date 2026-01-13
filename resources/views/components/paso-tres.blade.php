<!-- PASO 3: LOGO / BORDADO (COMBINADA) -->
<div class="form-step" data-step="3">
    <div class="step-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 3: LOGO / BORDADO</h2>
            <p style="font-size: 0.8rem !important; margin: 0 !important; color: #666 !important;">ESPECIFICA LOS DETALLES DEL LOGO Y BORDADO</p>
        </div>
        
        <!-- Selector de tipo de venta en la esquina derecha -->
        <div style="display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #0066cc, #0052a3); border: 2px solid #0052a3; border-radius: 8px; padding: 0.8rem 1.2rem; box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);">
            <label for="tipo_venta_paso3" style="font-weight: 700; font-size: 0.85rem; color: white; white-space: nowrap; display: flex; align-items: center; gap: 6px; margin: 0;">
                <i class="fas fa-tag"></i> Tipo Venta
            </label>
            <select id="tipo_venta_paso3" name="tipo_venta_paso3" style="padding: 0.5rem 0.6rem; border: 2px solid white; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; text-align: center; color: #0066cc; font-weight: 600; min-width: 100px;">
                <option value="">Selecciona</option>
                <option value="M">M</option>
                <option value="D">D</option>
                <option value="X">X</option>
            </select>
        </div>
    </div>

    <div class="form-section">
        <!-- TÉCNICAS -->
        <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Técnicas Disponibles</label>
            </div>
            
            <!-- Selector de Técnicas (Checkboxes) -->
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333;">Selecciona las técnicas a aplicar:</label>
                <div id="tecnicas-checkboxes-paso3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 15px;">
                    <!-- Se llenan dinámicamente -->
                </div>
                <button type="button" id="btnAgregarPrendasPaso3" onclick="abrirModalAgregarTecnicaPaso3()" style="background: #1e40af; color: white; border: none; cursor: pointer; padding: 10px 20px; border-radius: 4px; font-weight: 600; transition: background 0.2s ease; width: auto;">
                    <i class="fas fa-plus"></i> Agregar Prendas
                </button>
            </div>
            <!-- Lista de Prendas Agregadas por Técnica -->
            <div id="tecnicas_agregadas_paso3" style="margin-top: 15px;"></div>
            
            <!-- Sin Técnicas -->
            <div id="sin_tecnicas_paso3" style="padding: 20px; text-align: center; background: #f5f5f5; border-radius: 8px; color: #999; display: block;">
                <p>Selecciona técnicas y agrega prendas</p>
            </div>
        </div>

        <!-- OBSERVACIONES GENERALES -->
        <div class="form-section">
            <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Observaciones Generales</label>
                    <button type="button" onclick="agregarObservacionPaso3()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
                </div>
                
                <div id="observaciones_lista_paso3" style="display: flex; flex-direction: column; gap: 10px;"></div>
            </div>
        </div>
    </div>


    <div class="form-actions">
        <button type="button" class="btn-prev" onclick="if(typeof irAlPaso === 'function') irAlPaso(2)">
            <i class="fas fa-arrow-left"></i> ANTERIOR
        </button>
        <button type="button" class="btn-next" onclick="if(typeof irAlPaso === 'function') irAlPaso(4)">
            SIGUIENTE <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>

<!-- PASO 4: REFLECTIVO (COMBINADA) -->
<div class="form-step" data-step="4">
    <div class="step-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 4: REFLECTIVO</h2>
            <p style="font-size: 0.8rem !important; margin: 0 !important; color: #666 !important;">ESPECIFICA LOS DETALLES DEL REFLECTIVO POR PRENDA</p>
        </div>
    </div>

    <div class="form-section">
        <!-- CONTENEDOR DE PRENDAS REFLECTIVO -->
        <div id="prendas_reflectivo_container" style="margin-bottom: 20px;"></div>
        
        <!-- BOTÓN AGREGAR PRENDA REFLECTIVO -->
        <button type="button" id="btnAgregarPrendaReflectivo" onclick="agregarPrendaReflectivoPaso4(); console.log('✅ Botón Agregar Prenda Reflectivo clickeado');" style="width: 100%; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; cursor: pointer; padding: 12px 20px; border-radius: 4px; font-weight: 600; transition: background 0.2s ease; margin-bottom: 20px;">
            <i class="fas fa-plus"></i> Agregar Prenda Reflectivo
        </button>
        
        <!-- SIN PRENDAS -->
        <div id="sin_prendas_reflectivo" style="padding: 20px; text-align: center; background: #f5f5f5; border-radius: 8px; color: #999; display: block;">
            <p>Agrega prendas con reflectivo</p>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📦 Paso 4 Reflectivo - Listo para agregar prendas');
            // Agregar automáticamente un formulario vacío al cargar Paso 4
            if (typeof agregarPrendaReflectivoPaso4 === 'function') {
                agregarPrendaReflectivoPaso4();
                console.log('✅ Formulario vacío agregado automáticamente en Paso 4');
            }
        });
    </script>

    <div class="form-actions">
        <button type="button" class="btn-prev" onclick="if(typeof irAlPaso === 'function') irAlPaso(3)">
            <i class="fas fa-arrow-left"></i> ANTERIOR
        </button>
        <button type="button" class="btn-next" onclick="if(typeof irAlPaso === 'function') irAlPaso(5)">
            REVISAR <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>

<!-- MODAL PARA AGREGAR PRENDAS CON TÉCNICA PASO 3 -->
<div id="modalAgregarTecnicaPaso3" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 24px; max-width: 650px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        
        <!-- Header del Modal -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0; font-size: 1.2rem; font-weight: 600; color: #333;">Agregar Prendas</h2>
                <p style="margin: 8px 0 0 0; color: #666; font-size: 0.85rem;">Técnica: <strong id="tecnicaSeleccionadaNombrePaso3" style="color: #333;">--</strong></p>
            </div>
            <button type="button" onclick="cerrarModalAgregarTecnicaPaso3()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #ccc; padding: 0; line-height: 1; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>
        
        <!-- Lista de Prendas -->
        <div id="listaPrendasPaso3" style="margin-bottom: 16px;">
            <!-- Prendas dinámicas aquí -->
        </div>
        
        <!-- Sin prendas -->
        <div id="noPrendasMsgPaso3" style="padding: 16px; text-align: center; background: #f9f9f9; border-radius: 4px; color: #999; margin-bottom: 16px; display: block; font-size: 0.9rem;">
            <p style="margin: 0;">Agrega prendas con el botón de abajo</p>
        </div>
        
        <!-- Botón agregar prenda -->
        <button type="button" onclick="agregarFilaPrendaPaso3()" style="width: 100%; background: #f0f0f0; color: #333; border: 1px solid #ddd; font-size: 0.9rem; cursor: pointer; padding: 10px 12px; border-radius: 4px; font-weight: 500; margin-bottom: 16px; transition: background 0.2s;">
            + Agregar prenda
        </button>
        
        <!-- Botones de acción -->
        <div style="display: flex; gap: 8px; justify-content: flex-end; border-top: 1px solid #eee; padding-top: 16px;">
            <button type="button" onclick="cerrarModalAgregarTecnicaPaso3()" style="background: white; color: #333; border: 1px solid #ddd; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.9rem;">
                Cancelar
            </button>
            <button type="button" onclick="guardarTecnicaPaso3()" style="background: #333; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.9rem;">
                Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Validación - Seleccionar Técnica -->
<div id="modalValidacionTecnicaPaso3" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1001; align-items: center; justify-content: center; flex-direction: column;">
    <div style="background: white; border-radius: 8px; padding: 40px; width: 90%; max-width: 400px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
        <div style="font-size: 3rem; margin-bottom: 20px; color: #ff9800;">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <p style="color: #333; margin-bottom: 30px; font-size: 1.1rem; font-weight: 600;">Debes seleccionar una técnica antes de agregar prendas.</p>
        <button type="button" onclick="cerrarModalValidacionTecnicaPaso3()" style="background: #1e40af; color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; width: 100%;">
            Entendido
        </button>
    </div>
</div>

<script src="{{ asset('js/paso-tres-cotizacion-combinada.js') }}?v={{ time() }}"></script>


