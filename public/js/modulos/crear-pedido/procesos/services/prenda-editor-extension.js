/**
 * PATCH para PrendaEditor - Integración de Origen Automático
 * 
 * Este archivo muestra cómo extender PrendaEditor para incluir
 * la funcionalidad de origen automático desde cotizaciones
 * 
 * OPCIÓN 1: Agregar este código directamente en prenda-editor.js
 * OPCIÓN 2: Crear un mixin/addon separado
 */

// ============================================================================
// EXTENSIÓN PARA PRENDAEDITOR - Integración de Origen Automático
// ============================================================================

/**
 * Extensión del PrendaEditor existente
 * Agrega capacidad de procesar prendas desde cotizaciones
 */
class PrendaEditorExtension {
    /**
     * Inicializar extensión
     * Llamar una sola vez durante la inicialización de la app
     * 
     * @param {PrendaEditor} prendaEditorInstance - Instancia de PrendaEditor
     * @returns {void}
     */
    static inicializar(prendaEditorInstance) {
        if (!prendaEditorInstance) {
            console.error('[PrendaEditorExtension] PrendaEditor no proporcionado');
            return;
        }

        // Guardar referencia
        window.prendaEditorExtension = this;
        window._prendaEditorInstance = prendaEditorInstance;

        console.info('[PrendaEditorExtension] Inicializado correctamente');
    }

    /**
     * Agregar una prenda desde una cotización
     * Aplica origen automático según el tipo de cotización
     * 
     * @param {Object} prendaData - Datos de la prenda
     * @param {Object} cotizacionSeleccionada - Cotización asociada
     * @param {boolean} abrirModal - Si es true, abre el modal después
     * @returns {Object} - Prenda procesada
     */
    static agregarPrendaDesdeCotizacion(prendaData, cotizacionSeleccionada, abrirModal = true) {
        // Validar entrada
        if (!prendaData || !cotizacionSeleccionada) {
            console.error('[PrendaEditorExtension] Datos inválidos');
            return null;
        }

        // Procesar prenda con origen automático
        const prendaProcesada = CotizacionPrendaHandler.prepararPrendaParaEdicion(
            prendaData,
            cotizacionSeleccionada
        );

        // Guardar referencia a la cotización origen
        prendaProcesada.cotizacion_origen = {
            id: cotizacionSeleccionada.id,
            numero: cotizacionSeleccionada.numero_cotizacion,
            tipo_id: cotizacionSeleccionada.tipo_cotizacion_id
        };

        // Agregar a lista de prendas
        window.prendas = window.prendas || [];
        const indice = window.prendas.length;
        window.prendas.push(prendaProcesada);

        console.log('[PrendaEditorExtension] Prenda agregada:', {
            nombre: prendaProcesada.nombre,
            origen: prendaProcesada.origen,
            cotizacion: cotizacionSeleccionada.numero_cotizacion
        });

        // Abrir modal si se solicita
        if (abrirModal && window._prendaEditorInstance) {
            window._prendaEditorInstance.abrirModal(false, indice);
        }

        return prendaProcesada;
    }

    /**
     * Cargar múltiples prendas desde una cotización
     * Útil cuando se cargan todas las prendas de una cotización
     * 
     * @param {Array<Object>} prendas - Array de prendas
     * @param {Object} cotizacion - Cotización asociada
     * @returns {Array<Object>} - Prendas procesadas
     */
    static cargarPrendasDesdeCotizacion(prendas, cotizacion) {
        if (!Array.isArray(prendas) || !cotizacion) {
            console.error('[PrendaEditorExtension] Parámetros inválidos');
            return [];
        }

        console.info(`[PrendaEditorExtension] Cargando ${prendas.length} prendas desde cotización...`);

        // Procesar cada prenda
        const prendasProcesadas = prendas.map((prenda, index) => {
            const procesada = CotizacionPrendaHandler.prepararPrendaParaEdicion(
                prenda,
                cotizacion
            );

            procesada.cotizacion_origen = {
                id: cotizacion.id,
                numero: cotizacion.numero_cotizacion,
                tipo_id: cotizacion.tipo_cotizacion_id
            };

            console.debug(`  ✓ Prenda ${index + 1}: ${procesada.nombre} (origen: ${procesada.origen})`);
            return procesada;
        });

        // Agregar a la lista
        window.prendas = [...(window.prendas || []), ...prendasProcesadas];

        console.info(`[PrendaEditorExtension] ${prendasProcesadas.length} prendas cargadas exitosamente`);

        return prendasProcesadas;
    }

    /**
     * Verificar si una prenda viene de una cotización
     * 
     * @param {Object} prenda - Prenda a verificar
     * @returns {boolean} - true si tiene origen de cotización
     */
    static vieneDeCotizacion(prenda) {
        return prenda && prenda.cotizacion_origen && prenda.cotizacion_origen.id;
    }

    /**
     * Obtener información de cotización origen
     * 
     * @param {Object} prenda - Prenda a consultar
     * @returns {Object|null} - Información de cotización o null
     */
    static obtenerCotizacionOrigen(prenda) {
        return this.vieneDeCotizacion(prenda) ? prenda.cotizacion_origen : null;
    }

    /**
     * Re-procesar una prenda si cambió el tipo de cotización
     * Útil si el usuario modifica la cotización origen
     * 
     * @param {number} prendaIndex - Índice de la prenda
     * @param {Object} nuevaCotizacion - Nueva cotización
     * @returns {boolean} - true si se re-procesó
     */
    static reprocesarPrenda(prendaIndex, nuevaCotizacion) {
        if (!window.prendas || !window.prendas[prendaIndex]) {
            console.error('[PrendaEditorExtension] Prenda no encontrada');
            return false;
        }

        const prenda = window.prendas[prendaIndex];
        const origenAnterior = prenda.origen;

        // Re-procesar
        CotizacionPrendaHandler.prepararPrendaParaEdicion(prenda, nuevaCotizacion);

        // Actualizar referencia de cotización
        prenda.cotizacion_origen = {
            id: nuevaCotizacion.id,
            numero: nuevaCotizacion.numero_cotizacion,
            tipo_id: nuevaCotizacion.tipo_cotizacion_id
        };

        const cambio = origenAnterior !== prenda.origen;
        if (cambio) {
            console.info(
                `[PrendaEditorExtension] Origen actualizado: ${origenAnterior} → ${prenda.origen}`
            );
        }

        return cambio;
    }

    /**
     * Obtener estadísticas de prendas por origen
     * 
     * @returns {Object} - Conteo de prendas por origen
     */
    static obtenerEstadisticas() {
        const stats = {
            total: 0,
            bodega: 0,
            confeccion: 0,
            desdeCotizacion: 0,
            manuales: 0,
            sinOrigen: 0,
            porTipoCotizacion: {}
        };

        if (!window.prendas || !Array.isArray(window.prendas)) {
            return stats;
        }

        window.prendas.forEach(prenda => {
            stats.total++;

            // Por origen
            if (prenda.origen === 'bodega') stats.bodega++;
            else if (prenda.origen === 'confeccion') stats.confeccion++;
            else stats.sinOrigen++;

            // Desde cotización vs manual
            if (this.vieneDeCotizacion(prenda)) {
                stats.desdeCotizacion++;
                
                // Agrupar por tipo
                const tipoId = prenda.cotizacion_origen.tipo_id;
                stats.porTipoCotizacion[tipoId] = 
                    (stats.porTipoCotizacion[tipoId] || 0) + 1;
            } else {
                stats.manuales++;
            }
        });

        return stats;
    }

    /**
     * Mostrar reporte de estadísticas
     */
    static mostrarReporte() {
        const stats = this.obtenerEstadisticas();

        console.group('📊 Estadísticas de Prendas');
        console.table({
            'Total': stats.total,
            'Desde Cotización': stats.desdeCotizacion,
            'Manuales': stats.manuales,
            'Origen Bodega': stats.bodega,
            'Origen Confección': stats.confeccion,
            'Sin Origen': stats.sinOrigen
        });
        
        if (Object.keys(stats.porTipoCotizacion).length > 0) {
            console.log('Por tipo de cotización:', stats.porTipoCotizacion);
        }
        
        console.groupEnd();

        return stats;
    }

    /**
     * Limpiar referencias de cotización origen
     * Útil cuando se guarda el pedido
     * 
     * @param {boolean} incluirDesdeCotizacion - Si es true, limpia solo prendas desde cotización
     * @returns {number} - Cantidad de prendas limpiadas
     */
    static limpiarReferencias(incluirDesdeCotizacion = false) {
        if (!window.prendas) return 0;

        let limpiadas = 0;

        window.prendas.forEach(prenda => {
            if (incluirDesdeCotizacion && this.vieneDeCotizacion(prenda)) {
                delete prenda.cotizacion_origen;
                limpiadas++;
            } else if (!incluirDesdeCotizacion) {
                delete prenda.cotizacion_origen;
                limpiadas++;
            }
        });

        console.info(`[PrendaEditorExtension] ${limpiadas} referencias limpias`);
        return limpiadas;
    }
}

// ============================================================================
// EVENTOS PERSONALIZADOS
// ============================================================================

/**
 * Disparar evento cuando se agrega una prenda desde cotización
 * Permite que otros módulos reaccionen
 */
function dispatchPrendaAgregada(prenda, cotizacion) {
    const evento = new CustomEvent('prenda-agregada-desde-cotizacion', {
        detail: {
            prenda,
            cotizacion,
            timestamp: new Date().toISOString()
        }
    });
    document.dispatchEvent(evento);
}

/**
 * Disparar evento cuando se re-procesan prendas
 */
function dispatchPrendasReprocesadas(prendas, cotizacion) {
    const evento = new CustomEvent('prendas-reprocesadas', {
        detail: {
            prendas,
            cotizacion,
            estadisticas: PrendaEditorExtension.obtenerEstadisticas()
        }
    });
    document.dispatchEvent(evento);
}

// ============================================================================
// INTEGRACIÓN CON PrendaEditor EXISTENTE
// ============================================================================

/*
En el archivo prenda-editor.js, agregar esto en el método abrirModal():

    abrirModal(esEdicion = false, prendaIndex = null, cotizacionSeleccionada = null) {
        // ... código existente ...

        // ← AGREGAR: Si viene de cotización, procesar
        if (cotizacionSeleccionada && esEdicion === false) {
            const prenda = window.prendas[window.prendas.length - 1];
            if (prenda) {
                CotizacionPrendaHandler.prepararPrendaParaEdicion(
                    prenda,
                    cotizacionSeleccionada
                );
            }
        }

        // ... resto del código ...
    }

*/

// ============================================================================
// EJEMPLO DE USO COMPLETO
// ============================================================================

/*
// Al inicializar la app
document.addEventListener('DOMContentLoaded', async () => {
    // Cargar configuración
    await CotizacionPrendaConfig.inicializarDesdeAPI();

    // Inicializar extension
    const prendaEditor = new PrendaEditor({
        notificationService: window.notificationService
    });
    PrendaEditorExtension.inicializar(prendaEditor);

    // Agregar prendas desde cotización
    document.getElementById('btn-agregar-prendas').addEventListener('click', () => {
        const cotizacionId = document.getElementById('select-cotizacion').value;
        
        fetch(`/api/cotizaciones/${cotizacionId}`)
            .then(r => r.json())
            .then(data => {
                PrendaEditorExtension.cargarPrendasDesdeCotizacion(
                    data.prendas,
                    data.cotizacion
                );

                // Mostrar estadísticas
                PrendaEditorExtension.mostrarReporte();
            });
    });

    // Escuchar eventos
    document.addEventListener('prenda-agregada-desde-cotizacion', (e) => {
        console.log('Prenda agregada:', e.detail);
    });
});

*/

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorExtension;
}
