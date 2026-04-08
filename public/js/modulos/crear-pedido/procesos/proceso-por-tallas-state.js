/**
 * proceso-por-tallas-state.js
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
let modalPorTallasEventosIniciados = false;

const DEBUG_POR_TALLAS = Boolean(globalThis.DEBUG_POR_TALLAS);
function _log(...args) {
    if (DEBUG_POR_TALLAS) {
        console.log(...args);
    }
}


// ─── Listener global de PASTE en document (capture phase) ───
const pasteListenerPorTallas = function(e) {
    const modal = document.getElementById('modal-proceso-por-tallas');
    if (!modal || modal.style.display === 'none') return;
    
    _log('[pasteListenerPorTallas]  PASTE detectado en document');
    handlePasteGlobalPorTallas(e);
};

// Registrar listener globalmente en capture phase (máxima prioridad)
document.addEventListener('paste', pasteListenerPorTallas, true);

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

function _construirMapaSafeKeyAActualKey() {
    const mapa = new Map();
    Object.keys(datosPorTallaTemp).forEach((key) => {
        mapa.set(convertirAKeySegura(key), key);
    });
    return mapa;
}

function _aplicarValorInputsPorSelector(selector, idPrefix, callback, mapaSafeKeys = null) {
    document.querySelectorAll(selector).forEach((elemento) => {
        const idSinPrefijo = elemento.id.replace(idPrefix, '');
        const key = mapaSafeKeys ? mapaSafeKeys.get(idSinPrefijo) : idSinPrefijo;
        if (!key || !datosPorTallaTemp[key]) return;
        callback(datosPorTallaTemp[key], elemento);
    });
}

function _recolectarDatosModoGeneral() {
    ubicacionGeneralTemp = document.getElementById('ubicacion-general-input')?.value || '';

    const textareasGenerales = document.querySelectorAll('.prt-observaciones-general');
    _log('[GUARDAR-POR-TALLAS] Encontrados textareas de observaciones generales:', textareasGenerales.length);
    textareasGenerales.forEach((textarea) => {
        const key = textarea.dataset.key;
        const valor = textarea.value;
        _log('[GUARDAR-POR-TALLAS] Recolectando observación:', { key, valor, existeEnTemp: !!datosPorTallaTemp[key] });
        if (datosPorTallaTemp[key]) {
            datosPorTallaTemp[key].observaciones = valor;
        } else {
            console.warn('[GUARDAR-POR-TALLAS]  Clave no encontrada en datosPorTallaTemp:', key, 'Claves disponibles:', Object.keys(datosPorTallaTemp));
        }
    });

    const mapaSafeKeys = _construirMapaSafeKeyAActualKey();

    _aplicarValorInputsPorSelector('[id^="cantidad-mg-"]', 'cantidad-mg-', (datos, input) => {
        datos.cantidadSeleccionada = Math.max(0, Number(input.value) || 0);
    }, mapaSafeKeys);

    _aplicarValorInputsPorSelector('[id^="checkbox-mg-"]', 'checkbox-mg-', (datos, checkbox) => {
        datos.seleccionada = checkbox.checked;
    }, mapaSafeKeys);
}

function _recolectarDatosModoEspecifico() {
    const textareasUbicacion = document.querySelectorAll('.prt-ubicacion-input');
    _log('[GUARDAR-POR-TALLAS] Modo ESPECÍFICO - Encontrados textareas de ubicación:', textareasUbicacion.length);
    textareasUbicacion.forEach((textarea) => {
        const key = textarea.dataset.key;
        const valor = textarea.value;
        _log('[GUARDAR-POR-TALLAS] Recolectando ubicación:', { key, valor, existeEnTemp: !!datosPorTallaTemp[key] });
        if (datosPorTallaTemp[key]) {
            datosPorTallaTemp[key].ubicaciones = valor
                .split(',')
                .map((u) => u.trim())
                .filter((u) => u.length > 0);
        }
    });

    const textareasObs = document.querySelectorAll('.prt-observaciones');
    _log('[GUARDAR-POR-TALLAS] Encontrados textareas de observaciones específicas:', textareasObs.length);
    textareasObs.forEach((textarea) => {
        const key = textarea.dataset.key;
        const valor = textarea.value;
        _log('[GUARDAR-POR-TALLAS] Recolectando observación específica:', { key, valor, existeEnTemp: !!datosPorTallaTemp[key] });
        if (datosPorTallaTemp[key]) {
            datosPorTallaTemp[key].observaciones = valor;
        } else {
            console.warn('[GUARDAR-POR-TALLAS]  Clave no encontrada en datosPorTallaTemp:', key, 'Claves disponibles:', Object.keys(datosPorTallaTemp));
        }
    });

    _aplicarValorInputsPorSelector('[id^="cantidad-"]:not([id^="cantidad-mg-"])', 'cantidad-', (datos, input) => {
        datos.cantidadSeleccionada = Math.max(0, Number(input.value) || 0);
    });

    _aplicarValorInputsPorSelector('[id^="checkbox-"]:not([id^="checkbox-mg-"])', 'checkbox-', (datos, checkbox) => {
        datos.seleccionada = checkbox.checked;
    });
}

function _limpiarEstadoModalPorTallas({ resetModo = true, preserveProcesoActual = false } = {}) {
    desregistrarListenerPasteDelModal();
    tallaActivaParaPaste = null;

    if (!preserveProcesoActual) {
        procesoPorTallasActual = null;
    }

    datosPorTallaTemp = {};
    if (resetModo) {
        modoModalPorTallasActual = 'general';
    }
    fotosGeneralesExistentes = [];
    fotosGeneralesEliminadas = [];
    fotosGeneralesTemp = [];
    fotosGeneralesFilesTemp = [];
    ubicacionGeneralTemp = '';
    globalThis._fotosGeneralesKeys = new Set();
}

const nombresPorTallas = {
    reflectivo: 'Reflectivo',
    bordado: 'Bordado',
    estampado: 'Estampado',
    dtf: 'DTF',
    sublimado: 'Sublimado'
};

function _obtenerEstiloBotonModo(estaActivo) {
    if (estaActivo) {
        return 'padding: 0.4rem 1rem; border: 2px solid #333; border-radius: 6px; background: white; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: #333; opacity: 1;';
    }
    return 'padding: 0.4rem 1rem; border: 1px solid #ddd; border-radius: 6px; background: #f9f9f9; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: #999; opacity: 0.6;';
}

function _crearBotonAgregarGaleriaHTML(inputFileId, { dashedColor = '#ccc', iconColor = '#9ca3af', textColor = '#9ca3af' } = {}) {
    return `
        <div data-prt-action="abrir-input" data-target-id="${inputFileId}"
            style="width: 70px; height: 70px; border: 2px dashed ${dashedColor}; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: white; flex-shrink: 0;">
            <div style="text-align:center;">
                <span class="material-symbols-rounded" style="font-size:1.3rem;color:${iconColor};">add_photo_alternate</span>
                <div style="font-size:0.6rem;color:${textColor};">Agregar</div>
            </div>
        </div>
    `;
}

function _aplicarEstiloDragActivo(galeria, { outlineColor, backgroundColor }) {
    galeria.style.outline = `2px dashed ${outlineColor}`;
    galeria.style.outlineOffset = '2px';
    galeria.style.background = backgroundColor;
}

function _limpiarEstiloDragActivo(galeria) {
    galeria.style.outline = '';
    galeria.style.outlineOffset = '';
    galeria.style.background = '';
}

function _crearManejadorDropGaleria(galeria, onDropFiles) {
    return (e) => {
        e.preventDefault();
        e.stopPropagation();
        _limpiarEstiloDragActivo(galeria);

        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            onDropFiles(e.dataTransfer.files);
        }
    };
}

function _mostrarContenedoresPorModo(nuevoModo) {
    const containerGeneral = document.getElementById('modo-general-container');
    const containerEspecifico = document.getElementById('modo-especifico-container');
    if (!containerGeneral || !containerEspecifico) return;

    if (nuevoModo === 'general') {
        containerGeneral.style.display = 'block';
        containerEspecifico.style.display = 'none';
    } else {
        containerGeneral.style.display = 'none';
        containerEspecifico.style.display = 'block';
    }
}

function _mostrarModalProcesoPorTallas(modal, visible) {
    if (!modal) return;
    modal.style.display = visible ? 'flex' : 'none';
}

function _configurarHeaderModalPorTallas(tipoProceso) {
    const iconEl = document.getElementById('modal-por-tallas-icon');
    const tituloEl = document.getElementById('modal-por-tallas-titulo');
    if (iconEl) iconEl.textContent = iconosPorTallas[tipoProceso] || 'edit_note';
    if (tituloEl) tituloEl.textContent = `${nombresPorTallas[tipoProceso] || tipoProceso} - Por Tallas`;
}

function _mostrarEstadoSinTallas(modal) {
    const sinTallas = document.getElementById('sin-tallas-por-tallas');
    _limpiarContenedores();
    const secDama = document.getElementById('seccion-dama-por-tallas');
    const secCaballero = document.getElementById('seccion-caballero-por-tallas');
    if (secDama) secDama.style.display = 'none';
    if (secCaballero) secCaballero.style.display = 'none';
    if (sinTallas) sinTallas.style.display = 'block';
    PorTallasModulos.ui.mostrarModal(modal, true);
}

/**
 * Registra el listener de paste EN EL MODAL (mejor práctica que listener global)
 * Se ejecuta solo una vez al abrir el modal
 */
function registrarListenerPasteEnModal() {
    // El listener ya está registrado globalmente en document (capture phase)
    // No necesitamos hacer nada aquí
    _log('[PASTE-LISTENER]  Listener global ya activo en document');
}

/**
 * Desregistra el listener de paste del modal al cerrarlo
 */
function desregistrarListenerPasteDelModal() {
    // El listener global permanece activo pero no procesará nada si el modal está oculto
    _log('[PASTE-LISTENER] Modal cerrado, listener global permanece en standby');
}

function _inicializarEventosModalPorTallas() {
    if (modalPorTallasEventosIniciados) return;

    const modal = document.getElementById('modal-proceso-por-tallas');
    if (!modal) return;

    modal.addEventListener('click', (e) => {
        const actionEl = e.target.closest('[data-prt-action]');
        if (!actionEl || !modal.contains(actionEl)) return;

        const action = actionEl.dataset.prtAction;

        if (action === 'cambiar-modo') {
            cambiarModoModalPorTallas(actionEl.dataset.mode);
            return;
        }

        if (action === 'cerrar-modal') {
            cerrarModalProcesoPorTallas();
            return;
        }

        if (action === 'guardar-modal') {
            guardarProcesoPorTallas();
            return;
        }

        if (action === 'abrir-input') {
            const input = document.getElementById(actionEl.dataset.targetId || '');
            if (input) input.click();
            return;
        }

        if (action === 'eliminar-foto-general') {
            const index = Number(actionEl.dataset.index || '-1');
            const esExistente = actionEl.dataset.existente === 'true';
            eliminarFotoGeneral(index, esExistente);
            return;
        }

        if (action === 'eliminar-foto-talla') {
            const key = actionEl.dataset.key || '';
            const index = Number(actionEl.dataset.index || '-1');
            eliminarFotoPorTalla(key, index);
        }
    });

    modal.addEventListener('change', (e) => {
        const target = e.target;
        if (!target || !(target instanceof HTMLInputElement)) return;

        const action = target.dataset.prtAction;
        if (action === 'cargar-fotos-generales') {
            cargarFotosGenerales(target);
            return;
        }

        if (action === 'cargar-fotos-talla') {
            const key = target.dataset.key || '';
            cargarFotosPorTalla(key, target);
        }
    });

    modalPorTallasEventosIniciados = true;
}

// =============================================================================
// ESTRUCTURA INTERNA POR BLOQUES
// estado | ui | render | eventos | persistencia
// =============================================================================
function cambiarModoModalPorTallas(nuevoModo) {
    const btnGeneral = document.getElementById('btn-modo-general');
    const btnEspecifico = document.getElementById('btn-modo-especifico');
    
    if (nuevoModo === modoModalPorTallasActual) return;

    modoModalPorTallasActual = nuevoModo;

    // Actualizar estilos de botones - ambos siempre habilitados
    if (btnGeneral && btnEspecifico) {
        const generalActivo = nuevoModo === 'general';
        btnGeneral.style.cssText = _obtenerEstiloBotonModo(generalActivo);
        btnEspecifico.style.cssText = _obtenerEstiloBotonModo(!generalActivo);
        btnGeneral.disabled = false;
        btnEspecifico.disabled = false;
    }

    // Mostrar/ocultar contenedores
    PorTallasModulos.ui.mostrarContenedoresPorModo(nuevoModo);

    //  Registrar cambio de modo en el editor de procesos
    if (globalThis.procesosEditor && procesoPorTallasActual) {
        globalThis.procesosEditor.registrarCambioModoTallas(nuevoModo);
        _log('[por-tallas]Cambio de modo registrado en editor:', nuevoModo);
    }

    _log('[por-tallas] Cambiado a modo:', nuevoModo);
};

/**
 * Cargar fotos generales (compartidas para todas las tallas)
 */
function cargarFotosGenerales(inputElement) {
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

    _log('[agregarImagenesGenerales]  Intentando agregar', validos.length, 'imágenes');
    
    // Procesar cada archivo y calcular su hash para detectar duplicados
    validos.forEach((file, idx) => {
        _log('[agregarImagenesGenerales]   Archivo', idx, ':', file.name, file.size, 'bytes');
        
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
            _log('[agregarImagenesGenerales]  UID asignado al File:', file.uid, file.name);
        }
        
        // Agregar a los arrays
        const blobUrl = URL.createObjectURL(file);
        fotosGeneralesTemp.push(blobUrl);
        fotosGeneralesFilesTemp.push(file);
        
        _log('[agregarImagenesGenerales]  Imagen agregada:', fileKey, 'UID:', file.uid);
    });
    
    _log('[agregarImagenesGenerales] 🎉 Total en fotosGeneralesTemp después:', fotosGeneralesTemp.length);
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
        const src = _resolverSrcImagen(item);
        const esExistente = idx < fotosGeneralesExistentes.length;
        const indiceReal = idx;
        
        return `
        <div style="position:relative; width:70px; height:70px; border-radius:6px; overflow:hidden; border:1px solid #ddd; flex-shrink:0;">
            <img src="${src}" style="width:100%;height:100%;object-fit:cover;">
            <button type="button" data-prt-action="eliminar-foto-general" data-index="${indiceReal}" data-existente="${esExistente}"
                style="position:absolute;top:2px;right:2px;background:rgba(220,38,38,0.85);color:white;border:none;border-radius:50%;width:18px;height:18px;font-size:12px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;">&times;</button>
        </div>
    `;
    }).join('');

    galeria.innerHTML = `
        ${thumbs}
        ${_crearBotonAgregarGaleriaHTML('prt-foto-input-general', { dashedColor: '#ccc', iconColor: '#999', textColor: '#999' })}
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
    const safeKey = convertirAKeySegura(actualKey);
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
    
    _log('[por-tallas]  FUENTE 1 - Leyendo desde tabla de resumen (PRIORITARIO)');
    
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
    if (hayTallas) _log('[por-tallas]  FUENTE 1 exitosa:', tallasPrenda);
    
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
    
    _log('[por-tallas]FUENTE 2 - Leyendo desde tallasRelacionales');
    
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
    
    _log('[por-tallas]FUENTE 3 - Leyendo desde StateManager');
    
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
            _log('[por-tallas]  Tallas obtenidas desde proceso guardado (fuente secundaria)');
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
    
    _log('[por-tallas]  Combinando tallas evitando duplicados');
    
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
        _log('[por-tallas] ✓ Usando TABLA DE RESUMEN (fuente primaria)');
        return _combinarTallasEvitandoDuplicados(tallasTabla, tipoProceso);
    }
    _log('[por-tallas] ✗ Tabla no available, intentando siguiente fuente...');

    const { tallasPrenda: tallasRel, hayTallas: hayEnRel } = _cargarTallasDesdeRelacionales();
    if (hayEnRel) {
        _log('[por-tallas] ✓ Usando TALLAS RELACIONALES (fuente secundaria)');
        return _combinarTallasEvitandoDuplicados(tallasRel, tipoProceso);
    }
    _log('[por-tallas] ✗ Relacionales no available, intentando siguiente fuente...');

    const { tallasPrenda: tallasSM, hayTallas: hayEnSM } = _cargarTallasDesdeStateManager();
    if (hayEnSM) {
        _log('[por-tallas] ✓ Usando STATE MANAGER (fuente terciaria)');
        return _combinarTallasEvitandoDuplicados(tallasSM, tipoProceso);
    }
    _log('[por-tallas] ✗ StateManager no available, intentando última fuente...');

    const tallasBD = _obtenerTallasDelProcesoGuardado();
    if (tallasBD) {
        _log('[por-tallas] ✓ Usando PROCESO GUARDADO (fuente cuaternaria)');
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
    
    _log('[por-tallas]  Fotos generales cargadas:', {
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
        const url = img.previewUrl || img.dataURL || img.url || img.ruta_webp || img.ruta_original || img.ruta || '';
        return typeof url === 'string' && !url.startsWith('blob:') && !url.startsWith('data:');
    }
    return false;
}

function _agregarStorageUrl(url) {
    if (!url || typeof url !== 'string') return '';
    if (url.startsWith('/')) return url;
    if (url.startsWith('http')) return url;
    if (url.startsWith('blob:')) return url;
    if (url.startsWith('data:')) return url;
    return `/storage/${url}`;
}

function _resolverSrcImagen(item) {
    if (!item) return '';
    if (item instanceof File) return URL.createObjectURL(item);

    if (typeof item === 'string') {
        return _agregarStorageUrl(item);
    }

    if (typeof item === 'object') {
        if (item.file instanceof File) {
            return URL.createObjectURL(item.file);
        }
        const url = item.previewUrl || item.dataURL || item.url || item.ruta_webp || item.ruta_original || item.ruta || '';
        return _agregarStorageUrl(url);
    }

    return '';
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
        _log(`[_buscarDatos] Género "${genero}" no encontrado en datosExistentes`);
        return { datos: null, estrategiaUsada: null };
    }
    
    // Estrategia 1: Búsqueda exacta
    if (datosExistentes[genero][tallaKey]) {
        _log(`[_buscarDatos] ✓ Datos encontrados (búsqueda exacta): "${tallaKey}"`);
        return { datos: datosExistentes[genero][tallaKey], estrategiaUsada: 'exacta' };
    }
    
    // Estrategia 2: Búsqueda con variante de color
    const claveConColor = Object.keys(datosExistentes[genero]).find(clave => 
        clave.startsWith(tallaKey + '__')
    );
    
    if (claveConColor) {
        _log(`[_buscarDatos] ✓ Datos encontrados (búsqueda con variante): "${tallaKey}" → "${claveConColor}"`);
        return { datos: datosExistentes[genero][claveConColor], estrategiaUsada: 'variante' };
    }
    
    _log(`[_buscarDatos] ✗ No se encontraron datos para talla: "${tallaKey}"`);
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
    // Buscar primero en procesosGuardados (BD), luego en procesosSeleccionados (session)
    const dbData = globalThis.procesosGuardados?.[tipoProceso]?.datos || 
                   globalThis.procesosSeleccionados?.[tipoProceso]?.datos || 
                   {};
    const modoDb = dbData.modo_tallas?.toLowerCase();
    const datosExt = dbData.datosExtendidos || {};
    
    const hayEspecificos = (datosExt.dama && Object.keys(datosExt.dama).length > 0) ||
                           (datosExt.caballero && Object.keys(datosExt.caballero).length > 0) ||
                           (datosExt.sobremedida && Object.keys(datosExt.sobremedida).length > 0);
    
    // Si está guardado como específico Y tiene datos específicos, mantener ese modo
    // Si está guardado como específico pero NO tiene datos específicos, puede ser nuevo
    const resultado = (modoDb === 'especifico') ? 'especifico' : 'general';
    
    _log('[_determinarModoFinal] Determinando modo para:', tipoProceso, {
        modoDb,
        hayEspecificos,
        datosExtensionCount: {
            dama: Object.keys(datosExt.dama || {}).length,
            caballero: Object.keys(datosExt.caballero || {}).length,
            sobremedida: Object.keys(datosExt.sobremedida || {}).length
        },
        resultado
    });
    
    return resultado;
}
