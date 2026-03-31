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
let fotosGeneralesExistentes = []; // URLs de BD que ya existen (no modificar)
let fotosGeneralesEliminadas = []; // URLs de BD que fueron ELIMINADAS por el usuario
let fotosGeneralesTemp = []; // NUEVAS fotos agregadas por el usuario
let fotosGeneralesFilesTemp = []; // Archivos de fotos nuevas compartidas
let ubicacionGeneralTemp = ''; // Almacena ubicación compartida

// ─── Variables para manejo de listener de paste ───
// El listener se registra en el modal, no en document (mejor práctica)
let modalPasteListenerRegistrado = false;

// ─── Flag para evitar procesar múltiples pastes simultáneos ───
let procesandoPasteEvent = false;

// ─── Listener global de PASTE en document (capture phase) ───
globalThis.pasteListenerPorTallas = function(e) {
    const modal = document.getElementById('modal-proceso-por-tallas');
    if (!modal || modal.style.display === 'none') return;
    
    console.log('[pasteListenerPorTallas]  PASTE detectado en document');
    handlePasteGlobalPorTallas(e);
};

// Registrar listener globalmente en capture phase (máxima prioridad)
document.addEventListener('paste', globalThis.pasteListenerPorTallas, true);

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
            if (!tallaData?.imagenes) return;

            // Clave combinada: "genero__tallaKey"
            const claveCombinada = `${genero}__${tallaKey}`;

            // Solo incluir si hay imágenes
            if (tallaData.imagenes?.length > 0) {
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
    return actualKey.replaceAll(/[^a-zA-Z0-9_-]/g, '_');
}

const nombresPorTallas = {
    reflectivo: 'Reflectivo',
    bordado: 'Bordado',
    estampado: 'Estampado',
    dtf: 'DTF',
    sublimado: 'Sublimado'
};

/**
 * Registra el listener de paste EN EL MODAL (mejor práctica que listener global)
 * Se ejecuta solo una vez al abrir el modal
 */
function registrarListenerPasteEnModal() {
    // El listener ya está registrado globalmente en document (capture phase)
    // No necesitamos hacer nada aquí
    console.log('[PASTE-LISTENER]  Listener global ya activo en document');
}

/**
 * Desregistra el listener de paste del modal al cerrarlo
 */
function desregistrarListenerPasteDelModal() {
    // El listener global permanece activo pero no procesará nada si el modal está oculto
    console.log('[PASTE-LISTENER] Modal cerrado, listener global permanece en standby');
}

/**
 * Cambiar el modo de configuración del modal (General ↔ Específico)
 */
globalThis.cambiarModoModalPorTallas = function(nuevoModo) {
    const btnGeneral = document.getElementById('btn-modo-general');
    const btnEspecifico = document.getElementById('btn-modo-especifico');
    
    if (nuevoModo === modoModalPorTallasActual) return;

    modoModalPorTallasActual = nuevoModo;
    const containerGeneral = document.getElementById('modo-general-container');
    const containerEspecifico = document.getElementById('modo-especifico-container');

    // Actualizar estilos de botones - Ambos siempre habilitados
    if (btnGeneral && btnEspecifico) {
        if (nuevoModo === 'general') {
            // Modo GENERAL activo
            btnGeneral.style.cssText = 'padding: 0.4rem 1rem; border: 2px solid #333; border-radius: 6px; background: white; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: #333; opacity: 1;';
            btnGeneral.disabled = false;
            
            btnEspecifico.style.cssText = 'padding: 0.4rem 1rem; border: 1px solid #ddd; border-radius: 6px; background: #f9f9f9; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: #999; opacity: 0.6;';
            btnEspecifico.disabled = false;
        } else {
            // Modo ESPECÍFICO activo
            btnGeneral.style.cssText = 'padding: 0.4rem 1rem; border: 1px solid #ddd; border-radius: 6px; background: #f9f9f9; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: #999; opacity: 0.6;';
            btnGeneral.disabled = false;
            
            btnEspecifico.style.cssText = 'padding: 0.4rem 1rem; border: 2px solid #333; border-radius: 6px; background: white; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: #333; opacity: 1;';
            btnEspecifico.disabled = false;
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

    //  Registrar cambio de modo en el editor de procesos
    if (globalThis.procesosEditor && procesoPorTallasActual) {
        globalThis.procesosEditor.registrarCambioModoTallas(nuevoModo);
        console.log('[por-tallas]Cambio de modo registrado en editor:', nuevoModo);
    }

    console.log('[por-tallas] Cambiado a modo:', nuevoModo);
};

/**
 * Cargar fotos generales (compartidas para todas las tallas)
 */
globalThis.cargarFotosGenerales = function(inputElement) {
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

    console.log('[agregarImagenesGenerales]  Intentando agregar', validos.length, 'imágenes');
    
    // Procesar cada archivo y calcular su hash para detectar duplicados
    validos.forEach((file, idx) => {
        console.log('[agregarImagenesGenerales]   Archivo', idx, ':', file.name, file.size, 'bytes');
        
        // Crear una clave única para el archivo basada en nombre + tamano + tipo
        // (no es un hash criptográfico real, pero funciona para detectar duplicados)
        const fileKey = `${file.name}_${file.size}_${file.type}`;
        
        // Verificar si ya existe una imagen con la misma clave en fotosGeneralesTemp
        const yaExiste = globalThis._fotosGeneralesKeys?.has(fileKey);
        
        if (yaExiste) {
            console.warn('[agregarImagenesGenerales]  DUPLICADO DETECTADO:', fileKey, '- Ignorando');
            return; // Skip this file
        }
        
        // Inicializar el Set si no existe
        if (!globalThis._fotosGeneralesKeys) {
            globalThis._fotosGeneralesKeys = new Set();
        }
        
        // Marcar como procesado
        globalThis._fotosGeneralesKeys.add(fileKey);
        
        // NUEVO: Asignar UID único a cada File para mapeo backend
        if (!file.uid) {
            const randStr = Math.random().toString(36).substring(2, 11);
            file.uid = `uid-${Date.now()}-${randStr}`;
            console.log('[agregarImagenesGenerales]  UID asignado al File:', file.uid, file.name);
        }
        
        // Agregar a los arrays
        const blobUrl = URL.createObjectURL(file);
        fotosGeneralesTemp.push(blobUrl);
        fotosGeneralesFilesTemp.push(file);
        
        console.log('[agregarImagenesGenerales]  Imagen agregada:', fileKey, 'UID:', file.uid);
    });
    
    console.log('[agregarImagenesGenerales] 🎉 Total en fotosGeneralesTemp después:', fotosGeneralesTemp.length);
    renderizarGaleriaFotosGenerales();
}

/**
 * Re-renderiza la galería de fotos generales
 */
function renderizarGaleriaFotosGenerales() {
    const galeria = document.getElementById('prt-galeria-general');
    if (!galeria) return;

    // Combinar existentes y nuevas para mostrar en la galería
    const todasLasFotos = [...fotosGeneralesExistentes, ...fotosGeneralesTemp];
    
    const thumbs = todasLasFotos.map((item, idx) => {
        // Manejar tanto strings como objetos
        const src = typeof item === 'string' ? item : (item?.url || item);
        const esExistente = idx < fotosGeneralesExistentes.length;
        const indiceReal = idx;
        
        return `
        <div style="position:relative; width:70px; height:70px; border-radius:6px; overflow:hidden; border:1px solid #ddd; flex-shrink:0;">
            <img src="${src}" style="width:100%;height:100%;object-fit:cover;">
            <button type="button" onclick="eliminarFotoGeneral(${indiceReal}, ${esExistente})"
                style="position:absolute;top:2px;right:2px;background:rgba(220,38,38,0.85);color:white;border:none;border-radius:50%;width:18px;height:18px;font-size:12px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;">&times;</button>
        </div>
    `;
    }).join('');

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

    // Resetear flag de configuración después de redibujar para que se reconfiguren los listeners
    // (aunque innerHTML remueve los listeners, necesitamos limpiar el flag también)
    galeria._dragDropConfigured = false;

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
    const safeKey = actualKey.replaceAll(/[^a-zA-Z0-9_-]/g, '_');
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

// ────────────────────────────────────────────────────────────────────────────────
// HELPER FUNCTIONS - Extracted for cognitive complexity reduction
// ────────────────────────────────────────────────────────────────────────────────

/**
 * Carga tallas desde tabla de resumen (Fuente 1 - PRIORITARIA)
 */
function _cargarTallasDesdeTabla() {
    const tablaResumenBody = document.getElementById('tabla-resumen-asignaciones-cuerpo');
    const tallasPrenda = { dama: {}, caballero: {}, sobremedida: null };
    
    if (!tablaResumenBody?.querySelectorAll('tr').length) return { tallasPrenda, hayTallas: false };
    
    console.log('[por-tallas]  FUENTE 1 - Leyendo desde tabla de resumen (PRIORITARIO)');
    
    tablaResumenBody.querySelectorAll('tr[data-tipo="wizard"]').forEach((fila) => {
        const genero = fila.querySelector('[data-field="genero"]')?.textContent?.trim().toLowerCase();
        const talla = fila.querySelector('[data-field="talla"]')?.textContent?.trim().toUpperCase();
        const cantidad = Number(fila.querySelector('[data-field="cantidad"]')?.textContent?.trim() || '1');
        
        if (!talla || !cantidad) return;
        
        const colores = _extraerColoresDesdeTabla(fila);
        const tallasKey = _determinarGrupoTalla(genero);
        
        _agregarTallasConColores(tallasPrenda, tallasKey, talla, colores, cantidad);
    });
    
    const hayTallas = Object.keys(tallasPrenda.dama).length > 0 || Object.keys(tallasPrenda.caballero).length > 0 || tallasPrenda.sobremedida;
    if (hayTallas) console.log('[por-tallas]  FUENTE 1 exitosa:', tallasPrenda);
    
    return { tallasPrenda, hayTallas };
}

/**
 * Extrae colores y cantidades de una fila de tabla
 */
function _extraerColoresDesdeTabla(fila) {
    const colorCell = fila.querySelector('[data-field="color"]');
    const colores = [];
    
    if (!colorCell) return colores;
    
    colorCell.querySelectorAll('span').forEach(badge => {
        const texto = badge.textContent?.trim();
        if (!texto || texto === 'Sin color') return;
        
        const match = texto.match(/^(.+?)\s*\(\s*(\d+)\s*\)$/);
        if (match) {
            colores.push({ color: match[1].trim(), cantidad: Number(match[2]) || 1 });
        } else {
            colores.push({ color: texto, cantidad: 1 });
        }
    });
    
    return colores;
}

/**
 * Determina grupo de talla (dama, caballero, sobremedida)
 */
function _determinarGrupoTalla(genero) {
    if (genero === 'caballero') return 'caballero';
    if (genero === 'sobremedida' || genero === 'unisex') return 'sobremedida';
    return 'dama';
}

/**
 * Agrega tallas con colores a la estructura
 */
function _agregarTallasConColores(tallasPrenda, grupo, talla, colores, cantidadTotal) {
    const setupTarget = () => {
        if (grupo === 'sobremedida') {
            if (!tallasPrenda.sobremedida) tallasPrenda.sobremedida = {};
            return tallasPrenda.sobremedida;
        }
        return tallasPrenda[grupo];
    };
    
    if (colores.length > 0) {
        colores.forEach(({ color, cantidad }) => {
            const clave = `${talla}__${color}`;
            const target = setupTarget();
            target[clave] = (target[clave] || 0) + cantidad;
        });
    } else {
        const target = setupTarget();
        target[talla] = (target[talla] || 0) + cantidadTotal;
    }
}

/**
 * Carga tallas desde globalThis.tallasRelacionales (Fuente 2)
 */
function _cargarTallasDesdeRelacionales() {
    const tallasRel = globalThis.tallasRelacionales || {};
    const hayTallas = Object.values(tallasRel).some(gen => gen && Object.keys(gen).length > 0);
    
    if (!hayTallas) return { tallasPrenda: null, hayTallas: false };
    
    console.log('[por-tallas]FUENTE 2 - Leyendo desde tallasRelacionales');
    
    const { SOBREMEDIDA = {}, UNISEX = {} } = tallasRel;
    const sobremedidaKeys = { ...SOBREMEDIDA, ...UNISEX };
    const tallasPrenda = {
        dama: { ...tallasRel.DAMA },
        caballero: { ...tallasRel.CABALLERO },
        sobremedida: Object.keys(sobremedidaKeys).length > 0 ? sobremedidaKeys : null
    };
    
    return { tallasPrenda, hayTallas: true };
}

/**
 * Carga tallas desde StateManager (Fuente 3)
 */
function _cargarTallasDesdeStateManager() {
    if (!globalThis.StateManager?.getAsignaciones) return { tallasPrenda: null, hayTallas: false };
    
    const asignaciones = globalThis.StateManager.getAsignaciones();
    if (!asignaciones || Object.keys(asignaciones).length === 0) return { tallasPrenda: null, hayTallas: false };
    
    console.log('[por-tallas]FUENTE 3 - Leyendo desde StateManager');
    
    const tallasPrenda = { dama: {}, caballero: {}, sobremedida: null };
    
    Object.values(asignaciones).forEach(asig => {
        if (!asig.talla) return;
        const genero = asig.genero?.toLowerCase() || 'dama';
        const grupo = _determinarGrupoTalla(genero);
        const cantidad = (asig.colores || []).reduce((sum, c) => sum + (c.cantidad || 1), 0) || 0;
        
        if (cantidad > 0) {
            if (grupo === 'sobremedida') {
                if (!tallasPrenda.sobremedida) tallasPrenda.sobremedida = {};
                tallasPrenda.sobremedida[asig.talla] = (tallasPrenda.sobremedida[asig.talla] || 0) + cantidad;
            } else {
                tallasPrenda[grupo][asig.talla] = (tallasPrenda[grupo][asig.talla] || 0) + cantidad;
            }
        }
    });
    
    const hayTallas = Object.keys(tallasPrenda.dama).length > 0 || Object.keys(tallasPrenda.caballero).length > 0;
    return { tallasPrenda: hayTallas ? tallasPrenda : null, hayTallas };
}

/**
 * Intenta obtener tallas de fuente secundaria (proceso guardado)
 * Nota: Esta NO es una mala práctica de fallback silencioso.
 * Se usa solo después de fallar todas las fuentes primarias.
 */
function _obtenerTallasDelProcesoGuardado() {
    if (typeof obtenerTallasDeLaPrenda !== 'function') {
        console.warn('[por-tallas]  obtenerTallasDeLaPrenda no disponible en window');
        return null;
    }
    
    try {
        const tallasPrenda = obtenerTallasDeLaPrenda();
        const validas = tallasPrenda && 
                        (Object.keys(tallasPrenda.dama || {}).length > 0 || 
                         Object.keys(tallasPrenda.caballero || {}).length > 0);
        
        if (validas) {
            console.log('[por-tallas]  Tallas obtenidas desde proceso guardado (fuente secundaria)');
            return tallasPrenda;
        }
    } catch (error) {
        console.error('[por-tallas]  Error al obtener tallas del proceso guardado:', error);
    }
    
    return null;
}

/**
 * Combina tallas guardadas evitando duplicados
 */
function _combinarTallasEvitandoDuplicados(tallasPrenda, tipoProceso) {
    const procesoTallas = globalThis.procesosSeleccionados?.[tipoProceso]?.datos?.tallas;
    if (!procesoTallas?.dama && !procesoTallas?.caballero) return tallasPrenda;
    
    console.log('[por-tallas]  Combinando tallas evitando duplicados');
    
    const extraerBase = (clave) => clave.split('__')[0].toUpperCase();
    const baseGuardadas = {
        dama: new Set(Object.keys(procesoTallas.dama || {}).map(extraerBase)),
        caballero: new Set(Object.keys(procesoTallas.caballero || {}).map(extraerBase))
    };
    
    const damaObj = { ...procesoTallas.dama };
    const cabObj = { ...procesoTallas.caballero };
    
    Object.entries(tallasPrenda.dama || {}).forEach(([key, val]) => {
        if (!baseGuardadas.dama.has(key.toUpperCase())) damaObj[key] = val;
    });
    
    Object.entries(tallasPrenda.caballero || {}).forEach(([key, val]) => {
        if (!baseGuardadas.caballero.has(key.toUpperCase())) cabObj[key] = val;
    });
    
    return { dama: damaObj, caballero: cabObj, sobremedida: tallasPrenda.sobremedida || procesoTallas.sobremedida };
}

/**
 * Carga tallas respetando orden de prioridad explícito
 * Cada fuente es intentada en orden:
 * 1. TABLA DE RESUMEN (fuente primaria - estado actual)
 * 2. TALLAS RELACIONALES (tarjetas manuales)
 * 3. STATE MANAGER (wizard)
 * 4. PROCESO GUARDADO (BD - fuente secundaria)
 * Si todas fallan, retorna estructura vacía con log claro
 */
function _cargarTallasDesdeFuentes(tipoProceso) {
    // Intenta cada fuente en orden de prioridad
    const { tallasPrenda: tallasTabla, hayTallas: hayEnTabla } = _cargarTallasDesdeTabla();
    if (hayEnTabla) {
        console.log('[por-tallas] ✓ Usando TABLA DE RESUMEN (fuente primaria)');
        return _combinarTallasEvitandoDuplicados(tallasTabla, tipoProceso);
    }
    console.log('[por-tallas] ✗ Tabla no available, intentando siguiente fuente...');

    const { tallasPrenda: tallasRel, hayTallas: hayEnRel } = _cargarTallasDesdeRelacionales();
    if (hayEnRel) {
        console.log('[por-tallas] ✓ Usando TALLAS RELACIONALES (fuente secundaria)');
        return _combinarTallasEvitandoDuplicados(tallasRel, tipoProceso);
    }
    console.log('[por-tallas] ✗ Relacionales no available, intentando siguiente fuente...');

    const { tallasPrenda: tallasSM, hayTallas: hayEnSM } = _cargarTallasDesdeStateManager();
    if (hayEnSM) {
        console.log('[por-tallas] ✓ Usando STATE MANAGER (fuente terciaria)');
        return _combinarTallasEvitandoDuplicados(tallasSM, tipoProceso);
    }
    console.log('[por-tallas] ✗ StateManager no available, intentando última fuente...');

    const tallasBD = _obtenerTallasDelProcesoGuardado();
    if (tallasBD) {
        console.log('[por-tallas] ✓ Usando PROCESO GUARDADO (fuente cuaternaria)');
        return _combinarTallasEvitandoDuplicados(tallasBD, tipoProceso);
    }

    console.warn('[por-tallas] ⚠ NINGUNA FUENTE disponible - no hay tallas para cargar');
    return _combinarTallasEvitandoDuplicados({ dama: {}, caballero: {}, sobremedida: null }, tipoProceso);
}

/**
 * Restaura fotos generales desde datos existentes
 */
function _restaurarFotosGenerales(datosGenerales) {
    fotosGeneralesExistentes = [];
    fotosGeneralesTemp = [];
    fotosGeneralesFilesTemp = [];
    globalThis._fotosGeneralesKeys = new Set();
    
    const fotosACargar = datosGenerales?.fotosGenerales || datosGenerales?.imagenes || [];
    fotosGeneralesExistentes = fotosACargar.filter(img => _esUrlValida(img));
    
    if (datosGenerales?.imagenesFiles?.length > 0) {
        datosGenerales.imagenesFiles.forEach(file => {
            if (file instanceof File) {
                if (!file.uid) {
                    file.uid = `uid-${Date.now()}-${Math.random().toString(36).substring(2, 11)}`;
                }
                const blobUrl = URL.createObjectURL(file);
                fotosGeneralesTemp.push(blobUrl);
                fotosGeneralesFilesTemp.push(file);
                globalThis._fotosGeneralesKeys.add(`${file.name}_${file.size}_${file.type}`);
            }
        });
    }
    
    console.log('[por-tallas]  Fotos generales cargadas:', {
        existentes: fotosGeneralesExistentes.length,
        nuevas: fotosGeneralesTemp.length
    });
}

/**
 * Valida si una URL es válida para almacenamiento
 */
function _esUrlValida(img) {
    if (typeof img === 'string') return !img.startsWith('blob:') && !img.startsWith('data:');
    if (img && typeof img === 'object') {
        const url = img.url || img.ruta_webp || img.ruta_original || img.ruta || '';
        return typeof url === 'string' && !url.startsWith('blob:') && !url.startsWith('data:');
    }
    return false;
}

/**
 * Restaura ubicación general desde datos
 */
function _restaurarUbicacionGeneral(datosGenerales) {
    let ubicacion = '';
    if (datosGenerales?.ubicacionGeneral) {
        ubicacion = datosGenerales.ubicacionGeneral;
    } else if (Array.isArray(datosGenerales?.ubicaciones)) {
        ubicacion = datosGenerales.ubicaciones.filter(u => u && typeof u === 'string').join(', ');
    } else if (typeof datosGenerales?.ubicaciones === 'string') {
        ubicacion = datosGenerales.ubicaciones;
    }
    
    ubicacionGeneralTemp = ubicacion;
    const input = document.getElementById('ubicacion-general-input');
    if (input) input.value = ubicacion;
    
    return ubicacion;
}

/**
 * Busca datos existentes por talla de forma explícita
 * Intenta dos estrategias en orden:
 * 1. Búsqueda exacta: tallaKey (ej: "M")
 * 2. Búsqueda con variante de color: tallaKey__COLOR (ej: "M__AZUL")
 * Retorna: { datos, estrategiaUsada: 'exacta'|'variante'|null }
 */
function _buscarDatosExistentesPorTalla(genero, tallaKey, datosExistentes) {
    if (!datosExistentes?.[genero]) {
        console.log(`[_buscarDatos] Género "${genero}" no encontrado en datosExistentes`);
        return { datos: null, estrategiaUsada: null };
    }
    
    // Estrategia 1: Búsqueda exacta
    if (datosExistentes[genero][tallaKey]) {
        console.log(`[_buscarDatos] ✓ Datos encontrados (búsqueda exacta): "${tallaKey}"`);
        return { datos: datosExistentes[genero][tallaKey], estrategiaUsada: 'exacta' };
    }
    
    // Estrategia 2: Búsqueda con variante de color
    const claveConColor = Object.keys(datosExistentes[genero]).find(clave => 
        clave.startsWith(tallaKey + '__')
    );
    
    if (claveConColor) {
        console.log(`[_buscarDatos] ✓ Datos encontrados (búsqueda con variante): "${tallaKey}" → "${claveConColor}"`);
        return { datos: datosExistentes[genero][claveConColor], estrategiaUsada: 'variante' };
    }
    
    console.log(`[_buscarDatos] ✗ No se encontraron datos para talla: "${tallaKey}"`);
    return { datos: null, estrategiaUsada: null };
}

/**
 * Inicializa datos de una talla en datosPorTallaTemp
 */
function _inicializarDatosTalla(key, cantidad, datosExistentes, genero, tallaKey) {
    const resultado = _buscarDatosExistentesPorTalla(genero, tallaKey, datosExistentes);
    const existente = resultado.datos;
    const imagenesExistentes = _filtrarImagenesValidas(existente?.imagenes || existente?.imagen);
    
    datosPorTallaTemp[key] = {
        seleccionada: true,
        cantidadSeleccionada: existente?.cantidadSeleccionada || cantidad,
        ubicaciones: existente?.ubicaciones ? [...existente.ubicaciones] : [],
        observaciones: existente?.observaciones || '',
        imagenes: imagenesExistentes,
        imagenesFiles: []
    };
    
    if (existente?.imagenesFiles?.length > 0) {
        existente.imagenesFiles.forEach(file => {
            if (file instanceof File) {
                const blobUrl = URL.createObjectURL(file);
                datosPorTallaTemp[key].imagenes.push(blobUrl);
                datosPorTallaTemp[key].imagenesFiles.push(file);
            }
        });
    }
}

/**
 * Filtra imágenes válidas de storage
 */
function _filtrarImagenesValidas(imagenes) {
    if (!imagenes) return [];
    const arr = Array.isArray(imagenes) ? imagenes : [imagenes];
    return arr.filter(img => _esUrlValida(img));
}

/**
 * Renderiza tarjetas para un género
 */
function _renderizarTallasPorGenero(genero, tallas, datosExistentes, contenedor, seccion, contenedorGeneral, seccionGeneral) {
    if (tallas.length === 0) {
        if (seccion) seccion.style.display = 'none';
        if (seccionGeneral) seccionGeneral.style.display = 'none';
        return;
    }
    
    if (seccion) seccion.style.display = 'block';
    if (contenedor) {
        tallas.forEach(([tallaKey, cantidad]) => {
            const key = `${genero}__${tallaKey}`;
            _inicializarDatosTalla(key, cantidad, datosExistentes, genero, tallaKey);
            contenedor.appendChild(crearTarjetaTalla(genero, tallaKey, cantidad, datosPorTallaTemp[key]));
        });
    }
    
    if (contenedorGeneral && seccionGeneral) {
        seccionGeneral.style.display = 'block';
        tallas.forEach(([tallaKey, cantidad]) => {
            const key = `${genero}__${tallaKey}`;
            const datos = datosPorTallaTemp[key] || { cantidadSeleccionada: cantidad, observaciones: '' };
            contenedorGeneral.appendChild(crearTarjetaTallaGeneral(genero, tallaKey, cantidad, datos));
        });
    }
}

/**
 * Limpia todos los contenedores DOM
 */
function _limpiarContenedores() {
    const elementosALimpiar = [
        'tallas-dama-por-tallas',
        'tallas-caballero-por-tallas',
        'tallas-dama-modo-general',
        'tallas-caballero-modo-general',
        'prt-galeria-general'
    ];
    
    elementosALimpiar.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '';
        if (id === 'prt-galeria-general' && el) el._dragDropConfigured = false;
    });
}

/**
 * Determina el modo final (especifico o general)
 */
function _determinarModoFinal(tipoProceso) {
    const dbData = globalThis.procesosGuardados?.[tipoProceso]?.datos || {};
    const modoDb = dbData.modo_tallas?.toLowerCase();
    const datosExt = dbData.datosExtendidos || {};
    
    const hayEspecificos = (datosExt.dama && Object.keys(datosExt.dama).length > 0) ||
                           (datosExt.caballero && Object.keys(datosExt.caballero).length > 0) ||
                           (datosExt.sobremedida && Object.keys(datosExt.sobremedida).length > 0);
    
    return (modoDb === 'especifico' && hayEspecificos) ? 'especifico' : 'general';
}

/**
 * Abre el modal de proceso por tallas
 */
globalThis.abrirModalProcesoPorTallas = function(tipoProceso) {
    procesoPorTallasActual = tipoProceso;
    datosPorTallaTemp = {};
    modoModalPorTallasActual = 'general';
    ubicacionGeneralTemp = '';
    
    const inputUbicacion = document.getElementById('ubicacion-general-input');
    if (inputUbicacion) inputUbicacion.value = '';

    const modal = document.getElementById('modal-proceso-por-tallas');
    if (!modal) {
        console.error('[por-tallas] Modal no encontrado');
        return;
    }

    const iconEl = document.getElementById('modal-por-tallas-icon');
    const tituloEl = document.getElementById('modal-por-tallas-titulo');
    if (iconEl) iconEl.textContent = iconosPorTallas[tipoProceso] || 'edit_note';
    if (tituloEl) tituloEl.textContent = `${nombresPorTallas[tipoProceso] || tipoProceso} — Por Tallas`;

    const tallasPrenda = _cargarTallasDesdeFuentes(tipoProceso);
    const tallasDama = Object.entries(tallasPrenda.dama || {});
    const tallasCaballero = Object.entries(tallasPrenda.caballero || {});

    const sinTallas = document.getElementById('sin-tallas-por-tallas');
    if (tallasDama.length === 0 && tallasCaballero.length === 0) {
        _limpiarContenedores();
        document.getElementById('seccion-dama-por-tallas').style.display = 'none';
        document.getElementById('seccion-caballero-por-tallas').style.display = 'none';
        if (sinTallas) sinTallas.style.display = 'block';
        modal.style.display = 'flex';
        return;
    }

    if (sinTallas) sinTallas.style.display = 'none';

    const datosExistentes = globalThis.procesosSeleccionados?.[tipoProceso]?.datos?.datosExtendidos;
    const datosGenerales = globalThis.procesosSeleccionados?.[tipoProceso]?.datos;

    _limpiarContenedores();
    _restaurarUbicacionGeneral(datosGenerales);
    _restaurarFotosGenerales(datosGenerales);
    renderizarGaleriaFotosGenerales();

    const secDama = document.getElementById('seccion-dama-por-tallas');
    const secCab = document.getElementById('seccion-caballero-por-tallas');
    const contDama = document.getElementById('tallas-dama-por-tallas');
    const contCab = document.getElementById('tallas-caballero-por-tallas');
    const contDamaGeneral = document.getElementById('tallas-dama-modo-general');
    const secDamaGeneral = document.getElementById('seccion-dama-modo-general');
    const contCabGeneral = document.getElementById('tallas-caballero-modo-general');
    const secCabGeneral = document.getElementById('seccion-caballero-modo-general');

    _renderizarTallasPorGenero('dama', tallasDama, datosExistentes, contDama, secDama, contDamaGeneral, secDamaGeneral);
    _renderizarTallasPorGenero('caballero', tallasCaballero, datosExistentes, contCab, secCab, contCabGeneral, secCabGeneral);

    const modoFinal = _determinarModoFinal(tipoProceso);
    if (modoFinal === 'especifico') {
        cambiarModoModalPorTallas('especifico');
    } else {
        cambiarModoModalPorTallas('general');
    }

    modal.style.display = 'flex';
    console.log('[abrirModalProcesoPorTallas]  Modal abierto, listener global de paste activo');
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
    const safeKey = actualKey.replaceAll(/[^a-zA-Z0-9_-]/g, '_');
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
        galeria.style.outline = '2px dashed #3b82f6';
        galeria.style.outlineOffset = '2px';
        galeria.style.background = '#eff6ff';
    };
    galeria.addEventListener('dragover', manejadorDragOver);

    const manejadorDragLeave = _crearHandlerDragLeave();
    galeria.addEventListener('dragleave', manejadorDragLeave);

    const manejadorDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        galeria.style.outline = '';
        galeria.style.outlineOffset = '';
        galeria.style.background = '';

        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            agregarImagenesATalla(key, e.dataTransfer.files);
        }
    };
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
    console.log('[handlePasteGlobalPorTallas]  Agregando a GENERAL');
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
    
    console.log('[handlePasteGlobalPorTallas]  Agregando a talla:', targetKey);
    agregarImagenesATalla(targetKey, files);
    const safeKey = convertirAKeySegura(targetKey);
    _aplicarFeedbackVisual(`prt-galeria-${safeKey}`);
}

/**
 * Handler principal de paste global
 */
function handlePasteGlobalPorTallas(e) {
    console.log('[handlePasteGlobalPorTallas]  PASTE event processing');

    // ─── Validaciones iniciales ───
    const modal = _validarPasteEventoValido(e);
    if (!modal) {
        console.log('[handlePasteGlobalPorTallas]  Validación fallida, ignorando paste');
        return;
    }

    if (!_validarFocusPermitePaste()) {
        console.log('[handlePasteGlobalPorTallas]  Focus en input/textarea/contenteditable - ignorando');
        return;
    }

    const items = e.clipboardData?.items;
    if (!items) {
        console.log('[handlePasteGlobalPorTallas]  No clipboard items');
        return;
    }

    // ─── Extrae imágenes del clipboard ───
    const files = _extraerArchivosDelClipboard(items);
    console.log('[handlePasteGlobalPorTallas]  Archivos detectados:', files.length);
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
globalThis.cargarFotosPorTalla = function(key, input) {
    if (!input.files || input.files.length === 0) return;
    agregarImagenesATalla(key, input.files);
    input.value = '';
};

/**
 * Elimina una foto específica de una talla por índice
 */
globalThis.eliminarFotoPorTalla = function(key, index) {
    if (!datosPorTallaTemp[key]) return;
    const imgs = datosPorTallaTemp[key].imagenes;
    if (index < 0 || index >= imgs.length) return;

    // Revocar blob URL
    if (imgs[index]?.startsWith('blob:')) {
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

    const thumbs = (datos.imagenes || []).map((item, idx) => {
        // Manejar tanto strings como objetos
        const src = typeof item === 'string' ? item : (item?.url || item);
        
        return `
        <div style="position:relative; width:70px; height:70px; border-radius:6px; overflow:hidden; border:2px solid ${borderColor}; flex-shrink:0;">
            <img src="${src}" style="width:100%;height:100%;object-fit:cover;">
            <button type="button" onclick="eliminarFotoPorTalla('${key}', ${idx})"
                style="position:absolute;top:2px;right:2px;background:rgba(220,38,38,0.85);color:white;border:none;border-radius:50%;width:18px;height:18px;font-size:12px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;">&times;</button>
        </div>
    `;
    }).join('');

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

    // Resetear flag de configuración para que se reconfiguren los listeners
    galeria._dragDropPastaTallaConfigured = false;

    // Reconfigurar drag & drop después de redibujar
    configurarDragDropPasteTalla(galeria, key);
}

/**
 * Guardar proceso con datos por talla
 */
globalThis.guardarProcesoPorTallas = function() {
    if (!procesoPorTallasActual) return;

    console.log('[GUARDAR-POR-TALLAS] Iniciando guardado del proceso:', procesoPorTallasActual, 'Modo:', modoModalPorTallasActual);
    console.log('[GUARDAR-POR-TALLAS] datosPorTallaTemp ANTES de recolectar:', JSON.stringify(datosPorTallaTemp, null, 2));

    // ─── RECOGER DATOS SEGÚN EL MODO ACTUAL ───
    if (modoModalPorTallasActual === 'general') {
        // Modo general: ubicación general + observaciones por talla + fotos generales
        ubicacionGeneralTemp = document.getElementById('ubicacion-general-input')?.value || '';
        
        // Recoger observaciones generales
        const textareasGenerales = document.querySelectorAll('.prt-observaciones-general');
        console.log('[GUARDAR-POR-TALLAS] Encontrados textareas de observaciones generales:', textareasGenerales.length);
        
        textareasGenerales.forEach(textarea => {
            const key = textarea.dataset.key;
            const valor = textarea.value;
            console.log('[GUARDAR-POR-TALLAS] Recolectando observación:', { key, valor, existeEnTemp: !!datosPorTallaTemp[key] });
            if (datosPorTallaTemp[key]) {
                datosPorTallaTemp[key].observaciones = valor;
            } else {
                console.warn('[GUARDAR-POR-TALLAS]  Clave no encontrada en datosPorTallaTemp:', key, 'Claves disponibles:', Object.keys(datosPorTallaTemp));
            }
        });

        // Recoger cantidades generales
        document.querySelectorAll('[id^="cantidad-mg-"]').forEach(input => {
            const id = input.id.replace('cantidad-mg-', '');
            // Necesitamos encontrar la clave real desde el IDs
            const tallaKey = Object.keys(datosPorTallaTemp).find(k => 
                k.replaceAll(/[^a-zA-Z0-9_-]/g, '_') === id
            );
            if (tallaKey && datosPorTallaTemp[tallaKey]) {
                datosPorTallaTemp[tallaKey].cantidadSeleccionada = Math.max(0, Number(input.value) || 0);
            }
        });

        // Recoger checkboxes generales
        document.querySelectorAll('[id^="checkbox-mg-"]').forEach(checkbox => {
            const id = checkbox.id.replace('checkbox-mg-', '');
            const tallaKey = Object.keys(datosPorTallaTemp).find(k => 
                k.replaceAll(/[^a-zA-Z0-9_-]/g, '_') === id
            );
            if (tallaKey && datosPorTallaTemp[tallaKey]) {
                datosPorTallaTemp[tallaKey].seleccionada = checkbox.checked;
            }
        });
    } else {
        // Modo específico: recoger  (ubicación, observaciones e imágenes por talla)
        const textareasUbicacion = document.querySelectorAll('.prt-ubicacion-input');
        console.log('[GUARDAR-POR-TALLAS] Modo ESPECÍFICO - Encontrados textareas de ubicación:', textareasUbicacion.length);
        
        textareasUbicacion.forEach(textarea => {
            const key = textarea.dataset.key;
            const valor = textarea.value;
            console.log('[GUARDAR-POR-TALLAS] Recolectando ubicación:', { key, valor, existeEnTemp: !!datosPorTallaTemp[key] });
            if (datosPorTallaTemp[key]) {
                datosPorTallaTemp[key].ubicaciones = valor
                    .split(',')
                    .map(u => u.trim())
                    .filter(u => u.length > 0);
            }
        });

        const textareasObs = document.querySelectorAll('.prt-observaciones');
        console.log('[GUARDAR-POR-TALLAS] Encontrados textareas de observaciones específicas:', textareasObs.length);
        
        textareasObs.forEach(textarea => {
            const key = textarea.dataset.key;
            const valor = textarea.value;
            console.log('[GUARDAR-POR-TALLAS] Recolectando observación específica:', { key, valor, existeEnTemp: !!datosPorTallaTemp[key] });
            if (datosPorTallaTemp[key]) {
                datosPorTallaTemp[key].observaciones = valor;
            } else {
                console.warn('[GUARDAR-POR-TALLAS]  Clave no encontrada en datosPorTallaTemp:', key, 'Claves disponibles:', Object.keys(datosPorTallaTemp));
            }
        });

        document.querySelectorAll('[id^="cantidad-"]:not([id^="cantidad-mg-"])').forEach(input => {
            const id = input.id.replace('cantidad-', '');
            const key = id;
            if (datosPorTallaTemp[key]) {
                datosPorTallaTemp[key].cantidadSeleccionada = Math.max(0, Number(input.value) || 0);
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
            imagenesFilesCount: datos.imagenesFiles?.length,
            imagenesSample: datos.imagenes?.slice(0, 2).map(img => typeof img === 'string' ? img.substring(0, 60) : typeof img),
            imagenesFilesSample: datos.imagenesFiles?.slice(0, 2).map(file => file instanceof File ? `File: ${file.name}` : typeof file)
        });

        // Filtrar blob URLs de imagenes - solo mantener URLs del servidor
        const imagenesValidas = modoModalPorTallasActual === 'general' ? [] : (datos.imagenes || []).filter(img => {
            if (typeof img === 'string') {
                return !img.startsWith('blob:') && !img.startsWith('data:');
            }
            if (typeof img === 'object' && img) {
                const url = img.url || img.ruta_webp || img.ruta_original || img.ruta || '';
                return typeof url === 'string' && !url.startsWith('blob:') && !url.startsWith('data:');
            }
            return false;
        });

        datosExtendidos[genero][tallaKey] = {
            cantidadSeleccionada: datos.cantidadSeleccionada,
            ubicaciones: modoModalPorTallasActual === 'general' ? [] : (datos.ubicaciones || []),
            observaciones: datos.observaciones || '',
            imagenes: imagenesValidas,
            imagenesFiles: modoModalPorTallasActual === 'general' ? [] : (datos.imagenesFiles || [])
        };
    });

    // Guardar en procesosSeleccionados
    if (!globalThis.procesosSeleccionados) {
        globalThis.procesosSeleccionados = {};
    }

    if (!globalThis.procesosSeleccionados[procesoPorTallasActual]) {
        globalThis.procesosSeleccionados[procesoPorTallasActual] = {
            tipo: procesoPorTallasActual,
            datos: null
        };
    }

    globalThis.procesosSeleccionados[procesoPorTallasActual].datos = {
        tipo: procesoPorTallasActual,
        ubicaciones: modoModalPorTallasActual === 'general' ? [ubicacionGeneralTemp] : [],
        observaciones: '',
        tallas: tallas,
        //  FIX: Copiar File objects de imagenesFiles a imagenes para que FormData los encuentre
        imagenes: modoModalPorTallasActual === 'general' ? fotosGeneralesFilesTemp : [],
        imagenesFiles: fotosGeneralesFilesTemp, // File objects para las nuevas imágenes
        fotosGeneralesFiles: fotosGeneralesFilesTemp, // Alias para compatibilidad con renderizador
        imagenes_existentes: fotosGeneralesExistentes, // URLs de BD que se van a mantener
        imagenes_a_eliminar: fotosGeneralesEliminadas, // URLs de BD que fueron eliminadas - NOMBRE CORRECTO para que adapter lo encuentre
        imagenesEliminadas: [],
        datosExtendidos: datosExtendidos,
        modo_tallas: modoModalPorTallasActual, // Fuente única canónica
        ubicacionGeneral: modoModalPorTallasActual === 'general' ? ubicacionGeneralTemp : '',
        // CRÍTICO: Solo guardar URLs válidas del storage en fotosGenerales
        // NO incluir blobs temporales - solo las existentes que ya están en el servidor
        fotosGenerales: modoModalPorTallasActual === 'general' ? [...fotosGeneralesExistentes] : [],
        imagenes_por_talla: construirImagenesPorTalla(datosExtendidos)
    };

    console.log('[por-tallas] Proceso guardado:', procesoPorTallasActual, 'Modo:', modoModalPorTallasActual, {
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
    if (modal) modal.style.display = 'none';

    // Desregistrar el listener del modal
    desregistrarListenerPasteDelModal();
    procesandoPasteEvent = false; // Resetear flag de protección
    tallaActivaParaPaste = null;
    procesoPorTallasActual = null;
    datosPorTallaTemp = {};
    fotosGeneralesExistentes = [];
    fotosGeneralesTemp = [];
    fotosGeneralesFilesTemp = [];
    ubicacionGeneralTemp = '';
};

/**
 * Cerrar modal sin guardar
 */
globalThis.cerrarModalProcesoPorTallas = function() {
    const modal = document.getElementById('modal-proceso-por-tallas');
    if (modal) modal.style.display = 'none';

    // Desregistrar el listener del modal
    desregistrarListenerPasteDelModal();
    procesandoPasteEvent = false; // Resetear flag de protección
    tallaActivaParaPaste = null;

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

    procesoPorTallasActual = null;
    datosPorTallaTemp = {};
    modoModalPorTallasActual = 'general';  // Resetear a modo general para próxima vez
    fotosGeneralesExistentes = [];
    fotosGeneralesEliminadas = [];
    fotosGeneralesTemp = [];
    fotosGeneralesFilesTemp = [];
    ubicacionGeneralTemp = '';
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
    console.log('[eliminarFotoGeneral] Eliminando foto existente de BD:', index, fotoEliminada);
    
    const imagenAEliminar = _normalizarImagenParaEliminar(fotoEliminada);
    
    if (imagenAEliminar && (imagenAEliminar.url || imagenAEliminar.id)) {
        fotosGeneralesEliminadas.push(imagenAEliminar);
        console.log('[eliminarFotoGeneral] Foto eliminada registrada:', imagenAEliminar);
    }
    
    fotosGeneralesExistentes.splice(index, 1);
    console.log('[eliminarFotoGeneral] Fotos eliminadas registradas (total):', fotosGeneralesEliminadas.length);
}

/**
 * Elimina foto nueva agregada por usuario
 */
function _eliminarFotoNuevaAñadida(index) {
    const indiceNueva = index - fotosGeneralesExistentes.length;
    
    if (indiceNueva < 0 || indiceNueva >= fotosGeneralesTemp.length) return;
    
    console.log('[eliminarFotoGeneral] Eliminando foto nueva:', indiceNueva);
    
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
globalThis.eliminarFotoGeneral = function(index, esExistente) {
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
        galeria.style.outline = '2px dashed #333';
        galeria.style.outlineOffset = '2px';
        galeria.style.background = '#f0f0f0';
    };
    galeria.addEventListener('dragover', manejadorDragOver);

    // ─── Drag Leave ───
    const manejadorDragLeave = _crearHandlerDragLeave();
    galeria.addEventListener('dragleave', manejadorDragLeave);

    // ─── Drop ───
    const manejadorDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        galeria.style.outline = '';
        galeria.style.outlineOffset = '';
        galeria.style.background = '';

        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            agregarImagenesGenerales(e.dataTransfer.files);
        }
    };
    galeria.addEventListener('drop', manejadorDrop);

    // ─── Mouse Enter: Marcar para paste ───
    const manejadorMouseEnter = () => {
        tallaActivaParaPaste = 'GENERAL';
    };
    galeria.addEventListener('mouseenter', manejadorMouseEnter);
    
    // ─── Click: También marcar para paste por si el usuario hace click sin mover el mouse ───
    const manejadorClick = () => {
        tallaActivaParaPaste = 'GENERAL';
        console.log('[configurarDragDropPasteGeneral]  Área general activada para paste');
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
