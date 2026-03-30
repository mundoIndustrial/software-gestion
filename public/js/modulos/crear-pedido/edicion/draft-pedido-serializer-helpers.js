(function() {
    'use strict';

    // Compatibilidad hacia atrás: este archivo ya no define lógica propia.
    // La fuente de verdad quedó en draft-pedido-serializer.js para evitar
    // divergencias entre serializadores.
    if (window.DraftPedidoSerializer) {
        if (typeof window.sincronizarPrendaModalAntesDeGuardarBorrador !== 'function'
            && typeof window.DraftPedidoSerializer.sincronizarPrendaModalAntesDeGuardarBorrador === 'function') {
            window.sincronizarPrendaModalAntesDeGuardarBorrador =
                window.DraftPedidoSerializer.sincronizarPrendaModalAntesDeGuardarBorrador;
        }

        if (typeof window.serializarPrendaExistenteParaBorrador !== 'function'
            && typeof window.DraftPedidoSerializer.serializarPrendaExistenteParaBorrador === 'function') {
            window.serializarPrendaExistenteParaBorrador =
                window.DraftPedidoSerializer.serializarPrendaExistenteParaBorrador;
        }

        console.debug('[draft-pedido-serializer-helpers] Shim activo: usando DraftPedidoSerializer como fuente única.');
        return;
    }

    console.warn('[draft-pedido-serializer-helpers] DraftPedidoSerializer no está disponible; no se sobreescriben funciones globales.');
})();
