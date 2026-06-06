/**
 * Crea la tarjeta HTML para una talla con campos de ubicación, observaciones y foto
 */
function crearTarjetaTalla(genero, tallaKey, cantidad, datos) {
    const parts = String(tallaKey).split('__');
    const tallaDisplay = parts[0] || tallaKey;
    const colorDisplay = parts[1] || null;
    const etiqueta = colorDisplay ? `${tallaDisplay} - ${colorDisplay}` : tallaDisplay;
    const esRosa = genero === 'dama';
    const borderColor = esRosa ? '#fbcfe8' : '#93c5fd';
    const bgColor = esRosa ? '#fdf2f8' : '#eff6ff';
    const accentColor = esRosa ? '#ec4899' : '#1d4ed8';
    const dashedColor = esRosa ? '#f9a8d4' : '#93c5fd';
    // IMPORTANTE: Usar actualKey (con espacios) para data-key, y safeKey (con underscores) solo para IDs HTML
    const actualKey = `${genero}__${tallaKey}`;
    const safeKey = convertirAKeySegura(actualKey);
    const checkboxId = `checkbox-${safeKey}`;
    const inputCantidadId = `cantidad-${safeKey}`;
    
    // DEBUG
    _log(`[crearTarjetaTalla] Creando tarjeta para ${actualKey}:`, {
        ubicaciones: datos.ubicaciones,
        observaciones: datos.observaciones,
        imagenes: datos.imagenes,
        tieneUbicaciones: !!(datos.ubicaciones && datos.ubicaciones.length > 0),
        tieneObservaciones: !!datos.observaciones
    });

    const card = document.createElement('div');
    card.style.cssText = `border: 2px solid ${borderColor}; border-radius: 12px; background: ${bgColor}; padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem; transition: opacity 0.2s, background-color 0.2s;`;
    card.id = `tarjeta-${safeKey}`;

    // ─── Header con Checkbox ───
    const header = document.createElement('div');
    header.style.cssText = 'display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;';
    
    const checkboxDiv = document.createElement('div');
    checkboxDiv.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; flex: 1;';
    checkboxDiv.innerHTML = `
        <input type="checkbox" id="${checkboxId}" checked style="width: 18px; height: 18px; cursor: pointer;">
        <label for="${checkboxId}" style="cursor: pointer; display: flex; align-items: center; gap: 0.5rem; flex: 1;">
            <span style="font-weight: 900; font-size: 1.1rem; color: ${accentColor};">${etiqueta}</span>
        </label>
    `;
    header.appendChild(checkboxDiv);

    // ─── Cantidad editable ───
    const cantidadDiv = document.createElement('div');
    cantidadDiv.style.cssText = 'display: flex; align-items: center; gap: 0.3rem;';
    cantidadDiv.innerHTML = `
        <input type="number" id="${inputCantidadId}" min="0" value="${datos.cantidadSeleccionada || cantidad}" 
            style="width: 50px; padding: 0.3rem 0.5rem; border: 1px solid ${accentColor}; border-radius: 4px; text-align: center; font-size: 0.85rem; font-weight: 700;">
        <span style="font-size: 0.8rem; font-weight: 700; color: #6b7280;">und</span>
    `;
    header.appendChild(cantidadDiv);

    card.appendChild(header);

    // Crear contenedor para el resto del contenido (se ocultará si se deselecciona)
    const contenidoDiv = document.createElement('div');
    contenidoDiv.id = `contenido-${safeKey}`;
    contenidoDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.75rem; transition: opacity 0.2s;';

    // ─── Ubicación ───
    const ubicDiv = document.createElement('div');
    ubicDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.35rem;';
    ubicDiv.innerHTML = `
        <label style="font-size: 0.8rem; font-weight: 700; color: #374151; display: flex; align-items: center; gap: 0.35rem;">
            <span class="material-symbols-rounded" style="font-size: 1rem;">location_on</span>Ubicación(es)
        </label>
        <textarea class="prt-ubicacion-input" data-key="${actualKey}" placeholder="Ej: Frente, Espalda..."
            style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.85rem; min-height: 55px; resize: vertical; box-sizing: border-box;"
        >${(datos.ubicaciones || []).join(', ')}</textarea>
    `;
    contenidoDiv.appendChild(ubicDiv);

    // ─── Observaciones ───
    const obsDiv = document.createElement('div');
    obsDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.35rem;';
    obsDiv.innerHTML = `
        <label style="font-size: 0.8rem; font-weight: 700; color: #374151; display: flex; align-items: center; gap: 0.35rem;">
            <span class="material-symbols-rounded" style="font-size: 1rem;">description</span>Observaciones
        </label>
        <textarea class="prt-observaciones" data-key="${actualKey}" placeholder="Instrucciones especiales para esta talla..."
            style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.85rem; min-height: 55px; resize: vertical;"
        >${datos.observaciones}</textarea>
    `;
    contenidoDiv.appendChild(obsDiv);

    // ─── Fotos (múltiples) ───
    const fotoDiv = document.createElement('div');
    fotoDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.35rem;';
    const inputFileId = `prt-foto-input-${safeKey}`;
    const galeriaId = `prt-galeria-${safeKey}`;

    // Generar thumbnails de imágenes existentes
    const thumbsExistentes = (datos.imagenes || []).map((item, idx) => {
        const src = _resolverSrcImagen(item);
        return `
        <div style="position:relative; width:70px; height:70px; border-radius:6px; overflow:hidden; border:2px solid ${borderColor}; flex-shrink:0;">
            <img src="${src}" style="width:100%;height:100%;object-fit:cover;">
            <button type="button" data-prt-action="eliminar-foto-talla" data-key="${actualKey}" data-index="${idx}"
                style="position:absolute;top:2px;right:2px;background:rgba(220,38,38,0.85);color:white;border:none;border-radius:50%;width:18px;height:18px;font-size:12px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;">&times;</button>
        </div>
    `;
    }).join('');

    fotoDiv.innerHTML = `
        <label style="font-size: 0.8rem; font-weight: 700; color: #374151; display: flex; align-items: center; gap: 0.35rem;">
            <span class="material-symbols-rounded" style="font-size: 1rem;">photo_camera</span>Fotos
        </label>
        <div id="${galeriaId}" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: flex-start;">
            ${thumbsExistentes}
            ${_crearBotonAgregarGaleriaHTML(inputFileId, { dashedColor })}
        </div>
        <input type="file" id="${inputFileId}" data-prt-action="cargar-fotos-talla" data-key="${actualKey}" accept="image/*" multiple style="display:none;">
    `;
    contenidoDiv.appendChild(fotoDiv);

    card.appendChild(contenidoDiv);

    // ─── Event Listeners para Checkbox y Cantidad ───
    setTimeout(() => {
        const checkbox = document.getElementById(checkboxId);
        const inputCantidad = document.getElementById(inputCantidadId);

        if (checkbox) {
            checkbox.addEventListener('change', () => {
                datosPorTallaTemp[actualKey].seleccionada = checkbox.checked;
                // Cambiar opacidad del contenido
                contenidoDiv.style.opacity = checkbox.checked ? '1' : '0.5';
                contenidoDiv.style.pointerEvents = checkbox.checked ? 'auto' : 'none';
            });
        }

        if (inputCantidad) {
            inputCantidad.addEventListener('change', () => {
                const nuevaCantidad = Math.max(0, Number(inputCantidad.value) || cantidad);
                datosPorTallaTemp[actualKey].cantidadSeleccionada = nuevaCantidad;
                inputCantidad.value = nuevaCantidad;
            });
        }

        // Configurar Drag & Drop + Paste en la galería
        const galeria = document.getElementById(galeriaId);
        if (galeria) configurarDragDropPasteTalla(galeria, actualKey);
    }, 0);

    return card;
}

/**
 * Handler de dragLeave reutilizable para galerías
 */
function _crearHandlerDragLeave() {
    return (e) => {
        e.preventDefault();
        e.stopPropagation();
        const galeria = e.currentTarget;
        galeria.style.outline = '';
        galeria.style.outlineOffset = '';
        galeria.style.background = '';
    };
}

/**
 * Agrega imágenes (File[]) a una talla — función centralizada
 */
function agregarImagenesATalla(key, files) {
    if (!datosPorTallaTemp[key]) return;
    const validos = Array.from(files).filter(f => f.type.startsWith('image/'));
    if (validos.length === 0) return;

    validos.forEach(file => {
        const blobUrl = URL.createObjectURL(file);
        datosPorTallaTemp[key].imagenes.push(blobUrl);
        datosPorTallaTemp[key].imagenesFiles.push(file);
    });
    renderizarGaleriaFotos(key);
}

/**
 * Configura drag & drop y tracking de talla activa para paste
 */
function configurarDragDropPasteTalla(galeria, key) {
    // Si ya está configurado, evitar agregar listeners duplicados
    if (galeria._dragDropPastaTallaConfigured) return;

    // ── Drag & Drop ──
    const manejadorDragOver = (e) => {
        e.preventDefault();
        e.stopPropagation();
        _aplicarEstiloDragActivo(galeria, { outlineColor: '#3b82f6', backgroundColor: '#eff6ff' });
    };
    galeria.addEventListener('dragover', manejadorDragOver);

    const manejadorDragLeave = _crearHandlerDragLeave();
    galeria.addEventListener('dragleave', manejadorDragLeave);

    const manejadorDrop = _crearManejadorDropGaleria(galeria, (files) => agregarImagenesATalla(key, files));
    galeria.addEventListener('drop', manejadorDrop);

    // ── Marcar talla activa al interactuar (click o mouseenter) ──
    const card = galeria.closest('div[style*="border-radius: 12px"]');
    const marcarActiva = () => { tallaActivaParaPaste = key; };
    
    galeria.addEventListener('click', marcarActiva);
    galeria.addEventListener('mouseenter', marcarActiva);
    if (card) {
        card.addEventListener('click', marcarActiva);
        card.addEventListener('mouseenter', marcarActiva);
    }

    // Marcar como configurado
    galeria._dragDropPastaTallaConfigured = true;
}

/**
 * Handler global de paste (Ctrl+V) — activo mientras el modal por tallas esté abierto
 */
// ────────────────────────────────────────────────────────────────────────────────
// HELPER FUNCTIONS - handlePasteGlobalPorTallas refactoring
// ────────────────────────────────────────────────────────────────────────────────

/**
 * Valida si el paste debe ser procesado
 */
function _validarPasteEventoValido(e) {
    const modal = document.getElementById('modal-proceso-por-tallas');
    if (!modal?.style.display || modal.style.display === 'none') return null;
    
    if (!modal.contains(e.target)) return null;
    
    const modalEditWizard = document.getElementById('modal-editar-asignacion-wizard');
    if (modalEditWizard && modalEditWizard.style.display !== 'none' && modalEditWizard.classList.contains('show')) {
        return null;
    }
    
    return modal;
}

/**
 * Valida si el focus actual permite paste
 */
function _validarFocusPermitePaste() {
    const activeEl = document.activeElement;
    const tag = activeEl?.tagName;
    return !(tag === 'INPUT' || tag === 'TEXTAREA' || activeEl?.isContentEditable || activeEl?.getAttribute('contenteditable') === 'true');
}

/**
 * Extrae archivos de imagen desde clipboard items
 */
function _extraerArchivosDelClipboard(items) {
    const files = [];
    for (const item of items) {
        if (item.type.startsWith('image/')) {
            const file = item.getAsFile();
            if (file) files.push(file);
        }
    }
    return files;
}

/**
 * Aplica feedback visual a una galería
 */
function _aplicarFeedbackVisual(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.outline = '2px solid #22c55e';
        element.style.outlineOffset = '2px';
        setTimeout(() => {
            element.style.outline = '';
            element.style.outlineOffset = '';
        }, 600);
    }
}

/**
 * Maneja paste para fotos generales
 */
function _manejarPasteGeneral(files) {
    _log('[handlePasteGlobalPorTallas]  Agregando a GENERAL');
    agregarImagenesGenerales(files);
    _aplicarFeedbackVisual('prt-galeria-general');
}

/**
 * Maneja paste para fotos por talla
 */
function _manejarPastePosTalla(files) {
    let targetKey = tallaActivaParaPaste;
    
    if (!targetKey || !datosPorTallaTemp[targetKey]) {
        const keys = Object.keys(datosPorTallaTemp);
        if (keys.length === 0) return;
        targetKey = keys[0];
    }
    
    _log('[handlePasteGlobalPorTallas]  Agregando a talla:', targetKey);
    agregarImagenesATalla(targetKey, files);
    const safeKey = convertirAKeySegura(targetKey);
    _aplicarFeedbackVisual(`prt-galeria-${safeKey}`);
}

/**
 * Handler principal de paste global
 */
function handlePasteGlobalPorTallas(e) {
    _log('[handlePasteGlobalPorTallas]  PASTE event processing');

    // ─── Validaciones iniciales ───
    const modal = _validarPasteEventoValido(e);
    if (!modal) {
        _log('[handlePasteGlobalPorTallas]  Validación fallida, ignorando paste');
        return;
    }

    if (!_validarFocusPermitePaste()) {
        _log('[handlePasteGlobalPorTallas]  Focus en input/textarea/contenteditable - ignorando');
        return;
    }

    const items = e.clipboardData?.items;
    if (!items) {
        _log('[handlePasteGlobalPorTallas]  No clipboard items');
        return;
    }

    // ─── Extrae imágenes del clipboard ───
    const files = _extraerArchivosDelClipboard(items);
    _log('[handlePasteGlobalPorTallas]  Archivos detectados:', files.length);
    if (files.length === 0) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    try {
        // ─── Determina destino (general o talla específica) ───
        const galeriaGeneral = document.getElementById('prt-galeria-general');
        const targetIsInGeneral = galeriaGeneral?.contains(e.target);
        
        if (tallaActivaParaPaste === 'GENERAL' || targetIsInGeneral) {
            _manejarPasteGeneral(files);
        } else {
            _manejarPastePosTalla(files);
        }
    } catch (error) {
        console.error('[handlePasteGlobalPorTallas]  Error:', error);
    }
}



/**
 * Carga múltiples fotos para una talla (desde input file)
 */
function cargarFotosPorTalla(key, input) {
    if (!input.files || input.files.length === 0) return;
    agregarImagenesATalla(key, input.files);
    input.value = '';
};

/**
 * Elimina una foto específica de una talla por índice
 */
function eliminarFotoPorTalla(key, index) {
    if (!datosPorTallaTemp[key]) return;
    const imgs = datosPorTallaTemp[key].imagenes;
    if (index < 0 || index >= imgs.length) return;

    // Revocar blob URL
    const src = _resolverSrcImagen(imgs[index]);
    if (typeof src === 'string' && src.startsWith('blob:')) {
        URL.revokeObjectURL(src);
    }
    imgs.splice(index, 1);
    datosPorTallaTemp[key].imagenesFiles.splice(index, 1);
    renderizarGaleriaFotos(key);
};

/**
 * Re-renderiza la galería de fotos para una talla
 */
function renderizarGaleriaFotos(key) {
    // key es la actualKey con espacios, convertir a safeKey para buscar elementos DOM
    const safeKey = convertirAKeySegura(key);
    const galeria = document.getElementById(`prt-galeria-${safeKey}`);
    if (!galeria || !datosPorTallaTemp[key]) return;

    const datos = datosPorTallaTemp[key];
    const keyParts = key.split('__');
    const esRosa = keyParts[0] === 'dama';
    const borderColor = esRosa ? '#fbcfe8' : '#93c5fd';
    const dashedColor = esRosa ? '#f9a8d4' : '#93c5fd';
    const inputFileId = `prt-foto-input-${safeKey}`;

    const thumbs = (datos.imagenes || []).map((item, idx) => {
        const src = _resolverSrcImagen(item);
        
        return `
        <div style="position:relative; width:70px; height:70px; border-radius:6px; overflow:hidden; border:2px solid ${borderColor}; flex-shrink:0;">
            <img src="${src}" style="width:100%;height:100%;object-fit:cover;">
            <button type="button" data-prt-action="eliminar-foto-talla" data-key="${key}" data-index="${idx}"
                style="position:absolute;top:2px;right:2px;background:rgba(220,38,38,0.85);color:white;border:none;border-radius:50%;width:18px;height:18px;font-size:12px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;">&times;</button>
        </div>
    `;
    }).join('');

    galeria.innerHTML = `
        ${thumbs}
        ${_crearBotonAgregarGaleriaHTML(inputFileId, { dashedColor })}
    `;

    // Resetear flag de configuración para que se reconfiguren los listeners
    galeria._dragDropPastaTallaConfigured = false;

    // Reconfigurar drag & drop después de redibujar
    configurarDragDropPasteTalla(galeria, key);
}

function _obtenerImagenesValidasSegunModo(datos) {
    if (modoModalPorTallasActual === 'general') return [];

    return (datos.imagenes || []).filter((img) => {
        if (typeof img === 'string') {
            if (modoModalPorTallasActual === 'especifico') {
                return true;
            }
            return !img.startsWith('blob:') && !img.startsWith('data:');
        }
        if (typeof img === 'object' && img) {
            const url = img.url || img.ruta_webp || img.ruta_original || img.ruta || '';
            return typeof url === 'string' && !url.startsWith('blob:') && !url.startsWith('data:');
        }
        return false;
    });
}

function _construirTallasYDatosExtendidosDesdeTemp() {
    const tallas = { dama: {}, caballero: {}, sobremedida: {} };
    const datosExtendidos = { dama: {}, caballero: {}, sobremedida: {} };

    Object.entries(datosPorTallaTemp).forEach(([key, datos]) => {
        if (!datos.seleccionada) return;

        const sepIdx = key.indexOf('__');
        const genero = key.substring(0, sepIdx);
        const tallaKey = key.substring(sepIdx + 2);

        tallas[genero][tallaKey] = datos.cantidadSeleccionada;

        _log('[GUARDAR-POR-TALLAS] Guardando datosExtendidos para:', key, {
            cantidadSeleccionada: datos.cantidadSeleccionada,
            ubicaciones: datos.ubicaciones,
            observaciones: datos.observaciones,
            imagenesCount: datos.imagenes?.length,
            imagenesFilesCount: datos.imagenesFiles?.length,
            imagenesSample: datos.imagenes?.slice(0, 2).map((img) => typeof img === 'string' ? img.substring(0, 60) : typeof img),
            imagenesFilesSample: datos.imagenesFiles?.slice(0, 2).map((file) => file instanceof File ? `File: ${file.name}` : typeof file)
        });

        datosExtendidos[genero][tallaKey] = {
            cantidadSeleccionada: datos.cantidadSeleccionada,
            ubicaciones: modoModalPorTallasActual === 'general' ? [] : (datos.ubicaciones || []),
            observaciones: datos.observaciones || '',
            imagenes: _obtenerImagenesValidasSegunModo(datos),
            imagenesFiles: modoModalPorTallasActual === 'general' ? [] : (datos.imagenesFiles || [])
        };
    });

    return { tallas, datosExtendidos };
}

function _asegurarProcesoSeleccionadoActual() {
    if (!globalThis.procesosSeleccionados) {
        globalThis.procesosSeleccionados = {};
    }

    if (!globalThis.procesosSeleccionados[procesoPorTallasActual]) {
        globalThis.procesosSeleccionados[procesoPorTallasActual] = {
            tipo: procesoPorTallasActual,
            datos: null
        };
    }
}

function _construirDatosProcesoParaGuardar(tallas, datosExtendidos) {
    const tallasCanonicas = globalThis.ProcesoTallasCanonicas
        ? globalThis.ProcesoTallasCanonicas.desdeAgrupadas(tallas)
        : [];

    return {
        tipo: procesoPorTallasActual,
        ubicaciones: modoModalPorTallasActual === 'general' ? [ubicacionGeneralTemp] : [],
        observaciones: '',
        tallas: tallas,
        tallasCanonicas: tallasCanonicas,
        //  FIX: Copiar File objects de imagenesFiles a imagenes para que FormData los encuentre
        imagenes: modoModalPorTallasActual === 'general' ? fotosGeneralesFilesTemp : [],
        imagenesFiles: fotosGeneralesFilesTemp,
        fotosGeneralesFiles: fotosGeneralesFilesTemp,
        imagenes_existentes: fotosGeneralesExistentes,
        imagenes_a_eliminar: fotosGeneralesEliminadas,
        imagenesEliminadas: [],
        datosExtendidos: datosExtendidos,
        modo_tallas: modoModalPorTallasActual,
        ubicacionGeneral: modoModalPorTallasActual === 'general' ? ubicacionGeneralTemp : '',
        fotosGenerales: modoModalPorTallasActual === 'general' ? [...fotosGeneralesExistentes] : [],
        imagenes_por_talla: construirImagenesPorTalla(datosExtendidos)
    };
}

/**
 * Guardar proceso con datos por talla
 */
function guardarProcesoPorTallas() {
    if (!procesoPorTallasActual) return;

    _log('[GUARDAR-POR-TALLAS] Iniciando guardado del proceso:', procesoPorTallasActual, 'Modo:', modoModalPorTallasActual);
    _log('[GUARDAR-POR-TALLAS] datosPorTallaTemp ANTES de recolectar:', JSON.stringify(datosPorTallaTemp, null, 2));

    // ─── RECOGER DATOS SEGÚN EL MODO ACTUAL ───
    if (modoModalPorTallasActual === 'general') {
        PorTallasModulos.estado.recolectarDatosModoGeneral();
    } else {
        PorTallasModulos.estado.recolectarDatosModoEspecifico();
    }

    _log('[GUARDAR-POR-TALLAS] datosPorTallaTemp DESPUÉS de recolectar:', JSON.stringify(datosPorTallaTemp, null, 2));

    const { tallas, datosExtendidos } = PorTallasModulos.persistencia.construirTallasYDatosExtendidos();

    PorTallasModulos.persistencia.asegurarProcesoSeleccionado();
    globalThis.procesosSeleccionados[procesoPorTallasActual].datos = PorTallasModulos.persistencia.construirDatosProceso(tallas, datosExtendidos);

    _log('[GUARDAR-POR-TALLAS]  DATOS GUARDADOS EN SESIÓN - modo_tallas:', modoModalPorTallasActual, {
        tallasTotales: Object.keys(tallas.dama).length + Object.keys(tallas.caballero).length + Object.keys(tallas.sobremedida).length,
        datosExtendidosTotales: Object.values(datosExtendidos).reduce((acc, g) => acc + Object.keys(g).length, 0),
        modoGuardado: modoModalPorTallasActual
    });

    _log('[por-tallas] Proceso guardado:', procesoPorTallasActual, 'Modo:', modoModalPorTallasActual, {
        tipo: procesoPorTallasActual,
        fotosExistentes: fotosGeneralesExistentes.length,
        fotosNuevas: fotosGeneralesTemp.length,
        fotosEliminadas: fotosGeneralesEliminadas.length,
        archivosNuevos: fotosGeneralesFilesTemp.length,
        proceso: globalThis.procesosSeleccionados[procesoPorTallasActual]
    });

    // Guardar en procesosGuardados si existe
    if (globalThis.procesosGuardados) {
        globalThis.procesosGuardados[procesoPorTallasActual] = { ...globalThis.procesosSeleccionados[procesoPorTallasActual] };
    }

    // Actualizar resumen visual
    if (typeof actualizarResumenProcesos === 'function') {
        actualizarResumenProcesos();
    }
    
    // Renderizar tarjeta del proceso si existe la función
    if (typeof globalThis.renderizarTarjetasProcesos === 'function') {
        globalThis.renderizarTarjetasProcesos();
    }


    // Cerrar modal
    const modal = document.getElementById('modal-proceso-por-tallas');
    PorTallasModulos.ui.mostrarModal(modal, false);

    PorTallasModulos.estado.limpiarEstadoModal();
};

/**
 * Cerrar modal sin guardar
 */
function cerrarModalProcesoPorTallas() {
    const modal = document.getElementById('modal-proceso-por-tallas');
    PorTallasModulos.ui.mostrarModal(modal, false);

    // Desmarcar checkbox si no tiene datos guardados
    if (procesoPorTallasActual) {
        const yaGuardado = globalThis.procesosSeleccionados?.[procesoPorTallasActual]?.datos;
        if (!yaGuardado) {
            const checkbox = document.getElementById(`checkbox-${procesoPorTallasActual}`);
            if (checkbox) {
                checkbox._ignorarOnclick = true;
                checkbox.checked = false;
                checkbox._ignorarOnclick = false;
            }
            delete globalThis.procesosSeleccionados[procesoPorTallasActual];
            if (typeof actualizarResumenProcesos === 'function') {
                actualizarResumenProcesos();
            }
        }
    }

    PorTallasModulos.estado.limpiarEstadoModal();
};

/**
 * Elimina una foto general (compartida)
 */
// ────────────────────────────────────────────────────────────────────────────────
// HELPER FUNCTIONS - eliminarFotoGeneral refactoring
// ────────────────────────────────────────────────────────────────────────────────

/**
 * Convierte una foto (string u objeto) a estructura normalizada
 */
function _normalizarImagenParaEliminar(fotoEliminada) {
    if (typeof fotoEliminada === 'string') {
        return {
            url: fotoEliminada,
            ruta_original: fotoEliminada
        };
    }
    
    if (typeof fotoEliminada === 'object') {
        return {
            id: fotoEliminada.id || undefined,
            url: fotoEliminada.url || fotoEliminada.ruta_original || fotoEliminada.ruta_webp || '',
            ruta_original: fotoEliminada.ruta_original || fotoEliminada.url || fotoEliminada.ruta_webp || '',
            ruta_webp: fotoEliminada.ruta_webp || ''
        };
    }
    
    return null;
}

/**
 * Elimina foto existente de BD
 */
function _eliminarFotoExistenteBD(index) {
    if (index >= fotosGeneralesExistentes.length) return;
    
    const fotoEliminada = fotosGeneralesExistentes[index];
    _log('[eliminarFotoGeneral] Eliminando foto existente de BD:', index, fotoEliminada);
    
    const imagenAEliminar = _normalizarImagenParaEliminar(fotoEliminada);
    
    if (imagenAEliminar && (imagenAEliminar.url || imagenAEliminar.id)) {
        fotosGeneralesEliminadas.push(imagenAEliminar);
        _log('[eliminarFotoGeneral] Foto eliminada registrada:', imagenAEliminar);
    }
    
    fotosGeneralesExistentes.splice(index, 1);
    _log('[eliminarFotoGeneral] Fotos eliminadas registradas (total):', fotosGeneralesEliminadas.length);
}

/**
 * Elimina foto nueva agregada por usuario
 */
function _eliminarFotoNuevaAñadida(index) {
    const indiceNueva = index - fotosGeneralesExistentes.length;
    
    if (indiceNueva < 0 || indiceNueva >= fotosGeneralesTemp.length) return;
    
    _log('[eliminarFotoGeneral] Eliminando foto nueva:', indiceNueva);
    
    // Revocar URL del blob para liberar memoria
    const blobUrl = fotosGeneralesTemp[indiceNueva];
    if (typeof blobUrl === 'string' && blobUrl.startsWith('blob:')) {
        URL.revokeObjectURL(blobUrl);
    }
    
    fotosGeneralesTemp.splice(indiceNueva, 1);
    fotosGeneralesFilesTemp.splice(indiceNueva, 1);
}

/**
 * Elimina foto general (existente o nueva)
 */
function eliminarFotoGeneral(index, esExistente) {
    if (esExistente) {
        _eliminarFotoExistenteBD(index);
    } else {
        _eliminarFotoNuevaAñadida(index);
    }
    renderizarGaleriaFotosGenerales();
};

/**
 * Configura drag & drop y paste para fotos generales (compartidas)
 */
function configurarDragDropPasteGeneral() {
    const galeria = document.getElementById('prt-galeria-general');
    if (!galeria) return;

    // Si ya está completamente configurado, NO hacer nada para evitar listeners duplicados
    if (galeria._dragDropConfigured) return;

    // ─── Drag Over ───
    const manejadorDragOver = (e) => {
        e.preventDefault();
        e.stopPropagation();
        _aplicarEstiloDragActivo(galeria, { outlineColor: '#333', backgroundColor: '#f0f0f0' });
    };
    galeria.addEventListener('dragover', manejadorDragOver);

    // ─── Drag Leave ───
    const manejadorDragLeave = _crearHandlerDragLeave();
    galeria.addEventListener('dragleave', manejadorDragLeave);

    // ─── Drop ───
    const manejadorDrop = _crearManejadorDropGaleria(galeria, (files) => agregarImagenesGenerales(files));
    galeria.addEventListener('drop', manejadorDrop);

    // ─── Mouse Enter: Marcar para paste ───
    const manejadorMouseEnter = () => {
        tallaActivaParaPaste = 'GENERAL';
    };
    galeria.addEventListener('mouseenter', manejadorMouseEnter);
    
    // ─── Click: También marcar para paste por si el usuario hace click sin mover el mouse ───
    const manejadorClick = () => {
        tallaActivaParaPaste = 'GENERAL';
        _log('[configurarDragDropPasteGeneral]  Área general activada para paste');
    };
    galeria.addEventListener('click', manejadorClick);

    // Guardar referencias para poder remover después si es necesario
    galeria._manejadorDragOver = manejadorDragOver;
    galeria._manejadorDragLeave = manejadorDragLeave;
    galeria._manejadorDrop = manejadorDrop;
    galeria._manejadorMouseEnter = manejadorMouseEnter;
    galeria._manejadorClick = manejadorClick;

    // Marcar como configurado para evitar listeners duplicados
    galeria._dragDropConfigured = true;
};
