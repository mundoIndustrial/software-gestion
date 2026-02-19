/**
 * Real-Time Table Refresh System - Laravel Echo + Reverb
 * Usa únicamente Laravel Echo con broadcaster "reverb"
 * Eliminado todo código WebSocket manual
 */

class PedidosRealtimeRefresh {
    static instance = null;
    
    constructor(options = {}) {
        // Patrón Singleton - evitar múltiples instancias
        if (PedidosRealtimeRefresh.instance) {
            return PedidosRealtimeRefresh.instance;
        }
        
        PedidosRealtimeRefresh.instance = this;
        // Configuración optimada
        this.checkInterval = options.checkInterval || 30000; // 30 segundos para fallback
        this.autoStart = options.autoStart !== false;
        this.debug = options.debug || false; // Control de logs
        this.isRunning = false;
        this.lastUpdateTime = null;
        this.lastChangeTime = null;
        this.pedidosAnterior = new Map();
        
        // Control de actividad con debounce
        this.userActivityTimeout = null;
        this.activityDebounceTimeout = null;
        this.isVisible = true;
        this.hasFocus = true;
        
        // Laravel Echo
        this.echoChannel = null;
        this.usingWebSockets = false;
        
        // Detección de página
        this.isCarteraPage = window.location.pathname.includes('/cartera/pedidos');
        this.isAnyCarteraPage = window.location.pathname.includes('/cartera/');
        
        // No ejecutar realtime en páginas de cartera (excepto /cartera/pedidos)
        if (this.isAnyCarteraPage && !this.isCarteraPage) {
            console.log('[PedidosRealtime] Página de cartera detectada, desactivando realtime');
            this.isRunning = false;
            return;
        }
        
        // Elementos DOM
        this.tableContainer = this.isCarteraPage ? 
            document.querySelector('.table-scroll-container') : 
            document.querySelector('.table-scroll-container');
        
        this.init();
    }

    init() {
        if (this.debug) console.log(' [PedidosRealtime] Sistema inicializado');
        
        // Detectar actividad del usuario
        this.setupActivityDetection();
        
        // Detectar visibilidad de la página
        this.setupVisibilityDetection();
        
        // Configurar Laravel Echo
        this.setupEchoConnection();
        
        if (this.autoStart) {
            this.start();
        }
    }

    setupActivityDetection() {
        // Detectar actividad del usuario con debounce
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'click', 'focus'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.onUserActivityDebounced();
            }, { passive: true });
        });
    }

    setupVisibilityDetection() {
        // Detectar si la página está visible
        document.addEventListener('visibilitychange', () => {
            this.isVisible = !document.hidden;
            this.adjustPollingInterval();
        });

        // Detectar si la ventana tiene foco
        window.addEventListener('focus', () => {
            this.hasFocus = true;
            this.adjustPollingInterval();
        });

        window.addEventListener('blur', () => {
            this.hasFocus = false;
            this.adjustPollingInterval();
        });
    }

    /**
     * Configurar Laravel Echo
     */
    setupEchoConnection() {
        // Verificar si Echo está disponible
        if (!window.Echo) {
            console.warn(' [PedidosRealtime] Laravel Echo no está disponible, usando solo polling');
            return;
        }

        // Obtener user ID desde meta tags
        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

        if (!userId) {
            console.warn(' [PedidosRealtime] User ID no encontrado, no se puede suscribir a canales');
            return;
        }

        try {
            if (this.debug) {
                console.log('🔌 [PedidosRealtime] Suscribiendo a canal privado con Laravel Echo');
                console.log('  - User ID:', userId);
            }
            
            // Suscribir al canal privado usando Laravel Echo Instance
            this.echoChannel = window.EchoInstance.private(`pedidos.${userId}`)
                .listen('.PedidoActualizado', (event) => {
                    if (this.debug) console.log('📡 [PedidosRealtime] Evento recibido:', event.pedido.id);
                    this.handlePedidoUpdate(event.pedido, 'pedido.actualizado', event.changedFields);
                })
                .listen('.PedidoCreado', (event) => {
                    if (this.debug) console.log('➕ [PedidosRealtime] Nuevo pedido:', event.pedido.id);
                    this.handlePedidoUpdate(event.pedido, 'pedido.creado', event.changedFields);
                })
                .error((error) => {
                    console.error(' [PedidosRealtime] Error en canal Echo:', error);
                    this.usingWebSockets = false;
                    this.showConnectionIndicator('Echo Error', 'error');
                    // Si WebSockets falla, iniciar polling fallback
                    this.startPollingFallback();
                });

            this.usingWebSockets = true;
            if (this.debug) console.log(' [PedidosRealtime] Conexión WebSocket establecida - SIN POLLING');

        } catch (error) {
            console.error(' [PedidosRealtime] Error configurando WebSocket:', error);
            this.usingWebSockets = false;
            // Si hay error, usar polling fallback
            this.startPollingFallback();
        }
    }

    /**
     * Manejar actualización de pedido desde Echo
     */
    handlePedidoUpdate(pedido, action, changedFields) {
        if (this.debug) console.log(' [PedidosRealtime] Actualización:', pedido.id);
        
        // Actualizar o agregar el pedido específico
        this.actualizarPedidoIndividual(pedido, changedFields);
        
        this.lastChangeTime = new Date();
    }

    /**
     * Actualizar pedido individual (para Echo)
     */
    actualizarPedidoIndividual(pedido, changedFields) {
        // Buscar fila del pedido según la página
        const selector = this.isCarteraPage ? 
            `[data-orden-id="${pedido.id}"]` : 
            `[data-pedido-id="${pedido.id}"]`;
        const fila = document.querySelector(selector);
        
        if (fila) {
            // Actualizar fila existente
            this.actualizarFila(fila, pedido);
            
            // Resaltar campos cambiados
            if (changedFields) {
                this.resaltarCamposCambios(fila, changedFields);
            }
        } else {
            // Nuevo pedido - para Cartera, recargar toda la tabla
            if (this.isCarteraPage) {
                if (this.debug) console.log(' [PedidosRealtime] Nuevo pedido, recargando');
                if (window.cargarPedidos) {
                    window.cargarPedidos();
                }
            } else {
                // Para Asesores, agregar nueva fila
                if (this.debug) console.log('➕ [PedidosRealtime] Nuevo pedido:', pedido.id);
                this.agregarFilaNueva(pedido);
            }
        }
        // Actualizar estado interno
        this.pedidosAnterior.set(pedido.id, {
            estado: pedido.estado,
            novedades: pedido.novedades,
            forma_pago: pedido.forma_pago,
            fecha_estimada: pedido.fecha_estimada,
        });
    }

    /**
     * Resaltar campos que cambiaron
     */
    resaltarCamposCambios(fila, changedFields) {
        const celdas = fila.querySelectorAll('[style*="display: flex"]');
        
        if (changedFields.estado && celdas.length >= 2) {
            celdas[1].style.background = '#dcfce7'; // Verde claro
            setTimeout(() => {
                celdas[1].style.background = '';
                celdas[1].style.transition = 'background-color 1s ease-out';
            }, 3000);
        }
        
        if (changedFields.novedades && celdas.length > 5) {
            celdas[5].style.background = '#fef3c7'; // Amarillo claro
            setTimeout(() => {
                celdas[5].style.background = '';
                celdas[5].style.transition = 'background-color 1s ease-out';
            }, 3000);
        }
    }

    /**
     * Mostrar indicador de conexión
     */
    showConnectionIndicator(type, status) {
        // Crear o actualizar indicador
        let indicator = document.querySelector('.realtime-connection-indicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'realtime-connection-indicator';
            indicator.style.cssText = `
                position: fixed;
                top: 10px;
                right: 10px;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: bold;
                z-index: 9999;
                transition: all 0.3s ease;
            `;
            document.body.appendChild(indicator);
        }
        
        indicator.textContent = type;
        indicator.className = `realtime-connection-indicator ${status}`;
        
        // Colores según estado
        if (status === 'success') {
            indicator.style.background = '#22c55e';
            indicator.style.color = 'white';
        } else if (status === 'warning') {
            indicator.style.background = '#f59e0b';
            indicator.style.color = 'white';
        } else {
            indicator.style.background = '#ef4444';
            indicator.style.color = 'white';
        }
        
        // Ocultar después de 3 segundos
        setTimeout(() => {
            indicator.style.opacity = '0';
            setTimeout(() => indicator.remove(), 300);
        }, 3000);
    }

    /**
     * Ocultar indicador de conexión
     */
    hideConnectionIndicator() {
        const indicator = document.querySelector('.realtime-connection-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    onUserActivityDebounced() {
        // Limpiar timeout existente
        if (this.activityDebounceTimeout) {
            clearTimeout(this.activityDebounceTimeout);
        }
        
        // Esperar 500ms antes de procesar actividad
        this.activityDebounceTimeout = setTimeout(() => {
            this.onUserActivity();
        }, 500);
    }

    onUserActivity() {
        // Reiniciar timeout de inactividad
        clearTimeout(this.userActivityTimeout);
        
        // Si está inactivo, reactivar
        if (!this.isRunning) {
            if (this.debug) console.log(' [PedidosRealtime] Reactivando por actividad');
            this.start();
        }
        
        // Marcar como activo por 5 minutos
        this.userActivityTimeout = setTimeout(() => {
            if (this.debug) console.log(' [PedidosRealtime] Usuario inactivo, pausando');
            this.pause();
        }, 300000); // 5 minutos
    }

    adjustPollingInterval() {
        if (!this.isRunning) return;
        
        // Restaurar intervalo normal para producción
        let newInterval = this.isVisible && this.hasFocus ? 30000 : 60000; // 30s activo, 60s inactivo
        
        if (this.debug) console.log(' [PedidosRealtime]  Intervalo ajustado a', newInterval, 'ms');
        
        this.checkInterval = newInterval;
    }

    start() {
        if (this.isRunning) {
            return;
        }
        
        if (this.debug) console.log(` [PedidosRealtime]  Iniciando monitoreo`);
        this.isRunning = true;
        
        // Solo iniciar polling si WebSockets no está disponible
        if (!this.usingWebSockets) {
            if (this.debug) console.log(' [PedidosRealtime] WebSockets no disponible, usando polling fallback');
            this.startPollingFallback();
        } else {
            if (this.debug) console.log(' [PedidosRealtime] Usando WebSockets, sin polling necesario');
        }
    }
    
    /**
     * Sistema de polling fallback SOLO cuando WebSockets fallan
     */
    startPollingFallback() {
        if (!this.isRunning || this.usingWebSockets) {
            return; // No usar polling si WebSockets funciona
        }
        
        if (this.debug) {
            console.log(' [PedidosRealtime] Iniciando polling fallback cada', this.checkInterval, 'ms');
            console.log(' [PedidosRealtime] API URL:', this.getApiUrl());
        }
        
        const checkForUpdates = async () => {
            // Solo ejecutar si está activo y WebSockets no funciona
            if (!this.isRunning || !PedidosRealtimeRefresh.instance || this.usingWebSockets) {
                return;
            }
            
            if (this.debug) console.log(' [PedidosRealtime]  Verificando...');
            
            try {
                const response = await fetch(this.getApiUrl(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    if (this.debug) {
                        const errorText = await response.text();
                        console.error(' [PedidosRealtime]  Error:', response.status);
                    }
                    return;
                }
                
                const data = await response.json();
                
                if (data && data.data) {
                    await this.checkForChanges(data.data);
                }
            } catch (error) {
                if (this.debug) console.error(' [PedidosRealtime] Error en polling:', error.message);
            }
            
            // Programar siguiente verificación solo si WebSockets sigue sin funcionar
            if (this.isRunning && !this.usingWebSockets) {
                setTimeout(checkForUpdates, this.checkInterval);
            }
        };
        
        // Iniciar primera verificación después de 2 segundos
        setTimeout(checkForUpdates, 2000);
    }

    pause() {
        if (!this.isRunning) return;
        
        if (this.debug) console.log(' [PedidosRealtime] ⏸️ Pausado');
        this.isRunning = false;
    }

    stop() {
        if (!this.isRunning) return;
        
        if (this.debug) console.log(' [PedidosRealtime] ⏹️ Detenido');
        this.isRunning = false;
        clearTimeout(this.userActivityTimeout);
        
        // Desconectar canal Echo si existe
        if (this.echoChannel) {
            // Laravel Echo no tiene un método destroy explícito para canales individuales
            // El canal se desconectará automáticamente cuando el objeto Echo se destruya
            this.echoChannel = null;
        }
    }

    /**
     * Destruir instancia completamente - limpiar todos los recursos
     */
    destroy() {
        if (this.debug) console.log(' [PedidosRealtime] 💥 Destruyendo instancia');
        
        // Detener monitoreo
        this.stop();
        
        // Limpiar timeouts
        if (this.userActivityTimeout) {
            clearTimeout(this.userActivityTimeout);
            this.userActivityTimeout = null;
        }
        
        if (this.activityDebounceTimeout) {
            clearTimeout(this.activityDebounceTimeout);
            this.activityDebounceTimeout = null;
        }
        
        // Limpiar estado
        this.pedidosAnterior.clear();
        this.echoChannel = null;
        this.usingWebSockets = false;
        
        // Eliminar instancia singleton
        PedidosRealtimeRefresh.instance = null;
        
        // Ocultar indicador de conexión
        this.hideConnectionIndicator();
    }

    /**
     * Obtener estado del sistema
     */
    getStatus() {
        return {
            isRunning: this.isRunning,
            usingWebSockets: this.usingWebSockets,
            connectionType: this.usingWebSockets ? 'WebSocket (Real-time)' : 'Polling (Fallback)',
            isVisible: this.isVisible,
            hasFocus: this.hasFocus,
            checkInterval: this.checkInterval,
            pedidosCount: this.pedidosAnterior.size,
            lastChangeTime: this.lastChangeTime,
            echoChannel: this.echoChannel ? 'active' : 'inactive'
        };
    }

    /**
     * Método legacy para compatibilidad - ya no se usa
     */
    async verificar() {
        // Este método ya no se usa directamente, pero se mantiene por compatibilidad
        if (this.debug) console.log(' [PedidosRealtime] Método verificar() legacy');
        return;
    }

    /**
     * Obtener URL de API según la página actual
     */
    getApiUrl() {
        if (this.isCarteraPage) {
            return '/api/cartera/pedidos?estado=pendiente_cartera';
        } else {
            return '/asesores/realtime/pedidos'; // Nueva API específica para tiempo real
        }
    }
    
    /**
     * Verificar si hay cambios y actualizar tabla
     */
    async checkForChanges(pedidosNuevos) {
        if (this.debug) console.log(' [PedidosRealtime]  Analizando', pedidosNuevos.length, 'pedidos');
        
        const hayCambios = this.detectarCambios(pedidosNuevos);
        
        if (hayCambios) {
            if (this.debug) console.log(' [PedidosRealtime]  Cambios detectados');
            this.lastChangeTime = new Date();
            
            // Recargar la tabla completa solo si las funciones existen
            if (typeof window.cargarPedidos === 'function') {
                await window.cargarPedidos();
            } else if (this.isCarteraPage && typeof window.cargarPedidosCartera === 'function') {
                await window.cargarPedidosCartera();
            }
        }
    }

    detectarCambios(pedidosNuevos) {
        // Si es la primera vez, guardar y no actualizar
        if (this.pedidosAnterior.size === 0) {
            this.guardarEstadoPedidos(pedidosNuevos);
            return false;
        }
        
        // Verificar si hay cambios
        let hayCambios = false;
        
        // Verificar nuevos pedidos
        if (pedidosNuevos.length !== this.pedidosAnterior.size) {
            if (this.debug) {
                console.log(' [PedidosRealtime] Cantidad cambió:', this.pedidosAnterior.size, '->', pedidosNuevos.length);
            }
            hayCambios = true;
        }
        
        // Verificar cambios en pedidos existentes
        for (const pedido of pedidosNuevos) {
            const anterior = this.pedidosAnterior.get(pedido.id);
            
            if (!anterior) {
                if (this.debug) console.log('➕ [PedidosRealtime] Nuevo pedido:', pedido.id);
                hayCambios = true;
                continue;
            }
            
            // Comparar campos importantes
            if (anterior.estado !== pedido.estado) {
                if (this.debug) console.log(' [PedidosRealtime] Estado cambió #' + pedido.id);
                hayCambios = true;
            }
            
            if (anterior.novedades !== pedido.novedades) {
                if (this.debug) console.log(' [PedidosRealtime] Novedades cambió #' + pedido.id);
                hayCambios = true;
            }
        }
        
        // Guardar estado actual para próxima comparación
        this.guardarEstadoPedidos(pedidosNuevos);
        
        return hayCambios;
    }

    guardarEstadoPedidos(pedidos) {
        this.pedidosAnterior.clear();
        for (const pedido of pedidos) {
            this.pedidosAnterior.set(pedido.id, {
                estado: pedido.estado,
                novedades: pedido.novedades,
                forma_pago: pedido.forma_pago,
                fecha_estimada: pedido.fecha_estimada,
            });
        }
    }

    actualizarTabla(pedidos) {
        // Obtener contenedor de filas
        const tablasContainer = document.querySelector('.table-scroll-container');
        if (!tablasContainer) {
            console.error(' [PedidosRealtime] No se encontró el contenedor de la tabla');
            return;
        }
        
        // Obtener filas actuales
        const filasActuales = tablasContainer.querySelectorAll('[data-pedido-id]');
        const pedidosActuales = new Map(
            Array.from(filasActuales).map(fila => [
                parseInt(fila.dataset.pedidoId),
                fila
            ])
        );
        
        // Crear mapa de pedidos nuevos
        const pedidosNuevos = new Map(
            pedidos.map(p => [p.id, p])
        );
        
        // Actualizar filas existentes
        for (const [id, fila] of pedidosActuales) {
            if (pedidosNuevos.has(id)) {
                this.actualizarFila(fila, pedidosNuevos.get(id));
            } else {
                // Eliminar filas que ya no existen (con animación)
                fila.style.opacity = '0.5';
                fila.style.transition = 'opacity 0.3s ease-out';
                setTimeout(() => fila.remove(), 300);
            }
        }
        
        // Agregar nuevas filas
        const filasPadre = tablasContainer.querySelector('[style*="grid-template-columns"]')?.parentElement;
        for (const [id, pedido] of pedidosNuevos) {
            if (!pedidosActuales.has(id)) {
                console.log('➕ [PedidosRealtime] Nuevo pedido agregado:', id);
                // Las nuevas filas se agregan con una animación
                this.agregarFilaNueva(pedido);
            }
        }
    }

    actualizarFila(fila, pedido) {
        // Buscar celdas por posición
        const celdas = fila.querySelectorAll('[style*="display: flex"]');
        if (celdas.length >= 8) {
            // Estado (índice 1)
            const celdaEstado = celdas[1];
            const estadoActual = celdaEstado.textContent.trim();
            if (estadoActual !== pedido.estado) {
                celdaEstado.textContent = pedido.estado;
                fila.style.background = '#fef3c7'; // Resaltar cambio
                setTimeout(() => {
                    fila.style.background = '';
                    fila.style.transition = 'background-color 0.5s ease-out';
                }, 2000);
            }
            
            // Novedades (índice 5)
            if (celdas.length > 5) {
                const celdaNovedades = celdas[5];
                if (pedido.novedades) {
                    const conteo = (pedido.novedades.match(/\n/g) || []).length + 1;
                    celdaNovedades.textContent = conteo > 0 ? `${conteo} novedades` : 'Sin novedades';
                } else {
                    celdaNovedades.textContent = 'Sin novedades';
                }
            }
        }
    }

    agregarFilaNueva(pedido) {
        // Aquí se agregría lógica para crear una nueva fila con animación
        // Por ahora, se deja para recargar la página si hay nuevos pedidos
        console.log('Nueva fila:', pedido);
    }
}

// Inicializar cuando el DOM esté listo con patrón singleton
document.addEventListener('DOMContentLoaded', () => {
    // Destruir instancia existente si la hay
    if (window.pedidosRealtimeRefresh) {
        window.pedidosRealtimeRefresh.destroy();
        window.pedidosRealtimeRefresh = null;
    }
    
    // Crear nueva instancia solo si no existe
    if (!window.pedidosRealtimeRefresh) {
        window.pedidosRealtimeRefresh = new PedidosRealtimeRefresh({
            checkInterval: 30000, // 30 segundos
            autoStart: true,
            debug: false // Desactivar logs en producción
        });
    }
});

// También inicializar si el DOM ya está cargado
if (document.readyState === 'loading') {
    // DOM todavía cargando, esperar evento
} else {
    // DOM ya cargado, inicializar inmediatamente
    if (!window.pedidosRealtimeRefresh) {
        window.pedidosRealtimeRefresh = new PedidosRealtimeRefresh({
            checkInterval: 30000,
            autoStart: true,
            debug: false // Desactivar logs en producción
        });
    }
}
