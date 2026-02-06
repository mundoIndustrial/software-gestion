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
        
        // CONDICIÓN ESPECIAL: No mostrar recibo de COSTURA-BODEGA en supervisor-pedidos y registros
        const esSupervisorPedidos = window.location.pathname.includes('/supervisor-pedidos');
        const esRegistros = window.location.pathname.includes('/registros');
        const excluirCosturaBodega = (esSupervisorPedidos || esRegistros) && prenda.de_bodega == 1;
        
        if (excluirCosturaBodega) {
            console.log('📋 [ReceiptBuilder] COSTURA-BODEGA EXCLUIDO para prenda:', prenda.nombre);
        }
        
        if (!esVistaVisualizadorLogo && !excluirCosturaBodega) {
            // PASO 1: AGREGAR RECIBO BASE
            const tipoBase = prenda.de_bodega == 1 ? "costura-bodega" : "costura";
            const nombreBase = prenda.de_bodega == 1 ? "Bodega" : "Costura";
                
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
            
            // Agregar imágenes de todos los procesos
            procesos.forEach((proc) => {
                if (proc.imagenes && Array.isArray(proc.imagenes)) {
                    imagenesBase = [...imagenesBase, ...proc.imagenes];
                }
            });
            
            recibos.push({
                tipo: tipoBase,
                tipo_proceso: nombreBase,
                nombre_proceso: nombreBase,
                estado: "Pendiente",
                es_base: true,
                ubicaciones: [],
                observaciones: '',
                imagenes: imagenesBase,
                tallas: tallasObj
            });
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
                // Preparar imágenes para este proceso (prenda + tela + imágenes del proceso)
                let imagenesProceso = [];
                
                // Agregar imágenes de la prenda
                if (prenda.imagenes && Array.isArray(prenda.imagenes)) {
                    imagenesProceso = [...imagenesProceso, ...prenda.imagenes];
                }
                
                // Agregar imágenes de tela
                if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela)) {
                    imagenesProceso = [...imagenesProceso, ...prenda.imagenes_tela];
                }
                
                // Agregar imágenes específicas del proceso
                if (proc.imagenes && Array.isArray(proc.imagenes)) {
                    imagenesProceso = [...imagenesProceso, ...proc.imagenes];
                }
                
                // Asegurarse de que el proceso tenga el array de imágenes completo
                proc.imagenes = imagenesProceso;
                recibos.push(proc);
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
            String(r.tipo) === String(tipoRecibo) || 
            String(r.tipo_proceso || r.nombre_proceso || '') === String(tipoRecibo)
        );
    }
}

