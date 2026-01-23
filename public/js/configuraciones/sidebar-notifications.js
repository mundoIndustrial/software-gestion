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
document.addEventListener('DOMContentLoaded', function() {
    // Actualizar contador de cotizaciones pendientes para aprobador
    updateCotizacionesPendientesAprobador();
    
    // Actualizar cada 30 segundos
    setInterval(updateCotizacionesPendientesAprobador, 30000);
});

/**
 * Escuchar eventos de actualización desde otros tabs/ventanas
 */
window.addEventListener('storage', function(event) {
    if (event.key === 'cotizacionesUpdated') {

        updateCotizacionesPendientesAprobador();
    }
});
