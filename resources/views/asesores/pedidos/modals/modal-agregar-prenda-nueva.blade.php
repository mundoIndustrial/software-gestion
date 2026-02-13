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
                    <div class="foto-panel" id="panel-fotos-prenda" style="border: 2px solid #9333ea; border-radius: 8px; padding: 1rem; background: #faf5ff;">
                        <label for="nueva-prenda-foto-input" class="foto-panel-label" style="color: #7c3aed;">
                            <span class="material-symbols-rounded">photo_camera</span>📸 FOTOS DE PRENDA
                        </label>
                        
                        <!-- Imagen principal preview -->
                        <div id="nueva-prenda-foto-preview" class="foto-preview foto-preview-lg" tabindex="0" style="outline: none; border: 2px dashed #d1b3e6; background: white;" data-zona="prenda">
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
                                        <div id="nueva-prenda-tela-drop-zone" class="tela-drop-zone" style="position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80px; width: 100%; transition: all 0.2s ease; border: 2px dashed #10b981; border-radius: 6px; padding: 8px; cursor: pointer; background: #f0fdf4;" data-zona="tela" data-estado="inicial" tabindex="0">
                                            <button type="button" class="btn btn-success btn-flex" style="font-size: 0.75rem; padding: 0.5rem 1rem; transition: all 0.2s ease; margin-bottom: 8px; pointer-events: auto;" title="Click para seleccionar imagen o arrastra una aquí" onclick="event.stopPropagation(); event.preventDefault(); document.getElementById('nueva-prenda-tela-file-input').click(); return false;">
                                                <span class="material-symbols-rounded" style="font-size: 1.2rem; margin-right: 0.5rem;">image</span>
                                                <span style="font-size: 0.7rem;">Agregar imagen</span>
                                            </button>
                                            <input type="file" id="nueva-prenda-tela-file-input" accept="image/*" style="display: none;" aria-label="Imagen de la tela" onchange="manejarImagenTela(this)">
                                            
                                            <!-- Texto de ayuda -->
                                            <div style="text-align: center; color: #059669; font-size: 0.7rem; margin-top: 4px; pointer-events: none; font-weight: 500;">
                                                <div class="material-symbols-rounded" style="font-size: 1.2rem;">cloud_upload</div>
                                                <div>📸 TELA: Arrastra aquí o pega Ctrl+V</div>
                                            </div>
                                        </div>
                                        <!-- Preview temporal dentro de la celda -->
                                        <div id="nueva-prenda-tela-preview" style="display: none; flex-wrap: wrap; gap: 0.5rem; justify-content: center; align-items: flex-start; margin-top: 0.5rem; padding: 0.5rem; background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 4px; width: calc(100% + 1rem); margin-left: -0.5rem; margin-right: -0.5rem;"></div>
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

<!-- Componente: Galería Modal Reutilizable -->
<x-galeria-modal :id="'prenda'" />

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

<!-- Scripts: Sistema Drag & Drop Manager -->
<script src="{{ asset('js/componentes/prendas-wrappers.js') }}"></script>
<script src="{{ asset('js/componentes/prendas-module/services/UIHelperService.js') }}"></script>
<script src="{{ asset('js/componentes/prendas-module/services/ClipboardService.js') }}"></script>
<script src="{{ asset('js/componentes/prendas-module/services/ContextMenuService.js') }}"></script>
<script src="{{ asset('js/componentes/prendas-module/services/DragDropEventHandler.js') }}"></script>
<script src="{{ asset('js/componentes/prendas-module/handlers/BaseDragDropHandler.js') }}"></script>
<script src="{{ asset('js/componentes/prendas-module/handlers/PrendaDragDropHandler.js') }}"></script>
<script src="{{ asset('js/componentes/prendas-module/handlers/TelaDragDropHandler.js') }}"></script>
<script src="{{ asset('js/componentes/prendas-module/handlers/ProcesoDragDropHandler.js') }}"></script>
<script src="{{ asset('js/componentes/prendas-module/drag-drop-manager.js') }}"></script>

<!-- Script para inicializar el Drag & Drop cuando se abre el modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Función para inicializar el drag & drop cuando el modal se abra
    window.inicializarDragDropModalPrenda = function() {
        
        // Esperar un poco a que todos los scripts carguen
        setTimeout(() => {
            if (window.DragDropManager) {
                try {
                    // Forzar re-inicialización completa
                    window.DragDropManager.inicializar();
                    console.log('[DragDrop] ✅ Sistema inicializado correctamente');
                    
                    // Verificar estado
                    const estado = window.DragDropManager.getEstadoCompleto();
                    console.log('[DragDrop] 📊 Estado del sistema:', estado);
                    
                    // Debug info
                    const debugInfo = window.DragDropManager.getDebugInfo();
                    console.log('[DragDrop] 🔍 Debug info:', debugInfo);
                    
                    // Verificar que el preview esté configurado
                    const preview = document.getElementById('nueva-prenda-foto-preview');
                    if (preview) {
                        console.log('[DragDrop] ✅ Preview encontrado:', preview);
                        
                        // Agregar listeners manuales como fallback con máxima prioridad
                        preview.addEventListener('dragover', function(e) {
                            console.log('[DragDrop] 🎯 Drag over detectado en preview (manual)');
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            this.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
                            this.style.border = '2px dashed #3b82f6';
                        }, true);
                        
                        preview.addEventListener('dragleave', function(e) {
                            console.log('[DragDrop] 🎯 Drag leave detectado en preview (manual)');
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            this.style.backgroundColor = '';
                            this.style.border = '';
                        }, true);
                        
                        preview.addEventListener('drop', function(e) {
                            console.log('[DragDrop] 🎯 Drop detectado en preview (manual - PRIORIDAD MÁXIMA)');
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            
                            // Restaurar estilos
                            this.style.backgroundColor = '';
                            this.style.border = '';
                            
                            const files = e.dataTransfer.files;
                            console.log('[DragDrop] 📁 Archivos recibidos:', files.length);
                            
                            if (files.length > 0) {
                                console.log('[DragDrop] 📸 Procesando archivos...');
                                
                                // Asignar archivos directamente al input
                                const input = document.getElementById('nueva-prenda-foto-input');
                                const preview = document.getElementById('nueva-prenda-foto-preview');
                                
                                if (input && preview) {
                                    // Asignar archivos directamente al input
                                    input.files = files;
                                    console.log('[DragDrop] 📸 Archivos asignados al input');
                                    
                                    // Llamar a la función de manejo de imágenes
                                    if (typeof window.manejarImagenesPrenda === 'function') {
                                        console.log('[DragDrop] 📸 Llamando a manejarImagenesPrenda...');
                                        window.manejarImagenesPrenda(input);
                                    } else {
                                        console.error('[DragDrop] ❌ manejarImagenesPrenda no disponible');
                                        
                                        // Fallback: mostrar imagen manualmente
                                        const reader = new FileReader();
                                        reader.onload = function(e) {
                                            preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
                                            console.log('[DragDrop] 📸 Imagen mostrada manualmente (fallback)');
                                        };
                                        reader.readAsDataURL(files[0]);
                                    }
                                } else {
                                    console.error('[DragDrop] ❌ Input o preview no encontrados');
                                }
                            } else {
                                console.log('[DragDrop] ⚠️ No se recibieron archivos');
                            }
                        }, true);
                        
                        console.log('[DragDrop] ✅ Listeners manuales agregados como fallback');
                        
                        // Agregar listener de paste como fallback adicional
                        preview.addEventListener('paste', function(e) {
                            console.log('[DragDrop] 📋 Paste directo detectado en preview');
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const items = e.clipboardData.items;
                            console.log('[DragDrop] 📋 Items en portapapeles:', items.length);
                            
                            for (let i = 0; i < items.length; i++) {
                                const item = items[i];
                                console.log('[DragDrop] 📋 Item', i, ':', item.kind, item.type);
                                
                                if (item.kind === 'file' && item.type.startsWith('image/')) {
                                    const file = item.getAsFile();
                                    if (file) {
                                        console.log('[DragDrop] 📸 Imagen obtenida del portapapeles:', file.name, file.type, file.size);
                                        
                                        // Simular input file
                                        const input = document.getElementById('nueva-prenda-foto-input');
                                        if (input) {
                                            // Crear DataTransfer para asignar el archivo
                                            const dataTransfer = new DataTransfer();
                                            dataTransfer.items.add(file);
                                            input.files = dataTransfer.files;
                                            
                                            console.log('[DragDrop] 📸 Llamando a manejarImagenesPrenda desde paste...');
                                            if (typeof window.manejarImagenesPrenda === 'function') {
                                                window.manejarImagenesPrenda(input);
                                            } else {
                                                console.error('[DragDrop] ❌ manejarImagenesPrenda no disponible');
                                            }
                                        }
                                        break; // Solo procesar la primera imagen
                                    }
                                }
                            }
                        });
                        
                        console.log('[DragDrop] ✅ Listener de paste agregado como fallback');
                        
                        // NO reconfigurar el DragDropEventHandler para evitar conflictos
                        // Nuestro listener manual es suficiente y tiene prioridad
                        console.log('[DragDrop] ✅ Usando solo listeners manuales para evitar conflictos');
                    } else {
                        console.error('[DragDrop] ❌ Preview no encontrado');
                    }
                    
                    console.log('[DragDrop] ✅ Menú contextual agregado');
                    
                } catch (error) {
                    console.error('[DragDrop] ❌ Error al inicializar:', error);
                }
            } else {
                console.error('[DragDrop] ❌ DragDropManager no disponible');
            }
        }, 200);
    };
    
    // Escuchar cuando se abre el modal de prenda
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                const modal = document.getElementById('modal-agregar-prenda-nueva');
                if (modal && modal.style.display !== 'none' && modal.style.display !== '') {
                    console.log('[DragDrop] 📋 Modal detectado como visible, inicializando...');
                    window.inicializarDragDropModalPrenda();
                }
            }
        });
    });
    
    // Observar el modal
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        observer.observe(modal, { attributes: true });
    }
    
    // También escuchar por el evento de apertura del modal
    document.addEventListener('modalPrendaAbierto', function() {
        console.log('[DragDrop] 📋 Evento modalPrendaAbierto recibido');
        window.inicializarDragDropModalPrenda();
    });
    
    // ========================================================================
    // LISTENERS ESPECÍFICOS POR ZONA - PRENDA vs TELA
    // ========================================================================
    
    // ZONA 1: FOTOS DE PRENDA (Morado)
    const prendaPreview = document.getElementById('nueva-prenda-foto-preview');
    if (prendaPreview) {
        console.log('[📸 PRENDA] Inicializando listeners específicos para fotos de PRENDA');
        
        ['dragover', 'dragenter', 'dragleave', 'drop', 'paste'].forEach(event => {
            prendaPreview.addEventListener(event, (e) => {
                // Solo procesa si es para PRENDA
                if (e.target.getAttribute('data-zona') === 'prenda' || prendaPreview.contains(e.target)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true); // Usar capture mode para tomar prioridad
        });
        
        // Dragover - visual feedback
        prendaPreview.addEventListener('dragover', (e) => {
            if (prendaPreview.contains(e.target)) {
                prendaPreview.style.borderColor = '#7c3aed';
                prendaPreview.style.background = '#fdf2f8';
                prendaPreview.setAttribute('data-estado', 'hovering');
                console.log('[📸 PRENDA] 🎯 Dragging over prenda area');
            }
        });
        
        // Dragleave - reset
        prendaPreview.addEventListener('dragleave', (e) => {
            if (e.target === prendaPreview) {
                prendaPreview.style.borderColor = '#d1b3e6';
                prendaPreview.style.background = 'white';
                prendaPreview.setAttribute('data-estado', 'inicial');
                console.log('[📸 PRENDA] ⬆️ Left prenda area');
            }
        });
        
        // Drop en prenda
        prendaPreview.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (prendaPreview.contains(e.target)) {
                prendaPreview.style.borderColor = '#d1b3e6';
                prendaPreview.style.background = 'white';
                prendaPreview.setAttribute('data-estado', 'inicial');
                
                const files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    console.log('[📸 PRENDA] 📁 Archivos dropped en PRENDA:', files.length);
                    manejarImagenesPrendaDropped(files);
                }
            }
        });
        
        // Paste en prenda
        prendaPreview.addEventListener('paste', (e) => {
            if (prendaPreview.contains(document.activeElement) || e.target === prendaPreview) {
                e.preventDefault();
                e.stopPropagation();
                
                const items = e.clipboardData.items;
                const files = [];
                for (let i = 0; i < items.length; i++) {
                    if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
                        files.push(items[i].getAsFile());
                    }
                }
                
                if (files.length > 0) {
                    console.log('[📸 PRENDA] 📋 Paste detectado en PRENDA:', files.length, 'archivos');
                    manejarImagenesPrendaDropped(files);
                }
            }
        });
    }
    
    // ZONA 2: FOTOS DE TELA (Verde)
    const telaDropZone = document.getElementById('nueva-prenda-tela-drop-zone');
    if (telaDropZone) {
        console.log('[🧵 TELA] Inicializando listeners específicos para imágenes de TELA');
        
        ['dragover', 'dragenter', 'dragleave', 'drop', 'paste'].forEach(event => {
            telaDropZone.addEventListener(event, (e) => {
                // Solo procesa si es para TELA
                if (e.target.getAttribute('data-zona') === 'tela' || telaDropZone.contains(e.target)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true); // Usar capture mode para tomar prioridad
        });
        
        // Dragover - visual feedback
        telaDropZone.addEventListener('dragover', (e) => {
            if (telaDropZone.contains(e.target)) {
                telaDropZone.style.borderColor = '#059669';
                telaDropZone.style.background = '#dcfce7';
                telaDropZone.setAttribute('data-estado', 'hovering');
                console.log('[🧵 TELA] 🎯 Dragging over tela area');
            }
        });
        
        // Dragleave - reset
        telaDropZone.addEventListener('dragleave', (e) => {
            if (e.target === telaDropZone) {
                telaDropZone.style.borderColor = '#10b981';
                telaDropZone.style.background = '#f0fdf4';
                telaDropZone.setAttribute('data-estado', 'inicial');
                console.log('[🧵 TELA] ⬆️ Left tela area');
            }
        });
        
        // Drop en tela
        telaDropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (telaDropZone.contains(e.target)) {
                telaDropZone.style.borderColor = '#10b981';
                telaDropZone.style.background = '#f0fdf4';
                telaDropZone.setAttribute('data-estado', 'inicial');
                
                const files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    console.log('[🧵 TELA] 📁 Archivos dropped en TELA:', files.length);
                    manejarImagenTelaDropped(files[0]);
                }
            }
        });
        
        // Paste en tela
        telaDropZone.addEventListener('paste', (e) => {
            if (telaDropZone.contains(document.activeElement) || e.target === telaDropZone) {
                e.preventDefault();
                e.stopPropagation();
                
                const items = e.clipboardData.items;
                for (let i = 0; i < items.length; i++) {
                    if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
                        const file = items[i].getAsFile();
                        console.log('[🧵 TELA] 📋 Paste detectado en TELA:', file.name);
                        manejarImagenTelaDropped(file);
                        return;
                    }
                }
            }
        });
    }
    
    // Función auxiliar para manejar archivos en PRENDA
    window.manejarImagenesPrendaDropped = function(files) {
        const fileInput = document.getElementById('nueva-prenda-foto-input');
        if (fileInput) {
            const dataTransfer = new DataTransfer();
            for (let file of files) {
                dataTransfer.items.add(file);
            }
            fileInput.files = dataTransfer.files;
            const event = new Event('change', { bubbles: true });
            fileInput.dispatchEvent(event);
            console.log('[📸 PRENDA] ✅ Archivos procesados:', files.length);
        }
    };
    
    // Función auxiliar para manejar archivos en TELA
    window.manejarImagenTelaDropped = function(file) {
        const fileInput = document.getElementById('modal-agregar-prenda-nueva-file-input');
        if (fileInput) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;
            const event = new Event('change', { bubbles: true });
            fileInput.dispatchEvent(event);
            console.log('[🧵 TELA] ✅ Archivo procesado:', file.name);
        }
    };
});

// Función de debugging para probar el paste
window.probarPasteImagen = function() {
    console.log('[DragDrop] 🧪 Simulando paste de imagen...');
    
    const preview = document.getElementById('nueva-prenda-foto-preview');
    if (preview) {
        // Simular un evento de paste
        const mockEvent = {
            preventDefault: () => {},
            stopPropagation: () => {},
            clipboardData: {
                items: [
                    {
                        kind: 'file',
                        type: 'image/png',
                        getAsFile: () => new File(['test'], 'test.png', { type: 'image/png' })
                    }
                ]
            }
        };
        
        // Disparar evento paste manualmente
        if (preview.onpaste) {
            preview.onpaste(mockEvent);
        } else {
            console.log('[DragDrop] ❌ No hay listener de paste en el preview');
        }
    }
};
</script>

<!-- 🔧 LOADERS DE PRENDAS - Cargar directamente en el modal (si no se cargaron en Blade padre) -->
<script>
// Proteger contra carga múltiple
if (typeof PrendaEditorBasicos === 'undefined') {
    document.write('Cargando loaders del modal...');
}
</script>

<script src="{{ asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-basicos.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-imagenes.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-telas.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-variaciones.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-tallas.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-colores.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-procesos.js') }}"></script>

<!-- Manager del modal -->
<script src="{{ asset('js/modulos/crear-pedido/prendas/modalHandlers/prenda-modal-manager.js') }}"></script>

<!-- Servicios -->
<script src="{{ asset('js/modulos/crear-pedido/prendas/services/prenda-editor-service.js') }}"></script>

<!-- 🚫 NOTA: prenda-editor.js ya se carga en la Blade padre (crear-pedido.blade.php, edit.blade.php, etc.)
     Si se cargar aquí también, causará: "SyntaxError: Identifier 'PrendaEditor' has already been declared"
     Ahora prenda-editor.js tiene protección contra redeclaración con typeof PrendaEditor === 'undefined'
-->
