/**
 * PrendaTallasService - Gestión de tallas, cantidades y géneros
 * 
 * Responsabilidades:
 * - Cargar tallas desde múltiples fuentes (BD, formulario, procesos)
 * - Normalizar estructura relacional de tallas
 * - Renderizar tarjetas de género con tallas
 * - Sincronizar tallas entre prenda y procesos
 * - Aplicar tallas automáticamente a procesos
 */
class PrendaTallasService {
    constructor(opciones = {}) {
        this.api = opciones.api;
        this.domAdapter = opciones.domAdapter;
        this.eventBus = opciones.eventBus;
        this.tallasRelacionales = {
            DAMA: {},
            CABALLERO: {},
            UNISEX: {}
        };
    }

    /**
     * Cargar tallas desde prenda (estrategia de prioridades)
     * 
     * Prioridad:
     * 1. generosConTallas (BD)
     * 2. cantidad_talla (formulario)
     * 3. procesos (cotización)
     * 4. tallas_disponibles (fallback)
     */
    cargarTallasYCantidades(prenda) {
        console.log('[PrendaTallasService] Cargando tallas');

        // Resetear tallas
        this.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {} };

        // ESTRATEGIA 1: generosConTallas (formato BD)
        if (prenda.generosConTallas && Object.keys(prenda.generosConTallas).length > 0) {
            console.log('[PrendaTallasService] ✓ Cargando desde generosConTallas');
            this.procesarGenerosConTallas(prenda.generosConTallas);
        }
        // ESTRATEGIA 2: cantidad_talla (formato formulario)
        else if (prenda.cantidad_talla && typeof prenda.cantidad_talla === 'object') {
            console.log('[PrendaTallasService] ✓ Cargando desde cantidad_talla');
            this.procesarCantidadTalla(prenda.cantidad_talla);
        }
        // ESTRATEGIA 3: procesos (cotización)
        else if (prenda.cotizacion_id && prenda.procesos) {
            console.log('[PrendaTallasService] ✓ Cargando desde procesos');
            this.procesarTallasDesdeProcesos(prenda.procesos);
        }
        // ESTRATEGIA 4: tallas_disponibles (fallback)
        else if (prenda.tallas_disponibles && Array.isArray(prenda.tallas_disponibles)) {
            console.log('[PrendaTallasService] ✓ Cargando desde tallas_disponibles');
            this.procesarTallasDisponibles(prenda.tallas_disponibles);
        }

        // Asignar a window (compatibilidad)
        window.tallasRelacionales = this.tallasRelacionales;

        // Renderizar tarjetas
        this.renderizarTarjetasGenero();

        this.eventBus?.emit(PrendaEventBus.EVENTOS.TALLAS_CARGADAS, {
            tallas: this.tallasRelacionales
        });

        console.log('[PrendaTallasService] ✓ Tallas cargadas:', this.tallasRelacionales);
    }

    /**
     * Procesar generosConTallas (formato BD)
     * Estructura: { DAMA: { cantidades: { S: 10, M: 20 } }, ... }
     * @private
     */
    procesarGenerosConTallas(generosConTallas) {
        Object.entries(generosConTallas).forEach(([generoKey, tallaData]) => {
            const generoUpper = generoKey.toUpperCase();
            if (tallaData.cantidades && typeof tallaData.cantidades === 'object') {
                this.tallasRelacionales[generoUpper] = { ...tallaData.cantidades };
            }
        });
    }

    /**
     * Procesar cantidad_talla (formato formulario)
     * Estructura: { DAMA: { S: 20, M: 20 }, CABALLERO: { ... }, ... }
     * @private
     */
    procesarCantidadTalla(cantidadTalla) {
        Object.entries(cantidadTalla).forEach(([generoKey, tallasObj]) => {
            if (typeof tallasObj === 'object') {
                const generoUpper = generoKey.toUpperCase();
                this.tallasRelacionales[generoUpper] = { ...tallasObj };
            }
        });
    }

    /**
     * Procesar tallas desde procesos de cotización
     * Extrae talla_cantidad de cada proceso
     * @private
     */
    procesarTallasDesdeProcesos(procesos) {
        if (!procesos) return;

        const procesosArray = Array.isArray(procesos) ? procesos : Object.values(procesos);

        procesosArray.forEach(proceso => {
            const datosProc = proceso.datos ? proceso.datos : proceso;
            if (datosProc.talla_cantidad && typeof datosProc.talla_cantidad === 'object') {
                Object.entries(datosProc.talla_cantidad).forEach(([generoKey, tallasObj]) => {
                    const generoUpper = generoKey.toUpperCase();
                    if (!Object.keys(this.tallasRelacionales[generoUpper]).length) {
                        this.tallasRelacionales[generoUpper] = { ...tallasObj };
                    }
                });
            }
        });
    }

    /**
     * Procesar tallas_disponibles (fallback)
     * Simple array de tallas para todos los géneros
     * @private
     */
    procesarTallasDisponibles(tallasDisponibles) {
        if (!Array.isArray(tallasDisponibles)) return;

        // Crear estructura con todas las tallas en 0
        const estructura = {};
        tallasDisponibles.forEach(talla => {
            estructura[talla] = 0;
        });

        this.tallasRelacionales.DAMA = { ...estructura };
        this.tallasRelacionales.CABALLERO = { ...estructura };
        this.tallasRelacionales.UNISEX = { ...estructura };
    }

    /**
     * Renderizar tarjetas de género con tallas
     * Ejecuta la función global si existe (compatibilidad)
     * @private
     */
    renderizarTarjetasGenero() {
        // Asignar a window para renderizado
        window.tallasRelacionales = this.tallasRelacionales;

        // Disparar eventos de cambio en generos
        ['dama', 'caballero', 'unisex'].forEach(genero => {
            const checkbox = document.querySelector(`input[value="${genero}"]`);
            if (checkbox) {
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        // Usar función global si existe
        if (typeof window.mostrarTallasDisponibles === 'function') {
            window.mostrarTallasDisponibles();
        }

        console.log('[PrendaTallasService] Tarjetas de género renderizadas');
    }

    /**
     * Aplicar tallas automáticamente a procesos
     * Para cotizaciones: sincronizar tallas de prenda con procesos
     */
    aplicarTallasAutomaticamenteAProcesos(procesos, tallasDama = {}, tallasCaballero = {}) {
        if (!procesos || Object.keys(procesos).length === 0) {
            console.log('[PrendaTallasService] No hay procesos para sincronizar');
            return;
        }

        console.log('[PrendaTallasService] 🔄 Sincronizando tallas a procesos');

        Object.keys(procesos).forEach(procesoSlug => {
            const proceso = procesos[procesoSlug];
            if (!proceso?.datos) return;

            // Aplicar tallas DAMA
            if (Object.keys(tallasDama).length > 0) {
                proceso.datos.talla_cantidad = proceso.datos.talla_cantidad || {};
                proceso.datos.talla_cantidad.DAMA = { ...tallasDama };
            }

            // Aplicar tallas CABALLERO
            if (Object.keys(tallasCaballero).length > 0) {
                proceso.datos.talla_cantidad = proceso.datos.talla_cantidad || {};
                proceso.datos.talla_cantidad.CABALLERO = { ...tallasCaballero };
            }
        });

        // Re-renderizar procesos
        if (typeof window.renderizarTarjetasProcesos === 'function') {
            window.renderizarTarjetasProcesos();
        }

        console.log('[PrendaTallasService] ✓ Tallas aplicadas a procesos');
    }

    /**
     * Validar tallas antes de guardar
     */
    validarTallas(tallasRelacionales) {
        let totalTallas = 0;

        Object.values(tallasRelacionales).forEach(tallasGenero => {
            Object.values(tallasGenero).forEach(cantidad => {
                totalTallas += Math.max(0, parseInt(cantidad) || 0);
            });
        });

        return {
            valido: totalTallas > 0,
            total: totalTallas,
            errores: totalTallas === 0 ? ['Debe agregar al menos una talla'] : []
        };
    }

    /**
     * Obtener tallas relacionales normalizadas
     */
    obtenerTallasNormalizadas() {
        const normalizado = {};

        Object.entries(this.tallasRelacionales).forEach(([genero, tallas]) => {
            const tallasConValor = {};
            Object.entries(tallas).forEach(([talla, cantidad]) => {
                const cant = parseInt(cantidad) || 0;
                if (cant > 0) {
                    tallasConValor[talla] = cant;
                }
            });

            if (Object.keys(tallasConValor).length > 0) {
                normalizado[genero] = tallasConValor;
            }
        });

        return normalizado;
    }

    /**
     * Obtener total de prendas
     */
    obtenerTotalPrendas() {
        let total = 0;
        Object.values(this.tallasRelacionales).forEach(tallasGenero => {
            Object.values(tallasGenero).forEach(cantidad => {
                total += Math.max(0, parseInt(cantidad) || 0);
            });
        });
        return total;
    }

    /**
     * Obtener tallas de un género específico
     */
    obtenerTallasGenero(genero) {
        return this.tallasRelacionales[genero.toUpperCase()] || {};
    }

    /**
     * Establecer cantidad de una talla específica
     */
    establecerCantidadTalla(genero, talla, cantidad) {
        const generoUpper = genero.toUpperCase();
        if (!this.tallasRelacionales[generoUpper]) {
            this.tallasRelacionales[generoUpper] = {};
        }
        this.tallasRelacionales[generoUpper][talla] = Math.max(0, parseInt(cantidad) || 0);
        
        this.eventBus?.emit(PrendaEventBus.EVENTOS.TALLA_CANTIDAD_CAMBIADA, {
            genero: generoUpper,
            talla,
            cantidad: this.tallasRelacionales[generoUpper][talla]
        });
    }

    /**
     * Limpiar todas las tallas
     */
    limpiarTallas() {
        this.tallasRelacionales = {
            DAMA: {},
            CABALLERO: {},
            UNISEX: {}
        };
        window.tallasRelacionales = this.tallasRelacionales;
    }
}

window.PrendaTallasService = PrendaTallasService;
