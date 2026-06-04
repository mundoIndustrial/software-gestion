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

        // Manejar click en botón confirmar directamente
        if (e.target.id === 'btnConfirmarPasarRevisar' || e.target.closest('#btnConfirmarPasarRevisar')) {
            e.preventDefault();
            e.stopPropagation();
            handleConfirmarPasarRevisar();
        }
    });

    /**
     * Manejar envío del formulario por evento submit
     */
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.getAttribute('data-insumos-action') === 'pasar-revisar-submit') {
            e.preventDefault();
            handleConfirmarPasarRevisar();
        }
    });

    /**
     * Manejador centralizado para confirmar pasar a revisar
     */
    function handleConfirmarPasarRevisar() {
        const motivo = document.getElementById('motivoPasarRevisar').value;
        
        // Validar motivo
        if (!motivo || motivo.trim().length < 10) {
            alert('Por favor ingresa un motivo de al menos 10 caracteres');
            return;
        }

        // Llamar función de confirmación
        if (typeof confirmarPasarRevisar === 'function') {
            const event = new Event('submit', { cancelable: true });
            event.preventDefault = () => {};
            confirmarPasarRevisar(event);
        } else if (window.insumosHandlers?.statusActions?.confirmarPasarRevisar) {
            const event = new Event('submit', { cancelable: true });
            event.preventDefault = () => {};
            window.insumosHandlers.statusActions.confirmarPasarRevisar(event);
        } else {
            alert('Error: Handler no disponible');
        }
    }

}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPasarARevisarInsumos);
} else {
    initPasarARevisarInsumos();
}
