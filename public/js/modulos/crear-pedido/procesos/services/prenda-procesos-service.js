/**
 * PrendaProcesosService - Gestión de procesos de prendas
 * 
 * Responsabilidades:
 * - Normalizar procesos (object → array)
 * - Procesar cada proceso y extraer datos
 * - Gestionar ubicaciones de procesos
 * - Gestionar imágenes de procesos
 * - Sincronizar con checkboxes
 */ class PrendaProcesosService {
    constructor(opciones = {}) {
        this.domAdapter = opciones.domAdapter;
        this.eventBus = opciones.eventBus;
    }

    /**
     * Cargar procesos de la prenda
     * Normaliza, procesa y emite eventos
     */
    cargarProcesos(prenda) {
        console.log('[PrendaProcesosService] Cargando procesos');

        if (!prenda.procesos) {
            console.log('[PrendaProcesosService] Sin procesos');
            return {};
        }

        // Normalizar procesos (array vs object)
        const procesosNormalizados = this.normalizarProcesos(prenda.procesos);
        
        if (procesosNormalizados.length === 0) {
            console.log('[PrendaProcesosService] Sin procesos después de normalizar');
            return {};
        }

        console.log('[PrendaProcesosService] Procesando:', procesosNormalizados.length);

        const procesosSeleccionados = {};

        procesosNormalizados.forEach((proceso, idx) => {
            if (!proceso) return;

            const datosProc = this.procesarProcesoIndividual(proceso, idx);
            if (datosProc) {
                procesosSeleccionados[datosProc.tipo] = {
                    datos: datosProc
                };

                // Marcar checkbox
                this.marcarProcesoEnDOM(datosProc.tipo);
            }
        });

        // Asignar a window (compatibilidad)
        window.procesosSeleccionados = procesosSeleccionados;

        // Renderizar tarjetas
        this.renderizarTarjetasProcesos();

        this.eventBus?.emit(PrendaEventBus.EVENTOS.PROCESOS_CARGADOS, {
            cantidad: Object.keys(procesosSeleccionados).length,
            procesos: procesosSeleccionados
        });

        console.log('[PrendaProcesosService] ✓ Procesos cargados:', Object.keys(procesosSeleccionados));

        return procesosSeleccionados;
    }

    /**
     * Normalizar procesos: array vs object
     * Si viene como objeto {tipo: {...}}, convertir a array
     */
    normalizarProcesos(procesos) {
        if (!procesos) return [];

        // Ya es array
        if (Array.isArray(procesos)) {
            return procesos;
        }

        // Es object, convertir a array
        if (typeof procesos === 'object') {
            return Object.values(procesos).filter(p => p !== null && p !== undefined);
        }

        return [];
    }

    /**
     * Procesar un proceso individual
     * Extrae y normaliza todos los datos
     * @private
     */
    procesarProcesoIndividual(proceso, index) {
        // Obtener datos reales (puede ser envuelto o directo)
        const datosReales = proceso.datos ? proceso.datos : proceso;
        if (!datosReales) return null;

        try {
            // Determinar tipo/slug
            const tipoBackend = datosReales.tipo || datosReales.tipo_proceso || datosReales.nombre || `Proceso ${index}`;
            const slugDirecto = datosReales.slug;
            const tipo = slugDirecto || tipoBackend.toLowerCase().replace(/\s+/g, '-');

            console.log(`[PrendaProcesosService] Procesando [${index}] tipo="${tipo}"`);

            // Procesar ubicaciones
            const ubicaciones = this.extraerUbicaciones(datosReales.ubicaciones);

            // Procesar tallas
            const tallas = this.procesarTallas(datosReales.tallas);

            // Procesar imágenes
            const imagenes = this.extraerImagenes(datosReales.imagenes);

            return {
                id: datosReales.id,
                tipo: tipo,
                nombre: tipoBackend,
                nombre_proceso: datosReales.nombre_proceso,
                tipo_proceso: datosReales.tipo_proceso,
                tipo_proceso_id: datosReales.tipo_proceso_id,
                ubicaciones: ubicaciones,
                observaciones: (datosReales.observaciones || '').trim(),
                tallas: tallas,
                talla_cantidad: datosReales.talla_cantidad || {},
                variaciones_prenda: datosReales.variaciones_prenda || {},
                imagenes: imagenes
            };

        } catch (error) {
            console.error(`[PrendaProcesosService] Error procesando proceso ${index}:`, error);
            return null;
        }
    }

    /**
     * Extraer ubicaciones de proceso
     * Soporta: string, array, JSON
     * @private
     */
    extraerUbicaciones(ubicaciones) {
        if (!ubicaciones) return [];

        // Si es string, intentar parsear JSON
        if (typeof ubicaciones === 'string') {
            try {
                const parsed = JSON.parse(ubicaciones);
                return Array.isArray(parsed) ? parsed : [parsed];
            } catch {
                // Si no es JSON válido, retornar como array
                return [ubicaciones];
            }
        }

        // Si ya es array
        if (Array.isArray(ubicaciones)) {
            return ubicaciones;
        }

        // Si es objeto, retornar como array
        if (typeof ubicaciones === 'object') {
            return [ubicaciones];
        }

        return [];
    }

    /**
     * Procesar tallas de proceso
     * Valida estructura
     * @private
     */
    procesarTallas(tallas) {
        if (!tallas) {
            return { dama: {}, caballero: {} };
        }

        // Si ya es objeto correcto
        if (typeof tallas === 'object' && !Array.isArray(tallas)) {
            return tallas;
        }

        // Si es array vacío
        if (Array.isArray(tallas) && tallas.length === 0) {
            return { dama: {}, caballero: {} };
        }

        // Fallback
        return { dama: {}, caballero: {} };
    }

    /**
     * Extraer imágenes de proceso
     * Convierte a array de URLs
     * @private
     */
    extraerImagenes(imagenes) {
        if (!imagenes || imagenes.length === 0) return [];

        return imagenes
            .map(img => {
                if (typeof img === 'string') return img;
                if (img.url) return img.url;
                if (img.ruta) return img.ruta;
                if (img.ruta_webp) return img.ruta_webp;
                return null;
            })
            .filter(url => !!url);
    }

    /**
     * Marcar proceso en DOM (checkbox)
     * @private
     */
    marcarProcesoEnDOM(tipoProceso) {
        if (!this.domAdapter) return;
        this.domAdapter.marcarProceso(tipoProceso, true);
    }

    /**
     * Renderizar tarjetas de procesos
     * Usa función global si existe (compatibilidad)
     * @private
     */
    renderizarTarjetasProcesos() {
        if (typeof window.renderizarTarjetasProcesos === 'function') {
            console.log('[PrendaProcesosService] Renderizando tarjetas...');
            window.renderizarTarjetasProcesos();
        } else {
            console.warn('[PrendaProcesosService] window.renderizarTarjetasProcesos no existe');
        }
    }

    /**
     * Validar procesos
     */
    validarProcesos(procesosSeleccionados) {
        const errores = [];

        if (!procesosSeleccionados || Object.keys(procesosSeleccionados).length === 0) {
            errores.push('Debe seleccionar al menos un proceso');
        }

        return {
            valido: errores.length === 0,
            errores
        };
    }

    /**
     * Obtener procesos para guardar
     * Normaliza estructura para API
     */
    obtenerProcesosParaGuardar(procesosSeleccionados) {
        const procesos = [];

        Object.entries(procesosSeleccionados).forEach(([tipo, proceso]) => {
            procesos.push({
                tipo: tipo,
                datos: proceso.datos
            });
        });

        return procesos;
    }

    /**
     * Sincronizar tallas entre prenda y procesos
     */
    sincronizarTallasAProcesos(procesos, tallasRelacionales) {
        if (!procesos || !tallasRelacionales) return;

        Object.values(procesos).forEach(proceso => {
            if (proceso?.datos) {
                proceso.datos.talla_cantidad = proceso.datos.talla_cantidad || {};
                
                // Copiar tallas de la prenda
                Object.entries(tallasRelacionales).forEach(([genero, tallas]) => {
                    if (Object.keys(tallas).length > 0) {
                        proceso.datos.talla_cantidad[genero] = { ...tallas };
                    }
                });
            }
        });

        console.log('[PrendaProcesosService] Tallas sincronizadas a procesos');
    }

    /**
     * Limpiar procesos
     */
    limpiarProcesos() {
        window.procesosSeleccionados = {};
        this.eventBus?.emit(PrendaEventBus.EVENTOS.PROCESOS_CARGADOS, {});
    }

    /**
     * Obtener información de un proceso
     */
    obtenerProcesoInfo(tipo) {
        const proceso = window.procesosSeleccionados?.[tipo];
        return proceso?.datos || null;
    }

    /**
     * Contar procesos seleccionados
     */
    contarProcesos() {
        return Object.keys(window.procesosSeleccionados || {}).length;
    }
}

window.PrendaProcesosService = PrendaProcesosService;
