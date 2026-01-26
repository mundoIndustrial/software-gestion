/**
 * EppModalTemplate - Template HTML del modal de EPP
 * Patrón: Template Pattern
 * Responsabilidad: Proporcionar el HTML del modal
 */

class EppModalTemplate {
    /**
     * Obtener HTML completo del modal
     */
    static getHTML() {
        return `
        <div id="modal-agregar-epp" class="modal-overlay" style="display: none;">
            <div class="modal-container" style="max-width: 600px; max-height: 90vh; display: flex; flex-direction: column;">
                <div class="modal-header modal-header-primary">
                    <h3 class="modal-title">
                        <span class="material-symbols-rounded">shield</span>EPP
                    </h3>
                    <button class="modal-close-btn" onclick="cerrarModalAgregarEPP()">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
                
                <div class="modal-body" style="flex: 1; overflow-y: auto; padding-right: 0.5rem;">
                    <div style="padding-right: 0.5rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #1f2937; margin-bottom: 0.75rem;">Buscar por Referencia o Nombre</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <span class="material-symbols-rounded" style="position: absolute; left: 12px; color: #9ca3af; font-size: 20px; pointer-events: none;">search</span>
                            <input 
                                type="text" 
                                id="inputBuscadorEPP"
                                onkeyup="filtrarEPPBuscador(this.value); this.value = this.value.toUpperCase();"
                                placeholder="Ej. Casco, Nitrilo, Botas..." 
                                style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; transition: all 0.3s ease; font-family: inherit; text-transform: uppercase;"
                            >
                        </div>
                        <div id="resultadosBuscadorEPP" style="display: none; margin-top: 0.5rem; background: white; border: 1px solid #e5e7eb; border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 10; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);"></div>
                    </div>

                    <div style="display: flex; gap: 0.75rem; margin-bottom: 1.5rem;">
                        <button 
                            type="button"
                            onclick="mostrarFormularioCrearEPPNuevo()"
                            style="flex: 1; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; font-size: 0.95rem;"
                            onmouseover="this.style.background = '#1d4ed8';"
                            onmouseout="this.style.background = '#3b82f6';"
                        >
                            <span class="material-symbols-rounded" style="vertical-align: middle; font-size: 18px; margin-right: 0.5rem;">add</span>Crear EPP Nuevo
                        </button>
                    </div>

                    <div id="formularioEPPNuevo" style="display: none; background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem;">
                        <h4 style="margin: 0 0 1rem 0; color: #1d4ed8; font-size: 0.95rem;">Crear EPP Nuevo</h4>
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="font-size: 0.875rem; font-weight: 500; color: #1f2937; display: block; margin-bottom: 0.5rem;">Nombre *</label>
                            <input 
                                type="text"
                                id="nuevoEPPNombre"
                                placeholder="Ej. Casco de Seguridad"
                                style="width: 100%; padding: 0.75rem; border: 2px solid #bfdbfe; border-radius: 6px; font-size: 0.95rem; font-family: inherit;"
                            >
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <label style="font-size: 0.875rem; font-weight: 500; color: #1f2937; display: block; margin-bottom: 0.5rem;">Descripción</label>
                            <input 
                                type="text"
                                id="nuevoEPPDescripcion"
                                placeholder="Ej. Casco de protección ABS"
                                style="width: 100%; padding: 0.75rem; border: 2px solid #bfdbfe; border-radius: 6px; font-size: 0.95rem; font-family: inherit;"
                            >
                        </div>

                        <div style="display: flex; gap: 0.75rem;">
                            <button 
                                type="button"
                                onclick="crearEPPNuevoYAgregar()"
                                style="flex: 1; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;"
                                onmouseover="this.style.background = '#1d4ed8';"
                                onmouseout="this.style.background = '#3b82f6';"
                            >
                                Crear
                            </button>
                            <button 
                                type="button"
                                onclick="ocultarFormularioCrearEPP()"
                                style="flex: 1; padding: 0.75rem; background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;"
                                onmouseover="this.style.background = '#d1d5db';"
                                onmouseout="this.style.background = '#e5e7eb';"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>

                    <div id="productoCardEPP" style="display: none; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; animation: slideDown 0.3s ease;">
                        <div style="display: flex; gap: 1rem; align-items: flex-start;">
                            <img id="imagenProductoEPP" src="" alt="EPP" style="width: 80px; height: 80px; border-radius: 6px; object-fit: cover; border: 2px solid #bfdbfe; flex-shrink: 0;">
                            <div style="display: flex; flex-direction: column; justify-content: center; flex: 1;">
                                <h3 id="nombreProductoEPP" style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1f2937; line-height: 1.4; margin-bottom: 0.25rem;"></h3>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="font-size: 0.875rem; font-weight: 500; color: #1f2937; display: block; margin-bottom: 0.5rem;">Cantidad</label>
                        <input 
                            type="number"
                            id="cantidadEPP"
                            min="1"
                            value="1"
                            placeholder="0"
                            oninput="actualizarEstilosBotonEPP();"
                            disabled
                            style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem; font-family: inherit; background: #f3f4f6; color: #9ca3af; cursor: not-allowed;"
                        >
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="font-size: 0.875rem; font-weight: 500; color: #1f2937; display: block; margin-bottom: 0.5rem;">Observaciones</label>
                        <textarea 
                            id="observacionesEPP"
                            placeholder="Ej. Requerimiento especial, notas..."
                            disabled
                            style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem; font-family: inherit; resize: vertical; min-height: 80px; background: #f3f4f6; color: #9ca3af; cursor: not-allowed; text-transform: uppercase;"
                        ></textarea>
                    </div>

                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: #fafafa; border: 1px dashed #d1d5db; border-radius: 8px;">
                        <label style="font-size: 0.875rem; font-weight: 600; color: #1f2937; display: block; margin-bottom: 0.75rem;">
                            <span class="material-symbols-rounded" style="vertical-align: middle; font-size: 18px; margin-right: 0.5rem;">image</span>Imágenes (Opcional)
                        </label>
                        
                        <div id="areaCargarImagenes" style="display: block; margin-bottom: 1rem; padding: 1.5rem; background: white; border: 2px dashed #ccc; border-radius: 8px; text-align: center; cursor: not-allowed; transition: all 0.3s ease; opacity: 0.5;" onmouseover="this.style.borderColor = '#0052a3'; this.style.background = '#f0f7ff';" onmouseout="this.style.borderColor = '#ccc'; this.style.background = 'white';" onclick="document.getElementById('inputCargaImagenesEPP').click();">
                            <span class="material-symbols-rounded" style="font-size: 32px; color: #9ca3af; margin-bottom: 0.5rem; display: block;">cloud_upload</span>
                            <p style="margin: 0; font-size: 0.95rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">Selecciona un EPP primero</p>
                            <p style="margin: 0; font-size: 0.8rem; color: #9ca3af;">JPG, PNG, WebP - Máximo 5MB</p>
                        </div>
                        
                        <input 
                            type="file" 
                            id="inputCargaImagenesEPP" 
                            multiple 
                            accept="image/jpeg,image/png,image/webp"
                            style="display: none;"
                            onchange="manejarSeleccionImagenes(event)"
                        >
                        
                        <div id="listaImagenesSubidas" style="display: none; margin-top: 1rem;">
                            <p style="font-size: 0.8rem; font-weight: 600; color: #6b7280; margin-bottom: 0.75rem; text-transform: uppercase;">Imágenes subidas:</p>
                            <div id="contenedorImagenesSubidas" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.75rem;"></div>
                        </div>

                        <div id="mensajeSelecccionarEPP" style="padding: 1rem; background: #fef3c7; border: 1px solid #fcd34d; border-radius: 6px; color: #92400e; font-size: 0.875rem; text-align: center;">
                            <span class="material-symbols-rounded" style="vertical-align: middle; font-size: 18px; margin-right: 0.5rem;">info</span>
                            Selecciona un EPP primero para agregar imágenes
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button 
                            class="btn-cancel" 
                            onclick="cerrarModalAgregarEPP()"
                            style="padding: 0.75rem 1.5rem; border: 1px solid #e5e7eb; background: white; color: #1f2937; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 0.95rem; transition: all 0.3s ease;"
                        >
                            Cancelar
                        </button>
                        <button 
                            id="btnAgregarEPP"
                            onclick="agregarEPPAlPedido()"
                            disabled
                            style="padding: 0.75rem 1.5rem; background: #0066cc; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: not-allowed; font-size: 0.95rem; opacity: 0.5; transition: all 0.3s ease;"
                        >
                            Guardar
                        </button>
                    </div>
                    </div>
            </div>
        </div>
        `;
    }
}

// Exportar clase
window.EppModalTemplate = EppModalTemplate;
