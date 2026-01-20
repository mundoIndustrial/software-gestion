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
                console.log(` Badge actualizado: ${data.count} cotizaciones pendientes`);
            } else {
                badgeElement.style.display = 'none';
                console.log(' No hay cotizaciones pendientes');
            }
        })
        .catch(error => {
            console.error('Error al obtener contador de cotizaciones:', error);
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
        console.log('📢 Evento de actualización recibido desde otra ventana');
        updateCotizacionesPendientesAprobador();
    }
});
