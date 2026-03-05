/**
 * gestor-modal-proceso-por-tallas.js
 * 
 * Modal dedicado para configurar un proceso "Por Tallas".
 * Permite dos modos:
 * - GENERAL: Ubicación compartida + Observaciones por talla + Imágenes compartidas
 * - ESPECÍFICO: Ubicación, Observaciones e Imágenes por talla
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

// ─── Variables para modo de configuración ───
let modoModalPorTallasActual = 'general'; // 'general' o 'especifico'
let fotosGeneralesTemp = []; // Almacena URLs de fotos compartidas
let fotosGeneralesFilesTemp = []; // Almacena archivos de fotos compartidas
let ubicacionGeneralTemp = ''; // Almacena ubicación compartida

const iconosPorTallas = {
    reflectivo: 'light_mode',
    bordado: 'auto_awesome',
    estampado: 'format_paint',
    dtf: 'print',
    sublimado: 'palette'
};

/**
 * Construye la estructura imagenes_por_talla a partir de datosExtendidos
 * Transforma: datosExtendidos[genero][tallaKey] → imagenes_por_talla[genero__tallaKey]
 */
function construirImagenesPorTalla(datosExtendidos) {
    const imagenesPorTalla = {};
    
    Object.entries(datosExtendidos).forEach(([genero, tallasDatos]) => {
        if (!tallasDatos || typeof tallasDatos !== 'object') return;
        
        Object.entries(tallasDatos).forEach(([tallaKey, tallaData]) => {
            if (!tallaData || !tallaData.imagenes) return;
            
            // Clave combinada: "genero__tallaKey"
            const claveCombinada = `${genero}__${tallaKey}`;
            
            // Solo incluir si hay imágenes
            if (tallaData.imagenes && tallaData.imagenes.length > 0) {
                imagenesPorTalla[claveCombinada] = tallaData.imagenes;
            }
        });
    });
    
    return imagenesPorTalla;
}

/**
 * Convierte una clave actual (con espacios) a una clave segura (solo alfanuméricos e guiones bajos/guiones)
 * Usado para generar IDs válidos en HTML a partir de claves de datos
 */
function convertirAKeySegura(actualKey) {
    return actualKey.replace(/[^a-zA-Z0-9_-]/g, '_');
}

const nombresPorTallas = {
    reflectivo: 'Reflectivo',
    bordado: 'Bordado',
    estampado: 'Estampado',
    dtf: 'DTF',
    sublimado: 'Sublimado'
};

/**
 * Cambiar el modo de configuración del modal (General ↔ Específico)
 */
window.cambiarModoModalPorTallas = function(nuevoModo) {
    if (nuevoModo === modoModalPorTallasActual) return;

    modoModalPorTallasActual = nuevoModo;
    const btnGeneral = document.getElementById('btn-modo-general');
    const btnEspecifico = document.getElementById('btn-modo-especifico');
    const containerGeneral = document.getElementById('modo-general-container');
    const containerEspecifico = document.getElementById('modo-especifico-container');

    // Actualizar estilos de botones
    if (btnGeneral && btnEspecifico) {
        if (nuevoModo === 'general') {
            btnGeneral.style.cssText = 'padding: 0.4rem 1rem; border: 2px solid #333; border-radius: 6px; background: white; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: #333;';
            btnEspecifico.style.cssText = 'padding: 0.4rem 1rem; border: 1px solid #ddd; border-radius: 6px; background: #f9f9f9; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: #999;';
        } else {
            btnGeneral.style.cssText = 'padding: 0.4rem 1rem; border: 1px solid #ddd; border-radius: 6px; background: #f9f9f9; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: #999;';
            btnEspecifico.style.cssText = 'padding: 0.4rem 1rem; border: 2px solid #333; border-radius: 6px; background: white; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: #333;';
        }
    }

    // Mostrar/ocultar contenedores
    if (containerGeneral && containerEspecifico) {
        if (nuevoModo === 'general') {
            containerGeneral.style.display = 'block';
            containerEspecifico.style.display = 'none';
        } else {
            containerGeneral.style.display = 'none';
            containerEspecifico.style.display = 'block';
        }
    }

    console.log('[por-tallas] Cambiado a modo:', nuevoModo);
};

/**
 * Cargar fotos generales (compartidas para todas las tallas)
 */
window.cargarFotosGenerales = function(inputElement) {
    const files = Array.from(inputElement.files || []);
    if (files.length > 0) {
        agregarImagenesGenerales(files);
    }
    inputElement.value = '';
};

/**
 * Agrega imágenes a fotos generales
 */
function agregarImagenesGenerales(files) {
    const validos = Array.from(files).filter(f => f.type.startsWith('image/'));
    if (validos.length === 0) return;

    validos.forEach(file => {
        const blobUrl = URL.createObjectURL(file);
        fotosGeneralesTemp.push(blobUrl);
        fotosGeneralesFilesTemp.push(file);
    });
    renderizarGaleriaFotosGenerales();
}

/**
 * Re-renderiza la galería de fotos generales
 */
function renderizarGaleriaFotosGenerales() {
    const galeria = document.getElementById('prt-galeria-general');
    if (!galeria) return;

    const thumbs = (fotosGeneralesTemp || []).map((src, idx) => `
        <div style="position:relative; width:70px; height:70px; border-radius:6px; overflow:hidden; border:1px solid #ddd; flex-shrink:0;">
            <img src="${src}" style="width:100%;height:100%;object-fit:cover;">
            <button type="button" onclick="eliminarFotoGeneral(${idx})"
                style="position:absolute;top:2px;right:2px;background:rgba(220,38,38,0.85);color:white;border:none;border-radius:50%;width:18px;height:18px;font-size:12px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;">&times;</button>
        </div>
    `).join('');

    galeria.innerHTML = `
        ${thumbs}
        <div onclick="document.getElementById('prt-foto-input-general').click()"
            style="width: 70px; height: 70px; border: 2px dashed #ccc; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: white; flex-shrink: 0;">
            <div style="text-align:center;">
                <span class="material-symbols-rounded" style="font-size:1.3rem;color:#999;">add_photo_alternate</span>
                <div style="font-size:0.6rem;color:#999;">Agregar</div>
            </div>
        </div>
    `;

    // Reconfigurar drag & drop después de redibujar
    configurarDragDropPasteGeneral();
}

/**
 * Crear tarjeta simplificada para modo general (solo observaciones)
 */
function crearTarjetaTallaGeneral(genero, tallaKey, cantidad, datos) {
    const parts = String(tallaKey).split('__');
    const tallaDisplay = parts[0] || tallaKey;
    const colorDisplay = parts[1] || null;
    const etiqueta = colorDisplay ? `${tallaDisplay} - ${colorDisplay}` : tallaDisplay;
    
    const actualKey = `${genero}__${tallaKey}`;
    const safeKey = actualKey.replace(/[^a-zA-Z0-9_-]/g, '_');
    const checkboxId = `checkbox-mg-${safeKey}`;
    const inputCantidadId = `cantidad-mg-${safeKey}`;

    const card = document.createElement('div');
    card.style.cssText = `border: 1px solid #ddd; border-radius: 8px; background: #fafafa; padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem; transition: opacity 0.2s, background-color 0.2s;`;

    // Header con Checkbox
    const header = document.createElement('div');
    header.style.cssText = 'display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;';
    
    const checkboxDiv = document.createElement('div');
    checkboxDiv.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; flex: 1;';
    checkboxDiv.innerHTML = `
        <input type="checkbox" id="${checkboxId}" checked style="width: 18px; height: 18px; cursor: pointer;">
        <label for="${checkboxId}" style="cursor: pointer; display: flex; align-items: center; gap: 0.5rem; flex: 1;">
            <span style="font-weight: 600; font-size: 1rem; color: #333;">${etiqueta}</span>
        </label>
    `;
    header.appendChild(checkboxDiv);

    // Cantidad editable
    const cantidadDiv = document.createElement('div');
    cantidadDiv.style.cssText = 'display: flex; align-items: center; gap: 0.3rem;';
    cantidadDiv.innerHTML = `
        <input type="number" id="${inputCantidadId}" min="0" value="${datos.cantidadSeleccionada || cantidad}" 
            style="width: 50px; padding: 0.3rem 0.5rem; border: 1px solid #ddd; border-radius: 4px; text-align: center; font-size: 0.85rem; font-weight: 600;">
        <span style="font-size: 0.8rem; font-weight: 600; color: #666;">und</span>
    `;
    header.appendChild(cantidadDiv);
    card.appendChild(header);

    // Contenido: Solo Observaciones
    const contenidoDiv = document.createElement('div');
    contenidoDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.75rem;';
    
    const obsDiv = document.createElement('div');
    obsDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.35rem;';
    obsDiv.innerHTML = `
        <label style="font-size: 0.8rem; font-weight: 600; color: #555; display: flex; align-items: center; gap: 0.35rem;">
            <span class="material-symbols-rounded" style="font-size: 1rem;">description</span>Observaciones
        </label>
        <textarea class="prt-observaciones-general" data-key="${actualKey}" placeholder="Instrucciones especiales para esta talla..."
            style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px; font-size: 0.85rem; min-height: 55px; resize: vertical; box-sizing: border-box;"
        >${datos.observaciones || ''}</textarea>
    `;
    contenidoDiv.appendChild(obsDiv);
    card.appendChild(contenidoDiv);

    return card;
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
    
    // Limpiar contenedores del modo general
    const contDamaGeneral = document.getElementById('tallas-dama-modo-general');
    const secDamaGeneral = document.getElementById('seccion-dama-modo-general');
    const contCabGeneral = document.getElementById('tallas-caballero-modo-general');
    const secCabGeneral = document.getElementById('seccion-caballero-modo-general');
    
    if (contDamaGeneral) contDamaGeneral.innerHTML = '';
    if (contCabGeneral) contCabGeneral.innerHTML = '';
    
    // Limpiar galería de fotos generales
    const galeriaGeneral = document.getElementById('prt-galeria-general');
    if (galeriaGeneral) galeriaGeneral.innerHTML = '';
    
    // Marcar como no configurado para reconfigurar
    if (galeriaGeneral) galeriaGeneral._dragDropConfigured = false;

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
    
    console.log('[por-tallas] 🔍 DEBUGGING abrirModalProcesoPorTallas', {
        tipoProceso,
        procesoCompleto: window.procesosSeleccionados?.[tipoProceso],
        datosCompletos: window.procesosSeleccionados?.[tipoProceso]?.datos,
        datosExistentes,
        tieneDatatos: !!datosExistentes,
        estructuraDatos: datosExistentes ? Object.keys(datosExistentes) : 'VACIO'
    });

    // ─── Recuperar datos generales si existen ───
    const datosGenerales = window.procesosSeleccionados?.[tipoProceso]?.datos;
    if (datosGenerales?.ubicacionGeneral) {
        ubicacionGeneralTemp = datosGenerales.ubicacionGeneral;
        const inputUbicacion = document.getElementById('ubicacion-general-input');
        if (inputUbicacion) inputUbicacion.value = ubicacionGeneralTemp;
    }
    if (datosGenerales?.fotosGenerales && Array.isArray(datosGenerales.fotosGenerales)) {
        fotosGeneralesTemp = [...datosGenerales.fotosGenerales];
    }
    
    // Redibujar galería de fotos generales (con o sin fotos existentes)
    renderizarGaleriaFotosGenerales();

    // Renderizar DAMA
    if (tallasDama.length > 0 && contDama) {
        secDama.style.display = 'block';
        tallasDama.forEach(([tallaKey, cantidad]) => {
            const key = `dama__${tallaKey}`;
            const existente = datosExistentes?.dama?.[tallaKey];
            datosPorTallaTemp[key] = {
                seleccionada: true,
                cantidadSeleccionada: existente?.cantidadSeleccionada || cantidad,
                ubicaciones: existente?.ubicaciones ? [...existente.ubicaciones] : [],
                observaciones: existente?.observaciones || '',
                imagenes: existente?.imagenes ? [...existente.imagenes] : (existente?.imagen ? [existente.imagen] : []),
                imagenesFiles: []
            };
            contDama.appendChild(crearTarjetaTalla('dama', tallaKey, cantidad, datosPorTallaTemp[key]));
        });
        
        // También renderizar para modo general
        if (contDamaGeneral && secDamaGeneral) {
            secDamaGeneral.style.display = 'block';
            tallasDama.forEach(([tallaKey, cantidad]) => {
                const key = `dama__${tallaKey}`;
                const datosCard = datosPorTallaTemp[key] || { cantidadSeleccionada: cantidad, observaciones: '' };
                contDamaGeneral.appendChild(crearTarjetaTallaGeneral('dama', tallaKey, cantidad, datosCard));
            });
        }
    } else {
        if (secDama) secDama.style.display = 'none';
        if (document.getElementById('seccion-dama-modo-general')) {
            document.getElementById('seccion-dama-modo-general').style.display = 'none';
        }
    }

    // Renderizar CABALLERO
    if (tallasCaballero.length > 0 && contCab) {
        secCab.style.display = 'block';
        tallasCaballero.forEach(([tallaKey, cantidad]) => {
            const key = `caballero__${tallaKey}`;
            const existente = datosExistentes?.caballero?.[tallaKey];
            datosPorTallaTemp[key] = {
                seleccionada: true,
                cantidadSeleccionada: existente?.cantidadSeleccionada || cantidad,
                ubicaciones: existente?.ubicaciones ? [...existente.ubicaciones] : [],
                observaciones: existente?.observaciones || '',
                imagenes: existente?.imagenes ? [...existente.imagenes] : (existente?.imagen ? [existente.imagen] : []),
                imagenesFiles: []
            };
            contCab.appendChild(crearTarjetaTalla('caballero', tallaKey, cantidad, datosPorTallaTemp[key]));
        });
        
        // También renderizar para modo general
        if (contCabGeneral && secCabGeneral) {
            secCabGeneral.style.display = 'block';
            tallasCaballero.forEach(([tallaKey, cantidad]) => {
                const key = `caballero__${tallaKey}`;
                const datosCard = datosPorTallaTemp[key] || { cantidadSeleccionada: cantidad, observaciones: '' };
                contCabGeneral.appendChild(crearTarjetaTallaGeneral('caballero', tallaKey, cantidad, datosCard));
            });
        }
    } else {
        if (secCab) secCab.style.display = 'none';
        if (document.getElementById('seccion-caballero-modo-general')) {
            document.getElementById('seccion-caballero-modo-general').style.display = 'none';
        }
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
    // IMPORTANTE: Usar actualKey (con espacios) para data-key, y safeKey (con underscores) solo para IDs HTML
    const actualKey = `${genero}__${tallaKey}`;
    const safeKey = actualKey.replace(/[^a-zA-Z0-9_-]/g, '_');
    const checkboxId = `checkbox-${safeKey}`;
    const inputCantidadId = `cantidad-${safeKey}`;
    
    // DEBUG
    console.log(`[crearTarjetaTalla] Creando tarjeta para ${actualKey}:`, {
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
    const thumbsExistentes = (datos.imagenes || []).map((src, idx) => `
        <div style="position:relative; width:70px; height:70px; border-radius:6px; overflow:hidden; border:2px solid ${borderColor}; flex-shrink:0;">
            <img src="${src}" style="width:100%;height:100%;object-fit:cover;">
            <button type="button" onclick="eliminarFotoPorTalla('${actualKey}', ${idx})"
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
        <input type="file" id="${inputFileId}" accept="image/*" multiple style="display:none;" onchange="cargarFotosPorTalla('${actualKey}', this)">
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
                const nuevaCantidad = Math.max(0, parseInt(inputCantidad.value) || cantidad);
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

    // ─── Manejo para FOTOS GENERALES ───
    if (tallaActivaParaPaste === 'GENERAL') {
        agregarImagenesGenerales(files);
        const galeria = document.getElementById('prt-galeria-general');
        if (galeria) {
            galeria.style.outline = '2px solid #22c55e';
            galeria.style.outlineOffset = '2px';
            setTimeout(() => { galeria.style.outline = ''; galeria.style.outlineOffset = ''; }, 600);
        }
        return;
    }

    // ─── Manejo para FOTOS POR TALLA (específico) ───
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
        const safeKey = convertirAKeySegura(targetKey);
        const gal = document.getElementById(`prt-galeria-${safeKey}`);
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

    console.log('[GUARDAR-POR-TALLAS] Iniciando guardado del proceso:', procesoPorTallasActual, 'Modo:', modoModalPorTallasActual);

    // ─── RECOGER DATOS SEGÚN EL MODO ACTUAL ───
    if (modoModalPorTallasActual === 'general') {
        // Modo general: ubicación general + observaciones por talla + fotos generales
        ubicacionGeneralTemp = document.getElementById('ubicacion-general-input')?.value || '';
        
        // Recoger observaciones generales
        document.querySelectorAll('.prt-observaciones-general').forEach(textarea => {
            const key = textarea.dataset.key;
            const valor = textarea.value;
            if (datosPorTallaTemp[key]) {
                datosPorTallaTemp[key].observaciones = valor;
            }
        });

        // Recoger cantidades generales
        document.querySelectorAll('[id^="cantidad-mg-"]').forEach(input => {
            const id = input.id.replace('cantidad-mg-', '');
            // Necesitamos encontrar la clave real desde el IDs
            const tallaKey = Object.keys(datosPorTallaTemp).find(k => 
                k.replace(/[^a-zA-Z0-9_-]/g, '_') === id
            );
            if (tallaKey && datosPorTallaTemp[tallaKey]) {
                datosPorTallaTemp[tallaKey].cantidadSeleccionada = Math.max(0, parseInt(input.value) || 0);
            }
        });

        // Recoger checkboxes generales
        document.querySelectorAll('[id^="checkbox-mg-"]').forEach(checkbox => {
            const id = checkbox.id.replace('checkbox-mg-', '');
            const tallaKey = Object.keys(datosPorTallaTemp).find(k => 
                k.replace(/[^a-zA-Z0-9_-]/g, '_') === id
            );
            if (tallaKey && datosPorTallaTemp[tallaKey]) {
                datosPorTallaTemp[tallaKey].seleccionada = checkbox.checked;
            }
        });
    } else {
        // Modo específico: recoger todo (ubicación, observaciones e imágenes por talla)
        document.querySelectorAll('.prt-ubicacion-input').forEach(textarea => {
            const key = textarea.dataset.key;
            const valor = textarea.value;
            if (datosPorTallaTemp[key]) {
                datosPorTallaTemp[key].ubicaciones = valor
                    .split(',')
                    .map(u => u.trim())
                    .filter(u => u.length > 0);
            }
        });

        document.querySelectorAll('.prt-observaciones').forEach(textarea => {
            const key = textarea.dataset.key;
            const valor = textarea.value;
            if (datosPorTallaTemp[key]) {
                datosPorTallaTemp[key].observaciones = valor;
            }
        });

        document.querySelectorAll('[id^="cantidad-"]:not([id^="cantidad-mg-"])').forEach(input => {
            const id = input.id.replace('cantidad-', '');
            const key = id;
            if (datosPorTallaTemp[key]) {
                datosPorTallaTemp[key].cantidadSeleccionada = Math.max(0, parseInt(input.value) || 0);
            }
        });

        document.querySelectorAll('[id^="checkbox-"]:not([id^="checkbox-mg-"])').forEach(checkbox => {
            const id = checkbox.id.replace('checkbox-', '');
            const key = id;
            if (datosPorTallaTemp[key]) {
                datosPorTallaTemp[key].seleccionada = checkbox.checked;
            }
        });
    }

    console.log('[GUARDAR-POR-TALLAS] datosPorTallaTemp DESPUÉS de recolectar:', JSON.stringify(datosPorTallaTemp, null, 2));

    // Construir estructura de tallas y datosExtendidos
    const tallas = { dama: {}, caballero: {}, sobremedida: {} };
    const datosExtendidos = { dama: {}, caballero: {}, sobremedida: {} };

    // Obtener cantidades de la prenda
    const tallasPrenda = (typeof obtenerTallasDeLaPrenda === 'function')
        ? obtenerTallasDeLaPrenda()
        : { dama: {}, caballero: {}, sobremedida: null };

    Object.entries(datosPorTallaTemp).forEach(([key, datos]) => {
        // Solo guardar si está seleccionada
        if (!datos.seleccionada) return;

        // key = "dama__M" o "caballero__L"
        const sepIdx = key.indexOf('__');
        const genero = key.substring(0, sepIdx);
        const tallaKey = key.substring(sepIdx + 2);

        // Usar la cantidad seleccionada (editada) en lugar de la cantidad original
        tallas[genero][tallaKey] = datos.cantidadSeleccionada;

        console.log('[GUARDAR-POR-TALLAS] Guardando datosExtendidos para:', key, {
            cantidadSeleccionada: datos.cantidadSeleccionada,
            ubicaciones: datos.ubicaciones,
            observaciones: datos.observaciones,
            imagenesCount: datos.imagenes?.length,
            imagenesFilesCount: datos.imagenesFiles?.length
        });

        datosExtendidos[genero][tallaKey] = {
            cantidadSeleccionada: datos.cantidadSeleccionada,
            ubicaciones: modoModalPorTallasActual === 'general' ? [] : (datos.ubicaciones || []),
            observaciones: datos.observaciones || '',
            imagenes: modoModalPorTallasActual === 'general' ? [] : (datos.imagenes || []),
            imagenesFiles: modoModalPorTallasActual === 'general' ? [] : (datos.imagenesFiles || [])
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
        ubicaciones: modoModalPorTallasActual === 'general' ? [ubicacionGeneralTemp] : [],
        observaciones: '',
        tallas: tallas,
        imagenes: fotosGeneralesTemp,
        imagenesEliminadas: [],
        datosExtendidos: datosExtendidos,
        modoTallas: modoModalPorTallasActual,  // Guardar el modo: 'general' o 'especifico'
        ubicacionGeneral: modoModalPorTallasActual === 'general' ? ubicacionGeneralTemp : '',
        fotosGenerales: modoModalPorTallasActual === 'general' ? fotosGeneralesTemp : [],
        imagenes_por_talla: construirImagenesPorTalla(datosExtendidos)
    };

    console.log('[por-tallas] Proceso guardado:', procesoPorTallasActual, 'Modo:', modoModalPorTallasActual, window.procesosSeleccionados[procesoPorTallasActual]);

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
    fotosGeneralesTemp = [];
    fotosGeneralesFilesTemp = [];
    ubicacionGeneralTemp = '';
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
    modoModalPorTallasActual = 'general';  // Resetear a modo general para próxima vez
    fotosGeneralesTemp = [];
    fotosGeneralesFilesTemp = [];
    ubicacionGeneralTemp = '';
};

/**
 * Elimina una foto general (compartida)
 */
window.eliminarFotoGeneral = function(index) {
    if (index >= 0 && index < fotosGeneralesTemp.length) {
        // Revocar URL del blob para liberar memoria
        URL.revokeObjectURL(fotosGeneralesTemp[index]);
        fotosGeneralesTemp.splice(index, 1);
        fotosGeneralesFilesTemp.splice(index, 1);
        renderizarGaleriaFotosGenerales();
    }
};

/**
 * Configura drag & drop y paste para fotos generales (compartidas)
 */
function configurarDragDropPasteGeneral() {
    const galeria = document.getElementById('prt-galeria-general');
    if (!galeria || galeria._dragDropConfigured) return;

    // ─── Drag Over ───
    galeria.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        galeria.style.outline = '2px dashed #333';
        galeria.style.outlineOffset = '2px';
        galeria.style.background = '#f0f0f0';
    });

    // ─── Drag Leave ───
    galeria.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        galeria.style.outline = '';
        galeria.style.outlineOffset = '';
        galeria.style.background = '';
    });

    // ─── Drop ───
    galeria.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        galeria.style.outline = '';
        galeria.style.outlineOffset = '';
        galeria.style.background = '';

        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            agregarImagenesGenerales(e.dataTransfer.files);
        }
    });

    // ─── Mouse Enter: Marcar para paste ───
    galeria.addEventListener('mouseenter', () => {
        tallaActivaParaPaste = 'GENERAL';
    });

    // Marcar como configurado para evitar listeners duplicados
    galeria._dragDropConfigured = true;
};
