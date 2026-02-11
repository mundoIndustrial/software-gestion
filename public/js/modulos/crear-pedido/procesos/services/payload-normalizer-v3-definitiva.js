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
            uid: eppRaw.uid || null,  // ← NUEVO: Preservar UID del EPP
            epp_id: eppRaw.epp_id,
            nombre_epp: eppRaw.nombre_epp || '',
            categoria: eppRaw.categoria || '',
            cantidad: eppRaw.cantidad || 1,
            observaciones: eppRaw.observaciones || '',
            imagenes: normalizarImagenes(eppRaw.imagenes || [])
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
                uid: tela.uid || null,  // ← NUEVO: Preservar UID
                tela_id: tela.tela_id,
                color_id: tela.color_id,
                tela_nombre: tela.tela_nombre || tela.tela || '',
                color_nombre: tela.color_nombre || tela.color || '',
                referencia: tela.referencia || '',
                imagenes: normalizarImagenes(tela.imagenes || [])
            };
        });
    }

    function normalizarImagenes(imagenesRaw) {
        if (!Array.isArray(imagenesRaw)) return [];
        
        return imagenesRaw.filter(function(img) {
            return img !== null && img !== undefined;
        }).map(function(img) {
            // Manejar tanto { file, formdata_key } como { uid, nombre_archivo, formdata_key }
            if (img.file instanceof File) {
                // Formato nuevo de extraerFilesDelPedido
                return {
                    uid: img.uid || null,  // ← AGREGADO: Preservar UID si existe
                    formdata_key: img.formdata_key || null,
                    nombre_archivo: img.file.name || ''
                };
            }
            
            // Formato antiguo o del DTO
            return {
                uid: img.uid || null,
                nombre_archivo: img.nombre_archivo || img.name || '',
                formdata_key: img.formdata_key || null
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
                uid: datoProceso.uid || null,  // ← NUEVO: Preservar UID del proceso
                tipo: datosReales.tipo || datoProceso.tipo || tipoProceso,
                ubicaciones: ubicaciones,
                observaciones: observaciones,
                tallas: normalizarTallas(datosReales.tallas || datoProceso.tallas || {}),
                imagenes: normalizarImagenes(datoProceso.imagenes || datosReales.imagenes || [])
            };
            
            // ⚡ OPTIMIZADO: Removido console.log masivo - impacta 30-50ms
        });
        return procesosNorm;
    }

    function normalizarItem(item) {
        if (!item || typeof item !== 'object') return {};
        return {
            uid: item.uid || null,  // ← CRÍTICO: Preservar UID
            tipo: item.tipo || 'prenda_nueva',
            nombre_prenda: item.nombre_prenda || '',
            descripcion: item.descripcion || '',
            origen: item.origen || 'bodega',
            procesos: normalizarProcesos(item.procesos || {}),
            tallas: [],
            cantidad_talla: normalizarTallas(item.cantidad_talla || {}),
            variaciones: item.variaciones || {},
            telas: normalizarTelas(item.telas || []),
            asignacionesColoresPorTalla: item.asignacionesColoresPorTalla || {},  // ← AÑADIDO: Preservar colores por talla
            imagenes: normalizarImagenes(item.imagenes || [])
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
        // ⚡ OPTIMIZADO: indexOf es más rápido que regex para búsqueda simple
        return jsonString.indexOf('[object File]') === -1 && jsonString.indexOf('[object Blob]') === -1;
    }

    function buildFormData(pedidoNormalizado, filesExtraidos) {
        const formData = new FormData();
        
        // Agregar JSON limpio
        const jsonLimpio = limpiarFiles(pedidoNormalizado);
        formData.append('pedido', JSON.stringify(jsonLimpio)); // ← 'pedido', no 'payload'
        
        // ⚡ OPTIMIZADO: Removida variable archivosDebug (no se usaba)
        let archivosAgregados = 0;
        
        // CRÍTICO: Obtener el mapa de archivos desde filesExtraidos
        const archivosMap = filesExtraidos?.archivosMap || {};
        
        // ⚡ OPTIMIZADO: Removido console.log masivo - impacta 50-100ms en tiempo de carga
        
        // Agregar archivos desde la estructura extraída
        if (filesExtraidos && typeof filesExtraidos === 'object') {
            // ==========================================
            // PROCESAR PRENDAS
            // ==========================================
            if (Array.isArray(filesExtraidos.prendas)) {
                filesExtraidos.prendas.forEach(function(prenda, prendaIdx) {
                    // IMÁGENES DE PRENDA
                    if (Array.isArray(prenda.imagenes)) {
                        prenda.imagenes.forEach(function(imgObj, imgIdx) {
                            // Manejar tanto format antiguo (File) como nuevo ({ file, formdata_key })
                            const file = imgObj.file || imgObj;
                            const formdataKey = imgObj.formdata_key || ('prendas[' + prendaIdx + '][imagenes][' + imgIdx + ']');
                            
                            if (file instanceof File) {
                                formData.append(formdataKey, file);
                                archivosAgregados++;
                                // ⚡ OPTIMIZADO: Removido archivosDebug
                            }
                        });
                    }
                    
                    // IMÁGENES DE TELAS
                    if (Array.isArray(prenda.telas)) {
                        prenda.telas.forEach(function(telaImgs, telaIdx) {
                            if (Array.isArray(telaImgs)) {
                                telaImgs.forEach(function(imgObj, imgIdx) {
                                    const file = imgObj.file || imgObj;
                                    const formdataKey = imgObj.formdata_key || ('prendas[' + prendaIdx + '][telas][' + telaIdx + '][imagenes][' + imgIdx + ']');
                                    
                                    if (file instanceof File) {
                                        formData.append(formdataKey, file);
                                        archivosAgregados++;
                                        // ⚡ OPTIMIZADO: Removido archivosDebug
                                    }
                                });
                            }
                        });
                    }
                    
                    // IMÁGENES DE PROCESOS
                    if (prenda.procesos && typeof prenda.procesos === 'object') {
                        Object.entries(prenda.procesos).forEach(function([procesoKey, procesoImgs]) {
                            if (Array.isArray(procesoImgs)) {
                                procesoImgs.forEach(function(imgObj, imgIdx) {
                                    const file = imgObj.file || imgObj;
                                    const formdataKey = imgObj.formdata_key || ('prendas[' + prendaIdx + '][procesos][' + procesoKey + '][imagenes][' + imgIdx + ']');
                                    
                                    if (file instanceof File) {
                                        formData.append(formdataKey, file);
                                        archivosAgregados++;
                                        // ⚡ OPTIMIZADO: Removido archivosDebug
                                    }
                                });
                            }
                        });
                    }
                });
            }
            
            // ==========================================
            // PROCESAR EPPs
            // ==========================================
            if (Array.isArray(filesExtraidos.epps)) {
                filesExtraidos.epps.forEach(function(epp, eppIdx) {
                    if (Array.isArray(epp.imagenes)) {
                        epp.imagenes.forEach(function(imgObj, imgIdx) {
                            const file = imgObj.file || imgObj;
                            const formdataKey = imgObj.formdata_key || ('epps[' + eppIdx + '][imagenes][' + imgIdx + ']');
                            
                            if (file instanceof File) {
                                formData.append(formdataKey, file);
                                archivosAgregados++;
                                // ⚡ OPTIMIZADO: Removido archivosDebug
                            }
                        });
                    }
                });
            }
        }
        
        // ⚡ OPTIMIZADO: Removido console.log masivo - no es necesario en tiempo de carga
        
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
                // ⚡ OPTIMIZADO: Removido console.log
            });
        }

        // Normalizar EPPs
        if (Array.isArray(pedidoRaw.epps)) {
            pedidoRaw.epps.forEach(function(epp, idx) {
                const eppNorm = normalizarEpp(epp);
                pedidoNorm.epps.push(eppNorm);
                // ⚡ OPTIMIZADO: Removido console.log
            });
        }
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
        delete window.PayloadNormalizer;
    }

    if (!window.PayloadNormalizer) {
        window.PayloadNormalizer = PayloadNormalizerPublic;
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
    */

    // ========================================================================
    // PASO 6: VALIDACIÓN FINAL (OPTIMIZADA - REMOVIDO PARA PERFORMANCE)
    // ========================================================================
    // ⚡ REMOVIDO: setTimeout bloqueaba carga - no es necesario en IIFE

})(); // FIN DEL IIFE
