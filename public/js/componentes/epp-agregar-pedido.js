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

        // 2. Cerrar el modal actual (lista de EPP o "sin EPP")
        Swal.close();

        // 3. Cargar módulos EPP si no están cargados
        try {
            if (window.EPPManagerLoader && !window.EPPManagerLoader.isLoaded()) {
                console.log('[EPP-Agregar] Cargando módulos EPP...');
                
                // Mostrar indicador de carga
                Swal.fire({
                    title: 'Cargando...',
                    text: 'Preparando el módulo de EPP',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });

                await window.EPPManagerLoader.load();
                console.log('[EPP-Agregar] Módulos EPP cargados correctamente');
                
                Swal.close();
                
                // Pequeña espera para que los módulos se inicialicen
                await new Promise(resolve => setTimeout(resolve, 300));
            } else if (!window.EPPManagerLoader) {
                console.error('[EPP-Agregar] EPPManagerLoader no disponible');
                Swal.fire('Error', 'El módulo de EPP no está disponible', 'error');
                return;
            }
        } catch (error) {
            console.error('[EPP-Agregar] Error cargando módulos EPP:', error);
            Swal.close();
            Swal.fire('Error', 'No se pudieron cargar los módulos de EPP: ' + error.message, 'error');
            return;
        }

        // 4. Configurar el estado para agregar EPP a pedido existente
        if (window.eppStateManager) {
            // Resetear estado previo
            window.eppStateManager.resetear();
            
            // Configurar pedido ID para que _guardarEPPDesdeDB sepa a qué pedido agregar
            window.eppStateManager.setPedidoId(pedidoId);
            
            // Marcar como "desde BD" sin pedidoEppId → esto hace que guardarEPP use CREATE
            window.eppStateManager.iniciarEdicion(null, true, null);
            
            console.log('[EPP-Agregar] Estado configurado:', {
                pedidoId: window.eppStateManager.getPedidoId(),
                editandoDesdeDB: window.eppStateManager.isEditandoDesdeDB(),
                pedidoEppId: window.eppStateManager.getPedidoEppId()
            });
        } else {
            console.error('[EPP-Agregar] eppStateManager no disponible');
            Swal.fire('Error', 'El gestor de estado de EPP no está disponible', 'error');
            return;
        }

        // 5. Abrir modal de agregar EPP
        if (typeof abrirModalAgregarEPP === 'function') {
            console.log('[EPP-Agregar] Abriendo modal de agregar EPP');
            abrirModalAgregarEPP();
        } else if (window.eppService && typeof window.eppService.abrirModalAgregar === 'function') {
            console.log('[EPP-Agregar] Abriendo modal vía eppService');
            window.eppService.abrirModalAgregar();
        } else {
            console.error('[EPP-Agregar] No se encontró función para abrir modal de EPP');
            Swal.fire('Error', 'No se pudo abrir el formulario de EPP', 'error');
            return;
        }

        // 6. Interceptar el guardarEPP para asegurar que use el flujo correcto
        _interceptarGuardarEPP(pedidoId);
    };

    /**
     * Interceptar la función guardarEPP del eppService para asegurar
     * que al agregar EPP a pedido existente se use el flujo de API correcto.
     * 
     * El flujo normal del eppService.guardarEPP() detecta isEditandoDesdeDB()
     * y va por _guardarEPPDesdeDB → si no hay pedidoEppId → CREATE vía API.
     * 
     * Esta interceptación es un safety net: si el estado se pierde por alguna razón,
     * restauramos el pedidoId antes de guardar.
     */
    function _interceptarGuardarEPP(pedidoId) {
        // Esperar a que eppService esté disponible
        const checkInterval = setInterval(() => {
            if (window.eppService && window.eppService.guardarEPP) {
                clearInterval(checkInterval);

                const originalGuardarEPP = window.eppService.guardarEPP.bind(window.eppService);

                window.eppService.guardarEPP = async function() {
                    // Asegurar que el pedidoId está configurado
                    if (window.eppStateManager && !window.eppStateManager.getPedidoId()) {
                        console.log('[EPP-Agregar] Restaurando pedidoId antes de guardar:', pedidoId);
                        window.eppStateManager.setPedidoId(pedidoId);
                    }

                    // Asegurar que está en modo "desde BD" para usar la API
                    if (window.eppStateManager && !window.eppStateManager.isEditandoDesdeDB()) {
                        console.log('[EPP-Agregar] Activando modo desdeDB para guardar vía API');
                        window.eppStateManager.iniciarEdicion(
                            window.eppStateManager.getEppIdSeleccionado(),
                            true,
                            null
                        );
                    }

                    console.log('[EPP-Agregar] Ejecutando guardarEPP con estado:', {
                        pedidoId: window.eppStateManager?.getPedidoId(),
                        eppId: window.eppStateManager?.getEppIdSeleccionado(),
                        editandoDesdeDB: window.eppStateManager?.isEditandoDesdeDB(),
                        pedidoEppId: window.eppStateManager?.getPedidoEppId()
                    });

                    // Llamar al guardarEPP original
                    await originalGuardarEPP();

                    // Restaurar la función original después de guardar
                    window.eppService.guardarEPP = originalGuardarEPP;
                };

                console.log('[EPP-Agregar] guardarEPP interceptado exitosamente');
            }
        }, 200);

        // Timeout de seguridad: si después de 10s no se interceptó, limpiar
        setTimeout(() => {
            clearInterval(checkInterval);
        }, 10000);
    }

    console.log('[EPP-Agregar] Módulo epp-agregar-pedido.js cargado');
})();
