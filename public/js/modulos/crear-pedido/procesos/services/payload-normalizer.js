/**
 * ============================================================================
 * DEPRECATED - Usar payload-normalizer-v3-definitiva.js en su lugar
 * ============================================================================
 * Este archivo ha sido REEMPLAZADO por una versión más segura y robusta.
 * No debe cargarse. Ver payload-normalizer-v3-definitiva.js
 */

console.warn('[payload-normalizer.js]   DEPRECATED - Este archivo no debe cargarse.');

// HELPER: Normalizar tallas: "20" → 20
function normalizarTallas(tallasRaw) {
    if (!tallasRaw || typeof tallasRaw !== 'object') return {};
    const tallasNorm = {};
    Object.entries(tallasRaw).forEach(function([genero, tallasCant]) {
        if (!tallasCant || typeof tallasCant !== 'object') {
            tallasNorm[genero] = {};
            return;
        }
        tallasNorm[genero] = {};
        Object.entries(tallasCant).forEach(function([talla, cantidad]) {
            const num = parseInt(cantidad, 10);
            if (!isNaN(num) && num > 0) {
                tallasNorm[genero][talla] = num;
            }
        });
    });
    return tallasNorm;
}

// HELPER: Normalizar telas
function normalizarTelas(telasRaw) {
    if (!Array.isArray(telasRaw) || telasRaw.length === 0) return [];
    return telasRaw.map(function(tela) {
        return {
            tela_id: tela.tela_id,
            color_id: tela.color_id,
            tela_nombre: tela.tela_nombre || tela.tela || '',
            color_nombre: tela.color_nombre || tela.color || '',
            referencia: tela.referencia || ''
        };
    });
}

// HELPER: Normalizar tallas dentro de procesos
function normalizarTallasProceso(proceso) {
    let tallasBruta = null;
    if (proceso.tallas && typeof proceso.tallas === 'object') {
        tallasBruta = proceso.tallas;
    } else if (proceso.datos && proceso.datos.tallas && typeof proceso.datos.tallas === 'object') {
        tallasBruta = proceso.datos.tallas;
    }
    if (!tallasBruta) return {};
    return normalizarTallas(tallasBruta);
}

// HELPER: Normalizar procesos
function normalizarProcesos(procesosRaw) {
    if (!procesosRaw || typeof procesosRaw !== 'object') return {};
    const procesosNorm = {};
    Object.entries(procesosRaw).forEach(function([procesoKey, proceso]) {
        if (!proceso || typeof proceso !== 'object') return;
        procesosNorm[procesoKey] = {
            tipo: proceso.tipo,
            ubicaciones: Array.isArray(proceso.ubicaciones) ? proceso.ubicaciones : [],
            observaciones: proceso.observaciones || '',
            tallas: normalizarTallasProceso(proceso)
        };
        console.log('[PayloadNormalizer]  Proceso ' + procesoKey + ' normalizado');
    });
    return procesosNorm;
}

// HELPER: Normalizar una prenda
function normalizarItem(item) {
    const itemNorm = {
        tipo: item.tipo,
        nombre_prenda: item.nombre_prenda,
        descripcion: item.descripcion,
        origen: item.origen
    };
    itemNorm.cantidad_talla = normalizarTallas(item.cantidad_talla);
    itemNorm.variaciones = (item.variaciones && typeof item.variaciones === 'object') ? item.variaciones : {};
    console.log('[PayloadNormalizer] Variaciones preservadas:', itemNorm.variaciones);
    itemNorm.telas = normalizarTelas(item.telas);
    itemNorm.procesos = normalizarProcesos(item.procesos);
    return itemNorm;
}

// HELPER: Limpiar Files recursivamente
function limpiarFiles(obj) {
    if (obj === null || obj === undefined) return obj;
    if (obj instanceof File) return null;
    if (Array.isArray(obj)) return obj.map(function(item) { return limpiarFiles(item); });
    if (typeof obj === 'object') {
        const limpio = {};
        for (const key in obj) {
            if (obj.hasOwnProperty(key)) {
                limpio[key] = limpiarFiles(obj[key]);
            }
        }
        return limpio;
    }
    return obj;
}

// HELPER: Validar que NO hay Files en JSON
function validarNoHayFiles(jsonString) {
    if (!jsonString || typeof jsonString !== 'string') return;
    if (jsonString.includes('[object File]') || jsonString.includes('File')) {
        throw new Error(' CRITICAL: Files detectados en JSON.');
    }
}

// HELPER: Construir FormData
function buildFormData(pedidoNormalizado, filesExtraidos) {
    const formData = new FormData();
    formData.append('pedido', JSON.stringify(pedidoNormalizado));
    if (filesExtraidos && filesExtraidos.prendas && Array.isArray(filesExtraidos.prendas)) {
        filesExtraidos.prendas.forEach((prenda, prendasIdx) => {
            if (prenda.imagenes && Array.isArray(prenda.imagenes)) {
                prenda.imagenes.forEach((imagen, imgIdx) => {
                    if (imagen instanceof File) {
                        formData.append(`prendas[${prendasIdx}][imagenes][${imgIdx}]`, imagen);
                    }
                });
            }
            if (prenda.telas && Array.isArray(prenda.telas)) {
                prenda.telas.forEach((tela, telaIdx) => {
                    if (tela.imagenes && Array.isArray(tela.imagenes)) {
                        tela.imagenes.forEach((imagen, imgIdx) => {
                            if (imagen instanceof File) {
                                formData.append(`prendas[${prendasIdx}][telas][${telaIdx}][imagenes][${imgIdx}]`, imagen);
                            }
                        });
                    }
                });
            }
            if (prenda.procesos && typeof prenda.procesos === 'object') {
                Object.entries(prenda.procesos).forEach(([procesoKey, proceso]) => {
                    if (proceso.imagenes && Array.isArray(proceso.imagenes)) {
                        proceso.imagenes.forEach((imagen, imgIdx) => {
                            if (imagen instanceof File) {
                                formData.append(`prendas[${prendasIdx}][procesos][${procesoKey}][imagenes][${imgIdx}]`, imagen);
                            }
                        });
                    }
                });
            }
        });
    }
    if (filesExtraidos && filesExtraidos.epps && Array.isArray(filesExtraidos.epps)) {
        filesExtraidos.epps.forEach((epp, eppIdx) => {
            if (epp.imagenes && Array.isArray(epp.imagenes)) {
                epp.imagenes.forEach((imagen, imgIdx) => {
                    if (imagen instanceof File) {
                        formData.append(`epps[${eppIdx}][imagenes][${imgIdx}]`, imagen);
                    }
                });
            }
        });
    }
    console.log('[PayloadNormalizer] FormData construido');
    return formData;
}

// FUNCIÓN PRINCIPAL: Normalizar pedido
function normalizarPedido(pedidoRaw) {
    if (!pedidoRaw || typeof pedidoRaw !== 'object') {
        console.warn('[PayloadNormalizer]  Pedido inválido:', pedidoRaw);
        return pedidoRaw;
    }
    console.log('[PayloadNormalizer] 🔄 Normalizando pedido completo...');
    const pedidoNorm = {
        cliente: pedidoRaw.cliente,
        asesora: pedidoRaw.asesora,
        forma_de_pago: pedidoRaw.forma_de_pago,
        descripcion: pedidoRaw.descripcion || '',
    };
    if (Array.isArray(pedidoRaw.prendas) || Array.isArray(pedidoRaw.epps)) {
        pedidoNorm.prendas = [];
        pedidoNorm.epps = [];
        if (Array.isArray(pedidoRaw.prendas)) {
            pedidoRaw.prendas.forEach(function(prenda, idx) {
                const prendaNorm = normalizarItem(prenda);
                pedidoNorm.prendas.push(prendaNorm);
                console.log('[PayloadNormalizer] Prenda ' + idx + ' normalizada');
            });
        }
        if (Array.isArray(pedidoRaw.epps)) {
            pedidoRaw.epps.forEach(function(epp, idx) {
                const eppNorm = normalizarEpp(epp);
                pedidoNorm.epps.push(eppNorm);
                console.log('[PayloadNormalizer] EPP ' + idx + ' normalizado');
            });
        }
    } else {
        pedidoNorm.items = [];
        if (Array.isArray(pedidoRaw.items)) {
            pedidoRaw.items.forEach(function(item, idx) {
                const itemNorm = normalizarItem(item);
                pedidoNorm.items.push(itemNorm);
                console.log('[PayloadNormalizer] Item ' + idx + ' normalizado');
            });
        }
    }
    console.log('[PayloadNormalizer] Pedido completo normalizado');
    return pedidoNorm;
}

// === EXPORT AL GLOBAL - VERIFICACIÓN TRIPLE ===
try {
    window.PayloadNormalizer = window.PayloadNormalizer || {};
    window.PayloadNormalizer.normalizar = normalizarPedido;
    window.PayloadNormalizer.buildFormData = buildFormData;
    window.PayloadNormalizer.limpiarFiles = limpiarFiles;
    window.PayloadNormalizer.validarNoHayFiles = validarNoHayFiles;
    window.PayloadNormalizer.normalizarTallas = normalizarTallas;
    window.PayloadNormalizer.normalizarTelas = normalizarTelas;
    window.PayloadNormalizer.normalizarProcesos = normalizarProcesos;
    
    // Verificación inmediata
    const metodosExportados = Object.keys(window.PayloadNormalizer);

    // Validación de cada método
    metodosExportados.forEach(function(metodo) {
        const tipo = typeof window.PayloadNormalizer[metodo];
        console.log('[PayloadNormalizer v2.0] ✓ ' + metodo + ': ' + tipo);
    });
} catch (error) {
    console.error('[PayloadNormalizer v2.0]  ERROR en export:', error);
}


