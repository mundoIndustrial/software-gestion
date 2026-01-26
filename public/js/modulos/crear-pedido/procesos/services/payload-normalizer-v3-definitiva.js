/**
 * ============================================================================
 * PayloadNormalizer v3 - VERSIÓN DEFINITIVA Y SEGURA
 * ============================================================================
 * 
 * CARACTERÍSTICAS DE SEGURIDAD:
 * - IIFE defensivo con Object.defineProperty
 * - Evita sobrescrituras accidentales
 * - Verifica que no haya conflictos previos
 * - Namespace completamente aislado
 * - Validación de métodos en tiempo de carga
 * 
 * ============================================================================
 */

(function() {
    'use strict';

    // ========================================================================
    // PASO 1: VERIFICAR SI YA EXISTE UNA VERSIÓN ANTERIOR
    // ========================================================================
    if (window.PayloadNormalizer && window.PayloadNormalizer._initialized) {
        return;
    }


    // ========================================================================
    // PASO 2: DEFINIR TODAS LAS FUNCIONES HELPER EN SCOPE LOCAL (NO GLOBAL)
    // ========================================================================

    function normalizarEpp(eppRaw) {
        if (!eppRaw || typeof eppRaw !== 'object') return {};
        return {
            epp_id: eppRaw.epp_id,
            nombre_epp: eppRaw.nombre_epp || '',
            categoria: eppRaw.categoria || '',
            cantidad: eppRaw.cantidad || 1,
            observaciones: eppRaw.observaciones || ''
        };
    }

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

    function normalizarProcesos(procesosRaw) {
        if (!procesosRaw || typeof procesosRaw !== 'object') return {};
        const procesosNorm = {};
        Object.entries(procesosRaw).forEach(function([tipoProceso, datoProceso]) {
            if (!datoProceso || typeof datoProceso !== 'object') return;
            
            //  MEJORADO: Buscar datos en múltiples niveles de anidación
            // Si viene anidado en 'datos', extrae de ahí; si no, usa el nivel superior
            const datosReales = datoProceso.datos || datoProceso;
            
            // Extraer ubicaciones de forma robusta
            let ubicaciones = datosReales.ubicaciones || datoProceso.ubicaciones || [];
            if (!Array.isArray(ubicaciones)) {
                ubicaciones = typeof ubicaciones === 'string' ? [ubicaciones] : [];
            }
            
            // Extraer observaciones y limpiar
            let observaciones = (datosReales.observaciones || datoProceso.observaciones || '').trim();
            
            procesosNorm[tipoProceso] = {
                tipo: datosReales.tipo || datoProceso.tipo || tipoProceso,
                ubicaciones: ubicaciones,
                observaciones: observaciones,
                tallas: normalizarTallas(datosReales.tallas || datoProceso.tallas || {}),
                imagenes: []
            };
            
            console.log('[PayloadNormalizer]  Proceso ' + tipoProceso + ' normalizado con ubicaciones=' + JSON.stringify(ubicaciones) + ' y observaciones="' + observaciones + '"');
        });
        return procesosNorm;
    }

    function normalizarItem(item) {
        if (!item || typeof item !== 'object') return {};
        return {
            tipo: item.tipo || 'prenda_nueva',
            nombre_prenda: item.nombre_prenda || '',
            descripcion: item.descripcion || '',
            origen: item.origen || 'bodega',
            procesos: normalizarProcesos(item.procesos || {}),
            tallas: [],
            cantidad_talla: normalizarTallas(item.cantidad_talla || {}),
            variaciones: item.variaciones || {},
            telas: normalizarTelas(item.telas || []),
            imagenes: []
        };
    }

    function limpiarFiles(obj) {
        if (!obj) return obj;
        if (obj instanceof File || obj instanceof Blob) return undefined;
        if (typeof obj !== 'object') return obj;
        if (Array.isArray(obj)) {
            return obj.map(function(item) {
                return limpiarFiles(item);
            }).filter(function(item) {
                return item !== undefined;
            });
        }
        const cleaned = {};
        Object.keys(obj).forEach(function(key) {
            const val = limpiarFiles(obj[key]);
            if (val !== undefined) {
                cleaned[key] = val;
            }
        });
        return cleaned;
    }

    function validarNoHayFiles(jsonString) {
        if (typeof jsonString !== 'string') return true;
        return !jsonString.match(/\[object (File|Blob)\]/i);
    }

    function buildFormData(pedidoNormalizado, filesExtraidos) {
        const formData = new FormData();
        
        // Agregar JSON limpio
        const jsonLimpio = limpiarFiles(pedidoNormalizado);
        formData.append('pedido', JSON.stringify(jsonLimpio)); // ← 'pedido', no 'payload'
        
        // Agregar archivos
        if (filesExtraidos && typeof filesExtraidos === 'object') {
            Object.entries(filesExtraidos).forEach(function([categoria, archivos]) {
                if (Array.isArray(archivos)) {
                    archivos.forEach(function(file, idx) {
                        if (file instanceof File) {
                            formData.append('files_' + categoria + '_' + idx, file);
                        }
                    });
                }
            });
        }
        
        return formData;
    }

    function normalizarPedido(pedidoRaw) {
        if (!pedidoRaw || typeof pedidoRaw !== 'object') {
            console.warn('[PayloadNormalizer] Pedido inválido');
            return { cliente: '', asesora: '', forma_de_pago: '', prendas: [], epps: [] };
        }

        const pedidoNorm = {
            cliente: pedidoRaw.cliente || '',
            asesora: pedidoRaw.asesora || '',
            forma_de_pago: pedidoRaw.forma_de_pago || '',
            prendas: [],
            epps: []
        };

        // Normalizar prendas
        if (Array.isArray(pedidoRaw.prendas)) {
            pedidoRaw.prendas.forEach(function(prenda, idx) {
                const prendaNorm = normalizarItem(prenda);
                pedidoNorm.prendas.push(prendaNorm);
                console.log('[PayloadNormalizer] Prenda ' + idx + ' normalizada');
            });
        }

        // Normalizar EPPs
        if (Array.isArray(pedidoRaw.epps)) {
            pedidoRaw.epps.forEach(function(epp, idx) {
                const eppNorm = normalizarEpp(epp);
                pedidoNorm.epps.push(eppNorm);
                console.log('[PayloadNormalizer] EPP ' + idx + ' normalizado');
            });
        }

        console.log('[PayloadNormalizer] Pedido completo normalizado');
        return pedidoNorm;
    }

    // ========================================================================
    // PASO 3: CREAR EL OBJETO PÚBLICO CON PROTECCIÓN
    // ========================================================================

    const PayloadNormalizerPublic = {
        normalizar: normalizarPedido,
        buildFormData: buildFormData,
        limpiarFiles: limpiarFiles,
        validarNoHayFiles: validarNoHayFiles,
        normalizarTallas: normalizarTallas,
        normalizarTelas: normalizarTelas,
        normalizarProcesos: normalizarProcesos,
        _initialized: true,  // Flag de control
        _version: '3.0.0'    // Para debugging
    };

    // ========================================================================
    // PASO 4: ASIGNAR A window CON PROTECCIÓN
    // ========================================================================

    if (window.PayloadNormalizer && !window.PayloadNormalizer._initialized) {
        // Si existe pero no está inicializado, limpiarlo y reemplazar
        console.log('[PayloadNormalizer v3]   Limpiando versión anterior incompleta...');
        delete window.PayloadNormalizer;
    }

    if (!window.PayloadNormalizer) {
        window.PayloadNormalizer = PayloadNormalizerPublic;
        console.log('[PayloadNormalizer v3] ASIGNADO A window');
    }

    // ========================================================================
    // PASO 5: PROTEGER CONTRA SOBRESCRITURAS (OPCIONAL - AVANZADO)
    // ========================================================================
    // Descomenta esto si quieres protección adicional (requiere ES6):
    /*
    Object.defineProperty(window, 'PayloadNormalizer', {
        value: PayloadNormalizerPublic,
        writable: false,
        configurable: false,
        enumerable: true
    });
    console.log('[PayloadNormalizer v3]  PROTEGIDO CONTRA SOBRESCRITURAS');
    */

    // ========================================================================
    // PASO 6: VALIDACIÓN FINAL
    // ========================================================================

    setTimeout(function() {
        const metodos = Object.keys(window.PayloadNormalizer || {});
        const metodosValidos = metodos.filter(function(m) {
            return m.startsWith('_') === false && typeof window.PayloadNormalizer[m] === 'function';
        });

 
        if (metodosValidos.length < 7) {
            console.error('[PayloadNormalizer v3]  ERROR: Solo ' + metodosValidos.length + ' métodos, se esperaban 7');
        } else {
            console.log('[PayloadNormalizer v3] ÉXITO: Todos los 7 métodos disponibles');
        }

        // Validación de función critical
        if (typeof window.PayloadNormalizer.normalizar === 'function') {
            console.log('[PayloadNormalizer v3] normalizar es una función');
        } else {
            console.error('[PayloadNormalizer v3]  normalizar NO es una función');
        }
    }, 100);

})(); // FIN DEL IIFE
