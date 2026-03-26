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
window.pasteListenerPorTallas = function(e) {
    const modal = document.getElementById('modal-proceso-por-tallas');
    if (!modal || modal.style.display === 'none') return;
    
    console.log('[pasteListenerPorTallas]  PASTE detectado en document');
    handlePasteGlobalPorTallas(e);
};

// Registrar listener globalmente en capture phase (máxima prioridad)
document.addEventListener('paste', window.pasteListenerPorTallas, true);

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
window.cambiarModoModalPorTallas = function(nuevoModo) {
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
    if (window.procesosEditor && procesoPorTallasActual) {
        window.procesosEditor.registrarCambioModoTallas(nuevoModo);
        console.log('[por-tallas] 📊 Cambio de modo registrado en editor:', nuevoModo);
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

    console.log('[agregarImagenesGenerales] 📸 Intentando agregar', validos.length, 'imágenes');
    
    // Procesar cada archivo y calcular su hash para detectar duplicados
    validos.forEach((file, idx) => {
        console.log('[agregarImagenesGenerales]   Archivo', idx, ':', file.name, file.size, 'bytes');
        
        // Crear una clave única para el archivo basada en nombre + tamaño + tipo
        // (no es un hash criptográfico real, pero funciona para detectar duplicados)
        const fileKey = `${file.name}_${file.size}_${file.type}`;
        
        // Verificar si ya existe una imagen con la misma clave en fotosGeneralesTemp
        const yaExiste = window._fotosGeneralesKeys && window._fotosGeneralesKeys.has(fileKey);
        
        if (yaExiste) {
            console.warn('[agregarImagenesGenerales] ⚠️ DUPLICADO DETECTADO:', fileKey, '- Ignorando');
            return; // Skip this file
        }
        
        // Inicializar el Set si no existe
        if (!window._fotosGeneralesKeys) {
            window._fotosGeneralesKeys = new Set();
        }
        
        // Marcar como procesado
        window._fotosGeneralesKeys.add(fileKey);
        
        // NUEVO: Asignar UID único a cada File para mapeo backend
        if (!file.uid) {
            file.uid = `uid-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
            console.log('[agregarImagenesGenerales] 🆔 UID asignado al File:', file.uid, file.name);
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
    
    // Limpiar ubicación general y campo input
    ubicacionGeneralTemp = '';
    const inputUbicacionGeneral = document.getElementById('ubicacion-general-input');
    if (inputUbicacionGeneral) inputUbicacionGeneral.value = '';

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

    // 🔥 FIX: Obtener tallas desde MÚLTIPLES FUENTES en orden de prioridad
    // 1. PRIMERO: Desde tabla de resumen visible (CONTIENE ESTADO ACTUAL: nuevas + guardadas)
    // 2. SEGUNDO: Desde window.tallasRelacionales (tallas MANUALMENTE ingresadas en tarjetas)
    // 3. TERCERO: Desde StateManager (tallas del WIZARD con colores)
    // 4. CUARTO: Desde obtenerTallasDeLaPrenda (fallback para datos guardados en BD)
    
    let tallasPrenda = { dama: {}, caballero: {}, sobremedida: null };
    let leyóDesdeTabla = false;
    
    // ═ FUENTE 1: Tabla de resumen visible (PRIORITARIA - estado actual completo) ═
    // En modo EDICIÓN, la tabla de resumen SIEMPRE es la más confiable porque muestra
    // TODAS las tallas del pedido (tanto las de DB como las nuevas agregadas)
    const tablaResumenBody = document.getElementById('tabla-resumen-asignaciones-cuerpo');
    
    if (tablaResumenBody && tablaResumenBody.querySelectorAll('tr').length > 0) {
        console.log('[por-tallas]  FUENTE 1 - Leyendo desde tabla de resumen (PRIORITARIO)');
        console.log('[por-tallas] Filas encontradas en tabla:', tablaResumenBody.querySelectorAll('tr').length);
        
        const filasDebug = [];
        tablaResumenBody.querySelectorAll('tr[data-tipo="wizard"]').forEach((fila, idx) => {
            const dataGenero = fila.querySelector('[data-field="genero"]')?.textContent?.trim().toLowerCase();
            const dataTalla = fila.querySelector('[data-field="talla"]')?.textContent?.trim().toUpperCase();
            const dataCantidadTotal = parseInt(fila.querySelector('[data-field="cantidad"]')?.textContent?.trim() || '1');
            
            // 🎨 EXTRAER COLORES Y CANTIDADES de la celda [data-field="color"]
            // Estructura: <span>AQUA (1)</span><span>AMATISTA (1)</span> etc.
            const colorCell = fila.querySelector('[data-field="color"]');
            const coloresConCantidad = []; // Array de {color: 'AQUA', cantidad: 1}
            if (colorCell) {
                const badgesColores = colorCell.querySelectorAll('span');
                badgesColores.forEach(badge => {
                    const colorText = badge.textContent?.trim();
                    if (colorText && colorText !== 'Sin color') {
                        // Extraer color y cantidad: "AQUA (1)" → color: "AQUA", cantidad: 1
                        const match = colorText.match(/^(.+?)\s*\(\s*(\d+)\s*\)$/);
                        if (match) {
                            const colorLimpio = match[1].trim();
                            const cantidadColor = parseInt(match[2]) || 1;
                            coloresConCantidad.push({ color: colorLimpio, cantidad: cantidadColor });
                        } else {
                            // Si no tiene formato (X), asumir cantidad 1
                            coloresConCantidad.push({ color: colorText, cantidad: 1 });
                        }
                    }
                });
            }
            
            filasDebug.push({
                idx,
                genero: dataGenero,
                talla: dataTalla,
                cantidad: dataCantidadTotal,
                colores: coloresConCantidad.map(c => c.color),
                valido: dataTalla && dataCantidadTotal > 0
            });
            
            if (dataTalla && dataCantidadTotal > 0) {
                const tallasKey = dataGenero === 'caballero' ? 'caballero' : (dataGenero === 'sobremedida' || dataGenero === 'unisex' ? 'sobremedida' : 'dama');
                
                // 🔑 CREAR TARJETA PARA CADA COLOR (si hay) O UNA SOLA TARJETA (si no hay)
                if (coloresConCantidad.length > 0) {
                    // Para cada color, crear una clave TALLA__COLOR con su cantidad específica
                    coloresConCantidad.forEach(({color, cantidad}) => {
                        const claveParaAgregar = `${dataTalla}__${color}`;
                        
                        if (tallasKey === 'sobremedida') {
                            if (!tallasPrenda.sobremedida) tallasPrenda.sobremedida = {};
                            tallasPrenda.sobremedida[claveParaAgregar] = (tallasPrenda.sobremedida[claveParaAgregar] || 0) + cantidad;
                        } else {
                            tallasPrenda[tallasKey][claveParaAgregar] = (tallasPrenda[tallasKey][claveParaAgregar] || 0) + cantidad;
                        }
                        console.log(`[por-tallas]  Fila ${idx}: ${tallasKey.toUpperCase()} - ${claveParaAgregar} - ${cantidad} unidades`);
                    });
                } else {
                    // Sin colores: crear una sola tarjeta con solo TALLA
                    if (tallasKey === 'sobremedida') {
                        if (!tallasPrenda.sobremedida) tallasPrenda.sobremedida = {};
                        tallasPrenda.sobremedida[dataTalla] = (tallasPrenda.sobremedida[dataTalla] || 0) + dataCantidadTotal;
                    } else {
                        tallasPrenda[tallasKey][dataTalla] = (tallasPrenda[tallasKey][dataTalla] || 0) + dataCantidadTotal;
                    }
                    console.log(`[por-tallas]  Fila ${idx}: ${tallasKey.toUpperCase()} - ${dataTalla} - ${dataCantidadTotal} unidades (sin color)`);
                }
            }
        });
        
        console.log('[por-tallas] 📊 Resumen de filas leídas:', filasDebug);
        
        // Si encontró tallas en la tabla, marcar como exitoso
        if (Object.keys(tallasPrenda.dama).length > 0 || Object.keys(tallasPrenda.caballero).length > 0 || tallasPrenda.sobremedida) {
            leyóDesdeTabla = true;
            console.log('[por-tallas]  FUENTE 1 exitosa - Tallas desde tabla de resumen:', tallasPrenda);
        }
    }

    // ═ FUENTE 2: window.tallasRelacionales (si tabla no tuvo datos) ═
    if (!leyóDesdeTabla) {
        const tallasRelacionales = window.tallasRelacionales || { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
        const hayTallasRelacionales = Object.values(tallasRelacionales).some(generoTallas => 
            generoTallas && typeof generoTallas === 'object' && Object.keys(generoTallas).length > 0
        );
        
        if (hayTallasRelacionales) {
            console.log('[por-tallas] 📊 FUENTE 2 - Leyendo desde window.tallasRelacionales:', tallasRelacionales);
            
            // Copiar DAMA
            if (tallasRelacionales.DAMA && Object.keys(tallasRelacionales.DAMA).length > 0) {
                tallasPrenda.dama = { ...tallasRelacionales.DAMA };
            }
            // Copiar CABALLERO
            if (tallasRelacionales.CABALLERO && Object.keys(tallasRelacionales.CABALLERO).length > 0) {
                tallasPrenda.caballero = { ...tallasRelacionales.CABALLERO };
            }
            // Copiar SOBREMEDIDA y UNISEX
            if (tallasRelacionales.SOBREMEDIDA && Object.keys(tallasRelacionales.SOBREMEDIDA).length > 0) {
                tallasPrenda.sobremedida = { ...tallasRelacionales.SOBREMEDIDA };
            }
            if (tallasRelacionales.UNISEX && Object.keys(tallasRelacionales.UNISEX).length > 0) {
                tallasPrenda.sobremedida = { ...(tallasPrenda.sobremedida || {}), ...tallasRelacionales.UNISEX };
            }
            
            console.log('[por-tallas]  Tallas desde tallasRelacionales:', tallasPrenda);
        }
    }
    
    // ═ FUENTE 3: StateManager (si aún no hay datos) ═
    if (!leyóDesdeTabla && (Object.keys(tallasPrenda.dama).length === 0 && Object.keys(tallasPrenda.caballero).length === 0) && !tallasPrenda.sobremedida) {
        if (window.StateManager && typeof window.StateManager.getAsignaciones === 'function') {
            const asignaciones = window.StateManager.getAsignaciones();
            if (asignaciones && typeof asignaciones === 'object' && Object.keys(asignaciones).length > 0) {
                console.log('[por-tallas] 📊 FUENTE 3 - Leyendo tallas desde StateManager (asignaciones wizard):', asignaciones);
                
                Object.entries(asignaciones).forEach(([clave, asignacion]) => {
                    const genero = asignacion.genero ? asignacion.genero.toLowerCase() : 'dama';
                    const tallasKey = genero === 'caballero' ? 'caballero' : (genero === 'sobremedida' || genero === 'unisex' ? 'sobremedida' : 'dama');
                    
                    if (asignacion.talla) {
                        const talla = asignacion.talla;
                        const colores = asignacion.colores || [];
                        const totalCant = colores.reduce((sum, c) => sum + (c.cantidad || 1), 0);
                        if (totalCant > 0) {
                            if (tallasKey === 'sobremedida') {
                                if (!tallasPrenda.sobremedida) tallasPrenda.sobremedida = {};
                                tallasPrenda.sobremedida[talla] = (tallasPrenda.sobremedida[talla] || 0) + totalCant;
                            } else {
                                tallasPrenda[tallasKey][talla] = (tallasPrenda[tallasKey][talla] || 0) + totalCant;
                            }
                        }
                    }
                });
                
                console.log('[por-tallas]  Tallas desde StateManager:', tallasPrenda);
            }
        }
    }

    // ═ FUENTE 4: Fallback a obtenerTallasDeLaPrenda (datos guardados en BD) ═
    if (!leyóDesdeTabla && (Object.keys(tallasPrenda.dama).length === 0 && Object.keys(tallasPrenda.caballero).length === 0) && !tallasPrenda.sobremedida) {
        const tallasFallback = (typeof obtenerTallasDeLaPrenda === 'function')
            ? obtenerTallasDeLaPrenda()
            : { dama: {}, caballero: {}, sobremedida: null };
        
        if (Object.keys(tallasFallback.dama).length > 0 || Object.keys(tallasFallback.caballero).length > 0 || tallasFallback.sobremedida) {
            tallasPrenda.dama = { ...tallasFallback.dama };
            tallasPrenda.caballero = { ...tallasFallback.caballero };
            tallasPrenda.sobremedida = tallasFallback.sobremedida;
            console.log('[por-tallas]  Tallas desde obtenerTallasDeLaPrenda (fallback):', tallasPrenda);
        }
    }

    // Si el proceso ya tiene tallas propias guardadas, COMBINARLAS con las nuevas tallas agregadas
    // PERO evitando duplicados: si una talla ya existe con cualquier nombre (ej: M__AZUL_ACERO vs M),
    // no se agrega duplicada
    const procesoTallasGuardadas = window.procesosSeleccionados?.[tipoProceso]?.datos?.tallas;
    if (procesoTallasGuardadas && typeof procesoTallasGuardadas === 'object') {
        const damaObj = (procesoTallasGuardadas.dama && !Array.isArray(procesoTallasGuardadas.dama)) ? procesoTallasGuardadas.dama : {};
        const cabObj = (procesoTallasGuardadas.caballero && !Array.isArray(procesoTallasGuardadas.caballero)) ? procesoTallasGuardadas.caballero : {};
        const sobreObj = (procesoTallasGuardadas.sobremedida && !Array.isArray(procesoTallasGuardadas.sobremedida)) ? procesoTallasGuardadas.sobremedida : null;
        if (Object.keys(damaObj).length > 0 || Object.keys(cabObj).length > 0) {
            console.log('[por-tallas] 🔄 Combinando tallas guardadas en BD + nuevas tallas agregadas después (evitando duplicados)');
            
            // Extraer TALLAS BASE de las guardadas (primera parte antes de __)
            // Ej: M__AZUL_ACERO -> M, XXXXL__AZUL_CELESTE -> XXXXL, M -> M
            const extraerTallaBase = (clave) => {
                const partes = clave.split('__');
                return partes[0].toUpperCase(); // PRIMERA parte es la talla
            };
            
            const tallasBaseGuardadas = {
                dama: new Set(Object.keys(damaObj).map(extraerTallaBase)),
                caballero: new Set(Object.keys(cabObj).map(extraerTallaBase))
            };
            
            console.log('[por-tallas] 📝 Tallas base guardadas (DAMA):', Array.from(tallasBaseGuardadas.dama));
            console.log('[por-tallas] 📝 Tallas base guardadas (CABALLERO):', Array.from(tallasBaseGuardadas.caballero));
            
            // Agregar tallas nuevas SOLO si su nombre base no existe en guardadas
            Object.entries(tallasPrenda.dama || {}).forEach(([key, val]) => {
                const tallaBase = key.toUpperCase();
                if (!tallasBaseGuardadas.dama.has(tallaBase)) {
                    console.log(`[por-tallas]  Agregando talla DAMA nueva: ${key}`);
                    damaObj[key] = val;
                } else {
                    console.log(`[por-tallas] ⊘ Talla DAMA duplicada ignorada: ${key}`);
                }
            });
            
            Object.entries(tallasPrenda.caballero || {}).forEach(([key, val]) => {
                const tallaBase = key.toUpperCase();
                if (!tallasBaseGuardadas.caballero.has(tallaBase)) {
                    console.log(`[por-tallas]  Agregando talla CABALLERO nueva: ${key}`);
                    cabObj[key] = val;
                } else {
                    console.log(`[por-tallas] ⊘ Talla CABALLERO duplicada ignorada: ${key}`);
                }
            });
            
            // Mantener sobremedida
            if (sobreObj && Object.keys(sobreObj).length > 0) {
                tallasPrenda.sobremedida = sobreObj;
            }
            
            tallasPrenda.dama = damaObj;
            tallasPrenda.caballero = cabObj;
            console.log('[por-tallas]  Tallas combinadas sin duplicados:', tallasPrenda);
        }
    }

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
    
    console.log('[por-tallas]  DEBUGGING abrirModalProcesoPorTallas', {
        tipoProceso,
        procesoCompleto: window.procesosSeleccionados?.[tipoProceso],
        datosCompletos: window.procesosSeleccionados?.[tipoProceso]?.datos,
        datosExistentes,
        tieneDatatos: !!datosExistentes,
        estructuraDatos: datosExistentes ? Object.keys(datosExistentes) : 'VACIO',
        modo_tallas_directo: window.procesosSeleccionados?.[tipoProceso]?.datos?.modo_tallas,
        todosLosCampos: window.procesosSeleccionados?.[tipoProceso]?.datos ? Object.keys(window.procesosSeleccionados?.[tipoProceso]?.datos).slice(0, 20) : 'SIN DATOS'
    });

    // ─── Recuperar datos generales si existen ───
    const datosGenerales = window.procesosSeleccionados?.[tipoProceso]?.datos;
    
    // Mapear ubicación general (puede venir como ubicacionGeneral o ubicaciones array)
    let ubicacionDisplay = '';
    if (datosGenerales?.ubicacionGeneral) {
        ubicacionDisplay = datosGenerales.ubicacionGeneral;
    } else if (datosGenerales?.ubicaciones && Array.isArray(datosGenerales.ubicaciones)) {
        ubicacionDisplay = datosGenerales.ubicaciones.filter(u => u && typeof u === 'string').join(', ');
    } else if (datosGenerales?.ubicaciones && typeof datosGenerales.ubicaciones === 'string') {
        ubicacionDisplay = datosGenerales.ubicaciones;
    }
    
    if (ubicacionDisplay) {
        ubicacionGeneralTemp = ubicacionDisplay;
        const inputUbicacion = document.getElementById('ubicacion-general-input');
        if (inputUbicacion) inputUbicacion.value = ubicacionGeneralTemp;
    }
    
    // Mapear fotos generales (puede venir como fotosGenerales o imagenes array)
    // IMPORTANTE: Cargar en fotosGeneralesExistentes (no modificar) e inicializar fotosGeneralesTemp vacío
    fotosGeneralesExistentes = [];
    fotosGeneralesTemp = [];
    fotosGeneralesFilesTemp = [];
    window._fotosGeneralesKeys = new Set(); // Resetear Set de claves para detectar duplicados
    
    if (datosGenerales?.fotosGenerales && Array.isArray(datosGenerales.fotosGenerales)) {
        // Filtrar URLs blob (temporales e inválidas) - solo mantener URLs del storage
        fotosGeneralesExistentes = datosGenerales.fotosGenerales.filter(img => {
            if (typeof img === 'string') {
                // Rechazar blobs y data URLs - solo aceptar rutas del storage
                return !img.startsWith('blob:') && !img.startsWith('data:');
            }
            if (typeof img === 'object' && img) {
                // Para objetos, verificar que tengan una URL válida
                const url = img.url || img.ruta_webp || img.ruta_original || img.ruta || '';
                return typeof url === 'string' && !url.startsWith('blob:') && !url.startsWith('data:');
            }
            return false;
        });
    } else if (datosGenerales?.imagenes && Array.isArray(datosGenerales.imagenes)) {
        // Filtrar URLs blob (temporales e inválidas) - solo mantener URLs del storage
        fotosGeneralesExistentes = datosGenerales.imagenes.filter(img => {
            if (typeof img === 'string') {
                // Rechazar blobs y data URLs - solo aceptar rutas del storage
                return !img.startsWith('blob:') && !img.startsWith('data:');
            }
            if (typeof img === 'object' && img) {
                // Para objetos, verificar que tengan una URL válida
                const url = img.url || img.ruta_webp || img.ruta_original || img.ruta || '';
                return typeof url === 'string' && !url.startsWith('blob:') && !url.startsWith('data:');
            }
            return false;
        });
    }
    
    //  RESTAURAR FILES TEMPORALES: Si hay imagenesFiles (archivos que aún no se subieron al servidor),
    // recrear los blobs para visualización
    if (datosGenerales?.imagenesFiles && Array.isArray(datosGenerales.imagenesFiles) && datosGenerales.imagenesFiles.length > 0) {
        console.log('[por-tallas] 📸 Restaurando Files temporales:', datosGenerales.imagenesFiles.length);
        
        datosGenerales.imagenesFiles.forEach(file => {
            if (file instanceof File) {
                // NUEVO: Asignar UID único a cada File para mapeo backend
                if (!file.uid) {
                    file.uid = `uid-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
                    console.log('[por-tallas] 🆔 UID asignado al File restaurado:', file.uid, file.name);
                }
                
                // Recrear blob URL para visualización
                const blobUrl = URL.createObjectURL(file);
                fotosGeneralesTemp.push(blobUrl);
                fotosGeneralesFilesTemp.push(file);
                
                // Registrar clave para evitar duplicados
                const fileKey = `${file.name}_${file.size}_${file.type}`;
                window._fotosGeneralesKeys.add(fileKey);
            }
        });
        
        console.log('[por-tallas]  Files restaurados:', {
            filesTemp: fotosGeneralesFilesTemp.length,
            blobsTemp: fotosGeneralesTemp.length
        });
    }
    
    console.log('[por-tallas] 🔄 Datos generales cargados:', {
        ubicacionDisplay: ubicacionDisplay,
        fotosExistentes: fotosGeneralesExistentes.length,
        fotosNuevas: fotosGeneralesTemp.length,
        filesTemp: fotosGeneralesFilesTemp.length,
        datosGenerales_ubicacionGeneral: datosGenerales?.ubicacionGeneral,
        datosGenerales_ubicaciones: datosGenerales?.ubicaciones,
        datosGenerales_fotosGenerales: datosGenerales?.fotosGenerales,
        datosGenerales_imagenes: datosGenerales?.imagenes,
        datosGenerales_imagenesFiles: datosGenerales?.imagenesFiles?.length
    });
    
    // Redibujar galería de fotos generales (con o sin fotos existentes)
    renderizarGaleriaFotosGenerales();

    // ─── FUNCTION HELPER: Buscar datos existentes de forma inteligente ───
    // Si tallaKey es "M" pero en BD está como "M__AZUL_ACERO", encontrar esa clave
    const buscarDatosExistentesPorTalla = (genero, tallaKey) => {
        if (!datosExistentes || !datosExistentes[genero]) return null;
        
        // Primero intentar búsqueda exacta
        if (datosExistentes[genero][tallaKey]) {
            return datosExistentes[genero][tallaKey];
        }
        
        // Si no encuentra exacta, buscar cualquier clave que comience con tallaKey__
        // Ej: si busco "M", encontrar "M__AZUL_ACERO"
        const claveConColor = Object.keys(datosExistentes[genero]).find(clave => 
            clave.startsWith(tallaKey + '__')
        );
        
        if (claveConColor) {
            return datosExistentes[genero][claveConColor];
        }
        
        return null;
    };

    // Renderizar DAMA
    if (tallasDama.length > 0 && contDama) {
        secDama.style.display = 'block';
        tallasDama.forEach(([tallaKey, cantidad]) => {
            const key = `dama__${tallaKey}`;
            const existente = buscarDatosExistentesPorTalla('dama', tallaKey);
            
            // Filtrar imágenes: solo mantener URLs válidas del storage (no blobs ni data URLs)
            let imagenesExistentes = [];
            if (existente?.imagenes && Array.isArray(existente.imagenes)) {
                imagenesExistentes = existente.imagenes.filter(img => {
                    if (typeof img === 'string') {
                        return !img.startsWith('blob:') && !img.startsWith('data:');
                    }
                    if (typeof img === 'object' && img) {
                        const url = img.url || img.ruta_webp || img.ruta_original || img.ruta || '';
                        return typeof url === 'string' && !url.startsWith('blob:') && !url.startsWith('data:');
                    }
                    return false;
                });
            } else if (existente?.imagen) {
                // Imagen única (formato antiguo)
                const img = existente.imagen;
                if (typeof img === 'string' && !img.startsWith('blob:') && !img.startsWith('data:')) {
                    imagenesExistentes = [img];
                }
            }
            
            datosPorTallaTemp[key] = {
                seleccionada: true,
                cantidadSeleccionada: existente?.cantidadSeleccionada || cantidad,
                ubicaciones: existente?.ubicaciones ? [...existente.ubicaciones] : [],
                observaciones: existente?.observaciones || '',
                imagenes: imagenesExistentes,
                imagenesFiles: []
            };
            
            //  RESTAURAR FILES TEMPORALES de esta talla (archivos que aún no se subieron)
            if (existente?.imagenesFiles && Array.isArray(existente.imagenesFiles) && existente.imagenesFiles.length > 0) {
                console.log(`[por-tallas] 📸 Restaurando Files para ${key}:`, existente.imagenesFiles.length);
                existente.imagenesFiles.forEach(file => {
                    if (file instanceof File) {
                        const blobUrl = URL.createObjectURL(file);
                        datosPorTallaTemp[key].imagenes.push(blobUrl);
                        datosPorTallaTemp[key].imagenesFiles.push(file);
                    }
                });
            }
            
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
            const existente = buscarDatosExistentesPorTalla('caballero', tallaKey);
            
            // Filtrar imágenes: solo mantener URLs válidas del storage (no blobs ni data URLs)
            let imagenesExistentes = [];
            if (existente?.imagenes && Array.isArray(existente.imagenes)) {
                imagenesExistentes = existente.imagenes.filter(img => {
                    if (typeof img === 'string') {
                        return !img.startsWith('blob:') && !img.startsWith('data:');
                    }
                    if (typeof img === 'object' && img) {
                        const url = img.url || img.ruta_webp || img.ruta_original || img.ruta || '';
                        return typeof url === 'string' && !url.startsWith('blob:') && !url.startsWith('data:');
                    }
                    return false;
                });
            } else if (existente?.imagen) {
                const img = existente.imagen;
                if (typeof img === 'string' && !img.startsWith('blob:') && !img.startsWith('data:')) {
                    imagenesExistentes = [img];
                }
            }
            
            datosPorTallaTemp[key] = {
                seleccionada: true,
                cantidadSeleccionada: existente?.cantidadSeleccionada || cantidad,
                ubicaciones: existente?.ubicaciones ? [...existente.ubicaciones] : [],
                observaciones: existente?.observaciones || '',
                imagenes: imagenesExistentes,
                imagenesFiles: []
            };
            
            //  RESTAURAR FILES TEMPORALES de esta talla (archivos que aún no se subieron)
            if (existente?.imagenesFiles && Array.isArray(existente.imagenesFiles) && existente.imagenesFiles.length > 0) {
                console.log(`[por-tallas] 📸 Restaurando Files para ${key}:`, existente.imagenesFiles.length);
                existente.imagenesFiles.forEach(file => {
                    if (file instanceof File) {
                        const blobUrl = URL.createObjectURL(file);
                        datosPorTallaTemp[key].imagenes.push(blobUrl);
                        datosPorTallaTemp[key].imagenesFiles.push(file);
                    }
                });
            }
            
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

    // ─── Restaurar modo actual (general o especifico) ───
    // CRÍTICO: Buscar en múltiples ubicaciones por compatibilidad
    const modoGuardado = datosGenerales?.modoTallas || datosGenerales?.modo_tallas || 
                         window.procesosSeleccionados?.[tipoProceso]?.modoTallas || 'general';
    
    console.log('[por-tallas] 📊 Modo guardado detectado:', {
        tipoProceso: tipoProceso,
        modo: modoGuardado,
        datosGenerales_modoTallas: datosGenerales?.modoTallas,
        datosGenerales_modo_tallas: datosGenerales?.modo_tallas,
        proceso_modoTallas: window.procesosSeleccionados?.[tipoProceso]?.modoTallas,
        datosGenerales_tipo: datosGenerales?.tipo,
        datosGenerales_id: datosGenerales?.id,
        datosGenerales_exists: !!datosGenerales,
        procesosSeleccionados_keys: Object.keys(window.procesosSeleccionados || {}),
        datosGenerales_allKeys: datosGenerales ? Object.keys(datosGenerales).slice(0, 10) : 'N/A'
    });
    
    // 🔴 CRÍTICO: Si modo_tallas no se encontró en datos, intentar desde la relación tipoProceso
    let modoFinal = modoGuardado;
    if (!datosGenerales?.modoTallas && !datosGenerales?.modo_tallas && datosGenerales?.tipoProceso?.modo_tallas) {
        modoFinal = datosGenerales.tipoProceso.modo_tallas;
        console.warn('[por-tallas] ⚠️ modo_tallas rescatado de tipoProceso.modo_tallas:', modoFinal);
    }
    
    // Cambiar al modo correcto (activa los botones correspondientes)
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

    const manejadorDragLeave = (e) => {
        e.preventDefault();
        e.stopPropagation();
        galeria.style.outline = '';
        galeria.style.outlineOffset = '';
        galeria.style.background = '';
    };
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
function handlePasteGlobalPorTallas(e) {
    console.log('[handlePasteGlobalPorTallas]  PASTE event processing');

    const modal = document.getElementById('modal-proceso-por-tallas');
    if (!modal || modal.style.display === 'none') {
        console.log('[handlePasteGlobalPorTallas]  Modal no visible, ignorando paste');
        return;
    }

    //  CRÍTICO: Verificar que el paste ocurrió DENTRO del modal de procesos
    // Si el usuario está en otro lugar de la página (ej: fotos de prenda o modal wizard), ignorar
    const targetElement = e.target;
    if (!modal.contains(targetElement)) {
        console.log('[handlePasteGlobalPorTallas]  Paste fuera del modal de procesos, ignorando');
        return;
    }
    
    //  NUEVO: Si hay otro modal visible encima (como modal de edición wizard), ignorar
    const modalEditWizard = document.getElementById('modal-editar-asignacion-wizard');
    if (modalEditWizard && modalEditWizard.style.display !== 'none' && modalEditWizard.classList.contains('show')) {
        console.log('[handlePasteGlobalPorTallas]  Modal wizard de edición está abierto, delegando');
        return;
    }

    // Ignorar si el focus está en un input/textarea (el usuario está escribiendo)
    const activeEl = document.activeElement;
    const tag = activeEl?.tagName;
    const isContentEditable = activeEl?.isContentEditable || activeEl?.getAttribute('contenteditable') === 'true';
    
    if (tag === 'INPUT' || tag === 'TEXTAREA' || isContentEditable) {
        console.log('[handlePasteGlobalPorTallas] ⚠️ Focus en', tag, 'o contenteditable - ignorando paste');
        return;
    }

    const items = e.clipboardData?.items;
    if (!items) {
        console.log('[handlePasteGlobalPorTallas]  No clipboard items');
        return;
    }
    
    const files = [];
    for (let i = 0; i < items.length; i++) {
        if (items[i].type.startsWith('image/')) {
            const file = items[i].getAsFile();
            if (file) files.push(file);
        }
    }
    
    console.log('[handlePasteGlobalPorTallas] 📸 Archivos detectados:', files.length);
    if (files.length === 0) return;

    e.preventDefault();
    e.stopImmediatePropagation(); //  CRÍTICO: stopImmediatePropagation evita que otros listeners procesen este evento

    try {
        //  MEJORADO: Detectar automáticamente si el paste ocurrió en la galería general
        const galeriaGeneral = document.getElementById('prt-galeria-general');
        const targetIsInGeneral = galeriaGeneral && galeriaGeneral.contains(e.target);
        
        if (targetIsInGeneral) {
            console.log('[handlePasteGlobalPorTallas] 📍 Paste detectado dentro de galería general (por target)');
        }
        
        // ─── Manejo para FOTOS GENERALES ───
        if (tallaActivaParaPaste === 'GENERAL' || targetIsInGeneral) {
            console.log('[handlePasteGlobalPorTallas] 🖼️ Agregando a GENERAL', {
                porVariable: tallaActivaParaPaste === 'GENERAL',
                porTarget: targetIsInGeneral
            });
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
        let targetKey = tallaActivaParaPaste;

        // Si no hay talla activa, usar la primera disponible
        if (!targetKey || !datosPorTallaTemp[targetKey]) {
            const keys = Object.keys(datosPorTallaTemp);
            if (keys.length > 0) targetKey = keys[0];
        }

        if (targetKey && datosPorTallaTemp[targetKey]) {
            console.log('[handlePasteGlobalPorTallas] 🖼️ Agregando a talla:', targetKey);
            agregarImagenesATalla(targetKey, files);
            const safeKey = convertirAKeySegura(targetKey);
            const gal = document.getElementById(`prt-galeria-${safeKey}`);
            if (gal) {
                gal.style.outline = '2px solid #22c55e';
                gal.style.outlineOffset = '2px';
                setTimeout(() => { gal.style.outline = ''; gal.style.outlineOffset = ''; }, 600);
            }
        }
    } catch (error) {
        console.error('[handlePasteGlobalPorTallas]  Error:', error);
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
window.guardarProcesoPorTallas = function() {
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
                console.warn('[GUARDAR-POR-TALLAS] ⚠️ Clave no encontrada en datosPorTallaTemp:', key, 'Claves disponibles:', Object.keys(datosPorTallaTemp));
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
                console.warn('[GUARDAR-POR-TALLAS] ⚠️ Clave no encontrada en datosPorTallaTemp:', key, 'Claves disponibles:', Object.keys(datosPorTallaTemp));
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
    if (!window.procesosSeleccionados) {
        window.procesosSeleccionados = {};
    }

    if (!window.procesosSeleccionados[procesoPorTallasActual]) {
        window.procesosSeleccionados[procesoPorTallasActual] = {
            tipo: procesoPorTallasActual,
            datos: null
        };
    }

    //  CRÍTICO: Guardar modoTallas en AMBOS niveles (raíz y datos) con el valor CORRECTO
    // Esto asegura que se copie correctamente sin importar dónde lo busque el código
    window.procesosSeleccionados[procesoPorTallasActual].modoTallas = modoModalPorTallasActual;  // 'general' o 'especifico'
    window.procesosSeleccionados[procesoPorTallasActual].datos = {
        tipo: procesoPorTallasActual,
        ubicaciones: modoModalPorTallasActual === 'general' ? [ubicacionGeneralTemp] : [],
        observaciones: '',
        tallas: tallas,
        // 🔧 FIX: Copiar File objects de imagenesFiles a imagenes para que FormData los encuentre
        imagenes: modoModalPorTallasActual === 'general' ? fotosGeneralesFilesTemp : [],
        imagenesFiles: fotosGeneralesFilesTemp, // File objects para las nuevas imágenes
        fotosGeneralesFiles: fotosGeneralesFilesTemp, // Alias para compatibilidad con renderizador
        imagenes_existentes: fotosGeneralesExistentes, // URLs de BD que se van a mantener
        imagenes_a_eliminar: fotosGeneralesEliminadas, // URLs de BD que fueron eliminadas - NOMBRE CORRECTO para que adapter lo encuentre
        imagenesEliminadas: [],
        datosExtendidos: datosExtendidos,
        modoTallas: modoModalPorTallasActual,  // Guardar el modo: 'general' o 'especifico'
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
        proceso: window.procesosSeleccionados[procesoPorTallasActual]
    });

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

    //  COMENTADO: Este bloque estaba agregando PROCESOS como TELAS a window.telasCreacion
    // Los procesos (estampado, DTF, sublimado) NO son telas y no deberían estar en esta lista
    // Las tallas reales ya están asignadas con sus telas correspondientes (ej: ANT BABILONIA)
    
    /* 
    // 🔥 FIX: Sincronizar nuevas tallas con window.telasCreacion para que se muestren en la tabla
    // Las nuevas tallas que se agregaron en el modal deben estar disponibles en telasCreacion
    // para que actualizarTablaResumen() las pueda mostrar
    if (!window.telasCreacion) {
        window.telasCreacion = [];
    }

    let tallasSincronizadas = false;
    Object.entries(tallas).forEach(([genero, tallaDict]) => {
        Object.entries(tallaDict).forEach(([tallaKey, cantidad]) => {
            if (cantidad > 0) {
                // Buscar si esta talla ya existe en telasCreacion
                const yaExiste = window.telasCreacion.some(t => 
                    (t.genero === genero || t.genero === genero.toUpperCase()) &&
                    (t.talla === tallaKey || t.talla === tallaKey.toUpperCase())
                );
                
                if (!yaExiste) {
                    // Agregar nueva talla a telasCreacion
                    const telaDelProceso = procesoPorTallasActual || 'bordado';
                    window.telasCreacion.push({
                        tela: telaDelProceso.toUpperCase(),
                        genero: genero.toUpperCase(),
                        talla: tallaKey.toUpperCase(),
                        color: '',
                        referencia: '',
                        imagenes: [],
                        observaciones: datosExtendidos[genero]?.[tallaKey]?.observaciones || '',
                        cantidad: cantidad,
                        fechaCreacion: new Date().toISOString()
                    });
                    tallasSincronizadas = true;
                    console.log(`[por-tallas]  Sincronizado ${genero.toUpperCase()} - ${tallaKey.toUpperCase()} a telasCreacion`);
                }
            }
        });
    });

    // Actualizar la tabla de resumen si se sincronizaron nuevas tallas
    if (tallasSincronizadas) {
        console.log('[por-tallas] 📊 Actualizando tabla de resumen tras sincronizar nuevas tallas');
        if (window.ColoresPorTalla && typeof window.ColoresPorTalla.actualizarTablaResumen === 'function') {
            window.ColoresPorTalla.actualizarTablaResumen();
        }
    }
    */

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
window.cerrarModalProcesoPorTallas = function() {
    const modal = document.getElementById('modal-proceso-por-tallas');
    if (modal) modal.style.display = 'none';

    // Desregistrar el listener del modal
    desregistrarListenerPasteDelModal();
    procesandoPasteEvent = false; // Resetear flag de protección
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
    fotosGeneralesExistentes = [];
    fotosGeneralesEliminadas = [];
    fotosGeneralesTemp = [];
    fotosGeneralesFilesTemp = [];
    ubicacionGeneralTemp = '';
};

/**
 * Elimina una foto general (compartida)
 */
window.eliminarFotoGeneral = function(index, esExistente) {
    if (esExistente) {
        // Eliminar foto existente de BD - REGISTRARLA PARA ELIMINACIÓN
        if (index < fotosGeneralesExistentes.length) {
            const fotoEliminada = fotosGeneralesExistentes[index];
            console.log('[eliminarFotoGeneral] Eliminando foto existente de BD:', index, fotoEliminada);
            
            // Registrar en array de eliminadas con TODOS los datos para que el backend la identifique
            let imagenAEliminar = {};
            
            if (typeof fotoEliminada === 'string') {
                // Si es solo URL
                imagenAEliminar = {
                    url: fotoEliminada,
                    ruta_original: fotoEliminada
                };
            } else if (typeof fotoEliminada === 'object') {
                // Si es un objeto con id, ruta_original, etc.
                imagenAEliminar = {
                    id: fotoEliminada.id || undefined,
                    url: fotoEliminada.url || fotoEliminada.ruta_original || fotoEliminada.ruta_webp || '',
                    ruta_original: fotoEliminada.ruta_original || fotoEliminada.url || fotoEliminada.ruta_webp || '',
                    ruta_webp: fotoEliminada.ruta_webp || ''
                };
            }
            
            if (imagenAEliminar.url || imagenAEliminar.id) {
                fotosGeneralesEliminadas.push(imagenAEliminar);
                console.log('[eliminarFotoGeneral] Foto eliminada registrada:', imagenAEliminar);
            }
            
            fotosGeneralesExistentes.splice(index, 1);
            console.log('[eliminarFotoGeneral] Fotos eliminadas registradas (total):', fotosGeneralesEliminadas.length);
        }
    } else {
        // Eliminar foto nueva agregada
        const indiceNueva = index - fotosGeneralesExistentes.length;
        if (indiceNueva >= 0 && indiceNueva < fotosGeneralesTemp.length) {
            console.log('[eliminarFotoGeneral] Eliminando foto nueva:', indiceNueva);
            // Revocar URL del blob para liberar memoria
            if (typeof fotosGeneralesTemp[indiceNueva] === 'string' && fotosGeneralesTemp[indiceNueva].startsWith('blob:')) {
                URL.revokeObjectURL(fotosGeneralesTemp[indiceNueva]);
            }
            fotosGeneralesTemp.splice(indiceNueva, 1);
            fotosGeneralesFilesTemp.splice(indiceNueva, 1);
        }
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
    const manejadorDragLeave = (e) => {
        e.preventDefault();
        e.stopPropagation();
        galeria.style.outline = '';
        galeria.style.outlineOffset = '';
        galeria.style.background = '';
    };
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
        console.log('[configurarDragDropPasteGeneral] 🎯 Área general activada para paste');
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
