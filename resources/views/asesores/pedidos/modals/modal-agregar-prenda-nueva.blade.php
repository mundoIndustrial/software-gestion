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
                    <div class="foto-panel" id="panel-fotos-prenda" style="border: 2px solid #0066cc; border-radius: 8px; padding: 1rem; background: #f0f7ff;">
                        <label for="nueva-prenda-foto-input" class="foto-panel-label" style="color: #0066cc;">
                            <span class="material-symbols-rounded">photo_camera</span>📸 FOTOS DE PRENDA
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

                <!-- Color, Tela, Referencia e Imágenes de Tela -->
                <div class="form-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <label class="form-label-primary">
                            <span class="material-symbols-rounded">palette</span>COLOR, TELA Y REFERENCIA
                        </label>
                        <button type="button" id="btn-asignar-colores-tallas" class="btn btn-primary btn-sm" style="font-size: 0.85rem; padding: 0.5rem 1rem;" onclick="abrirModalAsignarColores();">
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
                                    <td style="padding: 0.5rem; text-align: center; vertical-align: top; width: 20%; position: relative; overflow: visible;">
                                        <!-- Botón para seleccionar imagen -->
                                        <button type="button" class="btn btn-success btn-flex" style="font-size: 0.75rem; padding: 0.5rem 1rem; transition: all 0.2s ease; margin-bottom: 8px; pointer-events: auto; background: rgb(37, 99, 235); transform: scale(1.05); box-shadow: rgba(59, 130, 246, 0.3) 0px 4px 12px;" title="Click para seleccionar imagen" onclick="event.stopPropagation(); event.preventDefault(); document.getElementById('modal-agregar-prenda-nueva-file-input').click(); return false;">
                                            <span class="material-symbols-rounded" style="font-size: 1.2rem; margin-right: 0.5rem;">image</span>
                                            <span style="font-size: 0.7rem;">Agregar imagen</span>
                                        </button>
                                        <input type="file" id="modal-agregar-prenda-nueva-file-input" accept="image/*" style="display: none;" aria-label="Imagen de la tela" onchange="manejarImagenTela(this)">
                                        
                                        <div id="nueva-prenda-tela-drop-zone" class="tela-drop-zone" style="position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80px; width: 100%; transition: all 0.2s ease; border: 2px dashed #0066cc; border-radius: 6px; padding: 8px; cursor: pointer; background: #f0f7ff;" data-zona="tela" data-estado="inicial" tabindex="0">
                                            <!-- Texto de ayuda -->
                                            <div style="text-align: center; color: #0066cc; font-size: 0.7rem; margin-top: 4px; pointer-events: none; font-weight: 500;">
                                                <div class="material-symbols-rounded" style="font-size: 1.2rem;">cloud_upload</div>
                                                <div>📸 TELA: Arrastra aquí o pega Ctrl+V</div>
                                            </div>
                                        </div>
                                        <!-- Preview temporal dentro de la celda -->
                                        <div id="nueva-prenda-tela-preview" style="display: none;"></div>
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
                    

                    <!-- Tallas y Cantidades -->
                    <!-- SECCIÓN 1: SELECCIONAR TALLAS POR GÉNERO -->
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
                            
                            <!-- Botón SOLO CANTIDAD -->
                            <button type="button" id="btn-genero-solo-cantidad" class="btn-genero" data-selected="false" onclick="abrirOpcionalSoloCantidad()">
                                <div class="btn-genero-content">
                                    <span class="material-symbols-rounded">shopping_cart</span>
                                    <span>SOLO CANTIDAD</span>
                                </div>
                                <span id="check-solo-cantidad" class="btn-genero-check">✓</span>
                            </button>
                        </div>
                        
                        <!-- Tarjetas de Géneros Seleccionados -->
                        <div id="tarjetas-generos-container" class="generos-container"></div>
                        
                        <!-- SECCIÓN SOLO CANTIDAD (Oculta por defecto) -->
                        <div id="seccion-solo-cantidad" style="display: none; margin-top: 1.5rem; padding: 1rem; background: #f0f7ff; border: 2px solid #0066cc; border-radius: 8px;">
                            <label class="form-label-primary" style="margin-bottom: 1rem;">
                                <span class="material-symbols-rounded">shopping_cart</span>CANTIDAD SIN ESPECÍFICAR TALLA *
                            </label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="flex: 1;">
                                    <label for="cantidad-solo" class="sr-only">Cantidad</label>
                                    <input type="number" id="cantidad-solo" placeholder="Ingresa la cantidad total..." min="1" class="form-input" style="font-size: 1rem; padding: 0.75rem;">
                                </div>
                                <button type="button" id="btn-agregar-solo-cantidad" class="btn btn-primary" style="white-space: nowrap; padding: 0.75rem 1.5rem;" onclick="agregarSoloCantidad()">
                                    <span class="material-symbols-rounded" style="vertical-align: middle;">add</span>
                                    Agregar
                                </button>
                                <button type="button" id="btn-cancelar-solo-cantidad" class="btn btn-outline-secondary" style="white-space: nowrap; padding: 0.75rem 1.5rem;" onclick="cancelarSoloCantidad()">
                                    <span class="material-symbols-rounded" style="vertical-align: middle;">close</span>
                                    Cancelar
                                </button>
                            </div>
                        </div>
                        
                        <!-- Tarjeta de SOLO CANTIDAD (Si está activada) -->
                        <div id="tarjeta-solo-cantidad" style="display: none; margin-top: 1rem; padding: 1rem; background: whitesmoke; border: 2px solid #0066cc; border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <p style="margin: 0; font-weight: 600; color: #0066cc;">CANTIDAD TOTAL: <span id="cantidad-solo-display" style="font-size: 1.2rem;">0</span> unidades</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger" onclick="eliminarSoloCantidad()" title="Eliminar">
                                    <span class="material-symbols-rounded">delete</span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Total general -->
                        <div class="total-box">
                            <span class="material-symbols-rounded">shopping_cart</span>
                            Total: <span id="total-prendas">0</span> unidades
                        </div>
                    </div>

                    <!-- SECCIÓN 2: RESUMEN DE ASIGNACIONES DE COLORES (Reemplaza la anterior) -->
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
                        
                        <div id="msg-resumen-vacio" style="text-align: center; padding: 2rem; color: rgb(156, 163, 175); background: rgb(249, 250, 251); border-radius: 8px; border: 1px dashed rgb(209, 213, 219); margin-top: 1rem; display: none;">
                            <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">inbox</span>
                            Sin asignaciones aún. Accede a "Asignar por Talla" para agregar.
                        </div>
                        
                        <div style="margin-top: 1rem; padding: 1rem; background: #e0f2fe; border-left: 4px solid #0369a1; border-radius: 4px;">
                            <p style="margin: 0; font-size: 0.875rem; color: #075985;">
                                <strong>Total asignado:</strong> <span id="total-asignaciones-resumen">0</span> unidades
                            </p>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1rem;">
                            <button type="button" id="btn-asignar-colores-prenda" class="btn btn-primary" style="flex: 1; min-width: 250px; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-weight: 600;">
                                <span class="material-symbols-rounded">palette</span>
                                ASIGNAR MÁS COLORES A LAS TALLAS
                            </button>
                            <button type="button" id="btn-limpiar-asignaciones" class="btn btn-outline-secondary" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1rem; font-weight: 600;">
                                <span class="material-symbols-rounded">delete_outline</span>
                                Limpiar Todo
                            </button>
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
                                        <input type="text" id="bolsillos-obs" placeholder="Observaciones (Ej: 4 bolsillos con cierre, ocultos, etc...)" disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
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

// ========== FUNCIONES PARA "SOLO CANTIDAD" ==========
/**
 * Variable global para almacenar la cantidad cuando se selecciona "SOLO CANTIDAD"
 */
window.cantidadSoloSeleccionada = null;

/**
 * Abre el campo de entrada para ingresar solo cantidad
 */
window.abrirOpcionalSoloCantidad = function() {
    const btnSoloCantidad = document.getElementById('btn-genero-solo-cantidad');
    const checkSoloCantidad = document.getElementById('check-solo-cantidad');
    const seccionSoloCantidad = document.getElementById('seccion-solo-cantidad');
    const tarjetaSoloCantidad = document.getElementById('tarjeta-solo-cantidad');
    
    // Verificar si ya está seleccionado
    const estáSeleccionado = btnSoloCantidad.getAttribute('data-selected') === 'true';
    
    if (estáSeleccionado) {
        // DESELECCIONAR
        btnSoloCantidad.style.background = 'white';
        btnSoloCantidad.style.borderColor = '#d1d5db';
        btnSoloCantidad.style.color = '#374151';
        btnSoloCantidad.setAttribute('data-selected', 'false');
        checkSoloCantidad.style.display = 'none';
        seccionSoloCantidad.style.display = 'none';
        
        // Limpiar la tarjeta si existe
        eliminarSoloCantidad();
    } else {
        // SELECCIONAR
        btnSoloCantidad.style.background = '#e0f2fe';
        btnSoloCantidad.style.borderColor = '#0369a1';
        btnSoloCantidad.style.color = '#0369a1';
        btnSoloCantidad.setAttribute('data-selected', 'true');
        checkSoloCantidad.style.display = 'inline-block';
        seccionSoloCantidad.style.display = 'block';
        
        // Desseleccionar otros géneros
        ['dama', 'caballero', 'sobremedida'].forEach(genero => {
            const btn = document.getElementById(`btn-genero-${genero}`);
            const check = document.getElementById(`check-${genero}`);
            if (btn) {
                btn.style.background = 'white';
                btn.style.borderColor = '#d1d5db';
                btn.style.color = '#374151';
                btn.setAttribute('data-selected', 'false');
                check.style.display = 'none';
            }
        });
        
        // Limpiar cualquier tarjeta de género anterior
        const tarjetasGeneros = document.getElementById('tarjetas-generos-container');
        if (tarjetasGeneros) {
            tarjetasGeneros.innerHTML = '';
        }
        
        // Enfocar en el campo de cantidad
        setTimeout(() => {
            document.getElementById('cantidad-solo').focus();
        }, 100);
    }
};

/**
 * Agrega la cantidad ingresada
 */
window.agregarSoloCantidad = function() {
    const cantidadInput = document.getElementById('cantidad-solo');
    const cantidad = parseInt(cantidadInput.value, 10);
    
    // Validar
    if (isNaN(cantidad) || cantidad <= 0) {
        alert('Por favor ingresa una cantidad válida');
        cantidadInput.focus();
        return;
    }
    
    // Guardar en variable global
    window.cantidadSoloSeleccionada = cantidad;
    
    // Actualizar display
    document.getElementById('cantidad-solo-display').textContent = cantidad;
    
    // Mostrar tarjeta
    const tarjeta = document.getElementById('tarjeta-solo-cantidad');
    tarjeta.style.display = 'block';
    
    // Ocultar formulario de entrada
    document.getElementById('seccion-solo-cantidad').style.display = 'none';
    
    // Actualizar total
    actualizarTotalPrendas();
    
    console.log('[SOLO CANTIDAD] Cantidad agregada:', cantidad);
};

/**
 * Cancela la entrada de "SOLO CANTIDAD"
 */
window.cancelarSoloCantidad = function() {
    document.getElementById('cantidad-solo').value = '';
    document.getElementById('seccion-solo-cantidad').style.display = 'none';
    
    const btnSoloCantidad = document.getElementById('btn-genero-solo-cantidad');
    btnSoloCantidad.style.background = 'white';
    btnSoloCantidad.style.borderColor = '#d1d5db';
    btnSoloCantidad.style.color = '#374151';
    btnSoloCantidad.setAttribute('data-selected', 'false');
    
    document.getElementById('check-solo-cantidad').style.display = 'none';
};

/**
 * Elimina la cantidad seleccionada
 */
window.eliminarSoloCantidad = function() {
    window.cantidadSoloSeleccionada = null;
    document.getElementById('cantidad-solo').value = '';
    document.getElementById('tarjeta-solo-cantidad').style.display = 'none';
    document.getElementById('seccion-solo-cantidad').style.display = 'none';
    
    const btnSoloCantidad = document.getElementById('btn-genero-solo-cantidad');
    btnSoloCantidad.style.background = 'white';
    btnSoloCantidad.style.borderColor = '#d1d5db';
    btnSoloCantidad.style.color = '#374151';
    btnSoloCantidad.setAttribute('data-selected', 'false');
    
    document.getElementById('check-solo-cantidad').style.display = 'none';
    
    actualizarTotalPrendas();
};

/**
 * Actualiza el total de prendas (incluyendo la cantidad "SOLO CANTIDAD")
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
    
    // Sumar cantidad "SOLO CANTIDAD"
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
<script defer src="{{ js_asset('js/arquitectura/WizardStateMachine.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/arquitectura/WizardEventBus.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/arquitectura/WizardLifecycleManager.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/arquitectura/WizardBootstrap.js') }}?v={{ $v }}"></script>

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

<!-- INCLUIR MODAL WIZARD DEDICADO -->
@include('asesores.pedidos.modals.modal-asignar-colores-por-talla')

<!-- Scripts para manejar el modal Bootstrap 4 -->
<script defer src="{{ js_asset('js/componentes/colores-por-talla/modal-manager.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/colores-por-talla/bootstrap-modal-init.js') }}?v={{ $v }}"></script>

<!-- Funciones para abrir y cerrar modal de colores por talla -->
<script>
    /**
     * Abre el modal de "Asignar Colores por Talla"
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
            // PASO 1: Obtener la tela seleccionada de la tabla
            let telaSeleccionada = null;
            
            // Buscar primera fila de DATOS del tbody con tela (OPCIÓN 1)
            // NOTA: La primera fila es la fila de inputs (tiene botón "agregarTelaNueva()"), hay que saltarla
            const tbody = document.getElementById('tbody-telas');
            if (tbody) {
                const filas = Array.from(tbody.querySelectorAll('tr'));
                // Buscar la primera fila que NO sea la fila de inputs
                const filaDatos = filas.find(tr => !tr.querySelector('button[onclick="agregarTelaNueva()"]') && !tr.querySelector('#nueva-prenda-tela'));
                if (filaDatos) {
                    const tdTela = filaDatos.querySelector('td:first-child');
                    if (tdTela) {
                        telaSeleccionada = tdTela.textContent.trim().toUpperCase();
                    }
                }
            }
            
            // OPCIÓN 2: Desde telasCreacion (cuando se crea nueva prenda)
            if (!telaSeleccionada && window.telasCreacion && window.telasCreacion.length > 0) {
                telaSeleccionada = window.telasCreacion[0].tela || window.telasCreacion[0].nombreTela || window.telasCreacion[0].nombre;
            }
            
            // OPCIÓN 3: Desde telasAgregadas (cuando se edita prenda)
            if (!telaSeleccionada && window.telasAgregadas && window.telasAgregadas.length > 0) {
                telaSeleccionada = window.telasAgregadas[0].tela || window.telasAgregadas[0].nombreTela || window.telasAgregadas[0].nombre;
            }
            
            // OPCIÓN 4: Desde telasEdicion (cuando se edita prenda)
            if (!telaSeleccionada && window.telasEdicion && window.telasEdicion.length > 0) {
                telaSeleccionada = window.telasEdicion[0].tela || window.telasEdicion[0].nombreTela || window.telasEdicion[0].nombre;
            }
            
            // VALIDACIÓN: Si no hay tela agregada, mostrar modal de advertencia
            if (!telaSeleccionada) {
                mostrarModalAdvertenciaTela();
                return;
            }

            // PASO 2: Resetear wizard state y UI al paso 1 (género)
            if (window.StateManager) {
                window.StateManager.resetWizardState();
                window.StateManager.setTelaSeleccionada(telaSeleccionada);
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
            
            if (typeof WizardManager !== 'undefined' && typeof WizardManager.irPaso === 'function') {
                WizardManager.irPaso(1);
            }
            
            // PASO 4: Abrir el modal
            $modal.modal('show');
        } catch (error) {
            alert('Error al abrir el modal: ' + error.message);
        }
    }
    
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
     * Muestra modal de advertencia cuando no hay tela agregada
     */
    function mostrarModalAdvertenciaTela() {
        // Remover modal previo si existe
        const previo = document.getElementById('modal-advertencia-tela');
        if (previo) previo.remove();

        const overlay = document.createElement('div');
        overlay.id = 'modal-advertencia-tela';
        overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:1070000;animation:fadeInOverlay 0.2s ease;';

        overlay.innerHTML = `
            <div style="background:#fff;border-radius:12px;padding:2rem 2.5rem;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);text-align:center;animation:scaleInModal 0.25s ease;">
                <div style="width:64px;height:64px;border-radius:50%;background:#FEF3C7;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                    <span class="material-symbols-rounded" style="font-size:2rem;color:#D97706;">warning</span>
                </div>
                <h3 style="margin:0 0 0.5rem;font-size:1.15rem;font-weight:700;color:#1f2937;">Tela requerida</h3>
                <p style="margin:0 0 1.5rem;font-size:0.9rem;color:#6b7280;line-height:1.5;">
                    Debes <strong>agregar al menos una tela</strong> antes de asignar cantidades por talla y color.
                </p>
                <button type="button" onclick="cerrarModalAdvertenciaTela()" style="background:#0066cc;color:#fff;border:none;padding:0.6rem 2rem;border-radius:8px;font-size:0.9rem;font-weight:600;cursor:pointer;transition:background 0.2s;">
                    Entendido
                </button>
            </div>
        `;

        // Cerrar al hacer click en el fondo
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) cerrarModalAdvertenciaTela();
        });

        document.body.appendChild(overlay);
    }

    /**
     * Cierra el modal de advertencia de tela
     */
    function cerrarModalAdvertenciaTela() {
        const modal = document.getElementById('modal-advertencia-tela');
        if (modal) {
            modal.style.animation = 'fadeOutOverlay 0.2s ease';
            setTimeout(() => modal.remove(), 200);
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
                                         (window.telasCreacion && window.telasCreacion[0]?.tela) ||
                                         (window.telasCreacion && window.telasCreacion[0]?.nombreTela) ||
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
                        // 🔴 NUEVO: Remover aria-hidden del contenedor padre para que el modal sea accesible
                        const asesorWrapper = document.querySelector('.asesores-wrapper');
                        if (asesorWrapper) {
                            asesorWrapper.removeAttribute('aria-hidden');
                        }
                        
                        // 🔴 NUEVO: Remover cualquier overlay existente antes de abrir el modal
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
                            
                            // 🔴 NUEVO: Remover aria-hidden del modal cuando se cierre
                            if (modalLimpiar) {
                                modalLimpiar.removeAttribute('aria-hidden');
                            }
                        });
                    }
                    
                    // 🔴 NUEVO: Listener para remover aria-hidden cuando se cierre el modal
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
                            
                            // 🔴 NUEVO: Remover aria-hidden del contenedor padre para que el modal sea accesible
                            const asesorWrapper = document.querySelector('.asesores-wrapper');
                            if (asesorWrapper) {
                                asesorWrapper.removeAttribute('aria-hidden');
                            }
                            
                            // 🔴 NUEVO: Remover cualquier overlay existente antes de abrir el modal
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
                                    
                                    // Actualizar tabla y otras secciones
                                    if (window.ColoresPorTalla && typeof window.ColoresPorTalla.actualizarTablaResumen === 'function') {
                                        window.ColoresPorTalla.actualizarTablaResumen();
                                    }
                                    if (typeof crearTarjetaGenero === 'function') crearTarjetaGenero();
                                    if (typeof actualizarTotalPrendas === 'function') actualizarTotalPrendas();
                                    
                                    console.log('✅ Asignación eliminada');
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
                console.warn('⚠️ jQuery o Bootstrap Modal no disponibles después de 5 segundos');
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

