/**
 * EPP Agregar a Pedido Existente
 * 
 * Módulo independiente que permite agregar EPPs a pedidos existentes
 * desde el modal de edición de pedido.
 * 
 * Flujo:
 * 1. Usuario abre "Editar EPP" → ve lista (o "Sin EPP")
 * 2. Clic en "＋ Agregar EPP" → agregarNuevoEPPAPedido()
 * 3. Se cargan módulos EPP (lazy) → se abre modal de agregar EPP
 * 4. El eppService guarda vía API: POST /api/pedidos/{id}/epp/agregar
 * 5. Se recarga la página para reflejar los cambios
 * 
 * Dependencias:
 * - EPPManagerLoader (lazy loader de módulos EPP)
 * - eppStateManager (gestión de estado)
 * - eppService (orquestador principal)
 * - window.datosEdicionPedido (datos del pedido en edición)
 */

(function() {
    'use strict';

    /**
     * Función principal: Agregar un nuevo EPP al pedido en edición
     * Se llama desde los botones "＋ Agregar EPP" en modal-editar-epp.blade.php
     */
    window.agregarNuevoEPPAPedido = async function() {
        console.log('[EPP-Agregar] Iniciando flujo de agregar EPP a pedido existente');

        // 1. Validar que hay un pedido en edición
        const datos = window.datosEdicionPedido;
        if (!datos) {
            console.error('[EPP-Agregar] No hay datosEdicionPedido');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No hay un pedido en edición', 'error');
            }
            return;
        }

        const pedidoId = datos.id || datos.numero_pedido;
        if (!pedidoId) {
            console.error('[EPP-Agregar] No se pudo determinar el ID del pedido');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No se pudo determinar el ID del pedido', 'error');
            }
            return;
        }

        console.log('[EPP-Agregar] Pedido ID:', pedidoId);

        // 2. Cerrar el modal actual de Swal (lista de EPP o "sin EPP")
        Swal.close();

        // 3. Marcar flag para que finalizarAgregarEPP() guarde vía API
        window.__EPP_AGREGAR_PEDIDO_EXISTENTE__ = pedidoId;
        console.log('[EPP-Agregar] Flag __EPP_AGREGAR_PEDIDO_EXISTENTE__ =', pedidoId);

        // 4. Abrir el mismo modal de agregar EPP (que ya está en el DOM via blade)
        if (typeof abrirModalAgregarEPP === 'function') {
            console.log('[EPP-Agregar] Abriendo modalAgregarEPP directamente');
            abrirModalAgregarEPP();
        } else {
            console.error('[EPP-Agregar] abrirModalAgregarEPP no disponible');
            Swal.fire('Error', 'No se pudo abrir el formulario de EPP', 'error');
            window.__EPP_AGREGAR_PEDIDO_EXISTENTE__ = null;
            return;
        }
    };

    // _interceptarGuardarEPP ya no es necesario.
    // finalizarAgregarEPP() detecta __EPP_AGREGAR_PEDIDO_EXISTENTE__ y guarda vía API.

    console.log('[EPP-Agregar] Módulo epp-agregar-pedido.js cargado');
})();
