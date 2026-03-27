<!-- Modal para agregar una nueva prenda -->
<script>
    /**
     * Abrir modal para agregar una nueva prenda
     * Usa el modal completo de crear prenda
     */
    function abrirAgregarPrenda() {
        // Cerrar el SweetAlert actual para que el modal aparezca correctamente
        Swal.close();
        
        // Esperar a que se cierre el SweetAlert
        setTimeout(() => {
            // Usar la función global que está disponible en prendas-wrappers.js
            if (typeof window.abrirModalPrendaNueva === 'function') {
                console.log('[MODAL-AGREGAR-PRENDA] Delegando a window.abrirModalPrendaNueva()');
                window.abrirModalPrendaNueva();
            } else {
                UI.error('Error', 'No se pudo abrir el modal de agregar prenda');
            }
        }, 100);
    }
</script>




