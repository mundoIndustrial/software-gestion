                    <!-- VISTA 2: WIZARD - Asignar Colores por Talla -->
                    <div id="vista-asignacion-colores" style="display: none;">
                        
                        <!-- INDICADOR DE PROGRESO -->
                        <div style="margin-bottom: 2rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                                <div style="flex: 1; text-align: center;">
                                    <div id="paso-1-indicator" style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: #3b82f6; color: white; border-radius: 50%; font-weight: bold; margin: 0 auto 0.5rem;">1</div>
                                    <div style="font-size: 0.875rem; font-weight: 500; color: #1f2937;">Género</div>
                                </div>
                                <div style="flex-grow: 1; height: 2px; background: #3b82f6; margin-bottom: 1.5rem;"></div>
                                <div style="flex: 1; text-align: center;">
                                    <div id="paso-2-indicator" style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: #d1d5db; color: #6b7280; border-radius: 50%; font-weight: bold; margin: 0 auto 0.5rem;">2</div>
                                    <div style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Talla</div>
                                </div>
                                <div style="flex-grow: 1; height: 2px; background: #d1d5db; margin-bottom: 1.5rem;"></div>
                                <div style="flex: 1; text-align: center;">
                                    <div id="paso-3-indicator" style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: #d1d5db; color: #6b7280; border-radius: 50%; font-weight: bold; margin: 0 auto 0.5rem;">3</div>
                                    <div style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Colores</div>
                                </div>
                            </div>
                        </div>

                        <!-- PASO 1: GÉNERO -->
                        <div id="wizard-paso-1" style="display: block;">
                            <div style="background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 12px; padding: 2rem; text-align: center;">
                                <h3 style="color: #1f2937; margin: 0 0 0.5rem 0; font-size: 1.25rem;">Género de la Prenda</h3>
                                <p style="color: #6b7280; margin: 0 0 2rem 0; font-size: 0.95rem;">Selecciona uno de los tipos disponibles</p>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem;">
                                    <button type="button" class="wizard-genero-btn" data-genero="dama" onclick="wizardSeleccionarGenero('dama')" style="padding: 1.5rem; border: 2px solid #3b82f6; background: white; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.95rem; color: #3b82f6;">
                                        <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">woman</span>
                                        DAMA
                                    </button>
                                    
                                    <button type="button" class="wizard-genero-btn" data-genero="caballero" onclick="wizardSeleccionarGenero('caballero')" style="padding: 1.5rem; border: 2px solid #3b82f6; background: white; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.95rem; color: #3b82f6;">
                                        <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">man</span>
                                        CABALLERO
                                    </button>
                                    
                                    <button type="button" class="wizard-genero-btn" data-genero="sobremedida" onclick="wizardSeleccionarGenero('sobremedida')" style="padding: 1.5rem; border: 2px solid #3b82f6; background: white; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.95rem; color: #3b82f6;">
                                        <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">accessibility_new</span>
                                        SOBREMEDIDA
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- PASO 2: TALLA -->
                        <div id="wizard-paso-2" style="display: none;">
                            <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 12px; padding: 2rem;">
                                <h3 style="color: #1f2937; margin: 0 0 0.5rem 0; font-size: 1.25rem;">Talla</h3>
                                <div id="wizard-genero-seleccionado" style="color: #6b7280; font-size: 0.9rem; margin-bottom: 1.5rem;">Género: <strong>-- No seleccionado --</strong></div>
                                
                                <div id="wizard-tallas-contenedor" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.75rem;">
                                    <!-- Botones de talla dinámicos -->
                                </div>
                            </div>
                        </div>

                        <!-- PASO 3: COLORES -->
                        <div id="wizard-paso-3" style="display: none;">
                            <div style="background: #f5f3ff; border: 2px solid #a855f7; border-radius: 12px; padding: 2rem;">
                                <h3 style="color: #1f2937; margin: 0 0 0.5rem 0; font-size: 1.25rem;">Colores y Cantidades</h3>
                                <div id="wizard-resumen-seleccion" style="color: #6b7280; font-size: 0.9rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; background: white; padding: 0.75rem; border-radius: 4px; border: 1px solid #e5e7eb;">
                                    <div>
                                        Género: <strong id="wizard-genero-label" style="color: #374151;">--</strong> | Talla: <strong id="wizard-talla-label" style="color: #374151;">--</strong>
                                    </div>
                                    <div style="background: #f3f4f6; padding: 0.5rem 0.75rem; border-radius: 4px; border: 1px solid #d1d5db; font-size: 0.85rem; white-space: nowrap;">
                                        <span style="color: #6b7280;">Tela:</span> <strong id="wizard-tela-label" style="color: #374151;">--</strong>
                                    </div>
                                </div>
                                
                                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                                    <label style="font-weight: 600; color: #1f2937; font-size: 0.95rem; display: block; margin-bottom: 1rem;">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem; vertical-align: middle; margin-right: 0.5rem;">color_lens</span>COLORES DISPONIBLES
                                    </label>
                                    <div id="lista-colores-checkboxes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 0.75rem;"></div>
                                </div>
                                
                                <div id="seccion-agregar-color-personalizado" style="background: white; border: 1px dashed #d1d5db; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; display: none;">
                                    <label style="font-size: 0.875rem; color: #6b7280; font-weight: 500; display: block; margin-bottom: 0.5rem;">O especificar color personalizado:</label>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <input type="text" id="color-personalizado-input" placeholder="Ej: ROJO, VERDE..." class="form-input" style="flex: 1; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                        <input type="number" id="cantidad-color-personalizado" placeholder="Cant" class="form-input" style="width: 80px; padding: 0.5rem;" min="1" value="1">
                                        <button type="button" class="btn btn-primary" onclick="agregarColorPersonalizado()" style="padding: 0.5rem 1rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
                                            <span class="material-symbols-rounded" style="font-size: 1.1rem;">add</span>
                                            <span>Agregar</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem;">
                                    <label style="font-weight: 600; color: #1f2937; font-size: 0.95rem; display: block; margin-bottom: 1rem;">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem; vertical-align: middle; margin-right: 0.5rem;">checklist</span>ASIGNACIONES ACTUALES
                                    </label>
                                    <div style="overflow-x: auto;">
                                        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                                            <thead>
                                                <tr style="background: #f3f4f6; border-bottom: 2px solid #d1d5db;">
                                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600;">COLOR</th>
                                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; width: 100px;">CANTIDAD</th>
                                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; width: 60px;">ACCIÓN</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tabla-asignaciones-cuerpo"></tbody>
                                        </table>
                                    </div>
                                    <div id="msg-sin-asignaciones" style="text-align: center; padding: 1.5rem; color: #9ca3af; background: #f9fafb; border-radius: 8px; border: 1px dashed #d1d5db;">
                                        <span class="material-symbols-rounded" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;">inbox</span>
                                        Sin colores asignados
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BOTONES DE NAVEGACIÓN -->
                        <div style="display: flex; gap: 1rem; justify-content: space-between; margin-top: 2rem;">
                            <button type="button" id="wzd-btn-atras" class="btn btn-secondary" onclick="wizardPasoAnterior()" style="display: none;">
                                <span class="material-symbols-rounded">arrow_back</span>Atrás
                            </button>
                            
                            <div style="display: flex; gap: 1rem; flex-grow: 1; justify-content: flex-end;">
                                <button type="button" class="btn btn-secondary" onclick="toggleVistaAsignacionColores()">
                                    <span class="material-symbols-rounded">close</span>Cancelar
                                </button>
                                
                                <button type="button" id="wzd-btn-siguiente" class="btn btn-primary" onclick="wizardPasoSiguiente()" style="display: none;">
                                    Siguiente<span class="material-symbols-rounded">arrow_forward</span>
                                </button>
                                
                                <button type="button" id="btn-guardar-asignacion" class="btn btn-success" onclick="guardarAsignacionColores()" style="display: none;">
                                    <span class="material-symbols-rounded">check_circle</span>Guardar
                                </button>
                            </div>
                        </div>

                        <input type="hidden" id="asignacion-genero-select" value="">
                        <input type="hidden" id="asignacion-talla-select" value="">
                        <input type="hidden" id="contador-asignaciones" value="0">
                    </div>
