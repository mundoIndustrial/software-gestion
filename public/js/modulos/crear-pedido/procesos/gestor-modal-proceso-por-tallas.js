/**
 * gestor-modal-proceso-por-tallas.js
 * 
 * Modal dedicado para configurar un proceso "Por Tallas".
 * Cada talla de la prenda muestra sus propios campos de:
 * - Ubicación (con lista)
 * - Observaciones (textarea)
 * - Foto (una por talla)
 * 
 * Se guarda en procesosSeleccionados con la misma estructura que el modal genérico
 * pero adicionando datosExtendidos por talla.
 */

let procesoPorTallasActual = null;

// Almacén temporal de datos por talla mientras el modal está abierto
let datosPorTallaTemp = {};
// { 'dama__M': { ubicaciones: ['Frente'], observaciones: 'texto', imagenes: [blobUrl, ...], imagenesFiles: [File, ...] }, ... }

// Talla activa para paste (Ctrl+V) — se actualiza al hacer click o hover en una tarjeta
let tallaActivaParaPaste = null;

const iconosPorTallas = {
    reflectivo: 'light_mode',
    bordado: 'auto_awesome',
    estampado: 'format_paint',
    dtf: 'print',
    sublimado: 'palette'
};

const nombresPorTallas = {
    reflectivo: 'Reflectivo',
    bordado: 'Bordado',
    estampado: 'Estampado',
    dtf: 'DTF',
    sublimado: 'Sublimado'
};

/**
 * Abre el modal de proceso por tallas
 */
window.abrirModalProcesoPorTallas = function(tipoProceso) {
    procesoPorTallasActual = tipoProceso;
    datosPorTallaTemp = {};

    const modal = document.getElementById('modal-proceso-por-tallas');
    if (!modal) {
        console.error('[por-tallas] Modal no encontrado');
        return;
    }

    // Actualizar header
    const iconEl = document.getElementById('modal-por-tallas-icon');
    const tituloEl = document.getElementById('modal-por-tallas-titulo');
    if (iconEl) iconEl.textContent = iconosPorTallas[tipoProceso] || 'edit_note';
    if (tituloEl) tituloEl.textContent = `${nombresPorTallas[tipoProceso] || tipoProceso} — Por Tallas`;

    // Obtener tallas de la prenda
    const tallasPrenda = (typeof obtenerTallasDeLaPrenda === 'function')
        ? obtenerTallasDeLaPrenda()
        : { dama: {}, caballero: {}, sobremedida: null };

    const tallasDama = Object.entries(tallasPrenda.dama || {});
    const tallasCaballero = Object.entries(tallasPrenda.caballero || {});

    const secDama = document.getElementById('seccion-dama-por-tallas');
    const secCab = document.getElementById('seccion-caballero-por-tallas');
    const sinTallas = document.getElementById('sin-tallas-por-tallas');
    const contDama = document.getElementById('tallas-dama-por-tallas');
    const contCab = document.getElementById('tallas-caballero-por-tallas');

    // Limpiar contenedores
    if (contDama) contDama.innerHTML = '';
    if (contCab) contCab.innerHTML = '';

    if (tallasDama.length === 0 && tallasCaballero.length === 0) {
        if (secDama) secDama.style.display = 'none';
        if (secCab) secCab.style.display = 'none';
        if (sinTallas) sinTallas.style.display = 'block';
        modal.style.display = 'flex';
        return;
    }

    if (sinTallas) sinTallas.style.display = 'none';

    // Restaurar datos si el proceso ya tiene datos guardados
    const datosExistentes = window.procesosSeleccionados?.[tipoProceso]?.datos?.datosExtendidos;

    // Renderizar DAMA
    if (tallasDama.length > 0 && contDama) {
        secDama.style.display = 'block';
        tallasDama.forEach(([tallaKey, cantidad]) => {
            const key = `dama__${tallaKey}`;
            const existente = datosExistentes?.dama?.[tallaKey];
            datosPorTallaTemp[key] = {
                ubicaciones: existente?.ubicaciones ? [...existente.ubicaciones] : [],
                observaciones: existente?.observaciones || '',
                imagenes: existente?.imagenes ? [...existente.imagenes] : (existente?.imagen ? [existente.imagen] : []),
                imagenesFiles: []
            };
            contDama.appendChild(crearTarjetaTalla('dama', tallaKey, cantidad, datosPorTallaTemp[key]));
        });
    } else {
        if (secDama) secDama.style.display = 'none';
    }

    // Renderizar CABALLERO
    if (tallasCaballero.length > 0 && contCab) {
        secCab.style.display = 'block';
        tallasCaballero.forEach(([tallaKey, cantidad]) => {
            const key = `caballero__${tallaKey}`;
            const existente = datosExistentes?.caballero?.[tallaKey];
            datosPorTallaTemp[key] = {
                ubicaciones: existente?.ubicaciones ? [...existente.ubicaciones] : [],
                observaciones: existente?.observaciones || '',
                imagenes: existente?.imagenes ? [...existente.imagenes] : (existente?.imagen ? [existente.imagen] : []),
                imagenesFiles: []
            };
            contCab.appendChild(crearTarjetaTalla('caballero', tallaKey, cantidad, datosPorTallaTemp[key]));
        });
    } else {
        if (secCab) secCab.style.display = 'none';
    }

    modal.style.display = 'flex';
    
    // Activar listener global de paste (Ctrl+V) en capture phase
    // para interceptar ANTES de que contenteditable lo capture
    document.addEventListener('paste', handlePasteGlobalPorTallas, true);
};

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
    const safeKey = `${genero}__${tallaKey}`.replace(/[^a-zA-Z0-9_-]/g, '_');

    const card = document.createElement('div');
    card.style.cssText = `border: 2px solid ${borderColor}; border-radius: 12px; background: ${bgColor}; padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;`;

    // ─── Header ───
    const header = document.createElement('div');
    header.style.cssText = 'display: flex; justify-content: space-between; align-items: center;';
    header.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-weight: 900; font-size: 1.1rem; color: ${accentColor};">${etiqueta}</span>
            <span style="font-size: 0.75rem; background: ${accentColor}; color: white; padding: 0.15rem 0.5rem; border-radius: 9999px; font-weight: 700;">${cantidad} und</span>
        </div>
    `;
    card.appendChild(header);

    // ─── Ubicación ───
    const ubicDiv = document.createElement('div');
    ubicDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.35rem;';
    ubicDiv.innerHTML = `
        <label style="font-size: 0.8rem; font-weight: 700; color: #374151; display: flex; align-items: center; gap: 0.35rem;">
            <span class="material-symbols-rounded" style="font-size: 1rem;">location_on</span>Ubicación(es)
        </label>
        <textarea class="prt-ubicacion-input" data-key="${safeKey}" placeholder="Ej: Frente, Espalda..."
            style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.85rem; min-height: 55px; resize: vertical; box-sizing: border-box;"
        >${(datos.ubicaciones || []).join(', ')}</textarea>
    `;
    card.appendChild(ubicDiv);

    // ─── Observaciones ───
    const obsDiv = document.createElement('div');
    obsDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.35rem;';
    obsDiv.innerHTML = `
        <label style="font-size: 0.8rem; font-weight: 700; color: #374151; display: flex; align-items: center; gap: 0.35rem;">
            <span class="material-symbols-rounded" style="font-size: 1rem;">description</span>Observaciones
        </label>
        <textarea class="prt-observaciones" data-key="${safeKey}" placeholder="Instrucciones especiales para esta talla..."
            style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.85rem; min-height: 55px; resize: vertical;"
        >${datos.observaciones}</textarea>
    `;
    card.appendChild(obsDiv);

    // ─── Fotos (múltiples) ───
    const fotoDiv = document.createElement('div');
    fotoDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.35rem;';
    const inputFileId = `prt-foto-input-${safeKey}`;
    const galeriaId = `prt-galeria-${safeKey}`;

    // Generar thumbnails de imágenes existentes
    const thumbsExistentes = (datos.imagenes || []).map((src, idx) => `
        <div style="position:relative; width:70px; height:70px; border-radius:6px; overflow:hidden; border:2px solid ${borderColor}; flex-shrink:0;">
            <img src="${src}" style="width:100%;height:100%;object-fit:cover;">
            <button type="button" onclick="eliminarFotoPorTalla('${safeKey}', ${idx})"
                style="position:absolute;top:2px;right:2px;background:rgba(220,38,38,0.85);color:white;border:none;border-radius:50%;width:18px;height:18px;font-size:12px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;">&times;</button>
        </div>
    `).join('');

    fotoDiv.innerHTML = `
        <label style="font-size: 0.8rem; font-weight: 700; color: #374151; display: flex; align-items: center; gap: 0.35rem;">
            <span class="material-symbols-rounded" style="font-size: 1rem;">photo_camera</span>Fotos
        </label>
        <div id="${galeriaId}" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: flex-start;">
            ${thumbsExistentes}
            <div onclick="document.getElementById('${inputFileId}').click()"
                style="width: 70px; height: 70px; border: 2px dashed ${dashedColor}; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: white; flex-shrink: 0;">
                <div style="text-align:center;">
                    <span class="material-symbols-rounded" style="font-size:1.3rem;color:#9ca3af;">add_photo_alternate</span>
                    <div style="font-size:0.6rem;color:#9ca3af;">Agregar</div>
                </div>
            </div>
        </div>
        <input type="file" id="${inputFileId}" accept="image/*" multiple style="display:none;" onchange="cargarFotosPorTalla('${safeKey}', this)">
    `;
    card.appendChild(fotoDiv);

    // ─── Configurar Drag & Drop + Paste en la galería ───
    setTimeout(() => {
        const galeria = document.getElementById(galeriaId);
        if (galeria) configurarDragDropPasteTalla(galeria, safeKey);
    }, 0);

    return card;
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
    // ── Drag & Drop ──
    galeria.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        galeria.style.outline = '2px dashed #3b82f6';
        galeria.style.outlineOffset = '2px';
        galeria.style.background = '#eff6ff';
    });

    galeria.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        galeria.style.outline = '';
        galeria.style.outlineOffset = '';
        galeria.style.background = '';
    });

    galeria.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        galeria.style.outline = '';
        galeria.style.outlineOffset = '';
        galeria.style.background = '';

        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            agregarImagenesATalla(key, e.dataTransfer.files);
        }
    });

    // ── Marcar talla activa al interactuar (click o mouseenter) ──
    const card = galeria.closest('div[style*="border-radius: 12px"]');
    const marcarActiva = () => { tallaActivaParaPaste = key; };
    
    galeria.addEventListener('click', marcarActiva);
    galeria.addEventListener('mouseenter', marcarActiva);
    if (card) {
        card.addEventListener('click', marcarActiva);
        card.addEventListener('mouseenter', marcarActiva);
    }
}

/**
 * Handler global de paste (Ctrl+V) — activo mientras el modal por tallas esté abierto
 */
function handlePasteGlobalPorTallas(e) {
    // Solo procesar si el modal está visible
    const modal = document.getElementById('modal-proceso-por-tallas');
    if (!modal || modal.style.display === 'none') return;

    // Ignorar si el focus está en un input/textarea (el usuario está escribiendo)
    const tag = document.activeElement?.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA') return;

    const items = e.clipboardData?.items;
    if (!items) return;
    
    const files = [];
    for (let i = 0; i < items.length; i++) {
        if (items[i].type.startsWith('image/')) {
            const file = items[i].getAsFile();
            if (file) files.push(file);
        }
    }
    if (files.length === 0) return;

    e.preventDefault();
    e.stopPropagation();

    // Determinar a qué talla agregar
    let targetKey = tallaActivaParaPaste;

    // Si no hay talla activa, usar la primera disponible
    if (!targetKey || !datosPorTallaTemp[targetKey]) {
        const keys = Object.keys(datosPorTallaTemp);
        if (keys.length > 0) targetKey = keys[0];
    }

    if (targetKey && datosPorTallaTemp[targetKey]) {
        agregarImagenesATalla(targetKey, files);
        // Flash visual en la galería destino
        const gal = document.getElementById(`prt-galeria-${targetKey}`);
        if (gal) {
            gal.style.outline = '2px solid #22c55e';
            gal.style.outlineOffset = '2px';
            setTimeout(() => { gal.style.outline = ''; gal.style.outlineOffset = ''; }, 600);
        }
    }
}



/**
 * Carga múltiples fotos para una talla (desde input file)
 */
window.cargarFotosPorTalla = function(key, input) {
    if (!input.files || input.files.length === 0) return;
    agregarImagenesATalla(key, input.files);
    input.value = '';
};

/**
 * Elimina una foto específica de una talla por índice
 */
window.eliminarFotoPorTalla = function(key, index) {
    if (!datosPorTallaTemp[key]) return;
    const imgs = datosPorTallaTemp[key].imagenes;
    if (index < 0 || index >= imgs.length) return;

    // Revocar blob URL
    if (imgs[index] && imgs[index].startsWith('blob:')) {
        URL.revokeObjectURL(imgs[index]);
    }
    imgs.splice(index, 1);
    datosPorTallaTemp[key].imagenesFiles.splice(index, 1);
    renderizarGaleriaFotos(key);
};

/**
 * Re-renderiza la galería de fotos para una talla
 */
function renderizarGaleriaFotos(key) {
    const galeria = document.getElementById(`prt-galeria-${key}`);
    if (!galeria || !datosPorTallaTemp[key]) return;

    const datos = datosPorTallaTemp[key];
    const keyParts = key.split('__');
    const esRosa = keyParts[0] === 'dama';
    const borderColor = esRosa ? '#fbcfe8' : '#93c5fd';
    const dashedColor = esRosa ? '#f9a8d4' : '#93c5fd';
    const inputFileId = `prt-foto-input-${key}`;

    const thumbs = (datos.imagenes || []).map((src, idx) => `
        <div style="position:relative; width:70px; height:70px; border-radius:6px; overflow:hidden; border:2px solid ${borderColor}; flex-shrink:0;">
            <img src="${src}" style="width:100%;height:100%;object-fit:cover;">
            <button type="button" onclick="eliminarFotoPorTalla('${key}', ${idx})"
                style="position:absolute;top:2px;right:2px;background:rgba(220,38,38,0.85);color:white;border:none;border-radius:50%;width:18px;height:18px;font-size:12px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;">&times;</button>
        </div>
    `).join('');

    galeria.innerHTML = `
        ${thumbs}
        <div onclick="document.getElementById('${inputFileId}').click()"
            style="width: 70px; height: 70px; border: 2px dashed ${dashedColor}; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: white; flex-shrink: 0;">
            <div style="text-align:center;">
                <span class="material-symbols-rounded" style="font-size:1.3rem;color:#9ca3af;">add_photo_alternate</span>
                <div style="font-size:0.6rem;color:#9ca3af;">Agregar</div>
            </div>
        </div>
    `;
}

/**
 * Guardar proceso con datos por talla
 */
window.guardarProcesoPorTallas = function() {
    if (!procesoPorTallasActual) return;

    // Recoger ubicaciones de los textareas
    document.querySelectorAll('.prt-ubicacion-input').forEach(textarea => {
        const key = textarea.dataset.key;
        if (datosPorTallaTemp[key]) {
            datosPorTallaTemp[key].ubicaciones = textarea.value
                .split(',')
                .map(u => u.trim())
                .filter(u => u.length > 0);
        }
    });

    // Recoger observaciones de los textareas
    document.querySelectorAll('.prt-observaciones').forEach(textarea => {
        const key = textarea.dataset.key;
        if (datosPorTallaTemp[key]) {
            datosPorTallaTemp[key].observaciones = textarea.value;
        }
    });

    // Construir estructura de tallas y datosExtendidos
    const tallas = { dama: {}, caballero: {}, sobremedida: {} };
    const datosExtendidos = { dama: {}, caballero: {}, sobremedida: {} };

    // Obtener cantidades de la prenda
    const tallasPrenda = (typeof obtenerTallasDeLaPrenda === 'function')
        ? obtenerTallasDeLaPrenda()
        : { dama: {}, caballero: {}, sobremedida: null };

    Object.entries(datosPorTallaTemp).forEach(([key, datos]) => {
        // key = "dama__M" o "caballero__L"
        const sepIdx = key.indexOf('__');
        const genero = key.substring(0, sepIdx);
        const tallaKey = key.substring(sepIdx + 2);

        // Copiar cantidad de la prenda
        if (tallasPrenda[genero] && tallasPrenda[genero][tallaKey] !== undefined) {
            tallas[genero][tallaKey] = tallasPrenda[genero][tallaKey];
        }

        datosExtendidos[genero][tallaKey] = {
            ubicaciones: datos.ubicaciones || [],
            observaciones: datos.observaciones || '',
            imagenes: datos.imagenes || [],
            imagenesFiles: datos.imagenesFiles || []
        };
    });

    // Guardar en procesosSeleccionados
    if (!window.procesosSeleccionados) {
        window.procesosSeleccionados = {};
    }

    if (!window.procesosSeleccionados[procesoPorTallasActual]) {
        window.procesosSeleccionados[procesoPorTallasActual] = {
            tipo: procesoPorTallasActual,
            datos: null
        };
    }

    window.procesosSeleccionados[procesoPorTallasActual].modoTallas = 'por_tallas';
    window.procesosSeleccionados[procesoPorTallasActual].datos = {
        tipo: procesoPorTallasActual,
        ubicaciones: [],
        observaciones: '',
        tallas: tallas,
        imagenes: [],
        imagenesEliminadas: [],
        datosExtendidos: datosExtendidos,
        modoTallas: 'por_tallas'  // NUEVO: Incluir modo_tallas dentro de datos también
    };

    console.log('[por-tallas] Proceso guardado:', procesoPorTallasActual, window.procesosSeleccionados[procesoPorTallasActual]);

    // Guardar en procesosGuardados si existe
    if (window.procesosGuardados) {
        window.procesosGuardados[procesoPorTallasActual] = { ...window.procesosSeleccionados[procesoPorTallasActual] };
    }

    // Actualizar resumen visual
    if (typeof actualizarResumenProcesos === 'function') {
        actualizarResumenProcesos();
    }
    
    // Renderizar tarjeta del proceso si existe la función
    if (typeof window.renderizarTarjetasProcesos === 'function') {
        window.renderizarTarjetasProcesos();
    }

    // Cerrar modal
    const modal = document.getElementById('modal-proceso-por-tallas');
    if (modal) modal.style.display = 'none';

    document.removeEventListener('paste', handlePasteGlobalPorTallas, true);
    tallaActivaParaPaste = null;
    procesoPorTallasActual = null;
    datosPorTallaTemp = {};
};

/**
 * Cerrar modal sin guardar
 */
window.cerrarModalProcesoPorTallas = function() {
    const modal = document.getElementById('modal-proceso-por-tallas');
    if (modal) modal.style.display = 'none';

    document.removeEventListener('paste', handlePasteGlobalPorTallas, true);
    tallaActivaParaPaste = null;

    // Desmarcar checkbox si no tiene datos guardados
    if (procesoPorTallasActual) {
        const yaGuardado = window.procesosSeleccionados?.[procesoPorTallasActual]?.datos;
        if (!yaGuardado) {
            const checkbox = document.getElementById(`checkbox-${procesoPorTallasActual}`);
            if (checkbox) {
                checkbox._ignorarOnclick = true;
                checkbox.checked = false;
                checkbox._ignorarOnclick = false;
            }
            delete window.procesosSeleccionados[procesoPorTallasActual];
            if (typeof actualizarResumenProcesos === 'function') {
                actualizarResumenProcesos();
            }
        }
    }

    procesoPorTallasActual = null;
    datosPorTallaTemp = {};
};
