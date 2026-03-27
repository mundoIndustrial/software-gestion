/**
 * Gestión de notificaciones en el sidebar
 * Actualiza badges de contadores para diferentes roles
 */

/**
 * Actualizar contador de cotizaciones pendientes para aprobador
 */
function updateCotizacionesPendientesAprobador() {
    const badgeElement = document.getElementById('cotizacionesPendientesAprobadorCount');
    
    if (!badgeElement) return;
    
    fetch('/pendientes-count')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.count > 0) {
                badgeElement.textContent = data.count;
                badgeElement.style.display = 'inline-flex';

            } else {
                badgeElement.style.display = 'none';

            }
        })
        .catch(error => {

        });
}

/**
 * Inicializar actualizaciones de notificaciones
 */
let sidebarNotificationsRealtimeBound = false;

function setupSidebarNotificationsRealtime() {
    if (sidebarNotificationsRealtimeBound) {
        return;
    }

    if (!window.shared?.isReady || typeof window.waitForEcho !== 'function') {
        setTimeout(setupSidebarNotificationsRealtime, 300);
        return;
    }

    const refreshPendingCount = () => {
        updateCotizacionesPendientesAprobador();
    };

    window.waitForEcho(() => {
        try {
            const ws = window.shared?.websocket;
            if (!ws) {
                setTimeout(setupSidebarNotificationsRealtime, 500);
                return;
            }

            ws.subscribe('cotizaciones', '.cotizacion.creada', refreshPendingCount);
            ws.subscribe('cotizaciones', '.cotizacion.estado.cambiado', refreshPendingCount);
            ws.subscribe('notifications', '.new-notification', refreshPendingCount);
            ws.subscribe('notifications', '.notifications-marked-read', refreshPendingCount);

            sidebarNotificationsRealtimeBound = true;
        } catch (_) {
            setTimeout(setupSidebarNotificationsRealtime, 500);
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Actualizar contador de cotizaciones pendientes para aprobador
    updateCotizacionesPendientesAprobador();
    setupSidebarNotificationsRealtime();
});

/**
 * Escuchar eventos de actualización desde otros tabs/ventanas
 */
window.addEventListener('storage', function(event) {
    if (event.key === 'cotizacionesUpdated') {

        updateCotizacionesPendientesAprobador();
    }
});
