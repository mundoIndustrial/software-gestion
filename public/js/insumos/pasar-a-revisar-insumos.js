/**
 * MÓDULO: Pasar a Revisar
 * Gestiona el modal para pasar recibos a revisión de asesor
 * 
 * Funciones exportadas:
 * - abrirModalPasarRevisar(reciboId, pedidoId)
 * - cerrarModalPasarRevisar()
 */

document.addEventListener('DOMContentLoaded', function() {
    /**
     * Abre el modal para pasar a revisar
     */
    window.abrirModalPasarRevisar = function(reciboId, pedidoId) {
        const modal = document.getElementById('modalPasarRevisar');
        if (!modal) {
            console.error('Modal no encontrado');
            return;
        }
        
        // Actualizar datos en el modal
        document.getElementById('reciboIdPasarRevisar').value = reciboId;
        document.getElementById('pedidoIdPasarRevisar').value = pedidoId;
        document.getElementById('formPasarRevisar').reset();
        document.getElementById('contadorPasarRevisar').textContent = '0';
        
        // Mostrar modal
        modal.style.display = 'flex';
    };

    /**
     * Cierra el modal de pasar a revisar
     */
    window.cerrarModalPasarRevisar = function() {
        const modal = document.getElementById('modalPasarRevisar');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    /**
     * Actualizar contador de caracteres en tiempo real
     */
    document.addEventListener('input', function(e) {
        if (e.target.id === 'motivoPasarRevisar') {
            const contador = document.getElementById('contadorPasarRevisar');
            if (contador) {
                contador.textContent = e.target.value.length;
            }
        }
    });

    /**
     * Cerrar modal al hacer clic fuera (en el overlay)
     */
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('modalPasarRevisar');
        if (modal && e.target === modal) {
            window.cerrarModalPasarRevisar();
        }
    });

    // Auto-inicializar si el documento ya está cargado
    if (document.readyState !== 'loading') {
        // Ya está cargado
    }
});
