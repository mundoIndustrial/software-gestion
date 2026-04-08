export function initGlobalModalClosers() {
    // Cerrar modales al hacer click fuera
    window.addEventListener('click', function (event) {
        const modalNovedad = document.getElementById('modalNovedad');
        const modalCostura = document.getElementById('modalCostura');
        const modalMensaje = document.getElementById('modalMensaje');

        if (modalNovedad && event.target === modalNovedad) {
            if (typeof window.cerrarModalNovedad === 'function') {
                window.cerrarModalNovedad();
            }
        }
        if (modalCostura && event.target === modalCostura) {
            if (typeof window.cerrarModalCostura === 'function') {
                window.cerrarModalCostura();
            }
        }
        if (modalMensaje && event.target === modalMensaje) {
            if (typeof window.cerrarModalMensaje === 'function') {
                window.cerrarModalMensaje();
            }
        }
    });

    // Cerrar drawers al hacer click fuera
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.mobile-actions-toggle') && !e.target.closest('.mobile-actions-drawer')) {
            document.querySelectorAll('.mobile-actions-drawer.active').forEach((d) => {
                d.classList.remove('active');
            });
            document.querySelectorAll('.mobile-actions-toggle.active').forEach((btn) => {
                btn.classList.remove('active');
            });
        }
    });
}
