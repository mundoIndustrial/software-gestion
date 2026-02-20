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

window.tallasSeleccionadasProceso = { dama: [], caballero: [], sobremedida: null };
window.ubicacionesProcesoSeleccionadas = [];
// ESTRUCTURA INDEPENDIENTE: Cantidades de TALLAS DEL PROCESO (NO de la prenda)
// Estructura: { dama: { S: 5, M: 3 }, caballero: { 32: 2 }, sobremedida: { DAMA: 10, CABALLERO: 5 } }
window.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };

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
    dama: ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
    caballero: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL']
};

// Abrir modal para un tipo específico de proceso
window.abrirModalProcesoGenerico = function(tipoProceso, esEdicion = false) {

    
    // Verificar que el modal existe
    const modal = document.getElementById('modal-proceso-generico');
    if (!modal) {

        return;
    }
    
    // 🔴 CRÍTICO: Resetear storage de imágenes eliminadas cuando se abre un nuevo modal
    window.imagenesEliminadasProcesoStorage = [];
    console.log('[abrirModalProcesoGenerico] 🔄 Storage de imágenes eliminadas reseteado');
    
    procesoActual = tipoProceso;
    // NUEVO: Establecer el modo (crear o editar)
    modoActual = esEdicion ? 'editar' : 'crear';
    
    // 🔧 FIX CRÍTICO: Establecer procesoActualIndex basándose en el orden de creación
    // Los índices 1, 2, 3 en window.procesosImagenesStorage corresponden al ORDEN de creación de procesos
    if (esEdicion && window.procesosSeleccionados?.[tipoProceso]?.indiceResultado !== undefined) {
        // En EDICIÓN: Usar el índice ya asignado
        window.procesoActualIndex = window.procesosSeleccionados[tipoProceso].indiceResultado;
        console.log(`🔢 [abrirModalProcesoGenerico] EDICIÓN: Usando índice existente ${window.procesoActualIndex} para ${tipoProceso}`);
    } else {
        // En CREACIÓN: Encontrar el primer índice disponible (1, 2, 3)
        const indicesUsados = new Set();
        
        // Recolectar todos los índices ya usados
        Object.values(window.procesosSeleccionados || {}).forEach(proceso => {
            if (proceso.indiceResultado !== undefined) {
                indicesUsados.add(proceso.indiceResultado);
            }
        });
        
        // Encontrar el primer índice disponible
        let indiceDisponible = 1;
        while (indicesUsados.has(indiceDisponible) && indiceDisponible <= 3) {
            indiceDisponible++;
        }
        
        window.procesoActualIndex = indiceDisponible;
        console.log(`🔢 [abrirModalProcesoGenerico] CREACIÓN: Índices usados=${[...indicesUsados]}, index asignado=${window.procesoActualIndex} para ${tipoProceso}`);
    }
    
    // 🔴 NUEVO: Limpiar el storage del índice que se va a usar para evitar contaminación
    if (window.universalImagenesStorage && !esEdicion && window.procesoActualIndex !== undefined) {
        console.log(`[abrirModalProcesoGenerico] 🧹 Limpiando storage de PROCESOS del índice ${window.procesoActualIndex} antes de usar`);
        window.universalImagenesStorage.eliminarTodasLasImagenes('procesos', window.procesoActualIndex);
    }
    const config = procesosConfig[tipoProceso];
    
    if (!config) {

        return;
    }
    
    try {
        // Actualizar título e icono
        const titleEl = document.getElementById('modal-proceso-titulo');
        const iconEl = document.getElementById('modal-proceso-icon');
        const btnTextoEl = document.getElementById('modal-btn-texto');
        
        // Determinar el texto basado en el modo (crear o editar)
        const textoTitulo = esEdicion ? `Editar ${nombresProcesos[tipoProceso] || tipoProceso}` : config.titulo;
        const textoBtnTexto = esEdicion ? `Editar ${nombresProcesos[tipoProceso] || tipoProceso}` : config.btnTexto;
        
        if (titleEl) titleEl.textContent = textoTitulo;
        if (iconEl) iconEl.textContent = config.icon;
        if (btnTextoEl) btnTextoEl.textContent = textoBtnTexto;
        
        // SOLO limpiar formulario si NO es edición
        if (!esEdicion) {
            const form = document.getElementById('form-proceso-generico');
            if (form) form.reset();
        }
        
        // SOLO limpiar variables si NO es edición
        if (!esEdicion) {
            // En CREACIÓN: limpiar todo
            window.tallasSeleccionadasProceso = { dama: [], caballero: [], sobremedida: null };
            window.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };
            
            // Limpiar resumen
            const resumenTallas = document.getElementById('proceso-tallas-resumen');
            if (resumenTallas) resumenTallas.innerHTML = '';
            
            // Limpiar ubicaciones
            window.ubicacionesProcesoSeleccionadas = [];
            const listaUbicaciones = document.getElementById('lista-ubicaciones-proceso');
            if (listaUbicaciones) listaUbicaciones.innerHTML = '';
            const inputUbicacion = document.getElementById('input-ubicacion-nueva');
            if (inputUbicacion) inputUbicacion.value = '';
            
            // Limpiar imágenes
            if (typeof limpiarImagenesProceso === 'function') {
                limpiarImagenesProceso();
            }
        } else {
            // En EDICIÓN: renderizar lo que ya está cargado
            // IMPORTANTE: Cargar las tallas del proceso existente en el window
            const procesoDatos = window.procesosSeleccionados[tipoProceso]?.datos;
            if (procesoDatos && procesoDatos.tallas) {
                window.tallasCantidadesProceso = { ...procesoDatos.tallas };
                console.log(' [EDICIÓN] Tallas del proceso cargadas en tallas CantidadesProceso:', window.tallasCantidadesProceso);
            }
            
            if (window.renderizarListaUbicaciones) {
                window.renderizarListaUbicaciones();
            }
            if (window.actualizarResumenTallasProceso) {
                window.actualizarResumenTallasProceso();
            }
        }
        
        // Mostrar modal
        modal.style.display = 'flex';
        // ⚡ CRÍTICO: Forzar z-index MÁXIMO para que esté siempre al frente
        modal.style.zIndex = '999999999';
        console.log(' [MODAL-PROCESO] Z-index forzado a:', modal.style.zIndex);
        
        // 🔴 NUEVO: Remover aria-hidden para que los elementos dentro sean accesibles
        // Esto evita el warning "Blocked aria-hidden on an element because its descendant retained focus"
        modal.removeAttribute('aria-hidden');
        console.log(' [MODAL-PROCESO] aria-hidden removido para accesibilidad');

    } catch (error) {

    }
};

// Cerrar modal
// @param {boolean} procesoGuardado - Si es true, mantiene el checkbox seleccionado (proceso guardado exitosamente)
window.cerrarModalProcesoGenerico = function(procesoGuardado = false) {
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
        if (checkbox && checkbox.checked) {
            // Usar una bandera temporal para evitar que onclick se dispare
            checkbox._ignorarOnclick = true;
            checkbox.checked = false;

        }
        
        //  PASO 2: Actualizar el estado del gestor (procesos seleccionados)
        if (window.manejarCheckboxProceso) {
            window.manejarCheckboxProceso(procesoActual, false);
        }
        
        // Limpiar la bandera
        if (checkbox) {
            checkbox._ignorarOnclick = false;
        }
        
        // PASO 3: Limpiar estructura de tallas del proceso
        window.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };
        
    } else if (modoActual === 'editar' && procesoGuardado) {
        console.log('[EDICIÓN] Modal cerrada - cambios en buffer temporal, esperando GUARDAR CAMBIOS final');
        
        // NUEVO: Guardar cambios en el gestor de edición
        if (window.gestorEditacionProcesos) {
            console.log('[EDICIÓN] 💾 Guardando cambios en gestorEditacionProcesos...');
            window.gestorEditacionProcesos.guardarCambiosActuales();
            console.log('[EDICIÓN]  Cambios guardados en gestorEditacionProcesos');
        }
    }
    
    // 🔧 LIMPIAR STORAGE DE IMÁGENES después de guardar (para evitar residuos en próximo proceso)
    if (modoActual === 'crear' && procesoGuardado && window.procesoActualIndex !== undefined) {
        // Limpiar el storage de imágenes del índice usado para este proceso
        console.log(`[cerrarModalProcesoGenerico] 🧹 Limpiando storage UNIVERSAL de PROCESOS del índice ${window.procesoActualIndex}`);
        if (window.universalImagenesStorage && typeof window.universalImagenesStorage.eliminarTodasLasImagenes === 'function') {
            window.universalImagenesStorage.eliminarTodasLasImagenes('procesos', window.procesoActualIndex);
            console.log(`[cerrarModalProcesoGenerico]  Storage UNIVERSAL de PROCESOS limpiado para índice ${window.procesoActualIndex}`);
        }
        
        // También limpiar el array local
        imagenesProcesoActual = [null, null, null];
        if (window.imagenesProcesoActual) {
            window.imagenesProcesoActual = [null, null, null];
        }
        console.log('[cerrarModalProcesoGenerico]  Arrays locales de imágenes limpiados');
    }
    
    // NUEVO: Reset de variables después de cerrar
    procesoActual = null;
    window.procesoActualIndex = undefined;
    modoActual = 'crear';  // Reset a valor por defecto
};

// Array para almacenar los archivos reales del proceso (hasta 3)
// Cambio: Ahora almacenamos File objects en lugar de base64
let imagenesProcesoActual = [null, null, null];

// Manejar upload de imagen individual
window.manejarImagenProceso = function(input, indice) {
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        
        //  CAMBIO: Guardar el File object directamente, NO convertir a base64
        imagenesProcesoActual[indice - 1] = file;
        
        //  CRÍTICO: Sincronizar con window.imagenesProcesoActual (usado en PATCH)
        if (!window.imagenesProcesoActual) {
            window.imagenesProcesoActual = [null, null, null];
        }
        window.imagenesProcesoActual[indice - 1] = file;
        console.log('[manejarImagenProceso]  Imagen guardada en window.imagenesProcesoActual:', {
            indice: indice,
            filename: file.name,
            size: file.size,
            type: file.type
        });
        
        // 🔴 NUEVO: Guardar en storage universal de PROCESOS
        if (window.universalImagenesStorage && window.procesoActualIndex !== undefined) {
            const imagenData = {
                file: file,
                previewUrl: URL.createObjectURL(file),
                nombre: file.name,
                tamaño: file.size,
                fileType: file.type,
                fileSize: file.size,
                fechaCreacion: new Date().toISOString()
            };
            
            window.universalImagenesStorage.agregarImagen('procesos', window.procesoActualIndex, imagenData);
            console.log(`[manejarImagenProceso] ✅ Imagen guardada en storage universal de PROCESOS[${window.procesoActualIndex}]`);
        } else {
            console.warn('[manejarImagenProceso] ⚠️ No se pudo guardar en storage universal - no disponible');
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
            
            // 🔴 Crear botón eliminar con data-indice (event delegation global lo detectará)
            let deleteBtn = preview.querySelector('.btn-eliminar-imagen-proceso');
            if (deleteBtn) deleteBtn.remove();
            deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn-eliminar-imagen-proceso';
            deleteBtn.type = 'button';
            deleteBtn.setAttribute('data-indice', indice);
            deleteBtn.style.cssText = 'position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; background: #ef4444; color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 16px; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold; z-index: 10;';
            deleteBtn.textContent = '×';
            preview.appendChild(deleteBtn);
            console.log('[manejarImagenProceso] ✅ Botón eliminar creado con data-indice:', indice);
            
            // Limpiar URL cuando el elemento se elimine (prevenir memory leaks)
            preview._objectUrl = objectUrl;
        }
        

    }
};

// 🔴 NUEVO: Variable global para rastrear qué imagen se está eliminando
window._imagenAEliminarIndice = null;

// 🔴 EVENT DELEGATION GLOBAL: Detectar clicks en botones .btn-eliminar-imagen-proceso
// Esto funciona incluso después de que setupDragAndDropProceso clone el preview con cloneNode(true)
// porque cloneNode NO copia event listeners, pero event delegation en document SÍ los detecta
(function() {
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-eliminar-imagen-proceso');
        if (btn) {
            console.log('[EVENT-DELEGATION] 🗑️ Click detectado en botón eliminar imagen');
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const indice = parseInt(btn.getAttribute('data-indice'), 10);
            console.log('[EVENT-DELEGATION] 📌 Índice del botón:', indice);
            
            if (indice && typeof window.eliminarImagenProceso === 'function') {
                console.log('[EVENT-DELEGATION] ✅ Llamando eliminarImagenProceso(' + indice + ')');
                window.eliminarImagenProceso(indice);
            } else {
                console.error('[EVENT-DELEGATION] ❌ Índice inválido o función no existe:', indice, typeof window.eliminarImagenProceso);
            }
        }
    }, true); // true = capture phase, se ejecuta ANTES que otros handlers
    console.log('[EVENT-DELEGATION] ✅ Event delegation global registrado para .btn-eliminar-imagen-proceso');
})();

// Mostrar modal de confirmación para eliminar imagen
window.mostrarModalConfirmarEliminarImagen = function(indice) {
    console.log('[mostrarModalConfirmarEliminarImagen] 📋 INICIANDO - Mostrando modal para imagen:', indice);
    window._imagenAEliminarIndice = indice;
    console.log('[mostrarModalConfirmarEliminarImagen] 📌 window._imagenAEliminarIndice establecido a:', window._imagenAEliminarIndice);
    
    const modal = document.getElementById('modal-confirmar-eliminar-imagen-proceso');
    console.log('[mostrarModalConfirmarEliminarImagen] 🔍 Modal encontrado?:', !!modal);
    
    if (modal) {
        console.log('[mostrarModalConfirmarEliminarImagen] ✅ Modal existe, mostrando...');
        modal.style.display = 'flex';
        // Forzar z-index máximo para que esté encima de todo
        modal.style.zIndex = '999999999';
        console.log('[mostrarModalConfirmarEliminarImagen] ✅ Modal mostrado con z-index:', modal.style.zIndex);
    } else {
        console.error('[mostrarModalConfirmarEliminarImagen] ❌ MODAL NO ENCONTRADO - ID: modal-confirmar-eliminar-imagen-proceso');
        console.log('[mostrarModalConfirmarEliminarImagen] 🔍 Elementos en body:', document.body.children.length);
        console.log('[mostrarModalConfirmarEliminarImagen] 🔍 Buscando modales con clase modal-overlay:');
        document.querySelectorAll('.modal-overlay').forEach((m, idx) => {
            console.log(`  [${idx}] ID: ${m.id}, Display: ${m.style.display}, Z-index: ${m.style.zIndex}`);
        });
    }
};

// Cerrar modal de confirmación
window.cerrarModalConfirmarEliminarImagen = function() {
    console.log('[cerrarModalConfirmarEliminarImagen] ❌ Cerrando modal');
    window._imagenAEliminarIndice = null;
    
    const modal = document.getElementById('modal-confirmar-eliminar-imagen-proceso');
    if (modal) {
        modal.style.display = 'none';
    }
};

// Confirmar eliminación de imagen
window.confirmarEliminarImagenProceso = function() {
    const indice = window._imagenAEliminarIndice;
    if (!indice) return;
    
    console.log('[confirmarEliminarImagenProceso] ✅ Confirmando eliminación de imagen:', indice);
    
    // Cerrar modal
    cerrarModalConfirmarEliminarImagen();
    
    // Limpiar URL.createObjectURL si existe
    const preview = document.getElementById(`proceso-foto-preview-${indice}`);
    if (preview) {
        const imgEl = preview.querySelector('img');
        if (imgEl && imgEl.src && imgEl.src.startsWith('blob:')) {
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
    if (window.imagenesProcesoActual) {
        window.imagenesProcesoActual[indice - 1] = null;
    }
    
    // 🔴 CRÍTICO: Guardar la imagen ANTES de marcarla como null
    if (window.imagenesProcesoExistentes && window.imagenesProcesoExistentes.length > (indice - 1)) {
        const imagenAeliminarObj = window.imagenesProcesoExistentes[indice - 1];
        
        // Inicializar array de eliminadas si no existe
        if (!window.imagenesEliminadasProcesoStorage) {
            window.imagenesEliminadasProcesoStorage = [];
        }
        
        // Guardar el objeto completo de la imagen a eliminar
        // IMPORTANTE: Usar ruta_original como identificador único (algunos objetos no tienen .id)
        if (imagenAeliminarObj && (imagenAeliminarObj.id || imagenAeliminarObj.ruta_original)) {
            const identificador = imagenAeliminarObj.id || imagenAeliminarObj.ruta_original;
            // Verificar que no esté duplicado
            const yaGuardado = window.imagenesEliminadasProcesoStorage.some(img => {
                return (img.id || img.ruta_original) === identificador;
            });
            if (!yaGuardado) {
                window.imagenesEliminadasProcesoStorage.push(imagenAeliminarObj);
                console.log('[confirmarEliminarImagenProceso] 💾 Imagen GUARDADA en storage de eliminadas:', {
                    id: imagenAeliminarObj.id,
                    ruta: imagenAeliminarObj.ruta_original,
                    identificador: identificador,
                    totalEliminadas: window.imagenesEliminadasProcesoStorage.length,
                    contenidoStorage: window.imagenesEliminadasProcesoStorage
                });
            }
        } else {
            console.warn('[confirmarEliminarImagenProceso] ⚠️ Imagen sin ID ni ruta_original, no se pudo guardar:', imagenAeliminarObj);
        }
        
        // AHORA marcar como null
        window.imagenesProcesoExistentes[indice - 1] = null;
        const imagenesParaEnviar = window.imagenesProcesoExistentes.filter(img => img !== null && img !== undefined && img !== '');
        console.log('[confirmarEliminarImagenProceso] 🗑️ Imagen existente marcada como eliminada:', {
            indice: indice - 1,
            imagenesRestantes: imagenesParaEnviar.length,
            storageLlenado: window.imagenesEliminadasProcesoStorage
        });
    } else {
        // Combinar imágenes existentes + nuevas que quedan
        let imagenesParaEnviar = [];
        if (window.imagenesProcesoExistentes) {
            imagenesParaEnviar = window.imagenesProcesoExistentes.filter(img => img !== null && img !== undefined && img !== '');
        }
        if (window.imagenesProcesoActual) {
            window.imagenesProcesoActual.forEach(img => {
                if (img instanceof File) imagenesParaEnviar.push(img);
            });
        }
        console.log('[confirmarEliminarImagenProceso] Imágenes restantes:', imagenesParaEnviar.length);
    }
    
    // Registrar cambio en editor de procesos
    if (window.procesosEditor) {
        let imagenesParaRegistrar = [];
        if (window.imagenesProcesoExistentes) {
            imagenesParaRegistrar = window.imagenesProcesoExistentes.filter(img => img !== null && img !== undefined && img !== '');
        }
        if (window.imagenesProcesoActual) {
            window.imagenesProcesoActual.forEach(img => {
                if (img instanceof File) imagenesParaRegistrar.push(img);
            });
        }
        window.procesosEditor.registrarCambioImagenes(imagenesParaRegistrar);
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
window.eliminarImagenProceso = function(indice) {
    console.log('[eliminarImagenProceso] 🗑️ INICIANDO - Click en botón eliminar para imagen:', indice);
    
    // Guardar el índice globalmente
    window._imagenAEliminarIndice = indice;
    console.log('[eliminarImagenProceso] � window._imagenAEliminarIndice establecido a:', window._imagenAEliminarIndice);
    
    // Buscar el modal directamente
    const modal = document.getElementById('modal-confirmar-eliminar-imagen-proceso');
    console.log('[eliminarImagenProceso] 🔍 Modal encontrado?:', !!modal);
    
    if (modal) {
        console.log('[eliminarImagenProceso] ✅ Modal existe, mostrando...');
        modal.style.display = 'flex';
        modal.style.zIndex = '999999999';
        console.log('[eliminarImagenProceso] ✅ Modal mostrado con z-index:', modal.style.zIndex);
    } else {
        console.error('[eliminarImagenProceso] ❌ MODAL NO ENCONTRADO - ID: modal-confirmar-eliminar-imagen-proceso');
        // Listar todos los modales disponibles
        console.log('[eliminarImagenProceso] 🔍 Modales disponibles en el DOM:');
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
        if (preview && preview._objectUrl) {
            URL.revokeObjectURL(preview._objectUrl);
            preview._objectUrl = null;
        }
    }
    
    imagenesProcesoActual = [null, null, null];
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
window.agregarUbicacionProceso = function() {
    const input = document.getElementById('input-ubicacion-nueva');
    const ubicacion = input?.value?.trim();
    
    if (!ubicacion) {

        return;
    }
    
    // Evitar duplicados
    if (window.ubicacionesProcesoSeleccionadas.includes(ubicacion)) {

        return;
    }
    
    // Agregar a la lista
    window.ubicacionesProcesoSeleccionadas.push(ubicacion);

    
    // Limpiar input
    input.value = '';
    
    // Renderizar lista
    window.renderizarListaUbicaciones();
};

// Remover ubicación de la lista
window.removerUbicacionProceso = function(ubicacion) {
    // Si es objeto, comparar por ubicacion.ubicacion
    if (typeof ubicacion === 'object' && ubicacion.ubicacion) {
        window.ubicacionesProcesoSeleccionadas = window.ubicacionesProcesoSeleccionadas.filter(u => {
            if (typeof u === 'object') return u.ubicacion !== ubicacion.ubicacion;
            return u !== ubicacion.ubicacion;
        });
    } else {
        // Si es string
        window.ubicacionesProcesoSeleccionadas = window.ubicacionesProcesoSeleccionadas.filter(u => {
            if (typeof u === 'object') return u.ubicacion !== ubicacion;
            return u !== ubicacion;
        });
    }

    window.renderizarListaUbicaciones();
};

// Renderizar la lista de ubicaciones
window.renderizarListaUbicaciones = function() {
    const container = document.getElementById('lista-ubicaciones-proceso');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (window.ubicacionesProcesoSeleccionadas.length === 0) {
        container.innerHTML = '<small style="color: #9ca3af;">Escribe una ubicación y haz click en "+" para agregarla</small>';
        return;
    }
    
    window.ubicacionesProcesoSeleccionadas.forEach((ubicacion, idx) => {
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
window.aplicarProcesoParaTodasTallas = function() {

    
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
    window.tallasSeleccionadasProceso = {
        dama: tallasPrendaArrays.dama,
        caballero: tallasPrendaArrays.caballero,
        sobremedida: tallasPrendaArrays.sobremedida
    };
    
    //  IMPORTANTE: Copiar TODAS las cantidades de la prenda al proceso
    // Esto hace que "Aplicar para todas" asigne las cantidades completas de la prenda
    window.tallasCantidadesProceso = {
        dama: { ...tallasPrendaConCantidades.dama } || {},
        caballero: { ...tallasPrendaConCantidades.caballero } || {},
        sobremedida: tallasPrendaConCantidades.sobremedida || {}
    };
    
    console.log(' [aplicarProcesoParaTodasTallas] Copiadas todas las tallas de la prenda al proceso:', {
        tallasCantidadesProceso: window.tallasCantidadesProceso,
        tallasSeleccionadas: window.tallasSeleccionadasProceso
    });

    actualizarResumenTallasProceso();
};

// Obtener tallas registradas en la prenda del modal
function obtenerTallasDeLaPrenda() {
    // NUEVO: Leer directamente del modelo relacional window.tallasRelacionales
    // Estructura: { DAMA: { S: 20, M: 20 }, CABALLERO: { 32: 10 }, SOBREMEDIDA: { UNISEX: 100 } }
    const tallasRelacionales = window.tallasRelacionales || { DAMA: {}, CABALLERO: {} };
    
    const tallas = { dama: {}, caballero: {}, sobremedida: null };
    
    console.log('[obtenerTallasDeLaPrenda] Leyendo de tallasRelacionales:', tallasRelacionales);
    
    // Obtener tallas de DAMA CON CANTIDADES
    if (tallasRelacionales.DAMA && Object.keys(tallasRelacionales.DAMA).length > 0) {
        tallas.dama = { ...tallasRelacionales.DAMA };
        console.log('[obtenerTallasDeLaPrenda] Tallas DAMA encontradas:', tallas.dama);
    } else {
        console.log('[obtenerTallasDeLaPrenda] No hay tallas DAMA');
    }
    
    // Obtener tallas de CABALLERO CON CANTIDADES
    if (tallasRelacionales.CABALLERO && Object.keys(tallasRelacionales.CABALLERO).length > 0) {
        tallas.caballero = { ...tallasRelacionales.CABALLERO };
        console.log('[obtenerTallasDeLaPrenda] Tallas CABALLERO encontradas:', tallas.caballero);
    } else {
        console.log('[obtenerTallasDeLaPrenda] No hay tallas CABALLERO');
    }
    
    // Obtener SOBREMEDIDA si existe
    if (tallasRelacionales.SOBREMEDIDA && Object.keys(tallasRelacionales.SOBREMEDIDA).length > 0) {
        tallas.sobremedida = { ...tallasRelacionales.SOBREMEDIDA };
        console.log('[obtenerTallasDeLaPrenda] Sobremedida encontrada:', tallas.sobremedida);
    } else {
        console.log('[obtenerTallasDeLaPrenda] No hay sobremedida');
    }
    
    console.log('[obtenerTallasDeLaPrenda] Resultado final:', tallas);
    return tallas;
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
            console.log('[MODAL-ADVERTENCIA-TALLAS] z-index swal2:', window.getComputedStyle(swal2Container).zIndex);
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
window.cerrarModalAdvertencia = function() {
    const modal = document.getElementById('modal-advertencia-tallas');
    if (modal) {
        modal.remove();
    }
};

// Abrir editor de tallas específicas
window.abrirEditorTallasEspecificas = function() {

    
    const modalEditor = document.getElementById('modal-editor-tallas');
    if (!modalEditor) {

        return;
    }
    
    // Obtener tallas registradas en la prenda (retorna objetos {talla: cantidad})
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
        
        if (tallasDamaArray.length === 0) {
            containerDama.innerHTML = '<p style="color: #9ca3af; font-size: 0.875rem;">No hay tallas DAMA seleccionadas en la prenda</p>';
        } else {
            tallasDamaArray.forEach(talla => {
                const isSelected = window.tallasSeleccionadasProceso.dama.includes(talla);
                const cantidadPrenda = tallasPrenda.dama[talla] || 0;
                const cantidadProceso = window.tallasCantidadesProceso?.dama?.[talla] || 0;
                
                // Calcular cuánto está asignado en otros procesos
                const { totalAsignado, procesosDetalle } = calcularCantidadAsignadaOtrosProcesos(talla, 'dama', procesoActual);
                const cantidadDisponible = cantidadPrenda - totalAsignado;
                
                const label = document.createElement('label');
                label.className = 'talla-checkbox-editor';
                label.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 0.5rem; width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s;">
                        <input type="checkbox" value="${talla}" ${isSelected ? 'checked' : ''} class="form-checkbox" data-genero="dama" style="cursor: pointer;">
                        <span style="font-weight: 600; min-width: 30px;">${talla}</span>
                        <input type="number" 
                            value="${cantidadProceso}" 
                            data-talla="${talla}"
                            data-genero="dama"
                            data-max="${cantidadDisponible}"
                            onchange="actualizarCantidadTallaProceso(this)"
                            placeholder="0"
                            style="width: 70px; padding: 0.25rem 0.5rem; border: 1px solid #be185d; border-radius: 4px; text-align: center; font-weight: 700; margin-left: auto; background: #fce7f3; color: #be185d;"
                            min="0"
                            max="${cantidadDisponible}">
                        <div style="font-size: 0.75rem; color: #9ca3af; white-space: nowrap;">
                            ${procesosDetalle.length > 0 ? `
                                <div style="margin-left: 0.5rem; padding-left: 0.5rem; border-left: 1px solid #d1d5db;">
                                    <strong style="color: #dc2626;">${totalAsignado}</strong> asignados
                                </div>
                            ` : `
                                <div style="margin-left: 0.5rem; padding-left: 0.5rem; border-left: 1px solid #d1d5db;">
                                    Disponible: <strong>${cantidadDisponible}</strong>
                                </div>
                            `}
                        </div>
                    </div>
                `;
                label.style.cssText = 'display: flex; flex-direction: column; gap: 0.5rem; cursor: pointer;';
                
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
        
        if (tallasCaballeroArray.length === 0) {
            containerCaballero.innerHTML = '<p style="color: #9ca3af; font-size: 0.875rem;">No hay tallas CABALLERO seleccionadas en la prenda</p>';
        } else {
            tallasCaballeroArray.forEach(talla => {
                const isSelected = window.tallasSeleccionadasProceso.caballero.includes(talla);
                const cantidadPrenda = tallasPrenda.caballero[talla] || 0;
                const cantidadProceso = window.tallasCantidadesProceso?.caballero?.[talla] || 0;
                
                // Calcular cuánto está asignado en otros procesos
                const { totalAsignado, procesosDetalle } = calcularCantidadAsignadaOtrosProcesos(talla, 'caballero', procesoActual);
                const cantidadDisponible = cantidadPrenda - totalAsignado;
                
                const label = document.createElement('label');
                label.className = 'talla-checkbox-editor';
                label.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 0.5rem; width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s;">
                        <input type="checkbox" value="${talla}" ${isSelected ? 'checked' : ''} class="form-checkbox" data-genero="caballero" style="cursor: pointer;">
                        <span style="font-weight: 600; min-width: 30px;">${talla}</span>
                        <input type="number" 
                            value="${cantidadProceso}" 
                            data-talla="${talla}"
                            data-genero="caballero"
                            data-max="${cantidadDisponible}"
                            onchange="actualizarCantidadTallaProceso(this)"
                            placeholder="0"
                            style="width: 70px; padding: 0.25rem 0.5rem; border: 1px solid #1d4ed8; border-radius: 4px; text-align: center; font-weight: 700; margin-left: auto; background: #dbeafe; color: #1d4ed8;"
                            min="0"
                            max="${cantidadDisponible}">
                        <div style="font-size: 0.75rem; color: #9ca3af; white-space: nowrap;">
                            ${procesosDetalle.length > 0 ? `
                                <div style="margin-left: 0.5rem; padding-left: 0.5rem; border-left: 1px solid #d1d5db;">
                                    <strong style="color: #dc2626;">${totalAsignado}</strong> asignados
                                </div>
                            ` : `
                                <div style="margin-left: 0.5rem; padding-left: 0.5rem; border-left: 1px solid #d1d5db;">
                                    Disponible: <strong>${cantidadDisponible}</strong>
                                </div>
                            `}
                        </div>
                    </div>
                `;
                label.style.cssText = 'display: flex; flex-direction: column; gap: 0.5rem; cursor: pointer;';
                
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
    console.log('📌 [EDITOR-TALLAS] Z-index COMPUTADO (getComputedStyle):', window.getComputedStyle(modalEditor).zIndex);
    
    // Obtener z-index del modal principal
    const modalPrincipal = document.getElementById('modal-proceso-generico');
    if (modalPrincipal) {
        console.log('📌 [EDITOR-TALLAS] Z-index MODAL PRINCIPAL (style):', modalPrincipal.style.zIndex || 'NO DEFINIDO');
        console.log('📌 [EDITOR-TALLAS] Z-index MODAL PRINCIPAL (computed):', window.getComputedStyle(modalPrincipal).zIndex);
    }
    
    // Forzar z-index aún más alto
    const zIndexEditorActual = parseInt(window.getComputedStyle(modalEditor).zIndex) || 100002;
    const zIndexPrincipalActual = parseInt(window.getComputedStyle(modalPrincipal).zIndex) || 999999999;
    const nuevoZIndexEditor = zIndexPrincipalActual + 1;
    
    console.log('📌 [EDITOR-TALLAS] Z-index EDITOR actual:', zIndexEditorActual);
    console.log('📌 [EDITOR-TALLAS] Z-index PRINCIPAL actual:', zIndexPrincipalActual);
    console.log('📌 [EDITOR-TALLAS] ASIGNANDO nuevo Z-index al editor:', nuevoZIndexEditor);
    
    // Aplicar z-index forzado
    modalEditor.style.zIndex = nuevoZIndexEditor.toString();
    console.log(' [EDITOR-TALLAS] Z-index FORZADO a:', modalEditor.style.zIndex);
    console.log(' [EDITOR-TALLAS] Z-index VERIFICADO (getComputedStyle):', window.getComputedStyle(modalEditor).zIndex);
    
    // Verificar contexto de apilamiento
    console.log('📌 [EDITOR-TALLAS] CONTEXTO DE APILAMIENTO:');
    console.log('   - Modal Principal display:', window.getComputedStyle(modalPrincipal).display);
    console.log('   - Modal Principal position:', window.getComputedStyle(modalPrincipal).position);
    console.log('   - Editor display:', window.getComputedStyle(modalEditor).display);
    console.log('   - Editor position:', window.getComputedStyle(modalEditor).position);
    
    // Listar todos los elementos con z-index alto en la página
    console.log('📌 [EDITOR-TALLAS] ELEMENTOS CON Z-INDEX ALTO:');
    document.querySelectorAll('[style*="z-index"], [class*="modal"], [class*="overlay"]').forEach((el, idx) => {
        const zIdx = window.getComputedStyle(el).zIndex;
        if (zIdx && zIdx !== 'auto' && parseInt(zIdx) > 100) {
            console.log(`   ${idx}. ${el.id || el.className || el.tagName} - Z-index: ${zIdx}, Display: ${window.getComputedStyle(el).display}`);
        }
    });

};

// Calcular cantidad ya asignada en OTROS procesos para una talla
function calcularCantidadAsignadaOtrosProcesos(talla, generoKey, procesoActualExcluir) {
    let totalAsignado = 0;
    const procesosDetalle = [];
    
    // Recorrer TODOS los procesos
    if (window.procesosSeleccionados) {
        Object.entries(window.procesosSeleccionados).forEach(([tipoProceso, datosProc]) => {
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
window.actualizarCantidadTallaProceso = function(input) {
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
        
        // Buscar el label padre que contiene todo
        const label = input.closest('label');
        console.log(' [ERROR-CSS] Label encontrado:', !!label);
        
        // Buscar o crear wrapper para mantener el grid ordenado
        let wrapper = label?.closest('.talla-error-wrapper');
        console.log(' [ERROR-CSS] Wrapper existente:', !!wrapper);
        
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'talla-error-wrapper';
            wrapper.style.cssText = 'display: contents;';
            
            if (label && label.parentNode) {
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
        console.log('   - Display (computed):', window.getComputedStyle(errorDiv).display);
        
        errorDiv.textContent = ` Máximo: ${cantidadDisponibleEnPrenda} unidades`;
        errorDiv.style.display = 'block';
        
        console.log(' [ERROR-CSS] Mensaje asignado');
        
        // Limpiar el campo (dejar en 0)
        input.value = 0;
        return;
        
        // CÓDIGO VIEJO - DESCARTAR
        /* Swal.fire({
            title: ' Cantidad Excedida',
            html: `<div style="text-align: left;">
                <p><strong>Talla:</strong> ${talla} (${generoKey.toUpperCase()})</p>
                <p><strong>Cantidad disponible en PRENDA:</strong> <span style="color: #dc2626; font-weight: bold;">${cantidadDisponibleEnPrenda}</span></p>
                <p><strong>Cantidad intentada:</strong> <span style="color: #dc2626; font-weight: bold;">${cantidad}</span></p>
                <hr>
                <p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                    💡 <strong>Nota:</strong> Las mismas prendas pueden recibir múltiples procesos.
                </p>
            </div>`,
            icon: 'warning',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#dc2626',
            allowOutsideClick: false,
            didOpen: (modal) => {
                //  DESPUÉS DE ABRIR
                console.log(' [MODAL-CANTIDAD-EXCEDIDA] ABIERTO');
                
                // Buscar elemento Swal2
                const swalContainer = document.querySelector('.swal2-container');
                const backdrop = document.querySelector('.swal2-backdrop-show');
                const swalModal = document.querySelector('.swal2-popup');
                
                console.log(' [MODAL-CANTIDAD-EXCEDIDA] DIAGNÓSTICO INICIAL:');
                console.log('   - swalContainer encontrado:', !!swalContainer);
                console.log('   - backdrop encontrado:', !!backdrop);
                console.log('   - swalModal encontrado:', !!swalModal);
                
                if (!swalContainer) {
                    console.error(' [MODAL-CANTIDAD-EXCEDIDA] NO SE ENCONTRÓ .swal2-container');
                    console.log(' [MODAL-CANTIDAD-EXCEDIDA] Elementos en DOM:');
                    document.querySelectorAll('[class*="swal"]').forEach((el, idx) => {
                        console.log(`   ${idx}. ${el.className} - display: ${window.getComputedStyle(el).display}, z-index: ${window.getComputedStyle(el).zIndex}`);
                    });
                    return;
                }
                
                // Z-index ANTES
                const zindexAntes = window.getComputedStyle(swalContainer).zIndex;
                console.log(' [MODAL-CANTIDAD-EXCEDIDA] Z-INDEX ANTES DE FORZAR:');
                console.log('   - Swal2 Container zIndex:', zindexAntes);
                console.log('   - Swal2 Container display:', window.getComputedStyle(swalContainer).display);
                console.log('   - Swal2 Container position:', window.getComputedStyle(swalContainer).position);
                console.log('   - Backdrop zIndex:', window.getComputedStyle(backdrop || {}).zIndex || 'NO ENCONTRADO');
                
                // Verificar padres
                console.log(' [MODAL-CANTIDAD-EXCEDIDA] PADRES DE SWAL2:');
                let padre = swalContainer.parentElement;
                let nivel = 0;
                while (padre && nivel < 5) {
                    const padreZindex = window.getComputedStyle(padre).zIndex;
                    console.log(`   Nivel ${nivel}: ${padre.tagName}#${padre.id} - z-index: ${padreZindex}, position: ${window.getComputedStyle(padre).position}`);
                    padre = padre.parentElement;
                    nivel++;
                }
                
                // Forzar z-index MÁS ALTO
                const zindexActual = parseInt(zindexAntes) || 9999998;
                const nuevoZindex = Math.max(zindexActual, 2000000000);
                
                console.log(' [MODAL-CANTIDAD-EXCEDIDA] FORZANDO Z-INDEX:');
                console.log('   - Z-index actual (parseado):', zindexActual);
                console.log('   - Nuevo z-index:', nuevoZindex);
                
                swalContainer.style.zIndex = nuevoZindex.toString();
                
                if (backdrop) {
                    const backdropZindex = Math.max(nuevoZindex - 1, 1999999999);
                    backdrop.style.zIndex = backdropZindex.toString();
                    console.log('   - Backdrop z-index también forzado a:', backdropZindex);
                }
                
                // Verificar DESPUÉS de forzar
                console.log(' [MODAL-CANTIDAD-EXCEDIDA] Z-INDEX DESPUÉS DE FORZAR:');
                const zindexDespues = window.getComputedStyle(swalContainer).zIndex;
                console.log('   - Swal2 Container zIndex (style):', swalContainer.style.zIndex);
                console.log('   - Swal2 Container zIndex (computed):', zindexDespues);
                console.log('   - Backdrop zIndex (computed):', window.getComputedStyle(backdrop || {}).zIndex || 'NO ENCONTRADO');
                
                // Buscar si algo está ENCIMA
                console.log(' [MODAL-CANTIDAD-EXCEDIDA] BÚSQUEDA DE ELEMENTOS ENCIMA:');
                document.querySelectorAll('*').forEach(el => {
                    const zIdx = window.getComputedStyle(el).zIndex;
                    if (zIdx && zIdx !== 'auto' && parseInt(zIdx) > parseInt(nuevoZindex)) {
                        console.warn(`    ELEMENTO ENCIMA: ${el.tagName}#${el.id}.${el.className} - z-index: ${zIdx}`);
                    }
                });
                
                // Listar todos los elementos con z-index alto
                console.log('📌 [MODAL-CANTIDAD-EXCEDIDA] ELEMENTOS CON Z-INDEX ALTO (> 100000):');
                let encontradosAltos = [];
                document.querySelectorAll('*').forEach((el) => {
                    const zIdx = window.getComputedStyle(el).zIndex;
                    if (zIdx && zIdx !== 'auto' && parseInt(zIdx) > 100000) {
                        encontradosAltos.push({
                            elemento: el.id ? `#${el.id}` : el.className ? `.${el.className}` : el.tagName,
                            zIndex: zIdx,
                            position: window.getComputedStyle(el).position
                        });
                    }
                });
                
                if (encontradosAltos.length === 0) {
                    console.log('    NO HAY ELEMENTOS CON Z-INDEX > 100000 (excepto Swal2)');
                } else {
                    encontradosAltos.sort((a, b) => parseInt(b.zIndex) - parseInt(a.zIndex));
                    encontradosAltos.forEach((item, idx) => {
                        console.log(`   ${idx + 1}. ${item.elemento} - z-index: ${item.zIndex}, position: ${item.position}`);
                    });
                }
                
                // Verificar si modal-editor-tallas está creando stacking context
                const editorTallas = document.getElementById('modal-editor-tallas');
                if (editorTallas) {
                    console.log(' [MODAL-CANTIDAD-EXCEDIDA] STACKING CONTEXT EDITOR-TALLAS:');
                    console.log('   - Encontrado: SI');
                    console.log('   - Z-index:', window.getComputedStyle(editorTallas).zIndex);
                    console.log('   - Position:', window.getComputedStyle(editorTallas).position);
                    console.log('   - Display:', window.getComputedStyle(editorTallas).display);
                    console.log('   - Visibility:', window.getComputedStyle(editorTallas).visibility);
                    console.log('   - Opacity:', window.getComputedStyle(editorTallas).opacity);
                }
                
                console.log(' [MODAL-CANTIDAD-EXCEDIDA] CSS PROPERTIES SWAL2:');
                console.log('   - Visibility:', window.getComputedStyle(swalContainer).visibility);
                console.log('   - Opacity:', window.getComputedStyle(swalContainer).opacity);
                console.log('   - Pointer-events:', window.getComputedStyle(swalContainer).pointerEvents);
                console.log('   - Overflow:', window.getComputedStyle(swalContainer).overflow);
                
                if (swalModal) {
                    console.log('   - swalModal visibility:', window.getComputedStyle(swalModal).visibility);
                    console.log('   - swalModal opacity:', window.getComputedStyle(swalModal).opacity);
                }
            }
        });
        */
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
    // NO tocar window.tallasRelacionales (que son las tallas de la PRENDA)
    const generoMinuscula = genero.toLowerCase();
    if (!window.tallasCantidadesProceso[generoMinuscula]) {
        window.tallasCantidadesProceso[generoMinuscula] = {};
    }
    
    if (cantidad > 0) {
        window.tallasCantidadesProceso[generoMinuscula][talla] = cantidad;
    } else {
        // Si la cantidad es 0, eliminar la talla de las cantidades del proceso
        delete window.tallasCantidadesProceso[generoMinuscula][talla];
    }
    
    // Limpiar estilos de error si la validación pasó
    input.style.borderColor = '';
    input.style.backgroundColor = '';
    
    console.log(' [actualizarCantidadTallaProceso] Actualizado en tallasCantidadesProceso:', {
        genero,
        talla,
        cantidad,
        cantidadDisponibleEnPrenda: cantidadDisponibleEnPrenda,
        estructuraActual: window.tallasCantidadesProceso
    });
};

// Cerrar editor de tallas
window.cerrarEditorTallas = function() {
    const modal = document.getElementById('modal-editor-tallas');
    if (modal) {
        console.log(' [EDITOR-TALLAS] Cerrando modal...');
        console.log(' [EDITOR-TALLAS] Z-index ANTES de cerrar:', window.getComputedStyle(modal).zIndex);
        modal.style.display = 'none';
        console.log(' [EDITOR-TALLAS] Modal cerrado. Display:', window.getComputedStyle(modal).display);
    }

};

// Guardar tallas seleccionadas desde el editor
window.guardarTallasSeleccionadas = function() {

    console.log('💾 [guardarTallasSeleccionadas] INICIANDO guardado de tallas...');
    console.log('💾 [guardarTallasSeleccionadas] Proceso actual:', procesoActual);
    console.log('💾 [guardarTallasSeleccionadas] Modo:', modoActual);
    
    // Recopilar tallas DAMA
    const checksDama = document.querySelectorAll('input[data-genero="dama"]:checked');
    window.tallasSeleccionadasProceso.dama = Array.from(checksDama).map(cb => cb.value);
    console.log('💾 [guardarTallasSeleccionadas] Tallas DAMA seleccionadas:', window.tallasSeleccionadasProceso.dama);
    
    // Recopilar tallas CABALLERO
    const checksCaballero = document.querySelectorAll('input[data-genero="caballero"]:checked');
    window.tallasSeleccionadasProceso.caballero = Array.from(checksCaballero).map(cb => cb.value);
    console.log('💾 [guardarTallasSeleccionadas] Tallas CABALLERO seleccionadas:', window.tallasSeleccionadasProceso.caballero);
    console.log('💾 [guardarTallasSeleccionadas] Cantidades por talla (proceso):', window.tallasCantidadesProceso);
    
    // IMPORTANTE: Actualizar el objeto del proceso con las tallas y cantidades
    // para que no pierda los datos cuando se cierre el modal
    if (procesoActual && window.procesosSeleccionados[procesoActual]?.datos) {
        window.procesosSeleccionados[procesoActual].datos.tallas = {
            dama: window.tallasCantidadesProceso.dama || {},
            caballero: window.tallasCantidadesProceso.caballero || {}
        };
        
        console.log(` [guardarTallasSeleccionadas] Tallas guardadas en proceso "${procesoActual}":`, {
            tallas: window.procesosSeleccionados[procesoActual].datos.tallas,
            tallasCantidadesProceso: window.tallasCantidadesProceso
        });
    } else {
        console.warn(` [guardarTallasSeleccionadas] NO SE PUDO GUARDAR: procesoActual="${procesoActual}", procesosSeleccionados exists=${!!window.procesosSeleccionados}`);
    }

    console.log('📌 [guardarTallasSeleccionadas] ESTADO ANTES DE CERRAR MODAL:');
    console.log('   - Modal editor display:', window.getComputedStyle(document.getElementById('modal-editor-tallas')).display);
    console.log('   - Modal principal display:', window.getComputedStyle(document.getElementById('modal-proceso-generico')).display);
    
    // Cerrar editor y actualizar resumen
    cerrarEditorTallas();
    
    console.log('📌 [guardarTallasSeleccionadas] ESTADO DESPUÉS DE CERRAR MODAL:');
    console.log('   - Modal editor display:', window.getComputedStyle(document.getElementById('modal-editor-tallas')).display);
    console.log('   - Modal principal display:', window.getComputedStyle(document.getElementById('modal-proceso-generico')).display);
    
    actualizarResumenTallasProceso();
    console.log(' [guardarTallasSeleccionadas] GUARDADO COMPLETADO');
};

// Actualizar resumen de tallas
window.actualizarResumenTallasProceso = function() {
    console.log('[actualizarResumenTallasProceso] 🎬 Iniciando renderización de resumen...');
    
    const resumen = document.getElementById('proceso-tallas-resumen');
    console.log('[actualizarResumenTallasProceso]  Elemento resumen encontrado?:', !!resumen);
    
    if (!resumen) {
        console.warn('[actualizarResumenTallasProceso]  NO SE ENCONTRÓ elemento #proceso-tallas-resumen');
        return;
    }
    
    console.log('[actualizarResumenTallasProceso]  window.tallasSeleccionadasProceso:', window.tallasSeleccionadasProceso);
    console.log('[actualizarResumenTallasProceso]  window.tallasCantidadesProceso:', window.tallasCantidadesProceso);
    
    const totalTallas = window.tallasSeleccionadasProceso.dama.length + window.tallasSeleccionadasProceso.caballero.length;
    const haySobremedida = window.tallasSeleccionadasProceso.sobremedida && Object.keys(window.tallasSeleccionadasProceso.sobremedida).length > 0;
    console.log('[actualizarResumenTallasProceso] 📈 Total de tallas seleccionadas:', totalTallas, ' | Hay sobremedida:', haySobremedida);
    
    if (totalTallas === 0 && !haySobremedida) {
        console.log('[actualizarResumenTallasProceso]  No hay tallas ni sobremedida seleccionadas, mostrando placeholder');
        resumen.innerHTML = '<p style="color: #9ca3af;">Selecciona tallas donde aplicar el proceso</p>';
        return;
    }
    
    let html = '<div style="display: flex; flex-direction: column; gap: 0.75rem;">';
    
    // Obtener cantidades desde tallasCantidadesProceso (ESTRUCTURA DEL PROCESO, NO DE LA PRENDA)
    const tallasProceso = window.tallasCantidadesProceso || { dama: {}, caballero: {} };
    console.log('[actualizarResumenTallasProceso]  tallasProceso para renderizar:', tallasProceso);
    
    if (window.tallasSeleccionadasProceso.dama.length > 0) {
        console.log('[actualizarResumenTallasProceso] 👩 Renderizando DAMA:', window.tallasSeleccionadasProceso.dama);
        const tallasDamaHTML = window.tallasSeleccionadasProceso.dama.map(t => {
            const cantidad = tallasProceso.dama?.[t] || 0;
            console.log(`[actualizarResumenTallasProceso] 📏 DAMA ${t}: cantidad=${cantidad}`);
            return `<span style="background: #fce7f3; color: #be185d; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem;">
                ${t}
                <span style="background: #be185d; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
            </span>`;
        }).join('');
        
        html += `
            <div>
                <strong style="color: #be185d; margin-bottom: 0.5rem; display: block;">
                    <i class="fas fa-female"></i> DAMA (${window.tallasSeleccionadasProceso.dama.length})
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${tallasDamaHTML}
                </div>
            </div>
        `;
    }
    
    if (window.tallasSeleccionadasProceso.caballero.length > 0) {
        console.log('[actualizarResumenTallasProceso] 👨 Renderizando CABALLERO:', window.tallasSeleccionadasProceso.caballero);
        const tallasCaballeroHTML = window.tallasSeleccionadasProceso.caballero.map(t => {
            const cantidad = tallasProceso.caballero?.[t] || 0;
            console.log(`[actualizarResumenTallasProceso] 📏 CABALLERO ${t}: cantidad=${cantidad}`);
            return `<span style="background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem;">
                ${t}
                <span style="background: #1d4ed8; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
            </span>`;
        }).join('');
        
        html += `
            <div>
                <strong style="color: #1d4ed8; margin-bottom: 0.5rem; display: block;">
                    <i class="fas fa-male"></i> CABALLERO (${window.tallasSeleccionadasProceso.caballero.length})
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${tallasCaballeroHTML}
                </div>
            </div>
        `;
    }
    
    // AGREGAR SOBREMEDIDA AL RESUMEN
    if (haySobremedida && window.tallasCantidadesProceso.sobremedida) {
        console.log('[actualizarResumenTallasProceso] 📐 Renderizando SOBREMEDIDA:', window.tallasCantidadesProceso.sobremedida);
        
        const sobremedidaHTML = Object.entries(window.tallasCantidadesProceso.sobremedida).map(([genero, cantidad]) => {
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
    console.log('[actualizarResumenTallasProceso] 📝 innerHTML actual:', resumen.innerHTML.substring(0, 200));
};

// Agregar proceso al pedido
window.agregarProcesoAlPedido = function() {
    if (!procesoActual) {
        alert('Error: no hay proceso seleccionado');
        return;
    }
    
    try {
        // 🔧 FIX CRÍTICO: El storage procesosImagenesStorage tiene estructura {_imagenes: {1: [], 2: [], 3: []}}
        // Las imágenes se guardan por índice numérico (1, 2, 3), NO por nombre de proceso
        console.log('[agregarProcesoAlPedido] 📸 Buscando imágenes en window.procesosImagenesStorage...');
        console.log('[agregarProcesoAlPedido] 📌 procesoActual:', procesoActual);
        console.log('[agregarProcesoAlPedido] 📌 procesoActualIndex:', window.procesoActualIndex);
        console.log('[agregarProcesoAlPedido] 📌 window.procesosImagenesStorage.obtenerImagenes:', typeof window.procesosImagenesStorage?.obtenerImagenes);
        
        // 🔴 CRÍTICO: En modo EDICIÓN, usar imagenesExistentes (que ya tiene eliminadas marcadas como null)
        // PERO TAMBIÉN capturar imágenes NUEVAS de imagenesProcesoActual (agregadas después de eliminar)
        const imagenesExistentes = (window.imagenesProcesoExistentes || []).filter(img => img !== null);
        
        let imagenesDelStorage = [];
        let imagenesNuevasAgregadas = [];
        
        // Solo obtener del storage si NO estamos en edición (creación de nuevo proceso)
        if (modoActual !== 'editar') {
            console.log('[agregarProcesoAlPedido] 🔧 Modo CREACIÓN: Buscando imágenes en storage UNIVERSAL de PROCESOS...');
            
            // ✅ CORRECCIÓN: Usar storage universal separado por tipo
            if (window.universalImagenesStorage && typeof window.universalImagenesStorage.obtenerImagenes === 'function') {
                if (window.procesoActualIndex !== undefined && window.procesoActualIndex > 0) {
                    const imagenesEnIndice = window.universalImagenesStorage.obtenerImagenes('procesos', window.procesoActualIndex);
                    console.log(`[agregarProcesoAlPedido] 🔢 Usando ÍNDICE ESPECÍFICO: ${window.procesoActualIndex} → ${imagenesEnIndice?.length || 0} imágenes de PROCESOS`);
                    if (imagenesEnIndice && imagenesEnIndice.length > 0) {
                        imagenesDelStorage = imagenesEnIndice.filter(img => img !== null);
                        console.log(`[agregarProcesoAlPedido] ✅ ENCONTRADAS ${imagenesDelStorage.length} imágenes de PROCESOS en índice ${window.procesoActualIndex}`);
                    }
                } else {
                    console.warn('[agregarProcesoAlPedido]  procesoActualIndex NO definido, buscando en índices 1-3 como fallback...');
                    // FALLBACK: Si no está definido (error), buscar en todos (pero esto no debería pasar)
                    for (let idx = 1; idx <= 3; idx++) {
                        const imagenesEnIndice = window.universalImagenesStorage.obtenerImagenes('procesos', idx);
                        console.log(`  [agregarProcesoAlPedido] Fallback: Índice ${idx}: ${imagenesEnIndice?.length || 0} imágenes de PROCESOS`);
                        if (imagenesEnIndice && imagenesEnIndice.length > 0) {
                            imagenesDelStorage = imagenesEnIndice.filter(img => img !== null);
                            console.log(`[agregarProcesoAlPedido] ⚠️ FALLBACK: ENCONTRADAS ${imagenesDelStorage.length} imágenes de PROCESOS en índice ${idx}`);
                            break;
                        }
                    }
                }
            }
            
            // Fallback: Imágenes locales del array imagenesProcesoActual
            if (imagenesDelStorage.length === 0) {
                const imagenesNuevas = imagenesProcesoActual.filter(img => img !== null);
                if (imagenesNuevas.length > 0) {
                    imagenesDelStorage = imagenesNuevas;
                    console.log('[agregarProcesoAlPedido] ✅ Fallback: Imágenes obtenidas desde imagenesProcesoActual:', imagenesDelStorage.length);
                }
            }
        } else {
            console.log('[agregarProcesoAlPedido] 🔧 Modo EDICIÓN: Usando imagenesExistentes + imágenes NUEVAS agregadas');
            // 🔴 NUEVO: En modo EDICIÓN, también capturar imágenes NUEVAS de imagenesProcesoActual
            // Esto permite agregar imágenes después de eliminar
            imagenesNuevasAgregadas = (window.imagenesProcesoActual || []).filter(img => img !== null && img instanceof File);
            console.log('[agregarProcesoAlPedido] 📸 Imágenes nuevas agregadas en edición:', imagenesNuevasAgregadas.length);
        }
        
        // 🔴 CORRECCIÓN CRÍTICA: Solo usar imágenes del proceso actual
        // No mezclar imágenes de diferentes procesos
        let imagenesFinales = [];
        
        if (modoActual === 'editar') {
            // En modo edición: usar imagenesExistentes (que ya están filtradas por eliminación)
            imagenesFinales = imagenesExistentes;
            console.log('[agregarProcesoAlPedido] 🔧 MODO EDICIÓN: Usando imágenes existentes:', imagenesFinales.length);
        } else {
            // En modo creación: SOLO usar imágenes del storage del proceso actual
            if (window.procesoActualIndex !== undefined && window.procesoActualIndex > 0) {
                imagenesFinales = imagenesDelStorage;
                console.log(`[agregarProcesoAlPedido] 🔧 MODO CREACIÓN: Usando imágenes del storage índice ${window.procesoActualIndex}:`, imagenesFinales.length);
            } else {
                console.warn('[agregarProcesoAlPedido] ⚠️ Sin índice de proceso definido, no se usarán imágenes');
                imagenesFinales = [];
            }
        }
        
        // Filtrar válidas (eliminadas marcadas como null)
        const imagenesValidas = imagenesFinales.filter(img => img !== null && img !== undefined && img !== '');
        
        console.log('[agregarProcesoAlPedido] 🖼️ IMÁGENES CAPTURADAS:', {
            modoActual: modoActual,
            procesoActualIndex: window.procesoActualIndex,
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
        // NO window.tallasRelacionales que son las cantidades DE LA PRENDA
        const cantidadOriginales = (window.imagenesProcesoExistentes || []).length;
        
        // 🔴 CRÍTICO: Usar storage de eliminadas, no nulls
        const imagenesEliminadasArray = window.imagenesEliminadasProcesoStorage || [];
        
        const datos = {
            tipo: procesoActual,
            ubicaciones: window.ubicacionesProcesoSeleccionadas,
            observaciones: document.getElementById('proceso-observaciones')?.value || '',
            tallas: {
                dama: { ...window.tallasCantidadesProceso?.dama } || {},
                caballero: { ...window.tallasCantidadesProceso?.caballero } || {},
                sobremedida: { ...window.tallasCantidadesProceso?.sobremedida } || {}
            },
            imagenes: imagenesValidas, // Array de imágenes (existentes + nuevas)
            // 🔴 CRÍTICO: imagenesEliminadas debe contener IDs de imágenes a eliminar
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
                .filter(img => img !== null && img !== undefined) // ✅ Filtrar por si acaso
        };
        
        // ✅ VERIFICAR: imagenesEliminadas se asignó correctamente
        console.log('[agregarProcesoAlPedido] ✅ OBJETO datos CONSTRUIDO:', {
            tipo: datos.tipo,
            tieneImagenes: !!datos.imagenes,
            tieneImagenesEliminadas: !!datos.imagenesEliminadas,
            imagenesEliminadasContent: datos.imagenesEliminadas,
            storageEliminadas: window.imagenesEliminadasProcesoStorage
        });
        
        console.log('[agregarProcesoAlPedido] 🔍 DEBUG imagenesEliminadas:', {
            cantidadOriginales: cantidadOriginales,
            storageLength: (window.imagenesEliminadasProcesoStorage || []).length,
            storageContent: window.imagenesEliminadasProcesoStorage,
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
            tallasCantidadesProceso: window.tallasCantidadesProceso,
            tieneUbicaciones: ubicacionesProcesoSeleccionadas.length > 0
        });
        
        // NUEVO: DIFERENCIAR ENTRE CREACIÓN Y EDICIÓN
        if (modoActual === 'crear') {
            // CREACIÓN: Guardar directamente en procesosSeleccionados (comportamiento actual)
            if (!window.procesosSeleccionados) {
                window.procesosSeleccionados = {};
            }
            
            // Si el proceso NO existe todavía, crearlo
            if (!window.procesosSeleccionados[procesoActual]) {
                window.procesosSeleccionados[procesoActual] = {
                    tipo: procesoActual,
                    indiceResultado: window.procesoActualIndex, // 🔧 Guardar el índice para futuras ediciones
                    datos: null
                };
            }
            
            // Asignar los datos capturados
            window.procesosSeleccionados[procesoActual].datos = datos;
            window.procesosSeleccionados[procesoActual].indiceResultado = window.procesoActualIndex; // Garantizar que el índice está guardado
            
            console.log('[agregarProcesoAlPedido-GUARDADO] Proceso guardado en window.procesosSeleccionados:', {
                tipo: procesoActual,
                indice: window.procesoActualIndex,
                datosGuardados: window.procesosSeleccionados[procesoActual].datos
            });
            
        } else if (modoActual === 'editar') {
            // EDICIÓN: Actualizar directamente en window.procesosSeleccionados
            console.log('✏️ [EDICIÓN] Guardando cambios del proceso');
            
            if (!window.procesosSeleccionados) {
                window.procesosSeleccionados = {};
            }
            
            // 🔴 NUEVO: Preservar el ID del proceso existente en BD
            const procesoExistente = window.procesosSeleccionados[procesoActual];
            const idExistente = procesoExistente?.datos?.id;
            const tipoProcesoId = procesoExistente?.datos?.tipo_proceso_id;
            
            if (idExistente) {
                datos.id = idExistente;
            }
            if (tipoProcesoId) {
                datos.tipo_proceso_id = tipoProcesoId;
            }
            
            // Actualizar directamente con los datos capturados del modal
            window.procesosSeleccionados[procesoActual] = {
                tipo: procesoActual,
                indiceResultado: window.procesoActualIndex,
                datos: datos
            };
            
            console.log(' [EDICIÓN] Datos actualizados en window.procesosSeleccionados:', {
                tipo: procesoActual,
                id: datos.id,
                ubicaciones: datos.ubicaciones?.length || 0,
                imagenes: datos.imagenes?.length || 0,
                tallas: datos.tallas
            });
            
            // También registrar en ProcesosEditor para tracking de cambios
            if (window.procesosEditor) {
                window.procesosEditor.registrarCambioUbicaciones(datos.ubicaciones);
                window.procesosEditor.registrarCambioImagenes(datos.imagenes);
                window.procesosEditor.registrarCambioObservaciones(datos.observaciones);
                window.procesosEditor.registrarCambioTallas(datos.tallas);
            }
            
            // Mantener buffer para compatibilidad
            cambiosProceso = datos;
        }
        
        // 🔴 NUEVO: Capturar modo ANTES de cerrar el modal (cerrar resetea modoActual a 'crear')
        const modoAntesDeCerrar = modoActual;
        console.log('[agregarProcesoAlPedido] Modo capturado antes de cerrar:', modoAntesDeCerrar);
        
        // 🔴 CRÍTICO: Resetear storage de eliminadas después de guardar
        window.imagenesEliminadasProcesoStorage = [];
        console.log('[agregarProcesoAlPedido] ✅ Storage de eliminadas reseteado después de guardar');
        
        // Cerrar modal indicando que el proceso fue guardado exitosamente
        cerrarModalProcesoGenerico(true);
        
        // Renderizar tarjetas SIEMPRE después de guardar (tanto en edición como en creación)
        if (window.renderizarTarjetasProcesos) {
            setTimeout(() => {
                console.log(` [agregarProcesoAlPedido] Renderizando tarjetas (modo: ${modoAntesDeCerrar})...`);
                window.renderizarTarjetasProcesos();
                
                // VERIFICACIÓN: Confirmar que se renderizó correctamente
                setTimeout(() => {
                    const container = document.getElementById('contenedor-tarjetas-procesos');
                    if (container) {
                        const tarjetas = container.querySelectorAll('[data-tipo-proceso]');
                        console.log(` [agregarProcesoAlPedido-VERIFY] Tarjetas renderizadas: ${tarjetas.length}`);
                        if (tarjetas.length === 0) {
                            console.warn(' [agregarProcesoAlPedido-VERIFY]  NO se encontraron tarjetas. Re-renderizando...');
                            window.renderizarTarjetasProcesos();
                        }
                    }
                }, 100);
            }, 50);
        }
        
        // Actualizar resumen en prenda modal
        if (window.actualizarResumenProcesos) {
            window.actualizarResumenProcesos();
        }
        
    } catch (error) {
        console.error('[agregarProcesoAlPedido] Error:', error);
    }
};

// NUEVO: Función para aplicar cambios del buffer cuando se hace GUARDAR CAMBIOS de la prenda
// Esta función es llamada ANTES de hacer el PATCH final
window.aplicarCambiosProcesosDesdeBuffer = function() {
    if (cambiosProceso) {
        console.log('[APLICAR-BUFFER] Aplicando cambios del proceso al procesosSeleccionados:', cambiosProceso);
        
        // Si no existe, crear
        if (!window.procesosSeleccionados) {
            window.procesosSeleccionados = {};
        }
        
        // Crear o actualizar el proceso con los cambios del buffer
        window.procesosSeleccionados[cambiosProceso.tipo] = {
            tipo: cambiosProceso.tipo,
            datos: cambiosProceso
        };
        
        console.log('[APLICAR-BUFFER]  Cambios aplicados a procesosSeleccionados');
        
        // Limpiar buffer
        cambiosProceso = null;
    }
};

// NUEVO: Función para obtener el estado actual del buffer (para debugging/validación)
window.obtenerBufferProcesoActual = function() {
    return cambiosProceso;
};

// NUEVO: Función para obtener el modo actual (para debugging)
window.obtenerModoActual = function() {
    return modoActual;
};

// Confirmar que el módulo se cargó correctamente

