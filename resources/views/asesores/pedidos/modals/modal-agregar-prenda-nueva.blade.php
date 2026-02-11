<!-- MODAL: Agregar Prenda Nueva (Sin Cotización) -->
<div id="modal-agregar-prenda-nueva" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 99999; align-items: center; justify-content: center; overflow-y: auto; padding: 2rem 0;">
    <div class="modal-container modal-xl">
        <!-- Header -->
        <div class="modal-header modal-header-primary">
            <h3 class="modal-title" id="modal-prenda-titulo">
                <span class="material-symbols-rounded" id="modal-prenda-icon">add_box</span>
                <span id="modal-prenda-texto">Agregar Prenda Nueva</span>
            </h3>
            <button class="modal-close-btn" onclick="cerrarModalPrendaNueva()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        
        <!-- Body -->
        <div class="modal-body">
            <form id="form-prenda-nueva">
                <!-- Layout en 2 columnas: Datos a la izquierda, Fotos a la derecha -->
                <div class="form-prenda-grid">
                    <!-- COLUMNA IZQUIERDA: Datos principales -->
                    <div>
                        <!-- Primera fila: Nombre y Origen -->
                        <div class="form-row-2col">
                            <!-- Nombre de la prenda -->
                            <div class="form-group">
                                <label for="nueva-prenda-nombre" class="form-label-primary">
                                    <span class="material-symbols-rounded">checkroom</span>NOMBRE DE LA PRENDA *
                                </label>
                                <input type="text" id="nueva-prenda-nombre" required placeholder="Ej: CAMISA DRILL, POLO, PANTALÓN..." class="form-input" onkeyup="this.value = this.value.toUpperCase(); cargarPrendasDatalist();" style="text-transform: uppercase;" list="lista-prendas-autocomplete">
                                <datalist id="lista-prendas-autocomplete">
                                    <!-- Las opciones se cargarán dinámicamente desde el JavaScript -->
                                </datalist>
                            </div>
                            
                            <!-- Origen -->
                            <div class="form-group">
                                <label for="nueva-prenda-origen-select" class="form-label-primary">
                                    <span class="material-symbols-rounded">location_on</span>ORIGEN *
                                </label>
                                <select id="nueva-prenda-origen-select" class="form-input">
                                    <option value="bodega">Bodega</option>
                                    <option value="confeccion">Confección</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Descripción -->
                        <div class="form-group">
                            <label for="nueva-prenda-descripcion" class="form-label-primary">
                                <span class="material-symbols-rounded">description</span>DESCRIPCIÓN
                            </label>
                            <textarea id="nueva-prenda-descripcion" placeholder="Descripción de la prenda, detalles especiales..." class="form-textarea" onkeyup="this.value = this.value.toUpperCase();" style="text-transform: uppercase;"></textarea>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: Fotos de la Prenda -->
                    <div class="foto-panel">
                        <label for="nueva-prenda-foto-input" class="foto-panel-label">
                            <span class="material-symbols-rounded">photo_camera</span>FOTOS
                        </label>
                        
                        <!-- Imagen principal preview -->
                        <div id="nueva-prenda-foto-preview" class="foto-preview foto-preview-lg">
                            <div class="foto-preview-content">
                                <div class="material-symbols-rounded">add_photo_alternate</div>
                                <div class="foto-preview-text">Click para seleccionar o<br>Ctrl+V para pegar imagen</div>
                            </div>
                        </div>
                        
                        <!-- Contador de fotos -->
                        <div id="nueva-prenda-foto-contador" class="foto-counter"></div>
                        
                        <!-- Input de archivos -->
                        <input type="file" id="nueva-prenda-foto-input" accept="image/*" style="display: none;" aria-label="Fotos de la prenda" onchange="manejarImagenesPrenda(this)">
                        
                        <!-- Botón agregar más fotos -->
                        <button type="button" id="nueva-prenda-foto-btn" class="btn btn-sm btn-primary" onclick="document.getElementById('nueva-prenda-foto-input').click()">
                            + Agregar
                        </button>
                    </div>
                </div>

                <!-- Color, Tela, Referencia e Imágenes de Tela -->
                <div class="form-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <label class="form-label-primary">
                            <span class="material-symbols-rounded">palette</span>COLOR, TELA Y REFERENCIA
                        </label>
                        <button type="button" id="btn-asignar-colores-tallas" class="btn btn-primary btn-sm" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
                            <span class="material-symbols-rounded" style="font-size: 1.1rem;">color_lens</span>Asignar por Talla
                        </button>
                    </div>
                    
                    <!-- VISTA 1: Tabla de telas (Por defecto visible) -->
                    <div id="vista-tabla-telas" style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem; table-layout: fixed;">
                            <thead>
                                <tr style="background: #0066cc; border-bottom: 2px solid #0066cc;">
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white; width: 20%;">Tela</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white; width: 20%;">Color</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white; width: 20%;">Referencia</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: white; width: 20%;">Imagen Tela</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: white; width: 20%;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbody-telas">
                                <!-- Fila para agregar nueva tela -->
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.5rem; width: 20%;">
                                        <label for="nueva-prenda-tela" class="sr-only">Tela</label>
                                        <input type="text" id="nueva-prenda-tela" placeholder="TELA..." class="form-input" list="opciones-telas" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                        <datalist id="opciones-telas">
                                            <!-- Opciones cargadas desde /asesores/api/telas -->
                                        </datalist>
                                    </td>
                                    <td style="padding: 0.5rem; width: 20%;">
                                        <label for="nueva-prenda-color" class="sr-only">Color</label>
                                        <input type="text" id="nueva-prenda-color" placeholder="COLOR..." class="form-input" list="opciones-colores" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                        <datalist id="opciones-colores">
                                            <!-- Opciones cargadas desde /asesores/api/colores -->
                                        </datalist>
                                    </td>
                                    <td style="padding: 0.5rem; width: 20%;">
                                        <label for="nueva-prenda-referencia" class="sr-only">Referencia</label>
                                        <input type="text" id="nueva-prenda-referencia" placeholder="REF..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                    </td>
                                    <td style="padding: 0.5rem; text-align: center; vertical-align: top; width: 20%;">
                                        <div id="nueva-prenda-tela-drop-zone" class="tela-drop-zone" style="position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80px; width: 100%; transition: all 0.2s ease; border: 2px dashed transparent; border-radius: 6px; padding: 8px; cursor: pointer;"
                                             ondrop="handleDropTela(event)" 
                                             ondragover="handleDragOver(event)"
                                             ondragleave="handleDragLeave(event)"
                                             onclick="document.getElementById('modal-agregar-prenda-nueva-file-input').click()">
                                            <button type="button" class="btn btn-primary btn-flex" style="font-size: 0.75rem; padding: 0.5rem 1rem; transition: all 0.2s ease; margin-bottom: 8px;" title="Agregar imagen (opcional) o arrastra una imagen aquí">
                                                <span class="material-symbols-rounded" style="font-size: 1.2rem; margin-right: 0.5rem;">image</span>
                                                <span style="font-size: 0.7rem;">Agregar imagen</span>
                                            </button>
                                            <input type="file" id="modal-agregar-prenda-nueva-file-input" accept="image/*" style="display: none;" aria-label="Imagen de la tela" onchange="manejarImagenTela(this)">
                                            
                                            <!-- Texto de ayuda -->
                                            <div style="text-align: center; color: #6b7280; font-size: 0.7rem; margin-top: 4px;">
                                                <div class="material-symbols-rounded" style="font-size: 1.2rem; opacity: 0.5;">cloud_upload</div>
                                                <div>Arrastra o Ctrl+V para pegar</div>
                                            </div>
                                        </div>
                                        <!-- Preview temporal dentro de la celda - EN EL FLUJO VISUAL Y VISIBLE -->
                                        <div id="nueva-prenda-tela-preview" style="display: none; flex-wrap: wrap; gap: 0.5rem; justify-content: center; align-items: flex-start; margin-top: 0.5rem; padding: 0.5rem; background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 4px; width: calc(100% + 1rem); margin-left: -0.5rem; margin-right: -0.5rem;"
                                             ondrop="handleDropTela(event)" 
                                             ondragover="handleDragOver(event)"
                                             ondragleave="handleDragLeave(event)"
                                             onclick="document.getElementById('modal-agregar-prenda-nueva-file-input').click()">
                                            
                                            <!-- Texto de ayuda -->
                                            <div style="text-align: center; color: #6b7280; font-size: 0.7rem; margin-top: 4px;">
                                                <div class="material-symbols-rounded" style="font-size: 1.2rem; opacity: 0.5;">cloud_upload</div>
                                                <div>Arrastra o Ctrl+V para pegar</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 0.5rem; text-align: center; width: 20%;">
                                        <button type="button" onclick="agregarTelaNueva()" class="btn btn-success btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;" title="Agregar esta tela">
                                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">add</span>Agregar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- VISTA 2: WIZARD - Asignar Colores por Talla (Oculta inicialmente) -->
                    <div id="vista-asignacion-colores" style="display: none;">
                        
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
                                    
                                    <button type="button" class="wizard-genero-btn" data-genero="sobremedida" onclick="wizardSeleccionarGenero('sobremedida')" style="padding: 1rem; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.85rem; color: #374151; transition: all 0.2s;">
                                        <span class="material-symbols-rounded" style="font-size: 1.5rem; display: block; margin-bottom: 0.25rem; color: #6b7280;">accessibility_new</span>
                                        SOBRE MEDIDA
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

                        <!-- BOTONES DE NAVEGACIÓN -->
                        <div style="display: flex; gap: 1rem; justify-content: space-between; align-items: center; margin-top: 2rem;">
                            <button type="button" id="wzd-btn-atras" class="btn btn-secondary" style="display: none;">
                                <span class="material-symbols-rounded">arrow_back</span>Atrás
                            </button>
                            
                            <div style="flex: 1;"></div>
                            
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <button type="button" class="btn btn-secondary">
                                    <span class="material-symbols-rounded">close</span>Cancelar
                                </button>
                                
                                <button type="button" id="wzd-btn-siguiente" class="btn btn-primary" style="display: none; align-items: center; gap: 0.25rem;">
                                    Siguiente<span class="material-symbols-rounded">arrow_forward</span>
                                </button>
                                
                                <button type="button" id="btn-guardar-asignacion" class="btn btn-success" style="display: none; align-items: center; justify-content: center; gap: 0.25rem; font-weight: 600;">
                                    <span class="material-symbols-rounded">check_circle</span>Guardar Asignación
                                </button>
                            </div>
                        </div>
                        <input type="hidden" id="asignacion-genero-select" value="" aria-label="Género seleccionado para asignación">
                        <input type="hidden" id="asignacion-talla-select" value="" aria-label="Talla seleccionada para asignación">
                        <input type="hidden" id="contador-asignaciones" value="0" aria-label="Contador de asignaciones">
                    </div>

                    <!-- RESUMEN DE ASIGNACIONES (Visible cuando estás en "Asignar por Talla") -->
                    <div id="seccion-resumen-asignaciones" style="display: none; margin-top: 1.5rem;">
                        <label class="form-label-primary">
                            <span class="material-symbols-rounded">checklist</span>RESUMEN DE ASIGNACIONES *
                        </label>
                        
                        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                                <thead>
                                    <tr style="background: #f3f4f6; border-bottom: 2px solid #d1d5db;">
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">TELA</th>
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">GÉNERO</th>
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">TALLA</th>
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">COLOR</th>
                                        <th style="padding: 0.75rem; text-align: center; font-weight: 600; width: 100px;">CANTIDAD</th>
                                        <th style="padding: 0.75rem; text-align: center; font-weight: 600; width: 60px;">ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-resumen-asignaciones-cuerpo"></tbody>
                            </table>
                        </div>
                        
                        <div id="msg-resumen-vacio" style="text-align: center; padding: 2rem; color: rgb(156, 163, 175); background: rgb(249, 250, 251); border-radius: 8px; border: 1px dashed rgb(209, 213, 219); margin-top: 1rem; display: block;">
                            <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">inbox</span>
                            Sin asignaciones aún. Accede a "Asignar por Talla" para agregar.
                        </div>
                        
                        <div style="margin-top: 1rem; padding: 1rem; background: #e0f2fe; border-left: 4px solid #0369a1; border-radius: 4px;">
                            <p style="margin: 0; font-size: 0.875rem; color: #075985;">
                                <strong>Total asignado:</strong> <span id="total-asignaciones-resumen">0</span> unidades
                            </p>
                        </div>
                    </div>

                    <!-- Tallas y Cantidades -->
                    <div id="seccion-tallas-cantidades" style="margin-top: 1.5rem;">
                        <label class="form-label-primary">
                            <span class="material-symbols-rounded">straighten</span>TALLAS Y CANTIDADES *
                        </label>
                        
                        <!-- Seleccionar Género(s) o Sobremedida -->
                        <div class="genero-buttons">
                            <!-- Botón DAMA -->
                            <button type="button" id="btn-genero-dama" class="btn-genero" data-selected="false" onclick="abrirModalSeleccionarTallas('dama')">
                                <div class="btn-genero-content">
                                    <span class="material-symbols-rounded">woman</span>
                                    <span>DAMA</span>
                                </div>
                                <span id="check-dama" class="btn-genero-check">✓</span>
                            </button>
                            
                            <!-- Botón CABALLERO -->
                            <button type="button" id="btn-genero-caballero" class="btn-genero" data-selected="false" onclick="abrirModalSeleccionarTallas('caballero')">
                                <div class="btn-genero-content">
                                    <span class="material-symbols-rounded">man</span>
                                    <span>CABALLERO</span>
                                </div>
                                <span id="check-caballero" class="btn-genero-check">✓</span>
                            </button>
                            
                            <!-- Botón SOBREMEDIDA -->
                            <button type="button" id="btn-genero-sobremedida" class="btn-genero" data-selected="false" onclick="abrirModalSobremedida()">
                                <div class="btn-genero-content">
                                    <span class="material-symbols-rounded">straighten</span>
                                    <span>SOBREMEDIDA</span>
                                </div>
                                <span id="check-sobremedida" class="btn-genero-check">✓</span>
                            </button>
                        </div>
                        
                        <!-- Tarjetas de Géneros Seleccionados -->
                        <div id="tarjetas-generos-container" class="generos-container"></div>
                        
                        <!-- Total general -->
                        <div class="total-box">
                            <span class="material-symbols-rounded">shopping_cart</span>
                            Total: <span id="total-prendas">0</span> unidades
                        </div>
                    </div>
                </div>

                <!-- Variaciones -->
                <div class="form-section">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">tune</span>VARIACIONES ESPECÍFICAS
                    </label>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #0066cc;">
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; width: 50px; color: white;">
                                        APLICA
                                    </th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white;">
                                        VARIACIÓN
                                    </th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white;">
                                        ESPECIFICACIÓN
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Manga -->
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <label for="aplica-manga" class="sr-only">Aplicar Manga</label>
                                        <input type="checkbox" id="aplica-manga" class="form-checkbox" onchange="manejarCheckVariacion(this)" style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <span style="font-weight: 600; color: #0066cc;">Manga</span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                            <div>
                                                <label for="manga-input" class="sr-only">Tipo de Manga</label>
                                                <input type="text" id="manga-input" placeholder="Ej: Larga, Corta, 3/4..." disabled list="opciones-manga" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%;">
                                                <datalist id="opciones-manga">
                                                    <!-- Las opciones se cargarán dinámicamente desde /asesores/api/tipos-manga -->
                                                </datalist>
                                            </div>
                                            <div>
                                                <label for="manga-obs" class="sr-only">Observaciones de Manga</label>
                                                <input type="text" id="manga-obs" placeholder="Observaciones..." disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Bolsillos -->
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <label for="aplica-bolsillos" class="sr-only">Aplicar Bolsillos</label>
                                        <input type="checkbox" id="aplica-bolsillos" class="form-checkbox" onchange="manejarCheckVariacion(this)" style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <span style="font-weight: 600; color: #0066cc;">Bolsillos</span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <label for="bolsillos-obs" class="sr-only">Observaciones de Bolsillos</label>
                                        <input type="text" id="bolsillos-obs" placeholder="Observaciones (Ej: 4 bolsillos con cierre, ocultos, etc...)" disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                    </td>
                                </tr>
                                
                                <!-- Broche/Botón -->
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <label for="aplica-broche" class="sr-only">Aplicar Broche/Botón</label>
                                        <input type="checkbox" id="aplica-broche" class="form-checkbox" onchange="manejarCheckVariacion(this)" style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <span style="font-weight: 600; color: #0066cc;">Broche/Botón</span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                            <div>
                                                <label for="broche-input" class="sr-only">Tipo de Broche/Botón</label>
                                                <select id="broche-input" disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%;">
                                                    <option value="">Seleccionar tipo...</option>
                                                    <option value="boton">Botón</option>
                                                    <option value="broche">Broche</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label for="broche-obs" class="sr-only">Observaciones de Broche/Botón</label>
                                                <input type="text" id="broche-obs" placeholder="Observaciones..." disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Procesos -->
                <div class="form-section">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">settings</span>PROCESOS (Opcional)
                    </label>
                    <div class="procesos-container">
                        <!-- Reflectivo -->
                        <label class="proceso-checkbox" for="checkbox-reflectivo">
                            <input type="checkbox" id="checkbox-reflectivo" name="nueva-prenda-procesos" value="reflectivo" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('reflectivo', true); } else { manejarCheckboxProceso('reflectivo', false); } }">
                            <span><i class="fas fa-lightbulb" style="color: #FFD700; margin-right: 6px;"></i>Reflectivo</span>
                        </label>
                        
                        <!-- Bordado -->
                        <label class="proceso-checkbox" for="checkbox-bordado">
                            <input type="checkbox" id="checkbox-bordado" name="nueva-prenda-procesos" value="bordado" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('bordado', true); } else { manejarCheckboxProceso('bordado', false); } }">
                            <span><i class="fas fa-gem" style="color: #9333EA; margin-right: 6px;"></i>Bordado</span>
                        </label>
                        
                        <!-- Estampado -->
                        <label class="proceso-checkbox" for="checkbox-estampado">
                            <input type="checkbox" id="checkbox-estampado" name="nueva-prenda-procesos" value="estampado" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('estampado', true); } else { manejarCheckboxProceso('estampado', false); } }">
                            <span><i class="fas fa-paint-brush" style="color: #DC2626; margin-right: 6px;"></i>Estampado</span>
                        </label>
                        
                        <!-- DTF -->
                        <label class="proceso-checkbox" for="checkbox-dtf">
                            <input type="checkbox" id="checkbox-dtf" name="nueva-prenda-procesos" value="dtf" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('dtf', true); } else { manejarCheckboxProceso('dtf', false); } }">
                            <span><i class="fas fa-print" style="color: #EA580C; margin-right: 6px;"></i>DTF</span>
                        </label>
                        
                        <!-- Sublimado -->
                        <label class="proceso-checkbox" for="checkbox-sublimado">
                            <input type="checkbox" id="checkbox-sublimado" name="nueva-prenda-procesos" value="sublimado" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('sublimado', true); } else { manejarCheckboxProceso('sublimado', false); } }">
                            <span><i class="fas fa-tint" style="color: #0891B2; margin-right: 6px;"></i>Sublimado</span>
                        </label>
                    </div>
                    
                    <!--  CONTENEDOR PARA TARJETAS DE PROCESOS CONFIGURADOS -->
                    <div id="contenedor-tarjetas-procesos" style="margin-top: 1rem; display: none;"></div>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModalPrendaNueva()">Cancelar</button>
            <button id="btn-guardar-prenda" class="btn btn-primary" onclick="agregarPrendaNueva()">
                <span class="material-symbols-rounded">check</span>Agregar Prenda
            </button>
        </div>
    </div>
</div>

<!-- SCRIPT: Asegurar que la fila de inputs de telas siempre esté disponible en edición -->
<script>
/**
 * Asegurar que la fila de inputs de telas sea siempre visible y funcional
 * Ejecutar después de que se renderice el modal en modo edición
 */
window.asegurarFilaTelasVisible = function() {
    // Esperar a que el DOM esté listo
    setTimeout(() => {
        const tbody = document.getElementById('tbody-telas');
        if (!tbody) return;
        
        const primeraFila = tbody.querySelector('tr:first-child');
        if (!primeraFila) return;
        
        const telasInputRow = primeraFila.querySelector('#nueva-prenda-tela');
        if (!telasInputRow) return;
        
        // Asegurar que la primera fila sea visible
        primeraFila.style.display = 'table-row';
        primeraFila.style.visibility = 'visible';
        primeraFila.style.opacity = '1';
        
        // Asegurar que todos los inputs sean interactivos
        const inputs = primeraFila.querySelectorAll('input, button');
        inputs.forEach(input => {
            input.disabled = false;
            input.style.display = '';
            input.style.visibility = 'visible';
            input.style.opacity = '1';
            input.style.pointerEvents = 'auto';
        });
        
        console.log('[asegurarFilaTelasVisible]  Fila de inputs de telas asegurada como visible y funcional');
    }, 100);
};

// Llamar al abrir el modal en modo edición
if (window.actualizarTablaTelas) {
    const originalActualizarTablaTelas = window.actualizarTablaTelas;
    window.actualizarTablaTelas = function() {
        originalActualizarTablaTelas();
        // Después de actualizar la tabla, asegurar que la fila sea visible
        setTimeout(() => {
            window.asegurarFilaTelasVisible();
        }, 50);
    };
}
</script>

<script>
// Funciones para drag & drop de imágenes de tela
function handleDragOver(event) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.style.borderColor = '#3b82f6';
    event.currentTarget.style.backgroundColor = '#eff6ff';
}

function handleDragLeave(event) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.style.borderColor = '#d1d5db';
    event.currentTarget.style.backgroundColor = '#f9fafb';
}

function handleDropTela(event) {
    event.preventDefault();
    event.stopPropagation();
    
    // Restaurar estilos
    event.currentTarget.style.borderColor = '#d1d5db';
    event.currentTarget.style.backgroundColor = '#f9fafb';
    
    // Obtener archivos
    const files = event.dataTransfer.files;
    if (files.length === 0) return;
    
    // Simular input change
    const input = document.getElementById('nueva-prenda-tela-imagen-input');
    input.files = files;
    manejarImagenTela(input);
}

// Función para manejar paste de imágenes
document.addEventListener('paste', function(event) {
    const activeElement = document.activeElement;
    
    // Verificar si estamos en un campo de tela
    if (activeElement && (
        activeElement.id === 'nueva-prenda-color' || 
        activeElement.id === 'nueva-prenda-tela' || 
        activeElement.id === 'nueva-prenda-referencia'
    )) {
        
        const items = event.clipboardData.items;
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            
            if (item.type.indexOf('image') !== -1) {
                const file = item.getAsFile();
                if (file) {
                    const input = document.getElementById('nueva-prenda-tela-imagen-input');
                    
                    // Crear un nuevo FileList
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    input.files = dataTransfer.files;
                    
                    manejarImagenTela(input);
                    break;
                }
            }
        }
    }
});
</script>

<!-- Scripts: Módulos desacoplados de Colores por Talla -->
<script src="{{ asset('js/componentes/colores-por-talla/StateManager.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/DOMUtils.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/AsignacionManager.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/WizardManager.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/UIRenderer.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/ColoresPorTalla.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/compatibilidad.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/diagnostico.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/diagnostico.js') }}"></script>
