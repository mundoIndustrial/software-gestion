/**
 * RecibosState - Singleton State Manager con patrón Observable
 *
 * Reemplaza todas las variables globales (window.*) con un estado centralizado.
 * Permite suscribirse a cambios específicos del estado usando dot notation.
 * Notificaciones cascada a paths padre.
 *
 * @class RecibosState
 */
class RecibosState {
    static #instance = null;

    constructor() {
        // Inicializar estado
        this._state = {
            //  ========== Data ==========
            recibos: [],
            paginacion: {
                current_page: 1,
                last_page: 1,
                per_page: 25,
                total: 0,
                from: 0,
                to: 0
            },

            // ========== Filtros ==========
            filtrosActivos: {
                numero_recibo: '',
                estado: '',
                area: '',
                cliente: '',
                dia_entrega: ''
            },
            opcionesFiltro: {
                estados: [],
                areas: [],
                numeros_recibo: [],
                clientes: [],
                dias_entrega: []
            },

            // ========== UI State ==========
            reciboSeleccionado: null,
            detallesRecibo: null,
            recibosSeen: new Set(),

            // ========== Loading & Errors ==========
            loading: false,
            error: null,
            successMessage: null,

            // ========== Modales ==========
            modalAgregarProceso: {
                abierto: false,
                pedidoId: null,
                prendaId: null,
                datos: null
            },
            modalSeguimiento: {
                abierto: false,
                numeroPedido: null
            },
            modalNovedades: {
                abierto: false,
                numeroPedido: null,
                numeroRecibo: null
            }
        };

        // Subscribers: Map de { ruta: Set<callbacks> }
        this._subscribers = new Map();

        // Timers para limpiar mensajes transitorios
        this._errorTimeoutId = null;
        this._successTimeoutId = null;
    }

    /**
     * Obtiene la instancia singleton
     */
    static getInstance() {
        if (!RecibosState.#instance) {
            RecibosState.#instance = new RecibosState();
        }
        return RecibosState.#instance;
    }

    /**
     * Obtiene una copia del estado completo
     */
    getState() {
        return JSON.parse(JSON.stringify(this._state));
    }

    /**
     * Obtiene un valor del estado usando ruta (dot notation)
     * Ejemplo: get('filtrosActivos.estado') retorna el valor del estado
     */
    get(ruta) {
        const partes = ruta.split('.');
        let valor = this._state;

        for (const parte of partes) {
            if (valor && typeof valor === 'object' && parte in valor) {
                valor = valor[parte];
            } else {
                return undefined;
            }
        }

        return valor;
    }

    /**
     * Establece un valor en el estado usando ruta (dot notation)
     * Dispara notificaciones a los subscribers
     *
     * Ejemplo: set('filtrosActivos.estado', 'En Ejecución')
     */
    set(ruta, valor) {
        const partes = ruta.split('.');
        const ultimaClave = partes.pop();

        let obj = this._state;
        for (const parte of partes) {
            if (!(parte in obj)) {
                obj[parte] = {};
            }
            obj = obj[parte];
        }

        // Evitar actualizar si el valor es el mismo
        if (obj[ultimaClave] === valor) {
            return;
        }

        obj[ultimaClave] = valor;

        // Notificar cambios
        this._notify(ruta);
    }

    /**
     * Actualiza múltiples valores en batch
     * Más eficiente que múltiples calls a set()
     */
    setMultiple(actualizaciones) {
        const rutasNotificadas = new Set();

        for (const [ruta, valor] of Object.entries(actualizaciones)) {
            const partes = ruta.split('.');
            const ultimaClave = partes.pop();

            let obj = this._state;
            for (const parte of partes) {
                if (!(parte in obj)) {
                    obj[parte] = {};
                }
                obj = obj[parte];
            }

            if (obj[ultimaClave] !== valor) {
                obj[ultimaClave] = valor;
                rutasNotificadas.add(ruta);
            }
        }

        // Notificar cada ruta que cambió
        for (const ruta of rutasNotificadas) {
            this._notify(ruta);
        }
    }

    /**
     * Se suscribe a cambios en una ruta específica
     * Retorna una función para desuscribirse
     *
     * Ejemplo:
     * const unsub = state.subscribe('recibos', (nuevosRecibos) => {
     *     console.log('Recibos cambiaron:', nuevosRecibos);
     * });
     * unsub(); // Desuscribirse
     */
    subscribe(ruta, callback) {
        if (!this._subscribers.has(ruta)) {
            this._subscribers.set(ruta, new Set());
        }

        this._subscribers.get(ruta).add(callback);

        // Retornar función de desuscripción
        return () => {
            const subscribers = this._subscribers.get(ruta);
            if (subscribers) {
                subscribers.delete(callback);
                if (subscribers.size === 0) {
                    this._subscribers.delete(ruta);
                }
            }
        };
    }

    /**
     * Notifica a los subscribers de cambios en una ruta
     * Notifica también a los paths padre (cascada)
     */
    _notify(ruta) {
        // Notificar esta ruta
        if (this._subscribers.has(ruta)) {
            const valor = this.get(ruta);
            for (const callback of this._subscribers.get(ruta)) {
                try {
                    callback(valor);
                } catch (error) {
                    console.error(`Error en subscriber de "${ruta}":`, error);
                }
            }
        }

        // Notificar paths padre (cascada)
        const partes = ruta.split('.');
        while (partes.length > 1) {
            partes.pop();
            const rutaPadre = partes.join('.');
            if (this._subscribers.has(rutaPadre)) {
                const valor = this.get(rutaPadre);
                for (const callback of this._subscribers.get(rutaPadre)) {
                    try {
                        callback(valor);
                    } catch (error) {
                        console.error(`Error en subscriber de "${rutaPadre}":`, error);
                    }
                }
            }
        }
    }

    /**
     * ========== Data Methods ==========
     */

    setRecibos(recibos) {
        this.set('recibos', recibos);
    }

    setPaginacion(paginacion) {
        this.set('paginacion', paginacion);
    }

    /**
     * ========== Loading & Error Methods ==========
     */

    setLoading(isLoading) {
        this.set('loading', isLoading);
    }

    setError(mensaje) {
        if(this._errorTimeoutId) {
            clearTimeout(this._errorTimeoutId);
        }

        this.set('error', mensaje);

        if (mensaje) {
            // Auto-limpiar error después de 5 segundos
            this._errorTimeoutId = setTimeout(() => {
                this.set('error', null);
            }, 5000);
        }
    }

    setSuccess(mensaje) {
        if(this._successTimeoutId) {
            clearTimeout(this._successTimeoutId);
        }

        this.set('successMessage', mensaje);

        if (mensaje) {
            // Auto-limpiar success después de 3 segundos
            this._successTimeoutId = setTimeout(() => {
                this.set('successMessage', null);
            }, 3000);
        }
    }

    /**
     * ========== Filter Methods ==========
     */

    setFiltrosActivos(filtros) {
        this.set('filtrosActivos', {
            ...this.get('filtrosActivos'),
            ...filtros
        });
    }

    setOpcionesFiltro(opciones) {
        this.set('opcionesFiltro', opciones);
    }

    limpiarFiltros() {
        this.setFiltrosActivos({
            numero_recibo: '',
            estado: '',
            area: '',
            cliente: '',
            dia_entrega: ''
        });
    }

    /**
     * Retorna solo los filtros activos (no vacíos) para enviar a la API
     */
    getFiltrosParaAPI() {
        const filtros = this.get('filtrosActivos');
        const filtrosActivos = {};

        for (const [clave, valor] of Object.entries(filtros)) {
            if (valor !== '' && valor !== null && valor !== undefined) {
                filtrosActivos[clave] = valor;
            }
        }

        return filtrosActivos;
    }

    /**
     * ========== Selection Methods ==========
     */

    setReciboSeleccionado(reciboId) {
        this.set('reciboSeleccionado', reciboId);

        if (reciboId) {
            const vistos = this.get('recibosSeen');
            vistos.add(reciboId);
        }
    }

    setDetallesRecibo(detalles) {
        this.set('detallesRecibo', detalles);
    }

    /**
     * ========== Modal Methods ==========
     */

    abrirModalAgregarProceso(pedidoId, prendaId, datos = null) {
        this.set('modalAgregarProceso', {
            abierto: true,
            pedidoId,
            prendaId,
            datos
        });
    }

    cerrarModalAgregarProceso() {
        this.set('modalAgregarProceso', {
            abierto: false,
            pedidoId: null,
            prendaId: null,
            datos: null
        });
    }

    abrirModalSeguimiento(numeroPedido) {
        this.set('modalSeguimiento', {
            abierto: true,
            numeroPedido
        });
    }

    cerrarModalSeguimiento() {
        this.set('modalSeguimiento', {
            abierto: false,
            numeroPedido: null
        });
    }

    abrirModalNovedades(numeroPedido, numeroRecibo) {
        this.set('modalNovedades', {
            abierto: true,
            numeroPedido,
            numeroRecibo
        });
    }

    cerrarModalNovedades() {
        this.set('modalNovedades', {
            abierto: false,
            numeroPedido: null,
            numeroRecibo: null
        });
    }

    /**
     * ========== Utilidades ==========
     */

    reset() {
        this._state = {
            recibos: [],
            paginacion: {
                current_page: 1,
                last_page: 1,
                per_page: 25,
                total: 0,
                from: 0,
                to: 0
            },
            filtrosActivos: {
                numero_recibo: '',
                estado: '',
                area: '',
                cliente: '',
                dia_entrega: ''
            },
            opcionesFiltro: {
                estados: [],
                areas: [],
                numeros_recibo: [],
                clientes: [],
                dias_entrega: []
            },
            reciboSeleccionado: null,
            detallesRecibo: null,
            recibosSeen: new Set(),
            loading: false,
            error: null,
            successMessage: null,
            modalAgregarProceso: { abierto: false, pedidoId: null, prendaId: null, datos: null },
            modalSeguimiento: { abierto: false, numeroPedido: null },
            modalNovedades: { abierto: false, numeroPedido: null, numeroRecibo: null }
        };

        this._notify('');
    }
}

/**
 * Exportar clase
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RecibosState;
}
