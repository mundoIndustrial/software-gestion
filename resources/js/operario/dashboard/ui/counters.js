export function actualizarContadorTarjetas() {
    const contador = document.getElementById('ordenesCount');
    if (contador) {
        const tarjetas = document.querySelectorAll('.orden-card-simple:not([style*="display: none"])');
        contador.textContent = String(tarjetas.length);
    }
    window.__updateDashboardPagination?.();
}
