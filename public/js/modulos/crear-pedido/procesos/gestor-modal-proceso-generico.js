/**
 * gestor-modal-proceso-generico.js
 * 
 * Maneja la funcionalidad del modal genérico de procesos
 * (Reflectivo, Estampado, Bordado, DTF, Sublimado)
 */

let procesoActual = null;
// NUEVO: Flag para diferenciar entre CREACIÓN y EDICIÓN
let modoActual = 'crear';  // 'crear' o 'editar'
// NUEVO: Buffer temporal para cambios en EDICIÓN (no se sincroniza hasta GUARDAR CAMBIOS final)
let cambiosProceso = null;

globalThis.tallasSeleccionadasProceso = { dama: [], caballero: [], sobremedida: null };
globalThis.ubicacionesProcesoSeleccionadas = [];
// ESTRUCTURA INDEPENDIENTE: Cantidades de TALLAS DEL PROCESO (NO de la prenda)
// Estructura: { dama: { S: 5, M: 3 }, caballero: { 32: 2 }, sobremedida: { DAMA: 10, CABALLERO: 5 } }
globalThis.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };

// Configuración por tipo de proceso
const procesosConfig = {
    reflectivo: {
        titulo: 'Agregar Reflectivo',
        icon: 'light_mode',
        btnTexto: 'Agregar Reflectivo'
    },
    estampado: {
        titulo: 'Agregar Estampado',
        icon: 'format_paint',
        btnTexto: 'Agregar Estampado'
    },
    bordado: {
        titulo: 'Agregar Bordado',
        icon: 'auto_awesome',
        btnTexto: 'Agregar Bordado'
    },
    dtf: {
        titulo: 'Agregar DTF',
        icon: 'print',
        btnTexto: 'Agregar DTF'
    },
    sublimado: {
        titulo: 'Agregar Sublimado',
        icon: 'palette',
        btnTexto: 'Agregar Sublimado'
    }
};

// Tallas estándar por género
const tallasEstandar = {
    dama: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
    caballero: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL']
};

// helpers para abrir modal genérico de proceso
function _procesoGenerico_resetImagenesEliminadas() {
    globalThis.imagenesEliminadasProcesoStorage = [];
    console.log('[abrirModalProcesoGenerico]  Storage de imágenes eliminadas reseteado');
}

function _procesoGenerico_determinarIndice(tipoProceso, esEdicion) {
    if (esEdicion && globalThis.procesosSeleccionados?.[tipoProceso]?.indiceResultado !== undefined) {
        globalThis.procesoActualIndex = globalThis.procesosSeleccionados[tipoProceso].indiceResultado;
        console.log(`🔢 [abrirModalProcesoGenerico] EDICIÓN: Usando índice existente ${globalThis.procesoActualIndex} para ${tipoProceso}`);
        return;
    }

    const indicesUsados = new Set();
    Object.values(globalThis.procesosSeleccionados || {}).forEach(proceso => {
        if (proceso.indiceResultado !== undefined) {
            indicesUsados.add(proceso.indiceResultado);
        }
    });

    let indiceDisponible = 1;
    while (indicesUsados.has(indiceDisponible) && indiceDisponible <= 3) {
        indiceDisponible++;
    }

    globalThis.procesoActualIndex = indiceDisponible;
    console.log(`🔢 [abrirModalProcesoGenerico] CREACIÓN: Índices usados=${[...indicesUsados]}, index asignado=${globalThis.procesoActualIndex} para ${tipoProceso}`);
}

function _procesoGenerico_limpiarStorageUniversal(esEdicion) {
    if (globalThis.universalImagenesStorage && !esEdicion && globalThis.procesoActualIndex !== undefined) {
        console.log(`[abrirModalProcesoGenerico]  LIMPIEZA FORZADA: Storage de PROCESOS[${globalThis.procesoActualIndex}] para nuevo proceso`);
        globalThis.universalImagenesStorage.eliminarTodasLasImagenes('procesos', globalThis.procesoActualIndex);
    }
}

function _procesoGenerico_limpiarCreacion() {
    globalThis.tallasSeleccionadasProceso = { dama: [], caballero: [], sobremedida: null };
    globalThis.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };

    if (globalThis.imagenesProcesoActual) {
        globalThis.imagenesProcesoActual = [null, null, null];
        console.log('[abrirModalProcesoGenerico]  globalThis.imagenesProcesoActual limpiado para nuevo proceso');
    }

    if (globalThis.imagenesProcesoExistentes) {
        globalThis.imagenesProcesoExistentes = [];
        console.log('[abrirModalProcesoGenerico]  globalThis.imagenesProcesoExistentes limpiado para nuevo proceso');
    }

    const resumenTallas = document.getElementById('proceso-tallas-resumen');
    if (resumenTallas) resumenTallas.innerHTML = '';

    globalThis.ubicacionesProcesoSeleccionadas = [];
    const listaUbicaciones = document.getElementById('lista-ubicaciones-proceso');
    if (listaUbicaciones) listaUbicaciones.innerHTML = '';
    const inputUbicacion = document.getElementById('input-ubicacion-nueva');
    if (inputUbicacion) inputUbicacion.value = '';

    if (typeof limpiarImagenesProceso === 'function') {
        limpiarImagenesProceso();
    }
}

function _procesoGenerico_cargarEdicion(tipoProceso) {
    const procesoDatos = globalThis.procesosSeleccionados?.[tipoProceso]?.datos;
    if (!procesoDatos?.tallas) {
        return;
    }

    globalThis.tallasCantidadesProceso = {
        dama: { ...(procesoDatos.tallas.dama ?? {}) },
        caballero: { ...(procesoDatos.tallas.caballero ?? {}) },
        sobremedida: { ...(procesoDatos.tallas.sobremedida ?? {}) }
    };

    const sobremedidaSel = Object.keys(procesoDatos.tallas.sobremedida ?? {}).length > 0
        ? { ...procesoDatos.tallas.sobremedida }
        : null;

    globalThis.tallasSeleccionadasProceso = {
        dama: Object.keys(procesoDatos.tallas.dama ?? {}),
        caballero: Object.keys(procesoDatos.tallas.caballero ?? {}),
        sobremedida: sobremedidaSel
    };

    console.log(' [EDICIÓN] Tallas del proceso cargadas en tallasCantidadesProceso y tallasSeleccionadasProceso:', {
        tallasCantidadesProceso: globalThis.tallasCantidadesProceso,
        tallasSeleccionadasProceso: globalThis.tallasSeleccionadasProceso
    });

    if (globalThis.renderizarListaUbicaciones) {
        globalThis.renderizarListaUbicaciones();
    }
    if (globalThis.actualizarResumenTallasProceso) {
        globalThis.actualizarResumenTallasProceso();
    }
}

function _procesoGenerico_actualizarTextos(config, tipoProceso, esEdicion) {
    const titleEl = document.getElementById('modal-proceso-titulo');
    const iconEl = document.getElementById('modal-proceso-icon');
    const btnTextoEl = document.getElementById('modal-btn-texto');

    const textoTitulo = esEdicion ? `Editar ${nombresProcesos[tipoProceso] || tipoProceso}` : config.titulo;
    const textoBtnTexto = esEdicion ? `Editar ${nombresProcesos[tipoProceso] || tipoProceso}` : config.btnTexto;

    if (titleEl) titleEl.textContent = textoTitulo;
    if (iconEl) iconEl.textContent = config.icon;
    if (btnTextoEl) btnTextoEl.textContent = textoBtnTexto;

    if (!esEdicion) {
        const form = document.getElementById('form-proceso-generico');
        if (form) form.reset();
    }
}

function _procesoGenerico_abrirModal(modal) {
    modal.style.display = 'flex';
    modal.style.zIndex = '999999999';
    console.log(' [MODAL-PROCESO] Z-index forzado a:', modal.style.zIndex);
    modal.removeAttribute('aria-hidden');
}

function _procesoGenerico_postOpen(esEdicion) {
    console.log('[abrirModalProcesoGenerico]  Sincronizando tallas de la prenda con el proceso...');
    globalThis.sincronizarTallasConModalProceso?.();
    console.log(' [MODAL-PROCESO] aria-hidden removido para accesibilidad');

    if (!esEdicion) {
        globalThis.aplicarProcesoParaTodasTallas();
        console.log('[abrirModalProcesoGenerico]  Tallas aplicadas automáticamente al abrir modal');
    }
}

// Abrir modal para un tipo específico de proceso
globalThis.abrirModalProcesoGenerico = function(tipoProceso, esEdicion = false) {
    const modal = document.getElementById('modal-proceso-generico');
    if (!modal) {
        return;
    }

    _procesoGenerico_resetImagenesEliminadas();

    procesoActual = tipoProceso;
    modoActual = esEdicion ? 'editar' : 'crear';

    _procesoGenerico_determinarIndice(tipoProceso, esEdicion);
    _procesoGenerico_limpiarStorageUniversal(esEdicion);

    const config = procesosConfig[tipoProceso];
    if (!config) {
        return;
    }

    try {
        _procesoGenerico_actualizarTextos(config, tipoProceso, esEdicion);

        if (esEdicion) {
            _procesoGenerico_cargarEdicion(tipoProceso);
        } else {
            _procesoGenerico_limpiarCreacion();
        }

        _procesoGenerico_abrirModal(modal);
        _procesoGenerico_postOpen(esEdicion);
    } catch (error) {
        console.error('[abrirModalProcesoGenerico] Error:', error);
    }
};

// Cerrar modal
// @param {boolean} procesoGuardado - Si es true, mantiene el checkbox seleccionado (proceso guardado exitosamente)
globalThis.cerrarModalProcesoGenerico = function(procesoGuardado = false) {
    const modal = document.getElementById('modal-proceso-generico');
    if (modal) {
        modal.style.display = 'none';
    }
    
    // En CREACIÓN: Deseleccionar si no se guardó
    // En EDICIÓN: No hacer nada (cambios están en buffer, se aplicarán en PATCH final)
    if (modoActual === 'crear' && procesoActual && !procesoGuardado) {

        
        //  PASO 1: Deseleccionar el checkbox visualmente en el HTML
        // IMPORTANTE: Hacemos esto ANTES de llamar a manejarCheckboxProceso
        // para que el .onclick no se dispare automáticamente
        const checkbox = document.getElementById(`checkbox-${procesoActual}`);
        if (checkbox?.checked) {
            // Usar una bandera temporal para evitar que onclick se dispare
            checkbox._ignorarOnclick = true;
            checkbox.checked = false;

        }
        
        //  PASO 2: Actualizar el estado del gestor (procesos seleccionados)
        globalThis.manejarCheckboxProceso?.(procesoActual, false);
        
        // Limpiar la bandera
        if (checkbox) {
            checkbox._ignorarOnclick = false;
        }
        
        // PASO 3: Limpiar estructura de tallas del proceso
        globalThis.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };
        
    } else if (modoActual === 'editar' && procesoGuardado) {
        console.log('[EDICIÓN] Modal cerrada - cambios en buffer temporal, esperando GUARDAR CAMBIOS final');
        
        // NUEVO: Guardar cambios en el gestor de edición
        if (globalThis.gestorEditacionProcesos) {
            console.log('[EDICIÓN]  Guardando cambios en gestorEditacionProcesos...');
            globalThis.gestorEditacionProcesos.guardarCambiosActuales();
            console.log('[EDICIÓN]  Cambios guardados en gestorEditacionProcesos');
        }
    }
    
    //  NO limpiar storage de imágenes inmediatamente después de guardar
    // Las imágenes se necesitan para renderizar las tarjetas
    // Se limpiarán en el próximo proceso nuevo o al recargar la página
    if (false && modoActual === 'crear' && procesoGuardado && globalThis.procesoActualIndex !== undefined) {
        // Código desactivado: No limpiar storage inmediatamente
        console.log(`[cerrarModalProcesoGenerico] 🚫 NO se limpia storage - imágenes necesarias para tarjetas`);
        
        // Limpiar el storage de imágenes del índice usado para este proceso
        console.log(`[cerrarModalProcesoGenerico]  Limpiando storage UNIVERSAL de PROCESOS del índice ${globalThis.procesoActualIndex}`);
        if (globalThis.universalImagenesStorage && typeof globalThis.universalImagenesStorage.eliminarTodasLasImagenes === 'function') {
            globalThis.universalImagenesStorage.eliminarTodasLasImagenes('procesos', globalThis.procesoActualIndex);
            console.log(`[cerrarModalProcesoGenerico]  Storage UNIVERSAL de PROCESOS limpiado para índice ${globalThis.procesoActualIndex}`);
        }
        
        // También limpiar el array local
        imagenesProcesoActual = [null, null, null];
        if (globalThis.imagenesProcesoActual) {
            globalThis.imagenesProcesoActual = [null, null, null];
        }
        console.log('[cerrarModalProcesoGenerico]  Arrays locales de imágenes limpiados');
    } else {
        console.log(`[cerrarModalProcesoGenerico]  Storage conservado - imágenes necesarias para renderizado`);
    }
    
    // NUEVO: Reset de variables después de cerrar
    procesoActual = null;
    globalThis.procesoActualIndex = undefined;
    modoActual = 'crear';  // Reset a valor por defecto
};

// Array para almacenar los archivos reales del proceso (hasta 3)
// Cambio: Ahora almacenamos File objects en lugar de base64
let imagenesProcesoActual = [null, null, null];

// Manejar upload de imagen individual
globalThis.manejarImagenProceso = function(input, indice) {
    console.log(`[manejarImagenProceso]  INICIO - input=${input?.tagName}, indice=${indice}, procesoActualIndex=${globalThis.procesoActualIndex}`);
    
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        console.log(`[manejarImagenProceso] 📁 Archivo detectado: ${file.name} (${file.size} bytes)`);
        
        //  CAMBIO: Guardar el File object directamente, NO convertir a base64
        imagenesProcesoActual[indice - 1] = file;
        
        //  CRÍTICO: Sincronizar con globalThis.imagenesProcesoActual (usado en PATCH)
        if (!globalThis.imagenesProcesoActual) {
            globalThis.imagenesProcesoActual = [null, null, null];
        }
        globalThis.imagenesProcesoActual[indice - 1] = file;
        console.log('[manejarImagenProceso]  Imagen guardada en globalThis.imagenesProcesoActual:', {
            indice: indice,
            filename: file.name,
            size: file.size,
            type: file.type
        });
        
        //  NUEVO: Guardar en storage universal de PROCESOS
        if (globalThis.universalImagenesStorage && globalThis.procesoActualIndex !== undefined) {
            console.log(`[manejarImagenProceso]  Guardando en storage universal - procesoActualIndex=${globalThis.procesoActualIndex}`);
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
            console.log(`[manejarImagenProceso]  Imagen guardada en storage universal de PROCESOS[${globalThis.procesoActualIndex}] - resultado=${resultado}`);
        } else {
            console.warn('[manejarImagenProceso]  No se pudo guardar en storage universal - no disponible o procesoActualIndex undefined');
            console.log('[manejarImagenProceso]  Debug:', {
                universalStorage: !!globalThis.universalImagenesStorage,
                procesoActualIndex: globalThis.procesoActualIndex,
                agregarImagen: typeof globalThis.universalImagenesStorage?.agregarImagen
            });
        }
        
        // Mostrar preview usando URL.createObjectURL (más eficiente que base64)
        const preview = document.getElementById(`proceso-foto-preview-${indice}`);
        if (preview) {
            const objectUrl = URL.createObjectURL(file);
            preview.style.border = '2px solid #0066cc';
            preview.style.background = 'transparent';
            preview.innerHTML = `
                <img src="${objectUrl}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
            `;
            
            //  Crear botón eliminar con data-indice (event delegation global lo detectará)
            let deleteBtn = preview.querySelector('.btn-eliminar-imagen-proceso');
            if (deleteBtn) deleteBtn.remove();
            deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn-eliminar-imagen-proceso';
            deleteBtn.type = 'button';
            deleteBtn.setAttribute('data-indice', indice);
            deleteBtn.style.cssText = 'position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; background: #ef4444; color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 16px; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold; z-index: 10;';
            deleteBtn.textContent = '×';
            preview.appendChild(deleteBtn);
            console.log('[manejarImagenProceso]  Botón eliminar creado con data-indice:', indice);
            
            // Limpiar URL cuando el elemento se elimine (prevenir memory leaks)
            preview._objectUrl = objectUrl;
        }
        
    } else {
        console.log('[manejarImagenProceso]  No hay archivos en el input');
    }
};

//  NUEVO: Variable global para rastrear qué imagen se está eliminando
globalThis._imagenAEliminarIndice = null;

//  EVENT DELEGATION GLOBAL: Detectar clicks en botones .btn-eliminar-imagen-proceso
// Esto funciona incluso después de que setupDragAndDropProceso clone el preview con cloneNode(true)
// porque cloneNode NO copia event listeners, pero event delegation en document SÍ los detecta
(function() {
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-eliminar-imagen-proceso');
        if (btn) {
            console.log('[EVENT-DELEGATION]  Click detectado en botón eliminar imagen');
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const indice = parseInt(btn.getAttribute('data-indice'), 10);
            console.log('[EVENT-DELEGATION] 📌 Índice del botón:', indice);
            
            if (indice && typeof globalThis.eliminarImagenProceso === 'function') {
                console.log('[EVENT-DELEGATION]  Llamando eliminarImagenProceso(' + indice + ')');
                globalThis.eliminarImagenProceso(indice);
            } else {
                console.error('[EVENT-DELEGATION]  Índice inválido o función no existe:', indice, typeof globalThis.eliminarImagenProceso);
            }
        }
    }, true); // true = capture phase, se ejecuta ANTES que otros handlers
    console.log('[EVENT-DELEGATION]  Event delegation global registrado para .btn-eliminar-imagen-proceso');
})();

// Mostrar modal de confirmación para eliminar imagen
globalThis.mostrarModalConfirmarEliminarImagen = function(indice) {
    console.log('[mostrarModalConfirmarEliminarImagen]  INICIANDO - Mostrando modal para imagen:', indice);
    globalThis._imagenAEliminarIndice = indice;
    console.log('[mostrarModalConfirmarEliminarImagen] 📌 globalThis._imagenAEliminarIndice establecido a:', globalThis._imagenAEliminarIndice);
    
    const modal = document.getElementById('modal-confirmar-eliminar-imagen-proceso');
    console.log('[mostrarModalConfirmarEliminarImagen]  Modal encontrado?:', !!modal);
    
    if (modal) {
        console.log('[mostrarModalConfirmarEliminarImagen]  Modal existe, mostrando...');
        modal.style.display = 'flex';
        modal.style.zIndex = '999999999';
        console.log('[mostrarModalConfirmarEliminarImagen]  Modal mostrado con z-index:', modal.style.zIndex);
    } else {
        console.error('[mostrarModalConfirmarEliminarImagen]  MODAL NO ENCONTRADO - ID: modal-confirmar-eliminar-imagen-proceso');
        console.log('[mostrarModalConfirmarEliminarImagen]  Elementos en body:', document.body.children.length);
        console.log('[mostrarModalConfirmarEliminarImagen]  Buscando modales con clase modal-overlay:');
        document.querySelectorAll('.modal-overlay').forEach((m, idx) => {
            console.log(`  [${idx}] ID: ${m.id}, Display: ${m.style.display}, Z-index: ${m.style.zIndex}`);
        });
    }
};

// Cerrar modal de confirmación
globalThis.cerrarModalConfirmarEliminarImagen = function() {
    console.log('[cerrarModalConfirmarEliminarImagen]  Cerrando modal');
    globalThis._imagenAEliminarIndice = null;
    
    const modal = document.getElementById('modal-confirmar-eliminar-imagen-proceso');
    if (modal) {
        modal.style.display = 'none';
    }
};

// Confirmar eliminación de imagen
globalThis.confirmarEliminarImagenProceso = function() {
    const indice = globalThis._imagenAEliminarIndice;
    if (!indice) return;
    
    console.log('[confirmarEliminarImagenProceso]  Confirmando eliminación de imagen:', indice);
    
    // Cerrar modal
    cerrarModalConfirmarEliminarImagen();
    
    // Limpiar URL.createObjectURL si existe
    const preview = document.getElementById(`proceso-foto-preview-${indice}`);
    if (preview) {
        const imgEl = preview.querySelector('img');
        if (imgEl?.src?.startsWith('blob:')) {
            URL.revokeObjectURL(imgEl.src);
        }
        if (preview._objectUrl) {
            URL.revokeObjectURL(preview._objectUrl);
            preview._objectUrl = null;
        }
    }
    
    // Limpiar en array local y global
    if (typeof imagenesProcesoActual !== 'undefined') {
        imagenesProcesoActual[indice - 1] = null;
    }
    if (globalThis.imagenesProcesoActual) {
        globalThis.imagenesProcesoActual[indice - 1] = null;
    }
    
    //  CRÍTICO: Guardar la imagen ANTES de marcarla como null
    if (globalThis.imagenesProcesoExistentes && globalThis.imagenesProcesoExistentes.length > (indice - 1)) {
        const imagenAeliminarObj = globalThis.imagenesProcesoExistentes[indice - 1];
        
        // Inicializar array de eliminadas si no existe
        if (!globalThis.imagenesEliminadasProcesoStorage) {
            globalThis.imagenesEliminadasProcesoStorage = [];
        }
        
        // Guardar el objeto completo de la imagen a eliminar
        // IMPORTANTE: Usar ruta_original como identificador único (algunos objetos no tienen .id)
        if (imagenAeliminarObj && (imagenAeliminarObj.id || imagenAeliminarObj.ruta_original)) {
            const identificador = imagenAeliminarObj.id || imagenAeliminarObj.ruta_original;
            // Verificar que no esté duplicado
            const yaGuardado = globalThis.imagenesEliminadasProcesoStorage.some(img => {
                return (img.id || img.ruta_original) === identificador;
            });
            if (!yaGuardado) {
                globalThis.imagenesEliminadasProcesoStorage.push(imagenAeliminarObj);
                console.log('[confirmarEliminarImagenProceso]  Imagen GUARDADA en storage de eliminadas:', {
                    id: imagenAeliminarObj.id,
                    ruta: imagenAeliminarObj.ruta_original,
                    identificador: identificador,
                    totalEliminadas: globalThis.imagenesEliminadasProcesoStorage.length,
                    contenidoStorage: globalThis.imagenesEliminadasProcesoStorage
                });
            }
        } else {
            console.warn('[confirmarEliminarImagenProceso]  Imagen sin ID ni ruta_original, no se pudo guardar:', imagenAeliminarObj);
        }
        
        // AHORA marcar como null
        globalThis.imagenesProcesoExistentes[indice - 1] = null;
        const imagenesParaEnviar = globalThis.imagenesProcesoExistentes.filter(img => img !== null && img !== undefined && img !== '');
        console.log('[confirmarEliminarImagenProceso]  Imagen existente marcada como eliminada:', {
            indice: indice - 1,
            imagenesRestantes: imagenesParaEnviar.length,
            storageLlenado: globalThis.imagenesEliminadasProcesoStorage,
            globalThisImagenesProcesoExistentes: globalThis.imagenesProcesoExistentes,
            imagenesParaEnviar: imagenesParaEnviar
        });
    } else {
        // Combinar imágenes existentes + nuevas que quedan
        let imagenesParaEnviar = [];
        if (globalThis.imagenesProcesoExistentes) {
            imagenesParaEnviar = globalThis.imagenesProcesoExistentes.filter(img => img !== null && img !== undefined && img !== '');
        }
        if (globalThis.imagenesProcesoActual) {
            globalThis.imagenesProcesoActual.forEach(img => {
                if (img instanceof File) imagenesParaEnviar.push(img);
            });
        }
        console.log('[confirmarEliminarImagenProceso] Imágenes restantes:', imagenesParaEnviar.length);
    }
    
    // Registrar cambio en editor de procesos
    if (globalThis.procesosEditor) {
        let imagenesParaRegistrar = [];
        if (globalThis.imagenesProcesoExistentes) {
            imagenesParaRegistrar = globalThis.imagenesProcesoExistentes.filter(img => img !== null && img !== undefined && img !== '');
        }
        if (globalThis.imagenesProcesoActual) {
            globalThis.imagenesProcesoActual.forEach(img => {
                if (img instanceof File) imagenesParaRegistrar.push(img);
            });
        }
        globalThis.procesosEditor.registrarCambioImagenes(imagenesParaRegistrar);
    }
    
    // Restaurar preview a estado vacío con estilo correcto
    if (preview) {
        preview.style.border = '2px dashed #0066cc';
        preview.style.background = '#f9fafb';
        preview.innerHTML = `
            <div class="placeholder-content" style="text-align: center;">
                <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Imagen ${indice}</div>
            </div>
        `;
        
        // Eliminar botón cuando se elimina la imagen
        const deleteBtn = preview.querySelector('.btn-eliminar-imagen-proceso');
        if (deleteBtn) {
            deleteBtn.remove();
        }
    }
    
    const input = document.getElementById(`proceso-foto-input-${indice}`);
    if (input) {
        input.value = '';
    }
};

// Eliminar imagen del proceso (ahora muestra modal de confirmación)
globalThis.eliminarImagenProceso = function(indice) {
    console.log('[eliminarImagenProceso]  INICIANDO - Click en botón eliminar para imagen:', indice);
    
    // Guardar el índice globalmente
    globalThis._imagenAEliminarIndice = indice;
    console.log('[eliminarImagenProceso]  globalThis._imagenAEliminarIndice establecido a:', globalThis._imagenAEliminarIndice);
    
    // Buscar el modal directamente
    const modal = document.getElementById('modal-confirmar-eliminar-imagen-proceso');
    console.log('[eliminarImagenProceso]  Modal encontrado?:', !!modal);
    
    if (modal) {
        console.log('[eliminarImagenProceso]  Modal existe, mostrando...');
        modal.style.display = 'flex';
        modal.style.zIndex = '999999999';
        console.log('[eliminarImagenProceso]  Modal mostrado con z-index:', modal.style.zIndex);
    } else {
        console.error('[eliminarImagenProceso]  MODAL NO ENCONTRADO - ID: modal-confirmar-eliminar-imagen-proceso');
        // Listar todos los modales disponibles
        console.log('[eliminarImagenProceso]  Modales disponibles en el DOM:');
        document.querySelectorAll('.modal-overlay').forEach((m, idx) => {
            console.log(`  [${idx}] ID: ${m.id}, Display: ${m.style.display}`);
        });
    }
};

// Limpiar todas las imágenes del proceso
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
    
    //  CRÍTICO: Limpiar arrays globales para evitar contaminación
    if (globalThis.imagenesProcesoActual) {
        globalThis.imagenesProcesoActual = [null, null, null];
        console.log('[limpiarImagenesProceso]  globalThis.imagenesProcesoActual limpiado');
    }
    
    if (globalThis.imagenesProcesoExistentes) {
        globalThis.imagenesProcesoExistentes = [];
        console.log('[limpiarImagenesProceso]  globalThis.imagenesProcesoExistentes limpiado');
    }
    
    //  NUEVO: Limpiar también el storage universal si hay un índice activo
    if (globalThis.universalImagenesStorage && globalThis.procesoActualIndex !== undefined) {
        console.log(`[limpiarImagenesProceso]  Limpiando storage universal de PROCESOS[${globalThis.procesoActualIndex}]`);
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

// Agregar ubicación a la lista
globalThis.agregarUbicacionProceso = function() {
    const input = document.getElementById('input-ubicacion-nueva');
    const ubicacion = input?.value?.trim();
    
    if (!ubicacion) {

        return;
    }
    
    // Evitar duplicados
    if (globalThis.ubicacionesProcesoSeleccionadas.includes(ubicacion)) {

        return;
    }
    
    // Agregar a la lista
    globalThis.ubicacionesProcesoSeleccionadas.push(ubicacion);

    
    // Limpiar input
    input.value = '';
    
    // Renderizar lista
    globalThis.renderizarListaUbicaciones();
};

// Remover ubicación de la lista
globalThis.removerUbicacionProceso = function(ubicacion) {
    // Si es objeto, comparar por ubicacion.ubicacion
    if (typeof ubicacion === 'object' && ubicacion.ubicacion) {
        globalThis.ubicacionesProcesoSeleccionadas = globalThis.ubicacionesProcesoSeleccionadas.filter(u => {
            if (typeof u === 'object') return u.ubicacion !== ubicacion.ubicacion;
            return u !== ubicacion.ubicacion;
        });
    } else {
        // Si es string
        globalThis.ubicacionesProcesoSeleccionadas = globalThis.ubicacionesProcesoSeleccionadas.filter(u => {
            if (typeof u === 'object') return u.ubicacion !== ubicacion;
            return u !== ubicacion;
        });
    }

    globalThis.renderizarListaUbicaciones();
};

// Renderizar la lista de ubicaciones
globalThis.renderizarListaUbicaciones = function() {
    const container = document.getElementById('lista-ubicaciones-proceso');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (globalThis.ubicacionesProcesoSeleccionadas.length === 0) {
        container.innerHTML = '<small style="color: #9ca3af;">Escribe una ubicación y haz click en "+" para agregarla</small>';
        return;
    }
    
    globalThis.ubicacionesProcesoSeleccionadas.forEach((ubicacion, idx) => {
        const tag = document.createElement('div');
        
        // Determinar si es objeto con descripcion o solo string
        let ubicacionTexto = '';
        let ubicacionKey = '';
        
        if (typeof ubicacion === 'object' && ubicacion.ubicacion) {
            ubicacionTexto = ubicacion.ubicacion;
            ubicacionKey = JSON.stringify(ubicacion); // Para comparación en remover
            
            // Si tiene descripción, mostrar expandido
            if (ubicacion.descripcion) {
                const desc = ubicacion.descripcion.substring(0, 80) + (ubicacion.descripcion.length > 80 ? '...' : '');
                tag.style.cssText = 'display: flex; gap: 0.75rem; background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: 6px; font-size: 0.875rem; flex-direction: column;';
                tag.innerHTML = `
                    <div style="display: flex; align-items: flex-start; gap: 0.5rem; justify-content: space-between;">
                        <div style="flex: 1;">
                            <strong style="display: block; margin-bottom: 0.25rem;">${ubicacionTexto}</strong>
                            <small style="color: #4b7c0f;">${desc}</small>
                        </div>
                        <button type="button" style="background: none; border: none; color: inherit; cursor: pointer; padding: 0; font-size: 1rem; display: flex; align-items: center; flex-shrink: 0;">
                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">close</span>
                        </button>
                    </div>
                `;
                // Agregar evento sin usar onclick inline
                const btnClose = tag.querySelector('button');
                btnClose.addEventListener('click', () => removerUbicacionProceso(ubicacionKey));
            } else {
                tag.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem;';
                tag.innerHTML = `
                    <span>${ubicacionTexto}</span>
                    <button type="button" style="background: none; border: none; color: inherit; cursor: pointer; padding: 0; font-size: 1rem; display: flex; align-items: center;">
                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">close</span>
                    </button>
                `;
                // Agregar evento sin usar onclick inline
                const btnClose = tag.querySelector('button');
                btnClose.addEventListener('click', () => removerUbicacionProceso(ubicacionKey));
            }
        } else {
            // Es un string simple
            ubicacionTexto = ubicacion;
            ubicacionKey = ubicacion;
            tag.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem;';
            tag.innerHTML = `
                <span>${ubicacionTexto}</span>
                <button type="button" style="background: none; border: none; color: inherit; cursor: pointer; padding: 0; font-size: 1rem; display: flex; align-items: center;">
                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">close</span>
                </button>
            `;
            // Agregar evento sin usar onclick inline
            const btnClose = tag.querySelector('button');
            btnClose.addEventListener('click', () => removerUbicacionProceso(ubicacionKey));
        }
        
        container.appendChild(tag);
    });
};

// Aplicar proceso para TODAS las tallas (de la prenda)
globalThis.aplicarProcesoParaTodasTallas = function() {

    
    // Obtener las tallas registradas de la prenda actual (con cantidades)
    const tallasPrendaConCantidades = obtenerTallasDeLaPrenda();
    
    // Extraer solo los nombres para UI (como arrays)
    const tallasPrendaArrays = {
        dama: Object.keys(tallasPrendaConCantidades.dama || {}),
        caballero: Object.keys(tallasPrendaConCantidades.caballero || {}),
        sobremedida: tallasPrendaConCantidades.sobremedida || null
    };
    
    // Si hay sobremedida, permitir aplicar
    const hayTallasNormales = tallasPrendaArrays.dama.length > 0 || tallasPrendaArrays.caballero.length > 0;
    const haySobremedida = tallasPrendaArrays.sobremedida !== null;
    
    if (!hayTallasNormales && !haySobremedida) {
        // No hay tallas ni sobremedida - mostrar modal de advertencia
        mostrarModalAdvertenciaTallas();
        return;
    }
    
    // Para UI, usamos arrays (nombres de tallas) o sobremedida
    globalThis.tallasSeleccionadasProceso = {
        dama: tallasPrendaArrays.dama,
        caballero: tallasPrendaArrays.caballero,
        sobremedida: tallasPrendaArrays.sobremedida
    };
    
    //  IMPORTANTE: Copiar TODAS las cantidades de la prenda al proceso
    // Esto hace que "Aplicar para todas" asigne las cantidades completas de la prenda
    globalThis.tallasCantidadesProceso = {
        dama: { ...tallasPrendaConCantidades.dama },
        caballero: { ...tallasPrendaConCantidades.caballero },
        sobremedida: tallasPrendaConCantidades.sobremedida || {}
    };
    
    console.log(' [aplicarProcesoParaTodasTallas] Copiadas todas las tallas de la prenda al proceso:', {
        tallasCantidadesProceso: globalThis.tallasCantidadesProceso,
        tallasSeleccionadas: globalThis.tallasSeleccionadasProceso
    });

    actualizarResumenTallasProceso();
};

// helpers internos para obtención de tallas
function _acumularTalla(tallas, genero, key, cantidad) {
    if (genero === 'sobremedida') {
        tallas.sobremedida = tallas.sobremedida || {};
        tallas.sobremedida[key] = (tallas.sobremedida[key] || 0) + cantidad;
    } else {
        tallas[genero][key] = (tallas[genero][key] || 0) + cantidad;
    }
}

function _extraerColoresDeCelda(colorCell) {
    if (!colorCell) return [];

    const colorDiv = colorCell.querySelector('div');
    if (!colorDiv) return [];

    const colores = [];
    colorDiv.querySelectorAll('span').forEach(span => {
        let colorText = span.textContent;
        if (!colorText) return;

        colorText = colorText.replace(/[\s\n\r\t]+/g, ' ').trim();
        if (!colorText) return;

        let colorLimpio = colorText.split('(')[0].trim();
        if (!colorLimpio || colorLimpio === colorText) {
            colorLimpio = colorText.replace(/\s*\(\d+\)\s*/g, '').trim();
        }

        if (colorLimpio && !colores.includes(colorLimpio)) {
            colores.push(colorLimpio);
        }
    });

    return colores;
}

function _leerTablaConTallas(tablaBody, normalizarGenero) {
    const tallasConColor = { dama: {}, caballero: {}, sobremedida: null };
    const tallasSinColor = { dama: {}, caballero: {}, sobremedida: null };
    const filas = tablaBody.querySelectorAll('tr[data-tipo="wizard"]');
    let totalFilas = 0;
    let conColorFilas = 0;

    filas.forEach(fila => {
        const generoRaw = fila.querySelector('[data-field="genero"]')?.textContent.trim();
        const tallaText = fila.querySelector('[data-field="talla"]')?.textContent.trim();
        const cantidadText = fila.querySelector('[data-field="cantidad"]')?.textContent.trim();
        if (!generoRaw || !tallaText || !cantidadText) return;

        const genero = normalizarGenero(generoRaw);
        if (!genero) return;

        const cantidad = parseInt(cantidadText, 10) || 0;
        if (cantidad <= 0) return;

        totalFilas += 1;
        const colores = _extraerColoresDeCelda(fila.querySelector('[data-field="color"]'));
        const keyBase = tallaText;

        if (colores.length > 0) {
            conColorFilas += 1;
            colores.forEach(color => {
                _acumularTalla(tallasConColor, genero, `${keyBase}__${color}`, cantidad);
            });
        } else {
            _acumularTalla(tallasSinColor, genero, keyBase, cantidad);
        }
    });

    return { tallasConColor, tallasSinColor, totalFilas, conColorFilas };
}

function _tieneTallas(tallas) {
    return Boolean(
        Object.keys(tallas.dama || {}).length ||
        Object.keys(tallas.caballero || {}).length ||
        Object.keys(tallas.sobremedida || {}).length
    );
}

function _obtenerAsignacionesColores() {
    const datosColores = globalThis.ColoresPorTalla?.datos;
    if (datosColores && typeof datosColores === 'object' && Object.keys(datosColores).length > 0) {
        return { fuente: 'ColoresPorTalla.datos', asignaciones: datosColores };
    }

    const asignacionesState = globalThis.StateManager?.getAsignaciones?.();
    if (asignacionesState && typeof asignacionesState === 'object' && Object.keys(asignacionesState).length > 0) {
        return { fuente: 'StateManager.getAsignaciones()', asignaciones: asignacionesState };
    }

    return { fuente: null, asignaciones: null };
}

function _extraerTallasDeAsignaciones(asignaciones, normalizarGenero) {
    const tallas = { dama: {}, caballero: {}, sobremedida: null };

    Object.values(asignaciones).forEach(asignacion => {
        const genero = normalizarGenero(asignacion?.genero);
        if (!genero) return;

        const talla = String(asignacion?.talla || '').trim().toUpperCase();
        if (!talla) return;

        const colores = Array.isArray(asignacion?.colores) ? asignacion.colores : [];
        colores.forEach(c => {
            const color = String(c?.nombre || '').trim().toUpperCase();
            const cantidad = parseInt(c?.cantidad, 10) || 0;
            if (!color || cantidad <= 0) return;

            _acumularTalla(tallas, genero, `${talla}__${color}`, cantidad);
        });
    });

    return tallas;
}

function _obtenerTallasDeLaPrendaDesdeRelacionales() {
    const tallasRelacionales = globalThis.tallasRelacionales || { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
    const hay = Object.values(tallasRelacionales).some(g => g && typeof g === 'object' && Object.keys(g).length > 0);
    if (!hay) return null;

    const tallas = { dama: {}, caballero: {}, sobremedida: null };

    if (Object.keys(tallasRelacionales.DAMA || {}).length > 0) {
        tallas.dama = { ...tallasRelacionales.DAMA };
    }
    if (Object.keys(tallasRelacionales.CABALLERO || {}).length > 0) {
        tallas.caballero = { ...tallasRelacionales.CABALLERO };
    }
    if (Object.keys(tallasRelacionales.SOBREMEDIDA || {}).length > 0) {
        tallas.sobremedida = { ...tallasRelacionales.SOBREMEDIDA };
    }
    if (Object.keys(tallasRelacionales.UNISEX || {}).length > 0) {
        tallas.sobremedida = { ...(tallas.sobremedida || {}), ...tallasRelacionales.UNISEX };
    }

    return tallas;
}

// Obtener tallas registradas en la prenda del modal
function obtenerTallasDeLaPrenda() {
    console.log('[obtenerTallasDeLaPrenda]  INICIANDO - buscando FUENTE DE VERDAD');

    const normalizarGenero = (generoRaw) => {
        const g = String(generoRaw || '').trim().toLowerCase();
        if (!g) return null;
        if (g === 'dama' || g.startsWith('dam')) return 'dama';
        if (g === 'caballero' || g.startsWith('cab')) return 'caballero';
        if (g === 'unisex' || g.startsWith('uni')) return 'sobremedida';
        return null;
    };

    const tablaBody = document.getElementById('tabla-resumen-asignaciones-cuerpo');
    const tablaInfo = tablaBody ? _leerTablaConTallas(tablaBody, normalizarGenero) : null;

    if (tablaInfo?.conColorFilas > 0) {
        console.log('[obtenerTallasDeLaPrenda]  FUENTE 1 USADA: Tabla HTML (CON COLORES - Fuente Definitiva)');
        return tablaInfo.tallasConColor;
    }

    const { fuente, asignaciones } = _obtenerAsignacionesColores();
    if (asignaciones) {
        console.log(`[obtenerTallasDeLaPrenda]  FUENTE 2 USADA: ${fuente}`);
        return _extraerTallasDeAsignaciones(asignaciones, normalizarGenero);
    }

    if (tablaInfo?.totalFilas > 0 && _tieneTallas(tablaInfo.tallasSinColor)) {
        console.log('[obtenerTallasDeLaPrenda]  FUENTE 3 USADA: Tabla HTML (SIN COLORES - fallback)');
        return tablaInfo.tallasSinColor;
    }

    const rel = _obtenerTallasDeLaPrendaDesdeRelacionales();
    if (rel) {
        console.log('[obtenerTallasDeLaPrenda]  FUENTE 4 USADA: tallasRelacionales (legacy)');
        return rel;
    }

    if (globalThis.cantidadSoloSeleccionada) {
        console.log('[obtenerTallasDeLaPrenda]  FUENTE 5 USADA: cantidadSoloSeleccionada');
        return { dama: {}, caballero: {}, sobremedida: { 'UNISEX': globalThis.cantidadSoloSeleccionada } };
    }

    console.log('[obtenerTallasDeLaPrenda]  NINGUNA FUENTE disponible - retornando vacío');
    return { dama: {}, caballero: {}, sobremedida: null };
}

// Mostrar modal de advertencia cuando no hay tallas seleccionadas
function mostrarModalAdvertenciaTallas() {
    const html = `
        <div style="text-align: center; padding: 2rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;"></div>
            <h3 style="color: #dc2626; margin-bottom: 1rem;">Sin Tallas Seleccionadas</h3>
            <p style="color: #6b7280; margin-bottom: 1.5rem; line-height: 1.6;">
                Debes seleccionar al menos una talla y su cantidad en la prenda 
                antes de aplicar el proceso.
            </p>
            <p style="color: #6b7280; margin-bottom: 2rem; font-size: 0.875rem;">
                Agrega tallas en la sección "TALLAS Y CANTIDADES" del formulario.
            </p>
            <button type="button" class="btn btn-primary" onclick="cerrarModalAdvertencia()" style="padding: 0.75rem 2rem;">
                <span class="material-symbols-rounded">check</span>Entendido
            </button>
        </div>
    `;
    
    // Crear modal temporal
    const modal = document.createElement('div');
    modal.id = 'modal-advertencia-tallas';
    modal.className = 'modal-overlay';
    modal.style.zIndex = '100002';
    modal.innerHTML = `
        <div class="modal-container modal-sm">
            <div class="modal-header" style="background: #fef2f2; border-bottom: 2px solid #fecaca;">
                <h3 class="modal-title" style="color: #dc2626;">
                    <span class="material-symbols-rounded">warning</span>Advertencia
                </h3>
                <button class="modal-close-btn" onclick="cerrarModalAdvertencia()">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="modal-body">
                ${html}
            </div>
        </div>
    `;
    
    console.log('[MODAL-ADVERTENCIA-TALLAS] Creando modal');
    
    // Agregar seguro a getComputedStyle
    try {
        const swal2Container = document.querySelector('.swal2-container');
        if (swal2Container) {
            console.log('[MODAL-ADVERTENCIA-TALLAS] z-index swal2:', globalThis.getComputedStyle(swal2Container).zIndex);
        }
    } catch (e) {
        console.log('[MODAL-ADVERTENCIA-TALLAS] Sin swal2-container activo');
    }
    
    document.body.appendChild(modal);
    modal.style.display = 'flex';
    
    // Forzar z-index máximo para asegurar que esté encima de todo
    setTimeout(() => {
        modal.style.setProperty('z-index', '9999999999', 'important');
        console.log('[MODAL-ADVERTENCIA-TALLAS] Modal visible');
    }, 10);
}

// Cerrar modal de advertencia
globalThis.cerrarModalAdvertencia = function() {
    const modal = document.getElementById('modal-advertencia-tallas');
    if (modal) {
        modal.remove();
    }
};

// Abrir editor de tallas específicas
globalThis.abrirEditorTallasEspecificas = function() {

    
    const modalEditor = document.getElementById('modal-editor-tallas');
    if (!modalEditor) {

        return;
    }
    
    // Obtener tallas registradas en la prenda (retorna objetos {talla: cantidad} o {talla__color: cantidad})
    const tallasPrenda = obtenerTallasDeLaPrenda();
    
    // Validar que haya tallas seleccionadas - son OBJETOS, no arrays
    const tallasDamaArray = Object.keys(tallasPrenda.dama || {});
    const tallasCaballeroArray = Object.keys(tallasPrenda.caballero || {});
    const haySobremedida = tallasPrenda.sobremedida !== null;
    
    if (tallasDamaArray.length === 0 && tallasCaballeroArray.length === 0 && !haySobremedida) {
        mostrarModalAdvertenciaTallas();
        return;
    }
    


    
    // Renderizar tallas DAMA (solo las seleccionadas en la prenda)
    const containerDama = document.getElementById('tallas-dama-container');
    if (containerDama) {
        containerDama.innerHTML = '';
        containerDama.style.display = 'grid';
        containerDama.style.gridTemplateColumns = 'repeat(auto-fill, minmax(240px, 1fr))';
        containerDama.style.gap = '0.75rem';
        
        if (tallasDamaArray.length === 0) {
            containerDama.innerHTML = '<p style="color: #9ca3af; font-size: 0.875rem;">No hay tallas DAMA seleccionadas en la prenda</p>';
        } else {
            tallasDamaArray.forEach(tallaKey => {
                const isSelected = globalThis.tallasSeleccionadasProceso.dama.includes(tallaKey);
                const cantidadPrenda = tallasPrenda.dama[tallaKey] || 0;
                const cantidadProceso = globalThis.tallasCantidadesProceso?.dama?.[tallaKey] || 0;

                const parts = String(tallaKey).split('__');
                const tallaDisplay = (parts[0] || tallaKey);
                const colorDisplay = (parts[1] || null);
                const etiquetaDisplay = colorDisplay ? `${tallaDisplay} - ${colorDisplay}` : tallaDisplay;
                
                // Calcular cuánto está asignado en otros procesos
                const { totalAsignado, procesosDetalle } = calcularCantidadAsignadaOtrosProcesos(tallaKey, 'dama', procesoActual);
                const cantidadDisponible = cantidadPrenda - totalAsignado;
                
                const label = document.createElement('label');
                label.className = 'talla-checkbox-editor';
                label.innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.2s; background: #ffffff;">
                        <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                            <input type="checkbox" value="${tallaKey}" ${isSelected ? 'checked' : ''} class="form-checkbox" data-genero="dama" style="cursor: pointer; margin-top: 0.2rem;">
                            <div style="min-width: 0;">
                                <div style="font-weight: 800; color: #111827; line-height: 1.2; word-break: break-word;">${etiquetaDisplay}</div>
                                <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.2rem;">
                                    ${procesosDetalle.length > 0 ? `
                                        Asignados: <strong style="color: #dc2626;">${totalAsignado}</strong>
                                    ` : `
                                        Disponible: <strong>${cantidadDisponible}</strong>
                                    `}
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                            <div style="font-size: 0.75rem; color: #9ca3af; white-space: nowrap;">Cantidad del proceso</div>
                            <input type="number" 
                                value="${cantidadProceso}" 
                                data-talla="${tallaKey}"
                                data-genero="dama"
                                data-max="${cantidadDisponible}"
                                onchange="actualizarCantidadTallaProceso(this)"
                                placeholder="0"
                                style="width: 88px; padding: 0.35rem 0.5rem; border: 1px solid #be185d; border-radius: 8px; text-align: center; font-weight: 800; background: #fce7f3; color: #be185d;"
                                min="0"
                                max="${cantidadDisponible}">
                    </div>
                `;
                label.style.cssText = 'display: block; cursor: pointer; user-select: none;';
                
                // Agregar información sobre procesos previos si existen
                if (procesosDetalle.length > 0) {
                    const infoDiv = document.createElement('div');
                    infoDiv.style.cssText = 'font-size: 0.8rem; color: #6b7280; margin-left: 2.5rem; padding: 0.5rem; background: #f9fafb; border-radius: 4px;';
                    infoDiv.innerHTML = `
                        <strong style="color: #dc2626;"> Ya asignadas:</strong><br>
                        ${procesosDetalle.map(p => `${p.nombre}: <strong>${p.cantidad}</strong>`).join('<br>')}
                    `;
                    label.appendChild(infoDiv);
                }
                
                containerDama.appendChild(label);
            });
        }
    }
    
    // Renderizar tallas CABALLERO (solo las seleccionadas en la prenda)
    const containerCaballero = document.getElementById('tallas-caballero-container');
    if (containerCaballero) {
        containerCaballero.innerHTML = '';
        containerCaballero.style.display = 'grid';
        containerCaballero.style.gridTemplateColumns = 'repeat(auto-fill, minmax(240px, 1fr))';
        containerCaballero.style.gap = '0.75rem';
        
        if (tallasCaballeroArray.length === 0) {
            containerCaballero.innerHTML = '<p style="color: #9ca3af; font-size: 0.875rem;">No hay tallas CABALLERO seleccionadas en la prenda</p>';
        } else {
            tallasCaballeroArray.forEach(tallaKey => {
                const isSelected = globalThis.tallasSeleccionadasProceso.caballero.includes(tallaKey);
                const cantidadPrenda = tallasPrenda.caballero[tallaKey] || 0;
                const cantidadProceso = globalThis.tallasCantidadesProceso?.caballero?.[tallaKey] || 0;

                const parts = String(tallaKey).split('__');
                const tallaDisplay = (parts[0] || tallaKey);
                const colorDisplay = (parts[1] || null);
                const etiquetaDisplay = colorDisplay ? `${tallaDisplay} - ${colorDisplay}` : tallaDisplay;
                
                // Calcular cuánto está asignado en otros procesos
                const { totalAsignado, procesosDetalle } = calcularCantidadAsignadaOtrosProcesos(tallaKey, 'caballero', procesoActual);
                const cantidadDisponible = cantidadPrenda - totalAsignado;
                
                const label = document.createElement('label');
                label.className = 'talla-checkbox-editor';
                label.innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.2s; background: #ffffff;">
                        <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                            <input type="checkbox" value="${tallaKey}" ${isSelected ? 'checked' : ''} class="form-checkbox" data-genero="caballero" style="cursor: pointer; margin-top: 0.2rem;">
                            <div style="min-width: 0;">
                                <div style="font-weight: 800; color: #111827; line-height: 1.2; word-break: break-word;">${etiquetaDisplay}</div>
                                <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.2rem;">
                                    ${procesosDetalle.length > 0 ? `
                                        Asignados: <strong style="color: #dc2626;">${totalAsignado}</strong>
                                    ` : `
                                        Disponible: <strong>${cantidadDisponible}</strong>
                                    `}
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                            <div style="font-size: 0.75rem; color: #9ca3af; white-space: nowrap;">Cantidad del proceso</div>
                            <input type="number" 
                                value="${cantidadProceso}" 
                                data-talla="${tallaKey}"
                                data-genero="caballero"
                                data-max="${cantidadDisponible}"
                                onchange="actualizarCantidadTallaProceso(this)"
                                placeholder="0"
                                style="width: 88px; padding: 0.35rem 0.5rem; border: 1px solid #1d4ed8; border-radius: 8px; text-align: center; font-weight: 800; background: #dbeafe; color: #1d4ed8;"
                                min="0"
                                max="${cantidadDisponible}">
                    </div>
                `;
                label.style.cssText = 'display: block; cursor: pointer; user-select: none;';
                
                // Agregar información sobre procesos previos si existen
                if (procesosDetalle.length > 0) {
                    const infoDiv = document.createElement('div');
                    infoDiv.style.cssText = 'font-size: 0.8rem; color: #6b7280; margin-left: 2.5rem; padding: 0.5rem; background: #f9fafb; border-radius: 4px;';
                    infoDiv.innerHTML = `
                        <strong style="color: #dc2626;"> Ya asignadas:</strong><br>
                        ${procesosDetalle.map(p => `${p.nombre}: <strong>${p.cantidad}</strong>`).join('<br>')}
                    `;
                    label.appendChild(infoDiv);
                }
                
                containerCaballero.appendChild(label);
            });
        }
    }
    
    // Mostrar modal editor
    modalEditor.style.display = 'flex';
    
    //  DIAGNÓSTICO Z-INDEX
    console.log('📌 [EDITOR-TALLAS] Abriendo modal de edición de tallas...');
    console.log('📌 [EDITOR-TALLAS] Z-index INICIAL (style.zIndex):', modalEditor.style.zIndex || 'NO DEFINIDO');
    console.log('📌 [EDITOR-TALLAS] Z-index COMPUTADO (getComputedStyle):', globalThis.getComputedStyle(modalEditor).zIndex);
    
    // Obtener z-index del modal principal
    const modalPrincipal = document.getElementById('modal-proceso-generico');
    if (modalPrincipal) {
        console.log('📌 [EDITOR-TALLAS] Z-index MODAL PRINCIPAL (style):', modalPrincipal.style.zIndex || 'NO DEFINIDO');
        console.log('📌 [EDITOR-TALLAS] Z-index MODAL PRINCIPAL (computed):', globalThis.getComputedStyle(modalPrincipal).zIndex);
    }
    
    // Forzar z-index aún más alto
    const zIndexEditorActual = parseInt(globalThis.getComputedStyle(modalEditor).zIndex) || 100002;
    const zIndexPrincipalActual = parseInt(globalThis.getComputedStyle(modalPrincipal).zIndex) || 999999999;
    const nuevoZIndexEditor = zIndexPrincipalActual + 1;
    
    console.log('📌 [EDITOR-TALLAS] Z-index EDITOR actual:', zIndexEditorActual);
    console.log('📌 [EDITOR-TALLAS] Z-index PRINCIPAL actual:', zIndexPrincipalActual);
    console.log('📌 [EDITOR-TALLAS] ASIGNANDO nuevo Z-index al editor:', nuevoZIndexEditor);
    
    // Aplicar z-index forzado
    modalEditor.style.zIndex = nuevoZIndexEditor.toString();
    console.log(' [EDITOR-TALLAS] Z-index FORZADO a:', modalEditor.style.zIndex);
    console.log(' [EDITOR-TALLAS] Z-index VERIFICADO (getComputedStyle):', globalThis.getComputedStyle(modalEditor).zIndex);
    
    // Verificar contexto de apilamiento
    console.log('📌 [EDITOR-TALLAS] CONTEXTO DE APILAMIENTO:');
    console.log('   - Modal Principal display:', globalThis.getComputedStyle(modalPrincipal).display);
    console.log('   - Modal Principal position:', globalThis.getComputedStyle(modalPrincipal).position);
    console.log('   - Editor display:', globalThis.getComputedStyle(modalEditor).display);
    console.log('   - Editor position:', globalThis.getComputedStyle(modalEditor).position);
    
    // Listar todos los elementos con z-index alto en la página
    console.log('📌 [EDITOR-TALLAS] ELEMENTOS CON Z-INDEX ALTO:');
    document.querySelectorAll('[style*="z-index"], [class*="modal"], [class*="overlay"]').forEach((el, idx) => {
        const zIdx = globalThis.getComputedStyle(el).zIndex;
        if (zIdx && zIdx !== 'auto' && parseInt(zIdx) > 100) {
            console.log(`   ${idx}. ${el.id || el.className || el.tagName} - Z-index: ${zIdx}, Display: ${globalThis.getComputedStyle(el).display}`);
        }
    });

};

// Calcular cantidad ya asignada en OTROS procesos para una talla
function calcularCantidadAsignadaOtrosProcesos(talla, generoKey, procesoActualExcluir) {
    let totalAsignado = 0;
    const procesosDetalle = [];
    
    // Recorrer TODOS los procesos
    if (globalThis.procesosSeleccionados) {
        Object.entries(globalThis.procesosSeleccionados).forEach(([tipoProceso, datosProc]) => {
            // Excluir el proceso actual
            if (tipoProceso === procesoActualExcluir) {
                return;
            }
            
            if (datosProc?.datos?.tallas) {
                const generoTallas = datosProc.datos.tallas[generoKey] || {};
                const cantidadEnEsteProceso = generoTallas[talla] || 0;
                
                if (cantidadEnEsteProceso > 0) {
                    totalAsignado += cantidadEnEsteProceso;
                    procesosDetalle.push({
                        nombre: tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1),
                        cantidad: cantidadEnEsteProceso
                    });
                }
            }
        });
    }
    
    return { totalAsignado, procesosDetalle };
}

// Actualizar cantidad de talla en el modal de proceso
globalThis.actualizarCantidadTallaProceso = function(input) {
    const genero = input.dataset.genero;
    const talla = input.dataset.talla;
    const cantidad = parseInt(input.value) || 0;
    
    // Obtener la cantidad máxima disponible en la prenda
    const tallasPrenda = obtenerTallasDeLaPrenda();
    const generoKey = genero.toLowerCase();
    const cantidadDisponibleEnPrenda = tallasPrenda[generoKey]?.[talla] || 0;
    
    //  LÓGICA CORREGIDA: Las mismas prendas pueden recibir MÚLTIPLES procesos
    // NO hay límite entre procesos. Solo validamos contra la cantidad total de la prenda.
    // Ejemplo: 20 camisas talla S pueden tener:
    //   - 10 con Bordado
    //   - 15 con Estampado (son las MISMAS u OTRAS camisas, lo importante es que NO superen 20 total)
    
    console.log(` [actualizarCantidadTallaProceso] Validación para ${talla}/${genero}:`, {
        cantidadIntentada: cantidad,
        cantidadDisponibleEnPrenda: cantidadDisponibleEnPrenda,
        procesoActual: procesoActual,
        nota: 'Sin límite entre procesos - mismas prendas pueden recibir múltiples procesos'
    });
    
    // VALIDACIÓN: Solo permitir que NO supere la cantidad total de la prenda
    if (cantidad > cantidadDisponibleEnPrenda) {
        console.warn(` [actualizarCantidadTallaProceso] Cantidad ${cantidad} supera disponible en PRENDA ${cantidadDisponibleEnPrenda}`);
        
        // Mostrar error INLINE en rojo debajo del input
        input.style.borderColor = '#dc2626';
        input.style.backgroundColor = '#fee2e2';
        
        // Buscar el label padre que contiene 
        const label = input.closest('label');
        console.log(' [ERROR-CSS] Label encontrado:', !!label);
        
        // Buscar o crear wrapper para mantener el grid ordenado
        let wrapper = label?.closest('.talla-error-wrapper');
        console.log(' [ERROR-CSS] Wrapper existente:', !!wrapper);
        
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'talla-error-wrapper';
            wrapper.style.cssText = 'display: contents;';
            
            if (label?.parentNode) {
                // Reemplazar label con wrapper en el DOM
                label.parentNode.insertBefore(wrapper, label);
                // Meter label dentro del wrapper
                wrapper.appendChild(label);
                console.log(' [ERROR-CSS] Wrapper CREADO y label MOVIDO dentro');
            }
        }
        
        // Buscar o crear elemento de error dentro del wrapper
        let errorDiv = wrapper?.querySelector('.error-cantidad');
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-cantidad';
            errorDiv.style.cssText = 'color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem; font-weight: 600; padding: 0 0.5rem; width: 100%; display: block;';
            if (wrapper) {
                wrapper.appendChild(errorDiv);
                console.log(' [ERROR-CSS] ErrorDiv CREADO dentro del wrapper');
            }
        }
        
        console.log(' [ERROR-CSS] ErrorDiv después de crear:');
        console.log('   - Existe:', !!errorDiv);
        console.log('   - Display (style):', errorDiv.style.display);
        console.log('   - Display (computed):', globalThis.getComputedStyle(errorDiv).display);
        
        errorDiv.textContent = ` Máximo: ${cantidadDisponibleEnPrenda} unidades`;
        errorDiv.style.display = 'block';
        
        console.log(' [ERROR-CSS] Mensaje asignado');
        
        // Limpiar el campo (dejar en 0)
        input.value = 0;
        return;
        
      
    } else {
        // Limpiar error si la cantidad es válida
        input.style.borderColor = '#be185d';
        input.style.backgroundColor = '#fce7f3';
        let errorDiv = input.parentNode.querySelector('.error-cantidad');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }
    
    // Actualizar SOLO en la estructura de TALLAS DEL PROCESO
    // NO tocar globalThis.tallasRelacionales (que son las tallas de la PRENDA)
    const generoMinuscula = genero.toLowerCase();
    if (!globalThis.tallasCantidadesProceso[generoMinuscula]) {
        globalThis.tallasCantidadesProceso[generoMinuscula] = {};
    }
    
    if (cantidad > 0) {
        globalThis.tallasCantidadesProceso[generoMinuscula][talla] = cantidad;
    } else {
        // Si la cantidad es 0, eliminar la talla de las cantidades del proceso
        delete globalThis.tallasCantidadesProceso[generoMinuscula][talla];
    }
    
    // Limpiar estilos de error si la validación pasó
    input.style.borderColor = '';
    input.style.backgroundColor = '';
    
    console.log(' [actualizarCantidadTallaProceso] Actualizado en tallasCantidadesProceso:', {
        genero,
        talla,
        cantidad,
        cantidadDisponibleEnPrenda: cantidadDisponibleEnPrenda,
        estructuraActual: globalThis.tallasCantidadesProceso
    });
};

// Cerrar editor de tallas
globalThis.cerrarEditorTallas = function() {
    const modal = document.getElementById('modal-editor-tallas');
    if (modal) {
        console.log(' [EDITOR-TALLAS] Cerrando modal...');
        console.log(' [EDITOR-TALLAS] Z-index ANTES de cerrar:', globalThis.getComputedStyle(modal).zIndex);
        modal.style.display = 'none';
        console.log(' [EDITOR-TALLAS] Modal cerrado. Display:', globalThis.getComputedStyle(modal).display);
    }

};

// Guardar tallas seleccionadas desde el editor
globalThis.guardarTallasSeleccionadas = function() {

    console.log(' [guardarTallasSeleccionadas] INICIANDO guardado de tallas...');
    console.log(' [guardarTallasSeleccionadas] Proceso actual:', procesoActual);
    console.log(' [guardarTallasSeleccionadas] Modo:', modoActual);
    
    // Recopilar tallas DAMA
    const checksDama = document.querySelectorAll('input[data-genero="dama"]:checked');
    globalThis.tallasSeleccionadasProceso.dama = Array.from(checksDama).map(cb => cb.value);
    console.log(' [guardarTallasSeleccionadas] Tallas DAMA seleccionadas:', globalThis.tallasSeleccionadasProceso.dama);
    
    // Recopilar tallas CABALLERO
    const checksCaballero = document.querySelectorAll('input[data-genero="caballero"]:checked');
    globalThis.tallasSeleccionadasProceso.caballero = Array.from(checksCaballero).map(cb => cb.value);
    console.log(' [guardarTallasSeleccionadas] Tallas CABALLERO seleccionadas:', globalThis.tallasSeleccionadasProceso.caballero);
    console.log(' [guardarTallasSeleccionadas] Cantidades por talla (proceso):', globalThis.tallasCantidadesProceso);
    
    // IMPORTANTE: Actualizar el objeto del proceso con las tallas y cantidades
    // para que no pierda los datos cuando se cierre el modal
    if (procesoActual && globalThis.procesosSeleccionados[procesoActual]?.datos) {
        globalThis.procesosSeleccionados[procesoActual].datos.tallas = {
            dama: globalThis.tallasCantidadesProceso.dama || {},
            caballero: globalThis.tallasCantidadesProceso.caballero || {},
            sobremedida: globalThis.tallasCantidadesProceso.sobremedida || {}
        };
        
        console.log(` [guardarTallasSeleccionadas] Tallas guardadas en proceso "${procesoActual}":`, {
            tallas: globalThis.procesosSeleccionados[procesoActual].datos.tallas,
            tallasCantidadesProceso: globalThis.tallasCantidadesProceso
        });
    } else {
        console.warn(` [guardarTallasSeleccionadas] NO SE PUDO GUARDAR: procesoActual="${procesoActual}", procesosSeleccionados exists=${!!globalThis.procesosSeleccionados}`);
    }

    console.log('📌 [guardarTallasSeleccionadas] ESTADO ANTES DE CERRAR MODAL:');
    console.log('   - Modal editor display:', globalThis.getComputedStyle(document.getElementById('modal-editor-tallas')).display);
    console.log('   - Modal principal display:', globalThis.getComputedStyle(document.getElementById('modal-proceso-generico')).display);
    
    // Cerrar editor y actualizar resumen
    cerrarEditorTallas();
    
    console.log('📌 [guardarTallasSeleccionadas] ESTADO DESPUÉS DE CERRAR MODAL:');
    console.log('   - Modal editor display:', globalThis.getComputedStyle(document.getElementById('modal-editor-tallas')).display);
    console.log('   - Modal principal display:', globalThis.getComputedStyle(document.getElementById('modal-proceso-generico')).display);
    
    actualizarResumenTallasProceso();
    console.log(' [guardarTallasSeleccionadas] GUARDADO COMPLETADO');
};

// Actualizar resumen de tallas
globalThis.actualizarResumenTallasProceso = function() {
    console.log('[actualizarResumenTallasProceso] 🎬 Iniciando renderización de resumen...');
    
    const resumen = document.getElementById('proceso-tallas-resumen');
    console.log('[actualizarResumenTallasProceso]  Elemento resumen encontrado?:', !!resumen);
    
    if (!resumen) {
        console.warn('[actualizarResumenTallasProceso]  NO SE ENCONTRÓ elemento #proceso-tallas-resumen');
        return;
    }
    
    console.log('[actualizarResumenTallasProceso]  globalThis.tallasSeleccionadasProceso:', globalThis.tallasSeleccionadasProceso);
    console.log('[actualizarResumenTallasProceso]  globalThis.tallasCantidadesProceso:', globalThis.tallasCantidadesProceso);
    
    const totalTallas = globalThis.tallasSeleccionadasProceso.dama.length + globalThis.tallasSeleccionadasProceso.caballero.length;
    const haySobremedida = globalThis.tallasSeleccionadasProceso.sobremedida && Object.keys(globalThis.tallasSeleccionadasProceso.sobremedida).length > 0;
    console.log('[actualizarResumenTallasProceso] 📈 Total de tallas seleccionadas:', totalTallas, ' | Hay sobremedida:', haySobremedida);
    
    if (totalTallas === 0 && !haySobremedida) {
        console.log('[actualizarResumenTallasProceso]  No hay tallas ni sobremedida seleccionadas, mostrando placeholder');
        resumen.innerHTML = '<p style="color: #9ca3af;">Selecciona tallas donde aplicar el proceso</p>';
        return;
    }
    
    let html = '<div style="display: flex; flex-direction: column; gap: 0.75rem;">';
    
    // Obtener cantidades desde tallasCantidadesProceso (ESTRUCTURA DEL PROCESO, NO DE LA PRENDA)
    const tallasProceso = globalThis.tallasCantidadesProceso || { dama: {}, caballero: {} };
    console.log('[actualizarResumenTallasProceso]  tallasProceso para renderizar:', tallasProceso);

    const formatearTallaKey = (tallaKey) => {
        const parts = String(tallaKey).split('__');
        const talla = (parts[0] || tallaKey);
        const color = (parts[1] || null);
        return color ? `${talla} - ${color}` : talla;
    };
    
    if (globalThis.tallasSeleccionadasProceso.dama.length > 0) {
        console.log('[actualizarResumenTallasProceso] 👩 Renderizando DAMA:', globalThis.tallasSeleccionadasProceso.dama);
        const tallasDamaHTML = globalThis.tallasSeleccionadasProceso.dama.map(t => {
            const cantidad = tallasProceso.dama?.[t] || 0;
            console.log(`[actualizarResumenTallasProceso]  DAMA ${t}: cantidad=${cantidad}`);
            return `<span style="background: #fce7f3; color: #be185d; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem;">
                ${formatearTallaKey(t)}
                <span style="background: #be185d; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
            </span>`;
        }).join('');
        
        html += `
            <div>
                <strong style="color: #be185d; margin-bottom: 0.5rem; display: block;">
                    <i class="fas fa-female"></i> DAMA (${globalThis.tallasSeleccionadasProceso.dama.length})
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${tallasDamaHTML}
                </div>
            </div>
        `;
    }
    
    if (globalThis.tallasSeleccionadasProceso.caballero.length > 0) {
        console.log('[actualizarResumenTallasProceso] 👨 Renderizando CABALLERO:', globalThis.tallasSeleccionadasProceso.caballero);
        const tallasCaballeroHTML = globalThis.tallasSeleccionadasProceso.caballero.map(t => {
            const cantidad = tallasProceso.caballero?.[t] || 0;
            console.log(`[actualizarResumenTallasProceso]  CABALLERO ${t}: cantidad=${cantidad}`);
            return `<span style="background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem;">
                ${formatearTallaKey(t)}
                <span style="background: #1d4ed8; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
            </span>`;
        }).join('');
        
        html += `
            <div>
                <strong style="color: #1d4ed8; margin-bottom: 0.5rem; display: block;">
                    <i class="fas fa-male"></i> CABALLERO (${globalThis.tallasSeleccionadasProceso.caballero.length})
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${tallasCaballeroHTML}
                </div>
            </div>
        `;
    }
    
    // AGREGAR SOBREMEDIDA AL RESUMEN
    if (haySobremedida && globalThis.tallasCantidadesProceso.sobremedida) {
        console.log('[actualizarResumenTallasProceso] 📐 Renderizando SOBREMEDIDA:', globalThis.tallasCantidadesProceso.sobremedida);
        
        const sobremedidaHTML = Object.entries(globalThis.tallasCantidadesProceso.sobremedida).map(([genero, cantidad]) => {
            console.log(`[actualizarResumenTallasProceso]  SOBREMEDIDA ${genero}: ${cantidad}`);
            const colorMap = {
                'DAMA': { bg: '#fce7f3', text: '#be185d' },
                'CABALLERO': { bg: '#dbeafe', text: '#1d4ed8' },
                'UNISEX': { bg: '#f3e8ff', text: '#7c3aed' }
            };
            const colores = colorMap[genero] || { bg: '#e5e7eb', text: '#374151' };
            
            return `<span style="background: ${colores.bg}; color: ${colores.text}; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem;">
                ${genero}
                <span style="background: ${colores.text}; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
            </span>`;
        }).join('');
        
        html += `
            <div>
                <strong style="color: #0066cc; margin-bottom: 0.5rem; display: block;">
                    <span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle;">straighten</span> SOBREMEDIDA
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${sobremedidaHTML}
                </div>
            </div>
        `;
    }
    
    html += '</div>';
    console.log('[actualizarResumenTallasProceso]  HTML generado (length):', html.length);
    console.log('[actualizarResumenTallasProceso]  HTML preview:', html.substring(0, 200) + '...');
    
    resumen.innerHTML = html;
    console.log('[actualizarResumenTallasProceso]  HTML inyectado en DOM');
    console.log('[actualizarResumenTallasProceso]  innerHTML actual:', resumen.innerHTML.substring(0, 200));
};

// Agregar proceso al pedido
globalThis.agregarProcesoAlPedido = function() {
    if (!procesoActual) {
        alert('Error: no hay proceso seleccionado');
        return;
    }
    
    try {
        //  FIX CRÍTICO: El storage procesosImagenesStorage tiene estructura {_imagenes: {1: [], 2: [], 3: []}}
        // Las imágenes se guardan por índice numérico (1, 2, 3), NO por nombre de proceso
        console.log('[agregarProcesoAlPedido]  Buscando imágenes en globalThis.procesosImagenesStorage...');
        console.log('[agregarProcesoAlPedido] 📌 procesoActual:', procesoActual);
        console.log('[agregarProcesoAlPedido] 📌 procesoActualIndex:', globalThis.procesoActualIndex);
        console.log('[agregarProcesoAlPedido] 📌 globalThis.procesosImagenesStorage.obtenerImagenes:', typeof globalThis.procesosImagenesStorage?.obtenerImagenes);
        
        //  CRÍTICO: En modo EDICIÓN, usar imagenesExistentes (que ya tiene eliminadas marcadas como null)
        // PERO TAMBIÉN capturar imágenes NUEVAS de imagenesProcesoActual (agregadas después de eliminar)
        const imagenesExistentes = (globalThis.imagenesProcesoExistentes || []).filter(img => img !== null);
        
        let imagenesDelStorage = [];
        let imagenesNuevasAgregadas = [];
        
        // Solo obtener del storage si NO estamos en edición (creación de nuevo proceso)
        if (modoActual !== 'editar') {
            console.log('[agregarProcesoAlPedido]  Modo CREACIÓN: Buscando imágenes en storage UNIVERSAL de PROCESOS...');
            
            //  CORRECCIÓN: Usar storage universal separado por tipo
            if (globalThis.universalImagenesStorage && typeof globalThis.universalImagenesStorage.obtenerImagenes === 'function') {
                if (globalThis.procesoActualIndex !== undefined && globalThis.procesoActualIndex > 0) {
                    const imagenesEnIndice = globalThis.universalImagenesStorage.obtenerImagenes('procesos', globalThis.procesoActualIndex);
                    console.log(`[agregarProcesoAlPedido] 🔢 Usando ÍNDICE ESPECÍFICO: ${globalThis.procesoActualIndex} → ${imagenesEnIndice?.length || 0} imágenes de PROCESOS`);
                    if (imagenesEnIndice && imagenesEnIndice.length > 0) {
                        imagenesDelStorage = imagenesEnIndice.filter(img => img !== null);
                        console.log(`[agregarProcesoAlPedido]  ENCONTRADAS ${imagenesDelStorage.length} imágenes de PROCESOS en índice ${globalThis.procesoActualIndex}`);
                    }
                } else {
                    console.warn('[agregarProcesoAlPedido]  procesoActualIndex NO definido, buscando en índices 1-3 como fallback...');
                    // FALLBACK: Si no está definido (error), buscar en todos (pero esto no debería pasar)
                    for (let idx = 1; idx <= 3; idx++) {
                        const imagenesEnIndice = globalThis.universalImagenesStorage.obtenerImagenes('procesos', idx);
                        console.log(`  [agregarProcesoAlPedido] Fallback: Índice ${idx}: ${imagenesEnIndice?.length || 0} imágenes de PROCESOS`);
                        if (imagenesEnIndice && imagenesEnIndice.length > 0) {
                            imagenesDelStorage = imagenesEnIndice.filter(img => img !== null);
                            console.log(`[agregarProcesoAlPedido]  FALLBACK: ENCONTRADAS ${imagenesDelStorage.length} imágenes de PROCESOS en índice ${idx}`);
                            break;
                        }
                    }
                }
            }
            
            // Fallback: Imágenes locales del array imagenesProcesoActual
            if (imagenesDelStorage.length === 0) {
                //  CORRECCIÓN: Usar globalThis.imagenesProcesoActual en lugar de la variable local
                const imagenesNuevas = (globalThis.imagenesProcesoActual || []).filter(img => img !== null);
                if (imagenesNuevas.length > 0) {
                    imagenesDelStorage = imagenesNuevas;
                    console.log('[agregarProcesoAlPedido]  Fallback: Imágenes obtenidas desde globalThis.imagenesProcesoActual:', imagenesDelStorage.length);
                }
            }
        } else {
            console.log('[agregarProcesoAlPedido]  Modo EDICIÓN: Usando imagenesExistentes + imágenes NUEVAS agregadas');
            //  NUEVO: En modo EDICIÓN, también capturar imágenes NUEVAS de imagenesProcesoActual
            // Esto permite agregar imágenes después de eliminar
            imagenesNuevasAgregadas = (globalThis.imagenesProcesoActual || []).filter(img => img !== null && img instanceof File);
            console.log('[agregarProcesoAlPedido]  Imágenes nuevas agregadas en edición:', imagenesNuevasAgregadas.length);
        }
        
        //  CORRECCIÓN CRÍTICA: Solo usar imágenes del proceso actual
        // No mezclar imágenes de diferentes procesos
        let imagenesFinales = [];
        
        if (modoActual === 'editar') {
            //  CORRECCIÓN: En modo edición, usar el estado ACTUAL del modal
            // que refleja las eliminaciones hechas por el usuario
            console.log('[agregarProcesoAlPedido]  DEBUG EDICIÓN - Estado de variables:', {
                'globalThis.imagenesProcesoExistentes': globalThis.imagenesProcesoExistentes,
                'length': globalThis.imagenesProcesoExistentes?.length || 0,
                'contenido': globalThis.imagenesProcesoExistentes?.map((img, idx) => ({
                    idx,
                    esNull: img === null,
                    esUndefined: img === undefined,
                    tipo: typeof img,
                    nombre: img?.name || img?.nombre || 'sin-nombre'
                }))
            });
            
            if (globalThis.imagenesProcesoExistentes && globalThis.imagenesProcesoExistentes.length > 0) {
                // Usar imágenes del estado actual del modal (con eliminaciones aplicadas)
                imagenesFinales = globalThis.imagenesProcesoExistentes.filter(img => img !== null && img !== undefined);
                console.log('[agregarProcesoAlPedido]  MODO EDICIÓN: Usando imágenes del estado actual del modal:', imagenesFinales.length);
                console.log('[agregarProcesoAlPedido]  Imágenes filtradas (no null):', imagenesFinales.map((img, idx) => ({
                    idx,
                    nombre: img?.name || img?.nombre || 'sin-nombre',
                    tipo: typeof img
                })));
            } else {
                // Fallback: usar imágenes del proceso guardado
                const procesoGuardado = globalThis.procesosSeleccionados?.[procesoActual]?.datos;
                console.log('[agregarProcesoAlPedido]  DEBUG - procesoGuardado:', procesoGuardado);
                if (procesoGuardado?.imagenes && procesoGuardado.imagenes.length > 0) {
                    imagenesFinales = procesoGuardado.imagenes.filter(img => img !== null && img !== undefined);
                    console.log('[agregarProcesoAlPedido]  MODO EDICIÓN: Usando imágenes del proceso guardado (fallback):', imagenesFinales.length);
                } else {
                    // Último fallback: usar imagenesExistentes
                    imagenesFinales = imagenesExistentes;
                    console.log('[agregarProcesoAlPedido]  MODO EDICIÓN: Usando imágenes existentes (último fallback):', imagenesFinales.length);
                }
            }
            
            //  CORRECCIÓN: Solo agregar imágenes NUEVAS que no estén ya en imagenesFinales
            // Evitar duplicación de imágenes existentes
            if (imagenesNuevasAgregadas.length > 0) {
                // Filtrar solo imágenes realmente nuevas (File objects que no estén en imagenesFinales)
                const imagenesUnicasNuevas = imagenesNuevasAgregadas.filter(nuevaImg => {
                    return nuevaImg instanceof File && !imagenesFinales.some(existingImg => 
                        existingImg instanceof File && existingImg.name === nuevaImg.name && existingImg.size === nuevaImg.size
                    );
                });
                
                if (imagenesUnicasNuevas.length > 0) {
                    imagenesFinales = [...imagenesFinales, ...imagenesUnicasNuevas];
                    console.log('[agregarProcesoAlPedido]  Agregando imágenes realmente nuevas en edición:', imagenesUnicasNuevas.length);
                } else {
                    console.log('[agregarProcesoAlPedido]  No hay imágenes nuevas únicas para agregar (todas ya existen)');
                }
            }
        } else {
            // En modo creación: SOLO usar imágenes del storage del proceso actual
            if (globalThis.procesoActualIndex !== undefined && globalThis.procesoActualIndex > 0) {
                imagenesFinales = imagenesDelStorage;
                console.log(`[agregarProcesoAlPedido]  MODO CREACIÓN: Usando imágenes del storage índice ${globalThis.procesoActualIndex}:`, imagenesFinales.length);
            } else {
                console.warn('[agregarProcesoAlPedido]  Sin índice de proceso definido, no se usarán imágenes');
                imagenesFinales = [];
            }
        }
        
        // Filtrar válidas (eliminadas marcadas como null)
        const imagenesValidas = imagenesFinales.filter(img => img !== null && img !== undefined && img !== '');
        
        console.log('[agregarProcesoAlPedido]  IMÁGENES CAPTURADAS:', {
            modoActual: modoActual,
            procesoActualIndex: globalThis.procesoActualIndex,
            imagenesFinales: imagenesFinales.length,
            imagenesValidas: imagenesValidas.length,
            fuentes: {
                imagenesDelStorage: imagenesDelStorage.length,
                imagenesExistentes: imagenesExistentes.length,
                imagenesNuevasAgregadas: imagenesNuevasAgregadas.length
            },
            imagenesValidasDetalle: imagenesValidas.map((img, idx) => ({
                index: idx,
                tipo: img instanceof File ? 'File' : 'Object',
                nombre: img?.nombre || img?.name || 'sin-nombre',
                tienePreviewUrl: !!img?.previewUrl,
                tieneDataURL: !!img?.dataURL,
                tieneFile: !!img?.file,
                tieneUrl: !!img?.url,
                tieneRuta: !!img?.ruta_original,
                previewUrlSample: img?.previewUrl?.substring(0, 50) || 'N/A',
                claves: typeof img === 'object' ? Object.keys(img) : 'N/A'
            }))
        });
        
        // IMPORTANTE: Usar tallasCantidadesProceso que contiene las cantidades DEL PROCESO
        // NO globalThis.tallasRelacionales que son las cantidades DE LA PRENDA
        const cantidadOriginales = (globalThis.imagenesProcesoExistentes || []).length;
        
        //  CRÍTICO: Usar storage de eliminadas, no nulls
        const imagenesEliminadasArray = globalThis.imagenesEliminadasProcesoStorage || [];
        
        const ubicacionesClonadas = (globalThis.ubicacionesProcesoSeleccionadas || []).map(u => {
            if (u && typeof u === 'object') {
                return { ...u };
            }
            return u;
        });

        const datos = {
            tipo: procesoActual,
            modo_tallas: globalThis.procesosSeleccionados?.[procesoActual]?.datos?.modo_tallas || 'generico',
            ubicaciones: ubicacionesClonadas,
            observaciones: document.getElementById('proceso-observaciones')?.value || '',
            tallas: {
                dama: { ...globalThis.tallasCantidadesProceso?.dama } || {},
                caballero: { ...globalThis.tallasCantidadesProceso?.caballero } || {},
                sobremedida: { ...globalThis.tallasCantidadesProceso?.sobremedida } || {}
            },
            imagenes: imagenesValidas, // Array de imágenes (existentes + nuevas)
            //  CRÍTICO: imagenesEliminadas debe contener IDs de imágenes a eliminar
            imagenesEliminadas: imagenesEliminadasArray
                .map(img => {
                    // Ya están filtradas en el storage, solo mapear
                    if (img && img.id) {
                        return {
                            id: img.id,
                            ruta_original: img.ruta_original,
                            ruta_webp: img.ruta_webp
                        };
                    }
                    return img;
                })
                .filter(img => img !== null && img !== undefined) //  Filtrar por si acaso
        };
        
        //  VERIFICAR: imagenesEliminadas se asignó correctamente
        console.log('[agregarProcesoAlPedido]  OBJETO datos CONSTRUIDO:', {
            tipo: datos.tipo,
            tieneImagenes: !!datos.imagenes,
            tieneImagenesEliminadas: !!datos.imagenesEliminadas,
            imagenesEliminadasContent: datos.imagenesEliminadas,
            storageEliminadas: globalThis.imagenesEliminadasProcesoStorage
        });
        
        console.log('[agregarProcesoAlPedido]  DEBUG imagenesEliminadas:', {
            cantidadOriginales: cantidadOriginales,
            storageLength: (globalThis.imagenesEliminadasProcesoStorage || []).length,
            storageContent: globalThis.imagenesEliminadasProcesoStorage,
            imagenesValidas: imagenesValidas.map((img, idx) => ({
                idx,
                esFile: img instanceof File,
                tipo: typeof img,
                nombre: img?.name || img?.nombre || 'sin-nombre'
            })),
            imagenesEliminadasArray: imagenesEliminadasArray.map((img, idx) => ({
                idx,
                esNull: img === null,
                esFile: img instanceof File,
                tipo: typeof img
            }))
        });
        
        console.log('[agregarProcesoAlPedido] Datos capturados:', {
            tipo: procesoActual,
            tallas: datos.tallas,
            tallasCantidadesProceso: globalThis.tallasCantidadesProceso,
            tieneUbicaciones: ubicacionesProcesoSeleccionadas.length > 0
        });
        
        // NUEVO: DIFERENCIAR ENTRE CREACIÓN Y EDICIÓN
        if (modoActual === 'crear') {
            // CREACIÓN: Guardar directamente en procesosSeleccionados (comportamiento actual)
            if (!globalThis.procesosSeleccionados) {
                globalThis.procesosSeleccionados = {};
            }
            
            // Si el proceso NO existe todavía, crearlo
            if (!globalThis.procesosSeleccionados[procesoActual]) {
                globalThis.procesosSeleccionados[procesoActual] = {
                    tipo: procesoActual,
                    indiceResultado: globalThis.procesoActualIndex, //  Guardar el índice para futuras ediciones
                    datos: null
                };
            }
            
            // Asignar los datos capturados
            globalThis.procesosSeleccionados[procesoActual].datos = {
                ...datos,
                ubicaciones: [...ubicacionesClonadas]
            };
            globalThis.procesosSeleccionados[procesoActual].indiceResultado = globalThis.procesoActualIndex; // Garantizar que el índice está guardado
            
            console.log('[agregarProcesoAlPedido-GUARDADO] Proceso guardado en globalThis.procesosSeleccionados:', {
                tipo: procesoActual,
                indice: globalThis.procesoActualIndex,
                datosGuardados: globalThis.procesosSeleccionados[procesoActual].datos
            });
            
        } else if (modoActual === 'editar') {
            // EDICIÓN: Actualizar directamente en globalThis.procesosSeleccionados
            console.log(' [EDICIÓN] Guardando cambios del proceso');
            
            if (!globalThis.procesosSeleccionados) {
                globalThis.procesosSeleccionados = {};
            }
            
            //  NUEVO: Preservar el ID del proceso existente en BD
            const procesoExistente = globalThis.procesosSeleccionados[procesoActual];
            const idExistente = procesoExistente?.datos?.id;
            const tipoProcesoId = procesoExistente?.datos?.tipo_proceso_id;
            
            if (idExistente) {
                datos.id = idExistente;
            }
            if (tipoProcesoId) {
                datos.tipo_proceso_id = tipoProcesoId;
            }
            
            // Actualizar directamente con los datos capturados del modal
            globalThis.procesosSeleccionados[procesoActual] = {
                tipo: procesoActual,
                indiceResultado: globalThis.procesoActualIndex,
                datos: {
                    ...datos,
                    ubicaciones: [...ubicacionesClonadas]
                }
            };
            
            console.log(' [EDICIÓN] Datos actualizados en globalThis.procesosSeleccionados:', {
                tipo: procesoActual,
                id: datos.id,
                ubicaciones: datos.ubicaciones?.length || 0,
                imagenes: datos.imagenes?.length || 0,
                tallas: datos.tallas
            });
            
            // También registrar en ProcesosEditor para tracking de cambios
            if (globalThis.procesosEditor) {
                globalThis.procesosEditor.registrarCambioUbicaciones(datos.ubicaciones);
                globalThis.procesosEditor.registrarCambioImagenes(datos.imagenes);
                globalThis.procesosEditor.registrarCambioObservaciones(datos.observaciones);
                globalThis.procesosEditor.registrarCambioTallas(datos.tallas);
            }
            
            // Mantener buffer para compatibilidad
            cambiosProceso = datos;
        }
        
        //  NUEVO: Capturar modo ANTES de cerrar el modal (cerrar resetea modoActual a 'crear')
        const modoAntesDeCerrar = modoActual;
        console.log('[agregarProcesoAlPedido] Modo capturado antes de cerrar:', modoAntesDeCerrar);
        
        //  CRÍTICO: Resetear storage de eliminadas después de guardar
        globalThis.imagenesEliminadasProcesoStorage = [];
        console.log('[agregarProcesoAlPedido]  Storage de eliminadas reseteado después de guardar');
        
        // Cerrar modal indicando que el proceso fue guardado exitosamente
        cerrarModalProcesoGenerico(true);
        
        // Renderizar tarjetas SIEMPRE después de guardar (tanto en edición como en creación)
        if (globalThis.renderizarTarjetasProcesos) {
            setTimeout(() => {
                console.log(` [agregarProcesoAlPedido] Renderizando tarjetas (modo: ${modoAntesDeCerrar})...`);
                globalThis.renderizarTarjetasProcesos();
                
                // VERIFICACIÓN: Confirmar que se renderizó correctamente
                setTimeout(() => {
                    const container = document.getElementById('contenedor-tarjetas-procesos');
                    if (container) {
                        const tarjetas = container.querySelectorAll('[data-tipo-proceso]');
                        console.log(` [agregarProcesoAlPedido-VERIFY] Tarjetas renderizadas: ${tarjetas.length}`);
                        if (tarjetas.length === 0) {
                            console.warn(' [agregarProcesoAlPedido-VERIFY]  NO se encontraron tarjetas. Re-renderizando...');
                            globalThis.renderizarTarjetasProcesos();
                        }
                    }
                }, 100);
            }, 50);
        }
        
        // Actualizar resumen en prenda modal
        if (globalThis.actualizarResumenProcesos) {
            globalThis.actualizarResumenProcesos();
        }
        
    } catch (error) {
        console.error('[agregarProcesoAlPedido] Error:', error);
    }
};

// NUEVO: Función para aplicar cambios del buffer cuando se hace GUARDAR CAMBIOS de la prenda
// Esta función es llamada ANTES de hacer el PATCH final
globalThis.aplicarCambiosProcesosDesdeBuffer = function() {
    if (cambiosProceso) {
        console.log('[APLICAR-BUFFER] Aplicando cambios del proceso al procesosSeleccionados:', cambiosProceso);
        
        // Si no existe, crear
        if (!globalThis.procesosSeleccionados) {
            globalThis.procesosSeleccionados = {};
        }
        
        // Crear o actualizar el proceso con los cambios del buffer
        globalThis.procesosSeleccionados[cambiosProceso.tipo] = {
            tipo: cambiosProceso.tipo,
            datos: cambiosProceso
        };
        
        console.log('[APLICAR-BUFFER]  Cambios aplicados a procesosSeleccionados');
        
        // Limpiar buffer
        cambiosProceso = null;
    }
};

// NUEVO: Función para obtener el estado actual del buffer (para debugging/validación)
globalThis.obtenerBufferProcesoActual = function() {
    return cambiosProceso;
};

// NUEVO: Función para obtener el modo actual (para debugging)
globalThis.obtenerModoActual = function() {
    return modoActual;
};

// Confirmar que el módulo se cargó correctamente
