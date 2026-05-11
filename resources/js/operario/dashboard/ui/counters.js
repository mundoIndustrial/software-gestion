export function actualizarContadorTarjetas() {
    const contador = document.getElementById('ordenesCount');
    const tarjetas = document.querySelectorAll('.orden-card-simple:not([style*="display: none"])');
    const total = tarjetas.length;

    if (contador) {
        contador.textContent = String(total);
    }

    // Actualizar el contador de la pestaña de Pendientes si existe
    // (Solo si estamos en la pestaña de pendientes, el total de tarjetas coincide con el total de pendientes)
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'pendientes';
    
    if (tab === 'pendientes') {
        const contadorPend = document.getElementById('contadorPendientes');
        if (contadorPend) {
            contadorPend.textContent = String(total);
        }
    }

    window.__updateDashboardPagination?.();
}

/**
 * Incrementa el contador de la pestaña de completados sin recargar
 */
export function incrementarContadorCompletados() {
    const contadorComp = document.getElementById('contadorCompletados');
    if (contadorComp) {
        const actual = parseInt(contadorComp.textContent) || 0;
        contadorComp.textContent = String(actual + 1);
    }
}

