/**
 * PrendaEventBus - Sistema de eventos desacoplado
 * 
 * Propósito: Facilitar comunicación entre componentes sin crear dependencias directas
 * Patrón: Publicador/Suscriptor (Observer pattern)
 */
class PrendaEventBus {
    constructor() {
        this.eventos = {};
        this.historial = [];
        this.debug = false;
    }

    /**
     * Suscribirse a un evento
     * @param {string} nombreEvento - Nombre único del evento
     * @param {Function} callback - Función a ejecutar cuando se emita el evento
     * @returns {Function} - Función para desuscribirse
     */
    on(nombreEvento, callback) {
        if (typeof callback !== 'function') {
            throw new Error(`Callback debe ser una función, recibido: ${typeof callback}`);
        }

        if (!this.eventos[nombreEvento]) {
            this.eventos[nombreEvento] = [];
        }

        this.eventos[nombreEvento].push(callback);

        if (this.debug) {
            console.log(`[EventBus] Suscriptor registrado: ${nombreEvento}`, {
                suscriptores: this.eventos[nombreEvento].length
            });
        }

        // Retornar función para desuscribirse
        return () => this.off(nombreEvento, callback);
    }

    /**
     * Suscribirse a un evento una única vez
     */
    once(nombreEvento, callback) {
        const unsubscribe = this.on(nombreEvento, (datos) => {
            callback(datos);
            unsubscribe();
        });

        return unsubscribe;
    }

    /**
     * Desuscribirse de un evento
     */
    off(nombreEvento, callback) {
        if (!this.eventos[nombreEvento]) {
            return;
        }

        this.eventos[nombreEvento] = this.eventos[nombreEvento].filter(cb => cb !== callback);

        if (this.debug) {
            console.log(`[EventBus] Suscriptor removido: ${nombreEvento}`, {
                suscriptores: this.eventos[nombreEvento].length
            });
        }
    }

    /**
     * Emitir un evento
     * @param {string} nombreEvento - Nombre del evento
     * @param {*} datos - Datos a pasar a los suscriptores
     */
    emit(nombreEvento, datos = null) {
        if (this.debug) {
            console.log(`[EventBus] Emitiendo evento: ${nombreEvento}`, datos);
        }

        // Registrar en historial
        this.historial.push({
            evento: nombreEvento,
            datos: datos,
            timestamp: new Date().toISOString()
        });

        if (!this.eventos[nombreEvento]) {
            return;
        }

        // Ejecutar callbacks de forma segura
        this.eventos[nombreEvento].forEach(callback => {
            try {
                callback(datos);
            } catch (error) {
                console.error(`[EventBus] Error en callback para ${nombreEvento}:`, error);
            }
        });
    }

    /**
     * Limpiar todos los eventos o un evento específico
     */
    limpiar(nombreEvento = null) {
        if (nombreEvento) {
            delete this.eventos[nombreEvento];
        } else {
            this.eventos = {};
        }
    }

    /**
     * Obtener historial de eventos
     */
    obtenerHistorial(numeroUltimos = null) {
        if (numeroUltimos) {
            return this.historial.slice(-numeroUltimos);
        }
        return this.historial;
    }

    /**
     * Activar/desactivar modo debug
     */
    setDebug(activo) {
        this.debug = activo;
    }

    /**
     * Obtener cantidad de suscriptores a un evento
     */
    contarSuscriptores(nombreEvento) {
        return this.eventos[nombreEvento]?.length || 0;
    }
}

// Eventos estándar del sistema
PrendaEventBus.EVENTOS = {
    // Ciclo de vida
    PRENDA_ABIERTA: 'prenda:abierta',
    PRENDA_CERRADA: 'prenda:cerrada',
    PRENDA_GUARDADA: 'prenda:guardada',
    PRENDA_CARGADA: 'prenda:cargada',

    // Cambios en campos
    NOMBRE_CAMBIADO: 'prenda:nombre-cambiado',
    ORIGEN_CAMBIADO: 'prenda:origen-cambiado',
    DESCRIPCION_CAMBIADA: 'prenda:descripcion-cambiada',

    // Telas
    TELA_AGREGADA: 'tela:agregada',
    TELA_REMOVIDA: 'tela:removida',
    TELAS_CARGADAS: 'telas:cargadas',
    TELAS_DESDE_COTIZACION: 'telas:desde-cotizacion',

    // Imágenes
    IMAGEN_AGREGADA: 'imagen:agregada',
    IMAGEN_REMOVIDA: 'imagen:removida',
    IMAGENES_CARGADAS: 'imagenes:cargadas',

    // Variaciones
    VARIACIONES_CARGADAS: 'variaciones:cargadas',
    VARIACION_CAMBIADA: 'variacion:cambiada',

    // Tallas
    TALLAS_CARGADAS: 'tallas:cargadas',
    TALLA_CANTIDAD_CAMBIADA: 'talla:cantidad-cambiada',

    // Procesos
    PROCESOS_CARGADOS: 'procesos:cargados',
    PROCESO_AGREGADO: 'proceso:agregado',
    PROCESO_REMOVIDO: 'proceso:removido',

    // Cotización
    COTIZACION_ASIGNADA: 'cotizacion:asignada',
    COTIZACION_DATOS_CARGADOS: 'cotizacion:datos-cargados',

    // Notificaciones
    NOTIFICACION_MOSTRADA: 'notificacion:mostrada',
    ERROR_OCURRIDO: 'error:ocurrido',

    // UI
    MODAL_ABIERTO: 'modal:abierto',
    MODAL_CERRADO: 'modal:cerrado',
    PREVIEW_ACTUALIZADO: 'preview:actualizado'
};

window.PrendaEventBus = PrendaEventBus;
