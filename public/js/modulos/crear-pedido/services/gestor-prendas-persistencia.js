(function() {
    'use strict';

    /**
     * GestorPrendasPersistencia
     *
     * Preserva el estado de prendas en sessionStorage para evitar pérdidas
     * cuando se navega, se cierran modales, o hay errores.
     *
     * Flujo:
     * 1. Guardar automáticamente cada cambio en el gestor
     * 2. Si las prendas se pierden → restaurar de storage
     * 3. Notificar al usuario si hay restauración
     */
    class GestorPrendasPersistencia {
        constructor() {
            this.storageKey = 'pedido_prendas_backup_' + (window.pedidoEditarId || 'nuevo');
            this.versionKey = this.storageKey + '_version';
            this.timeoutGuardar = null;
            this.ultimaVersion = 0;

            console.log('[GestorPrendasPersistencia] Inicializado para pedido:', window.pedidoEditarId || 'nuevo');
        }

        /**
         * Guardar estado actual del gestor en sessionStorage
         * Se ejecuta automáticamente después de cambios
         */
        guardarEstado(gestor) {
            if (!gestor || !Array.isArray(gestor.prendas)) {
                return;
            }

            clearTimeout(this.timeoutGuardar);

            // Esperar 500ms antes de guardar (evita guardar en cada cambio)
            this.timeoutGuardar = setTimeout(() => {
                try {
                    const datos = {
                        prendas: gestor.prendas,
                        timestamp: new Date().toISOString(),
                        version: ++this.ultimaVersion,
                        pedidoId: window.pedidoEditarId || null,
                    };

                    sessionStorage.setItem(this.storageKey, JSON.stringify(datos));

                    console.log('[GestorPrendasPersistencia] Estado guardado', {
                        cantidad: gestor.prendas.length,
                        version: this.ultimaVersion,
                        timestamp: datos.timestamp,
                    });
                } catch (e) {
                    console.warn('[GestorPrendasPersistencia] Error al guardar:', e.message);
                }
            }, 500);
        }

        /**
         * Restaurar prendas desde sessionStorage si se perdieron
         */
        restaurarEstado(gestor) {
            if (!gestor) {
                return false;
            }

            try {
                const datosGuardados = sessionStorage.getItem(this.storageKey);
                if (!datosGuardados) {
                    return false;
                }

                const datos = JSON.parse(datosGuardados);
                if (!datos.prendas || !Array.isArray(datos.prendas) || datos.prendas.length === 0) {
                    return false;
                }

                // Verificar que no sea un backup viejo (más de 24 horas)
                const timestamp = new Date(datos.timestamp);
                const ahora = new Date();
                const diferencia = (ahora - timestamp) / (1000 * 60 * 60);
                if (diferencia > 24) {
                    console.warn('[GestorPrendasPersistencia] Backup muy antiguo (>24h), ignorando');
                    return false;
                }

                // Restaurar prendas
                gestor.prendas = datos.prendas;
                this.ultimaVersion = datos.version || 0;

                console.warn('[GestorPrendasPersistencia] ⚠️ PRENDAS RESTAURADAS', {
                    cantidad: datos.prendas.length,
                    version: datos.version,
                    desde: datos.timestamp,
                });

                // Notificar al usuario
                this.notificarRestauracion(datos.prendas.length);

                return true;
            } catch (e) {
                console.error('[GestorPrendasPersistencia] Error al restaurar:', e.message);
                return false;
            }
        }

        /**
         * Verificar si las prendas se han perdido y restaurar
         */
        validarYRestaurarSiNecesario(gestor) {
            if (!gestor) return;

            // Si el gestor tiene prendas, no restaurar
            if (Array.isArray(gestor.prendas) && gestor.prendas.length > 0) {
                // Pero sí guardar el estado
                this.guardarEstado(gestor);
                return;
            }

            // El gestor está vacío, intentar restaurar
            const seRestauró = this.restaurarEstado(gestor);
            if (seRestauró) {
                // Renderizar las prendas restauradas
                if (typeof window.renderizarPrendasSinCotizacion === 'function') {
                    setTimeout(() => {
                        window.renderizarPrendasSinCotizacion();
                    }, 100);
                }
            }
        }

        /**
         * Notificar al usuario que se restauraron prendas
         */
        notificarRestauracion(cantidad) {
            // Usar sistema de notificaciones del proyecto si existe
            if (window.notificationService && typeof window.notificationService.info === 'function') {
                window.notificationService.info(
                    `✅ Se restauraron ${cantidad} prendas que se habían perdido`,
                    { duracion: 5000 }
                );
            } else if (window.showNotification) {
                window.showNotification(`Se restauraron ${cantidad} prendas`, 'info');
            } else {
                console.log(`[INFO] Se restauraron ${cantidad} prendas`);
            }
        }

        /**
         * Limpiar backup cuando se guarda exitosamente el pedido
         */
        limpiarBackup() {
            try {
                sessionStorage.removeItem(this.storageKey);
                this.ultimaVersion = 0;
                console.log('[GestorPrendasPersistencia] Backup eliminado (pedido guardado exitosamente)');
            } catch (e) {
                console.warn('[GestorPrendasPersistencia] Error al limpiar backup:', e.message);
            }
        }

        /**
         * Exportar estado completo (para debugging)
         */
        obtenerEstado() {
            try {
                const datos = sessionStorage.getItem(this.storageKey);
                return datos ? JSON.parse(datos) : null;
            } catch (e) {
                return null;
            }
        }
    }

    // Crear singleton global
    window.gestorPrendasPersistencia = new GestorPrendasPersistencia();
    window.GestorPrendasPersistencia = GestorPrendasPersistencia;
})();
