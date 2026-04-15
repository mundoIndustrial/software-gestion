/**
 * ReceiptBuilder.js
 * Construye y gestiona la lista de recibos (base + procesos adicionales)
 */

export class ReceiptBuilder {
    /**
     * Construye la lista completa de recibos para una prenda
     * Orden: RECIBO BASE SIEMPRE PRIMERO, luego procesos adicionales
     * 
     * @param {Object} prenda - Objeto de prenda
     * @returns {Array} Array con todos los recibos (base + adicionales)
     */
    static construirListaRecibos(prenda) {
        const recibos = [];
        
        // CONDICIÓN ESPECIAL PARA VISUALIZADOR-LOGO: No mostrar recibo base
        const esVistaVisualizadorLogo = window.location.pathname.includes('/visualizador-logo/pedidos-logo');
        
        // CONDICIÓN ESPECIAL: No mostrar recibo de COSTURA-BODEGA solo en registros y recibos-costura
        // PERMITIR en supervisor-pedidos para que pueda ver todos los procesos
        const esRegistros = window.location.pathname.includes('/registros');
        const esRecibosCostura = window.location.pathname.includes('/recibos-costura');
        const excluirCosturaBodega = (esRegistros || esRecibosCostura) && prenda.de_bodega == 1;
        
        if (excluirCosturaBodega) {
            console.log(' [ReceiptBuilder] COSTURA-BODEGA EXCLUIDO para prenda:', prenda.nombre);
        }
        
        if (!esVistaVisualizadorLogo && !excluirCosturaBodega) {
            // PASO 1: AGREGAR RECIBO BASE
            const recibosMap = (prenda && prenda.recibos && typeof prenda.recibos === 'object') ? prenda.recibos : {};
            const tieneCostura = !!recibosMap.COSTURA;
            const tieneCosturaBodega = !!recibosMap['COSTURA-BODEGA'];

            // Regla canónica: SIEMPRE usar recibo base COSTURA en UI.
            // Si en BD solo existe COSTURA-BODEGA, se usa como fuente de datos legacy.
            const tipoBase = "costura";
            const nombreBase = "Costura";
            const datosReciboBase = recibosMap.COSTURA || recibosMap['COSTURA-BODEGA'] || null;
                
            // Aplanar tallas: convertir {dama: {L: 30, S: 20}} a {dama-L: 30, dama-S: 20}
            let tallasObj = {};
            if (prenda.tallas && typeof prenda.tallas === 'object') {
                for (const [categoria, tallasByCategoria] of Object.entries(prenda.tallas)) {
                    if (typeof tallasByCategoria === 'object' && !Array.isArray(tallasByCategoria)) {
                        // Es un objeto anidado: {L: 30, S: 20}
                        for (const [talla, cantidad] of Object.entries(tallasByCategoria)) {
                            const claveTalla = categoria === 'dama' || categoria === 'caballero' ? `${categoria}-${talla}` : talla;
                            tallasObj[claveTalla] = cantidad;
                        }
                    } else if (typeof tallasByCategoria === 'number') {
                        // Es directo: {L: 30, S: 20}
                        tallasObj[categoria] = tallasByCategoria;
                    }
                }
            }
            
            // Obtener procesos para usarlos tanto en el recibo base como en los recibos de proceso
            const procesos = prenda.procesos || [];

            // Extraer imágenes de logo de la prenda (solo procesos de logo)
            const tiposLogo = new Set(['bordado', 'estampado', 'dtf', 'sublimado']);
            const procesoEsLogo = (proc) => {
                const tipoProcesoRaw = String(proc?.tipo_proceso || proc?.nombre_proceso || proc?.nombre || '').toLowerCase();
                if (tiposLogo.has(tipoProcesoRaw)) return true;
                // ids: 2 (Bordado), 3 (Estampado), 4 (DTF), 5 (Sublimado)
                const id = proc?.tipo_proceso_id;
                return id === 2 || id === 3 || id === 4 || id === 5;
            };

            const imagenesLogoPrenda = [];
            procesos.forEach((proc) => {
                if (!procesoEsLogo(proc)) return;
                if (proc.imagenes && Array.isArray(proc.imagenes)) {
                    imagenesLogoPrenda.push(...proc.imagenes);
                }
            });
            
            // Preparar imágenes para el recibo base
            let imagenesBase = [];
            
            // Agregar imágenes de la prenda
            if (prenda.imagenes && Array.isArray(prenda.imagenes)) {
                imagenesBase = [...imagenesBase, ...prenda.imagenes];
            }
            
            // Agregar imágenes de tela
            if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela)) {
                imagenesBase = [...imagenesBase, ...prenda.imagenes_tela];
            }

            // Agregar imágenes de logo (si existen) - SOLO de esta prenda
            if (imagenesLogoPrenda.length > 0) {
                imagenesBase = [...imagenesBase, ...imagenesLogoPrenda];
            }

            // IMPORTANTE:
            // El recibo base debe mostrar solo imágenes relacionadas a la prenda del recibo
            // (prenda + tela). NO debe arrastrar imágenes de otros procesos.
            
            const reciboBase = {
                tipo: tipoBase,
                tipo_proceso: nombreBase,
                nombre_proceso: nombreBase,
                estado: "Pendiente",
                es_base: true,
                ubicaciones: [],
                observaciones: '',
                imagenes: imagenesBase,
                tallas: tallasObj
            };
            
            // Agregar datos del recibo de costura si existen
            if (datosReciboBase && typeof datosReciboBase === 'object') {
                const datosRecibo = datosReciboBase;
                reciboBase.activo = datosRecibo.activo;
                reciboBase.created_at = datosRecibo.created_at;
                reciboBase.tipo_recibo = 'COSTURA';
                reciboBase.consecutivo_actual = datosRecibo.consecutivo_actual;
                
                console.log('[ReceiptBuilder] Datos de recibo agregados al recibo base:', {
                    tipoBase,
                    activo: datosRecibo.activo,
                    created_at: datosRecibo.created_at,
                    consecutivo_actual: datosRecibo.consecutivo_actual
                });
            }
            
            recibos.push(reciboBase);
        }
        // PASO 2: AGREGAR PROCESOS ADICIONALES
        const procesos = prenda.procesos || [];
        procesos.forEach((proc) => {
            const tipoProceso = String(proc.tipo_proceso || proc.nombre_proceso || '');
            
            // Filtrar: excluir REFLECTIVO si de_bodega es false
            if (!prenda.de_bodega && tipoProceso.toLowerCase() === 'reflectivo') {
                return; // Skip este proceso
            }
            
            // CONDICIÓN ESPECIAL PARA VISUALIZADOR-LOGO: Solo mostrar procesos específicos
            if (esVistaVisualizadorLogo) {
                // Solo mostrar procesos con tipo_proceso_id: 2 (Bordado), 3 (Estampado), 4 (DTF), 5 (Sublimado)
                const procesosPermitidos = [2, 3, 4, 5];
                if (!proc.tipo_proceso_id || !procesosPermitidos.includes(proc.tipo_proceso_id)) {
                    return; // Skip este proceso
                }
            }
            
            if (tipoProceso) {
                // Preparar imágenes para este proceso (prenda + imágenes del proceso)
                let imagenesProceso = [];
                
                // Agregar imágenes de la prenda
                if (prenda.imagenes && Array.isArray(prenda.imagenes)) {
                    imagenesProceso = [...imagenesProceso, ...prenda.imagenes];
                }
                
                // Agregar imágenes específicas del proceso
                if (proc.imagenes && Array.isArray(proc.imagenes)) {
                    imagenesProceso = [...imagenesProceso, ...proc.imagenes];
                }

                // Deduplicar (mantener orden)
                const seen = new Set();
                imagenesProceso = imagenesProceso.filter((img) => {
                    const key = typeof img === 'string' ? img : (img?.url || img?.ruta_webp || img?.ruta || img?.ruta_original || JSON.stringify(img));
                    if (seen.has(key)) return false;
                    seen.add(key);
                    return true;
                });
                
                // IMPORTANTE: No mutar el objeto `proc` original.
                // Mutarlo contamina `prenda.procesos` (y por ende la galería estilo insumos),
                // provocando mezcla de imágenes entre procesos (ej: Reflectivo mostrando Bordado).
                const procRecibo = { ...proc, imagenes: imagenesProceso };
                
                // Inicializar campos del recibo (importante para procesos pendientes)
                procRecibo.activo = null;
                procRecibo.created_at = null;
                procRecibo.tipo_recibo = null;
                procRecibo.consecutivo_actual = null;
                
                // Agregar datos del recibo (activo y created_at) si existen
                if (prenda.recibos && prenda.recibos[tipoProceso.toUpperCase()]) {
                    const datosRecibo = prenda.recibos[tipoProceso.toUpperCase()];
                    procRecibo.activo = datosRecibo.activo;
                    procRecibo.created_at = datosRecibo.created_at;
                    procRecibo.tipo_recibo = datosRecibo.tipo_recibo;
                    procRecibo.consecutivo_actual = datosRecibo.consecutivo_actual;
                    
                    console.log('[ReceiptBuilder] Datos de recibo agregados al proceso:', {
                        tipoProceso,
                        tipoReciboKey: tipoProceso.toUpperCase(),
                        activo: datosRecibo.activo,
                        created_at: datosRecibo.created_at,
                        consecutivo_actual: datosRecibo.consecutivo_actual,
                        datosReciboCompleto: datosRecibo
                    });
                } else {
                    // Proceso pendiente sin registro en consecutivos_recibos_pedidos
                    procRecibo.activo = 0; // Marcar como no activo explícitamente
                    procRecibo.tipo_recibo = tipoProceso.toUpperCase(); // Asignar tipo para identificación
                    
                    console.warn('[ReceiptBuilder] Proceso pendiente sin registro en consecutivos_recibos_pedidos:', {
                        tipoProceso,
                        tipoReciboKey: tipoProceso.toUpperCase(),
                        recibosDisponibles: prenda.recibos ? Object.keys(prenda.recibos) : 'null',
                        prendaRecibos: prenda.recibos,
                        marcadoComoNoActivo: true
                    });
                }
                
                recibos.push(procRecibo);
            }
        });
        
        return recibos;
    }

    /**
     * Encuentra un recibo por su tipo en la lista de recibos
     * 
     * @param {Array} recibos - Array de recibos
     * @param {string} tipoRecibo - Tipo de recibo a buscar
     * @returns {number} Índice del recibo o -1 si no existe
     */
    static encontrarReceibo(recibos, tipoRecibo) {
        return recibos.findIndex(r => 
            String(r.tipo).toLowerCase() === String(tipoRecibo).toLowerCase() || 
            String(r.tipo_proceso || r.nombre_proceso || '').toLowerCase() === String(tipoRecibo).toLowerCase()
        );
    }
}
