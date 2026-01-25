/**
 * PayloadNormalizer - VERSIÓN SIMPLE Y ROBUSTA
 * Exportado directamente a window sin IIFE
 */

console.log('[PayloadNormalizer] ⏳ Inicializando...');

window.PayloadNormalizer = window.PayloadNormalizer || {};

// Normalizar pedido completo
window.PayloadNormalizer.normalizar = function(pedidoRaw) {
    console.log('[PayloadNormalizer.normalizar] Entrada:', pedidoRaw);
    
    if (!pedidoRaw || typeof pedidoRaw !== 'object') {
        console.warn('[PayloadNormalizer] Pedido inválido');
        return pedidoRaw;
    }

    const pedido = {
        cliente: pedidoRaw.cliente || '',
        asesora: pedidoRaw.asesora || '',
        forma_de_pago: pedidoRaw.forma_de_pago || '',
        descripcion: pedidoRaw.descripcion || '',
        items: []
    };

    if (Array.isArray(pedidoRaw.items)) {
        pedidoRaw.items.forEach(function(item, idx) {
            pedido.items.push(window.PayloadNormalizer._normalizarItem(item));
        });
    }

    console.log('[PayloadNormalizer.normalizar] Salida:', pedido);
    return pedido;
};

// Normalizar un item (prenda)
window.PayloadNormalizer._normalizarItem = function(item) {
    if (!item) return {};

    const itemNorm = {
        tipo: item.tipo || 'prenda_nueva',
        nombre_prenda: item.nombre_prenda || '',
        descripcion: item.descripcion || '',
        origen: item.origen || 'bodega'
    };

    // Tallas
    itemNorm.cantidad_talla = window.PayloadNormalizer.normalizarTallas(item.cantidad_talla);

    // Variaciones - PRESERVAR
    itemNorm.variaciones = (item.variaciones && typeof item.variaciones === 'object') 
        ? item.variaciones 
        : {};

    // Telas sin imagenes
    itemNorm.telas = window.PayloadNormalizer._normalizarTelas(item.telas);

    // Procesos sin imagenes
    itemNorm.procesos = window.PayloadNormalizer._normalizarProcesos(item.procesos);

    return itemNorm;
};

// Normalizar tallas
window.PayloadNormalizer.normalizarTallas = function(tallasRaw) {
    if (!tallasRaw || typeof tallasRaw !== 'object') {
        return {};
    }

    const result = {};

    for (const genero in tallasRaw) {
        if (!tallasRaw.hasOwnProperty(genero)) continue;

        const tallasCant = tallasRaw[genero];
        if (!tallasCant || typeof tallasCant !== 'object') {
            result[genero] = {};
            continue;
        }

        result[genero] = {};

        for (const talla in tallasCant) {
            if (!tallasCant.hasOwnProperty(talla)) continue;

            const cantidad = tallasCant[talla];
            const num = parseInt(cantidad, 10);

            if (!isNaN(num) && num > 0) {
                result[genero][talla] = num;
            }
        }
    }

    return result;
};

// Normalizar telas
window.PayloadNormalizer._normalizarTelas = function(telasRaw) {
    if (!Array.isArray(telasRaw) || telasRaw.length === 0) {
        return [];
    }

    return telasRaw.map(function(tela) {
        return {
            tela_id: tela.tela_id || '',
            color_id: tela.color_id || '',
            tela_nombre: tela.tela_nombre || tela.tela || '',
            color_nombre: tela.color_nombre || tela.color || '',
            referencia: tela.referencia || ''
        };
    });
};

// Normalizar procesos
window.PayloadNormalizer._normalizarProcesos = function(procesosRaw) {
    if (!procesosRaw || typeof procesosRaw !== 'object') {
        return {};
    }

    const result = {};

    for (const key in procesosRaw) {
        if (!procesosRaw.hasOwnProperty(key)) continue;

        const proceso = procesosRaw[key];
        if (!proceso || typeof proceso !== 'object') continue;

        result[key] = {
            tipo: proceso.tipo || key,
            ubicaciones: Array.isArray(proceso.ubicaciones) ? proceso.ubicaciones : [],
            observaciones: proceso.observaciones || '',
            tallas: window.PayloadNormalizer._normalizarTallasProceso(proceso)
        };
    }

    return result;
};

// Normalizar tallas de proceso
window.PayloadNormalizer._normalizarTallasProceso = function(proceso) {
    let tallasBruta = null;

    if (proceso.tallas && typeof proceso.tallas === 'object') {
        tallasBruta = proceso.tallas;
    } else if (proceso.datos && proceso.datos.tallas && typeof proceso.datos.tallas === 'object') {
        tallasBruta = proceso.datos.tallas;
    }

    if (!tallasBruta) {
        return {};
    }

    return window.PayloadNormalizer.normalizarTallas(tallasBruta);
};

// Limpiar Files
window.PayloadNormalizer.limpiarFiles = function(obj) {
    if (obj === null || obj === undefined) {
        return obj;
    }

    if (obj instanceof File) {
        return null;
    }

    if (Array.isArray(obj)) {
        return obj.map(function(item) {
            return window.PayloadNormalizer.limpiarFiles(item);
        });
    }

    if (typeof obj === 'object') {
        const result = {};
        for (const key in obj) {
            if (obj.hasOwnProperty(key)) {
                result[key] = window.PayloadNormalizer.limpiarFiles(obj[key]);
            }
        }
        return result;
    }

    return obj;
};

// Validar no hay Files
window.PayloadNormalizer.validarNoHayFiles = function(jsonString) {
    if (!jsonString || typeof jsonString !== 'string') {
        return;
    }

    if (jsonString.includes('[object File]') || jsonString.includes('File')) {
        throw new Error('❌ CRITICAL: Files detectados en JSON');
    }
};

console.log('[PayloadNormalizer] ✅ Cargado exitosamente');
console.log('[PayloadNormalizer] Métodos disponibles:', Object.keys(window.PayloadNormalizer));
