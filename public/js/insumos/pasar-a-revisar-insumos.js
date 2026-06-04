/**
 * MÓDULO: Pasar a Revisar
 * Gestiona el modal para pasar recibos a revisión de asesor
 * 
 * Funciones exportadas:
 * - abrirModalPasarRevisar(reciboId, pedidoId)
 * - cerrarModalPasarRevisar()
 */

let pasarARevisarInitialized = false;

function abrirModalPasarRevisar(reciboId, pedidoId) {
    const modal = document.getElementById('modalPasarRevisar');
    if (!modal) {
        console.error('Modal no encontrado');
        return;
    }
    
    // Actualizar datos en el modal
    const reciboIdField = document.getElementById('reciboIdPasarRevisar');
    const pedidoIdField = document.getElementById('pedidoIdPasarRevisar');
    const form = document.getElementById('formPasarRevisar');
    const counter = document.getElementById('contadorPasarRevisar');
    
    if (reciboIdField) reciboIdField.value = reciboId;
    if (pedidoIdField) pedidoIdField.value = pedidoId;
    if (form) form.reset();
    if (counter) counter.textContent = '0';
    
    // Mostrar modal
    modal.style.display = 'flex';
}

function cerrarModalPasarRevisar() {
    const modal = document.getElementById('modalPasarRevisar');
    if (modal) {
        modal.style.display = 'none';
    }
}

function initPasarARevisarInsumos() {
    if (pasarARevisarInitialized) return;
    pasarARevisarInitialized = true;

    window.insumosHandlers = window.insumosHandlers || {};
    window.insumosHandlers.pasarARevisar = {
        abrirModalPasarRevisar,
        cerrarModalPasarRevisar,
    };

    /**
     * Abre el modal para pasar a revisar
     */
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
            cerrarModalPasarRevisar();
        }

        // Cerrar modal con botón cerrar
        if (e.target.getAttribute('data-insumos-action') === 'pasar-revisar-close') {
            e.preventDefault();
            cerrarModalPasarRevisar();
        }
    });

    /**
     * Manejar envío del formulario
     */
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.getAttribute('data-insumos-action') === 'pasar-revisar-submit') {
            e.preventDefault();
            if (typeof confirmarPasarRevisar === 'function') {
                confirmarPasarRevisar(e);
            } else if (window.insumosHandlers?.statusActions?.confirmarPasarRevisar) {
                window.insumosHandlers.statusActions.confirmarPasarRevisar(e);
            }
        }
    });

}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPasarARevisarInsumos);
} else {
    initPasarARevisarInsumos();
}
