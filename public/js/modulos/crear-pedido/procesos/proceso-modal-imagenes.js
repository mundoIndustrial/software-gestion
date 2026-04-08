/**
 * proceso-modal-imagenes.js
 * Extraccion de manejo de imagenes del modal de procesos.
 */
(function initProcesoModalImagenes(global) {
    const procesoModalModules = global.procesoModalModules || (global.procesoModalModules = { ui: {}, imagenes: {}, persistencia: {}, tallas: {} });
    const procesoModalDebug = global.procesoModalDebug || function() {};

let imagenesProcesoActual = [null, null, null];

// Manejar upload de imagen individual
globalThis.manejarImagenProceso = function(input, indice) {
    procesoModalDebug(`[manejarImagenProceso]  INICIO - input=${input?.tagName}, indice=${indice}, procesoActualIndex=${globalThis.procesoActualIndex}`);
    
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        procesoModalDebug(`[manejarImagenProceso]  Archivo detectado: ${file.name} (${file.size} bytes)`);
        
        //  CAMBIO: Guardar el File object directamente, NO convertir a base64
        imagenesProcesoActual[indice - 1] = file;
        
        //  CRITICO: Sincronizar con globalThis.imagenesProcesoActual (usado en PATCH)
        if (!globalThis.imagenesProcesoActual) {
            globalThis.imagenesProcesoActual = [null, null, null];
        }
        globalThis.imagenesProcesoActual[indice - 1] = file;
        procesoModalDebug('[manejarImagenProceso]  Imagen guardada en globalThis.imagenesProcesoActual:', {
            indice: indice,
            filename: file.name,
            size: file.size,
            type: file.type
        });
        
        //  NUEVO: Guardar en storage universal de PROCESOS
        if (globalThis.universalImagenesStorage && globalThis.procesoActualIndex !== undefined) {
            procesoModalDebug(`[manejarImagenProceso]  Guardando en storage universal - procesoActualIndex=${globalThis.procesoActualIndex}`);
            const imagenData = {
                file: file,
                previewUrl: URL.createObjectURL(file),
                nombre: file.name,
                tamano: file.size,
                fileType: file.type,
                fileSize: file.size,
                fechaCreacion: new Date().toISOString()
            };
            
            const resultado = globalThis.universalImagenesStorage.agregarImagen('procesos', globalThis.procesoActualIndex, imagenData);
            procesoModalDebug(`[manejarImagenProceso]  Imagen guardada en storage universal de PROCESOS[${globalThis.procesoActualIndex}] - resultado=${resultado}`);
        } else {
            console.warn('[manejarImagenProceso]  No se pudo guardar en storage universal - no disponible o procesoActualIndex undefined');
            procesoModalDebug('[manejarImagenProceso]  Debug:', {
                universalStorage: !!globalThis.universalImagenesStorage,
                procesoActualIndex: globalThis.procesoActualIndex,
                agregarImagen: typeof globalThis.universalImagenesStorage?.agregarImagen
            });
        }
        
        // Mostrar preview usando URL.createObjectURL (mas eficiente que base64)
        const preview = document.getElementById(`proceso-foto-preview-${indice}`);
        if (preview) {
            const objectUrl = URL.createObjectURL(file);
            preview.style.border = '2px solid #0066cc';
            preview.style.background = 'transparent';
            preview.innerHTML = `
                <img src="${objectUrl}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
            `;
            
            //  Crear boton eliminar con data-indice (event delegation global lo detectar)
            let deleteBtn = preview.querySelector('.btn-eliminar-imagen-proceso');
            if (deleteBtn) deleteBtn.remove();
            deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn-eliminar-imagen-proceso';
            deleteBtn.type = 'button';
            deleteBtn.dataset.indice = String(indice); // Usar dataset y asegurar string
            deleteBtn.setAttribute('data-indice', String(indice)); // Fallback con setAttribute
            deleteBtn.style.cssText = 'position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; background: #ef4444; color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 16px; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold; z-index: 10;';
            deleteBtn.textContent = '×';
            
            // Event listener directo como fallback (en caso que event delegation falle)
            deleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                if (typeof globalThis.eliminarImagenProceso === 'function') {
                    globalThis.eliminarImagenProceso(indice);
                }
            }, true);
            
            preview.appendChild(deleteBtn);
            procesoModalDebug('[manejarImagenProceso]  boton eliminar creado con data-indice:', indice);
            
            // Limpiar URL cuando el elemento se elimine (prevenir memory leaks)
            preview._objectUrl = objectUrl;
        }
        
    } else {
        procesoModalDebug('[manejarImagenProceso]  No hay archivos en el input');
    }
};

//  NUEVO: Variable global para rastrear que imagen se esta eliminando
globalThis._imagenAEliminarIndice = null;

//  EVENT DELEGATION GLOBAL: Detectar clicks en botones .btn-eliminar-imagen-proceso
// Esto funciona incluso despues de que setupDragAndDropProceso clone el preview con cloneNode(true)
// porque cloneNode NO copia event listeners, pero event delegation en document si los detecta
(function() {
    function handleDeleteBtnClick(e) {
        const btn = e.target.closest('.btn-eliminar-imagen-proceso');
        if (!btn) return;
        
        procesoModalDebug('[EVENT-DELEGATION]  Click detectado en boton eliminar imagen');
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        const indiceRaw = btn.dataset.indice || btn.getAttribute('data-indice');
        const indice = Number.parseInt(indiceRaw, 10);
        procesoModalDebug('[EVENT-DELEGATION]  indice del boton:', indice, 'raw:', indiceRaw);
        
        if (indice > 0 && typeof globalThis.eliminarImagenProceso === 'function') {
            procesoModalDebug('[EVENT-DELEGATION]  Llamando eliminarImagenProceso(' + indice + ')');
            globalThis.eliminarImagenProceso(indice);
        } else {
            console.error('[EVENT-DELEGATION]  indice invalido o funcion no existe:', indice, typeof globalThis.eliminarImagenProceso);
        }
    }
    
    document.addEventListener('click', handleDeleteBtnClick, true); // true = capture phase
    procesoModalDebug('[EVENT-DELEGATION]  Event delegation global registrado para .btn-eliminar-imagen-proceso');
    
    // FALLBACK: Observer para agregar listeners directos a botones creados dinamicamente
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Node.ELEMENT_NODE
                        const deleteBtn = node.classList?.contains('btn-eliminar-imagen-proceso') ? 
                            node : 
                            node.querySelector?.('.btn-eliminar-imagen-proceso');
                        
                        if (deleteBtn && !deleteBtn._hasEliminarListener) {
                            procesoModalDebug('[MUTATION-OBSERVER]  Nuevo boton eliminar detectado, agregando listener directo');
                            deleteBtn._hasEliminarListener = true;
                            deleteBtn.addEventListener('click', handleDeleteBtnClick, true);
                        }
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: false,
        characterData: false
    });
    procesoModalDebug('[MUTATION-OBSERVER]  MutationObserver registrado para agregar listeners a botones dinamicos');
})();

function _mostrarModalConfirmarEliminarImagen(indice, source = 'mostrarModalConfirmarEliminarImagen') {
    procesoModalDebug('[' + source + ']  INICIANDO - Mostrando modal para imagen:', indice);
    globalThis._imagenAEliminarIndice = indice;
    procesoModalDebug('[' + source + ']  globalThis._imagenAEliminarIndice establecido a:', globalThis._imagenAEliminarIndice);
    
    let modal = document.getElementById('modal-confirmar-eliminar-imagen-proceso');
    procesoModalDebug('[' + source + ']  Modal encontrado?:', !!modal);
    
    // FALLBACK: Si el modal no existe, crear un modal simple
    if (!modal) {
        console.warn('[' + source + ']  Modal no encontrado, creando uno temporal...');
        modal = document.createElement('div');
        modal.id = 'modal-confirmar-eliminar-imagen-proceso';
        modal.className = 'modal-overlay';
        modal.style.cssText = 'z-index: 2147483648 !important; display: flex; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); align-items: center; justify-content: center;';
        modal.innerHTML = `
            <div class="modal-container" style="max-width: 400px; background: white; border-radius: 8px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
                <div class="modal-header modal-header-danger" style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 0.75rem;">
                    <span class="material-symbols-rounded" style="color: #dc2626; font-size: 24px;">warning</span>
                    <h3 class="modal-title" style="margin: 0; color: #dc2626; font-weight: 600;">Confirmar eliminación</h3>
                </div>
                
                <div class="modal-body" style="padding: 1.5rem;">
                    <p style="color: #374151; margin: 0; font-size: 0.95rem;">
                        ¿Estás seguro de que deseas eliminar esta imagen?
                    </p>
                </div>
                
                <div class="modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalConfirmarEliminarImagen()" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 500;">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" onclick="confirmarEliminarImagenProceso()" style="background: #dc2626; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="material-symbols-rounded" style="font-size: 18px;">delete</span>Eliminar
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        procesoModalDebug('[' + source + ']  Modal temporal creado y agregado al DOM');
    }
    
    if (modal) {
        procesoModalDebug('[' + source + ']  Modal existe, mostrando...');
        modal.style.display = 'flex';
        modal.style.zIndex = '2147483648';
        procesoModalDebug('[' + source + ']  Modal mostrado con z-index:', modal.style.zIndex);
    } else {
        console.error('[' + source + ']  No se pudo crear o encontrar el modal');
    }
}
procesoModalModules.ui.mostrarModalConfirmarEliminarImagen = _mostrarModalConfirmarEliminarImagen;

// Mostrar modal de confirmacion para eliminar imagen
globalThis.mostrarModalConfirmarEliminarImagen = function(indice) {
    procesoModalModules.ui.mostrarModalConfirmarEliminarImagen(indice, 'mostrarModalConfirmarEliminarImagen');
};

// Cerrar modal de confirmacion
globalThis.cerrarModalConfirmarEliminarImagen = function() {
    procesoModalDebug('[cerrarModalConfirmarEliminarImagen]  Cerrando modal');
    globalThis._imagenAEliminarIndice = null;
    
    const modal = document.getElementById('modal-confirmar-eliminar-imagen-proceso');
    if (modal) {
        modal.style.display = 'none';
        procesoModalDebug('[cerrarModalConfirmarEliminarImagen]  Modal cerrado');
    }
};

// Helpers para confirmar eliminacion de imagen
function _confirmarEliminarImagenProceso_revocarURLs(preview) {
    if (!preview) return;

    const imgEl = preview.querySelector('img');
    if (imgEl?.src?.startsWith('blob:')) {
        URL.revokeObjectURL(imgEl.src);
    }
    if (preview._objectUrl) {
        URL.revokeObjectURL(preview._objectUrl);
        preview._objectUrl = null;
    }
}
procesoModalModules.imagenes.revocarURLs = _confirmarEliminarImagenProceso_revocarURLs;

function _confirmarEliminarImagenProceso_resetMocks(indice) {
    if (imagenesProcesoActual !== undefined) {
        imagenesProcesoActual[indice - 1] = null;
    }
    if (globalThis.imagenesProcesoActual) {
        globalThis.imagenesProcesoActual[indice - 1] = null;
    }
}
procesoModalModules.imagenes.resetMocks = _confirmarEliminarImagenProceso_resetMocks;

function _confirmarEliminarImagenProceso_guardarExistente(indice) {
    if (!(globalThis.imagenesProcesoExistentes && globalThis.imagenesProcesoExistentes.length > (indice - 1))) {
        return false;
    }

    const imagenAeliminarObj = globalThis.imagenesProcesoExistentes[indice - 1];
    if (!globalThis.imagenesEliminadasProcesoStorage) {
        globalThis.imagenesEliminadasProcesoStorage = [];
    }

    if (imagenAeliminarObj && (imagenAeliminarObj.id || imagenAeliminarObj.ruta_original)) {
        const identificador = imagenAeliminarObj.id || imagenAeliminarObj.ruta_original;
        const yaGuardado = globalThis.imagenesEliminadasProcesoStorage.some(img => (img.id || img.ruta_original) === identificador);

        if (!yaGuardado) {
            globalThis.imagenesEliminadasProcesoStorage.push(imagenAeliminarObj);
            procesoModalDebug('[confirmarEliminarImagenProceso]  Imagen GUARDADA en storage de eliminadas:', {
                id: imagenAeliminarObj.id,
                ruta: imagenAeliminarObj.ruta_original,
                identificador,
                totalEliminadas: globalThis.imagenesEliminadasProcesoStorage.length,
                contenidoStorage: globalThis.imagenesEliminadasProcesoStorage
            });
        }
    } else {
        console.warn('[confirmarEliminarImagenProceso]  Imagen sin ID ni ruta_original, no se pudo guardar:', imagenAeliminarObj);
    }

    globalThis.imagenesProcesoExistentes[indice - 1] = null;
    const imagenesParaEnviar = globalThis.imagenesProcesoExistentes.filter(img => img !== null && img !== undefined && img !== '');
    procesoModalDebug('[confirmarEliminarImagenProceso]  Imagen existente marcada como eliminada:', {
        indice: indice - 1,
        imagenesRestantes: imagenesParaEnviar.length,
        storageLlenado: globalThis.imagenesEliminadasProcesoStorage,
        globalThisImagenesProcesoExistentes: globalThis.imagenesProcesoExistentes,
        imagenesParaEnviar
    });

    return true;
}
procesoModalModules.imagenes.guardarExistente = _confirmarEliminarImagenProceso_guardarExistente;

function _confirmarEliminarImagenProceso_obtenerImagenesParaEnviar() {
    const imagenesParaEnviar = [];

    if (globalThis.imagenesProcesoExistentes) {
        imagenesParaEnviar.push(...globalThis.imagenesProcesoExistentes.filter(img => img !== null && img !== undefined && img !== ''));
    }

    if (globalThis.imagenesProcesoActual) {
        globalThis.imagenesProcesoActual.forEach(img => {
            if (img instanceof File) imagenesParaEnviar.push(img);
        });
    }

    procesoModalDebug('[confirmarEliminarImagenProceso] imagenes restantes:', imagenesParaEnviar.length);
    return imagenesParaEnviar;
}
procesoModalModules.imagenes.obtenerImagenesParaEnviar = _confirmarEliminarImagenProceso_obtenerImagenesParaEnviar;

function _confirmarEliminarImagenProceso_registrarEnEditor() {
    if (!globalThis.procesosEditor) return;

    const imagenesParaRegistrar = _confirmarEliminarImagenProceso_obtenerImagenesParaEnviar();
    globalThis.procesosEditor.registrarCambioImagenes(imagenesParaRegistrar);
}
procesoModalModules.imagenes.registrarEnEditor = _confirmarEliminarImagenProceso_registrarEnEditor;

function _confirmarEliminarImagenProceso_resetPreview(preview, indice) {
    if (!preview) return;

    preview.style.border = '2px dashed #0066cc';
    preview.style.background = '#f9fafb';
    preview.innerHTML = `
        <div class="placeholder-content" style="text-align: center;">
            <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
            <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Imagen ${indice}</div>
        </div>
    `;

    const deleteBtn = preview.querySelector('.btn-eliminar-imagen-proceso');
    if (deleteBtn) {
        deleteBtn.remove();
    }
}
procesoModalModules.imagenes.resetPreview = _confirmarEliminarImagenProceso_resetPreview;

// Confirmar eliminacion de imagen
globalThis.confirmarEliminarImagenProceso = function() {
    const indice = globalThis._imagenAEliminarIndice;
    if (!indice) return;

    procesoModalDebug('[confirmarEliminarImagenProceso]  Confirmando eliminacion de imagen:', indice);
    cerrarModalConfirmarEliminarImagen();

    const preview = document.getElementById(`proceso-foto-preview-${indice}`);
    procesoModalModules.imagenes.revocarURLs(preview);

    procesoModalModules.imagenes.resetMocks(indice);

    const eliminadoExistente = procesoModalModules.imagenes.guardarExistente(indice);
    if (!eliminadoExistente) {
        procesoModalModules.imagenes.obtenerImagenesParaEnviar();
    }

    procesoModalModules.imagenes.registrarEnEditor();

    procesoModalModules.imagenes.resetPreview(preview, indice);

    const input = document.getElementById(`proceso-foto-input-${indice}`);
    if (input) {
        input.value = '';
    }
};

// Eliminar imagen del proceso (ahora muestra modal de confirmacion)
globalThis.eliminarImagenProceso = function(indice) {
    procesoModalModules.ui.mostrarModalConfirmarEliminarImagen(indice, 'eliminarImagenProceso');
};

// Limpiar todas las imagenes del proceso
function limpiarImagenesProceso() {
    // Limpiar URLs generadas
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        if (preview?._objectUrl) {
            URL.revokeObjectURL(preview._objectUrl);
            preview._objectUrl = null;
        }
    }
    
    // Limpiar array local
    imagenesProcesoActual = [null, null, null];
    
    //  CRITICO: Limpiar arrays globales para evitar contaminacion
    if (globalThis.imagenesProcesoActual) {
        globalThis.imagenesProcesoActual = [null, null, null];
        procesoModalDebug('[limpiarImagenesProceso]  globalThis.imagenesProcesoActual limpiado');
    }
    
    if (globalThis.imagenesProcesoExistentes) {
        globalThis.imagenesProcesoExistentes = [];
        procesoModalDebug('[limpiarImagenesProceso]  globalThis.imagenesProcesoExistentes limpiado');
    }
    
    //  NUEVO: Limpiar tambien el storage universal si hay un indice activo
    if (globalThis.universalImagenesStorage && globalThis.procesoActualIndex !== undefined) {
        procesoModalDebug(`[limpiarImagenesProceso]  Limpiando storage universal de PROCESOS[${globalThis.procesoActualIndex}]`);
        globalThis.universalImagenesStorage.eliminarTodasLasImagenes('procesos', globalThis.procesoActualIndex);
    }
    
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        const input = document.getElementById(`proceso-foto-input-${i}`);
        
        if (preview) {
            preview.style.border = '2px dashed #0066cc';
            preview.style.background = '#f9fafb';
            preview.innerHTML = `
                <div class="placeholder-content" style="text-align: center;">
                    <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                    <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Imagen ${i}</div>
                </div>
            `;
        }
        
        if (input) {
            input.value = '';
        }
    }
}
globalThis.limpiarImagenesProceso = limpiarImagenesProceso;
procesoModalModules.imagenes.limpiar = limpiarImagenesProceso;

})(globalThis);

