<!-- MODAL: Agregar Prenda Nueva (Sin Cotización) -->
<div id="modal-agregar-prenda-nueva" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1050000; align-items: center; justify-content: center; overflow-y: auto; padding: 1vh 1vw;">
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
                                <input type="text" id="nueva-prenda-nombre" required placeholder="Ej: CAMISA DRILL, POLO, PANTALÓN..." class="form-input" onkeyup="convertirAMayusculasConCursor(this); if(typeof cargarPrendasDatalist==='function') cargarPrendasDatalist();" style="text-transform: uppercase;" list="lista-prendas-autocomplete">
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
                                    <option value="" disabled selected>— Seleccionar origen —</option>
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
                            <textarea id="nueva-prenda-descripcion" placeholder="Descripción de la prenda, detalles especiales..." class="form-textarea" onkeyup="convertirAMayusculasConCursor(this);" style="text-transform: uppercase;"></textarea>
                            <small style="display: block; margin-top: 0.5rem; color: #dc3545; font-weight: 500;">
                                <strong>⚠️ NO OLVIDES:</strong> Agregar en descripción: Manga, Bolsillo, cuellos y puños. Incluir: Tela, Referencia y Código de Puños
                            </small>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: Fotos de la Prenda -->
                    <div class="foto-panel" id="panel-fotos-prenda" style="border: 2px solid #0066cc; border-radius: 8px; padding: 1rem; background: #f0f7ff;">
                        <label for="nueva-prenda-foto-input" class="foto-panel-label" style="color: #0066cc;">
                            <span class="material-symbols-rounded">photo_camera</span> FOTOS DE PRENDA
                        </label>
                        
                        <!-- Imagen principal preview -->
                        <div id="nueva-prenda-foto-preview" class="foto-preview foto-preview-lg" tabindex="0" style="outline: none; border: 2px dashed #4da6ff; background: white;" data-zona="prenda">
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

                <!-- Color, Tela y Asignación por Talla -->
                <div class="form-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <label class="form-label-primary">
                            <span class="material-symbols-rounded">palette</span>COLOR, TELA Y REFERENCIA
                        </label>
                    </div>
                    
                    <!-- Botones CTA: Agregar Tela (simple) y Asignar por Talla (wizard) -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <!-- CTA Simple -->
                        <button type="button" onclick="abrirModalTelaSimple()" style="padding: 1.25rem 1rem; background: #f9fafb; border: 2px solid #d1d5db; border-radius: 8px; cursor: pointer; text-align: center; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onmouseover="this.style.borderColor='#0066cc';this.style.background='#f0f7ff'" onmouseout="this.style.borderColor='#d1d5db';this.style.background='#f9fafb'">
                            <span class="material-symbols-rounded" style="font-size: 1.8rem; color: #0066cc;">add_circle</span>
                            <span style="font-weight: 600; color: #374151; font-size: 0.9rem;">Agregar Tela</span>
                            <span style="font-size: 0.75rem; color: #6b7280;">Tela, color, referencia, imagen</span>
                        </button>
                        <!-- CTA Wizard -->
                        <button type="button" onclick="abrirModalAsignarColores()" style="padding: 1.25rem 1rem; background: #f9fafb; border: 2px solid #d1d5db; border-radius: 8px; cursor: pointer; text-align: center; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onmouseover="this.style.borderColor='#7c3aed';this.style.background='#f5f3ff'" onmouseout="this.style.borderColor='#d1d5db';this.style.background='#f9fafb'">
                            <span class="material-symbols-rounded" style="font-size: 1.8rem; color: #7c3aed;">color_lens</span>
                            <span style="font-weight: 600; color: #374151; font-size: 0.9rem;">Asignar por Talla</span>
                            <span style="font-size: 0.75rem; color: #6b7280;">Género, talla, colores y cantidades</span>
                        </button>
                    </div>

                    <!-- (Tabla de telas unificada: se muestra abajo en seccion-resumen-asignaciones) -->

                    <!-- Datalists ocultos (necesarios para autocompletar en otros JS) -->
                    <div style="display: none;">
                        <datalist id="opciones-telas">
                            <!-- Opciones cargadas desde /api/asesores/telas -->
                        </datalist>
                        <datalist id="opciones-colores">
                            <!-- Opciones cargadas desde /api/asesores/colores -->
                        </datalist>
                    </div>
                    

                    <!-- SECCIÓN: RESUMEN DE ASIGNACIONES (tabla unificada) -->
                    <div id="seccion-resumen-asignaciones" style="display: none; margin-top: 1.5rem;">
                        <label class="form-label-primary">
                            <span class="material-symbols-rounded">checklist</span>RESUMEN DE ASIGNACIONES *
                        </label>
                        
                        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                                <thead>
                                    <tr style="background: #0066cc; border-bottom: 2px solid #004d99;">
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: white;">TELA</th>
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: white;">COLOR</th>
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: white;">REFERENCIA</th>
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: white;">IMAGEN</th>
                                        <th data-col="wizard-only" style="padding: 0.75rem; text-align: left; font-weight: 600; color: white;">GÉNERO</th>
                                        <th data-col="wizard-only" style="padding: 0.75rem; text-align: left; font-weight: 600; color: white;">TALLA</th>
                                        <th data-col="wizard-only" style="padding: 0.75rem; text-align: center; font-weight: 600; width: 80px; color: white;">CANT.</th>
                                        <th style="padding: 0.75rem; text-align: center; font-weight: 600; width: 60px; color: white;">ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-resumen-asignaciones-cuerpo"></tbody>
                            </table>
                        </div>
                        
                        <div id="msg-resumen-vacio" style="text-align: center; padding: 2rem; color: rgb(156, 163, 175); background: rgb(249, 250, 251); border-radius: 8px; border: 1px dashed rgb(209, 213, 219); margin-top: 1rem; display: none;">
                            <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">inbox</span>
                            Sin asignaciones aún. Accede a "Asignar por Talla" para agregar.
                        </div>
                        
                        <div style="margin-top: 1rem; padding: 1rem; background: #e0f2fe; border-left: 4px solid #0369a1; border-radius: 4px;">
                            <p style="margin: 0; font-size: 0.875rem; color: #075985;">
                                <strong>Total asignado:</strong> <span id="total-asignaciones-resumen">0</span> unidades
                            </p>
                        </div>
                        

                    </div>

                    <!-- SECCIÓN: SELECCIONAR TALLAS POR GÉNERO -->
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
                            
                            <!-- Botón UNISEX -->
                            <button type="button" id="btn-genero-unisex" class="btn-genero" data-selected="false" onclick="abrirModalSeleccionarTallas('unisex')">
                                <div class="btn-genero-content">
                                    <span class="material-symbols-rounded">wc</span>
                                    <span>UNISEX</span>
                                </div>
                                <span id="check-unisex" class="btn-genero-check">✓</span>
                            </button>
                        </div>
                        
                        <!-- Tarjetas de Géneros Seleccionados -->
                        <div id="tarjetas-generos-container" class="generos-container"></div>
                        
                        <!-- Tarjeta de UNISEX (Si está activada, se crea dinámicamente en tarjetas-generos-container) -->
                        
                        <!-- Total general -->
                        <div class="total-box">
                            <span class="material-symbols-rounded">shopping_cart</span>
                            Total: <span id="total-prendas">0</span> unidades
                        </div>
                    </div>
                </div>

                <!-- Modal confirmación Limpiar Todo -->
                <div id="modal-confirmar-limpiar" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modal-confirmar-limpiar-titulo" aria-hidden="true" data-backdrop="false" data-keyboard="true" style="z-index: 1060000 !important;">
                    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                            <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #b91c1c); border: none; padding: 1.25rem 1.5rem;">
                                <h5 class="modal-title" id="modal-confirmar-limpiar-titulo" style="color: white; font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <span class="material-symbols-rounded" style="font-size: 1.3rem;">warning</span>
                                    Confirmar limpieza
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color: white; opacity: 0.8; text-shadow: none;">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body" style="padding: 1.5rem; text-align: center;">
                                <div style="margin-bottom: 1rem;">
                                    <span class="material-symbols-rounded" style="font-size: 3rem; color: #dc2626;">delete_forever</span>
                                </div>
                                <p style="font-size: 0.95rem; color: #374151; margin: 0 0 0.5rem 0; font-weight: 600;">¿Eliminar todas las asignaciones?</p>
                                <p style="font-size: 0.825rem; color: #6b7280; margin: 0;">Se borrarán todos los colores y cantidades asignados a las tallas. Esta acción no se puede deshacer.</p>
                            </div>
                            <div class="modal-footer" style="border-top: 1px solid #e5e7eb; padding: 1rem 1.5rem; display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="font-weight: 500; padding: 0.5rem 1.25rem;">Cancelar</button>
                                <button type="button" id="btn-confirmar-limpiar-todo" class="btn btn-danger" style="font-weight: 600; padding: 0.5rem 1.25rem; display: flex; align-items: center; gap: 0.4rem;">
                                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">delete_forever</span>
                                    Sí, limpiar todo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal confirmación Eliminar Asignación Individual -->
                <div id="modal-confirmar-eliminar-asignacion" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modal-confirmar-eliminar-titulo" aria-hidden="true" data-backdrop="false" data-keyboard="true" style="z-index: 1060000 !important;">
                    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                            <div class="modal-header" style="background: linear-gradient(135deg, #ef4444, #dc2626); border: none; padding: 1rem 1.25rem;">
                                <h5 class="modal-title" id="modal-confirmar-eliminar-titulo" style="color: white; font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <span class="material-symbols-rounded" style="font-size: 1.3rem;">warning</span>
                                    Eliminar asignación
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color: white; opacity: 0.8; text-shadow: none;">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body" style="padding: 1.5rem; text-align: center;">
                                <div style="margin-bottom: 1rem;">
                                    <span class="material-symbols-rounded" style="font-size: 3rem; color: #dc2626;">remove_circle</span>
                                </div>
                                <p style="font-size: 0.95rem; color: #374151; margin: 0 0 0.5rem 0; font-weight: 600;">¿Eliminar esta asignación?</p>
                                <p id="modal-eliminar-detalle" style="font-size: 0.85rem; color: #6b7280; margin: 0; background: #f3f4f6; padding: 0.5rem 0.75rem; border-radius: 6px; font-weight: 500;"></p>
                            </div>
                            <div class="modal-footer" style="border-top: 1px solid #e5e7eb; padding: 1rem 1.25rem; display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="font-weight: 500; padding: 0.5rem 1.25rem;">Cancelar</button>
                                <button type="button" id="btn-confirmar-eliminar-asignacion" class="btn btn-danger" style="font-weight: 600; padding: 0.5rem 1.25rem; display: flex; align-items: center; gap: 0.4rem;">
                                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">delete</span>
                                    Sí, eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Variaciones -->
                <div class="form-section" style="border: 2px solid #0066cc; border-radius: 8px; padding: 1.25rem; margin-top: 1.5rem; background: rgba(0, 102, 204, 0.02);">
                    <label class="form-label-primary" style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="material-symbols-rounded" style="color: #0066cc; font-size: 1.5rem;">tune</span>
                        <span style="font-weight: 700; font-size: 1rem; color: #0066cc;">VARIACIONES ESPECÍFICAS</span>
                    </label>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; background: white;">
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
                                <tr style="border-bottom: 1px solid #e5e7eb; background: #fafbfc;">
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
                                                    <!-- Las opciones se cargarán dinámicamente desde /api/asesores/tipos-manga -->
                                                </datalist>
                                            </div>
                                            <div>
                                                <label for="manga-obs" class="sr-only">Observaciones de Manga</label>
                                                <input type="text" id="manga-obs" placeholder="Observaciones..." disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%; text-transform: uppercase;" onkeyup="convertirAMayusculasConCursor(this);">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Bolsillos -->
                                <tr style="border-bottom: 1px solid #e5e7eb; background: white;">
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <label for="aplica-bolsillos" class="sr-only">Aplicar Bolsillos</label>
                                        <input type="checkbox" id="aplica-bolsillos" class="form-checkbox" onchange="manejarCheckVariacion(this)" style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <span style="font-weight: 600; color: #0066cc;">Bolsillos</span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <label for="bolsillos-obs" class="sr-only">Observaciones de Bolsillos</label>
                                        <input type="text" id="bolsillos-obs" placeholder="Observaciones (Ej: 4 bolsillos con cierre, ocultos, etc...)" disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%; text-transform: uppercase;" onkeyup="convertirAMayusculasConCursor(this);">
                                    </td>
                                </tr>
                                
                                <!-- Broche/Botón -->
                                <tr style="border-bottom: 1px solid #e5e7eb; background: #fafbfc;">
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
                                                <input type="text" id="broche-obs" placeholder="Observaciones..." disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%; text-transform: uppercase;" onkeyup="convertirAMayusculasConCursor(this);">
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
            <button type="button" id="btn-guardar-prenda" class="btn btn-primary" onclick="agregarPrendaNueva()">
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
 * Convierte el valor de un input a mayúsculas preservando la posición del cursor
 * Soluciona el problema de que el cursor salte al final al escribir
 * @param {HTMLInputElement|HTMLTextAreaElement} element - El elemento input o textarea
 */
window.convertirAMayusculasConCursor = function(element) {
    const start = element.selectionStart;
    const end = element.selectionEnd;
    element.value = element.value.toUpperCase();
    element.setSelectionRange(start, end);
};

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

// ========== FUNCIONES PARA "UNISEX" (antes "SOLO CANTIDAD") ==========
/**
 * Variable global para almacenar la cantidad cuando se selecciona "UNISEX"
 */
window.cantidadSoloSeleccionada = null;

/**
 * Abre modal estilo sobremedida para ingresar cantidad UNISEX
 */
window.abrirModalUnisex = function() {
    const modal = document.createElement('div');
    modal.id = 'modal-unisex';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1060000;';

    const container = document.createElement('div');
    container.style.cssText = 'background: white; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); overflow: hidden;';

    // Header
    const header = document.createElement('div');
    header.style.cssText = 'background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); color: white; padding: 1.5rem; display: flex; align-items: center; justify-content: space-between;';

    const headerContent = document.createElement('div');
    headerContent.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';
    headerContent.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">wc</span><h2 style="margin: 0; font-size: 1.25rem;">Agregar Unisex</h2>';
    header.appendChild(headerContent);

    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: transparent; color: white; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px;';
    btnCerrar.onclick = () => cerrarModalUnisex();
    header.appendChild(btnCerrar);

    container.appendChild(header);

    // Content
    const content = document.createElement('div');
    content.style.cssText = 'padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem;';

    // Explicación
    const explicacion = document.createElement('p');
    explicacion.style.cssText = 'margin: 0; color: #6b7280; font-size: 0.95rem; line-height: 1.5;';
    explicacion.textContent = 'La opción Unisex permite agregar una cantidad total sin especificar tallas individuales ni género. Ideal para prendas genéricas o a medida.';
    content.appendChild(explicacion);

    // Input de Cantidad
    const cantidadLabel = document.createElement('label');
    cantidadLabel.style.cssText = 'display: flex; flex-direction: column; gap: 0.5rem; font-weight: 600; color: #1f2937;';
    cantidadLabel.innerHTML = '<span>Cantidad Total *</span>';

    const cantidadInput = document.createElement('input');
    cantidadInput.id = 'unisex-cantidad';
    cantidadInput.type = 'number';
    cantidadInput.min = '1';
    cantidadInput.placeholder = 'Ej: 100';
    cantidadInput.style.cssText = 'padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 6px; font-size: 1rem; font-weight: 600;';

    // Enter para confirmar
    cantidadInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            confirmarUnisex();
        }
    });

    cantidadLabel.appendChild(cantidadInput);
    content.appendChild(cantidadLabel);

    container.appendChild(content);

    // Footer
    const footer = document.createElement('div');
    footer.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid #e5e7eb;';

    const btnCancelar = document.createElement('button');
    btnCancelar.type = 'button';
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;';
    btnCancelar.onmouseover = () => btnCancelar.style.background = '#d1d5db';
    btnCancelar.onmouseout = () => btnCancelar.style.background = '#e5e7eb';
    btnCancelar.onclick = () => cerrarModalUnisex();
    footer.appendChild(btnCancelar);

    const btnConfirmar = document.createElement('button');
    btnConfirmar.type = 'button';
    btnConfirmar.textContent = 'Confirmar';
    btnConfirmar.style.cssText = 'background: #7c3aed; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;';
    btnConfirmar.onmouseover = () => btnConfirmar.style.background = '#5b21b6';
    btnConfirmar.onmouseout = () => btnConfirmar.style.background = '#7c3aed';
    btnConfirmar.onclick = () => confirmarUnisex();
    footer.appendChild(btnConfirmar);

    container.appendChild(footer);
    modal.appendChild(container);

    document.body.appendChild(modal);

    // Focus en cantidad
    setTimeout(() => document.getElementById('unisex-cantidad').focus(), 100);
};

/**
 * Confirmar cantidad Unisex desde el modal
 */
window.confirmarUnisex = function() {
    const cantidad = parseInt(document.getElementById('unisex-cantidad').value) || 0;

    if (cantidad <= 0) {
        alert('La cantidad debe ser mayor a 0');
        document.getElementById('unisex-cantidad').focus();
        return;
    }

    // Guardar en variable global
    window.cantidadSoloSeleccionada = cantidad;

    // IMPORTANTE: Guardar también en la estructura relacional para que otros módulos (procesos) lo encuentren
    if (!window.tallasRelacionales) {
        window.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
    }
    window.tallasRelacionales.UNISEX = { 'UNISEX': cantidad };

    // Cerrar modal
    cerrarModalUnisex();

    // Crear tarjeta visual en el contenedor de géneros
    crearTarjetaUnisex(cantidad);

    // Actualizar total
    actualizarTotalPrendas();

    console.log('[UNISEX] Cantidad agregada:', cantidad, '| Guardado en tallasRelacionales:', window.tallasRelacionales.UNISEX);
};

/**
 * Cerrar modal de unisex
 */
window.cerrarModalUnisex = function() {
    const modal = document.getElementById('modal-unisex');
    if (modal) {
        modal.remove();
    }
};

/**
 * Crear tarjeta de unisex en el contenedor de géneros
 */
window.crearTarjetaUnisex = function(cantidad) {
    // Marcar botón
    const btnUnisex = document.getElementById('btn-genero-unisex');
    const checkMark = document.getElementById('check-unisex');

    if (btnUnisex) {
        btnUnisex.dataset.selected = 'true';
        btnUnisex.style.borderColor = '#7c3aed';
        btnUnisex.style.background = '#f5f3ff';
        btnUnisex.style.color = '#5b21b6';
    }

    if (checkMark) {
        checkMark.style.display = 'block';
    }

    // Obtener contenedor
    const container = document.getElementById('tarjetas-generos-container');
    if (!container) return;

    // Eliminar tarjeta anterior si existe
    const tarjetaAnterior = container.querySelector('[data-unisex="true"]');
    if (tarjetaAnterior) {
        tarjetaAnterior.remove();
    }

    // Crear tarjeta compacta
    const tarjeta = document.createElement('div');
    tarjeta.dataset.unisex = 'true';
    tarjeta.style.cssText = 'background: white; border: 2px solid #7c3aed; border-radius: 8px; padding: 1rem; margin-top: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);';

    // Header compacto
    const headerDiv = document.createElement('div');
    headerDiv.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; justify-content: space-between;';

    const headerLeft = document.createElement('div');
    headerLeft.style.cssText = 'display: flex; align-items: center; gap: 0.5rem;';
    headerLeft.innerHTML = `
        <span class="material-symbols-rounded" style="font-size: 1.25rem; color: #7c3aed;">wc</span>
        <div>
            <h4 style="margin: 0; color: #1f2937; font-size: 0.9rem; font-weight: 600;">UNISEX</h4>
            <p style="margin: 0; color: #6b7280; font-size: 0.75rem;">Cantidad total: ${cantidad} unidades</p>
        </div>
    `;
    headerDiv.appendChild(headerLeft);

    const btnEliminar = document.createElement('button');
    btnEliminar.type = 'button';
    btnEliminar.title = 'Eliminar unisex';
    btnEliminar.style.cssText = 'background: transparent; border: none; color: #6b7280; cursor: pointer; padding: 0.35rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border-radius: 4px; font-size: 1rem;';
    btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1rem;">delete</span>';
    btnEliminar.onmouseover = () => {
        btnEliminar.style.color = '#ef4444';
        btnEliminar.style.background = '#fee2e2';
    };
    btnEliminar.onmouseout = () => {
        btnEliminar.style.color = '#6b7280';
        btnEliminar.style.background = 'transparent';
    };
    btnEliminar.onclick = () => {
        eliminarUnisex();
    };
    headerDiv.appendChild(btnEliminar);

    tarjeta.appendChild(headerDiv);
    container.appendChild(tarjeta);
};

/**
 * Elimina la opción unisex
 */
window.eliminarUnisex = function() {
    window.cantidadSoloSeleccionada = null;

    // IMPORTANTE: Limpiar también de la estructura relacional
    if (window.tallasRelacionales) {
        window.tallasRelacionales.UNISEX = {};
        if (window.tallasRelacionales.GENERICO) {
            window.tallasRelacionales.GENERICO = {};
        }
    }

    // Eliminar tarjeta
    const container = document.getElementById('tarjetas-generos-container');
    if (container) {
        const tarjeta = container.querySelector('[data-unisex="true"]');
        if (tarjeta) tarjeta.remove();
    }

    // Resetear botón
    const btnUnisex = document.getElementById('btn-genero-unisex');
    const checkUnisex = document.getElementById('check-unisex');

    if (btnUnisex) {
        btnUnisex.style.background = 'white';
        btnUnisex.style.borderColor = '#d1d5db';
        btnUnisex.style.color = '#374151';
        btnUnisex.setAttribute('data-selected', 'false');
    }
    if (checkUnisex) checkUnisex.style.display = 'none';

    actualizarTotalPrendas();

    console.log('[UNISEX] Eliminado de cantidadSoloSeleccionada y tallasRelacionales');
};

// Alias de compatibilidad para funciones antiguas
window.abrirOpcionalSoloCantidad = window.abrirModalUnisex;
window.eliminarSoloCantidad = window.eliminarUnisex;
window.agregarSoloCantidad = window.confirmarUnisex;
window.cancelarSoloCantidad = window.cerrarModalUnisex;

/**
 * Actualiza el total de prendas (incluyendo la cantidad UNISEX)
 */
window.actualizarTotalPrendasOriginal = window.actualizarTotalPrendas || function() {};

window.actualizarTotalPrendas = function() {
    let totalPrendas = 0;
    
    // Sumar cantidades de tallas por género
    if (window.tallasRelacionales) {
        Object.values(window.tallasRelacionales).forEach(genero => {
            Object.values(genero).forEach(cantidad => {
                totalPrendas += parseInt(cantidad) || 0;
            });
        });
    }
    
    // Sumar cantidad UNISEX
    if (window.cantidadSoloSeleccionada) {
        totalPrendas += window.cantidadSoloSeleccionada;
    }
    
    // Actualizar display
    const totalSpan = document.getElementById('total-prendas');
    if (totalSpan) {
        totalSpan.textContent = totalPrendas;
    }
    
    console.log('[Total Prendas] Total actualizado:', totalPrendas);
};
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

<!-- ─── Modal Prenda: colores-por-talla, drag-drop, FSM, loaders ─── -->
<!-- En producción, js_asset() carga automáticamente .min.js si existe -->
@php $v = config('app.asset_version'); @endphp

<!-- NUEVA ARQUITECTURA: Máquina de Estados y Event Bus -->
<script defer src="{{ js_asset('js/prenda-color-wizard/WizardStateMachine.js') }}?v={{ $v }}"></script>
        <script defer src="{{ js_asset('js/prenda-color-wizard/WizardEventBus.js') }}?v={{ $v }}"></script>
        <script defer src="{{ js_asset('js/prenda-color-wizard/WizardLifecycleManager.js') }}?v={{ $v }}"></script>
        <script defer src="{{ js_asset('js/prenda-color-wizard/WizardBootstrap.js') }}?v={{ $v }}"></script>

<!-- MÓDULOS EXISTENTES -->
<script defer src="{{ js_asset('js/componentes/colores-por-talla/StateManager.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/colores-por-talla/DOMUtils.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/colores-por-talla/AsignacionManager.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/colores-por-talla/WizardManager.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/colores-por-talla/UIRenderer.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/colores-por-talla/ColoresPorTalla.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/colores-por-talla/compatibilidad.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/colores-por-talla/diagnostico.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/prendas-module/services/UIHelperService.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/prendas-module/services/ClipboardService.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/prendas-module/services/ContextMenuService.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/prendas-module/services/DragDropEventHandler.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/prendas-module/handlers/BaseDragDropHandler.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/prendas-module/handlers/PrendaDragDropHandler.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/prendas-module/handlers/TelaDragDropHandler.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/prendas-module/handlers/ProcesoDragDropHandler.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/prendas-module/drag-drop-manager.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/core/modal-mini-fsm.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-basicos.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-imagenes.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-telas.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-variaciones.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-tallas.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-colores.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/loaders/prenda-editor-procesos.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/modalHandlers/prenda-modal-manager.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/services/prenda-editor-service.js') }}?v={{ $v }}"></script>

<!-- NOTA: Estos módulos tienen protección interna contra redeclaración (typeof guard),
     por lo que es seguro que se carguen tanto aquí como desde prenda-editor-loader-modular.js -->

<!-- MODAL SIMPLE: Agregar Tela -->
<div id="modal-agregar-tela-simple" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modalTelaSimpleLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false" style="z-index: 1060000;">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                <h5 id="modal-tela-simple-titulo" style="margin: 0; color: white; font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">add_circle</span>Agregar Tela
                </h5>
                <button type="button" onclick="cerrarModalTelaSimple()" style="background: none; border: none; color: rgba(255,255,255,0.8); cursor: pointer; padding: 0.25rem; line-height: 1;">
                    <span class="material-symbols-rounded" style="font-size: 1.3rem;">close</span>
                </button>
            </div>
            <!-- Body -->
            <div style="padding: 1.25rem;">
                <!-- Tela y Color -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 600; color: #374151; display: block; margin-bottom: 0.3rem;">TELA *</label>
                        <input type="text" id="simple-tela-input" placeholder="Ej: DRILL, OXFORD..." list="opciones-telas" class="form-control" style="text-transform: uppercase; font-size: 0.9rem;" onkeyup="convertirAMayusculasConCursor(this);">
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 600; color: #374151; display: block; margin-bottom: 0.3rem;">COLOR</label>
                        <input type="text" id="simple-color-input" placeholder="Ej: AZUL, ROJO..." list="opciones-colores" class="form-control" style="text-transform: uppercase; font-size: 0.9rem;" onkeyup="convertirAMayusculasConCursor(this);">
                    </div>
                </div>
                <!-- Referencia -->
                <div style="margin-bottom: 0.75rem;">
                    <label style="font-size: 0.75rem; font-weight: 600; color: #374151; display: block; margin-bottom: 0.3rem;">REFERENCIA</label>
                    <input type="text" id="simple-referencia-input" placeholder="Ref..." class="form-control" style="text-transform: uppercase; font-size: 0.9rem;" onkeyup="convertirAMayusculasConCursor(this);">
                </div>
                <!-- Imagen - Drop Zone -->
                <div style="margin-bottom: 0.75rem;">
                    <label style="font-size: 0.75rem; font-weight: 600; color: #374151; display: block; margin-bottom: 0.3rem;">IMAGEN (opcional)</label>
                    <input type="file" id="simple-tela-img-input" accept="image/*" style="display: none;">
                    <!-- Drop zone (sin imagen) -->
                    <div id="simple-tela-dropzone" tabindex="0" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.4rem; padding: 1.25rem; border: 2px dashed #d1d5db; border-radius: 8px; background: #fafafa; color: #6b7280; cursor: pointer; transition: all 0.15s; outline: none; text-align: center;">
                        <span class="material-symbols-rounded" style="font-size: 2rem; color: #9ca3af;">add_photo_alternate</span>
                        <span style="font-size: 0.8rem;">Click, arrastra o <strong>Ctrl+V</strong> para agregar imagen</span>
                    </div>
                    <!-- Preview (con imagen) -->
                    <div id="simple-tela-preview" style="display: none; position: relative; border-radius: 8px; overflow: hidden; border: 1px solid #d1d5db;">
                        <img id="simple-tela-preview-img" src="" alt="Preview" style="width: 100%; max-height: 180px; object-fit: cover; display: block;">
                        <button type="button" id="simple-tela-preview-del" style="position: absolute; top: 6px; right: 6px; width: 28px; height: 28px; border-radius: 50%; border: none; background: #ef4444; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">
                            <span class="material-symbols-rounded" style="font-size: 1rem;">close</span>
                        </button>
                    </div>
                </div>
                <!-- Observaciones -->
         
            </div>
            <!-- Footer -->
            <div style="padding: 0.75rem 1.25rem; background: #f9fafb; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" onclick="cerrarModalTelaSimple()" class="btn btn-secondary" style="padding: 0.5rem 1.25rem; font-size: 0.85rem;">Cancelar</button>
                <button type="button" id="btn-modal-tela-simple-accion" onclick="agregarTelaSimple()" class="btn btn-primary" style="padding: 0.5rem 1.5rem; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.35rem;">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">add</span>Agregar Tela
                </button>
            </div>
        </div>
    </div>
</div>

<!-- INCLUIR MODAL WIZARD DEDICADO -->
@include('shared.pedidos.modals.modal-asignar-colores-por-talla')

<!-- Scripts para manejar el modal Bootstrap 4 -->
<script defer src="{{ js_asset('js/componentes/colores-por-talla/modal-manager.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/colores-por-talla/bootstrap-modal-init.js') }}?v={{ $v }}"></script>

<!-- Funciones para abrir y cerrar modal de colores por talla -->
<script>
    /**
     * Abre el modal de "Asignar Colores por Talla"
     * Flujo unificado: siempre empieza en Paso 0 (tela)
     */
    function abrirModalAsignarColores() {
        // Verificar que jQuery está disponible
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            alert('Error: jQuery no está cargado. Por favor recarga la página.');
            return;
        }
        
        // Verificar que el modal existe
        const $modal = jQuery('#modal-asignar-colores-por-talla');
        if ($modal.length === 0) {
            alert('Error: El modal no existe. Por favor recarga la página.');
            return;
        }
        
        // Verificar que Bootstrap modal está disponible
        if (typeof $modal.modal !== 'function') {
            alert('Error: Bootstrap Modal no está cargado. Por favor recarga la página.');
            return;
        }
        
        try {
            // Resetear wizard state
            if (window.StateManager) {
                window.StateManager.resetWizardState();
            }
            
            // Limpiar input de tela del paso 0
            const wizardTelaInput = document.getElementById('wizard-tela-input');
            if (wizardTelaInput) {
                wizardTelaInput.value = '';
                wizardTelaInput.style.borderColor = '#d1d5db';
                wizardTelaInput.style.boxShadow = 'none';
            }
            
            // Resetear botones de género visualmente
            document.querySelectorAll('.wizard-genero-btn').forEach(btn => {
                btn.style.background = 'white';
                btn.style.borderColor = '#d1d5db';
                btn.style.color = '#374151';
                btn.style.fontWeight = '500';
            });
            
            // Resetear checkboxes de tallas
            document.querySelectorAll('.wizard-talla-checkbox').forEach(cb => {
                cb.checked = false;
                const label = cb.closest('label');
                if (label) {
                    label.style.background = 'white';
                    label.style.borderColor = '#d1d5db';
                    label.style.fontWeight = '500';
                    label.style.color = '#374151';
                }
            });
            
            // Siempre empezar en Paso 0 (tela)
            if (typeof WizardManager !== 'undefined' && typeof WizardManager.irPaso === 'function') {
                WizardManager.irPaso(0);
            }
            
            // Abrir el modal
            $modal.modal('show');
        } catch (error) {
            alert('Error al abrir el modal: ' + error.message);
        }
    }
    
    /**
     * Valida el input de tela en el wizard paso 0
     */
    function wizardValidarTelaInput(input) {
        const valor = input.value.trim();
        if (valor.length > 0) {
            input.style.borderColor = '#10b981';
            input.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
            // Establecer tela en StateManager
            if (window.StateManager) {
                window.StateManager.setTelaSeleccionada(valor.toUpperCase());
            }
            // Habilitar botón siguiente
            const btnSiguiente = document.getElementById('wzd-btn-siguiente');
            if (btnSiguiente) {
                btnSiguiente.style.display = 'flex';
                btnSiguiente.disabled = false;
            }
        } else {
            input.style.borderColor = '#d1d5db';
            input.style.boxShadow = 'none';
        }
    }

    // ============================================
    // MODAL SIMPLE TELA - Abrir, cerrar y agregar
    // ============================================

    /** Imagen temporal para modal simple */
    window._imagenTelaSimple = null;
    window._simpleDropInitialized = false;
    /** Índice de tela que se está editando (null = agregar nueva) */
    window._editandoTelaIdx = null;

    /**
     * Abre el modal simple de agregar/editar tela
     * @param {number|undefined} editIdx - Si se pasa, entra en modo edición con ese índice
     */
    window.abrirModalTelaSimple = abrirModalTelaSimple;
    function abrirModalTelaSimple(editIdx) {
        const $modal = jQuery('#modal-agregar-tela-simple');
        if (!$modal.length) { alert('Modal no encontrado'); return; }

        const titulo = document.getElementById('modal-tela-simple-titulo');
        const btnAccion = document.getElementById('btn-modal-tela-simple-accion');
        const isEdit = (editIdx !== undefined && editIdx !== null && window.telasCreacion && window.telasCreacion[editIdx]);
        window._editandoTelaIdx = isEdit ? editIdx : null;

        // Actualizar título y botón según modo
        if (titulo) {
            titulo.innerHTML = isEdit
                ? '<span class="material-symbols-rounded" style="font-size: 1.2rem;">edit</span>Editar Tela'
                : '<span class="material-symbols-rounded" style="font-size: 1.2rem;">add_circle</span>Agregar Tela';
        }
        if (btnAccion) {
            btnAccion.innerHTML = isEdit
                ? '<span class="material-symbols-rounded" style="font-size: 1.1rem;">save</span>Guardar Cambios'
                : '<span class="material-symbols-rounded" style="font-size: 1.1rem;">add</span>Agregar Tela';
        }

        // Limpiar campos
        ['simple-tela-input','simple-color-input','simple-referencia-input'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const imgInput = document.getElementById('simple-tela-img-input');
        if (imgInput) imgInput.value = '';
        window._imagenTelaSimple = null;

        // Resetear dropzone visual
        const dz = document.getElementById('simple-tela-dropzone');
        const pv = document.getElementById('simple-tela-preview');
        const pvImg = document.getElementById('simple-tela-preview-img');
        if (dz) dz.style.display = 'flex';
        if (pv) pv.style.display = 'none';

        // Si es modo edición, pre-llenar campos con datos existentes
        if (isEdit) {
            const t = window.telasCreacion[editIdx];
            const telaInput = document.getElementById('simple-tela-input');
            const colorInput = document.getElementById('simple-color-input');
            const refInput = document.getElementById('simple-referencia-input');
            // const obsInput = document.getElementById('simple-observaciones-input');

            //  FIX: Buscar 'nombre_tela' primero (desde cotización), luego 'tela' (desde creación nueva)
            if (telaInput) telaInput.value = (t.nombre_tela || t.tela || '').toUpperCase();
            if (colorInput) colorInput.value = (t.color || t.color_nombre || '').toUpperCase();
            if (refInput) refInput.value = (t.referencia || '').toUpperCase();
            // if (obsInput) obsInput.value = t.observaciones || '';

            // Pre-cargar imagen existente
            if (t.imagenes && t.imagenes.length > 0) {
                const img = t.imagenes[0];
                window._imagenTelaSimple = img;
                if (img instanceof File || img instanceof Blob) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        if (pvImg) pvImg.src = e.target.result;
                        if (dz) dz.style.display = 'none';
                        if (pv) pv.style.display = 'block';
                    };
                    reader.readAsDataURL(img);
                } else if (typeof img === 'string') {
                    if (pvImg) pvImg.src = img;
                    if (dz) dz.style.display = 'none';
                    if (pv) pv.style.display = 'block';
                } else if (img && typeof img === 'object') {
                    const src = img.ruta || img.ruta_original || img.ruta_webp || img.url || img.previewUrl || '';
                    if (src) {
                        if (pvImg) pvImg.src = src;
                        if (dz) dz.style.display = 'none';
                        if (pv) pv.style.display = 'block';
                    }
                }
            }
        }

        // Inicializar drag-drop events (una sola vez)
        if (!window._simpleDropInitialized) {
            _initSimpleDropZone();
            window._simpleDropInitialized = true;
        }

        $modal.modal('show');
    }

    /**
     * Cierra el modal simple
     */
    function cerrarModalTelaSimple() {
        jQuery('#modal-agregar-tela-simple').modal('hide');
    }

    /**
     * Inicializa eventos de la drop zone de imagen (drag, click, paste, delete)
     */
    function _initSimpleDropZone() {
        const dropzone = document.getElementById('simple-tela-dropzone');
        const preview = document.getElementById('simple-tela-preview');
        const previewImg = document.getElementById('simple-tela-preview-img');
        const btnDel = document.getElementById('simple-tela-preview-del');
        const fileInput = document.getElementById('simple-tela-img-input');
        if (!dropzone || !preview || !previewImg || !btnDel || !fileInput) return;

        // Cargar imagen y mostrar preview
        const cargar = (file) => {
            if (!file || !file.type.startsWith('image/')) return;
            window._imagenTelaSimple = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                dropzone.style.display = 'none';
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        };

        // Exponer al DragDropManager vía registro de sub-modales (arquitectura limpia)
        if (window.DragDropManager && typeof window.DragDropManager.registrarSubModal === 'function') {
            window.DragDropManager.registrarSubModal('modal-agregar-tela-simple', cargar);
        }

        // Eliminar imagen
        const eliminar = () => {
            window._imagenTelaSimple = null;
            previewImg.src = '';
            fileInput.value = '';
            preview.style.display = 'none';
            dropzone.style.display = 'flex';
            dropzone.style.borderColor = '#d1d5db';
            dropzone.style.background = '#fafafa';
        };

        // Click abre file picker
        dropzone.addEventListener('click', () => fileInput.click());
        previewImg.addEventListener('click', () => fileInput.click());
        btnDel.addEventListener('click', (e) => { e.stopPropagation(); eliminar(); });
        fileInput.addEventListener('change', () => { if (fileInput.files.length) cargar(fileInput.files[0]); });

        // Drag & Drop
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault(); e.stopPropagation();
            dropzone.style.borderColor = '#3b82f6';
            dropzone.style.background = '#eff6ff';
            dropzone.style.borderStyle = 'solid';
        });
        dropzone.addEventListener('dragleave', (e) => {
            e.preventDefault(); e.stopPropagation();
            dropzone.style.borderColor = '#d1d5db';
            dropzone.style.background = '#fafafa';
            dropzone.style.borderStyle = 'dashed';
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault(); e.stopPropagation();
            dropzone.style.borderColor = '#d1d5db';
            dropzone.style.background = '#fafafa';
            dropzone.style.borderStyle = 'dashed';
            const files = e.dataTransfer.files;
            if (files.length && files[0].type.startsWith('image/')) cargar(files[0]);
        });

        // Ctrl+V (paste)
        dropzone.addEventListener('paste', (e) => {
            e.preventDefault();
            const items = e.clipboardData?.items;
            if (!items) return;
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.startsWith('image/')) {
                    const file = items[i].getAsFile();
                    if (file) cargar(file);
                    break;
                }
            }
        });

        // Hover
        dropzone.addEventListener('mouseover', () => {
            if (preview.style.display === 'none') {
                dropzone.style.borderColor = '#3b82f6'; dropzone.style.color = '#3b82f6'; dropzone.style.background = '#eff6ff';
            }
        });
        dropzone.addEventListener('mouseout', () => {
            if (preview.style.display === 'none') {
                dropzone.style.borderColor = '#d1d5db'; dropzone.style.color = '#6b7280'; dropzone.style.background = '#fafafa';
            }
        });

        // Focus para Ctrl+V
        dropzone.addEventListener('focus', () => { dropzone.style.borderColor = '#3b82f6'; dropzone.style.boxShadow = '0 0 0 3px rgba(59,130,246,0.15)'; });
        dropzone.addEventListener('blur', () => { if (preview.style.display === 'none') dropzone.style.borderColor = '#d1d5db'; dropzone.style.boxShadow = 'none'; });
    }

    /**
     * Agrega una tela desde el modal simple
     */
    function agregarTelaSimple() {
        const telaInput = document.getElementById('simple-tela-input');
        const colorInput = document.getElementById('simple-color-input');
        const refInput = document.getElementById('simple-referencia-input');
        // const obsInput = document.getElementById('simple-observaciones-input');

        const tela = (telaInput?.value || '').trim().toUpperCase();
        const color = (colorInput?.value || '').trim().toUpperCase();
        const referencia = (refInput?.value || '').trim().toUpperCase();
        const observaciones = ''; // (obsInput?.value || '').trim();

        if (!tela) {
            telaInput.style.borderColor = '#ef4444';
            telaInput.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
            telaInput.focus();
            setTimeout(() => { telaInput.style.borderColor = ''; telaInput.style.boxShadow = ''; }, 2000);
            return;
        }

        if (!window.telasCreacion) window.telasCreacion = [];

        const editIdx = window._editandoTelaIdx;
        const isEdit = (editIdx !== null && editIdx !== undefined && window.telasCreacion[editIdx]);

        // Verificar duplicado (excluir el propio registro en modo edición)
        const existe = window.telasCreacion.some((t, i) => {
            if (isEdit && i === editIdx) return false;
            return (t.tela || '').toUpperCase() === tela && (t.color || '').toUpperCase() === color;
        });
        if (existe) {
            telaInput.style.borderColor = '#f59e0b';
            telaInput.style.boxShadow = '0 0 0 3px rgba(245, 158, 11, 0.15)';
            setTimeout(() => { telaInput.style.borderColor = ''; telaInput.style.boxShadow = ''; }, 2000);
            alert('Esta combinación tela + color ya está agregada.');
            return;
        }

        if (isEdit) {
            // --- Modo Edición: actualizar registro existente ---
            //  FIX: Guardar en AMBAS propiedades (tela y nombre_tela) para compatibilidad
            window.telasCreacion[editIdx].tela = tela;
            window.telasCreacion[editIdx].nombre_tela = tela;
            window.telasCreacion[editIdx].color = color;
            window.telasCreacion[editIdx].color_nombre = color;
            window.telasCreacion[editIdx].referencia = referencia;
            window.telasCreacion[editIdx].observaciones = observaciones;
            const imagenesExistentes = Array.isArray(window.telasCreacion[editIdx].imagenes)
                ? window.telasCreacion[editIdx].imagenes
                : [];
            window.telasCreacion[editIdx].imagenes = window._imagenTelaSimple
                ? [window._imagenTelaSimple]
                : imagenesExistentes;
            console.log('[agregarTelaSimple] Tela editada idx:', editIdx, window.telasCreacion[editIdx]);
        } else {
            // --- Modo Agregar: crear nuevo registro ---
            const nuevaTela = {
                tela: tela,
                nombre_tela: tela,
                color: color,
                color_nombre: color,
                referencia: referencia,
                observaciones: observaciones,
                imagenes: window._imagenTelaSimple ? [window._imagenTelaSimple] : [],
                fechaCreacion: new Date().toISOString()
            };
            window.telasCreacion.push(nuevaTela);
            console.log('[agregarTelaSimple] Tela agregada:', nuevaTela, 'Total:', window.telasCreacion.length);
        }

        window._editandoTelaIdx = null;

        // Cerrar modal y actualizar tabla
        cerrarModalTelaSimple();
        renderizarTelasChips();
    }

    /**
     * Renderiza la tabla de telas agregadas debajo del formulario
     */
    /**
     * Renderiza telas en la tabla unificada de resumen.
     * Delega a ColoresPorTalla.actualizarTablaResumen() que maneja AMBOS
     * tipos de datos: asignaciones wizard + telas simples.
     */
    function renderizarTelasChips() {
        if (window.ColoresPorTalla && typeof window.ColoresPorTalla.actualizarTablaResumen === 'function') {
            window.ColoresPorTalla.actualizarTablaResumen();
        } else {
            console.warn('[renderizarTelasChips] ColoresPorTalla no disponible, reintentando...');
            setTimeout(renderizarTelasChips, 300);
        }
    }

    // Exponer para uso desde wizard
    window.renderizarTelasChips = renderizarTelasChips;
    
    /**
     * Cierra el modal de "Asignar Colores por Talla"
     */
    function cerrarModalAsignarColores() {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            return;
        }
        
        const $modal = jQuery('#modal-asignar-colores-por-talla');
        if ($modal.length === 0) {
            return;
        }
        
        try {
            $modal.modal('hide');
        } catch (error) {
        }
    }

    /**
     * Inicialización cuando el DOM esté listo
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Esperar a que jQuery esté disponible
        let intentos = 0;
        const maxIntentos = 50;
        
        const verificarJQuery = setInterval(() => {
            intentos++;
            
            if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
                clearInterval(verificarJQuery);
                
                // Configurar botones de la tabla de resumen
                const btnAsignarColores = document.getElementById('btn-asignar-colores-prenda');
                const btnLimpiarAsignaciones = document.getElementById('btn-limpiar-asignaciones');
                
                if (btnAsignarColores) {
                    btnAsignarColores.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // Obtener tela actual
                        const telaActual = document.querySelector('#tela-seleccionada')?.value ||
                                         (window.telasCreacion && window.telasCreacion[0]?.nombre_tela) ||
                                         (window.telasCreacion && window.telasCreacion[0]?.tela) ||
                                         '--';
                        
                        // Establecer la tela en StateManager si existe
                        if (window.StateManager && telaActual !== '--') {
                            window.StateManager.setTelaSeleccionada(telaActual);
                        }
                        
                        // Llamar al toggle para abrir el wizard
                        if (window.ColoresPorTalla && typeof window.ColoresPorTalla.toggleVistaAsignacion === 'function') {
                            window.ColoresPorTalla.toggleVistaAsignacion();
                        }
                    });
                }
                
                if (btnLimpiarAsignaciones) {
                    btnLimpiarAsignaciones.addEventListener('click', function(e) {
                        e.preventDefault();
                        //  NUEVO: Remover aria-hidden del contenedor padre para que el modal sea accesible
                        const asesorWrapper = document.querySelector('.asesores-wrapper');
                        if (asesorWrapper) {
                            asesorWrapper.removeAttribute('aria-hidden');
                        }
                        
                        //  NUEVO: Remover cualquier overlay existente antes de abrir el modal
                        const overlayExistente = document.getElementById('overlay-confirmar-limpiar');
                        if (overlayExistente) {
                            overlayExistente.remove();
                        }
                        
                        // Abrir modal de confirmación
                        jQuery('#modal-confirmar-limpiar').modal('show');
                    });
                    
                    // Listener para el botón de confirmar dentro del modal
                    const btnConfirmarLimpiar = document.getElementById('btn-confirmar-limpiar-todo');
                    if (btnConfirmarLimpiar) {
                        btnConfirmarLimpiar.addEventListener('click', function() {
                            // Limpiar asignaciones
                            if (window.StateManager && typeof window.StateManager.limpiarAsignaciones === 'function') {
                                window.StateManager.limpiarAsignaciones();
                            }
                            
                            // Actualizar tabla
                            if (window.ColoresPorTalla && typeof window.ColoresPorTalla.actualizarTablaResumen === 'function') {
                                window.ColoresPorTalla.actualizarTablaResumen();
                            }
                            
                            // Actualizar otras secciones
                            if (typeof crearTarjetaGenero === 'function') crearTarjetaGenero();
                            if (typeof actualizarTotalPrendas === 'function') actualizarTotalPrendas();
                            
                            // Cerrar modal de confirmación y remover overlay
                            const modalLimpiar = document.getElementById('modal-confirmar-limpiar');
                            jQuery(modalLimpiar).modal('hide');
                            const ov = document.getElementById('overlay-confirmar-limpiar');
                            if (ov) ov.remove();
                            
                            //  NUEVO: Remover aria-hidden del modal cuando se cierre
                            if (modalLimpiar) {
                                modalLimpiar.removeAttribute('aria-hidden');
                            }
                        });
                    }
                    
                    //  NUEVO: Listener para remover aria-hidden cuando se cierre el modal
                    jQuery('#modal-confirmar-limpiar').on('hidden.bs.modal', function() {
                        const asesorWrapper = document.querySelector('.asesores-wrapper');
                        if (asesorWrapper) {
                            asesorWrapper.setAttribute('aria-hidden', 'true');
                        }
                    });
                }
                
                // Agregar listener delegado para botones de eliminar asignación (creados dinámicamente)
                const tablaResumenBody = document.getElementById('tabla-resumen-asignaciones-cuerpo');
                if (tablaResumenBody) {
                    // Variables para almacenar datos de eliminación pendiente
                    let eliminacionPendiente = { clave: null, colorNombre: null };

                    tablaResumenBody.addEventListener('click', function(e) {
                        const btnEliminar = e.target.closest('.btn-eliminar-asignacion');
                        if (btnEliminar) {
                            e.preventDefault();
                            const clave = btnEliminar.getAttribute('data-clave');
                            const colorNombre = btnEliminar.getAttribute('data-color');
                            
                            // Guardar datos para cuando confirmen
                            eliminacionPendiente = { clave, colorNombre };
                            
                            // Mostrar detalle en el modal
                            const detalleEl = document.getElementById('modal-eliminar-detalle');
                            if (detalleEl) {
                                detalleEl.textContent = `Color: ${colorNombre} — Clave: ${clave}`;
                            }
                            
                            //  NUEVO: Remover aria-hidden del contenedor padre para que el modal sea accesible
                            const asesorWrapper = document.querySelector('.asesores-wrapper');
                            if (asesorWrapper) {
                                asesorWrapper.removeAttribute('aria-hidden');
                            }
                            
                            //  NUEVO: Remover cualquier overlay existente antes de abrir el modal
                            const overlayExistente = document.getElementById('overlay-confirmar-eliminar');
                            if (overlayExistente) {
                                overlayExistente.remove();
                            }
                            
                            // Abrir modal de confirmación
                            jQuery('#modal-confirmar-eliminar-asignacion').modal('show');
                        }
                    });

                    // Listener para confirmación de eliminar asignación individual
                    const btnConfirmarEliminar = document.getElementById('btn-confirmar-eliminar-asignacion');
                    if (btnConfirmarEliminar) {
                        btnConfirmarEliminar.addEventListener('click', function() {
                            const { clave, colorNombre } = eliminacionPendiente;
                            if (!clave) return;

                            // Eliminar del StateManager
                            if (window.StateManager) {
                                const asignaciones = window.StateManager.getAsignaciones();
                                if (asignaciones[clave]) {
                                    // Guardar datos ANTES de eliminar
                                    const telaGuardada = asignaciones[clave]?.tela || '';
                                    const claveParts = clave.split('-');
                                    
                                    delete asignaciones[clave];
                                    window.StateManager.setAsignaciones(asignaciones);
                                    
                                    // Intentar guardar cambios en servidor (opcional, no bloquea)
                                    try {
                                        if (window.AsignacionManager && typeof window.AsignacionManager.guardarAsignacionesMultiples === 'function' && telaGuardada && claveParts.length >= 3) {
                                            const genero = claveParts[0];
                                            const tipo = claveParts[1];
                                            const talla = claveParts.slice(2).join('-');
                                            
                                            const resultado = window.AsignacionManager.guardarAsignacionesMultiples(
                                                genero,
                                                [talla],
                                                tipo,
                                                telaGuardada,
                                                {}
                                            );
                                            if (resultado && typeof resultado.catch === 'function') {
                                                resultado.catch(err => console.error('[TablaResumen] Error al guardar:', err));
                                            }
                                        }
                                    } catch(e) {
                                        console.warn('[TablaResumen] No se pudo sincronizar eliminación con servidor:', e);
                                    }
                                    
                                    //  FIX: Si no quedan más asignaciones, limpiar estado completo
                                    const asignacionesRestantes = Object.keys(asignaciones);
                                    if (asignacionesRestantes.length === 0) {
                                        console.log('[TablaResumen]  No quedan asignaciones, limpiando estado...');
                                        
                                        // Limpiar tallasRelacionales para evitar que crearTarjetaGenero recree tarjetas
                                        if (window.tallasRelacionales) {
                                            Object.keys(window.tallasRelacionales).forEach(g => {
                                                window.tallasRelacionales[g] = {};
                                            });
                                        }
                                        
                                        // Limpiar telas huérfanas del wizard (sin datos propios)
                                        if (window.telasCreacion) {
                                            window.telasCreacion = window.telasCreacion.filter(t => {
                                                const tieneColor = t.color && t.color.trim() !== '';
                                                const tieneRef = t.referencia && t.referencia.trim() !== '';
                                                const tieneImgs = t.imagenes && t.imagenes.length > 0;
                                                return tieneColor || tieneRef || tieneImgs;
                                            });
                                        }
                                        
                                        // Limpiar tarjetas de genéro del DOM
                                        const containerTarjetas = document.getElementById('tarjetas-generos-container');
                                        if (containerTarjetas) containerTarjetas.innerHTML = '';
                                        
                                        // Desmarcar botones de género
                                        document.querySelectorAll('[id^="btn-genero-"]').forEach(btn => {
                                            btn.dataset.selected = 'false';
                                            btn.style.borderColor = '';
                                            btn.style.background = '';
                                        });
                                        document.querySelectorAll('[id^="check-"]').forEach(chk => {
                                            chk.style.display = 'none';
                                        });
                                    }
                                    
                                    // Actualizar tabla y otras secciones
                                    if (window.ColoresPorTalla && typeof window.ColoresPorTalla.actualizarTablaResumen === 'function') {
                                        window.ColoresPorTalla.actualizarTablaResumen();
                                    }
                                    // Solo recrear tarjeta si aún quedan asignaciones
                                    if (asignacionesRestantes.length > 0 && typeof crearTarjetaGenero === 'function') {
                                        crearTarjetaGenero();
                                    }
                                    if (typeof actualizarTotalPrendas === 'function') actualizarTotalPrendas();
                                    
                                    console.log(' Asignación eliminada');
                                }
                            }
                            
                            // Cerrar modal y overlay
                            jQuery('#modal-confirmar-eliminar-asignacion').modal('hide');
                            const ov = document.getElementById('overlay-confirmar-eliminar');
                            if (ov) ov.remove();
                            eliminacionPendiente = { clave: null, colorNombre: null };
                        });
                    }
                }
                
                // Actualizar tabla al cargar el modal
                if (window.ColoresPorTalla && typeof window.ColoresPorTalla.actualizarTablaResumen === 'function') {
                    window.ColoresPorTalla.actualizarTablaResumen();
                }
                
                return;
            }
            
            if (intentos >= maxIntentos) {
                clearInterval(verificarJQuery);
                console.warn(' jQuery o Bootstrap Modal no disponibles después de 5 segundos');
            }
        }, 100);
    });
</script>

<style>
    /* Modales de confirmación encima de todo */
    #modal-confirmar-limpiar,
    #modal-confirmar-eliminar-asignacion {
        z-index: 1060000 !important;
    }
    /* Wizard modal y su backdrop encima del modal de prenda */
    #modal-asignar-colores-por-talla {
        z-index: 1060000 !important;
    }
    #modal-asignar-colores-por-talla ~ .modal-backdrop,
    .modal-backdrop {
        z-index: 1055000 !important;
    }

    @keyframes fadeInOverlay {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes fadeOutOverlay {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    @keyframes scaleInModal {
        from { transform: scale(0.85); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>

<script>
    // Ocultar overlays cuando los modales se cierren por cualquier medio (X, Cancelar, Escape, etc.)
    jQuery(document).on('hidden.bs.modal', '#modal-confirmar-limpiar', function() {
        const ov = document.getElementById('overlay-confirmar-limpiar');
        if (ov) ov.style.display = 'none';
    });
    jQuery(document).on('hidden.bs.modal', '#modal-confirmar-eliminar-asignacion', function() {
        const ov = document.getElementById('overlay-confirmar-eliminar');
        if (ov) ov.style.display = 'none';
    });
</script>
