export function actualizarContadorTarjetas() {
    const contador = document.querySelector('.ordenes-count');
    if (contador) {
        const tarjetas = document.querySelectorAll('.orden-card-simple:not([style*="display: none"])');
        contador.textContent = String(tarjetas.length);
    }
}
