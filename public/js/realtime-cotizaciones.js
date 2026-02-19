/**
 * Real-time updates for quotations using Laravel Echo
 * Handles status changes and new quotations in real-time
 */

// Log inmediato para verificar que el archivo se carga
console.log('[REALTIME-COT] === ARCHIVO CARGADO ===');

// Protección contra cargas múltiples
if (window.realtimeCotizacionesLoaded) {
    console.warn('[REALTIME-COT] ⚠️  El archivo ya fue cargado, evitando duplicación');
    // No usar return aquí, simplemente salir del bloque
} else {
    window.realtimeCotizacionesLoaded = true;

    // Sistema de retry para esperar a que Echo esté disponible
    let echoCheckAttempts = 0;
    const MAX_ATTEMPTS = 50; // 5 segundos máximo (100ms * 50)

    function checkAndInitialize() {
        echoCheckAttempts++;
        console.log(`[REALTIME-COT] Intento ${echoCheckAttempts}/${MAX_ATTEMPTS} - Verificando Echo...`);
        console.log('[REALTIME-COT] window.Echo disponible:', typeof window.Echo);
        console.log('[REALTIME-COT] window.waitForEcho disponible:', typeof window.waitForEcho);

        // Si Echo está disponible, inicializar
        if (typeof window.Echo !== 'undefined' && window.Echo) {
            console.log('[REALTIME-COT] ✅ Echo encontrado, inicializando...');
            initializeRealtimeCotizaciones();
            return;
        }

        // Si tenemos waitForEcho, usarlo
        if (typeof window.waitForEcho === 'function') {
            console.log('[REALTIME-COT] Usando window.waitForEcho...');
            window.waitForEcho(initializeRealtimeCotizaciones);
            return;
        }

        // Si no está disponible y no hemos llegado al máximo, reintentar
        if (echoCheckAttempts < MAX_ATTEMPTS) {
            console.log('[REALTIME-COT] Echo no disponible, reintentando en 100ms...');
            setTimeout(checkAndInitialize, 100);
        } else {
            console.error('[REALTIME-COT] ❌ Echo no disponible después de varios intentos');
        }
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkAndInitialize);
    } else {
        checkAndInitialize();
    }

    function initializeRealtimeCotizaciones() {
        console.log('[REALTIME-COT] === INICIALIZANDO SISTEMA REALTIME ===');
        console.log('[REALTIME-COT] Echo está disponible:', typeof window.Echo);

        // Cache temporal para evitar procesar el mismo evento dos veces
        // (suele pasar por estar suscritos a cotizaciones + cotizaciones.contador)
        const processedEstadoEventKeys = new Map();
        const PROCESSED_EVENT_TTL_MS = 4000;

        function cleanupProcessedEstadoEvents() {
            const now = Date.now();
            for (const [key, ts] of processedEstadoEventKeys.entries()) {
                if (now - ts > PROCESSED_EVENT_TTL_MS) {
                    processedEstadoEventKeys.delete(key);
                }
            }
        }

        function shouldProcessEstadoEvent(event) {
            cleanupProcessedEstadoEvents();
            const key = `${event?.cotizacion_id ?? ''}|${event?.nuevo_estado ?? ''}|${event?.estado_anterior ?? ''}|${event?.timestamp ?? ''}`;
            if (processedEstadoEventKeys.has(key)) {
                console.log('[REALTIME-COT] Evento estado.cambiado duplicado, ignorado:', key);
                return false;
            }
            processedEstadoEventKeys.set(key, Date.now());
            return true;
        }

        try {
            const connection = window.Echo?.connector?.pusher?.connection;
            if (connection?.state) {
                console.log('[REALTIME-COT] Estado conexión WS:', connection.state);
            }
            if (typeof connection?.bind === 'function') {
                connection.bind('connected', () => console.log('[REALTIME-COT] WS connected'));
                connection.bind('disconnected', () => console.log('[REALTIME-COT] WS disconnected'));
                connection.bind('error', (err) => console.error('[REALTIME-COT] WS error', err));
            }
        } catch (e) {
            console.warn('[REALTIME-COT] No se pudo enlazar eventos de conexión:', e);
        }

        // Get current user ID from meta tag or global variable
        const userId = document.querySelector('meta[name="user-id"]')?.content || window.userId;
        console.log('[REALTIME-COT] User ID:', userId);
        console.log('[REALTIME-COT] Path actual:', window.location.pathname);
        console.log('[REALTIME-COT] isOnContadorPage:', isOnContadorPage());

        // Listen to general quotations channel
        console.log('[REALTIME-COT] Verificando Echo antes de suscribirse...');
        
        // Verificar que Echo tenga el método channel
        console.log('[REALTIME-COT] 🔍 DIAGNÓSTICO COMPLETO DE ECHO:');
        console.log('[REALTIME-COT] window.Echo existe:', !!window.Echo);
        console.log('[REALTIME-COT] typeof window.Echo:', typeof window.Echo);
        console.log('[REALTIME-COT] window.Echo:', window.Echo);
        console.log('[REALTIME-COT] window.Echo.constructor:', window.Echo?.constructor?.name);
        console.log('[REALTIME-COT] window.Echo.channel:', window.Echo?.channel);
        console.log('[REALTIME-COT] typeof window.Echo.channel:', typeof window.Echo?.channel);
        console.log('[REALTIME-COT] Métodos disponibles en Echo:', Object.getOwnPropertyNames(window.Echo || {}));
        
        if (typeof window.Echo.channel !== 'function') {
            console.error('[REALTIME-COT] ❌ ERROR: window.Echo.channel no es una función');
            console.log('[REALTIME-COT] window.Echo:', window.Echo);
            console.log('[REALTIME-COT] typeof window.Echo:', typeof window.Echo);
            console.log('[REALTIME-COT] typeof window.Echo.channel:', typeof window.Echo.channel);
            
            // Intentar ver si hay otra propiedad que pueda ser el canal
            console.log('[REALTIME-COT] Buscando alternativas...');
            if (window.EchoInstance && typeof window.EchoInstance.channel === 'function') {
                console.log('[REALTIME-COT] ✅ Encontrado window.EchoInstance con channel');
                window.Echo = window.EchoInstance; // Usar EchoInstance
            } else if (window.Echo && typeof window.Echo.listen === 'function') {
                console.log('[REALTIME-COT] ✅ Encontrado Echo con método listen');
            } else {
                console.error('[REALTIME-COT] ❌ No se encontró ninguna alternativa válida');
                return; // Este return ahora está dentro de la función
            }
        }
    
    console.log('[REALTIME-COT] ✅ Echo.channel verificado, suscribiéndose a canal: cotizaciones');
    window.Echo.channel('cotizaciones')
        .listen('.cotizacion.creada', (event) => {
            console.log('[REALTIME-COT] Evento cotizacion.creada recibido en canal cotizaciones:', event);
            handleNuevaCotizacion(event);
        })
        .listen('.cotizacion.estado.cambiado', (event) => {
            console.log('[REALTIME-COT] Evento cotizacion.estado.cambiado recibido:', event);
            handleEstadoCambiado(event);
        })
        .listen('.cotizacion.aprobada', (event) => {
            console.log('[REALTIME-COT] Evento cotizacion.aprobada recibido:', event);
            handleCotizacionAprobada(event);
        })
        .subscribed(() => console.log('[REALTIME-COT] ✅ Subscrito a canal: cotizaciones'))
        .error((err) => console.error('[REALTIME-COT] ❌ Error canal cotizaciones:', err));

    // Listen to user-specific channel if user is logged in
    if (userId) {
        console.log(`[REALTIME-COT] Suscribiéndose a canal: cotizaciones.asesor.${userId}`);
        window.Echo.channel(`cotizaciones.asesor.${userId}`)
            .listen('.cotizacion.creada', (event) => {
                console.log('[REALTIME-COT] Evento recibido en canal asesor:', event);
                handleNuevaCotizacion(event);
            })
            .listen('.cotizacion.estado.cambiado', (event) => {
                console.log('[REALTIME-COT] Evento estado cambiado en canal asesor:', event);
                handleEstadoCambiado(event);
                mostrarNotificacion('Estado Actualizado', `Tu cotización ha cambiado a: ${event.nuevo_estado}`);
            })
            .listen('.cotizacion.aprobada', (event) => {
                console.log('[REALTIME-COT] Evento aprobada en canal asesor:', event);
                handleCotizacionAprobada(event);
                mostrarNotificacion('Cotización Aprobada', 'Tu cotización ha sido aprobada');
            });
    }

    // Listen to contador channel
    console.log('[REALTIME-COT] Suscribiéndose a canal: cotizaciones.contador');
    window.Echo.channel('cotizaciones.contador')
        .listen('.cotizacion.creada', (event) => {
            console.log('[REALTIME-COT] ****************************');
            console.log('[REALTIME-COT] Evento recibido en canal contador:', event);
            console.log('[REALTIME-COT] ****************************');
            handleNuevaCotizacion(event);
        })
        .listen('.cotizacion.estado.cambiado', (event) => {
            console.log('[REALTIME-COT] Evento estado cambiado en canal contador:', event);
            handleEstadoCambiado(event);
        })
        .subscribed(() => console.log('[REALTIME-COT] ✅ Subscrito a canal: cotizaciones.contador'))
        .error((err) => console.error('[REALTIME-COT] ❌ Error canal cotizaciones.contador:', err));

    console.log('[REALTIME-COT] Suscripciones a canales completadas');

    /**
     * Handle new quotation created
     */
    function handleNuevaCotizacion(event) {
        // Extract relevant data from event
        const { cotizacion_id, asesor_id, estado, cotizacion } = event;

        const effectiveId = cotizacion?.id || cotizacion_id;
        if (effectiveId && cotizacionYaExisteEnTabla(effectiveId)) {
            console.log('[REALTIME-COT] Cotización ya existe en tabla, evitando duplicado:', effectiveId);
            return;
        }

        console.log('[REALTIME-COT] Estado:', estado, 'ID:', cotizacion_id, 'OnContadorPage:', isOnContadorPage());

        // Si llega una cotización ENVIADA_CONTADOR, actualizar badge de pendientes en cualquier vista del módulo contador
        if (isOnContadorPage() && estado === 'ENVIADA_CONTADOR') {
            incrementarPendientesBadge();
        }

        // If we're on the contador PENDIENTES page and status is ENVIADA_CONTADOR, add the new quotation
        if (isOnContadorPendientesPage() && estado === 'ENVIADA_CONTADOR') {
            console.log('[REALTIME-COT] Agregando cotización a tabla...');
            agregarCotizacionAContador(cotizacion);

            // Mostrar notificación toast
            mostrarNotificacionToast('Nueva Cotización', `Cotización #${cotizacion.numero_cotizacion || cotizacion.id} recibida`, 'info');
        } else {
            console.log('[REALTIME-COT] No se agregó: isOnContadorPage=' + isOnContadorPage() + ', estado=' + estado);
        }
    }

    /**
     * Show toast notification in app
     */
    function mostrarNotificacionToast(titulo, mensaje, tipo = 'success') {
        const notifId = 'notificacionCotizaciones' + Date.now();
        const notif = document.createElement('div');
        notif.id = notifId;

        const colores = {
            success: { bg: '#10b981', icon: '#22c55e', shadow: 'rgba(16, 185, 129, 0.3)' },
            info: { bg: '#2563eb', icon: '#3b82f6', shadow: 'rgba(37, 99, 235, 0.25)' },
            warning: { bg: '#f59e0b', icon: '#fbbf24', shadow: 'rgba(245, 158, 11, 0.3)' },
            error: { bg: '#dc2626', icon: '#ef4444', shadow: 'rgba(220, 38, 38, 0.3)' },
        };
        const c = colores[tipo] || colores.success;

        const iconos = {
            success: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>`,
            info: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`,
            warning: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`,
            error: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`,
        };

        notif.style.cssText = `
            position: fixed; top: 24px; right: 24px;
            background: white; border-radius: 12px; padding: 16px 20px;
            box-shadow: 0 20px 25px -5px ${c.shadow}, 0 10px 10px -5px rgba(0,0,0,0.04);
            display: flex; align-items: center; gap: 14px;
            z-index: 10000; min-width: 300px; max-width: 400px;
            border-left: 4px solid ${c.icon};
            animation: notifSlideIn 0.3s ease-out;
            font-family: system-ui, -apple-system, sans-serif;
        `;

        notif.innerHTML = `
            <div style="
                width: 36px; height: 36px; border-radius: 10px;
                background: ${c.bg}; display: flex; align-items: center; justify-content: center;
                flex-shrink: 0;
            ">${iconos[tipo] || iconos.success}</div>
            <div style="flex: 1;">
                <p style="margin: 0; font-size: 14px; font-weight: 500; color: #0f172a;">${titulo}</p>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: #64748b;">${mensaje}</p>
            </div>
            <button id="btnCerrarNotifCot" style="
                background: none; border: none; padding: 4px; cursor: pointer;
                color: #94a3b8; transition: color 0.15s;
            ">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            <style>@keyframes notifSlideIn { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }</style>
        `;

        document.body.appendChild(notif);

        const cerrar = () => {
            notif.style.animation = 'notifSlideIn 0.2s ease-out reverse';
            setTimeout(() => notif.remove(), 200);
        };

        const btnCerrar = document.getElementById('btnCerrarNotifCot');
        if (btnCerrar) btnCerrar.addEventListener('click', cerrar);
        setTimeout(cerrar, 3000);
    }

    function cotizacionYaExisteEnTabla(cotizacionId) {
        const tablaBody = document.getElementById('tablaCotizacionesBody');
        if (!tablaBody) return false;
        return !!tablaBody.querySelector(`.table-row[data-cotizacion-id="${cotizacionId}"]`);
    }

    /**
     * Handle quotation status change
     */
    function handleEstadoCambiado(event) {
        if (!shouldProcessEstadoEvent(event)) {
            return;
        }
        const { cotizacion_id, nuevo_estado, estado_anterior, cotizacion } = event;

        // Badge Pendientes global: si entra/sale de ENVIADA_CONTADOR
        if (isOnContadorPage()) {
            if (nuevo_estado === 'ENVIADA_CONTADOR' && estado_anterior !== 'ENVIADA_CONTADOR') {
                incrementarPendientesBadge();
            }
            if (estado_anterior === 'ENVIADA_CONTADOR' && nuevo_estado !== 'ENVIADA_CONTADOR') {
                decrementarPendientesBadge();
            }
        }

        // Update the quotation row if it exists on the page
        actualizarFilaCotizacion(cotizacion_id, nuevo_estado, cotizacion);

        // If status changed to ENVIADA_CONTADOR and we're on contador PENDIENTES page, add to list
        if (nuevo_estado === 'ENVIADA_CONTADOR' && isOnContadorPendientesPage()) {
            agregarCotizacionAContador(cotizacion);
        }

        // If status changed from ENVIADA_CONTADOR, remove from contador pending list
        if (estado_anterior === 'ENVIADA_CONTADOR' && nuevo_estado !== 'ENVIADA_CONTADOR' && isOnContadorPendientesPage()) {
            removerCotizacionDeContador(cotizacion_id);
        }

        // Por revisar (EN_CORRECCION): actualizar badge y tabla en tiempo real
        if (nuevo_estado === 'EN_CORRECCION' && isOnContadorPage()) {
            agregarCotizacionAPorRevisar(cotizacion);
            actualizarBadgePorRevisarDesdeTabla();
        }
        if (estado_anterior === 'EN_CORRECCION' && nuevo_estado !== 'EN_CORRECCION' && isOnContadorPage()) {
            removerCotizacionDePorRevisar(cotizacion_id);
            actualizarBadgePorRevisarDesdeTabla();
        }

        // Aprobadas (APROBADA_POR_APROBADOR): actualizar badge y tabla en tiempo real
        if (nuevo_estado === 'APROBADA_POR_APROBADOR' && isOnContadorPage()) {
            agregarCotizacionAAprobadas(cotizacion);
            actualizarBadgeAprobadasDesdeTabla();
        }
        if (estado_anterior === 'APROBADA_POR_APROBADOR' && nuevo_estado !== 'APROBADA_POR_APROBADOR' && isOnContadorPage()) {
            removerCotizacionDeAprobadas(cotizacion_id);
            actualizarBadgeAprobadasDesdeTabla();
        }

        // Recalcular badge de pendientes SOLO en /contador/dashboard
        if (isOnContadorPendientesPage()) {
            actualizarContadores();
        }
    }

    /**
     * Handle quotation approved
     */
    function handleCotizacionAprobada(event) {
        const { cotizacion_id, nuevo_estado, cotizacion } = event;

        // Update the quotation row
        actualizarFilaCotizacion(cotizacion_id, nuevo_estado, cotizacion);

        // Update counters solo si estamos en pendientes
        if (isOnContadorPendientesPage()) {
            actualizarContadores();
        }
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
            // Recalcular badge una vez la fila desaparece del DOM
            actualizarContadores();
        }, 500);
    }

    function isOnContadorPorRevisarPage() {
        return window.location.pathname.includes('/contador/por-revisar');
    }

    function actualizarBadgePorRevisarDesdeTabla() {
        const badge = document.getElementById('cotizacionesPorRevisarCount');
        if (!badge) return;

        if (!isOnContadorPorRevisarPage()) {
            return;
        }

        const tablaBody = document.getElementById('tablaCotizacionesBody');
        if (!tablaBody) return;

        const filas = tablaBody.querySelectorAll('.table-row[data-cotizacion-id]');
        const count = filas.length;
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-flex' : 'none';
        badge.classList.add('badge-pulse');
        setTimeout(() => badge.classList.remove('badge-pulse'), 1000);
    }

    function incrementarPendientesBadge() {
        const badge = document.getElementById('cotizacionesPendientesCount');
        if (!badge) return;
        let count = parseInt(badge.textContent) || 0;
        count++;
        badge.textContent = count;
        badge.style.display = 'inline-flex';
        badge.classList.add('badge-pulse');
        setTimeout(() => badge.classList.remove('badge-pulse'), 1000);
    }

    function decrementarPendientesBadge() {
        const badge = document.getElementById('cotizacionesPendientesCount');
        if (!badge) return;
        let count = parseInt(badge.textContent) || 0;
        count = Math.max(0, count - 1);
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-flex' : 'none';
        badge.classList.add('badge-pulse');
        setTimeout(() => badge.classList.remove('badge-pulse'), 1000);
    }

    function incrementarBadgePorRevisar() {
        const badge = document.getElementById('cotizacionesPorRevisarCount');
        if (!badge) return;
        let count = parseInt(badge.textContent) || 0;
        count++;
        badge.textContent = count;
        badge.style.display = 'inline-flex';
        badge.classList.add('badge-pulse');
        setTimeout(() => badge.classList.remove('badge-pulse'), 1000);
    }

    function decrementarBadgePorRevisar() {
        const badge = document.getElementById('cotizacionesPorRevisarCount');
        if (!badge) return;
        let count = parseInt(badge.textContent) || 0;
        count = Math.max(0, count - 1);
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-flex' : 'none';
        badge.classList.add('badge-pulse');
        setTimeout(() => badge.classList.remove('badge-pulse'), 1000);
    }

    function agregarCotizacionAPorRevisar(cotizacion) {
        if (!isOnContadorPorRevisarPage()) {
            incrementarBadgePorRevisar();
            return;
        }

        const tableBody = document.getElementById('tablaCotizacionesBody');
        if (!tableBody) return;

        const effectiveId = cotizacion?.id;
        if (effectiveId && tableBody.querySelector(`.table-row[data-cotizacion-id="${effectiveId}"]`)) {
            return;
        }

        const newRow = crearFilaCotizacionPorRevisar(cotizacion);
        newRow.classList.add('row-new');
        tableBody.insertBefore(newRow, tableBody.firstChild);
        setTimeout(() => newRow.classList.remove('row-new'), 2000);

        actualizarBadgePorRevisarDesdeTabla();
    }

    function removerCotizacionDePorRevisar(cotizacionId) {
        if (!isOnContadorPorRevisarPage()) {
            decrementarBadgePorRevisar();
            return;
        }

        const row = document.querySelector(`#tablaCotizacionesBody .table-row[data-cotizacion-id="${cotizacionId}"]`);
        if (!row) {
            decrementarBadgePorRevisar();
            return;
        }
        row.classList.add('row-removed');
        setTimeout(() => {
            row.remove();
            actualizarBadgePorRevisarDesdeTabla();
        }, 500);
    }

    function crearFilaCotizacionPorRevisar(cotizacion) {
        const row = document.createElement('div');
        row.className = 'table-row';
        row.setAttribute('data-cotizacion-id', cotizacion.id);
        row.setAttribute('data-numero', cotizacion.numero_cotizacion || 'N/A');
        row.setAttribute('data-cliente', cotizacion.cliente?.nombre || cotizacion.cliente || '');
        row.setAttribute('data-asesora', cotizacion.asesora || cotizacion.usuario?.name || '');
        row.setAttribute('data-fecha', cotizacion.created_at ? new Date(cotizacion.created_at).toLocaleDateString('es-CO') : '');
        row.setAttribute('data-estado', cotizacion.estado || '');
        row.setAttribute('data-novedades', cotizacion.novedades || '-');

        const cliente = cotizacion.cliente?.nombre || cotizacion.nombre_cliente || cotizacion.cliente || 'Sin cliente';
        const asesora = cotizacion.asesora || cotizacion.usuario?.name || 'Sin asesora';
        const fecha = cotizacion.created_at ? new Date(cotizacion.created_at).toLocaleString('es-CO') : '-';
        const novedades = cotizacion.novedades || '-';

        const estado = cotizacion.estado || 'EN_CORRECCION';
        const estadoColors = getEstadoColor(estado);

        row.innerHTML = `
            <!-- Acciones -->
            <div class="table-cell acciones-column" style="flex: 0 0 160px; justify-content: flex-start; position: relative;">
                <div class="actions-group">
                    <button class="action-view-btn" title="Ver opciones" data-cotizacion-id="${cotizacion.id}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <div class="action-menu" data-cotizacion-id="${cotizacion.id}">
                        <a href="#" class="action-menu-item" data-action="cotizacion" onclick="openCotizacionModal(${cotizacion.id}); return false;">
                            <i class="fas fa-file-alt"></i>
                            <span>Ver Cotización</span>
                        </a>
                        <a href="#" class="action-menu-item" data-action="costos" onclick="abrirModalVisorCostos(${cotizacion.id}, '${String(cliente).replace(/'/g, "\\'")}'); return false;">
                            <i class="fas fa-chart-bar"></i>
                            <span>Ver Costos</span>
                        </a>
                        <a href="/contador/cotizacion/${cotizacion.id}/pdf?tipo=prenda" class="action-menu-item" data-action="pdf" target="_blank">
                            <i class="fas fa-file-pdf"></i>
                            <span>Ver PDF</span>
                        </a>
                    </div>
                    <button class="btn-action btn-edit btn-editar-costos" data-cotizacion-id="${cotizacion.id}" data-cliente="${String(cliente).replace(/"/g, '&quot;')}" title="Editar Costos">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-action btn-success" onclick="aprobarCotizacion(${cotizacion.id})" title="Aprobar Cotización">
                        <i class="fas fa-check-circle"></i>
                    </button>
                </div>
            </div>

            <!-- Estado -->
            <div class="table-cell" style="flex: 0 0 150px;" data-estado="${estado}">
                <div class="cell-content" style="justify-content: center;">
                    <span style="background: ${estadoColors.bg}; color: ${estadoColors.color}; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                        ${String(estado).replace(/_/g, ' ')}
                    </span>
                </div>
            </div>

            <!-- Número -->
            <div class="table-cell" style="flex: 0 0 140px;" data-numero="${cotizacion.numero_cotizacion || 'N/A'}">
                <div class="cell-content" style="justify-content: center;">
                    <span style="font-weight: 600;">${cotizacion.numero_cotizacion || 'Por asignar'}</span>
                </div>
            </div>

            <!-- Fecha -->
            <div class="table-cell" style="flex: 0 0 180px;" data-fecha="${fecha}">
                <div class="cell-content" style="justify-content: center;">
                    <span>${fecha}</span>
                </div>
            </div>

            <!-- Cliente -->
            <div class="table-cell" style="flex: 0 0 200px;" data-cliente="${String(cliente).replace(/"/g, '&quot;')}">
                <div class="cell-content" style="justify-content: center;">
                    <span>${cliente}</span>
                </div>
            </div>

            <!-- Asesora -->
            <div class="table-cell" style="flex: 0 0 150px;" data-asesora="${String(asesora).replace(/"/g, '&quot;')}">
                <div class="cell-content" style="justify-content: center;">
                    <span>${asesora}</span>
                </div>
            </div>

            <!-- Novedades -->
            <div class="table-cell" style="flex: 0 0 180px;">
                <div class="cell-content" style="justify-content: center;">
                    <span style="font-size: 0.85rem;">${novedades}</span>
                </div>
            </div>
        `;

        return row;
    }

    function isOnContadorAprobadasPage() {
        return window.location.pathname.includes('/contador/aprobadas');
    }

    function actualizarBadgeAprobadasDesdeTabla() {
        const badge = document.getElementById('cotizacionesAprobadasCount');
        if (!badge) return;

        if (!isOnContadorAprobadasPage()) {
            return;
        }

        const tablaBody = document.getElementById('tablaCotizacionesBody');
        if (!tablaBody) return;

        const filas = tablaBody.querySelectorAll('.table-row[data-cotizacion-id]');
        const count = filas.length;
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-flex' : 'none';
        badge.classList.add('badge-pulse');
        setTimeout(() => badge.classList.remove('badge-pulse'), 1000);
    }

    function incrementarBadgeAprobadas() {
        const badge = document.getElementById('cotizacionesAprobadasCount');
        if (!badge) return;
        let count = parseInt(badge.textContent) || 0;
        count++;
        badge.textContent = count;
        badge.style.display = 'inline-flex';
        badge.classList.add('badge-pulse');
        setTimeout(() => badge.classList.remove('badge-pulse'), 1000);
    }

    function decrementarBadgeAprobadas() {
        const badge = document.getElementById('cotizacionesAprobadasCount');
        if (!badge) return;
        let count = parseInt(badge.textContent) || 0;
        count = Math.max(0, count - 1);
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-flex' : 'none';
        badge.classList.add('badge-pulse');
        setTimeout(() => badge.classList.remove('badge-pulse'), 1000);
    }

    function agregarCotizacionAAprobadas(cotizacion) {
        if (!isOnContadorAprobadasPage()) {
            incrementarBadgeAprobadas();
            return;
        }

        const tableBody = document.getElementById('tablaCotizacionesBody');
        if (!tableBody) return;

        const effectiveId = cotizacion?.id;
        if (effectiveId && tableBody.querySelector(`.table-row[data-cotizacion-id="${effectiveId}"]`)) {
            return;
        }

        const newRow = crearFilaCotizacion(cotizacion);
        newRow.classList.add('row-new');
        tableBody.insertBefore(newRow, tableBody.firstChild);
        setTimeout(() => newRow.classList.remove('row-new'), 2000);

        actualizarBadgeAprobadasDesdeTabla();
    }

    function removerCotizacionDeAprobadas(cotizacionId) {
        if (!isOnContadorAprobadasPage()) {
            decrementarBadgeAprobadas();
            return;
        }

        const row = document.querySelector(`#tablaCotizacionesBody .table-row[data-cotizacion-id="${cotizacionId}"]`);
        if (!row) {
            decrementarBadgeAprobadas();
            return;
        }
        row.classList.add('row-removed');
        setTimeout(() => {
            row.remove();
            actualizarBadgeAprobadasDesdeTabla();
        }, 500);
    }

    /**
     * Create quotation row HTML - COMPLETE VERSION matching contador table structure
     */
    function crearFilaCotizacion(cotizacion) {
        const row = document.createElement('div');
        row.className = 'table-row';
        row.setAttribute('data-cotizacion-id', cotizacion.id);
        row.setAttribute('data-numero', cotizacion.numero_cotizacion || 'N/A');
        row.setAttribute('data-cliente', cotizacion.cliente?.nombre || cotizacion.cliente || '');
        row.setAttribute('data-asesora', cotizacion.asesora || cotizacion.usuario?.name || '');
        row.setAttribute('data-fecha', cotizacion.created_at ? new Date(cotizacion.created_at).toLocaleDateString('es-ES') : '');
        row.setAttribute('data-estado', cotizacion.estado);
        row.setAttribute('data-novedades', cotizacion.novedades || '-');

        const estadoColors = getEstadoColor(cotizacion.estado);
        const fecha = cotizacion.created_at
            ? new Date(cotizacion.created_at).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
              new Date(cotizacion.created_at).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
            : '-';
        const cliente = cotizacion.cliente?.nombre || cotizacion.cliente || '-';
        const asesora = cotizacion.asesora || cotizacion.usuario?.name || '-';
        const novedades = cotizacion.novedades || '-';
        const numeroCotizacion = cotizacion.numero_cotizacion || 'Por asignar';

        row.innerHTML = `
            <!-- Acciones -->
            <div class="table-cell acciones-column" style="flex: 0 0 150px; justify-content: center; position: relative; display: flex; gap: 0.5rem;">
                <button class="action-view-btn" title="Ver opciones" data-cotizacion-id="${cotizacion.id}">
                    <i class="fas fa-eye"></i>
                </button>
                <div class="action-menu" data-cotizacion-id="${cotizacion.id}">
                    <a href="#" class="action-menu-item" data-action="cotizacion" onclick="openCotizacionModal(${cotizacion.id}); return false;">
                        <i class="fas fa-file-alt"></i>
                        <span>Ver Cotización</span>
                    </a>
                    <a href="#" class="action-menu-item" data-action="costos" onclick="abrirModalVisorCostos(${cotizacion.id}, '${cliente.replace(/'/g, "\\'")}'); return false;">
                        <i class="fas fa-chart-bar"></i>
                        <span>Ver Costos</span>
                    </a>
                    <a href="/contador/cotizacion/${cotizacion.id}/pdf?tipo=prenda" class="action-menu-item" data-action="pdf" target="_blank">
                        <i class="fas fa-file-pdf"></i>
                        <span>Ver PDF</span>
                    </a>
                </div>
                <button class="btn-action btn-edit btn-editar-costos" data-cotizacion-id="${cotizacion.id}" data-cliente="${cliente.replace(/"/g, '&quot;')}" title="Editar Costos">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-action btn-success" onclick="aprobarCotizacionEnLinea(${cotizacion.id}, '${String(cotizacion.estado || '').replace(/'/g, "\\'")}')" title="Aprobar Cotización">
                    <i class="fas fa-check-circle"></i>
                </button>
            </div>

            <!-- Estado -->
            <div class="table-cell" style="flex: 0 0 150px;" data-estado="${cotizacion.estado}">
                <div class="cell-content" style="justify-content: center;">
                    <span style="background: ${estadoColors.bg}; color: ${estadoColors.color}; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                        ${cotizacion.estado.replace(/_/g, ' ')}
                    </span>
                </div>
            </div>

            <!-- Número -->
            <div class="table-cell" style="flex: 0 0 140px;" data-numero="${numeroCotizacion}">
                <div class="cell-content" style="justify-content: center;">
                    <span style="font-weight: 600;">${numeroCotizacion}</span>
                </div>
            </div>

            <!-- Fecha -->
            <div class="table-cell" style="flex: 0 0 180px;" data-fecha="${fecha}">
                <div class="cell-content" style="justify-content: center;">
                    <span>${fecha}</span>
                </div>
            </div>

            <!-- Cliente -->
            <div class="table-cell" style="flex: 0 0 200px;" data-cliente="${cliente}">
                <div class="cell-content" style="justify-content: center;">
                    <span>${cliente}</span>
                </div>
            </div>

            <!-- Asesora -->
            <div class="table-cell" style="flex: 0 0 150px;" data-asesora="${asesora}">
                <div class="cell-content" style="justify-content: center;">
                    <span>${asesora}</span>
                </div>
            </div>

            <!-- Novedades -->
            <div class="table-cell" style="flex: 0 0 180px;">
                <div class="cell-content" style="justify-content: center;">
                    <span style="font-size: 0.85rem;">${novedades}</span>
                </div>
            </div>
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
     * Update counters - ACTUALIZADO para incluir badge en sidebar
     * Esta función actualiza el badge basándose en el conteo de la tabla
     */
    function actualizarContadores() {
        const badge = document.getElementById('cotizacionesPendientesCount');
        if (!badge) return;

        // IMPORTANTE: en otras vistas (/contador/por-revisar, /contador/aprobadas) también existe
        // #tablaCotizacionesBody pero NO corresponde a Pendientes. Solo recalcular en /contador/dashboard.
        if (!isOnContadorPendientesPage()) {
            return;
        }

        const tablaBody = document.getElementById('tablaCotizacionesBody');
        if (tablaBody) {
            const filas = tablaBody.querySelectorAll('.table-row[data-cotizacion-id]');
            const count = filas.length;
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-flex' : 'none';

            // Animación
            badge.classList.add('badge-pulse');
            setTimeout(() => badge.classList.remove('badge-pulse'), 1000);
        }
    }

    /**
     * Incrementar contador de cotizaciones pendientes
     * Se llama cuando llega una nueva cotización via WebSocket
     */
    function incrementarContador() {
        const badge = document.getElementById('cotizacionesPendientesCount');
        if (!badge) return;

        if (!isOnContadorPendientesPage()) {
            return;
        }

        let count = parseInt(badge.textContent) || 0;
        count++;
        badge.textContent = count;
        badge.style.display = 'inline-flex';

        // Animación
        badge.classList.add('badge-pulse');
        setTimeout(() => badge.classList.remove('badge-pulse'), 1000);
    }

    /**
     * Add CSS animation for badge pulse
     */
    function addBadgePulseStyle() {
        if (!document.getElementById('badge-pulse-style')) {
            const style = document.createElement('style');
            style.id = 'badge-pulse-style';
            style.textContent = `
                @keyframes badgePulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.2); }
                    100% { transform: scale(1); }
                }
                .badge-pulse {
                    animation: badgePulse 0.5s ease-in-out;
                }
            `;
            document.head.appendChild(style);
        }
    }

    // Initialize badge styles on load
    addBadgePulseStyle();

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

        // Also show in-app toast (estilo unificado)
        mostrarNotificacionToast(titulo, mensaje, 'info');
    }

    /**
     * Show in-app notification
     */
    function mostrarNotificacionEnApp(titulo, mensaje) {
        return;
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

    function isOnContadorPendientesPage() {
        return window.location.pathname.includes('/contador/dashboard');
    }

    } // Cierra la función initializeRealtimeCotizaciones

} // Cierra el bloque else principal

// El sistema ya está inicializado dentro del bloque else anterior
// No se necesita código adicional aquí
