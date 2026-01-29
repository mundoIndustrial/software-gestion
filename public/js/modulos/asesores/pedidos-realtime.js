/**
 * Real-Time Table Refresh System - Laravel Echo + Reverb
 * Usa únicamente Laravel Echo con broadcaster "reverb"
 * Eliminado todo código WebSocket manual
 */

class PedidosRealtimeRefresh {
    constructor(options = {}) {
        // Configuración optimada
        this.checkInterval = options.checkInterval || 30000; // 30 segundos para fallback
        this.autoStart = options.autoStart !== false;
        this.isRunning = false;
        this.lastUpdateTime = null;
        this.lastChangeTime = null;
        this.pedidosAnterior = new Map();
        
        // Control de actividad
        this.userActivityTimeout = null;
        this.isVisible = true;
        this.hasFocus = true;
        
        // Laravel Echo
        this.echoChannel = null;
        this.usingWebSockets = false;
        
        // Detección de página
        this.isCarteraPage = window.location.pathname.includes('/cartera/pedidos');
        
        // Elementos DOM
        this.tableContainer = this.isCarteraPage ? 
            document.querySelector('.table-scroll-container') : 
            document.querySelector('.table-scroll-container');
        
        this.init();
    }

    init() {
        console.log('🔄 [PedidosRealtime] Sistema con WebSockets inicializado');
        
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
        // Detectar actividad del usuario (mouse, teclado, scroll)
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'click', 'focus'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.onUserActivity();
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
            console.warn('⚠️ [PedidosRealtime] Laravel Echo no está disponible, usando solo polling');
            return;
        }

        // Obtener user ID desde meta tags
        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

        if (!userId) {
            console.warn('⚠️ [PedidosRealtime] User ID no encontrado, no se puede suscribir a canales');
            return;
        }

        try {
            console.log('🔌 [PedidosRealtime] Suscribiendo a canal privado con Laravel Echo');
            console.log('  - User ID:', userId);
            
            // Suscribir al canal privado usando Laravel Echo
            this.echoChannel = window.Echo.private(`pedidos.${userId}`)
                .listen('.PedidoActualizado', (event) => {
                    console.log('� [PedidosRealtime] Evento PedidoActualizado recibido via Echo:', event);
                    this.handlePedidoUpdate(event.pedido, 'pedido.actualizado', event.changedFields);
                })
                .error((error) => {
                    console.error('❌ [PedidosRealtime] Error en canal Echo:', error);
                    this.usingWebSockets = false;
                    this.showConnectionIndicator('Echo Error', 'error');
                });

            this.usingWebSockets = true;
            console.log('✅ [PedidosRealtime] Conexión Laravel Echo establecida');
            this.showConnectionIndicator('Echo', 'success');

        } catch (error) {
            console.error('❌ [PedidosRealtime] Error configurando Laravel Echo:', error);
            this.usingWebSockets = false;
        }
    }

    /**
     * Manejar actualización de pedido desde Echo
     */
    handlePedidoUpdate(pedido, action, changedFields) {
        console.log('🔄 [PedidosRealtime] Actualización de pedido por Echo:', pedido.id);
        
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
                console.log('🔄 [PedidosRealtime] Nuevo pedido en Cartera, recargando tabla');
                if (window.cargarPedidos) {
                    window.cargarPedidos();
                }
            } else {
                // Para Asesores, agregar nueva fila
                console.log('➕ [PedidosRealtime] Nuevo pedido por Echo:', pedido.id);
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

    onUserActivity() {
        // Reiniciar timeout de inactividad
        clearTimeout(this.userActivityTimeout);
        
        // Si está inactivo, reactivar
        if (!this.isRunning) {
            console.log('🔄 [PedidosRealtime] Reactivando por actividad del usuario');
            this.start();
        }
        
        // Marcar como activo por 5 minutos
        this.userActivityTimeout = setTimeout(() => {
            console.log('🔄 [PedidosRealtime] Usuario inactivo, pausando monitoreo');
            this.pause();
        }, 300000); // 5 minutos
    }

    adjustPollingInterval() {
        if (!this.isRunning) return;
        
        // Temporalmente desactivado para pruebas - siempre 10 segundos para pruebas rápidas
        let newInterval = 10000; // 10 segundos para pruebas
        
        console.log('🔄 [PedidosRealtime] 👀 Polling fijo a 10 segundos (pruebas rápidas)');
        
        this.checkInterval = newInterval;
    }

    start() {
        if (this.isRunning) {
            console.log('🔄 [PedidosRealtime] Ya está monitoreando');
            return;
        }
        
        console.log(`🔄 [PedidosRealtime] ✅ Iniciando monitoreo con polling (WebSockets desactivados)`);
        this.isRunning = true;
        
        // Iniciar sistema de polling como fallback
        this.startPollingFallback();
    }
    
    /**
     * Sistema de polling fallback cuando WebSockets fallan
     */
    startPollingFallback() {
        if (!this.isRunning) return;
        
        console.log('🔄 [PedidosRealtime] Iniciando polling fallback cada', this.checkInterval, 'ms');
        console.log('🔄 [PedidosRealtime] API URL:', this.getApiUrl());
        
        const checkForUpdates = async () => {
            // Temporalmente desactivado para pruebas - siempre ejecutar
            if (!this.isRunning) {
                console.log('🔄 [PedidosRealtime] Polling detenido - isRunning:', this.isRunning);
                return;
            }
            
            console.log('🔄 [PedidosRealtime] 🔍 Verificando actualizaciones...');
            
            try {
                const response = await fetch(this.getApiUrl(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                console.log('🔄 [PedidosRealtime] 📡 Respuesta recibida:', response.status);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('🔄 [PedidosRealtime] ❌ Error response:', errorText);
                    
                    // Intentar parsear como JSON para ver debug info
                    try {
                        const errorJson = JSON.parse(errorText);
                        console.error('🔄 [PedidosRealtime] 🐛 Debug info:', errorJson.debug);
                    } catch (e) {
                        // No es JSON, mostrar texto plano
                    }
                    return;
                }
                
                const data = await response.json();
                console.log('🔄 [PedidosRealtime] 📊 Datos recibidos:', data?.data?.length || 0, 'pedidos');
                
                if (data && data.data) {
                    await this.checkForChanges(data.data);
                }
            } catch (error) {
                console.error('❌ [PedidosRealtime] Error en polling:', error);
            }
            
            // Programar siguiente verificación
            if (this.isRunning) {
                console.log('🔄 [PedidosRealtime] ⏰ Siguiente verificación en', this.checkInterval, 'ms');
                setTimeout(checkForUpdates, this.checkInterval);
            }
        };
        
        // Iniciar primera verificación después de 2 segundos
        setTimeout(checkForUpdates, 2000);
    }

    pause() {
        if (!this.isRunning) return;
        
        console.log('🔄 [PedidosRealtime] ⏸️ Pausado por inactividad');
        this.isRunning = false;
    }

    stop() {
        if (!this.isRunning) return;
        
        console.log('🔄 [PedidosRealtime] ⏹️ Detenido');
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
     * Obtener estado del sistema
     */
    getStatus() {
        return {
            isRunning: this.isRunning,
            usingWebSockets: this.usingWebSockets,
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
        console.log('🔄 [PedidosRealtime] Método verificar() legacy - usando Laravel Echo');
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
        console.log('🔄 [PedidosRealtime] 🔍 Analizando', pedidosNuevos.length, 'pedidos para cambios');
        
        const hayCambios = this.detectarCambios(pedidosNuevos);
        
        console.log('🔄 [PedidosRealtime] 📊 ¿Hay cambios?', hayCambios);
        
        if (hayCambios) {
            console.log('🔄 [PedidosRealtime] 🔄 Cambios detectados, actualizando tabla');
            this.lastChangeTime = new Date();
            
            // Recargar la tabla completa
            if (typeof window.cargarPedidos === 'function') {
                console.log('🔄 [PedidosRealtime] 📞 Llamando a window.cargarPedidos()');
                await window.cargarPedidos();
            } else if (this.isCarteraPage && typeof window.cargarPedidosCartera === 'function') {
                console.log('🔄 [PedidosRealtime] 📞 Llamando a window.cargarPedidosCartera()');
                await window.cargarPedidosCartera();
            } else {
                console.log('🔄 [PedidosRealtime] ❌ No se encontró función para recargar tabla');
                console.log('🔄 [PedidosRealtime]   - window.cargarPedidos:', typeof window.cargarPedidos);
                console.log('🔄 [PedidosRealtime]   - window.cargarPedidosCartera:', typeof window.cargarPedidosCartera);
                console.log('🔄 [PedidosRealtime]   - isCarteraPage:', this.isCarteraPage);
            }
        } else {
            console.log('🔄 [PedidosRealtime] ✅ Sin cambios, tabla actualizada');
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
            console.log('📊 [PedidosRealtime] Cantidad de pedidos cambió:', this.pedidosAnterior.size, '->', pedidosNuevos.length);
            hayCambios = true;
        }
        
        // Verificar cambios en pedidos existentes
        for (const pedido of pedidosNuevos) {
            const anterior = this.pedidosAnterior.get(pedido.id);
            
            if (!anterior) {
                console.log('➕ [PedidosRealtime] Nuevo pedido:', pedido.id);
                hayCambios = true;
                continue;
            }
            
            // Comparar campos importantes
            if (anterior.estado !== pedido.estado) {
                console.log('🔄 [PedidosRealtime] Estado cambió (Pedido #' + pedido.id + '):', anterior.estado, '->', pedido.estado);
                hayCambios = true;
            }
            
            if (anterior.novedades !== pedido.novedades) {
                console.log('[PedidosRealtime] Novedades cambió (Pedido #' + pedido.id + ')');
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
            console.error('❌ [PedidosRealtime] No se encontró el contenedor de la tabla');
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

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.pedidosRealtimeRefresh = new PedidosRealtimeRefresh({
        checkInterval: 30000, // 30 segundos
        autoStart: true
    });
});
