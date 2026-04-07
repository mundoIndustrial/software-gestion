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
            deleteBtn.dataset.indice = indice;
            deleteBtn.style.cssText = 'position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; background: #ef4444; color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 16px; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold; z-index: 10;';
            deleteBtn.textContent = '×';
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
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-eliminar-imagen-proceso');
        if (btn) {
            procesoModalDebug('[EVENT-DELEGATION]  Click detectado en boton eliminar imagen');
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const indice = Number.parseInt(btn.dataset.indice, 10);
            procesoModalDebug('[EVENT-DELEGATION]  indice del boton:', indice);
            
            if (indice && typeof globalThis.eliminarImagenProceso === 'function') {
                procesoModalDebug('[EVENT-DELEGATION]  Llamando eliminarImagenProceso(' + indice + ')');
                globalThis.eliminarImagenProceso(indice);
            } else {
                console.error('[EVENT-DELEGATION]  indice invalido o funcion no existe:', indice, typeof globalThis.eliminarImagenProceso);
            }
        }
    }, true); // true = capture phase, se ejecuta ANTES que otros handlers
    procesoModalDebug('[EVENT-DELEGATION]  Event delegation global registrado para .btn-eliminar-imagen-proceso');
})();

function _mostrarModalConfirmarEliminarImagen(indice, source = 'mostrarModalConfirmarEliminarImagen') {
    procesoModalDebug('[' + source + ']  INICIANDO - Mostrando modal para imagen:', indice);
    globalThis._imagenAEliminarIndice = indice;
    procesoModalDebug('[' + source + ']  globalThis._imagenAEliminarIndice establecido a:', globalThis._imagenAEliminarIndice);
    
    const modal = document.getElementById('modal-confirmar-eliminar-imagen-proceso');
    procesoModalDebug('[' + source + ']  Modal encontrado?:', !!modal);
    
    if (modal) {
        procesoModalDebug('[' + source + ']  Modal existe, mostrando...');
        modal.style.display = 'flex';
        modal.style.zIndex = '999999999';
        procesoModalDebug('[' + source + ']  Modal mostrado con z-index:', modal.style.zIndex);
    } else {
        console.error('[' + source + ']  MODAL NO ENCONTRADO - ID: modal-confirmar-eliminar-imagen-proceso');
        procesoModalDebug('[' + source + ']  Elementos en body:', document.body.children.length);
        procesoModalDebug('[' + source + ']  Buscando modales con clase modal-overlay:');
        document.querySelectorAll('.modal-overlay').forEach((m, idx) => {
            procesoModalDebug(`  [${idx}] ID: ${m.id}, Display: ${m.style.display}, Z-index: ${m.style.zIndex}`);
        });
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

