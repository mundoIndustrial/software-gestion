/**
 * Real-time updates for quotations using Laravel Echo
 * Handles status changes and new quotations in real-time
 */

(function() {
    'use strict';

    // Check if Echo is available
    if (typeof window.Echo === 'undefined') {

        return;
    }

    // Get current user ID from meta tag or global variable
    const userId = document.querySelector('meta[name="user-id"]')?.content || window.userId;
    


    // Listen to general quotations channel
    window.Echo.channel('cotizaciones')
        .listen('.cotizacion.creada', (event) => {

            handleNuevaCotizacion(event);
        })
        .listen('.cotizacion.estado.cambiado', (event) => {

            handleEstadoCambiado(event);
        })
        .listen('.cotizacion.aprobada', (event) => {

            handleCotizacionAprobada(event);
        });

    // Listen to user-specific channel if user is logged in
    if (userId) {
        window.Echo.channel(`cotizaciones.asesor.${userId}`)
            .listen('.cotizacion.creada', (event) => {

                handleNuevaCotizacion(event);
            })
            .listen('.cotizacion.estado.cambiado', (event) => {

                handleEstadoCambiado(event);
                mostrarNotificacion('Estado Actualizado', `Tu cotización ha cambiado a: ${event.nuevo_estado}`);
            })
            .listen('.cotizacion.aprobada', (event) => {

                handleCotizacionAprobada(event);
                mostrarNotificacion('Cotización Aprobada', 'Tu cotización ha sido aprobada');
            });
    }

    // Listen to contador channel
    window.Echo.channel('cotizaciones.contador')
        .listen('.cotizacion.creada', (event) => {

            handleNuevaCotizacion(event);
            if (event.estado === 'ENVIADA_CONTADOR') {
                mostrarNotificacion('Nueva Cotización', 'Hay una nueva cotización para revisar');
            }
        })
        .listen('.cotizacion.estado.cambiado', (event) => {

            handleEstadoCambiado(event);
        });

    /**
     * Handle new quotation created
     */
    function handleNuevaCotizacion(event) {
        const { cotizacion_id, estado, cotizacion } = event;

        // If we're on the quotations list page, add the new quotation
        if (isOnCotizacionesPage()) {
            agregarNuevaCotizacionALista(cotizacion);
        }

        // Update counters
        actualizarContadores();
    }

    /**
     * Handle quotation status change
     */
    function handleEstadoCambiado(event) {
        const { cotizacion_id, nuevo_estado, estado_anterior, cotizacion } = event;

        // Update the quotation row if it exists on the page
        actualizarFilaCotizacion(cotizacion_id, nuevo_estado, cotizacion);

        // Update counters
        actualizarContadores();

        // If status changed to ENVIADA_CONTADOR and we're on contador page, add to list
        if (nuevo_estado === 'ENVIADA_CONTADOR' && isOnContadorPage()) {
            agregarCotizacionAContador(cotizacion);
        }

        // If status changed from ENVIADA_CONTADOR, remove from contador pending list
        if (estado_anterior === 'ENVIADA_CONTADOR' && nuevo_estado !== 'ENVIADA_CONTADOR' && isOnContadorPage()) {
            removerCotizacionDeContador(cotizacion_id);
        }
    }

    /**
     * Handle quotation approved
     */
    function handleCotizacionAprobada(event) {
        const { cotizacion_id, nuevo_estado, cotizacion } = event;

        // Update the quotation row
        actualizarFilaCotizacion(cotizacion_id, nuevo_estado, cotizacion);

        // Update counters
        actualizarContadores();
    }

    /**
     * Update quotation row in the table
     */
    function actualizarFilaCotizacion(cotizacionId, nuevoEstado, cotizacion) {
        const row = document.querySelector(`[data-cotizacion-id="${cotizacionId}"]`);
        
        if (!row) {

            return;
        }



        // Update status badge
        const estadoCell = row.querySelector('[data-estado]');
        if (estadoCell) {
            estadoCell.setAttribute('data-estado', nuevoEstado);
            const badge = estadoCell.querySelector('span');
            if (badge) {
                badge.textContent = nuevoEstado.replace(/_/g, ' ');
                badge.style.background = getEstadoColor(nuevoEstado).bg;
                badge.style.color = getEstadoColor(nuevoEstado).color;
            }
        }

        // Add animation to highlight the change
        row.classList.add('row-updated');
        setTimeout(() => {
            row.classList.remove('row-updated');
        }, 2000);
    }

    /**
     * Add new quotation to the list
     */
    function agregarNuevaCotizacionALista(cotizacion) {
        const tableBody = document.querySelector('#tablaCotizacionesBody, .table-body');
        
        if (!tableBody) {

            return;
        }



        // Create new row (you'll need to adapt this to your table structure)
        const newRow = crearFilaCotizacion(cotizacion);
        
        // Add to top of table with animation
        newRow.classList.add('row-new');
        tableBody.insertBefore(newRow, tableBody.firstChild);
        
        setTimeout(() => {
            newRow.classList.remove('row-new');
        }, 2000);
    }

    /**
     * Add quotation to contador pending list
     */
    function agregarCotizacionAContador(cotizacion) {
        const tableBody = document.querySelector('#tablaCotizacionesBody');
        
        if (!tableBody) {

            return;
        }



        const newRow = crearFilaCotizacion(cotizacion);
        newRow.classList.add('row-new');
        tableBody.insertBefore(newRow, tableBody.firstChild);
        
        setTimeout(() => {
            newRow.classList.remove('row-new');
        }, 2000);
    }

    /**
     * Remove quotation from contador pending list
     */
    function removerCotizacionDeContador(cotizacionId) {
        const row = document.querySelector(`[data-cotizacion-id="${cotizacionId}"]`);
        
        if (!row) {
            return;
        }



        row.classList.add('row-removed');
        setTimeout(() => {
            row.remove();
        }, 500);
    }

    /**
     * Create quotation row HTML
     */
    function crearFilaCotizacion(cotizacion) {
        const row = document.createElement('div');
        row.className = 'table-row';
        row.setAttribute('data-cotizacion-id', cotizacion.id);
        row.setAttribute('data-numero', cotizacion.numero_cotizacion || 'N/A');
        row.setAttribute('data-estado', cotizacion.estado);
        
        // You'll need to adapt this HTML to match your table structure
        row.innerHTML = `
            <div class="table-cell" style="flex: 0 0 150px;">
                <!-- Actions buttons -->
            </div>
            <div class="table-cell" style="flex: 0 0 150px;" data-estado="${cotizacion.estado}">
                <div class="cell-content" style="justify-content: center;">
                    <span style="background: ${getEstadoColor(cotizacion.estado).bg}; color: ${getEstadoColor(cotizacion.estado).color}; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                        ${cotizacion.estado.replace(/_/g, ' ')}
                    </span>
                </div>
            </div>
            <!-- Add more cells as needed -->
        `;
        
        return row;
    }

    /**
     * Get status color
     */
    function getEstadoColor(estado) {
        const colors = {
            'BORRADOR': { bg: '#e5e7eb', color: '#374151' },
            'ENVIADA_CONTADOR': { bg: '#fff3cd', color: '#856404' },
            'APROBADA_CONTADOR': { bg: '#d4edda', color: '#155724' },
            'EN_CORRECCION': { bg: '#f8d7da', color: '#721c24' },
            'APROBADA_COTIZACIONES': { bg: '#d1fae5', color: '#065f46' },
            'APROBADO_PARA_PEDIDO': { bg: '#ccfbf1', color: '#115e59' },
            'CONVERTIDA_PEDIDO': { bg: '#e3f2fd', color: '#1e40af' },
            'FINALIZADA': { bg: '#d1fae5', color: '#065f46' },
        };
        return colors[estado] || { bg: '#e3f2fd', color: '#1e40af' };
    }

    /**
     * Update counters
     */
    function actualizarContadores() {
        // Reload counters or update them via AJAX

        
        // You can implement a fetch to get updated counts
        // or increment/decrement based on the event
    }

    /**
     * Show notification
     */
    function mostrarNotificacion(titulo, mensaje) {
        // Check if browser supports notifications
        if (!('Notification' in window)) {

            return;
        }

        // Check notification permission
        if (Notification.permission === 'granted') {
            new Notification(titulo, {
                body: mensaje,
                icon: '/images/logo.png',
                badge: '/images/badge.png'
            });
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    new Notification(titulo, {
                        body: mensaje,
                        icon: '/images/logo.png',
                        badge: '/images/badge.png'
                    });
                }
            });
        }

        // Also show in-app notification
        mostrarNotificacionEnApp(titulo, mensaje);
    }

    /**
     * Show in-app notification
     */
    function mostrarNotificacionEnApp(titulo, mensaje) {
        const notification = document.createElement('div');
        notification.className = 'realtime-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <strong>${titulo}</strong>
                <p>${mensaje}</p>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }

    /**
     * Check if we're on quotations page
     */
    function isOnCotizacionesPage() {
        return window.location.pathname.includes('/cotizaciones') || 
               window.location.pathname.includes('/asesores/cotizaciones');
    }

    /**
     * Check if we're on contador page
     */
    function isOnContadorPage() {
        return window.location.pathname.includes('/contador');
    }


})();
