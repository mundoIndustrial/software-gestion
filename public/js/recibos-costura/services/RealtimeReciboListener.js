/**
 * RealtimeReciboListener
 * Escucha eventos en tiempo real de recibos aprobados a través de Echo/WebSocket
 * 
 * Responsabilidades:
 * - Conectar al canal de WebSocket 'recibos-costura'
 * - Escuchar evento 'recibo.aprobado'
 * - Mostrar notificación visual cuando se aprueba un recibo
 * - Recargar dinámicamente la tabla con nuevos datos
 * - Reinicializar event listeners en filas actualizadas
 * 
 * @class RealtimeReciboListener
 * @example
 * const listener = RealtimeReciboListener.getInstance();
 * listener.initialize(); // Iniciar listener
 */

class RealtimeReciboListener {
    constructor() {
        this.isInitialized = false;
        this.channel = null;
    }

    /**
     * Obtener instancia singleton del servicio
     * @static
     * @returns {RealtimeReciboListener} Instancia única
     */
    static getInstance() {
        if (!window.realtimeReciboListenerInstance) {
            window.realtimeReciboListenerInstance = new RealtimeReciboListener();
        }
        return window.realtimeReciboListenerInstance;
    }

    /**
     * Inicializar el listener de tiempo real
     * Espera a que Echo esté disponible antes de conectar
     * @public
     */
    initialize() {
        if (this.isInitialized) {
            console.warn('[🔴 RealtimeReciboListener] Ya está inicializado');
            return;
        }

        console.log('[🔴 RealtimeReciboListener] Inicializando listener en tiempo real...');

        // Esperar a que Echo esté listo
        window.waitForEcho(() => {
            try {
                console.log('[🔴 RealtimeReciboListener] Echo está listo, conectando al canal...');

                // Conectar al canal y escuchar el evento
                this.channel = window.EchoInstance.channel('recibos-costura')
                    .listen('recibo.aprobado', (data) => {
                        console.log('[🔴 RealtimeReciboListener] ¡Evento recibido en tiempo real!', data);
                        this._handleReciboAprobado(data);
                    });

                this.isInitialized = true;
                console.log('[ RealtimeReciboListener] Listener configurado correctamente');

            } catch (error) {
                console.error('[ RealtimeReciboListener] Error al configurar el listener:', error);
            }
        });
    }

    /**
     * PRIVADO: Procesar evento de recibo aprobado
     * @private
     * @param {Object} data - Datos del recibo aprobado
     */
    _handleReciboAprobado(data) {
        // Mostrar notificación visual
        this._showNotification(data);

        // Recargar la tabla dinámicamente
        this._reloadTable(data);
    }

    /**
     * PRIVADO: Mostrar notificación visual cuando se aprueba un recibo
     * @private
     * @param {Object} data - Datos del recibo (consecutivo, cliente, area)
     */
    _showNotification(data) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            z-index: 10000001;
            font-weight: 600;
            animation: slideInRight 0.3s ease-out;
            max-width: 400px;
            word-wrap: break-word;
        `;

        // Crear contenido con datos del recibo
        const contenido = `
            <div style="margin-bottom: 8px;">
                 <strong>Recibo Aprobado</strong>
            </div>
            <div style="font-size: 13px; opacity: 0.9;">
                <div> Recibo #${data.consecutivo}</div>
                <div>👤 Cliente: ${data.cliente || 'N/A'}</div>
                <div> Área: ${data.area || 'N/A'}</div>
            </div>
        `;

        notification.innerHTML = contenido;
        document.body.appendChild(notification);

        // Asegurar que exista la animación CSS
        this._ensureAnimationStyles();

        // Eliminar la notificación después de 5 segundos
        setTimeout(() => {
            notification.style.animation = 'slideInRight 0.3s ease-out reverse';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }

    /**
     * PRIVADO: Recargar la tabla dinámicamente cuando se aprueba un recibo
     * @private
     * @param {Object} data - Datos del recibo aprobado
     */
    _reloadTable(data) {
        try {
            const pathname = window.location.pathname;
            
            // Hacer solicitud AJAX para obtener los recibos actualizados
            fetch(pathname + '?ajax=1', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.recibos && result.recibos.html) {
                    console.log('[🔴 RealtimeReciboListener] HTML recibido, actualizando tabla...');

                    // Actualizar solo el tbody
                    const tbody = document.getElementById('tablaRecibosBody');
                    if (tbody) {
                        tbody.innerHTML = result.recibos.html;
                        console.log('[ RealtimeReciboListener] Tabla actualizada correctamente');

                        // Reinicializar event listeners en las nuevas filas
                        this._reinitializeTableListeners();
                    } else {
                        console.warn('[ RealtimeReciboListener] Element tablaRecibosBody no encontrado');
                    }
                } else {
                    console.warn('[ RealtimeReciboListener] Respuesta sin HTML de recibos');
                }
            })
            .catch(error => {
                console.error('[ RealtimeReciboListener] Error al recargar tabla:', error);
                
                // Como fallback, recargar toda la página después de 3 segundos
                setTimeout(() => {
                    console.log('[ RealtimeReciboListener] Recargando página como fallback...');
                    window.location.reload();
                }, 3000);
            });
        } catch (error) {
            console.error('[ RealtimeReciboListener] Error en _reloadTable:', error);
        }
    }

    /**
     * PRIVADO: Reinicializar event listeners en las filas actualizado de la tabla
     * @private
     */
    _reinitializeTableListeners() {
        // Llamar función del módulo si existe (para reinitializar dropdowns, etc.)
        if (typeof reinitializeTableListeners === 'function') {
            reinitializeTableListeners();
        } else {
            console.warn('[ RealtimeReciboListener] reinitializeTableListeners no disponible');
        }
    }

    /**
     * PRIVADO: Asegurar que la animación CSS existe
     * Añade los estilos de animación si aún no existen en el documento
     * @private
     */
    _ensureAnimationStyles() {
        if (!document.getElementById('slideInRightStyle')) {
            const style = document.createElement('style');
            style.id = 'slideInRightStyle';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }

    /**
     * Detener el listener
     * Cierra la conexión al canal de WebSocket
     * @public
     */
    stop() {
        if (this.channel) {
            this.channel.stopListening('recibo.aprobado');
            this.isInitialized = false;
            console.log('[🔴 RealtimeReciboListener] ⏸️ Listener detenido');
        }
    }
}
