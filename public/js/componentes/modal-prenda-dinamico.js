/**
 * MODAL PRENDA DINÁMICO
 * Carga el modal del formulario de prendas dinámicamente en el DOM
 * Evita conflictos CSS al inyectarlo directamente en el body
 */

class ModalPrendaDinamico {
    constructor() {
        this.modalId = 'modal-agregar-prenda-nueva';
        this.modalHTML = null;
        this.inicializarDependencias();
    }

    /**
     * Inicializa las dependencias necesarias que podrían no estar disponibles
     * en algunos contextos (como edición de pedidos)
     */
    inicializarDependencias() {
        // ✅ FALLBACK: manejarCheckboxProceso si no existe
        if (!window.manejarCheckboxProceso) {
            console.warn('⚠️ manejarCheckboxProceso no encontrada, usando fallback');
            window.manejarCheckboxProceso = (tipoProceso, estaChecked) => {
                console.log(`🎯 [FALLBACK] manejarCheckboxProceso(${tipoProceso}, ${estaChecked})`);
                // Fallback simple: solo registrar en consola
                // El comportamiento real vendría de manejadores-procesos-prenda.js
            };
        }

        // ✅ FALLBACK: window.imagenesTelaStorage si no existe
        if (!window.imagenesTelaStorage) {
            console.warn('⚠️ imagenesTelaStorage no encontrada, usando fallback');
            window.imagenesTelaStorage = {
                obtenerImagenes: () => [],
                agregarImagen: (file) => {
                    console.log('FALLBACK: Imagen agregada', file);
                    return Promise.resolve();
                },
                limpiar: () => {
                    console.log('FALLBACK: Storage limpiado');
                    return Promise.resolve();
                },
                obtenerBlob: (index) => null
            };
        }

        // ✅ FALLBACK: window.pedidosAPI si no existe
        if (!window.pedidosAPI) {
            console.warn('⚠️ pedidosAPI no encontrada, usando fallback');
            window.pedidosAPI = {
                obtenerItems: () => Promise.resolve({ items: [] }),
                agregarItem: (data) => Promise.resolve({ success: true, items: [] })
            };
        }
    }

    /**
     * Obtiene el HTML del modal
     */
    getModalHTML() {
        return `<!-- MODAL: Agregar Prenda Nueva (Sin Cotización) - CON ESTILOS AISLADOS -->
<style>
#modal-agregar-prenda-nueva * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
#modal-agregar-prenda-nueva .form-prenda-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
#modal-agregar-prenda-nueva .form-row-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
#modal-agregar-prenda-nueva .form-group {
    margin-bottom: 1rem;
}
#modal-agregar-prenda-nueva .form-label-primary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 700;
    color: #0066cc;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}
#modal-agregar-prenda-nueva .form-input,
#modal-agregar-prenda-nueva .form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    font-family: inherit;
    resize: vertical;
}
#modal-agregar-prenda-nueva .form-input:focus,
#modal-agregar-prenda-nueva .form-textarea:focus {
    outline: none;
    border-color: #0066cc;
    box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
}
#modal-agregar-prenda-nueva .form-section {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}
#modal-agregar-prenda-nueva .foto-panel {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
#modal-agregar-prenda-nueva .foto-panel-label {
    font-weight: 700;
    color: #0066cc;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}
#modal-agregar-prenda-nueva .foto-preview {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #f9fafb;
    min-height: 120px;
}
#modal-agregar-prenda-nueva .foto-preview:hover {
    border-color: #0066cc;
    background: #eff6ff;
}
#modal-agregar-prenda-nueva .foto-preview-lg {
    height: 180px;
}
#modal-agregar-prenda-nueva .foto-preview-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
}
#modal-agregar-prenda-nueva .foto-preview-content .material-symbols-rounded {
    font-size: 2.5rem;
}
#modal-agregar-prenda-nueva .foto-preview-text {
    font-weight: 600;
    font-size: 0.875rem;
}
#modal-agregar-prenda-nueva .foto-counter {
    font-size: 0.875rem;
    color: #6b7280;
    text-align: center;
}
#modal-agregar-prenda-nueva .btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
#modal-agregar-prenda-nueva .btn-primary {
    background: #0066cc;
    color: white;
}
#modal-agregar-prenda-nueva .btn-primary:hover {
    background: #0052a3;
}
#modal-agregar-prenda-nueva .btn-success {
    background: #16a34a;
    color: white;
}
#modal-agregar-prenda-nueva .btn-success:hover {
    background: #15803d;
}
#modal-agregar-prenda-nueva .btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
}
#modal-agregar-prenda-nueva .btn-flex {
    display: flex;
    align-items: center;
    justify-content: center;
}
#modal-agregar-prenda-nueva .genero-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}
#modal-agregar-prenda-nueva .btn-genero {
    padding: 1rem;
    border: 2px solid #d1d5db;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.3s;
    font-weight: 600;
    color: #374151;
}
#modal-agregar-prenda-nueva .btn-genero:hover {
    border-color: #0066cc;
    background: #eff6ff;
}
#modal-agregar-prenda-nueva .btn-genero[data-selected="true"] {
    border-color: #0066cc;
    background: #eff6ff;
}
#modal-agregar-prenda-nueva .btn-genero-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
#modal-agregar-prenda-nueva .btn-genero-check {
    display: none;
    color: #16a34a;
    font-weight: 700;
    font-size: 1.5rem;
}
#modal-agregar-prenda-nueva .btn-genero[data-selected="true"] .btn-genero-check {
    display: block;
}
#modal-agregar-prenda-nueva .generos-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}
#modal-agregar-prenda-nueva .total-box {
    padding: 1rem;
    background: #f0f9ff;
    border-left: 4px solid #0066cc;
    border-radius: 4px;
    font-weight: 700;
    color: #0066cc;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
#modal-agregar-prenda-nueva .procesos-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}
#modal-agregar-prenda-nueva .proceso-checkbox {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
}
#modal-agregar-prenda-nueva .proceso-checkbox:hover {
    background: #f9fafb;
}
#modal-agregar-prenda-nueva .form-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #0066cc;
}
</style>

<div id="modal-agregar-prenda-nueva" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 99999 !important; align-items: center; justify-content: center; overflow-y: auto; padding: 2rem 0; margin: 0; box-sizing: border-box;">
    <div style="width: 90%; max-width: 1200px; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); display: flex; flex-direction: column; max-height: 90vh; margin: auto;">
        <!-- Header -->
        <div style="padding: 1.5rem; background: linear-gradient(135deg, #0066cc 0%, #004494 100%); border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 0.75rem;">
                <span class="material-symbols-rounded">add_box</span>Agregar Prenda Nueva
            </h3>
            <button style="background: transparent; border: none; color: white; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='transparent'" onclick="window.modalPrendaDinamico.cerrar()">
                <span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>
            </button>
        </div>
        
        <!-- Body -->
        <div style="flex: 1; overflow-y: auto; padding: 1.5rem;">
            <form id="form-prenda-nueva">
                <!-- Layout en 2 columnas: Datos a la izquierda, Fotos a la derecha -->
                <div class="form-prenda-grid">
                    <!-- COLUMNA IZQUIERDA: Datos principales -->
                    <div>
                        <!-- Primera fila: Nombre y Origen -->
                        <div class="form-row-2col">
                            <!-- Nombre de la prenda -->
                            <div class="form-group">
                                <label class="form-label-primary">
                                    <span class="material-symbols-rounded">checkroom</span>NOMBRE DE LA PRENDA *
                                </label>
                                <input type="text" id="nueva-prenda-nombre" required placeholder="Ej: CAMISA DRILL, POLO, PANTALÓN..." class="form-input" onkeyup="this.value = this.value.toUpperCase();" style="text-transform: uppercase;">
                            </div>
                            
                            <!-- Origen -->
                            <div class="form-group">
                                <label class="form-label-primary">
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
                            <label class="form-label-primary">
                                <span class="material-symbols-rounded">description</span>DESCRIPCIÓN
                            </label>
                            <textarea id="nueva-prenda-descripcion" placeholder="Descripción de la prenda, detalles especiales..." class="form-textarea" onkeyup="this.value = this.value.toUpperCase();" style="text-transform: uppercase;"></textarea>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: Fotos de la Prenda -->
                    <div class="foto-panel">
                        <label class="foto-panel-label">
                            <span class="material-symbols-rounded">photo_camera</span>FOTOS
                        </label>
                        
                        <!-- Imagen principal preview -->
                        <div id="nueva-prenda-foto-preview" class="foto-preview foto-preview-lg">
                            <div class="foto-preview-content">
                                <div class="material-symbols-rounded">add_photo_alternate</div>
                                <div class="foto-preview-text">Agregar</div>
                            </div>
                        </div>
                        
                        <!-- Contador de fotos -->
                        <div id="nueva-prenda-foto-contador" class="foto-counter"></div>
                        
                        <!-- Input de archivos -->
                        <input type="file" id="nueva-prenda-foto-input" accept="image/*" style="display: none;" onchange="manejarImagenesPrenda(this)">
                        
                        <!-- Botón agregar más fotos -->
                        <button type="button" id="nueva-prenda-foto-btn" class="btn btn-sm btn-primary" onclick="document.getElementById('nueva-prenda-foto-input').click()">
                            + Agregar
                        </button>
                    </div>
                </div>

                <!-- Color, Tela, Referencia e Imágenes de Tela -->
                <div class="form-section">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">palette</span>COLOR, TELA Y REFERENCIA
                    </label>
                    
                    <!-- Tabla de telas -->
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem;">
                            <thead>
                                <tr style="background: #0066cc; border-bottom: 2px solid #0066cc;">
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white;">Tela</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white;">Color</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white;">Referencia</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: white;">Imagen Tela</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: white; width: 30px;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbody-telas">
                                <!-- Fila para agregar nueva tela -->
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.5rem;">
                                        <input type="text" id="nueva-prenda-tela" placeholder="TELA..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                    </td>
                                    <td style="padding: 0.5rem;">
                                        <input type="text" id="nueva-prenda-color" placeholder="COLOR..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                    </td>
                                    <td style="padding: 0.5rem;">
                                        <input type="text" id="nueva-prenda-referencia" placeholder="REF..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                    </td>
                                    <td style="padding: 0.5rem; text-align: center; vertical-align: top;">
                                        <button type="button" onclick="document.getElementById('nueva-prenda-tela-img-input').click()" class="btn btn-primary btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;" title="Agregar imagen (opcional)">
                                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">image</span>
                                        </button>
                                        <input type="file" id="nueva-prenda-tela-img-input" accept="image/*" style="display: none;" onchange="manejarImagenTela(this)">
                                        <!-- Preview temporal dentro de la celda - EN EL FLUJO VISUAL Y VISIBLE -->
                                        <div id="nueva-prenda-tela-preview" style="display: none; flex-wrap: wrap; gap: 0.5rem; justify-content: center; align-items: flex-start; margin-top: 0.5rem; padding: 0.5rem; background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 4px; width: calc(100% + 1rem); margin-left: -0.5rem; margin-right: -0.5rem;"></div>
                                    </td>
                                    <td style="padding: 0.5rem; text-align: center;">
                                        <button type="button" onclick="agregarTelaNueva()" class="btn btn-success btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;" title="Agregar esta tela">
                                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">add</span>Agregar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tallas y Cantidades -->
                    <label class="form-label-primary" style="margin-top: 1.5rem;">
                        <span class="material-symbols-rounded">straighten</span>TALLAS Y CANTIDADES *
                    </label>
                    
                    <!-- Seleccionar Género(s) -->
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
                    </div>
                    
                    <!-- Tarjetas de Géneros Seleccionados -->
                    <div id="tarjetas-generos-container" class="generos-container"></div>
                    
                    <!-- Total general -->
                    <div class="total-box">
                        <span class="material-symbols-rounded">shopping_cart</span>
                        Total: <span id="total-prendas">0</span> unidades
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
                                        <input type="checkbox" id="aplica-manga" class="form-checkbox" onchange="manejarCheckVariacion(this)" style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <span style="font-weight: 600; color: #0066cc;">Manga</span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                            <input type="text" id="manga-input" placeholder="Ej: manga larga..." disabled list="opciones-manga" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%;">
                                            <datalist id="opciones-manga">
                                                <option value="Manga Larga">
                                                <option value="Manga Corta">
                                                <option value="Manga Media">
                                                <option value="Sin Manga">
                                            </datalist>
                                            <input type="text" id="manga-obs" placeholder="Observaciones..." disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Bolsillos -->
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <input type="checkbox" id="aplica-bolsillos" class="form-checkbox" onchange="manejarCheckVariacion(this)" style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <span style="font-weight: 600; color: #0066cc;">Bolsillos</span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <input type="text" id="bolsillos-obs" placeholder="Observaciones (Ej: 4 bolsillos con cierre, ocultos, etc...)" disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                    </td>
                                </tr>
                                
                                <!-- Broche/Botón -->
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <input type="checkbox" id="aplica-broche" class="form-checkbox" onchange="manejarCheckVariacion(this)" style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <span style="font-weight: 600; color: #0066cc;">Broche/Botón</span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                            <select id="broche-input" disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%;">
                                                <option value="">Seleccionar tipo...</option>
                                                <option value="boton">Botón</option>
                                                <option value="broche">Broche</option>
                                            </select>
                                            <input type="text" id="broche-obs" placeholder="Observaciones..." disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
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
                        <label class="proceso-checkbox">
                            <input type="checkbox" id="checkbox-reflectivo" name="nueva-prenda-procesos" value="reflectivo" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('reflectivo', true); } else { manejarCheckboxProceso('reflectivo', false); } }">
                            <span><i class="fas fa-lightbulb" style="color: #FFD700; margin-right: 6px;"></i>Reflectivo</span>
                        </label>
                        
                        <!-- Bordado -->
                        <label class="proceso-checkbox">
                            <input type="checkbox" id="checkbox-bordado" name="nueva-prenda-procesos" value="bordado" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('bordado', true); } else { manejarCheckboxProceso('bordado', false); } }">
                            <span><i class="fas fa-gem" style="color: #9333EA; margin-right: 6px;"></i>Bordado</span>
                        </label>
                        
                        <!-- Estampado -->
                        <label class="proceso-checkbox">
                            <input type="checkbox" id="checkbox-estampado" name="nueva-prenda-procesos" value="estampado" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('estampado', true); } else { manejarCheckboxProceso('estampado', false); } }">
                            <span><i class="fas fa-paint-brush" style="color: #DC2626; margin-right: 6px;"></i>Estampado</span>
                        </label>
                        
                        <!-- DTF -->
                        <label class="proceso-checkbox">
                            <input type="checkbox" id="checkbox-dtf" name="nueva-prenda-procesos" value="dtf" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('dtf', true); } else { manejarCheckboxProceso('dtf', false); } }">
                            <span><i class="fas fa-print" style="color: #EA580C; margin-right: 6px;"></i>DTF</span>
                        </label>
                        
                        <!-- Sublimado -->
                        <label class="proceso-checkbox">
                            <input type="checkbox" id="checkbox-sublimado" name="nueva-prenda-procesos" value="sublimado" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('sublimado', true); } else { manejarCheckboxProceso('sublimado', false); } }">
                            <span><i class="fas fa-tint" style="color: #0891B2; margin-right: 6px;"></i>Sublimado</span>
                        </label>
                    </div>
                    
                    <!-- ✅ CONTENEDOR PARA TARJETAS DE PROCESOS CONFIGURADOS -->
                    <div id="contenedor-tarjetas-procesos" style="margin-top: 1rem; display: none;"></div>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div style="padding: 1.5rem; background: #f9fafb; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 1rem; flex-shrink: 0; border-radius: 0 0 12px 12px;">
            <button style="padding: 0.75rem 1.5rem; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; transition: background 0.3s;" onmouseover="this.style.background='#4b5563'" onmouseout="this.style.background='#6b7280'" onclick="window.modalPrendaDinamico.cerrar()">Cancelar</button>
            <button id="btn-guardar-prenda" style="padding: 0.75rem 1.5rem; background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem; transition: background 0.3s;" onmouseover="this.style.background='#0052a3'" onmouseout="this.style.background='#0066cc'" onclick="agregarPrendaNueva()">
                <span class="material-symbols-rounded" style="font-size: 1rem;">check</span>Agregar Prenda
            </button>
        </div>
    </div>
</div>`;
    }

    /**
     * Inyecta el modal en el body
     */
    inyectar() {
        // Verificar si ya existe
        if (document.getElementById(this.modalId)) {
            console.log('✅ Modal ya existe en DOM');
            return true;
        }

        // Crear contenedor temporal
        const div = document.createElement('div');
        div.innerHTML = this.getModalHTML();
        
        // Inyectar TODOS los elementos (style + div modal) en body
        while (div.firstChild) {
            document.body.appendChild(div.firstChild);
        }
        
        console.log('✅ Modal inyectado dinámicamente en body');
        return true;
    }

    /**
     * Abre el modal
     */
    abrir() {
        // Primero inyectar si no existe
        this.inyectar();
        
        // Buscar el modal
        const modal = document.getElementById(this.modalId);
        if (!modal) {
            console.error('❌ No se pudo encontrar el modal después de inyectar');
            return false;
        }

        // Mostrar con display flex
        modal.style.display = 'flex';
        console.log('✅ Modal abierto (inyectado dinámicamente)');
        return true;
    }

    /**
     * Cierra el modal
     */
    cerrar() {
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.style.display = 'none';
            console.log('✅ Modal cerrado');
        }
    }

    /**
     * Limpia y remueve el modal del DOM
     */
    remover() {
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.remove();
            console.log('✅ Modal removido del DOM');
        }
    }
}

// Instancia global
window.modalPrendaDinamico = new ModalPrendaDinamico();
