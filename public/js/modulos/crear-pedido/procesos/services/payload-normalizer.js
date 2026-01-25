/**
 * PayloadNormalizer.js
 * 
 * Normalización centralizada ROBUSTA de payloads para ambos endpoints:
 * - validarPedido()
 * - crearPedido()
 * 
 * Garantías:
 * ✅ NO contiene File objects en JSON
 * ✅ Elimina imagenes:[[]] vacías
 * ✅ Convierte tallas strings → números
 * ✅ Preserva manga_id, broche_id, procesos
 * ✅ Estructura limpia para JSON.stringify
 */

// Usar IIFE para evitar conflictos de scope
(function() {
    'use strict';

    // Función principal: Normalizar pedido
    function normalizarPedido(pedidoRaw) {
        if (!pedidoRaw || typeof pedidoRaw !== 'object') {
            console.warn('[PayloadNormalizer] ⚠️ Pedido inválido:', pedidoRaw);
            return pedidoRaw;
        }

        console.log('[PayloadNormalizer] 🔄 Normalizando pedido completo...');

        const pedidoNorm = {
            cliente: pedidoRaw.cliente,
            asesora: pedidoRaw.asesora,
            forma_de_pago: pedidoRaw.forma_de_pago,
            descripcion: pedidoRaw.descripcion || '',
            items: []
        };

        // Normalizar cada item (prenda)
        if (Array.isArray(pedidoRaw.items)) {
            pedidoRaw.items.forEach(function(item, idx) {
                const itemNorm = normalizarItem(item);
                pedidoNorm.items.push(itemNorm);
                console.log('[PayloadNormalizer] ✅ Item ' + idx + ' normalizado:', itemNorm);
            });
        }

        console.log('[PayloadNormalizer] ✅ Pedido completo normalizado');
        return pedidoNorm;
    }

    // Normalizar una prenda (item)
    function normalizarItem(item) {
        const itemNorm = {
            tipo: item.tipo,
            nombre_prenda: item.nombre_prenda,
            descripcion: item.descripcion,
            origen: item.origen
        };

        // Cantidad de tallas (convertir strings a números)
        itemNorm.cantidad_talla = normalizarTallas(item.cantidad_talla);

        // Variaciones (manga_id, broche_id, etc.) - PRESERVAR SIEMPRE
        itemNorm.variaciones = (item.variaciones && typeof item.variaciones === 'object') 
            ? item.variaciones 
            : {};
        
        console.log('[PayloadNormalizer] 📝 Variaciones preservadas:', itemNorm.variaciones);

        // Telas SIN imagenes en JSON
        itemNorm.telas = normalizarTelas(item.telas);

        // Procesos SIN imagenes en JSON
        itemNorm.procesos = normalizarProcesos(item.procesos);

        return itemNorm;
    }

    // Normalizar tallas: "20" → 20
    function normalizarTallas(tallasRaw) {
        if (!tallasRaw || typeof tallasRaw !== 'object') {
            return {};
        }

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

    // Normalizar telas: remover imagenes
    function normalizarTelas(telasRaw) {
        if (!Array.isArray(telasRaw) || telasRaw.length === 0) {
            return [];
        }

        return telasRaw.map(function(tela) {
            // Solo campos que vamos a enviar en JSON
            // Los Files de imagenes se enviarán separados en FormData
            return {
                tela_id: tela.tela_id,
                color_id: tela.color_id,
                tela_nombre: tela.tela_nombre || tela.tela || '',
                color_nombre: tela.color_nombre || tela.color || '',
                referencia: tela.referencia || ''
                // ❌ NO incluir tela.imagenes aquí
            };
        });
    }

    // Normalizar procesos: remover imagenes, normalizar tallas
    function normalizarProcesos(procesosRaw) {
        if (!procesosRaw || typeof procesosRaw !== 'object') {
            return {};
        }

        const procesosNorm = {};

        Object.entries(procesosRaw).forEach(function([procesoKey, proceso]) {
            if (!proceso || typeof proceso !== 'object') {
                return;
            }

            // Solo campos que vamos a enviar en JSON
            procesosNorm[procesoKey] = {
                tipo: proceso.tipo,
                ubicaciones: Array.isArray(proceso.ubicaciones) ? proceso.ubicaciones : [],
                observaciones: proceso.observaciones || '',
                // Normalizar tallas del proceso
                tallas: normalizarTallasProceso(proceso)
                // ❌ NO incluir proceso.imagenes aquí
            };

            console.log('[PayloadNormalizer] 📍 Proceso ' + procesoKey + ' normalizado');
        });

        return procesosNorm;
    }

    // Normalizar tallas dentro de procesos
    function normalizarTallasProceso(proceso) {
        // Buscar tallas en proceso.tallas o proceso.datos.tallas
        let tallasBruta = null;

        if (proceso.tallas && typeof proceso.tallas === 'object') {
            tallasBruta = proceso.tallas;
        } else if (proceso.datos && proceso.datos.tallas && typeof proceso.datos.tallas === 'object') {
            tallasBruta = proceso.datos.tallas;
        }

        if (!tallasBruta) {
            return {};
        }

        return normalizarTallas(tallasBruta);
    }

    // Limpiar Files recursivamente
    function limpiarFiles(obj) {
        if (obj === null || obj === undefined) {
            return obj;
        }

        // Si es File, retornar null
        if (obj instanceof File) {
            return null;
        }

        // Si es Array, mapear
        if (Array.isArray(obj)) {
            return obj.map(function(item) {
                return limpiarFiles(item);
            });
        }

        // Si es Object, limpiar propiedades
        if (typeof obj === 'object') {
            const limpio = {};
            for (const key in obj) {
                if (obj.hasOwnProperty(key)) {
                    limpio[key] = limpiarFiles(obj[key]);
                }
            }
            return limpio;
        }

        // Si es primitivo, retornar as-is
        return obj;
    }

    // Validar que NO hay Files en JSON
    function validarNoHayFiles(jsonString) {
        if (!jsonString || typeof jsonString !== 'string') {
            return;
        }

        if (jsonString.includes('[object File]') || jsonString.includes('File')) {
            throw new Error('❌ CRITICAL: Files detectados en JSON. FormData debe tener solo JSON + Files separados.');
        }
    }

    // Exportar como objeto global
    window.PayloadNormalizer = {
        normalizar: normalizarPedido,
        limpiarFiles: limpiarFiles,
        validarNoHayFiles: validarNoHayFiles,
        normalizarTallas: normalizarTallas,
        normalizarTelas: normalizarTelas,
        normalizarProcesos: normalizarProcesos
    };

    console.log('[PayloadNormalizer] ✅ Cargado exitosamente en window');

})();


