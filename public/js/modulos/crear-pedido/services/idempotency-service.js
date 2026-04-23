(function() {
    'use strict';

    /**
     * IdempotencyService
     *
     * Gestiona claves de idempotencia para prevenir duplicados por:
     * - Doble clic en guardar
     * - Conexiones lentas
     * - Reintentados automáticos
     *
     * Comportamiento:
     * - CREAR: Genera clave UNA VEZ, la mantiene para todo el ciclo de vida del borrador
     * - ACTUALIZAR: Genera clave NUEVA en cada actualización (PUT es idempotente por naturaleza)
     */
    class IdempotencyService {
        constructor() {
            // Clave para el BORRADOR en edición (se mantiene fija)
            this.idempotencyKeyParaEdicion = null;

            // Flag para saber si es creación o edición
            this.modoEdicion = false;
            this.pedidoIdActual = null;
        }

        /**
         * Inicializar modo (crear o editar)
         */
        inicializar(modoEdicion, pedidoId) {
            this.modoEdicion = modoEdicion;
            this.pedidoIdActual = pedidoId;

            if (!modoEdicion) {
                // 🔧 CREAR: Generar clave UNA SOLA VEZ
                this.idempotencyKeyParaEdicion = this.generarUUID();
                console.warn('[IdempotencyService] MODO CREACIÓN - Clave generada', {
                    idempotencyKey: this.idempotencyKeyParaEdicion,
                    timestamp: new Date().toISOString()
                });
            } else {
                // 🔧 EDITAR: NO usar idempotencia (PUT es idempotente por naturaleza)
                this.idempotencyKeyParaEdicion = null;
                console.warn('[IdempotencyService] MODO EDICIÓN - No se usa idempotencia', {
                    pedidoId: this.pedidoIdActual,
                    razon: 'PUT es idempotente automáticamente'
                });
            }
        }

        /**
         * Obtener la clave idempotencia para esta solicitud
         */
        obtenerIdempotencyKey() {
            if (this.modoEdicion) {
                // PUT: No usar idempotencia
                return null;
            }

            if (!this.idempotencyKeyParaEdicion) {
                // Fallback: generar si no existe (no debería ocurrir)
                this.idempotencyKeyParaEdicion = this.generarUUID();
            }

            return this.idempotencyKeyParaEdicion;
        }

        /**
         * Generar UUID v4
         */
        generarUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }

        /**
         * Limpiar cuando se abre un nuevo borrador
         */
        limpiar() {
            this.idempotencyKeyParaEdicion = null;
            this.modoEdicion = false;
            this.pedidoIdActual = null;
        }

        /**
         * Obtener estado actual (para debugging)
         */
        obtenerEstado() {
            return {
                modoEdicion: this.modoEdicion,
                pedidoIdActual: this.pedidoIdActual,
                idempotencyKey: this.idempotencyKeyParaEdicion,
                tieneKey: !!this.idempotencyKeyParaEdicion,
            };
        }
    }

    // Singleton global
    window.idempotencyService = new IdempotencyService();

    window.IdempotencyService = IdempotencyService;
})();
