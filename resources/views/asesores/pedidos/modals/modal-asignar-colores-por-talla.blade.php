<!-- MODAL: Asignar Colores por Talla (Wizard Dedicado) -->
<div id="modal-asignar-colores-por-talla" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modal-asignar-colores-titulo" aria-hidden="true" data-backdrop="static" data-keyboard="false" style="z-index: 1060000 !important;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header modal-header-primary" style="border-bottom: 2px solid #0066cc;">
                <h5 class="modal-title" id="modal-asignar-colores-titulo">
                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 0.5rem;">color_lens</span>
                    Asignar Colores por Talla
                </h5>
                <button type="button" class="close" id="btn-cerrar-modal-colores" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <!-- CONTENEDOR DE ALERTAS (para mensajes dinámicos) -->
                <div id="wizard-alert-container" style="margin-bottom: 1rem;"></div>

                <!-- INDICADOR DE PROGRESO -->
                <div id="wizard-indicador-progreso" style="margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                        <!-- PASO 0: TELA (solo visible si hay múltiples telas) -->
                        <div id="paso-0-wrapper" style="flex: 1; text-align: center; display: none;">
                            <div id="paso-0-indicator" style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: #3b82f6; color: white; border-radius: 50%; font-weight: bold; margin: 0 auto 0.5rem;">0</div>
                            <div style="font-size: 0.875rem; font-weight: 500; color: #1f2937;">Tela</div>
                        </div>
                        <div id="paso-0-linea" style="flex-grow: 1; height: 2px; background: #3b82f6; margin-bottom: 1.5rem; display: none;"></div>

                        <!-- PASO 1: GÉNERO -->
                        <div style="flex: 1; text-align: center;">
                            <div id="paso-1-indicator" style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: #3b82f6; color: white; border-radius: 50%; font-weight: bold; margin: 0 auto 0.5rem;">1</div>
                            <div style="font-size: 0.875rem; font-weight: 500; color: #1f2937;">Género</div>
                        </div>
                        <div style="flex-grow: 1; height: 2px; background: #3b82f6; margin-bottom: 1.5rem;"></div>
                        
                        <!-- PASO 2: TALLA -->
                        <div style="flex: 1; text-align: center;">
                            <div id="paso-2-indicator" style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: #d1d5db; color: #6b7280; border-radius: 50%; font-weight: bold; margin: 0 auto 0.5rem;">2</div>
                            <div style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Talla</div>
                        </div>
                        <div style="flex-grow: 1; height: 2px; background: #d1d5db; margin-bottom: 1.5rem;"></div>
                        
                        <!-- PASO 3: COLORES -->
                        <div style="flex: 1; text-align: center;">
                            <div id="paso-3-indicator" style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: #d1d5db; color: #6b7280; border-radius: 50%; font-weight: bold; margin: 0 auto 0.5rem;">3</div>
                            <div style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Colores</div>
                        </div>
                    </div>
                </div>

                <!-- PASO 0: SELECCIONAR TELA (solo visible si hay múltiples telas) -->
                <div id="wizard-paso-0" style="display: none;">
                    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; text-align: center;">
                        <h3 style="color: #111827; margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 600;">Selecciona Tela</h3>
                        <p style="color: #6b7280; margin: 0 0 1.5rem 0; font-size: 0.85rem;">¿Qué tela deseas asignar?</p>
                        
                        <div id="wizard-telas-selector" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem;">
                            <!-- Botones de tela dinámicos -->
                        </div>
                    </div>
                </div>

                <!-- PASO 1: GÉNERO -->
                <div id="wizard-paso-1" style="display: block;">
                    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; text-align: center;">
                        <h3 style="color: #111827; margin: 0 0 0.25rem 0; font-size: 1.1rem; font-weight: 600;">Selecciona Género</h3>
                        <p style="color: #6b7280; margin: 0 0 1.5rem 0; font-size: 0.85rem;">¿Qué tipo de prenda es?</p>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.75rem;">
                            <button type="button" class="wizard-genero-btn" data-genero="dama" onclick="wizardSeleccionarGenero('dama')" style="padding: 1rem; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.85rem; color: #374151; transition: all 0.2s;">
                                <span class="material-symbols-rounded" style="font-size: 1.5rem; display: block; margin-bottom: 0.25rem; color: #6b7280;">woman</span>
                                DAMA
                            </button>
                            
                            <button type="button" class="wizard-genero-btn" data-genero="caballero" onclick="wizardSeleccionarGenero('caballero')" style="padding: 1rem; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.85rem; color: #374151; transition: all 0.2s;">
                                <span class="material-symbols-rounded" style="font-size: 1.5rem; display: block; margin-bottom: 0.25rem; color: #6b7280;">man</span>
                                CABALLERO
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PASO 2: TALLA -->
                <div id="wizard-paso-2" style="display: none;">
                    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem;">
                        <h3 style="color: #111827; margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 600;">Selecciona Talla</h3>
                        <div id="wizard-genero-seleccionado" style="color: #6b7280; font-size: 0.85rem; margin-bottom: 1.25rem;">Género: <strong style="color: #374151;">-- No seleccionado --</strong></div>
                        
                        <div id="wizard-tallas-contenedor" style="display: flex; flex-direction: column; gap: 1rem;">
                            <!-- Botones de talla dinámicos -->
                        </div>
                    </div>
                </div>

                <!-- PASO 3: COLORES -->
                <div id="wizard-paso-3" style="display: none;">
                    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem;">
                        <h3 style="color: #111827; margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 600;">Asignar Colores y Cantidades</h3>
                        <div id="wizard-resumen-seleccion" style="color: #6b7280; font-size: 0.85rem; margin-bottom: 1.25rem; background: white; padding: 0.75rem; border-radius: 4px; border: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                Género: <strong style="color: #374151;" id="wizard-genero-label">--</strong> | Talla: <strong style="color: #374151;" id="wizard-talla-label">--</strong>
                            </div>
                            <div style="background: #f3f4f6; padding: 0.5rem 0.75rem; border-radius: 4px; border: 1px solid #d1d5db; font-size: 0.85rem; white-space: nowrap;">
                                <span style="color: #6b7280;">Tela:</span> <strong style="color: #374151;" id="wizard-tela-label">--</strong>
                            </div>
                        </div>
                        
                        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                            <label style="font-weight: 600; color: #1f2937; font-size: 0.95rem; display: block; margin-bottom: 1rem;">
                                <span class="material-symbols-rounded" style="font-size: 1.2rem; vertical-align: middle; margin-right: 0.5rem;">color_lens</span>COLORES DISPONIBLES
                            </label>
                            <div id="lista-colores-checkboxes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 0.75rem;"></div>
                        </div>
                        
                        <div id="seccion-agregar-color-personalizado" style="background: white; border: 1px dashed #d1d5db; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; display: none;">
                            <label for="color-personalizado-input" style="font-size: 0.875rem; color: #6b7280; font-weight: 500; display: block; margin-bottom: 0.5rem;">O especificar color personalizado:</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="text" id="color-personalizado-input" placeholder="Ej: ROJO, VERDE..." class="form-input" style="flex: 1; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                <label for="cantidad-color-personalizado" class="sr-only">Cantidad de color</label>
                                <input type="number" id="cantidad-color-personalizado" placeholder="Cant" class="form-input" style="width: 80px; padding: 0.5rem;" min="1" value="1">
                                <button type="button" class="btn btn-primary" onclick="agregarColorPersonalizado()" style="padding: 0.5rem 1rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">add</span>
                                    <span>Agregar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden inputs para compatibilidad -->
                <input type="hidden" id="asignacion-genero-select" value="" aria-label="Género seleccionado para asignación">
                <input type="hidden" id="asignacion-talla-select" value="" aria-label="Talla seleccionada para asignación">
                <input type="hidden" id="contador-asignaciones" value="0" aria-label="Contador de asignaciones">
            </div>

            <!-- Footer -->
            <div class="modal-footer" style="border-top: 1px solid #e5e7eb; padding: 1.5rem;">
                <button type="button" id="wzd-btn-atras" class="btn btn-secondary" style="display: none;">
                    <span class="material-symbols-rounded" style="font-size: 1.2rem; margin-right: 0.5rem;">arrow_back</span>
                    <span>Atrás</span>
                </button>
                
                <div style="flex: 1;"></div>
                
                <button type="button" id="btn-cancelar-wizard" class="btn btn-secondary" data-dismiss="modal">
                    <span class="material-symbols-rounded" style="margin-right: 0.5rem;">close</span>
                    <span>Cancelar</span>
                </button>
                
                <button type="button" id="wzd-btn-siguiente" class="btn btn-primary" style="display: none;">
                    <span>Siguiente</span>
                    <span class="material-symbols-rounded" style="font-size: 1.2rem; margin-left: 0.5rem;">arrow_forward</span>
                </button>
                
                <button type="button" id="btn-guardar-asignacion" class="btn btn-success" style="display: none; font-weight: 600;">
                    <span class="material-symbols-rounded" style="font-size: 1.2rem; margin-right: 0.5rem;">check_circle</span>
                    <span>Guardar Asignación</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos para asegurar que el modal aparezca encima de todo -->
<style>
    #modal-asignar-colores-por-talla {
        z-index: 99999 !important;
    }
    
    #modal-asignar-colores-por-talla.modal {
        z-index: 99999 !important;
    }
    
    #modal-asignar-colores-por-talla.modal.show {
        z-index: 99999 !important;
        display: flex !important;
    }
    
    /* Backdrop del modal */
    #modal-asignar-colores-por-talla ~ .modal-backdrop {
        z-index: 99998 !important;
    }
    
    .modal-backdrop.show {
        z-index: 99998 !important;
    }
</style>
